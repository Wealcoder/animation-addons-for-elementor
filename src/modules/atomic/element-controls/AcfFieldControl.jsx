/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * AcfFieldControl — pick an ACF field from a dropdown instead of pasting its key.
 *
 * PHP: inc/AtomicWidgets/Controls/class-aae-acf-field-control.php. Prop-bound
 * (stringPropTypeUtil in ./index.js) to the same String prop the old
 * Text_Control wrote — the value is still the field KEY, so the server side
 * (`Loop_Filter_Auth::resolve_acf()`) and every saved page are untouched.
 *
 * The list is narrowed to ONE post type, resolved per element, in this order:
 *   1. the sibling `postTypeProp` (the builder's own "Post type" pick);
 *   2. the Loop Grid this filter targets — `gridProp` names its element id, empty
 *      means the page's only grid — and that grid's `post_type`;
 *   3. the document's Preview Settings (`preview_type`, a theme-builder /
 *      loop template: `single/property`, `post_type_archive/property`).
 * With nothing resolved every post-scoped field is offered. Fields whose
 * group sits on another post type are hidden, because they can never match.
 *
 * The catalogue is fetched once per session (`aae_loop_query_options`,
 * kind=acf_field); a failed fetch is not memoised so a later mount retries.
 *
 * A key the list does not hold — typed by hand, or a field from a post type
 * the resolver did not pick — is NEVER blanked: the control shows it in a
 * text box instead, and "Type a key…" at the end of the list opens that box
 * on purpose.
 */

import * as React from 'react';
import { useEffect, useMemo, useState } from 'react';
import { getContainer } from '@elementor/editor-elements';
import {
	__privateUseListenTo as useListenTo,
	commandEndEvent,
	v1ReadyEvent,
} from '@elementor/editor-v1-adapters';
import { useElement } from '@elementor/editor-editing-panel';
import { useBoundProp } from '@elementor/editor-controls';
import { stringPropTypeUtil } from '@elementor/editor-props';
import { ListSubheader, MenuItem, Select, Stack, TextField, Typography } from '@elementor/ui';

const CUSTOM = '__aae_custom__';
const GRID_TYPE = 'e-aae-a-loop-grid';

let catalogPromise = null;

