(function () {
	'use strict';

	const NS = 'aaeAtomicSliderEditorBridge';

	if (window[NS]?.initialized) {
		return;
	}

	window[NS] = {
		initialized: false,
		originalRun: null,
		timers: new Map(),
	};

	const state = window[NS];

	function boot() {
		if (state.initialized) {
			return;
		}

		if (!window.$e?.run) {
			setTimeout(boot, 100);
			return;
		}

		state.initialized = true;
		installRunWrapper();

		console.log('AAE Atomic Slider Editor Bridge installed');
	}

	function installRunWrapper() {
		if (window.$e.run.__aaeSliderBridgeWrapped) {
			return;
		}

		state.originalRun = window.$e.run.bind(window.$e);

		function wrappedRun(command, args = {}, ...rest) {
			const shouldHandle = shouldHandleCommand(command);
			const beforeContext = shouldHandle
				? captureBeforeContext(command, args)
				: null;

			if (shouldHandle) {
				console.log('AAE Slider Bridge BEFORE:', {
					command,
					args,
					beforeContext,
				});
			}

			const result = state.originalRun(command, args, ...rest);

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

	function handleAfterCommand(command, args, result, context) {
		console.log('AAE Slider Bridge AFTER:', {
			command,
			args,
			result,
			context,
		});

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

	function handleDelete(context) {
		if (!Array.isArray(context?.deleted)) {
			return;
		}

		context.deleted.forEach((item) => {
			if (item.type !== 'e-aae-a-slide') {
				return;
			}

			const slider = findNearestSliderContainer(item.parent);

			if (slider) {
				queueSliderRefresh(slider, 'slide-delete');
			}
		});
	}

	function handleDuplicate(args, result, context) {
		if (Array.isArray(context?.sources)) {
			context.sources.forEach((item) => {
				if (item.type !== 'e-aae-a-slide') {
					return;
				}

				const slider = findNearestSliderContainer(item.parent);

				if (slider) {
					queueSliderRefresh(slider, 'slide-duplicate-source');
				}
			});
		}

		/**
		 * Duplicate may also return/trigger new created slide.
		 */
		requestAnimationFrame(() => {
			const containers = resolveContainers(result, args);

			containers.forEach((container) => {
				if (getElementType(container) !== 'e-aae-a-slide') {
					return;
				}

				const slider = findNearestSliderContainer(container);

				if (slider) {
					queueSliderRefresh(slider, 'slide-duplicate-result');
				}
			});
		});
	}

	function handleCreateLikeCommand(args, result, context) {
		/**
		 * Direct fallback:
		 * When slide is created under slider, args.container is usually slider.
		 */
		if (context?.parentType === 'e-aae-a-slider') {
			queueSliderRefresh(context.parent, `slide-${context.action}-parent`);
		}

		requestAnimationFrame(() => {
			const containers = resolveContainers(result, args);

			containers.forEach((container) => {
				if (getElementType(container) !== 'e-aae-a-slide') {
					return;
				}

				const slider = findNearestSliderContainer(container);

				if (slider) {
					queueSliderRefresh(slider, `slide-${context?.action || 'create'}`);
				}
			});
		});
	}

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

		console.log('AAE Slider Bridge queued refresh', {
			sliderId,
			reason,
		});
	}

	function refreshSliderInPreview(sliderId, reason) {
		const previewWindow = getPreviewWindow();

		if (!previewWindow) {
			console.warn('AAE Slider Bridge: preview window not found', {
				sliderId,
				reason,
			});

			return;
		}

		console.log('AAE Slider Bridge refresh preview slider', {
			sliderId,
			reason,
		});

		if (previewWindow.AAEAtomicSlider?.refreshById) {
			previewWindow.AAEAtomicSlider.refreshById(sliderId, reason);
			return;
		}

		/**
		 * Fallback custom event.
		 */
		previewWindow.dispatchEvent(
			new previewWindow.CustomEvent('aae:slider:refresh', {
				detail: {
					id: sliderId,
					reason,
				},
			})
		);
	}

	function getPreviewWindow() {
		const iframe =
			document.querySelector('#elementor-preview-iframe') ||
			document.querySelector('iframe[name="elementor-preview-iframe"]');

		if (iframe?.contentWindow) {
			return iframe.contentWindow;
		}

		if (window.elementor?.$preview?.[0]?.contentWindow) {
			return window.elementor.$preview[0].contentWindow;
		}

		return null;
	}

	function findNearestSliderContainer(container) {
		let current = container;

		while (current) {
			if (getElementType(current) === 'e-aae-a-slider') {
				return current;
			}

			current = getParentContainer(current);
		}

		return null;
	}

	function getParentContainer(container) {
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

	function getRootContainer() {
		const currentDocument = window.elementor?.documents?.getCurrent?.();

		if (currentDocument?.container?.model?.get) {
			return currentDocument.container;
		}

		return null;
	}

	function resolveContainers(result, args = {}) {
		const containers = [];
		const seen = new Set();

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

		function pushUnique(container) {
			const id = getContainerId(container) || Math.random().toString(36);

			if (seen.has(id)) {
				return;
			}

			seen.add(id);
			containers.push(container);
		}

		add(result);
		add(args?.container);
		add(args?.containers);
		add(args?.model);
		add(args?.models);

		return containers;
	}

	function walkContainerTree(container, callback) {
		if (!container) {
			return;
		}

		callback(container);

		getChildren(container).forEach((child) => {
			walkContainerTree(child, callback);
		});
	}

	function getChildren(container) {
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

	function getContainerId(container) {
		return (
			container?.id ||
			container?.model?.get?.('id') ||
			container?.model?.id ||
			null
		);
	}

	function getElementType(container) {
		return (
			container?.model?.get?.('widgetType') ||
			container?.model?.get?.('elType') ||
			container?.model?.get?.('type') ||
			container?.model?.get?.('name') ||
			''
		);
	}

	boot();
})();