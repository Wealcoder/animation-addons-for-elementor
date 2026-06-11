import { useState, useRef } from 'react';
import {
	Stack,
	TextField,
	Box,
	Typography,
	Autocomplete,
	Popover,
	IconButton,
	Switch,
} from '@elementor/ui';
import { Settings, RotateCcw } from 'lucide-react';

/* ---------- options ---------- */

const EASE_OPTIONS = [
	{ value: '', label: 'Default' },
	{ value: 'none', label: 'Linear / None' },
	{ value: 'power1.out', label: 'Power1 Out' },
	{ value: 'power1.inOut', label: 'Power1 InOut' },
	{ value: 'power2.out', label: 'Power2 Out' },
	{ value: 'power2.inOut', label: 'Power2 InOut' },
	{ value: 'power3.out', label: 'Power3 Out' },
	{ value: 'power3.inOut', label: 'Power3 InOut' },
	{ value: 'power4.out', label: 'Power4 Out' },
	{ value: 'power4.inOut', label: 'Power4 InOut' },
	{ value: 'back.out(1.7)', label: 'Back Out' },
	{ value: 'back.inOut(1.7)', label: 'Back InOut' },
	{ value: 'elastic.out(1, 0.3)', label: 'Elastic Out' },
	{ value: 'bounce.out', label: 'Bounce Out' },
	{ value: 'expo.out', label: 'Expo Out' },
	{ value: 'expo.inOut', label: 'Expo InOut' },
	{ value: 'sine.out', label: 'Sine Out' },
	{ value: 'sine.inOut', label: 'Sine InOut' },
	{ value: 'circ.out', label: 'Circ Out' },
	{ value: 'circ.inOut', label: 'Circ InOut' }
];

const FROM_OPTIONS = [
	{ value: '', label: 'Default (Start)' },
	{ value: 'start', label: 'Start' },
	{ value: 'center', label: 'Center' },
	{ value: 'edges', label: 'Edges' },
	{ value: 'random', label: 'Random' },
	{ value: 'end', label: 'End' },
];

const TYPE_OPTIONS = [
	{ value: 'each', label: 'Each (Delay per item)' },
	{ value: 'amount', label: 'Amount (Total time)' },
];

const GRID_OPTIONS = [
	{ value: '', label: 'None' },
	{ value: 'auto', label: 'Auto' },
];

const AXIS_OPTIONS = [
	{ value: '', label: 'Default (Both)' },
	{ value: 'x', label: 'X Axis' },
	{ value: 'y', label: 'Y Axis' },
];

/* ---------- helpers ---------- */

function parse(val) {
	// If it's a legacy plain number, migrate it on-the-fly
	if (typeof val === 'number' || typeof val === 'string') {
		const parsedNum = parseFloat(val);
		return {
			val: isNaN(parsedNum) ? 0.02 : parsedNum,
			type: 'each',
			from: '',
			repeat: 0,
			yoyo: false,
			ease: '',
			grid: '',
			axis: '',
		};
	}
	
	if (val && typeof val === 'object' && !Array.isArray(val)) {
		return {
			val: val.val ?? 0.02,
			type: val.type || 'each',
			from: val.from || '',
			repeat: val.repeat || 0,
			yoyo: !!val.yoyo,
			ease: val.ease || '',
			grid: val.grid || '',
			axis: val.axis || '',
		};
	}
	
	return {
		val: 0.02,
		type: 'each',
		from: '',
		repeat: 0,
		yoyo: false,
		ease: '',
		grid: '',
		axis: '',
	};
}

/* ==========================================================================
   StaggerInput
   ========================================================================== */

