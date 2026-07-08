/* eslint-env browser */

/**
 * Loop Grid editor preview — "full grid live".
 *
 * The loop grid is now a true atomic NESTED element: its authored loop-item
 * subtree is real, editable atomic children. The atomic editor preview renders
 * those children CLIENT-side exactly once (one editable card) — it never runs
 * our PHP WP_Query repetition.
 *
 * To give the "full grid" feel in the canvas without touching the editor's real
 * elements, this module:
 *   1. watches the preview for each loop-grid wrap,
 *   2. wraps the single authored card in a `.aae-a-loop-grid` grid container,
 *   3. fetches the queried posts' data (ajax: aae_loop_post_data) and appends
 *      inert CLONES of the authored card — one per remaining post — each filled
 *      with that post's title/image/url. Clones are display-only
 *      (pointer-events:none, data-aae-clone) and never become editor elements.
 *
 * Everything here is preview-cosmetic. The real output is produced server-side
 * by AAE_A_Loop_Grid::render_children_to_html(). No documents, no switch, no
 * raw-HTML injection, no CSS stripping.
 */

import { state } from './state.js';
import { getPreviewWindow } from './preview.js';
import { applyTitleLimit, readTitleLimit } from './post-title-limit.js';

const WRAP_SELECTOR = '.aae-a-loop-grid-wrap';

function isLoopGridSelection(doc) {
	const selected = doc.querySelector('.elementor-element-selected');
	if (!selected) {
		return false;
	}
	return !!(
		selected.closest(WRAP_SELECTOR) ||
		(selected.querySelector && selected.querySelector(WRAP_SELECTOR))
	);
}

/**
 * While we mutate the preview ourselves (append/remove clones), our own DOM
 * writes MUST NOT feed back into the MutationObserver → scan → hydrate loop.
 * That feedback loop is what let a nested-Flexbox re-render race with our clone
 * churn and drop the authored element's atomic classes. Every clone write is
 * wrapped in `muteObserver()` so `schedule()` ignores mutations we caused.
 */
let observerMuted = 0;
function muteObserver(fn) {
	observerMuted++;
	try {
		return fn();
	} finally {
		// Release on the next microtask: the MutationObserver delivers records
		// asynchronously, so a synchronous decrement would unmute before our own
		// records arrive and we'd re-enter the loop anyway.
		Promise.resolve().then(() => {
			observerMuted--;
		});
	}
}

function cfg() {
	return window.AAE_LOOP_GRID || {};
}

/** Resolve the editor container (V1) for an element id, if available. */
function getContainer(id) {
	try {
		return window.elementor?.getContainer?.(id) || null;
	} catch (e) {
		return null;
	}
}

/** Unwrap an atomic prop value ({ $$type, value } | scalar) to a scalar. */
function propValue(v) {
	return v && typeof v === 'object' && 'value' in v ? v.value : v;
}

/** Recursively unwrap nested { $$type, value } shapes to plain JSON values. */
function deepUnwrap(v) {
	if (v && typeof v === 'object' && '$$type' in v && 'value' in v) {
		return deepUnwrap(v.value);
	}
	if (Array.isArray(v)) {
		return v.map(deepUnwrap);
	}
	if (v && typeof v === 'object') {
		const out = {};
		for (const k of Object.keys(v)) {
			out[k] = deepUnwrap(v[k]);
		}
		return out;
	}
	return v;
}

/** Non-taxonomy advanced-filter props mirrored into the preview query. */
const FILTER_KEYS = ['include_posts', 'exclude_posts', 'date_range', 'meta_key_exists', 'only_featured_image', 'sticky_first', 'related_by', 'offset'];

