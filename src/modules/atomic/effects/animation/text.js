/* eslint-env browser */

/**
 * Text animation kind — REPEATER runtime.
 *
 * Config shape: { rows: [ <interaction>, ... ], rows_<bp>: [...] }. Each row
 * is one independent text interaction (effect + trigger + timing). This kind
 * binds N triggers / tweens — one per row.
 *
 * CRITICAL split design (the source of every past "last-only" / invisible-
 * text bug):
 *
 *   - We build ONE class-free SplitText per element ('lines,words,chars'),
 *     cached on el.__aaeTextSplit, and SHARE it across every row. SplitText
 *     is destructive — building it once per row would clobber the previous
 *     split, so only the last row would animate.
 *
 *   - The shared split carries NO per-effect classes. Effect-specific
 *     classes / styles (invert parentClass, reveal line-overflow, premium
 *     preserve-3d) are applied ONLY inside that row's playRow, against that
 *     row's own pieces — never on the shared element. That stops one effect's
 *     hiding CSS (opacity:0 / visibility:hidden from invert/reveal) from
 *     leaking onto another effect's text.
 *
 * Helpers come from window.AAEADDON. See note in regular.js.
 */

import { wireTrigger, modeFor, resolveTriggerEl } from './triggers';
import { PREMIUM_EFFECTS_BY_ID } from '../../extensions/text-animation/presets';

const { getGsap, getSplitText, configFor, pickConfigResponsive } = window.AAEADDON;

export const TEXT_MAP = 'AAE_INTERACTIONS_TEXT';
export const TEXT_PLAYED = '__aaeTextPlayed';

const SPLIT_KEY = '__aaeTextSplit';   // shared class-free SplitText instance
const INVERT_SPLIT_KEY = '__aaeInvertSplit'; // dedicated lines-only split for invert
const ROWS_KEY = '__aaeTextRows';     // [{ config, tween, dispose }]
const PARENT_CLASS = 'wcf-t-animation-text_invert';

/* ---------- number/time parsing ---------- */

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

/* ---------- read ---------- */

export function readText(el) {
	const cfg = configFor(el, TEXT_MAP);
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
		wrapper: row.wrapper || 'default',
		startTrigger: row.startTrigger || '',
		endTrigger: row.endTrigger || '',
		start: row.startPosition || 'top 85%',
		end: row.endPosition || 'bottom 30%',
		startPosition: row.startPosition || 'top 85%',
		endPosition: row.endPosition || 'bottom 30%',
		markers: !!row.markers,
		delay: parseTimeValue(row.delay, 0.15),
		duration: parseTimeValue(row.duration, 1),
		stagger: row.stagger ?? 0.02,
		translateX: parseNum(row.translateX, 20),
		translateY: parseNum(row.translateY, 0),
		rotationDir: row.rotationDir || 'x',
		rotation: parseNum(row.rotation, -80),
		transformOrigin: row.transformOrigin || '',
		textShadow: row.textShadow || '',
		invertStart: row.invertStart || 'top 85%',
		invertEnd: row.invertEnd || 'bottom center',
		scaleNum: parseNum(row.scaleNum, 1.5),
		scaleBreak: row.scaleBreak || 'lines',
		scaleEase: row.scaleEase || 'back',
		ease: row.ease || '',
	};
}

/* ---------- shared, class-free split ---------- */

/**
 * Build (or reuse) the element's single class-free SplitText covering
 * lines + words + chars. Every row picks the collection it needs from this.
 * No linesClass / parentClass here — those leak hiding CSS across effects.
 */
function getSharedSplit(el) {
	if (el[SPLIT_KEY]) return el[SPLIT_KEY];
	const SplitText = getSplitText();
	if (!SplitText) return null;
	try {
		el[SPLIT_KEY] = new SplitText(el, { type: 'lines,words,chars' });
	} catch (_) {
		return null;
	}
	return el[SPLIT_KEY];
}

function revertSharedSplit(el) {
	const split = el[SPLIT_KEY];
	if (split && typeof split.revert === 'function') {
		try { split.revert(); } catch (_) {}
	}
	delete el[SPLIT_KEY];

	// Clear element-level inline state that move / premium effects set
	// (perspective). Also defensively strip the invert parent class + CSS var in
	// case any stale state lingers (invert now owns these via revertInvertSplit).
	el.classList.remove(PARENT_CLASS);
	try { el.style.removeProperty('--text-color'); } catch (_) {}
	const gsap = getGsap();
	if (gsap) { try { gsap.set(el, { clearProps: 'perspective' }); } catch (_) {} }
}

