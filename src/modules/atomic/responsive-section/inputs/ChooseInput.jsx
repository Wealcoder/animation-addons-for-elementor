/* eslint-env browser */

import * as React from 'react';
import { ToggleButtonGroup, ToggleButton } from '@elementor/ui';

export function ChooseInput({ value, onChange, disabled, options = [] }) {
	return (
		<ToggleButtonGroup
			value={value}
			exclusive
			onChange={(_, next) => {
				if (next !== null) {
					onChange(next);
				}
			}}
			disabled={disabled}
			size="small"
			sx={{ height: 32 }}
		>
			{options.map((opt) => (
				<ToggleButton
					key={opt.value}
					value={opt.value}
					title={opt.label || opt.title || opt.value}
					sx={{
						px: 1.5,
						py: 0.5,
						minWidth: 36,
					}}
				>
					{opt.icon ? (
						<span className={opt.icon} style={{ fontSize: '14px' }} />
					) : (
						opt.label || opt.value
					)}
				</ToggleButton>
			))}
		</ToggleButtonGroup>
	);
}
