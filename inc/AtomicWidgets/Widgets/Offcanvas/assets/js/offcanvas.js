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

register( {
	elementType: 'e-aae-a-offcanvas',
	id: 'aae-a-offcanvas-handler',
	callback: ( { element } ) => initOffcanvas( element ),
} );
