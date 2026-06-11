/* eslint-env browser */

/**
 * Image Hover (Reveal-on-Hover) kind. Cursor-following floating image
 * revealed on mouseenter, faded on mouseleave, positioned to follow the
 * mouse on mousemove.
 *
 * Supports 5 animation presets + default (none):
 *   - none          : instant follow (original behavior)
 *   - magnetic-lag  : lerp-based smooth drag
 *   - spring-bounce : elastic.out overshoot
 *   - tilt-3d       : lerp follow + 3D rotation
 *   - trail-ghost   : 5 ghost copies with staggered lerp chain
 *   - elastic-snap  : elastic snap on fast cursor movement
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
export const IH_MAP = 'AAE_INTERACTIONS_IMGHOVER';
export const IH_PLAYED = '__aaeIhPlayed';
const IH_DISPOSE_KEY = '__aaeIhDispose';
const IH_OVERLAY_KEY = '__aaeIhOverlay';
const IH_RAF_KEY = '__aaeIhRaf';
const IH_GHOSTS_KEY = '__aaeIhGhosts';

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

export function readImageHover(el) {
	const cfg = configFor(el, IH_MAP);

	if (!cfg) return null;
	const imageUrl = pickConfigResponsive(cfg, 'imageUrl');

	if (!imageUrl) return null;
	const enabled = pickConfigResponsive(cfg, 'enabled');

	if (!enabled || enabled === 'false' || enabled === 'no') return null;
	return {
		enabled: enabled,
		imageUrl: String(imageUrl),
		width: Number(r(cfg, 'width', 300)),
		height: Number(r(cfg, 'height', 300)),
		top: Number(r(cfg, 'top', -15)),
		left: Number(r(cfg, 'left', 30)),
		zindex: Number(r(cfg, 'zindex', 1)),
		preset: String(r(cfg, 'preset', 'none')),
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
		position: 'absolute',
		pointerEvents: 'none',
		opacity: '0',
		visibility: 'hidden',
		top: '0',
		left: '0',
		width: `${config.width}px`,
		height: `${config.height}px`,
		zIndex: String(config.zindex),
		backgroundImage: `url("${config.imageUrl}")`,
		backgroundSize: 'cover',
		backgroundPosition: 'center',
		backgroundRepeat: 'no-repeat',
		willChange: 'transform, opacity',
	});

	// Use GSAP's xPercent/yPercent for centering so it's preserved when
	// GSAP later sets x/y/scale/rotation on the overlay. CSS translate()
	// gets overwritten by GSAP's transform — xPercent/yPercent stay intact.
	getGsap().set(overlay, { xPercent: -50, yPercent: -50 });

	return overlay;
}

/** Helper: lerp function */
function lerp(a, b, t) { return a + (b - a) * t; }

/** Get mouse position relative to host center */
function getMouseCenter(e, host) {
	const box = host.getBoundingClientRect();
	return {
		x: e.clientX - box.left - box.width / 2,
		y: e.clientY - box.top - box.height / 2,
	};
}

/** Get mouse position in host-local coordinates (for overlay positioning) */
function getMouseLocal(e, host) {
	const box = host.getBoundingClientRect();
	return {
		x: e.clientX - box.left,
		y: e.clientY - box.top,
	};
}

/* =====================================================================
 * Preset implementations
 * =================================================================== */

/**
 * Preset: none (default) — instant follow
 */
function bindPresetNone(el, overlay, host, config) {
	const gsap = getGsap();
	let activeConfig = null;

	const onEnter = () => {
		activeConfig = readImageHover(el);
		if (!activeConfig) { gsap.to(overlay, { duration: 0, autoAlpha: 0 }); return; }
		Object.assign(overlay.style, {
			width: `${activeConfig.width}px`,
			height: `${activeConfig.height}px`,
			zIndex: String(activeConfig.zindex),
			backgroundImage: `url("${activeConfig.imageUrl}")`,
		});
		gsap.to(overlay, { duration: 0, autoAlpha: 1 });
	};
	const onLeave = () => {
		gsap.to(overlay, { duration: 0, autoAlpha: 0 });
		activeConfig = null;
	};
	const onMove = (e) => {
		if (!activeConfig || activeConfig.enabled === false) return;
		const pos = getMouseLocal(e, host);
		gsap.set(overlay, { x: pos.x + activeConfig.left, y: pos.y + activeConfig.top });
	};

	el.addEventListener('mouseenter', onEnter);
	el.addEventListener('mouseleave', onLeave);
	el.addEventListener('mousemove', onMove);

	return () => {
		el.removeEventListener('mouseenter', onEnter);
		el.removeEventListener('mouseleave', onLeave);
		el.removeEventListener('mousemove', onMove);
	};
}

