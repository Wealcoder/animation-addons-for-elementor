// /* eslint-env browser */

// function getValue(settings, bind) {

// 	const item = settings?.[bind];

// 	if (!item) {
// 		return item;
// 	}

// 	// Atomic responsive object
// 	if (
// 		item.value &&
// 		typeof item.value === 'object'
// 	) {
// 		return item.value.desktop;
// 	}

// 	// Atomic plain object
// 	if ('value' in item) {
// 		return item.value;
// 	}

// 	return item;
// }

// export function isStickyEnabled(settings) {

// 	return !!getValue(
// 		settings,
// 		'aae_sticky_enable'
// 	);
// }

// export function pinTrigger(settings) {

// 	return getValue(
// 		settings,
// 		'aae_sticky_pin_trigger'
// 	);
// }

// export function pinEndTrigger(settings) {

// 	return getValue(
// 		settings,
// 		'aae_sticky_pin_end_trigger'
// 	);
// }

// export function showPinTrigger(settings) {

// 	return isStickyEnabled(settings);
// }

// export function showCustomPinArea(settings) {

// 	return (
// 		isStickyEnabled(settings) &&
// 		pinTrigger(settings) === 'custom'
// 	);
// }

// export function showPinEndTrigger(settings) {

// 	return isStickyEnabled(settings);
// }

// export function showCustomPinEndArea(settings) {

// 	return (
// 		isStickyEnabled(settings) &&
// 		pinEndTrigger(settings) === 'custom'
// 	);
// }

/* eslint-env browser */

function getValue(settings, bind) {

	const item = settings?.[bind];

	if (!item) {
		return item;
	}

	/*
	|--------------------------------------------------------------------------
	| Responsive Atomic Value
	|--------------------------------------------------------------------------
	|
	| Example:
	| {
	|   $$type: 'aae-rj',
	|   value: {
	|     desktop: 'custom'
	|   }
	| }
	|
	*/

	if (
		item.value &&
		typeof item.value === 'object'
	) {
		return item.value.desktop;
	}

	/*
	|--------------------------------------------------------------------------
	| Plain Atomic Value
	|--------------------------------------------------------------------------
	|
	| Example:
	| {
	|   $$type: 'boolean',
	|   value: true
	| }
	|
	*/

	if ('value' in item) {
		return item.value;
	}

	return item;
}

/* ==========================================================================
   Base
   ========================================================================== */

export function isStickyEnabled(settings) {

	return !!getValue(
		settings,
		'aae_sticky_enable'
	);
}

/* ==========================================================================
   Pin Trigger
   ========================================================================== */

export function pinTrigger(settings) {

	return getValue(
		settings,
		'aae_sticky_pin_trigger'
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

/* ==========================================================================
   Pin End Trigger
   ========================================================================== */

export function pinEndTrigger(settings) {

	return getValue(
		settings,
		'aae_sticky_pin_end_trigger'
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

/* ==========================================================================
   Pin
   ========================================================================== */

export function pin(settings) {

	return getValue(
		settings,
		'aae_sticky_pin'
	);
}

export function showPinFields(settings) {

	return isStickyEnabled(settings);
}

export function showCustomPin(settings) {

	return (
		isStickyEnabled(settings) &&
		pin(settings) === 'custom'
	);
}

/* ==========================================================================
   Pin Start
   ========================================================================== */

export function pinStart(settings) {

	return getValue(
		settings,
		'aae_sticky_pin_start'
	);
}

export function showCustomPinStart(settings) {

	return (
		isStickyEnabled(settings) &&
		pinStart(settings) === 'custom'
	);
}

/* ==========================================================================
   Pin End
   ========================================================================== */

export function pinEnd(settings) {

	return getValue(
		settings,
		'aae_sticky_pin_end'
	);
}

export function showCustomPinEnd(settings) {

	return (
		isStickyEnabled(settings) &&
		pinEnd(settings) === 'custom'
	);
}