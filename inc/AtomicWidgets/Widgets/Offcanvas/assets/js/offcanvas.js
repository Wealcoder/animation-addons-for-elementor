/**
 * AAE Offcanvas — frontend runtime.
 *
 * Open/close play selectable enter/exit animations via GSAP (`window.gsap`,
 * supplied by the Pro plugin); with GSAP absent every path degrades to the CSS
 * slide, so the widget still works on free installs.
 *
 * The panel + overlay are teleported into a transform-free `.elementor` host on
 * <body> (see getPortal) so `position: fixed` resolves against the viewport
 * (ancestor container transforms would otherwise trap it) WITHOUT leaving the
 * `.elementor` scope — every atomic style rule is `.elementor .e-xxx`, so a
 * teleport straight onto <body> silently strips the panel's width/padding/
 * background/base styles. This file owns only BEHAVIOUR + GEOMETRY (fixed
 * placement, the slide transform, visibility, scroll-lock); all VISUALS stay
 * with the panel's own scoped atomic styles.
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

// ── Animation (GSAP-driven, self-contained) ──────────────────────────────
// GSAP is supplied by the Pro plugin as `window.gsap`. When it is absent (free
// installs) every path degrades to the CSS slide above — never a hard failure.
const gsap   = () => window.gsap;
const reduce = () => !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

// Panel setting easing enum → GSAP ease string (two carry parameters).
const EASES = {
	'power2.out':  'power2.out',
	'power3.out':  'power3.out',
	'back.out':    'back.out(1.7)',
	'elastic.out': 'elastic.out(1, 0.5)',
	'expo.out':    'expo.out',
	none:          'none',
};

// The resting (fully-open) state every enter tween lands on.
const REST = { opacity: 1, x: 0, y: 0, scale: 1, rotationX: 0, rotationY: 0, filter: 'blur(0px)' };

// Self-contained drawer presets → the GSAP "from"/closed vars for a given
// animation name + drawer edge. `null` means no motion (instant).
const animFrom = ( name, position ) => {
	const axis    = ( position === 'top' || position === 'bottom' ) ? 'y' : 'x';
	const signOut = ( position === 'left' || position === 'top' ) ? -1 : 1; // toward its own edge
	switch ( name ) {
		case 'none':
			return null;
		case 'fade':
			return { opacity: 0 };
		case 'fade-slide':
			return { opacity: 0, [ axis ]: signOut * 40 };
		case 'zoom':
			return { opacity: 0, scale: 0.92 };
		case 'flip':
			return {
				opacity: 0,
				transformPerspective: 1000,
				transformOrigin: { left: 'left center', right: 'right center', top: 'center top', bottom: 'center bottom' }[ position ] || 'left center',
				...( axis === 'x' ? { rotationY: signOut * -90 } : { rotationX: signOut * 90 } ),
			};
		case 'blur':
			return { opacity: 0, filter: 'blur(16px)' };
		case 'slide':
		default:
			return { [ axis ]: signOut * 100 + '%' };
	}
};

/**
 * Shared teleport host: one bare `.elementor` wrapper appended to <body>.
 *
 * The panel must escape its ancestor container transforms (an `e-con` with a
 * transform traps `position:fixed`), which is why we move it out of the widget.
 * But moving it straight onto <body> drops ALL its styling: every atomic rule
 * is scoped `.elementor .e-xxx`, so outside the page's `.elementor` wrapper the
 * panel loses its width/padding/background/base styles (looked full-width and
 * unstyled on the frontend while fine in the editor, where we never teleport).
 * Parking it under a transform-free `.elementor` host fixes both at once:
 * viewport-relative fixed positioning AND intact scoped styles.
 */
