/* eslint-env browser */

/**
 * Image Animation — REPEATER runtime.
 *
 * Config: { rows: [ <interaction>, ... ], rows_<bp>: [...] }. Each row is one
 * independent image interaction (effect + trigger + config). Effects:
 *   - reveal  : clip-path wipe (preset)
 *   - scale   : scale from→to (preset)
 *   - stretch : width/border-radius grow (preset)
 *   - custom  : user GSAP from/to props (like regular animation)
 *   - glass_broken : shard-shatter reveal (new)
 *
 * Every row drives through the shared trigger dispatcher (wireTrigger), so
 * trigger is now a per-row choice (page-load / scroll / play-scroll / click /
 * hover) instead of being hardcoded per effect.
 */

import { wireTrigger, modeFor, resolveTriggerEl } from '../animation/triggers';

const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const IMG_MAP = 'AAE_INTERACTIONS_IMG';
export const IMG_PLAYED = '__aaeImgPlayed';
const ROWS_KEY = '__aaeImgRows';

function camelize(s) {
	const str = String(s).trim();
	const c = str.replace(/[-_ ]+([a-zA-Z])/g, (_, l) => l.toUpperCase());
	return c.charAt(0).toLowerCase() + c.slice(1);
}
function normalizeProps(arr) {
	if (!Array.isArray(arr)) return [];
	return arr.map((p) => (p && p.k ? { ...p, k: camelize(p.k) } : p));
}

/* ---------- read ---------- */

export function readImg(el) {
	const cfg = configFor(el, IMG_MAP);
	if (!cfg) return null;

	const rows = pickConfigResponsive(cfg, 'rows');
	if (!Array.isArray(rows) || rows.length === 0) return null;

	const rowConfigs = rows.map((row) => normalizeRow(row)).filter(Boolean);
	if (!rowConfigs.length) return null;
	return { rows: rowConfigs };
}

function normalizeRow(row) {
	if (!row || typeof row !== 'object') return null;
	const effect = row.effect;
	if (!effect || effect === 'none') return null;

	return {
		effect,
		trigger: row.trigger || 'on_scroll',
		triggerSelector: row.triggerSelector || '',
		start: row.startPosition || 'top center',
		end: row.endPosition || 'bottom bottom',
		startPosition: row.startPosition || 'top center',
		endPosition: row.endPosition || 'bottom bottom',
		startTrigger: row.startTrigger || '',
		endTrigger: row.endTrigger || '',
		markers: !!row.markers,
		duration: Number(row.duration ?? 1.5),
		delay: Number(row.delay ?? 0),
		ease: row.ease || 'power2.out',
		method: row.method || 'from',
		// reveal
		startFrom: row.startFrom || 'right',
		// scale
		scaleStart: Number(row.scaleStart ?? 0.5),
		scaleEnd: Number(row.scaleEnd ?? 1),
		// custom / preset props
		customProps: normalizeProps(row.customProps),
		customPropsTo: normalizeProps(row.customPropsTo),
	};
}

/* ---------- media target ---------- */

function findMedia(el) {
	return el.querySelector('img, svg') || el;
}

/* ---------- per-effect tween builders ----------
 * Each returns a GSAP tween/timeline (paused when `paused` true) that the
 * trigger dispatcher plays. They DON'T create their own ScrollTrigger — the
 * shared wireTrigger owns that.
 */

function buildRevealTween(el, config, paused) {
	const gsap = getGsap();
	if (!gsap) return null;
	const image = findMedia(el);
	const wrap = image.closest('.aae-img-reveal-wrap') || el;

	gsap.killTweensOf([wrap, image]);
	gsap.set([wrap, image], { clearProps: 'all' });

	const clip = {
		left: 'inset(0 0 0 100%)',
		right: 'inset(0 100% 0 0)',
		top: 'inset(100% 0 0 0)',
		bottom: 'inset(0 0 100% 0)',
	}[config.startFrom] || 'inset(0 100% 0 0)';

	const tl = gsap.timeline({ paused: !!paused });
	tl.set(wrap, { autoAlpha: 1 });
	tl.from(wrap, { clipPath: clip, duration: config.duration, ease: config.ease }, 0);
	tl.from(image, { scale: 1.0, duration: config.duration, ease: config.ease }, 0);
	return tl;
}

