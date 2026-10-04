/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * StackCardsControl — the "Cards" element-control for the AAE Stack Cards deck.
 *
 * Registered under the type id 'aae-stack-cards' (see ./index.js) and rendered
 * where the PHP side places an AAE_A_Stack_Items_Control. Same shape as
 * TimelineItemsControl: a custom list (never Elementor's <Repeater>, whose row
 * popover needs the row to be the SELECTED element — selecting it swaps the
 * panel away, not selecting it crashes with React #130) whose rows are a LIVE
 * PROJECTION of the deck's real <e-aae-a-stack-card> children. There is no
 * separate repeater data to keep in sync.
 *
 * Interactions:
 *   - Click a row  → expand it (rename field).
 *   - "+" (Add)    → append a new card.
 *   - Duplicate    → clone that card, content and styles included.
 *   - Remove (×)   → delete that card (hidden when only one remains).
 *   - Drag a row   → reorder cards, which is also their stacking order.
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

const ITEM_TYPE = 'e-aae-a-stack-card';

/**
 * Model for a fresh card. `elements: []` must be present and EMPTY, not
 * undefined — Elementor's delete command runs deselectRecursive(), which
 * iterates the collection and throws on an undefined one.
 */
function buildCardModel( position ) {
	return {
		elType: ITEM_TYPE,
		editor_settings: { title: `Card ${ position }` },
		elements: [],
	};
}

/** Live projection of the deck's direct card children. */
function useStackCards( deckId ) {
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
			const children = getContainer( deckId )?.model?.get?.( 'elements' );

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

			// Return the PREVIOUS array object when nothing changed, or every
			// command re-renders the whole panel.
			const signature = signatureParts.join( '|' );
			if ( cacheRef.current.signature !== signature ) {
				cacheRef.current = { signature, value: next };
			}
			return cacheRef.current.value;
		},
		[ deckId ]
	);
}

export function StackCardsControl( { label } ) {
	const { element } = useElement();
	const deckId = element.id;

	const items = useStackCards( deckId );

	const rows = ( items || [] ).map( ( item, index ) => ( {
		id: item.id,
		title: item.editorSettings?.title || `Card ${ index + 1 }`,
		index,
	} ) );

	const [ expandedId, setExpandedId ] = React.useState( null );
	const [ dragFrom, setDragFrom ] = React.useState( null );
	const [ dragOver, setDragOver ] = React.useState( null );

	const handleRowClick = ( row ) => {
		setExpandedId( ( cur ) => ( cur === row.id ? null : row.id ) );
	};

	const handleAdd = () => {
		const deck = getContainer( deckId );
		if ( ! deck ) {
			return;
		}
		createElements( {
			title: 'Stack Card',
			subtitle: 'Card added',
			elements: [
				{
					container: deck,
					model: buildCardModel( rows.length + 1 ),
					options: { at: rows.length },
				},
			],
		} );
	};

	const handleDuplicate = ( row ) => {
		duplicateElements( {
			elementIds: [ row.id ],
			title: 'Stack Card',
			subtitle: 'Card duplicated',
		} );
	};

	const handleRemove = ( row ) => {
		removeElements( {
			elementIds: [ row.id ],
			title: 'Stack Card',
			subtitle: 'Card removed',
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
		// Re-fetch: containers detach after any mutation.
		const deck = getContainer( deckId );
		const movedId = rows[ from ]?.id;
		const movedElement = movedId ? getContainer( movedId ) : null;
		// Guard against a stale index (concurrent create/delete between render
		// and drop): only move if the card is still a child of this deck.
		if ( deck && movedElement && movedElement.parent?.id === deck.id ) {
			moveElements( {
				title: 'Stack Card',
				subtitle: 'Card reordered',
				moves: [
					{
						element: movedElement,
						targetContainer: deck,
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
				<Tooltip title="Add Card">
					<IconButton size="tiny" onClick={ handleAdd } aria-label="Add Card">
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
									<RowTitle elementId={ row.id } fallback={ row.title } />
								</Typography>
								<Tooltip title="Duplicate">
									<IconButton
										size="tiny"
										aria-label="Duplicate card"
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
											aria-label="Remove card"
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
									<CardNameField elementId={ row.id } />
								</Box>
							</Collapse>
						</Box>
					);
				} ) }
			</Stack>

			{ rows.length < 2 && (
				<Typography variant="caption" sx={ { color: 'text.tertiary' } }>
					{ 'Add at least 2 cards — the deck needs two to stack.' }
				</Typography>
			) }
		</Stack>
	);
}

/**
 * Row label, read live off the element's own editor_settings rather than off
 * the cached projection above. That projection only recomputes on a fixed set
 * of document/elements/* commands, and updateElementEditorSettings() (the
 * rename field below) fires none of them — it is a bare Backbone
 * model.set('editor_settings', …) — so typing in the rename field updated the
 * field itself (which already reads this same live hook) but left the row
 * title stuck. Subscribing here directly sidesteps that dependency entirely.
 */
function RowTitle( { elementId, fallback } ) {
	const editorSettings = useElementEditorSettings( elementId );
	return editorSettings?.title || fallback;
}

function CardNameField( { elementId } ) {
	const editorSettings = useElementEditorSettings( elementId );
	const label = editorSettings?.title ?? '';

	return (
		<Stack gap={ 1 }>
			<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
				{ 'Card name' }
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
