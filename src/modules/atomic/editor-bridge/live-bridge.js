/* eslint-env browser */

import { getSelectedContainer, unwrap, getPreviewWindow } from './helpers';
import { track } from './disposables';
import { applySettingsToDom, replayInPreview } from './settings-bridge';

/**
 * Live-edit bridge — subscribes to the currently-selected atomic container's
 * settings model and mirrors changes into the preview iframe. Auto-detaches
 * when the user selects a different container or the current one is destroyed.
 *
 * `onChange` (optional) is invoked after every settings mutation, AFTER the
 * preview DOM has been updated. The bootstrap wires this to the responsive
 * panel scan so placeholder hints refresh in step with edits.
 */

let activeContainer       = null;
let activeChangeHandler   = null;
let activeDestroyHandler  = null;
let liveBridgeStarted     = false;

function detachLiveBridge() {
	if (activeContainer) {
		if (activeChangeHandler) {
			activeContainer.settings?.off?.('change', activeChangeHandler);
		}
		if (activeDestroyHandler) {
			activeContainer.model?.off?.('destroy', activeDestroyHandler);
		}
		const cmdHook = activeContainer.__aaeCmdHook;
		if (cmdHook && window.$e?.commands?.off) {
			try { window.$e.commands.off('run:after', cmdHook); } catch (_) { /* ignore */ }
			activeContainer.__aaeCmdHook = null;
		}
	}
	activeContainer = null;
	activeChangeHandler = null;
	activeDestroyHandler = null;
}

function attachLiveBridge(container, onChange) {
	if (!container || !container.settings) return;

	if (activeContainer === container) {
		applySettingsToDom(container);
		return;
	}

	detachLiveBridge();
	activeContainer = container;

	activeChangeHandler = () => {
		if (typeof onChange === 'function') onChange();

		const result = applySettingsToDom(container);
		if (!result) return;

		// A heading can have BOTH text + regular animations. Replay if ANY
		// applicable feature is active AND has its Enable On Editor toggle
		// on; otherwise reset (kills tweens / strips applied styles).
		const settings = container.settings.attributes;
		const shouldReplay = result.results.some((r) => {
			if (!r.active) return false;
			const autoKey = r.feature.autoReplaySetting;
			return autoKey ? !!unwrap(settings[autoKey]) : false;
		});

		if (!shouldReplay) {
			const win = getPreviewWindow();
			const api = win && win.aaeAtomicAnimations;
			if (api?.reset) api.reset(result.target);
			return;
		}

		replayInPreview(result.target);
	};

	activeDestroyHandler = () => detachLiveBridge();

	// Two listeners — same handler, two paths into the same widget settings:
	//
	// 1. `container.settings.on('change')` — Backbone-style. Fires for our
	//    own React inputs (NumberInput / SwitchInput / etc.) that write via
	//    @elementor/editor-elements' updateElementSettings.
	//
	// 2. `$e.commands.on('run:after', 'document/elements/settings')` — v4
	//    atomic-widgets path. Fires when Elementor's NATIVE controls
	//    (Image_Control, Switch_Control as a section item, etc.) commit a
	//    change. The Backbone change event doesn't always propagate from
	//    those, so without this hook the live preview misses native picks.
	container.settings.on('change', activeChangeHandler);
	container.model?.on?.('destroy', activeDestroyHandler);

	const cmds = window.$e?.commands;
	if (cmds?.on) {
		const cmdHook = (command /* , args, results */) => {
			if (command !== 'document/elements/settings') return;
			// Only re-apply if the still-active container matches the one
			// we're watching — `run:after` is global, fires for any element.
			if (activeContainer !== container) return;
			activeChangeHandler();
		};
		cmds.on('run:after', cmdHook);
		// Track for detach. We can't off-hook a non-named handler safely
		// across versions, so save it on the container ref for cleanup.
		activeContainer.__aaeCmdHook = cmdHook;
	}

	applySettingsToDom(container);0
}

/**
 * Start the live bridge. `onChange` is forwarded to attachLiveBridge so
 * callers can hook a panel-scan refresh into every settings mutation.
 */
export function startLiveBridge(onChange) {
	if (liveBridgeStarted) return;
	liveBridgeStarted = true;

	const tryAttach = () => attachLiveBridge(getSelectedContainer(), onChange);

	const editorChannel = window.elementor?.channels?.editor;
	if (editorChannel?.on) {
		editorChannel.on('section:activated', tryAttach);
		track(() => editorChannel.off?.('section:activated', tryAttach));
	}

	const selection = window.elementor?.selection;
	if (selection?.on) {
		selection.on('change:added change:removed', tryAttach);
		track(() => selection.off?.('change:added change:removed', tryAttach));
	}

	track(detachLiveBridge);
	tryAttach();
}

/** Exposed for the bootstrap's idempotent re-init. */
export function resetLiveBridgeFlag() {
	liveBridgeStarted = false;
}
