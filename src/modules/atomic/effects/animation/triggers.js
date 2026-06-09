/* eslint-env browser */

/**
 * Shared trigger dispatcher for every animation kind.
 *
 * Each kind owns its own trigger-name vocabulary (regular and text use:
 * on_scroll / on_page_load / play_with_scroll / mouseover / click). All
 * vocabularies map onto the same handful of wiring patterns — this file
 * is the single home for those patterns so new kinds (image, tilt,
 * parallax…) never re-implement them.
 *
 * Usage from a kind:
 *
 *   wireTrigger({
 *     el,
 *     mode: modeFor(config.trigger),
 *     play:          () => playRegular(el, config),
 *     buildScrubbed: () => buildScrubbedRegular(el, config),  // optional, scrub-only
 *   });
 *
 * Canonical modes (every kind maps its raw trigger name to one of these):
 *   - 'page-load'   → fire play() immediately
 *   - 'hover'       → fire on mouseenter
 *   - 'click'       → fire on click
 *   - 'scroll-tied' → ScrollTrigger replay (toggleActions, runs once forward
 *                     per scroll-down entry). Plays via the play() callback.
 *   - 'scrub'       → ScrollTrigger scrub (tween progress follows scroll
 *                     position; reverses on scroll-up). Requires the kind
 *                     to provide `buildScrubbed` that returns a PAUSED tween.
 *   - 'in-view'     → ScrollTrigger once:true on enter (DEFAULT fallback;
 *                     IntersectionObserver fallback if ScrollTrigger absent).
 *
 * Anything unrecognised falls through to in-view, which is the universal
 * safe default.
 */
const { getScrollTrigger } = window.AAEADDON;

const DISPOSE_KEY = '__aaeTriggerDispose';

const TRIGGER_MODES = {
	on_scroll: 'scroll-tied',
	play_with_scroll: 'scrub',
	on_page_load: 'page-load',
	mouseover: 'hover',
	click: 'click',
};

export function modeFor(trigger) {
	return TRIGGER_MODES[trigger] || 'in-view';
}

export function resolveTriggerEl(mode, selector, config) {

	if (mode === 'hover' || mode === 'click') {
		if (config.triggerSelector && config.triggerSelector !== '') {
			selector = config.triggerSelector;
		}
	}

	if ((mode !== 'hover' && mode !== 'click') || !selector) return undefined;
	// check selectoor is html element or not
	if (selector instanceof HTMLElement) return selector;
	return document.querySelector(selector) || undefined;
}

/**
 * Tear down whatever wireTrigger previously installed on this element —
 * DOM listeners, ScrollTrigger instances, IntersectionObservers. Safe to
 * call on elements with no prior bind (no-op). Called both internally on
 * every wireTrigger invocation AND by the runtime's rebind() path so the
 * editor can rebind continuously without stacking handlers.
 */
export function cleanupTriggerOn(el) {
	if (!el) return;
	const dispose = el[DISPOSE_KEY];
	if (typeof dispose === 'function') {
		try { dispose(); } catch (_) { /* never let cleanup throw */ }
	}
	el[DISPOSE_KEY] = null;
}

