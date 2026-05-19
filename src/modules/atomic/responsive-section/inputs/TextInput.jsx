/* eslint-env browser */

import * as React from 'react';
import { TextField, Autocomplete } from '@elementor/ui';

/** Plain Text input. */
export function TextInput({ value, onChange, disabled, placeholder, datalist }) {
	if (datalist) {
		const options = datalist.map((item) => typeof item === 'object' ? String(item.value) : String(item));
		return (
			<Autocomplete
				freeSolo
				size="tiny"
				fullWidth
				disabled={disabled}
				options={options}
				value={value ? String(value) : ''}
				onChange={(event, newValue) => {
					onChange(newValue || '');
				}}
				onInputChange={(event, newInputValue) => {
					onChange(newInputValue || '');
				}}
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
