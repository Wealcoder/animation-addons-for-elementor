/**
 * AAE Form — password reveal (eye) toggle.
 *
 * Progressive enhancement over the real `<input type="password">` (see
 * aae-a-form-password.html.twig): flips the input's `type` between
 * `password` and `text` and swaps the button's two inline SVGs. Everything
 * else about the field — submit, validation, sanitize — is untouched, so a
 * password field is "just an input" to the rest of form.js.
 *
 * Min-length and confirm-match validation live in form.js (validateFrontend)
 * next to the other rules, not here — this module is purely the toggle.
 *
 * Loaded via a STATIC import from form.js but INVOKED only when a form
 * actually contains a `.aae-a-form-password` wrapper (see initForm).
 *
 * No framework, no GSAP (MVP form constraint).
 */

const BOUND = '__aaePasswordBound';

/** i18n for the toggle's accessible label, via the shared config bag. */
const label = ( key, fallback ) =>
	window.AAEFormConfig?.i18n?.[ key ] || fallback;

function applyState( input, button, revealed ) {
	input.type = revealed ? 'text' : 'password';
	button.setAttribute( 'aria-pressed', revealed ? 'true' : 'false' );
	button.setAttribute(
		'aria-label',
		revealed
			? label( 'hidePassword', 'Hide password' )
			: label( 'showPassword', 'Show password' )
	);

	const eye = button.querySelector( '.aae-a-form-password-eye' );
	const eyeOff = button.querySelector( '.aae-a-form-password-eye-off' );
	if ( eye ) {
		eye.hidden = revealed;
	}
	if ( eyeOff ) {
		eyeOff.hidden = ! revealed;
	}
}

function bindOne( wrap ) {
	if ( wrap[ BOUND ] ) {
		return;
	}

	const input = wrap.querySelector( 'input.aae-a-form-password-input' );
	const button = wrap.querySelector( '[data-aae-password-toggle]' );
	if ( ! input || ! button ) {
		return; // toggle switched off — nothing to enhance.
	}

	wrap[ BOUND ] = true;

	button.addEventListener( 'click', () => {
		const revealed = 'text' === input.type;
		applyState( input, button, ! revealed );
		// Keep the caret where the visitor was typing.
		input.focus( { preventScroll: true } );
	} );

	applyState( input, button, false );
}

/**
 * Enhance every password field inside a form. Idempotent — safe to call on
 * every editor re-render (mirrors initRating/initMultiSelect).
 *
 * @param {HTMLFormElement} form
 */
export function initPassword( form ) {
	form
		.querySelectorAll( '.aae-a-form-password' )
		.forEach( ( wrap ) => bindOne( wrap ) );
}

/**
 * Re-lock every revealed password field (called after a successful submit /
 * form reset, so a reset form never leaves a password on screen).
 *
 * @param {HTMLFormElement} form
 */
export function resetPassword( form ) {
	form.querySelectorAll( '.aae-a-form-password' ).forEach( ( wrap ) => {
		const input = wrap.querySelector( 'input.aae-a-form-password-input' );
		const button = wrap.querySelector( '[data-aae-password-toggle]' );
		if ( input && button ) {
			applyState( input, button, false );
		}
	} );
}