/** { acf: bool, fields: [] } — one request per editor session. */
export function loadAcfCatalog() {
	if ( catalogPromise ) {
		return catalogPromise;
	}
	const cfg = window.AAE_LOOP_GRID || {};
	if ( ! cfg.ajaxUrl || ! cfg.nonce ) {
		return Promise.resolve( { acf: false, fields: [] } );
	}
	const body = new FormData();
	body.append( 'action', 'aae_loop_query_options' );
	body.append( 'nonce', cfg.nonce );
	body.append( 'kind', 'acf_field' );
	catalogPromise = fetch( cfg.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
		.then( ( r ) => r.json() )
		.then( ( json ) => {
			if ( ! json || ! json.success ) {
				throw new Error( 'acf catalogue' );
			}
			return {
				acf: !! json.data?.acf,
				fields: Array.isArray( json.data?.options ) ? json.data.options : [],
			};
		} )
		.catch( () => {
			catalogPromise = null;
			return { acf: true, fields: [], failed: true };
		} );
	return catalogPromise;
}

/** Unwrap one { $$type, value } setting off a container. */
function settingOf( container, key ) {
	try {
		const raw = container?.model?.get( 'settings' )?.get( key );
		return raw && typeof raw === 'object' && 'value' in raw ? raw.value : raw;
	} catch ( _e ) {
		return undefined;
	}
}

function typeOf( container ) {
	const m = container?.model;
	return m?.get?.( 'widgetType' ) || m?.get?.( 'elType' ) || '';
}

/** Every Loop Grid container in the current document. */
function findGrids() {
	const out = [];
	const walk = ( c ) => {
		if ( ! c ) {
			return;
		}
		if ( typeOf( c ) === GRID_TYPE ) {
			out.push( c );
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

/** 'single/property' | 'post_type_archive/property' → 'property'. */
function previewPostType() {
	try {
		const raw = String( window.elementor?.settings?.page?.model?.get?.( 'preview_type' ) || '' );
		const [ kind, type ] = raw.split( '/' );
		if ( type && ( kind === 'single' || kind === 'post_type_archive' ) ) {
			return type;
		}
	} catch ( _e ) {
		// Not a theme-builder document.
	}
	return '';
}

/**
 * The post type a filter element's ACF list should be narrowed to, and where
 * that answer came from. Shared with the canvas mirror (Pro) through
 * window.AAEAcfField so the panel and the canvas cannot disagree.
 */
export function resolvePostType( elementId, postTypeProp = 'acf_post_type', gridProp = 'target_grid' ) {
	const self = getContainer( elementId );
	const manual = String( settingOf( self, postTypeProp ) || '' ).trim();
	if ( manual ) {
		return { postType: manual, from: 'manual' };
	}
	const wanted = String( settingOf( self, gridProp ) || '' ).trim();
	const grids = findGrids();
	// `target_grid` holds the grid's ELEMENT id — what the authoriser matches.
	const grid = wanted
		? grids.find( ( g ) => String( g.id ) === wanted )
		: grids.length === 1 ? grids[ 0 ] : null;
	const gridType = String( settingOf( grid, 'post_type' ) || '' );
	if ( gridType && gridType !== 'related' && gridType !== 'current_query' ) {
		return { postType: gridType, from: 'grid' };
	}
	const preview = previewPostType();
	if ( preview ) {
		return { postType: preview, from: 'preview' };
	}
	return { postType: '', from: grids.length > 1 && ! wanted ? 'ambiguous' : 'none' };
}

/** Re-read whenever a setting changes anywhere in the document. */
function useResolvedPostType( elementId, postTypeProp, gridProp ) {
	return useListenTo(
		[
			v1ReadyEvent(),
			commandEndEvent( 'document/elements/settings' ),
			commandEndEvent( 'document/elements/set-settings' ),
		],
		() => resolvePostType( elementId, postTypeProp, gridProp ),
		[ elementId, postTypeProp, gridProp ]
	);
}

function fieldMatches( field, postType, types ) {
	if ( types.length && ! types.includes( field.type ) ) {
		return false;
	}
	if ( ! postType ) {
		return true;
	}
	const pts = Array.isArray( field.post_types ) ? field.post_types : [];
	return pts.includes( '*' ) || pts.includes( postType );
}

window.AAEAcfField = { loadCatalog: loadAcfCatalog, resolvePostType };

export function AcfFieldControl( props ) {
	const { types = [], postTypeProp = 'acf_post_type', gridProp = 'target_grid', placeholder } = props || {};
	const { element } = useElement();
	const elementId = element.id;

	const { value, setValue, disabled } = useBoundProp( stringPropTypeUtil );
	const current = String( value || '' );

	const [ catalog, setCatalog ] = useState( null );
	const [ manual, setManual ] = useState( false );

	useEffect( () => {
		let alive = true;
		loadAcfCatalog().then( ( c ) => alive && setCatalog( c ) );
		return () => {
			alive = false;
		};
	}, [] );

	const { postType, from } = useResolvedPostType( elementId, postTypeProp, gridProp );

	const offered = useMemo( () => {
		const fields = catalog?.fields || [];
		return fields.filter( ( f ) => fieldMatches( f, postType, types ) );
	}, [ catalog, postType, types ] );

	// Grouped by field group, in catalogue (ACF menu) order.
	const groups = useMemo( () => {
		const map = new Map();
		offered.forEach( ( f ) => {
			const g = f.group || 'Fields';
			if ( ! map.has( g ) ) {
				map.set( g, [] );
			}
			map.get( g ).push( f );
		} );
		return Array.from( map.entries() );
	}, [ offered ] );

	const known = offered.some( ( f ) => f.key === current );
	const noAcf = !! catalog && ! catalog.acf;
	const showBox = noAcf || manual || ( current && ! known );

	const caption = ( () => {
		if ( ! catalog ) {
			return 'Loading fields…';
		}
		if ( noAcf ) {
			return 'ACF is not active on this site — type the field key.';
		}
		if ( current && ! known && ! manual ) {
			return postType
				? `This key is not among ${ postType }'s fields. It is kept as typed.`
				: 'This key is not in the ACF field list. It is kept as typed.';
		}
		if ( postType ) {
			const src = {
				manual: 'your Post type pick',
				grid: 'the Loop Grid',
				preview: "this template's Preview Settings",
			}[ from ];
			return `Fields for ${ postType } — from ${ src }.`;
		}
		if ( from === 'ambiguous' ) {
			return 'Several grids on this page: set Target grid, or pick a Post type above.';
		}
		return 'No grid to read the post type from — every field is listed. Pick a Post type above to narrow it.';
	} )();

	const selectValue = known ? current : ( manual || current ? CUSTOM : '' );

	return (
		// The panel prints the control's label itself (SettingsField); a second
		// one here read as a duplicate.
		<Stack gap={ 0.5 }>
			{ ! noAcf ? (
				<Select
					size="tiny"
					fullWidth
					disabled={ disabled || ! catalog }
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
							return 'Typed key';
						}
						const f = offered.find( ( x ) => x.key === v );
						return f ? f.label : <em>Pick a field</em>;
					} }
				>
					<MenuItem value="">
						<em>Pick a field</em>
					</MenuItem>
					{ groups.map( ( [ group, fields ] ) => [
						<ListSubheader key={ 'h-' + group } sx={ { lineHeight: '28px', fontSize: 11 } }>
							{ group }
						</ListSubheader>,
						...fields.map( ( f ) => (
							<MenuItem key={ f.key } value={ f.key } sx={ { display: 'block' } }>
								<Typography variant="body2" component="span">{ f.label }</Typography>
								<Typography variant="caption" component="span" sx={ { color: 'text.secondary', ml: 0.75 } }>
									{ f.name } · { f.type }
								</Typography>
							</MenuItem>
						) ),
					] ) }
					<MenuItem value={ CUSTOM }>
						<em>Type a key…</em>
					</MenuItem>
				</Select>
			) : null }

			{ showBox ? (
				<TextField
					size="tiny"
					fullWidth
					disabled={ disabled }
					value={ current }
					placeholder={ placeholder || 'field_64a1b2c3d4e5f' }
					onChange={ ( e ) => setValue( e.target.value ) }
				/>
			) : null }

			<Typography variant="caption" sx={ { color: 'text.secondary', lineHeight: 1.4 } }>
				{ caption }
			</Typography>
		</Stack>
	);
}
