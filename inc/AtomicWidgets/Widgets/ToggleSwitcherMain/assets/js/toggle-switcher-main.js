import { register } from '@elementor/frontend-handlers';
import '../scss/toggle-switcher-main.scss';

// Persists toggle state across editor re-initializations, keyed by element data-id.
const toggleState = new Map();

const initToggleSwitcher = ( container, signal ) => {
	const input       = container.querySelector( 'input[type="checkbox"]' );
	const beforeLabel = container.querySelector( '.before_label' );
	const afterLabel  = container.querySelector( '.after_label' );
	const switchLabel = container.querySelector( 'label.switcher' );

	if ( ! input ) return;

	const elementId = container.dataset.id || '';

	// Re-queries panes on every call so the function works regardless of when
	// child panes appear in the DOM (editor renders children asynchronously).
	const applyState = ( checked ) => {
		if ( elementId ) toggleState.set( elementId, checked );
		input.checked = checked;
		const panes  = container.querySelectorAll( '.aae-a-toggle-pane-main' );
		const labels = container.querySelectorAll( '.before_label, .after_label' );
		panes.forEach( ( pane, i ) => {
			const show = checked ? i === 1 : i === 0;
			pane.classList.toggle( 'show', show );
			// Use inline !important so Elementor's editor CSS (display:flex on
			// .e-con containers) cannot override the hidden state.
			if ( show ) {
				pane.style.removeProperty( 'display' );
			} else {
				pane.style.setProperty( 'display', 'none', 'important' );
			}
		} );
		labels.forEach( ( label, i ) => label.classList.toggle( 'active', checked ? i === 1 : i === 0 ) );
	};

	// Capture phase fires before any ancestor/Elementor click handler that may
	// call preventDefault() and break the native label→checkbox link.
	// e.preventDefault() here stops the browser from also toggling the checkbox
	// via the `for` attribute, preventing a double-toggle.
	const captureOpts = signal ? { signal, capture: true } : { capture: true };

	beforeLabel?.addEventListener( 'click', ( e ) => { e.preventDefault(); applyState( false ); }, captureOpts );
	afterLabel?.addEventListener(  'click', ( e ) => { e.preventDefault(); applyState( true );  }, captureOpts );
	switchLabel?.addEventListener( 'click', ( e ) => { e.preventDefault(); applyState( ! input.checked ); }, captureOpts );

	// Show the correct pane on init. Restores the last-known state so that
	// editor re-initializations (on settings change, etc.) don't reset the
	// visible pane back to the first one.
	const syncInitialState = () => {
		const panes = container.querySelectorAll( '.aae-a-toggle-pane-main' );
		if ( ! panes.length ) return false;
		const saved = elementId ? ( toggleState.get( elementId ) ?? false ) : false;
		applyState( saved );
		return true;
	};

	if ( ! syncInitialState() ) {
		const observer = new MutationObserver( () => {
			if ( syncInitialState() ) observer.disconnect();
		} );
		observer.observe( container, { childList: true, subtree: true } );
		if ( signal ) {
			signal.addEventListener( 'abort', () => observer.disconnect(), { once: true } );
		}
	}
};

register( {
	elementType: 'e-aae-a-toggle-switcher-main',
	id: 'aae-a-toggle-switcher-main-handler',
	callback: ( { element, signal } ) => {
		const container = element.classList.contains( 'aae-a-toggle-switcher-main' )
			? element
			: element.querySelector( '.aae-a-toggle-switcher-main' );
		if ( container ) initToggleSwitcher( container, signal );
	},
} );
