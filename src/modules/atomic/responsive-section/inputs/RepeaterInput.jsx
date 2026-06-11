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
	Popover,
	Slider,
	InputAdornment,
	styled,
} from '@elementor/ui';
import { HexColorPicker } from 'react-colorful';

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
	settings,
	activeBp,
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
		const oldRow = next[index] || {};
		const newRow = { ...oldRow, [cellBind]: nextValue };
		
		if (cellBind === 'property' && oldRow.property !== nextValue) {
			const getFieldType = (prop) => {
				if (!prop) return 'text';
				const p = String(prop).toLowerCase().replace(/[\s-]/g, '');
				if (['border', 'borderradius', 'boxshadow', 'textshadow', 'clippath', 'force3d', 'transformorigin', 'yoyo', 'margin', 'padding', 'filter', 'backdropfilter', 'color', 'background', 'backgroundcolor', 'bordercolor', 'outlinecolor', 'fill', 'stroke', 'outlinewidth', 'outlineoffset', 'overflow', 'overflowx', 'overflowy', 'mixblendmode', 'stagger', 'overwrite'].includes(p)) return p;
				return 'text';
			};
			if (getFieldType(oldRow.property) !== getFieldType(nextValue)) {
				newRow.value = '';
			}
		}
		
		next[index] = newRow;
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
								if (typeof cellCfg.when === 'function' && !cellCfg.when(settings, activeBp)) {
									return null;
								}
								
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
											rows={rows}
											index={index}
											onRowsChange={writeRows}
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

import { Border } from './repeater/Border';
import { BorderRadius } from './repeater/BorderRadius';

function cssToBorderObj(cssStr) {
	if (!cssStr || typeof cssStr !== 'string') return null;
	const parts = cssStr.split(' ');
	if (parts.length >= 3) {
		const width = parts[0].replace(/px/g, '');
		const style = parts[1];
		const color = parts.slice(2).join(' ');
		return {
			style,
			width: { top: width, right: width, bottom: width, left: width },
			color,
		};
	}
	return null;
}

function borderObjToCss(obj) {
	if (!obj || !obj.style || obj.style === 'none' || obj.style === '') return '';
	return `${obj.width?.top || 0}px ${obj.style} ${obj.color || '#000000'}`;
}

function BorderPopupCell({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = React.useState(null);
	
	const handleOpen = (e) => {
		if (!disabled) setAnchorEl(e.currentTarget);
	};
	const handleClose = () => setAnchorEl(null);
	
	const borderObj = cssToBorderObj(value) || {};
	
	const handleBorderChange = (newObj) => {
		onChange(borderObjToCss(newObj));
	};
	
	return (
		<>
			<TextField
				size="tiny"
				fullWidth
				value={value ?? ''}
				placeholder="Click for settings"
				disabled={disabled}
				onClick={handleOpen}
				InputProps={{ readOnly: true, style: { cursor: 'pointer' } }}
			/>
			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={handleClose}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2, width: 250 }}>
					<Border value={borderObj} onChange={handleBorderChange} />
				</Box>
			</Popover>
		</>
	);
}

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

import { TextShadowInput } from './TextShadowInput';

function TextShadowPopupCell({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = React.useState(null);
	
	const handleOpen = (e) => {
		if (!disabled) setAnchorEl(e.currentTarget);
	};
	const handleClose = () => setAnchorEl(null);
	
	return (
		<>
			<TextField
				size="tiny"
				fullWidth
				value={value ?? ''}
				placeholder="Click for settings"
				disabled={disabled}
				onClick={handleOpen}
				InputProps={{ readOnly: true, style: { cursor: 'pointer' } }}
			/>
			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={handleClose}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2, width: 320, maxHeight: 450, overflowY: 'auto' }}>
					<TextShadowInput value={value} onChange={onChange} disabled={disabled} />
				</Box>
			</Popover>
		</>
	);
}

import { BoxShadow } from './repeater/BoxShadow';

