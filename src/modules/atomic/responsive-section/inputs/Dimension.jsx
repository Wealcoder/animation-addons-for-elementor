/* eslint-env browser */

import * as React from 'react';
import { useState } from 'react';
import { Stack, TextField, ToggleButtonGroup, ToggleButton, Box, Typography, IconButton } from '@elementor/ui';

/* ---------- link icon ---------- */
const LinkIcon = ({ linked }) => (
	<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
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

/* ==========================================================================
   DimensionInput (Single Value + Unit)
   ========================================================================== */
export function DimensionInput({
	value,
	onChange,
	disabled,
	placeholder = '',
	units = ['px', '%', 'em', 'rem', 'vh', 'vw'],
	defaultUnit = 'px',
}) {
	// Parse value
	let size = '';
	let unit = defaultUnit || (units.length > 0 ? units[0] : '');

	if (value && typeof value === 'object') {
		size = value.size !== undefined ? value.size : (value.value !== undefined ? value.value : '');
		unit = value.unit || unit;
	} else if (value !== null && value !== undefined && value !== '') {
		// If string like "100px", parse it
		const str = String(value).trim();
		const numMatch = str.match(/^(-?\d*(?:\.\d+)?)(.*)$/);
		if (numMatch) {
			size = numMatch[1];
			const parsedUnit = numMatch[2].trim();
			if (units.includes(parsedUnit)) {
				unit = parsedUnit;
			}
		} else {
			size = str;
		}
	}

	const handleSizeChange = (newSize) => {
		if (newSize === '') {
			onChange(null);
			return;
		}
		const num = Number(newSize);
		const finalSize = Number.isFinite(num) ? num : newSize;

		onChange({
			size: finalSize,
			unit
		});
	};

	const handleUnitChange = (e, newUnit) => {
		if (!newUnit) return;
		onChange({
			size: size === '' ? null : (Number.isFinite(Number(size)) ? Number(size) : size),
			unit: newUnit
		});
	};

	return (
		<Stack direction="row" alignItems="center" gap={1} sx={{ width: '100%' }}>
			<TextField
				size="tiny"
				value={size}
				placeholder={placeholder}
				disabled={disabled}
				onChange={(e) => handleSizeChange(e.target.value)}
				sx={{ flex: 1 }}
			/>

			{units.length > 0 && (
				<ToggleButtonGroup
					value={unit}
					exclusive
					onChange={handleUnitChange}
					disabled={disabled}
					size="small"
					sx={{ height: 26, flexShrink: 0 }}
				>
					{units.map((u) => (
						<ToggleButton
							key={u}
							value={u}
							sx={{
								px: 0.8,
								py: 0,
								fontSize: '10px',
								textTransform: 'none',
								height: '100%',
								minWidth: 26
							}}
						>
							{u}
						</ToggleButton>
					))}
				</ToggleButtonGroup>
			)}
		</Stack>
	);
}

/* ==========================================================================
   DimensionsInput (Top, Right, Bottom, Left + Unit + Link)
   ========================================================================== */
export function DimensionsInput({
	value,
	onChange,
	disabled,
	placeholder = '',
	units = ['px', '%', 'em', 'rem'],
	defaultUnit = 'px',
}) {
	// Parse value
	let top = '';
	let right = '';
	let bottom = '';
	let left = '';
	let unit = defaultUnit || (units.length > 0 ? units[0] : '');
	let isLinked = true;

	if (value && typeof value === 'object') {
		top = value.top !== undefined ? value.top : '';
		right = value.right !== undefined ? value.right : '';
		bottom = value.bottom !== undefined ? value.bottom : '';
		left = value.left !== undefined ? value.left : '';
		unit = value.unit || unit;
		isLinked = value.isLinked !== undefined ? value.isLinked : true;
	}

	const updateValues = (newTop, newRight, newBottom, newLeft, newUnit = unit, newLinked = isLinked) => {
		onChange({
			top: newTop === '' ? '' : (Number.isFinite(Number(newTop)) ? Number(newTop) : newTop),
			right: newRight === '' ? '' : (Number.isFinite(Number(newRight)) ? Number(newRight) : newRight),
			bottom: newBottom === '' ? '' : (Number.isFinite(Number(newBottom)) ? Number(newBottom) : newBottom),
			left: newLeft === '' ? '' : (Number.isFinite(Number(newLeft)) ? Number(newLeft) : newLeft),
			unit: newUnit,
			isLinked: newLinked,
		});
	};

	const handleValChange = (side, val) => {
		if (isLinked) {
			updateValues(val, val, val, val);
		} else {
			if (side === 'top') updateValues(val, right, bottom, left);
			if (side === 'right') updateValues(top, val, bottom, left);
			if (side === 'bottom') updateValues(top, right, val, left);
			if (side === 'left') updateValues(top, right, bottom, val);
		}
	};

	const toggleLink = () => {
		const nextLinked = !isLinked;
		if (nextLinked) {
			// Unify all sides to the top value when linking
			updateValues(top, top, top, top, unit, nextLinked);
		} else {
			updateValues(top, right, bottom, left, unit, nextLinked);
		}
	};

	const handleUnitChange = (e, newUnit) => {
		if (!newUnit) return;
		updateValues(top, right, bottom, left, newUnit);
	};

	return (
		<Stack direction="column" gap={1} sx={{ width: '100%' }}>
			{/* Top bar with units and Link button */}
			<Stack direction="row" justifyContent="flex-end" alignItems="center" gap={1}>
				{units.length > 0 && (
					<ToggleButtonGroup
						value={unit}
						exclusive
						onChange={handleUnitChange}
						disabled={disabled}
						size="small"
						sx={{ height: 24 }}
					>
						{units.map((u) => (
							<ToggleButton
								key={u}
								value={u}
								sx={{
									px: 0.6,
									py: 0,
									fontSize: '9px',
									textTransform: 'none',
									height: '100%',
									minWidth: 24
								}}
							>
								{u}
							</ToggleButton>
						))}
					</ToggleButtonGroup>
				)}

				<IconButton
					size="small"
					onClick={toggleLink}
					disabled={disabled}
					sx={{
						p: 0.5,
						border: '1px solid',
						borderColor: 'divider',
						borderRadius: 1,
						height: 24,
						width: 24
					}}
					title={isLinked ? 'Unlink values' : 'Link values'}
				>
					<LinkIcon linked={isLinked} />
				</IconButton>
			</Stack>

			{/* 4 inputs row */}
			<Stack direction="row" gap={0.5}>
				{[
					{ key: 'top', label: 'Top', val: top },
					{ key: 'right', label: 'Right', val: right },
					{ key: 'bottom', label: 'Bottom', val: bottom },
					{ key: 'left', label: 'Left', val: left },
				].map(({ key, label, val }) => (
					<Stack key={key} direction="column" alignItems="center" gap={0.25} sx={{ flex: 1, minWidth: 0 }}>
						<TextField
							size="tiny"
							value={val}
							placeholder={placeholder}
							disabled={disabled}
							onChange={(e) => handleValChange(key, e.target.value)}
							inputProps={{
								style: { textAlign: 'center', padding: '4px 2px' }
							}}
							fullWidth
						/>
						<Typography variant="caption" sx={{ fontSize: '9px', color: 'text.secondary' }}>
							{label}
						</Typography>
					</Stack>
				))}
			</Stack>
		</Stack>
	);
}
