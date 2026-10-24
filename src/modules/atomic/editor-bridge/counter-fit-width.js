/* eslint-env browser */

/**
 * A freshly dropped Counter hugs its content instead of stretching across the row.
 *
 * The Counter's root carries Elementor's `.e-con` class, which gives it
 * `width: var(--width)` (100%). Dropped on a page it therefore spans the whole
 * width, the number centred inside it, and its selection box, hover outline and
 * spacing all span the row too.
 *
 * The width is NOT changed in the widget's base style: that would change every
 * Counter already saved (one that sits centred in a column would jump to the
 * left on a live site). So it is done per element instead. When the user drops a
 * new Counter, one local style, `width: auto`, is written onto it. Counters that
 * already exist carry no such style and render exactly as before, and on the new
 * one it is an ordinary Style tab value (Size > Width) the user can change or
 * clear.
 *
 * It runs as a data hook after `document/elements/create`. A drop from the panel
 * sends the panel's model, which has no `id`. Paste, duplicate, undo, a library
 * insert and a preset apply all send a full model (an `id`, plus `settings` or
 * `elements`), so they are left alone.
 */

import { createElementStyle } from '@elementor/editor-elements';

const COUNTER_TYPE = 'e-aae-a-counter';

// Data hooks live on the command bus for the whole editor session and cannot be
// unregistered, so this installs once even though bootstrap() runs on every
// preview load.
let registered = false;

export function startCounterFitWidth() {
	if ( registered ) {
		return;
	}

	const hooks = window.$e?.hooks;
	const After = window.$e?.modules?.hookData?.After;

	if ( ! hooks?.registerDataAfter || ! After ) {
		return;
	}

	class CounterFitWidth extends After {
		getCommand() {
			return 'document/elements/create';
		}

		getId() {
			return 'aae-counter-fit-width--document/elements/create';
		}

		getConditions( args ) {
			const model = args?.model || {};

			return COUNTER_TYPE === ( model.widgetType || model.elType )
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

			// Never write over an element that already carries local styles.
			if ( Object.keys( created.model?.get?.( 'styles' ) || {} ).length ) {
				return;
			}

			try {
				createElementStyle( {
					elementId: created.id,
					classesProp: 'classes',
					label: 'local',
					meta: { breakpoint: 'desktop', state: null },
					props: { width: { $$type: 'size', value: { size: '', unit: 'auto' } } },
				} );
			} catch ( _e ) {
				// A cosmetic default. It must never get in the way of the drop itself.
			}
		}
	}

	hooks.registerDataAfter( new CounterFitWidth() );
	registered = true;
}
