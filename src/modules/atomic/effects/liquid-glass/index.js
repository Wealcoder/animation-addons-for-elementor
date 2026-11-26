/* eslint-env browser */

/**
 * Liquid Glass — a frosted, refracting glass surface for containers.
 *
 * A plain style application (no GSAP, nothing animated): `backdrop-filter`
 * blur, optionally combined with an SVG displacement filter
 * (`feTurbulence` + `feDisplacementMap`) for the "liquid" refraction, plus a
 * glass-style inner shadow, border and radius.
 *
 * Browser support: the `url(#filter)` part of backdrop-filter only works in
 * Chromium. Everywhere else (Safari, Firefox) the same element gets the plain
 * blur, so the glass still reads as glass instead of dropping the whole
 * declaration (an unsupported value would invalidate it, blur included).
 * That is detected once with CSS.supports().
 *
 * One hidden <svg> holds every filter that is used; a filter is created on
 * first use and shared by every element with the same preset + distortion.
 */

const { configFor, pickConfigResponsive } = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_LG';
const APPLIED_KEY = '__aaeLgApplied';
const SVG_NS = 'http://www.w3.org/2000/svg';
const HOLDER_ID = 'aae-lg-filters';

/**
 * blur (px) + the displacement filter. `scale` is the strength at 100%
 * distortion; a preset without `freq` is blur only. Keep the key list in sync
 * with LiquidGlass\Schema::presets().
 */
const PRESETS = {
	'frosted': { blur: 12, freq: '0.012 0.012', octaves: 2, seed: 7, scale: 28 },
	'liquid-lens': { blur: 10, freq: '0.0016 0.0016', octaves: 1, seed: 3, scale: 180, smooth: 3 },
	'ripple': { blur: 8, freq: '0.004 0.014', octaves: 2, seed: 11, scale: 90, smooth: 1.5 },
	'crystal': { blur: 5, freq: '0.04 0.04', octaves: 3, seed: 5, scale: 16 },
	'wave': { blur: 9, freq: '0.012 0.004', octaves: 1, seed: 21, scale: 70, smooth: 1 },
	'prism': { blur: 6, freq: '0.025 0.008', octaves: 2, seed: 13, scale: 60, saturate: 1.6 },
	'soft-blur': { blur: 16 },
	'jelly': { blur: 12, freq: '0.002 0.005', octaves: 2, seed: 4, scale: 210, smooth: 4 },
};

const SHADOWS = {
	'none': '',
	'soft-glow': 'inset 0 0 15px 0 rgba(255, 255, 255, 0.55)',
	'bright-glow': 'inset 0 0 18px 0 rgba(255, 255, 255, 0.75)',
	'wide-bloom': 'inset 0 0 30px 1px rgba(255, 255, 255, 0.65)',
	'top-light': 'inset 0 18px 14px -8px rgba(255, 255, 255, 0.5)',
	'rim-light': 'inset 0 0 10px 0 rgba(255, 255, 255, 0.8), inset 0 0 2px 0 rgba(255, 255, 255, 0.9)',
	'inner-shadow': 'inset 0 2px 8px 0 rgba(0, 0, 0, 0.15), inset 0 0 12px 0 rgba(255, 255, 255, 0.3)',
};

const COLOR_RE = /^(#[0-9a-f]{3,8}|(rgb|hsl)a?\(\s*[0-9.%,\s/-]+\)|[a-z]+)$/i;

let svgFilterSupported;

function supportsSvgBackdrop() {
	if (svgFilterSupported === undefined) {
		svgFilterSupported = !!(window.CSS && CSS.supports
			&& CSS.supports('backdrop-filter', 'blur(2px) url(#aae-lg-probe)'));
	}
	return svgFilterSupported;
}

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === null || v === '') ? fallback : v;
}

function num(value, fallback, min, max) {
	const n = Number(value);
	return Number.isFinite(n) ? Math.max(min, Math.min(max, n)) : fallback;
}

function holder() {
	let el = document.getElementById(HOLDER_ID);
	if (!el) {
		el = document.createElementNS(SVG_NS, 'svg');
		el.setAttribute('id', HOLDER_ID);
		el.setAttribute('width', '0');
		el.setAttribute('height', '0');
		el.setAttribute('aria-hidden', 'true');
		el.style.cssText = 'position:absolute;overflow:hidden;pointer-events:none';
		el.appendChild(document.createElementNS(SVG_NS, 'defs'));
		document.body.appendChild(el);
	}
	return el;
}

