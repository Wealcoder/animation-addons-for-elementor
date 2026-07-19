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
import { initMultiSelect, syncMultiSelect } from './lib/multi-select';

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

// The optional Field Error style-source widget (e-aae-a-form-field-error):
// an editor-styleable sample span, hidden on the frontend. Its classes (base
// + user styles) are mirrored onto every injected error span, and its text
// is the form's required-field message. Absent → stylesheet defaults.
const errorSourceOf = ( form ) =>
	form.querySelector( '[data-element_type="e-aae-a-form-field-error"]' );

const requiredMessage = ( form ) => {
	const text = errorSourceOf( form )?.textContent.trim();
	return text || t( 'required', 'This field is required.' );
};

// Per-field override: the field widget's "Error message" setting (rendered
// as data-aae-error-message) wins over every default for that field —
// required, format and range errors alike.
const messageFor = ( control, fallback ) =>
	control.dataset.aaeErrorMessage || fallback;

const errorAnchor = ( control ) =>
	// The error belongs after the field's whole visual block, never inside
	// it: a radio group's wrapper (not between its option rows), a checkbox/
	// radio row (not between box and label), or the multi-select enhancement
	// wrap (the native select is hidden INSIDE .aae-ms — inserting after it
	// would paint the message above the trigger button).
	control.closest( '.aae-form-radio-group' ) ||
	control.closest( '.aae-form-checkbox-row' ) ||
	control.closest( '.aae-ms' ) ||
	control;

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

	// Mirror the Field Error style-source widget's classes (its base style +
	// whatever the user styled in the panel) so injected errors look like the
	// sample. The source itself stays hidden via its data-element_type.
	const source = errorSourceOf( form );
	if ( source ) {
		source.classList.forEach( ( cls ) => el.classList.add( cls ) );

		// Invalid-control border colour rides the same element: an explicit
		// Border → Color set in its panel wins; otherwise border computes to
		// currentColor, i.e. the message's text colour. One CSS var keeps the
		// stylesheet rule (.aae-form-invalid) as the single border owner.
		const cs = window.getComputedStyle( source );
		const invalidColor = cs.borderTopColor || cs.color;
		if ( invalidColor ) {
			form.style.setProperty( '--aae-form-invalid-color', invalidColor );
		}
	}

	anchor.insertAdjacentElement( 'afterend', el );
	control.setAttribute( 'aria-invalid', 'true' );
	control.setAttribute( 'aria-describedby', errorId );
	control.classList.add( 'aae-form-invalid' );

	// Field errors clear as soon as the visitor edits that field. For a radio
	// group the edit can land on ANY radio in the group — not just the first
	// one (which is what validation anchored the error to) — so listen on all.
	const clearOnEdit = () => clearFieldError( control );
	const targets =
		'radio' === control.type && control.form
			? Array.from( control.form.querySelectorAll( `input[type="radio"][name="${ CSS.escape( control.name ) }"]` ) )
			: [ control ];
	targets.forEach( ( el ) => {
		el.addEventListener( 'input', clearOnEdit, { once: true } );
		el.addEventListener( 'change', clearOnEdit, { once: true } );
	} );
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
	const requiredMsg = requiredMessage( form );

	for ( const control of controlsOf( form ) ) {
		const value = control.value.trim();

		if ( 'radio' === control.type ) {
			if ( seenRadioGroups.has( control.name ) ) {
				continue;
			}
			seenRadioGroups.add( control.name );
			const group = Array.from( form.querySelectorAll( `input[type="radio"][name="${ CSS.escape( control.name ) }"]` ) );
			if ( group.some( ( radio ) => radio.required ) && ! group.some( ( radio ) => radio.checked ) ) {
				// The message may sit on ANY radio widget of the group.
				const custom = group.map( ( radio ) => radio.dataset.aaeErrorMessage ).find( Boolean );
				errors.push( [ control, custom || requiredMsg ] );
			}
			continue;
		}

		if ( 'checkbox' === control.type ) {
			if ( control.required && ! control.checked ) {
				errors.push( [ control, messageFor( control, requiredMsg ) ] );
			}
			continue;
		}

		if ( 'file' === control.type ) {
			// Size/type/count rules from the widget's own settings (the server
			// re-validates all of it from the schema at upload time).
			const files = Array.from( control.files || [] );

			if ( ! files.length ) {
				if ( control.required ) {
					errors.push( [ control, messageFor( control, requiredMsg ) ] );
				}
				continue;
			}

			const maxFiles = control.multiple ? parseInt( control.dataset.aaeMaxFiles, 10 ) || 10 : 1;
			const maxMb = parseFloat( control.dataset.aaeMaxSize ) || 10;
			const accept = ( control.dataset.aaeAccept || '' )
				.toLowerCase()
				.split( ',' )
				.map( ( s ) => s.trim() )
				.filter( Boolean );

			if ( files.length > maxFiles ) {
				errors.push( [ control, messageFor( control, t( 'tooManyFiles', `Please choose at most ${ maxFiles } file(s).` ) ) ] );
			} else if ( files.some( ( file ) => file.size > maxMb * 1024 * 1024 ) ) {
				errors.push( [ control, messageFor( control, t( 'fileTooLarge', `File is too large. Maximum size is ${ maxMb } MB.` ) ) ] );
			} else if (
				accept.length &&
				files.some( ( file ) => ! accept.includes( ( file.name.split( '.' ).pop() || '' ).toLowerCase() ) )
			) {
				errors.push( [ control, messageFor( control, t( 'fileType', 'This file type is not allowed.' ) ) ] );
			}
			continue;
		}

		if ( control.required && '' === value ) {
			errors.push( [ control, messageFor( control, requiredMsg ) ] );
			continue;
		}

		if ( '' === value ) {
			continue;
		}

		if ( 'email' === control.type && ! EMAIL_RE.test( value ) ) {
			errors.push( [ control, messageFor( control, t( 'invalidEmail', 'Please enter a valid email address.' ) ) ] );
		} else if ( 'url' === control.type && ! /^https?:\/\/\S+\.\S+/.test( value ) ) {
			errors.push( [ control, messageFor( control, t( 'invalidUrl', 'Please enter a valid URL.' ) ) ] );
		} else if ( 'number' === control.type ) {
			// Rules the builder typed into the widget's Min/Max value inputs
			// (or min/max attributes) — "age must be 18" style range checks.
			const num = Number( value );
			if ( Number.isNaN( num ) ) {
				errors.push( [ control, messageFor( control, t( 'invalidNumber', 'Please enter a number.' ) ) ] );
			} else if ( '' !== control.min && num < Number( control.min ) ) {
				errors.push( [ control, messageFor( control, t( 'numberMin', `Please enter ${ control.min } or more.` ) ) ] );
			} else if ( '' !== control.max && num > Number( control.max ) ) {
				errors.push( [ control, messageFor( control, t( 'numberMax', `Please enter ${ control.max } or less.` ) ) ] );
			}
		} else if ( 'tel' === control.type && ! TEL_RE.test( value ) ) {
			errors.push( [ control, messageFor( control, t( 'invalidTel', 'Please enter a valid phone number.' ) ) ] );
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
		if ( value instanceof File ) {
			continue; // files ride as pre-upload refs, never inline (JSON body).
		}
		if ( rawName.endsWith( '[]' ) ) {
			const name = rawName.slice( 0, -2 );
			( fields[ name ] = fields[ name ] || [] ).push( value );
		} else {
			fields[ rawName ] = value;
		}
	}

	return fields;
};

