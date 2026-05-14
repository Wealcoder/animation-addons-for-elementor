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
	on_scroll:        'scroll-tied',
	play_with_scroll: 'scrub',
	on_page_load:     'page-load',
	mouseover:        'hover',
	click:            'click',
};

export function modeFor(trigger) {
	return TRIGGER_MODES[trigger] || 'in-view';
}

export function resolveTriggerEl(mode, selector) {
	if ((mode !== 'hover' && mode !== 'click') || !selector) return undefined;
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

export function wireTrigger({ el, mode, play, buildScrubbed, triggerEl, markers }) {
	// Always clean up the previous wiring first — in the editor, settings
	// changes fire rebind() which can re-call us on the same element many
	// times. Without this, listeners and ScrollTriggers stack up.
	cleanupTriggerOn(el);

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
		if (!el.__aaeClickWarned) {
			el.__aaeClickWarned = true;
			console.warn('Click trigger is not recommended for accessibility reasons — prefer hover or in-view where possible', el);
		}
		const target = triggerEl || el;
		target.addEventListener('click', play);
		el[DISPOSE_KEY] = () => target.removeEventListener('click', play);
		return;
	}

	const ScrollTrigger = getScrollTrigger();

	// Scrub mode: tween progress follows scroll position. The kind must
	// build a PAUSED tween and return it from buildScrubbed(); we hand it
	// to ScrollTrigger as the `animation` so it can advance/reverse it.
	if (mode === 'scrub' && ScrollTrigger && typeof buildScrubbed === 'function') {
		const tween = buildScrubbed();
		if (!tween) return;
		const st = ScrollTrigger.create({
			trigger: el,
			animation: tween,
			start: 'top 85%',
			end: 'top 30%',
			scrub: true,
			invalidateOnRefresh: true,
			markers: !!markers,
		});
		// `st.kill(true)` reverts — removes the marker <div> nodes
		// ScrollTrigger appended to <body>. Plain `kill()` leaves them
		// behind, which is what makes stale markers appear in the editor.
		el[DISPOSE_KEY] = () => { st.kill(true); tween.kill?.(); };
		return;
	}

	if (mode === 'scroll-tied' && ScrollTrigger) {
		const st = ScrollTrigger.create({
			trigger: el,
			start: 'top 85%',
			end: 'top 30%',
			toggleActions: 'play none none none',
			onEnter: play,
			markers: !!markers,
		});
		el[DISPOSE_KEY] = () => st.kill(true);
		return;
	}

	// Default: in-view, play once when the element scrolls into view.
	// Prefer ScrollTrigger so the timing stays in sync with SmoothScroll
	// and the rest of the GSAP pipeline. IntersectionObserver is the
	// fallback path when ScrollTrigger isn't loaded (rare).
	if (ScrollTrigger) {
		const st = ScrollTrigger.create({
			trigger: el,
			start: 'top 85%',
			once: true,
			onEnter: play,
			markers: !!markers,
		});
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
	observer.observe(el);
	el[DISPOSE_KEY] = () => observer.disconnect();
}
