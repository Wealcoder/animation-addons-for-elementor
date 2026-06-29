/* eslint-env browser */

/**
 * Container-tree utilities — feature-agnostic helpers for reading Elementor's
 * V1 container model (ids, types, parents, children) and resolving the
 * container(s) a command operated on.
 *
 * These are shared by the slider-refresh and accordion modules. They make no
 * assumptions about widget type, so keep them pure and side-effect free.
 */

/** Best-effort container id across the shapes Elementor hands us. */
export function getContainerId(container) {
	return (
		container?.id ||
		container?.model?.get?.('id') ||
		container?.model?.id ||
		null
	);
}

/** Best-effort element type (widgetType > elType > type > name). */
export function getElementType(container) {
	return (
		container?.model?.get?.('widgetType') ||
		container?.model?.get?.('elType') ||
		container?.model?.get?.('type') ||
		container?.model?.get?.('name') ||
		''
	);
}

/** The current document's root container, or null. */
export function getRootContainer() {
	const currentDocument = window.elementor?.documents?.getCurrent?.();

	if (currentDocument?.container?.model?.get) {
		return currentDocument.container;
	}

	return null;
}

/**
 * Child containers of `container`, gathered from the model's `elements`
 * collection, any repeaters, and a plain `elements` array fallback.
 */
export function getChildren(container) {
	const children = [];

	const modelElements = container?.model?.get?.('elements');

	if (modelElements?.models) {
		modelElements.models.forEach((model) => {
			children.push({
				id: model.get('id') || model.id,
				model,
			});
		});
	} else if (Array.isArray(modelElements)) {
		children.push(...modelElements);
	}

	const repeaters = container?.repeaters;

	if (repeaters && typeof repeaters === 'object') {
		Object.keys(repeaters).forEach((repeaterName) => {
			const repeaterChildren = repeaters[repeaterName]?.children;

			if (Array.isArray(repeaterChildren)) {
				children.push(...repeaterChildren);
			}
		});
	}

	if (Array.isArray(container?.elements)) {
		children.push(...container.elements);
	}

	return children;
}

/** Depth-first walk; calls `callback(container)` for every node. */
export function walkContainerTree(container, callback) {
	if (!container) {
		return;
	}

	callback(container);

	getChildren(container).forEach((child) => {
		walkContainerTree(child, callback);
	});
}

/** Find a mounted container by id by walking the document tree. */
export function findContainerInTree(id) {
	const root = getRootContainer();
	if (!root || !id) {
		return null;
	}
	let found = null;
	walkContainerTree(root, (container) => {
		if (found) {
			return;
		}
		if (getContainerId(container) === id && container.model?.get) {
			found = container;
		}
	});
	return found;
}

/** Resolve a container's parent across the shapes Elementor exposes. */
export function getParentContainer(container) {
	if (!container) {
		return null;
	}

	if (container.parent?.model?.get) {
		return container.parent;
	}

	if (typeof container.getParent === 'function') {
		const parent = container.getParent();

		if (parent?.model?.get) {
			return parent;
		}
	}

	if (container.parentContainer?.model?.get) {
		return container.parentContainer;
	}

	return findParentContainerFromDocument(container);
}

/** Fallback parent lookup: scan the tree for whoever lists us as a child. */
function findParentContainerFromDocument(targetContainer) {
	const targetId = getContainerId(targetContainer);

	if (!targetId) {
		return null;
	}

	const root = getRootContainer();

	if (!root) {
		return null;
	}

	let foundParent = null;

	walkContainerTree(root, (container) => {
		if (foundParent) {
			return;
		}

		const hasTargetChild = getChildren(container).some((child) => {
			return getContainerId(child) === targetId;
		});

		if (hasTargetChild) {
			foundParent = container;
		}
	});

	return foundParent;
}

/**
 * Normalize the many shapes a command result / args can take into a flat,
 * de-duplicated list of containers. Used to find which slides a create /
 * duplicate / paste / import command produced.
 */
export function resolveContainers(result, args = {}) {
	const containers = [];
	const seen = new Set();

	function pushUnique(container) {
		const id = getContainerId(container) || Math.random().toString(36);

		if (seen.has(id)) {
			return;
		}

		seen.add(id);
		containers.push(container);
	}

	function add(item) {
		if (!item) {
			return;
		}

		if (Array.isArray(item)) {
			item.forEach(add);
			return;
		}

		if (item.model?.get) {
			pushUnique(item);
			return;
		}

		if (item.container?.model?.get) {
			pushUnique(item.container);
			return;
		}

		if (item.containers) {
			add(item.containers);
			return;
		}

		if (item.view?.container?.model?.get) {
			pushUnique(item.view.container);
			return;
		}

		if (typeof item.get === 'function') {
			pushUnique({
				id: item.get('id') || item.id,
				model: item,
			});
		}
	}

	add(result);
	add(args?.container);
	add(args?.containers);
	add(args?.model);
	add(args?.models);

	return containers;
}

/** Unwrap an atomic transformable envelope ({ $$type, value }) to its scalar. */
export function unwrapPropValue(raw) {
	if (raw && typeof raw === 'object' && '$$type' in raw) {
		return raw.value;
	}
	return raw;
}
