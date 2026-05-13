/* eslint-env browser */

import * as React from 'react';
import { Autocomplete, TextField } from '@elementor/ui';

/**
 * Searchable Select input. Receives the active-breakpoint scalar via
 * `value`, notifies via `onChange(nextValue)`. Listbox is capped at
 * 300px so long option lists scroll instead of overflowing the panel.
 */
export function SelectInput({ value, onChange, disabled, options = [], placeholder }) {
	const selected = options.find((o) => o.value === value) || null;
	return (
		<Autocomplete
			size="tiny"
			fullWidth
			disabled={disabled}
			options={options}
			value={selected}
			isOptionEqualToValue={(opt, val) => opt.value === val.value}
			getOptionLabel={(opt) => opt.label || String(opt.value)}
			onChange={(_, next) => onChange(next ? next.value : '')}
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