function BoxShadowPopupCell({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = React.useState(null);
	
	const handleOpen = (e) => {
		if (!disabled) setAnchorEl(e.currentTarget);
	};
	const handleClose = () => setAnchorEl(null);
	
	return (
		<>
			<TextField
				size="tiny"
				fullWidth
				value={value ?? ''}
				placeholder="Click for settings"
				disabled={disabled}
				onClick={handleOpen}
				InputProps={{ readOnly: true, style: { cursor: 'pointer' } }}
			/>
			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={handleClose}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2, width: 340, maxHeight: 450, overflowY: 'auto' }}>
					<BoxShadow value={value} onChange={onChange} disabled={disabled} />
				</Box>
			</Popover>
		</>
	);
}

import { ClipPath } from './repeater/ClipPath';

function ClipPathPopupCell({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = React.useState(null);
	
	const handleOpen = (e) => {
		if (!disabled) setAnchorEl(e.currentTarget);
	};
	const handleClose = () => setAnchorEl(null);
	
	return (
		<>
			<TextField
				size="tiny"
				fullWidth
				value={value ?? ''}
				placeholder="Click for settings"
				disabled={disabled}
				onClick={handleOpen}
				InputProps={{ readOnly: true, style: { cursor: 'pointer' } }}
			/>
			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={handleClose}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2, width: 280, maxHeight: 450, overflowY: 'auto' }}>
					<ClipPath value={value} onChange={onChange} disabled={disabled} />
				</Box>
			</Popover>
		</>
	);
}

import { Filter } from './repeater/Filter';

function FilterPopupCell({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = React.useState(null);
	
	const handleOpen = (e) => {
		if (!disabled) setAnchorEl(e.currentTarget);
	};
	const handleClose = () => setAnchorEl(null);
	
	return (
		<>
			<TextField
				size="tiny"
				fullWidth
				value={value ?? ''}
				placeholder="Click for settings"
				disabled={disabled}
				onClick={handleOpen}
				InputProps={{ readOnly: true, style: { cursor: 'pointer' } }}
			/>
			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={handleClose}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2 }}>
					<Filter value={value} onChange={onChange} disabled={disabled} onClose={handleClose} />
				</Box>
			</Popover>
		</>
	);
}

import { TransformOrigin } from './repeater/TransformOrigin';

function TransformOriginPopupCell({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = React.useState(null);
	
	const handleOpen = (e) => {
		if (!disabled) setAnchorEl(e.currentTarget);
	};
	const handleClose = () => setAnchorEl(null);
	
	return (
		<>
			<TextField
				size="tiny"
				fullWidth
				value={value ?? ''}
				placeholder="Click for settings"
				disabled={disabled}
				onClick={handleOpen}
				InputProps={{ readOnly: true, style: { cursor: 'pointer' } }}
			/>
			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={handleClose}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2, width: 280 }}>
					<TransformOrigin value={value} onChange={onChange} disabled={disabled} />
				</Box>
			</Popover>
		</>
	);
}

import { Stagger } from './repeater/Stagger';

function StaggerPopupCell({ value, onChange, disabled }) {
	return <Stagger value={value} onChange={onChange} disabled={disabled} />;
}

import { Dimensions } from './repeater/Dimensions';

function DimensionsPopupCell({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = React.useState(null);
	
	const handleOpen = (e) => {
		if (!disabled) setAnchorEl(e.currentTarget);
	};
	const handleClose = () => setAnchorEl(null);
	
	return (
		<>
			<TextField
				size="tiny"
				fullWidth
				value={value ?? ''}
				placeholder="Click for settings"
				disabled={disabled}
				onClick={handleOpen}
				InputProps={{ readOnly: true, style: { cursor: 'pointer' } }}
			/>
			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={handleClose}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2, width: 280 }}>
					<Dimensions value={value} onChange={onChange} disabled={disabled} />
				</Box>
			</Popover>
		</>
	);
}

