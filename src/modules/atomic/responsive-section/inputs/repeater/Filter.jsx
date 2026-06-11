import { useState, useEffect } from 'react';
import { Stack, TextField, Box, Typography, IconButton, Button } from '@elementor/ui';

const AVAILABLE_FILTERS = [
	{ id: 'blur', label: 'Blur', default: '0px' },
	{ id: 'brightness', label: 'Brightness', default: '1' },
	{ id: 'contrast', label: 'Contrast', default: '100%' },
	{ id: 'grayscale', label: 'Grayscale', default: '100%' },
	{ id: 'hue-rotate', label: 'Hue Rotate', default: '0deg' },
	{ id: 'invert', label: 'Invert', default: '100%' },
	{ id: 'saturate', label: 'Saturate', default: '100%' },
	{ id: 'sepia', label: 'Sepia', default: '100%' },
	{ id: 'drop-shadow', label: 'Drop Shadow', default: '0px 0px 0px #000' }
];

export function Filter({ value, onChange, disabled, onClose }) {
	const [filters, setFilters] = useState([]);
	const [view, setView] = useState('main'); // 'main' | 'add'

	useEffect(() => {
		if (!value) {
			setFilters([]);
			return;
		}
		const matches = [...String(value).matchAll(/([a-z-]+)\(([^)]+)\)/g)];
		setFilters(matches.map(m => ({ id: m[1], val: m[2] })));
	}, [value]);

	const handleApply = () => {
		const str = filters.map(f => `${f.id}(${f.val})`).join(' ');
		onChange(str);
		if (onClose) onClose();
	};

	const updateFilter = (index, val) => {
		const next = [...filters];
		next[index].val = val;
		setFilters(next);
	};

	const removeFilter = (index) => {
		const next = filters.filter((_, i) => i !== index);
		setFilters(next);
	};

	const addFilter = (id) => {
		const def = AVAILABLE_FILTERS.find(f => f.id === id)?.default || '';
		setFilters([...filters, { id, val: def }]);
		setView('main');
	};

	const headerStyle = {
		display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2, pb: 1, borderBottom: '1px solid', borderColor: 'divider'
	};

	if (view === 'add') {
		return (
			<Stack spacing={2} sx={{ width: 280 }}>
				<Box sx={headerStyle}>
					<Typography variant="subtitle2" fontWeight="bold">Filter Properties</Typography>
					<IconButton size="small" onClick={onClose} sx={{ p: 0.5 }}>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
					</IconButton>
				</Box>
				
				<Stack spacing={0.5} sx={{ maxHeight: 300, overflowY: 'auto', mt: 0 }}>
					{AVAILABLE_FILTERS.map(f => (
						<Box
							key={f.id}
							onClick={() => addFilter(f.id)}
							sx={{
								display: 'flex', alignItems: 'center', p: 1, 
								cursor: 'pointer', borderRadius: 1,
								'&:hover': { bgcolor: 'action.hover' }
							}}
						>
							<svg style={{ marginRight: 8, opacity: 0.5 }} width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
							<Typography variant="body2">{f.label}</Typography>
						</Box>
					))}
				</Stack>
				
				<Stack direction="row" spacing={1} sx={{ mt: 1 }}>
					<Button size="small" variant="contained" color="inherit" onClick={() => setView('main')} sx={{ textTransform: 'none' }}>Close</Button>
					<Button size="small" variant="contained" color="primary" onClick={handleApply} sx={{ textTransform: 'none' }}>Apply</Button>
				</Stack>
			</Stack>
		);
	}

	return (
		<Stack spacing={2} sx={{ width: 280 }}>
			<Box sx={headerStyle}>
				<Typography variant="subtitle2" fontWeight="bold">Filter Properties</Typography>
				<IconButton size="small" onClick={onClose} sx={{ p: 0.5 }}>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
				</IconButton>
			</Box>

			<Box
				onClick={() => setView('add')}
				sx={{
					display: 'flex', justifyContent: 'space-between', alignItems: 'center',
					cursor: 'pointer', p: 1, borderRadius: 1, mt: 0,
					'&:hover': { bgcolor: 'action.hover' }
				}}
			>
				<Typography variant="subtitle2" fontWeight="bold">Add Filter Properties</Typography>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
			</Box>

			<Stack spacing={1.5} sx={{ maxHeight: 300, overflowY: 'auto' }}>
				{filters.map((f, i) => {
					const label = AVAILABLE_FILTERS.find(a => a.id === f.id)?.label || f.id;
					return (
						<Stack key={i} direction="row" spacing={1} alignItems="center">
							<Box sx={{ display: 'flex', alignItems: 'center', width: 90 }}>
								<Typography variant="caption">{label}</Typography>
								<svg style={{ marginLeft: 4, opacity: 0.5 }} width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
							</Box>
							<TextField
								size="small"
								fullWidth
								value={f.val}
								onChange={(e) => updateFilter(i, e.target.value)}
								sx={{ flex: 1 }}
							/>
							<IconButton size="small" onClick={() => removeFilter(i)}>
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
							</IconButton>
						</Stack>
					);
				})}
			</Stack>

			<Stack direction="row" spacing={1} sx={{ mt: 1 }}>
				<Button size="small" variant="contained" color="inherit" onClick={onClose} sx={{ textTransform: 'none' }}>Back</Button>
				<Button size="small" variant="contained" color="primary" onClick={handleApply} sx={{ textTransform: 'none' }}>Apply</Button>
			</Stack>
		</Stack>
	);
}
