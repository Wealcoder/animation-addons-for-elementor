/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * HotspotsControl — the "Hotspots" element-control for the AAE Image Hotspot.
 *
 * Registered under the type id 'aae-hotspots' (see ./index.js) and rendered by
 * the editing panel where the PHP side places an AAE_A_Hotspots_Control.
 * Mirrors AccordionItemsControl: hotspot points are DIRECT children of the
 * image-hotspot container (no intermediate "track", unlike NestedSlider's
 * slides), so the child list is read straight off the V1 container model via
 * the useListenTo pattern rather than useElementChildren, whose schema only
 * matches descendants nested under a named child type.
 *
 * Interactions:
 *   - Click a row  → expand it (rename field) AND flash-highlight that point
 *                    in the preview.
 *   - "+" (Add)    → append a new point at the canvas center (50/50).
 *   - Duplicate    → clone that point.
 *   - Remove (×)   → delete that point (hidden when only one remains).
 *   - Drag a row   → reorder points (HTML5 native drag).
 */

import * as React from 'react';
import {
	createElements,
	duplicateElements,
	getContainer,
	moveElements,
	removeElements,
	updateElementEditorSettings,
	useElementEditorSettings,
} from '@elementor/editor-elements';
import {
	__privateUseListenTo as useListenTo,
	commandEndEvent,
	v1ReadyEvent,
} from '@elementor/editor-v1-adapters';
import { useElement } from '@elementor/editor-editing-panel';
import {
	Box,
	Collapse,
	IconButton,
	Stack,
	TextField,
	Tooltip,
	Typography,
} from '@elementor/ui';

const POINT_TYPE = 'e-aae-a-hotspot-point';

/**
 * Model for a fresh hotspot point. `elements: []` (empty, not undefined) lets
 * the delete command's deselectRecursive() iterate safely, and lets
 * Elementor's onElementCreate() populate the default tooltip content child.
 */
function buildPointModel( position ) {
	return {
		elType: POINT_TYPE,
		editor_settings: { title: `Hotspot ${ position }` },
		settings: {
			pos_left: { $$type: 'number', value: 50 },
			pos_top: { $$type: 'number', value: 50 },
		},
		elements: [],
	};
}

/** Live projection of the image-hotspot's direct point children. */
function useHotspotPoints( hotspotId ) {
	const cacheRef = React.useRef( { signature: null, value: [] } );

	return useListenTo(
		[
			v1ReadyEvent(),
			commandEndEvent( 'document/elements/create' ),
			commandEndEvent( 'document/elements/delete' ),
			commandEndEvent( 'document/elements/update' ),
			commandEndEvent( 'document/elements/settings' ),
			commandEndEvent( 'document/elements/set-settings' ),
			commandEndEvent( 'document/elements/duplicate' ),
			commandEndEvent( 'document/elements/move' ),
		],
		() => {
			const children = getContainer( hotspotId )?.model?.get?.( 'elements' );

			if ( ! children ) {
				if ( cacheRef.current.signature !== '' ) {
					cacheRef.current = { signature: '', value: [] };
				}
				return cacheRef.current.value;
			}

			const next = [];
			const signatureParts = [];
			children.each( ( model ) => {
				if ( ( model.get( 'widgetType' ) || model.get( 'elType' ) ) !== POINT_TYPE ) {
					return;
				}
				const id = model.get( 'id' );
				const editorSettings = model.get( 'editor_settings' ) || {};
				next.push( { id, editorSettings } );
				signatureParts.push( `${ id }:${ editorSettings.title || '' }` );
			} );

			const signature = signatureParts.join( '|' );
			if ( cacheRef.current.signature !== signature ) {
				cacheRef.current = { signature, value: next };
			}
			return cacheRef.current.value;
		},
		[ hotspotId ]
	);
}

/**
 * Flash-highlight a point marker in the preview so the builder can see which
 * row they clicked, without touching Elementor's selection (selecting the
 * point would flip the editing panel away from the image-hotspot container).
 */
function highlightPointInPreview( pointId ) {
	try {
		const previewWin = window.elementor?.$preview?.[ 0 ]?.contentWindow || null;
		if ( ! previewWin ) {
			return;
		}
		const pointNode = previewWin.document.querySelector( `[data-id="${ pointId }"]` );
		if ( pointNode ) {
			pointNode.classList.add( 'aae-hotspot-editor-focus' );
			window.setTimeout( () => pointNode.classList.remove( 'aae-hotspot-editor-focus' ), 1200 );
		}
	} catch ( _e ) {
		/* preview not ready — ignore */
	}
}

