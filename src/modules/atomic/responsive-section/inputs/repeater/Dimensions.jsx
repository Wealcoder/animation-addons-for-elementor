import { useState } from 'react';
import {
	Stack,
	TextField,
	Box,
	Typography,
	Popover,
	IconButton,
	Switch,
} from '@elementor/ui';

function extractUnit(str) {
	if (!str) return 'px';
	const match = str.match(/(px|%|em|rem|vh|vw)/);
	return match ? match[1] : 'px';
}

function parseDimStr(str) {
	if (!str) return { t: '', r: '', b: '', l: '' };
	const parts = String(str).replace(/(px|%|em|rem|vh|vw)/g, '').split(' ').filter(Boolean);
	if (parts.length === 1) return { t: parts[0], r: parts[0], b: parts[0], l: parts[0] };
	if (parts.length === 2) return { t: parts[0], r: parts[1], b: parts[0], l: parts[1] };
	if (parts.length === 3) return { t: parts[0], r: parts[1], b: parts[2], l: parts[1] };
	if (parts.length === 4) return { t: parts[0], r: parts[1], b: parts[2], l: parts[3] };
	return { t: '', r: '', b: '', l: '' };
}

function dimObjToStr(obj, unit = 'px') {
	if (!obj) return '';
	const t = obj.t || '0';
	const r = obj.r || '0';
	const b = obj.b || '0';
	const l = obj.l || '0';
	if (t === r && r === b && b === l) {
		return (t !== '0' && t !== '') ? `${t}${unit}` : '';
	}
	return `${t}${unit} ${r}${unit} ${b}${unit} ${l}${unit}`;
}

const UNITS = ['px', '%', 'em', 'rem', 'vw', 'vh'];

export function Dimensions({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = useState(null);
	
	const currentUnit = extractUnit(value);
	const dimObj = parseDimStr(value);
	
	const [linked, setLinked] = useState(() => {
		const { t, r, b, l } = dimObj;
		if (!t && !r && !b && !l) return true;
		return t === r && r === b && b === l;
	});

	const unifiedValue = dimObj.t || '';

	const update = (key, val) => {
		const newObj = { ...dimObj, [key]: val };
		onChange(dimObjToStr(newObj, currentUnit));
	};

	const handleUnifiedChange = (val) => {
		onChange(dimObjToStr({ t: val, r: val, b: val, l: val }, currentUnit));
	};

	const handleUnitChange = (u) => {
		onChange(dimObjToStr(dimObj, u));
	};

	const toggleLinked = (checked) => {
		setLinked(checked);
		if (checked) {
			handleUnifiedChange(unifiedValue);
		}
	};

	return (
		<Stack direction="row" spacing={0.5} alignItems="center" sx={{ width: '100%' }}>
			<TextField
				size="tiny"
				type="number"
				inputProps={{ min: -9999, step: currentUnit === 'em' || currentUnit === 'rem' ? 0.1 : 1 }}
				value={linked ? unifiedValue : ''}
				onChange={(e) => {
					if (linked) handleUnifiedChange(e.target.value);
				}}
				disabled={disabled || !linked}
				placeholder={linked ? "0" : "..."}
				InputProps={{
					endAdornment: <Typography variant="caption" sx={{ color: 'text.secondary', ml: 0.5, pr: 0.5 }}>{currentUnit}</Typography>,
					sx: { pr: 0 }
				}}
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
					<rect x="3" y="3" width="18" height="18" rx="2" ry="2" strokeDasharray="4 4" />
				</svg>
			</IconButton>

			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={() => setAnchorEl(null)}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
				transformOrigin={{ vertical: 'top', horizontal: 'right' }}
				PaperProps={{ sx: { width: 280, p: 2, mt: 1 } }}
			>
				<Stack spacing={2}>
					<Stack direction="row" justifyContent="space-between" alignItems="center">
						<Typography variant="caption" fontWeight="bold">Link Values</Typography>
						<Switch size="small" checked={linked} onChange={(e) => toggleLinked(e.target.checked)} />
					</Stack>

					<Stack direction="row" spacing={1} justifyContent="center" sx={{ pb: 1, borderBottom: '1px solid', borderColor: 'divider' }}>
						{UNITS.map(u => (
							<Box
								key={u}
								onClick={() => handleUnitChange(u)}
								sx={{
									cursor: 'pointer',
									px: 1,
									py: 0.5,
									borderRadius: 1,
									fontSize: '0.75rem',
									bgcolor: currentUnit === u ? 'primary.main' : 'transparent',
									color: currentUnit === u ? 'primary.contrastText' : 'text.secondary',
									'&:hover': { bgcolor: currentUnit === u ? 'primary.main' : 'action.hover' }
								}}
							>
								{u}
							</Box>
						))}
					</Stack>

					<Stack spacing={1.5}>
						<Stack direction="row" spacing={1}>
							<Box sx={{ flex: 1 }}>
								<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Top</Typography>
								<TextField size="small" type="number" fullWidth disabled={linked} value={dimObj.t} onChange={(e) => update('t', e.target.value)} />
							</Box>
							<Box sx={{ flex: 1 }}>
								<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Right</Typography>
								<TextField size="small" type="number" fullWidth disabled={linked} value={dimObj.r} onChange={(e) => update('r', e.target.value)} />
							</Box>
						</Stack>
						<Stack direction="row" spacing={1}>
							<Box sx={{ flex: 1 }}>
								<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Bottom</Typography>
								<TextField size="small" type="number" fullWidth disabled={linked} value={dimObj.b} onChange={(e) => update('b', e.target.value)} />
							</Box>
							<Box sx={{ flex: 1 }}>
								<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Left</Typography>
								<TextField size="small" type="number" fullWidth disabled={linked} value={dimObj.l} onChange={(e) => update('l', e.target.value)} />
							</Box>
						</Stack>
					</Stack>
				</Stack>
			</Popover>
		</Stack>
	);
}
