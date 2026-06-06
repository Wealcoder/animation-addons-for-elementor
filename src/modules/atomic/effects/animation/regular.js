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
/**
 * Parse a value to its proper type (mirrors pro version's perse_value).
 * Handles boolean strings, numeric strings, and returns everything else as-is.
 */
function parseValue(value) {
	if (typeof value !== 'string') return value;

	const lower = value.toLowerCase().trim();

	// Boolean detection
	if (lower === 'true') return true;
	if (lower === 'false') return false;

	// Pure number (integer or float) - negative numbers supported
	if (/^-?\d+(\.\d+)?$/.test(value)) {
		return parseFloat(value);
	}

	// All other values: return as string
	return value;
}

/** Read a config field with responsive cascade + a JS-side default. */
function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

export function readRegular(el) {
	const cfg = configFor(el, ANIM_MAP);

	console.log('[AAE Regular] readRegular called, cfg:', cfg);

	if (!cfg) return null;
	const effect = pickConfigResponsive(cfg, 'effect');
	console.log('[AAE Regular] effect:', effect);
	if (!effect || effect === 'none') return null;

	const config = {
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

	console.log('[AAE Regular] config:', config);
	return config;
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
				force3D: true,
				transformOrigin: config.transformOrigin,
				[axisKey]: config.rotation,
			},
			to: {
				opacity: 1,
				force3D: true,
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
		// Uses parseValue to convert strings to proper types (bools, numbers).
		const target = {};
		for (const { k, v } of config.customProps || []) {
			if (!k) continue;
			target[k] = parseValue(v);
		}
		return config.method === 'to'
			? { from: {}, to: target }
			: { from: target, to: {} };
	}

	return null;
}

/** Strip ONLY the props this effect touched. `clearProps: 'all'` would
 *  erase Elementor's own inline styles. force3D is a GSAP property, not CSS. */
function clearPropsFor(fromObj, toObj) {
	const props = new Set();
	const addFromKey = (k) => {
		// Skip GSAP-specific properties that aren't CSS
		if (k === 'force3D') return;

		if (k === 'opacity') props.add('opacity');
		else if (k === 'x' || k === 'y' || k === 'scale'
			|| k === 'rotation' || k === 'rotationX' || k === 'rotationY' || k === 'z'
			|| k === 'rotationZ') {
			props.add('transform');
		}
		else if (k === 'transformOrigin') props.add('transform-origin');
		else if (k === 'transform') props.add('transform');
		else props.add(k);
	};
	Object.keys(fromObj || {}).forEach(addFromKey);
	Object.keys(toObj   || {}).forEach(addFromKey);
	return props.size ? Array.from(props).join(',') : false;
}

export function playRegular(el, config) {
	console.log('[AAE Regular] playRegular called with config:', config);

	const gsap = getGsap();
	if (!gsap) {
		console.log('[AAE Regular] GSAP not available');
		return;
	}

	if (el[REGULAR_PLAYED]) el[REGULAR_PLAYED].kill();

	// For move animation, set perspective on parent element (matches pro version)
	if (config.effect === 'move') {
		console.log('[AAE Regular] Setting up move animation with:');
		console.log('  - rotationDir:', config.rotationDir);
		console.log('  - rotation:', config.rotation);
		console.log('  - transformOrigin:', config.transformOrigin);
		if (el.parentElement) {
			console.log('  - Setting perspective: 400px on parent element');
			gsap.set(el.parentElement, { perspective: 400 });
		}
	}

	const tween = regularTween(config);
	console.log('[AAE Regular] tween:', tween);
	console.log('[AAE Regular] tween.from:', tween?.from);
	console.log('[AAE Regular] tween.to:', tween?.to);
	if (!tween) {
		console.log('[AAE Regular] No tween returned from regularTween');
		return;
	}

	el[REGULAR_PLAYED] = gsap.fromTo(el, tween.from, {
		...tween.to,
		duration:   config.duration,
		delay:      config.delay,
		ease:       config.easing,
		clearProps: clearPropsFor(tween.from, tween.to),
	});

	console.log('[AAE Regular] Tween created:', el[REGULAR_PLAYED]);
}

function buildScrubbedRegular(el, config) {
	const gsap = getGsap();
	if (!gsap) return null;

	if (el[REGULAR_PLAYED]) el[REGULAR_PLAYED].kill();

	// For move animation, set perspective on parent element (matches pro version)
	if (config.effect === 'move' && el.parentElement) {
		gsap.set(el.parentElement, { perspective: 400 });
	}

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

	// Clean up perspective on parent if it was set for move animation
	const gsap = getGsap();
	if (gsap && el.parentElement) {
		gsap.set(el.parentElement, { clearProps: 'transform' });
	}
}

export function bindRegular(el, config) {
	console.log('[AAE Regular] bindRegular called with config:', config);
	const mode = modeFor(config.trigger);
	console.log('[AAE Regular] trigger mode:', mode);
	wireTrigger({
		el,
		mode,
		triggerEl:     resolveTriggerEl(mode, config.triggerSelector),
		markers:       config.markers,
		play:          () => playRegular(el, config),
		buildScrubbed: () => buildScrubbedRegular(el, config),
		config:        config,
	});
}
