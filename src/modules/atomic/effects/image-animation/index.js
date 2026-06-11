/* eslint-env browser */

const {
	getGsap,
	getScrollTrigger,
	configFor,
	pickConfigResponsive
} = window.AAEADDON;

export const IMG_MAP = 'AAE_INTERACTIONS_IMG';
export const IMG_PLAYED = '__aaeImgPlayed';
const IMG_DISPOSE_KEY = '__aaeImgDispose';

/* =========================
 * CONFIG
 * ========================= */

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
		startFrom: r(cfg, 'startFrom', 'right'),
		ease: r(cfg, 'ease', 'power2.out'),
		scaleStart: Number(r(cfg, 'scaleStart', 0.5)),
		scaleEnd: Number(r(cfg, 'scaleEnd', 1)),
		startPos: r(cfg, 'startPos', 'top center'),
		customStart: r(cfg, 'customStart', 'top 10%'),
		endPos: r(cfg, 'endPos', 'bottom bottom+=10'),
		enableMarker: !!pickConfigResponsive(cfg, 'enableMarker'),
	};
}

/* =========================
 * HELPERS
 * ========================= */

function resolveStart(config) {
	return config.startPos === 'custom'
		? config.customStart
		: config.startPos;
}

function resolveEnd(config) {
	if (!config.endPos) return undefined;
	return config.endPos === 'custom'
		? config.customEnd
		: config.endPos;
}

function findMedia(el) {
	return el.querySelector('img, svg') || el;
}

function cleanupImg(el) {
	const gsap = getGsap();
	const dispose = el[IMG_DISPOSE_KEY];

	if (typeof dispose === 'function') {
		try { dispose(); } catch (_) {}
	}

	el[IMG_DISPOSE_KEY] = null;

	if (el[IMG_PLAYED]) {
		try { el[IMG_PLAYED].kill?.(); } catch (_) {}
		delete el[IMG_PLAYED];
	}

	if (gsap) {
		try {
			gsap.killTweensOf(el);
			gsap.killTweensOf(findMedia(el));
		} catch (_) {}
	}
}

/* =========================
 * REVEAL
 * ========================= */

function bindReveal(el, config, preview = false) {
	const gsap = getGsap();
	if (!gsap) return;

	const image = findMedia(el);
	const wrap = image.closest('.aae-img-reveal-wrap') || el;

	// IMPORTANT: prevent stuck hidden state during switching
	gsap.killTweensOf([wrap, image]);
	gsap.set([wrap, image], { clearProps: 'all' });

	const contentAnim = {
		duration: 1.5,
		ease: config.ease,
	};

	const imageAnim = {
		duration: 1.5,
		scale: 1.3,
		ease: config.ease,
	};

	switch (config.startFrom) {
		case 'left':
			contentAnim.clipPath = 'inset(0 0 0 100%)';
			break;
		case 'right':
			contentAnim.clipPath = 'inset(0 100% 0 0)';
			break;
		case 'top':
			contentAnim.clipPath = 'inset(100% 0 0 0)';
			break;
		default:
			contentAnim.clipPath = 'inset(0 0 100% 0)';
	}

	// ✅ FIX: GSAP controls visibility (no CSS visibility hacks)
	gsap.set(wrap, { autoAlpha: 0 });

	const tl = gsap.timeline();

	if (preview) {
		tl.set(wrap, { autoAlpha: 1 });
		tl.from(wrap, contentAnim);
		tl.from(image, imageAnim);
	} else {
		tl.set(wrap, { autoAlpha: 1 });
		tl.from(wrap, contentAnim, 0);
		tl.from(image, imageAnim, 0);
	}

	el[IMG_PLAYED] = tl;

	el[IMG_DISPOSE_KEY] = () => {
		tl.scrollTrigger?.kill?.();
		tl.kill();

		gsap.set(wrap, {
			clearProps: 'overflow,visibility,display,opacity'
		});

		gsap.set(image, {
			clearProps: 'transform'
		});
	};
}

/* =========================
 * SCALE
 * ========================= */

