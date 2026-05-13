/* eslint-env browser */

/**
 * Image Hover (Reveal-on-Hover) kind. Cursor-following floating image
 * revealed on mouseenter, faded on mouseleave, positioned to follow the
 * mouse on mousemove. Same behavior as v3 wcf-addons-pro.js's
 * "image-hover" module.
 *
 * Reads from `window.AAE_INTERACTIONS_IMGHOVER[<interactionId>]`. Storage is a
 * flat cfg object emitted by Render.php / features.js. Helpers come from
 * window.AAEADDON — same convention as other effect bundles.
 *
 * DOM: we inject a `<div class="aae-ih-image">` inside the target element
 * on first bind. Its position/size are set inline (px) from cfg so we
 * don't depend on a styleshift from PHP.
 */
const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const IH_MAP    = 'AAE_INTERACTIONS_IMGHOVER';
export const IH_PLAYED = '__aaeIhPlayed';
const IH_DISPOSE_KEY   = '__aaeIhDispose';
const IH_OVERLAY_KEY   = '__aaeIhOverlay';

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

export function readImageHover(el) {
	const cfg = configFor(el, IH_MAP);
	if (!cfg) return null;
	if (!cfg.enabled) return null;
	if (!cfg.imageUrl) return null;
	return {
		imageUrl: String(cfg.imageUrl),
		width:    Number(r(cfg, 'width',  300)),
		height:   Number(r(cfg, 'height', 300)),
		top:      Number(r(cfg, 'top',    0)),
		left:     Number(r(cfg, 'left',   0)),
		zindex:   Number(r(cfg, 'zindex', 1)),
	};
}

/**
 * Resolve the host that will hold the overlay.
 *
 * Always prefer the parent element when one exists. Reasons:
 *   - <img> / <svg> can't host children at all.
 *   - text-animation co-binds on heading/paragraph widgets and wipes
 *     `el.innerHTML` when splitting text into per-character spans —
 *     which would also wipe our overlay if we hosted it inside `el`.
 *
 * Mouse coordinates still come from the parent's bounding box so the
 * overlay's absolute-positioning frame stays aligned with the event
 * origin. The hover effect's "hot area" is therefore the entire host
 * (parent), which matches user intent — hovering anywhere in the
 * widget's visible region triggers the reveal.
 */
function hostFor(el) {
	return el.parentElement || el;
}

/** Create (or re-use) the floating image overlay inside the host. */
function ensureOverlay(el, config) {
	const host = hostFor(el);

	let overlay = el[IH_OVERLAY_KEY];
	if (!overlay || !overlay.isConnected) {
		overlay = document.createElement('div');
		overlay.className = 'aae-ih-image';
		host.appendChild(overlay);
		el[IH_OVERLAY_KEY] = overlay;
	}

	// Position absolutely inside the host. If host is static, promote it
	// to relative so the overlay anchors to it.
	if (getComputedStyle(host).position === 'static') {
		host.style.position = 'relative';
	}

	Object.assign(overlay.style, {
		position:           'absolute',
		pointerEvents:      'none',
		opacity:            '0',
		visibility:         'hidden',
		top:                `${config.top}px`,
		left:               `${config.left}px`,
		width:              `${config.width}px`,
		height:             `${config.height}px`,
		zIndex:             String(config.zindex),
		backgroundImage:    `url("${config.imageUrl}")`,
		backgroundSize:     'cover',
		backgroundPosition: 'center',
		backgroundRepeat:   'no-repeat',
		// Anchor on center so the cursor sits in the middle of the image.
		transform:          'translate(-50%, -50%)',
		willChange:         'transform, opacity',
	});

	return overlay;
}

export function bindImageHover(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	const overlay = ensureOverlay(el, config);
	// Hover listeners go on the host (the overlay's offset parent), not on
	// `el` — when el is an <img>, mouse events relative to it work but the
	// overlay is positioned inside the parent's box, so coordinates must
	// match. Using host for both makes the math simple.
	const host = hostFor(el);

	const onEnter = () => {
		gsap.to(overlay, { duration: 0, autoAlpha: 1 });
	};
	const onLeave = () => {
		gsap.to(overlay, { duration: 0, autoAlpha: 0 });
	};
	const onMove = (e) => {
		const box = host.getBoundingClientRect();
		const dx  = e.clientX - box.left;
		const dy  = e.clientY - box.top;
		// Offset by configured top/left so user-set position acts as anchor.
		gsap.set(overlay, { x: dx - config.left, y: dy - config.top });
	};

	host.addEventListener('mouseenter', onEnter);
	host.addEventListener('mouseleave', onLeave);
	host.addEventListener('mousemove',  onMove);

	el[IH_PLAYED]      = overlay;
	el[IH_DISPOSE_KEY] = () => {
		host.removeEventListener('mouseenter', onEnter);
		host.removeEventListener('mouseleave', onLeave);
		host.removeEventListener('mousemove',  onMove);
	};
}

/**
 * playImageHover — used by the editor "Play Now" button. There's no
 * tween to replay (the effect is event-driven), so we just rebind. That
 * forces the overlay's size / image / position to refresh after a
 * settings change.
 */
export function playImageHover(el, config) {
	cleanupImageHover(el);
	bindImageHover(el, config);
}

function cleanupImageHover(el) {
	const dispose = el[IH_DISPOSE_KEY];
	if (typeof dispose === 'function') {
		try { dispose(); } catch (_) { /* ignore */ }
	}
	el[IH_DISPOSE_KEY] = null;

	const overlay = el[IH_OVERLAY_KEY];
	if (overlay && overlay.parentNode) {
		try { overlay.parentNode.removeChild(overlay); } catch (_) { /* ignore */ }
	}
	el[IH_OVERLAY_KEY] = null;
	delete el[IH_PLAYED];
}

export function resetImageHover(el) {
	cleanupImageHover(el);
}

window.AAEADDON.register({
	name:       'image-hover',
	mapName:    IH_MAP,
	boundFlag:  'aae-ih-bound',
	playedKey:  IH_PLAYED,
	read:       readImageHover,
	play:       playImageHover,
	bind:       bindImageHover,
	unbind:     cleanupImageHover,
	reset:      resetImageHover,
});
