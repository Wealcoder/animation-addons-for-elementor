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

const MAP =	'AAE_INTERACTIONS_MOUSE_MOVE_EFFECT';

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

function read(el) {
	const cfg = configFor(el, MAP);
	console.log(cfg);
	if (!cfg) {
		return null;
	}
	const isEnabled = r(cfg, 'enable', false);
	if (!isEnabled) {
		return null;
	}

	return {
		enabled: true,
		movement_wrapper: r(cfg, 'movement_wrapper', 'default'),
		move_x: r(cfg, 'move_x', '100'),
		move_y: r(cfg, 'move_y', '100'),
		duration: r(cfg, 'duration', '1'),
		customs: r(cfg, 'customs', ''),
		customProps: Array.isArray(pickConfigResponsive(cfg, 'customProps'))
			? pickConfigResponsive(cfg, 'customProps')
			: [],
	};
}

function bind(el, config) {
	console.log(		
		config
	);
}

function unbind(el) {
	void el;
}

window.AAEADDON.register({
	name: 'mouse-move-effect',
	mapName: MAP,
	boundFlag: 'aae-mouse-move-effect-bound',
	read,
	bind,
	unbind,
	reset: unbind
});