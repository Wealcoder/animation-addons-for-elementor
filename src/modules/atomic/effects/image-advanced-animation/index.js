/* eslint-env browser */

/**
 * Image Advanced Animation — REPEATER runtime.
 *
 * Ported from the "Advanced Single Image GSAP Widget" prototype
 * (z_temp/Image Animation/script.js): 8 cinematic presets — cinematicMask,
 * scaleAnimation, sliceShutter, mosaicDepth, liquidClip, orbitTilt,
 * zoomTunnel, scrollParallax. Config shape mirrors ImageAnimation's runtime:
 * `{ rows: [ <interaction>, ... ], rows_<bp>: [...] }`, each row an
 * independent interaction (preset + trigger + config).
 *
 * Unlike the prototype (bespoke play/hover/scrollEnter/scrollScrub wiring +
 * continuous pointer-follow hover tilt), every row here dispatches through
 * the SAME shared wireTrigger() dispatcher every other kind in this plugin
 * uses — hover fires the preset's entrance timeline once on mouseenter
 * (no continuous pointer tracking), scroll trigger fires once on enter,
 * and "Play With Scroll" scrubs the timeline's progress to scroll position.
 *
 * DOM: the prototype's markup pre-declares .image-widget__{tiles,slices,
 * sweep,shade} overlay layers. Atomic e-image/e-svg widgets render only the
 * bare <img>/<svg>, so this runtime builds that overlay host itself
 * (ensureHost) the first time any row targets the element, entirely via
 * inline styles — no new stylesheet/build entry required.
 */

import { wireTrigger, modeFor, resolveTriggerEl } from '../animation/triggers';

const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const IMGADV_MAP = 'AAE_INTERACTIONS_IMGADV';
export const IMGADV_PLAYED = '__aaeImgAdvPlayed';
const ROWS_KEY = '__aaeImgAdvRows';
const HOST_KEY = '__aaeImgAdvHost';

/* ============================================================
 * read
 * ============================================================ */

const FIELD_DEFAULTS = {
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
		duration: Number(row.duration ?? 1.2),
		delay: Number(row.delay ?? 0),
		ease: row.ease || 'expo.out',
	};
	for (const [key, fallback] of Object.entries(FIELD_DEFAULTS)) {
		const v = row[key];
		cfg[key] = (v === undefined || v === null || v === '') ? fallback : v;
	}
	return cfg;
}

export function readImgAdv(el) {
	const cfg = configFor(el, IMGADV_MAP);
	if (!cfg) return null;

	const rows = pickConfigResponsive(cfg, 'rows');
	if (!Array.isArray(rows) || rows.length === 0) return null;

	const rowConfigs = rows.map((row) => normalizeRow(row)).filter(Boolean);
	if (!rowConfigs.length) return null;
	return { rows: rowConfigs };
}

/* ============================================================
 * overlay host — built once per element, on demand
 * ============================================================ */

function findMedia(el) {
	return el.querySelector('img, svg') || el;
}

function overlayEl(className) {
	const node = document.createElement('div');
	node.className = className;
	Object.assign(node.style, {
		position: 'absolute', inset: '0', pointerEvents: 'none', willChange: 'transform,opacity',
	});
	return node;
}

