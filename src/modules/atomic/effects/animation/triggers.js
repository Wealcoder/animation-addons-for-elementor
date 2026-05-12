/* eslint-env browser */

/**
 * Shared trigger dispatcher for every animation kind.
 *
 * Each kind owns its own trigger-name vocabulary (regular: in-view /
 * page-load / scroll-progress; text: on_scroll / on_page_load /
 * play_with_scroll / mouseover / click). Both vocabularies map onto the
 * same handful of wiring patterns — this file is the single home for
 * those patterns so new kinds (image, tilt, parallax…) never re-implement
 * them.
 *
 * Usage from a kind:
 *
 *   wireTrigger({
 *     el,
 *     mode: normalizedTriggerMode(config.trigger),  // see canonical names below
 *     play: () => playRegular(el, config),
 *   });
 *
 * Canonical modes (every kind maps its raw trigger name to one of these):
 *   - 'page-load'      → fire play() immediately
 *   - 'hover'          → fire on mouseenter
 *   - 'click'          → fire on click
 *   - 'scroll-tied'    → ScrollTrigger replay onEnter/onEnterBack
 *   - 'in-view'        → IntersectionObserver, fire once on enter (DEFAULT)
 *
 * Anything unrecognised falls through to in-view, which is the universal
 * safe default. The `scroll-progress` mode (scrubbing the tween to scroll
 * progress) is NOT generic — only `regular` supports it today because the
 * scrubbed tween targets need preset-specific configuration. That stays
 * inside regular.js.
 */
const { getScrollTrigger } = window.AAEADDON;

export function wireTrigger({ el, mode, play }) {
	if (mode === 'page-load') {
		play();
		return;
	}

	if (mode === 'hover') {
		el.addEventListener('mouseenter', play);
		return;
	}

	if (mode === 'click') {
		el.addEventListener('click', play);
		return;
	}

	const ScrollTrigger = getScrollTrigger();
	if (mode === 'scroll-tied' && ScrollTrigger) {
		ScrollTrigger.create({
			trigger: el,
			start: 'top bottom',
			end:   'bottom top',
			onEnter:     play,
			onEnterBack: play,
		});
		return;
	}

	// Default: in-view, play once on entry.
	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			play();
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.15 });
	observer.observe(el);
}
