/**
 * AAE Offcanvas — frontend runtime (vanilla JS, no GSAP).
 *
 * The panel + overlay are teleported to <body> so `position: fixed` resolves
 * against the viewport (Elementor container transforms would otherwise trap
 * it). The panel's VISUAL look (background, width, padding…) comes from its
 * atomic base style / Style-panel classes, which are global and survive the
 * move — this file only owns BEHAVIOUR + GEOMETRY (fixed placement, the slide
 * transform, visibility, scroll-lock). The one visual value we re-apply inline
 * is the computed background, read BEFORE the move as a guard against the
 * teleport dropping it on some themes (see the skill's teleport pitfall).
 */

import { register } from '@elementor/frontend-handlers';

// Closed-state slide per edge (100% = the element's own size, so we never need
// to measure it).
const TRANSFORMS = {
	left:   'translateX(-100%)',
	right:  'translateX(100%)',
	top:    'translateY(-100%)',
	bottom: 'translateY(100%)',
};

// Fixed geometry per edge. Left/right leave width/height to the base style
// (full-height side drawer); top/bottom force a full-width, content-height bar.
const POS = {
	left:   { left: '0',  top: '0',    bottom: '0',    right: 'auto' },
	right:  { right: '0', top: '0',    bottom: '0',    left: 'auto' },
	top:    { top: '0',   left: '0',   right: '0',     bottom: 'auto', width: '100%', maxWidth: 'none', height: 'auto', maxHeight: '90vh' },
	bottom: { bottom: '0', left: '0',  right: '0',     top: 'auto',    width: '100%', maxWidth: 'none', height: 'auto', maxHeight: '90vh' },
};

const SLIDE = 'transform 0.35s cubic-bezier(0.4, 0, 0.2, 1)';

