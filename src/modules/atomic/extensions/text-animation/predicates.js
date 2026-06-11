/* eslint-env browser */

import { valueAt, valueEq, valueIn } from '../../responsive-section/helpers';
import { PREMIUM_EFFECTS_BY_ID } from './presets';

/**
 * Predicate helpers for TextAnimation field visibility. Each takes
 * (settings, activeBp) and resolves the responsive prop's value at the
 * active breakpoint (with cascade) before comparing.
 */

const ANIMATED_EFFECTS   = ['char', 'word', 'text_move', 'text_reveal', 'text_scale', 'text_invert'];
// We'll dynamically check for 'premium_' prefix in isAnimated
const DURATION_EFFECTS   = ['char', 'word', 'text_reveal', 'text_move', 'text_scale'];
const TRANSLATE_EFFECTS  = ['char', 'word'];
const SCROLL_TRIGGERS    = ['on_scroll', 'play_with_scroll'];
const SELECTOR_TRIGGERS  = ['mouseover', 'click'];

export function startPositionAt(s, bp)  { return valueAt(s, 'aae_text_start_position', bp); }
export function endPositionAt(s, bp)    { return valueAt(s, 'aae_text_end_position',   bp); }

export function isPremiumEffect(s, bp) {
	const effect = valueAt(s, 'aae_text_effect', bp);
	return effect && !!PREMIUM_EFFECTS_BY_ID[effect];
}

export function isAnimated(s, bp)        { return isPremiumEffect(s, bp) || valueIn(s, 'aae_text_effect',  bp, ANIMATED_EFFECTS); }
export function isDurationEffect(s, bp)  { return isPremiumEffect(s, bp) || valueIn(s, 'aae_text_effect',  bp, DURATION_EFFECTS); }
export function isTranslateEffect(s, bp) { return valueIn(s, 'aae_text_effect',  bp, TRANSLATE_EFFECTS); }
export function isScrollTrigger(s, bp)   { 
	const trigger = valueAt(s, 'aae_text_trigger', bp) || 'in-view';
	return SCROLL_TRIGGERS.includes(trigger); 
}
export function isSelectorTrigger(s, bp) { 
	const trigger = valueAt(s, 'aae_text_trigger', bp) || 'in-view';
	return SELECTOR_TRIGGERS.includes(trigger); 
}

export function isMove(s, bp)    { return valueEq(s, 'aae_text_effect', bp, 'text_move'); }
export function isInvert(s, bp)  { return valueEq(s, 'aae_text_effect', bp, 'text_invert'); }
export function isScale(s, bp)   { return valueEq(s, 'aae_text_effect', bp, 'text_scale'); }

// is transform_origin show, I will provide specific preset here

export function isMoveOrPremium(s, bp) {
	if (isMove(s, bp)) return true;
	const effect = valueAt(s, 'aae_text_effect', bp);
	return effect === 'origami_fold' || effect === 'shutter_cascade';
}

export function showTextShadow(s, bp) {
	return valueEq(s, 'aae_text_effect', bp, 'cyber_phantom');
}

export function isWrapperCustom(s, bp) { return valueEq(s, 'aae_text_wrapper', bp, 'custom'); }

/* ----- composite gates ----- */

export function showTriggerSelector(s, bp) {
	return showTriggerDropdown(s, bp) && isSelectorTrigger(s, bp);
}

export function showWrapper(s, bp) {
	return showTriggerDropdown(s, bp) && isScrollTrigger(s, bp);
}

export function showWrapperSelector(s, bp) {
	return isWrapperCustom(s, bp);
}
export function showTriggerDropdown(s, bp) {
	// Let text_invert use the normal trigger dropdown!
	return isAnimated(s, bp);
}
export function showScrollCustomBlock(s, bp) {
	return showTriggerDropdown(s, bp) && isScrollTrigger(s, bp) && isWrapperCustom(s, bp) && !isInvert(s, bp);
}

export function showScrollPosition(s, bp) {
	return showTriggerDropdown(s, bp) && isScrollTrigger(s, bp) && !isInvert(s, bp);
}

export function showStartCustom(s, bp) {
	return showScrollCustomBlock(s, bp) && startPositionAt(s, bp) === 'custom';
}

export function showEndCustom(s, bp) {
	return showScrollCustomBlock(s, bp) && endPositionAt(s, bp) === 'custom';
}

export function showDelay(s, bp) {
	return isAnimated(s, bp) && !isInvert(s, bp);
}

/* ----- non-responsive rows (read raw envelope.value) ----- */

/** Pull a non-responsive boolean's primitive (or null). */
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
	return isAnimated(s, bp) && plainBool(s, 'aae_text_enable_editor');
}
