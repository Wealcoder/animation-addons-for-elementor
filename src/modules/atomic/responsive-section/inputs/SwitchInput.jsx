/* eslint-env browser */

import * as React from 'react';
import { Switch } from '@elementor/ui';

/** Plain Switch input. */
export function SwitchInput({ value, onChange, disabled }) {
	return (
		<Switch
			size="small"
			checked={!!value}
			disabled={disabled}
			onChange={(_, checked) => onChange(checked)}
		/>
	);
}
