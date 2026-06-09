/* eslint-env browser */

import { wireTrigger, modeFor, resolveTriggerEl } from './triggers';
import { PREMIUM_EFFECTS, PREMIUM_EFFECT_OPTIONS } from '../../extensions/text-animation/presets';

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
	return (v === undefined || v === null || v === '') ? fallback : v;
}

/**
 * Find the innermost element containing the actual text.
 * Elementor Legacy widgets use .elementor-widget-container.
 * Elementor Atomic (e-heading, e-paragraph) don't have a container.
 */
function getInnerElement(el) {
	const container = el.querySelector('.elementor-widget-container');
	if (container) {
		const valid = Array.from(container.children).filter(c => c.tagName !== 'SCRIPT' && c.tagName !== 'STYLE');
		if (valid.length > 0) return valid[valid.length - 1];
		return container;
	}
	
	const textNode = el.querySelector('h1, h2, h3, h4, h5, h6, p, .elementor-heading-title, .elementor-text-editor');
	if (textNode) return textNode;
	
	return el;
}

function parseNum(val, fallback) {
	if (val === undefined || val === null || val === '') return fallback;
	const num = Number(val);
	return Number.isFinite(num) ? num : fallback;
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
		startTrigger: r(cfg, 'startTrigger', ''),
		endTrigger: r(cfg, 'endTrigger', ''),
		start: r(cfg, 'startPosition', 'top 85%'),
		end: r(cfg, 'endPosition', 'bottom 30%'),
		markers: !!cfg.markers,
		delay: parseTimeValue(r(cfg, 'delay', 0.15), 0.15),
		duration: parseTimeValue(r(cfg, 'duration', 1), 1),
		stagger: r(cfg, 'stagger', 0.02),
		translateX: parseNum(r(cfg, 'translateX', 20), 20),
		translateY: parseNum(r(cfg, 'translateY', 0), 0),
		rotationDir: r(cfg, 'rotationDir', 'x'),
		rotation: parseNum(r(cfg, 'rotation', -80), -80),
		transformOrigin: r(cfg, 'transformOrigin', ''),
		textShadow: r(cfg, 'textShadow', ''),
		invertStart: r(cfg, 'invertStart', 'top 85%'),
		invertEnd: r(cfg, 'invertEnd', 'bottom center'),
		spinColor: r(cfg, 'spinColor', '#000'),
		spinStart: r(cfg, 'spinStart', 'top 50%'),
		spinEnd: r(cfg, 'spinEnd', 'bottom 30%'),
		spinToggle: r(cfg, 'spinToggle', 'play none none reverse'),
		scaleNum: parseNum(r(cfg, 'scaleNum', 1.5), 1.5),
		scaleBreak: r(cfg, 'scaleBreak', 'lines'),
		scaleEase: r(cfg, 'scaleEase', 'back'),
		ease: r(cfg, 'ease', ''),
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
	text_scale: { type: 'lines,words,chars', target: 'dynamic', linesClass: 'text-scale-anim' },
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
function textTween(effect, config, pieces, el) {
	const shared = {
		duration: config.duration,
		delay: config.delay,
		stagger: buildStaggerConfig(config.stagger),
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
					transformOrigin: config.transformOrigin || 'top center -50',
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
				props: { 
					...shared, 
					scale: config.scaleNum, 
					autoAlpha: 0, 
					transformOrigin: '50% 0%', 
					ease: config.scaleEase 
				},
			};

		case 'text_invert': {
			// Calculate HSL color from the original element and assign it to --text-color
			const colorStr = window.getComputedStyle(el).color;
			const rgb = colorStr.match(/\d+/g);
			if (rgb && rgb.length >= 3) {
				let r = parseInt(rgb[0]) / 255;
				let g = parseInt(rgb[1]) / 255;
				let b = parseInt(rgb[2]) / 255;
				const l = Math.max(r, g, b);
				const s = l - Math.min(r, g, b);
				const h = s ? (l === r ? (g - b) / s : l === g ? 2 + (b - r) / s : 4 + (r - g) / s) : 0;
				const hslH = 60 * h < 0 ? 60 * h + 360 : 60 * h;
				const hslS = 100 * (s ? (l <= 0.5 ? s / (2 * l - s) : s / (2 - (2 * l - s))) : 0);
				const hslL = (100 * (2 * l - s)) / 2;
				el.style.setProperty('--text-color', `${hslH.toFixed(1)}, ${hslS.toFixed(1)}%, ${hslL.toFixed(1)}%`);
			}

			// CSS parks each .invert-line at background-position-x:100% (the
			// dark half). On scrub, we tween backgroundPositionX from 100%->0%
			// which slides the gradient (which has the light half on the left)
			// across, revealing the line. fromTo so we don't depend on the
			// computed start state — keeps the editor preview consistent.
			return {
				method: 'fromTo',
				from: { backgroundPositionX: '100%' },
				to: { ...shared, backgroundPositionX: '0%', ease: 'none' }
			};
		}
		
		default: {
			if (effect && effect.startsWith('premium_')) {
				const presetKey = PREMIUM_EFFECT_OPTIONS.find(o => o.value === effect)?._originalKey;
				const preset = PREMIUM_EFFECTS[presetKey];
				if (preset) {
					const { runAsTo, ...gsapConfig } = preset;
					
					const overrides = {};
					if (config.duration !== undefined && config.duration !== '') {
						overrides.duration = config.duration;
					}
					
					if (config.stagger !== undefined && config.stagger !== '') {
						overrides.stagger = buildStaggerConfig(config.stagger, gsapConfig.stagger);
					}
					
					if (config.ease !== undefined && config.ease !== '') {
						overrides.ease = config.ease;
					}

					if (config.transformOrigin !== undefined && config.transformOrigin !== '') {
						if (config.effect === 'premium_origami_fold' || config.effect === 'premium_shutter_cascade') {
							overrides.transformOrigin = config.transformOrigin;
						}
					}

					if (config.textShadow !== undefined && config.textShadow !== '') {
						if (config.effect === 'premium_cyber_phantom') {
							overrides.textShadow = config.textShadow;
						}
					}
					
					return {
						method: runAsTo ? 'to' : 'from',
						props: { ...shared, ...gsapConfig, ...overrides, force3D: true }
					};
				}
			}
			return null;
		}
	}
}

