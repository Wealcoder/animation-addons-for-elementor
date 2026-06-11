import { useState, useEffect } from 'react';
import {
	Stack,
	TextField,
	Box,
	Typography,
	InputAdornment,
	Popover,
	IconButton,
	styled,
	Button
} from '@elementor/ui';
import { HexColorPicker } from 'react-colorful';

const ColorIndicator = styled(Box)(({ theme }) => ({
	width: '24px',
	height: '24px',
	borderRadius: '4px',
	border: `1px solid ${theme.palette.divider || 'rgba(0, 0, 0, 0.15)'}`,
	cursor: 'pointer',
	backgroundColor: 'currentColor',
	'&:hover': { opacity: 0.8 },
}));

const CloseIcon = () => (
	<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
		<line x1="18" y1="6" x2="6" y2="18"></line>
		<line x1="6" y1="6" x2="18" y2="18"></line>
	</svg>
);

function parseShadows(str) {
	if (!str) return [];
	// Split by comma, ignoring commas inside rgba()
	const parts = str.match(/(?:[^,(]|\([^)]*\))+/g);
	if (!parts) return [];

	return parts.map(part => {
		let color = '#000000';
		let rest = part.trim();
		
		const colorEnd = rest.match(/(rgba?\([^)]+\)|#[0-9a-fA-F]{3,8}|[a-zA-Z]+)$/i);
		if (colorEnd) {
			color = colorEnd[0];
			rest = rest.slice(0, colorEnd.index).trim();
		} else {
			const colorStart = rest.match(/^(rgba?\([^)]+\)|#[0-9a-fA-F]{3,8}|[a-zA-Z]+)\s+/i);
			if (colorStart) {
				color = colorStart[1];
				rest = rest.slice(colorStart[0].length).trim();
			}
		}

		const dims = rest.split(/\s+/).map(v => parseFloat(v) || 0);
		return {
			x: dims[0] || 0,
			y: dims[1] || 0,
			blur: dims[2] || 0,
			color: color
		};
	});
}

function stringifyShadows(shadows) {
	if (!shadows || shadows.length === 0) return '';
	return shadows.map(s => `${s.x}px ${s.y}px ${s.blur}px ${s.color}`).join(', ');
}

export function TextShadowInput({ value, onChange, disabled }) {
	const [shadows, setShadows] = useState(() => parseShadows(value));
	const [pickerAnchor, setPickerAnchor] = useState(null);
	const [activeShadowIdx, setActiveShadowIdx] = useState(null);

	// Sync when external value changes
	useEffect(() => {
		setShadows(parseShadows(value));
	}, [value]);

	const updateShadows = (newShadows) => {
		setShadows(newShadows);
		onChange(stringifyShadows(newShadows));
	};

	const updateShadow = (idx, key, val) => {
		const newShadows = [...shadows];
		newShadows[idx] = { ...newShadows[idx], [key]: val };
		updateShadows(newShadows);
	};

	const addShadow = () => {
		updateShadows([...shadows, { x: 0, y: 0, blur: 0, color: '#000000' }]);
	};

	const removeShadow = (idx) => {
		const newShadows = shadows.filter((_, i) => i !== idx);
		updateShadows(newShadows);
	};

	return (
		<Stack spacing={2} sx={{ width: '100%', mt: 0.5 }}>
			{shadows.map((shadow, idx) => (
				<Box key={idx} sx={{ p: 1.5, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
					<Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.5 }}>
						<Typography variant="caption" fontWeight="bold">Shadow {idx + 1}</Typography>
						<IconButton size="small" onClick={() => removeShadow(idx)} disabled={disabled}>
							<CloseIcon />
						</IconButton>
					</Stack>

					<Stack direction="row" spacing={1} sx={{ mb: 1.5 }}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>X Offset</Typography>
							<TextField
								size="small" type="number" fullWidth disabled={disabled}
								value={shadow.x} onChange={(e) => updateShadow(idx, 'x', e.target.value)}
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Y Offset</Typography>
							<TextField
								size="small" type="number" fullWidth disabled={disabled}
								value={shadow.y} onChange={(e) => updateShadow(idx, 'y', e.target.value)}
							/>
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Blur</Typography>
							<TextField
								size="small" type="number" fullWidth disabled={disabled}
								value={shadow.blur} onChange={(e) => updateShadow(idx, 'blur', e.target.value)}
							/>
						</Box>
					</Stack>

					<Box>
						<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Color</Typography>
						<TextField
							size="small" fullWidth disabled={disabled} value={shadow.color}
							onChange={(e) => updateShadow(idx, 'color', e.target.value)}
							InputProps={{
								startAdornment: (
									<InputAdornment position="start">
										<ColorIndicator
											style={{ color: shadow.color }}
											onClick={(e) => {
												if (!disabled) {
													setActiveShadowIdx(idx);
													setPickerAnchor(e.currentTarget);
												}
											}}
										/>
									</InputAdornment>
								)
							}}
						/>
					</Box>
				</Box>
			))}

			<Button variant="outlined" size="small" onClick={addShadow} disabled={disabled}>
				+ Add Shadow
			</Button>

			<Popover
				open={Boolean(pickerAnchor)}
				anchorEl={pickerAnchor}
				onClose={() => { setPickerAnchor(null); setActiveShadowIdx(null); }}
				anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
				transformOrigin={{ vertical: 'top', horizontal: 'left' }}
			>
				<Box sx={{ p: 2 }}>
					<HexColorPicker
						color={activeShadowIdx !== null && shadows[activeShadowIdx]?.color.startsWith('#') ? shadows[activeShadowIdx].color : '#000000'}
						onChange={(c) => {
							if (activeShadowIdx !== null) updateShadow(activeShadowIdx, 'color', c);
						}}
					/>
				</Box>
			</Popover>
		</Stack>
	);
}
