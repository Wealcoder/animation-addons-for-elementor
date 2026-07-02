/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * NavItemsControl — "Menu Items" element-control for the AAE Nav widget.
 *
 * Registered under the type id 'aae-nav-items' (see ./index.js). The PHP side
 * (AAE_A_Nav_Items_Control) places it inside the nav widget's panel.
 *
 * This control manages the LIST of nav-items (add / duplicate / remove /
 * reorder) and each item's own settings (title text prop, link, has_dropdown
 * toggle, trigger, dropdown animation). When the user enables Dropdown
 * Content, an empty core Elementor Flexbox is inserted as the nav-item's
 * child — the actual dropdown content is edited by selecting that Flexbox
 * (or its descendants) directly in the preview / Structure tree using
 * Elementor's own controls, NOT from this panel.
 */

import * as React from 'react';
import {
	createElements,
	duplicateElements,
	getContainer,
	moveElements,
	removeElements,
	updateElementEditorSettings,
	updateElementSettings,
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
	MenuItem,
	Select,
	Stack,
	Switch,
	TextField,
	Tooltip,
	Typography,
} from '@elementor/ui';

const NAV_ITEM_TYPE = 'e-aae-a-nav-item';

const prop = ( type, value ) => ( { $$type: type, value } );

function readProp( value, fallback = '' ) {
	if ( value === undefined || value === null ) {
		return fallback;
	}

	if ( typeof value === 'object' && Object.prototype.hasOwnProperty.call( value, 'value' ) ) {
		return value.value ?? fallback;
	}

	return value;
}

function syncEditorDropdownPreview( navId, itemId ) {
	const previewDocument = window.elementor?.$preview?.[ 0 ]?.contentDocument;
	const nav = previewDocument?.querySelector( `.aae-a-nav[data-id="${ navId }"]` );
	if ( ! nav ) {
		return;
	}

	const currentlyOpen = nav.querySelector(
		':scope > .aae-a-nav-item.aae-editor-dropdown-open'
	);
	const currentOpenId = currentlyOpen?.getAttribute( 'data-id' ) || null;
	if ( currentOpenId === ( itemId || null ) ) {
		return;
	}

	if ( currentlyOpen ) {
		currentlyOpen.classList.remove( 'aae-editor-dropdown-open' );
	}
	if ( itemId ) {
		nav.querySelector( `:scope > .aae-a-nav-item[data-id="${ itemId }"]` )
			?.classList.add( 'aae-editor-dropdown-open' );
	}
}

function syncEditorDropdownPreviewAfterRender( navId, itemId ) {
	const delays = [ 0, 80, 200, 400 ];
	const timers = delays.map( ( delay ) => window.setTimeout(
		() => syncEditorDropdownPreview( navId, itemId ),
		delay
	) );

	return () => timers.forEach( ( timer ) => window.clearTimeout( timer ) );
}

function buildNavItemModel( position ) {
	return {
		elType: NAV_ITEM_TYPE,
		editor_settings: { title: `Menu Item ${ position }` },
		settings: {
			text: prop( 'html-v3', {
				content: prop( 'string', `Menu Item ${ position }` ),
				children: [],
			} ),
			has_dropdown: prop( 'boolean', false ),
			trigger: prop( 'string', 'click' ),
			dropdown_animation: prop( 'string', 'gsap' ),
		},
		elements: [],
	};
}

function useNavItems( navId ) {
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
		],
		() => {
			const children = getContainer( navId )?.model?.get?.( 'elements' );

			if ( ! children ) {
				if ( cacheRef.current.signature !== '' ) {
					cacheRef.current = { signature: '', value: [] };
				}
				return cacheRef.current.value;
			}

			const next = [];
			const signatureParts = [];
			children.each( ( model ) => {
				if ( ( model.get( 'widgetType' ) || model.get( 'elType' ) ) !== NAV_ITEM_TYPE ) {
					return;
				}
				const id = model.get( 'id' );
				const editorSettings = model.get( 'editor_settings' ) || {};
				next.push( { id, editorSettings } );
				/* Signature includes has_dropdown state and child count so
				 * toggling dropdown or add/remove of dropdown content
				 * invalidates the cache — downstream effects (in particular
				 * syncEditorDropdownPreview which re-applies the visibility
				 * class after Elementor re-renders) depend on this. */
				const container = getContainer( id );
				const hasDropdown = readProp(
					container?.settings?.get?.( 'has_dropdown' ),
					false
				);
				const childCount = container?.model?.get?.( 'elements' )?.length ?? 0;
				signatureParts.push(
					`${ id }:${ editorSettings.title || '' }:${ hasDropdown ? 1 : 0 }:${ childCount }`
				);
			} );

			const signature = signatureParts.join( '|' );
			if ( cacheRef.current.signature === signature ) {
				return cacheRef.current.value;
			}
			cacheRef.current = { signature, value: next };
			return next;
		},
		[ navId ]
	);
}

