/* eslint-env browser */

import * as React from 'react';
import { Autocomplete, TextField } from '@elementor/ui';

import { getSelectedContainer } from '../../editor-bridge/helpers';
import { replayInPreview } from '../../editor-bridge/settings-bridge';
import { applySettingsToDom } from '../../editor-bridge/settings-bridge';

/**
 * Searchable Select input. Receives the active-breakpoint scalar via
 * `value`, notifies via `onChange(nextValue)`. Listbox is capped at
 * 300px so long option lists scroll instead of overflowing the panel.
 */
export function SelectInput({ value, onChange, disabled, options = [], placeholder, play_group = '' }) {
	const selected = options.find((o) => o.value === value) || null;

	const handleChange = (_, next) => {
		onChange(next ? next.value : '');

		if (play_group) {
			setTimeout(() => {
				const container = getSelectedContainer();
				if (!container) return;

				let dom_settings = applySettingsToDom(container, play_group);

				if (!replayInPreview(dom_settings.target, play_group)) {
					// eslint-disable-next-line no-console
					console.warn('[AAE] Play: animation runtime (aaeAtomicAnimations) not available in preview.');
				}
			}, 150);
		}
	};

	return (
		<Autocomplete
			size="tiny"
			fullWidth
			disabled={disabled}
			options={options}
			value={selected}
			isOptionEqualToValue={(opt, val) => opt.value === val.value}
			getOptionLabel={(opt) => opt.label || String(opt.value)}
			onChange={handleChange}
			ListboxProps={{ style: { maxHeight: 300 } }}
			renderInput={(params) => (
				<TextField
					{...params}
					size="tiny"
					placeholder={placeholder || ''}
				/>
			)}
		/>
	);
}
