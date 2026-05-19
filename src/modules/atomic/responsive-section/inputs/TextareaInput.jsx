/* eslint-env browser */

import * as React from 'react';
import { TextField } from '@elementor/ui';

export function TextareaInput({ value, onChange, disabled, placeholder }) {
	return (
		<TextField
			value={value ?? ''}
			onChange={(e) => onChange(e.target.value)}
			disabled={disabled}
			placeholder={placeholder || ''}
			size="small"
			fullWidth
			multiline
			minRows={3}
			maxRows={8}
		/>
	);
}
