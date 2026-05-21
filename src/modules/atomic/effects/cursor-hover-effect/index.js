const {
	configFor,
	pickConfigResponsive,
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

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

function read(el) {

	const cfg =	configFor(el, MAP);

	if (!cfg) {
		return null;
	}
	
	const isEnabled = r(cfg, 'enabled', false);
	if (!isEnabled) {
		return null;
	}

	return {
		enabled: true,
		text: r(cfg, 'text', ''),
		color: r(cfg, 'color', '#ffffff'),
		background: r(cfg, 'background', '#000000'),
		width: r(cfg, 'width', ''),
		height: r(cfg, 'height', ''),
		border: r(cfg, 'border', '1px solid #ffffff'),
		borderRadius: r(cfg, 'borderRadius', null),
	};
}

function bind(el, config) {

	/*
	|--------------------------------------------------------------------------
	| REAL GSAP LOGIC HERE
	|--------------------------------------------------------------------------
	*/

	console.log(		
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