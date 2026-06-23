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
		//installRunWrapper();
	}

	function installRunWrapper() {
		if (window.$e.run.__aaeSliderBridgeWrapped) {
			return;
		}

		state.originalRun = window.$e.run.bind(window.$e);

		function wrappedRun(command, args = {}, ...rest) {
			// Suppress Elementor's re-render for any settings change on an
			// accordion or anything nested inside it. Re-rendering rebuilds the
			// accordion from the twig, flickers, and drops open/closed state. We
			// force render off, then mirror the live-able settings onto the
			// preview DOM so the change is visible without a render.
			if (command === 'document/elements/settings') {
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

	/* ------------------------------------------------------------------ *
	 * Accordion: suppress ALL settings-driven editor re-renders
	 *
	 * Re-rendering an accordion in the editor rebuilds it from the twig,
	 * re-distributes its children, flickers, and drops open/closed state. We
	 * don't want that while editing. For every settings change on the accordion
	 * itself OR on any element nested inside it, we tell Elementor to persist
	 * the value but skip rendering (render/renderUI off).
	 *
	 * Pure-presentation settings that the runtime reads from the DOM are also
	 * mirrored onto the live preview so the change is visible without a render:
	 *   - parent: default_state, max_items_expanded, gap
	 *   - child item: is_active (open/closed state)
	 * Other settings persist to the model and show on save/reload.
	 * ------------------------------------------------------------------ */

	// Parent (accordion) live settings -> how to apply to the accordion element.
	// `attr` is the data-attribute the runtime reads; `style` patches inline style.
	const ACCORDION_LIVE_SETTINGS = {
		default_state:      { attr: 'data-default-state' },
		max_items_expanded: { attr: 'data-max-items-expanded' },
		gap:                { style: (el, v) => { el.style.gap = (parseFloat(v) || 0) + 'px'; } },
	};

	// Unwrap an atomic transformable envelope ({ $$type, value }) to its scalar.
	function unwrapPropValue(raw) {
		if (raw && typeof raw === 'object' && '$$type' in raw) {
			return raw.value;
		}
		return raw;
	}

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

	// If this settings command targets an accordion (or anything inside one),
	// force render off and return a descriptor for the optional DOM patch.
	// Returns null when the command is unrelated to any accordion.
	function maybeHandleAccordionLiveSettings(args) {
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

	function applyAccordionLiveSettings(handled) {
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

	}

	function refreshSliderInPreview(sliderId, reason) {
		const previewWindow = getPreviewWindow();
	
		if (!previewWindow) {		

			return;
		}


		// if (previewWindow.AAEAtomicSlider?.refreshById) {
		// 	previewWindow.AAEAtomicSlider.refreshById(sliderId, reason);
		// 	return;
		// }

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

	/* ------------------------------------------------------------------ *
	 * Accordion "Items" panel — lists the accordion's child items in the
	 * parent's Content tab. Each row opens the Structure panel and selects
	 * its child element (which opens that item's settings). Read-only: it
	 * never mutates the document.
	 * ------------------------------------------------------------------ */

	const ACCORDION_TYPE = 'e-aae-a-accordion';
	const ITEM_TYPE = 'e-aae-a-accordion-item';
	const ITEMS_LIST_CLASS = 'aae-accordion-items-list';

	function getSelectedContainer() {
		try {
			const selected = window.elementor?.selection?.getElements?.();
			if (Array.isArray(selected) && selected.length) {
				return selected[0];
			}
		} catch (e) { /* noop */ }
		return null;
	}

	function getItemTitle(child, index) {
		const settings = child?.model?.get?.('settings');
		const raw = settings?.get?.('item_title') ?? settings?.item_title;
		const val = raw && typeof raw === 'object' ? raw.value : raw;
		if (typeof val === 'string' && val.trim()) {
			return val.trim();
		}
		const editorTitle = child?.model?.get?.('title');
		if (editorTitle) {
			return editorTitle;
		}
		return 'Item #' + (index + 1);
	}

	// Find the "Items" section in the rendered panel by its label text.
	// Returns { section, heading } — heading is the clickable label row that we
	// turn into the collapse toggle.
	function findItemsSection(doc) {
		const headings = doc.querySelectorAll(
			'.MuiTypography-root, .e-control-section-title, [class*="section"] h2, [class*="section"] h3, button, summary'
		);
		for (const el of headings) {
			if ((el.textContent || '').trim() === 'Items') {
				const section = el.closest('section, [class*="Section"], details, [class*="section"]') || el.parentElement;
				return { section, heading: el };
			}
		}
		return null;
	}

	function findContainerInTree(id) {
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

	function doSelect(id) {
		let container = findContainerInTree(id);
		if (!container) {
			container = window.elementor?.getContainer?.(id) || null;
		}
		if (container && typeof container.lookup === 'function') {
			container = container.lookup();
		}
		if (!container) {
			return;
		}
		window.$e.run('document/elements/select', { container, append: false });
	}

	function isNavigatorOpen() {
		try {
			return !!window.$e?.components?.get?.('navigator')?.isOpen;
		} catch (e) {
			return false;
		}
	}

	// Open the Structure panel (so nested atomic children are mounted), then
	// select the child item on the next frames.
	function selectItemById(id) {
		if (!id) {
			return;
		}
		try {
			if (isNavigatorOpen()) {
				doSelect(id);
				return;
			}
			try {
				window.$e.run('navigator/open');
			} catch (e) { /* navigator command unavailable */ }
			requestAnimationFrame(() => {
				requestAnimationFrame(() => doSelect(id));
			});
		} catch (e) { /* noop */ }
	}

	function buildRow(doc, label, onClick) {
		const row = doc.createElement('button');
		row.type = 'button';
		row.className = 'aae-accordion-item-row';
		row.textContent = label;
		row.style.cssText = [
			'display:block', 'width:100%', 'text-align:left',
			'padding:8px 12px', 'margin:4px 0', 'cursor:pointer',
			'border:1px solid var(--e-a-border-color, #e6e8ea)', 'border-radius:4px',
			'background:var(--e-a-bg-default, #fff)', 'color:var(--e-a-color-txt, #1a1a1a)',
			'font-size:12px', 'line-height:1.4',
		].join(';');
		row.addEventListener('mouseenter', () => { row.style.background = 'var(--e-a-bg-hover, #f1f2f3)'; });
		row.addEventListener('mouseleave', () => { row.style.background = 'var(--e-a-bg-default, #fff)'; });
		row.addEventListener('click', (e) => {
			e.preventDefault();
			e.stopPropagation();
			onClick();
		});
		return row;
	}

	function renderItemsPanel() {
		const panelDoc = document;
		const selected = getSelectedContainer();
		if (!selected || getElementType(selected) !== ACCORDION_TYPE) {
			return;
		}

		const found = findItemsSection(panelDoc);
		if (!found) {
			return;
		}
		const { section, heading } = found;

		const items = getChildren(selected).filter(
			(child) => getElementType(child) === ITEM_TYPE
		);

		const signature = items.map((c) => getContainerId(c)).join('|');
		if (section.__aaeItemsSignature === signature && section.querySelector('.' + ITEMS_LIST_CLASS)) {
			return;
		}
		section.__aaeItemsSignature = signature;

		const existing = section.querySelector('.' + ITEMS_LIST_CLASS);
		if (existing) {
			existing.remove();
		}

		const list = panelDoc.createElement('div');
		list.className = ITEMS_LIST_CLASS;
		list.style.cssText = 'padding:8px 16px 12px;';

		if (!items.length) {
			const empty = panelDoc.createElement('div');
			empty.textContent = 'No items yet.';
			empty.style.cssText = 'padding:8px 0;font-size:12px;opacity:.7;';
			list.appendChild(empty);
		} else {
			items.forEach((child, index) => {
				const childId = getContainerId(child);
				const row = buildRow(panelDoc, getItemTitle(child, index), () => {
					selectItemById(childId);
				});
				list.appendChild(row);
			});
		}

		section.appendChild(list);
		makeCollapsible(section, heading, list);
	}

	// Make the empty "Items" panel section collapse/expand like the native
	// sections. The native empty section has no collapsible body, so we drive
	// it ourselves: clicking the heading toggles the injected list and a
	// chevron. Collapse state is remembered per section across re-renders.
	function makeCollapsible(section, heading, list) {
		const collapsed = !!section.__aaeItemsCollapsed;
		list.style.display = collapsed ? 'none' : '';

		// Add a chevron indicator to the heading (once).
		let chevron = heading.querySelector('.aae-items-chevron');
		if (!chevron) {
			chevron = document.createElement('span');
			chevron.className = 'aae-items-chevron';
			chevron.textContent = '⌄';
			chevron.style.cssText = 'margin-left:auto;transition:transform .2s ease;display:inline-block;';
			// Keep the heading row laid out so the chevron sits at the right.
			if (getComputedStyle(heading).display.indexOf('flex') === -1) {
				heading.style.display = 'flex';
				heading.style.alignItems = 'center';
			}
			heading.appendChild(chevron);
		}
		chevron.style.transform = collapsed ? 'rotate(-90deg)' : 'rotate(0deg)';

		if (!heading.__aaeCollapseBound) {
			heading.__aaeCollapseBound = true;
			heading.style.cursor = 'pointer';
			heading.addEventListener('click', (e) => {
				e.preventDefault();
				e.stopPropagation();
				const next = !section.__aaeItemsCollapsed;
				section.__aaeItemsCollapsed = next;
				const body = section.querySelector('.' + ITEMS_LIST_CLASS);
				if (body) {
					body.style.display = next ? 'none' : '';
				}
				const chev = heading.querySelector('.aae-items-chevron');
				if (chev) {
					chev.style.transform = next ? 'rotate(-90deg)' : 'rotate(0deg)';
				}
			}, true);
		}
	}

	function installItemsPanel() {
		if (state.itemsPanelInstalled) {
			return;
		}
		state.itemsPanelInstalled = true;

		const schedule = () => {
			if (state.itemsRaf) {
				return;
			}
			state.itemsRaf = requestAnimationFrame(() => {
				state.itemsRaf = null;
				renderItemsPanel();
			});
		};

		const observer = new MutationObserver(schedule);
		observer.observe(document.body, { childList: true, subtree: true });
		schedule();
	}

	boot();
	installItemsPanel();
})();