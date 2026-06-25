/**
 * AAE Pro Button — atomic widget handler (source, ES module style)
 *
 * Build target: ../../../../../../assets/atomic/js/button-pro.js
 *
 * Styles handled here:
 *   1  — Border Divide  (borderDivideSetup — DOM restructure, CSS animates)
 *   3  — Text Flip      (textFlipSetup     — set data-text attr for CSS ::before)
 *   4  — Ripple         (rippleEffect      — GSAP quickTo for smooth cursor tracking)
 *   5/6 — Group Swap    (groupSwapSetup   — clone SVG, CSS handles slide in/out)
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
 * Style 1 — Border Divide
 * Wraps atomic paragraph + SVG children in .text and .icon <span>s so the
 * CSS border-bottom + overflow-hidden icon-slide animation works correctly.
 * Idempotent: removes existing wrappers before re-running.
 * ------------------------------------------------------------------------- */
const borderDivideSetup = ( container ) => {
	// Remove previously injected wrappers
	container.querySelector( ':scope > span.text' )?.remove();
	container.querySelector( ':scope > span.icon' )?.remove();

	const svgEl = container.querySelector(
		'.elementor-widget-e-svg .e-svg-base, :scope > .e-svg-base',
	);
	if ( ! svgEl ) return;

	const textEl = container.querySelector(
		'.elementor-widget-e-paragraph .e-paragraph-base, :scope > .e-paragraph-base',
	);
	if ( ! textEl ) return;

	// Clone for the "entering" duplicate in the icon wrapper
	const clonedSvg = svgEl.cloneNode( true );
	clonedSvg.setAttribute( 'data-swap-clone', 'true' );
	clonedSvg.removeAttribute( 'data-interaction-id' );

	const textWrapper = document.createElement( 'span' );
	textWrapper.classList.add( 'text' );

	const iconWrapper = document.createElement( 'span' );
	iconWrapper.classList.add( 'icon' );

	const clonedText = textEl.cloneNode( true );
	clonedText.removeAttribute( 'draggable' );
	textWrapper.appendChild( clonedText );

	iconWrapper.appendChild( svgEl.cloneNode( true ) );
	iconWrapper.appendChild( clonedSvg );

	container.prepend( iconWrapper );
	container.prepend( textWrapper );
};

/* -------------------------------------------------------------------------
 * Style 3 — Text Flip
 * Copies the paragraph text into data-text on its <span> child so the CSS
 * ::before pseudo-element can mirror it for the rotateX flip.
 * ------------------------------------------------------------------------- */
const textFlipSetup = ( container ) => {
	const spanEl = container.querySelector( '.e-paragraph-base span, :scope > span' );
	if ( ! spanEl ) return;
	if ( ! spanEl.dataset.text ) {
		spanEl.dataset.text = spanEl.textContent.trim();
	}
};

/* -------------------------------------------------------------------------
 * Register handler with Elementor v2 atomic frontend loader
 * ------------------------------------------------------------------------- */
register( {
	elementType: 'e-aae-a-button-pro',
	id: 'e-aae-a-button-pro-handler',
	callback: ( { element } ) => {
		if ( element.classList.contains( 'btn-hover' ) ) {
			rippleEffect( element );
		} else if ( element.classList.contains( 'aae-btn-pro-group' ) ) {
			groupSwapSetup( element );
		} else if ( element.classList.contains( 'btn-border-divide' ) ) {
			borderDivideSetup( element );
		} else if ( element.classList.contains( 'btn-text-flip' ) ) {
			textFlipSetup( element );
		}
	},
} );
