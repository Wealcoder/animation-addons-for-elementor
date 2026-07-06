/* eslint-env browser */

/**
 * Post Title limit — live editor preview mirror.
 *
 * The AAE Post Title widget trims its text in PHP (get_atomic_settings():
 * limit_by = word|char + title_limit). The editor canvas renders the widget
 * CLIENT-side (twig), where that PHP never runs — so changing the limit did
 * nothing live (frontend was always correct). This module mirrors the trim in
 * the preview DOM:
 *
 *   - scan() finds every real (non-clone) post-title widget, remembers its
 *     FULL text on the node, and rewrites the visible text to the trimmed
 *     version per the widget's CURRENT settings.
 *   - A MutationObserver re-runs the scan whenever Elementor re-renders (a
 *     re-render restores the full text — we re-trim in the same frame).
 *   - command-bridge.js also schedules a scan on `document/elements/settings`
 *     so a limit change with no re-render still updates immediately.
 *
 * Loop-grid CLONES are handled separately (fillClone applies the same trim via
 * applyTitleLimit) because sanitized clones carry no data-id to read settings
 * from.
 */

import { state } from './state.js';
import { getPreviewWindow } from './preview.js';

const TITLE_SELECTOR = '[data-widget_type^="e-aae-a-post-title"][data-id]';

/** Unwrap an atomic prop value ({ $$type, value } | scalar) to a scalar. */
function propValue(v) {
	return v && typeof v === 'object' && 'value' in v ? v.value : v;
}

/** Read the limit settings of a post-title widget from its editor container. */
export function readTitleLimit(id) {
	try {
		const c = window.elementor?.getContainer?.(id);
		const get = (k, d) => {
			const val = propValue(c?.settings?.get?.(k));
			return val === undefined || val === null || val === '' ? d : val;
		};
		// Fallbacks mirror the PHP schema defaults (limit_by 'line', title_limit
		// 2) so an untouched widget previews exactly like the frontend render.
		return {
			by: get('limit_by', 'line'),
			n: parseInt(get('title_limit', 2), 10) || 2,
		};
	} catch (e) {
		return { by: 'line', n: 2 };
	}
}

/** JS mirror of the PHP trim (wp_trim_words / mb_substr + '...'). */
export function applyTitleLimit(text, by, n) {
	const full = String(text || '');
	if (by === 'word') {
		const words = full.trim().split(/\s+/);
		return words.length > n ? words.slice(0, n).join(' ') + '...' : full;
	}
	if (by === 'char') {
		return full.length > n ? full.slice(0, n) + '...' : full;
	}
	return full;
}

/** The element whose text is the title (the widget root often IS the tag). */
function titleTarget(el) {
	return el.querySelector('h1,h2,h3,h4,h5,h6,a,span,p') || el;
}

function scan() {
	const win = getPreviewWindow();
	const doc = win && win.document;
	if (!doc) {
		return;
	}
	doc.querySelectorAll(TITLE_SELECTOR).forEach((el) => {
		// Loop-grid clones are inert snapshots — fillClone already trims them.
		if (el.closest('[data-aae-clone]')) {
			return;
		}
		const id = el.getAttribute('data-id');
		if (!id) {
			return;
		}
		const target = titleTarget(el);
		// Trim: the twig emits whitespace/indentation around the title text,
		// which would otherwise count against a char limit (PHP trims the raw
		// title, so the mirror must too).
		const current = (target.textContent || '').trim();

		// Track the FULL title on the node. If the current text isn't the trim
		// we last applied, Elementor re-rendered with fresh (full) text —
		// capture it as the new full title. Otherwise keep the stored one so a
		// LOOSER limit can re-expand the text.
		if (target.__aaeFullTitle === undefined || current !== target.__aaeLastApplied) {
			target.__aaeFullTitle = current;
		}

		const { by, n } = readTitleLimit(id);
		const desired = applyTitleLimit(target.__aaeFullTitle, by, n);
		if (current !== desired) {
			target.textContent = desired;
		}
		target.__aaeLastApplied = desired;
	});
}

/** Schedule a scan on the next animation frame (coalesces bursts). */
export function schedulePostTitleScan() {
	if (!state.postTitleLimitInstalled || state.postTitleRaf) {
		return;
	}
	state.postTitleRaf = requestAnimationFrame(() => {
		state.postTitleRaf = null;
		scan();
	});
}

export function installPostTitleLimit() {
	if (state.postTitleLimitInstalled) {
		return;
	}
	state.postTitleLimitInstalled = true;

	// The preview iframe document can exist before its <body>; gate on body and
	// only observe once (idempotent across in-place re-renders).
	const hookPreview = () => {
		const win = getPreviewWindow();
		const pdoc = win && win.document;
		if (pdoc && pdoc.body && !pdoc.__aaeTitleObserved) {
			pdoc.__aaeTitleObserved = true;
			new MutationObserver(schedulePostTitleScan).observe(pdoc.body, { childList: true, subtree: true });
		}
	};
	hookPreview();
	setInterval(hookPreview, 1500);
	schedulePostTitleScan();
}