/**
 * Preset 1: Magnetic lag — smooth lerp following with drag
 */
function bindPresetMagneticLag(el, overlay, host, config) {
	const gsap = getGsap();
	let activeConfig = null;
	let mouseX = 0, mouseY = 0;
	let currentX = 0, currentY = 0;
	let rafId = null;

	const tick = () => {
		currentX = lerp(currentX, mouseX, 0.08);
		currentY = lerp(currentY, mouseY, 0.08);
		gsap.set(overlay, { x: currentX, y: currentY });
		rafId = requestAnimationFrame(tick);
	};

	const onEnter = (e) => {
		activeConfig = readImageHover(el);
		if (!activeConfig) { gsap.to(overlay, { duration: 0, autoAlpha: 0 }); return; }
		Object.assign(overlay.style, {
			width: `${activeConfig.width}px`,
			height: `${activeConfig.height}px`,
			zIndex: String(activeConfig.zindex),
			backgroundImage: `url("${activeConfig.imageUrl}")`,
		});
		// Initialize position from enter event so overlay starts at cursor
		const startPos = getMouseLocal(e, host);
		mouseX = currentX = startPos.x + activeConfig.left;
		mouseY = currentY = startPos.y + activeConfig.top;
		gsap.set(overlay, { x: currentX, y: currentY });
		gsap.to(overlay, { opacity: 1, scale: 1, duration: 0.45, ease: 'power3.out' });
		overlay.style.visibility = 'visible';
		rafId = requestAnimationFrame(tick);
	};
	const onLeave = () => {
		gsap.to(overlay, { opacity: 0, scale: 0.85, duration: 0.3, ease: 'power3.in' });
		activeConfig = null;
		if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
	};
	const onMove = (e) => {
		if (!activeConfig || activeConfig.enabled === false) return;
		const pos = getMouseLocal(e, host);
		mouseX = pos.x + activeConfig.left;
		mouseY = pos.y + activeConfig.top;
	};

	el.addEventListener('mouseenter', onEnter);
	el.addEventListener('mouseleave', onLeave);
	el.addEventListener('mousemove', onMove);

	return () => {
		el.removeEventListener('mouseenter', onEnter);
		el.removeEventListener('mouseleave', onLeave);
		el.removeEventListener('mousemove', onMove);
		if (rafId) cancelAnimationFrame(rafId);
	};
}

/**
 * Preset 2: Spring bounce — elastic.out overshoot on every move
 */
function bindPresetSpringBounce(el, overlay, host, config) {
	const gsap = getGsap();
	let activeConfig = null;

	const onEnter = (e) => {
		activeConfig = readImageHover(el);
		if (!activeConfig) { gsap.to(overlay, { duration: 0, autoAlpha: 0 }); return; }
		Object.assign(overlay.style, {
			width: `${activeConfig.width}px`,
			height: `${activeConfig.height}px`,
			zIndex: String(activeConfig.zindex),
			backgroundImage: `url("${activeConfig.imageUrl}")`,
		});
		// Initialize position from enter event so overlay starts at cursor
		const startPos = getMouseLocal(e, host);
		const sx = startPos.x + activeConfig.left;
		const sy = startPos.y + activeConfig.top;
		gsap.set(overlay, { x: sx, y: sy });
		gsap.to(overlay, { opacity: 1, scale: 1, duration: 0.5, ease: 'back.out(2)' });
		overlay.style.visibility = 'visible';
	};
	const onLeave = () => {
		gsap.to(overlay, { opacity: 0, scale: 0.7, duration: 0.35, ease: 'power2.in' });
		activeConfig = null;
	};
	const onMove = (e) => {
		if (!activeConfig || activeConfig.enabled === false) return;
		const pos = getMouseLocal(e, host);
		gsap.to(overlay, {
			x: pos.x + activeConfig.left,
			y: pos.y + activeConfig.top,
			duration: 0.9,
			ease: 'elastic.out(1, 0.4)',
			overwrite: 'auto',
		});
	};

	el.addEventListener('mouseenter', onEnter);
	el.addEventListener('mouseleave', onLeave);
	el.addEventListener('mousemove', onMove);

	return () => {
		el.removeEventListener('mouseenter', onEnter);
		el.removeEventListener('mouseleave', onLeave);
		el.removeEventListener('mousemove', onMove);
	};
}

