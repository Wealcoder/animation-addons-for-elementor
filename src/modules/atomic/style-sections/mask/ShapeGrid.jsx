/* eslint-env browser */

import * as React from 'react';
import { useCallback } from 'react';
import { Box, Button, Stack, Typography, styled } from '@elementor/ui';

import { CUSTOM_SHAPE, getShapes, unwrap } from './prop-type';
import { stringProp } from './use-mask-style-field';

/**
 * The shape picker for `mask-image`.
 *
 * A plain controlled component (value / onChange) rather than a bound control:
 * core's PropProvider plumbing that useBoundProp needs is not on the public
 * runtime surface, so the section owns its own read/write via
 * useMaskStyleField. See use-mask-style-field.js.
 */

const Tile = styled( Box, {
	shouldForwardProp: ( prop ) => 'isSelected' !== prop,
} )( ( { theme, isSelected } ) => ( {
	aspectRatio: '1 / 1',
	display: 'flex',
	alignItems: 'center',
	justifyContent: 'center',
	borderRadius: theme.shape?.borderRadius ?? 6,
	border: `1px solid ${ isSelected ? theme.palette.primary.main : theme.palette.divider }`,
	boxShadow: isSelected ? `0 0 0 1px ${ theme.palette.primary.main }` : 'none',
	background: isSelected ? 'rgba(127,127,127,0.12)' : 'transparent',
	cursor: 'pointer',
	padding: 6,
	transition: 'border-color .15s, box-shadow .15s',
	'&:hover': { borderColor: theme.palette.primary.main },
} ) );

// The shape files are solid silhouettes, so they are used as a MASK of a flat
// colour rather than as an <img>: one asset set that stays legible in both the
// light and dark panel themes.
const Swatch = styled( Box )( ( { theme } ) => ( {
	width: '100%',
	height: '100%',
	backgroundColor: theme.palette.text.primary,
	maskRepeat: 'no-repeat',
	maskPosition: 'center',
	maskSize: 'contain',
	WebkitMaskRepeat: 'no-repeat',
	WebkitMaskPosition: 'center',
	WebkitMaskSize: 'contain',
} ) );

function openSvgPicker() {
	return new Promise( ( resolve ) => {
		const wp = window.wp;
		if ( ! wp || ! wp.media ) {
			resolve( null );
			return;
		}

		const frame = wp.media( {
			title: 'Select Mask Image',
			multiple: false,
			library: { type: 'image' },
			button: { text: 'Use as mask' },
		} );

		frame.on( 'select', () => {
			const json = frame.state().get( 'selection' ).first()?.toJSON() ?? null;
			resolve( json && json.url ? { url: json.url } : null );
		} );

		frame.open();
	} );
}

export const ShapeGrid = ( { value, onChange, disabled } ) => {
	const shapes = getShapes();
	const inner = unwrap( value ) || {};
	const current = unwrap( inner.shape ) || '';
	const src = inner.src ?? null;

	const write = useCallback(
		( shape, nextSrc ) => {
			onChange( {
				$$type: 'aae-mask-image',
				value: {
					shape: stringProp( shape ),
					// Keep any existing upload so switching to a built-in shape
					// and back is not destructive.
					src: undefined === nextSrc ? src : nextSrc,
				},
			} );
		},
		[ onChange, src ]
	);

	const pickCustom = useCallback( async () => {
		const picked = await openSvgPicker();
		if ( ! picked ) {
			return;
		}

		write( CUSTOM_SHAPE, {
			$$type: 'image-src',
			value: {
				id: null,
				url: { $$type: 'url', value: picked.url },
			},
		} );
	}, [ write ] );

	const customUrl = unwrap( unwrap( src )?.url );

	return (
		<Stack gap={ 1 } sx={ { width: '100%' } }>
			<Box sx={ { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 0.75 } }>
				{ Object.keys( shapes ).map( ( slug ) => (
					<Tile
						key={ slug }
						isSelected={ slug === current }
						onClick={ () => ! disabled && write( slug ) }
						title={ shapes[ slug ].label || slug }
						role="button"
						aria-label={ shapes[ slug ].label || slug }
						aria-pressed={ slug === current }
					>
						<Swatch
							sx={ {
								maskImage: `url("${ shapes[ slug ].image }")`,
								WebkitMaskImage: `url("${ shapes[ slug ].image }")`,
							} }
						/>
					</Tile>
				) ) }
			</Box>

			<Stack direction="row" gap={ 1 }>
				<Button
					size="small"
					variant={ CUSTOM_SHAPE === current ? 'contained' : 'outlined' }
					color="secondary"
					disabled={ disabled }
					onClick={ pickCustom }
					fullWidth
				>
					{ 'Custom Mask' }
				</Button>
				{ current ? (
					<Button
						size="small"
						variant="text"
						color="secondary"
						disabled={ disabled }
						onClick={ () => onChange( null ) }
					>
						{ 'Clear' }
					</Button>
				) : null }
			</Stack>

			{ CUSTOM_SHAPE === current && customUrl ? (
				<Typography variant="caption" color="text.secondary" sx={ { wordBreak: 'break-all' } }>
					{ decodeURIComponent( String( customUrl ).split( '/' ).pop() ) }
				</Typography>
			) : null }
		</Stack>
	);
};
