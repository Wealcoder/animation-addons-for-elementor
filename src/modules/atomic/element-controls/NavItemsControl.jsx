

/* eslint-env browser */
/* eslint-disable react/prop-types */

/**
 * NavItemsControl — "Menu Items" element-control for the AAE Nav widget.
 *
 * Registered under the type id 'aae-nav-items' (see ./index.js). The PHP side
 * (AAE_A_Nav_Items_Control) places it inside the nav widget's panel.
 *
 * Mirrors SlidesControl in spirit but the nav contains nav-items DIRECTLY —
 * no intermediate track container — so this is a flat list against the nav's
 * own elements collection. Same UX:
 *   - Click a row  → expand it (rename field).
 *   - "+" button   → append a new nav-item.
 *   - ⧉ Duplicate  → clone that nav-item (with its children, i.e. its sub-dropdown).
 *   - × Remove     → delete (hidden when only one item remains).
 *   - Drag a row   → reorder.
 */

import * as React from 'react';
import {
	createElements,
	duplicateElements,
	generateElementId,
	getContainer,
	moveElements,
	removeElements,
	replaceElement,
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
	Button,
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
const NAV_SUB_TYPE = 'e-aae-a-nav-sub';
const NAV_SUB_ITEM_TYPE = 'e-aae-a-nav-sub-item';

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

function findChild( parentId, type ) {
	const children = getContainer( parentId )?.model?.get?.( 'elements' );
	let found = null;

	children?.each?.( ( model ) => {
		const modelType = model.get( 'widgetType' ) || model.get( 'elType' );
		if ( ! found && modelType === type ) {
			found = model.get( 'id' );
		}
	} );

	return found;
}

function syncEditorDropdownPreview( navId, itemId ) {
	const apply = () => {
		const previewDocument = window.elementor?.$preview?.[ 0 ]?.contentDocument;
		const nav = previewDocument?.querySelector( `.aae-a-nav[data-id="${ navId }"]` );
		if ( ! nav ) {
			return;
		}

		nav.querySelectorAll( ':scope > .aae-a-nav-item.aae-editor-dropdown-open' )
			.forEach( ( item ) => item.classList.remove( 'aae-editor-dropdown-open' ) );

		if ( itemId ) {
			nav.querySelector( `:scope > .aae-a-nav-item[data-id="${ itemId }"]` )
				?.classList.add( 'aae-editor-dropdown-open' );
		}
	};

	apply();
	window.setTimeout( apply, 80 );
}

function buildDropdownItemModel( text = 'Dropdown Item', url = '' ) {
	return {
		id: generateElementId(),
		elType: 'widget',
		widgetType: NAV_SUB_ITEM_TYPE,
		editor_settings: { title: text },
		settings: {
			paragraph: prop( 'html-v3', {
				content: prop( 'string', text ),
				children: [],
			} ),
			...( url ? {
				link: prop( 'link', {
					destination: prop( 'url', url ),
					isTargetBlank: prop( 'boolean', false ),
					tag: prop( 'string', 'a' ),
				} ),
			} : {} ),
		},
		elements: [],
		isInner: false,
		styles: [],
		interactions: [],
		version: '0.0',
	};
}

function buildMenuLabelModel( text = 'Menu Item' ) {
	return {
		id: generateElementId(),
		elType: 'widget',
		widgetType: 'e-paragraph',
		editor_settings: { title: text },
		settings: {
			paragraph: prop( 'html-v3', {
				content: prop( 'string', text ),
				children: [],
			} ),
			tag: prop( 'string', 'span' ),
		},
		elements: [],
		isInner: false,
		styles: [],
		interactions: [],
		version: '0.0',
	};
}

function buildDropdownModel() {
	return {
		elType: NAV_SUB_TYPE,
		elements: [
			buildDropdownItemModel(),
			buildDropdownItemModel(),
			buildDropdownItemModel(),
		],
	};
}

function useNavItems( navId ) {
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
				return [];
			}

			return children
				.filter( ( model ) =>
					( model.get( 'widgetType' ) || model.get( 'elType' ) ) === NAV_ITEM_TYPE
				)
				.map( ( model ) => ( {
					id: model.get( 'id' ),
					editorSettings: model.get( 'editor_settings' ) || {},
				} ) );
		},
		[ navId ]
	);
}

