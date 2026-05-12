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

import { readRegular, playRegular, bindRegular, resetRegular, REGULAR_PLAYED, ANIM_MAP } from './regular';
import { readText,    playText,    bindText,    resetText,    TEXT_PLAYED,    TEXT_MAP  } from './text';
import { cleanupTriggerOn } from './triggers';

// Every kind dispatches on Elementor's universal `data-interaction-id`
// (no custom DOM attrs from us). Per-kind `mapName` is the disambiguator:
// `text` reads window.AAE_INTERACTIONS_TEXT[id], `regular` reads
// window.AAE_INTERACTIONS_ANIM[id]. An element can have entries in both
// maps and gets bound by both kinds independently.
window.AAEADDON.register({
	name:       'text',
	mapName:    TEXT_MAP,
	boundFlag:  'aae-text-anim-bound',
	playedKey:  TEXT_PLAYED,
	read:       readText,
	play:       playText,
	bind:       bindText,
	unbind:     cleanupTriggerOn,
	reset:      resetText,
});

window.AAEADDON.register({
	name:       'regular',
	mapName:    ANIM_MAP,
	boundFlag:  'aae-anim-bound',
	playedKey:  REGULAR_PLAYED,
	read:       readRegular,
	play:       playRegular,
	bind:       bindRegular,
	unbind:     cleanupTriggerOn,
	reset:      resetRegular,
});
