/**
 * AAE Form — checkbox multi-select enhancement.
 *
 * Progressive enhancement over a native <select multiple>: the real element
 * stays in the DOM (visually hidden) so submit / validation / sanitize are
 * unchanged — this only adds a friendlier UI on top. A button opens a panel
 * of checkbox options; toggling a checkbox mirrors the change onto the real
 * <option>.selected and fires a `change` event so the form runtime and any
 * validation see it exactly as a native pick.
 *
 * Loaded via a STATIC import from form.js but INVOKED only when a form
 * actually contains a `select[multiple]` (see initForm) — so it's inert on
 * forms that don't use it (runtime-lazy).
 *
 * No framework, no GSAP (MVP form constraint). Styling lives in the form
 * stylesheet, scoped to `.aae-ms-*`, inheriting the native select's look.
 */

const OPEN_CLASS = 'aae-ms-open';

/** Build the label shown on the trigger button from current selection. */
function triggerLabel( select, placeholder ) {
	const picked = Array.from( select.selectedOptions );
	if ( ! picked.length ) {
		return placeholder;
	}
	if ( picked.length <= 2 ) {
		return picked.map( ( o ) => o.textContent.trim() ).join( ', ' );
	}
	return `${ picked.length } selected`;
}

/** Enhance one <select multiple>. Idempotent (guards on a bound flag). */
function enhance( select ) {
	if ( ! select || select.dataset.aaeMsBound === 'true' ) {
		return;
	}
	select.dataset.aaeMsBound = 'true';

	const doc = select.ownerDocument;
	const placeholder = select.getAttribute( 'data-placeholder' ) || 'Select…';

	// Visually hide the native control but keep it submittable + focusable
	// for a11y fallback. Not display:none (some browsers skip its value then).
	select.classList.add( 'aae-ms-native' );

	const wrap = doc.createElement( 'div' );
	wrap.className = 'aae-ms';

	const button = doc.createElement( 'button' );
	button.type = 'button';
	button.className = 'aae-ms-trigger';
	button.setAttribute( 'aria-haspopup', 'listbox' );
	button.setAttribute( 'aria-expanded', 'false' );

	const panel = doc.createElement( 'div' );
	panel.className = 'aae-ms-panel';
	panel.setAttribute( 'role', 'listbox' );
	panel.setAttribute( 'aria-multiselectable', 'true' );

	const refreshLabel = () => {
		button.textContent = triggerLabel( select, placeholder );
	};

	// One clickable text row per real <option> — NO checkbox element, so the
	// row is plain text that inherits the element's Style-panel text colour;
	// selection shows via the .aae-ms-selected class (a checkmark + tint),
	// not a native checkbox.
	Array.from( select.options ).forEach( ( option, index ) => {
		const row = doc.createElement( 'div' );
		row.className = 'aae-ms-option';
		row.setAttribute( 'role', 'option' );
		row.dataset.aaeMsIndex = String( index );
		row.textContent = option.textContent;

		const paint = () => {
			row.classList.toggle( 'aae-ms-selected', option.selected );
			row.setAttribute( 'aria-selected', option.selected ? 'true' : 'false' );
		};

		row.addEventListener( 'click', () => {
			option.selected = ! option.selected;
			paint();
			// Let the form runtime / validation react as to a native change.
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			refreshLabel();
		} );

		paint();
		panel.appendChild( row );
	} );

	const close = () => {
		wrap.classList.remove( OPEN_CLASS );
		button.setAttribute( 'aria-expanded', 'false' );
	};
	const open = () => {
		wrap.classList.add( OPEN_CLASS );
		button.setAttribute( 'aria-expanded', 'true' );
	};
	const toggle = () => ( wrap.classList.contains( OPEN_CLASS ) ? close() : open() );

	button.addEventListener( 'click', toggle );

	// Outside click / Escape close.
	doc.addEventListener( 'click', ( e ) => {
		if ( ! wrap.contains( e.target ) ) {
			close();
		}
	} );
	wrap.addEventListener( 'keydown', ( e ) => {
		if ( 'Escape' === e.key ) {
			close();
			button.focus();
		}
	} );

	// Insert the widget right after the native select, then move the select
	// inside the wrap so a11y focus order and form ownership stay intact.
	select.parentNode.insertBefore( wrap, select );
	wrap.appendChild( select );
	wrap.appendChild( button );
	wrap.appendChild( panel );

	// Mirror the native <select>'s Style-panel colours onto the wrap so the
	// trigger/panel (which are NOT the styled element) pick them up: text via
	// `color: inherit`, background via the `--aae-ms-bg` custom prop. Only a
	// non-transparent background is copied (else keep the CSS #fff fallback).
	const applyStyle = () => {
		const cs = doc.defaultView.getComputedStyle( select );
		if ( cs.color ) {
			wrap.style.color = cs.color;
		}
		const bg = cs.backgroundColor;
		if ( bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent' ) {
			wrap.style.setProperty( '--aae-ms-bg', bg );
		}
	};
	applyStyle();

	refreshLabel();
}

/** Enhance every native multi-select inside a form. */
export function initMultiSelect( form ) {
	form.querySelectorAll( 'select[multiple]' ).forEach( enhance );
}

/**
 * Re-sync every enhanced multi-select's option rows + trigger label from the
 * real <select> state. Call after a native form reset (which clears the real
 * <select> but not our custom panel) so the UI matches again.
 */
export function syncMultiSelect( form ) {
	form.querySelectorAll( 'select[multiple].aae-ms-native' ).forEach( ( select ) => {
		const wrap = select.closest( '.aae-ms' );
		if ( ! wrap ) {
			return;
		}
		wrap.querySelectorAll( '.aae-ms-option' ).forEach( ( row ) => {
			const option = select.options[ Number( row.dataset.aaeMsIndex ) ];
			const on = !! option?.selected;
			row.classList.toggle( 'aae-ms-selected', on );
			row.setAttribute( 'aria-selected', on ? 'true' : 'false' );
		} );
		const button = wrap.querySelector( '.aae-ms-trigger' );
		const placeholder = select.getAttribute( 'data-placeholder' ) || 'Select…';
		if ( button ) {
			button.textContent = triggerLabel( select, placeholder );
		}
	} );
}