export function NavItemsControl( { label } ) {
	const { element } = useElement();
	const navId = element.id;

	const sourceItems = useNavItems( navId );
	const [ items, setItems ] = React.useState( sourceItems );

	React.useEffect( () => {
		setItems( sourceItems );
	}, [ sourceItems ] );

	const rows = ( items || [] ).map( ( item, index ) => ( {
		id:    item.id,
		title: item.editorSettings?.title || `Menu Item ${ index + 1 }`,
		index,
	} ) );

	const [ expandedId, setExpandedId ] = React.useState( null );
	const [ draggedId,  setDraggedId  ] = React.useState( null );
	const [ dragOver,   setDragOver   ] = React.useState( null );

	React.useEffect( () => {
		/* Creating/removing the dropdown Flexbox can replace the preview DOM more
		 * than once. Re-apply the marker across that short render window so the
		 * expanded panel row remains the only visible dropdown placeholder. */
		return syncEditorDropdownPreviewAfterRender( navId, expandedId );
	}, [ navId, expandedId, sourceItems ] );

	const getNavContainer = () => getContainer( navId );

	const handleRowClick = ( row ) => {
		const nextId = expandedId === row.id ? null : row.id;
		setExpandedId( nextId );
		syncEditorDropdownPreview( navId, nextId );
	};

	const handleAdd = () => {
		const nav = getNavContainer();
		if ( ! nav ) return;
		const position = rows.length + 1;
		const result = createElements( {
			title:    'Menu Item',
			subtitle: 'Menu item added',
			elements: [
				{
					container: nav,
					model:     buildNavItemModel( position ),
					options:   { at: rows.length },
				},
			],
		} );
		const id = result?.createdElements?.[ 0 ]?.containerId;
		if ( id ) {
			setItems( ( current ) => [
				...current,
				{ id, editorSettings: { title: `Menu Item ${ position }` } },
			] );
		}
	};

	const handleDuplicate = ( row ) => {
		const result = duplicateElements( {
			elementIds: [ row.id ],
			title:      'Menu Item',
			subtitle:   'Menu item duplicated',
		} );
		const id = result?.duplicatedElements?.[ 0 ]?.containerId;
		if ( id ) {
			setItems( ( current ) => {
				const index = current.findIndex( ( item ) => item.id === row.id );
				const next = [ ...current ];
				next.splice( index + 1, 0, {
					id,
					editorSettings: { title: `${ row.title } Copy` },
				} );
				return next;
			} );
		}
	};

	const handleRemove = ( row ) => {
		removeElements( {
			elementIds: [ row.id ],
			title:      'Menu Item',
			subtitle:   'Menu item removed',
		} );
		setItems( ( current ) => current.filter( ( item ) => item.id !== row.id ) );
		if ( expandedId === row.id ) {
			setExpandedId( null );
		}
	};

	const handleDrop = ( toIndex ) => {
		const movedId = draggedId;
		setDraggedId( null );
		setDragOver( null );
		if ( ! movedId || rows[ toIndex ]?.id === movedId ) return;

		const nav = getNavContainer();
		const movedElement = getContainer( movedId );
		if ( nav && movedElement && movedElement.parent?.id === nav.id ) {
			moveElements( {
				title:    'Menu Item',
				subtitle: 'Menu item reordered',
				moves: [
					{
						element:         movedElement,
						targetContainer: nav,
						options:         { at: toIndex },
					},
				],
			} );
			setItems( ( current ) => {
				const fromIndex = current.findIndex( ( item ) => item.id === movedId );
				if ( fromIndex < 0 ) {
					return current;
				}

				const next = [ ...current ];
				const [ moved ] = next.splice( fromIndex, 1 );
				next.splice( toIndex, 0, moved );
				return next;
			} );
		}
	};

	return (
		<Stack gap={ 1 }>
			<Stack
				direction="row"
				alignItems="center"
				justifyContent="space-between"
			>
				<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
					{ label }
				</Typography>
				<Tooltip title="Add Menu Item">
					<IconButton size="tiny" onClick={ handleAdd } aria-label="Add Menu Item">
						<span style={ { fontSize: 16, lineHeight: 1 } }>+</span>
					</IconButton>
				</Tooltip>
			</Stack>

			<Stack gap={ 0.5 }>
				{ rows.map( ( row ) => {
					const isExpanded = expandedId === row.id;
					const isDragOver = dragOver === row.index && draggedId !== row.id;
					return (
						<Box
							key={ row.id }
							draggable
							onDragStart={ () => setDraggedId( row.id ) }
							onDragOver={ ( e ) => {
								e.preventDefault();
								setDragOver( row.index );
							} }
							onDrop={ () => handleDrop( row.index ) }
							onDragEnd={ () => {
								setDraggedId( null );
								setDragOver( null );
							} }
							sx={ {
								border:       '1px solid',
								borderColor:  isDragOver ? 'primary.main' : 'divider',
								borderRadius: 1,
								overflow:     'hidden',
								bgcolor:      'background.default',
							} }
						>
							<Stack
								direction="row"
								alignItems="center"
								gap={ 0.5 }
								onClick={ () => handleRowClick( row ) }
								sx={ {
									px:         1,
									py:         0.75,
									cursor:     'pointer',
									userSelect: 'none',
									'&:hover':  { bgcolor: 'action.hover' },
								} }
							>
								<Box
									component="span"
									sx={ { color: 'text.tertiary', cursor: 'grab', fontSize: 14, lineHeight: 1 } }
									aria-hidden
								>
									⠿
								</Box>
								<Typography
									variant="body2"
									sx={ { flex: 1, fontWeight: isExpanded ? 600 : 400 } }
								>
									{ row.title }
								</Typography>
								<Tooltip title="Duplicate">
									<IconButton
										size="tiny"
										aria-label="Duplicate menu item"
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
											aria-label="Remove menu item"
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
					<NavItemFields
						elementId={ row.id }
						fallbackTitle={ row.title }
						onDropdownToggle={ ( enabled ) => {
							const activeId = enabled ? row.id : null;
							setExpandedId( activeId );
							syncEditorDropdownPreviewAfterRender( navId, activeId );
						} }
						onTitleChange={ ( title ) => setItems( ( current ) => current.map( ( item ) =>
											item.id === row.id
												? { ...item, editorSettings: { ...item.editorSettings, title } }
												: item
										) ) }
									/>
								</Box>
							</Collapse>
						</Box>
					);
				} ) }
			</Stack>
		</Stack>
	);
}

