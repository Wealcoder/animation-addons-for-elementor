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
	selectElement,
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
const MOBILE_NAV_TYPE = 'e-aae-a-mobile-nav';
const DROPDOWN_CLASS = 'aae-a-nav-dropdown';
const dropdownSelectTimers = new Set();

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

function normalizeClassesValue( classes ) {
	const raw = readProp( classes, [] );
	if ( Array.isArray( raw ) ) {
		return raw;
	}
	if ( typeof raw === 'string' ) {
		return raw.split( /\s+/ ).filter( Boolean );
	}
	return [];
}

function classToken( item ) {
	if ( typeof item === 'string' ) {
		return item;
	}
	return item?.value || item?.label || item?.name || item?.id || '';
}

function syncEditorDropdownPreview( navId, itemId ) {
	const previewDocument = window.elementor?.$preview?.[ 0 ]?.contentDocument;
	const nav = previewDocument?.querySelector( `.aae-a-nav[data-id="${ navId }"]` );
	if ( ! nav ) {
		return;
	}

	const revealDropdown = ( item, open ) => {
		const dropdown = item?.querySelector?.(
			':scope > .aae-a-nav-dropdown, :scope > .e-flexbox-base, :scope > .e-con'
		);
		item?.classList.toggle( 'aae-editor-dropdown-open', open );
		if ( ! dropdown ) return;
		dropdown.classList.add( DROPDOWN_CLASS );
		dropdown.setAttribute( 'data-aae-dropdown-for', item.getAttribute( 'data-id' ) || '' );
		if ( open ) {
			dropdown.style.visibility = 'visible';
			dropdown.style.opacity = '1';
			dropdown.style.pointerEvents = 'auto';
		} else {
			dropdown.style.removeProperty( 'visibility' );
			dropdown.style.removeProperty( 'opacity' );
			dropdown.style.removeProperty( 'pointer-events' );
		}
	};

	nav.querySelectorAll( ':scope > .aae-a-nav-item.aae-editor-dropdown-open' ).forEach( item => {
		if ( item.getAttribute( 'data-id' ) !== itemId ) {
			revealDropdown( item, false );
		}
	} );
	if ( itemId ) {
		revealDropdown(
			nav.querySelector( `:scope > .aae-a-nav-item[data-id="${ itemId }"]` ),
			true
		);
	}
}

function syncEditorNestedPreview( itemId, open ) {
	const previewDocument = window.elementor?.$preview?.[ 0 ]?.contentDocument;
	const item = previewDocument?.querySelector( `.aae-a-nav-item[data-id="${ itemId }"]` );
	if ( ! item ) return;

	item.classList.toggle( 'aae-editor-dropdown-open', open );
	const dropdown = item.querySelector(
		':scope > .aae-a-nav-dropdown, :scope > .e-flexbox-base, :scope > .e-con'
	);
	if ( dropdown ) {
		dropdown.classList.add( DROPDOWN_CLASS );
		dropdown.setAttribute( 'data-aae-dropdown-for', item.getAttribute( 'data-id' ) || '' );
	}
	if ( dropdown && open ) {
		dropdown.style.visibility = 'visible';
		dropdown.style.opacity = '1';
		dropdown.style.pointerEvents = 'auto';
	}
	if ( dropdown && ! open ) {
		dropdown.style.removeProperty( 'visibility' );
		dropdown.style.removeProperty( 'opacity' );
		dropdown.style.removeProperty( 'pointer-events' );
	}
	if ( ! open ) return;

	let ancestor = item.parentElement?.closest( '.aae-a-nav-item' );
	while ( ancestor ) {
		ancestor.classList.add( 'aae-editor-dropdown-open' );
		const ancestorDropdown = ancestor.querySelector(
			':scope > .aae-a-nav-dropdown, :scope > .e-flexbox-base, :scope > .e-con'
		);
		if ( ancestorDropdown ) {
			ancestorDropdown.classList.add( DROPDOWN_CLASS );
			ancestorDropdown.setAttribute( 'data-aae-dropdown-for', ancestor.getAttribute( 'data-id' ) || '' );
			ancestorDropdown.style.visibility = 'visible';
			ancestorDropdown.style.opacity = '1';
			ancestorDropdown.style.pointerEvents = 'auto';
		}
		ancestor = ancestor.parentElement?.closest( '.aae-a-nav-item' );
	}
}

