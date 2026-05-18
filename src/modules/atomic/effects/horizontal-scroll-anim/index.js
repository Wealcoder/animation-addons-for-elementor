
const {
	configFor,
} = window.AAEADDON;

/*
|--------------------------------------------------------------------------
| MUST MATCH
|--------------------------------------------------------------------------
|
| PHP:
| InteractionsMap::register()
|
*/

const MAP =
	'AAE_INTERACTIONS_HORIZONTAL_SCROLL_ANIM';

function read(el) {

	const cfg =
		configFor(el, MAP);

	if (!cfg || !cfg.enabled) {
		return null;
	}

	return cfg;
}

function bind(el, config) {

	/*
	|--------------------------------------------------------------------------
	| REAL GSAP LOGIC HERE
	|--------------------------------------------------------------------------
	*/

	console.log(
		config.intensity
	);
}

function unbind(el) {
	void el;
}

window.AAEADDON.register({

	/*
	|--------------------------------------------------------------------------
	| CHANGE THIS
	|--------------------------------------------------------------------------
	*/

	name: 'horizontal-scroll-anim',

	mapName: MAP,

	boundFlag:
		'aae-horizontal-scroll-anim-bound',

	read,

	bind,

	unbind,

	reset: unbind,
});