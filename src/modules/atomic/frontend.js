import { PRESETS, RESET_TO } from './presets';

const PLAYED_KEY = '__aaeAnimPlayed';
const BOUND_CLASS = 'aae-anim-bound';
const TEXT_BOUND_CLASS = 'aae-text-anim-bound';
const PLAYED_CLASS = 'aae-anim-played';

function getGsap() {
	return typeof window !== 'undefined' ? window.gsap : null;
}

function getScrollTrigger() {
	return typeof window !== 'undefined' ? window.ScrollTrigger : null;
}

function readConfig(el) {
	const effect = el.dataset.aaeAnim;
	if (!effect || 'none' === effect || !PRESETS[effect]) {
		return null;
	}

	return {
		effect,
		trigger:  el.dataset.aaeTrigger || 'in-view',
		duration: (parseFloat(el.dataset.aaeDuration) || 600) / 1000,
		delay:    (parseFloat(el.dataset.aaeDelay)    || 0)   / 1000,
		easing:   el.dataset.aaeEasing || 'power2.out',
		repeat:   parseInt(el.dataset.aaeRepeat, 10) || 0,
	};
}

/* =====================================================================
 * Text animation runtime
 * =================================================================== */

const TEXT_PLAYED_KEY = '__aaeTextPlayed';

/**
 * Snake-case breakpoint key → camelCase suffix for dataset access.
 * 'tablet'       → 'Tablet'
 * 'mobile_extra' → 'MobileExtra'
 */
function bpToSuffix(bp) {
	return bp
		.split('_')
		.map((p) => p.charAt(0).toUpperCase() + p.slice(1))
		.join('');
}

/**
 * Returns the active breakpoint key for the current viewport.
 * Prefers Elementor's own resolver so we honour all configured breakpoints
 * (widescreen, laptop, tablet_extra, mobile_extra, …). Falls back to a
 * simple width-based check when the frontend helper isn't loaded.
 */
function currentBreakpoint() {
	const ef = window.elementorFrontend;
	if (typeof ef?.getCurrentDeviceMode === 'function') {
		try {
			const mode = ef.getCurrentDeviceMode();
			if (mode) return mode;
		} catch (_) { /* fall through */ }
	}

	// Fallback: minimal mobile/tablet/desktop heuristic.
	const bp = ef?.config?.responsive?.breakpoints
		|| ef?.config?.breakpoints
		|| {};
	const tabletMax = bp.lg?.value || bp.tablet?.value || bp.lg || bp.tablet || 1024;
	const mobileMax = bp.md?.value || bp.mobile?.value || bp.md || bp.mobile || 768;
	const w = window.innerWidth;
	if (w <= mobileMax) return 'mobile';
	if (w <= tabletMax) return 'tablet';
	return 'desktop';
}

/**
 * Picks an aaeText* dataset value for the active breakpoint, walking up the
 * cascade until a non-empty value is found. The cascade order matches
 * Elementor's device-mode hierarchy:
 *   mobile_extra → mobile → tablet → tablet_extra → laptop → desktop → widescreen
 * (smallest to largest), so an undefined mobile value inherits from tablet, etc.
 *
 * `baseKey` is the camelCase desktop dataset key, e.g. 'aaeTextDelay'.
 */
const BP_CASCADE = {
	mobile:       [ 'mobile', 'tablet' ],
	mobile_extra: [ 'mobile_extra', 'mobile', 'tablet' ],
	tablet:       [ 'tablet' ],
	tablet_extra: [ 'tablet_extra', 'tablet' ],
	laptop:       [ 'laptop' ],
	desktop:      [],
	widescreen:   [ 'widescreen' ],
};

function pickResponsive(el, baseKey) {
	const bp = currentBreakpoint();
	const chain = BP_CASCADE[bp] || [];

	for (const step of chain) {
		const v = el.dataset[baseKey + bpToSuffix(step)];
		if (v !== undefined && v !== '') return v;
	}
	return el.dataset[baseKey];
}

function readTextConfig(el) {
	// `aaeTextAnim` is itself responsive — pick the active-breakpoint value.
	const effect = pickResponsive(el, 'aaeTextAnim') || 'none';
	if (!effect || effect === 'none') {
		return null;
	}
	return {
		effect,
		trigger:         pickResponsive(el, 'aaeTextTrigger')         || 'on_scroll',
		triggerSelector: pickResponsive(el, 'aaeTextTriggerSelector') || '',
		wrapper:         pickResponsive(el, 'aaeTextWrapper')         || 'default',
		wrapperSelector: pickResponsive(el, 'aaeTextWrapperSelector') || '',
		delay:           parseFloat(pickResponsive(el, 'aaeTextDelay'))      || 0,
		duration:        parseFloat(pickResponsive(el, 'aaeTextDuration'))   || 1,
		stagger:         parseFloat(pickResponsive(el, 'aaeTextStagger'))    || 0.02,
		translateX:      parseFloat(pickResponsive(el, 'aaeTextTranslateX')) || 0,
		translateY:      parseFloat(pickResponsive(el, 'aaeTextTranslateY')) || 0,
		rotationDir:     pickResponsive(el, 'aaeTextRotationDir')     || 'x',
		rotation:        parseFloat(pickResponsive(el, 'aaeTextRotation'))   || 0,
		transformOrigin: pickResponsive(el, 'aaeTextTransformOrigin') || 'top center -50',
	};
}

