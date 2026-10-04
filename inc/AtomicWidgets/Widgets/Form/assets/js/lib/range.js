/**
 * AAE Form — range slider colour bridge + the Range Group's live readout.
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
 * TWO entry points share this module, and each answers a different question:
 *
 *   - `initRange( form )` — imported by form.js, runs for every range field
 *     inside a form. Colour only; a bare Range field has no readout.
 *   - `syncRangeGroup( group )` — imported by the Range Group's own bundle
 *     (assets/js/range-group.js), which the widget loads wherever it is
 *     dropped, form or no form. Colour + readout + the accessible name.
 *
 * Both are idempotent, so a group inside a form being reached by both is a
 * no-op the second time rather than a double binding.
 */

/** Copy one range input's own computed background-color onto accent-color. */
export function applyAccentColor( input ) {
	const cs = input.ownerDocument.defaultView.getComputedStyle( input );
	const bg = cs.backgroundColor;
	if ( bg && 'rgba(0, 0, 0, 0)' !== bg && 'transparent' !== bg ) {
		input.style.accentColor = bg;
	}
}

/**
 * Give the slider an accessible name from the group's caption.
 *
 * The caption is a real heading child, so it can't carry a `for=` the way a
 * `<label>` would — and the two elements only meet at runtime. Pointing
 * `aria-labelledby` at the heading is the equivalent that works for whatever
 * the builder put there, and it never overwrites a name the builder set
 * themselves on the slider.
 */
function nameSlider( group, input ) {
	if ( input.getAttribute( 'aria-label' ) || input.getAttribute( 'aria-labelledby' ) ) {
		return;
	}

	const caption = group.querySelector( 'h1, h2, h3, h4, h5, h6, label' );
	if ( ! caption || ! caption.textContent.trim() ) {
		return;
	}

	if ( ! caption.id ) {
		caption.id = 'aae-range-label-' + ( group.getAttribute( 'data-id' ) || '' ) +
			'-' + Math.random().toString( 36 ).slice( 2, 7 );
	}

	input.setAttribute( 'aria-labelledby', caption.id );
}

/** Print the current value into the readout, prefix/suffix included. */
function paint( group ) {
	const input = group.querySelector( 'input[data-aae-range="true"]' );
	if ( ! input ) {
		return;
	}

	applyAccentColor( input );

	const output = group.querySelector( '[data-aae-range-value="true"]' );
	if ( ! output ) {
		return;
	}

	nameSlider( group, input );

	// Prefix joins the number directly ("$250"), suffix is spaced ("250 Sq.")
	// — the same join the value widget's twig prints at rest, so the text
	// doesn't shift the instant the visitor drags.
	const prefix = output.dataset.aaeRangePrefix || '';
	const suffix = output.dataset.aaeRangeSuffix || '';

	output.textContent = prefix + input.value + ( suffix ? ' ' + suffix : '' );
}

/**
 * Wire one Range Group: paint now, and repaint while the slider moves.
 *
 * The listener is DELEGATED to the group root rather than bound to the input
 * for one specific reason: in the editor, changing any setting on the slider
 * re-renders that child, replacing the very node a direct listener would be
 * attached to. The group root survives its children being re-rendered, so
 * delegation keeps working with no re-init, no polling, and no observer.
 *
 * Idempotent — the guard is on the root, and a re-rendered root arrives
 * without it, which is exactly when a fresh binding IS wanted.
 */
export function syncRangeGroup( group ) {
	if ( ! group ) {
		return;
	}

	if ( group.dataset.aaeRangeGroupBound !== 'true' ) {
		group.dataset.aaeRangeGroupBound = 'true';

		group.addEventListener( 'input', ( event ) => {
			if ( event.target?.matches?.( 'input[data-aae-range="true"]' ) ) {
				paint( group );
			}
		} );

		// A form reset restores the slider's default AFTER the event fires.
		group.closest( 'form' )?.addEventListener( 'reset', () => {
			window.requestAnimationFrame( () => paint( group ) );
		} );
	}

	paint( group );
}

/** Bridge every range field inside a form. Idempotent (re-reads are cheap/safe). */
export function initRange( form ) {
	form.querySelectorAll( 'input[data-aae-range="true"]' ).forEach( applyAccentColor );
	form.querySelectorAll( '[data-aae-range-group="true"]' ).forEach( syncRangeGroup );
}