export function HotspotsControl( { label } ) {
	const { element } = useElement();
	const hotspotId = element.id;

	const points = useHotspotPoints( hotspotId );

	const rows = ( points || [] ).map( ( point, index ) => ( {
		id: point.id,
		title: point.editorSettings?.title || `Hotspot ${ index + 1 }`,
		index,
	} ) );

	const [ expandedId, setExpandedId ] = React.useState( null );
	const [ dragFrom, setDragFrom ] = React.useState( null );
	const [ dragOver, setDragOver ] = React.useState( null );

	const handleRowClick = ( row ) => {
		setExpandedId( ( cur ) => ( cur === row.id ? null : row.id ) );
		highlightPointInPreview( row.id );
	};

	const handleAdd = () => {
		const hotspot = getContainer( hotspotId );
		if ( ! hotspot ) {
			return;
		}
		createElements( {
			title: 'Hotspot',
			subtitle: 'Hotspot added',
			elements: [
				{
					container: hotspot,
					model: buildPointModel( rows.length + 1 ),
					options: { at: rows.length },
				},
			],
		} );
	};

	const handleDuplicate = ( row ) => {
		duplicateElements( {
			elementIds: [ row.id ],
			title: 'Hotspot',
			subtitle: 'Hotspot duplicated',
		} );
	};

	const handleRemove = ( row ) => {
		removeElements( {
			elementIds: [ row.id ],
			title: 'Hotspot',
			subtitle: 'Hotspot removed',
		} );
		if ( expandedId === row.id ) {
			setExpandedId( null );
		}
	};

	const handleDrop = ( toIndex ) => {
		const from = dragFrom;
		setDragFrom( null );
		setDragOver( null );
		if ( from == null || from === toIndex ) {
			return;
		}
		const hotspot = getContainer( hotspotId );
		const movedId = rows[ from ]?.id;
		const movedElement = movedId ? getContainer( movedId ) : null;
		// Guard against a stale index (concurrent create/delete between render
		// and drop): only move if the point is still a child of this container.
		if ( hotspot && movedElement && movedElement.parent?.id === hotspot.id ) {
			moveElements( {
				title: 'Hotspot',
				subtitle: 'Hotspot reordered',
				moves: [
					{
						element: movedElement,
						targetContainer: hotspot,
						options: { at: toIndex },
					},
				],
			} );
		}
	};

	return (
		<Stack gap={ 1 }>
			<Stack direction="row" alignItems="center" justifyContent="space-between">
				<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
					{ label }
				</Typography>
				<Tooltip title="Add Hotspot">
					<IconButton size="tiny" onClick={ handleAdd } aria-label="Add Hotspot">
						<span style={ { fontSize: 16, lineHeight: 1 } }>+</span>
					</IconButton>
				</Tooltip>
			</Stack>

			<Stack gap={ 0.5 }>
				{ rows.map( ( row ) => {
					const isExpanded = expandedId === row.id;
					const isDragOver = dragOver === row.index && dragFrom !== row.index;
					return (
						<Box
							key={ row.id }
							draggable
							onDragStart={ () => setDragFrom( row.index ) }
							onDragOver={ ( e ) => {
								e.preventDefault();
								setDragOver( row.index );
							} }
							onDrop={ () => handleDrop( row.index ) }
							onDragEnd={ () => {
								setDragFrom( null );
								setDragOver( null );
							} }
							sx={ {
								border: '1px solid',
								borderColor: isDragOver ? 'primary.main' : 'divider',
								borderRadius: 1,
								overflow: 'hidden',
								bgcolor: 'background.default',
							} }
						>
							<Stack
								direction="row"
								alignItems="center"
								gap={ 0.5 }
								onClick={ () => handleRowClick( row ) }
								sx={ {
									px: 1,
									py: 0.75,
									cursor: 'pointer',
									userSelect: 'none',
									'&:hover': { bgcolor: 'action.hover' },
								} }
							>
								<Box
									component="span"
									sx={ { color: 'text.tertiary', cursor: 'grab', fontSize: 14, lineHeight: 1 } }
									aria-hidden
								>
									⠿
								</Box>
								<Typography variant="body2" sx={ { flex: 1, fontWeight: isExpanded ? 600 : 400 } }>
									{ row.title }
								</Typography>
								<Tooltip title="Duplicate">
									<IconButton
										size="tiny"
										aria-label="Duplicate hotspot"
										onClick={ ( e ) => {
											e.stopPropagation();
											handleDuplicate( row );
										} }
									>
										<span style={ { fontSize: 13, lineHeight: 1 } }>⧉</span>
									</IconButton>
								</Tooltip>
								{ rows.length > 1 && (
									<Tooltip title="Remove">
										<IconButton
											size="tiny"
											aria-label="Remove hotspot"
											onClick={ ( e ) => {
												e.stopPropagation();
												handleRemove( row );
											} }
										>
											<span style={ { fontSize: 14, lineHeight: 1 } }>×</span>
										</IconButton>
									</Tooltip>
								) }
							</Stack>

							<Collapse in={ isExpanded } unmountOnExit>
								<Box sx={ { px: 1.5, py: 1.5, borderTop: '1px solid', borderColor: 'divider' } }>
									<PointNameField elementId={ row.id } />
								</Box>
							</Collapse>
						</Box>
					);
				} ) }
			</Stack>
		</Stack>
	);
}

function PointNameField( { elementId } ) {
	const editorSettings = useElementEditorSettings( elementId );
	const label = editorSettings?.title ?? '';

	return (
		<Stack gap={ 1 }>
			<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
				{ 'Hotspot name' }
			</Typography>
			<TextField
				size="tiny"
				value={ label }
				onChange={ ( { target } ) =>
					updateElementEditorSettings( {
						elementId,
						settings: { title: target.value },
					} )
				}
			/>
		</Stack>
	);
}
