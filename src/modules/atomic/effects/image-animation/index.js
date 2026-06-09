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

	let wrap, outer;
	let createdWrap = false;

	if (image === el) {
		if (
			image.parentElement &&
			image.parentElement.classList.contains('aae-img-reveal-wrap')
		) {
			wrap = image.parentElement;
		} else {
			wrap = document.createElement('div');
			wrap.className = 'aae-img-reveal-wrap';

			image.parentNode.insertBefore(wrap, image);
			wrap.appendChild(image);

			createdWrap = true;
		}

		outer = wrap;
	} else {
		wrap = image.parentElement;

		if (wrap && !el.contains(wrap)) {
			wrap = el;
		}

		outer = wrap === el ? el : wrap.parentElement;

		if (outer && !el.contains(outer)) {
			outer = el;
		}
	}

	outer.style.overflow = 'hidden';
	wrap.style.overflow = 'hidden';

	const tl = gsap.timeline({
		scrollTrigger: {
			trigger: wrap,
			start: resolveStart(config),
			toggleActions: 'play none none none',
		},
	});

	const contentAnim = {
		duration: 1.5,
		ease: config.ease,
	};

	const imageAnim = {
		duration: 1.5,
		scale: 1.3,
		delay: -1.5,
		ease: config.ease,
	};

	switch (config.startFrom) {
		case 'left':
			contentAnim.xPercent = 100;
			imageAnim.xPercent = -100;
			break;

		case 'right':
			contentAnim.xPercent = -100;
			imageAnim.xPercent = 100;
			break;

		case 'top':
			contentAnim.yPercent = 100;
			imageAnim.yPercent = -100;
			break;

		default:
			contentAnim.yPercent = -100;
			imageAnim.yPercent = 100;
	}

	tl.set([wrap, image], { autoAlpha: 1 });

	tl.from(wrap, contentAnim);
	tl.from(image, imageAnim);

	el[IMG_PLAYED] = tl;

	el[IMG_DISPOSE_KEY] = () => {
		tl.scrollTrigger?.kill();
		tl.kill();

		gsap.set([wrap, image], { clearProps: 'all' });

		if (createdWrap && wrap.parentNode) {
			wrap.parentNode.insertBefore(image, wrap);
			wrap.remove();
		}
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

	const tween = gsap.fromTo(
		image,
		{
			scale: config.scaleStart,
		},
		{
			scale: config.scaleEnd,
			ease: 'none',
			paused: true,
		}
	);

	const trigger = image.parentElement || el;

	const st = ScrollTrigger.create({
		trigger,
		start: resolveStart(config),
		scrub: true,
		animation: tween,
		invalidateOnRefresh: true,
		// markers: true,
	});

	// Force ScrollTrigger to recalculate after setup
	requestAnimationFrame(() => {
		ScrollTrigger.refresh();
	});

	if (image.parentElement) {
		image.parentElement.style.overflow = 'hidden';
	}

	el[IMG_PLAYED] = tween;

	el[IMG_DISPOSE_KEY] = () => {
		st.kill(true);
		tween.kill();
	};
}

/* =====================================================================
 * stretch — pinned scrub that grows width to 100% + flattens border radius
 * =================================================================== */

function bindStretch(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	const image = findMedia(el);
	let wrap;
	let createdWrap = false;

	if (image === el) {
		if (image.parentElement && image.parentElement.classList.contains('aae-img-stretch-wrap')) {
			wrap = image.parentElement;
		} else {
			wrap = document.createElement('div');
			wrap.className = 'aae-img-stretch-wrap';
			wrap.style.width = '100%';
			wrap.style.display = 'flex';
			wrap.style.justifyContent = 'center';
			wrap.style.alignItems = 'flex-start';
			image.parentNode.insertBefore(wrap, image);
			wrap.appendChild(image);
			createdWrap = true;
		}
	} else {
		wrap = image.parentElement;
		if (wrap && !el.contains(wrap)) wrap = el;
	}

	if (wrap) {
		wrap.style.paddingBottom = '395px';
		wrap.style.transition    = 'none';
	}

	const tl = gsap.timeline({
		scrollTrigger: {
			trigger: wrap,
			start: 'top top',
			pin: true,
			scrub: 1,
			pinSpacing: false,
			end: 'bottom bottom+=100',
			invalidateOnRefresh: true,
		},
	});

	tl.to(image, {
		width: '100%',
		borderRadius: '0px',
		ease: 'none',
	});

	el[IMG_PLAYED] = tl;
	el[IMG_DISPOSE_KEY] = () => { 
		tl.scrollTrigger?.kill(true); 
		tl.kill(); 
		
		gsap.set(image, { clearProps: 'all' });
		if (wrap) gsap.set(wrap, { clearProps: 'all' });

		if (createdWrap && wrap.parentNode) {
			wrap.parentNode.insertBefore(image, wrap);
			wrap.parentNode.removeChild(wrap);
		}
	};

	// Suppress unused-var lint
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
