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

import { readRegular, playRegular, playRegularRow, bindRegular, resetRegular, REGULAR_PLAYED, ANIM_MAP } from './regular';
import { readText, playText, playTextRow, bindText, resetText, TEXT_PLAYED, TEXT_MAP } from './text';

window.AAEADDON.register({
	name: 'text',
	mapName: TEXT_MAP,
	boundFlag: 'aae-text-anim-bound',
	playedKey: TEXT_PLAYED,
	read:       readText,
	play: playText,
	playRow: playTextRow,
	bind: bindText,
	// resetText tears down every row's tween + the shared split (per-row
	// state lives in el.__aaeTextRows, not the single DISPOSE_KEY).
	unbind: resetText,
	reset: resetText,
});

window.AAEADDON.register({
	name: 'regular',
	mapName: ANIM_MAP,
	boundFlag: 'aae-anim-bound',
	playedKey: REGULAR_PLAYED,
	read: readRegular,
	play: playRegular,
	playRow: playRegularRow,
	bind: bindRegular,
	// resetRegular tears down EVERY row's tween + trigger disposer (the
	// repeater stores per-row state in el.__aaeAnimRows, not the single
	// DISPOSE_KEY cleanupTriggerOn reads). So unbind === reset here.
	unbind: resetRegular,
	reset: resetRegular,
});
