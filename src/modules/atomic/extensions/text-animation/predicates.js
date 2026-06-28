/* eslint-env browser */

/**
 * Predicate helpers for TextAnimation. Post-repeater, the section is a single
 * `interactions` repeater — per-row field visibility lives inside the row
 * schema (config.js `rowFields[].when`), reading the FLAT row object.
 *
 * There is no longer a section-level "Enable On Editor" switch or global
 * "Play" button — each interaction row carries its own ▶ play button, and
 * editor binding is driven by the rows' triggers (see settings-bridge
 * shouldBindInEditor).
 */

/** True when at least one text interaction row exists at any breakpoint. */
export function hasInteractions(s) {
	const env = s?.['aae_text_interactions'];
	const map = (env && typeof env === 'object' && env.$$type === 'aae-rj') ? (env.value || {}) : {};
	return Object.values(map).some((rows) => Array.isArray(rows) && rows.length > 0);
}
