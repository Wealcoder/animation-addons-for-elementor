/* eslint-env browser */

import * as React from 'react';
import { useState, useCallback, useEffect } from 'react';
import { Box, Stack, Typography, IconButton, Select, MenuItem, styled } from '@elementor/ui';
import { Image as ImageIcon, Pencil as EditIcon, Trash2 as TrashIcon, Upload as UploadIcon } from 'lucide-react';

/* -----------------------------------------------------------------------
 * Styled pieces
 * --------------------------------------------------------------------- */

const Wrapper = styled(Box)(({ theme }) => ({
	width: '100%',
	borderRadius: theme.shape?.borderRadius ?? 8,
	border: `1px solid ${theme.palette.divider}`,
	overflow: 'hidden',
	cursor: 'pointer',
	position: 'relative',
	background: '#262626',
	transition: 'border-color 0.2s ease, box-shadow 0.2s ease',
	'&:hover': {
		borderColor: theme.palette.primary.main,
		boxShadow: `0 0 0 1px ${theme.palette.primary.main}`,
	},
	'&:hover .aae-media-overlay': { opacity: 1 },
}));

const Thumbnail = styled('img')({
	display: 'block',
	width: '100%',
	height: 130,
	objectFit: 'cover',
});

const Placeholder = styled(Stack)(({ theme }) => ({
	height: 130,
	alignItems: 'center',
	justifyContent: 'center',
	backgroundImage: 'radial-gradient(circle at 20% 35%, rgba(255, 255, 255, 0.04) 22%, transparent 22%), linear-gradient(135deg, #1e1e1e 55%, #2a2a2a 55%)',
	gap: theme.spacing(1.5),
	padding: theme.spacing(2),
}));

const SelectImageButton = styled(Box)(({ theme }) => ({
	border: '1px solid #ffffff',
	borderRadius: '6px',
	color: '#ffffff',
	fontSize: '12px',
	fontWeight: 600,
	padding: '6px 20px',
	textAlign: 'center',
	backgroundColor: 'transparent',
	textTransform: 'none',
	letterSpacing: '0.5px',
	transition: 'background-color 0.2s ease, border-color 0.2s ease',
	'&:hover': {
		backgroundColor: 'rgba(255, 255, 255, 0.12)',
	},
}));

const UploadButton = styled(Box)(({ theme }) => ({
	display: 'flex',
	flexDirection: 'row',
	alignItems: 'center',
	gap: '4px',
	color: 'rgba(255, 255, 255, 0.85)',
	transition: 'color 0.2s ease, opacity 0.2s ease',
	cursor: 'pointer',
	'&:hover': {
		color: '#ffffff',
	},
}));

const Overlay = styled(Box)({
	position: 'absolute',
	inset: 0,
	background: 'rgba(0,0,0,0.45)',
	display: 'flex',
	alignItems: 'center',
	justifyContent: 'center',
	opacity: 0,
	transition: 'opacity 0.15s ease',
});

/* -----------------------------------------------------------------------
 * wp.media helpers
 * --------------------------------------------------------------------- */

/**
 * Open the WP media library frame and resolve with a base attachment object
 * { id, url, sizes } where sizes = { full, large, medium, thumbnail, … }.
 */
function openMediaFrame({ title = 'Select Image', tab = 'library' } = {}) {
	return new Promise((resolve) => {
		const wp = /** @type {any} */ (window.wp);
		if (!wp?.media) {
			// eslint-disable-next-line no-console
			console.warn('[AAE MediaInput] wp.media is not available.');
			resolve(null);
			return;
		}

		const frame = wp.media({
			title,
			multiple: false,
			library: { type: 'image' },
			button: { text: 'Use Image' },
		});

		frame.on('select', () => {
			const json = frame.state().get('selection').first()?.toJSON() ?? null;
			if (!json) { resolve(null); return; }
			resolve({
				id:    json.id    ?? null,
				url:   json.url   ?? '',
				sizes: json.sizes ?? {},   // e.g. { thumbnail:{url,width,height}, … }
			});
		});

		if (tab === 'upload') {
			frame.on('open', () => {
				const router = frame.views.get('router');
				if (router) {
					router.activate('upload');
				}
			});
		}

		frame.open();
	});
}

/**
 * Resolve the URL for a given size using the wp.media attachment cache.
 * Falls back to the `full` URL when the requested size doesn't exist.
 */
function resolveUrl(id, size, sizes) {
	if (!id) return sizes?.full?.url ?? '';
	const wp = /** @type {any} */ (window.wp);
	// Try cache first (populated by Elementor's media picker on re-open).
	const cached = wp?.media?.attachment?.(id)?.attributes;
	const sizesMap = cached?.sizes ?? sizes ?? {};
	return sizesMap[size]?.url ?? sizesMap.full?.url ?? '';
}

/**
 * Build a sorted label→value list from a sizes map.
 * Always includes 'full'.
 */
