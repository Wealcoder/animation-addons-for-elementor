/* eslint-env browser */

import { getPreviewWindow } from './helpers';
import { featuresFor } from './features';

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
	if (typeof feature.buildConfig === 'function') {
		return feature.buildConfig(settings);
	}
	return null;
}

/**
 * Apply ALL features that target this widget type — a heading carries both
 * text-animation and regular-animation, and the user may enable either /
 * both. Each feature writes to its own preview-iframe map independently.
 *
 * Returns a summary { target, results: [{ feature, active }] } where
 * `active` is true when that feature emitted a cfg, false when it cleared
 * (effect=none / toggled off). Returns null when no preview / no target /
 * no features for this widget type.
 */
export function applySettingsToDom(container) {
	
	const features = featuresFor(container);
	if (!features.length) return null;

	const win = getPreviewWindow();
	if (!win) return null;

	const api = win.aaeAtomicAnimations;

	// Every feature shares the same target lookup (data-interaction-id), so
	// we resolve once. If the first feature can't find a target the others
	// won't either.
	const target = features[0].findTarget(win.document, container.id);
	if (!target) return null;

	const results = [];

	for (const feature of features) {
		if (!feature.mapName) continue;

		const cfg = buildConfigFromSettings(feature, container);
		const map = win[feature.mapName] = win[feature.mapName] || {};

		if (!cfg) {
			// Feature toggled off — drop our entry so previously-installed
			// listeners / ScrollTriggers tear down on the rebind below.
			delete map[container.id];
			results.push({ feature, active: false });
			continue;
		}

		map[container.id] = cfg;
		results.push({ feature, active: true });
	}

	// Single rebind() per element regardless of how many features applied —
	// common.js's rebind walks ALL kinds and re-reads each map.
	if (api?.rebind) api.rebind(target);

	return { target, results };
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
