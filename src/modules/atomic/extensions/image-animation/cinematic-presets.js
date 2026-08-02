/* eslint-env browser */

/**
 * Cinematic preset defaults for ImageAnimation's 8 merged-in cinematic
 * effects. This is a deliberate, standalone COPY of the sibling
 * ImageAdvancedAnimation extension's presets.js (ported from the "Advanced
 * Single Image GSAP Widget" prototype, z_temp/Image Animation/script.js) —
 * ImageAnimation does not import from image-advanced-animation, so the two
 * extensions stay fully decoupled and ImageAdvancedAnimation is untouched.
 *
 * Selecting a preset in the "Animation" select auto-fills every field it
 * uses with its signature default via `cinematicPresetRowPatch()`. Fields
 * the preset doesn't use are left alone.
 */
export const CINEMATIC_PRESET_DEFAULTS = {
	cinematicMask: {
		direction: 'bottomToTop', start_scale: 1.28, end_scale: 1, image_shift: 12,
		travel: 72, tilt: 8, shade_opacity: 0.22, radius: 8, sweep: true,
		ease: 'expo.inOut', duration: 1.25,
	},
	scaleAnimation: {
		start_scale: 0.58, end_scale: 1, origin: 'center', fade: true, blur: 8,
		rotation: 0, move_direction: 'none', image_shift: 18,
		ease: 'back.out(1.35)', duration: 1,
	},
	sliceShutter: {
		slice_axis: 'vertical', slice_direction: 'alternate', slice_count: 14,
		slice_skew: 8, depth: 42, stagger: 0.48, brightness: 1.25, saturation: 0.8,
		duration: 1.15, ease: 'expo.out',
	},
	mosaicDepth: {
		tile_columns: 7, tile_rows: 5, tile_order: 'random', tile_scatter: 90,
		depth: 260, tile_start_scale: 0.52, tile_rotation: 55, stagger: 0.72,
		brightness: 1.28, saturation: 0.65, duration: 1.22, ease: 'expo.out',
	},
	liquidClip: {
		direction: 'bottomToTop', start_scale: 1.2, end_scale: 1, image_shift: 12,
		blur: 8, wave_size: 12, saturation: 1.25, sweep: true,
		duration: 1.18, ease: 'expo.out',
	},
	orbitTilt: {
		orbit_direction: 'left', start_scale: 1.16, end_scale: 1, travel: 90,
		rotation_x: 18, rotation_y: 58, rotation_z: 6, depth: 220, saturation: 0.75,
		brightness: 1, sweep: true, duration: 1.22, ease: 'expo.out',
	},
	zoomTunnel: {
		origin: 'center', circle_start: 8, circle_end: 75, start_scale: 1.75,
		end_scale: 1, tilt: 9, depth: 120, brightness: 1.35,
		duration: 1.18, ease: 'expo.inOut',
	},
	scrollParallax: {
		parallax_direction: 'up', frame_distance: 110, image_distance: 14,
		start_scale: 1.32, end_scale: 1.04, rotation_x: 10, shade_opacity: 0.72,
		duration: 1, ease: 'none',
	},
};

/** Effect id → row patch, or null when the effect isn't a cinematic preset. */
export function cinematicPresetRowPatch(effect) {
	const preset = CINEMATIC_PRESET_DEFAULTS[effect];
	return preset ? { ...preset } : null;
}
