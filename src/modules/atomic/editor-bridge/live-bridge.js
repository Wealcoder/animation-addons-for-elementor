/* eslint-env browser */

import { getSelectedContainer, unwrap, getPreviewWindow } from './helpers';
import { track } from './disposables';
import { applySettingsToDom, replayInPreview } from './settings-bridge';
import { FEATURES } from './features';

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

/**
 * Tear down everything an element accumulated in the preview iframe:
 *   - drop its entry from every feature's interactions map
 *   - call rebind(target) so every kind's `unbind` fires (kills the
 *     ScrollTrigger that was holding a ref to this DOM node)
 *
 * Looks up the target via the first feature's findTarget — every feature
 * uses the same data-interaction-id strategy so any one of them suffices.
 * If the iframe DOM node is already gone, fall back to a manual ScrollTrigger
 * sweep keyed by id so orphan markers still get killed.
 */
function teardownPreviewForId(id) {
	if (!id) return;
	const win = getPreviewWindow();
	if (!win) return;

	for (const feature of FEATURES) {
		const map = win[feature.mapName];
		if (map && map[id]) delete map[id];
	}

	const target = FEATURES[0]?.findTarget?.(win.document, id);
	const api = win.aaeAtomicAnimations;

	if (target && api?.rebind) {
		api.rebind(target);
		return;
	}

	// Target already detached — sweep ScrollTriggers whose `.trigger`
	// element is no longer in the document. Cheap, runs once per delete.
	const ScrollTrigger = win.ScrollTrigger;
	if (ScrollTrigger?.getAll) {
		for (const st of ScrollTrigger.getAll()) {
			const tEl = st.trigger;
			if (tEl && !win.document.contains(tEl)) {
				try { st.kill(true); } catch (_) { /* ignore */ }
			}
		}
	}
}

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

	// Settings whose change should ALWAYS force a Play-button-style refresh,
	// regardless of the per-feature `Enable On Editor` toggle. These are the
	// fields whose visible result is built at bind time (effect family
	// branches into a different tween shape; markers redraws the
	// ScrollTrigger overlay) — without a forced replay the canvas keeps
	// showing the previous ST's markers / the previous tween's end-state.
	const FORCE_REPLAY_KEYS = new Set([
		'aae_anim_effect',
		'aae_anim_markers',
		'aae_text_effect',
	]);

	// React inputs write via @elementor/editor-elements'
	// `updateElementSettings`, which goes through the v4 Redux store. The
	// Backbone `container.settings.attributes` mirror that we read in
	// `applySettingsToDom` only catches up AFTER the change event fires.
	// Defer to a microtask so we read post-sync values, not stale ones.
	let scheduled = false;
	const runChange = () => {
		scheduled = false;

		const result = applySettingsToDom(container);
		if (!result) return;

		// `changedAttributes()` returns the keys mutated by the most recent
		// `set()` call (Backbone). Falsy when this handler was invoked
		// outside a change cycle (e.g. via the cmds hook below).
		const changed = container.settings.changedAttributes?.() || {};
		const forceReplay = Object.keys(changed).some((k) => FORCE_REPLAY_KEYS.has(k));

		// Hard-destroy guarantee: if every feature came back inactive
		// (effect set to none / image picker cleared / etc.), force a full
		// reset so the killed tween's revert() runs and the bound-flag
		// CSS class drops. applySettingsToDom already called rebind() which
		// tore the ScrollTrigger down, but reset() also reverts inline
		// styles GSAP applied — without it the element stays at the
		// tween's end state.
		const allInactive = result.results.length > 0 && result.results.every((r) => !r.active);
		if (allInactive) {
			const win = getPreviewWindow();
			const api = win && win.aaeAtomicAnimations;
			if (api?.reset) api.reset(result.target);
			return;
		}

		// A heading can have BOTH text + regular animations. Replay if ANY
		// applicable feature is active AND has its Enable On Editor toggle
		// on; otherwise reset (kills tweens / strips applied styles).
		const settings = container.settings.attributes;
		const shouldReplay = forceReplay || result.results.some((r) => {
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

	// Coalesce burst writes (one Backbone `change` event per attribute, and
	// the cmds hook firing in parallel for native controls) into a single
	// rAF-deferred run. rAF gives the v4 store enough time to mirror into
	// container.settings.attributes; coalescing avoids N rebinds per click.
	activeChangeHandler = () => {
		// onChange (panel-scan refresh) stays synchronous — placeholder hint
		// updates shouldn't lag a frame behind the user's keystroke.
		if (typeof onChange === 'function') onChange();

		if (scheduled) return;
		scheduled = true;
		requestAnimationFrame(runChange);
	};

	activeDestroyHandler = () => {
		// Element removed from the canvas — wipe any registered configs so
		// the runtime's rebind() tears down every kind's trigger (kills
		// orphan ScrollTriggers, removes hover/click listeners, drops the
		// CSS bound-flag). Without this the ScrollTrigger keeps drawing
		// markers + holding a ref to the now-detached DOM node.
		teardownPreviewForId(container.id);
		detachLiveBridge();
	};

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
		const cmdHook = (a, b) => {
			const command = (typeof a === 'string') ? a : (typeof b === 'string' ? b : null);
			if (command !== 'document/elements/settings') return;
			if (activeContainer !== container) return;
			activeChangeHandler();
		};
		cmds.on('run:after', cmdHook);
		// Track for detach. We can't off-hook a non-named handler safely
		// across versions, so save it on the container ref for cleanup.
		activeContainer.__aaeCmdHook = cmdHook;
	}

	applySettingsToDom(container);
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

	// Belt-and-braces: any element delete (selected or otherwise) sweeps
	// orphan ScrollTriggers in the preview iframe. The selected-container
	// model.on('destroy') hook above handles the targeted case; this
	// catches deletions that bypass selection (multi-delete, navigator
	// remove, undo/redo of a delete).
	const cmds = window.$e?.commands;
	if (cmds?.on) {
		const deleteHook = (a, b) => {
			// $e.commands `run:after` signature in current atomic editor is
			// (component:Component, command:string, args). Older versions
			// flipped it to (command:string, args, results) — handle both
			// by picking whichever positional arg is a string.
			const command = (typeof a === 'string') ? a : (typeof b === 'string' ? b : null);
			if (!command) return;
			if (!/elements\/(delete|remove|destroy)/.test(command)) return;

			const win = getPreviewWindow();
			const ScrollTrigger = win?.ScrollTrigger;
			if (!ScrollTrigger?.getAll) return;
			for (const st of ScrollTrigger.getAll()) {
				const tEl = st.trigger;
				if (tEl && !win.document.contains(tEl)) {
					try { st.kill(true); } catch (_) { /* ignore */ }
				}
			}
		};
		cmds.on('run:after', deleteHook);
		track(() => { try { cmds.off('run:after', deleteHook); } catch (_) { /* ignore */ } });
	}

	tryAttach();
}

/** Exposed for the bootstrap's idempotent re-init. */
export function resetLiveBridgeFlag() {
	liveBridgeStarted = false;
}
