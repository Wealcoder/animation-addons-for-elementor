/* eslint-env browser */

/**
 * Regular-animation presets. Each effect maps to a { start, end } pair of
 * GSAP property objects. Selecting an effect in an interaction row fills that
 * row's custom_props (from `start`) and custom_props_to (from `end`) and sets
 * method='fromTo' — same UX as the old single-config PRESETS behaviour, now
 * per-row.
 *
 * 'custom' is the sentinel for "user-defined": no auto-fill, method stays
 * whatever the user picked, they add their own properties.
 */
export const PRESETS = {
	custom: null,
	fadeUp: { start: { opacity: 0, y: 80 }, end: { opacity: 1, y: 0 } },
	blurReveal: { start: { opacity: 0, filter: 'blur(20px)', y: 40 }, end: { opacity: 1, filter: 'blur(0px)', y: 0 } },
	skewUp: { start: { opacity: 0, y: 100, skewY: 12 }, end: { opacity: 1, y: 0, skewY: 0 } },
	clipReveal: { start: { clipPath: 'polygon(0 0, 0 0, 0 100%, 0% 100%)' }, end: { clipPath: 'polygon(0 0, 100% 0, 100% 100%, 0 100%)' } },
	scaleIn: { start: { opacity: 0, scale: 0.6 }, end: { opacity: 1, scale: 1 } },
	zoomOut: { start: { opacity: 0, scale: 1.5, filter: 'blur(15px)' }, end: { opacity: 1, scale: 1, filter: 'blur(0px)' } },
	flipUp3D: { start: { opacity: 0, rotationX: -90, transformOrigin: '50% 100%' }, end: { opacity: 1, rotationX: 0 } },
	swingDrop: { start: { opacity: 0, rotationX: -90, transformOrigin: '50% 0%' }, end: { opacity: 1, rotationX: 0 } },
	elasticPop: { start: { opacity: 0, scale: 0.2, rotation: -15 }, end: { opacity: 1, scale: 1, rotation: 0 } },
	flipY: { start: { opacity: 0, rotationY: 90, transformOrigin: '50% 50%' }, end: { opacity: 1, rotationY: 0 } },
	spinIn: { start: { opacity: 0, rotation: 180, scale: 0.5 }, end: { opacity: 1, rotation: 0, scale: 1 } },
	slideRight: { start: { opacity: 0, x: -100 }, end: { opacity: 1, x: 0 } },
	cinematicFocus: { start: { opacity: 0, scale: 1.15, filter: 'blur(12px) brightness(1.5)' }, end: { opacity: 1, scale: 1, filter: 'blur(0px) brightness(1)' } },
	maskRevealUp: { start: { clipPath: 'inset(100% 0% 0% 0%)', y: 40 }, end: { clipPath: 'inset(0% 0% 0% 0%)', y: 0 } },
	perspectiveFall: { start: { opacity: 0, z: 400, rotationX: 25, y: -80 }, end: { opacity: 1, z: 0, rotationX: 0, y: 0 } },
	unfold3D: { start: { opacity: 0, rotationX: -90, scale: 0.9, transformOrigin: '50% 0%' }, end: { opacity: 1, rotationX: 0, scale: 1 } },
	magneticSlide: { start: { opacity: 0, x: -100, skewX: 15 }, end: { opacity: 1, x: 0, skewX: 0 } },
	luxDrift: { start: { opacity: 0, y: 30, filter: 'grayscale(100%)' }, end: { opacity: 1, y: 0, filter: 'grayscale(0%)' } },
	saasDashboard: { start: { opacity: 0, y: 60, scale: 0.95 }, end: { opacity: 1, y: 0, scale: 1 } },
	ecomUnbox: { start: { clipPath: 'inset(20% 20% 20% 20% round 30px)', scale: 1.15, opacity: 0 }, end: { clipPath: 'inset(0% 0% 0% 0% round 16px)', scale: 1, opacity: 1 } },
	neonPulse: { start: { opacity: 0, scale: 0.85, boxShadow: '0 0 60px 10px rgba(99, 102, 241, 0.6)' }, end: { opacity: 1, scale: 1, boxShadow: '0 0 0px 0px rgba(99, 102, 241, 0)' } },
	floatIn: { start: { opacity: 0, y: 40, rotation: -2 }, end: { opacity: 1, y: 0, rotation: 0 } },
};

/** Effect id → { custom_props, custom_props_to, method } row patch, or null
 *  when the effect is 'custom' / unknown (no auto-fill). */
export function presetRowPatch(effect) {
	const preset = PRESETS[effect];
	if (!preset) return null;
	const toRows = (obj) =>
		Object.entries(obj || {}).map(([property, value]) => ({ property, value: String(value) }));
	return {
		method: 'fromTo',
		custom_props: toRows(preset.start),
		custom_props_to: toRows(preset.end),
	};
}
