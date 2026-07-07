const { register } = window.elementorV2?.frontendHandlers || window.elementorFrontend?.elementsHandler || {};
import '../scss/flip-box.scss';

register( {
	elementType: 'e-aae-a-flip-box',
	id: 'aae-a-flip-box-handler',
	// The flip animation is entirely CSS-driven. This handler exists so
	// Elementor's frontend-handlers pipeline attaches to the element and
	// the SCSS bundle is included in the build.
	callback: () => {},
} );