function NavItemFields( { elementId, fallbackTitle, onDropdownToggle, onTitleChange } ) {
	const data = useListenTo(
		[
			v1ReadyEvent(),
			commandEndEvent( 'document/elements/create' ),
			commandEndEvent( 'document/elements/delete' ),
			commandEndEvent( 'document/elements/duplicate' ),
			commandEndEvent( 'document/elements/settings' ),
			commandEndEvent( 'document/elements/set-settings' ),
		],
		() => {
			const container = getContainer( elementId );
			const text = readProp( container?.settings?.get?.( 'text' ), {} );
			const link = readProp( container?.settings?.get?.( 'link' ), {} );

			return {
				title:             readProp( text?.content, '' ) || fallbackTitle,
				url:               readProp( link?.destination, '' ),
				hasDropdown:       readProp( container?.settings?.get?.( 'has_dropdown' ), false ),
				trigger:           readProp( container?.settings?.get?.( 'trigger' ), 'click' ),
				dropdownAnimation: readProp( container?.settings?.get?.( 'dropdown_animation' ), 'gsap' ),
			};
		},
		[ elementId, fallbackTitle ]
	);
	const [ titleValue, setTitleValue ] = React.useState( data.title );
	const [ dropdownEnabled, setDropdownEnabled ] = React.useState( data.hasDropdown );
	const [ trigger, setTrigger ] = React.useState( data.trigger );
	const [ dropdownAnimation, setDropdownAnimation ] = React.useState( data.dropdownAnimation );

	React.useEffect( () => {
		setTitleValue( data.title );
	}, [ data.title ] );

	React.useEffect( () => {
		setDropdownEnabled( data.hasDropdown );
	}, [ data.hasDropdown ] );

	React.useEffect( () => {
		setTrigger( data.trigger );
	}, [ data.trigger ] );

	React.useEffect( () => {
		setDropdownAnimation( data.dropdownAnimation );
	}, [ data.dropdownAnimation ] );

	const updateTitle = ( title ) => {
		setTitleValue( title );
		onTitleChange( title || 'Menu Item' );
		updateElementSettings( {
			id: elementId,
			props: {
				text: prop( 'html-v3', {
					content: prop( 'string', title ),
					children: [],
				} ),
			},
		} );
		updateElementEditorSettings( {
			elementId,
			settings: { title: title || 'Menu Item' },
		} );
	};

	const updateLink = ( url ) => {
		updateElementSettings( {
			id: elementId,
			props: {
				link: url ? prop( 'link', {
					destination: prop( 'url', url ),
					isTargetBlank: prop( 'boolean', false ),
					tag: prop( 'string', 'a' ),
				} ) : null,
			},
		} );
	};

	const toggleDropdown = ( enabled ) => {
		setDropdownEnabled( enabled );
		onDropdownToggle( enabled );

		const container = getContainer( elementId );
		const existingChildren = container?.model?.get?.( 'elements' );
		const childIds = [];
		existingChildren?.each?.( ( child ) => {
			const id = child.get?.( 'id' );
			if ( id ) {
				childIds.push( id );
			}
		} );

		if ( enabled ) {
			if ( container && childIds.length === 0 ) {
				/* Create an EMPTY core Flexbox as the dropdown wrapper. User
				 * fills it with widgets or clicks "Add dropdown item" in this
				 * panel. Nested `elements` in createElements silently fails
				 * for atomic containers, so we ship empty and let the user
				 * populate it themselves. */
				createElements( {
					title: 'Dropdown',
					subtitle: 'Dropdown added',
					elements: [ {
						container,
						model: {
							elType: 'e-flexbox',
							editor_settings: { title: 'Dropdown' },
						},
						options: { at: 0 },
					} ],
				} );
			}
		} else if ( childIds.length > 0 ) {
			/* Toggle disabled — clean up the dropdown container children so the
			 * empty-view placeholder inside the Flexbox doesn't linger below
			 * the label. Re-enabling creates a fresh Flexbox. */
			removeElements( {
				elementIds: childIds,
				title: 'Dropdown',
				subtitle: 'Dropdown removed',
			} );
		}

		updateElementSettings( {
			id: elementId,
			props: { has_dropdown: prop( 'boolean', enabled ) },
		} );
	};

	const updateDropdownSetting = ( key, value, setter ) => {
		setter( value );
		updateElementSettings( {
			id: elementId,
			props: { [ key ]: prop( 'string', value ) },
		} );
	};

	return (
		<Stack gap={ 1.5 }>
			<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
				{ 'Title' }
			</Typography>
			<TextField
				size="tiny"
				value={ titleValue }
				onChange={ ( { target } ) => updateTitle( target.value ) }
			/>
			<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
				{ 'Link' }
			</Typography>
			<TextField
				size="tiny"
				placeholder="Paste URL"
				value={ data.url }
				onChange={ ( { target } ) => updateLink( target.value ) }
			/>
			<Stack direction="row" alignItems="center" justifyContent="space-between">
				<Typography variant="caption">{ 'Dropdown Content' }</Typography>
				<Switch
					size="small"
					checked={ dropdownEnabled }
					onChange={ ( event ) => toggleDropdown( event.target.checked ) }
				/>
			</Stack>
			{ dropdownEnabled && (
				<>
					<Typography variant="caption">{ 'Trigger' }</Typography>
					<Select
						size="tiny"
						value={ trigger }
						onChange={ ( event ) => updateDropdownSetting( 'trigger', event.target.value, setTrigger ) }
					>
						<MenuItem value="click">{ 'Click' }</MenuItem>
						<MenuItem value="hover">{ 'Hover' }</MenuItem>
					</Select>
					<Typography variant="caption">{ 'Dropdown Animation' }</Typography>
					<Select
						size="tiny"
						value={ dropdownAnimation }
						onChange={ ( event ) => updateDropdownSetting(
							'dropdown_animation',
							event.target.value,
							setDropdownAnimation
						) }
					>
						<MenuItem value="gsap">{ 'Default (GSAP)' }</MenuItem>
						<MenuItem value="grow-down">{ 'Grow Down' }</MenuItem>
						<MenuItem value="rotate-3d">{ 'Rotate 3D' }</MenuItem>
						<MenuItem value="grow-out">{ 'Grow Out' }</MenuItem>
						<MenuItem value="slide-items">{ 'Slide Items' }</MenuItem>
						<MenuItem value="rotate-items">{ 'Rotate Items' }</MenuItem>
					</Select>
				</>
			) }
		</Stack>
	);
}
