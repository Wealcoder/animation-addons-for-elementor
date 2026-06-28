/* eslint-env browser */

import * as React from 'react';
import { registerControlReplacement, useBoundProp } from '@elementor/editor-controls';

import { addSection, getSection, getAllAnchorKeys } from './registry';
import { ResponsiveSection } from './ResponsiveSection';
import { startSectionBranding } from './section-branding';

/**
 * Public API: register a responsive section.
 *
 * config = {
 *   anchorKey: 'aae-section-aae-animation',   // matches PHP Section_Anchor_Prop_Type::get_key()
 *   fields:    [                               // table of rows to render
 *     {
 *       bind:        'aae_anim_effect',        // matches the PHP prop key
 *       label:       'Animation',
 *       control:     'select',                 // 'select' | 'text' | 'number' | 'switch'
 *       options?:    [{ value, label }, ...],  // for select
 *       placeholder?: 'placeholder',
 *       min?, max?, step?,                     // for number
 *       when?:       (settings, activeBp) => boolean,   // visibility predicate
 *     },
 *     ...
 *   ],
 * }
 *
 * Sections are wired by:
 *   1. PHP Schema.php registers ONE Section_Anchor_Prop_Type subclass with
 *      get_key() === anchorKey.
 *   2. PHP Controls.php places ONE control bound to the anchor prop inside
 *      its Section::make().
 *   3. JS calls registerResponsiveSection(config) at editor bootstrap.
 *
 * Adds the config to the registry. Wiring (the single shared
 * registerControlReplacement) is done lazily on the first call via
 * bootstrapDispatcher() — registering N times for N sections would have
 * each replacement fire on every panel render, multiplying work and risking
 * order-dependent shadowing.
 */
export function registerResponsiveSection(config) {
	addSection(config);
	bootstrapDispatcher();
	// Global branding observer — brands ALL AAE section headers (logo + lock/
	// try), expanded or not. Idempotent; only the first call wires it up.
	startSectionBranding();
}

/* ---------- one-time shared dispatcher ---------- */

let bootstrapped = false;

function bootstrapDispatcher() {
	if (bootstrapped) return;
	bootstrapped = true;

	registerControlReplacement({
		id: 'aae-responsive-section:dispatcher',
		component: SectionDispatcher,
		condition: () => true,
	});
}

/**
 * Inside the dispatcher: propType IS visible via useBoundProp(). Read it,
 * look up the matching section config, and render <ResponsiveSection>. If
 * the bound prop isn't one of ours (the liberal null-condition catch-all
 * matched something else), defer to OriginalControl so native panel rows
 * still render normally.
 */
function SectionDispatcher(props) {
	const { propType } = useBoundProp();
	const anchorKey = propType && propType.key;
	const config = anchorKey ? getSection(anchorKey) : null;

	if (!config) {
		const { OriginalControl } = props;
		if (OriginalControl) return <OriginalControl {...props} />;
		return null;
	}

	return <ResponsiveSection config={config} />;
}