/**
 * Pre-upload every chosen file to the uploads endpoint (one request per
 * file), returning { fieldKey: [ { id, key }, … ] } to merge into the submit
 * payload. Throws a tagged error ({ uploadControl, uploadMessage }) so the
 * submit handler can paint the failure on the right field.
 */
const uploadPendingFiles = async ( form, formKey, nonce ) => {
	const refs = {};

	for ( const input of form.querySelectorAll( 'input[type="file"]' ) ) {
		const files = Array.from( input.files || [] );
		if ( ! files.length || ! input.name ) {
			continue;
		}

		const fieldKey = input.name.replace( /\[\]$/, '' );
		refs[ fieldKey ] = [];

		for ( const file of files ) {
			const body = new FormData();
			body.append( 'file', file );
			body.append( 'field_key', fieldKey );
			body.append( 'nonce', nonce );

			const response = await fetch(
				`${ config().restUrl }forms/${ encodeURIComponent( formKey ) }/uploads`,
				{ method: 'POST', cache: 'no-store', body }
			);
			const data = await response.json().catch( () => ( {} ) );

			if ( ! response.ok ) {
				const error = new Error( 'upload_failed' );
				error.uploadControl = input;
				error.uploadMessage = data.message || '';
				throw error;
			}

			refs[ fieldKey ].push( { id: data.id, key: data.key } );
		}
	}

	return refs;
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

/** The authored (styleable) status container for a tone, if the form has one. */
const messageContainer = ( form, tone ) =>
	form.querySelector(
		'success' === tone
			? '[data-element_type="e-aae-a-form-success-message"]'
			: '[data-element_type="e-aae-a-form-error-message"]'
	);

/**
 * Route a runtime message (duplicate / rate-limit / offline / timeout /
 * server error) into the editor-authored status container, so the user's
 * panel styling applies to every message the form can show. The paragraph's
 * authored text is saved and restored when the message clears. Forms built
 * before the containers existed fall back to the injected div. 'info' hints
 * (slow connection) stay in the div — transient progress, not a form state.
 */
const showRuntimeMessage = ( form, message, tone ) => {
	if ( 'info' === tone && message ) {
		const el = runtimeMessageEl( form );
		el.textContent = message;
		el.dataset.tone = 'info';
		el.hidden = false;
		return;
	}

	const container = messageContainer( form, tone );

	if ( ! container ) {
		const el = runtimeMessageEl( form );
		el.textContent = message;
		el.dataset.tone = tone || 'error';
		el.hidden = ! message;
		return;
	}

	// Routed to the authored container — silence the fallback div (it may
	// still hold a stale 'slow connection' hint from this same request).
	const legacy = form.querySelector( '.aae-form-runtime-message' );
	if ( legacy ) {
		legacy.hidden = true;
	}

	const paragraph = container.querySelector( 'p' ) || container;

	if ( message ) {
		if ( undefined === container.dataset.aaeAuthoredText ) {
			container.dataset.aaeAuthoredText = paragraph.textContent;
		}
		paragraph.textContent = message;
		container.setAttribute( 'role', 'status' );
		container.setAttribute( 'aria-live', 'polite' );
		container.style.display = 'block'; // inline beats the display:none base style
		container.scrollIntoView( { block: 'nearest', behavior: 'auto' } );
	} else {
		if ( undefined !== container.dataset.aaeAuthoredText ) {
			paragraph.textContent = container.dataset.aaeAuthoredText;
			delete container.dataset.aaeAuthoredText;
		}
		container.style.display = ''; // back to CSS control (form-state reveal)
	}
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

	// Checkbox multi-select UI over any native select[multiple]. Runs in the
	// editor preview too (it's pure UI — no submit), and stays inert on forms
	// without a multi-select, so the module is only touched when needed.
	const hasMultiSelect = !! form.querySelector( 'select[multiple]' );
	if ( hasMultiSelect ) {
		initMultiSelect( form );
	}

	// A Reset/Clear button (submit widget with button_type=reset) fires the
	// form's native `reset` event. The browser clears the real controls, but
	// our custom bits don't follow — so resync the multi-select UI, drop
	// inline errors and any success/error state after the reset applies.
	form.addEventListener( 'reset', () => {
		// Defer: on `reset` the field values haven't been cleared yet.
		setTimeout( () => {
			if ( hasMultiSelect ) {
				syncMultiSelect( form );
			}
			clearAllErrors( form );
			showRuntimeMessage( form, '', 'error' );
			form.classList.remove( 'form-state-default', 'form-state-success', 'form-state-error' );
		}, 0 );
	} );

	// Previews must never create real submissions (spec principle #9).
	if ( isEditMode() ) {
		return;
	}

	// The Field Error style-source sample is editor-only content. Its
	// visibility is runtime-owned (JS, not a stylesheet rule): the editor
	// canvas keeps it visible for styling because this init returns early
	// above, and the planned Validation Pro / Conditional Display engines can
	// drive the same element without fighting CSS. Inline display beats the
	// widget's own display:block base style; [hidden] alone would lose to it.
	const errorSource = errorSourceOf( form );
	if ( errorSource ) {
		errorSource.setAttribute( 'hidden', '' );
		errorSource.style.display = 'none';
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

			// Files first: each rides its own multipart request to the uploads
			// endpoint; the JSON submit then carries only the returned refs.
			const fileRefs = await uploadPendingFiles( form, formKey, nonce );

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
						fields: { ...collectFields( form ), ...fileRefs },
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
			if ( error && error.uploadControl ) {
				// A pre-upload was rejected (type/size/rate limit) — paint it
				// on the field; the visitor's other input is untouched.
				showFieldError( form, error.uploadControl, error.uploadMessage || t( 'uploadFailed', 'We could not upload your file. Please try again.' ) );
				focusFirstInvalid( form );
			} else if ( false === navigator.onLine ) {
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

// ---------------------------------------------------------------------------
// Dynamically-injected forms (custom AJAX, third-party popups, anything that
// bypasses Elementor's element lifecycle so the handler above never fires).
//
//  1. window.aaeInitForms( root? ) — public hook: call it after injecting
//     form markup to init every .aae-a-form under `root` (default document).
//  2. A MutationObserver auto-inits any .aae-a-form added to the DOM, so it
//     works even without the manual call. initForm's data-aaeFormReady guard
//     keeps both paths idempotent (no double-init).
// ---------------------------------------------------------------------------
const initFormsIn = ( root ) => {
	const scope = root || document;
	if ( scope.nodeType === 1 && scope.classList?.contains( 'aae-a-form' ) ) {
		initForm( scope );
	}
	scope.querySelectorAll?.( '.aae-a-form' ).forEach( initForm );
};

// Public hook.
window.aaeInitForms = initFormsIn;

// Auto-detect injected forms.
if ( typeof MutationObserver !== 'undefined' ) {
	const observer = new MutationObserver( ( mutations ) => {
		for ( const m of mutations ) {
			for ( const node of m.addedNodes ) {
				if ( node.nodeType !== 1 ) {
					continue; // element nodes only
				}
				if ( node.classList.contains( 'aae-a-form' ) ) {
					initForm( node );
				} else if ( node.querySelector?.( '.aae-a-form' ) ) {
					node.querySelectorAll( '.aae-a-form' ).forEach( initForm );
				}

				// A <select multiple> can render AFTER its form was already
				// init'd (atomic widgets render children client-side, so the
				// form shell exists — data-aae-form-ready set — before its
				// select does). initForm's guard then skips it, leaving the
				// multi-select un-enhanced. Catch a late/added select here and
				// enhance its form directly (initMultiSelect is idempotent via
				// data-aae-ms-bound). This is the editor-preview case the
				// Playwright probe caught (formReady:1 but enhanced:0).
				const late = node.matches?.( 'select[multiple]' )
					? [ node ]
					: node.querySelectorAll?.( 'select[multiple]' );
				if ( late && late.length ) {
					const seen = new Set();
					late.forEach( ( sel ) => {
						const form = sel.closest( '.aae-a-form' );
						if ( form && ! seen.has( form ) ) {
							seen.add( form );
							initMultiSelect( form );
						}
					} );
				}
			}
		}
	} );

	const start = () =>
		observer.observe( document.body, { childList: true, subtree: true } );

	if ( document.body ) {
		start();
	} else {
		document.addEventListener( 'DOMContentLoaded', start );
	}
}

// Initial scan — catches forms ALREADY in the DOM when this script loads.
// The register() handler above covers Elementor's frontend lifecycle, but in
// the editor preview atomic widgets render client-side and that callback may
// not fire (and the observer only sees *newly added* nodes) — so scan once on
// load and again on DOM ready to cover both. Idempotent via data-aaeFormReady.
initFormsIn( document );
if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', () => initFormsIn( document ) );
}
