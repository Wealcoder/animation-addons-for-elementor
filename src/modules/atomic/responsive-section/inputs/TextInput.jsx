/* eslint-env browser */

import * as React from 'react';
import { TextField, Autocomplete } from '@elementor/ui';

/** Plain Text input. */
export function TextInput({ value, onChange, disabled, placeholder, datalist }) {
	const [inputValue, setInputValue] = React.useState(value ? String(value) : '');
	const [isFocused, setIsFocused] = React.useState(false);

	React.useEffect(() => {
		if (!isFocused) {
			setInputValue(value ? String(value) : '');
		}
	}, [value, isFocused]);

	if (datalist) {
		const options = datalist.map((item) => typeof item === 'object' ? String(item.value) : String(item));
		return (
			<Autocomplete
				freeSolo
				forcePopupIcon={true}
				size="tiny"
				fullWidth
				disabled={disabled}
				options={options}
				value={value ? String(value) : ''}
				inputValue={inputValue}
				onFocus={() => setIsFocused(true)}
				onBlur={() => setIsFocused(false)}
				onChange={(event, newValue) => {
					const val = newValue || '';
					setInputValue(val);
					onChange(val);
				}}
				onInputChange={(event, newInputValue, reason) => {
					if (reason === 'reset') return;
					setInputValue(newInputValue || '');
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
			value={isFocused ? inputValue : (value ?? '')}
			placeholder={placeholder || ''}
			disabled={disabled}
			onFocus={() => setIsFocused(true)}
			onBlur={() => setIsFocused(false)}
			onChange={(e) => {
				setInputValue(e.target.value);
				onChange(e.target.value);
			}}
		/>
	);
}
