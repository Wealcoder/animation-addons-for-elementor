/* eslint-env browser */

import { wireTrigger, modeFor, resolveTriggerEl } from './triggers';
import { PREMIUM_EFFECTS_BY_ID } from '../../extensions/text-animation/presets';

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
			// Find the actual text node to get the correct computed color, 
			// as the wrapper might just have an inherited default color.
			const textNode = el;
			const colorStr = window.getComputedStyle(textNode).color;
			const rgb = colorStr.match(/\d+/g);
			
			if (rgb && rgb.length >= 3) {
				let r = parseInt(rgb[0]) / 255;
				let g = parseInt(rgb[1]) / 255;
				let b = parseInt(rgb[2]) / 255;
				
				const max = Math.max(r, g, b);
				const min = Math.min(r, g, b);
				const chroma = max - min;
				
				const l = (max + min) / 2;
				let h = 0;
				let s = 0;
				
				if (chroma !== 0) {
					s = l <= 0.5 ? chroma / (max + min) : chroma / (2 - (max + min));
					
					switch (max) {
						case r: h = (g - b) / chroma + (g < b ? 6 : 0); break;
						case g: h = (b - r) / chroma + 2; break;
						case b: h = (r - g) / chroma + 4; break;
					}
					h *= 60;
				}
				
				el.style.setProperty('--text-color', `${h.toFixed(1)}, ${(s * 100).toFixed(1)}%, ${(l * 100).toFixed(1)}%`);
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
			const preset = PREMIUM_EFFECTS_BY_ID[effect];
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

					if (config.transformOrigin) {
						overrides.transformOrigin = config.transformOrigin;
					}

					if (config.textShadow) {
						overrides.textShadow = config.textShadow;
					}
					
					return {
						method: runAsTo ? 'to' : 'from',
						props: { ...shared, ...gsapConfig, ...overrides, force3D: true }
					};
				}
			return null;
		}
	}
}

/**
 * Builds a GSAP-compatible stagger object from the stored data.
 * The complex parsing of legacy formats is now handled by the backend PHP.
 */
function buildStaggerConfig(userStagger, presetStagger = null) {
	let staggerObj = typeof userStagger === 'object' && userStagger !== null 
		? userStagger 
		: { each: parseNum(userStagger, 0.02) };

	if (typeof presetStagger === 'object' && presetStagger !== null) {
		return { ...presetStagger, ...staggerObj };
	}
	
	// If presetStagger is just a number (e.g. `stagger: 0.05`), we map it to `each`
	if (typeof presetStagger === 'number') {
		return { each: presetStagger, ...staggerObj };
	}

	return staggerObj;
}

/** Build the SplitText instance for `effect` and return the tween targets.
 *  Returns null when the effect needs no split (target the element itself). */
function splitFor(el, effect, config) {
	let recipe = SPLIT_RECIPE[effect];

	const isPremium = effect && !!PREMIUM_EFFECTS_BY_ID[effect];
	if (isPremium) {
		recipe = { type: 'chars, words', target: 'chars', perspective: 1500 };
	}

	if (!recipe || !recipe.type) return null;

	const SplitText = getSplitText();
	if (!SplitText) return null;

	// Target the innermost text element so SplitText doesn't wrap outer divs/styles
	let targetEl = el;

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

/**
 * Core text animation builder. Handles splitting the text,
 * generating the GSAP tween/timeline based on the config, 
 * and applying optional overrides (like paused, ease).
 */
function buildTextTween(el, config, isScrub = false, isPaused = false) {
	const gsap = getGsap();
	if (!gsap) return null;

	resetText(el);
	
	const pieces = targetsFor(el, config);
	if (!pieces) return null;

	const tween = textTween(config.effect, config, pieces, el);

	if (!tween) return null;
	
	const overrides = {};
	if (isPaused || isScrub) overrides.paused = true;
	
	// Scrub mode generally prefers 'none' (linear) easing
	if (isScrub && (!tween.props || !tween.props.ease)) {
		overrides.ease = 'none';
	}

	if (tween.method === 'fromTo') {
		el[TEXT_PLAYED] = gsap.fromTo(pieces, tween.from, { ...tween.to, ...overrides });
	} else if (tween.method === 'timeline') {
		const tl = gsap.timeline({ paused: overrides.paused });
		tween.build(tl, pieces, config, el);
		el[TEXT_PLAYED] = tl;
	} else if (tween.method) {
		el[TEXT_PLAYED] = gsap[tween.method](pieces, { ...tween.props, ...overrides });
	}

	return el[TEXT_PLAYED];
}

export function playText(el, config) {
	const mode = modeFor(config.trigger);
	const isEditMode = window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode();

	if (isEditMode && (mode === 'scrub' || mode === 'scroll-tied' || mode === 'in-view')) {
		const tween = buildTextTween(el, config);
		
		// If the editor forces a replay, the preview auto-plays but detaches the ScrollTrigger!
		// We use onComplete to silently rebind and re-sync the markers afterwards.
		if (tween && !el._aaeTriggerPlay) {
			tween.eventCallback("onComplete", () => {
				bindText(el, config);
			});
		}
		return;
	}

	buildTextTween(el, config);
}

export function bindText(el, config) {
	const mode = modeFor(config.trigger);	
	let triggerSelector = '';
	if (config.wrapper === 'default' && config.triggerSelector == '') {
		triggerSelector = el;
	}
	
	// Pre-build the tween so SplitText modifies the DOM BEFORE ScrollTrigger 
	// measures it. This prevents GSAP infinite loops when markers are active.
	if (mode !== 'scrub' && mode !== 'page-load') {
		buildTextTween(el, config, false, true);
	}
	
	wireTrigger({
		el,
		mode,
		triggerEl: resolveTriggerEl(mode, triggerSelector, config),
		markers: config.markers,
		play: () => {
			if (el[TEXT_PLAYED]) {
				if (el[TEXT_PLAYED].paused()) {
					el[TEXT_PLAYED].play();
				} else {
					// Use restart so it plays again on subsequent trigger fires
					// (e.g. scroll up and down) without rebuilding the DOM!
					el[TEXT_PLAYED].restart(true);
				}
			} else {
				// Defer slightly to avoid GSAP crash if run during ST init
				setTimeout(() => {
					el._aaeTriggerPlay = true;
					playText(el, config);
					delete el._aaeTriggerPlay;
				}, 0);
			}
		},
		buildScrubbed: () => buildTextTween(el, config, true, false),
		config: {
			...config,
			// map text.js startPosition/endPosition to triggers.js start/end so it uses the correct offsets!
			start: config.effect === 'text_invert' ? config.invertStart : (config.effect === 'text_spin' ? config.spinStart : (config.startPosition || config.start)),
			end: config.effect === 'text_invert' ? config.invertEnd : (config.effect === 'text_spin' ? config.spinEnd : (config.endPosition || config.end))
		}
	});
}
