/* eslint-env browser */

import * as React from 'react';
import {
	Autocomplete,
	Box,
	Chip,
	IconButton,
	Stack,
	Switch,
	TextField,
	Tooltip,
	Typography,
} from '@elementor/ui';

/**
 * Generic repeater input — rendered by ResponsiveRow when control === 'repeater'.
 *
 * Receives the full row array via `value` and emits a new array via
 * `onChange(nextRows)`. Each row is a plain JS object whose keys match the
 * `cells` config entries. No transformable nesting — the storage prop type
 * (Responsive_Json_Prop_Type) is permissive by design, so JS owns the row
 * contract entirely.
 *
 * Config (from the section's field entry):
 *   cells:       [{ bind, type, options?, placeholder?, min?, max?, step?, width? }, ...]
 *   addLabel:    'Add Property' (button text)
 *   rowDefaults: { property: 'opacity', value: '' }  (seed for new rows)
 *
 * Cell types: 'text' | 'number' | 'switch' | 'select' | 'multi-select'.
 */
export function RepeaterInput({
	value,
	onChange,
	disabled,
	cells = [],
	addLabel = 'Add Item',
	rowDefaults = {},
}) {
	const rows = Array.isArray(value) ? value : [];

	const writeRows = (nextRows) => onChange(nextRows);

	const addRow = () => writeRows([
		...rows,
		{ ...rowDefaults },
	]);

	const removeAt = (index) => writeRows(rows.filter((_, i) => i !== index));

	const updateRowCell = (index, cellBind, nextValue) => {
		const next = rows.slice();
		next[index] = { ...(next[index] || {}), [cellBind]: nextValue };
		writeRows(next);
	};

	return (
		<Stack direction="column" spacing={1} sx={{ width: '100%' }}>
			{rows.map((row, index) => {
				return (
					<Stack
						key={index}
						direction="row"
						spacing={1}
						alignItems="center"
						sx={{
							p: 1,
							border: 1,
							borderColor: 'divider',
							borderRadius: 1,
						}}
					>
						<Stack
							direction="row"
							spacing={1}
							alignItems="center"
							sx={{ flex: 1, minWidth: 0 }}
						>
							{cells.map((cellCfg) => {
								const Cell = CELL_COMPONENTS[cellCfg.type];
								if (!Cell) {
									return (
										<Box key={cellCfg.bind} sx={{ flex: 1 }}>
											<Typography variant="caption" color="error">
												Unknown cell: {cellCfg.type}
											</Typography>
										</Box>
									);
								}
								return (
									<Box key={cellCfg.bind} sx={{ flex: cellCfg.width || 1, minWidth: 0 }}>
										<Cell
											value={row?.[cellCfg.bind]}
											config={cellCfg}
											disabled={disabled}
											onChange={(next) => updateRowCell(index, cellCfg.bind, next)}
										/>
									</Box>
								);
							})}
						</Stack>

						<Tooltip title="Remove row">
							<span>
								<IconButton
									size="small"
									onClick={() => removeAt(index)}
									disabled={disabled}
									aria-label="Remove row"
								>
									×
								</IconButton>
							</span>
						</Tooltip>
					</Stack>
				);
			})}

			<Box>
				<IconButton
					size="small"
					color="primary"
					onClick={addRow}
					disabled={disabled}
					sx={{
						border: '1px dashed',
						borderRadius: 1,
						width: '100%',
						justifyContent: 'flex-start',
						px: 1.5,
						gap: 1,
					}}
				>
					<Typography variant="caption">+ {addLabel}</Typography>
				</IconButton>
			</Box>
		</Stack>
	);
}

/* ---------- cell renderers ---------- */

function TextCell({ value, onChange, disabled, config }) {
	return (
		<TextField
			size="tiny"
			fullWidth
			value={value ?? ''}
			placeholder={config.placeholder || ''}
			disabled={disabled}
			onChange={(e) => onChange(e.target.value)}
		/>
	);
}

function NumberCell({ value, onChange, disabled, config }) {
	const { min, max, step, placeholder } = config;
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

function SwitchCell({ value, onChange, disabled }) {
	return (
		<Switch
			size="small"
			checked={!!value}
			disabled={disabled}
			onChange={(_, checked) => onChange(checked)}
		/>
	);
}

function SelectCell({ value, onChange, disabled, config }) {
	const options = config.options || [];
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
					placeholder={config.placeholder || ''}
				/>
			)}
		/>
	);
}

function MultiSelectCell({ value, onChange, disabled, config }) {
	const options = config.options || [];
	const selected = Array.isArray(value) ? value : [];
	const valueAsObjects = selected.map(
		(v) => options.find((o) => o.value === v) || { value: v, label: String(v) }
	);
	return (
		<Autocomplete
			multiple
			size="tiny"
			disabled={disabled}
			options={options}
			value={valueAsObjects}
			isOptionEqualToValue={(opt, val) => opt.value === val.value}
			getOptionLabel={(opt) => opt.label || String(opt.value)}
			onChange={(_, next) => onChange(next.map((o) => o.value))}
			renderTags={(picked, getTagProps) =>
				picked.map((opt, index) => (
					<Chip
						{...getTagProps({ index })}
						key={opt.value}
						size="small"
						label={opt.label}
					/>
				))
			}
			renderInput={(params) => (
				<TextField
					{...params}
					size="tiny"
					placeholder={selected.length ? '' : (config.placeholder || '')}
				/>
			)}
			fullWidth
		/>
	);
}

const CELL_COMPONENTS = {
	text:           TextCell,
	number:         NumberCell,
	switch:         SwitchCell,
	select:         SelectCell,
	'multi-select': MultiSelectCell,
};