export function wireTrigger({ el, mode, play, buildScrubbed, triggerEl, markers, config }) {
	// Always clean up the previous wiring first — in the editor, settings
	// changes fire rebind() which can re-call us on the same element many
	// times. Without this, listeners and ScrollTriggers stack up.
	cleanupTriggerOn(el);
	if (config == undefined) {
		return;
	}

	if (mode === 'page-load') {
		play();
		// page-load has nothing to dispose — the tween itself is tracked
		// by the kind's playedKey and killed on rebind by common.js.
		return;
	}

	// hover / click can listen on a separate element when the kind has
	// resolved a Trigger Selector (e.g. "#atomic-btn"). Defaults to el.
	if (mode === 'hover') {
		const target = triggerEl || el;
		target.addEventListener('mouseenter', play);
		el[DISPOSE_KEY] = () => target.removeEventListener('mouseenter', play);
		return;
	}

	if (mode === 'click') {
		// Warn once per element — in the editor, rebind() fires on every
		// settings change, so a per-call warn() would spam the console.			
		const target = triggerEl || el;
		target.addEventListener('click', play);
		el[DISPOSE_KEY] = () => target.removeEventListener('click', play);
		return;
	}

	const ScrollTrigger = getScrollTrigger();

	let start = config.start || 'top 85%';
	let end = config.end || 'top 30%';
	let toggleActions = 'play none none none';
	let triggerSelector = el;

	if (config.triggerSelector && config.triggerSelector != '') {
		triggerSelector = document.querySelector(config.triggerSelector) || config.triggerSelector;
	}

	if (config.startTrigger && config.startTrigger != '') {
		triggerSelector = document.querySelector(config.startTrigger) || config.startTrigger;
	}

	let endTrigger;
	if (config.endTrigger && config.endTrigger != '') {
		endTrigger = document.querySelector(config.endTrigger) || config.endTrigger;
	}

	if (config.effect == 'text_spin') {
		start = config.spinStart;
		end = config.spinEnd;
		toggleActions = config.spinToggle;
	}

	// Scrub mode: tween progress follows scroll position. The kind must
	// build a PAUSED tween and return it from buildScrubbed(); we hand it
	// to ScrollTrigger as the `animation` so it can advance/reverse it.
	if (mode === 'scrub' && ScrollTrigger && typeof buildScrubbed === 'function') {
		const tween = buildScrubbed();
		if (!tween) return;

		const stConfig = {
			trigger: triggerSelector,
			animation: tween,
			start: start,
			end: end,
			scrub: true,
			invalidateOnRefresh: true,
			markers: markers,
		};
		if (endTrigger) stConfig.endTrigger = endTrigger;
	
		const st = ScrollTrigger.create(stConfig);

		// `st.kill(true)` reverts — removes the marker <div> nodes
		// ScrollTrigger appended to <body>. Plain `kill()` leaves them
		// behind, which is what makes stale markers appear in the editor.
		el[DISPOSE_KEY] = () => { st.kill(true); tween.kill?.(); };
		return;
	}

	if (mode === 'scroll-tied' && ScrollTrigger) {
		// Guard against ScrollSmoother / ScrollTrigger.refresh() re-firing
		// onEnter on already-played elements. Refresh re-runs init
		// synchronously and re-checks viewport position — so a single bind
		// can fire onEnter 2-3 times before the user scrolls anywhere.
		// Per-bind `played` flag means each ScrollTrigger only fires play()
		// once. cleanupTriggerOn() calls dispose, which destroys the ST and
		// drops the closure — next bind starts fresh.
		let played = false;
		const stConfig = {
			trigger: triggerSelector,
			start: start,
			end: end,
			toggleActions: toggleActions,
			onEnter: () => {
				if (played) return;
				played = true;
				play();
			},
			markers: !!markers,
		};
		if (endTrigger) stConfig.endTrigger = endTrigger;

		const st = ScrollTrigger.create(stConfig);
		el[DISPOSE_KEY] = () => st.kill(true);
		return;
	}

	// Default: in-view, play once when the element scrolls into view.
	// Prefer ScrollTrigger so the timing stays in sync with SmoothScroll
	// and the rest of the GSAP pipeline. IntersectionObserver is the
	// fallback path when ScrollTrigger isn't loaded (rare).
	if (ScrollTrigger) {
		// Same `played` guard as scroll-tied — in-view also fires onEnter on
		// init/refresh when the element is already in the viewport. ST's
		// `once:true` only stops re-fires on the SAME ScrollTrigger; if
		// ScrollSmoother revert+recreates it, a new instance fires again.
		let played = false;
		const stConfig = {
			trigger: triggerSelector, // Use triggerSelector so startTrigger applies here too
			start: 'top 85%',
			once: true,
			onEnter: () => {
				if (played) return;
				played = true;
				play();
			},
			markers: !!markers,
		};
		if (endTrigger) stConfig.endTrigger = endTrigger;

		const st = ScrollTrigger.create(stConfig);
		el[DISPOSE_KEY] = () => st.kill(true);
		return;
	}

	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			play();
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.15 });
	observer.observe(triggerSelector instanceof HTMLElement ? triggerSelector : el);
	el[DISPOSE_KEY] = () => observer.disconnect();
}