function buildScaleTween(el, config, paused, scrub) {
	const gsap = getGsap();
	if (!gsap) return null;
	const image = findMedia(el);
	gsap.killTweensOf(image);
	gsap.set(image, { clearProps: 'transform' });
	if (image.parentElement) image.parentElement.style.overflow = 'hidden';

	return gsap.fromTo(
		image,
		{ scale: config.scaleStart },
		{
			scale: config.scaleEnd,
			duration: config.duration,
			ease: scrub ? 'none' : config.ease,
			paused: !!paused,
		}
	);
}

function buildStretchTween(el, config, paused, scrub) {
	const gsap = getGsap();
	if (!gsap) return null;
	const image = findMedia(el);
	gsap.killTweensOf(image);
	return gsap.to(image, {
		width: '100%',
		borderRadius: '0px',
		duration: config.duration,
		ease: scrub ? 'none' : config.ease,
		paused: !!paused,
	});
}

/** custom GSAP from/to props (mirrors regular.js). */
function buildCustomTween(el, config, paused, scrub) {
	const gsap = getGsap();
	if (!gsap) return null;
	const image = findMedia(el);

	const toTarget = (pairs) => {
		const out = {};
		for (const { k, v } of pairs || []) {
			if (!k) continue;
			if (v === '' || v === null || v === undefined) continue;
			const num = Number(v);
			out[k] = Number.isFinite(num) ? num : v;
		}
		return out;
	};
	const from = toTarget(config.customProps);
	const to = toTarget(config.customPropsTo);

	const timing = {
		duration: config.duration,
		delay: config.delay,
		ease: scrub ? 'none' : config.ease,
		paused: !!paused,
	};

	if (config.method === 'set') {
		// Instant state — no tween/duration. Returns a zero-duration tween so
		// the row's playedKey / replay bookkeeping still works.
		return gsap.set(image, { ...from });
	}
	if (config.method === 'to') {
		return gsap.to(image, { ...from, ...timing });
	}
	if (config.method === 'fromTo') {
		return gsap.fromTo(image, from, { ...to, ...timing });
	}
	return gsap.from(image, { ...from, ...timing });
}

/** Build one row's tween for the given effect. reveal/scale/stretch are
 *  built-in presets with bespoke logic; everything else (custom + premium
 *  presets like fadeUp/blurReveal) runs through the custom-props tween — the
 *  editor fills custom_props from the preset table, so they're identical at
 *  runtime. */
function buildRowTween(el, config, paused, scrub) {
	switch (config.effect) {
		case 'reveal':  return buildRevealTween(el, config, paused);
		case 'scale':   return buildScaleTween(el, config, paused, scrub);
		case 'stretch': return buildStretchTween(el, config, paused, scrub);
		default:        return buildCustomTween(el, config, paused, scrub);
	}
}

/* ---------- per-element row state ---------- */

function getRowState(el) {
	return Array.isArray(el[ROWS_KEY]) ? el[ROWS_KEY] : [];
}

function killAllRows(el) {
	const gsap = getGsap();
	const state = getRowState(el);
	const image = findMedia(el);
	const wrap = image && image.closest ? (image.closest('.aae-img-reveal-wrap') || (image.parentElement || el)) : el;

	for (const entry of state) {
		try { entry.dispose && entry.dispose(); } catch (_) {}
		// DON'T revert() — for `from`-based effects (reveal timeline, 3D
		// presets) revert() returns the element to the tween's PRE state, i.e.
		// the hidden / clipped / rotated start, leaving the image stuck. Just
		// kill the tween and clear the props it touched (below) so we land on
		// the natural resting state instead.
		if (entry.tween) {
			try { entry.tween.kill?.(); } catch (_) {}
		}
	}
	el[ROWS_KEY] = [];
	delete el[IMG_PLAYED];

	if (gsap) {
		try {
			gsap.killTweensOf(el);
			gsap.killTweensOf(image);
			// Clear everything our effects can set on the image + reveal wrap,
			// returning the element to its natural (CSS-defined) appearance.
			gsap.set(image, { clearProps: 'transform,opacity,visibility,clipPath,width,borderRadius,filter,scale' });
			if (wrap && wrap !== image) {
				gsap.set(wrap, { clearProps: 'clipPath,opacity,visibility,overflow' });
			}
		} catch (_) {}
	}
}

