

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

const MAP = 'AAE_INTERACTIONS_CURSOR_HOVER_EFFECT';

function r(cfg, key, fallback) {

	const v =
		pickConfigResponsive(
			cfg,
			key
		);

	return (
		v === undefined ||
		v === ''
	)
		? fallback
		: v;
}

function read(el) {

	const cfg = configFor(el, MAP);

	if (!cfg) {
		return null;
	}

	const isEnabled =
		r(
			cfg,
			'enabled',
			false
		);

	if (!isEnabled) {
		return null;
	}

	return {

		enabled: true,

		text:
			r(
				cfg,
				'text',
				''
			),

		color:
			r(
				cfg,
				'color',
				'#ffffff'
			),

		background:
			r(
				cfg,
				'background',
				'#000000'
			),

		width:
			r(
				cfg,
				'width',
				''
			),

		height:
			r(
				cfg,
				'height',
				''
			),

		border:
			r(
				cfg,
				'border',
				'1px solid #ffffff'
			),

		borderRadius:
			r(
				cfg,
				'borderRadius',
				null
			),
	};
}

function bind(el, config) {

	console.log('Cursor Hover Effect',config);

	const widgetId =
		el.dataset.id ||
		el.dataset.elementorId ||
		Math.random()
			.toString(36)
			.slice(2);

	let cursor =
		document.querySelector(
			`.wcf-hover-cursor-effect.active-${widgetId}`
		);

	/*
	|--------------------------------------------------------------------------
	| Create Cursor
	|--------------------------------------------------------------------------
	*/

	if (!cursor) {

		cursor =
			document.createElement('div');

		cursor.className =
			`wcf-hover-cursor-effect active-${widgetId}`;

		document.body.prepend(
			cursor
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Base Style
	|--------------------------------------------------------------------------
	*/

	cursor.style.position =
		'fixed';

	cursor.style.left = 0;

	cursor.style.top = 0;

	cursor.style.pointerEvents =
		'none';

	cursor.style.zIndex =
		99999;

	cursor.style.display =
		'flex';

	cursor.style.alignItems =
		'center';

	cursor.style.justifyContent =
		'center';

	cursor.style.color =
		config.color;

	cursor.style.background =
		config.background;

	cursor.style.border =
		config.border;

	/*
	|--------------------------------------------------------------------------
	| Optional Styles
	|--------------------------------------------------------------------------
	*/

	if (config.width) {
		cursor.style.width =
			config.width;
	}

	if (config.height) {
		cursor.style.height =
			config.height;
	}

	if (config.borderRadius) {
		cursor.style.borderRadius =
			config.borderRadius;
	}

	/*
	|--------------------------------------------------------------------------
	| GSAP Init
	|--------------------------------------------------------------------------
	*/

	gsap.set(cursor, {

		xPercent: -50,

		yPercent: -50,

		scale: 0,

		opacity: 0,
	});

	const setCursorX =
		gsap.quickTo(
			cursor,
			'x',
			{
				duration: 0.6,
				ease: 'expo',
			}
		);

	const setCursorY =
		gsap.quickTo(
			cursor,
			'y',
			{
				duration: 0.6,
				ease: 'expo',
			}
		);

	const tl =
		gsap.timeline({
			paused: true,
		});

	tl.to(cursor, {

		scale: 1,

		opacity: 1,

		duration: 0.5,

		ease: 'expo.inOut',
	});

	/*
	|--------------------------------------------------------------------------
	| Mouse Move
	|--------------------------------------------------------------------------
	*/

	const onMouseMove =
		(e) => {

			setCursorX(
				e.clientX
			);

			setCursorY(
				e.clientY
			);
		};

	document.addEventListener(
		'mousemove',
		onMouseMove
	);

	/*
	|--------------------------------------------------------------------------
	| Mouse Enter
	|--------------------------------------------------------------------------
	*/

	const onMouseEnter =
		() => {

			cursor.innerHTML =
				config.text;

			tl.play();
		};

	/*
	|--------------------------------------------------------------------------
	| Mouse Leave
	|--------------------------------------------------------------------------
	*/

	const onMouseLeave =
		() => {

			tl.reverse();
		};

	el.addEventListener(
		'mouseenter',
		onMouseEnter
	);

	el.addEventListener(
		'mouseleave',
		onMouseLeave
	);

	/*
	|--------------------------------------------------------------------------
	| Store Cleanup Refs
	|--------------------------------------------------------------------------
	*/

	el.__aaeCursorHoverCleanup =
		() => {

			document.removeEventListener(
				'mousemove',
				onMouseMove
			);

			el.removeEventListener(
				'mouseenter',
				onMouseEnter
			);

			el.removeEventListener(
				'mouseleave',
				onMouseLeave
			);

			cursor.remove();
		};
}

function unbind(el) {

	if (
		el.__aaeCursorHoverCleanup
	) {

		el.__aaeCursorHoverCleanup();

		delete el.__aaeCursorHoverCleanup;
	}
}

window.AAEADDON.register({

	name: 'cursor-hover-effect',

	mapName: MAP,

	boundFlag: 'aae-cursor-hover-effect-bound',

	read,

	bind,

	unbind,

	reset: unbind,
});