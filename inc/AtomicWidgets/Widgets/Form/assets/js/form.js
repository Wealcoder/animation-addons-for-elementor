/**
 * AAE Form — frontend submit runtime (Milestone 5).
 *
 * Plain fetch + DOM, deliberately no GSAP (hard MVP constraint). Flow per
 * the spec's "Submit runtime requirements":
 *
 *   - init once per form (data-aae-form-ready guard)
 *   - fetch a fresh single-use token (no-store) on first interaction
 *   - intercept submit: frontend-validate → POST JSON to /aae/v1 → map the
 *     response-code table to distinct UI states
 *   - field errors inline (aria-invalid + aria-describedby), focus the
 *     first invalid field, never clear user input on failure
 *   - success/error reveal the editor-authored status-message containers
 *     via the same form-state-{value} classes the editor States toggle uses
 *   - network-state copy (slow/offline/timeout/duplicate/rate-limit) goes
 *     to a dedicated aria-live region, not the authored messages
 *   - a consumed/failed token is dropped; retries fetch a fresh one
 *
 * In the Elementor editor preview the runtime stays inert — previews must
 * never create real submissions.
 */

import { register } from '@elementor/frontend-handlers';

const SLOW_AFTER_MS = 8000;
const TIMEOUT_MS = 30000;

const config = () => window.AAEFormConfig || { restUrl: '/wp-json/aae/v1/', i18n: {} };
const t = ( key, fallback ) => config().i18n?.[ key ] || fallback;

const isEditMode = () =>
	!! ( window.elementorFrontend?.isEditMode?.() || window.elementor );

/* ------------------------------------------------------------------ */
/* Token                                                               */
/* ------------------------------------------------------------------ */

const fetchToken = async ( formKey ) => {
	const response = await fetch(
		`${ config().restUrl }forms/${ encodeURIComponent( formKey ) }/token`,
		{ method: 'POST', cache: 'no-store' }
	);
	if ( ! response.ok ) {
		const error = new Error( 'token' );
		error.status = response.status;
		throw error;
	}
	return response.json(); // { token, nonce, expires_in }
};

/* ------------------------------------------------------------------ */
/* Field errors                                                        */
/* ------------------------------------------------------------------ */

const errorAnchor = ( control ) =>
	// Checkbox/radio sit inside a flex row next to their label — the error
	// belongs after the row, not squeezed between box and label.
	control.closest( '.aae-form-checkbox-row' ) || control;

const clearFieldError = ( control ) => {
	const anchor = errorAnchor( control );
	anchor.parentNode
		?.querySelector( `.aae-form-field-error[data-for="${ control.name }"]` )
		?.remove();
	control.removeAttribute( 'aria-invalid' );
	control.removeAttribute( 'aria-describedby' );
	control.classList.remove( 'aae-form-invalid' );
};

const clearAllErrors = ( form ) => {
	form.querySelectorAll( '.aae-form-field-error' ).forEach( ( el ) => el.remove() );
	form.querySelectorAll( '[aria-invalid]' ).forEach( ( el ) => {
		el.removeAttribute( 'aria-invalid' );
		el.removeAttribute( 'aria-describedby' );
		el.classList.remove( 'aae-form-invalid' );
	} );
};

const showFieldError = ( form, control, message ) => {
	clearFieldError( control );

	const anchor = errorAnchor( control );
	const errorId = `aae-err-${ form.dataset.id || 'f' }-${ control.name.replace( /\W/g, '_' ) }`;

	const el = document.createElement( 'span' );
	el.className = 'aae-form-field-error';
	el.id = errorId;
	el.dataset.for = control.name;
	el.textContent = message;

	anchor.insertAdjacentElement( 'afterend', el );
	control.setAttribute( 'aria-invalid', 'true' );
	control.setAttribute( 'aria-describedby', errorId );
	control.classList.add( 'aae-form-invalid' );

	// Field errors clear as soon as the visitor edits that field.
	control.addEventListener( 'input', () => clearFieldError( control ), { once: true } );
	control.addEventListener( 'change', () => clearFieldError( control ), { once: true } );
};

const focusFirstInvalid = ( form ) => {
	const control = form.querySelector( '[aria-invalid="true"]' );
	if ( ! control ) {
		return;
	}
	// Reveal-before-focus, minimal pass: open any collapsed <details>
	// ancestors so focus doesn't land on a hidden field. Accordion/tab/popup
	// integrations join when those widgets expose a programmatic API.
	let details = control.closest( 'details:not([open])' );
	while ( details ) {
		details.open = true;
		details = details.parentElement?.closest( 'details:not([open])' );
	}
	control.scrollIntoView( { block: 'center', behavior: 'auto' } );
	control.focus( { preventScroll: true } );
};

/* ------------------------------------------------------------------ */
/* Frontend validation (backend repeats all of this authoritatively)   */
/* ------------------------------------------------------------------ */

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const TEL_RE = /^\+?[0-9\-().\s]{3,30}$/;

