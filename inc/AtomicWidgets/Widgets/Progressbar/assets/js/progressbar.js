import { register } from '@elementor/frontend-handlers';
import '../scss/progressbar.scss';

const DURATION           = 1400;
const CIRCLE_RADIUS      = 40;
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

		const type        = el.dataset.pbType    || 'line';
		const pct         = parseFloat( el.dataset.pbPercentage || 50 ) / 100;
		const showPct     = el.dataset.pbDisplayPercentage === 'true';
		const pctEl       = showPct ? el.querySelector( '.aae-pb-pct' ) : null;
		const trackHeight = parseFloat( el.dataset.pbTrackHeight || 8 );
		const strokeWidth = parseFloat( el.dataset.pbStrokeWidth || 10 );

		// Apply configurable values as CSS custom properties.
		el.style.setProperty( '--aae-pb-track-height', trackHeight + 'px' );
		el.style.setProperty( '--aae-pb-stroke-width', String( strokeWidth ) );

		// ── Dot ──────────────────────────────────────────────────────────────
		if ( type === 'dot' ) {
			const dots   = el.querySelectorAll( '.dot' );
			const active = Math.round( pct * dots.length );
			dots.forEach( ( dot, i ) => {
				setTimeout( () => dot.classList.toggle( 'active', i < active ), i * 150 );
			} );
			return;
		}

		// ── Circle ───────────────────────────────────────────────────────────
		if ( type === 'circle' ) {
			const path = el.querySelector( '.progressbar-path' );
			if ( ! path ) return;

			// Reset for clean editor re-runs, then animate.
			path.style.transition      = 'none';
			path.style.strokeDashoffset = CIRCLE_CIRCUMFERENCE;
			requestAnimationFrame( () => {
				path.style.transition      = '';
				requestAnimationFrame( () => {
					path.style.strokeDashoffset = CIRCLE_CIRCUMFERENCE * ( 1 - pct );
				} );
			} );

			if ( pctEl ) animateCounter( pctEl, Math.round( pct * 100 ), DURATION );
			return;
		}

		// ── Line ─────────────────────────────────────────────────────────────
		const fill = el.querySelector( '.progressbar-fill' );
		if ( ! fill ) return;

		// Elementor's frontend CSS shrinks the span to its natural content width, so
		// width:100% computes to the span's own text width rather than the container.
		// translateX(-50%) centres without depending on any percentage-based width.
		if ( pctEl ) {
			pctEl.style.position  = 'absolute';
			pctEl.style.top       = '0';
			pctEl.style.left      = '50%';
			pctEl.style.transform = 'translateX(-50%)';
		}

		// Reset for clean editor re-runs, then animate.
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
