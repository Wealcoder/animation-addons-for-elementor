/* eslint-env browser */

/**
 * Predicate helpers for Image Hover field visibility.
 *
 * Note: this extension's enable / image / z-index are non-responsive
 * primitives rendered natively in PHP — they don't pass through these
 * predicates at all. Only the responsive Width / Height / Top / Left
 * rows are gated here, on the non-responsive `aae_ih_enable` switch.
 */

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

/** True when the hover effect is enabled (non-responsive switch). */
export function isHoverEnabled(s /* , bp */) {
	return plainBool(s, 'aae_ih_enable');
}