const controlsOf = ( form ) =>
	Array.from( form.elements ).filter(
		( el ) => el.name && ! el.disabled && ! [ 'submit', 'button', 'reset' ].includes( el.type )
	);

const validateFrontend = ( form ) => {
	const errors = [];
	const seenRadioGroups = new Set();

	for ( const control of controlsOf( form ) ) {
		const value = control.value.trim();

		if ( 'radio' === control.type ) {
			if ( seenRadioGroups.has( control.name ) ) {
				continue;
			}
			seenRadioGroups.add( control.name );
			const group = Array.from( form.querySelectorAll( `input[type="radio"][name="${ CSS.escape( control.name ) }"]` ) );
			if ( group.some( ( radio ) => radio.required ) && ! group.some( ( radio ) => radio.checked ) ) {
				errors.push( [ control, t( 'required', 'This field is required.' ) ] );
			}
			continue;
		}

		if ( 'checkbox' === control.type ) {
			if ( control.required && ! control.checked ) {
				errors.push( [ control, t( 'required', 'This field is required.' ) ] );
			}
			continue;
		}

		if ( control.required && '' === value ) {
			errors.push( [ control, t( 'required', 'This field is required.' ) ] );
			continue;
		}

		if ( '' === value ) {
			continue;
		}

		if ( 'email' === control.type && ! EMAIL_RE.test( value ) ) {
			errors.push( [ control, t( 'invalidEmail', 'Please enter a valid email address.' ) ] );
		} else if ( 'url' === control.type && ! /^https?:\/\/\S+\.\S+/.test( value ) ) {
			errors.push( [ control, t( 'invalidUrl', 'Please enter a valid URL.' ) ] );
		} else if ( 'number' === control.type && Number.isNaN( Number( value ) ) ) {
			errors.push( [ control, t( 'invalidNumber', 'Please enter a number.' ) ] );
		} else if ( 'tel' === control.type && ! TEL_RE.test( value ) ) {
			errors.push( [ control, t( 'invalidTel', 'Please enter a valid phone number.' ) ] );
		}
	}

	return errors;
};

/* ------------------------------------------------------------------ */
/* Payload                                                             */
/* ------------------------------------------------------------------ */

const collectFields = ( form ) => {
	const fields = {};

	for ( const [ rawName, value ] of new FormData( form ) ) {
		if ( rawName.endsWith( '[]' ) ) {
			const name = rawName.slice( 0, -2 );
			( fields[ name ] = fields[ name ] || [] ).push( value );
		} else {
			fields[ rawName ] = value;
		}
	}

	return fields;
};

/* ------------------------------------------------------------------ */
/* UI state                                                            */
/* ------------------------------------------------------------------ */

const setFormState = ( form, state ) => {
	form.classList.remove( 'form-state-default', 'form-state-success', 'form-state-error' );
	form.classList.add( `form-state-${ state }` );
};

const runtimeMessageEl = ( form ) => {
	let el = form.querySelector( '.aae-form-runtime-message' );
	if ( ! el ) {
		el = document.createElement( 'div' );
		el.className = 'aae-form-runtime-message';
		el.setAttribute( 'role', 'status' );
		el.setAttribute( 'aria-live', 'polite' );
		form.appendChild( el );
	}
	return el;
};

const showRuntimeMessage = ( form, message, tone ) => {
	const el = runtimeMessageEl( form );
	el.textContent = message;
	el.dataset.tone = tone || 'error';
	el.hidden = ! message;
};

const setLoading = ( form, button, loading ) => {
	if ( ! button ) {
		return;
	}
	if ( loading ) {
		button.dataset.originalHtml = button.innerHTML;
		button.disabled = true;
		button.setAttribute( 'aria-busy', 'true' );
		button.textContent = button.dataset.loadingLabel || t( 'sending', 'Sending…' );
	} else {
		button.disabled = false;
		button.removeAttribute( 'aria-busy' );
		if ( button.dataset.originalHtml ) {
			button.innerHTML = button.dataset.originalHtml;
			delete button.dataset.originalHtml;
		}
	}
};

/* ------------------------------------------------------------------ */
/* Init                                                                */
/* ------------------------------------------------------------------ */

