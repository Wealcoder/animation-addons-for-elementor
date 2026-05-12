/* eslint-env browser */

import { getPreviewWindow, unwrap } from './helpers';
import { featureFor } from './features';

/**
 * Settings → preview-iframe bridge (interactions-map flavour).
 *
 * Old flow: copy every setting onto a `data-aae-*` attr on the preview
 * element, observe attr changes from inside the iframe to rebind.
 *
 * New flow: build a config object from the container's settings (mirroring
 * what server-side Render.php builds at publish), write it into the iframe
 * window's `window.AAE_INTERACTIONS[id]`, ensure the target carries
 * `data-aae-id="{id}"`, then call rebind() inside the iframe so it re-reads.
 *
 * The container.id is the same value Elementor uses for `data-id` and what
 * Render.php passes to InteractionsMap::register() at publish time.
 */

/**
 * Build the config object the JS reader expects. Per-feature shape lives
 * on the feature object (`feature.buildConfig`) so each feature owns its
 * own setting→config mapping. Returns null when feature is "off" (effect
 * = none / not selected).
 */
function buildConfigFromSettings(feature, container) {
	const settings = container.settings?.attributes || {};
	const enableValue = unwrap(settings[feature.enableSetting]);
	if (!enableValue || enableValue === 'none') return null;

	if (typeof feature.buildConfig === 'function') {
		return feature.buildConfig(settings, unwrap);
	}
	return null;
}

/**
 * Write the container's settings into the preview iframe's interactions map
 * for THIS feature (text → AAE_INTERACTIONS_TEXT, anim → AAE_INTERACTIONS_ANIM)
 * AND ensure the target element carries the feature's dispatch attr.
 * Returns { feature, target, active } or null if the bridge can't apply.
 */
export function applySettingsToDom(container) {
	const feature = featureFor(container);
	if (!feature || !feature.mapName) return null;

	const win = getPreviewWindow();
	if (!win) return null;

	const target = feature.findTarget(win.document, container.id);
	if (!target) return null;

	// No DOM attr to set — Elementor's own `data-interaction-id` (or the
	// fallback `data-id`) is already on the target. We only manage the
	// per-feature JS map below.

	const cfg = buildConfigFromSettings(feature, container);

	const map = win[feature.mapName] = win[feature.mapName] || {};
	const api = win.aaeAtomicAnimations;

	if (!cfg) {
		// Feature toggled off — drop our entry from the map and rebind so any
		// previously-installed listeners / ScrollTriggers tear down. The DOM
		// attr can stay; an empty config simply means no animation.
		delete map[container.id];
		if (api?.rebind) api.rebind(target);
		return { feature, target, active: false };
	}

	map[container.id] = cfg;

	// Tell the runtime to re-read the map and reconfigure listeners /
	// ScrollTriggers. Without this, e.g. switching from On Click → On Scroll
	// would leave the old click listener live until the next page reload.
	if (api?.rebind) api.rebind(target);

	return { feature, target, active: true };
}

/**
 * Trigger the preview-iframe runtime to replay an animation on `target`.
 * Returns true if the runtime API was found and called.
 */
export function replayInPreview(target) {
	const win = getPreviewWindow();
	const api = win && win.aaeAtomicAnimations;
	if (!api || !target) return false;

	if (typeof api.replay === 'function') {
		api.replay(target);
	} else if (typeof api.rebind === 'function') {
		api.rebind(target);
	}
	return true;
}
