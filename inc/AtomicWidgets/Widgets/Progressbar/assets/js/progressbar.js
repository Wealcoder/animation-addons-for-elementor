import { register } from '@elementor/frontend-handlers';
import '../scss/progressbar.scss';

/**
 * Generic marker-class handler for the AAE Progress Bar wrapper — no stored
 * `style` setting to branch on (see class-aae-a-progressbar.php), so unlike
 * ProgressbarMain's handler this one auto-detects the shape from whichever
 * children a preset (or hand-editing) actually supplied:
 *   .progressbar-path  → circle (stroke-dashoffset ring)
 *   .dot                → dot (sequential reveal)
 *   .progressbar-fill  → line (width fill) — the fallback/default look
 */

const DURATION             = 1400;
const CIRCLE_RADIUS        = 40;
const CIRCLE_CIRCUMFERENCE = 2 * Math.PI * CIRCLE_RADIUS; // ≈ 251.327

/**
 * Animate a numeric counter from 0 to `to` over `duration` ms,
 * writing `value + '%'` into `el` on each frame.
 */
function animateCounter( el, to, duration ) {
	const startTime = performance.now();
	( function tick( now ) {
		const progress = Math.min( ( now - startTime ) / duration, 1 );
		el.textContent = Math.round( progress * to ) + '%';
		if ( progress < 1 ) requestAnimationFrame( tick );
	} )( performance.now() );
}

register( {
	elementType: 'e-aae-a-progressbar',
	id:          'e-aae-a-progressbar-handler',
	callback: ( { element } ) => {
		const el = element;
		if ( ! el ) return;

		const pct     = parseFloat( el.dataset.pbPercentage || 50 ) / 100;
		const showPct = el.dataset.pbDisplayPercentage === 'true';
		const pctEl   = showPct ? el.querySelector( '.aae-pb-pct' ) : null;

		// ── Circle ───────────────────────────────────────────────────────────
		const path = el.querySelector( '.progressbar-path' );
		if ( path ) {
			path.style.transition       = 'none';
			path.style.strokeDasharray  = String( CIRCLE_CIRCUMFERENCE );
			path.style.strokeDashoffset = String( CIRCLE_CIRCUMFERENCE );
			requestAnimationFrame( () => {
				path.style.transition = '';
				requestAnimationFrame( () => {
					path.style.strokeDashoffset = String( CIRCLE_CIRCUMFERENCE * ( 1 - pct ) );
				} );
			} );

			if ( pctEl ) animateCounter( pctEl, Math.round( pct * 100 ), DURATION );
			return;
		}

		// ── Dot ──────────────────────────────────────────────────────────────
		// No `.dot.active` CSS rule on purpose: "active" isn't a real pseudo
		// class Elementor's Style tab can target, so instead of hardcoding a
		// fill colour here we borrow whatever native border-color the user
		// already set per dot — the active fill always matches it, and it
		// stays editable from the Style tab like everything else.
		const dots = el.querySelectorAll( '.dot' );
		if ( dots.length ) {
			const active = Math.round( pct * dots.length );
			dots.forEach( ( dot, i ) => {
				setTimeout( () => {
					if ( i < active ) {
						dot.style.backgroundColor = getComputedStyle( dot ).borderColor;
						dot.style.opacity = '1';
					} else {
						dot.style.backgroundColor = '';
						dot.style.opacity = '';
					}
				}, i * 150 );
			} );
			return;
		}

		// ── Line ─────────────────────────────────────────────────────────────
		const fill = el.querySelector( '.progressbar-fill' );
		if ( ! fill ) return;

		// Elementor's frontend CSS shrinks the span to its natural content width, so
		// width:100% computes to the span's own text width rather than the container.
		// translateX(-50%) centres without depending on any percentage-based width.
		// if ( pctEl ) {
		// 	pctEl.style.position  = 'absolute';
		// 	pctEl.style.top       = '0';
		// 	pctEl.style.left      = '50%';
		// 	pctEl.style.transform = 'translateX(-50%)';
		// }

		fill.style.transition = 'none';
		fill.style.width      = '0%';
		requestAnimationFrame( () => {
			fill.style.transition = '';
			requestAnimationFrame( () => {
				fill.style.width = ( pct * 100 ) + '%';
			} );
		} );

		if ( pctEl ) animateCounter( pctEl, Math.round( pct * 100 ), DURATION );
	},
} );
