/* eslint-env browser */

import { PRESETS, RESET_TO } from '../presets';

/**
 * Shared helpers come from window.AAEADDON (set up by common.js). NEVER
 * `import` from '../common' — that would inline ~1.5 KB of helper code into
 * every effect bundle. With 4-5 effects on a page that's 6+ KB of duplication.
 *
 * Effect bundles are enqueued AFTER common.js by Assets.php (dep chain), so
 * window.AAEADDON is always present by the time this module's top-level runs.
 */
const { getGsap, getScrollTrigger, pickResponsive, numOr } = window.AAEADDON;

/**
 * Animation Effect Bundle — text + regular
 *
 * Self-registers with the core runtime (window.AAEADDON) when this file
 * loads. Server-side Render.php enqueues this bundle only on pages that
 * actually use a text or regular animation; otherwise the bytes never ship.
 *
 * This file owns two kinds:
 *   - `regular` → data-aae-anim       (PRESETS-based fade/move/custom)
 *   - `text`    → data-aae-text-anim  (char/word/text_move/reveal/scale/invert/spin)
 *
 * Order in the registry matters: text registers first so a widget carrying
 * both data-attrs binds as text (Render.php mirrors the effect for legacy CSS).
 */

/* =====================================================================
 * Kind: Regular animation  (data-aae-anim)
 * =================================================================== */

const REGULAR_PLAYED = '__aaeAnimPlayed';
const REGULAR_PLAYED_CLASS = 'aae-anim-played';

function readRegular(el) {
	const effect = el.dataset.aaeAnim;
	if (!effect || effect === 'none' || !PRESETS[effect]) return null;
	return {
		effect,
		trigger:  el.dataset.aaeTrigger || 'in-view',
		duration: numOr(el.dataset.aaeDuration, 600) / 1000,
		delay:    numOr(el.dataset.aaeDelay,    0)   / 1000,
		easing:   el.dataset.aaeEasing || 'power2.out',
		repeat:   numOr(el.dataset.aaeRepeat,   0),
	};
}

function playRegular(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	if (el[REGULAR_PLAYED]) {
		el[REGULAR_PLAYED].kill();
	}

	const preset = PRESETS[config.effect];
	el[REGULAR_PLAYED] = gsap.fromTo(el, preset.from, {
		...RESET_TO,
		duration:   config.duration,
		delay:      config.delay,
		ease:       config.easing,
		repeat:     config.repeat,
		yoyo:       config.repeat !== 0,
		clearProps: 'all',
	});
	el.classList.add(REGULAR_PLAYED_CLASS);
}

function bindRegular(el, config) {
	if (config.trigger === 'page-load') {
		playRegular(el, config);
		return;
	}

	const ScrollTrigger = getScrollTrigger();
	if (config.trigger === 'scroll-progress' && ScrollTrigger) {
		const preset = PRESETS[config.effect];
		getGsap().fromTo(el, preset.from, {
			...RESET_TO,
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start: 'top bottom',
				end:   'bottom top',
				scrub: true,
			},
		});
		return;
	}

	// Default: in-view — IntersectionObserver, play once on enter.
	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			playRegular(entry.target, config);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.15 });

	observer.observe(el);
}

/* =====================================================================
 * Kind: Text animation  (data-aae-text-anim)
 * =================================================================== */

const TEXT_PLAYED = '__aaeTextPlayed';

function readText(el) {
	const effect = pickResponsive(el, 'aaeTextAnim') || 'none';
	if (!effect || effect === 'none') return null;
	return {
		effect,
		trigger:         pickResponsive(el, 'aaeTextTrigger')         || 'on_scroll',
		triggerSelector: pickResponsive(el, 'aaeTextTriggerSelector') || '',
		wrapper:         pickResponsive(el, 'aaeTextWrapper')         || 'default',
		wrapperSelector: pickResponsive(el, 'aaeTextWrapperSelector') || '',
		// Defaults match Schema::RESPONSIVE_NUMBER_SETTINGS — Render.php omits
		// attrs whose value equals the default, so these fallbacks restore them.
		delay:           numOr(pickResponsive(el, 'aaeTextDelay'),      0.15),
		duration:        numOr(pickResponsive(el, 'aaeTextDuration'),   1),
		stagger:         numOr(pickResponsive(el, 'aaeTextStagger'),    0.02),
		translateX:      numOr(pickResponsive(el, 'aaeTextTranslateX'), 20),
		translateY:      numOr(pickResponsive(el, 'aaeTextTranslateY'), 0),
		rotationDir:     pickResponsive(el, 'aaeTextRotationDir')     || 'x',
		rotation:        numOr(pickResponsive(el, 'aaeTextRotation'),  -80),
		transformOrigin: pickResponsive(el, 'aaeTextTransformOrigin') || 'top center -50',
	};
}

