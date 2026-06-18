import { register } from '@elementor/frontend-handlers';

/**
 * AAE Atomic Image Compare — simple version.
 *
 * All drag/touch/keyboard interaction is handled by the native
 * <input type="range"> element. JS only:
 *   1. Listens for the range "input" event.
 *   2. Moves the slider line + thumb to the value%.
 *   3. Clips the "before" image via clip-path.
 */
const initImageCompare = ( container ) => {
	if ( container.dataset.aaeCompareReady === '1' ) return;
	container.dataset.aaeCompareReady = '1';

	const range  = container.querySelector( '[data-aae-compare-range]' );
	const before = container.querySelector( '.aae-a-image-compare-before' );
	const after  = container.querySelector( '.aae-a-image-compare-after' );
	const slider = container.querySelector( '[data-aae-compare-slider]' );
	const beforeCaption = container.querySelector( '.aae-a-image-compare-caption-before' );
	const afterCaption = container.querySelector( '.aae-a-image-compare-caption-after' );

	if ( ! range ) return;

	const setStyles = ( element, styles ) => {
		if ( ! element ) return;
		Object.entries( styles ).forEach( ( [ property, value ] ) => {
			element.style[ property ] = value;
		} );
	};

	setStyles( container, {
		position: 'relative',
		overflow: 'hidden',
		width: '100%',
		maxWidth: '100%',
		display: 'grid',
	} );

	setStyles( after, {
		gridArea: '1 / 1',
		position: 'relative',
		zIndex: '1',
		width: '100%',
		maxWidth: '100%',
		height: 'auto',
		display: 'block',
		objectFit: 'cover',
		objectPosition: '0 50%',
		margin: '0',
	} );

	setStyles( before, {
		gridArea: '1 / 1',
		position: 'absolute',
		top: '0',
		left: '0',
		zIndex: '2',
		width: '100%',
		maxWidth: '100%',
		height: '100%',
		display: 'block',
		objectFit: 'cover',
		objectPosition: '0 50%',
		margin: '0',
	} );

	setStyles( slider, {
		position: 'absolute',
		top: '0',
		height: '100%',
	} );

	setStyles( beforeCaption, {
		position: 'absolute',
		top: '16px',
		left: '16px',
		zIndex: '12',
		margin: '0',
		pointerEvents: 'none',
		lineHeight: '1',
	} );

	setStyles( afterCaption, {
		position: 'absolute',
		top: '16px',
		right: '16px',
		zIndex: '12',
		margin: '0',
		pointerEvents: 'none',
		lineHeight: '1',
		textAlign: 'right',
	} );

	const getDefaultPosition = () => {
		const fallback = Number( container.dataset.defaultPosition );
		return Number.isFinite( fallback ) ? fallback : 50;
	};

	/* Initial state */
	const update = () => {
		const rawValue = range.value === '' ? getDefaultPosition() : Number( range.value );
		const value = Math.min( 100, Math.max( 0, Number.isFinite( rawValue ) ? rawValue : getDefaultPosition() ) );
		range.value = value;
		container.style.setProperty( '--aae-image-compare-position', value + '%' );

		if ( before ) {
			before.style.clipPath = `inset(0 ${ 100 - value }% 0 0)`;
			before.style.webkitClipPath = `inset(0 ${ 100 - value }% 0 0)`;
		}

		if ( beforeCaption ) {
			beforeCaption.style.clipPath = `inset(0 ${ 100 - value }% 0 0)`;
			beforeCaption.style.webkitClipPath = `inset(0 ${ 100 - value }% 0 0)`;
		}

		if ( slider ) {
			slider.style.left = value + '%';
		}

		container.dataset.beforeLabelHidden = value < 12 ? 'true' : 'false';
		container.dataset.afterLabelHidden = value > 88 ? 'true' : 'false';
	};

	range.addEventListener( 'input', update );

	/* Run once on load */
	update();
};

register( {
	elementType: 'e-aae-a-image-compare',
	id: 'e-aae-a-image-compare-handler',
	callback: ( { element } ) => {
		element.removeAttribute( 'data-aae-compare-ready' );
		initImageCompare( element );
	},
} );
