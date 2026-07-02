/**
 * AAE Pro Button — atomic widget handler (source, ES module style)
 *
 * Build target: ../../../../../../assets/atomic/js/button-pro.js
 *
 * Styles handled here:
 *   4  — Ripple         (rippleEffect    — GSAP quickTo for smooth cursor tracking)
 *   5/6 — Group Swap   (groupSwapSetup  — clone SVG, CSS handles slide in/out)
 *   9/10 — Hover Fill  (hoverFillSetup  — cursor-origin expanding circle)
 *   9/10/11 — Magnetic (magneticSetup   — GSAP cursor-following parallax)
 */

import { register } from '@elementor/frontend-handlers';
import '../scss/button-pro.scss';

/* -------------------------------------------------------------------------
 * Style 4 — Ripple (GSAP-powered)
 * Uses gsap.quickTo() for butter-smooth, lag-free cursor tracking of the
 * ripple origin span; significantly smoother than vanilla JS .style.left/.top.
 * ------------------------------------------------------------------------- */
const rippleEffect = ( container ) => {
	const span = container.querySelector( '.aae-ripple-el' ) ||
	             container.querySelector( 'span:first-child' );

	if ( ! span || typeof gsap === 'undefined' ) return;

	const xTo = gsap.quickTo( span, 'left', { duration: 0.3, ease: 'power2.out' } );
	const yTo = gsap.quickTo( span, 'top',  { duration: 0.3, ease: 'power2.out' } );

	const track = ( e ) => {
		const rect = container.getBoundingClientRect();
		xTo( e.clientX - rect.left );
		yTo( e.clientY - rect.top );
	};

	const onEnter = ( e ) => {
		const rect = container.getBoundingClientRect();
		const x = e.clientX - rect.left;
		const y = e.clientY - rect.top;
		// Diameter must reach the farthest corner from the entry point so the
		// full button is covered regardless of where the cursor enters.
		const maxDist = Math.max(
			Math.hypot( x, y ),
			Math.hypot( rect.width - x, y ),
			Math.hypot( x, rect.height - y ),
			Math.hypot( rect.width - x, rect.height - y )
		);
		container.style.setProperty( '--ripple-size', Math.ceil( maxDist * 2 ) + 'px' );
		xTo( x );
		yTo( y );
	};

	container.addEventListener( 'mouseenter', onEnter );
	container.addEventListener( 'mouseleave', track );
};

/* -------------------------------------------------------------------------
 * Styles 5 & 6 — Group Swap
 * Clones the last .e-svg-base and prepends it so CSS scale3d / margin
 * transitions can animate a "second" icon in and out on hover.
 * Idempotent: removes previous clones before re-running (editor re-renders).
 * ------------------------------------------------------------------------- */
const groupSwapSetup = ( container ) => {
	container.querySelectorAll( '[data-swap-clone]' ).forEach( ( el ) => el.remove() );

	const svgEls = container.querySelectorAll( '.e-svg-base' );
	if ( ! svgEls.length ) return;

	const lastSvg    = svgEls[ svgEls.length - 1 ];
	const clonedSvg  = lastSvg.cloneNode( true );
	clonedSvg.setAttribute( 'data-swap-clone', 'true' );
	clonedSvg.removeAttribute( 'data-interaction-id' );
	container.prepend( clonedSvg );
};

/* -------------------------------------------------------------------------
 * Styles 9 & 10 — Hover Fill from cursor point (Oval / Circle)
 * Places .aae-btn-fill-el inside a dedicated .aae-fill-clip wrapper that
 * owns its own overflow:hidden, so the fill is always clipped to the button
 * bounds regardless of what Elementor does to the container's overflow.
 *
 * Why a clip wrapper is needed:
 *   Elementor's .e-con class sets `overflow: var(--overflow)`. On the
 *   frontend, Elementor's JS applies `style="--overflow:visible"` to
 *   container elements AFTER our callback runs, silently overriding the
 *   inline `overflow:hidden` we set. The clip wrapper is a plain <span>
 *   that Elementor never touches, so its overflow:hidden is permanent.
 *
 * The clip wrapper sits at z-index:-1 inside the button (which has
 * z-index:1 via SCSS, creating a stacking context). This ensures the fill
 * is painted above the button's background colour but below the text/icon.
 * Idempotent via data-aae-fill-bound flag.
 * ------------------------------------------------------------------------- */
