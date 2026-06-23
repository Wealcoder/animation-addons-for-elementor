import { register } from '@elementor/frontend-handlers';
import '../scss/video-mask.scss';

// Resolve Elementor's assets base URL so the mask-shape SVG path can be built
// without hard-coding an absolute URL (which varies per installation).
const getElementorAssetsUrl = () =>
	window.elementorFrontend?.config?.urls?.assets ??
	window.elementorCommon?.config?.urls?.assets ??
	'';

// Set only the dynamic mask-image URL via inline style.
// mask-size / mask-position / mask-repeat are owned by video-mask.scss so
// they remain overridable from the Elementor Style panel.
const applyMaskShape = ( container ) => {
	const wrapper = container.querySelector( '.vm-video-wrapper' );
	if ( ! wrapper ) return;

	const shape   = wrapper.dataset.shape || 'circle';
	const baseUrl = getElementorAssetsUrl();
	if ( ! baseUrl ) return;

	const url = `${ baseUrl }mask-shapes/${ shape }.svg`;

	wrapper.style.webkitMaskImage = `url(${ url })`;
	wrapper.style.maskImage       = `url(${ url })`;
};

const initVideoMask = ( container, signal ) => {
	// The click-trigger is the inner AAE_A_Video_Mask_Btn atomic element.
	// Using data-element_type to find it is robust against class-name changes.
	const btn   = container.querySelector( '[data-element_type="e-aae-a-video-mask-btn"]' );
	const video = container.querySelector( 'video' );
	if ( ! btn || ! video ) return;

	// Idle state: apply the shape mask — circle appears centered over the button.
	applyMaskShape( container );

	const opts = signal ? { signal } : {};

	btn.addEventListener( 'click', () => {
		const isOpen = container.classList.toggle( 'mask-open' );

		if ( isOpen ) {
			// CSS removes mask via .mask-open .vm-video-wrapper { mask-image: none !important }
			// → video expands to its full rectangular (tetragon) area.
			video.play().catch( () => {} );
		} else {
			// Removing .mask-open lets the JS inline mask-image take effect again
			// → restores the idle circle shape.
			video.pause();
			video.currentTime = 0;
		}
	}, opts );

	// When a non-looping video ends, restore the idle masked state automatically.
	video.addEventListener( 'ended', () => {
		container.classList.remove( 'mask-open' );
	}, opts );
};

register( {
	elementType: 'e-aae-a-video-mask',
	id: 'aae-a-video-mask-handler',
	callback: ( { element, signal } ) => {
		const container = element.classList.contains( 'aae-a-video-mask' )
			? element
			: element.querySelector( '.aae-a-video-mask' );
		if ( container ) initVideoMask( container, signal );
	},
} );
