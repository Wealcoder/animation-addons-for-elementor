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
	'AAE_INTERACTIONS_CURSOR_HOVER_EFFECT';

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
		'Cursor Hover Effect',
		el,
		config
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

	name: 'cursor-hover-effect',

	mapName: MAP,

	boundFlag:
		'aae-cursor-hover-effect-bound',

	read,

	bind,

	unbind,

	reset: unbind,
});