/** Read the loop-grid query settings from its editor container (falls back to defaults). */
function readSettings(wrap) {
	const id = wrap.getAttribute('data-id');
	const c = id ? getContainer(id) : null;
	const get = (k, d) => {
		try {
			const raw = c?.settings?.get?.(k);
			const val = propValue(raw);
			return val === undefined || val === null || val === '' ? d : val;
		} catch (e) {
			return d;
		}
	};

	// Advanced query filters. Taxonomy term chips are DYNAMIC `tax_<taxonomy>`
	// props, so enumerate the settings model instead of hardcoding a list. The
	// plain-JSON blob goes to the server whole; build_query_args() sanitizes it
	// and assembles the exact query the frontend render runs.
	const filters = {};
	try {
		const attrs = c?.settings?.attributes || {};
		for (const k of Object.keys(attrs)) {
			if (!k.startsWith('tax_') && !FILTER_KEYS.includes(k)) {
				continue;
			}
			const val = deepUnwrap(attrs[k]);
			const empty =
				val === undefined ||
				val === null ||
				val === '' ||
				val === false ||
				(Array.isArray(val) && !val.length);
			if (!empty) {
				filters[k] = val;
			}
		}
	} catch (e) {
		/* settings model unavailable — no filters */
	}

	return {
		post_type: get('post_type', 'post'),
		posts_per_page: parseInt(get('posts_per_page', 6), 10) || 6,
		order_by: get('order_by', 'date'),
		order: get('order', 'desc'),
		filters,
	};
}

/**
 * Signature so we only refetch/rebuild when the QUERY changes.
 *
 * Deliberately excludes the authored card's HTML: selecting/hovering a widget
 * inside the card makes Elementor add overlay nodes + selection classes, which
 * would change the card markup and (if included here) trigger a full clone
 * remove+refetch+re-append on every click — the visible "flicker". The clone
 * SET is a function of the query alone, so key only on that.
 */
function signature(wrap, s) {
	return [s.post_type, s.posts_per_page, s.order_by, s.order, JSON.stringify(s.filters || {})].join('|');
}

/**
 * Per-query post-data cache, keyed by the query signature.
 *
 * A settings change that feeds the element's render context (pagination_type /
 * load_method) can make Elementor re-render the loop-item subtree in place,
 * wiping our appended clones — but the QUERY is unchanged. Without a cache the
 * recovery rebuild would re-hit AJAX, and the round-trip is a visible flicker
 * (clones vanish, then reappear a moment later). Caching the posts per query
 * lets the rebuild run synchronously from memory, so the clones are restored in
 * the same tick — no refetch, no flicker.
 */
const postCache = new Map();