/**
 * The prototype is THREE independent layers, not two:
 *   .image-widget (frame: perspective/rotate/scale/border-radius/translate)
 *     > .image-widget__media (media: overflow-hidden clip-path MASK WINDOW)
 *       > img (image: pan/zoom/filter)
 *
 * cinematicMask/liquidClip/zoomTunnel all clip-path the MASK, independently
 * of the image's own scale/filter/pan — that's what makes the reveal shape
 * stay a clean static window while the picture pans/zooms inside it. e-image
 * / e-svg render the bare tag as the interaction root (no wrapper at all in
 * the markup, `data-interaction-id` sits directly on the `<img>`/`<svg>`),
 * which collapses all three layers onto ONE node — clip-path and scale then
 * both apply to the same element, so the clip region visually scales/shifts
 * WITH the image instead of staying a fixed window, and frame-level
 * scale/rotate (the "pulled back" 3D tilt) fights the image's own scale for
 * control of the same property on the same tick.
 *
 * Fix: rebuild the real three-layer structure, sized from a one-time
 * `getBoundingClientRect()` measurement of the original tag rather than by
 * copying its class list onto the wrapper. Copying classes was tried first
 * and reverted: atomic per-instance style classes carry more than layout
 * (object-fit, border-radius, filters, background, box-shadow…), and having
 * that SAME class resolve on both the new wrapper AND the original tag
 * doubled up any such decoration — a visibly different result, not just a
 * sizing bug. Measuring instead pins frame/media to the image's own
 * rendered box (whatever combination of flex/grid/CSS produced it) and
 * forces `image` to fill it at 100%/100% — same aspect ratio as before, so
 * nothing stretches. Trade-off: the box no longer re-flows on a live window
 * resize once wrapped (same trade-off already accepted elsewhere in this
 * codebase for exact-geometry cases — see Multi-Step Forms' rect-capture
 * fix in CLAUDE.md); fine for a transient entrance/hover animation.
 */
