/* eslint-env browser */

const {
	configFor,
	pickConfigResponsive
} = window.AAEADDON;

export const STICKY_MAP = 'AAE_INTERACTIONS_STICKY';

/* ==========================================================================
   Helpers
   ========================================================================== */

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

/* ==========================================================================
   Read Config
   ========================================================================== */

function readSticky(el) {

	const cfg = configFor(el, STICKY_MAP);

	if (!cfg) {
		return null;
	}

	const isEnabled = r(cfg, 'enable', false);

	if (!isEnabled) {
		return null;
	}

	return {

		enabled: true,

		/*
		|--------------------------------------------------------------------------
		| Trigger
		|--------------------------------------------------------------------------
		*/

		pinTrigger:       r(cfg, 'pinTrigger', 'default'),
		customPinArea:    r(cfg, 'customPinArea', ''),

		/*
		|--------------------------------------------------------------------------
		| End Trigger
		|--------------------------------------------------------------------------
		*/

		pinEndTrigger:    r(cfg, 'pinEndTrigger', 'default'),
		customPinEndArea: r(cfg, 'customPinEndArea', ''),

		/*
		|--------------------------------------------------------------------------
		| Pin
		|--------------------------------------------------------------------------
		*/

		pin:              r(cfg, 'pin', true),
		customPin:        r(cfg, 'customPin', ''),

		/*
		|--------------------------------------------------------------------------
		| Pin Start
		|--------------------------------------------------------------------------
		*/

		pinStart:         r(cfg, 'pinStart', 'top top'),
		customPinStart:   r(cfg, 'customPinStart', ''),

		/*
		|--------------------------------------------------------------------------
		| Pin End
		|--------------------------------------------------------------------------
		*/

		pinEnd:           r(cfg, 'pinEnd', 'bottom bottom'),
		customPinEnd:     r(cfg, 'customPinEnd', ''),

		/*
		|--------------------------------------------------------------------------
		| Pin Spacing
		|--------------------------------------------------------------------------
		*/

		pinSpacing:       r(cfg, 'pinSpacing', true),

		/*
		|--------------------------------------------------------------------------
		| Pin Markers
		|--------------------------------------------------------------------------
		*/

		pinMarkers:       r(cfg, 'pinMarkers', false),

		/*
		|--------------------------------------------------------------------------
		| Style (Border & Custom CSS)
		|--------------------------------------------------------------------------
		*/

		border:           r(cfg, 'border', null),
		customCSS:        r(cfg, 'customCSS', ''),
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

export function playSticky(el, config) {
	cleanupSticky(el);
	bindSticky(el, config);
}

/* ==========================================================================
   Register
   ========================================================================== */

window.AAEADDON.register({

	name:      'sticky',

	mapName:   STICKY_MAP,

	boundFlag: 'aae-sticky-bound',

	read:      readSticky,

	play:      playSticky,

	bind:      bindSticky,

	unbind:    cleanupSticky,

	reset:     resetSticky,
});