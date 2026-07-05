/* eslint-env browser */

/**
 * Command bridge — wraps `$e.run` so we can react to Elementor document
 * commands without patching the editor.
 *
 * Two responsibilities:
 *   1. Accordion settings: intercept `document/elements/settings`, suppress the
 *      re-render, and mirror live settings onto the preview (accordion-live.js).
 *   2. Structural commands (create/duplicate/delete/paste/import/repeater
 *      insert): capture before-context, let the command run, then refresh any
 *      affected slider (slider-refresh.js).
 *
 * The wrapper is installed once and is transparent for every other command.
 */

import { state } from './state.js';
import {
	getContainerId,
	getElementType,
	getParentContainer,
	resolveContainers,
} from './container-utils.js';
import {
	maybeHandleAccordionLiveSettings,
	applyAccordionLiveSettings,
} from './accordion-live.js';
import {
	handleDelete,
	handleDuplicate,
	handleCreateLikeCommand,
} from './slider-refresh.js';
import { schedulePostTitleScan } from './post-title-limit.js';

// Structural commands we post-process for slider refresh.
function shouldHandleCommand(command) {
	return [
		'document/elements/create',
		'document/elements/duplicate',
		'document/elements/delete',
		'document/elements/paste',
		'document/elements/import',
		'document/repeater/insert',
	].includes(command);
}

// Snapshot the relevant containers BEFORE the command runs (parents/ids can be
// gone by the time it resolves — especially for delete).
function captureBeforeContext(command, args = {}) {
	if (command === 'document/elements/create') {
		const parent = args?.container || null;

		return {
			command,
			action: 'create',
			parent,
			parentId: getContainerId(parent),
			parentType: getElementType(parent),
		};
	}

	if (command === 'document/repeater/insert') {
		const parent = args?.container || null;

		return {
			command,
			action: 'insert',
			parent,
			parentId: getContainerId(parent),
			parentType: getElementType(parent),
		};
	}

	if (command === 'document/elements/delete') {
		const deletedContainers = resolveContainers(null, args);

		return {
			command,
			action: 'delete',
			deleted: deletedContainers.map((container) => {
				const parent = getParentContainer(container);

				return {
					container,
					id: getContainerId(container),
					type: getElementType(container),
					parent,
					parentId: getContainerId(parent),
					parentType: getElementType(parent),
				};
			}),
		};
	}

	if (command === 'document/elements/duplicate') {
		const sourceContainers = resolveContainers(null, args);

		return {
			command,
			action: 'duplicate',
			sources: sourceContainers.map((container) => {
				const parent = getParentContainer(container);

				return {
					container,
					id: getContainerId(container),
					type: getElementType(container),
					parent,
					parentId: getContainerId(parent),
					parentType: getElementType(parent),
				};
			}),
		};
	}

	return {
		command,
		action: 'generic',
	};
}

// Route a finished structural command to the matching slider handler.
function handleAfterCommand(command, args, result, context) {
	if (command === 'document/elements/delete') {
		handleDelete(context);
		return;
	}

	if (command === 'document/elements/duplicate') {
		handleDuplicate(args, result, context);
		return;
	}

	if (
		command === 'document/elements/create' ||
		command === 'document/repeater/insert' ||
		command === 'document/elements/paste' ||
		command === 'document/elements/import'
	) {
		handleCreateLikeCommand(args, result, context);
	}
}

/** Install the `$e.run` wrapper (idempotent). */
export function installRunWrapper() {
	if (window.$e.run.__aaeSliderBridgeWrapped) {
		return;
	}

	state.originalRun = window.$e.run.bind(window.$e);

	function wrappedRun(command, args = {}, ...rest) {
		// Accordion settings: suppress re-render + mirror live settings.
		if (command === 'document/elements/settings') {
			// Post-title limit mirror: a limit change may not re-render the
			// widget (no DOM mutation), so nudge the scan directly.
			schedulePostTitleScan();

			const handled = maybeHandleAccordionLiveSettings(args);
			if (handled) {
				const result = state.originalRun(command, args, ...rest);
				applyAccordionLiveSettings(handled);
				return result;
			}
		}

		const shouldHandle = shouldHandleCommand(command);
		const beforeContext = shouldHandle
			? captureBeforeContext(command, args)
			: null;

		const result = state.originalRun(command, args, ...rest);

		// Structural commands may be sync or thenable; handle both.
		if (result && typeof result.then === 'function') {
			return result.then((resolvedResult) => {
				if (shouldHandle) {
					handleAfterCommand(command, args, resolvedResult, beforeContext);
				}

				return resolvedResult;
			});
		}

		if (shouldHandle) {
			handleAfterCommand(command, args, result, beforeContext);
		}

		return result;
	}

	wrappedRun.__aaeSliderBridgeWrapped = true;
	wrappedRun.__aaeOriginalRun = state.originalRun;

	window.$e.run = wrappedRun;
}
