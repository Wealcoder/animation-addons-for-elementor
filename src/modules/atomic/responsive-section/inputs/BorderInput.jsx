import { useState, useRef } from 'react';
import {
	Stack,
	TextField,
	Box,
	Typography,
	Autocomplete,
	InputAdornment,
	Popover,
	IconButton,
	styled,
} from '@elementor/ui';
import { HexColorPicker } from 'react-colorful';
/* ---------- style options ---------- */

const BORDER_STYLES = [
	{ value: '', label: 'Default' },
	{ value: 'none', label: 'None' },
	{ value: 'solid', label: 'Solid' },
	{ value: 'double', label: 'Double' },
	{ value: 'dotted', label: 'Dotted' },
	{ value: 'dashed', label: 'Dashed' },
	{ value: 'groove', label: 'Groove' },
];

/* ---------- color swatch ---------- */

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

/* ---------- link icon ---------- */

const LinkIcon = ({ linked }) => (
	<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
		{linked ? (
			<>
				<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
				<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
			</>
		) : (
			<>
				<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
				<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
				<line x1="2" y1="2" x2="22" y2="22" strokeOpacity="0.6" />
			</>
		)}
	</svg>
);

/* ---------- helpers ---------- */

function parse(val) {
	if (val && typeof val === 'object' && !Array.isArray(val)) {
		let parsedRadius = { top: '', right: '', bottom: '', left: '' };
		if (typeof val.radius === 'object' && val.radius !== null) {
			parsedRadius = {
				top: val.radius.top ?? '',
				right: val.radius.right ?? '',
				bottom: val.radius.bottom ?? '',
				left: val.radius.left ?? '',
			};
		} else if (typeof val.radius === 'string' || typeof val.radius === 'number') {
			let rv = String(val.radius).replace(/[^0-9.]/g, '');
			parsedRadius = { top: rv, right: rv, bottom: rv, left: rv };
		}

		return {
			style:  val.style  || '',
			width: {
				top:    val.width?.top    ?? '',
				right:  val.width?.right  ?? '',
				bottom: val.width?.bottom ?? '',
				left:   val.width?.left   ?? '',
			},
			color:  val.color  || '',
			radius: parsedRadius,
		};
	}
	return {
		style: '',
		width: { top: '', right: '', bottom: '', left: '' },
		color: '',
		radius: { top: '', right: '', bottom: '', left: '' },
	};
}

function hasVisibleBorder(style) {
	return style && style !== '' && style !== 'none';
}

function allSidesEqual(w) {
	const vals = [w.top, w.right, w.bottom, w.left].map((v) => v || '');
	return vals.every((v) => v === vals[0]);
}

/* ==========================================================================
   BorderInput
   ========================================================================== */

