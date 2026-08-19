/* eslint-env browser */

import { disposeAll } from './editor-bridge/disposables';
import { FEATURES } from './editor-bridge/features';
import { registerResponsiveSection } from './responsive-section';
import { registerAaeElementControls } from './element-controls';
import { startNavMenuAutoSync } from './element-controls/NavItemsControl';
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
import menuSections from './extensions/menu-sections/config';

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
// WP Menu widget sections (not extensions) — one per CSS-variable-driven
// panel section, each attaching by anchor key to e-aae-a-menu only, the same
// way the Nested Slider panel does.
menuSections.forEach( registerResponsiveSection );

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
import { startNavCompanionLifecycle } from './editor-bridge/nav-companion-lifecycle';

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

	// Delete a Nav → its Mobile Nav companion goes with it. Must live HERE and
	// not in the Nav's panel control: a panel control unmounts the moment the
	// Nav is deleted, so its sweep can never run for that Nav.
	startNavCompanionLifecycle();

	// Navs imported from a WordPress menu re-sync themselves on document open,
	// so the "Update from WordPress" button is only needed for a deliberate
	// reset. Structure only — it never overwrites a label you edited here.
	startNavMenuAutoSync();

	// NOTE — Advanced Heading used to install a hand-rolled contenteditable
	// toolbar here (startAdvancedHeadingInline, deleted 2026-08-04). Its text is
	// now an html-v3 prop edited through core's Inline_Editing_Control in the
	// panel, so there is nothing to boot. Do not reinstate a canvas toolbar
	// without reading the widget's class docblock first: anything that writes
	// `class`/`style` into the content is stripped by wp_kses on save.
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
		//
		// DANGER — a `removedNodes` entry is NOT proof of a deletion, and reading it
		// as one froze the editor solid. Elementor hides an element from the
		// Structure panel's eye by WRAPPING it, not by deleting it:
		//
		//     this.$el.wrap( '<div data-type="hide-atomic-widget" style="display: none" />' )
		//     — elementor/assets/dev/js/editor/elements/views/base.js, toggleVisibilityClass()
		//
		// jQuery's `.wrap()` detaches the node and re-inserts it, so it arrives here
		// as a removal. We then reset every `[data-interaction-id]` under it (387 on
		// the aae-v4-widgets fixture, from ONE click) — and because a reset itself
		// re-parents nodes, each one comes back through this callback. Measured gain
		// was ~5 resets per reset, i.e. divergent: capping real resets at 500 still
		// produced 2990 calls, at 1000 it produced 4979, uncapped it never finished.
		// The main thread never yields, so there is no error and nothing in the
		// console — the tab is simply dead. Three guards, all load-bearing:
		//
		//   1. `isConnected` — still in the document means re-parented, not deleted.
		//      This alone takes the fixture from "never terminates" to 388 skipped
		//      calls and a responsive editor. It is also just correct: a wrapped
		//      element still needs its ScrollTrigger.
		//   2. `seen` — removed subtrees overlap, so the same element is reachable
		//      from several removed ancestors and would be reset repeatedly.
		//   3. `sweeping` — reset mutates the DOM, and a MutationObserver callback
		//      is a microtask: re-entering here never lets the queue drain.
		//
		// Repro/regression: E:\Local Testing\probe-eye-proof.mjs (control/count/
		// trace/noop/fix modes) and probe-eye-stack.mjs (CDP stack of the hung thread).
		// Re-apply every element's config after Elementor repaints the canvas.
		//
		// Elementor re-renders a widget's DOM on ANY settings change, and the new
		// node is a different element: the runtime's bound flag, its `__aae*`
		// handles and the injected Custom CSS <style> all belonged to the node that
		// was just thrown away. Nothing put them back — the maps were populated
		// once, by the bulk sync below at editor load — so changing any unrelated
		// field made Custom CSS (and every other effect) silently stop applying
		// until the editor was reloaded.
		//
		// DEBOUNCED AND SCHEDULED OUT OF THE CALLBACK, deliberately. A
		// MutationObserver callback is a microtask, and re-syncing inline would
		// mutate the very tree being reported and feed itself — that is precisely
		// the shape that once froze the editor solid with an empty console (see the
		// removal-sweep note above). A timer breaks the loop: the work lands in a
		// later task, and repaint storms coalesce into ONE resync.
		let resyncTimer = null;
		const scheduleResync = () => {
			if (resyncTimer) {
				clearTimeout(resyncTimer);
			}

			resyncTimer = setTimeout(() => {
				resyncTimer = null;
				syncAllElements();
			}, 150);
		};

		let sweeping = false;
		const observer = new win.MutationObserver((mutations) => {
			if (sweeping) return;
			sweeping = true;
			try {
				const seen = new Set();
				let sawAdded = false;

				mutations.forEach((mutation) => {
					// An ADDED node carrying an interaction id is a re-render landing.
					// Only a flag is set here; the work happens in the timer above.
					if (!sawAdded) {
						mutation.addedNodes.forEach((node) => {
							if (sawAdded || node.nodeType !== 1) return;
							if (
								node.hasAttribute('data-interaction-id') ||
								node.querySelector('[data-interaction-id]')
							) {
								sawAdded = true;
							}
						});
					}

					mutation.removedNodes.forEach((node) => {
						if (node.nodeType !== 1) return; // ELEMENT_NODE only
						if (node.isConnected) return;    // re-parented, not deleted
						const targets = Array.from(node.querySelectorAll('[data-interaction-id]'));
						if (node.hasAttribute('data-interaction-id')) {
							targets.push(node);
						}
						targets.forEach(el => {
							if (seen.has(el)) return;
							seen.add(el);
							if (win.aaeAtomicAnimations && typeof win.aaeAtomicAnimations.reset === 'function') {
								win.aaeAtomicAnimations.reset(el);
							}
						});
					});
				});

				if (sawAdded) {
					scheduleResync();
				}
			} finally {
				sweeping = false;
			}
		});

		// One observer per preview window, always. `document:loaded` fires again on
		// every document switch, and the preview iframe is NOT reloaded for that —
		// so without this each switch stacked another live observer on the same
		// body, multiplying the fan-out above. Keyed on the preview window rather
		// than tracked through disposables/`track()` deliberately: disposeAll()
		// runs from `preview:loaded`, and this observer is created from
		// `document:loaded`, so the two orderings are not guaranteed and a
		// tracked teardown could dispose the observer moments after it was
		// installed — silently switching the cleanup off. A stale observer from a
		// previous preview document dies with its window.
		if (win.__aaeRemovalObserver) {
			try { win.__aaeRemovalObserver.disconnect(); } catch (_) {}
		}
		win.__aaeRemovalObserver = observer;
		observer.observe(win.document.body, { childList: true, subtree: true });

		// Declared as a FUNCTION, not a const arrow: scheduleResync() above closes
		// over it and is defined earlier in the file, so it relies on hoisting.
		function syncAllElements() {
			const elements = getElements();

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
			});

			// Rebuilding the maps is only half of it — scan() is what binds the
			// freshly-rendered DOM nodes to them. A re-render produces new elements
			// with no bound flag, so this re-injects the Custom CSS <style> and
			// re-arms every other effect on them.
			try {
				win.aaeAtomicAnimations?.scan(win.document);
			} catch (_) {
				// A resync racing a preview teardown is not worth breaking the editor
				// over; the next one will pick it up.
			}
		}

		syncAllElements();
	}, 500);
});