export function StaggerInput({ value, onChange, disabled }) {
	const current = parse(value);
	const [pickerOpen, setPickerOpen] = useState(false);
	const anchorRef = useRef(null);

	const update = (key, next) => {
		onChange({ ...current, [key]: next });
	};

	const resetAdvanced = () => {
		onChange({
			val: current.val,
			type: 'each',
			from: '',
			repeat: 0,
			yoyo: false,
			ease: '',
			grid: '',
			axis: '',
		});
	};

	const selectedEase = EASE_OPTIONS.find((o) => o.value === current.ease) || null;
	const selectedFrom = FROM_OPTIONS.find((o) => o.value === current.from) || null;
	const selectedType = TYPE_OPTIONS.find((o) => o.value === current.type) || null;
	const selectedAxis = AXIS_OPTIONS.find((o) => o.value === current.axis) || null;

	return (
		<Stack direction="row" spacing={1} sx={{ width: '100%', mt: 0.5, alignItems: 'flex-start' }}>
			{/* Main Numeric Input */}
			<Box sx={{ flex: 1 }}>
				<TextField
					size="small"
					type="number"
					inputProps={{ min: 0, step: 0.01 }}
					value={current.val === 0 ? '0' : current.val || ''}
					onChange={(e) => update('val', parseFloat(e.target.value) || 0)}
					disabled={disabled}
					fullWidth
					placeholder="0.02"
				/>
			</Box>

			{/* Advanced Settings Button */}
			<IconButton
				ref={anchorRef}
				size="small"
				onClick={() => !disabled && setPickerOpen(true)}
				disabled={disabled}
				sx={{ mt: 0.25 }}
				title="Advanced Stagger Settings"
			>
				<Settings size={18} />
			</IconButton>

			{/* Advanced Settings Popover */}
			<Popover
				open={pickerOpen}
				anchorEl={anchorRef.current}
				onClose={() => setPickerOpen(false)}
				anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
				transformOrigin={{ vertical: 'bottom', horizontal: 'right' }}
			>
				<Stack direction="column" spacing={2} sx={{ p: 2, minWidth: 400 }}>
					<Stack direction="row" alignItems="center" justifyContent="space-between">
						<Typography variant="subtitle2" sx={{ fontWeight: 600 }}>
							Advanced Stagger
						</Typography>
						<IconButton size="small" onClick={resetAdvanced} title="Reset Advanced Settings">
							<RotateCcw size={14} />
						</IconButton>
					</Stack>

					{/* Row 1 */}
					<Stack direction="row" spacing={2}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Type
							</Typography>
							<Autocomplete
								size="tiny"
								fullWidth
								options={TYPE_OPTIONS}
								value={selectedType}
								isOptionEqualToValue={(opt, val) => opt.value === val?.value}
								getOptionLabel={(opt) => opt.label || ''}
								onChange={(_, next) => update('type', next ? next.value : 'each')}
								renderInput={(params) => <TextField {...params} size="tiny" />}
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								From
							</Typography>
							<Autocomplete
								size="tiny"
								fullWidth
								options={FROM_OPTIONS}
								value={selectedFrom}
								isOptionEqualToValue={(opt, val) => opt.value === val?.value}
								getOptionLabel={(opt) => opt.label || ''}
								onChange={(_, next) => update('from', next ? next.value : '')}
								renderInput={(params) => <TextField {...params} size="tiny" placeholder="Default" />}
							/>
						</Box>
					</Stack>

					{/* Row 2 */}
					<Stack direction="row" spacing={2}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Stagger Ease
							</Typography>
							<Autocomplete
								size="tiny"
								fullWidth
								options={EASE_OPTIONS}
								value={selectedEase}
								isOptionEqualToValue={(opt, val) => opt.value === val?.value}
								getOptionLabel={(opt) => opt.label || ''}
								onChange={(_, next) => update('ease', next ? next.value : '')}
								renderInput={(params) => <TextField {...params} size="tiny" placeholder="Default" />}
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Grid (e.g. "auto" or "[9,15]")
							</Typography>
							<TextField
								size="small"
								fullWidth
								value={current.grid || ''}
								onChange={(e) => update('grid', e.target.value)}
								placeholder="auto"
							/>
						</Box>
					</Stack>

					{/* Row 3 */}
					<Stack direction="row" spacing={2}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Axis
							</Typography>
							<Autocomplete
								size="tiny"
								fullWidth
								options={AXIS_OPTIONS}
								value={selectedAxis}
								isOptionEqualToValue={(opt, val) => opt.value === val?.value}
								getOptionLabel={(opt) => opt.label || ''}
								onChange={(_, next) => update('axis', next ? next.value : '')}
								renderInput={(params) => <TextField {...params} size="tiny" placeholder="Default" />}
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Repeat
							</Typography>
							<TextField
								size="small"
								type="number"
								inputProps={{ min: -1, step: 1 }}
								value={current.repeat === 0 ? '0' : current.repeat || ''}
								onChange={(e) => update('repeat', parseInt(e.target.value, 10) || 0)}
								fullWidth
								placeholder="0"
							/>
						</Box>
					</Stack>

					{/* Row 4 */}
					<Stack direction="row" spacing={1} alignItems="center">
						<Typography 
							variant="caption" 
							color="text.secondary"
							sx={{ cursor: 'pointer' }}
							onClick={() => update('yoyo', !current.yoyo)}
						>
							Yoyo
						</Typography>
						<Switch
							checked={!!current.yoyo}
							onChange={(e, checked) => update('yoyo', checked !== undefined ? checked : e.target.checked)}
						/>
					</Stack>
				</Stack>
			</Popover>
		</Stack>
	);
}