export function BorderInput({ value, onChange, disabled }) {
	const current = parse(value);
	const [linked, setLinked] = useState(() => allSidesEqual(current.width));
	const [radiusLinked, setRadiusLinked] = useState(() => allSidesEqual(current.radius));
	const [pickerOpen, setPickerOpen] = useState(false);
	const swatchRef = useRef(null);

	const update = (key, next) => {
		onChange({ ...current, [key]: next });
	};

	const updateWidth = (side, val) => {
		if (linked) {
			update('width', { top: val, right: val, bottom: val, left: val });
		} else {
			update('width', { ...current.width, [side]: val });
		}
	};

	const toggleLinked = () => {
		if (!linked) {
			const unify = current.width.top || '';
			update('width', { top: unify, right: unify, bottom: unify, left: unify });
		}
		setLinked(!linked);
	};

	const updateRadius = (side, val) => {
		if (radiusLinked) {
			update('radius', { top: val, right: val, bottom: val, left: val });
		} else {
			update('radius', { ...current.radius, [side]: val });
		}
	};

	const toggleRadiusLinked = () => {
		if (!radiusLinked) {
			const unify = current.radius.top || '';
			update('radius', { top: unify, right: unify, bottom: unify, left: unify });
		}
		setRadiusLinked(!radiusLinked);
	};

	const selectedStyle = BORDER_STYLES.find((o) => o.value === current.style) || null;

	// Unified width display when linked
	const unifiedWidth = current.width.top || '';
	const unifiedRadius = current.radius.top || '';

	return (
		<Stack direction="column" spacing={1.5} sx={{ width: '100%', mt: 0.5 }}>
			{/* ---- Border Style ---- */}
			<Box>
				<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
					Border Type
				</Typography>
				<Autocomplete
					size="tiny"
					fullWidth
					disabled={disabled}
					options={BORDER_STYLES}
					value={selectedStyle}
					isOptionEqualToValue={(opt, val) => opt.value === val?.value}
					getOptionLabel={(opt) => opt.label || ''}
					onChange={(_, next) => update('style', next ? next.value : '')}
					renderInput={(params) => (
						<TextField {...params} size="tiny" placeholder="Default" />
					)}
				/>
			</Box>

			{/* ---- Width, Color, Radius — only when a real style is chosen ---- */}
			{hasVisibleBorder(current.style) && (
				<>
					{/* Width */}
					<Box>
						<Stack direction="row" alignItems="center" sx={{ mb: 0.5 }}>
							<Typography variant="caption" color="text.secondary" sx={{ flex: 1 }}>
								Width (px)
							</Typography>
							<IconButton
								size="small"
								onClick={toggleLinked}
								disabled={disabled}
								sx={{ p: 0.25 }}
								title={linked ? 'Unlink sides' : 'Link sides'}
							>
								<LinkIcon linked={linked} />
							</IconButton>
						</Stack>

						{linked ? (
							/* Single unified width input */
							<TextField
								size="small"
								type="number"
								inputProps={{ min: 0, step: 1 }}
								value={unifiedWidth}
								onChange={(e) => updateWidth('top', e.target.value)}
								disabled={disabled}
								fullWidth
								placeholder="0"
							/>
						) : (
							/* 4 separate side inputs */
							<Stack direction="row" spacing={0.5}>
								{['top', 'right', 'bottom', 'left'].map((side) => (
									<Box key={side} sx={{ flex: 1 }}>
										<TextField
											size="small"
											type="number"
											inputProps={{ min: 0, step: 1 }}
											value={current.width[side] || ''}
											onChange={(e) => updateWidth(side, e.target.value)}
											disabled={disabled}
											fullWidth
											placeholder="0"
											label={side.charAt(0).toUpperCase() + side.slice(1)}
										/>
									</Box>
								))}
							</Stack>
						)}
					</Box>

					{/* Radius */}
					<Box>
						<Stack direction="row" alignItems="center" sx={{ mb: 0.5 }}>
							<Typography variant="caption" color="text.secondary" sx={{ flex: 1 }}>
								Radius (px)
							</Typography>
							<IconButton
								size="small"
								onClick={toggleRadiusLinked}
								disabled={disabled}
								sx={{ p: 0.25 }}
								title={radiusLinked ? 'Unlink sides' : 'Link sides'}
							>
								<LinkIcon linked={radiusLinked} />
							</IconButton>
						</Stack>

						{radiusLinked ? (
							<TextField
								size="small"
								type="number"
								inputProps={{ min: 0, step: 1 }}
								value={unifiedRadius}
								onChange={(e) => updateRadius('top', e.target.value)}
								disabled={disabled}
								fullWidth
								placeholder="0"
							/>
						) : (
							<Stack direction="row" spacing={0.5}>
								{['top', 'right', 'bottom', 'left'].map((side) => (
									<Box key={side} sx={{ flex: 1 }}>
										<TextField
											size="small"
											type="number"
											inputProps={{ min: 0, step: 1 }}
											value={current.radius[side] || ''}
											onChange={(e) => updateRadius(side, e.target.value)}
											disabled={disabled}
											fullWidth
											placeholder="0"
											label={side.charAt(0).toUpperCase() + side.slice(1)}
										/>
									</Box>
								))}
							</Stack>
						)}
					</Box>

					{/* Color */}
					<Box>
						<Typography variant="caption" color="text.secondary" sx={{ mb: 0.5, display: 'block' }}>
							Color
						</Typography>
						<TextField
							size="small"
							fullWidth
							disabled={disabled}
							value={current.color || ''}
							onChange={(e) => update('color', e.target.value)}
							placeholder="#000000"
							InputProps={{
								startAdornment: (
									<InputAdornment position="start">
										<ColorSwatch
											ref={swatchRef}
											onClick={() => !disabled && setPickerOpen(true)}
											style={{ color: current.color || '#ffffff' }}
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
									color={current.color || '#000000'}
									onChange={(c) => update('color', c)}
								/>
								<Box
									sx={{
										mt: 1,
										height: 24,
										borderRadius: 1,
										backgroundColor: current.color || '#000000',
									}}
								/>
							</Box>
						</Popover>
					</Box>
				</>
			)}
		</Stack>
	);
}
