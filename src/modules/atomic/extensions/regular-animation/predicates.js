/* eslint-env browser */

/**
 * Predicate helpers for RegularAnimation. Post-repeater, the section is a
 * single `interactions` repeater — per-row field visibility lives inside the
 * row schema (config.js `rowFields[].when`), reading the FLAT row object.
 *
 * The only section-level (non-row) rows left are the shared "Enable On
 * Editor" switch and the "Play" button, both gated on whether any
 * interaction exists.
 */

/** Pull a non-responsive boolean's primitive out of its envelope. */
function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

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

/** Enable On Editor visible whenever at least one interaction exists. */
export function showEnableEditor(s) {
	return hasInteractions(s);
}

/** Play Now visible only when Enable On Editor is true AND an interaction exists. */
export function showPlayButton(s) {
	return hasInteractions(s) && plainBool(s, 'aae_anim_enable_editor');
}
