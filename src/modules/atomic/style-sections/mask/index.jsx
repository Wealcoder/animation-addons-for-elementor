/* eslint-env browser */

/**
 * Mask — a Style-tab section for e-flexbox / e-div-block / e-grid.
 *
 * These are REAL atomic style props (registered on the styles schema in
 * inc/Atomic/Mask/Schema.php), not settings props, so per-breakpoint values,
 * `:hover` and other state variants and global classes all come from
 * Elementor's own styles engine, and the CSS compiles into the element's
 * stylesheet — no runtime JS on the published page.
 *
 * v3's mask (Common_Base::_section_masking) exists only for widgets; a
 * container could never be masked. This is new capability, not a port.
 *
 * ONLY public runtime API is used here. Several things that look importable
 * from `@elementor/editor-editing-panel` — StylesField, StylesFieldLayout,
 * Section — are exports of the package's internal modules but are NOT on the
 * `elementorV2.editorEditingPanel` global, so importing them silently yields
 * `undefined` and React throws #130 the moment the section body mounts, which
 * the panel's error boundary shows as the section disappearing on click.
 * Anything added here must be checked against the live global, not the bundle.
 */

import * as React from 'react';
import {
	SectionContent,
	StyleTabSection,
	injectIntoStyleTab,
	useElement,
} from '@elementor/editor-editing-panel';
import { styleTransformersRegistry } from '@elementor/editor-canvas';
import { MenuItem, Select, Stack, Typography } from '@elementor/ui';

import { ShapeGrid } from './ShapeGrid';
import {
	MASK_IMAGE_KEY,
	MASK_POSITION_KEY,
	MASK_REPEAT_KEY,
	MASK_SIZE_KEY,
	maskImagePropTypeUtil,
	unwrap,
} from './prop-type';
import { maskImageTransformer } from './transformer';
import { stringProp, useMaskStyleField } from './use-mask-style-field';

/** Only containers — masking a heading or a button is meaningless. */
const TARGET_TYPES = [ 'e-flexbox', 'e-div-block', 'e-grid' ];

const SIZE_OPTIONS = [
	{ value: 'contain', label: 'Fit' },
	{ value: 'cover', label: 'Fill' },
	{ value: 'auto', label: 'Auto' },
];

const POSITION_OPTIONS = [
	{ value: 'center center', label: 'Center Center' },
	{ value: 'center left', label: 'Center Left' },
	{ value: 'center right', label: 'Center Right' },
	{ value: 'top center', label: 'Top Center' },
	{ value: 'top left', label: 'Top Left' },
	{ value: 'top right', label: 'Top Right' },
	{ value: 'bottom center', label: 'Bottom Center' },
	{ value: 'bottom left', label: 'Bottom Left' },
	{ value: 'bottom right', label: 'Bottom Right' },
];

const REPEAT_OPTIONS = [
	{ value: 'no-repeat', label: 'No-repeat' },
	{ value: 'repeat', label: 'Repeat' },
	{ value: 'repeat-x', label: 'Repeat-x' },
	{ value: 'repeat-y', label: 'Repeat-y' },
	{ value: 'round', label: 'Round' },
	{ value: 'space', label: 'Space' },
];

/** Label + control on one row, matching the panel's own field rhythm. */
const Row = ( { label, children } ) => (
	<Stack direction="row" alignItems="center" justifyContent="space-between" gap={ 1 }>
		<Typography variant="caption" color="text.secondary" sx={ { flexShrink: 0 } }>
			{ label }
		</Typography>
		{ children }
	</Stack>
);

const EnumRow = ( { label, bind, options, placeholder } ) => {
	const { value, setValue, canEdit } = useMaskStyleField( bind );
	const current = unwrap( value ) ?? '';

	return (
		<Row label={ label }>
			<Select
				size="small"
				displayEmpty
				disabled={ ! canEdit }
				value={ current }
				onChange={ ( event ) => {
					const next = event.target.value;
					setValue( next ? stringProp( next ) : null );
				} }
				sx={ { minWidth: 130, fontSize: 11 } }
			>
				<MenuItem value="">
					<em>{ placeholder }</em>
				</MenuItem>
				{ options.map( ( option ) => (
					<MenuItem key={ option.value } value={ option.value } sx={ { fontSize: 11 } }>
						{ option.label }
					</MenuItem>
				) ) }
			</Select>
		</Row>
	);
};

const ShapeRow = () => {
	const { value, setValue, canEdit } = useMaskStyleField( MASK_IMAGE_KEY );

	return (
		<Stack gap={ 0.75 }>
			<Typography variant="caption" color="text.secondary">
				{ 'Shape' }
			</Typography>
			<ShapeGrid value={ value } onChange={ setValue } disabled={ ! canEdit } />
		</Stack>
	);
};

const MaskSectionContent = () => (
	<SectionContent>
		<ShapeRow />
		<EnumRow label="Size" bind={ MASK_SIZE_KEY } options={ SIZE_OPTIONS } placeholder="Default" />
		<EnumRow label="Position" bind={ MASK_POSITION_KEY } options={ POSITION_OPTIONS } placeholder="Default" />
		<EnumRow label="Repeat" bind={ MASK_REPEAT_KEY } options={ REPEAT_OPTIONS } placeholder="Default" />
	</SectionContent>
);

const MaskSection = () => {
	const { element } = useElement();

	// The slot renders every injected section for every element type, so the
	// type gate has to live here.
	if ( ! element || ! TARGET_TYPES.includes( element.type ) ) {
		return null;
	}

	return (
		<StyleTabSection
			section={ {
				component: MaskSectionContent,
				name: 'Mask',
				title: 'Mask',
			} }
			fields={ [ MASK_IMAGE_KEY, MASK_SIZE_KEY, MASK_POSITION_KEY, MASK_REPEAT_KEY ] }
		/>
	);
};

let registered = false;

export function registerMaskStyleSection() {
	if ( registered ) {
		return;
	}
	registered = true;

	try {
		// Without this the canvas renders no mask at all while the published
		// page renders it fine — see transformer.js.
		styleTransformersRegistry.register( maskImagePropTypeUtil.key, maskImageTransformer );
	} catch ( e ) {
		// Already registered (HMR / double init) — not fatal.
	}

	try {
		injectIntoStyleTab( { id: 'aae-mask', component: MaskSection } );
	} catch ( e ) {
		// Older Elementor with no style-tab slot: the props still save and
		// still render, they just get no panel UI.
	}
}
