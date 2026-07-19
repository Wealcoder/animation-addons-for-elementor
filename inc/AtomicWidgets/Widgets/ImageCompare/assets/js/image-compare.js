import '../scss/image-compare.scss';

const { register } = window.elementorV2?.frontendHandlers || window.elementorFrontend?.elementsHandler || {};

/**
 * AAE Atomic Image Compare (wrapper) — frontend handler.
 *
 * Identical behavior to Image Compare Main's handler — see
 * Widgets/ImageCompareMain/assets/js/image-compare-main.js for the full
 * rationale comments on the ancestor-overlay suppression and the
 * non-draggable-root workaround. Kept as a separate bundle (not a shared
 * import) so this widget's on-demand script stays fully independent of the
 * Main widget's, matching how Btn/BtnPro each ship their own bundle.
 */
const CLICK_VS_DRAG_THRESHOLD_PX = 4;

const suppressAncestorOverlays = ( container ) => {
	const restores = [];
	let node = container.parentElement;

	while ( node ) {
		const overlay = node.querySelector( ':scope > .elementor-element-overlay' );
		if ( overlay ) {
			restores.push( [ overlay, overlay.style.getPropertyValue( 'pointer-events' ), overlay.style.getPropertyPriority( 'pointer-events' ) ] );
			overlay.style.setProperty( 'pointer-events', 'none', 'important' );
		}
		node = node.parentElement;
	}

	return () => {
		restores.forEach( ( [ overlay, value, priority ] ) => {
			if ( value ) {
				overlay.style.setProperty( 'pointer-events', value, priority );
			} else {
				overlay.style.removeProperty( 'pointer-events' );
			}
		} );
	};
};

const keepContainerNonDraggable = ( container ) => {
	const enforce = () => {
		if ( container.getAttribute( 'draggable' ) !== 'false' ) {
			container.setAttribute( 'draggable', 'false' );
		}
	};
	enforce();
	new MutationObserver( enforce ).observe( container, { attributes: true, attributeFilter: [ 'draggable' ] } );
};

const initImageCompare = ( container ) => {
	if ( container.dataset.aaeCompareReady === '1' ) return;
	container.dataset.aaeCompareReady = '1';

	keepContainerNonDraggable( container );

	const range = container.querySelector( '[data-aae-compare-range]' );

	if ( ! range ) return;

	const isVertical = () => container.dataset.direction === 'vertical';

	/**
	 * Direction is fixed for the element's lifetime — cursor, writing-mode,
	 * and user-select are static per-instance values with no schema/preset
	 * home (no Elementor element to attach them to, or unsupported style
	 * keys), so they're set once here instead of via a `[data-direction]`
	 * CSS selector. The live drag position stays a CSS custom property
	 * (see update() below) — unlike these, it has to reflect each
	 * instance's own "Initial Position" setting from first paint, which
	 * only a server-rendered CSS var can guarantee.
	 */
	const vertical  = isVertical();
	const thumb     = container.querySelector( '.aae-a-image-compare-thumb' );
	const beforeLbl = container.querySelector( '.aae-a-image-compare-caption-before' );
	const afterLbl  = container.querySelector( '.aae-a-image-compare-caption-after' );

	container.style.userSelect = 'none';
	range.style.cursor = vertical ? 'ns-resize' : 'ew-resize';
	if ( vertical ) range.style.writingMode = 'vertical-lr';
	if ( thumb ) thumb.style.cursor = vertical ? 'ns-resize' : 'ew-resize';

	const getDefaultPosition = () => {
		const fallback = Number( container.dataset.defaultPosition );
		return Number.isFinite( fallback ) ? fallback : 50;
	};

	const update = () => {
		const rawValue = range.value === '' ? getDefaultPosition() : Number( range.value );
		const value    = Math.min( 100, Math.max( 0, Number.isFinite( rawValue ) ? rawValue : getDefaultPosition() ) );

		range.value = value;
		container.style.setProperty( '--aae-image-compare-position', value + '%' );

		if ( beforeLbl ) beforeLbl.style.opacity = value < 12 ? '0' : '1';
		if ( afterLbl )  afterLbl.style.opacity  = value > 88 ? '0' : '1';
	};

	range.addEventListener( 'input', update );

	let pointerStartCoord = null;
	let pointerStartValue = null;
	let pointerMoved      = false;
	let restoreOverlays   = null;

	const getAxisCoord = ( event ) => isVertical() ? event.clientY : event.clientX;

	range.addEventListener( 'pointerdown', ( event ) => {
		pointerStartCoord = getAxisCoord( event );
		pointerStartValue = range.value;
		pointerMoved      = false;
		restoreOverlays?.();
		restoreOverlays   = suppressAncestorOverlays( container );
	} );

	range.addEventListener( 'pointermove', ( event ) => {
		if ( pointerStartCoord === null ) return;
		if ( Math.abs( getAxisCoord( event ) - pointerStartCoord ) > CLICK_VS_DRAG_THRESHOLD_PX ) {
			pointerMoved = true;
		}
	} );

	const pointerEnd = () => {
		restoreOverlays?.();
		restoreOverlays = null;

		if ( pointerStartCoord === null ) return;

		const clickMoveDisabled = container.dataset.enableClickMove === 'no';
		if ( clickMoveDisabled && ! pointerMoved ) {
			range.value = pointerStartValue;
			update();
		}

		pointerStartCoord = null;
		pointerStartValue = null;
		pointerMoved      = false;
	};

	range.addEventListener( 'pointerup', pointerEnd );
	range.addEventListener( 'pointercancel', pointerEnd );

	const swallow = ( event ) => event.stopPropagation();
	[ 'pointerdown', 'mousedown', 'touchstart', 'dragstart' ].forEach( ( eventName ) => {
		range.addEventListener( eventName, swallow );
	} );

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
