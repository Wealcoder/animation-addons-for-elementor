/* eslint-env browser */

/**
 * Accordion "Items" panel — lists the accordion's child items in the parent's
 * Content tab. Each row opens the Structure panel and selects its child element
 * (which opens that item's settings). Read-only: it never mutates the document.
 *
 * Implemented as a MutationObserver on the panel that, whenever the accordion is
 * the selected element, injects a collapsible list of its items into the native
 * (empty) "Items" section.
 */

import { state } from './state.js';
import { ACCORDION_TYPE, ITEM_TYPE } from './accordion-constants.js';
import {
	getChildren,
	getContainerId,
	getElementType,
	getRootContainer,
	walkContainerTree,
} from './container-utils.js';

const ITEMS_LIST_CLASS = 'aae-accordion-items-list';

/** The currently-selected container in the editor, or null. */
function getSelectedContainer() {
	try {
		const selected = window.elementor?.selection?.getElements?.();
		if (Array.isArray(selected) && selected.length) {
			return selected[0];
		}
	} catch (e) { /* noop */ }
	return null;
}

/** Display label for an accordion item: its title setting, else a fallback. */
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

// Find the "Items" section in the rendered panel by its label text. Returns
// { section, heading } — heading is the clickable label row we turn into the
// collapse toggle.
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

/** Resolve a mounted container by id (tree first, then elementor lookup). */
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

// Open the Structure panel (so nested atomic children are mounted), then select
// the child item on the next frames.
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
// sections. The native empty section has no collapsible body, so we drive it
// ourselves: clicking the heading toggles the injected list and a chevron.
// Collapse state is remembered per section across re-renders.
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

/** Install the panel observer (idempotent). */
export function installItemsPanel() {
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
