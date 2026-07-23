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

	// Teleport to <body> so fixed positioning uses the viewport.
	document.body.appendChild( panel );
	if ( overlay ) {
		document.body.appendChild( overlay );
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