const getPortal = () => {
	let portal = document.querySelector( 'body > .aae-offcanvas-portal' );
	if ( ! portal ) {
		portal = document.createElement( 'div' );
		portal.className = 'elementor aae-offcanvas-portal';
		document.body.appendChild( portal );
	}
	return portal;
};

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
	// The close button is a real atomic child element (AAE_A_Offcanvas_Close)
	// seeded inside the panel. Match its hook class, falling back to its
	// element-type attr for robustness.
	let closeBtn = panel
		? ( panel.querySelector( '.aae-offcanvas-close' )
			|| panel.querySelector( '[data-e-type="e-aae-a-offcanvas-close"]' ) )
		: null;

	if ( ! trigger || ! panel ) {
		return;
	}

	// EDITOR: never teleport or drive the drawer here. The panel is opened for
	// editing purely from the "Open Panel (Editor)" switch, which re-renders the
	// Twig with a baked show-rule — no JS needed. Bail out.
	if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
		return;
	}

	// Migration safety-net: offcanvas instances saved BEFORE the close became a
	// real child element have no close button (the old plain-markup close was
	// removed from the panel Twig). Inject a minimal functional one so the drawer
	// is never trapped. New offcanvases ship the styleable close element instead.
	if ( panel && ! closeBtn ) {
		closeBtn = document.createElement( 'button' );
		closeBtn.type = 'button';
		closeBtn.className = 'aae-offcanvas-close';
		closeBtn.setAttribute( 'aria-label', 'Close panel' );
		Object.assign( closeBtn.style, {
			alignSelf: 'flex-end', background: 'transparent', border: '0',
			cursor: 'pointer', color: 'inherit', padding: '0', lineHeight: '0',
			marginBottom: '16px',
		} );
		closeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
		panel.insertBefore( closeBtn, panel.firstChild );
	}

	const position        = root.dataset.position || 'left';
	const closedTransform  = TRANSFORMS[ position ] || TRANSFORMS.left;
	const posStyles        = POS[ position ] || POS.left;
	const overlayColor     = root.dataset.overlayColor || 'rgba(0,0,0,0.5)';
	const closeOnOverlay   = root.dataset.closeOnOverlay !== 'false';
	const closeOnEsc       = root.dataset.closeOnEsc !== 'false';

	// Teleport into a transform-free `.elementor` host so fixed positioning uses
	// the viewport WHILE the panel keeps its `.elementor`-scoped atomic styles
	// (width/padding/background/base). See getPortal() for the why.
	const portal = getPortal();
	portal.appendChild( panel );
	if ( overlay ) {
		portal.appendChild( overlay );
	}

	// Animation config (from the panel settings, via data-attrs on the root).
	const hasGsap  = !! gsap();
	const enterAnim = root.dataset.enterAnim || 'slide';
	const exitAnim  = root.dataset.exitAnim  || 'reverse';
	const duration  = ( parseInt( root.dataset.animDuration, 10 ) || 400 ) / 1000;
	const ease      = EASES[ root.dataset.ease ] || 'power2.out';

	// Base fixed geometry — always applied, independent of the animation engine.
	// transition:none so a GSAP tween never fights a CSS transition.
	Object.assign( panel.style, {
		position:      'fixed',
		zIndex:        '9999',
		display:       'flex',
		flexDirection: 'column',
		overflowY:     'auto',
		visibility:    'hidden',
		pointerEvents: 'none',
		transition:    'none',
		...posStyles,
	} );

	// No GSAP → rest the panel off-screen and (re-)enable the CSS slide next frame.
	if ( ! hasGsap ) {
		panel.style.transform = closedTransform;
		requestAnimationFrame( () => { panel.style.transition = SLIDE; } );
	}

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

	let closeTimer;

	const killTween = () => {
		if ( panel.__aaeTween ) {
			panel.__aaeTween.kill();
			panel.__aaeTween = null;
		}
	};

	const showOverlay = () => {
		if ( ! overlay ) return;
		overlay.style.transition    = 'opacity 0.3s ease';
		overlay.style.visibility    = 'visible';
		overlay.style.opacity       = '1';
		overlay.style.pointerEvents = 'auto';
	};
	const hideOverlay = () => {
		if ( ! overlay ) return;
		overlay.style.transition    = 'opacity 0.3s ease, visibility 0s 0.3s';
		overlay.style.opacity       = '0';
		overlay.style.pointerEvents = 'none';
		overlay.style.visibility    = 'hidden';
	};

	const open = () => {
		window.clearTimeout( closeTimer );
		killTween();
		panel.style.visibility    = 'visible';
		panel.style.pointerEvents = 'auto';
		showOverlay();
		root.classList.add( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'true' );
		document.body.style.overflow = 'hidden'; // scroll-lock while open

		const from = animFrom( enterAnim, position );
		if ( hasGsap && from && ! reduce() ) {
			// Enter tween: from the preset's closed vars → the resting state.
			panel.__aaeTween = gsap().fromTo( panel, from, {
				...REST,
				duration,
				ease,
				clearProps:  'transform',
				onComplete:  () => { panel.__aaeTween = null; },
			} );
		} else {
			// No-GSAP CSS slide (transform:none triggers SLIDE), or instant when
			// GSAP is present but the animation is "none"/reduced-motion.
			panel.style.transform = 'none';
			panel.style.opacity   = '1';
			panel.style.filter    = 'none';
		}
	};

	const finishClose = () => {
		panel.style.visibility = 'hidden';
	};

	const close = () => {
		killTween();
		hideOverlay();
		root.classList.remove( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		document.body.style.overflow = '';
		panel.style.pointerEvents = 'none';

		// `reverse` (default) exits with the enter animation's own closed vars.
		const exitName = exitAnim === 'reverse' ? enterAnim : exitAnim;
		const to       = animFrom( exitName, position );

		if ( hasGsap && to && ! reduce() ) {
			panel.__aaeTween = gsap().to( panel, {
				...to,
				duration,
				ease,
				onComplete: () => { panel.__aaeTween = null; finishClose(); },
			} );
		} else if ( ! hasGsap ) {
			// CSS slide-out, then hide after it so it can't catch off-screen clicks.
			panel.style.transform = closedTransform;
			closeTimer = window.setTimeout( finishClose, 350 );
		} else {
			// GSAP present but exit is "none"/reduced-motion → instant hide.
			finishClose();
		}
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
