/* eslint-env browser */

/**
 * Image Animation kind — reveal / scale / stretch for e-image and e-svg.
 *
 * Reads from `window.AAE_INTERACTIONS_IMG[<interactionId>]`. Each effect
 * has its own DOM target + trigger model (hardcoded per effect — there is
 * no user-selectable trigger):
 *
 *   - reveal  → scroll-tied timeline. Tweens the image wrapper and the
 *               inner <img> in opposing directions (parallax-style reveal).
 *   - scale   → scrub. gsap.set(image, scale: scaleStart) → gsap.to(image,
 *               scale: scaleEnd) driven by ScrollTrigger.
 *   - stretch → pinned scrub. Tweens width to 100% + borderRadius to 0,
 *               pinned from top top → bottom bottom+=100.
 *
 * Helpers come from window.AAEADDON — same convention as the other effect
 * bundles. NEVER import from '../../common'.
 */
const { getGsap, getScrollTrigger, configFor, pickConfigResponsive } = window.AAEADDON;

export const IMG_MAP    = 'AAE_INTERACTIONS_IMG';
export const IMG_PLAYED = '__aaeImgPlayed';
const IMG_DISPOSE_KEY   = '__aaeImgDispose';

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

export function readImg(el) {
	const cfg = configFor(el, IMG_MAP);
	if (!cfg) return null;
	const effect = pickConfigResponsive(cfg, 'effect');
	if (!effect || effect === 'none') return null;
	return {
		effect,
		startFrom:   r(cfg, 'startFrom',   'right'),
		ease:        r(cfg, 'ease',        'power2.out'),
		scaleStart:  Number(r(cfg, 'scaleStart', 0.5)),
		scaleEnd:    Number(r(cfg, 'scaleEnd',   1)),
		startPos:    r(cfg, 'startPos',    'top center'),
		customStart: r(cfg, 'customStart', 'top 90%'),
	};
}

/** Resolve the trigger's `start` value — falls back to startPos unless 'custom'. */
function resolveStart(config) {
	return config.startPos === 'custom' ? config.customStart : config.startPos;
}

/** Find the inner <img> the effect should tween. e-image renders <img> inside
 *  a figure/wrapper; e-svg uses inline <svg>. Fall back to the element itself. */
function findMedia(el) {
	return el.querySelector('img, svg') || el;
}

/* =====================================================================
 * reveal — opposing tween on wrap + inner image (parallax reveal)
 * =================================================================== */

function bindReveal(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	const image = findMedia(el);
	const wrap  = image.parentElement || el;
	const outer = wrap.parentElement || wrap;

	// v3 forces overflow hidden on the outer + wrap, and hides wrap until
	// the timeline runs (autoAlpha:1 in the first .set step).
	outer.style.overflow    = 'hidden';
	wrap.style.overflow     = 'hidden';
	wrap.style.display      = 'block';
	wrap.style.visibility   = 'hidden';
	wrap.style.transition   = 'none';

	const tl = gsap.timeline({
		scrollTrigger: {
			trigger: el,
			start:   resolveStart(config),
			toggleActions: 'play none none none',
		},
	});

	const contentAnim = { duration: 1.5, ease: config.ease };
	const imageAnim   = { duration: 1.5, scale: 1.3, delay: -1.5, ease: config.ease };

	switch (config.startFrom) {
		case 'left':   contentAnim.xPercent =  100; imageAnim.xPercent = -100; break;
		case 'right':  contentAnim.xPercent = -100; imageAnim.xPercent =  100; break;
		case 'top':    contentAnim.yPercent =  100; imageAnim.yPercent = -100; break;
		case 'bottom':
		default:       contentAnim.yPercent = -100; imageAnim.yPercent =  100; break;
	}

	tl.set(wrap,  { autoAlpha: 1 });
	tl.from(wrap,  contentAnim);
	tl.from(image, imageAnim);

	el[IMG_PLAYED] = tl;
	el[IMG_DISPOSE_KEY] = () => {
		tl.scrollTrigger?.kill();
		tl.kill();
	};
}

