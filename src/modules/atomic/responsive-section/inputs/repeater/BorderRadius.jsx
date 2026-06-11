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
	const match = str.match(/(px|%|em|rem)/);
	return match ? match[1] : 'px';
}

function parseRadiusStr(str) {
	if (!str) return { tl: '', tr: '', br: '', bl: '' };
	const parts = String(str).replace(/(px|%|em|rem)/g, '').split(' ').filter(Boolean);
	if (parts.length === 1) return { tl: parts[0], tr: parts[0], br: parts[0], bl: parts[0] };
	if (parts.length === 2) return { tl: parts[0], tr: parts[1], br: parts[0], bl: parts[1] };
	if (parts.length === 3) return { tl: parts[0], tr: parts[1], br: parts[2], bl: parts[1] };
	if (parts.length === 4) return { tl: parts[0], tr: parts[1], br: parts[2], bl: parts[3] };
	return { tl: '', tr: '', br: '', bl: '' };
}

function radiusObjToStr(obj, unit = 'px') {
	if (!obj) return '';
	const tl = obj.tl || '0';
	const tr = obj.tr || '0';
	const br = obj.br || '0';
	const bl = obj.bl || '0';
	if (tl === tr && tr === br && br === bl) {
		return (tl !== '0' && tl !== '') ? `${tl}${unit}` : '';
	}
	return `${tl}${unit} ${tr}${unit} ${br}${unit} ${bl}${unit}`;
}

const UNITS = ['px', '%', 'em', 'rem'];

export function BorderRadius({ value, onChange, disabled }) {
	const [anchorEl, setAnchorEl] = useState(null);
	
	const currentUnit = extractUnit(value);
	const radiusObj = parseRadiusStr(value);
	
	const [linked, setLinked] = useState(() => {
		const { tl, tr, br, bl } = radiusObj;
		if (!tl && !tr && !br && !bl) return true;
		return tl === tr && tr === br && br === bl;
	});

	const unifiedValue = radiusObj.tl || '';

	const update = (key, val) => {
		const newObj = { ...radiusObj, [key]: val };
		onChange(radiusObjToStr(newObj, currentUnit));
	};

	const handleUnifiedChange = (val) => {
		onChange(radiusObjToStr({ tl: val, tr: val, br: val, bl: val }, currentUnit));
	};

	const handleUnitChange = (u) => {
		onChange(radiusObjToStr(radiusObj, u));
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
				inputProps={{ min: 0, step: currentUnit === 'em' || currentUnit === 'rem' ? 0.1 : 1 }}
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
					borderColor: 'divider',
					backgroundColor: anchorEl ? 'action.hover' : 'transparent'
				}}
			>
				{/* Dashed square icon */}
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeDasharray="4 4">
					<rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
				</svg>
			</IconButton>

			<Popover
				open={Boolean(anchorEl)}
				anchorEl={anchorEl}
				onClose={() => setAnchorEl(null)}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
				transformOrigin={{ vertical: 'top', horizontal: 'right' }}
			>
				<Box sx={{ p: 2, width: 260 }}>
					<Stack direction="row" alignItems="center" justifyContent="space-between" sx={{ mb: 2 }}>
						<Typography variant="caption" color="text.secondary">
							Link Corners Value
						</Typography>
						<Switch 
							size="small" 
							checked={linked} 
							onChange={(e) => toggleLinked(e.target.checked)} 
						/>
					</Stack>

					<Stack direction="row" spacing={1} sx={{ mb: 2 }}>
						{UNITS.map(u => (
							<Box 
								key={u} 
								onClick={() => handleUnitChange(u)}
								sx={{ 
									flex: 1,
									textAlign: 'center',
									py: 0.5, 
									fontSize: '0.75rem', 
									cursor: 'pointer',
									borderRadius: 1,
									bgcolor: currentUnit === u ? 'primary.main' : 'transparent',
									color: currentUnit === u ? 'primary.contrastText' : 'text.primary',
									border: '1px solid',
									borderColor: currentUnit === u ? 'primary.main' : 'divider',
									transition: 'all 0.2s',
									'&:hover': {
										bgcolor: currentUnit === u ? 'primary.main' : 'action.hover',
									}
								}}
							>
								{u}
							</Box>
						))}
					</Stack>

					<Stack direction="row" spacing={1} sx={{ mb: 1 }}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>
								Top Left
							</Typography>
							<TextField
								size="small"
								type="number"
								inputProps={{ min: 0, step: currentUnit === 'em' || currentUnit === 'rem' ? 0.1 : 1 }}
								value={radiusObj.tl || ''}
								onChange={(e) => update('tl', e.target.value)}
								fullWidth
								placeholder="0"
								InputProps={{
									endAdornment: <Typography variant="caption" sx={{ color: 'text.secondary', ml: 0.5 }}>{currentUnit}</Typography>
								}}
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>
								Top Right
							</Typography>
							<TextField
								size="small"
								type="number"
								inputProps={{ min: 0, step: currentUnit === 'em' || currentUnit === 'rem' ? 0.1 : 1 }}
								value={radiusObj.tr || ''}
								onChange={(e) => update('tr', e.target.value)}
								fullWidth
								placeholder="0"
								InputProps={{
									endAdornment: <Typography variant="caption" sx={{ color: 'text.secondary', ml: 0.5 }}>{currentUnit}</Typography>
								}}
							/>
						</Box>
					</Stack>

					<Stack direction="row" spacing={1}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>
								Bottom Right
							</Typography>
							<TextField
								size="small"
								type="number"
								inputProps={{ min: 0, step: currentUnit === 'em' || currentUnit === 'rem' ? 0.1 : 1 }}
								value={radiusObj.br || ''}
								onChange={(e) => update('br', e.target.value)}
								fullWidth
								placeholder="0"
								InputProps={{
									endAdornment: <Typography variant="caption" sx={{ color: 'text.secondary', ml: 0.5 }}>{currentUnit}</Typography>
								}}
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>
								Bottom Left
							</Typography>
							<TextField
								size="small"
								type="number"
								inputProps={{ min: 0, step: currentUnit === 'em' || currentUnit === 'rem' ? 0.1 : 1 }}
								value={radiusObj.bl || ''}
								onChange={(e) => update('bl', e.target.value)}
								fullWidth
								placeholder="0"
								InputProps={{
									endAdornment: <Typography variant="caption" sx={{ color: 'text.secondary', ml: 0.5 }}>{currentUnit}</Typography>
								}}
							/>
						</Box>
					</Stack>
				</Box>
			</Popover>
		</Stack>
	);
}
