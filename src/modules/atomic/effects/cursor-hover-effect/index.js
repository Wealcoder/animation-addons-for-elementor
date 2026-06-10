

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
				'Cursor Hover Effect'
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

		border: r(cfg, 'border', null),		

		fontSize: r(cfg, 'fontSize', ''),

		padding: r(cfg, 'padding', ''),
	};
}

function play(el, config) {
	unbind(el);
	bind(el, config);
	
}

function bind(el, config) {
	unbind(el);
	if (!config) return;

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

	/*
	|--------------------------------------------------------------------------
	| Border
	|--------------------------------------------------------------------------
	*/
	if (config.border && config.border.style && config.border.style !== 'none' && config.border.style !== '') {
		const b = config.border;
		const w = b.width || {};
		const wUnit = 'px';
		const topW = (w.top || 0);
		const rightW = (w.right || 0);
		const bottomW = (w.bottom || 0);
		const leftW = (w.left || 0);

		cursor.style.borderStyle = b.style;
		cursor.style.borderWidth = `${topW}${wUnit} ${rightW}${wUnit} ${bottomW}${wUnit} ${leftW}${wUnit}`;
		cursor.style.borderColor = b.color || '#000000';

		if (b.radius) {
			const rv = b.radius;
			if (typeof rv === 'object' && rv !== null) {
				cursor.style.borderRadius = `${rv.top || 0}px ${rv.right || 0}px ${rv.bottom || 0}px ${rv.left || 0}px`;
			} else if (typeof rv === 'number') {
				cursor.style.borderRadius = `${rv}px`;
			} else if (/^\d+(\.\d+)?$/.test(String(rv))) {
				cursor.style.borderRadius = `${rv}px`;
			} else {
				cursor.style.borderRadius = rv;
			}
		}
	} else {
		cursor.style.border = 'none';
	}

	/*
	|--------------------------------------------------------------------------
	| Border Radius (independent of border style)
	|--------------------------------------------------------------------------
	*/
	if (config.borderRadius) {
		if (typeof config.borderRadius === 'object') {
			const r = config.borderRadius;
			if (r.top !== undefined || r.right !== undefined) {
				const u = r.unit || 'px';
				cursor.style.borderRadius = `${r.top || 0}${u} ${r.right || 0}${u} ${r.bottom || 0}${u} ${r.left || 0}${u}`;
			} else if (r.size !== undefined) {
				const u = r.unit || 'px';
				cursor.style.borderRadius = `${r.size}${u}`;
			}
		} else if (typeof config.borderRadius === 'number') {
			cursor.style.borderRadius = `${config.borderRadius}px`;
		} else {
			cursor.style.borderRadius = config.borderRadius;
		}
	}

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

	if (config.fontSize) {
		if (typeof config.fontSize === 'object') {
			const size = config.fontSize.size || 16;
			const unit = config.fontSize.unit || 'px';
			cursor.style.fontSize = `${size}${unit}`;
		} else if (typeof config.fontSize === 'number') {
			cursor.style.fontSize = `${config.fontSize}px`;
		} else {
			cursor.style.fontSize = config.fontSize;
		}
	}

	if (config.padding) {
		if (typeof config.padding === 'object') {
			const size = config.padding.size || '';
			const unit = config.padding.unit || 'px';
			if (size !== '') {
				cursor.style.padding = `${size}${unit}`;
			}
		} else {
			cursor.style.padding = config.padding;
		}
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
	play,
	bind,
	unbind,
	reset: unbind,
});