/* eslint-env browser */

const {
	configFor,
} = window.AAEADDON;

export const STICKY_MAP = 'AAE_INTERACTIONS_STICKY';

function readSticky(el) {

	const cfg = configFor(el, STICKY_MAP);

	if (!cfg || !cfg.enabled) {
		return null;
	}

	return {
		enabled: true,

		pinTrigger:
			cfg.pinTrigger || 'default',

		customPinArea:
			cfg.customPinArea || '',

		pinEndTrigger:
			cfg.pinEndTrigger || 'default',

		customPinEndArea:
			cfg.customPinEndArea || '',
	};
}

function bindSticky(el, config) {

	if (!config?.enabled) {
		return;
	}

	// GSAP ScrollTrigger logic here
	// Example placeholder:

	console.log('Sticky Bind', {
		element: el,
		config,
	});
}

function cleanupSticky(el) {
	void el;
}

function resetSticky(el) {
	cleanupSticky(el);
}

window.AAEADDON.register({
	name:      'sticky',
	mapName:   STICKY_MAP,
	boundFlag: 'aae-sticky-bound',

	read:      readSticky,
	bind:      bindSticky,
	unbind:    cleanupSticky,
	reset:     resetSticky,
});