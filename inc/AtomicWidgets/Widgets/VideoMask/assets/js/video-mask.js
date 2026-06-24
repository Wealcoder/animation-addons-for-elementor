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

// Center the mask on the button — no matter where the button is placed.
// mask-position is the top-left corner of the mask image, so we offset by
// half the mask dimensions to land its centre exactly on the button's centre.
const syncMaskToBtn = ( container ) => {
	const btn     = container.querySelector( '[data-element_type="e-aae-a-video-mask-btn"]' );
	const wrapper = container.querySelector( '.vm-video-wrapper' );
	if ( ! btn || ! wrapper ) return;

	const containerRect = container.getBoundingClientRect();
	const btnRect       = btn.getBoundingClientRect();

	const cx = btnRect.left - containerRect.left + btnRect.width  / 2;
	const cy = btnRect.top  - containerRect.top  + btnRect.height / 2;

	// Read current mask-size so position stays correct even if the user changes it.
	const cs          = getComputedStyle( wrapper );
	const maskSizeRaw = cs.getPropertyValue( 'mask-size' )
	                 || cs.getPropertyValue( '-webkit-mask-size' )
	                 || '200px';
	const parts = maskSizeRaw.trim().split( /\s+/ );
	const mw    = parseFloat( parts[ 0 ] ) || 200;
	const mh    = parseFloat( parts.length > 1 ? parts[ 1 ] : parts[ 0 ] ) || mw;

	// Clamp so the full mask shape is never cropped by the container edge.
	const maxX = Math.max( 0, containerRect.width  - mw );
	const maxY = Math.max( 0, containerRect.height - mh );
	const posX = Math.max( 0, Math.min( cx - mw / 2, maxX ) );
	const posY = Math.max( 0, Math.min( cy - mh / 2, maxY ) );

	wrapper.style.webkitMaskPosition = `${ posX }px ${ posY }px`;
	wrapper.style.maskPosition       = `${ posX }px ${ posY }px`;
};

const initVideoMask = ( container, signal ) => {
	// The click-trigger is the inner AAE_A_Video_Mask_Btn atomic element.
	// Using data-element_type to find it is robust against class-name changes.
	const btn   = container.querySelector( '[data-element_type="e-aae-a-video-mask-btn"]' );
	const video = container.querySelector( 'video' );
	if ( ! btn || ! video ) return;

	// Apply shape and align mask to the button's initial position.
	applyMaskShape( container );
	syncMaskToBtn( container );

	const opts = signal ? { signal } : {};

	btn.addEventListener( 'click', () => {
		const isOpen = container.classList.toggle( 'mask-open' );

		if ( isOpen ) {
			// CSS removes mask via .mask-open .vm-video-wrapper { mask-image: none !important }
			// → video expands to its full rectangular (tetragon) area.
			video.play().catch( () => {} );
		}
		// On minimize: just restore the mask shape — video keeps playing in the background.
	}, opts );

	// When a non-looping video ends, restore the idle masked state automatically.
	video.addEventListener( 'ended', () => {
		container.classList.remove( 'mask-open' );
	}, opts );

	// Re-sync mask position if the layout shifts (e.g. window resize).
	window.addEventListener( 'resize', () => syncMaskToBtn( container ), opts );
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
