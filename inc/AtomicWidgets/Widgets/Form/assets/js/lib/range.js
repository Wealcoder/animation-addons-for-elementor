/**
 * AAE Form — range slider colour bridge.
 *
 * There is no `accent-color` key in Elementor's atomic Style-panel schema,
 * so a range field's Style tab → Background Color can't compile straight
 * into `accent-color` the way border/color/background compile into their
 * own real CSS properties. This copies the ALREADY-STYLED input's own
 * computed `background-color` onto its `accentColor` at init — the one CSS
 * property that actually recolors a native range's track/thumb consistently
 * across Chrome/Firefox/Safari. Mirrors `lib/multi-select.js`'s
 * `applyStyle()`, which does the same computed-style-copy trick for the
 * multi-select trigger button.
 *
 * Loaded via a STATIC import from form.js but INVOKED only when a form
 * actually contains a `[data-aae-range]` input (see initForm) — inert on
 * forms that don't use it.
 */

/** Copy one range input's own computed background-color onto accent-color. */
function applyAccentColor( input ) {
	const cs = input.ownerDocument.defaultView.getComputedStyle( input );
	const bg = cs.backgroundColor;
	if ( bg && 'rgba(0, 0, 0, 0)' !== bg && 'transparent' !== bg ) {
		input.style.accentColor = bg;
	}
}

/** Bridge every range field inside a form. Idempotent (re-reads are cheap/safe). */
export function initRange( form ) {
	form.querySelectorAll( 'input[data-aae-range="true"]' ).forEach( applyAccentColor );
}
