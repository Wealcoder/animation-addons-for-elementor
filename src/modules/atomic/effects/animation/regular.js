/* eslint-env browser */

import { PRESETS, RESET_TO } from '../../presets';
import { wireTrigger } from './triggers';

/**
 * Regular animation kind — preset-based fade/move/zoom.
 *   data-aae-anim   → effect name (PRESETS key)
 *   data-aae-trigger  → in-view | page-load | scroll-progress
 *
 * Helpers come from window.AAEADDON. NEVER import from '../../common' —
 * that would inline ~1.5 KB of helpers into every effect bundle.
 */
const { getGsap, getScrollTrigger, numOr } = window.AAEADDON;

export const REGULAR_PLAYED = '__aaeAnimPlayed';
const REGULAR_PLAYED_CLASS = 'aae-anim-played';

export function readRegular(el) {
	const effect = el.dataset.aaeAnim;
	if (!effect || effect === 'none' || !PRESETS[effect]) return null;
	return {
		effect,
		trigger:  el.dataset.aaeTrigger || 'in-view',
		duration: numOr(el.dataset.aaeDuration, 600) / 1000,
		delay:    numOr(el.dataset.aaeDelay,    0)   / 1000,
		easing:   el.dataset.aaeEasing || 'power2.out',
		repeat:   numOr(el.dataset.aaeRepeat,   0),
	};
}

export function playRegular(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	if (el[REGULAR_PLAYED]) {
		el[REGULAR_PLAYED].kill();
	}

	const preset = PRESETS[config.effect];
	el[REGULAR_PLAYED] = gsap.fromTo(el, preset.from, {
		...RESET_TO,
		duration:   config.duration,
		delay:      config.delay,
		ease:       config.easing,
		repeat:     config.repeat,
		yoyo:       config.repeat !== 0,
		clearProps: 'all',
	});
	el.classList.add(REGULAR_PLAYED_CLASS);
}

/** Map regular-anim's trigger vocabulary to the shared dispatcher's modes. */
function modeFor(trigger) {
	if (trigger === 'page-load') return 'page-load';
	return 'in-view'; // 'in-view' (explicit) and anything unrecognised
}

export function bindRegular(el, config) {
	// `scroll-progress` is special — it scrubs the tween to scroll position
	// rather than firing play() once. Handled inline because the scrubbed
	// tween needs preset-specific from/to values that the generic dispatcher
	// can't construct.
	const ScrollTrigger = getScrollTrigger();
	if (config.trigger === 'scroll-progress' && ScrollTrigger) {
		const preset = PRESETS[config.effect];
		getGsap().fromTo(el, preset.from, {
			...RESET_TO,
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start: 'top bottom',
				end:   'bottom top',
				scrub: true,
			},
		});
		return;
	}

	wireTrigger({
		el,
		mode: modeFor(config.trigger),
		play: () => playRegular(el, config),
	});
}