/* ---------- text_invert: dedicated, V3-faithful path ----------
 *
 * Invert is special: V3 builds its OWN `type:"lines"` SplitText (linesClass
 * "invert-line") — NOT the shared lines/words/chars split — so each line is a
 * single element with text directly inside it. That's required for
 * `-webkit-background-clip: text` to clip the gradient to the glyphs. With the
 * shared split, lines wrap nested word/char divs (no direct text), so clipping
 * on the line shows nothing. We replicate V3 exactly and bind a per-line scrub
 * ScrollTrigger that sweeps background-position-x → 0. */

function computeInvertTextColor(el) {
	const colorStr = window.getComputedStyle(el).color;
	const rgb = colorStr.match(/\d+/g);
	if (!rgb || rgb.length < 3) return;
	const rr = parseInt(rgb[0]) / 255;
	const gg = parseInt(rgb[1]) / 255;
	const bb = parseInt(rgb[2]) / 255;
	const max = Math.max(rr, gg, bb);
	const min = Math.min(rr, gg, bb);
	const chroma = max - min;
	const l = (max + min) / 2;
	let h = 0;
	let s = 0;
	if (chroma !== 0) {
		s = l <= 0.5 ? chroma / (max + min) : chroma / (2 - (max + min));
		switch (max) {
			case rr: h = (gg - bb) / chroma + (gg < bb ? 6 : 0); break;
			case gg: h = (bb - rr) / chroma + 2; break;
			case bb: h = (rr - gg) / chroma + 4; break;
		}
		h *= 60;
	}
	el.style.setProperty('--text-color', `${h.toFixed(1)}, ${(s * 100).toFixed(1)}%, ${(l * 100).toFixed(1)}%`);
}

function revertInvertSplit(el) {
	const split = el[INVERT_SPLIT_KEY];
	if (split && typeof split.revert === 'function') {
		try { split.revert(); } catch (_) {}
	}
	delete el[INVERT_SPLIT_KEY];
	el.classList.remove(PARENT_CLASS);
	try { el.style.removeProperty('--text-color'); } catch (_) {}
}

/**
 * Build the invert effect on `el`. Returns a disposer that kills the per-line
 * ScrollTriggers and reverts the dedicated split. `forcePreview` plays a quick
 * non-scroll preview (editor ▶) instead of a scroll-tied tween.
 */
function buildInvert(el, config, forcePreview = false) {
	const gsap = getGsap();
	const SplitText = getSplitText();
	if (!gsap || !SplitText) return () => {};

	// Fresh dedicated split each time.
	revertInvertSplit(el);
	el.classList.add(PARENT_CLASS);
	computeInvertTextColor(el);

	let split;
	try {
		split = new SplitText(el, { type: 'lines', linesClass: 'invert-line' });
	} catch (_) {
		return () => {};
	}
	el[INVERT_SPLIT_KEY] = split;

	const lines = split.lines || [];
	// Gradient + clip on each line (V3's .invert-line CSS, inlined so the
	// atomic frontend needs no extra stylesheet). --text-color inherits from el.
	lines.forEach((line) => {
		line.style.setProperty('background-image', 'linear-gradient(to right, hsla(var(--text-color), 1) 50%, hsla(var(--text-color), 0.3) 50%)', 'important');
		line.style.setProperty('background-size', '200% 100%', 'important');
		line.style.setProperty('background-repeat', 'no-repeat', 'important');
		line.style.setProperty('background-position-x', '100%');
		line.style.setProperty('color', 'transparent', 'important');
		line.style.setProperty('-webkit-background-clip', 'text', 'important');
		line.style.setProperty('background-clip', 'text', 'important');
		line.style.setProperty('-webkit-text-fill-color', 'transparent', 'important');
	});

	const tweens = [];
	const start = config.invertStart || 'top 85%';
	const end = config.invertEnd || 'bottom center';

	if (forcePreview) {
		// Editor ▶ preview: one quick sweep per line, no scroll dependency.
		lines.forEach((line) => {
			tweens.push(gsap.fromTo(line,
				{ backgroundPositionX: '100%' },
				{ backgroundPositionX: '0%', ease: 'none', duration: config.duration || 1 }
			));
		});
	} else {
		// Frontend: per-line scrub ScrollTrigger (exactly V3).
		lines.forEach((line) => {
			tweens.push(gsap.to(line, {
				backgroundPositionX: '0%',
				ease: 'none',
				scrollTrigger: {
					trigger: line,
					scrub: 1,
					start,
					end,
					markers: !!config.markers,
				},
			}));
		});
	}

	return () => {
		tweens.forEach((t) => {
			try { t.scrollTrigger?.kill?.(); } catch (_) {}
			try { t.kill?.(); } catch (_) {}
		});
		revertInvertSplit(el);
	};
}

