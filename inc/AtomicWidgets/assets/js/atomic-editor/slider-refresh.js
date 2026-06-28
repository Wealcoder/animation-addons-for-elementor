/* eslint-env browser */

/**
 * Slider refresh — when a slide is created, deleted, or duplicated inside an
 * `e-aae-a-slider`, the preview's slider runtime needs to re-init so the new
 * slide count / order takes effect. We detect those structural commands (after
 * they run) and dispatch a debounced `aae:slider:refresh` event into the
 * preview iframe, which the slider runtime listens for.
 *
 * Only structural commands matter here; settings changes don't change slide
 * membership. See command-bridge.js for where these handlers are invoked.
 */

import { state } from './state.js';
import { getPreviewWindow } from './preview.js';
import {
	getContainerId,
	getElementType,
	getParentContainer,
	resolveContainers,
} from './container-utils.js';

const SLIDER_TYPE = 'e-aae-a-slider';
const SLIDE_TYPE = 'e-aae-a-slide';

/** Walk up from `container` to the nearest slider container, or null. */
function findNearestSliderContainer(container) {
	let current = container;

	while (current) {
		if (getElementType(current) === SLIDER_TYPE) {
			return current;
		}

		current = getParentContainer(current);
	}

	return null;
}

/**
 * Debounce a refresh per-slider (multiple slide ops in a burst collapse to one
 * refresh), then dispatch on the next two frames so the DOM has settled.
 */
function queueSliderRefresh(sliderContainer, reason) {
	const sliderId = getContainerId(sliderContainer);

	if (!sliderId) {
		return;
	}

	clearTimeout(state.timers.get(sliderId));

	const timer = setTimeout(() => {
		state.timers.delete(sliderId);

		requestAnimationFrame(() => {
			requestAnimationFrame(() => {
				refreshSliderInPreview(sliderId, reason);
			});
		});
	}, 180);

	state.timers.set(sliderId, timer);
}

/** Tell the preview's slider runtime to re-init the slider with this id. */
function refreshSliderInPreview(sliderId, reason) {
	const previewWindow = getPreviewWindow();

	if (!previewWindow) {
		return;
	}

	previewWindow.dispatchEvent(
		new previewWindow.CustomEvent('aae:slider:refresh', {
			detail: {
				id: sliderId,
				reason,
			},
		})
	);
}

/** A slide was deleted → refresh its (former) slider. */
export function handleDelete(context) {
	if (!Array.isArray(context?.deleted)) {
		return;
	}

	context.deleted.forEach((item) => {
		if (item.type !== SLIDE_TYPE) {
			return;
		}

		const slider = findNearestSliderContainer(item.parent);

		if (slider) {
			queueSliderRefresh(slider, 'slide-delete');
		}
	});
}

/** A slide (or its source) was duplicated → refresh the slider. */
export function handleDuplicate(args, result, context) {
	if (Array.isArray(context?.sources)) {
		context.sources.forEach((item) => {
			if (item.type !== SLIDE_TYPE) {
				return;
			}

			const slider = findNearestSliderContainer(item.parent);

			if (slider) {
				queueSliderRefresh(slider, 'slide-duplicate-source');
			}
		});
	}

	// Duplicate may also produce a new created slide in the result.
	requestAnimationFrame(() => {
		const containers = resolveContainers(result, args);

		containers.forEach((container) => {
			if (getElementType(container) !== SLIDE_TYPE) {
				return;
			}

			const slider = findNearestSliderContainer(container);

			if (slider) {
				queueSliderRefresh(slider, 'slide-duplicate-result');
			}
		});
	});
}

/** A slide was created / pasted / imported → refresh the slider. */
export function handleCreateLikeCommand(args, result, context) {
	// Direct fallback: a slide created under a slider usually has the slider as
	// args.container, available before the result resolves.
	if (context?.parentType === SLIDER_TYPE) {
		queueSliderRefresh(context.parent, `slide-${context.action}-parent`);
	}

	requestAnimationFrame(() => {
		const containers = resolveContainers(result, args);

		containers.forEach((container) => {
			if (getElementType(container) !== SLIDE_TYPE) {
				return;
			}

			const slider = findNearestSliderContainer(container);

			if (slider) {
				queueSliderRefresh(slider, `slide-${context?.action || 'create'}`);
			}
		});
	});
}
