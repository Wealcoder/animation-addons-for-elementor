/* eslint-env browser */

/**
 * A Nav dropped from the panel starts from the site's WordPress menu.
 *
 * Runs as a data hook after `document/elements/create`. A drop from the panel
 * sends the panel's model: no `id`, no `settings`, no `elements`. Paste,
 * duplicate, undo, a library insert and a preset apply all send a full model, so
 * they are left alone. The actual work (and every safety check) is
 * autoPopulateNavFromWordPress() in NavItemsControl.jsx.
 */

import { autoPopulateNavFromWordPress } from '../element-controls/NavItemsControl';

const NAV_TYPE = 'e-aae-a-nav';

// Data hooks live on the command bus for the whole editor session and cannot be
// unregistered, so this installs once even though bootstrap() runs on every
// preview load.
let registered = false;

export function startNavAutoMenu() {
	if ( registered ) {
		return;
	}

	const hooks = window.$e?.hooks;
	const After = window.$e?.modules?.hookData?.After;

	if ( ! hooks?.registerDataAfter || ! After ) {
		return;
	}

	class NavAutoMenu extends After {
		getCommand() {
			return 'document/elements/create';
		}

		getId() {
			return 'aae-nav-auto-menu--document/elements/create';
		}

		getConditions( args ) {
			const model = args?.model || {};

			return NAV_TYPE === ( model.widgetType || model.elType )
				&& ! model.id
				&& ! model.settings
				&& ! model.elements
				&& ! args?.options?.clone;
		}

		apply( args, result ) {
			const created = Array.isArray( result ) ? result[ 0 ] : result;

			if ( ! created?.id ) {
				return;
			}

			try {
				autoPopulateNavFromWordPress( created.id );
			} catch ( e ) {
				// A convenience. It must never get in the way of the drop itself.
			}
		}
	}

	hooks.registerDataAfter( new NavAutoMenu() );
	registered = true;
}