/**
 * Builds a GSAP-compatible stagger object from the stored data.
 * The stored stagger data can be:
 *   - A legacy number (e.g. 0.02)
 *   - A stringified legacy number
 *   - An object: { val: 0.02, type: 'each', from: 'start', repeat: 0, yoyo: false, ease: '' }
 */
function buildStaggerConfig(staggerData, presetStagger = null) {
	let userStagger = {};
	if (typeof staggerData === 'object' && staggerData !== null) {
		if (staggerData.type === 'amount') {
			userStagger.amount = staggerData.val;
		} else {
			userStagger.each = staggerData.val;
		}
		if (staggerData.from) userStagger.from = staggerData.from;
		if (staggerData.ease) userStagger.ease = staggerData.ease;
		if (staggerData.repeat !== undefined && staggerData.repeat !== 0) userStagger.repeat = staggerData.repeat;
		if (staggerData.yoyo) userStagger.yoyo = staggerData.yoyo;
		if (staggerData.grid) {
			let g = staggerData.grid;
			if (typeof g === 'string' && g.startsWith('[') && g.endsWith(']')) {
				try { g = JSON.parse(g); } catch (e) {}
			}
			userStagger.grid = g;
		}
		if (staggerData.axis) userStagger.axis = staggerData.axis;
	} else {
		userStagger.each = parseNum(staggerData, 0.02);
	}

	if (typeof presetStagger === 'object' && presetStagger !== null) {
		return { ...presetStagger, ...userStagger };
	}
	
	// If presetStagger is just a number (e.g. `stagger: 0.05`), we map it to `each`
	if (typeof presetStagger === 'number') {
		return { each: presetStagger, ...userStagger };
	}

	return userStagger;
}

