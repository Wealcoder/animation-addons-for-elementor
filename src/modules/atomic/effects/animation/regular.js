/* eslint-env browser */

import { wireTrigger, modeFor, resolveTriggerEl } from './triggers';

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
	console.log({
		effect,
		method:          r(cfg, 'method',  'from'),
		trigger:         r(cfg, 'trigger', 'on_scroll'),
		triggerSelector: r(cfg, 'triggerSelector', ''),
		
		wrapper:         r(cfg, 'wrapper', 'default'),
		startTrigger:    r(cfg, 'startTrigger', ''),
		endTrigger:      r(cfg, 'endTrigger', ''),
		start:           r(cfg, 'startPosition', 'top center'),
		end:             r(cfg, 'endPosition', 'bottom bottom'),

		easing:          r(cfg, 'easing',   'power2.out'),
		duration:        Number(r(cfg, 'duration', 1.5)),
		delay:           Number(r(cfg, 'delay',    0.15)),
		// Non-responsive: cfg.markers is a top-level boolean.
		markers:         !!cfg.markers,

		// custom
		customProps:     (Array.isArray(pickConfigResponsive(cfg, 'customProps'))
			? pickConfigResponsive(cfg, 'customProps')
			: []).map(p => (p && p.k ? { ...p, k: p.k.toLowerCase() } : p)),
		customPropsTo:   (Array.isArray(pickConfigResponsive(cfg, 'customPropsTo'))
			? pickConfigResponsive(cfg, 'customPropsTo')
			: []).map(p => (p && p.k ? { ...p, k: p.k.toLowerCase() } : p)),
	});
	return {
		effect,
		method:          r(cfg, 'method',  'from'),
		trigger:         r(cfg, 'trigger', 'on_scroll'),
		triggerSelector: r(cfg, 'triggerSelector', ''),
		
		wrapper:         r(cfg, 'wrapper', 'default'),
		startTrigger:    r(cfg, 'startTrigger', ''),
		endTrigger:      r(cfg, 'endTrigger', ''),
		start:           r(cfg, 'startPosition', 'top center'),
		end:             r(cfg, 'endPosition', 'bottom bottom'),

		easing:          r(cfg, 'easing',   'power2.out'),
		duration:        Number(r(cfg, 'duration', 1.5)),
		delay:           Number(r(cfg, 'delay',    0.15)),
		// Non-responsive: cfg.markers is a top-level boolean.
		markers:         !!cfg.markers,

		// custom
		customProps:     (Array.isArray(pickConfigResponsive(cfg, 'customProps'))
			? pickConfigResponsive(cfg, 'customProps')
			: []).map(p => (p && p.k ? { ...p, k: p.k.toLowerCase() } : p)),
		customPropsTo:   (Array.isArray(pickConfigResponsive(cfg, 'customPropsTo'))
			? pickConfigResponsive(cfg, 'customPropsTo')
			: []).map(p => (p && p.k ? { ...p, k: p.k.toLowerCase() } : p)),
	};
}

/**
 * Build `from` / `to` tween targets for each effect. Mirrors text.js's
 * textTween() — the runtime composes from primitives instead of looking
 * up a named preset, so the editor can mix-and-match (effect=fade +
 * fadeFrom=left + fadeOffset=120) without exploding the preset table.
 */
function regularTween(config) {
	// Presets are editor-side macros that populate customProps/customPropsTo.
	// We just read those directly to build the tween target.
	console.log('lowercase issue',config);
	const fromTarget = {};
	for (const { k, v } of config.customProps || []) {
		if (!k) continue;
		const num = Number(v);
		fromTarget[k] = (v !== '' && Number.isFinite(num)) ? num : v;
	}

	const toTarget = {};
	for (const { k, v } of config.customPropsTo || []) {
		if (!k) continue;
		const num = Number(v);
		toTarget[k] = (v !== '' && Number.isFinite(num)) ? num : v;
	}

	const tween = { from: {}, to: {} };
	if (config.method === 'from') {
		tween.from = fromTarget;
	} else if (config.method === 'to') {
		tween.to = fromTarget; // 'To' method uses fromTarget because the UI binds the single repeater to customProps
	} else if (config.method === 'fromTo') {
		tween.from = fromTarget;
		tween.to = toTarget;
	} else {
		return null;
	}

	tween.duration = tween.to.duration ?? tween.from.duration ?? config.duration;
	tween.delay = tween.to.delay ?? tween.from.delay ?? config.delay;
	tween.ease = tween.to.ease ?? tween.to.easing ?? tween.from.ease ?? tween.from.easing ?? config.easing;

	delete tween.from.duration; delete tween.from.delay; delete tween.from.ease; delete tween.from.easing;
	delete tween.to.duration; delete tween.to.delay; delete tween.to.ease; delete tween.to.easing;
	
	return tween;
}

