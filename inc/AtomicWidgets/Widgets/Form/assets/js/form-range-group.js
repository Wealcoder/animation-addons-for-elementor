/**
 * AAE Form Range Group — frontend + editor-preview handler.
 *
 * The group has its own bundle, separate from form.js, because the widget is
 * useful on its own: a "how many m²" slider with a live readout is a perfectly
 * good page element with no <form> around it, and form.js only ever
 * initialises what it finds inside `.aae-a-form`. This handler is enqueued by
 * the widget's own registry entry, so it loads exactly where the widget is and
 * nowhere else (the plugin's on-demand asset rule).
 *
 * Everything it needs is keyed on rendered data-attributes — never on classes,
 * which the editor canvas strips from atomic elements in edit-mode, and never
 * on a `classes`-prop hook, which the panel's "missing classes" ✕ can unapply.
 * That is what makes the same code path correct on the frontend and inside the
 * editor, where it also means the readout tracks the slider live while the
 * builder is still designing the row.
 *
 * The real work — paint, delegate, name the slider — lives in lib/range.js, so
 * the in-form path (form.js) and this one cannot drift apart.
 */

import { register } from '@elementor/frontend-handlers';
import { syncRangeGroup } from './lib/range';

register( {
	elementType: 'e-aae-a-form-range-group',
	id: 'aae-a-form-range-group-handler',
	callback: ( { element } ) => {
		const group = element.matches?.( '[data-aae-range-group="true"]' )
			? element
			: element.querySelector( '[data-aae-range-group="true"]' );

		if ( group ) {
			syncRangeGroup( group );
		}
	},
} );