/**
 * Replace the element's text with per-piece <span>s. Uses textContent so
 * repeated calls yield the same flat-text result (idempotent across re-runs).
 * Spaces stay as text nodes to preserve word breaks; newlines become <br>.
 */
function splitTextInto(el, mode) {
	const text = el.textContent || '';
	el.innerHTML = '';

	const fragment = document.createDocumentFragment();

	if (mode === 'word') {
		const tokens = text.split(/(\s+)/);
		for (const token of tokens) {
			if (/^\s+$/.test(token)) {
				fragment.appendChild(document.createTextNode(token));
			} else if (token.length > 0) {
				const span = document.createElement('span');
				span.textContent = token;
				span.className = 'aae-text-piece';
				span.style.display = 'inline-block';
				fragment.appendChild(span);
			}
		}
	} else {
		for (const ch of text) {
			if (ch === '\n') {
				//fragment.appendChild(document.createElement('br'));
			} else if (ch === ' ' || ch === '\t') {
				//fragment.appendChild(document.createTextNode(ch));
			} else {
				const span = document.createElement('span');
				span.textContent = ch;
				span.className = 'aae-text-piece';
				span.style.display = 'inline-block';
				fragment.appendChild(span);
			}
		}
	}

	el.appendChild(fragment);
	return el.querySelectorAll('span.aae-text-piece');
}

function playText(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	if (el[TEXT_PLAYED_KEY]) {
		el[TEXT_PLAYED_KEY].kill();
	}

	const splitMode = config.effect === 'word' ? 'word' : 'char';
	const pieces = splitTextInto(el, splitMode);
	if (!pieces.length) return;

	let from;
	let to = { opacity: 1 };

	switch (config.effect) {
		case 'char':
		case 'word':
			from = { x: config.translateX, y: config.translateY, opacity: 0 };
			to = { x: 0, y: 0, opacity: 1 };
			break;

		case 'text_move':
			from = {
				opacity: 0,
				[config.rotationDir === 'y' ? 'rotationY' : 'rotationX']: config.rotation,
				transformOrigin: config.transformOrigin,
			};
			to = { opacity: 1, rotationX: 0, rotationY: 0, transformOrigin: config.transformOrigin };
			break;

		case 'text_reveal':
			pieces.forEach((p) => {
				p.style.overflow = 'hidden';
				p.style.verticalAlign = 'bottom';
			});
			from = { yPercent: 100, opacity: 0 };
			to = { yPercent: 0, opacity: 1 };
			break;

		case 'text_scale':
			from = { scale: 0, opacity: 0, transformOrigin: 'center center' };
			to = { scale: 1, opacity: 1, transformOrigin: 'center center' };
			break;

		case 'text_invert':
			from = { rotationX: 180, opacity: 0, transformOrigin: 'center center' };
			to = { rotationX: 0, opacity: 1, transformOrigin: 'center center' };
			break;

		case 'text_spin':
			from = { rotationY: 180, opacity: 0, transformOrigin: 'center center' };
			to = { rotationY: 0, opacity: 1, transformOrigin: 'center center' };
			break;

		default:
			return;
	}

	el[TEXT_PLAYED_KEY] = gsap.fromTo(pieces, from, {
		...to,
		duration: config.duration,
		delay: config.delay,
		stagger: config.stagger,
		ease: 'power2.out',
	});
}

function play(el, config) {
	const gsap = getGsap();
	if (!gsap) {
		return;
	}

	if (el[PLAYED_KEY]) {
		el[PLAYED_KEY].kill();
	}

	const preset = PRESETS[config.effect];

	el[PLAYED_KEY] = gsap.fromTo(el, preset.from, {
		...RESET_TO,
		duration: config.duration,
		delay: config.delay,
		ease: config.easing,
		repeat: config.repeat,
		yoyo: config.repeat !== 0,
		clearProps: 'all',
	});

	el.classList.add(PLAYED_CLASS);
}

function bind(el) {
	const gsap = getGsap();
	if (!gsap) {
		return;
	}

	const config = readConfig(el);
	if (!config) {
		return;
	}

	if ('page-load' === config.trigger) {
		play(el, config);
		return;
	}

	const ScrollTrigger = getScrollTrigger();

	if ('scroll-progress' === config.trigger && ScrollTrigger) {
		const preset = PRESETS[config.effect];
		gsap.fromTo(el, preset.from, {
			...RESET_TO,
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start: 'top bottom',
				end: 'bottom top',
				scrub: true,
			},
		});
		return;
	}

	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) {
				return;
			}
			play(entry.target, config);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.15 });

	observer.observe(el);
}