function DynamicValueCell({ value, onChange, disabled, config, rows, index, onRowsChange }) {
	const row = rows[index] || {};
	// Strip spaces and hyphens to normalize 'Border Radius', 'border-radius', and 'borderRadius' into 'borderradius'
	const prop = typeof row.property === 'string' ? row.property.toLowerCase().replace(/[\s-]/g, '') : '';
	
	if (prop === 'margin' || prop === 'padding') {
		return <DimensionsPopupCell value={value} onChange={onChange} disabled={disabled} />;
	}
	if (prop === 'stagger') {
		return <StaggerPopupCell value={value} onChange={onChange} disabled={disabled} />;
	}
	if (prop === 'border') {
		return <BorderPopupCell value={value} onChange={onChange} disabled={disabled} />;
	}
	if (prop === 'borderradius') {
		return <BorderRadius value={value} onChange={onChange} disabled={disabled} />;
	}
	if (prop === 'boxshadow') {
		return <BoxShadowPopupCell value={value} onChange={onChange} disabled={disabled} />;
	}
	if (prop === 'textshadow') {
		return <TextShadowPopupCell value={value} onChange={onChange} disabled={disabled} />;
	}
	if (prop === 'clippath') {
		return <ClipPathPopupCell value={value} onChange={onChange} disabled={disabled} />;
	}
	
	if (['color', 'background', 'backgroundcolor', 'bordercolor', 'outlinecolor', 'fill', 'stroke'].includes(prop)) {
		return <ColorCell value={value} onChange={onChange} disabled={disabled} />;
	}
	
	const sliderConfigs = {
		x: { min: -1000, max: 1000, step: 1 },
		y: { min: -1000, max: 1000, step: 1 },
		z: { min: -1000, max: 1000, step: 1 },
		zindex: { min: -1000, max: 1000, step: 1 },
		opacity: { min: 0, max: 1, step: 0.1 },
		autoalpha: { min: 0, max: 1, step: 0.1 },
		scale: { min: 0, max: 5, step: 0.1 },
		scalex: { min: 0, max: 5, step: 0.1 },
		scaley: { min: 0, max: 5, step: 0.1 },
		rotate: { min: -360, max: 360, step: 1 },
		rotation: { min: -360, max: 360, step: 1 },
		rotatex: { min: -360, max: 360, step: 1 },
		rotatey: { min: -360, max: 360, step: 1 },
		rotationx: { min: -360, max: 360, step: 1 },
		rotationy: { min: -360, max: 360, step: 1 },
		skewx: { min: -180, max: 180, step: 1 },
		skewy: { min: -180, max: 180, step: 1 },
		duration: { min: 0, max: 10, step: 0.1 },
		delay: { min: 0, max: 10, step: 0.1 },
		repeatdelay: { min: 0, max: 10, step: 0.1 },
		xpercent: { min: -100, max: 100, step: 1 },
		ypercent: { min: -100, max: 100, step: 1 },
		strokewidth: { min: 0, max: 20, step: 1 },
		repeat: { min: -1, max: 10, step: 1 },
		outlinewidth: { min: 0, max: 50, step: 1 },
		outlineoffset: { min: -50, max: 50, step: 1 },
	};

	if (sliderConfigs[prop]) {
		return <SliderCell value={value} onChange={onChange} disabled={disabled} config={sliderConfigs[prop]} />;
	}

	if (prop === 'filter' || prop === 'backdropfilter') {
		return <FilterPopupCell value={value} onChange={onChange} disabled={disabled} />;
	}
	if (prop === 'transformorigin') {
		return <TransformOriginPopupCell value={value} onChange={onChange} disabled={disabled} />;
	}
	if (prop === 'yoyo') {
		return <SwitchCell value={value} onChange={onChange} disabled={disabled} />;
	}
	
	if (['force3d', 'overwrite'].includes(prop)) {
		return <SelectCell 
			value={value} 
			onChange={onChange} 
			disabled={disabled} 
			config={{ 
				options: [
					{ value: 'auto', label: 'Auto' },
					{ value: 'true', label: 'True' },
					{ value: 'false', label: 'False' }
				],
				freeSolo: false
			}} 
		/>;
	}

	if (['overflow', 'overflowx', 'overflowy'].includes(prop)) {
		return <SelectCell 
			value={value} 
			onChange={onChange} 
			disabled={disabled} 
			config={{ 
				options: [
					{ value: 'visible', label: 'Visible' },
					{ value: 'hidden', label: 'Hidden' },
					{ value: 'clip', label: 'Clip' },
					{ value: 'scroll', label: 'Scroll' },
					{ value: 'auto', label: 'Auto' }
				],
				freeSolo: true
			}} 
		/>;
	}

	if (prop === 'backfacevisibility') {
		return <SelectCell 
			value={value} 
			onChange={onChange} 
			disabled={disabled} 
			config={{ 
				options: [
					{ value: 'visible', label: 'Visible' },
					{ value: 'hidden', label: 'Hidden' }
				],
				freeSolo: false
			}} 
		/>;
	}

	if (prop === 'transformstyle') {
		return <SelectCell 
			value={value} 
			onChange={onChange} 
			disabled={disabled} 
			config={{ 
				options: [
					{ value: 'flat', label: 'Flat' },
					{ value: 'preserve-3d', label: 'Preserve 3D' },
					{ value: 'initial', label: 'Initial' },
					{ value: 'inherit', label: 'Inherit' }
				],
				freeSolo: false
			}} 
		/>;
	}

	if (prop === 'mixblendmode') {
		return <SelectCell 
			value={value} 
			onChange={onChange} 
			disabled={disabled} 
			config={{ 
				options: [
					{ value: 'normal', label: 'Normal' },
					{ value: 'multiply', label: 'Multiply' },
					{ value: 'screen', label: 'Screen' },
					{ value: 'overlay', label: 'Overlay' },
					{ value: 'darken', label: 'Darken' },
					{ value: 'lighten', label: 'Lighten' },
					{ value: 'color-dodge', label: 'Color Dodge' },
					{ value: 'color-burn', label: 'Color Burn' },
					{ value: 'hard-light', label: 'Hard Light' },
					{ value: 'soft-light', label: 'Soft Light' },
					{ value: 'difference', label: 'Difference' },
					{ value: 'exclusion', label: 'Exclusion' },
					{ value: 'hue', label: 'Hue' },
					{ value: 'saturation', label: 'Saturation' },
					{ value: 'color', label: 'Color' },
					{ value: 'luminosity', label: 'Luminosity' }
				],
				freeSolo: true
			}} 
		/>;
	}
	
	return <TextCell value={value} onChange={onChange} disabled={disabled} config={config} />;
}