const initForm = ( form ) => {
	if ( form.dataset.aaeFormReady === 'true' ) {
		return;
	}
	form.dataset.aaeFormReady = 'true';

	// Previews must never create real submissions (spec principle #9).
	if ( isEditMode() ) {
		return;
	}

	const formKey = form.dataset.aaeFormKey;
	if ( ! formKey ) {
		return; // never saved yet — no schema to submit against.
	}

	let auth = null; // { token, nonce }
	let authPromise = null;
	let inFlight = false;

	const ensureAuth = () => {
		if ( auth ) {
			return Promise.resolve( auth );
		}
		if ( ! authPromise ) {
			authPromise = fetchToken( formKey )
				.then( ( data ) => ( auth = data ) )
				.finally( () => ( authPromise = null ) );
		}
		return authPromise;
	};

	// Prefetch on first interaction: by submit time the token is already
	// there, and its issue timestamp makes the server's minimum-submit-time
	// check measure real filling time.
	const prefetch = () => ensureAuth().catch( () => {} );
	form.addEventListener( 'focusin', prefetch, { once: true } );
	form.addEventListener( 'pointerdown', prefetch, { once: true } );

	form.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();

		if ( inFlight ) {
			return; // double-submit guard #2 (the button is disabled too).
		}

		clearAllErrors( form );
		showRuntimeMessage( form, '', 'error' );
		setFormState( form, 'default' );

		const frontendErrors = validateFrontend( form );
		if ( frontendErrors.length ) {
			frontendErrors.forEach( ( [ control, message ] ) => showFieldError( form, control, message ) );
			focusFirstInvalid( form );
			return;
		}

		const button = form.querySelector( 'button[type="submit"]' );
		inFlight = true;
		setLoading( form, button, true );

		const slowTimer = setTimeout( () => {
			showRuntimeMessage( form, t( 'slow', 'Your connection seems slow. Please wait a moment.' ), 'info' );
		}, SLOW_AFTER_MS );

		const abort = new AbortController();
		const timeoutTimer = setTimeout( () => abort.abort(), TIMEOUT_MS );

		try {
			const { token, nonce } = await ensureAuth();

			const response = await fetch(
				`${ config().restUrl }forms/${ encodeURIComponent( formKey ) }/submit`,
				{
					method: 'POST',
					cache: 'no-store',
					headers: { 'Content-Type': 'application/json' },
					signal: abort.signal,
					body: JSON.stringify( {
						token,
						nonce,
						fields: collectFields( form ),
						source_url: window.location.href,
						referrer_url: document.referrer || '',
					} ),
				}
			);

			auth = null; // single-use: spent (or rejected) either way.

			const data = await response.json().catch( () => ( {} ) );

			if ( response.ok ) {
				// Redirect is the immediate UX action — it must not wait for
				// queued email/webhook jobs (spec). Success state still shows
				// for the instant before navigation.
				setFormState( form, 'success' );
				showRuntimeMessage( form, '', 'success' );
				form.reset();
				if ( data.redirect_url ) {
					window.location.assign( data.redirect_url );
					return;
				}
				form.querySelector( '[data-element_type="e-aae-a-form-success-message"]' )
					?.scrollIntoView( { block: 'nearest', behavior: 'auto' } );
				return;
			}

			// Response-code table → distinct states.
			switch ( response.status ) {
				case 422: {
					const errors = data.errors || {};
					let mappedAny = false;
					for ( const [ name, message ] of Object.entries( errors ) ) {
						const control =
							form.querySelector( `[name="${ CSS.escape( name ) }"]` ) ||
							form.querySelector( `[name="${ CSS.escape( name ) }[]"]` );
						if ( control ) {
							showFieldError( form, control, String( message ) );
							mappedAny = true;
						}
					}
					if ( mappedAny ) {
						focusFirstInvalid( form );
					} else {
						showRuntimeMessage( form, data.message || t( 'genericError', 'We could not submit the form. Please try again.' ), 'error' );
					}
					break;
				}
				case 409:
					showRuntimeMessage( form, t( 'duplicate', 'This form was already submitted.' ), 'error' );
					break;
				case 429:
					showRuntimeMessage( form, t( 'rateLimit', 'Too many attempts. Please wait a moment and try again.' ), 'error' );
					break;
				default:
					// 400 / 403 / 404 / 500 — authored error message + copy.
					setFormState( form, 'error' );
					showRuntimeMessage( form, data.message || t( 'genericError', 'We could not submit the form. Please try again.' ), 'error' );
			}
		} catch ( error ) {
			auth = null;
			if ( false === navigator.onLine ) {
				showRuntimeMessage( form, t( 'offline', 'You appear to be offline. Please reconnect and try again.' ), 'error' );
			} else {
				// Abort/timeout/network — input is untouched, retry gets a fresh token.
				showRuntimeMessage( form, t( 'timeout', 'We could not submit the form. Your information is still here. Please try again.' ), 'error' );
			}
		} finally {
			clearTimeout( slowTimer );
			clearTimeout( timeoutTimer );
			setLoading( form, button, false );
			inFlight = false;
		}
	} );
};

register( {
	elementType: 'e-aae-a-form',
	id: 'aae-a-form-handler',
	callback: ( { element } ) => {
		const root = element.classList.contains( 'aae-a-form' )
			? element
			: element.querySelector( '.aae-a-form' );
		if ( root ) {
			initForm( root );
		}
	},
} );
