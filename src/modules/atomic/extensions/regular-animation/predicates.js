/* eslint-env browser */

import { valueAt, valueEq, valueIn } from '../../responsive-section/helpers';

/**
 * Predicate helpers for RegularAnimation field visibility. Each takes
 * (settings, activeBp) and resolves the responsive prop's value at the
 * active breakpoint (with cascade) before comparing. Names match the
 * conceptual gates in the v3 / pre-section Schema, so a reader who knows
 * the old code can map directly to the new declarative table.
 */

const ANIMATED_EFFECTS  = ['fade', 'move', 'custom'];
const DURATION_EFFECTS  = ['fade', 'move'];
const EASE_EFFECTS      = ['fade', 'move', 'custom'];
const SCROLL_TRIGGERS   = ['on_scroll', 'play_with_scroll'];
const SELECTOR_TRIGGERS = ['mouseover', 'click'];
const DIRECTIONAL_FADES = ['top', 'bottom', 'left', 'right'];

export function fadeFromAt(s, bp)      { return valueAt(s, 'aae_anim_fade_from',      bp); }
export function startPositionAt(s, bp) { return valueAt(s, 'aae_anim_start_position', bp); }
export function endPositionAt(s, bp)   { return valueAt(s, 'aae_anim_end_position',   bp); }

export function isAnimated(s, bp)        { return valueIn(s, 'aae_anim_effect',  bp, ANIMATED_EFFECTS); }
export function isDurationEffect(s, bp)  { return valueIn(s, 'aae_anim_effect',  bp, DURATION_EFFECTS); }
export function isEaseEffect(s, bp)      { return valueIn(s, 'aae_anim_effect',  bp, EASE_EFFECTS); }
export function isScrollTrigger(s, bp)   { return valueIn(s, 'aae_anim_trigger', bp, SCROLL_TRIGGERS); }
export function isSelectorTrigger(s, bp) { return valueIn(s, 'aae_anim_trigger', bp, SELECTOR_TRIGGERS); }

export function isFade(s, bp)   { return valueEq(s, 'aae_anim_effect', bp, 'fade'); }
export function isMove(s, bp)   { return valueEq(s, 'aae_anim_effect', bp, 'move'); }
export function isCustom(s, bp) { return valueEq(s, 'aae_anim_effect', bp, 'custom'); }

/* (legacy duplicate avoided — `isCustom` declared once above) */

export function isWrapperCustom(s, bp) { return valueEq(s, 'aae_anim_wrapper', bp, 'custom'); }

/* ----- composite gates that several rows share ----- */

export function showScrollCustomBlock(s, bp) {
	return isAnimated(s, bp) && isScrollTrigger(s, bp) && isWrapperCustom(s, bp);
}

export function showStartCustom(s, bp) {
	return showScrollCustomBlock(s, bp)
		&& startPositionAt(s, bp) === 'custom';
}

export function showEndCustom(s, bp) {
	return showScrollCustomBlock(s, bp)
		&& endPositionAt(s, bp) === 'custom';
}

export function showFadeOffset(s, bp) {
	return isFade(s, bp) && DIRECTIONAL_FADES.includes(fadeFromAt(s, bp));
}

export function showScale(s, bp) {
	return isFade(s, bp) && fadeFromAt(s, bp) === 'scale';
}

export function showTriggerSelector(s, bp) {
	return isAnimated(s, bp) && isSelectorTrigger(s, bp);
}

export function showWrapper(s, bp) {
	return isAnimated(s, bp) && isScrollTrigger(s, bp);
}

/* ----- non-responsive rows (read raw envelope.value) ----- */

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

/** Enable On Editor visible whenever an animation is selected at desktop level. */
export function showEnableEditor(s, bp) {
	return isAnimated(s, bp);
}

/** Play Now visible only when Enable On Editor is true AND an effect is selected. */
export function showPlayButton(s, bp) {
	return isAnimated(s, bp) && plainBool(s, 'aae_anim_enable_editor');
}
