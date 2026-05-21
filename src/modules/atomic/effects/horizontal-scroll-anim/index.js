
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

const MAP = 'AAE_INTERACTIONS_HORIZONTAL';

function read(el) {

	const cfg =
		configFor(el, MAP);

	if (!cfg || !cfg.enabled) {
		return null;
	}

	console.log(cfg);
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
	name: 'horizontal',
	mapName: MAP,
	boundFlag: 'aae-horizontal-bound',
	read,
	bind,
	unbind,
	reset: unbind,
});