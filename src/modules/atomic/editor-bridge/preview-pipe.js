/* eslint-env browser */

import { getPreviewWindow } from './helpers';
import { track } from './disposables';

/**
 * Preview-iframe event pipe.
 *
 * Atomic widget re-renders inside the iframe fire `elementor/element/render`.
 * The animation runtime needs to rebind+scan those elements so newly-rendered
 * nodes pick up their data-aae-* attributes. The runtime API is exposed on
 * the iframe window as `aaeAtomicAnimations` — we wait for it to appear and
 * then forward render events into rebind/scan.
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
