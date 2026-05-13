/* eslint-env browser */

import { getPreviewWindow } from './helpers';
import { FEATURES } from './features';

/**
 * One-shot: when the editor first loads, walk every animated widget in
 * the preview iframe and replay() those with Enable On Editor = true.
 *
 * Runs ONCE per editor session. The previously-watched
 * `elementor/element/render` events (preview-pipe) handle later re-binds
 * when widgets change; this just kick-starts the visible state on load
 * so authors don't have to hit Play Now on every refresh.
 */

let initialReplayDone = false;

export function runInitialReplayAll() {
	if (initialReplayDone) return;

	const win = getPreviewWindow();
	if (!win || !win.aaeAtomicAnimations) return;
	const replay = win.aaeAtomicAnimations.replay;
	if (typeof replay !== 'function') return;

	initialReplayDone = true;

	// Every animated element carries Elementor's universal data-interaction-id.
	// Per-feature maps disambiguate which kinds own which elements.
	const nodes = win.document.querySelectorAll('[data-interaction-id]');

	for (const feature of FEATURES) {
		if (!feature.mapName) continue;
		const map = win[feature.mapName];
		if (!map) continue;

		nodes.forEach((el) => {
			const id = el.getAttribute('data-interaction-id');
			const cfg = id && map[id];
			if (cfg && cfg.enableEditor) {
				try { replay(el); } catch (_) { /* ignore per-element failures */ }
			}
		});
	}
}

/** Exposed for the bootstrap's idempotent re-init. */
export function resetInitialReplayFlag() {
	initialReplayDone = false;
}
