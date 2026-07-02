/* eslint-env browser */

/**
 * Shared editor-bridge state.
 *
 * A single namespaced object on `window` so the bridge stays idempotent across
 * re-inits / HMR / double-enqueue. Every module imports `state` from here rather
 * than keeping its own — there must be exactly one timers map, one
 * originalRun reference, etc.
 */

const NS = 'aaeAtomicSliderEditorBridge';

if (!window[NS]) {
	window[NS] = {
		initialized: false,
		originalRun: null,
		timers: new Map(),
	};
}

/** The one-and-only bridge state object. */
export const state = window[NS];

/** True if the bridge already booted (run-wrapper installed). */
export function isInitialized() {
	return !!state.initialized;
}
