/* eslint-env browser */

/**
 * Loop Grid editor bridge.
 *
 * The atomic editor preview renders client-side from raw settings, so our
 * server-side WP_Query/loop-item render never runs there. This module bridges
 * that gap:
 *
 *   1. LIVE PREVIEW — watches the preview iframe for loop-grid widgets, fetches
 *      the server-rendered grid (ajax: aae_render_loop_grid) and injects it.
 *      The injected markup's loop-item document wrappers are stripped so the
 *      editor doesn't treat them as embedded documents.
 *   2. CREATE TEMPLATE — the placeholder's "Create a template" button (and the
 *      panel control) create a loop-item document, bind it to the widget, and
 *      switch the editor into it.
 *   3. EDIT IN PLACE — switches the editor to the loop-item DOCUMENT (preview
 *      re-renders to it with the native add-widget zone; the top window / panel
 *      stay, so the editor is never left). A floating "Save & Back" tab returns.
 *
 * Runs in the editor OUTER frame; reaches the preview via getPreviewWindow().
 * Config (ajax url + nonce) comes from window.AAE_LOOP_GRID.
 */

import { state } from './state.js';
import { getPreviewWindow } from './preview.js';

const WRAP_SELECTOR = '.aae-a-loop-grid-wrap';
const LOOP_ITEM_TYPE = 'aae-loop-item';

function cfg() {
	return window.AAE_LOOP_GRID || {};
}

/** Build the query signature for a wrap so we only refetch when it changes. */
function wrapSignature(wrap) {
	return [
		wrap.getAttribute('data-aae-template-id'),
		wrap.getAttribute('data-aae-columns'),
		wrap.getAttribute('data-aae-post-type'),
		wrap.getAttribute('data-aae-posts-per-page'),
		wrap.getAttribute('data-aae-order-by'),
		wrap.getAttribute('data-aae-order'),
	].join('|');
}

