import { register } from '@elementor/frontend-handlers';
import '../scss/toggle-switcher.scss';

const initToggleSwitcher = ( container ) => {
	const inputs       = container.querySelectorAll( 'input[type="checkbox"]' );
	const togglePanes  = container.querySelectorAll( '.toggle-pane' );
	const toggleLabels = container.querySelectorAll( '.before_label, .after_label' );

	inputs.forEach( ( input ) => {
		input.addEventListener( 'change', () => {
			togglePanes.forEach( ( pane ) => pane.classList.toggle( 'show' ) );
			toggleLabels.forEach( ( label ) => label.classList.toggle( 'active' ) );
		} );
	} );
};

register( { handler: initToggleSwitcher, widgetType: 'e-aae-a-toggle-switcher' } );
