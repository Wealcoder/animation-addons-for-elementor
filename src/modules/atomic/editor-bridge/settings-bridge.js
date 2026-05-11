/* eslint-env browser */

import { getPreviewWindow, unwrap } from './helpers';
import { featureFor } from './features';
import { ALL_BREAKPOINT_KEYS, AAE_RESPONSIVE_BASES } from './responsive-config';

/**
 * Settings → preview-DOM bridge.
 *
 * Mirrors atomic widget settings into data-aae-* attributes on the preview
 * iframe's DOM whenever the user edits a control. The runtime in the iframe
 * watches those attributes (via animation.js) and re-binds animations on
 * change. Per-breakpoint variants are written too so device-mode preview
 * matches the published frontend exactly.
 */

/** Remove every per-breakpoint variant we may have set for one base data-attr. */
function clearAllVariantAttrs(target, baseAttr) {
	for (const bp of ALL_BREAKPOINT_KEYS) {
		target.removeAttribute(baseAttr + '-' + bp);
	}
}

/**
 * Write the container's current settings onto its preview-iframe target node.
 * Returns { feature, target, active } so callers can chain replay logic, or
 * null if the bridge can't be applied (no feature / no preview / no target).
 */
export function applySettingsToDom(container) {
	const feature = featureFor(container);
	if (!feature) return null;

	const win = getPreviewWindow();
	if (!win) return null;

	const target = feature.findTarget(win.document, container.id);
	if (!target) return null;

	const settings = container.settings.attributes || {};
	const enableValue = unwrap(settings[feature.enableSetting]);

	// Strip stale attrs first — base + every per-breakpoint variant.
	for (const attr of Object.values(feature.attrMap)) {
		target.removeAttribute(attr);
		clearAllVariantAttrs(target, attr);
	}

	if (!enableValue || enableValue === 'none') {
		return { feature, target, active: false };
	}

	// Cache desktop values so variants can inherit from them.
	const desktopByAttr = {};

	for (const [key, attr] of Object.entries(feature.attrMap)) {
		let value = unwrap(settings[key]);
		if (value === undefined || value === null) continue;
		if (typeof value === 'boolean') value = value ? '1' : '0';
		target.setAttribute(attr, String(value));
		desktopByAttr[attr] = value;
	}

	// Write per-breakpoint variants for responsive settings. Cascade:
	// empty variant → use the desktop value (same logic Render.php applies).
	for (const base of AAE_RESPONSIVE_BASES) {
		const baseAttr = feature.attrMap[base];
		if (!baseAttr) continue; // Not a feature-tracked attr (e.g. wrapper isn't in attrMap)

		const desktopValue = desktopByAttr[baseAttr];

		for (const bp of ALL_BREAKPOINT_KEYS) {
			const variantKey  = base + '_' + bp;
			const variantAttr = baseAttr + '-' + bp;

			let value = unwrap(settings[variantKey]);
			if (value === undefined || value === null || value === '') {
				value = desktopValue; // inherit
			}
			if (value === undefined || value === null) continue;
			if (typeof value === 'boolean') value = value ? '1' : '0';

			target.setAttribute(variantAttr, String(value));
		}
	}

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
