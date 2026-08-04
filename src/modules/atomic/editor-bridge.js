/* eslint-env browser */

import { disposeAll } from './editor-bridge/disposables';
import { FEATURES } from './editor-bridge/features';
import { registerResponsiveSection } from './responsive-section';
import { registerAaeElementControls } from './element-controls';
import { registerMaskStyleSection } from './style-sections/mask';
import { startCardBranding } from './widget-panel/card-branding';
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
import backgroundVideoSection from './extensions/background-video/config';
import customCssSection from './extensions/custom-css/config';
import nestedSliderSection from './extensions/nested-slider/config';

/* ---------------------------------------------------------------------------
 * Editor-only crash guard for Elementor v4's colour-picker (MUI Popover).
 *
 * Opening the Style-tab colour picker and interacting with it can throw
 * `NotFoundError: Failed to execute 'removeChild' … not a child` from deep
 * inside react-dom's commit phase (the popover is a React portal; React tries
 * to remove a portal node the DOM no longer parents). A console diagnostic
 * confirmed the crash stack is 100% react-dom with NO AAE frame and NO AAE
 * document command running while the popover is open — i.e. it originates in
 * Elementor/MUI core, not this plugin. Until core fixes it, make removeChild /
 * insertBefore no-op instead of throwing when the node isn't actually parented
 * by the target. This is the well-known React #11538 guard; it loads only in
 * the editor (this bundle is editor-only) and never ships to the frontend.
 * ------------------------------------------------------------------------- */
( function guardReactPortalDomOps() {
	if ( typeof Node !== 'function' || ! Node.prototype || Node.prototype.__aaeDomGuard ) {
		return;
	}
	Node.prototype.__aaeDomGuard = true;

	/* ONLY removeChild is guarded. A no-op removeChild is safe: it fires only
	 * when the node is already not parented by the target (the exact crash
	 * case), so the intended removal is effectively already done. We do NOT
	 * guard insertBefore — a no-op insert would DROP a node React needs, which
	 * broke selection/style rendering. The reported crash was removeChild. */
	const originalRemoveChild = Node.prototype.removeChild;
	Node.prototype.removeChild = function ( child ) {
		if ( child && child.parentNode !== this ) {
			return child;
		}
		return originalRemoveChild.apply( this, arguments );
	};
} )();

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

// Widget-picker card branding — runs unconditionally, independent of any
// one extension's section registration. See widget-panel/card-branding.js.
startCardBranding();

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
registerResponsiveSection( backgroundVideoSection );
registerResponsiveSection( customCssSection );
registerResponsiveSection( nestedSliderSection );

// Native Elementor element-controls (e.g. the slider's "Slides" list). These
// register into Elementor's shared controlsRegistry, separate from the
// ResponsiveSection mechanism above.
registerAaeElementControls();

// AAE Mask — a Style-tab section backed by real atomic style props
// (inc/Atomic/Mask/). Registers its canvas transformer at the same time.
registerMaskStyleSection();
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
import { startSlideSelectNav } from './editor-bridge/slide-select-nav';
import { startSliderEditorPreview } from './editor-bridge/slider-editor-preview';
import { startAutoPreset } from './editor-bridge/auto-preset';
import { startFormGuards } from './editor-bridge/form-guards';
import { startAdvancedHeadingInline } from './editor-bridge/advanced-heading-inline';

function bootstrap() {
	// `preview:loaded` fires on EVERY preview (re)load — switching documents,
	// responsive-mode changes, etc. Each bootstrap installs observers + heartbeats
	// + teardown callbacks; running it again without tearing down the previous run
	// left MULTIPLE live instances, and a stale instance's teardown/prune (bound to
	// the OLD preview window) would remove the current run's toggle button ~200ms
	// after it was injected → the button BLINKED. So dispose the previous run
	// first, giving us exactly one clean set of instances per preview load.
	disposeAll();

	// Selecting a slide (Structure panel, canvas, anywhere) drives the preview
	// slider to it — same as clicking its row in the panel's "Slides" list.
	startSlideSelectNav();

	// Loop Grid Slider has no query in the editor (one authored slide), so
	// duplicate that slide client-side for a realistic multi-up / effect preview.
	startSliderEditorPreview();

	// Apply a default preset the first time certain widgets (Loop Grid Slider) are
	// dropped, so they land styled instead of as a bare Post Image + Title card.
	startAutoPreset();

	// Save-time warnings: form without a Submit button / nested forms
	// (spec hard rules — warn, never block the save).
	startFormGuards();

	// Advanced Heading: click its text in the canvas to edit inline, with the
	// same floating format toolbar core gives e-paragraph plus a colour field.
	startAdvancedHeadingInline();
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

		// When an element is deleted in Elementor, it is removed from the DOM.
		// If we don't kill its ScrollTrigger, the GSAP markers will stay on screen forever.
		// A MutationObserver catches removed nodes so we can properly dispose of them.
		const observer = new win.MutationObserver((mutations) => {
			mutations.forEach((mutation) => {
				mutation.removedNodes.forEach((node) => {
					if (node.nodeType === 1) { // ELEMENT_NODE
						const targets = Array.from(node.querySelectorAll('[data-interaction-id]'));
						if (node.hasAttribute('data-interaction-id')) {
							targets.push(node);
						}
						targets.forEach(el => {
							if (win.aaeAtomicAnimations && typeof win.aaeAtomicAnimations.reset === 'function') {
								win.aaeAtomicAnimations.reset(el);
							}
						});
					}
				});
			});
		});
		observer.observe(win.document.body, { childList: true, subtree: true });

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