import { AdvancedValue } from './repeater/AdvancedValue';

function SliderCell({ value, onChange, disabled, config }) {
	const { min = 0, max = 100, step = 1 } = config;
	const isAdvanced = typeof value === 'string' && (value.startsWith('__JS__') || value.startsWith('random('));
	const numValue = isAdvanced ? min : (typeof value === 'number' ? value : (Number(value) || min));
	
	const [anchorEl, setAnchorEl] = React.useState(null);

	return (
		<Box sx={{ display: 'flex', alignItems: 'center', width: '100%', gap: 0.5, pl: 0.5 }}>
			<Box sx={{ flex: 1, display: 'flex', alignItems: 'center', gap: 1 }}>
				{!isAdvanced ? (
					<>
						<Slider
							size="small"
							value={numValue}
							min={min}
							max={max}
							step={step}
							disabled={disabled}
							onChange={(_, val) => onChange(val)}
							sx={{ flex: 1 }}
						/>
						<TextField
							size="tiny"
							type="number"
							value={value ?? ''}
							disabled={disabled}
							onChange={(e) => {
								const raw = e.target.value;
								if (raw === '') return onChange(null);
								const num = Number(raw);
								onChange(Number.isFinite(num) ? num : null);
							}}
							inputProps={{ min, max, step }}
							sx={{ width: 60 }}
						/>
					</>
				) : (
					<TextField
						size="tiny"
						fullWidth
						value={value}
						disabled
						InputProps={{ style: { fontSize: 11, fontFamily: 'monospace' } }}
					/>
				)}
			</Box>

			<IconButton size="small" onClick={(e) => setAnchorEl(e.currentTarget)} disabled={disabled} sx={{ width: 24, height: 24 }}>
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M4 21v-7m0-4V3m8 18v-9m0-4V3m8 18v-5m0-4V3M1 14h6m2-6h6m2 8h6"/></svg>
			</IconButton>

			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={() => setAnchorEl(null)}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
				transformOrigin={{ vertical: 'top', horizontal: 'right' }}
			>
				<AdvancedValue value={value} onChange={onChange} config={config} disabled={disabled} />
			</Popover>
		</Box>
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
		<Box sx={{ display: 'flex', justifyContent: 'flex-end', width: '100%', pr: 1 }}>
			<Switch
				size="small"
				checked={!!value}
				disabled={disabled}
				onChange={(_, checked) => onChange(checked)}
			/>
		</Box>
	);
}

