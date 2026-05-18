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
	| {
	|   $$type: 'aae-rj',
	|   value: {
	|     desktop: true
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

export function isEnabled(settings) {

	return !!getValue(
		settings,
		'aae_horizontal_scroll_anim_enable'
	);
}

export function showCustomWidth(settings) {

	return (
		isEnabled(settings) &&
		getValue(
			settings,
			'aae_horizontal_scroll_anim_width'
		) === 'custom'
	);
}

export function showCustomEnd(settings) {

	return (
		isEnabled(settings) &&
		getValue(
			settings,
			'aae_horizontal_scroll_anim_end'
		) === 'custom'
	);
}
