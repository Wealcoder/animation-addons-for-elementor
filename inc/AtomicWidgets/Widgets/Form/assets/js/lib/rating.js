/**
 * AAE Form — star-rating enhancement.
 *
 * Progressive enhancement over a real `<input type="number">` (see
 * aae-a-form-rating.html.twig): the native input stays in the DOM, visually
 * hidden, so submit/validation/sanitize are completely unchanged — Rating is
 * "just a number field" to the rest of form.js and to Validator.php. This
 * only paints a row of clickable star buttons on top and mirrors clicks onto
 * the real input's value, firing a `change` event so validation reacts
 * exactly as to a native edit.
 *
 * Loaded via a STATIC import from form.js but INVOKED only when a form
 * actually contains a `[data-aae-rating]` wrapper (see initForm) — inert on
 * forms that don't use it.
 *
 * No framework, no GSAP (MVP form constraint).
 */

const STAR_FULL =
	'M12 2 14.9 8.1 21.6 9 16.8 13.6 18 20.3 12 17.1 6 20.3 7.2 13.6 2.4 9 9.1 8.1z';

function starSvg() {
	const doc = document;
	const svg = doc.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
	svg.setAttribute( 'viewBox', '0 0 24 24' );
	svg.setAttribute( 'width', '1em' );
	svg.setAttribute( 'height', '1em' );
	svg.setAttribute( 'aria-hidden', 'true' );
	svg.setAttribute( 'focusable', 'false' );

	const path = doc.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
	path.setAttribute( 'd', STAR_FULL );
	path.setAttribute( 'fill', 'currentColor' );
	svg.appendChild( path );

	return svg;
}

/** Paint the filled/empty state of every star button from the input's value. */
function paint( wrap, input ) {
	const value = Number( input.value ) || 0;
	wrap.querySelectorAll( '.aae-a-form-rating-star' ).forEach( ( btn ) => {
		const on = Number( btn.dataset.aaeRatingValue ) <= value;
		btn.classList.toggle( 'aae-a-form-rating-star--on', on );
		btn.setAttribute( 'aria-checked', on ? 'true' : 'false' );
	} );
}

/** Enhance one `[data-aae-rating]` wrapper. Idempotent (guards on a bound flag). */
function enhance( wrap ) {
	if ( ! wrap || wrap.dataset.aaeRatingBound === 'true' ) {
		return;
	}

	const input = wrap.querySelector( 'input.aae-a-form-rating-native' );
	if ( ! input ) {
		return;
	}
	wrap.dataset.aaeRatingBound = 'true';

	const max = parseInt( wrap.dataset.aaeRatingMax, 10 ) || 5;
	const doc = wrap.ownerDocument;

	// Visually hide the native control but keep it submittable + focusable for
	// a11y fallback — not display:none (some browsers skip its value then).
	input.classList.add( 'aae-a-form-rating-native--hidden' );

	const row = doc.createElement( 'div' );
	row.className = 'aae-a-form-rating-row';
	row.setAttribute( 'role', 'radiogroup' );

	for ( let i = 1; i <= max; i++ ) {
		const btn = doc.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'aae-a-form-rating-star';
		btn.dataset.aaeRatingValue = String( i );
		btn.setAttribute( 'role', 'radio' );
		btn.setAttribute( 'aria-checked', 'false' );
		btn.setAttribute( 'aria-label', String( i ) );
		btn.appendChild( starSvg() );

		btn.addEventListener( 'click', () => {
			// Clicking the currently-set star clears the rating (toggle off) —
			// lets a visitor undo an accidental click without a separate control.
			const next = Number( input.value ) === i ? 0 : i;
			input.value = String( next );
			paint( wrap, input );
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );

		row.appendChild( btn );
	}

	wrap.appendChild( row );
	paint( wrap, input );

	input.addEventListener( 'change', () => paint( wrap, input ) );
}

/** Enhance every rating field inside a form. */
export function initRating( form ) {
	form.querySelectorAll( '[data-aae-rating]' ).forEach( enhance );
}

/**
 * Re-sync every enhanced rating's star row from the real input's value. Call
 * after a native form reset (which clears the real input but not our custom
 * row) so the UI matches again — same reasoning as multi-select's syncMultiSelect.
 */
export function syncRating( form ) {
	form.querySelectorAll( '[data-aae-rating].aae-a-form-rating' ).forEach( ( wrap ) => {
		const input = wrap.querySelector( 'input.aae-a-form-rating-native' );
		if ( input ) {
			paint( wrap, input );
		}
	} );
}
