const { register } = window.elementorV2?.frontendHandlers || window.elementorFrontend?.elementsHandler || {};

const POSITION_TRANSFORMS = {
	left:   'translateX(-100%)',
	right:  'translateX(100%)',
	top:    'translateY(-100%)',
	bottom: 'translateY(100%)',
};

const POSITION_STYLES = {
	left:   { left: '0', top: '0', bottom: '0', right: '' },
	right:  { right: '0', top: '0', bottom: '0', left: '' },
	top:    { top: '0', left: '0', right: '0', width: '100%', maxWidth: 'none', height: 'auto', bottom: '', minHeight: '220px', maxHeight: '90vh' },
	bottom: { bottom: '0', left: '0', right: '0', width: '100%', maxWidth: 'none', height: 'auto', top: 'auto', minHeight: '220px', maxHeight: '90vh' },
};

const getPanelShellSize = ( panel, position ) => {
	const computed = window.getComputedStyle( panel );
	const widthValue = Number.parseFloat( computed.width );
	const heightValue = Number.parseFloat( computed.height );
	const width = computed.width && computed.width !== 'auto' && widthValue > 0 ? computed.width : '320px';
	const maxWidth = computed.maxWidth && computed.maxWidth !== 'none' ? computed.maxWidth : '90vw';
	const height = computed.height && computed.height !== 'auto' && heightValue > 0 ? computed.height : '100vh';

	if ( position === 'top' || position === 'bottom' ) {
		return {};
	}

	return {
		width,
		maxWidth,
		height,
	};
};

const initOffcanvas = ( container ) => {
	if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
		return;
	}

	const trigger  = container.querySelector( '.aae-offcanvas-trigger' );
	const overlay  = container.querySelector( '.aae-offcanvas-overlay' );
	const shell    = container.querySelector( '.aae-offcanvas-shell' );
	const panel    = container.querySelector( '.aae-a-offcanvas-panel' );
	const closeBtn = panel ? panel.querySelector( '.aae-offcanvas-close' ) : null;

	if ( ! trigger || ! shell || ! panel ) return;

	const position      = container.dataset.position || 'left';
	const closedTransform = POSITION_TRANSFORMS[ position ] || 'translateX(-100%)';
	const posStyles     = POSITION_STYLES[ position ] || POSITION_STYLES.left;
	const panelSize     = getPanelShellSize( panel, position );
	let closeTimer;

	// Disable transition during init so the initial off-screen placement is instant (no flash)
	shell.style.transition = 'none';

	Object.assign( shell.style, panelSize, posStyles );
	shell.style.transform = closedTransform;

	// Move the pre-rendered shell to <body> so fixed positioning uses the viewport.
	document.body.appendChild( shell );
	if ( overlay ) document.body.appendChild( overlay );

	// Re-enable transition on the next frame so open/close animate normally
	requestAnimationFrame( () => {
		shell.style.transition = '';
	} );

	const open = () => {
		window.clearTimeout( closeTimer );
		// Show before animating in so the slide is visible
		shell.style.visibility = 'visible';
		shell.style.transition = 'transform 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
		shell.style.transform = 'none';
		shell.style.pointerEvents = 'auto';
		if ( overlay ) {
			overlay.classList.add( 'is-open' );
			overlay.style.opacity = '1';
			overlay.style.visibility = 'visible';
			overlay.style.transition = 'opacity 0.3s ease';
			overlay.style.pointerEvents = 'auto';
		}
		container.classList.add( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'true' );
	};

	const close = () => {
		shell.style.transition = 'transform 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
		shell.style.transform = closedTransform;
		shell.style.pointerEvents = 'none';
		if ( overlay ) {
			overlay.classList.remove( 'is-open' );
			overlay.style.opacity = '0';
			overlay.style.visibility = 'hidden';
			overlay.style.transition = 'opacity 0.3s ease, visibility 0s 0.3s';
			overlay.style.pointerEvents = 'none';
		}
		container.classList.remove( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		closeTimer = window.setTimeout( () => {
			shell.style.visibility = 'hidden';
		}, 350 );
	};

	trigger.addEventListener( 'click', open );
	if ( overlay ) overlay.addEventListener( 'click', close );
	if ( closeBtn ) closeBtn.addEventListener( 'click', close );
	document.addEventListener( 'keydown', ( ev ) => {
		if ( ev.key === 'Escape' && shell.style.transform === 'none' ) close();
	} );
};

