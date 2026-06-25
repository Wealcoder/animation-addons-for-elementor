/* eslint-env browser */

/**
 * Predicate helpers for ImageAnimation. Post-repeater, per-row field
 * visibility lives in config.js `rowFields[].when`; only the shared
 * "Enable On Editor" switch + "Play" button gate on whether any interaction
 * exists.
 */

function plainBool(s, bind) {
	const v = s?.[bind];
	if (v && typeof v === 'object' && '$$type' in v) return !!v.value;
	return !!v;
}

export function hasInteractions(s) {
	const env = s?.['aae_img_interactions'];
	const map = (env && typeof env === 'object' && env.$$type === 'aae-rj') ? (env.value || {}) : {};
	return Object.values(map).some((rows) => Array.isArray(rows) && rows.length > 0);
}

export function showEnableEditor(s) {
	return hasInteractions(s);
}

export function showPlayButton(s) {
	return hasInteractions(s) && plainBool(s, 'aae_img_enable_editor');
}