/** Strip ONLY the props this effect touched. `clearProps: 'all'` would
 *  erase Elementor's own inline styles. */
function clearPropsFor(fromObj, toObj) {
	const props = new Set([
		...Object.keys(fromObj || {}),
		...Object.keys(toObj || {})
	]);
	return props.size ? Array.from(props).join(',') : false;
}

function buildRegularTween(el, config, isPaused = false, isScrubbed = false) {
	console.log(config);
	const gsap = getGsap();
	if (!gsap) return null;

	if (el[REGULAR_PLAYED]) el[REGULAR_PLAYED].kill();

	const tweenCfg = regularTween(config);
	if (!tweenCfg) return null;

	const overrides = {};
	if (isPaused || isScrubbed) overrides.paused = true;
	if (isScrubbed) overrides.ease = 'none';

	if (config.method === 'to') {
		el[REGULAR_PLAYED] = gsap.to(el, {
			...tweenCfg.to,
			duration: tweenCfg.duration,
			delay: tweenCfg.delay,
			ease: tweenCfg.ease,
			clearProps: clearPropsFor(tweenCfg.from, tweenCfg.to),
			...overrides
		});
	} else if (config.method === 'fromTo') {
		el[REGULAR_PLAYED] = gsap.fromTo(el, tweenCfg.from, {
			...tweenCfg.to,
			duration: tweenCfg.duration,
			delay: tweenCfg.delay,
			ease: tweenCfg.ease,
			clearProps: clearPropsFor(tweenCfg.from, tweenCfg.to),
			...overrides
		});
	} else {
		el[REGULAR_PLAYED] = gsap.from(el, {
			...tweenCfg.from,
			duration: tweenCfg.duration,
			delay: tweenCfg.delay,
			ease: tweenCfg.ease,
			clearProps: clearPropsFor(tweenCfg.from, tweenCfg.to),
			...overrides
		});
	}
	return el[REGULAR_PLAYED];
}

export function playRegular(el, config) {
	buildRegularTween(el, config, false, false);
}

function buildScrubbedRegular(el, config) {
	return buildRegularTween(el, config, false, true);
}

/**
 * Restore the element to its pre-animation state. Used when the editor's
 * `Enable On Editor` toggle flips OFF — kill the tween and revert any
 * GSAP-applied inline styles so the canvas reflects "no animation".
 */
export function resetRegular(el) {
	if (!el[REGULAR_PLAYED]) return;
	
	const tween = el[REGULAR_PLAYED];
	const clearProps = tween.vars && tween.vars.clearProps;
	
	try { tween.revert(); } catch (_) { /* ignore */ }
	tween.kill?.();
	delete el[REGULAR_PLAYED];

	if (clearProps) {
		const gsap = getGsap();
		if (gsap) gsap.set(el, { clearProps });
	}
}

export function bindRegular(el, config) {
	const mode = modeFor(config.trigger);
	let triggerSelector = '';
	if (config.wrapper === 'default' && config.triggerSelector == '') {
		triggerSelector = el;
	}

	if (mode !== 'scrub' && mode !== 'page-load') {
		buildRegularTween(el, config, true, false);
	}

	wireTrigger({
		el,
		mode,
		animation: el[REGULAR_PLAYED],
		triggerEl: resolveTriggerEl(mode, triggerSelector, config),
		markers: config.markers,
		play: () => {
			if (el[REGULAR_PLAYED]) {
				if (el[REGULAR_PLAYED].paused()) {
					el[REGULAR_PLAYED].play();
				} else {
					el[REGULAR_PLAYED].restart(true);
				}
			} else {
				setTimeout(() => {
					el._aaeTriggerPlay = true;
					playRegular(el, config);
					delete el._aaeTriggerPlay;
				}, 0);
			}
		},
		buildScrubbed: () => buildScrubbedRegular(el, config),
		config: {
			...config,
			start: config.startPosition || config.start,
			end: config.endPosition || config.end
		}
	});
}
