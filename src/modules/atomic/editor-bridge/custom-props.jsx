/* eslint-env browser */

import * as React from 'react';

import {
	morphPanelRowToReact,
	useContainerSettingArray,
	Repeater,
	Select,
	TextInput,
} from '../ui-builder';

/**
 * Custom Properties — React-driven repeater.
 *
 * The Regular Animation panel exposes a "Custom Properties" Switch_Control as
 * a placeholder row (only when Animation = Custom). This module finds that
 * row by label and replaces its editable cell with a full property+value
 * repeater that writes to two parallel String_Array props:
 *
 *   aae_anim_custom_prop_keys    — array of GSAP property names
 *   aae_anim_custom_prop_values  — array of corresponding values (1:1 by index)
 *
 * Both arrays stay in sync because writes always go through writeBack() which
 * splits the single rows[] state into two array writes.
 */

/* GSAP property dropdown — mirrors Schema::custom_property_options() on the PHP side. */
const PROPERTY_OPTIONS = [
	{ value: 'none',            label: 'None' },
	{ value: 'opacity',         label: 'Opacity' },
	{ value: 'x',               label: 'X' },
	{ value: 'y',               label: 'Y' },
	{ value: 'width',           label: 'Width' },
	{ value: 'height',          label: 'Height' },
	{ value: 'scale',           label: 'Scale' },
	{ value: 'repeat',          label: 'Repeat' },
	{ value: 'rotate',          label: 'Rotate' },
	{ value: 'rotateX',         label: 'RotateX' },
	{ value: 'rotateY',         label: 'RotateY' },
	{ value: 'transformOrigin', label: 'TransformOrigin' },
	{ value: 'color',           label: 'Color' },
	{ value: 'background',      label: 'Background' },
	{ value: 'border',          label: 'Border' },
	{ value: 'boxShadow',       label: 'BoxShadow' },
	{ value: 'force3D',         label: 'Force3D' },
	{ value: 'delay',           label: 'Delay' },
	{ value: 'duration',        label: 'Duration' },
	{ value: 'maxWidth',        label: 'Max Width' },
	{ value: 'maxHeight',       label: 'Max Height' },
	{ value: 'minWidth',        label: 'Min Width' },
	{ value: 'minHeight',       label: 'Min Height' },
	{ value: 'mixBlendMode',    label: 'Mix Blend Mode' },
	{ value: 'padding',         label: 'Padding' },
	{ value: 'borderRadius',    label: 'Border Radius' },
	{ value: 'repeatDelay',     label: 'Repeat Delay' },
	{ value: 'scaleX',          label: 'ScaleX' },
	{ value: 'scaleY',          label: 'ScaleY' },
	{ value: 'xPercent',        label: 'XPercent' },
	{ value: 'yPercent',        label: 'YPercent' },
	{ value: 'autoAlpha',       label: 'Auto Alpha' },
	{ value: 'yoyo',            label: 'YoYo' },
];

const KEYS_PROP   = 'aae_anim_custom_prop_keys';
const VALUES_PROP = 'aae_anim_custom_prop_values';

function CustomPropsRepeater({ container }) {
	const [keys,   setKeys]   = useContainerSettingArray(container, KEYS_PROP);
	const [values, setValues] = useContainerSettingArray(container, VALUES_PROP);

	// Zip the two parallel arrays into [{ property, value }, ...]. Use the
	// longer of the two lengths so half-saved data still surfaces.
	const length = Math.max(keys.length, values.length);
	const rows = [];
	for (let i = 0; i < length; i++) {
		rows.push({
			property: keys[i]   || '',
			value:    values[i] || '',
		});
	}

	const writeBack = (nextRows) => {
		setKeys(nextRows.map((r) => r.property || ''));
		setValues(nextRows.map((r) => r.value || ''));
	};

	return (
		<div style={{ padding: '4px 0' }}>
			<Repeater
				items={rows}
				addLabel="Add Property"
				makeEmpty={() => ({ property: 'opacity', value: '' })}
				onChange={writeBack}
				renderRow={(row, index, update) => (
					<>
						<div style={{ flex: '1 1 0', minWidth: 0 }}>
							<Select
								value={row.property}
								options={PROPERTY_OPTIONS}
								onChange={(v) => update({ ...row, property: v })}
							/>
						</div>
						<div style={{ flex: '1 1 0', minWidth: 0 }}>
							<TextInput
								value={row.value}
								placeholder="value"
								onChange={(v) => update({ ...row, value: v })}
							/>
						</div>
					</>
				)}
			/>
		</div>
	);
}

/**
 * Wire-up entry. Called once after editor bootstrap.
 */
export function startCustomPropsBridge() {
	morphPanelRowToReact({
		label: 'Custom Properties',
		render: (container) => <CustomPropsRepeater container={container} />,
	});
}
