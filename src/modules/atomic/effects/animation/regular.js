/* eslint-env browser */

import { PRESETS, RESET_TO } from '../../presets';
import { wireTrigger } from './triggers';

/**
 * Regular animation kind — preset-based fade / 3D move / custom.
 *
 * Reads from `window.AAE_INTERACTIONS_ANIM[el.dataset.aaeAnimId]`. Server-
 * side Render.php builds the config (with responsive variants); this file
 * just consumes it via `pickConfigResponsive`.
 *
 * Helpers come from window.AAEADDON. NEVER import from '../../common' —
 * that would inline ~1.5 KB of helpers into every effect bundle.
 */
const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const ANIM_MAP = 'AAE_INTERACTIONS_ANIM';

/** Read a config field with responsive cascade + a JS-side default. */
function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

export const REGULAR_PLAYED = '__aaeAnimPlayed';
const REGULAR_PLAYED_CLASS = 'aae-anim-played';

export function readRegular(el) {
	const cfg = configFor(el, ANIM_MAP);
	if (!cfg || !cfg.effect || cfg.effect === 'none' || !PRESETS[cfg.effect]) return null;
	return {
		effect:          cfg.effect,
		method:          r(cfg, 'method',  'from'),
		trigger:         r(cfg, 'trigger', 'on_scroll'),
		triggerSelector: r(cfg, 'triggerSelector', ''),
		easing:          r(cfg, 'easing', 'power2.out'),
		// Server emits seconds (float). Schema defaults: 1.5s duration, 0.15s delay.
		duration:        Number(r(cfg, 'duration', 1.5)),
		delay:           Number(r(cfg, 'delay',    0.15)),
		repeat:          Number(r(cfg, 'repeat',   0)),
	};
}

/**
 * Restore the element to its pre-animation state — kill + revert the
 * tween so any GSAP-applied inline styles are removed. Used when the
 * editor's `Enable On Editor` toggle flips OFF.
 */
export function resetRegular(el) {
	if (el[REGULAR_PLAYED]) {
		// .revert() unsets the tween's affected properties; clean .kill()
		// alone leaves the element at its current (mid-tween) state.
		try { el[REGULAR_PLAYED].revert(); } catch (_) { /* ignore */ }
		el[REGULAR_PLAYED].kill?.();
		delete el[REGULAR_PLAYED];
	}
	el.classList.remove(REGULAR_PLAYED_CLASS);
}

/**
 * Build a CSS-property list for `clearProps` from the preset's `from` keys.
 * `clearProps: 'all'` strips EVERY inline style on the element — including
 * Elementor's own positioning / hover-state inline styles — which can leave
 * widgets invisible if Elementor injected an opacity/transform before our
 * tween started. Limiting the strip to only the props we touched keeps the
 * cleanup safe for everything else.
 *
 * GSAP shorthand (x/y/scale/rotation/rotationX/rotationY) all live under
 * the single CSS `transform` property, so any of them present in `from`
 * means we strip `transform`. Plus opacity if `from` set it.
 */
function clearPropsFor(presetFrom) {
	const props = new Set();
	if ('opacity' in presetFrom) props.add('opacity');
	for (const k of ['x', 'y', 'scale', 'rotation', 'rotationX', 'rotationY']) {
		if (k in presetFrom) { props.add('transform'); break; }
	}
	return props.size ? Array.from(props).join(',') : false;
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
		// Strip ONLY the props this effect actually touched, so we don't
		// erase Elementor's own inline styles on completion.
		clearProps: clearPropsFor(preset.from),
	});
	el.classList.add(REGULAR_PLAYED_CLASS);
}

/** Build a PAUSED tween used by `play_with_scroll` — ScrollTrigger advances
 *  its progress to match scroll position (forward on wheel-down, reverse
 *  on wheel-up). Returns null if GSAP isn't loaded. */
function buildScrubbedRegular(el, config) {
	const gsap = getGsap();
	if (!gsap) return null;

	if (el[REGULAR_PLAYED]) {
		el[REGULAR_PLAYED].kill();
	}

	const preset = PRESETS[config.effect];
	const tween = gsap.fromTo(el, preset.from, {
		...RESET_TO,
		ease:     'none',          // scrub ignores easing — keep linear progress
		duration: config.duration, // duration is logical here, ScrollTrigger maps
		paused:   true,            //   it onto the scroll distance (start→end)
	});
	el[REGULAR_PLAYED] = tween;
	el.classList.add(REGULAR_PLAYED_CLASS);
	return tween;
}

/** Map regular-anim's trigger vocabulary to the shared dispatcher's modes.
 *  Mirrors RegularAnimation\Schema::triggers() + legacy aliases. */
function modeFor(trigger) {
	if (trigger === 'on_scroll')                                return 'scroll-tied';
	if (trigger === 'play_with_scroll')                         return 'scrub';
	if (trigger === 'on_page_load')                              return 'page-load';
	if (trigger === 'mouseover')                                return 'hover';
	if (trigger === 'click')                                    return 'click';
	return 'in-view'; // safe default for unrecognised values
}

/** Resolve `triggerSelector` to a DOM node for hover/click only — Schema
 *  gates the selector field to those triggers. Empty or no-match →
 *  undefined; the dispatcher falls back to the animated element itself. */
function resolveTriggerEl(mode, selector) {
	if ((mode !== 'hover' && mode !== 'click') || !selector) return undefined;
	return document.querySelector(selector) || undefined;
}

export function bindRegular(el, config) {
	const mode = modeFor(config.trigger);
	wireTrigger({
		el,
		mode,
		triggerEl:     resolveTriggerEl(mode, config.triggerSelector),
		play:          () => playRegular(el, config),
		buildScrubbed: () => buildScrubbedRegular(el, config),
	});
}
