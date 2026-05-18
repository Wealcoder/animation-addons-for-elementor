/* eslint-env browser */

const {
	configFor,
} = window.AAEADDON;

export const STICKY_MAP = 'AAE_INTERACTIONS_STICKY';

/* ==========================================================================
   Helpers
   ========================================================================== */

function responsiveValue(value, fallback = null) {

	if (!value) {
		return fallback;
	}

	/*
	|--------------------------------------------------------------------------
	| Responsive Atomic
	|--------------------------------------------------------------------------
	|
	| {
	|   $$type: 'aae-rj',
	|   value: {
	|     desktop: 'custom'
	|   }
	| }
	|
	*/

	if (
		value.value &&
		typeof value.value === 'object'
	) {
		return value.value.desktop;
	}

	/*
	|--------------------------------------------------------------------------
	| Plain Atomic
	|--------------------------------------------------------------------------
	*/

	if ('value' in value) {
		return value.value;
	}

	return value;
}

/* ==========================================================================
   Read Config
   ========================================================================== */

function readSticky(el) {

	const cfg = configFor(el, STICKY_MAP);

	if (!cfg || !cfg.enabled) {
		return null;
	}

	return {

		enabled: true,

		/*
		|--------------------------------------------------------------------------
		| Trigger
		|--------------------------------------------------------------------------
		*/

		pinTrigger:
			responsiveValue(
				cfg.pinTrigger,
				'default'
			),

		customPinArea:
			responsiveValue(
				cfg.customPinArea,
				''
			),

		/*
		|--------------------------------------------------------------------------
		| End Trigger
		|--------------------------------------------------------------------------
		*/

		pinEndTrigger:
			responsiveValue(
				cfg.pinEndTrigger,
				'default'
			),

		customPinEndArea:
			responsiveValue(
				cfg.customPinEndArea,
				''
			),

		/*
		|--------------------------------------------------------------------------
		| Pin
		|--------------------------------------------------------------------------
		*/

		pin:
			responsiveValue(
				cfg.pin,
				true
			),

		customPin:
			responsiveValue(
				cfg.customPin,
				''
			),

		/*
		|--------------------------------------------------------------------------
		| Pin Start
		|--------------------------------------------------------------------------
		*/

		pinStart:
			responsiveValue(
				cfg.pinStart,
				'top top'
			),

		customPinStart:
			responsiveValue(
				cfg.customPinStart,
				''
			),

		/*
		|--------------------------------------------------------------------------
		| Pin End
		|--------------------------------------------------------------------------
		*/

		pinEnd:
			responsiveValue(
				cfg.pinEnd,
				'bottom bottom'
			),

		customPinEnd:
			responsiveValue(
				cfg.customPinEnd,
				''
			),

		/*
		|--------------------------------------------------------------------------
		| Pin Spacing
		|--------------------------------------------------------------------------
		*/

		pinSpacing:
			responsiveValue(
				cfg.pinSpacing,
				true
			),

		/*
		|--------------------------------------------------------------------------
		| Pin Markers
		|--------------------------------------------------------------------------
		*/

		pinMarkers:
			cfg.pinMarkers ?? false,
	};
}

/* ==========================================================================
   Bind
   ========================================================================== */

function bindSticky(el, config) {

	if (!config?.enabled) {
		return;
	}

	console.log('Sticky Bind', {

		element: el,

		config,
	});

	/*
	|--------------------------------------------------------------------------
	| TODO:
	|--------------------------------------------------------------------------
	|
	| ScrollTrigger.create({
	|
	|   trigger,
	|   start,
	|   end,
	|   pin,
	|   pinSpacing,
	|   markers,
	|
	| })
	|
	*/
}

/* ==========================================================================
   Cleanup
   ========================================================================== */

function cleanupSticky(el) {

	void el;
}

function resetSticky(el) {

	cleanupSticky(el);
}

/* ==========================================================================
   Register
   ========================================================================== */

window.AAEADDON.register({

	name:      'sticky',

	mapName:   STICKY_MAP,

	boundFlag: 'aae-sticky-bound',

	read:      readSticky,

	bind:      bindSticky,

	unbind:    cleanupSticky,

	reset:     resetSticky,
});