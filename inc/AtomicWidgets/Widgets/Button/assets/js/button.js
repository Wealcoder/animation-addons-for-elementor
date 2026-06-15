import { register } from '@elementor/frontend-handlers';
import "../scss/button.scss";

// Style-4 only: track the cursor position so the ripple span originates at the click point.
const initButton = ( container ) => {
	const rippleBtn = container.querySelector( '.btn-hover' );
	if ( ! rippleBtn ) return;

	const moveRipple = ( e ) => {
		const span = rippleBtn.querySelector( 'span:first-child' );
		if ( ! span ) return;
		const rect  = rippleBtn.getBoundingClientRect();
		span.style.left = ( e.clientX - rect.left ) + 'px';
		span.style.top  = ( e.clientY - rect.top  ) + 'px';
	};

	rippleBtn.addEventListener( 'mouseenter', moveRipple );
	rippleBtn.addEventListener( 'mouseleave', moveRipple );
};

register( { handler: initButton, widgetType: 'e-aae-atomic-button' } );
