/* eslint-env browser */

/**
 * Default styles for the accordion item Header div block.
 *
 * Elementor's AtomicElementBaseModel.buildElement() whitelists the keys it
 * copies from `default_children` (elType/settings/elements/…) and silently
 * drops `styles`, so the PHP side cannot ship default local styles with the
 * Header child. Instead, after any create-like document command we walk the
 * created elements, find accordion-item Header div blocks that have no local
 * styles yet, and seed them with display:flex / row / space-between through
 * the official v4 styles API (window.elementorV2.editorElements
 * .createElementStyle). That API generates a unique per-element style id and
 * registers the class on the element, so the values show in the style panel,
 * persist with the document, and render on the frontend.
 */

import { ITEM_TYPE } from './accordion-constants.js';

const HEADER_CLASS = 'aae-header-element';

const HEADER_DEFAULT_PROPS = {
	display: { $$type: 'string', value: 'flex' },
	'flex-direction': { $$type: 'string', value: 'row' },
	'justify-content': { $$type: 'string', value: 'space-between' },
};

/** The v4 styles API, or null when the packages bundle isn't loaded. */
function getCreateElementStyle() {
	return window.elementorV2?.editorElements?.createElementStyle || null;
}

/** Read the plain classes array from a Backbone element model. */
function getModelClasses(model) {
	const settings = model?.get?.('settings');
	const classes = settings?.get?.('classes');

	return Array.isArray(classes?.value) ? classes.value : [];
}

/** True when the model already carries local style definitions. */
function hasLocalStyles(model) {
	const styles = model?.get?.('styles');

	return Boolean(styles && Object.keys(styles).length);
}

/** Collect the Backbone child models of an element model. */
function getChildModels(model) {
	const elements = model?.get?.('elements');

	if (elements?.models) {
		return elements.models;
	}

	return [];
}

/** Seed the default flex styles on one Header model (idempotent). */
function seedHeaderStyles(headerModel) {
	if (hasLocalStyles(headerModel)) {
		return;
	}

	const createElementStyle = getCreateElementStyle();
	const elementId = headerModel?.get?.('id') || headerModel?.id;

	if (!createElementStyle || !elementId) {
		return;
	}

	try {
		createElementStyle({
			elementId,
			classesProp: 'classes',
			label: 'local',
			meta: { breakpoint: 'desktop', state: null },
			props: HEADER_DEFAULT_PROPS,
		});
	} catch (e) {
		// The styles store may not know the element yet (or the API surface
		// changed); never let this break the create command itself.
	}
}

/** Depth-first walk: seed every accordion-item Header under `model`. */
function walkAndSeed(model) {
	if (!model?.get) {
		return;
	}

	const type = model.get('widgetType') || model.get('elType') || '';

	if (type === ITEM_TYPE) {
		getChildModels(model).forEach((child) => {
			if (getModelClasses(child).includes(HEADER_CLASS)) {
				seedHeaderStyles(child);
			}
		});
	}

	getChildModels(model).forEach(walkAndSeed);
}

/**
 * Entry point, called from the command bridge after create-like commands
 * (create / paste / import / repeater insert). `result` is the container —
 * or array of containers — the command produced.
 */
export function applyAccordionHeaderDefaults(result) {
	const containers = Array.isArray(result) ? result : [result];

	// Defer a tick so the v2 elements store has synced the new models.
	setTimeout(() => {
		containers.forEach((container) => {
			walkAndSeed(container?.model);
		});
	}, 0);
}
