import { register } from '@elementor/frontend-handlers';
import '../scss/progressbar.scss';

register({
	elementType: 'e-aae-a-progressbar',
	id: 'e-aae-a-progressbar-handler',
	callback: ( { element } ) => {
		const el = element;
		if ( ! el ) return;

		const type        = el.dataset.pbType        || 'line';
		const pct         = parseFloat( el.dataset.pbPercentage || 50 ) / 100;
		const color       = el.dataset.pbColor       || '#7DDED8';
		const trailColor  = el.dataset.pbBgColor      || '#eee';
		const strokeWidth = parseFloat( el.dataset.pbStrokeWidth || 2 );
		const trailWidth  = parseFloat( el.dataset.pbTrailWidth  || 1 );
		const showPct     = el.dataset.pbDisplayPercentage === 'true';

		// Dot style: staggered fill animation
		if ( type === 'dot' ) {
			const dots   = el.querySelectorAll( '.dot' );
			const active = Math.round( pct * dots.length );
			dots.forEach( ( dot, i ) => {
				setTimeout( () => dot.classList.toggle( 'active', i < active ), i * 150 );
			} );
			return;
		}

		// Line / Circle: delegate to ProgressBar.js (expected to be a global)
		if ( typeof ProgressBar === 'undefined' ) {
			// eslint-disable-next-line no-console
			console.warn( 'AAE Progressbar: ProgressBar.js library not found.' );
			return;
		}

		const container = el.querySelector( '.progressbar' );
		if ( ! container ) return;

		// Clear any previously-injected SVG so re-runs in the editor don't stack bars.
		container.innerHTML = '';

		const opts = {
			color,
			trailColor,
			strokeWidth,
			trailWidth,
			duration: 1400,
			easing:   'easeInOut',
		};

		if ( showPct ) {
			const circleCenter = type === 'circle'
				? { top: '50%', left: '50%', transform: 'translate(-50%, -50%)', margin: '0', padding: '0' }
				: {};
			opts.text = {
				style: {
					color:    'inherit',
					position: 'absolute',
					...circleCenter,
				},
				autoStyleContainer: false,
			};
			opts.step = ( state, bar ) => {
				bar.setText( Math.round( bar.value() * 100 ) + '%' );
			};
		}

		const bar = type === 'circle'
			? new ProgressBar.Circle( container, opts )
			: new ProgressBar.Line( container, opts );

		bar.animate( pct );
	},
} );
