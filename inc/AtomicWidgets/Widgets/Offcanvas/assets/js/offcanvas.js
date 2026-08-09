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
//
// No `maxHeight` here any more. It used to cap top/bottom drawers at 90vh, but
// as an INLINE style it outranked every stylesheet — so a builder who set Max
// Height in the Style tab watched it do nothing, with no way to discover why.
// Only genuine runtime state (which edge the drawer is pinned to) belongs
// inline; a design default belongs in a base style, where it is overridable.
// It is not moved to the panel's base style because that is shared by all four
// edges, and a 90vh cap would then also shrink the full-height left/right
// drawers, which are 100vh by design.
const POS = {
	left:   { left: '0',  top: '0',    bottom: '0',    right: 'auto' },
	right:  { right: '0', top: '0',    bottom: '0',    left: 'auto' },
	top:    { top: '0',   left: '0',   right: '0',     bottom: 'auto', width: '100%', maxWidth: 'none', height: 'auto' },
	bottom: { bottom: '0', left: '0',  right: '0',     top: 'auto',    width: '100%', maxWidth: 'none', height: 'auto' },
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
		case 'circle':  // shape reveal — handled via clip-path, not a transform tween
		case 'blinds':  // bar reveal — handled by a cover layer, not a transform tween
		case 'stripes': // ditto (vertical bars)
		case 'tiles':   // mosaic reveal — cover layer of grid tiles
		case 'curtain': // two cover panes part in opposite directions
		case 'stagger': // panel in place, its children cascade in
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

// ── "Circle Reveal" shape animation ──────────────────────────────────────
// A circle grows from the drawer's corner (clip-path 0% -> 150%). A 150% radius
// fully covers the panel rectangle, so the whole bg appears GRADUALLY with no
// snap. Reliable everywhere (clip-path circle interpolates cleanly) unlike the
// earlier mask-cloud. Reverses to 0% on close. Stays within the drawer's box.
const CIRCLE_ORIGIN = { left: '0% 0%', right: '100% 0%', top: '50% 0%', bottom: '50% 100%' };

// clip-path circle of `pct` radius centred at `origin`.
const circleClip = ( pct, origin ) => `circle(${ pct } at ${ origin })`;

// Drop the clip once fully open so the resting panel is never clipped.
const clearClip = ( panel ) => { panel.style.clipPath = ''; panel.style.webkitClipPath = ''; };

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

// Tabbable elements inside the panel — used by the focus trap while open.
const FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

// Body scroll-lock shared across every drawer on the page: only the FIRST open
// locks and only the LAST close unlocks, so closing one drawer never re-enables
// page scroll while another one is still open.
let bodyLockCount = 0;
let savedBodyPadR = '';
const lockScroll = () => {
	if ( bodyLockCount === 0 ) {
		// Compensate the scrollbar width BEFORE hiding overflow, otherwise removing
		// the scrollbar reflows the whole page sideways (~15px) — a jarring "jump"
		// right as the drawer opens. Measure, then pad the body by that width.
		const scrollbar = window.innerWidth - document.documentElement.clientWidth;
		savedBodyPadR = document.body.style.paddingRight;
		if ( scrollbar > 0 ) {
			const current = parseFloat( getComputedStyle( document.body ).paddingRight ) || 0;
			document.body.style.paddingRight = ( current + scrollbar ) + 'px';
		}
		document.body.style.overflow = 'hidden';
	}
	bodyLockCount += 1;
};
const unlockScroll = () => {
	bodyLockCount = Math.max( 0, bodyLockCount - 1 );
	if ( bodyLockCount === 0 ) {
		document.body.style.overflow = '';
		document.body.style.paddingRight = savedBodyPadR;
	}
};

const initOffcanvas = ( root ) => {
	if ( root.dataset.aaeOffcanvasInit === 'true' ) {
		return;
	}
	root.dataset.aaeOffcanvasInit = 'true';

	// The trigger is a real atomic child element (AAE_A_Offcanvas_Trigger) seeded
	// in the offcanvas root. Match its hook class, falling back to its element-type
	// attr (the v4 editor strips custom classes off atomic children; data-attrs stay).
	let trigger    = root.querySelector( '.aae-offcanvas-trigger' )
		|| root.querySelector( '[data-e-type="e-aae-a-offcanvas-trigger"]' );
	// The scrim is a real atomic child element (AAE_A_Offcanvas_Overlay). Match its
	// hook class, falling back to its element-type attr (editor strips classes).
	let overlay = root.querySelector( '.aae-offcanvas-overlay' )
		|| root.querySelector( '[data-e-type="e-aae-a-offcanvas-overlay"]' );
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

	if ( ! panel ) {
		return;
	}

	// EDITOR: never teleport or drive the drawer here. The panel is opened for
	// editing purely from the "Open Panel (Editor)" switch, which re-renders the
	// Twig with a baked show-rule — no JS needed. Bail out.
	if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
		return;
	}

	// Migration safety-net: offcanvas instances saved BEFORE the trigger became a
	// real child element have no trigger (the old plain-markup trigger was removed
	// from the parent Twig). Inject a minimal functional one so the drawer can
	// still be opened. New offcanvases ship the styleable trigger element instead.
	if ( ! trigger ) {
		trigger = document.createElement( 'button' );
		trigger.type = 'button';
		trigger.className = 'aae-offcanvas-trigger';
		trigger.setAttribute( 'aria-haspopup', 'dialog' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		trigger.setAttribute( 'aria-label', 'Open panel' );
		Object.assign( trigger.style, {
			display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
			cursor: 'pointer', background: 'transparent', border: '0', color: 'inherit',
			padding: '0', fontSize: '24px', lineHeight: '0',
		} );
		trigger.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>';
		root.insertBefore( trigger, root.firstChild );
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

	// Migration safety-net: instances saved BEFORE the overlay became a real child
	// element have none (the old plain-markup scrim was removed from the Twig).
	// Inject a minimal one with the default scrim colour so the backdrop still works.
	if ( ! overlay ) {
		overlay = document.createElement( 'div' );
		overlay.className = 'aae-offcanvas-overlay';
		overlay.setAttribute( 'aria-hidden', 'true' );
		overlay.style.background = 'rgba(0, 0, 0, 0.5)';
	}

	const position        = root.dataset.position || 'left';
	const closedTransform  = TRANSFORMS[ position ] || TRANSFORMS.left;
	const posStyles        = POS[ position ] || POS.left;
	const closeOnOverlay   = root.dataset.closeOnOverlay !== 'false';
	const closeOnEsc       = root.dataset.closeOnEsc !== 'false';
	const overlayAnim      = root.dataset.overlayAnim || 'fade';

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
	// NB: no `display` / `flexDirection` here. The panel is a plain block whose
	// layout comes from its base style (and whatever the builder sets in the
	// Style tab). Writing them inline would beat BOTH and silently pin every
	// panel to a flex column — only the fixed-position geometry belongs inline,
	// because that is runtime state, not a design choice.
	Object.assign( panel.style, {
		position:      'fixed',
		zIndex:        '9999',
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
		// NB: no `background` here — the scrim colour comes from the overlay
		// element's own base style / Style-tab Background, so it stays user-editable.
		Object.assign( overlay.style, {
			position:      'fixed',
			inset:         '0',
			zIndex:        '9998',
			opacity:       '0',
			visibility:    'hidden',
			pointerEvents: 'none',
			transition:    'opacity 0.3s ease, visibility 0s 0.3s',
		} );
	}

	let closeTimer;
	let panelEnterTimer;
	let previouslyFocused = null;

	// Panel is the modal surface — make it programmatically focusable so focus
	// can land on it when it holds no natural tab stop.
	panel.setAttribute( 'tabindex', '-1' );

	// Visible, tabbable elements inside the panel right now.
	const getFocusable = () =>
		Array.from( panel.querySelectorAll( FOCUSABLE ) ).filter(
			( el ) => el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement
		);

	// Keep Tab / Shift+Tab inside the open drawer — `aria-modal` promises this,
	// so without it keyboard focus silently escapes to the page behind.
	const trapFocus = ( ev ) => {
		if ( ev.key !== 'Tab' ) {
			return;
		}
		const focusables = getFocusable();
		if ( ! focusables.length ) {
			ev.preventDefault();
			panel.focus();
			return;
		}
		const first = focusables[ 0 ];
		const last  = focusables[ focusables.length - 1 ];
		if ( ev.shiftKey && document.activeElement === first ) {
			ev.preventDefault();
			last.focus();
		} else if ( ! ev.shiftKey && document.activeElement === last ) {
			ev.preventDefault();
			first.focus();
		}
	};

	const killTween = () => {
		if ( panel.__aaeTween ) {
			panel.__aaeTween.kill();
			panel.__aaeTween = null;
		}
	};

	// Circle-reveal geometry for the backdrop: bloom from the trigger's centre,
	// radius = the farthest viewport corner (so the scrim fully covers). The
	// overlay is `position:fixed; inset:0`, so its box == the viewport and the
	// trigger's viewport-relative rect maps straight onto the clip coordinates.
	const overlayCircle = () => {
		const r  = trigger.getBoundingClientRect();
		const cx = r.left + r.width / 2;
		const cy = r.top + r.height / 2;
		const vw = window.innerWidth;
		const vh = window.innerHeight;
		const radius = Math.ceil( Math.max(
			Math.hypot( cx, cy ),
			Math.hypot( vw - cx, cy ),
			Math.hypot( cx, vh - cy ),
			Math.hypot( vw - cx, vh - cy )
		) ) + 2;
		return { cx, cy, radius };
	};
	const killOverlayTween = () => {
		if ( overlay && overlay.__aaeOvTween ) {
			overlay.__aaeOvTween.kill();
			overlay.__aaeOvTween = null;
		}
	};
	const clearOverlayClip = () => {
		if ( ! overlay ) return;
		overlay.style.clipPath = '';
		overlay.style.webkitClipPath = '';
	};

	// Blinds / stripes: split the backdrop into N vertical bars that drop in one
	// after another (stagger). The scrim colour moves onto the bars; the overlay
	// itself becomes a transparent, clipping container. Bars are built once and
	// reused across open/close cycles.
	const BLIND_COUNT = 6;
	// Bars must be OPAQUE: with a translucent fill the 1px overlap (that hides
	// sub-pixel gaps) stacks alpha into a visible dark seam line, and a see-through
	// scrim makes the staggered reveal almost imperceptible. Force full opacity
	// from the chosen scrim colour so each column reads as a solid, clear cover.
	// Read the scrim colour off the overlay element's OWN computed background
	// (its base style / the user's Style-tab pick) and force it fully opaque.
	const opaqueBarFill = () => {
		const c = overlay ? getComputedStyle( overlay ).backgroundColor : '';
		if ( ! c || c === 'transparent' || c === 'rgba(0, 0, 0, 0)' ) return 'rgb(0, 0, 0)';
		const m = c.match( /rgba?\(([^)]+)\)/ );
		if ( m ) {
			const p = m[ 1 ].split( ',' ).map( ( s ) => s.trim() );
			return `rgb(${ p[ 0 ] }, ${ p[ 1 ] }, ${ p[ 2 ] })`;
		}
		return c;
	};
	const buildBlinds = () => {
		if ( ! overlay ) return [];
		if ( overlay.__aaeBars ) return overlay.__aaeBars;
		// Capture the colour BEFORE clearing the container's background.
		const fill = opaqueBarFill();
		overlay.style.background = 'transparent';
		overlay.style.overflow   = 'hidden';
		const bars = [];
		const w = 100 / BLIND_COUNT;
		for ( let i = 0; i < BLIND_COUNT; i++ ) {
			const bar = document.createElement( 'div' );
			Object.assign( bar.style, {
				position:        'absolute',
				top:             '0',
				bottom:          '0',
				// +1px overlap kills sub-pixel seams (safe now the fill is opaque).
				left:            `calc(${ i * w }% - 0.5px)`,
				width:           `calc(${ w }% + 1px)`,
				background:      fill,
				transform:       'scaleY(0)',
				transformOrigin: 'top',
				willChange:      'transform',
			} );
			overlay.appendChild( bar );
			bars.push( bar );
		}
		overlay.__aaeBars = bars;
		return bars;
	};

	// How long the backdrop reveal takes (ms). Used to delay the panel's entrance
	// on open so a non-fade backdrop (blinds/circle) plays FULLY and stays visible
	// before the drawer slides over it — otherwise the panel covers the staircase.
	// `fade` and reduced-motion return 0 (panel enters immediately, as before).
	const overlayRevealMs = () => {
		if ( reduce() ) return 0;
		if ( overlayAnim === 'blinds' ) {
			const n    = ( overlay && overlay.__aaeBars ) ? overlay.__aaeBars.length : BLIND_COUNT;
			const stag = duration / ( n * 2 );
			return ( duration + stag * ( n - 1 ) ) * 1000;
		}
		if ( overlayAnim === 'circle' ) {
			return duration * 1000;
		}
		return 0;
	};

	const showOverlay = () => {
		if ( ! overlay ) return;
		overlay.style.visibility    = 'visible';
		overlay.style.pointerEvents = 'auto';

		if ( overlayAnim === 'blinds' && ! reduce() ) {
			// Bars drop in left→right; the container carries no colour of its own.
			const bars = buildBlinds();
			overlay.style.opacity = '1';
			killOverlayTween();
			const stag = duration / ( bars.length * 2 );
			if ( hasGsap ) {
				overlay.__aaeOvTween = gsap().to( bars, {
					scaleY: 1, transformOrigin: 'top', duration, ease,
					stagger: stag,
					onComplete: () => { overlay.__aaeOvTween = null; },
				} );
			} else {
				bars.forEach( ( bar, i ) => {
					bar.style.transition = `transform ${ duration }s ease ${ i * stag }s`;
					bar.style.transform  = 'scaleY(1)';
				} );
			}
			return;
		}

		if ( overlayAnim === 'circle' && ! reduce() ) {
			// Backdrop blooms outward as a growing clip-path circle.
			killOverlayTween();
			const g    = overlayCircle();
			const from = `circle(0px at ${ g.cx }px ${ g.cy }px)`;
			const to   = `circle(${ g.radius }px at ${ g.cx }px ${ g.cy }px)`;
			overlay.style.opacity = '1';
			if ( hasGsap ) {
				overlay.style.transition = 'none';
				overlay.__aaeOvTween = gsap().fromTo( overlay,
					{ clipPath: from, webkitClipPath: from },
					{ clipPath: to, webkitClipPath: to, duration, ease, onComplete: () => { overlay.__aaeOvTween = null; } }
				);
			} else {
				overlay.style.transition   = 'none';
				overlay.style.clipPath     = from;
				overlay.style.webkitClipPath = from;
				requestAnimationFrame( () => {
					const t = `clip-path ${ duration }s ease, -webkit-clip-path ${ duration }s ease`;
					overlay.style.transition     = t;
					overlay.style.clipPath       = to;
					overlay.style.webkitClipPath = to;
				} );
			}
			return;
		}

		// Default: plain opacity fade.
		clearOverlayClip();
		overlay.style.transition = 'opacity 0.3s ease';
		overlay.style.opacity    = '1';
	};

	const hideOverlay = () => {
		if ( ! overlay ) return;
		overlay.style.pointerEvents = 'none';

		if ( overlayAnim === 'blinds' && ! reduce() ) {
			// Bars retract right→left (reverse stagger), then the overlay hides.
			const bars = overlay.__aaeBars || buildBlinds();
			killOverlayTween();
			const stag = duration / ( bars.length * 2 );
			if ( hasGsap ) {
				overlay.__aaeOvTween = gsap().to( bars, {
					scaleY: 0, transformOrigin: 'top', duration, ease,
					stagger: { each: stag, from: 'end' },
					onComplete: () => { overlay.__aaeOvTween = null; overlay.style.visibility = 'hidden'; },
				} );
			} else {
				bars.forEach( ( bar, i ) => {
					const rev = bars.length - 1 - i;
					bar.style.transition = `transform ${ duration }s ease ${ rev * stag }s`;
					bar.style.transform  = 'scaleY(0)';
				} );
				window.setTimeout(
					() => { overlay.style.visibility = 'hidden'; },
					( duration + stag * bars.length ) * 1000 + 50
				);
			}
			return;
		}

		if ( overlayAnim === 'circle' && ! reduce() ) {
			// Backdrop shrinks back into the trigger, then hides.
			killOverlayTween();
			const g    = overlayCircle();
			const from = `circle(${ g.radius }px at ${ g.cx }px ${ g.cy }px)`;
			const to   = `circle(0px at ${ g.cx }px ${ g.cy }px)`;
			if ( hasGsap ) {
				overlay.style.transition = 'none';
				overlay.__aaeOvTween = gsap().fromTo( overlay,
					{ clipPath: from, webkitClipPath: from },
					{ clipPath: to, webkitClipPath: to, duration, ease, onComplete: () => {
						overlay.__aaeOvTween = null;
						overlay.style.visibility = 'hidden';
						clearOverlayClip();
					} }
				);
			} else {
				const t = `clip-path ${ duration }s ease, -webkit-clip-path ${ duration }s ease`;
				overlay.style.transition     = t;
				overlay.style.clipPath       = to;
				overlay.style.webkitClipPath = to;
				window.setTimeout( () => {
					overlay.style.visibility = 'hidden';
					clearOverlayClip();
				}, duration * 1000 + 50 );
			}
			return;
		}

		// Default: plain opacity fade.
		clearOverlayClip();
		overlay.style.transition    = 'opacity 0.3s ease, visibility 0s 0.3s';
		overlay.style.opacity       = '0';
		overlay.style.visibility    = 'hidden';
	};

	// ── Panel cover-layer reveals (Blinds / Stripes / Tiles / Curtain) ────────
	// The drawer is unveiled through opaque shapes laid OVER it in a temporary
	// cover layer: on open they clear off the panel in a stagger so the content
	// appears piece-by-piece; on close they sweep back to cover it. The layer is
	// removed the moment the reveal finishes, so it never touches the panel's own
	// layout or the focus trap. Each effect only differs in what shapes fill the
	// layer and how they animate. `stagger` (below) is separate — it moves the
	// panel's real children, not a cover layer.
	const PANEL_BLIND_COUNT = 6;   // bars (blinds / stripes)
	const TILE_COLS = 6, TILE_ROWS = 4; // tiles (mosaic)

	// Shape colour: the SCRIM colour (same source as the backdrop blinds), forced
	// opaque, so the cover reads as the backdrop opening to reveal the drawer.
	// Using the panel's OWN background instead looked broken — a white cover over
	// a white drawer is invisible, so the reveal appeared to do nothing. The scrim
	// colour contrasts with a typical light panel and keeps all cover effects
	// visually unified. Users retint it via the Overlay element's Style-tab
	// Background, exactly like the backdrop blinds.
	const panelBarFill = () => opaqueBarFill();

	const clearPanelBars = () => {
		if ( panel.__aaeBarLayer ) {
			panel.__aaeBarLayer.remove();
			panel.__aaeBarLayer = null;
		}
	};

	// The absolute, clipping host all cover shapes live in (tracked for cleanup).
	const coverLayer = () => {
		const layer = document.createElement( 'div' );
		Object.assign( layer.style, {
			position: 'absolute', inset: '0', overflow: 'hidden',
			pointerEvents: 'none', zIndex: '3',
		} );
		panel.appendChild( layer );
		panel.__aaeBarLayer = layer;
		return layer;
	};

	// Blinds (horizontal bars) / Stripes (vertical bars).
	const buildPanelBars = ( vertical ) => {
		const layer = coverLayer();
		const fill  = panelBarFill();
		const bars  = [];
		const size  = 100 / PANEL_BLIND_COUNT;
		for ( let i = 0; i < PANEL_BLIND_COUNT; i++ ) {
			const bar = document.createElement( 'div' );
			const common = { position: 'absolute', background: fill, willChange: 'transform' };
			if ( vertical ) {
				Object.assign( bar.style, common, {
					top: '0', bottom: '0',
					left: `calc(${ i * size }% - 0.5px)`,
					width: `calc(${ size }% + 1px)`,
					transformOrigin: 'left',
				} );
			} else {
				Object.assign( bar.style, common, {
					left: '0', right: '0',
					top: `calc(${ i * size }% - 0.5px)`,
					height: `calc(${ size }% + 1px)`,
					transformOrigin: 'top',
				} );
			}
			layer.appendChild( bar );
			bars.push( bar );
		}
		return { layer, els: bars };
	};

	// Tiles (mosaic grid). Row-major order so a GSAP grid stagger flows diagonally.
	const buildPanelTiles = () => {
		const layer = coverLayer();
		const fill  = panelBarFill();
		const tiles = [];
		const w = 100 / TILE_COLS, h = 100 / TILE_ROWS;
		for ( let r = 0; r < TILE_ROWS; r++ ) {
			for ( let c = 0; c < TILE_COLS; c++ ) {
				const t = document.createElement( 'div' );
				Object.assign( t.style, {
					position: 'absolute', background: fill, willChange: 'transform',
					left: `calc(${ c * w }% - 0.5px)`, top: `calc(${ r * h }% - 0.5px)`,
					width: `calc(${ w }% + 1px)`, height: `calc(${ h }% + 1px)`,
					transformOrigin: 'center',
				} );
				layer.appendChild( t );
				tiles.push( t );
			}
		}
		return { layer, els: tiles };
	};

	// Curtain (two panes that part in opposite directions).
	const buildCurtain = () => {
		const layer = coverLayer();
		const fill  = panelBarFill();
		const panes = [];
		for ( let i = 0; i < 2; i++ ) {
			const p = document.createElement( 'div' );
			Object.assign( p.style, {
				position: 'absolute', top: '0', bottom: '0', background: fill,
				willChange: 'transform',
				left: i === 0 ? '-0.5px' : 'calc(50% - 0.5px)',
				width: 'calc(50% + 1px)',
			} );
			layer.appendChild( p );
			panes.push( p );
		}
		return { layer, els: panes };
	};

	// Run a cover-layer reveal. `reveal` true → clear off the panel (open); false →
	// sweep back to cover it (close). GSAP if present, else a CSS-transition
	// stagger (start state pinned + reflowed first, so a freshly-inserted node's
	// transition actually runs instead of the whole thing popping in one paint).
	const runCoverReveal = ( name, reveal, done ) => {
		clearPanelBars();
		const finish = () => { panel.__aaeTween = null; clearPanelBars(); if ( done ) done(); };

		// Per-effect: the builder, the transform axis, and each element's delay unit.
		let els, gsapVars, cssFrom, cssTo, cssDelay, span;
		if ( name === 'tiles' ) {
			( { els } = buildPanelTiles() );
			const s0 = reveal ? 1 : 0, s1 = reveal ? 0 : 1;
			span = duration; // total stagger spread
			gsapVars = {
				set: { scale: s0 },
				to: { scale: s1, duration, ease,
					stagger: { grid: [ TILE_ROWS, TILE_COLS ], from: reveal ? 'start' : 'end', amount: span } },
			};
			cssFrom  = ( ) => `scale(${ s0 })`;
			cssTo    = ( ) => `scale(${ s1 })`;
			cssDelay = ( i ) => {
				const r = Math.floor( i / TILE_COLS ), c = i % TILE_COLS;
				const d = reveal ? ( r + c ) : ( ( TILE_ROWS - 1 - r ) + ( TILE_COLS - 1 - c ) );
				return ( d / ( ( TILE_ROWS - 1 ) + ( TILE_COLS - 1 ) ) ) * span;
			};
		} else if ( name === 'curtain' ) {
			( { els } = buildCurtain() );
			const out = ( i ) => ( i === 0 ? -101 : 101 );
			span = 0;
			gsapVars = {
				set: reveal ? { xPercent: 0 } : { xPercent: ( i ) => out( i ) },
				to:  reveal ? { xPercent: ( i ) => out( i ), duration, ease }
					: { xPercent: 0, duration, ease },
			};
			cssFrom  = ( i ) => `translateX(${ reveal ? 0 : out( i ) }%)`;
			cssTo    = ( i ) => `translateX(${ reveal ? out( i ) : 0 }%)`;
			cssDelay = ( ) => 0;
		} else { // blinds / stripes
			const vertical = name === 'stripes';
			const fn = vertical ? 'scaleX' : 'scaleY';
			const origin = vertical ? 'left' : 'top';
			( { els } = buildPanelBars( vertical ) );
			const s0 = reveal ? 1 : 0, s1 = reveal ? 0 : 1;
			const stag = duration / ( els.length * 2 );
			span = stag * els.length;
			gsapVars = {
				set: { [ fn === 'scaleX' ? 'scaleX' : 'scaleY' ]: s0, transformOrigin: origin },
				to: { [ fn === 'scaleX' ? 'scaleX' : 'scaleY' ]: s1, transformOrigin: origin, duration, ease,
					stagger: reveal ? stag : { each: stag, from: 'end' } },
			};
			cssFrom  = ( ) => `${ fn }(${ s0 })`;
			cssTo    = ( ) => `${ fn }(${ s1 })`;
			cssDelay = ( i ) => ( reveal ? i : ( els.length - 1 - i ) ) * stag;
		}

		if ( hasGsap ) {
			gsap().set( els, gsapVars.set );
			panel.__aaeTween = gsap().to( els, { ...gsapVars.to, onComplete: finish } );
		} else {
			els.forEach( ( el, i ) => {
				el.style.transition = 'none';
				el.style.transform  = cssFrom( i );
			} );
			void panel.__aaeBarLayer.offsetHeight; // commit the start state
			requestAnimationFrame( () => {
				els.forEach( ( el, i ) => {
					el.style.transition = `transform ${ duration }s ease ${ cssDelay( i ) }s`;
					el.style.transform  = cssTo( i );
				} );
			} );
			window.setTimeout( finish, ( duration + span ) * 1000 + 80 );
		}
	};

	// ── Panel "Stagger Content" reveal ────────────────────────────────────────
	// Not a cover layer: the panel shows in place and its own children cascade in
	// (fade + rise) one after another — the "premium menu" look. Transient inline
	// styles only, cleared on finish so the panel returns to its authored state.
	const staggerTargets = () => {
		let kids = Array.from( panel.children ).filter(
			( el ) => el.nodeType === 1 && el !== panel.__aaeBarLayer
		);
		// A single wrapper child → descend one level so there's something to cascade.
		if ( kids.length === 1 && kids[ 0 ].children.length > 1 ) {
			kids = Array.from( kids[ 0 ].children ).filter( ( el ) => el.nodeType === 1 );
		}
		return kids;
	};
	const resetStagger = () => {
		if ( panel.__aaeStaggerEls ) {
			panel.__aaeStaggerEls.forEach( ( t ) => {
				t.style.opacity = ''; t.style.transform = ''; t.style.transition = '';
			} );
			panel.__aaeStaggerEls = null;
		}
	};
	const animateStagger = ( reveal, done ) => {
		resetStagger();
		const targets = staggerTargets();
		const finish  = () => { panel.__aaeTween = null; resetStagger(); if ( done ) done(); };
		if ( ! targets.length ) { // nothing to cascade → just settle the panel
			panel.style.opacity = '1';
			finish();
			return;
		}
		panel.__aaeStaggerEls = targets;
		const each = Math.min( 0.08, ( duration * 0.9 ) / targets.length );

		if ( hasGsap ) {
			panel.__aaeTween = reveal
				? gsap().fromTo( targets, { opacity: 0, y: 24 },
					{ opacity: 1, y: 0, duration: duration * 0.8, ease, stagger: each, onComplete: finish } )
				: gsap().to( targets,
					{ opacity: 0, y: 16, duration: duration * 0.6, ease, stagger: { each, from: 'end' }, onComplete: finish } );
		} else {
			const per = duration * 0.8;
			targets.forEach( ( t ) => {
				t.style.transition = 'none';
				t.style.opacity   = reveal ? '0' : '1';
				t.style.transform = reveal ? 'translateY(24px)' : 'none';
			} );
			void panel.offsetHeight; // commit the start state
			requestAnimationFrame( () => {
				targets.forEach( ( t, i ) => {
					const order = reveal ? i : ( targets.length - 1 - i );
					const delay = order * each;
					t.style.transition = `opacity ${ per }s ease ${ delay }s, transform ${ per }s ease ${ delay }s`;
					t.style.opacity   = reveal ? '1' : '0';
					t.style.transform = reveal ? 'none' : 'translateY(16px)';
				} );
			} );
			window.setTimeout( finish, ( per + each * targets.length ) * 1000 + 80 );
		}
	};

	// The animation names driven by a cover layer (vs. transform tween / stagger).
	const COVER_REVEALS = [ 'blinds', 'stripes', 'tiles', 'curtain' ];

	const open = () => {
		// Already open → don't re-run (would double-count the scroll-lock).
		if ( root.classList.contains( 'is-open' ) ) {
			return;
		}
		// Remember where focus was so we can return it on close.
		previouslyFocused = document.activeElement;
		window.clearTimeout( closeTimer );
		window.clearTimeout( panelEnterTimer );
		killTween();
		clearPanelBars(); // drop any cover layer left by a killed reveal
		resetStagger();   // and clear any half-applied stagger inline styles
		panel.style.pointerEvents = 'auto';
		showOverlay();
		root.classList.add( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'true' );
		lockScroll(); // scroll-lock while open (shared across drawers)
		panel.addEventListener( 'keydown', trapFocus );

		// The drawer's own entrance. Runs immediately for a plain `fade` backdrop,
		// but is delayed until AFTER a blinds/circle backdrop has fully revealed —
		// so the backdrop effect plays over the whole screen instead of being hidden
		// behind the incoming panel.
		const runPanelEnter = () => {
			panel.style.visibility = 'visible';
			const focusables = getFocusable();
			( focusables[ 0 ] || panel ).focus();

			const from = animFrom( enterAnim, position );
			if ( COVER_REVEALS.includes( enterAnim ) && ! reduce() ) {
				// Cover reveal: panel sits fully in place, cover shapes clear off it.
				panel.style.transform = 'none';
				panel.style.opacity   = '1';
				panel.style.filter    = 'none';
				runCoverReveal( enterAnim, true, null );
			} else if ( enterAnim === 'stagger' && ! reduce() ) {
				// Panel shows in place; its children cascade in.
				panel.style.transform = 'none';
				panel.style.opacity   = '1';
				panel.style.filter    = 'none';
				animateStagger( true, null );
			} else if ( hasGsap && enterAnim === 'circle' && ! reduce() ) {
				// Shape reveal: grow a clip-path circle from the corner (0% → 150%, full).
				panel.style.transform = 'none';
				const o = CIRCLE_ORIGIN[ position ] || CIRCLE_ORIGIN.left;
				panel.__aaeTween = gsap().fromTo( panel,
					{ clipPath: circleClip( '0%', o ), webkitClipPath: circleClip( '0%', o ) },
					{
						clipPath:       circleClip( '150%', o ),
						webkitClipPath: circleClip( '150%', o ),
						duration,
						ease,
						onComplete: () => { panel.__aaeTween = null; clearClip( panel ); },
					}
				);
			} else if ( hasGsap && from && ! reduce() ) {
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

		const lead = overlayRevealMs();
		if ( lead > 0 ) {
			// Keep the panel hidden (and parked off-screen for the CSS-slide path) so
			// it doesn't flash or cover the backdrop reveal during the lead.
			panel.style.visibility = 'hidden';
			if ( ! hasGsap ) {
				panel.style.transition = 'none';
				panel.style.transform  = closedTransform;
				requestAnimationFrame( () => { panel.style.transition = SLIDE; } );
			}
			panelEnterTimer = window.setTimeout( runPanelEnter, lead );
		} else {
			runPanelEnter();
		}
	};

	const finishClose = () => {
		panel.style.visibility = 'hidden';
	};

	const close = () => {
		// Only act on an actually-open drawer, so the scroll-lock count and the
		// focus-return stay balanced with open().
		if ( ! root.classList.contains( 'is-open' ) ) {
			return;
		}
		window.clearTimeout( panelEnterTimer );
		killTween();
		clearPanelBars(); // drop any cover layer left by a killed reveal
		resetStagger();   // and clear any half-applied stagger inline styles
		hideOverlay();
		root.classList.remove( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		unlockScroll();
		panel.style.pointerEvents = 'none';
		panel.removeEventListener( 'keydown', trapFocus );

		// Return focus to wherever it was (normally the trigger) so keyboard
		// users aren't dumped at the top of the page.
		const returnTo = ( previouslyFocused && typeof previouslyFocused.focus === 'function' )
			? previouslyFocused
			: trigger;
		returnTo.focus();
		previouslyFocused = null;

		// `reverse` (default) exits with the enter animation's own closed vars.
		const exitName = exitAnim === 'reverse' ? enterAnim : exitAnim;
		const to       = animFrom( exitName, position );

		if ( COVER_REVEALS.includes( exitName ) && ! reduce() ) {
			// Cover reveal out: sweep the cover back over the panel, then hide it.
			panel.style.transform = 'none';
			runCoverReveal( exitName, false, finishClose );
		} else if ( exitName === 'stagger' && ! reduce() ) {
			// Children cascade out, then hide the panel.
			animateStagger( false, finishClose );
		} else if ( hasGsap && exitName === 'circle' && ! reduce() ) {
			// Shape reveal out: shrink the clip-path circle back to the corner.
			const o = CIRCLE_ORIGIN[ position ] || CIRCLE_ORIGIN.left;
			panel.__aaeTween = gsap().fromTo( panel,
				{ clipPath: circleClip( '150%', o ), webkitClipPath: circleClip( '150%', o ) },
				{
					clipPath:       circleClip( '0%', o ),
					webkitClipPath: circleClip( '0%', o ),
					duration,
					ease,
					onComplete: () => { panel.__aaeTween = null; clearClip( panel ); finishClose(); },
				}
			);
		} else if ( hasGsap && to && ! reduce() ) {
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

const hyphenate = ( s ) => s.replace( /[A-Z]/g, ( m ) => '-' + m.toLowerCase() );

// Inline props the editor reveal sets on the panel — cleared when it re-hides.
const EDITOR_PANEL_RESET = [ 'position', 'z-index', 'top', 'left', 'right', 'bottom', 'width', 'max-width', 'height', 'max-height', 'visibility' ];

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
			const isFloating = 'fixed' === panel.style.position;
			if ( open && ! isFloating ) {
				// Reveal as a FIXED drawer at its edge (out of flow) instead of an
				// in-flow block — an in-flow 300–800px×100vh panel overflows the tiny
				// offcanvas and overlaps the sibling columns / pushes the layout.
				// Floating it keeps the canvas layout intact and mirrors the frontend.
				const position = container.getAttribute( 'data-position' ) || 'left';
				const pos = POS[ position ] || POS.left;
				panel.style.removeProperty( 'display' );
				panel.style.setProperty( 'position', 'fixed', 'important' );
				// 10000, not 9999: Elementor's own `.elementor-element-overlay`
				// (hover/selection layer) sits at 9999, so an equal value ties and
				// the overlay of an element BEHIND the drawer wins on DOM order —
				// it then paints through the panel and steals hover/selection.
				panel.style.setProperty( 'z-index', '10000', 'important' );
				panel.style.setProperty( 'visibility', 'visible', 'important' );
				panel.style.setProperty( 'transform', 'none', 'important' );
				Object.entries( pos ).forEach( ( [ k, v ] ) =>
					panel.style.setProperty( hyphenate( k ), v, 'important' )
				);
			} else if ( ! open && ( isFloating || 'none' !== panel.style.display ) ) {
				EDITOR_PANEL_RESET.forEach( ( p ) => panel.style.removeProperty( p ) );
				panel.style.removeProperty( 'transform' );
				panel.style.setProperty( 'display', 'none', 'important' );
			}
		}

		// Scrim BETWEEN the page and the drawer, exactly as on the frontend.
		// Without it the editor shows the drawer floating over a live canvas: the
		// page behind stays visible AND hoverable, so Elementor's own hover /
		// selection outlines (z-index 9998-9999) bleed through and steal focus.
		// z-index 9999 keeps it under the panel (10000) and over those overlays.
		const scrim = container.querySelector( '[data-e-type="e-aae-a-offcanvas-overlay"]' );
		if ( scrim ) {
			if ( open ) {
				[ [ 'position', 'fixed' ], [ 'inset', '0' ], [ 'z-index', '9999' ],
				  [ 'opacity', '1' ], [ 'visibility', 'visible' ], [ 'pointer-events', 'auto' ] ]
					.forEach( ( [ k, v ] ) => scrim.style.setProperty( k, v, 'important' ) );
			} else {
				[ 'position', 'inset', 'z-index', 'opacity', 'visibility', 'pointer-events' ]
					.forEach( ( p ) => scrim.style.removeProperty( p ) );
			}
		}

		// The panel is never in-flow in the editor now (hidden, or a fixed overlay),
		// so keep the root flat — its `.elementor-empty-view` still matches the core
		// `:has(){min-height:120px}` and would otherwise leave a phantom box.
		if ( '0px' !== container.style.minHeight ) {
			container.style.setProperty( 'min-height', '0', 'important' );
			container.style.setProperty( 'min-block-size', '0', 'important' );
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
