/* eslint-env browser */

import * as React from 'react';
import { Stack, Slider, TextField, ToggleButtonGroup, ToggleButton, Box } from '@elementor/ui';

export function SliderInput({
	value,
	onChange,
	disabled,
	placeholder,
	min = 0,
	max = 100,
	step = 1,
	units = [], // e.g. ['px', '%'] or ['s', 'ms']
	defaultUnit = '',
}) {
	// Parse value into size (number) and unit (string)
	let size = '';
	let unit = '';

	if (value && typeof value === 'object') {
		size = value.size !== undefined ? value.size : (value.value !== undefined ? value.value : '');
		unit = value.unit || defaultUnit || (units.length > 0 ? units[0] : '');
	} else {
		size = value !== null && value !== undefined ? value : '';
		unit = defaultUnit || (units.length > 0 ? units[0] : '');
	}

	const numericSize = size === '' ? '' : Number(size);

	const handleSizeChange = (newSize) => {
		if (newSize === '') {
			onChange(null);
			return;
		}

		const num = Number(newSize);
		const finalSize = Number.isFinite(num) ? num : null;

		if (units.length > 0) {
			onChange({
				size: finalSize,
				unit: unit || units[0]
			});
		} else {
			onChange(finalSize);
		}
	};

	const handleUnitChange = (newUnit) => {
		if (!newUnit) return;
		onChange({
			size: numericSize === '' ? null : numericSize,
			unit: newUnit
		});
	};

	// Determine min/max/step based on selected unit if they are dynamic (or default to props)
	const currentMin = typeof min === 'object' ? (min[unit] ?? min.default ?? 0) : min;
	const currentMax = typeof max === 'object' ? (max[unit] ?? max.default ?? 100) : max;
	const currentStep = typeof step === 'object' ? (step[unit] ?? step.default ?? 1) : step;

	return (
		<Stack direction="column" gap={1} sx={{ width: '100%' }}>
			<Stack direction="row" alignItems="center" gap={1.5} sx={{ width: '100%' }}>
				{/* The Slider Bar */}
				<Box sx={{ flex: 1, minWidth: 0, px: 1 }}>
					<Slider
						value={typeof numericSize === 'number' ? numericSize : currentMin}
						min={currentMin}
						max={currentMax}
						step={currentStep}
						disabled={disabled}
						onChange={(e, val) => handleSizeChange(val)}
						size="small"
					/>
				</Box>

				{/* Precise Value Text Field */}
				<TextField
					size="tiny"
					type="number"
					value={size}
					placeholder={placeholder || ''}
					disabled={disabled}
					onChange={(e) => handleSizeChange(e.target.value)}
					inputProps={{
						min: currentMin,
						max: currentMax,
						step: currentStep,
						style: { textAlign: 'center', padding: '4px 6px' }
					}}
					sx={{ width: 65, flexShrink: 0 }}
				/>

				{/* Optional Unit Selector */}
				{units.length > 0 && (
					<ToggleButtonGroup
						value={unit}
						exclusive
						onChange={(e, val) => handleUnitChange(val)}
						disabled={disabled}
						size="small"
						sx={{ flexShrink: 0, height: 26 }}
					>
						{units.map((u) => (
							<ToggleButton
								key={u}
								value={u}
								sx={{
									px: 0.8,
									py: 0,
									fontSize: '10px',
									textTransform: 'none',
									lineHeight: 1,
									height: '100%',
									minWidth: 26
								}}
							>
								{u}
							</ToggleButton>
						))}
					</ToggleButtonGroup>
				)}
			</Stack>
		</Stack>
	);
}