const ColorSwatch = styled(Box)(({ theme }) => ({
	width: 24,
	height: 24,
	borderRadius: 4,
	border: `1px solid ${theme.palette.divider}`,
	cursor: 'pointer',
	backgroundColor: 'currentColor',
	flexShrink: 0,
	'&:hover': { opacity: 0.85 },
}));

function ColorCell({ value, onChange, disabled }) {
	const [pickerOpen, setPickerOpen] = React.useState(false);
	const swatchRef = React.useRef(null);

	return (
		<Box sx={{ display: 'flex', width: '100%', alignItems: 'center' }}>
			<TextField
				size="tiny"
				fullWidth
				disabled={disabled}
				value={value || ''}
				onChange={(e) => onChange(e.target.value)}
				placeholder="#000000"
				InputProps={{
					startAdornment: (
						<InputAdornment position="start">
							<ColorSwatch
								ref={swatchRef}
								onClick={() => !disabled && setPickerOpen(true)}
								style={{ color: value || '#ffffff' }}
							/>
						</InputAdornment>
					),
				}}
			/>
			<Popover
				open={pickerOpen}
				anchorEl={swatchRef.current}
				onClose={() => setPickerOpen(false)}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2 }}>
					<HexColorPicker
						color={value || '#000000'}
						onChange={(c) => onChange(c)}
					/>
					<Box
						sx={{
							mt: 1,
							height: 24,
							borderRadius: 1,
							backgroundColor: value || '#000000',
						}}
					/>
				</Box>
			</Popover>
		</Box>
	);
}

function SelectCell({ value, onChange, disabled, config, rows, index }) {
	let options = config.options || [];
	
	if (config.unique && rows) {
		const usedValues = rows
			.map((r, i) => i !== index ? r[config.bind] : null)
			.filter(Boolean);
		options = options.filter((opt) => !usedValues.includes(typeof opt === 'string' ? opt : opt.value));
	}

	const found = options.find((o) => (typeof o === 'string' ? o : o.value) === value);
	const selected = found || (config.freeSolo && value ? value : null);

	return (
		<Autocomplete
			size="tiny"
			fullWidth
			disabled={disabled}
			options={options}
			value={selected}
			freeSolo={!!config.freeSolo}
			isOptionEqualToValue={(opt, val) => {
				const optValue = typeof opt === 'object' && opt !== null ? opt.value : opt;
				const valValue = typeof val === 'object' && val !== null ? val.value : val;
				return optValue === valValue;
			}}
			getOptionLabel={(opt) => {
				if (typeof opt === 'string') return opt;
				return opt.label || String(opt.value);
			}}
			onChange={(_, next) => {
				if (typeof next === 'string') {
					onChange(next);
				} else {
					onChange(next ? next.value : '');
				}
			}}
			groupBy={(opt) => {
				const optCategory = typeof opt === 'object' && opt !== null ? opt.category : undefined;
				return optCategory || 'Other';
			}}
			ListboxProps={{ style: { maxHeight: 300 } }}
			renderInput={(params) => {
				const { onBlur, ...restInputProps } = params.inputProps;
				return (
					<TextField
						{...params}
						size="tiny"
						placeholder={config.placeholder || ''}
						inputProps={{
							...restInputProps,
							onBlur: (e) => {
								if (config.freeSolo) {
									onChange(e.target.value);
								}
								if (onBlur) onBlur(e);
							}
						}}
					/>
				);
			}}
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
	'dynamic-value': DynamicValueCell,
};
