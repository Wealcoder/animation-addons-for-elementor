/* eslint-env browser */

/**
 * Image Overlay — a static color/gradient tint on e-image / e-svg.
 *
 * Always rendered as a REAL OVERLAY NODE, never as a style applied directly
 * to the target element and never as a `::before`/`::after` pseudo-element —
 * browsers generate no pseudo-element boxes on replaced elements (`<img>`,
 * `<video>`, `<input>`, …), so a stylesheet rule targeting `img::after`
 * silently renders nothing at all. That rules out pseudo-elements for the
 * bare-`<img>` case, and by extension it's simplest to never rely on them
 * here at all.
 *
 * Two different ways to attach that overlay node, depending on what `el`
 * actually is:
 *
 * - Anything that WRAPS real DOM content: e-image WITH a Link (Elementor
 *   puts `data-interaction-id` on the `<a>`, and the actual `<img>` is a
 *   child of it) and e-svg (always a `<div>`/`<a>` holding the raw `<svg>`
 *   markup as a real child). Here the overlay is inserted as a CHILD of
 *   `el`, `position: absolute; inset: 0`. Being positioned, it paints after
 *   `el`'s normal-flow content regardless of DOM order, so it always shows
 *   on top of the wrapped image/svg — and `pointer-events: none` keeps it
 *   from swallowing clicks on a Link.
 *
 * - A bare, unwrapped `<img>` (e-image with no Link). A replaced element
 *   can't host a DOM child (or a pseudo-element) at all — anything
 *   appended to it is simply never rendered — so there is nowhere on the
 *   `<img>` itself to attach an overlay. Instead the overlay is inserted as
 *   a SIBLING, next to the `<img>`, inside its existing parent (made
 *   `position: relative` if it wasn't already — this doesn't touch the
 *   `<img>`'s own box or its participation in a flex/grid layout, unlike
 *   wrapping the `<img>` itself would). A ResizeObserver on both the image
 *   and its parent keeps the overlay's left/top/width/height in sync with
 *   the image's rendered box (needed for responsive images, breakpoint
 *   changes, and images that resize once they finish loading).
 *
 * Blend Mode always applies (even for Color) as a secondary effect: once
 * the tint is painted, blend-mode still governs how the overlay composites
 * against whatever is behind it in the page.
 *
 * No GSAP dependency — this is a plain style application, not an
 * animation, so it is always-on: bind() just sets the styles once (and
 * again on resize/breakpoint change, via common.js's rebind cycle).
 */

const { configFor, pickConfigResponsive } = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_IMG_OVL';
const APPLIED_KEY = '__aaeImgOvlApplied';
const LAYER_KEY = '__aaeImgOvlLayer';
const POSITIONED_FLAG = 'data-aae-img-ovl-positioned';
const IMG_RESIZE_OBS_KEY = '__aaeImgOvlResizeObs';
const IMG_RESIZE_FN_KEY = '__aaeImgOvlResizeFn';
const PARENT_POSITIONED_FLAG = 'data-aae-img-ovl-parent-positioned';

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === null || v === '') ? fallback : v;
}

/** '#rgb' / '#rrggbb' / 'rgb(...)' / 'rgba(...)' → { r, g, b }, or null. */
function parseColor(color) {
	if (typeof color !== 'string') return null;
	const hex = color.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
	if (hex) {
		let h = hex[1];
		if (h.length === 3) h = h.split('').map((c) => c + c).join('');
		const num = parseInt(h, 16);
		return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
	}
	const fn = color.trim().match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
	if (fn) {
		return { r: Number(fn[1]), g: Number(fn[2]), b: Number(fn[3]) };
	}
	return null;
}

function withAlpha(color, alpha) {
	const parsed = parseColor(color);
	if (!parsed) return color;
	return `rgba(${parsed.r}, ${parsed.g}, ${parsed.b}, ${alpha})`;
}

/**
 * Get (or create) the positioned overlay child used for anything that isn't
 * a bare `<img>`. Idempotent — safe to call on every play().
 */
function ensureOverlayLayer(el) {
	let layer = el[LAYER_KEY];
	if (layer && layer.parentNode === el) return layer;

	if (getComputedStyle(el).position === 'static') {
		el.style.position = 'relative';
		el.setAttribute(POSITIONED_FLAG, '1');
	}

	layer = document.createElement('span');
	layer.setAttribute('data-aae-img-ovl-layer', '');
	layer.style.position = 'absolute';
	layer.style.inset = '0';
	layer.style.pointerEvents = 'none';
	el.appendChild(layer);
	el[LAYER_KEY] = layer;
	return layer;
}

function removeOverlayLayer(el) {
	const layer = el[LAYER_KEY];
	if (layer && layer.parentNode === el) {
		layer.parentNode.removeChild(layer);
	}
	delete el[LAYER_KEY];

	if (el.getAttribute(POSITIONED_FLAG) === '1') {
		el.style.position = '';
		el.removeAttribute(POSITIONED_FLAG);
	}
}

/** Copy the image's rendered box onto its sibling overlay. */
function syncImgOverlayRect(img, overlay) {
	overlay.style.left = img.offsetLeft + 'px';
	overlay.style.top = img.offsetTop + 'px';
	overlay.style.width = img.offsetWidth + 'px';
	overlay.style.height = img.offsetHeight + 'px';
}

/**
 * Get (or create) the overlay used for a bare `<img>` — a sibling inside
 * the image's own parent (never a child of the `<img>` itself; a replaced
 * element renders no DOM children), kept aligned to the image's box via
 * ResizeObserver since neither the image's size nor its position is fixed
 * (responsive srcset, lazy-load, breakpoint changes, unrelated reflows).
 */