/** Fetch the server-rendered grid HTML for a wrap's settings. */
async function fetchGrid(wrap) {
	const c = cfg();
	if (!c.ajaxUrl || !c.createNonce) {
		return null;
	}
	const body = new FormData();
	body.append('action', 'aae_render_loop_grid');
	body.append('nonce', c.createNonce);
	body.append('template_id', wrap.getAttribute('data-aae-template-id') || '0');
	body.append('columns', wrap.getAttribute('data-aae-columns') || '3');
	body.append('post_type', wrap.getAttribute('data-aae-post-type') || 'post');
	body.append('posts_per_page', wrap.getAttribute('data-aae-posts-per-page') || '6');
	body.append('order_by', wrap.getAttribute('data-aae-order-by') || 'date');
	body.append('order', wrap.getAttribute('data-aae-order') || 'desc');

	try {
		const res = await fetch(c.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
		const json = await res.json();
		return json && json.success ? json.data.html : null;
	} catch (e) {
		return null;
	}
}

/**
 * Strip loop-item document wrappers from injected preview markup.
 *
 * print_content() wraps each item in the loop-item document's container attrs
 * (data-elementor-type / data-elementor-id / .elementor-<id>). Left in place,
 * Elementor's embedded-document machinery treats each item as a live document
 * and fires global-classes requests (some with post_id=NaN → 400). The live
 * preview is read-only, so we neutralise those attributes.
 */
function stripDocumentWrappers(root) {
	root.querySelectorAll('[data-elementor-type], [data-elementor-id]').forEach((node) => {
		node.removeAttribute('data-elementor-type');
		node.removeAttribute('data-elementor-id');
		node.removeAttribute('data-elementor-settings');
		Array.from(node.classList).forEach((cls) => {
			if (cls === 'elementor' || /^elementor-\d+$/.test(cls)) {
				node.classList.remove(cls);
			}
		});
	});
}

/**
 * Ensure the hidden in-place edit-area attach target exists in a wrap.
 *
 * The editor switches the loop-item document INTO this node ($e switch with
 * `selector`), rendering its editable canvas (drag-widget zone) here. It carries
 * a VALID data-elementor-id so it is the one legitimate embedded document — the
 * read-only live-preview items have their wrappers stripped, so only this node
 * is treated as an embedded document.
 */
function ensureEditArea(wrap, doc, tplId) {
	let area = wrap.querySelector('.aae-a-loop-grid-editarea');
	// If an edit area exists for a DIFFERENT template (the binding changed, e.g.
	// after creating/selecting a new template), discard it. Leaving a stale
	// `.elementor-<oldId>` node makes Elementor keep trying — and failing — to
	// attach the old document ("Can't attach preview to document '<oldId>'").
	if (area && area.getAttribute('data-elementor-id') !== String(tplId)) {
		area.remove();
		area = null;
	}
	const created = !area;
	if (!area) {
		area = doc.createElement('div');
		area.setAttribute('data-elementor-type', LOOP_ITEM_TYPE);
		area.setAttribute('data-elementor-title', 'Loop Item');
		const inner = doc.createElement('div');
		inner.className = 'elementor-section-wrap ui-sortable';
		area.appendChild(inner);
	}

	// Place the editable master inside the live grid container as its first item
	// (just like Elementor Pro does) so it sits on the left side of the grid.
	const gridEl = wrap.querySelector('.aae-a-loop-grid');
	if (gridEl) {
		if (area.parentNode !== gridEl) {
			gridEl.insertBefore(area, gridEl.firstChild);
		}
	} else {
		// Fallback: place above live preview
		const liveEl = wrap.querySelector('.aae-a-loop-grid-live');
		if (liveEl) {
			if (area.parentNode !== wrap) {
				wrap.insertBefore(area, liveEl);
			}
		} else if (area.parentNode !== wrap) {
			wrap.appendChild(area);
		}
	}

	// Only (re)assert our own identity classes — do NOT overwrite className, or
	// we'd strip the elementor-edit-area-active / elementor-edit-mode classes
	// Elementor adds when this node is the live embedded edit area.
	area.classList.add('elementor', 'elementor-' + tplId, 'aae-a-loop-grid-editarea');
	area.setAttribute('data-elementor-id', String(tplId));
	// Hide only on first creation; once it exists, syncEditState owns visibility
	// (re-hiding an active edit area here is what made the drop zone disappear).
	if (created) {
		area.style.display = 'none';
	}
	return area;
}

/** Hydrate one loop-grid wrap: inject the (cleaned) live grid + edit target. */
async function hydrateWrap(wrap) {
	const tplId = parseInt(wrap.getAttribute('data-aae-template-id') || '0', 10);
	if (!tplId) {
		return;
	}

	const sig = wrapSignature(wrap);
	if (wrap.__aaeLoopSig === sig && !wrap.querySelector('[data-aae-loop-pending]')) {
		return; // already hydrated for these settings
	}
	wrap.__aaeLoopSig = sig;

	const live = wrap.querySelector('.aae-a-loop-grid-live');
	if (!live) {
		return;
	}

	const html = await fetchGrid(wrap);
	if (!wrap.isConnected) {
		return; // wrap was replaced by a re-render while awaiting
	}
	if (html != null) {
		live.innerHTML = html;
		stripDocumentWrappers(live); // read-only items: no embedded-doc scanning
	} else {
		live.innerHTML = '<div class="aae-a-loop-grid-empty">Preview unavailable.</div>';
	}
	live.removeAttribute('data-aae-loop-pending');

	ensureEditArea(wrap, wrap.ownerDocument, tplId); // keep one valid attach target
}

/** Scan the preview for pending loop-grids and hydrate them. */
function scanAndHydrate() {
	const win = getPreviewWindow();
	const doc = win && win.document;
	if (!doc) {
		return;
	}
	doc.querySelectorAll(WRAP_SELECTOR).forEach((wrap) => {
		if (wrap.querySelector('.aae-a-loop-grid-live[data-aae-loop-pending]')) {
			hydrateWrap(wrap);
		}
	});
}

/* ----------------------------------------------------------------------- *
 *  Create + edit in place
 * ----------------------------------------------------------------------- */

/** Resolve the V1 container for an element id. */
function getContainer(id) {
	try {
		return window.elementor?.getContainer?.(id) || null;
	} catch (e) {
		return null;
	}
}

/** Write the template_id prop onto a loop-grid widget. */
function setTemplateId(widgetId, tplId) {
	const container = getContainer(widgetId);
	if (!container || !window.$e?.run) {
		return;
	}
	window.$e.run('document/elements/settings', {
		container,
		settings: { loop_template_id: { $$type: 'number', value: parseInt(tplId, 10) } },
	});
}

/** Create a loop-item template, bind it to the widget, and switch into it. */
export async function createTemplate(widgetId) {
	const c = cfg();
	if (!c.ajaxUrl || !c.createNonce) {
		return;
	}
	try {
		const body = new FormData();
		body.append('action', 'aae_create_loop_item');
		body.append('nonce', c.createNonce);
		body.append('title', 'Loop Item');

		const res = await fetch(c.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
		const json = await res.json();
		if (!json || !json.success || !json.data || !json.data.id) {
			return;
		}
		const tplId = json.data.id;
		if (widgetId) {
			setTemplateId(widgetId, tplId);
		}
		// The widget re-renders (client-side) with the new template_id; wait for
		// its wrap, then edit. editTemplate retries until the wrap exists.
		editTemplate(tplId, widgetId);
	} catch (e) { /* noop */ }
}

/** Locate a loop-grid wrap by widget id (preferred) or bound template id. */
function findWrap(doc, tplId, widgetId) {
	if (widgetId) {
		const byId = doc.querySelector(`${WRAP_SELECTOR}[data-id="${widgetId}"]`);
		if (byId) {
			return byId;
		}
	}
	return doc.querySelector(`${WRAP_SELECTOR}[data-aae-template-id="${tplId}"]`);
}

/**
 * Edit the loop-item template IN PLACE.
 *
 * Reveals the wrap's hidden edit-area attach target (hiding the read-only live
 * grid) and switches the loop-item document into it via `selector`. The editor
 * renders the loop-item's editable canvas (drag-widget zone) into that node
 * without reloading the page. The page document stays mounted, so the back
 * switch can re-attach to it.
 */
export function editTemplate(tplId, widgetId, attempt) {
	const id = parseInt(tplId, 10);
	if (!id || !window.$e?.run) {
		return;
	}
	const tries = attempt || 0;

	const win = getPreviewWindow();
	const doc = win && win.document;
	const wrap = doc ? findWrap(doc, id, widgetId) : null;

	// After a template_id change the widget re-renders from the placeholder to
	// the template-bound (live) markup, REPLACING the wrap and its children. If
	// we attach the edit area to the placeholder wrap, that re-render destroys it
	// and the switch has no target. So retry until the wrap has settled into its
	// live state (placeholder gone, live container present) before attaching.
	const ready = !!wrap
		&& !!wrap.querySelector('.aae-a-loop-grid-live')
		&& !wrap.querySelector('.aae-a-loop-grid-placeholder');
	if (!ready) {
		if (tries < 30) {
			setTimeout(() => editTemplate(id, widgetId, tries + 1), 100);
		}
		return;
	}

	const area = ensureEditArea(wrap, doc, id);
	area.style.display = '';
	// Do not hide the live grid container anymore so the edit area and other 
	// cards remain visible next to each other in the grid columns.

	try {
		state.loopGridParentDoc = window.elementor.documents.getCurrentId();
	} catch (e) { /* noop */ }

	try {
		window.$e.run('editor/documents/switch', {
			id,
			selector: '.elementor-' + id,
			shouldNavigateToDefaultRoute: false,
			setAsInitial: false,
		});
	} catch (e) { /* noop */ }
}

/**
 * Save the loop item and switch back to the main post document.
 *
 * Mirrors Elementor Pro's "Save %s" back handle: switch to the initial document
 * with mode:'save' (persists the loop-item edits). Falls back to the remembered
 * parent if the initial-document id isn't available.
 */
export function backToPage() {
	if (!window.$e?.run) {
		return;
	}
	let parent = state.loopGridParentDoc;
	if (!parent) {
		try {
			parent = window.elementor?.config?.initial_document?.id;
		} catch (e) { /* noop */ }
	}
	if (!parent) {
		return;
	}
	try {
		window.$e.run('editor/documents/switch', {
			id: parseInt(parent, 10),
			mode: 'save',
			selector: '.elementor-' + parseInt(parent, 10),
			shouldNavigateToDefaultRoute: false,
		});
	} catch (e) { /* noop */ }

	// syncEditState restores the live grids once the active class drops; nudge
	// the visible state immediately so the transition feels instant.
	const win = getPreviewWindow();
	const doc = win && win.document;
	if (doc) {
		doc.querySelectorAll(WRAP_SELECTOR).forEach((wrap) => {
			const live = wrap.querySelector('.aae-a-loop-grid-live');
			if (live) {
				live.style.display = '';
				live.setAttribute('data-aae-loop-pending', '1');
			}
			const area = wrap.querySelector('.aae-a-loop-grid-editarea');
			if (area) {
				area.style.display = 'none';
				removeHandle(area, 'back');
			}
			wrap.__aaeLoopSig = null;
		});
	}
}

/** Click delegation for the placeholder "Create a template" button. */
function onPreviewClick(e) {
	const btn = e.target.closest && e.target.closest('.aae-a-loop-grid-create-btn');
	if (!btn) {
		return;
	}
	e.preventDefault();
	e.stopPropagation();
	const wrap = btn.closest(WRAP_SELECTOR);
	const widgetId = wrap ? wrap.getAttribute('data-id') : null;
	createTemplate(widgetId);
}

/* ----------------------------------------------------------------------- *
 *  Pro-style edit / back (save) handles.
 *
 *  Mirrors Elementor Pro's loop document handles (see Pro preview.js
 *  document-handle util):
 *    - EDIT handle (pencil)     on a bound loop grid → switch into the template;
 *    - BACK handle (left arrow) while editing        → save + back to the post.
 *  Built with our own classes (self-styled in loop-grid.scss) so they don't
 *  depend on core editor CSS being present in the atomic preview iframe.
 * ----------------------------------------------------------------------- */

/** Build a handle element (icon + label) wired to onClick. */
function buildHandle(doc, kind, label, onClick) {
	const handle = doc.createElement('div');
	handle.className = 'aae-loop-handle aae-loop-handle--' + kind;
	handle.title = label; // native tooltip
	const inner = doc.createElement('div');
	inner.className = 'aae-loop-handle__inner';
	const icon = doc.createElement('i');
	icon.className = kind === 'edit' ? 'eicon-edit' : 'eicon-arrow-left';
	icon.setAttribute('aria-hidden', 'true');
	const text = doc.createElement('span');
	text.className = 'aae-loop-handle__title';
	text.textContent = label;
	inner.appendChild(icon);
	inner.appendChild(text);
	handle.appendChild(inner);
	handle.addEventListener('click', (ev) => {
		ev.preventDefault();
		ev.stopPropagation();
		onClick();
	});
	return handle;
}

/** Direct-child handle of a kind ('edit' | 'back'), or null. */
function childHandle(parent, kind) {
	return parent ? parent.querySelector(':scope > .aae-loop-handle--' + kind) : null;
}

function removeHandle(parent, kind) {
	const h = childHandle(parent, kind);
	if (h) {
		h.remove();
	}
}

/** Ensure the "Edit Loop Item" handle exists on a bound, not-editing wrap. */
function ensureEditHandle(wrap) {
	if (childHandle(wrap, 'edit')) {
		return;
	}
	const tplId = parseInt(wrap.getAttribute('data-aae-template-id'), 10) || 0;
	const widgetId = wrap.getAttribute('data-id') || null;
	if (!tplId) {
		return;
	}
	wrap.prepend(buildHandle(wrap.ownerDocument, 'edit', 'Edit Loop Item', () => editTemplate(tplId, widgetId)));
}

/** Ensure the "Back to post" (save + back) handle exists on the active area. */
function ensureBackHandle(area) {
	if (childHandle(area, 'back')) {
		return;
	}
	area.prepend(buildHandle(area.ownerDocument, 'back', 'Back to post', () => backToPage()));
}

/**
 * Keep each wrap's edit-area / live-grid visibility in sync with reality.
 *
 * The single source of truth is the DOM: Elementor stamps the active embedded
 * edit area with `elementor-edit-area-active` when a document is switched into
 * it. So per wrap:
 *   - edit area is the active one → show it, hide the read-only live grid;
 *   - otherwise               → hide the edit area, restore the live grid.
 *
 * Runs on every mutation tick, so it self-heals the race where a re-render or
 * re-hydration tried to re-hide the active edit area. (Elementor shows its own
 * "Back to page" affordance, so we don't add our own button — when the user
 * returns by any means the active class drops and the live grid comes back.)
 */
function syncEditState() {
	const win = getPreviewWindow();
	const doc = win && win.document;
	if (!doc) {
		return;
	}
	// Second signal: the id of the document Elementor currently has active. The
	// DOM `elementor-edit-area-active` class and this id update at slightly
	// different points during a switch; honouring whichever says "editing" first
	// avoids a one-tick flicker where the live grid flashes back in.
	let currentId = 0;
	try {
		currentId = parseInt(window.elementor?.documents?.getCurrentId?.(), 10) || 0;
	} catch (e) { /* noop */ }

	doc.querySelectorAll(WRAP_SELECTOR).forEach((wrap) => {
		const area = wrap.querySelector('.aae-a-loop-grid-editarea');
		const live = wrap.querySelector('.aae-a-loop-grid-live');
		const areaId = area ? parseInt(area.getAttribute('data-elementor-id'), 10) || 0 : 0;
		const editing = !!area && (
			area.classList.contains('elementor-edit-area-active') ||
			(areaId > 0 && areaId === currentId)
		);

		// The live grid preview stays visible at all times — Pro shows the grid
		// below the master item you're editing; only the edit area toggles.
		if (live && live.style.display === 'none') {
			live.style.display = '';
		}

		if (editing) {
			if (area.style.display === 'none') {
				area.style.display = '';
			}
			ensureBackHandle(area);   // "← Back to post" while editing
			removeHandle(wrap, 'edit');
			wrap.__aaeWasEditing = true;
		} else {
			if (area && area.style.display !== 'none') {
				area.style.display = 'none';
			}
			if (area) {
				removeHandle(area, 'back');
			}
			// Just returned from editing (our handle OR the native back) — refresh
			// the live preview so it reflects whatever was saved on the master.
			if (wrap.__aaeWasEditing) {
				wrap.__aaeWasEditing = false;
				if (live) {
					live.setAttribute('data-aae-loop-pending', '1');
					wrap.__aaeLoopSig = null;
				}
			}
			// "✎ Edit Loop Item" only on a bound grid (live present); never on the
			// first-drop placeholder (it has its own "Create a template" button).
			if (live) {
				ensureEditHandle(wrap);
			} else {
				removeHandle(wrap, 'edit');
			}
		}
	});
}

/** Install the preview hydrator, create-button delegation + back tab. */
export function installLoopGrid() {
	if (state.loopGridInstalled) {
		return;
	}
	state.loopGridInstalled = true;

	// Expose for the React panel control (separate bundle, shared window).
	window.AAELoopGrid = { createTemplate, editTemplate, backToPage };

	const schedule = () => {
		if (state.loopGridRaf) {
			return;
		}
		state.loopGridRaf = requestAnimationFrame(() => {
			state.loopGridRaf = null;
			scanAndHydrate();
			syncEditState();
		});
	};

	const observer = new MutationObserver(schedule);
	if (document.body) {
		observer.observe(document.body, { childList: true, subtree: true });
	}

	// Watch the preview document + delegate create-button clicks once available.
	// On editor (re)load the preview iframe's document can exist before its
	// <body> does; observe()-ing a null body throws, so gate on body and only
	// mark observed once it succeeds (the interval retries until body appears).
	const hookPreview = () => {
		const win = getPreviewWindow();
		const pdoc = win && win.document;
		if (pdoc && pdoc.body && !pdoc.__aaeLoopObserved) {
			pdoc.__aaeLoopObserved = true;
			new MutationObserver(schedule).observe(pdoc.body, { childList: true, subtree: true });
			pdoc.addEventListener('click', onPreviewClick, true);
		}
	};
	hookPreview();
	setInterval(hookPreview, 1500);

	schedule();
}