/** Which split collection a given effect tweens. null → whole element. */
function piecesFor(el, config) {
	const split = getSharedSplit(el);
	if (!split) {
		// No SplitText (e.g. premium plugin absent) — fall back to the element
		// for effects that can run whole-element; otherwise nothing.
		return [el];
	}
	const effect = config.effect;
	const isPremium = !!PREMIUM_EFFECTS_BY_ID[effect];

	if (isPremium || effect === 'char') return split.chars || [el];
	if (effect === 'word') return split.words || [el];
	if (effect === 'text_move') return split.lines || [el];
	if (effect === 'text_reveal') return split.chars || [el];
	if (effect === 'text_scale') return split[config.scaleBreak || 'lines'] || [el];
	// Unknown → whole element.
	return [el];
}

/* ---------- stagger ---------- */

function buildStaggerConfig(userStagger, presetStagger = null) {
	const staggerObj = (typeof userStagger === 'object' && userStagger !== null)
		? userStagger
		: { each: parseNum(userStagger, 0.02) };

	if (typeof presetStagger === 'object' && presetStagger !== null) {
		return { ...presetStagger, ...staggerObj };
	}
	if (typeof presetStagger === 'number') {
		return { each: presetStagger, ...staggerObj };
	}
	return staggerObj;
}

/* ---------- per-effect tween descriptor ---------- */

/**
 * Returns { method, props } or { method:'fromTo', from, to }. Applies any
 * effect-specific DOM prep (reveal line-overflow, invert gradient/parent
 * class, premium preserve-3d) HERE, scoped to this row's pieces only.
 */
function textTween(effect, config, pieces, el) {
	const gsap = getGsap();
	const shared = {
		duration: config.duration,
		delay: config.delay,
		stagger: buildStaggerConfig(config.stagger),
		ease: 'power2.out',
	};

	switch (effect) {
		case 'char':
		case 'word':
			return {
				method: 'from',
				props: { ...shared, autoAlpha: 0, x: config.translateX, y: config.translateY },
			};

		case 'text_move':
			gsap?.set(el, { perspective: 400 });
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
			// Clip each char's owning line so chars slide up from below it.
			// Scoped to THIS row's pieces — no shared linesClass.
			pieces.forEach((p) => {
				const line = p.parentElement;
				if (line) line.style.overflow = 'hidden';
			});
			return { method: 'from', props: { ...shared, yPercent: 100, autoAlpha: 0 } };

		case 'text_scale':
			return {
				method: 'from',
				props: {
					...shared,
					scale: config.scaleNum,
					autoAlpha: 0,
					transformOrigin: '50% 0%',
					ease: config.scaleEase,
				},
			};

		// text_invert is handled by its dedicated buildInvert() path (own split +
		// per-line scroll triggers), never through this shared-pieces tween.

		default: {
			const preset = PREMIUM_EFFECTS_BY_ID[effect];
			if (!preset) return null;
			// Premium effects need 3D context on each piece.
			gsap?.set(el, { perspective: 1500 });
			gsap?.set(pieces, { transformStyle: 'preserve-3d', display: 'inline-block' });

			const { runAsTo, ...gsapConfig } = preset;
			const overrides = {};
			if (config.duration !== undefined && config.duration !== '') overrides.duration = config.duration;
			if (config.stagger !== undefined && config.stagger !== '') overrides.stagger = buildStaggerConfig(config.stagger, gsapConfig.stagger);
			if (config.ease !== undefined && config.ease !== '') overrides.ease = config.ease;
			if (config.transformOrigin) overrides.transformOrigin = config.transformOrigin;
			if (config.textShadow) overrides.textShadow = config.textShadow;

			return {
				method: runAsTo ? 'to' : 'from',
				props: { ...shared, ...gsapConfig, ...overrides, force3D: true },
			};
		}
	}
}

/* ---------- build one row's tween ---------- */

function buildRowTween(el, config, isScrub = false, isPaused = false) {
	const gsap = getGsap();
	if (!gsap) return null;

	const pieces = piecesFor(el, config);
	if (!pieces || !pieces.length) return null;

	const tween = textTween(config.effect, config, pieces, el);
	if (!tween) return null;

	const overrides = {};
	if (isPaused || isScrub) overrides.paused = true;
	if (isScrub && (!tween.props || !tween.props.ease)) overrides.ease = 'none';

	if (tween.method === 'fromTo') {
		return gsap.fromTo(pieces, tween.from, { ...tween.to, ...overrides });
	}
	if (tween.method) {
		return gsap[tween.method](pieces, { ...tween.props, ...overrides });
	}
	return null;
}

/* ---------- per-element row state ---------- */

function getRowState(el) {
	return Array.isArray(el[ROWS_KEY]) ? el[ROWS_KEY] : [];
}

