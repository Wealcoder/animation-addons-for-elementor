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
	if (initialReplayDone) {
		console.debug('[AAE] initial-replay: skipped (already done)');
		return;
	}

	const win = getPreviewWindow();
	if (!win || !win.aaeAtomicAnimations) {
		console.debug('[AAE] initial-replay: runtime not ready yet');
		return;
	}
	const replay = win.aaeAtomicAnimations.replay;
	if (typeof replay !== 'function') {
		console.debug('[AAE] initial-replay: replay() missing on runtime');
		return;
	}

	initialReplayDone = true;
	let firedCount = 0;

	// Every animated element carries Elementor's universal data-interaction-id.
	// Per-feature maps disambiguate which kinds own which elements.
	const nodes = win.document.querySelectorAll('[data-interaction-id]');

	for (const feature of FEATURES) {
		if (!feature.mapName) continue;
		const map = win[feature.mapName];
		console.debug(
			`[AAE] initial-replay: feature=${feature.name} map=${feature.mapName} keys=${map ? Object.keys(map).length : 'missing'} dom=${nodes.length}`,
		);
		if (!map) continue;

		nodes.forEach((el) => {
			const id = el.getAttribute('data-interaction-id');
			const cfg = id && map[id];
			if (cfg && cfg.enableEditor) {
				firedCount++;
				try { replay(el); } catch (e) { console.warn('[AAE] initial-replay error', e); }
			}
		});
	}

	console.debug(`[AAE] initial-replay: fired ${firedCount} replays`);
}

/** Exposed for the bootstrap's idempotent re-init. */
export function resetInitialReplayFlag() {
	initialReplayDone = false;
}