function ensureHost(el) {
	if (el[HOST_KEY]) return el[HOST_KEY];

	let frame = el;
	const image = findMedia(el);

	if (image === el && el.parentNode) {
		const rect = el.getBoundingClientRect();
		const wrapper = document.createElement('span');
		wrapper.style.display = 'block';
		if (rect.width > 0) wrapper.style.width = rect.width + 'px';
		if (rect.height > 0) wrapper.style.height = rect.height + 'px';
		el.parentNode.insertBefore(wrapper, el);
		wrapper.appendChild(el);
		Object.assign(el.style, { display: 'block', width: '100%', height: '100%' });
		frame = wrapper;
	}

	// Middle "media" layer — the clip-path mask window, independent of both
	// frame's own transform and image's own pan/zoom. Wraps `image` in place,
	// filling frame at 100%/100% — same box as `image` occupied before.
	const media = document.createElement('span');
	Object.assign(media.style, { display: 'block', width: '100%', height: '100%', overflow: 'hidden' });
	image.parentNode.insertBefore(media, image);
	media.appendChild(image);

	const cs = getComputedStyle(frame);
	if (cs.position === 'static') frame.style.position = 'relative';
	if (cs.overflow === 'visible' || !cs.overflow) frame.style.overflow = 'hidden';

	const tiles = overlayEl('aae-imgadv-tiles');
	tiles.style.zIndex = '4';
	const slices = overlayEl('aae-imgadv-slices');
	slices.style.zIndex = '4';
	const sweep = overlayEl('aae-imgadv-sweep');
	Object.assign(sweep.style, {
		zIndex: '5', width: '42%', left: '-58%', right: 'auto',
		background: 'linear-gradient(90deg, transparent, rgba(255,255,255,.64), transparent)',
		mixBlendMode: 'overlay', transform: 'skewX(-18deg)', opacity: '0',
	});
	const shade = overlayEl('aae-imgadv-shade');
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

/* ============================================================
 * pure geometry helpers — ported from the prototype
 * ============================================================ */

function originPoint(origin) {
	const origins = {
		center: '50% 50%', top: '50% 0%', bottom: '50% 100%', left: '0% 50%', right: '100% 50%',
		topLeft: '0% 0%', topRight: '100% 0%', bottomLeft: '0% 100%', bottomRight: '100% 100%',
	};
	return origins[origin] || origins.center;
}

function directionClip(direction) {
	const clips = {
		topToBottom: 'inset(0% 0% 100% 0%)', bottomToTop: 'inset(100% 0% 0% 0%)',
		leftToRight: 'inset(0% 100% 0% 0%)', rightToLeft: 'inset(0% 0% 0% 100%)',
		centerOut: 'inset(42% 42% 42% 42%)',
	};
	return clips[direction] || clips.bottomToTop;
}

function directionOffset(direction, amount, unit = 'percent') {
	const offset = { x: 0, y: 0, xPercent: 0, yPercent: 0 };
	const xKey = unit === 'pixel' ? 'x' : 'xPercent';
	const yKey = unit === 'pixel' ? 'y' : 'yPercent';
	if (direction === 'topToBottom') offset[yKey] = -amount;
	else if (direction === 'bottomToTop') offset[yKey] = amount;
	else if (direction === 'leftToRight') offset[xKey] = -amount;
	else if (direction === 'rightToLeft') offset[xKey] = amount;
	return offset;
}

function sliceOffset(direction, axis, index, amount = 104) {
	let resolved = direction;
	if (direction === 'alternate') {
		resolved = axis === 'horizontal'
			? (index % 2 ? 'rightToLeft' : 'leftToRight')
			: (index % 2 ? 'bottomToTop' : 'topToBottom');
	}
	return directionOffset(resolved, amount);
}

function orbitStart(cfg) {
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

function liquidPolygons(direction, wave) {
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

function parallaxValues(direction, frameDistance, imageDistance) {
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

/* ============================================================
 * reset
 * ============================================================ */

function resetHost(host) {
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

/* ============================================================
 * per-preset tween builders
 * ============================================================ */

function baseTimeline(cfg, paused, scrub) {
	const gsap = getGsap();
	return gsap.timeline({ paused: !!paused, defaults: { ease: scrub ? 'none' : (cfg.ease || 'expo.out') } });
}

function sweepLight(tl, host, cfg, at = 0.2) {
	if (!cfg.sweep) return;
	tl.fromTo(host.sweep,
		{ autoAlpha: 0, xPercent: 0 },
		{ autoAlpha: 0.85, xPercent: 390, duration: Math.min(0.95, cfg.duration), ease: 'power2.inOut' },
		at
	).to(host.sweep, { autoAlpha: 0, duration: 0.22 }, at + Math.min(0.74, cfg.duration * 0.65));
}

function buildCinematicMask(host, cfg, paused, scrub) {
	const { frame, media, image, shade } = host;
	const imageOffset = directionOffset(cfg.direction, cfg.imageShift);
	const frameOffset = directionOffset(cfg.direction, cfg.travel, 'pixel');
	const tl = baseTimeline(cfg, paused, scrub);

	tl.set(frame, {
		...frameOffset, rotationX: cfg.tilt, scale: 0.94, borderRadius: cfg.radius,
		// Without a perspective ancestor (the prototype relies on the demo
		// page's `.widget-stage { perspective: 1400px }`, which doesn't exist
		// in atomic markup), rotationX renders as a flat, invisible-in-2D
		// rotation — transformPerspective supplies that depth cue directly on
		// the tweened element, same as orbitTilt already does via orbitStart().
		transformPerspective: 1400,
	})
		// The mask window (media) clips independently of the image's own pan/
		// zoom below — this is what keeps the reveal a clean static rectangle
		// instead of scaling/shifting along with the picture.
		.set(media, { clipPath: directionClip(cfg.direction) })
		.set(image, { ...imageOffset, scale: cfg.startScale, filter: `saturate(${cfg.saturation || 0.8}) contrast(1.14)` })
		.set(shade, { autoAlpha: 0 })
		.to(frame, { x: 0, y: 0, rotationX: 0, scale: 1, duration: cfg.duration }, 0)
		.to(media, { clipPath: 'inset(0% 0% 0% 0%)', duration: cfg.duration }, 0)
		.to(image, { xPercent: 0, yPercent: 0, scale: cfg.endScale, filter: 'saturate(1) contrast(1)', duration: cfg.duration * 1.08, ease: scrub ? 'none' : 'power3.out' }, 0)
		.to(shade, { autoAlpha: 0.9, duration: cfg.duration * 0.32 }, 0.12)
		.to(shade, { autoAlpha: cfg.shadeOpacity, duration: cfg.duration * 0.55 }, cfg.duration * 0.56);

	sweepLight(tl, host, cfg, cfg.duration * 0.28);
	return tl;
}

function buildScaleAnimation(host, cfg, paused, scrub) {
	const { frame, image } = host;
	const movement = directionOffset(cfg.moveDirection, cfg.imageShift);
	const tl = baseTimeline(cfg, paused, scrub);

	tl.set(frame, {
		transformOrigin: originPoint(cfg.origin), scale: cfg.startScale, rotation: cfg.rotation,
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
	const tl = baseTimeline(cfg, paused, scrub);

	if (!src) {
		// No raster src (e.g. inline <svg>) — graceful fallback: simple fade/scale.
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
			xPercent: (i) => sliceOffset(cfg.sliceDirection, cfg.sliceAxis, i).xPercent,
			yPercent: (i) => sliceOffset(cfg.sliceDirection, cfg.sliceAxis, i).yPercent,
			rotationY: (i) => {
				const x = sliceOffset(cfg.sliceDirection, cfg.sliceAxis, i).xPercent;
				return x ? (x > 0 ? -cfg.depth : cfg.depth) : 0;
			},
			rotationX: (i) => {
				const y = sliceOffset(cfg.sliceDirection, cfg.sliceAxis, i).yPercent;
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
	const tl = baseTimeline(cfg, paused, scrub);

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
	const [startClip, midClip, endClip] = liquidPolygons(cfg.direction, cfg.waveSize);
	const imageOffset = directionOffset(cfg.direction, cfg.imageShift);
	const tl = baseTimeline(cfg, paused, scrub);

	tl.set(image, { clipPath: startClip })
		.set(image, { ...imageOffset, scale: cfg.startScale, filter: `blur(${cfg.blur}px) saturate(${cfg.saturation})` })
		.to(image, { clipPath: midClip, duration: cfg.duration * 0.38, ease: scrub ? 'none' : 'sine.inOut' })
		.to(image, { clipPath: endClip, duration: cfg.duration * 0.68 })
		.to(image, { xPercent: 0, yPercent: 0, scale: cfg.endScale, filter: 'blur(0px) saturate(1)', duration: cfg.duration, ease: scrub ? 'none' : 'power3.out' }, 0);

	sweepLight(tl, host, cfg, cfg.duration * 0.48);
	return tl;
}

function buildOrbitTilt(host, cfg, paused, scrub) {
	const { frame, image } = host;
	const tl = baseTimeline(cfg, paused, scrub);

	tl.set(frame, orbitStart(cfg))
		.set(image, { scale: cfg.startScale, filter: `saturate(${cfg.saturation}) brightness(${cfg.brightness}) contrast(1.14)` })
		.to(frame, { autoAlpha: 1, rotationY: 0, rotationX: 0, rotationZ: 0, z: 0, x: 0, y: 0, duration: cfg.duration })
		.to(image, { scale: cfg.endScale, filter: 'saturate(1) brightness(1) contrast(1)', duration: cfg.duration, ease: scrub ? 'none' : 'power3.out' }, 0);

	sweepLight(tl, host, cfg, cfg.duration * 0.3);
	return tl;
}

function buildZoomTunnel(host, cfg, paused, scrub) {
	const { frame, image } = host;
	const origin = originPoint(cfg.origin);
	const tl = baseTimeline(cfg, paused, scrub);

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
	const values = parallaxValues(cfg.parallaxDirection, cfg.frameDistance, cfg.imageDistance);
	const tl = baseTimeline(cfg, paused, true); // parallax is scroll-first by nature — always linear

	tl.set(frame, { ...values.frameStart, rotationX: cfg.rotationX, scale: 0.95 })
		.set(image, { ...values.imageStart, scale: cfg.startScale })
		.set(shade, { autoAlpha: cfg.shadeOpacity })
		.to(frame, { ...values.frameEnd, rotationX: -cfg.rotationX * 0.4, scale: 1, duration: cfg.duration, ease: 'none' }, 0)
		.to(image, { ...values.imageEnd, scale: cfg.endScale, duration: cfg.duration, ease: 'none' }, 0)
		.to(shade, { autoAlpha: Math.min(cfg.shadeOpacity, 0.18), duration: cfg.duration, ease: 'none' }, 0);

	void scrub;
	return tl;
}

const PRESET_BUILDERS = {
	cinematicMask: buildCinematicMask,
	scaleAnimation: buildScaleAnimation,
	sliceShutter: buildSliceShutter,
	mosaicDepth: buildMosaicDepth,
	liquidClip: buildLiquidClip,
	orbitTilt: buildOrbitTilt,
	zoomTunnel: buildZoomTunnel,
	scrollParallax: buildScrollParallax,
};

function buildRowTween(el, cfg, paused, scrub) {
	const gsap = getGsap();
	if (!gsap) return null;
	const host = ensureHost(el);
	resetHost(host);
	const builder = PRESET_BUILDERS[cfg.effect];
	if (!builder) return null;
	return builder(host, cfg, paused, scrub);
}

/* ============================================================
 * per-element row state (mirrors ImageAnimation's kind interface)
 * ============================================================ */

function getRowState(el) {
	return Array.isArray(el[ROWS_KEY]) ? el[ROWS_KEY] : [];
}

function killAllRows(el) {
	const state = getRowState(el);
	for (const entry of state) {
		if (entry.dispose) { try { entry.dispose(); } catch (_) {} }
		if (entry.tween) { try { entry.tween.kill?.(); } catch (_) {} }
	}
	el[ROWS_KEY] = [];
	delete el[IMGADV_PLAYED];

	const host = el[HOST_KEY];
	if (host) resetHost(host);
}

export function resetImgAdv(el) {
	killAllRows(el);
}

export function playImgAdv(el, mapConfig) {
	const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];
	killAllRows(el);

	const state = [];
	for (const rowCfg of rows) {
		const tween = buildRowTween(el, rowCfg, false, false);
		state.push({ config: rowCfg, tween, dispose: null });
		if (tween) el[IMGADV_PLAYED] = tween;
	}
	el[ROWS_KEY] = state;
}

const SCROLL_MODES = ['scroll-tied', 'scrub', 'in-view'];

/** Editor-only: play ONE row in isolation (per-row play icon). */
export function playImgAdvRow(el, mapConfig, rowIndex = 0, explicitRow = null) {
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
		bindImgAdv(el, { rows: [rowCfg] }, true);
		return;
	}

	const tween = buildRowTween(el, rowCfg, false, false);
	el[ROWS_KEY] = [{ config: rowCfg, tween, dispose: null }];
	if (tween) el[IMGADV_PLAYED] = tween;
}

export function bindImgAdv(el, mapConfig, forcePreview = false) {
	const rows = mapConfig && mapConfig.rows ? mapConfig.rows : [];
	killAllRows(el);

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

		const rebuildEachPlay = mode === 'slide-change';

		const play = () => {
			if (rebuildEachPlay) {
				if (entry.tween) { try { entry.tween.kill?.(); } catch (_) {} }
				const live = buildRowTween(el, config, false, false);
				entry.tween = live;
				if (live) el[IMGADV_PLAYED] = live;
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
				if (live) el[IMGADV_PLAYED] = live;
			}
		};

		const dispose = wireTrigger({
			el,
			mode,
			triggerEl: resolveTriggerEl(mode, el, config),
			markers: config.markers,
			play,
			buildScrubbed: () => {
				const t = buildRowTween(el, config, false, true);
				entry.tween = t;
				if (t) el[IMGADV_PLAYED] = t;
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
	name: 'image-advanced-animation',
	mapName: IMGADV_MAP,
	boundFlag: 'aae-imgadv-anim-bound',
	playedKey: IMGADV_PLAYED,
	read: readImgAdv,
	play: playImgAdv,
	playRow: playImgAdvRow,
	bind: bindImgAdv,
	unbind: resetImgAdv,
	reset: resetImgAdv,
});