const hoverFillSetup = ( container ) => {
	// position:relative is still needed so the clip's inset:0 resolves to
	// the button bounds.
	container.style.position = 'relative';

	// Create (or reuse) the clip wrapper. It covers the button exactly via
	// inset:0 and provides its own overflow:hidden that Elementor cannot
	// override.
	let clip = container.querySelector( ':scope > .aae-fill-clip' );
	if ( ! clip ) {
		clip = document.createElement( 'span' );
		clip.className = 'aae-fill-clip';
		clip.setAttribute( 'aria-hidden', 'true' );
		clip.style.cssText = [
			'position:absolute',
			'inset:0',
			'overflow:hidden',
			'border-radius:inherit',
			'z-index:-1',
			'pointer-events:none',
		].join( ';' ) + ';';
		// Insert as first child so it sits below text/icon siblings in DOM
		// order (the button's z-index:1 stacking context ensures z-index:-1
		// paints it after the background but before normal-flow children).
		container.insertBefore( clip, container.firstChild );
	}

	// Move the fill into the clip wrapper. The Twig template renders the
	// fill directly inside the button; we re-parent it here so it is clipped
	// by the wrapper's overflow:hidden rather than the container's overflow.
	let fill = container.querySelector( '.aae-btn-fill-el' );
	if ( ! fill ) {
		fill = document.createElement( 'span' );
		fill.className = 'aae-btn-fill-el';
	}
	if ( fill.parentElement !== clip ) {
		clip.appendChild( fill );
	}

	if ( container.dataset.aaeFillBound ) return;
	container.dataset.aaeFillBound = '1';

	const onEnter = ( e ) => {
		const rect = container.getBoundingClientRect();
		const x = e.clientX - rect.left;
		const y = e.clientY - rect.top;
		// Diameter must reach the farthest corner so the full button is
		// covered regardless of where the cursor enters.
		const maxDist = Math.max(
			Math.hypot( x, y ),
			Math.hypot( rect.width - x, y ),
			Math.hypot( x, rect.height - y ),
			Math.hypot( rect.width - x, rect.height - y )
		);
		container.style.setProperty( '--fill-size', Math.ceil( maxDist * 2 ) + 'px' );
		fill.style.left = x + 'px';
		fill.style.top  = y + 'px';
	};

	const onLeave = ( e ) => {
		const rect = container.getBoundingClientRect();
		fill.style.left = ( e.clientX - rect.left ) + 'px';
		fill.style.top  = ( e.clientY - rect.top  ) + 'px';
	};

	container.addEventListener( 'mouseenter', onEnter );
	container.addEventListener( 'mouseleave', onLeave );
};

/* -------------------------------------------------------------------------
 * Styles 9 & 10 — Magnetic movement (Oval / Circle)
 * Mirrors the v3 btn-wrapper + btn-item parallax: button physically follows
 * the cursor within its bounds, then snaps back on leave.
 * Requires GSAP (loaded as a dependency). Idempotent via data attr.
 * ------------------------------------------------------------------------- */
const magneticSetup = ( container ) => {
	if ( container.dataset.aaeMagneticBound || typeof gsap === 'undefined' ) return;
	container.dataset.aaeMagneticBound = '1';
	container.addEventListener( 'mousemove', ( e ) => {
		if (
			! container.classList.contains( 'aae-btn-oval' ) &&
			! container.classList.contains( 'aae-btn-circle' ) &&
			! container.classList.contains( 'aae-btn-ellipse' )
		) return;
		const rect = container.getBoundingClientRect();
		gsap.to( container, {
			duration: 0.5,
			x: ( ( e.clientX - rect.left - rect.width  / 2 ) / rect.width  ) * 80,
			y: ( ( e.clientY - rect.top  - rect.height / 2 ) / rect.height ) * 80,
			ease: 'power2.out',
		} );
	} );
	container.addEventListener( 'mouseleave', () => {
		gsap.to( container, { duration: 0.5, x: 0, y: 0, ease: 'power2.out' } );
	} );
};

/* -------------------------------------------------------------------------
 * Register handler with Elementor v2 atomic frontend loader
 * ------------------------------------------------------------------------- */
register( {
	elementType: 'e-aae-a-button-pro',
	id: 'e-aae-a-button-pro-handler',
	callback: ( { element } ) => {
		if ( typeof gsap !== 'undefined' ) {
			gsap.killTweensOf( element );
			gsap.set( element, { clearProps: 'x,y' } );
		}
		if ( element.classList.contains( 'btn-hover' ) ) {
			rippleEffect( element );
		} else if ( element.classList.contains( 'aae-btn-pro-group' ) ) {
			groupSwapSetup( element );
		} else if (
			element.classList.contains( 'aae-btn-oval' ) ||
			element.classList.contains( 'aae-btn-circle' ) ||
			element.classList.contains( 'aae-btn-ellipse' )
		) {
			hoverFillSetup( element );
			magneticSetup( element );
		}
	},
} );