function buildNavItemModel( position ) {
	return {
		elType: NAV_ITEM_TYPE,
		editor_settings: { title: `Menu Item ${ position }` },
		settings: {
			has_dropdown: { $$type: 'boolean', value: false },
			trigger: { $$type: 'string', value: 'click' },
			dropdown_animation: { $$type: 'string', value: 'gsap' },
		},
		elements: [ buildMenuLabelModel() ],
	};
}

export function NavItemsControl( { label } ) {
	const { element } = useElement();
	const navId = element.id;

	// Direct children of the nav of type nav-item — useElementChildren accepts
	// a parent-type → child-type map; for our flat hierarchy the nav itself
	// is both the navId and the parent type that contains the items.
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
		syncEditorDropdownPreview( navId, expandedId );
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
		/* Move the nav-item container itself. Elementor serializes its complete
		 * model here, so its paragraph, link and dropdown subtree travel with it. */
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

function NavItemFields( { elementId, fallbackTitle, onTitleChange } ) {
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
			const labelId = findChild( elementId, 'e-paragraph' );
			const label = labelId ? getContainer( labelId ) : null;
			const paragraph = readProp( label?.settings?.get?.( 'paragraph' ), {} );
			const link = readProp( label?.settings?.get?.( 'link' ), {} );

			return {
				labelId,
				title: readProp( paragraph?.content, '' ) || fallbackTitle,
				url: readProp( link?.destination, '' ),
				hasDropdown: readProp(
					getContainer( elementId )?.settings?.get?.( 'has_dropdown' ),
					false
				),
				trigger: readProp(
					getContainer( elementId )?.settings?.get?.( 'trigger' ),
					'click'
				),
				dropdownAnimation: readProp(
					getContainer( elementId )?.settings?.get?.( 'dropdown_animation' ),
					'gsap'
				),
				dropdownId: findChild( elementId, NAV_SUB_TYPE ),
			};
		},
		[ elementId, fallbackTitle ]
	);
	const [ titleValue, setTitleValue ] = React.useState( data.title );
	const [ dropdownEnabled, setDropdownEnabled ] = React.useState( data.hasDropdown );
	const [ dropdownId, setDropdownId ] = React.useState( data.dropdownId );
	const [ editingDropdown, setEditingDropdown ] = React.useState( false );
	const [ trigger, setTrigger ] = React.useState( data.trigger );
	const [ dropdownAnimation, setDropdownAnimation ] = React.useState( data.dropdownAnimation );

	React.useEffect( () => {
		setTitleValue( data.title );
	}, [ data.title ] );

	React.useEffect( () => {
		setDropdownEnabled( data.hasDropdown );
	}, [ data.hasDropdown ] );

	React.useEffect( () => {
		if ( data.dropdownId ) {
			setDropdownId( data.dropdownId );
		}
	}, [ data.dropdownId ] );

	React.useEffect( () => {
		setTrigger( data.trigger );
	}, [ data.trigger ] );

	React.useEffect( () => {
		setDropdownAnimation( data.dropdownAnimation );
	}, [ data.dropdownAnimation ] );

	const updateTitle = ( title ) => {
		setTitleValue( title );
		onTitleChange( title || 'Menu Item' );
		if ( ! data.labelId ) {
			updateElementEditorSettings( {
				elementId,
				settings: { title: title || 'Menu Item' },
			} );
			return;
		}

		updateElementSettings( {
			id: data.labelId,
			props: {
				paragraph: prop( 'html-v3', {
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
		if ( ! data.labelId ) {
			return;
		}

		updateElementSettings( {
			id: data.labelId,
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

		if ( enabled && ! dropdownId ) {
			const container = getContainer( elementId );
			if ( container ) {
				const result = createElements( {
					title: 'Dropdown',
					subtitle: 'Dropdown content added',
					elements: [ { container, model: buildDropdownModel() } ],
				} );
				const createdId = result?.createdElements?.[ 0 ]?.containerId;
				if ( createdId ) {
					setDropdownId( createdId );
				}
			}
		}

		updateElementSettings( {
			id: elementId,
			props: { has_dropdown: prop( 'boolean', enabled ) },
		} );

		if ( ! enabled ) {
			setEditingDropdown( false );
		}
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
			{ dropdownEnabled && dropdownId && (
				<Button
					size="tiny"
					variant="outlined"
					onClick={ () => setEditingDropdown( ( current ) => ! current ) }
				>
					{ editingDropdown ? 'Close dropdown content' : 'Edit dropdown content' }
				</Button>
			) }
			{ dropdownEnabled && dropdownId && editingDropdown && (
				<DropdownItemsFields dropdownId={ dropdownId } />
			) }
		</Stack>
	);
}

function DropdownItemsFields( { dropdownId } ) {
	const sourceItems = useListenTo(
		[
			v1ReadyEvent(),
			commandEndEvent( 'document/elements/create' ),
			commandEndEvent( 'document/elements/delete' ),
			commandEndEvent( 'document/elements/duplicate' ),
			commandEndEvent( 'document/elements/settings' ),
			commandEndEvent( 'document/elements/set-settings' ),
		],
		() => {
			const children = getContainer( dropdownId )?.model?.get?.( 'elements' );
			if ( ! children ) {
				return [];
			}

			return children
				.filter( ( model ) => {
					const type = model.get( 'widgetType' ) || model.get( 'elType' );
					return type === NAV_SUB_ITEM_TYPE || type === 'e-paragraph';
				} )
				.map( ( model, index ) => {
				const id = model.get( 'id' );
				const paragraph = readProp(
					getContainer( id )?.settings?.get?.( 'paragraph' ),
					{}
				);
				const link = readProp(
					getContainer( id )?.settings?.get?.( 'link' ),
					{}
				);

				return {
					id,
					label: readProp( paragraph?.content, '' ) || `Dropdown Item ${ index + 1 }`,
					url: readProp( link?.destination, '' ),
					legacy: model.get( 'elType' ) !== 'widget' ||
						model.get( 'widgetType' ) !== NAV_SUB_ITEM_TYPE,
				};
			} );
		},
		[ dropdownId ]
	);
	const [ items, setItems ] = React.useState( sourceItems );
	const repairedIds = React.useRef( new Set() );

	React.useEffect( () => {
		setItems( sourceItems );
	}, [ sourceItems ] );

	React.useEffect( () => {
		items.forEach( ( item ) => {
			if ( ! item.legacy || repairedIds.current.has( item.id ) ) {
				return;
			}

			repairedIds.current.add( item.id );
			replaceElement( {
				currentElementId: item.id,
				newElement: buildDropdownItemModel( item.label, item.url ),
			} );
		} );
	}, [ items ] );
	const addItem = () => {
		const container = getContainer( dropdownId );
		if ( ! container ) {
			return;
		}

		const result = createElements( {
			title: 'Dropdown Item',
			subtitle: 'Dropdown item added',
			elements: [ {
				container,
				model: buildDropdownItemModel(),
				options: { at: items.length },
			} ],
		} );
		const id = result?.createdElements?.[ 0 ]?.containerId;
		if ( id ) {
			setItems( ( current ) => [
				...current,
				{ id, label: 'Dropdown Item', url: '', legacy: false },
			] );
		}
	};

	const duplicateItem = ( item ) => {
		const result = duplicateElements( {
			elementIds: [ item.id ],
			title: 'Dropdown Item',
			subtitle: 'Dropdown item duplicated',
		} );
		const id = result?.duplicatedElements?.[ 0 ]?.containerId;

		if ( id ) {
			setItems( ( current ) => {
				const index = current.findIndex( ( currentItem ) => currentItem.id === item.id );
				const next = [ ...current ];
				next.splice( index + 1, 0, {
					id,
					label: item.label,
					url: item.url,
					legacy: false,
				} );
				return next;
			} );
		}
	};

	const removeItem = ( item ) => {
		removeElements( {
			elementIds: [ item.id ],
			title: 'Dropdown Item',
			subtitle: 'Dropdown item removed',
		} );
		setItems( ( current ) => current.filter( ( currentItem ) => currentItem.id !== item.id ) );
	};

	return (
		<Stack gap={ 1 } sx={ { pt: 0.5 } }>
			<Stack direction="row" alignItems="center" justifyContent="space-between">
				<Typography variant="caption" sx={ { fontWeight: 600 } }>
					{ 'Dropdown items' }
				</Typography>
				<Tooltip title="Add dropdown item">
					<IconButton size="tiny" onClick={ addItem } aria-label="Add dropdown item">
						<span style={ { fontSize: 16, lineHeight: 1 } }>+</span>
					</IconButton>
				</Tooltip>
			</Stack>
			{ items.map( ( item ) => (
				<DropdownItemField
					key={ item.id }
					item={ item }
					canRemove={ items.length > 1 }
					onDuplicate={ duplicateItem }
					onRemove={ removeItem }
				/>
			) ) }
		</Stack>
	);
}

function DropdownItemField( { item, canRemove, onDuplicate, onRemove } ) {
	const [ value, setValue ] = React.useState( item.label );
	const [ url, setUrl ] = React.useState( item.url || '' );

	React.useEffect( () => {
		setValue( item.label );
	}, [ item.label ] );

	React.useEffect( () => {
		setUrl( item.url || '' );
	}, [ item.url ] );

	React.useEffect( () => {
		updateElementEditorSettings( {
			elementId: item.id,
			settings: { title: item.label || 'Dropdown Item' },
		} );
	}, [ item.id, item.label ] );

	const updateLabel = ( label ) => {
		setValue( label );
		updateElementSettings( {
			id: item.id,
			props: {
				paragraph: prop( 'html-v3', {
					content: prop( 'string', label ),
					children: [],
				} ),
			},
		} );
		updateElementEditorSettings( {
			elementId: item.id,
			settings: { title: label || 'Dropdown Item' },
		} );
	};

	const updateLink = ( nextUrl ) => {
		setUrl( nextUrl );
		updateElementSettings( {
			id: item.id,
			props: {
				link: nextUrl ? prop( 'link', {
					destination: prop( 'url', nextUrl ),
					isTargetBlank: prop( 'boolean', false ),
					tag: prop( 'string', 'a' ),
				} ) : null,
			},
		} );
	};

	return (
		<Stack gap={ 0.5 } sx={ { p: 0.75, border: '1px solid', borderColor: 'divider', borderRadius: 1 } }>
			<Stack direction="row" gap={ 0.5 } alignItems="center">
				<TextField
					size="tiny"
					placeholder="Title"
					value={ value }
					onChange={ ( { target } ) => updateLabel( target.value ) }
					sx={ { flex: 1 } }
				/>
				<Tooltip title="Duplicate">
				<IconButton
					size="tiny"
					aria-label="Duplicate dropdown item"
					onClick={ () => onDuplicate( item ) }
				>
					<span style={ { fontSize: 13, lineHeight: 1 } }>⧉</span>
				</IconButton>
				</Tooltip>
				{ canRemove && (
				<Tooltip title="Remove">
					<IconButton
						size="tiny"
						aria-label="Remove dropdown item"
						onClick={ () => onRemove( item ) }
					>
						<span style={ { fontSize: 14, lineHeight: 1 } }>×</span>
					</IconButton>
				</Tooltip>
				) }
			</Stack>
			<TextField
				size="tiny"
				placeholder="Link URL"
				value={ url }
				onChange={ ( { target } ) => updateLink( target.value ) }
			/>
		</Stack>
	);
}