function buildSizeOptions(sizes = {}) {
	const LABEL = {
		thumbnail:   'Thumbnail',
		medium:      'Medium',
		medium_large:'Medium Large',
		large:       'Large',
		full:        'Full',
	};
	const keys = Array.from(
		new Set(['thumbnail', 'medium', 'medium_large', 'large', 'full',
			...Object.keys(sizes)])
	).filter((k) => k === 'full' || sizes[k]);

	return keys.map((k) => {
		const s = sizes[k];
		const dim = s ? ` (${s.width}×${s.height})` : '';
		return { value: k, label: (LABEL[k] ?? k) + dim };
	});
}

/* -----------------------------------------------------------------------
 * MediaInput
 *
 * value shape : { id, url, size, sizes } | null
 *   id    — WP attachment ID
 *   url   — resolved URL for the active size
 *   size  — selected size key (default: 'full')
 *   sizes — map of all available sizes (from wp.media JSON)
 *
 * Props:
 *   value    — current value object or null
 *   onChange — called with the new value object or null
 *   disabled
 * --------------------------------------------------------------------- */
export function MediaInput({ value, onChange, disabled }) {
	const normalised = (value && typeof value === 'object' && value.url) ? value : null;

	const id    = normalised?.id    ?? null;
	const url   = normalised?.url   ?? null;
	const size  = normalised?.size  ?? 'full';
	const sizes = normalised?.sizes ?? {};

	const sizeOptions = buildSizeOptions(sizes);

	/* -- pick a new image from WP media library -- */
	const handleOpen = useCallback(async (e, tab = 'library') => {
		if (e) {
			e.stopPropagation();
			e.preventDefault();
		}
		if (disabled) return;
		const picked = await openMediaFrame({ title: 'Select Image', tab });
		if (!picked) return;
		const activeSize = size ?? 'full';
		const resolvedUrl = resolveUrl(picked.id, activeSize, picked.sizes);
		onChange({
			id:    picked.id,
			url:   resolvedUrl || picked.url,
			size:  activeSize,
			sizes: picked.sizes,
		});
	}, [disabled, onChange, size]);

	/* -- change size of already-picked image -- */
	const handleSizeChange = useCallback((e) => {
		e.stopPropagation();
		const newSize = e.target.value;
		const newUrl  = resolveUrl(id, newSize, sizes);
		onChange({ id, url: newUrl || url, size: newSize, sizes });
	}, [id, url, sizes, onChange]);

	/* -- clear the image -- */
	const handleClear = useCallback((e) => {
		e.stopPropagation();
		onChange(null);
	}, [onChange]);

	return (
		<Stack spacing={0.5} sx={{ width: '100%' }}>
			{/* ---- Thumbnail / Placeholder ---- */}
			<Wrapper
				onClick={(e) => handleOpen(e, 'library')}
				role="button"
				tabIndex={disabled ? -1 : 0}
				onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') handleOpen(e, 'library'); }}
				aria-label={url ? 'Change image' : 'Select image'}
			>
				{url ? (
					<>
						<Thumbnail src={url} alt="Selected media" draggable={false} />
						<Overlay className="aae-media-overlay">
							<IconButton size="small" sx={{ color: '#fff', mr: 0.5 }}
								onClick={(e) => handleOpen(e, 'library')} aria-label="Replace image">
								<EditIcon size={16} />
							</IconButton>
							{!disabled && (
								<IconButton size="small" sx={{ color: '#ff6b6b' }}
									onClick={handleClear} aria-label="Remove image">
									<TrashIcon size={16} />
								</IconButton>
							)}
						</Overlay>
					</>
				) : (
					<Placeholder>
						{disabled ? (
							<Typography variant="caption" color="rgba(255,255,255,0.4)">
								No image
							</Typography>
						) : (
							<>
								<SelectImageButton onClick={(e) => handleOpen(e, 'library')}>
									Select image
								</SelectImageButton>
								<UploadButton onClick={(e) => handleOpen(e, 'upload')}>
									<UploadIcon size={13} strokeWidth={2.5} />
									<Typography sx={{ fontSize: '11px', fontWeight: 600, color: 'inherit' }}>
										Upload
									</Typography>
								</UploadButton>
							</>
						)}
					</Placeholder>
				)}
			</Wrapper>

			{/* ---- Resolution / Size selector (only when an image is picked) ---- */}
			{url && sizeOptions.length > 1 && (
				<Stack
					direction="row"
					alignItems="center"
					justifyContent="space-between"
					sx={{ mt: 2.5, width: '100%' }}
				>
					<Typography variant="caption" color="text.secondary" sx={{ fontWeight: 500, fontSize: '11px' }}>
						Resolution
					</Typography>
					<Select
						size="small"
						value={size}
						onChange={handleSizeChange}
						onClick={(e) => e.stopPropagation()}
						disabled={disabled}
						sx={{
							fontSize: '11px',
							height: '28px',
							minWidth: '100px',
							'& .MuiSelect-select': {
								py: '4px',
								px: '12px',
							},
						}}
					>
						{sizeOptions.map((opt) => (
							<MenuItem key={opt.value} value={opt.value} sx={{ fontSize: 11 }}>
								{opt.label}
							</MenuItem>
						))}
					</Select>
				</Stack>
			)}
		</Stack>
	);
}