const initOffcanvas = ( root ) => {
	if ( root.dataset.aaeOffcanvasInit === 'true' ) {
		return;
	}
	root.dataset.aaeOffcanvasInit = 'true';

	const trigger  = root.querySelector( '.aae-offcanvas-trigger' );
	const overlay  = root.querySelector( '.aae-offcanvas-overlay' );
	// Find the panel by class, falling back to its element-type attr — the v4
	// editor strips custom classes off atomic child elements, but data-attrs stay.
	const panel    = root.querySelector( '.aae-a-offcanvas-panel' )
		|| root.querySelector( '[data-element_type="e-aae-a-offcanvas-panel"]' );
	const closeBtn = panel ? panel.querySelector( '.aae-offcanvas-close' ) : null;

	if ( ! trigger || ! panel ) {
		return;
	}

	// EDITOR: never teleport or drive the drawer here. The panel is opened for
	// editing purely from the "Open Panel (Editor)" switch, which re-renders the
	// Twig with a baked show-rule — no JS needed. Bail out.
	if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
		return;
	}

	const position        = root.dataset.position || 'left';
	const closedTransform  = TRANSFORMS[ position ] || TRANSFORMS.left;
	const posStyles        = POS[ position ] || POS.left;
	const overlayColor     = root.dataset.overlayColor || 'rgba(0,0,0,0.5)';
	const closeOnOverlay   = root.dataset.closeOnOverlay !== 'false';
	const closeOnEsc       = root.dataset.closeOnEsc !== 'false';

	// Preserve the panel's computed background BEFORE it leaves Elementor's
	// scoped DOM — teleporting can otherwise drop the Style-tab background.
	const cs      = window.getComputedStyle( panel );
	const bgImage = cs.backgroundImage;
	const bgColor = cs.backgroundColor;
	const panelBg = ( bgImage && bgImage !== 'none' )
		? cs.background
		: ( bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent' )
			? bgColor
			: '#ffffff';

	// Teleporting the panel out of the document wrapper drops ALL its scoped
	// atomic styles (base + Style-panel), not just the background — so the width /
	// height / padding / border the user set from the Style tab vanish on the
	// frontend. Snapshot the whole box-model now and re-apply it inline after the
	// move. Per-edge geometry (POS) is applied AFTER this, so top/bottom still win
	// with their forced full-width sizing.
	const PRESERVE = [
		'width', 'height', 'minWidth', 'minHeight', 'maxWidth', 'maxHeight',
		'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
		'borderTopWidth', 'borderTopStyle', 'borderTopColor',
		'borderRightWidth', 'borderRightStyle', 'borderRightColor',
		'borderBottomWidth', 'borderBottomStyle', 'borderBottomColor',
		'borderLeftWidth', 'borderLeftStyle', 'borderLeftColor',
		'borderTopLeftRadius', 'borderTopRightRadius',
		'borderBottomRightRadius', 'borderBottomLeftRadius',
		'boxShadow',
	];
	const panelBox = {};
	PRESERVE.forEach( function ( prop ) { panelBox[ prop ] = cs[ prop ]; } );

	// The Close icon is an atomic child INSIDE the panel, so it loses its scoped
	// base / Style-panel styles when the panel teleports out of the document
	// wrapper (same root cause as the panel background above). Snapshot its
	// computed size + colour now, re-apply inline after the move — otherwise the
	// svg (width/height:100%) has no sized parent and blows up to a giant icon.
	let closeStyle = null;
	if ( closeBtn ) {
		const ccs = window.getComputedStyle( closeBtn );
		closeStyle = { width: ccs.width, height: ccs.height, color: ccs.color };
	}

	// Teleport to <body> so fixed positioning uses the viewport.
	document.body.appendChild( panel );
	if ( overlay ) {
		document.body.appendChild( overlay );
	}

	// Restore the close icon's size/colour inline so it survives the teleport.
	if ( closeBtn && closeStyle ) {
		Object.assign( closeBtn.style, closeStyle );
	}

	// Place the panel off-screen. Transition off for the initial placement so it
	// doesn't animate in from nowhere on load; re-enabled next frame.
	Object.assign( panel.style, {
		position:      'fixed',
		zIndex:        '9999',
		display:       'flex',
		flexDirection: 'column',
		overflowY:     'auto',
		visibility:    'hidden',
		pointerEvents: 'none',
		transition:    'none',
		background:    panelBg,
		...panelBox,
		...posStyles,
		transform:     closedTransform,
	} );

	if ( overlay ) {
		Object.assign( overlay.style, {
			position:      'fixed',
			inset:         '0',
			zIndex:        '9998',
			background:     overlayColor,
			opacity:       '0',
			visibility:    'hidden',
			pointerEvents: 'none',
			transition:    'opacity 0.3s ease, visibility 0s 0.3s',
		} );
	}

	requestAnimationFrame( () => {
		panel.style.transition = SLIDE;
	} );

	let closeTimer;

	const open = () => {
		window.clearTimeout( closeTimer );
		panel.style.visibility    = 'visible';
		panel.style.pointerEvents = 'auto';
		panel.style.transform     = 'none';
		if ( overlay ) {
			overlay.style.transition    = 'opacity 0.3s ease';
			overlay.style.visibility    = 'visible';
			overlay.style.opacity       = '1';
			overlay.style.pointerEvents = 'auto';
		}
		root.classList.add( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'true' );
		document.body.style.overflow = 'hidden'; // scroll-lock while open
	};

	const close = () => {
		panel.style.transform     = closedTransform;
		panel.style.pointerEvents = 'none';
		if ( overlay ) {
			overlay.style.transition    = 'opacity 0.3s ease, visibility 0s 0.3s';
			overlay.style.opacity       = '0';
			overlay.style.pointerEvents = 'none';
			overlay.style.visibility    = 'hidden';
		}
		root.classList.remove( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		document.body.style.overflow = '';
		// Hide after the slide-out so it can't catch pointer events off-screen.
		closeTimer = window.setTimeout( () => {
			panel.style.visibility = 'hidden';
		}, 350 );
	};

	trigger.addEventListener( 'click', open );
	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', close );
	}
	if ( overlay && closeOnOverlay ) {
		overlay.addEventListener( 'click', close );
	}
	if ( closeOnEsc ) {
		document.addEventListener( 'keydown', ( ev ) => {
			if ( ev.key === 'Escape' && root.classList.contains( 'is-open' ) ) {
				close();
			}
		} );
	}
};

register( {
	elementType: 'e-aae-a-offcanvas',
	id: 'aae-a-offcanvas-handler',
	callback: ( { element } ) => {
		const root = element.classList.contains( 'aae-a-offcanvas' )
			? element
			: element.querySelector( '.aae-a-offcanvas' );
		if ( root ) {
			initOffcanvas( root );
		}
	},
} );
