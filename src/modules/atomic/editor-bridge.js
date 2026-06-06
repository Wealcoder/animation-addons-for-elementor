/* eslint-env browser */

import { disposeAll } from './editor-bridge/disposables';
import { FEATURES } from './editor-bridge/features';
import { registerResponsiveSection } from './responsive-section';
import regularAnimationSection from './extensions/regular-animation/config';
import textAnimationSection from './extensions/text-animation/config';
import parallaxSection from './extensions/parallax/config';
import imageAnimationSection from './extensions/image-animation/config';
import imageHoverSection from './extensions/image-hover/config';
import stickySection from './extensions/sticky/config';
import horizontalScrollAnimSection from './extensions/horizontal-scroll-anim/config';
import cursorHoverEffectSection from './extensions/cursor-hover-effect/config';
import mouseMoveEffectSection from './extensions/mouse-move-effect/config';
import advancetooltipSection from './extensions/advance-tooltip/config';
import tilt from './extensions/tilt/config';
import scrollTo from './extensions/scroll-to/config';
import wrapperLinkSection from './extensions/wrapper-link/config';
import customCssSection from './extensions/custom-css/config';
import './effects/wrapper-link/index';
/* =====================================================================
 * Responsive sections (one section per AAE extension)
 *
 * Each extension declares a config table of fields (bind, label, control
 * type, options, visibility predicate). registerResponsiveSection() adds
 * the table to the in-memory registry and lazily installs ONE shared
 * registerControlReplacement dispatcher. When Elementor renders the
 * placeholder Text_Control bound to an extension's Section_Anchor prop,
 * the dispatcher inspects propType.key, looks up the matching config,
 * and renders <ResponsiveSection> in place of the Text_Control row —
 * which renders one <ResponsiveRow> per (active-bp-visible) field.
 *
 * Adding a new section = one new extensions/<name>/config.js + one
 * import + one registerResponsiveSection() call below. The PHP side
 * supplies a Section_Anchor_Prop_Type subclass and a single anchor
 * Text_Control inside its Section::make().
 * =================================================================== */

registerResponsiveSection( regularAnimationSection );
registerResponsiveSection( textAnimationSection );
registerResponsiveSection( parallaxSection );
registerResponsiveSection( imageAnimationSection );
registerResponsiveSection( imageHoverSection );
registerResponsiveSection( stickySection );
registerResponsiveSection( horizontalScrollAnimSection );
registerResponsiveSection( cursorHoverEffectSection );
registerResponsiveSection( mouseMoveEffectSection );
registerResponsiveSection( advancetooltipSection );
registerResponsiveSection( tilt );
registerResponsiveSection( scrollTo );
registerResponsiveSection( wrapperLinkSection );
registerResponsiveSection( customCssSection );
/**
 * Animation Addons — Atomic Editor Bridge (entry)
 *
 * Two responsibilities, each implemented in its own module under
 * `editor-bridge/`:
 *
 *   1. Mirror atomic widget settings into preview-iframe DOM data-attrs
 *      live, as the user edits.            → settings-bridge + live-bridge
 *   2. Re-bind the runtime animation handler when elements re-render
 *      inside the iframe.                  → preview-pipe
 *
 * The "Play Now" button lives inside the responsive-section as a React
 * 'play-button' control — see responsive-section/inputs/PlayButtonInput.jsx.
 *
 * Adding a new widget/effect = add one entry to FEATURES in
 * `editor-bridge/features.js`. Everything else flows from that table.
 *
 * Cleanup-aware: every listener / observer / timer is tracked through the
 * disposables registry and torn down on document switch + beforeunload.
 */

/* =====================================================================
 * Bootstrap — idempotent. Tears down on document switch / unload.
 * =================================================================== */

import { getPreviewWindow } from './editor-bridge/helpers';
import { applySettingsToDom, applySettingsToDoms, replayInPreview } from './editor-bridge/settings-bridge';

let bootstrapped = false;

function bootstrap() {
	if (bootstrapped) {
		bootstrapped = false;
	}
	bootstrapped = true;
	
}

if (window.elementor && window.elementor.on) {
	window.elementor.on('preview:loaded', bootstrap);
} else {
	document.addEventListener('DOMContentLoaded', bootstrap);
}

// Tear down on page unload as a final safety net.
window.addEventListener('beforeunload', disposeAll);

// Expose for manual debugging / cleanup from console.
window.__aaeAtomicBridge = {
	disposeAll,
	getFeatures: () => FEATURES,
};

// Best approach for V4 Atomic Elements
import { getElements } from '@elementor/editor-elements';

window.elementor.on('document:loaded', () => {

	setTimeout(() => {
		
		const win = getPreviewWindow();
		if (!win || !win.aaeAtomicAnimations) return;

		const elements = getElements();
		let syncCount = 0;
		
		elements.forEach((element) => {
			const elType = element.model.get('elType');
			const widgetType = element.model.get('widgetType');
			
			// Get ALL settings from the element
			const allSettings = element.settings.toJSON();
			if(element.id == 'document'){
				return;
			}
			
			// Create a mock container that settings-bridge / featuresFor can read.
			// featuresFor() accesses container.model.get('widgetType'), so the
			// getter MUST live under `.model`, not at the top level.
			const mockContainer = {
				id: element.id,
				model: {
					get: (prop) => prop === 'elType' ? elType : (prop === 'widgetType' ? widgetType : undefined),
				},
				settings: {
					attributes: allSettings
				}
			};

			// Bulk sync without requiring the target DOM element to exist yet
			applySettingsToDoms(mockContainer);
			
			syncCount++;	
		
		});
		

		win.aaeAtomicAnimations.scan(win.document);
	}, 500);
});