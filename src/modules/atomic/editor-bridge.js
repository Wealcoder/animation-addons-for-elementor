/* eslint-env browser */

import { disposeAll } from './editor-bridge/disposables';
import { FEATURES } from './editor-bridge/features';
import { registerResponsiveSection } from './responsive-section';
import { registerAaeElementControls } from './element-controls';
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
import customCssSection from './extensions/custom-css/config';
import nestedSliderSection from './extensions/nested-slider/config';
import lightboxStyleSection from './extensions/lightbox-style/config';
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
registerResponsiveSection( customCssSection );
registerResponsiveSection( nestedSliderSection );
registerResponsiveSection( lightboxStyleSection );

// Native Elementor element-controls (e.g. the slider's "Slides" list). These
// register into Elementor's shared controlsRegistry, separate from the
// ResponsiveSection mechanism above.
registerAaeElementControls();
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

let bootstrapped = false;

function bootstrap() {
	if (bootstrapped) {
		bootstrapped = false;
	}
	bootstrapped = true;

	// Selecting a slide (Structure panel, canvas, anywhere) drives the preview
	// slider to it — same as clicking its row in the panel's "Slides" list.
	startSlideSelectNav();

	// Loop Grid Slider has no query in the editor (one authored slide), so
	// duplicate that slide client-side for a realistic multi-up / effect preview.
	startSliderEditorPreview();
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
