/* eslint-env browser */

function getValue(settings, bind) {

	const item = settings?.[bind];

	if (!item) {
		return item;
	}

	// Atomic responsive object
	if (
		item.value &&
		typeof item.value === 'object'
	) {
		return item.value.desktop;
	}

	// Atomic plain object
	if ('value' in item) {
		return item.value;
	}

	return item;
}

export function isStickyEnabled(settings) {

	return !!getValue(
		settings,
		'aae_sticky_enable'
	);
}

export function pinTrigger(settings) {

	return getValue(
		settings,
		'aae_sticky_pin_trigger'
	);
}

export function pinEndTrigger(settings) {

	return getValue(
		settings,
		'aae_sticky_pin_end_trigger'
	);
}

export function showPinTrigger(settings) {

	return isStickyEnabled(settings);
}

export function showCustomPinArea(settings) {

	return (
		isStickyEnabled(settings) &&
		pinTrigger(settings) === 'custom'
	);
}

export function showPinEndTrigger(settings) {

	return isStickyEnabled(settings);
}

export function showCustomPinEndArea(settings) {

	return (
		isStickyEnabled(settings) &&
		pinEndTrigger(settings) === 'custom'
	);
}