/** Fetch per-post data for the preview (cached by query signature). */
async function fetchPosts(s, sig) {
	if (sig && postCache.has(sig)) {
		return postCache.get(sig);
	}
	const c = cfg();
	if (!c.ajaxUrl || !c.nonce) {
		return null;
	}
	const body = new FormData();
	body.append('action', 'aae_loop_post_data');
	body.append('nonce', c.nonce);
	body.append('post_type', s.post_type);
	body.append('posts_per_page', String(s.posts_per_page));
	body.append('order_by', s.order_by);
	body.append('order', s.order);
	if (s.filters && Object.keys(s.filters).length) {
		body.append('filters', JSON.stringify(s.filters));
	}
	try {
		const res = await fetch(c.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
		const json = await res.json();
		const posts = json && json.success ? json.data.posts : null;
		if (sig && posts) {
			postCache.set(sig, posts);
		}
		return posts;
	} catch (e) {
		return null;
	}
}

/** Synchronous cache read — used to rebuild instantly (no AJAX, no flicker). */
function cachedPosts(sig) {
	return sig && postCache.has(sig) ? postCache.get(sig) : null;
}

/**
 * Strip all editor machinery from a clone so it's pure, inert preview HTML.
 *
 * A raw cloneNode still carries injected overlay nodes and model attributes,
 * which can make Elementor treat the preview copy as an editable element.
 * Keep every CSS class: Elementor's generated styles and tooltip behavior are
 * scoped by element classes, so removing them changes the rendered clone.
 */
function sanitizeClone(root) {
	// This sanitizer belongs exclusively to Loop Grid preview clones. Keep the
	// guard here so a future caller cannot accidentally scrub another widget.
	if ( ! root.matches('[data-aae-clone]') ) {
		return;
	}

	// Drop editor-injected overlay/handle nodes entirely.
	root.querySelectorAll('.elementor-element-overlay, .ui-resizable-handle').forEach((n) => n.remove());

	const scrub = (el) => {
		// Remove editor identity + model attributes.
		[ 'data-id', 'data-model-cid', 'data-element_type', 'data-e-type', 'data-widget_type',
			'draggable', 'data-interaction-id' ].forEach((a) => el.removeAttribute(a));
		// Keep every class. Elementor's generated styles are class-scoped.
	};
	scrub(root);
	root.querySelectorAll('*').forEach(scrub);
}

/**
 * Fill a cloned card's current-post widgets with one post's data.
 * Best-effort + non-destructive: only touches known AAE post widgets.
 */
function fillClone(clone, post) {
	// Atomic widgets render with data-widget_type="<type>.<variant>" (e.g.
	// "e-aae-a-post-title.default"), NOT data-e-type — match by prefix.
	// Title: replace the text in the post-title widget.
	const titleEl = clone.querySelector('[data-widget_type^="e-aae-a-post-title"]');
	if (titleEl && post.title) {
		const target = titleEl.querySelector('h1,h2,h3,h4,h5,h6,a,span,p') || titleEl;
		// Mirror the widget's PHP limit (word/char) — fillClone runs BEFORE
	
		// data-id and we can read its live settings.
		const { by, n } = readTitleLimit(titleEl.getAttribute('data-id'));
		target.textContent = applyTitleLimit(post.title, by, n);
	}
	// Image: swap the featured-image src (post-image widget, or any img fallback).
	const imgEl = clone.querySelector('[data-widget_type^="e-aae-a-post-image"] img, img');
	if (imgEl && post.image) {
		imgEl.setAttribute('src', post.image);
		imgEl.removeAttribute('srcset');
	}
	// Links point at the post.
	if (post.url) {
		clone.querySelectorAll('a[href]').forEach((a) => a.setAttribute('href', post.url));
	}
}

/**
 * Locate the grid CONTAINER + the authored Loop Item inside a wrap.
 *
 * Two DOM shapes must both work:
 *   - New tree:  .aae-a-loop-grid-wrap > .aae-a-loop-grid (Loop Layout) > .aae-a-loop-item
 *   - Old tree:  .aae-a-loop-grid-wrap > .aae-a-loop-item   (wrap itself is the grid)
 *
 * The Loop Item is a direct child of the Loop Layout — no wrapper cell.
 */
function findGridAndItem(wrap) {
	const layout = wrap.querySelector(':scope > .aae-a-loop-grid');
	const grid = layout || wrap; // Loop Layout if present, else the wrap
	const item = grid.querySelector(':scope > .aae-a-loop-item');
	return { grid, item };
}

/** Remove every clone cell in a grid (idempotent). */
function removeClones(grid) {
	const clones = grid.querySelectorAll('[data-aae-clone]');
	if (!clones.length) {
		return;
	}
	muteObserver(() => clones.forEach((n) => n.remove()));
}

/**
 * Capture the authored card's rendered element tree without transient editor
 * overlays. Nested Flexbox/Container children mount asynchronously, so query
 * settings alone cannot tell us whether an earlier clone is now incomplete.
 */
function authoredStructureSignature(item) {
	const parts = [];
	const visit = (el, depth) => {
		if (el.matches('.elementor-element-overlay, .ui-resizable-handle')) {
			return;
		}
		parts.push([
			depth,
			el.tagName,
			el.getAttribute('data-widget_type') || '',
			el.getAttribute('data-e-type') || '',
			el.getAttribute('data-element_type') || '',
		].join(':'));
		Array.from(el.children).forEach((child) => visit(child, depth + 1));
	};
	visit(item, 0);
	return parts.join('|');
}

/**
 * Build the grid preview: the authored item as the first flex child + inert
 * clones for the rest of the queried posts. The Loop Item's own div is the
 * direct flex child of the Loop Layout — no wrapper cell.
 *
 * Race-safety: settings changes make Elementor re-render the Loop Item mid-run.
 * Without guards, overlapping runs each append their own clone set and posts
 * pile up / duplicate. So we (a) skip if a run is already in flight for this
 * wrap, and (b) strip ALL clones immediately AND again right before appending.
 */

/** Build inert preview clones (posts[1..]) after the authored item. */
function buildClones(grid, item, posts, doc, structureSig = authoredStructureSignature(item)) {
	removeClones(grid);
	if (!posts || posts.length <= 1) {
		return;
	}
	// Build the fragment OUTSIDE the mute window (cloneNode/fill/sanitize touch
	// only the detached fragment — no observed mutation). Only the single
	// grid.appendChild is a live DOM write, so that is all we mute.
	const frag = doc.createDocumentFragment();
	for (let i = 1; i < posts.length; i++) {
		const clone = item.cloneNode(true);
		clone.setAttribute('data-aae-clone', '1');
		clone.__aaeSourceStructure = structureSig;
		clone.style.pointerEvents = 'none';
		fillClone(clone, posts[i]);  // uses data-widget_type selectors
		sanitizeClone(clone);        // then strip editor identity attributes
		frag.appendChild(clone);
	}
	muteObserver(() => grid.appendChild(frag));
}

async function hydrate(wrap) {
	const { grid, item } = findGridAndItem(wrap);
	if (!item) {
		return; // authored Loop Item not rendered yet
	}

	// Key the rebuild on the QUERY only (see signature()). Selecting a widget
	// inside the card changes the card markup but NOT the query, so this stays
	// stable across clicks — no rebuild, no flicker, no refetch.
	const s = readSettings(wrap);
	const sig = signature(wrap, s);
	const doc = wrap.ownerDocument;

	// The sig lives on the wrap NODE. Some setting changes (e.g. pagination_type
	// / load_method) feed the element's PHP render context, so Elementor may
	// re-render the loop-item subtree in place — which WIPES our appended clones
	// while the wrap node (and its cached __aaeSig) survive. A pure sig check
	// then early-returns and the grid stays stuck at a single item ("loop item
	// disappear" when pagination/load method changes). So only trust the sig
	// when the DOM still reflects it: at least one clone present, OR the query
	// legitimately yields a single item (nothing to clone). Otherwise rebuild.
	const firstClone = grid.querySelector('[data-aae-clone]');
	const clonesPresent = !!firstClone;
	const structureSig = authoredStructureSignature(item);
	const structureStale = clonesPresent && firstClone.__aaeSourceStructure !== structureSig;

	// Clones are a DOM snapshot of the authored card. On a FRESH DROP the card's
	// widgets can finish mounting AFTER the clones were built — e.g. the
	// post-image <img> appears late, so every clone is permanently missing its
	// image ("first drop e image show kore na"). The query sig never changes, so
	// without this check nothing would ever rebuild. Detect the drift (img count
	// differs between card and clone) and treat the clones as stale — the post
	// cache makes the rebuild synchronous, so there's no refetch/flicker.
	const imgStale = clonesPresent &&
		firstClone.querySelectorAll('img').length !== item.querySelectorAll('img').length;

	// Post-title limit settings (limit_by / title_limit) trim the clone titles
	// via fillClone. They're WIDGET settings, not loop-grid settings, so the
	// query sig can't see them change — key them separately so a limit change
	// re-fills the clones (sync, from cache).
	const titleSig = [ ...item.querySelectorAll('[data-widget_type^="e-aae-a-post-title"][data-id]') ]
		.map((el) => {
			const { by, n } = readTitleLimit(el.getAttribute('data-id'));
			return by + ':' + n;
		})
		.join('|');
	const titleStale = clonesPresent && wrap.__aaeTitleSig !== titleSig;

	const cloneStale = structureStale || imgStale || titleStale;

	const inSync = ( clonesPresent || wrap.__aaeSingle === true ) && !cloneStale;
	if (wrap.__aaeSig === sig && !wrap.__aaeDirty && inSync) {
		return;
	}

	// FAST PATH (flicker-free recovery): the query is unchanged and we already
	// have its posts cached — Elementor re-rendered and wiped the clones, or the
	// clones went stale vs the card (late-mounting <img> / title-limit change
	// above). Rebuild synchronously from the cache in this same tick: no AJAX
	// round-trip, so the clones never visibly disappear.
	if (wrap.__aaeSig === sig && ( ! clonesPresent || cloneStale )) {
		const cached = cachedPosts(sig);
		if (cached) {
			buildClones(grid, item, cached, doc, structureSig);
			wrap.__aaeSingle = cached.length <= 1;
			wrap.__aaeTitleSig = titleSig;
			return;
		}
	}

	if (wrap.__aaeBusy) {
		wrap.__aaeDirty = true; // a run is active; ask it to re-run when done
		return;
	}
	wrap.__aaeBusy = true;
	wrap.__aaeDirty = false;
	wrap.__aaeSig = sig;

	// If we can serve this query from cache, DON'T wipe the clones before the
	// (now synchronous) rebuild — avoids a flash. Only clear up front when we
	// must await a fresh fetch (query genuinely changed).
	const preCached = cachedPosts(sig);
	if (!preCached) {
		removeClones(grid);
	}



	let posts = preCached;
	if (!posts) {
		try {
			posts = await fetchPosts(s, sig);
		} finally {
			wrap.__aaeBusy = false;
		}
	} else {
		wrap.__aaeBusy = false;
	}

	if (!wrap.isConnected) {
		return;
	}

	// Re-run requested while we were awaiting (settings changed again) — restart
	// so we build against the latest state instead of stacking stale clones.
	if (wrap.__aaeDirty) {
		wrap.__aaeSig = null;
		hydrate(wrap);
		return;
	}

	if (!posts || !posts.length) {
		removeClones(grid);
		wrap.__aaeSingle = true; // nothing to clone — a later no-clone DOM is in sync
		return;
	}

	// Remember whether this query legitimately produces a single item (no clones
	// expected). Without this the in-sync check above would treat a correct
	// single-item grid as "clones missing" and rebuild on every tick.
	wrap.__aaeSingle = posts.length <= 1;

	buildClones(grid, item, posts, doc, structureSig);
	wrap.__aaeTitleSig = titleSig;
}

/**
 * The post chosen in Page Settings → Preview Settings (Pro's
 * `aae_loop_page_post`), read LIVE from the page-settings model — so the value
 * is current right after "Apply & Preview" (which only reloads the preview
 * iframe; the editor's boot-time widget config keeps the OLD sample).
 */
function pageSamplePostId() {
	try {
		const v = window.elementor?.settings?.page?.model?.get?.('aae_loop_page_post');
		const id = parseInt(propValue(v), 10);
		return id > 0 ? id : 0;
	} catch (e) {
		return 0;
	}
}

/**
 * Fill each authored (non-clone) loop-item card with the Preview Settings
 * post's title/image. Runs once per preview document load: the PHP schema
 * default covers the editor BOOT; this covers the Apply-&-Preview reload,
 * where the boot config is stale. Clones are untouched (they show the real
 * queried posts via fillClone).
 */
async function fillAuthoredSample(pdoc) {
	const id = pageSamplePostId();
	if (!id) {
		return;
	}
	const c = cfg();
	if (!c.ajaxUrl || !c.nonce) {
		return;
	}
	const body = new FormData();
	body.append('action', 'aae_loop_sample_post');
	body.append('nonce', c.nonce);
	body.append('sample_id', String(id));
	let data = null;
	try {
		const res = await fetch(c.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
		const json = await res.json();
		data = json && json.success ? json.data : null;
	} catch (e) {
		return;
	}
	if (!data || !pdoc.body) {
		return;
	}
	pdoc.querySelectorAll(WRAP_SELECTOR).forEach((wrap) => {
		const item = [...wrap.querySelectorAll('.aae-a-loop-item')].find((i) => !i.closest('[data-aae-clone]'));
		if (!item) {
			return;
		}
		const titleEl = item.querySelector('[data-widget_type^="e-aae-a-post-title"]');
		if (titleEl && data.title) {
			const target = titleEl.querySelector('h1,h2,h3,h4,h5,h6,a,span,p') || titleEl;
			// Plain full text — the post-title-limit module captures + trims it.
			target.textContent = data.title;
		}
		const img = item.querySelector('[data-widget_type^="e-aae-a-post-image"] img, img');
		if (img && data.image) {
			img.setAttribute('src', data.image);
			img.removeAttribute('srcset');
		}
	});
}

/**
 * Preview the repeating page-number bar in the editor.
 *
 * The page numbers are ONE authored atomic template (AAE_A_Loop_Number) that
 * repeats per page link on the frontend (its print_content loops 1..N). The
 * editor renders that template CLIENT-side just once, so the canvas would show a
 * single number. To make the bar look real — and to let the user see their
 * styling on every number — we clone the authored number into a smart-truncated
 * [1][2][3]…[N] set of INERT display-only copies (data-aae-num-clone,
 * pointer-events:none), exactly like the loop-item card clones. The authored
 * number stays the first, selectable element; clones are never editor elements.
 *
 * Preview page count is a fixed sample (current 3 of 7) so gaps + a current-page
 * state are both visible to style. Keyed by a signature (authored markup +
 * sample) so we only rebuild on real changes.
 */
const PREVIEW_CURRENT = 3;
const PREVIEW_TOTAL = 7;

function smartPagesPreview(current, total) {
	if (total <= 7) {
		const all = [];
		for (let i = 1; i <= Math.max(1, total); i++) {
			all.push(i);
		}
		return all;
	}
	const pages = [1];
	const start = Math.max(2, current - 1);
	const end = Math.min(total - 1, current + 1);
	if (start > 2) {
		pages.push('...');
	}
	for (let p = start; p <= end; p++) {
		pages.push(p);
	}
	if (end < total - 1) {
		pages.push('...');
	}
	pages.push(total);
	return pages;
}

function stampPreviewNumber(el, item, current) {
	const isGap = item === '...';
	const isCurrent = item === current;
	el.classList.toggle('is-gap', isGap);
	el.classList.toggle('aae-a-loop-num-gap', isGap);
	el.classList.toggle('is-active', isCurrent);
	el.classList.toggle('e--selected', isCurrent);
	el.textContent = isGap ? '…' : String(item);
	el.removeAttribute('href');
	el.style.pointerEvents = 'none';
}

function previewNumbers(doc) {
	doc.querySelectorAll('.aae-a-loop-numbers').forEach((numbersEl) => {
		// The authored template is the first real (non-clone) number.
		const authored = numbersEl.querySelector('.aae-a-loop-number:not([data-aae-num-clone])');
		if (!authored) {
			return;
		}

		const items = smartPagesPreview(PREVIEW_CURRENT, PREVIEW_TOTAL);
		// Signature: authored markup (styling/classes) + the sample page set. If
		// the user restyles the template, authored.outerHTML changes -> rebuild.
		const sig = authored.className + '|' + authored.getAttribute('style') + '|' + items.join(',');
		if (numbersEl.__aaeNumPreviewSig === sig) {
			return;
		}
		numbersEl.__aaeNumPreviewSig = sig;

		// Drop old clones. Leave the AUTHORED number untouched (mutating it would
		// fight Elementor's model re-render and muddle selection) — it stands in as
		// page "1". Append inert clones for the remaining smart-truncated items.
		const oldClones = numbersEl.querySelectorAll('[data-aae-num-clone]');

		const frag = doc.createDocumentFragment();
		for (let i = 1; i < items.length; i++) {
			const clone = authored.cloneNode(true);
			clone.setAttribute('data-aae-num-clone', '1');
			// Strip editor identity so the clone is never treated as an element.
			['data-id', 'data-model-cid', 'data-element_type', 'data-e-type', 'data-widget_type', 'data-interaction-id'].forEach((a) => clone.removeAttribute(a));
			stampPreviewNumber(clone, items[i], PREVIEW_CURRENT);
			frag.appendChild(clone);
		}
		// Both DOM writes muted so our own number clones don't re-trigger scan().
		muteObserver(() => {
			oldClones.forEach((n) => n.remove());
			numbersEl.appendChild(frag);
		});
	});
}

/** Scan the preview and hydrate all loop grids. */
function scan() {
	const win = getPreviewWindow();
	const doc = win && win.document;
	if (!doc) {
		return;
	}
	if (!isLoopGridSelection(doc)) {
		return;
	}
	// Cheap early-out: on any page WITHOUT a loop grid (the common case), do zero
	// work beyond this one querySelector. Without it, the 1.5s poll would run
	// previewNumbers + pageSamplePostId every tick for the life of the editor,
	// even though nothing on the page is a loop grid.
	const wraps = doc.querySelectorAll(WRAP_SELECTOR);
	if (!wraps.length) {
		return;
	}
	wraps.forEach((wrap) => {
		hydrate(wrap);
	});

	previewNumbers(doc);

	// Whenever the Preview Settings post CHANGES (boot, Apply-&-Preview, or a
	// direct setting edit): sample it into the authored card. Keyed on the id
	// so unchanged settings never refetch. Gate on an authored item existing so
	// the fill lands on real DOM.
	const sid = pageSamplePostId();
	if (doc.__aaeSampleId !== sid && doc.querySelector(WRAP_SELECTOR + ' .aae-a-loop-item')) {
		doc.__aaeSampleId = sid;
		if (sid) {
			fillAuthoredSample(doc);
		}
	}
}

/** True if a mutation record is relevant to a loop grid (and not self-caused). */
function isLoopGridRecord(record) {
	const t = record.target;
	if (!t || t.nodeType !== 1) {
		return false;
	}
	// Our own clone subtree is inert preview scaffolding — never react to it, or
	// the append/remove cycle re-feeds scan() (the class-drop feedback loop).
	if (t.closest('[data-aae-clone], [data-aae-num-clone]')) {
		return false;
	}
	// Only mutations inside a loop-grid wrap matter. An unrelated Flexbox being
	// edited elsewhere in the canvas must not trigger a hydrate.
	return !!(t.closest(WRAP_SELECTOR) || (t.querySelector && t.querySelector(WRAP_SELECTOR)));
}

export function installLoopGrid() {
	if (state.loopGridInstalled) {
		return;
	}

	state.loopGridInstalled = true;

	const schedule = () => {
		// Ignore mutations we caused ourselves (clone churn) — see muteObserver().
		if (observerMuted) {
			return;
		}
		if (state.loopGridRaf) {
			return;
		}
		state.loopGridRaf = requestAnimationFrame(() => {
			state.loopGridRaf = null;
			scan();
		});
	};

	// Only schedule when at least one record actually concerns a loop grid.
	const onMutations = (records) => {
		if (observerMuted) {
			return;
		}
		if (records.some(isLoopGridRecord)) {
			schedule();
		}
	};

	// The preview iframe document can exist before its <body>; gate on body and
	// only observe once (idempotent across in-place re-renders). We observe the
	// preview body (where loop grids live), NOT the outer editor chrome.
	const hookPreview = () => {
		const win = getPreviewWindow();
		const pdoc = win && win.document;
		if (pdoc && pdoc.body && !pdoc.__aaeLoopObserved) {
			pdoc.__aaeLoopObserved = true;
			new MutationObserver(onMutations).observe(pdoc.body, { childList: true, subtree: true });
		}
		// Poll a scan too: a Preview Settings change (page-settings model) has
		// no DOM mutation of its own, so the observer alone would miss it.
		schedule();
	};
	hookPreview();
	setInterval(hookPreview, 1500);

	schedule();
}
