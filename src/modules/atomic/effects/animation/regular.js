/* eslint-env browser */

/**
 * Regular animation — REPEATER runtime.
 *
 * The element's config is now { rows: [ <interaction>, ... ], rows_<bp>: [...] }.
 * Each row is one independent interaction (effect + trigger + tween config).
 * This kind binds N triggers / tweens — one per row — and stores per-row
 * state (tween + trigger disposer) in arrays on the element so they never
 * clobber each other.
 *
 * common.js still calls read/bind/play/reset ONCE per element; the
 * multiplicity lives entirely inside this adapter. The single `playedKey`
 * (el.__aaeAnimPlayed) is kept pointing at the LAST row's tween purely so
 * common.js's chain-completion / kill bookkeeping has something to hook —
 * the authoritative state is el.__aaeAnimRows.
 */

import { wireTrigger, modeFor, resolveTriggerEl } from './triggers';

const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const ANIM_MAP = 'AAE_INTERACTIONS_ANIM';
export const REGULAR_PLAYED = '__aaeAnimPlayed';

/** Per-element array of { config, tween, dispose } — one entry per row. */
const ROWS_KEY = '__aaeAnimRows';

const camelize = (s) => {
	const str = String(s).trim();
	const c = str.replace(/[-_ ]+([a-zA-Z])/g, (_, l) => l.toUpperCase());
	return c.charAt(0).toLowerCase() + c.slice(1);
};

function normalizeProps(arr) {
	if (!Array.isArray(arr)) return [];
	return arr.map((p) => (p && p.k ? { ...p, k: camelize(p.k) } : p));
}

/**
 * Read the rows array for the active breakpoint. The PHP side emits
 * `rows` (desktop) plus optional `rows_<bp>` overrides; pickConfigResponsive
 * walks the cascade for us (treats `rows` as the base key, `rows_tablet` etc.
 * as per-bp variants).
 */
export function readRegular(el) {
	const cfg = configFor(el, ANIM_MAP);
	if (!cfg) return null;

	const rows = pickConfigResponsive(cfg, 'rows');
	if (!Array.isArray(rows) || rows.length === 0) return null;

	const rowConfigs = rows
		.map((row) => normalizeRow(row))
		.filter(Boolean);

	if (!rowConfigs.length) return null;
	return { rows: rowConfigs };
}

/** One emitted row → the normalized per-row config the tween builder needs. */
function normalizeRow(row) {
	if (!row || typeof row !== 'object') return null;
	const effect = row.effect;
	if (!effect || effect === 'none') return null;

	return {
		effect,
		method: row.method || 'from',
		trigger: row.trigger || 'on_scroll',
		triggerSelector: row.triggerSelector || '',
		wrapper: row.wrapper || 'default',
		startTrigger: row.startTrigger || '',
		endTrigger: row.endTrigger || '',
		start: row.startPosition || 'top center',
		end: row.endPosition || 'bottom bottom',
		startPosition: row.startPosition || 'top center',
		endPosition: row.endPosition || 'bottom bottom',
		easing: row.easing || 'power2.out',
		duration: Number(row.duration ?? 1.5),
		delay: Number(row.delay ?? 0.15),
		markers: !!row.markers,
		customProps: normalizeProps(row.customProps),
		customPropsTo: normalizeProps(row.customPropsTo),
	};
}

/* ---------- tween building (per-row, unchanged logic) ---------- */