/**
 * Replace the element's text with per-piece <span>s. Idempotent: re-runs
 * read from textContent so they don't compound the previous split. Word
 * mode preserves whitespace; char mode skips whitespace (matches V3).
 */
function splitTextInto(el, mode) {
	const text = el.textContent || '';
	el.innerHTML = '';
	const fragment = document.createDocumentFragment();

	if (mode === 'word') {
		for (const token of text.split(/(\s+)/)) {
			if (/^\s+$/.test(token)) {
				fragment.appendChild(document.createTextNode(token));
			} else if (token.length > 0) {
				fragment.appendChild(makePiece(token));
			}
		}
	} else {
		for (const ch of text) {
			if (ch === '\n' || ch === ' ' || ch === '\t') continue;
			fragment.appendChild(makePiece(ch));
		}
	}

	el.appendChild(fragment);
	return el.querySelectorAll('span.aae-text-piece');
}

function makePiece(content) {
	const span = document.createElement('span');
	span.textContent = content;
	span.className = 'aae-text-piece';
	span.style.display = 'inline-block';
	return span;
}

/** Build `from` / `to` tween targets for each text effect. */
function textTween(effect, config, pieces) {
	switch (effect) {
		case 'char':
		case 'word':
			return {
				from: { x: config.translateX, y: config.translateY, opacity: 0 },
				to:   { x: 0, y: 0, opacity: 1 },
			};

		case 'text_move':
			return {
				from: {
					opacity: 0,
					[config.rotationDir === 'y' ? 'rotationY' : 'rotationX']: config.rotation,
					transformOrigin: config.transformOrigin,
				},
				to: {
					opacity: 1,
					rotationX: 0,
					rotationY: 0,
					transformOrigin: config.transformOrigin,
				},
			};

		case 'text_reveal':
			pieces.forEach((p) => {
				p.style.overflow = 'hidden';
				p.style.verticalAlign = 'bottom';
			});
			return {
				from: { yPercent: 100, opacity: 0 },
				to:   { yPercent: 0,   opacity: 1 },
			};

		case 'text_scale':
			return {
				from: { scale: 0, opacity: 0, transformOrigin: 'center center' },
				to:   { scale: 1, opacity: 1, transformOrigin: 'center center' },
			};

		case 'text_invert':
			return {
				from: { rotationX: 180, opacity: 0, transformOrigin: 'center center' },
				to:   { rotationX: 0,   opacity: 1, transformOrigin: 'center center' },
			};

		case 'text_spin':
			return {
				from: { rotationY: 180, opacity: 0, transformOrigin: 'center center' },
				to:   { rotationY: 0,   opacity: 1, transformOrigin: 'center center' },
			};

		default:
			return null;
	}
}

function playText(el, config) {
	const gsap = getGsap();
	if (!gsap) return;

	if (el[TEXT_PLAYED]) {
		el[TEXT_PLAYED].kill();
	}

	const splitMode = config.effect === 'word' ? 'word' : 'char';
	const pieces = splitTextInto(el, splitMode);
	if (!pieces.length) return;

	const tween = textTween(config.effect, config, pieces);
	if (!tween) return;

	el[TEXT_PLAYED] = gsap.fromTo(pieces, tween.from, {
		...tween.to,
		duration: config.duration,
		delay:    config.delay,
		stagger:  config.stagger,
		ease:     'power2.out',
	});
}

function bindText(el, config) {
	const trigger = config.trigger;

	if (trigger === 'on_page_load') {
		playText(el, config);
		return;
	}

	if (trigger === 'mouseover') {
		el.addEventListener('mouseenter', () => playText(el, config));
		return;
	}

	if (trigger === 'click') {
		el.addEventListener('click', () => playText(el, config));
		return;
	}

	const ScrollTrigger = getScrollTrigger();
	if (trigger === 'play_with_scroll' && ScrollTrigger) {
		ScrollTrigger.create({
			trigger: el,
			start: 'top bottom',
			end:   'bottom top',
			onEnter:     () => playText(el, config),
			onEnterBack: () => playText(el, config),
		});
		return;
	}

	// Default: on_scroll
	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			playText(entry.target, config);
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.15 });

	observer.observe(el);
}

/* =====================================================================
 * Self-register with the core runtime
 *
 * register() is idempotent (deduped by name), so even if Render.php
 * accidentally enqueues this bundle twice nothing breaks. Order matters:
 * text registers first so co-rendered elements bind to text precedence.
 * =================================================================== */

window.AAEADDON.register({
	name:      'text',
	selector:  '[data-aae-text-anim]',
	boundFlag: 'aae-text-anim-bound',
	playedKey: TEXT_PLAYED,
	read:      readText,
	play:      playText,
	bind:      bindText,
});

window.AAEADDON.register({
	name:      'regular',
	selector:  '[data-aae-anim]',
	boundFlag: 'aae-anim-bound',
	playedKey: REGULAR_PLAYED,
	read:      readRegular,
	play:      playRegular,
	bind:      bindRegular,
});
