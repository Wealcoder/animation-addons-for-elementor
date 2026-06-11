/* eslint-env browser */

import { valueAt, valueEq, valueIn } from '../../responsive-section/helpers';

/**
 * Predicate helpers for RegularAnimation field visibility. Each takes
 * (settings, activeBp) and resolves the responsive prop's value at the
 * active breakpoint (with cascade) before comparing. Names match the
 * conceptual gates in the v3 / pre-section Schema, so a reader who knows
 * the old code can map directly to the new declarative table.
 */


const SCROLL_TRIGGERS = ['on_scroll', 'play_with_scroll'];
const SELECTOR_TRIGGERS = ['mouseover', 'click'];

export function isAnimated(s, bp) { 
	const effect = valueAt(s, 'aae_anim_effect', bp);
	return !!effect && effect !== 'none'; 
}
export function isDurationEffect(s, bp) { return isAnimated(s, bp); }
export function isEaseEffect(s, bp) { return isAnimated(s, bp); }
export function isScrollTrigger(s, bp) { 
	const trigger = valueAt(s, 'aae_anim_trigger', bp) || 'on_scroll';
	return SCROLL_TRIGGERS.includes(trigger); 
}
export function isSelectorTrigger(s, bp) { 
	const trigger = valueAt(s, 'aae_anim_trigger', bp) || 'on_scroll';
	return SELECTOR_TRIGGERS.includes(trigger); 
}

export function isWrapperCustom(s, bp) { return valueEq(s, 'aae_anim_wrapper', bp, 'custom'); }

/* ----- composite gates that several rows share ----- */

export function showScrollCustomBlock(s, bp) {
	return isAnimated(s, bp) && isScrollTrigger(s, bp) && isWrapperCustom(s, bp);
}


export function showTriggerSelector(s, bp) {
	return isAnimated(s, bp) && isSelectorTrigger(s, bp);
}

export function showWrapper(s, bp) {
	return isAnimated(s, bp) && isScrollTrigger(s, bp);
}

export function showMarkers(s, bp) {
	return isAnimated(s, bp) && isScrollTrigger(s, bp);
}

export function showScrollPosition(s, bp) {
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
