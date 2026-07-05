/* eslint-env browser */

/**
 * AAE Query Chips — AJAX-searchable MULTI select for atomic panels.
 *
 * The prop-bound counterpart of Elementor's single-value `query` control
 * (the multi `query-chips` control only exists in the 4.3-dev packages, not
 * in the installed 4.1.x runtime). Registered under type 'aae-query-chips';
 * the PHP side is AAE_Query_Chips_Control, which passes { kind, taxonomy,
 * placeholder } and binds a String_Array prop.
 *
 * Storage format: each selected option is ONE string of the bound
 * String_Array — a JSON object `{"id":123,"label":"Hello"}`. The id feeds
 * WP_Query on the server (AAE_A_Loop_Grid::build_query_args), the label
 * re-hydrates the chip when the panel reopens, no lookup round-trip needed.
 *
 * Search goes through admin-ajax `aae_loop_query_options` (kind: 'post'
 * searches titles/IDs across public post types, kind: 'term' searches one
 * taxonomy's terms). An empty search returns the 20 most recent / first 20 —
 * so opening the field shows browsable options immediately.
 */

import * as React from 'react';
import { useMemo, useRef, useState } from 'react';
import { useBoundProp } from '@elementor/editor-controls';
import { useElement } from '@elementor/editor-editing-panel';
import { stringArrayPropTypeUtil, stringPropTypeUtil } from '@elementor/editor-props';
import { Autocomplete, TextField } from '@elementor/ui';

const DEBOUNCE_MS = 250;

function parseChip( raw ) {
	// String_Array items are WRAPPED string props ({ $$type: 'string', value })
	// — the save-time schema validation rejects bare strings. Unwrap first,
	// but tolerate a plain string too (older saved data).
	const str = raw && typeof raw === 'object' && '$$type' in raw ? raw.value : raw;
	try {
		const o = JSON.parse( str );
		if ( o && o.id !== undefined && o.id !== null ) {
			return { id: String( o.id ), label: String( o.label ?? o.id ) };
		}
	} catch ( _e ) {
		// Not JSON — tolerate a bare numeric id string.
		if ( /^\d+$/.test( String( str ) ) ) {
			return { id: String( str ), label: String( str ) };
		}
	}
	return null;
}

async function fetchOptions( { kind, taxonomy, search, postType } ) {
	const cfg = window.AAE_LOOP_GRID || {};
	if ( ! cfg.ajaxUrl || ! cfg.nonce ) {
		return [];
	}
	const body = new FormData();
	body.append( 'action', 'aae_loop_query_options' );
	body.append( 'nonce', cfg.nonce );
	body.append( 'kind', kind );
	if ( taxonomy ) {
		body.append( 'taxonomy', taxonomy );
	}
	if ( postType ) {
		body.append( 'post_type', postType );
	}
	body.append( 'search', search || '' );
	try {
		const res = await fetch( cfg.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } );
		const json = await res.json();
		if ( json && json.success && Array.isArray( json.data?.options ) ) {
			return json.data.options.map( ( o ) => ( { id: String( o.id ), label: String( o.label ) } ) );
		}
	} catch ( _e ) {
		// Network/parse failure — just show no options.
	}
	return [];
}

export const QueryChipsControl = ( props ) => {
	const { kind = 'post', taxonomy = '', placeholder } = props || {};
	const { value, setValue, disabled } = useBoundProp( stringArrayPropTypeUtil );

	const [ options, setOptions ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const timerRef = useRef( null );
	const requestSeq = useRef( 0 );

	const chips = useMemo(
		() => ( Array.isArray( value ) ? value : [] ).map( parseChip ).filter( Boolean ),
		[ value ]
	);

	const selectedIds = useMemo( () => new Set( chips.map( ( c ) => c.id ) ), [ chips ] );

	const runSearch = ( search ) => {
		const seq = ++requestSeq.current;
		setLoading( true );
		fetchOptions( { kind, taxonomy, search } ).then( ( result ) => {
			if ( seq !== requestSeq.current ) {
				return; // A newer search superseded this one.
			}
			setOptions( result.filter( ( o ) => ! selectedIds.has( o.id ) ) );
			setLoading( false );
		} );
	};

	const handleInputChange = ( _event, term, reason ) => {
		if ( reason === 'reset' ) {
			return; // Selection made — don't refetch on the cleared input.
		}
		if ( timerRef.current ) {
			clearTimeout( timerRef.current );
		}
		timerRef.current = setTimeout( () => runSearch( term ), DEBOUNCE_MS );
	};

	const handleOpen = () => {
		runSearch( '' );
	};

	const handleChange = ( _event, newValue ) => {
		setValue(
			newValue.map( ( option ) =>
				stringPropTypeUtil.create(
					JSON.stringify( { id: Number( option.id ), label: option.label } )
				)
			)
		);
	};

	return (
		<Autocomplete
			multiple
			fullWidth
			size="tiny"
			disabled={ disabled }
			loading={ loading }
			value={ chips }
			options={ options }
			onChange={ handleChange }
			onInputChange={ handleInputChange }
			onOpen={ handleOpen }
			getOptionLabel={ ( option ) => option.label }
			isOptionEqualToValue={ ( option, val ) => option.id === val.id }
			filterOptions={ ( opts ) => opts }
			renderInput={ ( params ) => (
				<TextField { ...params } placeholder={ placeholder || 'Search…' } />
			) }
		/>
	);
};
