/* eslint-env browser */

/**
 * Post Excerpt limit — live editor preview mirror.
 *
 * The AAE Post Excerpt widget resolves its text in PHP (get_atomic_settings():
 * limit_by = word|char + limit + more). The editor canvas renders the widget
 * CLIENT-side (twig), where that PHP never runs — the canvas would show the
 * schema default (the sample post's untrimmed excerpt) whatever the panel
 * said, while the front end was always right. Same shape, same fix as
 * post-title-limit.js:
 *
 *   - scan() finds every real (non-clone) excerpt widget, remembers its FULL
 *     text on the node, and rewrites the visible text per the widget's
 *     CURRENT settings.
 *   - a MutationObserver re-runs the scan on every re-render, and
 *     command-bridge.js schedules one on `document/elements/settings`.
 *
 * Loop-grid CLONES go through applyExcerptLimit() from fillClone, fed with
 * the per-post `excerpt` / `excerpt_full` pair `ajax_loop_post_data` returns
 * — the same two sources the PHP trims from, so the canvas and the page trim
 * the same words.
 *
 * The line clamp needs no mirror: the twig emits it inline from the settings
 * and the canvas re-renders on a settings change.
 */

import { state } from './state.js';
import { getPreviewWindow } from './preview.js';

const EXCERPT_SELECTOR = '[data-widget_type^="e-aae-a-post-excerpt"][data-id]';

/** Unwrap an atomic prop value ({ $$type, value } | scalar) to a scalar. */
function propValue(v) {
	return v && typeof v === 'object' && 'value' in v ? v.value : v;
}

/** Read the limit settings of an excerpt widget from its editor container. */
export function readExcerptLimit(id) {
	try {
		const c = window.elementor?.getContainer?.(id);
		const get = (k, d) => {
			const val = propValue(c?.settings?.get?.(k));
			return val === undefined || val === null || val === '' ? d : val;
		};
		// Fallbacks mirror the PHP schema defaults (limit_by 'line', limit 3,
		// more '…') so an untouched widget previews exactly like the front end.
		return {
			by: get('limit_by', 'line'),
			n: parseInt(get('limit', 3), 10) || 3,
			more: String(get('more', '…')),
		};
	} catch (e) {
		return { by: 'line', n: 3, more: '…' };
	}
}

/**
 * JS mirror of AAE_A_Post_Excerpt::trim(). Word: wp_trim_words. Char: cut at
 * the limit, stepping back to the last space when one sits inside the last
 * fifth of the allowance, then trailing punctuation dropped — the same
 * boundary rule as the PHP, so a canvas card ends on the same word.
 */
export function applyExcerptLimit(text, by, n, more) {
	const full = String(text || '');
	const ending = more === undefined ? '…' : String(more);
	if (!(n > 0)) {
		return full;
	}
	if (by === 'word') {
		const words = full.trim().split(/\s+/).filter(Boolean);
		return words.length > n ? words.slice(0, n).join(' ') + ending : full;
	}
	if (by === 'char') {
		const chars = Array.from(full);
		if (chars.length <= n) {
			return full;
		}
		let cut = chars.slice(0, n).join('');
		if (!/\s/.test(chars[n])) {
			const lastSpace = cut.lastIndexOf(' ');
			if (lastSpace !== -1 && Array.from(cut.slice(0, lastSpace)).length >= Math.floor(n * 0.8)) {
				cut = cut.slice(0, lastSpace);
			}
		}
		return cut.replace(/[\s,;:]+$/, '') + ending;
	}
	return full;
}

/**
 * The text a post's excerpt widget shows under a given limit, from the pair
 * the editor endpoints return: `excerpt` for none / line, `excerpt_full` for
 * a word / char trim (falling back to `excerpt` when the full text is absent
 * — an older response, or the widget switched off server-side).
 */
export function excerptForPost(post, by, n, more) {
	const trimming = by === 'word' || by === 'char';
	const source = trimming ? (post.excerpt_full || post.excerpt || '') : (post.excerpt || '');
	return applyExcerptLimit(source, by, n, more);
}

/** The node whose text is the excerpt (the widget root IS the tag). */
function excerptTarget(el) {
	return el.querySelector('p,div,span') || el;
}

function scan() {
	const win = getPreviewWindow();
	const doc = win && win.document;
	if (!doc) {
		return;
	}
	doc.querySelectorAll(EXCERPT_SELECTOR).forEach((el) => {
		// Loop-grid clones are inert snapshots — fillClone already trims them.
		if (el.closest('[data-aae-clone]')) {
			return;
		}
		const id = el.getAttribute('data-id');
		if (!id) {
			return;
		}
		const target = excerptTarget(el);
		const current = (target.textContent || '').trim();

		// Track the FULL text on the node. If the current text isn't the trim
		// we last applied, Elementor re-rendered with fresh (full) text —
		// capture it as the new full text. Otherwise keep the stored one so a
		// LOOSER limit can re-expand the text.
		if (target.__aaeFullExcerpt === undefined || current !== target.__aaeLastApplied) {
			target.__aaeFullExcerpt = current;
		}

		const { by, n, more } = readExcerptLimit(id);
		const desired = applyExcerptLimit(target.__aaeFullExcerpt, by, n, more);
		if (current !== desired) {
			target.textContent = desired;
		}
		target.__aaeLastApplied = desired;
	});
}

/** Schedule a scan on the next animation frame (coalesces bursts). */
export function schedulePostExcerptScan() {
	if (!state.postExcerptLimitInstalled || state.postExcerptRaf) {
		return;
	}
	state.postExcerptRaf = requestAnimationFrame(() => {
		state.postExcerptRaf = null;
		scan();
	});
}

export function installPostExcerptLimit() {
	if (state.postExcerptLimitInstalled) {
		return;
	}
	state.postExcerptLimitInstalled = true;

	const hookPreview = () => {
		const win = getPreviewWindow();
		const pdoc = win && win.document;
		if (pdoc && pdoc.body && !pdoc.__aaeExcerptObserved) {
			pdoc.__aaeExcerptObserved = true;
			new MutationObserver(schedulePostExcerptScan).observe(pdoc.body, { childList: true, subtree: true });
		}
	};
	hookPreview();
	setInterval(hookPreview, 1500);
	schedulePostExcerptScan();
}
