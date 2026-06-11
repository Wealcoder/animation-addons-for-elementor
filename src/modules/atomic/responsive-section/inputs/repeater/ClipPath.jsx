import { useState, useEffect } from 'react';
import { Stack, TextField, Box, Typography, Autocomplete } from '@elementor/ui';

const SHAPES = [
	{ value: 'custom', label: 'Custom / Polygon' },
	{ value: 'circle', label: 'Circle' },
	{ value: 'ellipse', label: 'Ellipse' },
	{ value: 'inset', label: 'Inset' }
];

export function ClipPath({ value, onChange, disabled }) {
	const [type, setType] = useState('custom');
	const [raw, setRaw] = useState('');
	
	const [cRadius, setCRadius] = useState('50%');
	const [cX, setCX] = useState('50%');
	const [cY, setCY] = useState('50%');
	
	const [eRx, setERx] = useState('50%');
	const [eRy, setERy] = useState('50%');
	const [eX, setEX] = useState('50%');
	const [eY, setEY] = useState('50%');

	const [iTop, setITop] = useState('0%');
	const [iRight, setIRight] = useState('0%');
	const [iBottom, setIBottom] = useState('0%');
	const [iLeft, setILeft] = useState('0%');

	useEffect(() => {
		if (!value) {
			setType('custom');
			setRaw('');
			return;
		}
		
		const v = value.trim();
		if (v.startsWith('circle(')) {
			setType('circle');
			const m = v.match(/circle\(([^ ]+)\s+at\s+([^ ]+)\s+([^)]+)\)/);
			if (m) { setCRadius(m[1]); setCX(m[2]); setCY(m[3]); }
		} else if (v.startsWith('ellipse(')) {
			setType('ellipse');
			const m = v.match(/ellipse\(([^ ]+)\s+([^ ]+)\s+at\s+([^ ]+)\s+([^)]+)\)/);
			if (m) { setERx(m[1]); setERy(m[2]); setEX(m[3]); setEY(m[4]); }
		} else if (v.startsWith('inset(')) {
			setType('inset');
			const m = v.match(/inset\((.*?)\)/);
			if (m) {
				const p = m[1].trim().split(/\s+/);
				setITop(p[0] || '0%');
				setIRight(p[1] || p[0] || '0%');
				setIBottom(p[2] || p[0] || '0%');
				setILeft(p[3] || p[1] || p[0] || '0%');
			}
		} else {
			setType('custom');
			setRaw(v);
		}
	}, [value]);

	const updateCircle = (r, x, y) => {
		setCRadius(r); setCX(x); setCY(y);
		onChange(`circle(${r} at ${x} ${y})`);
	};

	const updateEllipse = (rx, ry, x, y) => {
		setERx(rx); setERy(ry); setEX(x); setEY(y);
		onChange(`ellipse(${rx} ${ry} at ${x} ${y})`);
	};

	const updateInset = (t, r, b, l) => {
		setITop(t); setIRight(r); setIBottom(b); setILeft(l);
		onChange(`inset(${t} ${r} ${b} ${l})`);
	};

	const updateCustom = (val) => {
		setRaw(val);
		onChange(val);
	};

	const handleTypeChange = (newType) => {
		setType(newType);
		if (newType === 'circle') onChange(`circle(${cRadius} at ${cX} ${cY})`);
		else if (newType === 'ellipse') onChange(`ellipse(${eRx} ${eRy} at ${eX} ${eY})`);
		else if (newType === 'inset') onChange(`inset(${iTop} ${iRight} ${iBottom} ${iLeft})`);
		else onChange(raw);
	};

	return (
		<Stack spacing={2} sx={{ width: '100%', mt: 0.5 }}>
			<Box>
				<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Shape Type</Typography>
				<Autocomplete
					size="small"
					options={SHAPES}
					value={SHAPES.find(s => s.value === type) || SHAPES[0]}
					getOptionLabel={(opt) => opt.label}
					isOptionEqualToValue={(opt, val) => opt.value === val.value}
					onChange={(_, next) => handleTypeChange(next ? next.value : 'custom')}
					disabled={disabled}
					disableClearable
					renderInput={(params) => <TextField {...params} size="small" />}
				/>
			</Box>

			{type === 'circle' && (
				<Stack spacing={1.5}>
					<Box>
						<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Radius</Typography>
						<TextField size="small" fullWidth disabled={disabled} value={cRadius} onChange={(e) => updateCircle(e.target.value, cX, cY)} />
					</Box>
					<Stack direction="row" spacing={1}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Position X</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={cX} onChange={(e) => updateCircle(cRadius, e.target.value, cY)} />
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Position Y</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={cY} onChange={(e) => updateCircle(cRadius, cX, e.target.value)} />
						</Box>
					</Stack>
				</Stack>
			)}

			{type === 'ellipse' && (
				<Stack spacing={1.5}>
					<Stack direction="row" spacing={1}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Radius X</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={eRx} onChange={(e) => updateEllipse(e.target.value, eRy, eX, eY)} />
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Radius Y</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={eRy} onChange={(e) => updateEllipse(eRx, e.target.value, eX, eY)} />
						</Box>
					</Stack>
					<Stack direction="row" spacing={1}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Position X</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={eX} onChange={(e) => updateEllipse(eRx, eRy, e.target.value, eY)} />
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Position Y</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={eY} onChange={(e) => updateEllipse(eRx, eRy, eX, e.target.value)} />
						</Box>
					</Stack>
				</Stack>
			)}

			{type === 'inset' && (
				<Stack spacing={1.5}>
					<Stack direction="row" spacing={1}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Top</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={iTop} onChange={(e) => updateInset(e.target.value, iRight, iBottom, iLeft)} />
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Right</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={iRight} onChange={(e) => updateInset(iTop, e.target.value, iBottom, iLeft)} />
						</Box>
					</Stack>
					<Stack direction="row" spacing={1}>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Bottom</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={iBottom} onChange={(e) => updateInset(iTop, iRight, e.target.value, iLeft)} />
						</Box>
						<Box sx={{ flex: 1 }}>
							<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Left</Typography>
							<TextField size="small" fullWidth disabled={disabled} value={iLeft} onChange={(e) => updateInset(iTop, iRight, iBottom, e.target.value)} />
						</Box>
					</Stack>
				</Stack>
			)}

			{type === 'custom' && (
				<Box>
					<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>CSS Value</Typography>
					<TextField 
						size="small" 
						fullWidth 
						disabled={disabled} 
						value={raw} 
						placeholder="polygon(50% 0%, 0% 100%, 100% 100%)"
						onChange={(e) => updateCustom(e.target.value)} 
					/>
				</Box>
			)}
		</Stack>
	);
}