/**
 * Auto-trigger for text animations. Mirrors V3 AAE's trigger semantics:
 *   - on_page_load   → fire immediately
 *   - on_scroll      → IntersectionObserver, fire on enter (once)
 *   - play_with_scroll → ScrollTrigger with scrub (drives timeline by scroll)
 *   - mouseover/click → bound to the element itself for now (selector
 *                       support can come later)
 *
 * `Enable On Editor` only matters in the editor (handled by the live-edit
 * bridge). On the published frontend, trigger always wins.
 */
function bindText(el) {
	const config = readTextConfig(el);
	if (!config) return;

	const trigger = config.trigger;

	if (trigger === 'on_page_load') {
		playText(el, config);
		return;
	}

	if (trigger === 'mouseover') {
		const handler = () => playText(el, config);
		el.addEventListener('mouseenter', handler);
		return;
	}

	if (trigger === 'click') {
		const handler = () => playText(el, config);
		el.addEventListener('click', handler);
		return;
	}

	const ScrollTrigger = getScrollTrigger();

	if (trigger === 'play_with_scroll' && ScrollTrigger) {
		// scrub-driven; binding logic handled inside playText would need a
		// timeline rather than fromTo — first cut: play once when in view.
		ScrollTrigger.create({
			trigger: el,
			start: 'top bottom',
			end: 'bottom top',
			onEnter: () => playText(el, config),
			onEnterBack: () => playText(el, config),
		});
		return;
	}

	// Default: on_scroll (intersection observer, fire once on enter)
	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			playText(entry.target, config);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.15 });

	observer.observe(el);
}

export function scan(root) {
	const scope = root && root.querySelectorAll ? root : document;

	// Text animation FIRST — text-anim elements may also carry data-aae-anim
	// (because Render.php mirrors the effect to both keys), and we want the
	// text runtime to win. Uses its own bound class so the regular-anim scan
	// below doesn't double-bind the same element.
	scope.querySelectorAll(`[data-aae-text-anim]:not(.${TEXT_BOUND_CLASS})`).forEach((el) => {
		el.classList.add(TEXT_BOUND_CLASS);
		bindText(el);
	});

	// Regular animation — skip if text-anim already bound this element.
	scope.querySelectorAll(`[data-aae-anim]:not(.${BOUND_CLASS}):not(.${TEXT_BOUND_CLASS})`).forEach((el) => {
		el.classList.add(BOUND_CLASS);
		bind(el);
	});
}

export function rebind(el) {
	if (!el) {
		return;
	}
	el.classList.remove(BOUND_CLASS, TEXT_BOUND_CLASS, PLAYED_CLASS);
	if (el[PLAYED_KEY]) {
		el[PLAYED_KEY].kill();
		delete el[PLAYED_KEY];
	}
	if (el.dataset.aaeTextAnim) {
		bindText(el);
	} else {
		bind(el);
	}
}

/**
 * Force-replay the animation on an element regardless of trigger setting.
 * Used by the editor "Play Now" button.
 *
 * Order: text-animation (data-aae-text-anim) → regular animation (data-aae-anim).
 * If neither is set on `el`, recurse into descendants.
 */
export function replay(el) {
	if (!el) {
		return;
	}

	// 1) Text animation
	const textConfig = readTextConfig(el);
	if (textConfig) {
		playText(el, textConfig);
		return;
	}

	// 2) Regular animation
	const config = readConfig(el);
	if (config) {
		if (el[PLAYED_KEY]) {
			el[PLAYED_KEY].kill();
			delete el[PLAYED_KEY];
		}
		el.classList.remove(PLAYED_CLASS);
		play(el, config);
		return;
	}

	// 3) Nothing on this element — try descendants of either flavor.
	el.querySelectorAll('[data-aae-anim], [data-aae-text-anim]').forEach(replay);
}



function init() {

	const gsap = getGsap();
	if (!gsap) {
		console.warn('[AAE frontend] init: window.gsap is undefined, exiting before exposing API.');
		return;
	}

	const ScrollTrigger = getScrollTrigger();
	if (ScrollTrigger && gsap.registerPlugin) {
		gsap.registerPlugin(ScrollTrigger);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', () => scan());
	} else {
		scan();
	}

	window.addEventListener('elementor/element/render', (event) => {
		const el = event.detail && event.detail.element;

		if (el && el.matches && el.matches('[data-aae-anim]')) {
			el.classList.add(BOUND_CLASS);
			rebind(el);
		}
		scan(el);
	});

	window.addEventListener('elementor/frontend/init', () => {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/global', ($scope) => {
				scan($scope && $scope[0]);
			});
		}
	});

	window.aaeAtomicAnimations = { scan, rebind, replay };
}

init();
