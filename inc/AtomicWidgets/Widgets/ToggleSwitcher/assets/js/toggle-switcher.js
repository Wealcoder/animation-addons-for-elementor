import { register } from '@elementor/frontend-handlers';
import '../scss/toggle-switcher.scss';

const initToggleSwitcher = ( container ) => {
	const input    = container.querySelector( 'input[type="checkbox"]' );
	const panes    = container.querySelectorAll( '.aae-a-toggle-pane' );
	const labels   = container.querySelectorAll( '.before_label, .after_label' );

	if ( ! input || panes.length < 2 ) return;

	// Show first pane by default.
	panes[ 0 ].classList.add( 'show' );

	input.addEventListener( 'change', () => {
		panes.forEach( ( pane ) => pane.classList.toggle( 'show' ) );
		labels.forEach( ( label ) => label.classList.toggle( 'active' ) );
	} );
};

register( {
	elementType: 'e-aae-a-toggle-switcher',
	id: 'aae-a-toggle-switcher-handler',
	callback: ( { element } ) => {
		const container = element.classList.contains( 'aae-a-toggle-switcher' )
			? element
			: element.querySelector( '.aae-a-toggle-switcher' );
		if ( container ) initToggleSwitcher( container );
	},
} );
