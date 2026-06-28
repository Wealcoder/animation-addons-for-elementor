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

	container.addEventListener( 'mouseenter', track );
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
 * Ensures .aae-btn-fill-el exists, then tracks mouseenter/leave position so
 * the expanding circle originates from the cursor entry/exit point rather
 * than always from the centre. Idempotent via data-aae-fill-bound flag.
 * ------------------------------------------------------------------------- */
const hoverFillSetup = ( container ) => {
	let fill = container.querySelector( '.aae-btn-fill-el' );
	if ( ! fill ) {
		fill = document.createElement( 'span' );
		fill.classList.add( 'aae-btn-fill-el' );
		container.append( fill );
	}
	if ( container.dataset.aaeFillBound ) return;
	container.dataset.aaeFillBound = '1';
	const updatePos = ( e ) => {
		const rect = container.getBoundingClientRect();
		fill.style.left = ( e.clientX - rect.left ) + 'px';
		fill.style.top  = ( e.clientY - rect.top  ) + 'px';
	};
	container.addEventListener( 'mouseenter', updatePos );
	container.addEventListener( 'mouseleave', updatePos );
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