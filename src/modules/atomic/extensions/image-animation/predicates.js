/* eslint-env browser */

/**
 * Predicate helpers for ImageAnimation. Post-repeater, per-row field
 * visibility lives in config.js `rowFields[].when`. There is no longer a
 * section-level "Enable On Editor" switch or global "Play" button — each
 * interaction row carries its own ▶ play button, and editor binding is driven
 * by the rows' triggers (see settings-bridge shouldBindInEditor).
 */

export function hasInteractions(s) {
	const env = s?.['aae_img_interactions'];
	const map = (env && typeof env === 'object' && env.$$type === 'aae-rj') ? (env.value || {}) : {};
	return Object.values(map).some((rows) => Array.isArray(rows) && rows.length > 0);
}
