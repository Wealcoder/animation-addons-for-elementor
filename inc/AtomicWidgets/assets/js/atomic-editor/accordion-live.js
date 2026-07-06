/* eslint-env browser */

/**
 * Accordion live settings — suppress settings-driven editor re-renders for
 * accordions and mirror the live-able presentation settings onto the preview
 * DOM instead.
 *
 * Re-rendering an accordion in the editor rebuilds it from the twig,
 * re-distributes its children, flickers, and drops open/closed state. So for
 * every settings change on the accordion itself OR on any element nested inside
 * it, we tell Elementor to persist the value but skip rendering
 * (render/renderUI off), then patch the few presentation settings the runtime
 * reads from the DOM:
 *   - parent: default_state, max_items_expanded
 *   - child item: is_active (open/closed)
 * Other settings persist to the model and show on save/reload.
 */

import { getPreviewWindow } from './preview.js';
import { ACCORDION_TYPE, ITEM_TYPE } from './accordion-constants.js';
import {
	getContainerId,
	getElementType,
	getParentContainer,
	unwrapPropValue,
} from './container-utils.js';

// Parent (accordion) live settings -> how to apply to the accordion element.
// `attr` is the data-attribute the runtime reads; `style` patches inline style.
const ACCORDION_LIVE_SETTINGS = {
	default_state:      { attr: 'data-default-state' },
	max_items_expanded: { attr: 'data-max-items-expanded' },
};

// Walk up the container tree; return the nearest accordion container id, or
// null. Used so that editing ANY descendant of an accordion also skips the
// accordion's re-render.
function findAccordionAncestorId(container) {
	let current = container;
	let guard = 0;

	while (current && guard < 100) {
		if (getElementType(current) === ACCORDION_TYPE) {
			return getContainerId(current);
		}
		current = getParentContainer(current);
		guard += 1;
	}

	return null;
}

/**
 * If this settings command targets an accordion (or anything inside one), force
 * render off on `args` and return a descriptor for the optional DOM patch.
 * Returns null when the command is unrelated to any accordion.
 */
export function maybeHandleAccordionLiveSettings(args) {
	const settings = args?.settings;
	if (!settings || typeof settings !== 'object') {
		return null;
	}

	const keys = Object.keys(settings);
	if (!keys.length) {
		return null;
	}

	const container = args?.container;
	if (!container) {
		return null;
	}

	const accordionId = findAccordionAncestorId(container);
	if (!accordionId) {
		return null;
	}

	// Persist the value but skip rendering for the whole accordion subtree.
	args.options = Object.assign({}, args.options, {
		render: false,
		renderUI: false,
	});

	const containerType = getElementType(container);
	const containerId = getContainerId(container);

	// Accordion parent: collect the live-able presentation settings to mirror.
	if (containerType === ACCORDION_TYPE) {
		const values = {};
		keys.forEach((key) => {
			if (key in ACCORDION_LIVE_SETTINGS) {
				values[key] = unwrapPropValue(settings[key]);
			}
		});
		return { kind: 'parent', accordionId, values };
	}

	// Accordion item: mirror is_active (open/closed) to the live DOM.
	if (containerType === ITEM_TYPE && 'is_active' in settings) {
		return {
			kind: 'item',
			accordionId,
			itemId: containerId,
			isActive: !!unwrapPropValue(settings.is_active),
		};
	}

	// Deeper descendant (header/content widget, title text, …): no DOM mirror
	// available, but the render is still suppressed above.
	return { kind: 'none', accordionId };
}

/** Apply the descriptor from maybeHandleAccordionLiveSettings to the preview DOM. */
export function applyAccordionLiveSettings(handled) {
	const previewWindow = getPreviewWindow();
	if (!previewWindow) {
		return;
	}

	if (handled.kind === 'item') {
		if (handled.itemId && previewWindow.AAEAccordion?.setItemActive) {
			previewWindow.AAEAccordion.setItemActive(handled.itemId, handled.isActive);
		}
		return;
	}

	if (handled.kind !== 'parent') {
		return;
	}

	const el = previewWindow.document.querySelector(
		'.aae-a-accordion[data-id="' + handled.accordionId + '"]'
	);
	if (!el) {
		return;
	}

	Object.keys(handled.values).forEach((key) => {
		const def = ACCORDION_LIVE_SETTINGS[key];
		const value = handled.values[key];
		if (!def || value === undefined || value === null) {
			return;
		}
		if (def.style) {
			def.style(el, value);
		} else if (def.attr) {
			el.setAttribute(def.attr, String(value));
		}
	});

	// default_state is consumed once (applyDefaultState guards with
	// data-aae-state-applied). Re-arm it so the runtime re-seeds open/closed
	// state from the new default on the next observer tick.
	if ('default_state' in handled.values) {
		el.removeAttribute('data-aae-state-applied');
		if (previewWindow.AAEAccordion?.applyDefaultState) {
			previewWindow.AAEAccordion.applyDefaultState(el);
		}
	}
}
