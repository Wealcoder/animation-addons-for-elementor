/* eslint-env browser */

import * as React from 'react';
import { TextField } from '@elementor/ui';

/** Plain Text input. */
export function TextInput({ value, onChange, disabled, placeholder }) {
	return (
		<TextField
			size="tiny"
			fullWidth
			value={value ?? ''}
			placeholder={placeholder || ''}
			disabled={disabled}
			onChange={(e) => onChange(e.target.value)}
		/>
	);
}
