/* eslint-env browser */

import * as React from 'react';
import { useState, useEffect, useRef } from 'react';
import { Autocomplete, TextField, Switch, Stack, Typography } from '@elementor/ui';

/**
 * Custom LinkInput component for the responsive section.
 * Renders an autocomplete search box (AJAX WordPress page/post suggests) and a target blank switch.
 */
export function LinkInput({ value, onChange, disabled, placeholder }) {
	const destinationVal = typeof value === 'string' ? value : '';

	const [inputValue, setInputValue] = useState(destinationVal);
	const [options, setOptions] = useState([]);
	const [loading, setLoading] = useState(false);
	const searchTimeoutRef = useRef(null);

	// Sync local input value with destination value when destination value changes from outside
	useEffect(() => {
		setInputValue(destinationVal);
	}, [destinationVal]);

	// Fetch suggestions when input value changes
	useEffect(() => {
		if (!inputValue || inputValue.length < 2 || inputValue.includes('://') || inputValue.startsWith('//')) {
			setOptions([]);
			return;
		}

		if (searchTimeoutRef.current) {
			clearTimeout(searchTimeoutRef.current);
		}

		searchTimeoutRef.current = setTimeout(async () => {
			setLoading(true);
			try {
				const restRoot = window.wpApiSettings?.root || '/wp-json/';
				const nonce = window.wpApiSettings?.nonce;
				const url = `${restRoot}wp/v2/search?search=${encodeURIComponent(inputValue)}&per_page=10`;
				
				const response = await fetch(url, {
					headers: nonce ? { 'X-WP-Nonce': nonce } : {},
				});
				
				if (response.ok) {
					const data = await response.json();
					const formatted = data.map((item) => {
						const subtype = item.subtype || '';
						const typeLabel = subtype ? ` (${subtype.charAt(0).toUpperCase() + subtype.slice(1)})` : '';
						return {
							label: `${item.title}${typeLabel}`,
							value: item.url,
						};
					});
					setOptions(formatted);
				}
			} catch (err) {
				console.error('[AAE LinkInput] Error fetching search suggestions:', err);
			} finally {
				setLoading(false);
			}
		}, 300);

		return () => {
			if (searchTimeoutRef.current) {
				clearTimeout(searchTimeoutRef.current);
			}
		};
	}, [inputValue]);

	const updateLink = (url) => {
		onChange(url);
	};

	return (
		<Stack spacing={1.5} sx={{ width: '100%' }}>
			<Autocomplete
				freeSolo
				size="tiny"
				fullWidth
				disabled={disabled}
				loading={loading}
				options={options}
				value={destinationVal}
				inputValue={inputValue}
				getOptionLabel={(option) => {
					if (typeof option === 'string') return option;
					return option.label || option.value || '';
				}}
				isOptionEqualToValue={(option, val) => {
					const cmpVal = typeof val === 'object' ? val?.value : val;
					return option.value === cmpVal;
				}}
				onInputChange={(event, newInputValue, reason) => {
					if (reason === 'reset') return;
					setInputValue(newInputValue || '');
					updateLink(newInputValue || '');
				}}
				onChange={(event, newValue) => {
					let url = '';
					if (newValue) {
						if (typeof newValue === 'object') {
							url = newValue.value;
						} else {
							url = newValue;
						}
					}
					setInputValue(url);
					updateLink(url);
				}}
				ListboxProps={{ style: { maxHeight: 250 } }}
				renderInput={(params) => (
					<TextField
						{...params}
						size="tiny"
						placeholder={placeholder || 'https://your-link.com'}
					/>
				)}
			/>
			
		</Stack>
	);
}