/**
 * Preset 3: Tilt 3D — lerp follow + 3D rotation based on offset
 */
function bindPresetTilt3d(el, overlay, host, config) {
	const gsap = getGsap();
	let activeConfig = null;
	let ix = 0, iy = 0, mx = 0, my = 0;
	let rafId = null;

	let centerX = 0, centerY = 0; // center-relative for rotation

	const tick = () => {
		ix = lerp(ix, mx, 0.1);
		iy = lerp(iy, my, 0.1);
		centerX = lerp(centerX, mx - (host.offsetWidth / 2), 0.1);
		centerY = lerp(centerY, my - (host.offsetHeight / 2), 0.1);

		const cx = host.offsetWidth / 2;
		const cy = host.offsetHeight / 2;

		const rotX = -(centerY / cy) * 18;
		const rotY = (centerX / cx) * 18;

		gsap.set(overlay, {
			x: ix, y: iy,
			rotationX: rotX,
			rotationY: rotY,
			transformPerspective: 600,
		});
		rafId = requestAnimationFrame(tick);
	};

	const onEnter = (e) => {
		activeConfig = readImageHover(el);
		if (!activeConfig) { gsap.to(overlay, { duration: 0, autoAlpha: 0 }); return; }
		Object.assign(overlay.style, {
			width: `${activeConfig.width}px`,
			height: `${activeConfig.height}px`,
			zIndex: String(activeConfig.zindex),
			backgroundImage: `url("${activeConfig.imageUrl}")`,
		});
		// Initialize position from enter event so overlay starts at cursor
		const startPos = getMouseLocal(e, host);
		mx = ix = startPos.x + activeConfig.left;
		my = iy = startPos.y + activeConfig.top;
		centerX = mx - (host.offsetWidth / 2);
		centerY = my - (host.offsetHeight / 2);
		gsap.set(overlay, { x: ix, y: iy, rotationX: 0, rotationY: 0 });
		gsap.to(overlay, { opacity: 1, scale: 1, duration: 0.4, ease: 'power3.out' });
		overlay.style.visibility = 'visible';
		rafId = requestAnimationFrame(tick);
	};
	const onLeave = () => {
		gsap.to(overlay, {
			opacity: 0, scale: 0.85,
			rotationX: 0, rotationY: 0,
			duration: 0.4, ease: 'power2.in',
		});
		activeConfig = null;
		if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
	};
	const onMove = (e) => {
		if (!activeConfig || activeConfig.enabled === false) return;
		const pos = getMouseLocal(e, host);
		mx = pos.x + activeConfig.left;
		my = pos.y + activeConfig.top;
	};

	el.addEventListener('mouseenter', onEnter);
	el.addEventListener('mouseleave', onLeave);
	el.addEventListener('mousemove', onMove);

	return () => {
		el.removeEventListener('mouseenter', onEnter);
		el.removeEventListener('mouseleave', onLeave);
		el.removeEventListener('mousemove', onMove);
		if (rafId) cancelAnimationFrame(rafId);
	};
}

/**
 * Preset 4: Trail ghost — 5 ghost copies with staggered lerp chain
 */
