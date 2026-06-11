import { useState } from 'react';
import { Stack, TextField, Box, Typography, Autocomplete, Popover, IconButton, Switch } from '@elementor/ui';

const STAGGER_FROM_OPTIONS = [
	{ value: 'start', label: 'Default (Start)' },
	{ value: 'center', label: 'Center' },
	{ value: 'edges', label: 'Edges' },
	{ value: 'random', label: 'Random' },
	{ value: 'end', label: 'End' },
];

const STAGGER_AXIS_OPTIONS = [
	{ value: '', label: 'Default (Both)' },
	{ value: 'x', label: 'X Axis' },
	{ value: 'y', label: 'Y Axis' },
];

const STAGGER_MODE_OPTIONS = [
	{ value: 'each', label: 'Each (Default)' },
	{ value: 'amount', label: 'Total Amount' },
];

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
	{ value: 'back.out', label: 'Back Out' },
	{ value: 'back.inOut', label: 'Back InOut' },
	{ value: 'bounce.out', label: 'Bounce Out' },
	{ value: 'elastic.out', label: 'Elastic Out' },
	{ value: 'circ.out', label: 'Circ Out' },
	{ value: 'expo.out', label: 'Expo Out' }
];

function parse(val) {
	if (!val) return { mode: 'each', time: 0.1, from: 'start', axis: '', grid: '', ease: '', repeat: 0, yoyo: false };
	if (typeof val === 'number' || (typeof val === 'string' && !val.startsWith('{'))) {
		return { mode: 'each', time: Number(val) || 0, from: 'start', axis: '', grid: '', ease: '', repeat: 0, yoyo: false };
	}
	try {
		const obj = typeof val === 'string' ? JSON.parse(val) : val;
		return {
			mode: obj.amount !== undefined ? 'amount' : 'each',
			time: obj.amount !== undefined ? obj.amount : (obj.each || 0),
			from: obj.from || 'start',
			axis: obj.axis || '',
			grid: obj.grid || '',
			ease: obj.ease || '',
			repeat: obj.repeat || 0,
			yoyo: obj.yoyo || false,
		};
	} catch (e) {
		return { mode: 'each', time: 0.1, from: 'start', axis: '', grid: '', ease: '', repeat: 0, yoyo: false };
	}
}

