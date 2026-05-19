/* eslint-env browser */

import { wireTrigger, modeFor, resolveTriggerEl } from './triggers';

/**
 * Text animation kind — char/word/text_move/text_reveal/text_scale/...
 *
 * Reads its config from `window.AAE_INTERACTIONS_TEXT[interactionId]`.
 * Splitting is done with GSAP's official SplitText plugin (Club / shipped
 * by the Pro plugin). When SplitText isn't loaded we bail rather than fall
 * back to a hand-rolled splitter — the Pro plugin already enqueues it.
 *
 * Helpers come from window.AAEADDON. See note in regular.js.
 */
const { getGsap, getSplitText, configFor, pickConfigResponsive } = window.AAEADDON;

export const TEXT_MAP = 'AAE_INTERACTIONS_TEXT';
export const TEXT_PLAYED = '__aaeTextPlayed';
const TEXT_SPLIT_KEY = '__aaeTextSplit';

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

function parseTimeValue(val, fallback) {
	if (val === undefined || val === null || val === '') return fallback;
	if (typeof val === 'number') return val;
	if (typeof val === 'object') {
		const size = val.size !== undefined ? val.size : val.value;
		const unit = val.unit || 's';
		if (size === undefined || size === null || size === '') return fallback;
		const num = Number(size);
		if (!Number.isFinite(num)) return fallback;
		return unit === 'ms' ? num / 1000 : num;
	}
	const str = String(val).trim();
	if (str.endsWith('ms')) {
		const num = Number(str.slice(0, -2));
		return Number.isFinite(num) ? num / 1000 : fallback;
	}
	if (str.endsWith('s')) {
		const num = Number(str.slice(0, -1));
		return Number.isFinite(num) ? num : fallback;
	}
	const num = Number(str);
	return Number.isFinite(num) ? num : fallback;
}

export function readText(el) {
	const cfg = configFor(el, TEXT_MAP);

	if (!cfg) return null;
	const effect = pickConfigResponsive(cfg, 'effect');
	if (!effect || effect === 'none') return null;

	return {
		effect,
		trigger: r(cfg, 'trigger', 'on_scroll'),
		triggerSelector: r(cfg, 'triggerSelector', ''),
		wrapper: r(cfg, 'wrapper', 'default'),
		endWrapper: r(cfg, 'endWrapper', ''),
		markers: !!cfg.markers,
		delay: parseTimeValue(r(cfg, 'delay', 0.15), 0.15),
		duration: parseTimeValue(r(cfg, 'duration', 1), 1),
		stagger: Number(r(cfg, 'stagger', 0.02)),
		translateX: Number(r(cfg, 'translateX', 20)),
		translateY: Number(r(cfg, 'translateY', 0)),
		rotationDir: r(cfg, 'rotationDir', 'x'),
		rotation: Number(r(cfg, 'rotation', -80)),
		transformOrigin: r(cfg, 'transformOrigin', 'top center -50'),
		spinColor: r(cfg, 'spinColor', '#000'),
		spinStart: r(cfg, 'spinStart', 'top 85%'),
		spinEnd: r(cfg, 'spinEnd', 'top 30%'),
		spinToggle: r(cfg, 'spinToggle', 'play none none reverse'),
	};
}

/**
 * Per-effect SplitText recipe. `type` is the SplitText `type` option;
 * `target` is which split collection to tween (chars / words / lines /
 * null = whole element, no split needed).
 *
 * Effects whose target is null skip SplitText entirely — they animate
 * the full element (text_scale, text_invert, text_spin in this build).
 */
const SPLIT_RECIPE = {
	char: { type: 'chars,words', target: 'chars' },
	word: { type: 'chars,words', target: 'words' },
	text_move: { type: 'lines', target: 'lines', perspective: 400 },
	text_reveal: { type: 'lines,words,chars', target: 'chars', linesClass: 'anim-reveal-line' },
	text_scale: { type: null, target: null },
	text_invert: { type: 'lines', target: 'lines', linesClass: 'invert-line', parentClass: 'wcf-t-animation-text_invert' },
	text_spin: { type: null, target: null },
};

/**
 * Per-effect tween descriptor. Matches V3's `gsap.from(target, props)`
 * pattern — one call, no `to` state. `props` contains the FROM values plus
 * shared timing (duration/delay/stagger). For text_invert we tween between
 * two background-position-x states, so it returns `{method:'fromTo'}`.
 *
 * `text_spin` is the only effect that needs a complex multi-tween timeline
 * (V3 builds it inline with cloning); we keep a placeholder for now.
 */