/* ---------- kind interface ---------- */

export function resetImg(el) {
	killAllRows(el);
}

export function playImg(el, mapConfig) {
	const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];
	killAllRows(el);

	const state = [];
	for (const rowCfg of rows) {
		const tween = buildRowTween(el, rowCfg, false, false);
		state.push({ config: rowCfg, tween, dispose: null });
		if (tween) el[IMG_PLAYED] = tween;
	}
	el[ROWS_KEY] = state;
}

const SCROLL_MODES = ['scroll-tied', 'scrub', 'in-view'];

/** Editor-only: play ONE row in isolation (per-row play icon). Scroll-style
 *  rows bind their real ScrollTrigger (with markers); others play once. */
export function playImgRow(el, mapConfig, rowIndex = 0, explicitRow = null) {
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

	const mode = modeFor(rowCfg.trigger);
	if (SCROLL_MODES.includes(mode)) {
		bindImg(el, { rows: [rowCfg] }, true);
		return;
	}

	const tween = buildRowTween(el, rowCfg, false, false);
	el[ROWS_KEY] = [{ config: rowCfg, tween, dispose: null }];
	if (tween) el[IMG_PLAYED] = tween;
}

export function bindImg(el, mapConfig, forcePreview = false) {
	const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];
	killAllRows(el);

	// Editor: don't auto-fire scroll / page-load / scrub rows on load — keep the
	// canvas resting. Interactive rows (click / hover) DO bind so the user can
	// trigger them; the rest preview via ▶ play. The published frontend binds
	// everything. forcePreview overrides so a single scroll row previews markers.
	const isEditMode = !forcePreview && !!(window.elementorFrontend
		&& window.elementorFrontend.isEditMode
		&& window.elementorFrontend.isEditMode());

	const state = [];

	for (const config of rows) {
		const mode = modeFor(config.trigger);

		if (isEditMode && mode !== 'hover' && mode !== 'click' && mode !== 'slide-change') {
			state.push({ config, tween: null, dispose: null });
			continue;
		}

		const entry = { config, tween: null, dispose: null };
		state.push(entry);

		// slide-change replays the entrance every time the slide is entered. The
		// image effects (reveal clip-path %, scale) build their "from" state from
		// the element's CURRENT geometry, so a tween cached from an earlier play —
		// built while the slide was off-screen / mid-transition — restarts from a
		// stale (wrong) start box and only the tail shows. Rebuild fresh each time
		// so the entrance always measures the now-settled slide and plays in full.
		const rebuildEachPlay = mode === 'slide-change';

		const play = () => {
			// `set` is instant — re-apply each time the trigger fires.
			// slide-change rows also rebuild every time (fresh geometry, see above).
			if (config.method === 'set' || rebuildEachPlay) {
				if (entry.tween) { try { entry.tween.kill?.(); } catch (_) {} }
				const live = buildRowTween(el, config, false, false);
				entry.tween = live;
				if (live) el[IMG_PLAYED] = live;
				return;
			}
			if (entry.tween) {
				if (entry.tween.paused()) {
					entry.tween.play();
				} else {
					entry.tween.restart(true);
				}
			} else {
				const live = buildRowTween(el, config, false, false);
				entry.tween = live;
				if (live) el[IMG_PLAYED] = live;
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
				if (t) el[IMG_PLAYED] = t;
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

/* ---------- register ---------- */

window.AAEADDON.register({
	name: 'image-animation',
	mapName: IMG_MAP,
	boundFlag: 'aae-img-anim-bound',
	playedKey: IMG_PLAYED,
	read: readImg,
	play: playImg,
	playRow: playImgRow,
	bind: bindImg,
	unbind: resetImg,
	reset: resetImg,
});
