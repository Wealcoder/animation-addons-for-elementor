/* eslint-env browser */

import { disposeAll } from './editor-bridge/disposables';
import { FEATURES } from './editor-bridge/features';
import { tryPipe, resetPipeState } from './editor-bridge/preview-pipe';
import { startLiveBridge, resetLiveBridgeFlag } from './editor-bridge/live-bridge';
import { startPanelObserver } from './editor-bridge/play-button';
import { startResponsiveBridge, queueResponsiveScan } from './editor-bridge/responsive-bridge';

/**
 * Animation Addons — Atomic Editor Bridge (entry)
 *
 * Three responsibilities, each implemented in its own module under
 * `editor-bridge/`:
 *
 *   1. Mirror atomic widget settings into preview-iframe DOM data-attrs
 *      live, as the user edits.            → settings-bridge + live-bridge
 *   2. Re-bind the runtime animation handler when elements re-render
 *      inside the iframe.                  → preview-pipe
 *   3. Inject a "Play Now" button into the panel that replays the
 *      animation on the active selection.  → play-button
 *
 * Plus three editor-UX polish layers:
 *   - responsive-visibility   hide rows that don't match the active device
 *   - responsive-placeholders show cascaded parent value as input hint
 *   - float-step-fix          let Duration / Delay accept decimals
 *
 * Adding a new widget/effect = add one entry to FEATURES in
 * `editor-bridge/features.js`. Everything else flows from that table.
 *
 * Cleanup-aware: every listener / observer / timer is tracked through the
 * disposables registry and torn down on document switch + beforeunload.
 */

/* =====================================================================
 * Bootstrap — idempotent. Tears down on document switch / unload.
 * =================================================================== */

let bootstrapped = false;

function bootstrap() {
	if (bootstrapped) {
		// Document switched — tear down old listeners and re-init.
		disposeAll();
		bootstrapped = false;
		resetLiveBridgeFlag();
		resetPipeState();
	}
	bootstrapped = true;

	tryPipe();

	// The live-bridge invokes queueResponsiveScan after every settings
	// mutation so placeholder hints refresh in step with edits.
	startLiveBridge(queueResponsiveScan);

	startResponsiveBridge();
}

if (window.elementor && window.elementor.on) {
	window.elementor.on('preview:loaded', bootstrap);
} else {
	document.addEventListener('DOMContentLoaded', bootstrap);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', startPanelObserver);
} else {
	startPanelObserver();
}

// Tear down on page unload as a final safety net.
window.addEventListener('beforeunload', disposeAll);

// Expose for manual debugging / cleanup from console.
window.__aaeAtomicBridge = {
	disposeAll,
	getFeatures: () => FEATURES,
};
