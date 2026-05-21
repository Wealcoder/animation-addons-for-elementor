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
const { getGsap, configFor, pickConfigResponsive, currentBreakpoint } = window.AAEADDON;
// Image reveal
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
	if (!cfg.imageUrl) return null;
	const enabled = pickConfigResponsive(cfg, 'enabled');
	
	if (!enabled || enabled === 'false' || enabled === 'no') return null;
	return {
		enabled:  true,
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
 * The parent gives the overlay a stable mount point. Hover *listeners*
 * stay on `el` so the hot area exactly matches the widget (not the
 * larger parent), and mouse coordinates are translated into the host's
 * frame so the absolute-positioned overlay tracks correctly.
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

	// Sweep orphan overlays: in the editor, React re-renders replace the
	// widget's DOM node — the old el's overlay is left behind in the host
	// (we can't reach it via the new el's IH_OVERLAY_KEY). Remove any
	// `.aae-ih-image` siblings that aren't the one we just bound.
	const others = host.querySelectorAll(':scope > .aae-ih-image');
	others.forEach((node) => {
		if (node !== overlay) node.parentNode?.removeChild(node);
	});

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
		// Anchor at host's origin — the per-mousemove transform handles
		// both the cursor follow and the user-configured offset.
		top:                '0',
		left:               '0',
		width:              `${config.width}px`,
		height:             `${config.height}px`,
		zIndex:             String(config.zindex),
		backgroundImage:    `url("${config.imageUrl}")`,
		backgroundSize:     'cover',
		backgroundPosition: 'center',
		backgroundRepeat:   'no-repeat',
		// Center the image on the cursor by default. translate(-50%,-50%)
		// pulls the image back by half its size so the cursor sits at its
		// center; the per-mousemove gsap.set adds the cursor coords + the
		// user's Top/Left offset on top.
		transform:          'translate(-50%, -50%)',
		willChange:         'transform, opacity',
	});

	return overlay;
}

export function bindImageHover(el, config) {
	const gsap = getGsap();
	
	if (!gsap) return;

	const overlay = ensureOverlay(el, config);
	const host    = hostFor(el);

	let activeConfig = null;

	const onEnter = () => {
		activeConfig = readImageHover(el);
		if (!activeConfig) {
			gsap.to(overlay, { duration: 0, autoAlpha: 0 });
			return;
		}
		Object.assign(overlay.style, {
			width:              `${activeConfig.width}px`,
			height:             `${activeConfig.height}px`,
			zIndex:             String(activeConfig.zindex),
			backgroundImage:    `url("${activeConfig.imageUrl}")`,
		});
		gsap.to(overlay, { duration: 0, autoAlpha: 1 });
	};
	const onLeave = () => {
		gsap.to(overlay, { duration: 0, autoAlpha: 0 });
		activeConfig = null;
	};
	// Mouse coords arrive in viewport space. The overlay's absolute frame
	// is the host (parent), so we subtract host.rect.left/top — that puts
	// the cursor in host-local coordinates. Then we add the user-configured
	// Top/Left as a positive offset (Left=50 → image shifts 50px right of
	// cursor; Top=20 → 20px down). Listeners live on `el` so the hot area
	// equals the widget, not the larger host.
	const onMove = (e) => {
		if (!activeConfig || activeConfig.enabled === false) return;
		const box = host.getBoundingClientRect();
		const dx  = e.clientX - box.left;
		const dy  = e.clientY - box.top;
		gsap.set(overlay, { x: dx + activeConfig.left, y: dy + activeConfig.top });
	};

	el.addEventListener('mouseenter', onEnter);
	el.addEventListener('mouseleave', onLeave);
	el.addEventListener('mousemove',  onMove);

	el[IH_PLAYED]      = overlay;
	el[IH_DISPOSE_KEY] = () => {
		el.removeEventListener('mouseenter', onEnter);
		el.removeEventListener('mouseleave', onLeave);
		el.removeEventListener('mousemove',  onMove);
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
// Image reveal hover
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