function textTween(effect, config, pieces) {
	const shared = {
		duration: config.duration,
		delay: config.delay,
		stagger: config.stagger,
		ease: 'power2.out',
	};

	switch (effect) {
		case 'char':
		case 'word':
			// V3 pattern: gsap.from(chars, { duration, delay, stagger,
			// autoAlpha: 0, x: translateX, y: translateY })
			return {
				method: 'from',
				props: {
					...shared,
					autoAlpha: 0,
					x: config.translateX,
					y: config.translateY,
				},
			};

		case 'text_move':
			return {
				method: 'from',
				props: {
					...shared,
					autoAlpha: 0,
					[config.rotationDir === 'y' ? 'rotationY' : 'rotationX']: config.rotation,
					transformOrigin: config.transformOrigin,
				},
			};

		case 'text_reveal':
			// Per-line clip wrapper — chars slide up from below their line.
			pieces.forEach((p) => {
				const line = p.closest('.anim-reveal-line') || p.parentElement;
				if (line) line.style.overflow = 'hidden';
			});
			return {
				method: 'from',
				props: { ...shared, yPercent: 100, autoAlpha: 0 },
			};

		case 'text_scale':
			return {
				method: 'from',
				props: { ...shared, scale: 0, autoAlpha: 0, transformOrigin: 'center center' },
			};

		case 'text_invert':
			// CSS parks each .invert-line at background-position-x:100% (the
			// transparent half showing). Tween to 0% to slide the opaque half
			// across, revealing the line. fromTo so we don't depend on the
			// computed start state — keeps the editor preview consistent.
			return {
				method: 'fromTo',
				from: { backgroundPositionX: '100%' },
				to: { ...shared, backgroundPositionX: '0%', ease: 'none' },
			};

		case 'text_spin':
			return {
				method: 'from',
				props: { ...shared, rotationY: 180, autoAlpha: 0, transformOrigin: 'center center' },
			};

		default:
			return null;
	}
}

/** Build the SplitText instance for `effect` and return the tween targets.
 *  Returns null when the effect needs no split (target the element itself). */
function splitFor(el, effect) {
	const recipe = SPLIT_RECIPE[effect];

	if (!recipe || !recipe.type) return null;

	const SplitText = getSplitText();
	if (!SplitText) return null;

	if (recipe.parentClass) el.classList.add(recipe.parentClass);

	const opts = { type: recipe.type };

	if (recipe.linesClass) opts.linesClass = recipe.linesClass;
	const split = new SplitText(el, opts);

	if (recipe.perspective) {
		const gsap = getGsap();
		gsap?.set(el, { perspective: recipe.perspective });
	}

	el[TEXT_SPLIT_KEY] = split;

	return split[recipe.target] || null;
}

/** Pick the actual tween targets for `effect` — the split collection when
 *  the recipe needs splitting, the element itself otherwise. */
function targetsFor(el, effect) {
	const pieces = splitFor(el, effect);

	if (pieces && pieces.length) return pieces;

	if (SPLIT_RECIPE[effect] && SPLIT_RECIPE[effect].target === null) return [el];
	return null;
}

/**
 * Restore the element to its pre-animation state — kill the tween and
 * revert the SplitText so the original DOM (and any inline styles
 * SplitText set) is back in place.
 */
export function resetText(el) {
	if (el[TEXT_PLAYED]) {
		try { el[TEXT_PLAYED].kill(); } catch (_) { /* ignore */ }
		delete el[TEXT_PLAYED];
	}
	const split = el[TEXT_SPLIT_KEY];
	if (split && typeof split.revert === 'function') {
		try { split.revert(); } catch (_) { /* ignore */ }
		delete el[TEXT_SPLIT_KEY];
	}
	// Drop any per-effect parent classes we attached in splitFor().
	for (const recipe of Object.values(SPLIT_RECIPE)) {
		if (recipe.parentClass) el.classList.remove(recipe.parentClass);
	}
}

export function playText(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	// Always reset before re-splitting — split.revert() puts the original
	// DOM back so a new SplitText doesn't compound on the previous output.
	resetText(el);

	const pieces = targetsFor(el, config.effect);
	if (!pieces) return;

	const tween = textTween(config.effect, config, pieces);
	if (!tween) return;

	if (tween.method === 'fromTo') {
		el[TEXT_PLAYED] = gsap.fromTo(pieces, tween.from, tween.to);
	} else {
		// 'from' — V3 default for every text effect
		el[TEXT_PLAYED] = gsap.from(pieces, tween.props);
	}
}

/** Build a PAUSED text tween used by `play_with_scroll` — ScrollTrigger
 *  advances its progress to match scroll position. Returns null if GSAP
 *  isn't loaded or the effect has no tween descriptor. */
function buildScrubbedText(el, config) {
	const gsap = getGsap();
	if (!gsap) return null;

	resetText(el);

	const pieces = targetsFor(el, config.effect);
	if (!pieces) return null;

	const tween = textTween(config.effect, config, pieces);
	if (!tween) return null;

	// Force linear easing + paused for scrub regardless of effect default.
	const overrides = { ease: 'none', paused: true };

	if (tween.method === 'fromTo') {
		el[TEXT_PLAYED] = gsap.fromTo(pieces, tween.from, { ...tween.to, ...overrides });
	} else {
		el[TEXT_PLAYED] = gsap.from(pieces, { ...tween.props, ...overrides });
	}
	return el[TEXT_PLAYED];
}
// kind.bind() calls this to wire the animation to the element based on current
export function bindText(el, config) {
	const mode = modeFor(config.trigger);

	let triggerSelector = '';
	if (config.wrapper === 'default' && config.triggerSelector == '') {
		triggerSelector = el;
	}

	wireTrigger({
		el,
		mode,
		triggerEl: resolveTriggerEl(mode, triggerSelector, config),
		markers: config.markers,
		play: () => playText(el, config),
		buildScrubbed: () => buildScrubbedText(el, config),
		config: config
	});
}
