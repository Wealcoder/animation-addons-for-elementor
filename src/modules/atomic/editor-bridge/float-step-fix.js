/* eslint-env browser */

import { rowFromLabel, originalLabelText } from './panel-rows';

/**
 * Elementor's atomic Number_Control ships with `step="1"` on its native input,
 * which makes the browser round typed decimals to integers on blur. Our schema
 * declares these props as float, and the React reader parses via Number() when
 * `shouldForceInt: false` — but the HTML constraint wins first.
 *
 * Fix: rewrite step="1" → step="any" on inputs of every label that should
 * accept decimals (Duration / Delay / Stagger / Transform-X/Y / Rotation Value).
 * Repeat field is integer-only so it's intentionally absent from the set.
 */

const FLOAT_LABEL_BASES = new Set([
	'Duration', 'Duration (ms)',
	'Delay', 'Delay (ms)',
	'Stagger',
	'Transform-X', 'Transform-Y',
	'Rotation Value',
]);

export function allowFloatStepsOnNumberInputs() {
	const labels = document.querySelectorAll('label, .MuiFormLabel-root, .MuiTypography-root');
	for (const label of labels) {
		const text = originalLabelText(label);
		if (!FLOAT_LABEL_BASES.has(text)) continue;

		const row = rowFromLabel(label);
		if (!row) continue;

		const inputs = row.querySelectorAll('input[type="number"]');
		for (const input of inputs) {
			if (input.getAttribute('step') !== 'any') {
				input.setAttribute('step', 'any');
			}
		}
	}
}
