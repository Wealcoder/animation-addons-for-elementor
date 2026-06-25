/* eslint-env browser */

/**
 * Predicate helpers for RegularAnimation. Post-repeater, the section is a
 * single `interactions` repeater — per-row field visibility lives inside the
 * row schema (config.js `rowFields[].when`), reading the FLAT row object.
 *
 * There is no longer a section-level "Enable On Editor" switch or global
 * "Play" button — each interaction row carries its own ▶ play button, and
 * editor binding is driven by the rows' triggers (see settings-bridge
 * shouldBindInEditor). hasInteractions() remains as the only gate used by
 * the section.
 */

/**
 * True when at least one interaction row exists at any breakpoint. The
 * effect now lives inside each row of the `aae_anim_interactions` repeater
 * (Responsive_Json_Prop_Type) instead of a top-level prop.
 */
export function hasInteractions(s) {
	const env = s?.['aae_anim_interactions'];
	const map = (env && typeof env === 'object' && env.$$type === 'aae-rj') ? (env.value || {}) : {};
	return Object.values(map).some((rows) => Array.isArray(rows) && rows.length > 0);
}
