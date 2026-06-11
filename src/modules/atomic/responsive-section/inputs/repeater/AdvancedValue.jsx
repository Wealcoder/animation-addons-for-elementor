import { useState } from 'react';
import { Stack, Box, Typography, Autocomplete, TextField } from '@elementor/ui';

const MODE_OPTIONS = [
	{ value: 'static', label: 'Static Number' },
	{ value: 'random_range', label: 'Random Range' },
	{ value: 'random_array', label: 'Random Array' },
];

export function AdvancedValue({ value, onChange, config, disabled }) {
	// Parse current mode
	let currentMode = 'static';
	let rangeState = { min: config?.min || 0, max: config?.max || 100, step: config?.step || 1 };
	let arrayState = '0, 50, 100';
	if (typeof value === 'string') {
		if (value.startsWith('random([')) {
			currentMode = 'random_array';
			const match = value.match(/random\(\[(.*?)\]\)/);
			if (match) arrayState = match[1];
		} else if (value.startsWith('random(')) {
			currentMode = 'random_range';
			const match = value.match(/random\(([^,]+),\s*([^,]+)(?:,\s*([^)]+))?\)/);
			if (match) {
				rangeState.min = Number(match[1]) || 0;
				rangeState.max = Number(match[2]) || 100;
				if (match[3]) rangeState.step = Number(match[3]);
			}
		}
	}

	const handleModeChange = (newMode) => {
		if (newMode === 'static') {
			onChange(config?.min || 0);
		} else if (newMode === 'random_range') {
			onChange(`random(${rangeState.min}, ${rangeState.max}${rangeState.step ? `, ${rangeState.step}` : ''})`);
		} else if (newMode === 'random_array') {
			onChange(`random([${arrayState}])`);
		}
	};

	const updateRange = (k, v) => {
		const next = { ...rangeState, [k]: Number(v) };
		onChange(`random(${next.min}, ${next.max}${next.step ? `, ${next.step}` : ''})`);
	};

	const updateArray = (v) => {
		onChange(`random([${v}])`);
	};



	return (
		<Stack direction="column" spacing={2} sx={{ width: 320, p: 2 }}>
			<Box>
				<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
					Value Mode
				</Typography>
				<Autocomplete
					size="small"
					fullWidth
					disabled={disabled}
					options={MODE_OPTIONS}
					value={MODE_OPTIONS.find(o => o.value === currentMode) || MODE_OPTIONS[0]}
					isOptionEqualToValue={(opt, val) => opt.value === val?.value}
					getOptionLabel={(opt) => opt.label}
					onChange={(_, next) => handleModeChange(next ? next.value : 'static')}
					renderInput={(params) => <TextField {...params} size="small" />}
				/>
			</Box>

			{currentMode === 'static' && (
				<Typography variant="caption" color="text.secondary">
					Use the inline slider in the panel to adjust the static value.
				</Typography>
			)}

			{currentMode === 'random_range' && (
				<Stack direction="row" spacing={1}>
					<Box sx={{ flex: 1 }}>
						<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>Min</Typography>
						<TextField size="small" type="number" fullWidth value={rangeState.min} onChange={(e) => updateRange('min', e.target.value)} disabled={disabled} />
					</Box>
					<Box sx={{ flex: 1 }}>
						<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>Max</Typography>
						<TextField size="small" type="number" fullWidth value={rangeState.max} onChange={(e) => updateRange('max', e.target.value)} disabled={disabled} />
					</Box>
					<Box sx={{ flex: 1 }}>
						<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>Step (Optional)</Typography>
						<TextField size="small" type="number" fullWidth value={rangeState.step || ''} onChange={(e) => updateRange('step', e.target.value)} disabled={disabled} />
					</Box>
				</Stack>
			)}

			{currentMode === 'random_array' && (
				<Box>
					<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
						Comma-separated values (e.g. 0, 50, 100)
					</Typography>
					<TextField
						size="small"
						fullWidth
						value={arrayState}
						onChange={(e) => updateArray(e.target.value)}
						disabled={disabled}
						placeholder="0, 50, 100"
					/>
				</Box>
			)}
		</Stack>
	);
}
