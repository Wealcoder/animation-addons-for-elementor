/* eslint-env browser */

/**
 * Animation Effect Bundle — entry point.
 *
 * Owns two kinds:
 *   - `regular` → data-aae-anim       (PRESETS-based fade/move/zoom)
 *   - `text`    → data-aae-text-anim  (char/word/text_move/reveal/scale/invert/spin)
 *
 * Self-registers with the core runtime (window.AAEADDON) at module load.
 * Server-side Render.php enqueues this bundle only on pages that actually
 * use a regular or text animation; otherwise the bytes never ship.
 *
 * Order matters: text registers first so a widget that carries BOTH
 * dispatch attrs binds to text precedence (Render.php still mirrors the
 * regular effect for legacy CSS). register() is idempotent (dedupe by name).
 */

import { readRegular, playRegular, bindRegular, REGULAR_PLAYED } from './regular';
import { readText,    playText,    bindText,    TEXT_PLAYED    } from './text';

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
