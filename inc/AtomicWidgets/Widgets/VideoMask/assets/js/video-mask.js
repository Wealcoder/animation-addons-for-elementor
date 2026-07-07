const { register } = window.elementorV2?.frontendHandlers || window.elementorFrontend?.elementsHandler || {};
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
// mask-position is relative to the wrapper element's own box, so we compute
// the button centre against wrapperRect (not containerRect) for accuracy.
const syncMaskToBtn = ( container ) => {
	const btn     = container.querySelector( '[data-element_type="e-aae-a-video-mask-btn"]' );
	const wrapper = container.querySelector( '.vm-video-wrapper' );
	if ( ! btn || ! wrapper ) return;

	const wrapperRect = wrapper.getBoundingClientRect();
	const btnRect     = btn.getBoundingClientRect();

	// Button centre expressed in the wrapper's own coordinate space.
	const cx = btnRect.left - wrapperRect.left + btnRect.width  / 2;
	const cy = btnRect.top  - wrapperRect.top  + btnRect.height / 2;

	// Read current mask-size so position stays correct even if the user changes it.
	const cs          = getComputedStyle( wrapper );
	const maskSizeRaw = cs.getPropertyValue( 'mask-size' )
	                 || cs.getPropertyValue( '-webkit-mask-size' )
	                 || '200px';
	const parts = maskSizeRaw.trim().split( /\s+/ );
	const mw    = parseFloat( parts[ 0 ] ) || 200;
	const mh    = parseFloat( parts.length > 1 ? parts[ 1 ] : parts[ 0 ] ) || mw;

	wrapper.style.webkitMaskPosition = `${ cx - mw / 2 }px ${ cy - mh / 2 }px`;
	wrapper.style.maskPosition       = `${ cx - mw / 2 }px ${ cy - mh / 2 }px`;
};

const initVideoMask = ( container, signal ) => {
	// The click-trigger is the inner AAE_A_Video_Mask_Btn atomic element.
	// Using data-element_type to find it is robust against class-name changes.
	const btn   = container.querySelector( '[data-element_type="e-aae-a-video-mask-btn"]' );
	const video = container.querySelector( 'video' );
	if ( ! btn || ! video ) return;

	applyMaskShape( container );

	const opts   = signal ? { signal } : {};
	const doSync = () => syncMaskToBtn( container );

	// Defer the initial sync two frames: the first frame lets the browser apply
	// all stylesheets; the second lets it finish layout (incl. video dimensions).
	// This is needed on both frontend (video height unknown at parse time) and
	// editor (Elementor applies user styles after mount).
	requestAnimationFrame( () => requestAnimationFrame( doSync ) );

	// Safety net: re-sync once the video knows its natural size.
	video.addEventListener( 'loadedmetadata', doSync, { once: true, ...opts } );

	// Re-sync when the viewport resizes (button position may shift).
	window.addEventListener( 'resize', doSync, opts );

	// In the editor, re-sync whenever Elementor writes a new style rule
	// (fires each time the user moves or resizes the button in the Style panel).
	if ( window.elementorFrontend?.isEditMode?.() ) {
		const observer = new MutationObserver( () => requestAnimationFrame( doSync ) );
		observer.observe( document.head, { childList: true, subtree: true, characterData: true } );
		if ( signal ) signal.addEventListener( 'abort', () => observer.disconnect() );
	}

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