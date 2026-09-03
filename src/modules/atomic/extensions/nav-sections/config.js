/* eslint-env browser */

/**
 * Nav → the Dropdown Icon panel section.
 *
 * This is an ELEMENT section, not an extension: it attaches to an anchor prop
 * AAE_A_Nav declares in its own schema, so it appears on e-aae-a-nav only.
 * Same wiring as the WP Menu's panel — the responsive framework binds by
 * anchor key, never by element type, which is what lets it attach to an
 * Atomic_Element_Base the same way it attaches to a widget.
 *
 * Every row is a new responsive prop APPENDED to the existing Dropdown Icon
 * section. Nothing else on the Nav changes: the section keeps its Show Icon
 * switch and its Icon picker, in that order, and the three mobile icons keep
 * their pickers where they have always been (see fields.js for why they need
 * no rows of their own).
 *
 * Frontend/canvas delivery is CSS, not JS config:
 *   PHP  → Widgets/Nav/class-aae-a-nav-responsive.php  (footer <style>)
 *   JS   → editor-bridge/nav-responsive-preview.js     (same node, live)
 */

import { NAV_SECTIONS, NAV_PLAY_GROUP } from './fields';

/** The input a kind is edited with. */
function controlFor( field ) {
	return field.kind === 'color' ? 'color' : 'number';
}

const configs = NAV_SECTIONS.map( ( section ) => ( {
	anchorKey: section.anchorKey,
	bindPrefix: section.prefix,
	fields: section.fields.map( ( field ) => {
		const row = {
			bind: field.bind,
			label: field.label,
			control: controlFor( field ),
			// Every row starts EMPTY. A seeded default would make the panel
			// claim a value the element is not actually rendering with, and
			// would tempt the builder into "resetting" it to a number that then
			// gets emitted for real. Placeholder text carries the current
			// behaviour instead, via each field's `help`.
			defaultValue: null,
			play_group: NAV_PLAY_GROUP,
		};

		if ( field.tab ) row.tab = field.tab;
		if ( field.help ) row.help = field.help;

		// Rotation is the one row with a meaningful negative value; sizes and
		// padding have none, and a stray minus there reads as a broken field
		// rather than a deliberate choice.
		if ( field.kind === 'px' ) row.min = 0;
		if ( typeof field.min === 'number' ) row.min = field.min;
		if ( typeof field.max === 'number' ) row.max = field.max;

		return row;
	} ),
} ) );

export default configs;
