/* eslint-env browser */

/**
 * Client-side content-type + extension registry, mirrored onto window.AAELightbox
 * so third-party scripts can extend the lightbox without touching core:
 *
 *   window.AAELightbox.registerType({ name, match, render })
 *   window.AAELightbox.addToolbarButton({ id, icon, title, onClick })
 *   window.AAELightbox.registerAnimation({ name })    // reserved
 *
 * Core registers its own types (image) at load. Types are matched in
 * registration order; the first `match(slide)` that returns true wins.
 */

import { imageType } from './content-types/image';
import { videoType } from './content-types/video';
import { htmlType } from './content-types/html';

const types = [];
const toolbarButtons = [];

export function registerType(type) {
	if (type && typeof type.render === 'function') {
		types.push(type);
	}
}

export function resolveType(slide) {
	for (let i = types.length - 1; i >= 0; i -= 1) {
		try {
			if (types[i].match(slide)) return types[i];
		} catch (_) { /* ignore a bad matcher */ }
	}
	return types[0]; // image is registered first, safe default
}

export function addToolbarButton(btn) {
	if (btn && btn.id && typeof btn.onClick === 'function') {
		toolbarButtons.push(btn);
	}
}

export function customToolbarButtons() {
	return toolbarButtons.slice();
}

// Register core types. imageType is registered first so it stays the safe
// default (resolveType falls back to types[0]); videoType matches on type
// 'video', htmlType on type 'html'/'content' (container "Full Child Content").
registerType(imageType);
registerType(videoType);
registerType(htmlType);

// Public extension surface.
export function exposeApi() {
	if (window.AAELightbox) return;
	window.AAELightbox = {
		registerType,
		addToolbarButton,
		registerAnimation() { /* reserved for Phase 2 */ },
	};
}
