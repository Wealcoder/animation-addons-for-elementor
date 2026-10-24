/* eslint-env browser */

/**
 * Mobile menu icons picked in the Nav's "Mobile Menu Icons" section reach the
 * companion's SVG parts right away.
 *
 * The copy used to live only in the Mobile Menu section's reconciler, which exists
 * only while THAT section is open - so an icon chosen or uploaded in the Icons
 * section never arrived. This data hook runs after every settings change on a Nav
 * and does the same idempotent copy (syncCompanionIcons), nothing else.
 */

import { findMobileCompanion, syncCompanionIcons } from '../element-controls/NavItemsControl';

const NAV_TYPE = 'e-aae-a-nav';
const COMMANDS = [ 'document/elements/settings', 'document/elements/set-settings' ];

let registered = false;

export function startNavIconSync() {
	if ( registered ) {
		return;
	}

	const hooks = window.$e?.hooks;
	const After = window.$e?.modules?.hookData?.After;

	if ( ! hooks?.registerDataAfter || ! After ) {
		return;
	}

	COMMANDS.forEach( ( command ) => {
		class NavIconSync extends After {
			getCommand() {
				return command;
			}

			getId() {
				return `aae-nav-icon-sync--${ command }`;
			}

			getConditions( args ) {
				const settings = args?.settings || {};

				return Object.keys( settings ).some( ( key ) => /^mobile_.*_icon$/.test( key ) );
			}

			apply( args ) {
				const containers = args?.containers || ( args?.container ? [ args.container ] : [] );

				containers.forEach( ( container ) => {
					if ( NAV_TYPE !== ( container?.model?.get?.( 'widgetType' ) || container?.model?.get?.( 'elType' ) ) ) {
						return;
					}

					try {
						syncCompanionIcons( container, findMobileCompanion( container ), true );
					} catch ( e ) {
						// A convenience; never get in the way of the edit itself.
					}
				} );
			}
		}

		hooks.registerDataAfter( new NavIconSync() );
	} );

	registered = true;
}
