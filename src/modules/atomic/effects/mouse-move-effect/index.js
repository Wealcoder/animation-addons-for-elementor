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
	'AAE_INTERACTIONS_MOUSE_MOVE_EFFECT';

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
		'Mouse Move Effect',
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

	name: 'mouse-move-effect',

	mapName: MAP,

	boundFlag:
		'aae-mouse-move-effect-bound',

	read,

	bind,

	unbind,

	reset: unbind,
});