/* eslint-env browser */

import { getSelectedContainer } from './helpers';
import {
	RESPONSIVE_SUFFIX_BY_MODE,
	LABEL_TO_BASE,
	humanLabelFor,
	currentDeviceMode,
	cascadedParentValue,
	readSetting,
	isEmptyValue,
} from './responsive-config';
import {
	rowFromLabel,
	originalLabelText,
	isSelectRow,
	inputFromRow,
	selectDisplayCell,
} from './panel-rows';

/**
 * Placeholder hints: when a non-desktop row's input is empty, show the
 * cascaded parent value as the input's native HTML placeholder. As soon as
 * the user types something, the browser hides the placeholder; clearing
 * the input brings it back. The stored prop stays null until explicitly set.
 *
 * Select rows can't carry an HTML placeholder, so we overlay an italic span
 * inside the visible MUI Select cell instead.
 */

/** Remove the in-field placeholder overlay from a select row. */
function clearInheritHint(row) {
	const cell = selectDisplayCell(row);
	if (!cell) return;
	const overlay = cell.querySelector(':scope > .aae-select-placeholder');
	if (overlay) overlay.remove();
}

/**
 * Overlay an italic placeholder string inside an *empty* MUI Select cell.
 * Reads like a native input placeholder; removed automatically when the user
 * picks a real value (MUI fills the cell with text nodes).
 */
function setInheritHint(row, hintText) {
	const cell = selectDisplayCell(row);
	if (!cell) return;

	// If the select already has a real value, MUI puts it in the cell as
	// (mostly) text nodes. MUI also inserts zero-width / non-breaking spaces
	// to keep line height — strip those before deciding "is the cell empty".
	const ownText = Array.from(cell.childNodes)
		.filter((n) => !(n.nodeType === 1 && n.classList?.contains('aae-select-placeholder')))
		.map((n) => (n.textContent || '').replace(/[​ \s]+/g, ''))
		.join('');

	if (ownText) {
		const stale = cell.querySelector(':scope > .aae-select-placeholder');
		if (stale) stale.remove();
		return;
	}

	let overlay = cell.querySelector(':scope > .aae-select-placeholder');
	if (!overlay) {
		overlay = document.createElement('span');
		overlay.className = 'aae-select-placeholder';
		overlay.setAttribute('aria-hidden', 'true');
		overlay.style.cssText = [
			'color: rgba(255, 255, 255, 0.4)',
			'font-style: italic',
			'pointer-events: none',
			'user-select: none',
		].join(';');
		if (getComputedStyle(cell).position === 'static') {
			cell.style.position = 'relative';
		}
		cell.appendChild(overlay);
	}
	overlay.textContent = hintText;
}

/**
 * Scan every per-breakpoint row whose label matches the active device mode
 * and set its placeholder/hint to the inherited parent value (or clear it
 * when the row already has its own value).
 */
export function applyResponsivePlaceholders() {
	const mode = currentDeviceMode();
	if (mode === 'desktop') return; // Desktop rows don't inherit.

	const activeSuffix = RESPONSIVE_SUFFIX_BY_MODE[mode];
	if (!activeSuffix) return;

	const container = getSelectedContainer();
	if (!container?.settings) return;

	const labels = document.querySelectorAll('label, .MuiFormLabel-root, .MuiTypography-root');
	for (const label of labels) {
		const text = originalLabelText(label);
		if (!text.endsWith(activeSuffix)) continue;

		const baseLabel = text.slice(0, -activeSuffix.length).trim();
		const base      = LABEL_TO_BASE[baseLabel];
		if (!base) continue;

		const row = rowFromLabel(label);
		if (!row) continue;

		const ownKey   = base + '_' + mode;
		const ownValue = readSetting(container, ownKey);
		const hasOwn   = !isEmptyValue(ownValue);

		const parentVal = cascadedParentValue(container, base, mode);

		// Select rows take precedence — MUI Selects also contain a hidden <input>
		// for form data which would otherwise be misdetected as a text input.
		if (isSelectRow(row)) {
			if (hasOwn || parentVal === undefined) {
				clearInheritHint(row);
			} else {
				setInheritHint(row, humanLabelFor(base, parentVal));
			}
			continue;
		}

		// Text / number / textarea — native HTML placeholder.
		const input = inputFromRow(row);
		if (input) {
			if (parentVal === undefined) {
				input.removeAttribute('placeholder');
			} else {
				input.setAttribute('placeholder', String(parentVal));
			}
			clearInheritHint(row); // never both
		}
	}
}
