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
	on_slide_change: 'slide-change',
};

export function modeFor(trigger) {
	return TRIGGER_MODES[trigger] || 'in-view';
}

/**
 * Resolve the element a hover/click trigger should listen on.
 *
 * Rule: for click / hover, an empty Trigger Selector means the element
 * itself (self). A non-empty selector resolves via querySelector; if it
 * matches nothing we fall back to the element so the interaction never goes
 * dead. Non-interactive modes (scroll / page-load) return undefined — they
 * don't use a listen target.
 *
 * `el` is the animated element; `config.triggerSelector` is the user value.
 * The legacy `selector` second arg is ignored (kept for call-site compat).
 */
export function resolveTriggerEl(mode, el, config) {
	if (mode !== 'hover' && mode !== 'click') return undefined;

	const sel = config && config.triggerSelector;
	// Empty → self element.
	if (!sel || sel === '') {
		return el instanceof HTMLElement ? el : undefined;
	}
	if (sel instanceof HTMLElement) return sel;
	// Non-empty selector → match, else fall back to self.
	return document.querySelector(sel) || (el instanceof HTMLElement ? el : undefined);
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

/**
 * Wire a trigger. Returns a disposer function that tears down whatever this
 * call installed (listener / ScrollTrigger / observer). For single-config
 * kinds the disposer is ALSO stored on el[DISPOSE_KEY] (so the legacy
 * cleanupTriggerOn(el) path keeps working). Multi-row kinds pass
 * `skipCleanup: true` and `skipGlobalKey: true` so stacked rows don't clobber
 * each other's disposer — they collect the returned disposers themselves.
 */
export function wireTrigger({ el, mode, play, animation, buildScrubbed, triggerEl, markers, config, skipCleanup, skipGlobalKey }) {
	// Always clean up the previous wiring first — in the editor, settings
	// changes fire rebind() which can re-call us on the same element many
	// times. Multi-row kinds skip this (they manage their own disposer list).
	if (!skipCleanup) {
		cleanupTriggerOn(el);
	}
	if (config == undefined) {
		return () => {};
	}

	// Store the disposer on the global key only for single-config kinds.
	const setDisposer = (fn) => {
		if (!skipGlobalKey) el[DISPOSE_KEY] = fn;
		return fn;
	};

	if (mode === 'page-load') {
		play();
		// page-load has nothing to dispose — the tween itself is tracked
		// by the kind's playedKey and killed on rebind by common.js.
		return () => {};
	}

	// hover / click can listen on a separate element via a Trigger Selector
	// (e.g. "#atomic-btn"). When a selector is given we DON'T bind directly to a
	// resolved node — in the editor that node may not exist yet at bind time (or
	// gets re-rendered), so a direct listener silently misses. Instead we
	// delegate from the document: one listener that fires play() whenever the
	// event's target is inside something matching the selector. This is
	// timing-proof and survives re-renders. With no selector we bind to el (self).
	const sel = config && config.triggerSelector;
	const hasSelector = typeof sel === 'string' && sel !== '';

	if (mode === 'hover') {
		if (hasSelector) {
			const doc = el.ownerDocument || document;
			const onOver = (e) => {
				const t = e.target;
				if (t && t.closest && t.closest(sel)) play();
			};
			// mouseover (delegatable, bubbles) instead of mouseenter (doesn't bubble).
			doc.addEventListener('mouseover', onOver, true);
			return setDisposer(() => doc.removeEventListener('mouseover', onOver, true));
		}
		const target = triggerEl || el;
		target.addEventListener('mouseenter', play);
		return setDisposer(() => target.removeEventListener('mouseenter', play));
	}

	if (mode === 'click') {
		const isEdit = !!(window.elementorFrontend
			&& window.elementorFrontend.isEditMode
			&& window.elementorFrontend.isEditMode());

		if (hasSelector) {
			const doc = el.ownerDocument || document;
			const evtName = isEdit ? 'pointerdown' : 'click';
			const onEvt = (e) => {
				const t = e.target;
				if (t && t.closest && t.closest(sel)) play();
			};
			doc.addEventListener(evtName, onEvt, true);
			return setDisposer(() => doc.removeEventListener(evtName, onEvt, true));
		}
		const target = triggerEl || el;
		const evtName = isEdit ? 'pointerdown' : 'click';
		target.addEventListener(evtName, play);
		return setDisposer(() => target.removeEventListener(evtName, play));
	}

	// slide-change: replay whenever the AAE nested slider this element lives in
	// switches to a slide that contains `el`. The slider runtime dispatches
	// 'aae:slide:change' on its root (.aae-a-slider) from goToSlide(), carrying
	// the now-active slide in detail.activeSlide. Fires every time (re-runs on
	// every entry), per the chosen "always replay" behaviour.
	if (mode === 'slide-change') {
		const slider = el.closest && el.closest('.aae-a-slider');
		if (!slider) return setDisposer(() => {});
		const onSlide = (e) => {
			const active = e.detail && e.detail.activeSlide;
			// No activeSlide (edge) → replay to be safe; else only when el is inside it.
			if (!active || (active.contains && active.contains(el))) play();
		};
		slider.addEventListener('aae:slide:change', onSlide);

		// Bind-time catch-up for the FIRST slide. The slider emits its initial
		// slide-change during init — but the runtime may bind this listener a hair
		// later (microtask scan), so that first event is missed and the resting
		// slide never animates until the user navigates. The slider also marks the
		// active slide with [data-aae-slide-active]; if el is inside it right now,
		// the entry already happened, so play once to make up for the missed event.
		//
		// Defer to the next frame: at bind time the active slide may still be mid
		// layout / transition, so a geometry-based "from" tween (image clip-path /
		// scale) would measure the wrong start box and only the tail of the
		// animation would show. A rAF lets layout settle so it plays from the top.
		const activeNow = el.closest && el.closest('.aae-a-slide[data-aae-slide-active]');
		let catchupRaf = 0;
		if (activeNow && slider.contains(activeNow)) {
			catchupRaf = requestAnimationFrame(() => { catchupRaf = 0; play(); });
		}

		return setDisposer(() => {
			if (catchupRaf) cancelAnimationFrame(catchupRaf);
			slider.removeEventListener('aae:slide:change', onSlide);
		});
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
		if (!tween) return () => {};

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
		return setDisposer(() => { st.kill(true); tween.kill?.(); });
	}

	if (mode === 'scroll-tied' && ScrollTrigger) {
		// times. Without this, listeners and ScrollTriggers stack up.

		const stConfig = {
			trigger: triggerSelector,
			start: start,
			end: end,
			toggleActions: toggleActions,
			markers: markers,
		};

		if (animation) {
			stConfig.animation = animation;
		} else {
			let played = false;
			stConfig.onEnter = () => {
				if (played) return;
				played = true;
				play();
			};
		}

		if (endTrigger) stConfig.endTrigger = endTrigger;

		const st = ScrollTrigger.create(stConfig);
		return setDisposer(() => st.kill(true));
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
		const stConfig = {
			trigger: triggerSelector, // Use triggerSelector so startTrigger applies here too
			start: 'top 85%',
			once: true,
			markers: !!markers,
		};
		
		if (animation) {
			stConfig.animation = animation;
		} else {
			let played = false;
			stConfig.onEnter = () => {
				if (played) return;
				played = true;
				play();
			};
		}
		
		if (endTrigger) stConfig.endTrigger = endTrigger;

		const st = ScrollTrigger.create(stConfig);
		return setDisposer(() => st.kill(true));
	}

	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			play();
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.15 });
	observer.observe(triggerSelector instanceof HTMLElement ? triggerSelector : el);
	return setDisposer(() => observer.disconnect());
}
