/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * GalleryItemsControl — the "Images" element-control for the AAE Image Gallery.
 *
 * Registered under the type id 'aae-gallery-items' (see ./index.js) and
 * rendered by the editing panel where the PHP side places an
 * AAE_A_Gallery_Items_Control. Mirrors the Accordion's AccordionItemsControl:
 * a custom list whose rows are a LIVE PROJECTION of the gallery's real
 * <e-aae-a-image-gallery-item> children (direct children of the gallery, read
 * straight off the V1 container model via useListenTo).
 *
 * Interactions:
 *   - Click a row  → select that item in the editor (so its Image control opens).
 *   - "+" (Add)    → append a new gallery item (its default Atomic_Image child
 *                    is created by Elementor's default_children pipeline).
 *   - Duplicate    → clone that item.
 *   - Remove (×)   → delete that item (hidden when only one remains).
 *   - Drag a row   → reorder items (HTML5 native drag).
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

const ITEM_TYPE = 'e-aae-a-image-gallery-item';

/**
 * Model for a fresh gallery item. `elements: []` (empty, not undefined) lets
 * Elementor's onElementCreate() populate the default Atomic_Image child, and
 * keeps the delete command's deselectRecursive() from throwing.
 */
function buildItemModel( position ) {
	return {
		elType: ITEM_TYPE,
		editor_settings: { title: `Gallery Item ${ position }` },
		elements: [],
	};
}

/** Live projection of the gallery's direct item children. */
function useGalleryItems( galleryId ) {
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
			const children = getContainer( galleryId )?.model?.get?.( 'elements' );

			if ( ! children ) {
				if ( cacheRef.current.signature !== '' ) {
					cacheRef.current = { signature: '', value: [] };
				}
				return cacheRef.current.value;
			}

			const next = [];
			const signatureParts = [];
			children.each( ( model ) => {
				if ( ( model.get( 'widgetType' ) || model.get( 'elType' ) ) !== ITEM_TYPE ) {
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
		[ galleryId ]
	);
}

/** Select an item in the editor so its (Image child's) settings panel opens. */
function selectItem( itemId ) {
	try {
		const container = getContainer( itemId );
		if ( container && window.$e?.run ) {
			window.$e.run( 'document/elements/select', { container } );
		}
	} catch ( _e ) {
		/* selection is best-effort */
	}
}

export function GalleryItemsControl( { label } ) {
	const { element } = useElement();
	const galleryId = element.id;

	const items = useGalleryItems( galleryId );

	const rows = ( items || [] ).map( ( item, index ) => ( {
		id: item.id,
		title: item.editorSettings?.title || `Gallery Item ${ index + 1 }`,
		index,
	} ) );

	const [ expandedId, setExpandedId ] = React.useState( null );
	const [ dragFrom, setDragFrom ] = React.useState( null );
	const [ dragOver, setDragOver ] = React.useState( null );

	const handleRowClick = ( row ) => {
		setExpandedId( ( cur ) => ( cur === row.id ? null : row.id ) );
		selectItem( row.id );
	};

	const handleAdd = () => {
		const gallery = getContainer( galleryId );
		if ( ! gallery ) {
			return;
		}
		createElements( {
			title: 'Gallery Item',
			subtitle: 'Image added',
			elements: [
				{
					container: gallery,
					model: buildItemModel( rows.length + 1 ),
					options: { at: rows.length },
				},
			],
		} );
	};

	const handleDuplicate = ( row ) => {
		duplicateElements( {
			elementIds: [ row.id ],
			title: 'Gallery Item',
			subtitle: 'Image duplicated',
		} );
	};

	const handleRemove = ( row ) => {
		removeElements( {
			elementIds: [ row.id ],
			title: 'Gallery Item',
			subtitle: 'Image removed',
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
		const gallery = getContainer( galleryId );
		const movedId = rows[ from ]?.id;
		const movedElement = movedId ? getContainer( movedId ) : null;
		// Guard against a stale index (concurrent create/delete between render
		// and drop): only move if the item is still a child of this gallery.
		if ( gallery && movedElement && movedElement.parent?.id === gallery.id ) {
			moveElements( {
				title: 'Gallery Item',
				subtitle: 'Image reordered',
				moves: [
					{
						element: movedElement,
						targetContainer: gallery,
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
				<Tooltip title="Add Image">
					<IconButton size="tiny" onClick={ handleAdd } aria-label="Add Image">
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
										aria-label="Duplicate image"
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
											aria-label="Remove image"
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
									<ItemNameField elementId={ row.id } />
								</Box>
							</Collapse>
						</Box>
					);
				} ) }
			</Stack>
		</Stack>
	);
}

function ItemNameField( { elementId } ) {
	const editorSettings = useElementEditorSettings( elementId );
	const label = editorSettings?.title ?? '';

	return (
		<Stack gap={ 1 }>
			<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
				{ 'Item name' }
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
