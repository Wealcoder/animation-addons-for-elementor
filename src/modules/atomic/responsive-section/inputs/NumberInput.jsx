/* eslint-env browser */

import * as React from 'react';
import { TextField } from '@elementor/ui';

/**
 * Plain Number input. Reading null shows placeholder; clearing emits null
 * (which our useResponsivePropValue maps to "inherit from parent BP").
 */
export function NumberInput({ value, onChange, disabled, placeholder, min, max, step }) {
	return (
		<TextField
			size="tiny"
			fullWidth
			type="number"
			value={value ?? ''}
			placeholder={placeholder || ''}
			disabled={disabled}
			onChange={(e) => {
				const raw = e.target.value;
				if (raw === '') return onChange(null);
				const num = Number(raw);
				onChange(Number.isFinite(num) ? num : null);
			}}
			inputProps={{ min, max, step }}
		/>
	);
}
