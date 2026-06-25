/* eslint-env browser */

/**
 * Predicate helpers for TextAnimation. Post-repeater, the section is a single
 * `interactions` repeater — per-row field visibility lives inside the row
 * schema (config.js `rowFields[].when`), reading the FLAT row object.
 *
 * The only section-level rows left are the shared "Enable On Editor" switch
 * and the "Play" button, both gated on whether any interaction exists.
 */

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

/** True when at least one text interaction row exists at any breakpoint. */
export function hasInteractions(s) {
	const env = s?.['aae_text_interactions'];
	const map = (env && typeof env === 'object' && env.$$type === 'aae-rj') ? (env.value || {}) : {};
	return Object.values(map).some((rows) => Array.isArray(rows) && rows.length > 0);
}

export function showEnableEditor(s) {
	return hasInteractions(s);
}

export function showPlayButton(s) {
	return hasInteractions(s) && plainBool(s, 'aae_text_enable_editor');
}