/** Create (once) and return the id of the filter for a preset + distortion %. */
function ensureFilter(name, preset, distortion) {
	const scale = Math.round(preset.scale * (distortion / 100));
	if (scale <= 0) return '';

	const id = `aae-lg-${name}-${scale}`;
	if (document.getElementById(id)) return id;

	const filter = document.createElementNS(SVG_NS, 'filter');
	filter.setAttribute('id', id);
	// Wider than the box so the displaced edge pixels are not clipped.
	filter.setAttribute('x', '-10%');
	filter.setAttribute('y', '-10%');
	filter.setAttribute('width', '120%');
	filter.setAttribute('height', '120%');

	const noise = document.createElementNS(SVG_NS, 'feTurbulence');
	noise.setAttribute('type', 'fractalNoise');
	noise.setAttribute('baseFrequency', preset.freq);
	noise.setAttribute('numOctaves', String(preset.octaves || 1));
	noise.setAttribute('seed', String(preset.seed || 1));
	noise.setAttribute('result', 'noise');
	filter.appendChild(noise);

	let map = 'noise';
	if (preset.smooth) {
		const soft = document.createElementNS(SVG_NS, 'feGaussianBlur');
		soft.setAttribute('in', 'noise');
		soft.setAttribute('stdDeviation', String(preset.smooth));
		soft.setAttribute('result', 'soft');
		filter.appendChild(soft);
		map = 'soft';
	}

	const disp = document.createElementNS(SVG_NS, 'feDisplacementMap');
	disp.setAttribute('in', 'SourceGraphic');
	disp.setAttribute('in2', map);
	disp.setAttribute('scale', String(scale));
	disp.setAttribute('xChannelSelector', 'R');
	disp.setAttribute('yChannelSelector', 'G');
	filter.appendChild(disp);

	if (preset.saturate) {
		const sat = document.createElementNS(SVG_NS, 'feColorMatrix');
		sat.setAttribute('type', 'saturate');
		sat.setAttribute('values', String(preset.saturate));
		filter.appendChild(sat);
	}

	holder().firstChild.appendChild(filter);
	return id;
}

function clearStyles(el) {
	el.style.removeProperty('backdrop-filter');
	el.style.removeProperty('-webkit-backdrop-filter');
	el.style.removeProperty('box-shadow');
	el.style.removeProperty('border');
	el.style.removeProperty('border-radius');
}

export function readLiquidGlass(el) {
	const cfg = configFor(el, MAP);
	if (!cfg) return null;
	if (!pickConfigResponsive(cfg, 'enabled')) return null;
	return cfg;
}

export function playLiquidGlass(el, cfg) {
	if (!cfg) return;

	clearStyles(el);

	const name = PRESETS[r(cfg, 'preset', 'frosted')] ? r(cfg, 'preset', 'frosted') : 'frosted';
	const preset = PRESETS[name];
	const customBlur = num(r(cfg, 'blur', 0), 0, 0, 50);
	const blur = customBlur > 0 ? customBlur : preset.blur;
	const distortion = num(r(cfg, 'distortion', 100), 100, 0, 300);

	let filter = `blur(${blur}px)`;
	if (preset.freq && supportsSvgBackdrop()) {
		const id = ensureFilter(name, preset, distortion);
		if (id) filter += ` url(#${id})`;
	}
	el.style.setProperty('backdrop-filter', filter);
	el.style.setProperty('-webkit-backdrop-filter', filter);

	const shadow = SHADOWS[r(cfg, 'shadow', 'soft-glow')];
	if (shadow) el.style.setProperty('box-shadow', shadow);

	const borderWidth = num(r(cfg, 'borderWidth', 1), 1, 0, 20);
	if (borderWidth > 0) {
		const color = String(r(cfg, 'borderColor', 'rgba(255,255,255,0.35)')).trim();
		el.style.setProperty('border', `${borderWidth}px solid ${COLOR_RE.test(color) ? color : 'rgba(255,255,255,0.35)'}`);
	}

	const radius = num(r(cfg, 'radius', 0), 0, 0, 500);
	if (radius > 0) el.style.setProperty('border-radius', `${radius}px`);

	el[APPLIED_KEY] = true;
}

export function bindLiquidGlass(el, cfg) {
	playLiquidGlass(el, cfg);
}

export function resetLiquidGlass(el) {
	if (!el[APPLIED_KEY]) return;
	clearStyles(el);
	delete el[APPLIED_KEY];
}

function unbindLiquidGlass(el) {
	resetLiquidGlass(el);
}

window.AAEADDON.register({
	name: 'liquid-glass',
	mapName: MAP,
	boundFlag: 'aae-liquid-glass-bound',
	playedKey: APPLIED_KEY,
	read: readLiquidGlass,
	play: playLiquidGlass,
	bind: bindLiquidGlass,
	unbind: unbindLiquidGlass,
	reset: resetLiquidGlass,
});