function regularTween(config) {
	const fromTarget = {};
	for (const { k, v } of config.customProps || []) {
		if (!k) continue;
		let finalVal = v;
		if (k === 'stagger' && typeof v === 'string' && v.startsWith('{')) {
			try { finalVal = JSON.parse(v); } catch (e) {}
		} else if (typeof v === 'string' && v.startsWith('__JS__')) {
			try {
				const body = v.replace('__JS__', '');
				finalVal = new Function('index', 'target', 'targets', body);
			} catch (e) {
				console.error('AAE GSAP Custom Function Error:', e);
			}
		}

		if (typeof finalVal === 'function') {
			fromTarget[k] = finalVal;
		} else if (typeof finalVal === 'string' && finalVal.startsWith('random(')) {
			fromTarget[k] = finalVal;
		} else {
			// Skip empty values entirely — passing '' / null into a GSAP
			// transform prop makes it call ''.split() internally and throw
			// ("o.split is not a function"). A row with no value contributes
			// nothing to the tween.
			if (finalVal === '' || finalVal === null || finalVal === undefined) continue;
			const num = Number(finalVal);
			fromTarget[k] = Number.isFinite(num) ? num : finalVal;
		}
	}

	const toTarget = {};
	for (const { k, v } of config.customPropsTo || []) {
		if (!k) continue;
		let finalVal = v;
		if (k === 'stagger' && typeof v === 'string' && v.startsWith('{')) {
			try { finalVal = JSON.parse(v); } catch (e) {}
		} else if (typeof v === 'string' && v.startsWith('random(')) {
			finalVal = v;
		}

		if (typeof finalVal === 'string' && finalVal.startsWith('random(')) {
			toTarget[k] = finalVal;
		} else {
			if (finalVal === '' || finalVal === null || finalVal === undefined) continue;
			const num = Number(finalVal);
			toTarget[k] = Number.isFinite(num) ? num : finalVal;
		}
	}

	const tween = { from: {}, to: {} };
	if (config.method === 'from') {
		tween.from = fromTarget;
	} else if (config.method === 'to') {
		tween.to = fromTarget;
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

function clearPropsFor(fromObj, toObj) {
	const props = new Set([
		...Object.keys(fromObj || {}),
		...Object.keys(toObj || {}),
	]);
	return props.size ? Array.from(props).join(',') : false;
}

/** Build one row's tween. Returns the GSAP tween (or null). */
function buildRowTween(el, config, isPaused = false, isScrubbed = false) {
	const gsap = getGsap();
	if (!gsap) return null;

	const tweenCfg = regularTween(config);
	if (!tweenCfg) return null;

	const overrides = {};
	if (isPaused || isScrubbed) overrides.paused = true;
	if (isScrubbed) overrides.ease = 'none';

	let tween;
	if (config.method === 'to') {
		tween = gsap.to(el, {
			...tweenCfg.to,
			duration: tweenCfg.duration,
			delay: tweenCfg.delay,
			ease: tweenCfg.ease,
			clearProps: clearPropsFor(tweenCfg.from, tweenCfg.to),
			...overrides,
		});
	} else if (config.method === 'fromTo') {
		tween = gsap.fromTo(el, tweenCfg.from, {
			...tweenCfg.to,
			duration: tweenCfg.duration,
			delay: tweenCfg.delay,
			ease: tweenCfg.ease,
			clearProps: clearPropsFor(tweenCfg.from, tweenCfg.to),
			...overrides,
		});
	} else {
		tween = gsap.from(el, {
			...tweenCfg.from,
			duration: tweenCfg.duration,
			delay: tweenCfg.delay,
			ease: tweenCfg.ease,
			clearProps: clearPropsFor(tweenCfg.from, tweenCfg.to),
			...overrides,
		});
	}
	return tween;
}

/* ---------- per-element row state ---------- */

function getRowState(el) {
	return Array.isArray(el[ROWS_KEY]) ? el[ROWS_KEY] : [];
}

function killAllRows(el) {
	const gsap = getGsap();
	const state = getRowState(el);
	const propsToClear = new Set();

	for (const entry of state) {
		// 1. Tear down the trigger (ScrollTrigger / listener / observer).
		try { entry.dispose && entry.dispose(); } catch (_) {}

		const t = entry.tween;
		if (t) {
			// 2. revert() returns the element to its pre-tween state, then
			//    kill() frees the tween. Collect this row's touched props so
			//    we can clear them all in one pass afterwards.
			const clearProps = t.vars && t.vars.clearProps;
			if (typeof clearProps === 'string' && clearProps && clearProps !== 'all') {
				clearProps.split(',').forEach((p) => propsToClear.add(p.trim()));
			}
			try { t.revert(); } catch (_) {}
			try { t.kill?.(); } catch (_) {}
		}
	}

	if (gsap) {
		// 3. Kill any stray tweens GSAP still holds on this element (e.g. a
		//    rapid repeat of per-row play before the previous finished), then
		//    clear exactly the props our rows touched — never 'all', which
		//    would wipe Elementor's own inline styles.
		try { gsap.killTweensOf(el); } catch (_) {}
		if (propsToClear.size) {
			try { gsap.set(el, { clearProps: Array.from(propsToClear).join(',') }); } catch (_) {}
		}
	}

	el[ROWS_KEY] = [];
	delete el[REGULAR_PLAYED];
}

/* ---------- kind interface ---------- */

/** Play every row immediately (used by editor Play Now / replay). */
export function playRegular(el, mapConfig) {
	const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];
	killAllRows(el);

	const state = [];
	for (const rowCfg of rows) {
		const tween = buildRowTween(el, rowCfg, false, false);
		state.push({ config: rowCfg, tween, dispose: null });
		if (tween) el[REGULAR_PLAYED] = tween;
	}
	el[ROWS_KEY] = state;
}

/**
 * Editor-only: play just ONE row in isolation (per-row play icon). Kills any
 * running tweens first so the preview shows exactly that interaction's
 * effect, regardless of its real trigger (click / scroll / page-load).
 *
 * `explicitRow`, when passed by the editor, is the exact row to preview —
 * used in preference to indexing `mapConfig.rows`, because the runtime list
 * may differ from the editor list (dropped effect=none rows, exclusive-
 * trigger dedupe). It's a raw editor row, so normalize it first.
 */
const SCROLL_MODES = ['scroll-tied', 'scrub', 'in-view'];

export function playRegularRow(el, mapConfig, rowIndex = 0, explicitRow = null) {
	let rowCfg = null;
	if (explicitRow && typeof explicitRow === 'object') {
		rowCfg = normalizeRow(explicitRow);
	}
	if (!rowCfg) {
		const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];
		rowCfg = rows[rowIndex];
	}
	if (!rowCfg) return;

	killAllRows(el);

	// For scroll-style rows, bind the real ScrollTrigger (with markers) so the
	// editor preview shows the trigger lines and the user can tune start/end.
	// For page-load / click / hover, just play the tween once (isolation).
	const mode = modeFor(rowCfg.trigger);
	if (SCROLL_MODES.includes(mode)) {
		bindRegular(el, { rows: [rowCfg] }, true);
		return;
	}

	const tween = buildRowTween(el, rowCfg, false, false);
	el[ROWS_KEY] = [{ config: rowCfg, tween, dispose: null }];
	if (tween) el[REGULAR_PLAYED] = tween;
}

