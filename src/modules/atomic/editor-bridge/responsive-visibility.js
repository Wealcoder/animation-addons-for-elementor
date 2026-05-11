/* eslint-env browser */

import { RESPONSIVE_SUFFIX_BY_MODE, currentDeviceMode } from './responsive-config';
import { rowFromLabel, matchSuffix, stripVisibleSuffix, originalLabelText, panelLabels } from './panel-rows';

/**
 * Per-breakpoint row visibility.
 *
 * Convention: each responsive control's label ends with " (Tablet)" /
 * " (Mobile)" / etc.; desktop controls have no suffix. We read the editor's
 * current device mode from elementor.channels.deviceMode and toggle the rows
 * accordingly. Also strips the suffix from the visible label so users see
 * "Delay" instead of "Delay (Mobile)" — the original is cached so subsequent
 * scans still match the row to its breakpoint.
 */
export function applyResponsiveVisibility(labels = panelLabels()) {
	const mode         = currentDeviceMode();
	const activeSuffix = RESPONSIVE_SUFFIX_BY_MODE[mode] || null; // null = desktop

	// Pass 1: collect base labels that have any breakpoint variant, so we can
	// hide the corresponding desktop row when a non-desktop mode is active.
	const baseLabelsWithVariants = new Set();
	for (const label of labels) {
		const text   = originalLabelText(label);
		const suffix = matchSuffix(text);
		if (suffix) {
			baseLabelsWithVariants.add(text.slice(0, -suffix.length).trim());
		}
	}

	// Pass 2: toggle every row's display based on the active mode.
	for (const label of labels) {
		const text   = originalLabelText(label);
		const suffix = matchSuffix(text);
		const row    = rowFromLabel(label);
		if (!row) continue;

		if (suffix) {
			// Per-breakpoint row — show only when it matches the active mode,
			// and strip the suffix from the visible label.
			stripVisibleSuffix(label, text, suffix);
			row.style.display = (suffix === activeSuffix) ? '' : 'none';
		} else if (mode !== 'desktop' && baseLabelsWithVariants.has(text)) {
			// Desktop row of a setting that has variants — hide when off-desktop.
			row.style.display = 'none';
		} else if (row.style.display === 'none' && baseLabelsWithVariants.has(text)) {
			// Reset rows we hid in a previous pass.
			row.style.display = '';
		}
	}
}
