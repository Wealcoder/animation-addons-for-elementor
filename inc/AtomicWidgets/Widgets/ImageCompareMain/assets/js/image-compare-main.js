const { register } = window.elementorV2?.frontendHandlers || window.elementorFrontend?.elementsHandler || {};

/**
 * AAE Atomic Image Compare — frontend handler.
 *
 * The native <input type="range"> handles drag + touch + keyboard for free
 * in both axes (writing-mode: vertical-lr rotates the control for vertical
 * mode without changing its value semantics — top = min, bottom = max).
 *
 * This handler only:
 *   1. Mirrors the range value into the `--aae-image-compare-main-position`
 *      CSS variable. Everything visual — divider/thumb position AND the
 *      clip-path direction on the Before image + Before label — reads
 *      that variable through CSS rules scoped by `[data-direction]`, so
 *      this handler never has to know which axis is active.
 *   2. Toggles `data-{before,after}-label-hidden` when the handle reaches
 *      the edges so labels fade out gracefully.
 *   3. Suppresses track-clicks when `data-enable-click-move="no"` (drag
 *      only — clicks revert to the pre-click value), using the right
 *      axis for the drag-distance check.
 *   4. Stops pointerdown from bubbling so the v4 editor's element-drag
 *      layer doesn't intercept the slider drag inside the editor.
 *   5. While a drag is in progress, forces pointer-events:none on every
 *      ANCESTOR container's own `.elementor-element-overlay` (see
 *      suppressAncestorOverlays below) — required only when this widget
 *      is nested inside another container (Flexbox/Div-block/etc).
 */
const CLICK_VS_DRAG_THRESHOLD_PX = 4;

/**
 * Elementor gives every `.elementor-element-overlay` a global
 * `z-index: 9998`. Our widget's own root div has no z-index (auto), so when
 * this widget is nested inside another container, that PARENT container's
 * own overlay — a sibling of our widget, not a descendant — wins the
 * stacking comparison outright and sits above our entire widget regardless
 * of any z-index set *inside* our own widget (thumb/divider/range). When the
 * v4 editor's "child-selection" layer re-enables that ancestor overlay's
 * pointer-events (to let hover/select reach nested elements), it becomes the
 * real hit target for the whole area our widget occupies, and dragging on it
 * moves the ANCESTOR instead of our slider. Placed directly on the page
 * root, there's no such sibling overlay in the way, which is why the bug
 * only reproduces when nested.
 *
 * Fighting this with a bigger z-index on our own root would mean out-ranking
 * 9998 at every possible nesting depth — fragile. Instead, for the duration
 * of an actual drag gesture, force pointer-events:none on every ancestor's
 * own overlay div and restore it on pointerup/cancel.
 */
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

/**
 * The v4 editor marks a container's root element `draggable="true"` (native
 * HTML5 drag) so the CANVAS can reposition it among its siblings — but only
 * when that element actually HAS siblings to reorder against. A widget that
 * is the page's sole/top-level element gets no such siblings, so the editor
 * never marks it draggable and the invisible range works fine. The same
 * widget nested inside a Flexbox/Div-block DOES get draggable="true" on its
 * own root — and since that root is the NEAREST ancestor-or-self match, a
 * mousedown+drag gesture anywhere inside it (including on our invisible
 * range, which never declares its own draggable state) is resolved by the
 * browser as "drag this root", not "drag the range's value" — moving the
 * whole widget instead of the divider. This is set via direct DOM attribute
 * mutation by the editor's own render pipeline, so a static `draggable`
 * attribute in the Twig template gets overwritten right after insertion —
 * only a MutationObserver forcing it back to "false" survives.
 *
 * There is no safe "empty" area on this widget to leave draggable instead:
 * the invisible range covers its entire body. Losing canvas drag-to-reorder
 * for this widget is an accepted trade-off — reorder it via the Navigator/
 * Structure panel instead.
 */
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

	const getDefaultPosition = () => {
		const fallback = Number( container.dataset.defaultPosition );
		return Number.isFinite( fallback ) ? fallback : 50;
	};

	const update = () => {
		const rawValue = range.value === '' ? getDefaultPosition() : Number( range.value );
		const value    = Math.min( 100, Math.max( 0, Number.isFinite( rawValue ) ? rawValue : getDefaultPosition() ) );

		range.value = value;
		container.style.setProperty( '--aae-image-compare-main-position', value + '%' );

		container.dataset.beforeLabelHidden = value < 12 ? 'true' : 'false';
		container.dataset.afterLabelHidden  = value > 88 ? 'true' : 'false';
	};

	range.addEventListener( 'input', update );

	/* Click-to-move suppression. Native range fires `input` on both drag
	   AND click — distinguish by tracking pointer movement on the relevant
	   axis (X for horizontal, Y for vertical) between pointerdown and
	   pointerup. Movement under the threshold → click → revert. */
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

	/* Editor preview: the v4 editor wraps every element with a drag layer
	   that captures pointerdown to move the widget around the canvas. That
	   layer eats the range input's drag, so the user ends up moving the
	   whole widget instead of the slider. Stop pointer events from
	   bubbling up — the range input still receives them, so drag / touch /
	   keyboard interaction stays intact. */
	const swallow = ( event ) => event.stopPropagation();
	[ 'pointerdown', 'mousedown', 'touchstart', 'dragstart' ].forEach( ( eventName ) => {
		range.addEventListener( eventName, swallow );
	} );

	/* Run once on load. */
	update();
};

register( {
	elementType: 'e-aae-a-image-compare-main',
	id: 'e-aae-a-image-compare-main-handler',
	callback: ( { element } ) => {
		element.removeAttribute( 'data-aae-compare-ready' );
		initImageCompare( element );
	},
} );
