/* eslint-env browser */

import { wireTrigger, modeFor, resolveTriggerEl } from './triggers';

/**
 * Text animation kind — char/word/move/reveal/scale/invert/spin.
 *
 * Reads its config from `window.AAE_INTERACTIONS_TEXT[el.dataset.aaeTextId]`.
 * Responsive values are flat per-bp keys on the config (e.g. `duration`,
 * `duration_tablet`, `duration_mobile`) resolved via `pickConfigResponsive`.
 *
 * Helpers come from window.AAEADDON. See note in regular.js.
 */
const { getGsap, configFor, pickConfigResponsive } = window.AAEADDON;

export const TEXT_MAP = 'AAE_INTERACTIONS_TEXT';

/** Read a config field with responsive cascade + a JS-side default. */
function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

export const TEXT_PLAYED = '__aaeTextPlayed';

export function readText(el) {
	const cfg = configFor(el, TEXT_MAP);
	if (!cfg) return null;
	// effect is responsive — user can disable the animation on a specific
	// breakpoint by setting effect to 'none' (or unsetting it) in that bp's
	// row. The runtime treats that as "no animation for this device".
	const effect = pickConfigResponsive(cfg, 'effect');
	if (!effect || effect === 'none') return null;
	return {
		effect,
		trigger:         r(cfg, 'trigger', 'on_scroll'),
		triggerSelector: r(cfg, 'triggerSelector', ''),
		wrapper:         r(cfg, 'wrapper', 'default'),
		wrapperSelector: r(cfg, 'wrapperSelector', ''),
		// Non-responsive: cfg.markers is a top-level boolean.
		markers:         !!cfg.markers,
		// Defaults mirror Schema::RESPONSIVE_NUMBER_SETTINGS — Render.php
		// omits keys equal to the default, so these fallbacks restore them.
		delay:           Number(r(cfg, 'delay',      0.15)),
		duration:        Number(r(cfg, 'duration',   1)),
		stagger:         Number(r(cfg, 'stagger',    0.02)),
		translateX:      Number(r(cfg, 'translateX', 20)),
		translateY:      Number(r(cfg, 'translateY', 0)),
		rotationDir:     r(cfg, 'rotationDir', 'x'),
		rotation:        Number(r(cfg, 'rotation', -80)),
		transformOrigin: r(cfg, 'transformOrigin', 'top center -50'),
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

/**
 * Restore the element to its pre-animation state — kill the tween and
 * un-split the text pieces back into a single text node. Used when the
 * editor's `Enable On Editor` toggle flips OFF so the canvas reflects
 * "this widget won't animate" instead of getting stuck mid-tween.
 */
export function resetText(el) {
	if (el[TEXT_PLAYED]) {
		el[TEXT_PLAYED].kill();
		delete el[TEXT_PLAYED];
	}
	// Flatten <span class="aae-text-piece"> back to plain text. textContent
	// getter joins all descendant text, the setter replaces children with a
	// single text node — un-splits in one step.
	if (el.querySelector('span.aae-text-piece')) {
		el.textContent = el.textContent;
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

/** Build a PAUSED text tween used by `play_with_scroll` — ScrollTrigger
 *  advances its progress to match scroll position (forward on wheel-down,
 *  reverse on wheel-up). Returns null if GSAP isn't loaded or the effect
 *  has no tween descriptor. */
function buildScrubbedText(el, config) {
	const gsap = getGsap();
	if (!gsap) return null;

	if (el[TEXT_PLAYED]) {
		el[TEXT_PLAYED].kill();
	}

	const splitMode = config.effect === 'word' ? 'word' : 'char';
	const pieces = splitTextInto(el, splitMode);
	if (!pieces.length) return null;

	const tween = textTween(config.effect, config, pieces);
	if (!tween) return null;

	el[TEXT_PLAYED] = gsap.fromTo(pieces, tween.from, {
		...tween.to,
		duration: config.duration,
		delay:    config.delay,
		stagger:  config.stagger,
		ease:     'none',   // scrub ignores easing — keep linear progress
		paused:   true,
	});
	return el[TEXT_PLAYED];
}

export function bindText(el, config) {
	const mode = modeFor(config.trigger);
	wireTrigger({
		el,
		mode,
		triggerEl:     resolveTriggerEl(mode, config.triggerSelector),
		markers:       config.markers,
		play:          () => playText(el, config),
		buildScrubbed: () => buildScrubbedText(el, config),
	});
}