function bindPresetTrailGhost(el, overlay, host, config) {
	const gsap = getGsap();
	const COUNT = 5;
	let activeConfig = null;
	let mx = 0, my = 0;
	let rafId = null;

	// Ghost elements stored on el so we can clean them up
	let ghosts = [];

	const ensureGhosts = (cfg) => {
		// Remove old ghosts
		ghosts.forEach((g) => { if (g.parentNode) g.parentNode.removeChild(g); });
		ghosts = [];

		for (let i = 0; i < COUNT; i++) {
			const div = document.createElement('div');
			div.className = 'aae-ih-image aae-ih-ghost';
			Object.assign(div.style, {
				position: 'absolute',
				top: '0',
				left: '0',
				width: `${cfg.width - i * 22}px`,
				height: `${cfg.height - i * 15}px`,
				borderRadius: '8px',
				overflow: 'hidden',
				pointerEvents: 'none',
				opacity: '0',
				visibility: 'hidden',
				zIndex: String(cfg.zindex + COUNT - i),
				backgroundImage: `url("${cfg.imageUrl}")`,
				backgroundSize: 'cover',
				backgroundPosition: 'center',
				backgroundRepeat: 'no-repeat',
				willChange: 'transform, opacity',
			});
			// Use GSAP xPercent/yPercent for centering (preserved across transforms)
			gsap.set(div, { xPercent: -50, yPercent: -50 });
			host.appendChild(div);
			ghosts.push(div);
		}
	};

	// Position state for each ghost (staggered lerp chain)
	const pos = Array.from({ length: COUNT }, () => ({ x: 0, y: 0 }));

	const tick = () => {
		pos[0].x = lerp(pos[0].x, mx, 0.22);
		pos[0].y = lerp(pos[0].y, my, 0.22);
		for (let i = 1; i < COUNT; i++) {
			pos[i].x = lerp(pos[i].x, pos[i - 1].x, 0.2);
			pos[i].y = lerp(pos[i].y, pos[i - 1].y, 0.2);
		}
		ghosts.forEach((g, i) => {
			gsap.set(g, { x: pos[i].x, y: pos[i].y });
		});
		rafId = requestAnimationFrame(tick);
	};

	const onEnter = (e) => {
		activeConfig = readImageHover(el);
		if (!activeConfig) {
			ghosts.forEach((g) => gsap.to(g, { duration: 0, autoAlpha: 0 }));
			return;
		}
		ensureGhosts(activeConfig);
		// Initialize all positions from enter event so ghosts start at cursor
		const startPos = getMouseLocal(e, host);
		const sx = startPos.x + activeConfig.left;
		const sy = startPos.y + activeConfig.top;
		mx = sx; my = sy;
		for (let i = 0; i < COUNT; i++) { pos[i].x = sx; pos[i].y = sy; }
		// Set initial ghost positions immediately
		ghosts.forEach((g) => { gsap.set(g, { x: sx, y: sy }); });

		ghosts.forEach((g, i) => {
			gsap.to(g, { opacity: 1 - i * 0.15, duration: 0.4, delay: i * 0.04 });
			g.style.visibility = 'visible';
		});
		rafId = requestAnimationFrame(tick);
	};
	const onLeave = () => {
		ghosts.forEach((g) => gsap.to(g, { opacity: 0, duration: 0.3 }));
		activeConfig = null;
		if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
	};
	const onMove = (e) => {
		if (!activeConfig || activeConfig.enabled === false) return;
		const posLocal = getMouseLocal(e, host);
		mx = posLocal.x + activeConfig.left;
		my = posLocal.y + activeConfig.top;
	};

	el.addEventListener('mouseenter', onEnter);
	el.addEventListener('mouseleave', onLeave);
	el.addEventListener('mousemove', onMove);

	// Hide the main overlay for trail-ghost preset (ghosts replace it)
	overlay.style.display = 'none';

	return () => {
		el.removeEventListener('mouseenter', onEnter);
		el.removeEventListener('mouseleave', onLeave);
		el.removeEventListener('mousemove', onMove);
		if (rafId) cancelAnimationFrame(rafId);
		ghosts.forEach((g) => { if (g.parentNode) g.parentNode.removeChild(g); });
		ghosts = [];
		overlay.style.display = '';
	};
}

/**
 * Preset 5: Elastic snap — only snaps when cursor speed exceeds threshold
 */