/* =====================================================================
 * scale — scrub-driven scale on inner image
 * =================================================================== */

function bindScale(el, config) {
	const gsap = getGsap();
	const ScrollTrigger = getScrollTrigger();
	if (!gsap || !ScrollTrigger) return;

	const image = findMedia(el);

	gsap.set(image, { scale: config.scaleStart });

	const tween = gsap.to(image, {
		scale: config.scaleEnd,
		ease:  'none',
		paused: true,
	});

	const st = ScrollTrigger.create({
		trigger:   el,
		start:     resolveStart(config),
		scrub:     true,
		animation: tween,
		invalidateOnRefresh: true,
	});

	const parent = image.parentElement;
	if (parent) parent.style.overflow = 'hidden';

	el[IMG_PLAYED] = tween;
	el[IMG_DISPOSE_KEY] = () => { st.kill(); tween.kill(); };
}

/* =====================================================================
 * stretch — pinned scrub that grows width to 100% + flattens border radius
 * =================================================================== */

function bindStretch(el, config) {
	const gsap = getGsap();
	const ScrollTrigger = getScrollTrigger();
	if (!gsap || !ScrollTrigger) return;

	const image = findMedia(el);
	const wrap  = image.parentElement || el;

	// v3 sets a fixed 395px padding-bottom on the wrap for the pin spacing.
	// Reproduced here so the published page matches; users who don't want
	// it can override via custom CSS on the widget.
	wrap.style.paddingBottom = '395px';
	wrap.style.transition    = 'none';

	const tween = gsap.to(image, {
		width: '100%',
		borderRadius: '0px',
		ease: 'none',
		paused: true,
	});

	const st = ScrollTrigger.create({
		trigger:   wrap,
		start:     'top top',
		end:       'bottom bottom+=100',
		pin:       true,
		pinSpacing: false,
		scrub:     1,
		animation: tween,
		invalidateOnRefresh: true,
	});

	el[IMG_PLAYED] = tween;
	el[IMG_DISPOSE_KEY] = () => { st.kill(); tween.kill(); };

	// Suppress unused-var lint — config kept for symmetry with the other
	// effects in case future fields apply here.
	void config;
}

/* =====================================================================
 * Kind interface — read / play / bind / reset / unbind
 * =================================================================== */

/**
 * playImg manually re-runs the chosen effect. For scrub effects this just
 * rebinds the ScrollTrigger (replaying a scrub-paused tween doesn't make
 * visual sense — the position is what advances it). For reveal it kills
 * and re-creates the timeline.
 */
export function playImg(el, config) {
	cleanupImg(el);
	bindImg(el, config);
}

export function bindImg(el, config) {
	if (config.effect === 'reveal')  return bindReveal(el, config);
	if (config.effect === 'scale')   return bindScale(el, config);
	if (config.effect === 'stretch') return bindStretch(el, config);
}

function cleanupImg(el) {
	const dispose = el[IMG_DISPOSE_KEY];
	if (typeof dispose === 'function') {
		try { dispose(); } catch (_) { /* ignore */ }
	}
	el[IMG_DISPOSE_KEY] = null;
	if (el[IMG_PLAYED]) {
		try { el[IMG_PLAYED].kill?.(); } catch (_) { /* ignore */ }
		delete el[IMG_PLAYED];
	}
}

export function resetImg(el) {
	cleanupImg(el);
}

window.AAEADDON.register({
	name:       'image-animation',
	mapName:    IMG_MAP,
	boundFlag:  'aae-img-anim-bound',
	playedKey:  IMG_PLAYED,
	read:       readImg,
	play:       playImg,
	bind:       bindImg,
	unbind:     cleanupImg,
	reset:      resetImg,
});