function bindScale(el, config, preview = false) {
	const gsap = getGsap();
	const ScrollTrigger = getScrollTrigger();
	if (!gsap || !ScrollTrigger) return;

	const image = findMedia(el);

	gsap.killTweensOf(image);
	gsap.set(image, { clearProps: 'transform' });

	if (preview) {
		const tl = gsap.timeline();

		tl.fromTo(
			image,
			{ scale: config.scaleStart },
			{
				scale: config.scaleEnd,
				duration: 1.5,
				ease: 'power2.out'
			}
		);

		el[IMG_PLAYED] = tl;

		el[IMG_DISPOSE_KEY] = () => {
			tl.kill();
			gsap.set(image, { clearProps: 'transform' });
		};

		return tl;
	}

	const tween = gsap.fromTo(
		image,
		{ scale: config.scaleStart },
		{ scale: config.scaleEnd, ease: 'none' }
	);

	const st = ScrollTrigger.create({
		trigger: image.parentElement || el,
		start: resolveStart(config),
		end: resolveEnd(config),
		scrub: true,
		animation: tween,
		invalidateOnRefresh: true,
		markers: config.enableMarker
	});

	el[IMG_PLAYED] = tween;

	el[IMG_DISPOSE_KEY] = () => {
		st.kill();
		tween.kill();
	};
}

/* =========================
 * STRETCH
 * ========================= */

function bindStretch(el, config, preview = false) {
	const gsap = getGsap();
	const ScrollTrigger = getScrollTrigger();
	if (!gsap || !ScrollTrigger) return;

	const image = findMedia(el);
	const wrap = image.parentElement || el;

	gsap.killTweensOf(image);

	wrap.style.paddingBottom = r(config, 'paddingBottom', '395px');
	wrap.style.transition = 'none';

	if (preview) {
		const tl = gsap.timeline();

		tl.to(image, {
			width: '100%',
			borderRadius: '0px',
			duration: 1.5,
			ease: r(config, 'ease', 'power2.out')
		});

		el[IMG_PLAYED] = tl;

		el[IMG_DISPOSE_KEY] = () => {
			tl.kill();
			gsap.set(image, {
				clearProps: 'width,borderRadius'
			});
		};

		return tl;
	}

	const tween = gsap.to(image, {
		width: '100%',
		borderRadius: '0px',
		ease: 'none'
	});

	const st = ScrollTrigger.create({
		trigger: wrap,
		start: 'top top',
		end: 'bottom bottom+=100',
		scrub: 1,
		pin: true,
		pinSpacing: false,
		animation: tween,
		invalidateOnRefresh: true
	});

	el[IMG_PLAYED] = tween;

	el[IMG_DISPOSE_KEY] = () => {
		st.kill();
		tween.kill();

		gsap.set(image, {
			clearProps: 'width,borderRadius'
		});

		gsap.set(wrap, {
			clearProps: 'paddingBottom,transition'
		});
	};
}

/* =========================
 * PLAY ENGINE
 * ========================= */

export function playImg(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	cleanupImg(el);

	switch (config.effect) {
		case 'reveal':
			bindReveal(el, config, true);
			break;

		case 'scale':
			bindScale(el, config, true);
			break;

		case 'stretch':
			bindStretch(el, config, true);
			break;

		default:
			bindImg(el, config);
	}

	el[IMG_DISPOSE_KEY] = () => {
		el[IMG_PLAYED]?.kill?.();

		gsap.set(findMedia(el), {
			clearProps: 'transform,width,borderRadius,opacity,visibility'
		});
	};
}

/* =========================
 * BIND FRONTEND
 * ========================= */

export function bindImg(el, config) {
	if (config.effect === 'reveal') return bindReveal(el, config, false);
	if (config.effect === 'scale') return bindScale(el, config, false);
	if (config.effect === 'stretch') return bindStretch(el, config, false);
}

/* =========================
 * RESET
 * ========================= */

export function resetImg(el) {
	cleanupImg(el);
}

/* =========================
 * REGISTER
 * ========================= */

window.AAEADDON.register({
	name: 'image-animation',
	mapName: IMG_MAP,
	boundFlag: 'aae-img-anim-bound',
	playedKey: IMG_PLAYED,
	read: readImg,
	play: playImg,
	bind: bindImg,
	unbind: cleanupImg,
	reset: resetImg,
});