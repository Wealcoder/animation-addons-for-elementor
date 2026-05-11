/* eslint-env browser */

import { ALL_SUFFIXES } from './responsive-config';

/**
 * DOM utilities for inspecting Elementor V4 atomic panel control rows.
 *
 * Atomic widgets render each control as:
 *   <span data-type="settings-field">
 *     <div class="MuiBox-root">         ← display: none flips here when hidden
 *       <div class="MuiStack-root"><label/></div>
 *       <span class="eui-..."> ...input/select... </span>
 *     </div>
 *   </span>
 *
 * These helpers are used by every panel-touching module (visibility,
 * placeholders, float-step fix, play-button) so they share one mental model.
 */

/**
 * Walk up from a label DOM node to its full row container — the inner
 * MuiBox-root that Elementor toggles for show/hide. Falls back to the
 * legacy selectors for non-atomic panels.
 */
export function rowFromLabel(label) {
	const fieldWrap = label.closest('[data-type="settings-field"]');
	if (fieldWrap) {
		return fieldWrap.querySelector(':scope > .MuiBox-root') || fieldWrap;
	}
	return label.closest('[data-control-id]')
		|| label.closest('.MuiFormControl-root')
		|| label.closest('.MuiBox-root')
		|| label.parentElement;
}

/** Find which breakpoint suffix (if any) a label ends with. null = desktop. */
export function matchSuffix(text) {
	for (const suffix of ALL_SUFFIXES) {
		if (text.endsWith(suffix)) return suffix;
	}
	return null;
}

/* =====================================================================
 * Original-label caching
 *
 * PHP emits labels like "Animation (Laptop)" because the JS needs the
 * suffix to identify which breakpoint a row belongs to. The visibility
 * module strips the suffix from the visible text but caches the original
 * (in WeakMap + data-attr) so subsequent scans can still match.
 * =================================================================== */

const originalLabelCache = new WeakMap();

export function stripVisibleSuffix(label, originalText, suffix) {
	originalLabelCache.set(label, originalText);
	if (!label.dataset.aaeOrigLabel) {
		label.dataset.aaeOrigLabel = originalText;
	}
	const cleaned = originalText.slice(0, -suffix.length).trimEnd();
	if (label.textContent !== cleaned) {
		label.textContent = cleaned;
	}
}

/** Return the original (suffix-bearing) label text — what PHP emitted. */
export function originalLabelText(label) {
	const fromMap = originalLabelCache.get(label);
	if (fromMap) return fromMap;
	if (label.dataset.aaeOrigLabel) return label.dataset.aaeOrigLabel;
	return (label.textContent || '').trim();
}

/* =====================================================================
 * Row-content sniffing
 * =================================================================== */

/** True if the row contains a select-like control (MUI / native / aria combobox). */
export function isSelectRow(row) {
	return !!(
		row.querySelector('[role="combobox"]')
		|| row.querySelector('[role="listbox"]')
		|| row.querySelector('.MuiSelect-select')
		|| row.querySelector('.MuiSelect-root')
		|| row.querySelector('select')
	);
}

/**
 * Find an editable input/textarea inside a control row. Skips hidden /
 * checkbox / radio types AND skips inputs that are part of a MUI Select
 * (they're hidden form-data carriers, not what the user types into).
 */
export function inputFromRow(row) {
	const candidates = row.querySelectorAll('input, textarea');
	for (const el of candidates) {
		if (el.tagName === 'TEXTAREA') return el;

		const t = (el.getAttribute('type') || 'text').toLowerCase();
		if (t === 'checkbox' || t === 'hidden' || t === 'radio') continue;

		// Skip MUI Select's internal native input.
		if (el.classList.contains('MuiSelect-nativeInput')) continue;
		if (el.closest('.MuiSelect-root, .MuiSelect-select')) continue;
		if (el.getAttribute('aria-hidden') === 'true') continue;

		return el;
	}
	return null;
}

/**
 * The MUI Select's visible "value" cell — the box that shows the selected
 * option (or is empty when nothing is picked). Used by placeholder overlay.
 */
export function selectDisplayCell(row) {
	return row.querySelector('.MuiSelect-select')
		|| row.querySelector('[role="combobox"]')
		|| null;
}