function bindPresetElasticSnap(el, overlay, host, config) {
	const gsap = getGsap();
	let activeConfig = null;
	let mx = 0, my = 0, lx = 0, ly = 0;
	let rafId = null;

	const tick = () => {
		const speed = Math.hypot(mx - lx, my - ly);
		lx = mx; ly = my;

		if (speed > 4) {
			gsap.to(overlay, {
				x: mx, y: my,
				duration: 0.9,
				ease: 'elastic.out(1.2, 0.5)',
				overwrite: 'auto',
			});
		}
		rafId = requestAnimationFrame(tick);
	};

	const onEnter = (e) => {
		activeConfig = readImageHover(el);
		if (!activeConfig) { gsap.to(overlay, { duration: 0, autoAlpha: 0 }); return; }
		Object.assign(overlay.style, {
			width: `${activeConfig.width}px`,
			height: `${activeConfig.height}px`,
			zIndex: String(activeConfig.zindex),
			backgroundImage: `url("${activeConfig.imageUrl}")`,
		});
		// Initialize position from enter event so overlay starts at cursor
		const startPos = getMouseLocal(e, host);
		mx = lx = startPos.x + activeConfig.left;
		my = ly = startPos.y + activeConfig.top;
		gsap.set(overlay, { x: mx, y: my });
		gsap.to(overlay, { opacity: 1, scale: 1, duration: 0.5, ease: 'back.out(2.5)' });
		overlay.style.visibility = 'visible';
		rafId = requestAnimationFrame(tick);
	};
	const onLeave = () => {
		gsap.to(overlay, { opacity: 0, scale: 0.8, duration: 0.3, ease: 'power3.in' });
		activeConfig = null;
		if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
	};
	const onMove = (e) => {
		if (!activeConfig || activeConfig.enabled === false) return;
		const pos = getMouseLocal(e, host);
		mx = pos.x + activeConfig.left;
		my = pos.y + activeConfig.top;
	};

	el.addEventListener('mouseenter', onEnter);
	el.addEventListener('mouseleave', onLeave);
	el.addEventListener('mousemove', onMove);

	return () => {
		el.removeEventListener('mouseenter', onEnter);
		el.removeEventListener('mouseleave', onLeave);
		el.removeEventListener('mousemove', onMove);
		if (rafId) cancelAnimationFrame(rafId);
	};
}

/* =====================================================================
 * Main bind — dispatches to the correct preset
 * =================================================================== */

const PRESET_BINDERS = {
	'none': bindPresetNone,
	'magnetic-lag': bindPresetMagneticLag,
	'spring-bounce': bindPresetSpringBounce,
	'tilt-3d': bindPresetTilt3d,
	'trail-ghost': bindPresetTrailGhost,
	'elastic-snap': bindPresetElasticSnap,
};

export function bindImageHover(el, config) {
	const gsap = getGsap();

	if (!gsap) return;

	const overlay = ensureOverlay(el, config);
	const host = hostFor(el);

	// Select the binder based on preset; fall back to 'none'.
	const preset = config.preset || 'none';
	const binder = PRESET_BINDERS[preset] || bindPresetNone;

	const dispose = binder(el, overlay, host, config);

	el[IH_PLAYED] = overlay;
	el[IH_DISPOSE_KEY] = () => {
		if (typeof dispose === 'function') {
			try { dispose(); } catch (_) { /* ignore */ }
		}
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

	// Also clean up any ghost elements
	const host = el.parentElement;
	if (host) {
		const ghosts = host.querySelectorAll(':scope > .aae-ih-ghost');
		ghosts.forEach((g) => { if (g.parentNode) g.parentNode.removeChild(g); });
	}

	delete el[IH_PLAYED];
}

export function resetImageHover(el) {
	cleanupImageHover(el);
}
// Image reveal hover
window.AAEADDON.register({
	name: 'image-hover',
	mapName: IH_MAP,
	boundFlag: 'aae-ih-bound',
	playedKey: IH_PLAYED,
	read: readImageHover,
	play: playImageHover,
	bind: bindImageHover,
	unbind: cleanupImageHover,
	reset: resetImageHover,
});