function ensureImgOverlay(img) {
	const parent = img.parentElement;
	if (!parent) return null;

	let overlay = img[LAYER_KEY];
	if (overlay && overlay.parentNode === parent) {
		syncImgOverlayRect(img, overlay);
		return overlay;
	}

	if (getComputedStyle(parent).position === 'static') {
		parent.style.position = 'relative';
		parent.setAttribute(PARENT_POSITIONED_FLAG, '1');
	}

	overlay = document.createElement('span');
	overlay.setAttribute('data-aae-img-ovl-layer', '');
	overlay.style.position = 'absolute';
	overlay.style.pointerEvents = 'none';
	parent.insertBefore(overlay, img.nextSibling);
	img[LAYER_KEY] = overlay;
	syncImgOverlayRect(img, overlay);

	if (typeof ResizeObserver !== 'undefined') {
		const ro = new ResizeObserver(() => syncImgOverlayRect(img, overlay));
		ro.observe(img);
		ro.observe(parent);
		img[IMG_RESIZE_OBS_KEY] = ro;
	}
	const onWindowResize = () => syncImgOverlayRect(img, overlay);
	window.addEventListener('resize', onWindowResize);
	img[IMG_RESIZE_FN_KEY] = onWindowResize;

	return overlay;
}

function removeImgOverlay(img) {
	const overlay = img[LAYER_KEY];
	if (overlay && overlay.parentNode) {
		overlay.parentNode.removeChild(overlay);
	}
	delete img[LAYER_KEY];

	const ro = img[IMG_RESIZE_OBS_KEY];
	if (ro) {
		ro.disconnect();
		delete img[IMG_RESIZE_OBS_KEY];
	}

	const onWindowResize = img[IMG_RESIZE_FN_KEY];
	if (onWindowResize) {
		window.removeEventListener('resize', onWindowResize);
		delete img[IMG_RESIZE_FN_KEY];
	}

	const parent = img.parentElement;
	if (parent && parent.getAttribute(PARENT_POSITIONED_FLAG) === '1') {
		parent.style.position = '';
		parent.removeAttribute(PARENT_POSITIONED_FLAG);
	}
}

/** Paint the resolved type/color/gradient + blend mode onto an overlay node. */
function paintOverlay(node, type, gradientImage, solidColor, blendMode) {
	if (type === 'gradient') {
		node.style.backgroundColor = '';
		node.style.backgroundImage = gradientImage();
	} else {
		node.style.backgroundImage = '';
		node.style.backgroundColor = solidColor();
	}
	node.style.mixBlendMode = blendMode;
}

export function readImageOverlay(el) {
	const cfg = configFor(el, MAP);
	if (!cfg) return null;
	if (!pickConfigResponsive(cfg, 'enabled')) return null;
	return cfg;
}

export function playImageOverlay(el, cfg) {
	if (!cfg) return;

	const type = r(cfg, 'type', 'color');
	const opacityPct = Number(r(cfg, 'opacity', 50));
	const alpha = Math.max(0, Math.min(100, Number.isFinite(opacityPct) ? opacityPct : 50)) / 100;
	const blendMode = r(cfg, 'blendMode', 'multiply');

	const gradientImage = () => {
		const c1 = withAlpha(r(cfg, 'gradientColor1', '#000000'), alpha);
		const c2 = withAlpha(r(cfg, 'gradientColor2', '#ffffff'), alpha);
		const angle = Number(r(cfg, 'gradientAngle', 180));
		return `linear-gradient(${Number.isFinite(angle) ? angle : 180}deg, ${c1}, ${c2})`;
	};
	const solidColor = () => withAlpha(r(cfg, 'color', '#000000'), alpha);

	// Always clear anything a previous version of this effect (or a stale
	// cached bundle) may have painted directly onto el — el itself is never
	// styled by the current implementation, only an overlay node is.
	el.style.boxShadow = '';
	el.style.backgroundImage = '';
	el.style.backgroundColor = '';
	el.style.mixBlendMode = '';

	if (el.tagName === 'IMG') {
		// Bare, unwrapped <img> — see file header. Neither a child node nor
		// a ::before/::after pseudo-element renders on a replaced element,
		// so the overlay is a sibling inside the image's own parent.
		removeOverlayLayer(el);
		const overlay = ensureImgOverlay(el);
		if (overlay) {
			paintOverlay(overlay, type, gradientImage, solidColor, blendMode);
		}
	} else {
		// el WRAPS real content (a Linked image's <a>, or e-svg's element
		// holding raw SVG markup) — paint a positioned overlay CHILD
		// instead, see file header for why.
		removeImgOverlay(el);
		const layer = ensureOverlayLayer(el);
		paintOverlay(layer, type, gradientImage, solidColor, blendMode);
	}

	el[APPLIED_KEY] = true;
}

export function bindImageOverlay(el, cfg) {
	playImageOverlay(el, cfg);
}

export function resetImageOverlay(el) {
	if (!el[APPLIED_KEY]) return;
	el.style.boxShadow = '';
	el.style.backgroundImage = '';
	el.style.backgroundColor = '';
	el.style.mixBlendMode = '';
	removeOverlayLayer(el);
	removeImgOverlay(el);
	delete el[APPLIED_KEY];
}

function unbindImageOverlay(el) {
	resetImageOverlay(el);
}

window.AAEADDON.register({
	name: 'image-overlay',
	mapName: MAP,
	boundFlag: 'aae-image-overlay-bound',
	playedKey: APPLIED_KEY,
	read: readImageOverlay,
	play: playImageOverlay,
	bind: bindImageOverlay,
	unbind: unbindImageOverlay,
	reset: resetImageOverlay,
});
