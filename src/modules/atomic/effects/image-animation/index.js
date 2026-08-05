/* eslint-env browser */

/**
 * Image Animation — REPEATER runtime.
 *

 */

import { wireTrigger, modeFor, resolveTriggerEl } from '../animation/triggers';

const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const IMG_MAP = 'AAE_INTERACTIONS_IMG';
export const IMG_PLAYED = '__aaeImgPlayed';
const ROWS_KEY = '__aaeImgRows';
const HOST_KEY = '__aaeImgCineHost';

// Cinematic-preset field defaults (8 built-in presets merged in from the
// sibling ImageAdvancedAnimation extension — see buildRowTween's PRESET_
// BUILDERS dispatch below). Independent copy; ImageAdvancedAnimation's own
// runtime file is untouched.
const CINEMATIC_FIELD_DEFAULTS = {
	direction: 'bottomToTop', moveDirection: 'none', orbitDirection: 'left',
	parallaxDirection: 'up', sliceAxis: 'vertical', sliceDirection: 'alternate',
	origin: 'center', tileOrder: 'random',
	startScale: 1, endScale: 1, imageShift: 0, travel: 0, tilt: 0, rotation: 0,
	rotationX: 0, rotationY: 0, rotationZ: 0, blur: 0, brightness: 1, saturation: 1,
	radius: 0, shadeOpacity: 0, sliceCount: 12, sliceSkew: 0, depth: 0, stagger: 0,
	tileColumns: 6, tileRows: 5, tileScatter: 0, tileStartScale: 1, tileRotation: 0,
	waveSize: 12, circleStart: 0, circleEnd: 100, frameDistance: 0, imageDistance: 0,
	sweep: false, fade: false,
};

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

	const cfg = {
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

	// Cinematic-preset fields — pass through with their own defaults.
	for (const [key, fallback] of Object.entries(CINEMATIC_FIELD_DEFAULTS)) {
		const v = row[key];
		cfg[key] = (v === undefined || v === null || v === '') ? fallback : v;
	}

	return cfg;
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

/* ============================================================
 * Cinematic presets — merged in from the sibling ImageAdvancedAnimation
 * extension (cinematicMask, scaleAnimation, sliceShutter, mosaicDepth,
 * liquidClip, orbitTilt, zoomTunnel, scrollParallax). This block is a
 * deliberate, independent copy — ImageAdvancedAnimation's own runtime file
 * is untouched.
 *
 * DOM: e-image/e-svg render only the bare <img>/<svg> (findMedia() above),
 * so these presets build a 3-layer overlay host on demand the first time any
 * row on this element uses one of them (ensureHost) — entirely via inline
 * styles, no new stylesheet/build entry required:
 *   .aae-img-cine-frame (perspective/rotate/scale/translate)
 *     > .aae-img-cine-media (overflow-hidden clip-path MASK WINDOW)
 *       > img/svg (pan/zoom/filter)
 * plus optional tile-grid / slice-strip / sweep / shade overlay layers.
 * ============================================================ */

const CINE_FRAME_CLASS = 'aae-img-cine-frame';
const CINE_MEDIA_CLASS = 'aae-img-cine-media';

function overlayEl(className) {
	const node = document.createElement('div');
	node.className = className;
	Object.assign(node.style, {
		position: 'absolute', inset: '0', pointerEvents: 'none', willChange: 'transform,opacity',
	});
	return node;
}

/**
 * Re-find a wrapper structure that survived, from a FRESH `el` that no
 * longer carries it. Elementor re-renders an atomic widget's markup from its
 * twig template on ANY settings change, regenerating the `<img>`/`<svg>` tag
 * as a brand-new DOM node in place — a property cached on the OLD node
 * (HOST_KEY) can never be seen on the new one. The surrounding wrapper we
 * built usually survives that repaint, so detect it by marker class on the
 * current parent instead of by node identity, and reuse it.
 */
function findSurvivingCineHost(el) {
	const media = el.parentNode;
	if (!media || !media.classList || !media.classList.contains(CINE_MEDIA_CLASS)) {
		return null;
	}
	const frame = media.parentNode;
	if (!frame) return null;

	const tiles = frame.querySelector('.aae-img-cine-tiles');
	const slices = frame.querySelector('.aae-img-cine-slices');
	const sweep = frame.querySelector('.aae-img-cine-sweep');
	const shade = frame.querySelector('.aae-img-cine-shade');
	if (!tiles || !slices || !sweep || !shade) return null;

	return { frame, media, image: el, tiles, slices, sweep, shade };
}

function ensureHost(el) {
	if (el[HOST_KEY]) return el[HOST_KEY];

	const survived = findSurvivingCineHost(el);
	if (survived) {
		el[HOST_KEY] = survived;
		return survived;
	}

	let frame = el;
	const image = findMedia(el);

	if (image === el && el.parentNode) {
		const rect = el.getBoundingClientRect();
		const wrapper = document.createElement('span');
		wrapper.className = CINE_FRAME_CLASS;
		wrapper.style.display = 'block';
		if (rect.width > 0) wrapper.style.width = rect.width + 'px';
		if (rect.height > 0) wrapper.style.height = rect.height + 'px';
		el.parentNode.insertBefore(wrapper, el);
		wrapper.appendChild(el);
		Object.assign(el.style, { display: 'block', width: '100%', height: '100%' });
		frame = wrapper;

		// The frame's own transform (rotateX/translate/scale, per preset) moves
		// its rendered box beyond its resting position — clip that at the real
		// widget container (the frame's parent, e.g. the e-flexbox/e-div-block
		// wrapping this image) so the tilt/pan doesn't bleed outside its edges.
		if (wrapper.parentNode && wrapper.parentNode.style) {
			wrapper.parentNode.style.overflow = 'hidden';
		}
	}

	const media = document.createElement('span');
	media.className = CINE_MEDIA_CLASS;
	Object.assign(media.style, { display: 'block', width: '100%', height: '100%', overflow: 'hidden' });
	image.parentNode.insertBefore(media, image);
	media.appendChild(image);

	const cs = getComputedStyle(frame);
	if (cs.position === 'static') frame.style.position = 'relative';
	if (cs.overflow === 'visible' || !cs.overflow) frame.style.overflow = 'hidden';

	const tiles = overlayEl('aae-img-cine-tiles');
	tiles.style.zIndex = '4';
	const slices = overlayEl('aae-img-cine-slices');
	slices.style.zIndex = '4';
	const sweep = overlayEl('aae-img-cine-sweep');
	Object.assign(sweep.style, {
		zIndex: '5', width: '42%', left: '-58%', right: 'auto',
		background: 'linear-gradient(90deg, transparent, rgba(255,255,255,.64), transparent)',
		mixBlendMode: 'overlay', transform: 'skewX(-18deg)', opacity: '0',
	});
	const shade = overlayEl('aae-img-cine-shade');
	Object.assign(shade.style, {
		zIndex: '6', opacity: '0', mixBlendMode: 'overlay',
		background: 'linear-gradient(135deg, rgba(255,255,255,.22), transparent 34%), linear-gradient(to top, rgba(0,0,0,.45), transparent 42%)',
	});

	frame.appendChild(tiles);
	frame.appendChild(slices);
	frame.appendChild(sweep);
	frame.appendChild(shade);

	const host = { frame, media, image, tiles, slices, sweep, shade };
	el[HOST_KEY] = host;
	return host;
}

function mediaSrc(image) {
	if (image && image.tagName === 'IMG') return image.currentSrc || image.src || '';
	return '';
}

/** Lazily (re)builds the tile grid. Rebuilds only when dimensions/src changed. */
function ensureTiles(host, cols, rows) {
	const src = mediaSrc(host.image);
	const layer = host.tiles;
	const key = `${cols}x${rows}|${src}`;
	if (layer.dataset.key === key) {
		return Array.from(layer.children);
	}
	layer.innerHTML = '';
	layer.dataset.key = key;
	if (!src) return [];

	const children = [];
	for (let y = 0; y < rows; y += 1) {
		for (let x = 0; x < cols; x += 1) {
			const tile = document.createElement('span');
			Object.assign(tile.style, {
				position: 'absolute', overflow: 'hidden', willChange: 'transform,opacity,filter',
				width: `${100 / cols}%`, height: `${100 / rows}%`,
				left: `${(x * 100) / cols}%`, top: `${(y * 100) / rows}%`,
				backgroundImage: `url("${src}")`, backgroundRepeat: 'no-repeat',
				backgroundSize: `${cols * 100}% ${rows * 100}%`,
				backgroundPosition: `${(x / Math.max(cols - 1, 1)) * 100}% ${(y / Math.max(rows - 1, 1)) * 100}%`,
			});
			layer.appendChild(tile);
			children.push(tile);
		}
	}
	return children;
}

/** Lazily (re)builds the slice strip set. Rebuilds only when count/axis/src changed. */
function ensureSlices(host, count, axis) {
	const src = mediaSrc(host.image);
	const layer = host.slices;
	const key = `${count}|${axis}|${src}`;
	if (layer.dataset.key === key) {
		return Array.from(layer.children);
	}
	layer.innerHTML = '';
	layer.dataset.key = key;
	if (!src) return [];

	const children = [];
	for (let i = 0; i < count; i += 1) {
		const slice = document.createElement('span');
		const base = {
			position: 'absolute', overflow: 'hidden', willChange: 'transform,opacity,filter',
			backgroundImage: `url("${src}")`, backgroundRepeat: 'no-repeat',
		};
		if (axis === 'horizontal') {
			Object.assign(slice.style, base, {
				width: '100%', height: `${100 / count}%`, left: '0', top: `${(i * 100) / count}%`,
				backgroundSize: `100% ${count * 100}%`,
				backgroundPosition: `0 ${(i / Math.max(count - 1, 1)) * 100}%`,
			});
		} else {
			Object.assign(slice.style, base, {
				width: `${100 / count}%`, height: '100%', left: `${(i * 100) / count}%`, top: '0',
				backgroundSize: `${count * 100}% 100%`,
				backgroundPosition: `${(i / Math.max(count - 1, 1)) * 100}% 0`,
			});
		}
		layer.appendChild(slice);
		children.push(slice);
	}
	return children;
}

/* ---------- pure geometry helpers ---------- */

function cineOriginPoint(origin) {
	const origins = {
		center: '50% 50%', top: '50% 0%', bottom: '50% 100%', left: '0% 50%', right: '100% 50%',
		topLeft: '0% 0%', topRight: '100% 0%', bottomLeft: '0% 100%', bottomRight: '100% 100%',
	};
	return origins[origin] || origins.center;
}

function cineDirectionClip(direction) {
	const clips = {
		topToBottom: 'inset(0% 0% 100% 0%)', bottomToTop: 'inset(100% 0% 0% 0%)',
		leftToRight: 'inset(0% 100% 0% 0%)', rightToLeft: 'inset(0% 0% 0% 100%)',
		centerOut: 'inset(42% 42% 42% 42%)',
	};
	return clips[direction] || clips.bottomToTop;
}

function cineDirectionOffset(direction, amount, unit = 'percent') {
	const offset = { x: 0, y: 0, xPercent: 0, yPercent: 0 };
	const xKey = unit === 'pixel' ? 'x' : 'xPercent';
	const yKey = unit === 'pixel' ? 'y' : 'yPercent';
	if (direction === 'topToBottom') offset[yKey] = -amount;
	else if (direction === 'bottomToTop') offset[yKey] = amount;
	else if (direction === 'leftToRight') offset[xKey] = -amount;
	else if (direction === 'rightToLeft') offset[xKey] = amount;
	return offset;
}

function cineSliceOffset(direction, axis, index, amount = 104) {
	let resolved = direction;
	if (direction === 'alternate') {
		resolved = axis === 'horizontal'
			? (index % 2 ? 'rightToLeft' : 'leftToRight')
			: (index % 2 ? 'bottomToTop' : 'topToBottom');
	}
	return cineDirectionOffset(resolved, amount);
}

function cineOrbitStart(cfg) {
	const start = {
		autoAlpha: 0, rotationX: 0, rotationY: 0, rotationZ: 0, z: -cfg.depth, x: 0, y: 0,
		transformPerspective: 1400,
	};
	if (cfg.orbitDirection === 'right') {
		start.rotationY = cfg.rotationY; start.rotationZ = cfg.rotationZ; start.x = cfg.travel;
	} else if (cfg.orbitDirection === 'top') {
		start.rotationX = -cfg.rotationX; start.y = -cfg.travel;
	} else if (cfg.orbitDirection === 'bottom') {
		start.rotationX = cfg.rotationX; start.y = cfg.travel;
	} else {
		start.rotationY = -cfg.rotationY; start.rotationZ = -cfg.rotationZ; start.x = -cfg.travel;
	}
	return start;
}

function cineLiquidPolygons(direction, wave) {
	const w = Number(wave);
	const mid = 50;
	if (direction === 'centerOut') {
		return ['circle(0% at 50% 50%)', 'circle(42% at 50% 50%)', 'circle(75% at 50% 50%)'];
	}
	if (direction === 'topToBottom') {
		return [
			`polygon(0 0, 100% 0, 100% ${w}%, 82% ${w * 1.35}%, 64% ${w * 0.8}%, 45% ${w * 1.55}%, 28% ${w * 0.9}%, 12% ${w * 1.25}%, 0 ${w}%)`,
			`polygon(0 0, 100% 0, 100% ${mid + w}%, 82% ${mid + w * 1.35}%, 64% ${mid + w * 0.8}%, 45% ${mid + w * 1.45}%, 28% ${mid + w * 0.7}%, 12% ${mid + w * 1.15}%, 0 ${mid + w}%)`,
			'polygon(0 0, 100% 0, 100% 100%, 0 100%)',
		];
	}
	if (direction === 'leftToRight') {
		return [
			`polygon(0 0, ${w}% 0, ${w * 1.45}% 16%, ${w * 0.8}% 32%, ${w * 1.35}% 50%, ${w * 0.9}% 68%, ${w * 1.25}% 84%, ${w}% 100%, 0 100%)`,
			`polygon(0 0, ${mid + w}% 0, ${mid + w * 1.35}% 16%, ${mid + w * 0.7}% 32%, ${mid + w * 1.3}% 50%, ${mid + w * 0.85}% 68%, ${mid + w * 1.2}% 84%, ${mid + w}% 100%, 0 100%)`,
			'polygon(0 0, 100% 0, 100% 100%, 0 100%)',
		];
	}
	if (direction === 'rightToLeft') {
		return [
			`polygon(${100 - w}% 0, 100% 0, 100% 100%, ${100 - w}% 100%, ${100 - w * 1.3}% 84%, ${100 - w * 0.85}% 68%, ${100 - w * 1.35}% 50%, ${100 - w * 0.8}% 32%, ${100 - w * 1.45}% 16%)`,
			`polygon(${mid - w}% 0, 100% 0, 100% 100%, ${mid - w}% 100%, ${mid - w * 1.2}% 84%, ${mid - w * 0.75}% 68%, ${mid - w * 1.3}% 50%, ${mid - w * 0.8}% 32%, ${mid - w * 1.35}% 16%)`,
			'polygon(0 0, 100% 0, 100% 100%, 0 100%)',
		];
	}
	return [
		`polygon(0 ${100 - w}%, 12% ${100 - w * 1.25}%, 28% ${100 - w * 0.9}%, 45% ${100 - w * 1.55}%, 64% ${100 - w * 0.8}%, 82% ${100 - w * 1.35}%, 100% ${100 - w}%, 100% 100%, 0 100%)`,
		`polygon(0 ${mid - w}%, 12% ${mid - w * 1.15}%, 28% ${mid - w * 0.7}%, 45% ${mid - w * 1.45}%, 64% ${mid - w * 0.8}%, 82% ${mid - w * 1.35}%, 100% ${mid - w}%, 100% 100%, 0 100%)`,
		'polygon(0 0, 100% 0, 100% 100%, 0 100%)',
	];
}

function cineParallaxValues(direction, frameDistance, imageDistance) {
	const frameStart = { x: 0, y: 0 };
	const frameEnd = { x: 0, y: 0 };
	const imageStart = { xPercent: 0, yPercent: 0 };
	const imageEnd = { xPercent: 0, yPercent: 0 };
	if (direction === 'down') {
		frameStart.y = -frameDistance; frameEnd.y = frameDistance * 0.35;
		imageStart.yPercent = -imageDistance; imageEnd.yPercent = imageDistance;
	} else if (direction === 'left') {
		frameStart.x = frameDistance; frameEnd.x = -frameDistance * 0.35;
		imageStart.xPercent = imageDistance; imageEnd.xPercent = -imageDistance;
	} else if (direction === 'right') {
		frameStart.x = -frameDistance; frameEnd.x = frameDistance * 0.35;
		imageStart.xPercent = -imageDistance; imageEnd.xPercent = imageDistance;
	} else if (direction === 'diagonalUp') {
		frameStart.x = frameDistance * 0.45; frameStart.y = frameDistance;
		frameEnd.x = -frameDistance * 0.35; frameEnd.y = -frameDistance * 0.45;
		imageStart.xPercent = imageDistance * 0.45; imageStart.yPercent = imageDistance;
		imageEnd.xPercent = -imageDistance * 0.45; imageEnd.yPercent = -imageDistance;
	} else if (direction === 'diagonalDown') {
		frameStart.x = -frameDistance * 0.45; frameStart.y = -frameDistance;
		frameEnd.x = frameDistance * 0.35; frameEnd.y = frameDistance * 0.45;
		imageStart.xPercent = -imageDistance * 0.45; imageStart.yPercent = -imageDistance;
		imageEnd.xPercent = imageDistance * 0.45; imageEnd.yPercent = imageDistance;
	} else {
		frameStart.y = frameDistance; frameEnd.y = -frameDistance * 0.35;
		imageStart.yPercent = imageDistance; imageEnd.yPercent = -imageDistance;
	}
	return { frameStart, frameEnd, imageStart, imageEnd };
}

function resetCineHost(host) {
	const gsap = getGsap();
	if (!gsap) return;
	const { frame, media, image, tiles, slices, sweep, shade } = host;
	const tileChildren = Array.from(tiles.children);
	const sliceChildren = Array.from(slices.children);

	gsap.killTweensOf([frame, media, image, tiles, slices, sweep, shade, ...tileChildren, ...sliceChildren]);
	gsap.set([frame, media, image], {
		clearProps: 'transform,opacity,visibility,clipPath,filter,borderRadius,scale,x,y,z,rotation,rotationX,rotationY,rotationZ,skewX,skewY,xPercent,yPercent,transformPerspective',
	});
	gsap.set(frame, { autoAlpha: 1 });
	gsap.set(media, { autoAlpha: 1 });
	gsap.set([tiles, slices, sweep, shade], { autoAlpha: 0 });
}

function cineBaseTimeline(cfg, paused, scrub) {
	const gsap = getGsap();
	return gsap.timeline({ paused: !!paused, defaults: { ease: scrub ? 'none' : (cfg.ease || 'expo.out') } });
}

function cineSweepLight(tl, host, cfg, at = 0.2) {
	if (!cfg.sweep) return;
	tl.fromTo(host.sweep,
		{ autoAlpha: 0, xPercent: 0 },
		{ autoAlpha: 0.85, xPercent: 390, duration: Math.min(0.95, cfg.duration), ease: 'power2.inOut' },
		at
	).to(host.sweep, { autoAlpha: 0, duration: 0.22 }, at + Math.min(0.74, cfg.duration * 0.65));
}

function buildCinematicMask(host, cfg, paused, scrub) {
	const { frame, media, image, shade } = host;
	const imageOffset = cineDirectionOffset(cfg.direction, cfg.imageShift);
	const frameOffset = cineDirectionOffset(cfg.direction, cfg.travel, 'pixel');
	const tl = cineBaseTimeline(cfg, paused, scrub);

	tl.set(frame, {
		...frameOffset, rotationX: cfg.tilt, scale: 0.94, borderRadius: cfg.radius,
		transformPerspective: 1400,
	})
		.set(media, { clipPath: cineDirectionClip(cfg.direction) })
		.set(image, { ...imageOffset, scale: cfg.startScale, filter: `saturate(${cfg.saturation || 0.8}) contrast(1.14)` })
		.set(shade, { autoAlpha: 0 })
		.to(frame, { x: 0, y: 0, rotationX: 0, scale: 1, duration: cfg.duration }, 0)
		.to(media, { clipPath: 'inset(0% 0% 0% 0%)', duration: cfg.duration }, 0)
		.to(image, { xPercent: 0, yPercent: 0, scale: cfg.endScale, filter: 'saturate(1) contrast(1)', duration: cfg.duration * 1.08, ease: scrub ? 'none' : 'power3.out' }, 0)
		.to(shade, { autoAlpha: 0.9, duration: cfg.duration * 0.32 }, 0.12)
		.to(shade, { autoAlpha: cfg.shadeOpacity, duration: cfg.duration * 0.55 }, cfg.duration * 0.56);

	cineSweepLight(tl, host, cfg, cfg.duration * 0.28);
	return tl;
}

function buildScaleAnimationCine(host, cfg, paused, scrub) {
	const { frame, image } = host;
	const movement = cineDirectionOffset(cfg.moveDirection, cfg.imageShift);
	const tl = cineBaseTimeline(cfg, paused, scrub);

	tl.set(frame, {
		transformOrigin: cineOriginPoint(cfg.origin), scale: cfg.startScale, rotation: cfg.rotation,
		autoAlpha: cfg.fade ? 0 : 1, ...movement,
	})
		.set(image, { scale: Math.max(cfg.endScale, 1) + 0.08, filter: `blur(${cfg.blur}px)` })
		.to(frame, { xPercent: 0, yPercent: 0, scale: cfg.endScale, rotation: 0, autoAlpha: 1, duration: cfg.duration }, 0)
		.to(image, { scale: 1, filter: 'blur(0px)', duration: cfg.duration, ease: scrub ? 'none' : 'power3.out' }, 0);

	return tl;
}

function buildSliceShutter(host, cfg, paused, scrub) {
	const { image } = host;
	const src = mediaSrc(image);
	const tl = cineBaseTimeline(cfg, paused, scrub);

	if (!src) {
		tl.set(image, { autoAlpha: 0, scale: 1.08 }).to(image, { autoAlpha: 1, scale: 1, duration: cfg.duration });
		return tl;
	}

	const slices = ensureSlices(host, cfg.sliceCount, cfg.sliceAxis);
	if (!slices.length) {
		tl.set(image, { autoAlpha: 0 }).to(image, { autoAlpha: 1, duration: cfg.duration });
		return tl;
	}

	tl.set(image, { autoAlpha: 0 })
		.set(host.slices, { autoAlpha: 1 })
		.set(slices, {
			autoAlpha: 0,
			xPercent: (i) => cineSliceOffset(cfg.sliceDirection, cfg.sliceAxis, i).xPercent,
			yPercent: (i) => cineSliceOffset(cfg.sliceDirection, cfg.sliceAxis, i).yPercent,
			rotationY: (i) => {
				const x = cineSliceOffset(cfg.sliceDirection, cfg.sliceAxis, i).xPercent;
				return x ? (x > 0 ? -cfg.depth : cfg.depth) : 0;
			},
			rotationX: (i) => {
				const y = cineSliceOffset(cfg.sliceDirection, cfg.sliceAxis, i).yPercent;
				return y ? (y > 0 ? cfg.depth : -cfg.depth) : 0;
			},
			skewY: (i) => (i % 2 ? cfg.sliceSkew : -cfg.sliceSkew),
			transformOrigin: 'center center',
			filter: `brightness(${cfg.brightness}) saturate(${cfg.saturation})`,
		})
		.to(slices, {
			autoAlpha: 1, xPercent: 0, yPercent: 0, rotationY: 0, rotationX: 0, skewY: 0,
			filter: 'brightness(1) saturate(1)', duration: cfg.duration,
			stagger: { amount: cfg.stagger, from: 'center' },
		})
		.set(image, { autoAlpha: 1 }, '-=0.12')
		.to(host.slices, { autoAlpha: 0, duration: Math.min(0.35, cfg.duration * 0.3) }, '<');

	return tl;
}

function buildMosaicDepth(host, cfg, paused, scrub) {
	const gsap = getGsap();
	const { image } = host;
	const src = mediaSrc(image);
	const tl = cineBaseTimeline(cfg, paused, scrub);

	if (!src) {
		tl.set(image, { autoAlpha: 0, scale: 1.08 }).to(image, { autoAlpha: 1, scale: 1, duration: cfg.duration });
		return tl;
	}

	const tiles = ensureTiles(host, cfg.tileColumns, cfg.tileRows);
	if (!tiles.length) {
		tl.set(image, { autoAlpha: 0 }).to(image, { autoAlpha: 1, duration: cfg.duration });
		return tl;
	}

	tl.set(image, { autoAlpha: 0 })
		.set(host.tiles, { autoAlpha: 1 })
		.set(tiles, {
			autoAlpha: 0, scale: cfg.tileStartScale,
			z: () => gsap.utils.random(-cfg.depth, cfg.depth),
			x: () => gsap.utils.random(-cfg.tileScatter, cfg.tileScatter),
			y: () => gsap.utils.random(-cfg.tileScatter, cfg.tileScatter),
			rotationX: () => gsap.utils.random(-cfg.tileRotation, cfg.tileRotation),
			rotationY: () => gsap.utils.random(-cfg.tileRotation, cfg.tileRotation),
			filter: `brightness(${cfg.brightness}) saturate(${cfg.saturation})`,
		})
		.to(tiles, {
			autoAlpha: 1, scale: 1, x: 0, y: 0, z: 0, rotationX: 0, rotationY: 0,
			filter: 'brightness(1) saturate(1)', duration: cfg.duration,
			stagger: { amount: cfg.stagger, from: cfg.tileOrder, grid: [cfg.tileRows, cfg.tileColumns] },
		})
		.set(image, { autoAlpha: 1 }, '-=0.15')
		.to(host.tiles, { autoAlpha: 0, duration: Math.min(0.38, cfg.duration * 0.32) }, '<');

	return tl;
}

function buildLiquidClip(host, cfg, paused, scrub) {
	const { image } = host;
	const [startClip, midClip, endClip] = cineLiquidPolygons(cfg.direction, cfg.waveSize);
	const imageOffset = cineDirectionOffset(cfg.direction, cfg.imageShift);
	const tl = cineBaseTimeline(cfg, paused, scrub);

	tl.set(image, { clipPath: startClip })
		.set(image, { ...imageOffset, scale: cfg.startScale, filter: `blur(${cfg.blur}px) saturate(${cfg.saturation})` })
		.to(image, { clipPath: midClip, duration: cfg.duration * 0.38, ease: scrub ? 'none' : 'sine.inOut' })
		.to(image, { clipPath: endClip, duration: cfg.duration * 0.68 })
		.to(image, { xPercent: 0, yPercent: 0, scale: cfg.endScale, filter: 'blur(0px) saturate(1)', duration: cfg.duration, ease: scrub ? 'none' : 'power3.out' }, 0);

	cineSweepLight(tl, host, cfg, cfg.duration * 0.48);
	return tl;
}

function buildOrbitTilt(host, cfg, paused, scrub) {
	const { frame, image } = host;
	const tl = cineBaseTimeline(cfg, paused, scrub);

	tl.set(frame, cineOrbitStart(cfg))
		.set(image, { scale: cfg.startScale, filter: `saturate(${cfg.saturation}) brightness(${cfg.brightness}) contrast(1.14)` })
		.to(frame, { autoAlpha: 1, rotationY: 0, rotationX: 0, rotationZ: 0, z: 0, x: 0, y: 0, duration: cfg.duration })
		.to(image, { scale: cfg.endScale, filter: 'saturate(1) brightness(1) contrast(1)', duration: cfg.duration, ease: scrub ? 'none' : 'power3.out' }, 0);

	cineSweepLight(tl, host, cfg, cfg.duration * 0.3);
	return tl;
}

function buildZoomTunnel(host, cfg, paused, scrub) {
	const { frame, image } = host;
	const origin = cineOriginPoint(cfg.origin);
	const tl = cineBaseTimeline(cfg, paused, scrub);

	tl.set(image, { clipPath: `circle(${cfg.circleStart}% at ${origin})` })
		.set(frame, { scale: 0.82, rotationX: cfg.tilt, z: -cfg.depth })
		.set(image, { scale: cfg.startScale, filter: `brightness(${cfg.brightness}) contrast(1.18)` })
		.to(image, { clipPath: `circle(${cfg.circleEnd}% at ${origin})`, duration: cfg.duration }, 0)
		.to(frame, { scale: 1, rotationX: 0, z: 0, duration: cfg.duration, ease: scrub ? 'none' : 'expo.out' }, 0)
		.to(image, { scale: cfg.endScale, filter: 'brightness(1) contrast(1)', duration: cfg.duration, ease: scrub ? 'none' : 'power4.out' }, 0);

	return tl;
}

function buildScrollParallax(host, cfg, paused, scrub) {
	const { frame, image, shade } = host;
	const values = cineParallaxValues(cfg.parallaxDirection, cfg.frameDistance, cfg.imageDistance);
	const tl = cineBaseTimeline(cfg, paused, true); // parallax is scroll-first by nature — always linear

	tl.set(frame, { ...values.frameStart, rotationX: cfg.rotationX, scale: 0.95 })
		.set(image, { ...values.imageStart, scale: cfg.startScale })
		.set(shade, { autoAlpha: cfg.shadeOpacity })
		.to(frame, { ...values.frameEnd, rotationX: -cfg.rotationX * 0.4, scale: 1, duration: cfg.duration, ease: 'none' }, 0)
		.to(image, { ...values.imageEnd, scale: cfg.endScale, duration: cfg.duration, ease: 'none' }, 0)
		.to(shade, { autoAlpha: Math.min(cfg.shadeOpacity, 0.18), duration: cfg.duration, ease: 'none' }, 0);

	void scrub;
	return tl;
}

const CINEMATIC_PRESET_BUILDERS = {
	cinematicMask: buildCinematicMask,
	scaleAnimation: buildScaleAnimationCine,
	sliceShutter: buildSliceShutter,
	mosaicDepth: buildMosaicDepth,
	liquidClip: buildLiquidClip,
	orbitTilt: buildOrbitTilt,
	zoomTunnel: buildZoomTunnel,
	scrollParallax: buildScrollParallax,
};

/** Build one row's tween for the given effect. reveal/scale/stretch are
 *  built-in presets with bespoke logic; the 8 cinematic presets build/reuse
 *  an overlay host (ensureHost); everything else (custom + premium presets
 *  like fadeUp/blurReveal) runs through the custom-props tween — the editor
 *  fills custom_props from the preset table, so they're identical at
 *  runtime. */
function buildRowTween(el, config, paused, scrub) {
	switch (config.effect) {
		case 'reveal':  return buildRevealTween(el, config, paused);
		case 'scale':   return buildScaleTween(el, config, paused, scrub);
		case 'stretch': return buildStretchTween(el, config, paused, scrub);
		default: {
			const cineBuilder = CINEMATIC_PRESET_BUILDERS[config.effect];
			if (cineBuilder) {
				const gsap = getGsap();
				if (!gsap) return null;
				const host = ensureHost(el);
				resetCineHost(host);
				return cineBuilder(host, config, paused, scrub);
			}
			return buildCustomTween(el, config, paused, scrub);
		}
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

	// Cinematic preset host (frame/media/tiles/slices/sweep/shade), built on
	// demand the first time any row on this element used one of the 8
	// cinematic presets.
	const cineHost = el[HOST_KEY];
	if (cineHost) resetCineHost(cineHost);
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
