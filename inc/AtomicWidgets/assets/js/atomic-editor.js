/* eslint-env browser */

/**
 * AAE atomic editor bridge — entry point.
 *
 * Runs in the Elementor editor's OUTER frame. It wraps `$e.run` to react to
 * document commands (without patching the editor) and installs a read-only
 * "Items" panel for accordions. The actual logic lives in ./atomic-editor/*:
 *
 *   state.js                 — shared, namespaced window state (idempotency)
 *   preview.js               — access to the preview iframe window
 *   container-utils.js       — id/type/parent/children tree helpers (shared)
 *   accordion-constants.js   — accordion element type ids
 *   accordion-live.js        — suppress accordion re-render + mirror live settings
 *   accordion-items-panel.js — inject the "Items" list into the accordion panel
 *   slider-refresh.js        — refresh a slider when its slides change
 *   command-bridge.js        — the $e.run wrapper that ties the above together
 *
 * This file only orchestrates: wait for $e, install the wrapper, install the
 * items panel. Add a new editor behaviour by creating a module under
 * ./atomic-editor/ and wiring it here (or into command-bridge.js).
 */

import { state, isInitialized } from './atomic-editor/state.js';
import { installRunWrapper } from './atomic-editor/command-bridge.js';
import { installItemsPanel } from './atomic-editor/accordion-items-panel.js';

function boot() {
	if (isInitialized()) {
		return;
	}

	// $e isn't ready immediately on editor load — poll until it is.
	if (!window.$e?.run) {
		setTimeout(boot, 100);
		return;
	}

	state.initialized = true;
	installRunWrapper();
}

boot();
installItemsPanel();