export function resetRegular(el) {
	killAllRows(el);
}

/** Bind every row's trigger. Each row gets its own paused tween + disposer.
 *  `forcePreview` = bind even scroll/page-load rows in the editor (used by the
 *  per-row ▶ play so scroll rows can show their ScrollTrigger markers). */
export function bindRegular(el, mapConfig, forcePreview = false) {
	const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];

	// Fresh start — kill any previous binding so editor rebinds don't stack.
	killAllRows(el);

	// In the editor, scroll-tied / page-load / scrub rows must NOT auto-fire on
	// load — keep the canvas at rest. Interactive rows (click / hover) DO bind
	// so the user can click/hover the trigger element and see the animation; the
	// non-interactive rows preview via the per-row ▶ play. The published
	// frontend binds everything. forcePreview overrides for marker preview.
	const isEditMode = !forcePreview && !!(window.elementorFrontend
		&& window.elementorFrontend.isEditMode
		&& window.elementorFrontend.isEditMode());

	const state = [];

	for (const config of rows) {
		const mode = modeFor(config.trigger);

		if (isEditMode && mode !== 'hover' && mode !== 'click') {
			state.push({ config, tween: null, dispose: null });
			continue;
		}

		// IMPORTANT: do NOT pre-build a paused tween here. A paused "from"
		// tween parks the element in its hidden start state (opacity:0, y…)
		// and, if the trigger never fires (e.g. a click row sitting idle in
		// the editor), the element just stays invisible. Build lazily — only
		// when the row's trigger actually plays.
		const entry = { config, tween: null, dispose: null };
		state.push(entry);

		const play = () => {
			if (entry.tween) {
				if (entry.tween.paused()) {
					entry.tween.play();
				} else {
					entry.tween.restart(true);
				}
			} else {
				const live = buildRowTween(el, config, false, false);
				entry.tween = live;
				if (live) el[REGULAR_PLAYED] = live;
			}
		};

		const dispose = wireTrigger({
			el,
			mode,
			// click/hover: empty Trigger Selector → self element; else querySelector.
			triggerEl: resolveTriggerEl(mode, el, config),
			markers: config.markers,
			play,
			buildScrubbed: () => {
				const t = buildRowTween(el, config, false, true);
				entry.tween = t;
				if (t) el[REGULAR_PLAYED] = t;
				return t;
			},
			config: {
				...config,
				start: config.startPosition || config.start,
				end: config.endPosition || config.end,
			},
			skipCleanup: true,
			skipGlobalKey: true,
		});

		entry.dispose = dispose;
	}

	el[ROWS_KEY] = state;
}
