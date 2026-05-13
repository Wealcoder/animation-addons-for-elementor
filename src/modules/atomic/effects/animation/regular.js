/* eslint-env browser */

import { wireTrigger, modeFor, resolveTriggerEl } from './triggers';

/**
 * Regular animation kind — composes a GSAP tween from the (effect, …)
 * config emitted by Render.php (frontend) or the editor-bridge (preview).
 *
 * Reads from `window.AAE_INTERACTIONS_ANIM[<interactionId>]`. Mirrors the
 * shape used by text.js: read once via configFor → resolve responsive
 * primitives via pickConfigResponsive → compose (from, to) per effect →
 * tween the element itself.
 *
 * Helpers come from window.AAEADDON. NEVER import from '../../common' —
 * that would inline ~1.5 KB of helpers into every effect bundle.
 */
const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const ANIM_MAP = 'AAE_INTERACTIONS_ANIM';

export const REGULAR_PLAYED = '__aaeAnimPlayed';

/** Read a config field with responsive cascade + a JS-side default. */
function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

export function readRegular(el) {
	const cfg = configFor(el, ANIM_MAP);
	
	if (!cfg) return null;
	const effect = pickConfigResponsive(cfg, 'effect');
	if (!effect || effect === 'none') return null;
	return {
		effect,
		method:          r(cfg, 'method',  'from'),
		trigger:         r(cfg, 'trigger', 'on_scroll'),
		triggerSelector: r(cfg, 'triggerSelector', ''),
		easing:          r(cfg, 'easing',   'power2.out'),
		duration:        Number(r(cfg, 'duration', 1.5)),
		delay:           Number(r(cfg, 'delay',    0.15)),
		// Non-responsive: cfg.markers is a top-level boolean.
		markers:         !!cfg.markers,
		// fade
		fadeFrom:        r(cfg, 'fadeFrom',   'bottom'),
		fadeOffset:      Number(r(cfg, 'fadeOffset', 50)),
		scale:           Number(r(cfg, 'scale',      0.7)),
		// move
		rotationDir:     r(cfg, 'rotationDir',     'x'),
		rotation:        Number(r(cfg, 'rotation', -80)),
		transformOrigin: r(cfg, 'transformOrigin', 'top center -50'),
		// custom
		customProps:     Array.isArray(pickConfigResponsive(cfg, 'customProps'))
			? pickConfigResponsive(cfg, 'customProps')
			: [],
	};
}

/**
 * Build `from` / `to` tween targets for each effect. Mirrors text.js's
 * textTween() — the runtime composes from primitives instead of looking
 * up a named preset, so the editor can mix-and-match (effect=fade +
 * fadeFrom=left + fadeOffset=120) without exploding the preset table.
 */
function regularTween(config) {
	if (config.effect === 'fade') {
		const { fadeFrom, fadeOffset, scale } = config;
		if (fadeFrom === 'scale') {
			return {
				from: { opacity: 0, scale },
				to:   { opacity: 1, scale: 1 },
			};
		}
		if (fadeFrom === 'in') {
			return {
				from: { opacity: 0 },
				to:   { opacity: 1 },
			};
		}
		const axis = (fadeFrom === 'left' || fadeFrom === 'right') ? 'x' : 'y';
		const sign = (fadeFrom === 'left' || fadeFrom === 'top') ? -1 : 1;
		return {
			from: { opacity: 0, [axis]: sign * fadeOffset },
			to:   { opacity: 1, [axis]: 0 },
		};
	}

	if (config.effect === 'move') {
		const axisKey = config.rotationDir === 'y' ? 'rotationY' : 'rotationX';
		return {
			from: {
				opacity: 0,
				[axisKey]: config.rotation,
				transformOrigin: config.transformOrigin,
			},
			to: {
				opacity: 1,
				rotationX: 0,
				rotationY: 0,
				transformOrigin: config.transformOrigin,
			},
		};
	}

	if (config.effect === 'custom') {
		// `customProps` is the editor's repeater output, normalised to
		// `[{ k, v }]` pairs in features.js. Each pair becomes a GSAP
		// tween target: method=from animates FROM the listed state TO
		// the existing one, method=to animates the opposite way.
		const target = {};
		for (const { k, v } of config.customProps || []) {
			if (!k) continue;
			// Numeric strings become numbers; anything else stays string
			// (so colour/transform-origin/etc. pass through verbatim).
			const num = Number(v);
			target[k] = (v !== '' && Number.isFinite(num)) ? num : v;
		}
		return config.method === 'to'
			? { from: {}, to: target }
			: { from: target, to: {} };
	}

	return null;
}

/** Strip ONLY the props this effect touched. `clearProps: 'all'` would
 *  erase Elementor's own inline styles. */
function clearPropsFor(fromObj, toObj) {
	const props = new Set();
	const addFromKey = (k) => {
		if (k === 'opacity') props.add('opacity');
		else if (k === 'x' || k === 'y' || k === 'scale'
			|| k === 'rotation' || k === 'rotationX' || k === 'rotationY') {
			props.add('transform');
		}
		else if (k === 'transformOrigin') props.add('transform-origin');
		else props.add(k);
	};
	Object.keys(fromObj || {}).forEach(addFromKey);
	Object.keys(toObj   || {}).forEach(addFromKey);
	return props.size ? Array.from(props).join(',') : false;
}

export function playRegular(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	if (el[REGULAR_PLAYED]) el[REGULAR_PLAYED].kill();

	const tween = regularTween(config);
	if (!tween) return;

	el[REGULAR_PLAYED] = gsap.fromTo(el, tween.from, {
		...tween.to,
		duration:   config.duration,
		delay:      config.delay,
		ease:       config.easing,
		clearProps: clearPropsFor(tween.from, tween.to),
	});
}

function buildScrubbedRegular(el, config) {
	const gsap = getGsap();
	if (!gsap) return null;

	if (el[REGULAR_PLAYED]) el[REGULAR_PLAYED].kill();

	const tween = regularTween(config);
	if (!tween) return null;

	el[REGULAR_PLAYED] = gsap.fromTo(el, tween.from, {
		...tween.to,
		duration: config.duration,
		ease:     'none',
		paused:   true,
	});
	return el[REGULAR_PLAYED];
}

/**
 * Restore the element to its pre-animation state. Used when the editor's
 * `Enable On Editor` toggle flips OFF — kill the tween and revert any
 * GSAP-applied inline styles so the canvas reflects "no animation".
 */
export function resetRegular(el) {
	if (!el[REGULAR_PLAYED]) return;
	try { el[REGULAR_PLAYED].revert(); } catch (_) { /* ignore */ }
	el[REGULAR_PLAYED].kill?.();
	delete el[REGULAR_PLAYED];
}

export function bindRegular(el, config) {
	const mode = modeFor(config.trigger);
	wireTrigger({
		el,
		mode,
		triggerEl:     resolveTriggerEl(mode, config.triggerSelector),
		markers:       config.markers,
		play:          () => playRegular(el, config),
		buildScrubbed: () => buildScrubbedRegular(el, config),
	});
}
