const {
	configFor,
	pickConfigResponsive,
	getGsap,
} = window.AAEADDON;

const MAP = 'AAE_INTERACTIONS_MOUSE_MOVE_EFFECT';

function r(cfg, key, fallback) {
	const v = pickConfigResponsive(cfg, key);
	return (v === undefined || v === '') ? fallback : v;
}

function read(el) {
	const cfg = configFor(el, MAP);
	if (!cfg) return null;

	const isEnabled = r(cfg, 'enable', false);
	if (!isEnabled) return null;

	return {
		enabled: true,
		movement_wrapper: r(cfg, 'movement_wrapper', 'default'),
		move_x: Number(r(cfg, 'move_x', 100)),
		move_y: Number(r(cfg, 'move_y', 100)),
		duration: Number(r(cfg, 'duration', 1)),
		customs: r(cfg, 'customs', ''),
		customProps: Array.isArray(pickConfigResponsive(cfg, 'customProps'))
			? pickConfigResponsive(cfg, 'customProps')
			: [],
		enable_editor: cfg.enable_editor || cfg.enableEditor || false,
	};
}

function play(el, config) {
	unbind(el);
	bind(el, config);
}

/**
 * Parse a CSS value to a number. Returns NaN if not numeric.
 * Handles '0.5', '50%', '10px', '100', etc.
 */
function parseNumericValue(v) {
	if (typeof v === 'number') return v;
	if (typeof v !== 'string') return NaN;
	// Strip common units but remember them
	const num = parseFloat(v);
	return num;
}

function bind(el, config) {
	unbind(el);
	if (!config) return;

	const gsap = getGsap();
	if (!gsap) return;

	// Find the wrapper area
	let wrapper = el;
	if (config.movement_wrapper === 'custom' && config.customs) {
		const customEl = el.closest(config.customs) || document.querySelector(config.customs);
		if (customEl) wrapper = customEl;
	}

	const moveX = config.move_x || 100;
	const moveY = config.move_y || 100;
	// Lerp factor: higher duration = smoother trailing
	const lerpFactor = 1 - Math.pow(0.01, 1 / ((config.duration || 1) * 60));

	// Current & target positions
	let currentX = 0;
	let currentY = 0;
	let targetX = 0;
	let targetY = 0;
	let rafId = null;

	// Parse custom properties into numeric targets for smooth lerping
	const customPropDefs = [];
	if (Array.isArray(config.customProps)) {
		for (const prop of config.customProps) {
			if (prop.k && prop.v !== undefined && prop.v !== '') {
				const numVal = parseNumericValue(prop.v);
				if (!isNaN(numVal)) {
					// Store original value to restore on leave
					const origVal = gsap.getProperty(el, prop.k);
					customPropDefs.push({
						key: prop.k,
						targetVal: numVal,
						origVal: typeof origVal === 'number' ? origVal : 0,
						currentVal: typeof origVal === 'number' ? origVal : 0,
					});
				}
			}
		}
	}

	function startLoop() {
		if (rafId) return;
		tick();
	}

	function tick() {
		// Lerp x/y toward target
		currentX += (targetX - currentX) * lerpFactor;
		currentY += (targetY - currentY) * lerpFactor;

		// Lerp custom props toward target
		const props = { x: currentX, y: currentY };
		for (const cp of customPropDefs) {
			cp.currentVal += (cp.targetVal - cp.currentVal) * lerpFactor;
			props[cp.key] = cp.currentVal;
		}

		gsap.set(el, props);

		// Check if close enough to target to stop
		let totalDist = Math.abs(targetX - currentX) + Math.abs(targetY - currentY);
		for (const cp of customPropDefs) {
			totalDist += Math.abs(cp.targetVal - cp.currentVal);
		}

		if (totalDist < 0.05) {
			// Snap to final values
			currentX = targetX;
			currentY = targetY;
			const finalProps = { x: targetX, y: targetY };
			for (const cp of customPropDefs) {
				cp.currentVal = cp.targetVal;
				finalProps[cp.key] = cp.targetVal;
			}
			gsap.set(el, finalProps);
			rafId = null;
			return;
		}

		rafId = requestAnimationFrame(tick);
	}

	const onMouseMove = (e) => {
		const rect = wrapper.getBoundingClientRect();
		const xNorm = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
		const yNorm = ((e.clientY - rect.top) / rect.height - 0.5) * 2;

		targetX = -xNorm * moveX * 0.5;
		targetY = -yNorm * moveY * 0.5;

		// Set custom prop targets to their hover values
		for (const cp of customPropDefs) {
			cp.targetVal = cp.targetHoverVal;
		}

		startLoop();
	};

	const onMouseLeave = () => {
		// Reset targets to origin
		targetX = 0;
		targetY = 0;

		// Reset custom prop targets to original values
		for (const cp of customPropDefs) {
			cp.targetVal = cp.origVal;
		}

		startLoop();
	};

	// Store hover target values (the configured custom property values)
	// and set origVal from the element's current computed value
	for (const cp of customPropDefs) {
		cp.targetHoverVal = cp.targetVal; // The value from config
	}

	wrapper.addEventListener('mousemove', onMouseMove);
	wrapper.addEventListener('mouseleave', onMouseLeave);

	el.__aaeMouseMoveCleanup = () => {
		if (rafId) cancelAnimationFrame(rafId);
		rafId = null;
		wrapper.removeEventListener('mousemove', onMouseMove);
		wrapper.removeEventListener('mouseleave', onMouseLeave);
		// Reset element to original state
		const resetProps = { x: 0, y: 0 };
		for (const cp of customPropDefs) {
			resetProps[cp.key] = cp.origVal;
		}
		gsap.set(el, resetProps);
		currentX = 0;
		currentY = 0;
		targetX = 0;
		targetY = 0;
	};
}

function unbind(el) {
	if (el.__aaeMouseMoveCleanup) {
		el.__aaeMouseMoveCleanup();
		delete el.__aaeMouseMoveCleanup;
	}
}

window.AAEADDON.register({
	name: 'mouse-move-effect',
	mapName: MAP,
	boundFlag: 'aae-mouse-move-effect-bound',
	read,
	play,
	bind,
	unbind,
	reset: unbind,
});