/* ── Editor preview reveal ──────────────────────────────────────────────
 * The "Preview Open (Editor)" switch (editor_open prop) shows/hides the panel
 * live on the canvas so builders can fill/style it. Two hard facts drive this:
 *
 *   1. An atomic Switch commits through an internal set-settings transaction
 *      that updates the model WITHOUT re-rendering the Twig — so a
 *      `{% if editor_open %}` reveal goes stale on every live toggle (works
 *      only after a reload). We therefore reconcile from JS: poll the live
 *      `editor_open` setting off the editor model on an interval (the atomic
 *      Switch has no reliable commandEnd, same as Nav's mobile reconciler).
 *
 *   2. In edit-mode Elementor mounts the panel CHILD element as a DIRECT child
 *      of the offcanvas root, NOT inside the Twig `.aae-offcanvas-shell` (whose
 *      children_placeholder renders empty in the editor). So the toggle must
 *      show/hide the real `.aae-a-offcanvas-panel` element — hiding the empty
 *      shell (the old approach) left the editable panel visible regardless.
 *
 * When hidden we also flatten the root's min-height: the panel's
 * `.elementor-empty-view` still matches `:has()` while display:none, so the
 * core `min-height:120px` bubble would otherwise keep a phantom box under the
 * hamburger. */
const editorReconcilers = new Map();

const readEditorOpen = ( id ) => {
	try {
		const editorWindow = window.parent && window.parent !== window ? window.parent : window;
		const container = editorWindow.elementor?.getContainer?.( id );
		let value;
		if ( container?.settings?.get ) {
			value = container.settings.get( 'editor_open' );
		}
		if ( value === undefined && container?.model?.get ) {
			const settings = container.model.get( 'settings' );
			value = settings?.get ? settings.get( 'editor_open' ) : settings?.editor_open;
		}
		// Atomic booleans may arrive raw or wrapped as { $$type, value }.
		return ( value && typeof value === 'object' ) ? !! value.value : !! value;
	} catch ( error ) {
		return false;
	}
};

const initOffcanvasEditor = ( container ) => {
	const id = container.getAttribute( 'data-id' );
	if ( ! id ) return;

	// Idempotent: re-queries the panel every tick so a late-mounted / re-rendered
	// panel still gets the current open state, and only writes when it changes.
	const apply = ( open ) => {
		container.classList.toggle( 'is-open', open );

		const panel = container.querySelector( '.aae-a-offcanvas-panel' );
		if ( panel ) {
			const isHidden = 'none' === panel.style.display;
			if ( open && isHidden ) {
				panel.style.removeProperty( 'display' );
			} else if ( ! open && ! isHidden ) {
				panel.style.setProperty( 'display', 'none', 'important' );
			}
		}

		// Cancel the empty-view 120px bubble while the panel is hidden.
		const wantFlat = ! open;
		const isFlat = '0px' === container.style.minHeight;
		if ( wantFlat && ! isFlat ) {
			container.style.setProperty( 'min-height', '0', 'important' );
			container.style.setProperty( 'min-block-size', '0', 'important' );
		} else if ( ! wantFlat && isFlat ) {
			container.style.removeProperty( 'min-height' );
			container.style.removeProperty( 'min-block-size' );
		}
	};

	const reconcile = () => {
		// Element gone (deleted / re-rendered into a new node) → stop this timer.
		if ( ! document.body.contains( container ) ) {
			window.clearInterval( editorReconcilers.get( id ) );
			editorReconcilers.delete( id );
			return;
		}
		apply( readEditorOpen( id ) );
	};

	// A re-render can call this again for the same id — replace the old timer.
	if ( editorReconcilers.has( id ) ) {
		window.clearInterval( editorReconcilers.get( id ) );
	}
	reconcile();
	editorReconcilers.set( id, window.setInterval( reconcile, 250 ) );
};

register( {
	elementType: 'e-aae-a-offcanvas',
	id: 'aae-a-offcanvas-handler',
	callback: ( { element } ) => {
		if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
			initOffcanvasEditor( element );
		} else {
			initOffcanvas( element );
		}
	},
} );