export function Stagger({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = useState(null);
	const current = parse(value);

	const isSimple = current.mode === 'each' && current.from === 'start' && !current.axis && !current.grid && !current.ease && !current.repeat && !current.yoyo;

	const update = (key, next) => {
		const nextState = { ...current, [key]: next };
		
		// If perfectly default simple stagger, output number
		if (nextState.mode === 'each' && nextState.from === 'start' && !nextState.axis && !nextState.grid && !nextState.ease && !nextState.repeat && !nextState.yoyo) {
			onChange(nextState.time.toString());
			return;
		}

		// Otherwise, output JSON object
		const out = {
			[nextState.mode]: Number(nextState.time) || 0,
			from: nextState.from,
		};
		if (nextState.axis) out.axis = nextState.axis;
		if (nextState.grid) out.grid = nextState.grid;
		if (nextState.ease) out.ease = nextState.ease;
		if (nextState.repeat) out.repeat = Number(nextState.repeat);
		if (nextState.yoyo) out.yoyo = true;

		onChange(JSON.stringify(out));
	};

	return (
		<Stack direction="row" spacing={0.5} alignItems="center" sx={{ width: '100%' }}>
			<TextField
				size="tiny"
				type={isSimple ? "number" : "text"}
				inputProps={{ min: 0, step: 0.05 }}
				value={isSimple ? current.time : '{...}'}
				onChange={(e) => {
					if (isSimple) update('time', e.target.value);
				}}
				disabled={disabled || !isSimple}
				placeholder="0.1"
				sx={{ flex: 1 }}
			/>
			<IconButton
				size="small"
				onClick={(e) => !disabled && setAnchorEl(e.currentTarget)}
				sx={{ 
					p: 0.5, 
					borderRadius: 1, 
					border: '1px solid', 
					borderColor: Boolean(anchorEl) ? 'primary.main' : 'divider',
					color: Boolean(anchorEl) ? 'primary.main' : 'text.secondary',
					backgroundColor: Boolean(anchorEl) ? 'action.hover' : 'transparent',
				}}
			>
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
				</svg>
			</IconButton>

			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={() => setAnchorEl(null)}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
				transformOrigin={{ vertical: 'top', horizontal: 'right' }}
				PaperProps={{ sx: { width: 340, p: 2, mt: 1 } }}
			>
				<Stack spacing={2}>
					<Stack direction="row" justifyContent="space-between" alignItems="center">
						<Typography variant="caption" fontWeight="bold">Advanced Stagger</Typography>
						<IconButton size="small" onClick={() => onChange('0.1')} sx={{ p: 0 }}>
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
						</IconButton>
					</Stack>

					<Stack direction="row" spacing={1.5}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Type
							</Typography>
							<Autocomplete
								size="small"
								fullWidth
								options={STAGGER_MODE_OPTIONS}
								value={STAGGER_MODE_OPTIONS.find(o => o.value === current.mode) || STAGGER_MODE_OPTIONS[0]}
								isOptionEqualToValue={(opt, val) => opt.value === val?.value}
								getOptionLabel={(opt) => opt.label}
								onChange={(_, next) => update('mode', next ? next.value : 'each')}
								renderInput={(params) => <TextField {...params} size="small" />}
								disableClearable
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								From
							</Typography>
							<Autocomplete
								size="small"
								fullWidth
								options={STAGGER_FROM_OPTIONS}
								value={STAGGER_FROM_OPTIONS.find(o => o.value === current.from) || STAGGER_FROM_OPTIONS[0]}
								isOptionEqualToValue={(opt, val) => opt.value === val?.value}
								getOptionLabel={(opt) => opt.label}
								onChange={(_, next) => update('from', next ? next.value : 'start')}
								renderInput={(params) => <TextField {...params} size="small" />}
								disableClearable
							/>
						</Box>
					</Stack>

					<Stack direction="row" spacing={1.5}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Stagger Ease
							</Typography>
							<Autocomplete
								size="small"
								fullWidth
								options={EASE_OPTIONS}
								value={EASE_OPTIONS.find(o => o.value === current.ease) || EASE_OPTIONS[0]}
								isOptionEqualToValue={(opt, val) => opt.value === val?.value}
								getOptionLabel={(opt) => opt.label}
								onChange={(_, next) => update('ease', next ? next.value : '')}
								renderInput={(params) => <TextField {...params} size="small" />}
								disableClearable
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Grid (e.g. "auto" or "[9,15]")
							</Typography>
							<TextField
								size="small"
								fullWidth
								value={current.grid}
								onChange={(e) => update('grid', e.target.value)}
								placeholder='auto'
							/>
						</Box>
					</Stack>

					<Stack direction="row" spacing={1.5}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
								Axis
							</Typography>
							<Autocomplete
								size="small"
								fullWidth
								options={STAGGER_AXIS_OPTIONS}
								value={STAGGER_AXIS_OPTIONS.find(o => o.value === current.axis) || STAGGER_AXIS_OPTIONS[0]}
								isOptionEqualToValue={(opt, val) => opt.value === val?.value}
								getOptionLabel={(opt) => opt.label}
								onChange={(_, next) => update('axis', next ? next.value : '')}
								renderInput={(params) => <TextField {...params} size="small" />}
								disableClearable
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
								value={current.repeat}
								onChange={(e) => update('repeat', e.target.value)}
								fullWidth
							/>
						</Box>
					</Stack>

					<Stack direction="row" spacing={1} alignItems="center">
						<Typography variant="caption" color="text.secondary">
							Yoyo
						</Typography>
						<Switch
							size="small"
							checked={current.yoyo}
							onChange={(e) => update('yoyo', e.target.checked)}
						/>
					</Stack>
				</Stack>
			</Popover>
		</Stack>
	);
}
