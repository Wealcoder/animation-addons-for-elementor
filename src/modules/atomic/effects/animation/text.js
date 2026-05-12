/* eslint-env browser */

import { wireTrigger } from './triggers';

/**
 * Text animation kind — char/word/move/reveal/scale/invert/spin.
 *   data-aae-text-anim → effect name
 *   data-aae-text-*    → per-effect config (responsive via pickResponsive)
 *
 * Helpers come from window.AAEADDON. See note in regular.js.
 */
const { getGsap, pickResponsive, numOr } = window.AAEADDON;

export const TEXT_PLAYED = '__aaeTextPlayed';

export function readText(el) {
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

export function playText(el, config) {
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

/** Map text-anim's trigger vocabulary to the shared dispatcher's modes. */
function modeFor(trigger) {
	if (trigger === 'on_page_load')     return 'page-load';
	if (trigger === 'mouseover')         return 'hover';
	if (trigger === 'click')             return 'click';
	if (trigger === 'play_with_scroll')  return 'scroll-tied';
	return 'in-view'; // 'on_scroll' and anything unrecognised
}

export function bindText(el, config) {
	wireTrigger({
		el,
		mode: modeFor(config.trigger),
		play: () => playText(el, config),
	});
}
