import { register } from '@elementor/frontend-handlers';
import '../scss/progressbar.scss';

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

function easeInOutQuad( t ) {
	return t < 0.5 ? 2 * t * t : 1 - Math.pow( -2 * t + 2, 2 ) / 2;
}

function animateWidth( el, toPercent, duration ) {
	el.style.transition = 'none';
	el.style.width       = '0%';
	const startTime = performance.now();
	( function tick( now ) {
		const progress = Math.min( ( now - startTime ) / duration, 1 );
		el.style.width = ( easeInOutQuad( progress ) * toPercent ) + '%';
		if ( progress < 1 ) requestAnimationFrame( tick );
	} )( performance.now() );
}

/** Run the actual reveal — called once the progress bar scrolls into view. */
function playProgressBar( el ) {
	const pct     = parseFloat( el.dataset.pbPercentage || 50 ) / 100;
	const showPct = el.dataset.pbDisplayPercentage === 'true';
	const pctEl   = showPct ? el.querySelector( '.aae-pb-pct' ) : null;

	// ── Circle ───────────────────────────────────────────────────────────
	const path = el.querySelector( '.aae-progressbar-path' );
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
	const dots = el.querySelectorAll( '.aae-progressbar-dot' );
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
	const fill = el.querySelector( '.aae-progressbar-fill' );
	if ( ! fill ) return;

	animateWidth( fill, pct * 100, DURATION );

	if ( pctEl ) animateCounter( pctEl, Math.round( pct * 100 ), DURATION );
}

register( {
	elementType: 'e-aae-a-progressbar',
	id:          'e-aae-a-progressbar-handler',
	callback: ( { element } ) => {
		const el = element;
		if ( ! el ) return;

		// Fire the reveal once the bar actually enters the viewport — by
		// scrolling down to it, or because it's already in view on a fresh
		// page load/refresh (IntersectionObserver reports that immediately
		// on observe(), so both cases are covered by the same code path).
		if ( typeof IntersectionObserver === 'undefined' ) {
			playProgressBar( el );
			return;
		}

		const observer = new IntersectionObserver( ( entries, obs ) => {
			for ( const entry of entries ) {
				if ( entry.isIntersecting ) {
					playProgressBar( el );
					obs.disconnect();
				}
			}
		}, { threshold: 0.3 } );

		observer.observe( el );

		return () => observer.disconnect();
	},
} );
