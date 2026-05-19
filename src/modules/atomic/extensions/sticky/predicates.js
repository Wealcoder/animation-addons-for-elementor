import { valueAt, valueEq } from '../../responsive-section/helpers';

/* ==========================================================================
   Base
   ========================================================================== */

export function isStickyEnabled(settings, bp) {
	return !!valueAt(settings, 'aae_sticky_enable', bp);
}

/* ==========================================================================
   Pin Trigger
   ========================================================================== */

export function pinTrigger(settings, bp) {
	return valueAt(settings, 'aae_sticky_pin_trigger', bp);
}

export function showPinTrigger(settings, bp) {
	return isStickyEnabled(settings, bp);
}

export function showCustomPinArea(settings, bp) {
	return (
		isStickyEnabled(settings, bp) &&
		pinTrigger(settings, bp) === 'custom'
	);
}

/* ==========================================================================
   Pin End Trigger
   ========================================================================== */

export function pinEndTrigger(settings, bp) {
	return valueAt(settings, 'aae_sticky_pin_end_trigger', bp);
}

export function showPinEndTrigger(settings, bp) {
	return isStickyEnabled(settings, bp);
}

export function showCustomPinEndArea(settings, bp) {
	return (
		isStickyEnabled(settings, bp) &&
		pinEndTrigger(settings, bp) === 'custom'
	);
}

/* ==========================================================================
   Pin
   ========================================================================== */

export function pin(settings, bp) {
	return valueAt(settings, 'aae_sticky_pin', bp);
}

export function showPinFields(settings, bp) {
	return isStickyEnabled(settings, bp);
}

export function showCustomPin(settings, bp) {
	return (
		isStickyEnabled(settings, bp) &&
		pin(settings, bp) === 'custom'
	);
}

/* ==========================================================================
   Pin Start
   ========================================================================== */

export function pinStart(settings, bp) {
	return valueAt(settings, 'aae_sticky_pin_start', bp);
}

export function showCustomPinStart(settings, bp) {
	return (
		isStickyEnabled(settings, bp) &&
		pinStart(settings, bp) === 'custom'
	);
}

/* ==========================================================================
   Pin End
   ========================================================================== */

export function pinEnd(settings, bp) {
	return valueAt(settings, 'aae_sticky_pin_end', bp);
}

export function showCustomPinEnd(settings, bp) {
	return (
		isStickyEnabled(settings, bp) &&
		pinEnd(settings, bp) === 'custom'
	);
}

/* ==========================================================================
   Editor & Play
   ========================================================================== */

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

export function showEnableEditor(settings, bp) {
	return isStickyEnabled(settings, bp);
}

export function showPlayButton(settings, bp) {
	return (
		isStickyEnabled(settings, bp) &&
		plainBool(settings, 'aae_sticky_enable_editor')
	);
}