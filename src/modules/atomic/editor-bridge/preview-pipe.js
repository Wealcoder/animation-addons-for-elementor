/* eslint-env browser */

import { getPreviewWindow } from './helpers';
import { track } from './disposables';
import { runInitialReplayAll } from './initial-replay';
import { seedAllAnimatedContainers } from './seed-canvas';

/**
 * Preview-iframe event pipe.
 *
 * Atomic widget re-renders inside the iframe fire `elementor/element/render`.
 * The animation runtime needs to rebind+scan those elements so newly-rendered
 * nodes pick up their entry in the per-feature interactions map (keyed by
 * Elementor's universal `data-interaction-id`). The runtime API is exposed
 * on the iframe window as `aaeAtomicAnimations` — we wait for it to appear
 * and then forward render events into rebind/scan.
 */

const MAX_PIPE_ATTEMPTS = 50;
let pipeAttempts = 0;
let pipeTimer    = null;

function pipePreviewRenderEvents() {
	const win = getPreviewWindow();
	if (!win || !win.aaeAtomicAnimations) return false;

	const handler = (event) => {
		const el = event.detail && event.detail.element;
		if (!el) return;
		win.aaeAtomicAnimations.rebind(el);
		win.aaeAtomicAnimations.scan(el);
	};

	win.addEventListener('elementor/element/render', handler);
	track(() => {
		try { win.removeEventListener('elementor/element/render', handler); } catch (_) { }
	});

	// Atomic widgets in the editor render via React client-side — the PHP
	// Render hook never fires for them, so the iframe ships without any
	// AAE_INTERACTIONS_* maps. Seed them from JS by walking Elementor's
	// container tree. seed-canvas retries on its own (atomic widgets mount
	// async after `preview:loaded`); we only kick off the first-load replay
	// once it actually populates the maps.
	seedAllAnimatedContainers(() => {
		runInitialReplayAll();
	});

	return true;
}

export function tryPipe() {
	pipeAttempts++;

	if (pipePreviewRenderEvents()) return;

	if (pipeAttempts >= MAX_PIPE_ATTEMPTS) {
		console.warn('[AAE] Gave up waiting for preview iframe. Is GSAP enqueued?');
		return;
	}

	pipeTimer = setTimeout(tryPipe, 400);
}

// Cancel any in-flight retry on teardown.
track(() => {
	if (pipeTimer) clearTimeout(pipeTimer);
	pipeTimer = null;
});

/** Exposed for the bootstrap's idempotent re-init. */
export function resetPipeState() {
	pipeAttempts = 0;
	if (pipeTimer) clearTimeout(pipeTimer);
	pipeTimer = null;
}
