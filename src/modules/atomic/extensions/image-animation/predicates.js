/* eslint-env browser */

import { valueAt, valueEq, valueIn } from '../../responsive-section/helpers';

/**
 * Predicate helpers for ImageAnimation field visibility. Each takes
 * (settings, activeBp) and resolves the responsive prop's value at the
 * active breakpoint (with cascade) before comparing.
 */

const ANIMATED_EFFECTS    = ['reveal', 'scale', 'stretch'];
const REVEAL_OR_SCALE     = ['reveal', 'scale'];

export function startPosAt(s, bp)   { return valueAt(s, 'aae_img_start_pos', bp); }

export function isAnimated(s, bp)   { return valueIn(s, 'aae_img_effect', bp, ANIMATED_EFFECTS); }
export function isReveal(s, bp)     { return valueEq(s, 'aae_img_effect', bp, 'reveal'); }
export function isScale(s, bp)      { return valueEq(s, 'aae_img_effect', bp, 'scale'); }

/** True for the fields shared by reveal + scale (start position picker). */
export function isRevealOrScale(s, bp) {
	return valueIn(s, 'aae_img_effect', bp, REVEAL_OR_SCALE);
}

/** Custom start text — visible only when start position is set to 'custom'. */
export function showCustomStart(s, bp) {
	return isRevealOrScale(s, bp) && startPosAt(s, bp) === 'custom';
}

/* ----- non-responsive rows ----- */

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

/** Enable On Editor visible whenever an effect is selected. */
export function showEnableEditor(s, bp) {
	return isAnimated(s, bp);
}

/** Play Now visible only when an effect is selected AND Enable On Editor is on. */
export function showPlayButton(s, bp) {
	return isAnimated(s, bp) && plainBool(s, 'aae_img_enable_editor');
}