/** Build the SplitText instance for `effect` and return the tween targets.
 *  Returns null when the effect needs no split (target the element itself). */
function splitFor(el, effect, config) {
	let recipe = SPLIT_RECIPE[effect];

	const isPremium = effect && effect.startsWith('premium_');
	if (isPremium) {
		recipe = { type: 'chars, words', target: 'chars', perspective: 1500 };
	}

	if (!recipe || !recipe.type) return null;

	const SplitText = getSplitText();
	if (!SplitText) return null;

	// Target the innermost text element so SplitText doesn't wrap outer divs/styles
	let targetEl = getInnerElement(el);

	if (recipe.parentClass) targetEl.classList.add(recipe.parentClass);

	const opts = { type: recipe.type };

	if (recipe.linesClass) opts.linesClass = recipe.linesClass;
	const split = new SplitText(targetEl, opts);

	if (recipe.perspective) {
		const gsap = getGsap();
		gsap?.set(targetEl, { perspective: recipe.perspective });
	}

	if (isPremium) {
		const gsap = getGsap();
		gsap?.set([split.words, split.chars], { 
			transformStyle: "preserve-3d",
			display: "inline-block" 
		});
	}

	el[TEXT_SPLIT_KEY] = split;

	if (recipe.target === 'dynamic') {
		return split[config.scaleBreak || 'lines'] || null;
	}

	return split[recipe.target] || null;
}

/** Pick the actual tween targets for `effect` — the split collection when
 *  the recipe needs splitting, the element itself otherwise. */
function targetsFor(el, config) {
	
	const effect = config.effect;
	const pieces = splitFor(el, effect, config);

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
	resetText(el);
	bindText(el,config);
}

/** Build a PAUSED text tween used by `play_with_scroll` — ScrollTrigger
 *  advances its progress to match scroll position. Returns null if GSAP
 *  isn't loaded or the effect has no tween descriptor. */
function buildScrubbedText(el, config) {
	const gsap = getGsap();
	if (!gsap) return null;

	resetText(el);

	const pieces = targetsFor(el, config);
	if (!pieces) return null;

	const tween = textTween(config.effect, config, pieces, el);

	if (!tween) return null;

	// Allow the preset or user ease to pass through, otherwise default to linear for scrub
	const overrides = { paused: true };
	if (!tween.props || !tween.props.ease) overrides.ease = 'none';

	if (tween.method === 'fromTo') {
		el[TEXT_PLAYED] = gsap.fromTo(pieces, tween.from, { ...tween.to, ...overrides });
	} else if (tween.method === 'timeline') {
		const tl = gsap.timeline({ paused: true });
		tween.build(tl, pieces, config, el);
		el[TEXT_PLAYED] = tl;
	} else if (tween.method) {
		el[TEXT_PLAYED] = gsap[tween.method](pieces, { ...tween.props, ...overrides });
	}
	return el[TEXT_PLAYED];
}
export function bindText(el, config) {
	const mode = config.effect === 'text_invert' ? 'scrub' : modeFor(config.trigger);	
	let triggerSelector = '';
	if (config.wrapper === 'default' && config.triggerSelector == '') {
		triggerSelector = el;
	}

	wireTrigger({
		el,
		mode,
		triggerEl: resolveTriggerEl(mode, triggerSelector, config),
		markers: config.markers,
		play: () => {
			
			playText(el, config);
		},
		buildScrubbed: () => buildScrubbedText(el, config),
		config: {
			...config,
			// map text.js startPosition/endPosition to triggers.js start/end so it uses the correct offsets!
			start: config.effect === 'text_invert' ? config.invertStart : (config.effect === 'text_spin' ? config.spinStart : (config.startPosition || config.start)),
			end: config.effect === 'text_invert' ? config.invertEnd : (config.effect === 'text_spin' ? config.spinEnd : (config.endPosition || config.end))
		}
	});
}
