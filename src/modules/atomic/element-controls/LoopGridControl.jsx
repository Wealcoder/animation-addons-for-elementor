/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * LoopGridControl — pick the Loop Grid a filter drives from a dropdown.
 *
 * PHP: inc/AtomicWidgets/Controls/class-aae-loop-grid-control.php. Prop-bound
 * (stringPropTypeUtil in ./index.js) to the same String prop the old Text_Control
 * wrote (`target_grid`), so every saved page and the server side
 * (`Aaeaddon_A_Loop_Grid::resolve_grid_ref()`) are untouched.
 *
 * The list is every Loop Grid in the document being edited, read live from the
 * element tree and refreshed whenever an element is created, deleted, moved or
 * has a setting changed — so naming a grid, or dropping a second one, shows up
 * here with no reload. Each entry is labelled with the Grid ID the builder gave
 * it, or "Loop Grid 2" by position, plus its source and element id.
 *
 * Picking one stores its ELEMENT id, which survives the grid being renamed. A
 * value the list does not hold — a Grid ID typed by hand, or the id of a grid
 * that has since been deleted — is NEVER blanked: it stays in a text box, and
 * "Type an ID…" at the end of the list opens that box on purpose. A stored Grid
 * ID that does name one of the grids is shown as that grid.
 */

import * as React from 'react';
import { useState } from 'react';
import {
	__privateUseListenTo as useListenTo,
	commandEndEvent,
	v1ReadyEvent,
} from '@elementor/editor-v1-adapters';
import { useBoundProp } from '@elementor/editor-controls';
import { stringPropTypeUtil } from '@elementor/editor-props';
import { MenuItem, Select, Stack, TextField, Typography } from '@elementor/ui';
import { settingOf, typeOf } from './AcfFieldControl';

const CUSTOM = '__aae_custom__';
// Mirrors Aaeaddon_A_Loop_Grid::GRID_TYPES.
const GRID_TYPES = [ 'e-aae-a-loop-grid', 'e-aae-a-loop-grid-slider' ];

/** The same reduction the server applies to a Grid ID (grid_name_key()). */
const slug = ( v ) =>
	String( v || '' )
		.trim()
		.toLowerCase()
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-+|-+$/g, '' );

/** Every Loop Grid in the current document, in document order. */
function collectGrids() {
	const out = [];
	const walk = ( c ) => {
		if ( ! c ) {
			return;
		}
		if ( GRID_TYPES.includes( typeOf( c ) ) ) {
			const name = String( settingOf( c, 'grid_name' ) || '' ).trim();
			out.push( {
				id: String( c.id ),
				name,
				postType: String( settingOf( c, 'post_type' ) || 'post' ),
				label: name || `Loop Grid ${ out.length + 1 }`,
			} );
		}
		Array.from( c.children || [] ).forEach( walk );
	};
	try {
		walk( window.elementor?.documents?.getCurrent?.()?.container );
	} catch ( _e ) {
		// No document yet.
	}
	return out;
}

/** Re-read whenever the tree or any setting changes. */
function useGrids() {
	return useListenTo(
		[
			v1ReadyEvent(),
			commandEndEvent( 'document/elements/create' ),
			commandEndEvent( 'document/elements/delete' ),
			commandEndEvent( 'document/elements/duplicate' ),
			commandEndEvent( 'document/elements/move' ),
			commandEndEvent( 'document/elements/settings' ),
			commandEndEvent( 'document/elements/set-settings' ),
			commandEndEvent( 'document/history/undo' ),
			commandEndEvent( 'document/history/redo' ),
		],
		collectGrids,
		[]
	);
}

export function LoopGridControl() {
	const { value, setValue, disabled } = useBoundProp( stringPropTypeUtil );
	const current = String( value || '' ).trim();

	const grids = useGrids() || [];
	const [ manual, setManual ] = useState( false );

	// An element id always wins, exactly as it does on the server.
	const known =
		grids.find( ( g ) => g.id === current ) ||
		( current ? grids.find( ( g ) => g.name && slug( g.name ) === slug( current ) ) : undefined );

	const showBox = manual || ( !! current && ! known );
	const selectValue = known ? known.id : manual || current ? CUSTOM : '';

	const caption = ( () => {
		if ( ! grids.length ) {
			return 'There is no Loop Grid on this page yet.';
		}
		if ( current && ! known ) {
			return 'No grid on this page has this ID. It is kept as typed.';
		}
		if ( known ) {
			return `Drives ${ known.label } (${ known.postType }).`;
		}
		if ( grids.length > 1 ) {
			return `This page has ${ grids.length } Loop Grids — pick the one this should drive.`;
		}
		return 'Automatic: this page has one Loop Grid, so it is used.';
	} )();

	return (
		// The panel prints the control's label itself (SettingsField); a second
		// one here read as a duplicate.
		<Stack gap={ 0.5 }>
			<Select
				size="tiny"
				fullWidth
				disabled={ disabled }
				displayEmpty
				value={ selectValue }
				onChange={ ( e ) => {
					const v = e.target.value;
					if ( v === CUSTOM ) {
						setManual( true );
						return;
					}
					setManual( false );
					setValue( v );
				} }
				renderValue={ ( v ) => {
					if ( v === CUSTOM ) {
						return 'Typed ID';
					}
					const g = grids.find( ( x ) => x.id === v );
					return g ? g.label : <em>Automatic (the grid on this page)</em>;
				} }
			>
				<MenuItem value="">
					<em>Automatic (the grid on this page)</em>
				</MenuItem>
				{ grids.map( ( g ) => (
					<MenuItem key={ g.id } value={ g.id } sx={ { display: 'block' } }>
						<Typography variant="body2" component="span">{ g.label }</Typography>
						<Typography variant="caption" component="span" sx={ { color: 'text.secondary', ml: 0.75 } }>
							{ g.postType } · #{ g.id }
						</Typography>
					</MenuItem>
				) ) }
				<MenuItem value={ CUSTOM }>
					<em>Type an ID…</em>
				</MenuItem>
			</Select>

			{ showBox ? (
				<TextField
					size="tiny"
					fullWidth
					disabled={ disabled }
					value={ current }
					// Fixed, not the PHP-supplied one: Pro still passes the old text field's
					// "Leave blank for the grid on this page", which is wrong for a box that
					// only appears when the builder is TYPING an id.
					placeholder="Grid ID or element ID"
					onChange={ ( e ) => setValue( e.target.value ) }
				/>
			) : null }

			<Typography variant="caption" sx={ { color: 'text.secondary', lineHeight: 1.4 } }>
				{ caption }
			</Typography>
		</Stack>
	);
}