function syncEditorDropdownPreviewAfterRender( navId, itemId ) {
	const delays = [ 0, 80, 200, 400, 800, 1200 ];
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

function buildSubItemModel( position ) {
	/* A nested menu item. NOT locked (unlike the top-level defaults) so users
	 * can freely reorder/remove it. Starts without its own dropdown; toggling
	 * "Enable Dropdown" on it (or its own "Add Sub-Item") goes one level deeper. */
	return {
		elType: NAV_ITEM_TYPE,
		editor_settings: { title: `Sub Item ${ position }` },
		settings: {
			text: prop( 'html-v3', {
				content: prop( 'string', `Sub Item ${ position }` ),
				children: [],
			} ),
			has_dropdown: prop( 'boolean', false ),
			trigger: prop( 'string', 'click' ),
			dropdown_animation: prop( 'string', 'gsap' ),
		},
		elements: [],
	};
}

/* First direct child of `container` whose type matches `type` (as a container). */
function findFirstChildOfType( container, type ) {
	const children = container?.model?.get?.( 'elements' );
	let found = null;
	children?.each?.( ( model ) => {
		if ( found ) {
			return;
		}
		if ( ( model.get( 'widgetType' ) || model.get( 'elType' ) ) === type ) {
			found = getContainer( model.get( 'id' ) );
		}
	} );
	return found;
}

function getElementId( container ) {
	return container?.id || container?.model?.get?.( 'id' ) || container?.model?.id || null;
}

function findOwnerNavId( itemId ) {
	let current = getContainer( itemId );
	while ( current ) {
		const type = current.model?.get?.( 'widgetType' ) || current.model?.get?.( 'elType' );
		if ( type === 'e-aae-a-nav' ) {
			return getElementId( current );
		}
		current = current.parent || null;
	}
	return null;
}

function isTopLevelNavItem( itemId ) {
	const item = getContainer( itemId );
	return ( item?.parent?.model?.get?.( 'widgetType' ) || item?.parent?.model?.get?.( 'elType' ) ) === 'e-aae-a-nav';
}

function findDropdownContainer( itemId ) {
	const item = getContainer( itemId );
	if ( ! item ) return null;

	let dropdown = findFirstChildOfType( item, 'e-flexbox' );
	if ( dropdown ) return dropdown;

	item.model?.get?.( 'elements' )?.each?.( model => {
		if ( dropdown ) return;
		const child = getContainer( model.get( 'id' ) );
		if ( hasElementClass( child, DROPDOWN_CLASS ) ) {
			dropdown = child;
		}
	} );

	return dropdown;
}

function selectDropdownContainer( itemId ) {
	const dropdown = normalizeDropdownModel( itemId ) || findDropdownContainer( itemId );
	const dropdownId = getElementId( dropdown );
	if ( ! dropdownId ) return;

	syncEditorNestedPreview( itemId, true );
	try {
		selectElement( dropdownId );
	} catch ( error ) {
		/* The package helper occasionally races during v4 panel re-render.
		 * Fall through to Elementor's lower-level command. */
	}

	const container = getContainer( dropdownId );
	if ( container ) {
		markDropdownFlexbox( container );
		try {
			window.$e?.run?.( 'document/elements/select', {
				container: typeof container.lookup === 'function' ? container.lookup() : container,
				append: false,
			} );
		} catch ( error ) {
			/* best effort */
		}
	}
}

function selectDropdownContainerAfterRender( itemId ) {
	dropdownSelectTimers.forEach( timer => window.clearTimeout( timer ) );
	dropdownSelectTimers.clear();
	[ 0, 120, 280 ].forEach( delay => {
		const timer = window.setTimeout( () => {
			dropdownSelectTimers.delete( timer );
			selectDropdownContainer( itemId );
		}, delay );
		dropdownSelectTimers.add( timer );
	} );
}

function markDropdownFlexbox( flexbox ) {
	if ( ! flexbox || hasElementClass( flexbox, DROPDOWN_CLASS ) ) {
		return;
	}
	const classes = normalizeClassesValue( flexbox.settings?.get?.( 'classes' ) );
	updateElementSettings( {
		id: flexbox.id,
		props: {
			classes: prop( 'classes', [
				...classes,
				DROPDOWN_CLASS,
			] ),
		},
	} );
}

/* Repair old structures where widgets/nav-items were inserted directly below
 * the item. A nav-item must own exactly one dropdown flexbox; everything else
 * belongs inside it. Keeping this invariant makes every nesting level behave
 * identically on desktop, mobile and in the editor. */
function normalizeDropdownModel( itemId ) {
	const item = getContainer( itemId );
	if ( ! item ) return null;

	const childModels = item.model?.get?.( 'elements' );
	let flexbox = findFirstChildOfType( item, 'e-flexbox' );
	const stray = [];
	childModels?.each?.( model => {
		const child = getContainer( model.get( 'id' ) );
		if ( child && child.id !== flexbox?.id ) stray.push( child );
	} );

	const hasDropdown = readProp( item.settings?.get?.( 'has_dropdown' ), false );
	if ( ! flexbox && ( hasDropdown || stray.length ) ) {
		const result = createElements( {
			title: 'Dropdown',
			subtitle: stray.length ? 'Menu structure repaired' : 'Dropdown added',
			elements: [ {
				container: item,
				model: {
					elType: 'e-flexbox',
					editor_settings: { title: 'Dropdown' },
					settings: { classes: prop( 'classes', [ DROPDOWN_CLASS ] ) },
				},
				options: { at: 0 },
			} ],
		} );
		const flexId = result?.createdElements?.[ 0 ]?.containerId;
		flexbox = flexId ? getContainer( flexId ) : findFirstChildOfType( getContainer( itemId ), 'e-flexbox' );
	}

	if ( ! flexbox ) return null;
	markDropdownFlexbox( flexbox );
	if ( stray.length ) {
		moveElements( {
			title: 'Dropdown',
			subtitle: 'Menu structure repaired',
			moves: stray.map( ( element, index ) => ( {
				element,
				targetContainer: flexbox,
				options: { at: index },
			} ) ),
		} );
	}

	/* Migrate the common authoring mistake "AAE Nav inside Dropdown". Promote
	 * that nested nav's items into this dropdown so they become genuine child
	 * items, then remove only the redundant nav wrapper. */
	const nestedNavs = [];
	flexbox.model?.get?.( 'elements' )?.each?.( model => {
		if ( ( model.get( 'widgetType' ) || model.get( 'elType' ) ) === 'e-aae-a-nav' ) {
			const nestedNav = getContainer( model.get( 'id' ) );
			if ( nestedNav ) nestedNavs.push( nestedNav );
		}
	} );
	nestedNavs.forEach( nestedNav => {
		const items = [];
		nestedNav.model?.get?.( 'elements' )?.each?.( model => {
			if ( ( model.get( 'widgetType' ) || model.get( 'elType' ) ) === NAV_ITEM_TYPE ) {
				const child = getContainer( model.get( 'id' ) );
				if ( child ) items.push( child );
			}
		} );
		if ( items.length ) {
			const start = flexbox.model?.get?.( 'elements' )?.length ?? 0;
			moveElements( {
				title: 'Menu hierarchy',
				subtitle: 'Nested menu converted to child items',
				moves: items.map( ( element, index ) => ( {
					element,
					targetContainer: flexbox,
					options: { at: start + index },
				} ) ),
			} );
		}
		removeElements( {
			elementIds: [ nestedNav.id ],
			title: 'Menu hierarchy',
			subtitle: 'Redundant nested Nav removed',
		} );
	} );
	return flexbox;
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
				/* A locked nav-item also locks its dropdown Flexbox in Elementor,
				 * preventing both Structure selection and Style-tab editing. Older
				 * documents were saved locked, so normalize them to editable here. */
				if ( model.get( 'isLocked' ) === true ) {
					model.set( 'isLocked', false, { silent: true } );
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

function useMobileNavSettings( navId ) {
	return useListenTo(
		[
			v1ReadyEvent(),
			commandEndEvent( 'document/elements/create' ),
			commandEndEvent( 'document/elements/update' ),
			commandEndEvent( 'document/elements/settings' ),
			commandEndEvent( 'document/elements/set-settings' ),
		],
		() => {
			const settings = getContainer( navId )?.settings;
			return {
				enabled: readProp( settings?.get?.( 'mobile_enabled' ), false ),
				breakpoint: String( readProp( settings?.get?.( 'mobile_breakpoint' ), '767' ) ),
				position: readProp( settings?.get?.( 'mobile_position' ), 'right' ),
				closeOnLink: readProp( settings?.get?.( 'mobile_close_on_link' ), true ),
				lockScroll: readProp( settings?.get?.( 'mobile_lock_scroll' ), true ),
			};
		},
		[ navId ]
	);
}

function findMobileCompanion( nav ) {
	const siblings = nav?.parent?.model?.get?.( 'elements' );
	let match = null;
	siblings?.each?.( ( model ) => {
		if ( match || ( model.get( 'widgetType' ) || model.get( 'elType' ) ) !== MOBILE_NAV_TYPE ) {
			return;
		}
		const container = getContainer( model.get( 'id' ) );
		if ( readProp( container?.settings?.get?.( 'source_nav_id' ), '' ) === nav.id ) {
			match = container;
		}
	} );
	return match;
}

function hasElementClass( container, className ) {
	return normalizeClassesValue( container?.settings?.get?.( 'classes' ) )
		.some( item => classToken( item ) === className );
}

function flattenLegacyMobileCompanion( companion ) {
	const rootChildren = companion?.model?.get?.( 'elements' );
	let drawer = null;
	rootChildren?.each?.( model => {
		const child = getContainer( model.get( 'id' ) );
		if ( hasElementClass( child, 'aae-mobile-nav-drawer' ) ) drawer = child;
	} );
	if ( ! drawer ) return;

	const moves = [];
	const removeIds = [];
	drawer.model?.get?.( 'elements' )?.each?.( model => {
		const child = getContainer( model.get( 'id' ) );
		if ( hasElementClass( child, 'aae-mobile-nav-close' ) ||
			hasElementClass( child, 'aae-mobile-nav-arrow-template' ) ) {
			moves.push( {
				element: child,
				targetContainer: companion,
				options: { at: companion.model?.get?.( 'elements' )?.length ?? 0 },
			} );
		} else if ( hasElementClass( child, 'aae-mobile-nav-mount' ) ) {
			removeIds.push( child.id );
		}
	} );

	if ( moves.length ) {
		moveElements( {
			title: 'Mobile Menu',
			subtitle: 'Mobile structure optimized',
			moves,
		} );
	}
	if ( removeIds.length ) {
		removeElements( {
			elementIds: removeIds,
			title: 'Mobile Menu',
			subtitle: 'Legacy mount removed',
		} );
	}
}

function hasDescendantClass( container, className ) {
	const children = container?.model?.get?.( 'elements' );
	let found = false;
	children?.each?.( model => {
		if ( found ) return;
		const child = getContainer( model.get( 'id' ) );
		found = hasElementClass( child, className ) || hasDescendantClass( child, className );
	} );
	return found;
}

/* First descendant container (any depth) carrying `className`. */
function findDescendantByClass( container, className ) {
	const children = container?.model?.get?.( 'elements' );
	let found = null;
	children?.each?.( model => {
		if ( found ) return;
		const child = getContainer( model.get( 'id' ) );
		if ( hasElementClass( child, className ) ) {
			found = child;
			return;
		}
		const deep = findDescendantByClass( child, className );
		if ( deep ) found = deep;
	} );
	return found;
}

/* Nav "Mobile Menu" icon-picker prop → the class of the companion's SVG child
 * it should drive. The pickers live on the Nav panel (so users needn't dig
 * into the Structure tree); the reconciler copies each picked svg-src value
 * onto the matching Atomic_Svg inside the companion. */
const MOBILE_ICON_MAP = [
	{ prop: 'mobile_hamburger_icon', className: 'aae-mobile-nav-hamburger' },
	{ prop: 'mobile_close_icon',     className: 'aae-mobile-nav-close-icon' },
	{ prop: 'mobile_dropdown_icon',  className: 'aae-mobile-nav-arrow-template' },
	{ prop: 'mobile_back_icon',      className: 'aae-mobile-nav-back-icon' },
];

function syncCompanionIcons( nav, companion ) {
	MOBILE_ICON_MAP.forEach( ( { prop, className } ) => {
		const iconValue = nav.settings?.get?.( prop );
		if ( ! iconValue ) return;
		const svgChild = findDescendantByClass( companion, className );
		if ( ! svgChild ) return;
		/* Both the picker and the child store the same `svg-src` shape, so copy
		 * through verbatim. Compare against the child's current value so we only
		 * dispatch when the user actually changed the icon (no 200ms spam). */
		const current = svgChild.settings?.get?.( 'svg' );
		if ( JSON.stringify( current ) === JSON.stringify( iconValue ) ) return;
		updateElementSettings( { id: svgChild.id, props: { svg: iconValue } } );
	} );
}

export function MobileNavLifecycleControl() {
	const { element } = useElement();
	const navId = element.id;
	const mobileSettings = useMobileNavSettings( navId );
	const syncRef = React.useRef( '' );
	const creatingRef = React.useRef( false );

	/* Single reconciler for the companion element.
	 *
	 * Elementor's atomic Switch control does not reliably emit a public
	 * commandEnd event when toggled (it commits through an internal
	 * `document/elements/set-settings` transaction), so the listener-based
	 * `mobileSettings` hook can miss the change until an unrelated command
	 * fires. Earlier revisions worked around that with several overlapping
	 * creators (an interval poller + a staggered setTimeout sync + the manual
	 * button), each with its own latch — and they raced each other, which is
	 * why the companion appeared only *sometimes*.
	 *
	 * This is now ONE interval-driven reconciler that reads the nav's live
	 * Backbone settings (bypassing the unreliable event) and converges the
	 * document to the correct state: companion exists iff `mobile_enabled`,
	 * and its props mirror the nav's. The `creatingRef` latch is released only
	 * when the created companion is actually observed in the tree, so there is
	 * exactly one create in flight at a time — no double-create, no missed
	 * create. */
	React.useEffect( () => {
		const reconcile = () => {
			const nav = getContainer( navId );
			if ( ! nav?.parent ) return;

			/* Sweep orphaned companions: a Mobile Nav is a SIBLING of its source
			 * Nav, so deleting the Nav does not cascade-delete it. Any companion
			 * whose source_nav_id no longer resolves is dead weight — it bloats
			 * the saved document and (before nav.js tears down its editor
			 * machinery) contributes to the device-switch hang. Remove them here
			 * while some Nav is selected. */
			const parentChildren = nav.parent.model?.get?.( 'elements' );
			const orphanIds = [];
			parentChildren?.each?.( ( model ) => {
				if ( ( model.get( 'widgetType' ) || model.get( 'elType' ) ) !== MOBILE_NAV_TYPE ) {
					return;
				}
				const container = getContainer( model.get( 'id' ) );
				const src = readProp( container?.settings?.get?.( 'source_nav_id' ), '' );
				if ( src && ! getContainer( src ) ) {
					orphanIds.push( model.get( 'id' ) );
				}
			} );
			if ( orphanIds.length ) {
				removeElements( {
					elementIds: orphanIds,
					title: 'Mobile Menu',
					subtitle: 'Orphaned companion removed',
				} );
				return;
			}

			const settings = nav.settings;
			const liveProps = {
				source_nav_id: prop( 'string', navId ),
				enabled: prop( 'boolean', readProp( settings?.get?.( 'mobile_enabled' ), false ) ),
				breakpoint: prop( 'string', String( readProp( settings?.get?.( 'mobile_breakpoint' ), '767' ) ) ),
				position: prop( 'string', readProp( settings?.get?.( 'mobile_position' ), 'right' ) ),
				close_on_link: prop( 'boolean', readProp( settings?.get?.( 'mobile_close_on_link' ), true ) ),
				lock_scroll: prop( 'boolean', readProp( settings?.get?.( 'mobile_lock_scroll' ), true ) ),
			};
			const enabled = liveProps.enabled.value;
			const companion = findMobileCompanion( nav );

			if ( companion ) {
				/* Create landed (or one already existed) — release the latch. */
				creatingRef.current = false;
				/* Icons update SVG children (not the companion root), so run them
				 * independently of the root-props signature guard below. */
				syncCompanionIcons( nav, companion );
				const signature = JSON.stringify( liveProps );
				if ( syncRef.current !== signature ) {
					syncRef.current = signature;
					flattenLegacyMobileCompanion( companion );
					updateElementSettings( { id: companion.id, props: liveProps } );
				}
				return;
			}

			/* No companion. Reset the mirror signature so a later re-enable is
			 * always re-evaluated, then create once while enabled. The latch
			 * blocks re-entry until createElements lands — the next tick sees
			 * the companion and releases it. */
			syncRef.current = '';
			if ( ! enabled || creatingRef.current ) return;

			creatingRef.current = true;
			const siblings = nav.parent.model?.get?.( 'elements' );
			const navIndex = siblings?.indexOf?.( nav.model ) ?? -1;
			createElements( {
				title: 'Mobile Menu',
				subtitle: 'Mobile companion added',
				elements: [ {
					container: nav.parent,
					model: {
						elType: MOBILE_NAV_TYPE,
						editor_settings: { title: 'Mobile Nav' },
						settings: liveProps,
					},
					options: { at: navIndex >= 0 ? navIndex + 1 : siblings?.length ?? 0 },
				} ],
			} );
		};

		reconcile();
		const interval = window.setInterval( reconcile, 200 );
		return () => window.clearInterval( interval );
	}, [ navId ] );

	const nav = getContainer( navId );
	const companion = findMobileCompanion( nav );

	const createMobileStructure = () => {
		const currentNav = getContainer( navId );
		if ( ! currentNav?.parent || findMobileCompanion( currentNav ) || creatingRef.current ) return;
		/* Share the reconciler's latch so a button click and an interval tick
		 * can't both create in the same window. */
		creatingRef.current = true;
		const props = {
			source_nav_id: prop( 'string', navId ),
			enabled: prop( 'boolean', true ),
			breakpoint: prop( 'string', mobileSettings.breakpoint ),
			position: prop( 'string', mobileSettings.position ),
			close_on_link: prop( 'boolean', mobileSettings.closeOnLink ),
			lock_scroll: prop( 'boolean', mobileSettings.lockScroll ),
		};
		const siblings = currentNav.parent.model?.get?.( 'elements' );
		const navIndex = siblings?.indexOf?.( currentNav.model ) ?? -1;
		createElements( {
			title: 'Mobile Menu',
			subtitle: 'Mobile companion added',
			elements: [ {
				container: currentNav.parent,
				model: {
					elType: MOBILE_NAV_TYPE,
					editor_settings: { title: 'Mobile Nav' },
					settings: props,
				},
				options: { at: navIndex >= 0 ? navIndex + 1 : siblings?.length ?? 0 },
			} ],
		} );
	};

	if ( ! mobileSettings?.enabled ) return null;
	const complete = companion &&
		hasDescendantClass( companion, 'aae-mobile-nav-header' ) &&
		hasDescendantClass( companion, 'aae-mobile-nav-back' ) &&
		hasDescendantClass( companion, 'aae-mobile-nav-back-icon' ) &&
		hasDescendantClass( companion, 'aae-mobile-nav-menu-area' ) &&
		hasDescendantClass( companion, 'aae-mobile-nav-footer' );
	if ( companion && complete ) return null;

	const rebuildMobileStructure = () => {
		if ( ! companion ) {
			createMobileStructure();
			return;
		}
		removeElements( {
			elementIds: [ companion.id ],
			title: 'Mobile Menu',
			subtitle: 'Legacy mobile structure removed',
		} );
		window.setTimeout( createMobileStructure, 350 );
	};

	return (
		<Button size="tiny" variant="outlined" onClick={ rebuildMobileStructure } fullWidth>
			{ companion ? 'Upgrade Mobile Menu (adds SVG Back Icon)' : 'Create Mobile Structure' }
		</Button>
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
				<Button size="tiny" variant="outlined" onClick={ handleAdd }>
					{ '+ Add main item' }
				</Button>
			</Stack>
			<Typography variant="caption" sx={ { color: 'text.tertiary' } }>
				{ 'Build the complete menu here. Expand a MAIN item, enable Dropdown Content, then use Add child. Do not place another AAE Nav inside a dropdown.' }
			</Typography>

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
								<Typography variant="caption" sx={ { color: 'text.tertiary', fontWeight: 700 } }>
									{ 'MAIN' }
								</Typography>
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

/* Sub-items of a nav-item = the nav-item children of that item's dropdown
 * flexbox. Same signature-cache pattern as useNavItems, scoped to the flexbox. */
function useSubItems( itemId ) {
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
			const flexbox = findFirstChildOfType( getContainer( itemId ), 'e-flexbox' );
			const children = flexbox?.model?.get?.( 'elements' );
			const next = [];
			const signatureParts = [];
			children?.each?.( ( model ) => {
				if ( ( model.get( 'widgetType' ) || model.get( 'elType' ) ) !== NAV_ITEM_TYPE ) {
					return;
				}
				const id = model.get( 'id' );
				const editorSettings = model.get( 'editor_settings' ) || {};
				const container = getContainer( id );
				const text = readProp( container?.settings?.get?.( 'text' ), {} );
				const title = readProp( text?.content, '' ) || editorSettings.title || 'Sub Item';
				next.push( { id, title } );
				signatureParts.push( `${ id }:${ title }` );
			} );

			const signature = signatureParts.join( '|' );
			if ( cacheRef.current.signature === signature ) {
				return cacheRef.current.value;
			}
			cacheRef.current = { signature, value: next };
			return next;
		},
		[ itemId ]
	);
}

/**
 * NavSubItemsControl — the "Sub-menu Items" manager shown on EVERY nav-item's
 * panel (registered under 'aae-nav-sub-items'). It lists / adds / removes the
 * nested menu items inside this item's dropdown flexbox. Because it's on every
 * nav-item, selecting a nested item shows the same manager — that is how a user
 * builds 2nd / 3rd-level menus without touching the Structure tree.
 *
 * Nested items are placed INSIDE the item's dropdown flexbox (a core element),
 * so the AAE-element chain never runs deep enough to trigger the device-switch
 * freeze (Nav → item → flexbox → nested item → flexbox → …).
 */
export function NavSubItemsControl() {
	const { element } = useElement();
	return <SubItemsManager itemId={ element.id } />;
}

function SubItemsManager( { itemId } ) {
	const subItems = useSubItems( itemId );
	const normalizedRef = React.useRef( null );
	const [ expandedId, setExpandedId ] = React.useState( null );

	React.useEffect( () => {
		if ( normalizedRef.current === itemId ) return;
		normalizedRef.current = itemId;
		normalizeDropdownModel( itemId );
	}, [ itemId ] );

	/* Ensure this item has a dropdown flexbox (its submenu container); create it
	 * and flip has_dropdown on if missing — so "Add Sub-Item" is one click. */
	const ensureDropdownFlexbox = () => {
		const item = getContainer( itemId );
		if ( ! item ) {
			return null;
		}
		const existing = normalizeDropdownModel( itemId );
		if ( existing ) {
			return existing;
		}
		const result = createElements( {
			title: 'Dropdown',
			subtitle: 'Dropdown added',
			elements: [ {
				container: item,
				model: {
					elType: 'e-flexbox',
					editor_settings: { title: 'Dropdown' },
					settings: { classes: prop( 'classes', [ DROPDOWN_CLASS ] ) },
				},
				options: { at: 0 },
			} ],
		} );
		updateElementSettings( {
			id: itemId,
			props: { has_dropdown: prop( 'boolean', true ) },
		} );
		const flexId = result?.createdElements?.[ 0 ]?.containerId;
		return flexId ? getContainer( flexId ) : findFirstChildOfType( getContainer( itemId ), 'e-flexbox' );
	};

	const handleAddSubItem = () => {
		const flexbox = ensureDropdownFlexbox();
		if ( ! flexbox ) {
			return;
		}
		const result = createElements( {
			title: 'Sub Item',
			subtitle: 'Sub-menu item added',
			elements: [ {
				container: flexbox,
				model: buildSubItemModel( subItems.length + 1 ),
				options: { at: subItems.length },
			} ],
		} );
		const id = result?.createdElements?.[ 0 ]?.containerId;
		if ( id ) setExpandedId( id );
	};

	const handleRemove = ( id ) => {
		removeElements( {
			elementIds: [ id ],
			title: 'Sub Item',
			subtitle: 'Sub-menu item removed',
		} );
	};

	const toggleChild = ( id ) => {
		const opening = expandedId !== id;
		if ( expandedId ) syncEditorNestedPreview( expandedId, false );
		setExpandedId( opening ? id : null );
		syncEditorNestedPreview( id, opening );
	};

	return (
		<Stack gap={ 1 } sx={ { mt: 0.5 } }>
			<Stack direction="row" alignItems="center" justifyContent="space-between">
				<Typography variant="caption" sx={ { fontWeight: 500, color: 'text.secondary' } }>
					{ 'Child items' }
				</Typography>
				<Button size="tiny" variant="outlined" onClick={ handleAddSubItem }>
					{ '+ Add child' }
				</Button>
			</Stack>

			{ subItems.length === 0 ? (
				<Typography variant="caption" sx={ { color: 'text.tertiary' } }>
					{ 'No child items. Add one to create the next menu level.' }
				</Typography>
			) : (
				<Stack gap={ 0.5 }>
					{ subItems.map( ( row ) => (
						<Box
							key={ row.id }
							sx={ {
								display: 'grid',
								gridTemplateColumns: '1fr auto',
								alignItems: 'center',
								border: '1px solid',
								borderColor: expandedId === row.id ? 'primary.main' : 'divider',
								borderRadius: 1,
								bgcolor: 'background.default',
								overflow: 'hidden',
							} }
						>
							<Box
								onClick={ () => toggleChild( row.id ) }
								sx={ { display: 'flex', alignItems: 'center', gap: 0.75, px: 1, py: 0.7, cursor: 'pointer' } }
							>
								<Typography variant="caption" sx={ { color: 'primary.main', fontWeight: 700 } }>
									{ 'CHILD' }
								</Typography>
								<Typography variant="body2" sx={ { fontWeight: expandedId === row.id ? 600 : 400 } }>
									{ row.title }
								</Typography>
							</Box>
							<Tooltip title="Remove">
								<IconButton
									size="tiny"
									aria-label="Remove sub-item"
									onClick={ () => handleRemove( row.id ) }
									sx={ { mr: 0.5 } }
								>
									<span style={ { fontSize: 14, lineHeight: 1 } }>×</span>
								</IconButton>
							</Tooltip>
							<Collapse in={ expandedId === row.id } sx={ { gridColumn: '1 / -1' } }>
								<Box sx={ { p: 1, borderTop: '1px solid', borderColor: 'divider' } }>
									<NavItemFields
										elementId={ row.id }
										fallbackTitle={ row.title }
										onDropdownToggle={ () => {} }
										onTitleChange={ () => {} }
									/>
								</Box>
							</Collapse>
						</Box>
					) ) }
				</Stack>
			) }
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
							settings: { classes: prop( 'classes', [ DROPDOWN_CLASS ] ) },
						},
						options: { at: 0 },
					} ],
				} );
			}
		}

		updateElementSettings( {
			id: elementId,
			props: { has_dropdown: prop( 'boolean', enabled ) },
		} );
		if ( enabled && childIds.length > 0 ) {
			normalizeDropdownModel( elementId );
		}
		if ( enabled ) {
			const ownerNavId = findOwnerNavId( elementId );
			if ( ownerNavId && isTopLevelNavItem( elementId ) ) {
				syncEditorDropdownPreviewAfterRender( ownerNavId, elementId );
			} else {
				syncEditorNestedPreview( elementId, true );
			}
		}
	};

	const updateDropdownSetting = ( key, value, setter ) => {
		setter( value );
		updateElementSettings( {
			id: elementId,
			props: { [ key ]: prop( 'string', value ) },
		} );
	};

	const editDropdownStyle = () => {
		/* normalizeDropdownModel can dispatch settings/move commands that replace
		 * the editing-panel tree without throwing. Re-select across that short
		 * render window so the user always lands on the actual Flexbox Style tab. */
		selectDropdownContainerAfterRender( elementId );
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
					<Button size="tiny" variant="outlined" onClick={ editDropdownStyle } fullWidth>
						{ 'Edit dropdown style' }
					</Button>
					<Typography variant="caption" sx={ { color: 'text.tertiary' } }>
						{ 'Selects this item’s dropdown container. Use its Style tab for background, width, padding and layout.' }
					</Typography>
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
					<Box sx={ { pt: 1, mt: 0.5, borderTop: '1px solid', borderColor: 'divider' } }>
						<SubItemsManager itemId={ elementId } />
					</Box>
				</>
			) }
		</Stack>
	);
}
