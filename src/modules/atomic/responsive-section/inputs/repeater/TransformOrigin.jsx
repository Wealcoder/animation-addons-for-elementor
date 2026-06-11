import { useState, useEffect } from 'react';
import { Stack, TextField, Box, Typography, InputAdornment } from '@elementor/ui';

export function TransformOrigin({ value, onChange, disabled }) {
	const [left, setLeft] = useState('50');
	const [top, setTop] = useState('50');

	useEffect(() => {
		if (!value) {
			setLeft('50');
			setTop('50');
			return;
		}
		const parts = String(value).trim().split(/\s+/);
		let l = '50', t = '50';
		
		if (parts[0]) {
			if (parts[0] === 'left') l = '0';
			else if (parts[0] === 'center') l = '50';
			else if (parts[0] === 'right') l = '100';
			else l = parts[0].replace('%', '');
		}
		
		if (parts[1]) {
			if (parts[1] === 'top') t = '0';
			else if (parts[1] === 'center') t = '50';
			else if (parts[1] === 'bottom') t = '100';
			else t = parts[1].replace('%', '');
		}
		
		setLeft(l);
		setTop(t);
	}, [value]);

	const handleUpdate = (l, t) => {
		setLeft(l);
		setTop(t);
		onChange(`${l}% ${t}%`);
	};

	const isMatch = (l, t) => left === String(l) && top === String(t);

	const renderDot = (l, t) => (
		<Box
			onClick={() => { if (!disabled) handleUpdate(String(l), String(t)); }}
			sx={{
				width: 8,
				height: 8,
				borderRadius: '50%',
				backgroundColor: isMatch(l, t) ? 'primary.main' : 'divider',
				cursor: disabled ? 'default' : 'pointer',
				transition: 'background-color 0.2s',
				'&:hover': {
					backgroundColor: isMatch(l, t) ? 'primary.main' : 'text.secondary',
				}
			}}
		/>
	);

	return (
		<Stack direction="row" spacing={3} alignItems="center">
			<Box sx={{
				display: 'grid',
				gridTemplateColumns: 'repeat(3, 1fr)',
				gap: 2.5,
				p: 2.5,
				bgcolor: 'background.paper',
				border: '1px solid',
				borderColor: 'divider',
				borderRadius: 2
			}}>
				{renderDot(0, 0)} {renderDot(50, 0)} {renderDot(100, 0)}
				{renderDot(0, 50)} {renderDot(50, 50)} {renderDot(100, 50)}
				{renderDot(0, 100)} {renderDot(50, 100)} {renderDot(100, 100)}
			</Box>

			<Stack spacing={2} sx={{ width: 100 }}>
				<Box>
					<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Left</Typography>
					<TextField
						size="small"
						type="number"
						fullWidth
						disabled={disabled}
						value={left}
						onChange={(e) => handleUpdate(e.target.value, top)}
						InputProps={{
							endAdornment: <InputAdornment position="end">%</InputAdornment>
						}}
					/>
				</Box>
				<Box>
					<Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>Top</Typography>
					<TextField
						size="small"
						type="number"
						fullWidth
						disabled={disabled}
						value={top}
						onChange={(e) => handleUpdate(left, e.target.value)}
						InputProps={{
							endAdornment: <InputAdornment position="end">%</InputAdornment>
						}}
					/>
				</Box>
			</Stack>
		</Stack>
	);
}