function killAllRows(el) {
	const gsap = getGsap();
	const state = getRowState(el);
	for (const entry of state) {
		try { entry.dispose && entry.dispose(); } catch (_) {}
		if (entry.tween) {
			try { entry.tween.revert?.(); } catch (_) {}
			try { entry.tween.kill?.(); } catch (_) {}
		}
	}
	el[ROWS_KEY] = [];
	delete el[TEXT_PLAYED];

	// Revert both splits LAST — after every row's tween/disposer is gone — so the
	// original text DOM (and any inline styles) is restored cleanly.
	revertInvertSplit(el);
	revertSharedSplit(el);
	if (gsap) { try { gsap.killTweensOf(el); } catch (_) {} }
}

/* ---------- kind interface ---------- */

export function resetText(el) {
	killAllRows(el);
}

/** Play every row immediately (editor Play Now / replay). */
export function playText(el, mapConfig) {
	const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];
	killAllRows(el);

	const state = [];
	for (const rowCfg of rows) {
		if (rowCfg.effect === 'text_invert') {
			// Dedicated path — quick one-shot sweep for an immediate preview.
			const dispose = buildInvert(el, rowCfg, true);
			state.push({ config: rowCfg, tween: null, dispose });
			continue;
		}
		const tween = buildRowTween(el, rowCfg, false, false);
		state.push({ config: rowCfg, tween, dispose: null });
		if (tween) el[TEXT_PLAYED] = tween;
	}
	el[ROWS_KEY] = state;
}

const SCROLL_MODES = ['scroll-tied', 'scrub', 'in-view'];

/** Editor-only: play ONE row in isolation (per-row play icon). Scroll-style
 *  rows bind their real ScrollTrigger (with markers) so the editor preview
 *  shows the trigger lines; others play once. */
export function playTextRow(el, mapConfig, rowIndex = 0, explicitRow = null) {
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
		bindText(el, { rows: [rowCfg] }, true);
		return;
	}

	const tween = buildRowTween(el, rowCfg, false, false);
	el[ROWS_KEY] = [{ config: rowCfg, tween, dispose: null }];
	if (tween) el[TEXT_PLAYED] = tween;
}

/** Bind every row's trigger. Each row owns a paused tween + a disposer.
 *  `forcePreview` = bind even scroll/page-load rows in the editor (per-row ▶). */
export function bindText(el, mapConfig, forcePreview = false) {
	const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];
	killAllRows(el);

	// In the editor, scroll-tied / page-load / scrub rows must NOT auto-fire on
	// load — doing so splits the text and leaves it broken on the canvas. We
	// bind only interactive rows (click / hover) there so the user can trigger
	// them; the others preview via the per-row ▶ play. The published frontend
	// binds everything. forcePreview overrides for marker preview.
	const isEditMode = !forcePreview && !!(window.elementorFrontend
		&& window.elementorFrontend.isEditMode
		&& window.elementorFrontend.isEditMode());

	const state = [];

	for (const config of rows) {
		const mode = modeFor(config.trigger);

		// text_invert: dedicated path (own split + per-line scroll triggers).
		if (config.effect === 'text_invert') {
			if (isEditMode && !forcePreview) {
				// Don't split/sweep on the resting editor canvas; ▶ previews it.
				state.push({ config, tween: null, dispose: null });
				continue;
			}
			// forcePreview (per-row ▶) → quick one-shot sweep; frontend → scrub.
			const dispose = buildInvert(el, config, forcePreview);
			state.push({ config, tween: null, dispose });
			continue;
		}

		if (isEditMode && mode !== 'hover' && mode !== 'click' && mode !== 'slide-change') {
			// Skip auto-firing modes in the editor — keep the text intact.
			state.push({ config, tween: null, dispose: null });
			continue;
		}

		// IMPORTANT: do NOT pre-build a paused tween here. Multiple rows share
		// one SplitText, so pre-building every row's "from" state at bind time
		// stacks all their hiding styles (opacity:0, gradients, text-shadow,
		// 3D) onto the same pieces at once — leaving the text invisible /
		// artefacted before any trigger fires. Each row builds its tween
		// lazily, only when its own trigger plays.
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
				if (live) el[TEXT_PLAYED] = live;
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
				const t = buildRowTween(el, config, true, false);
				entry.tween = t;
				if (t) el[TEXT_PLAYED] = t;
				return t;
			},
			config: {
				...config,
				start: config.effect === 'text_invert' ? config.invertStart : (config.startPosition || config.start),
				end: config.effect === 'text_invert' ? config.invertEnd : (config.endPosition || config.end),
			},
			skipCleanup: true,
			skipGlobalKey: true,
		});

		entry.dispose = dispose;
	}

	el[ROWS_KEY] = state;
}
