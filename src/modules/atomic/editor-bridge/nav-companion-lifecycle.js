/* eslint-env browser */

/**
 * Nav companion lifecycle — delete the Mobile Nav together with its Nav.
 *
 * THE BUG THIS FIXES. A Mobile Nav (`e-aae-a-mobile-nav`) is a SIBLING of the
 * Nav it belongs to, so Elementor does not cascade-delete it. There is already
 * an orphan sweep in NavItemsControl.jsx, but it lives inside
 * `MobileNavLifecycleControl` — a PANEL CONTROL, mounted only while that Nav is
 * selected. Deleting the Nav unmounts the control and tears down its
 * `setInterval`, so the sweep can never run for the Nav you just deleted. It
 * only fires later, when some OTHER Nav happens to be selected — which is why
 * the user had to hunt the companion down in the Structure panel.
 *
 * This module lives in the editor-bridge instead, so it is document-wide and
 * selection-independent. The panel-control sweep is deliberately left in place
 * as a second belt.
 *
 * WHY IT TRIGGERS ON `document/elements/delete` AND NOTHING ELSE — this is the
 * important part, not a detail. `removeElements()` registers its OWN undoable
 * history entry (editor-elements.js: `undoable({do, undo})` with
 * `useHistory: false` on the inner deletes). So deleting a Nav produces TWO
 * history steps: the Nav delete, then our companion sweep. Undo therefore
 * restores the companion FIRST — at which point its source Nav still does not
 * exist. A sweep that ran after *any* command (the obvious shape, and what
 * form-guards.js does for saves) would immediately re-delete it, and the user
 * could never undo their way back. Narrowing the trigger to the delete command
 * keeps undo working, because undo runs a history command, not a delete.
 *
 * Consequence worth knowing: getting a deleted Nav back takes TWO undos — first
 * the companion, then the Nav. That is inherent to `removeElements` owning its
 * own history entry, and it is recoverable, which the alternative was not.
 */

import { getContainer, removeElements } from '@elementor/editor-elements';
import { track } from './disposables';

const MOBILE_NAV_TYPE = 'e-aae-a-mobile-nav';
const DELETE_COMMAND = 'document/elements/delete';

/** Unwrap an atomic prop envelope (`{ $$type, value }`) or pass a raw value through. */
function readProp( value, fallback = '' ) {
	if ( value === undefined || value === null ) {
		return fallback;
	}

	if ( 'object' === typeof value && Object.prototype.hasOwnProperty.call( value, 'value' ) ) {
		return value.value ?? fallback;
	}

	return value;
}

const typeOf = ( container ) => {
	const model = container?.model;

	if ( ! model?.get ) {
		return '';
	}

	const elType = model.get( 'elType' );

	return 'widget' === elType ? ( model.get( 'widgetType' ) || '' ) : ( elType || '' );
};

const childrenOf = ( container ) => {
	const children = container?.children;

	return children ? Array.from( children ) : [];
};

/** Depth-first walk. Same shape as form-guards.js — keep them consistent. */
const walk = ( container, visit ) => {
	visit( container, typeOf( container ) );
	childrenOf( container ).forEach( ( child ) => walk( child, visit ) );
};

/**
 * Mirrors isEditorModalOrPopoverActive() in NavItemsControl.jsx and
 * isEditorPopoverActive() in nav.js. Mutating the document while a MUI popover
 * is open (the Style-tab colour picker, say) yanks its portal node out from
 * under React and throws "removeChild … not a child", killing the panel.
 * THREE copies of this check now exist — change one, change all three.
 */
function isEditorModalOrPopoverActive() {
	return !! document.querySelector(
		'.MuiPopover-root, .MuiModal-root, [role="presentation"][id*="popover"]'
	);
}

/**
 * Every companion in the current document whose `source_nav_id` is set but no
 * longer resolves to a live element.
 *
 * An EMPTY `source_nav_id` is deliberately left alone: that is either a
 * hand-placed companion or one caught mid-creation by the reconciler, and
 * deleting those would be destroying work rather than cleaning up.
 */
export function findOrphanCompanions( root ) {
	const orphans = [];

	walk( root, ( container, type ) => {
		if ( MOBILE_NAV_TYPE !== type ) {
			return;
		}

		const sourceId = readProp( container.settings?.get?.( 'source_nav_id' ), '' );

		if ( sourceId && ! getContainer( sourceId ) ) {
			orphans.push( container.id );
		}
	} );

	return orphans;
}

export function startNavCompanionLifecycle() {
	const $e = window.$e;

	if ( ! $e?.commands?.on ) {
		return;
	}

	// Guards against re-entry: removeElements() deletes elements, which can put
	// another delete through the command bus. The sweep is idempotent (a second
	// pass finds no orphans) so this is tidiness rather than a correctness fix.
	let sweeping = false;

	const sweep = () => {
		if ( sweeping ) {
			return;
		}

		// Never mutate under an open popover — see isEditorModalOrPopoverActive.
		// Skipping is safe: the panel-control sweep and the PHP save-time sweep
		// both still catch this orphan later.
		if ( isEditorModalOrPopoverActive() ) {
			return;
		}

		const root = window.elementor?.documents?.getCurrent?.()?.container;

		if ( ! root ) {
			return;
		}

		const orphans = findOrphanCompanions( root );

		if ( ! orphans.length ) {
			return;
		}

		sweeping = true;

		try {
			removeElements( {
				elementIds: orphans,
				title: 'Mobile Menu',
				subtitle: 'Removed with its Nav',
			} );
		} finally {
			sweeping = false;
		}
	};

	const onAfter = ( component, command ) => {
		if ( DELETE_COMMAND !== command ) {
			return;
		}

		// Out of the deleting command's own commit: mutating the document from
		// inside another command's synchronous tail re-enters Elementor's React
		// panel mid-render. Same reason NavItemsControl defers its writes.
		window.requestAnimationFrame( sweep );
	};

	$e.commands.on( 'run:after', onAfter );
	track( () => $e.commands.off?.( 'run:after', onAfter ) );
}
