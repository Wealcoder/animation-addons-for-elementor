import { register } from '@elementor/frontend-handlers';

const UNIT_TYPES = [ 'days', 'hours', 'minutes', 'seconds' ];

const MS_PER_SECOND = 1000;
const MS_PER_MINUTE = 60 * MS_PER_SECOND;
const MS_PER_HOUR   = 60 * MS_PER_MINUTE;
const MS_PER_DAY    = 24 * MS_PER_HOUR;

const pad = ( value ) => String( Math.max( 0, value ) ).padStart( 2, '0' );

const computeFragments = ( distanceMs ) => {
	if ( distanceMs <= 0 ) {
		return { days: 0, hours: 0, minutes: 0, seconds: 0 };
	}
	return {
		days:    Math.floor( distanceMs / MS_PER_DAY ),
		hours:   Math.floor( ( distanceMs % MS_PER_DAY )    / MS_PER_HOUR ),
		minutes: Math.floor( ( distanceMs % MS_PER_HOUR )   / MS_PER_MINUTE ),
		seconds: Math.floor( ( distanceMs % MS_PER_MINUTE ) / MS_PER_SECOND ),
	};
};

const applyLayout = ( container ) => {
	container.style.flexDirection =
		container.dataset.layout === 'vertical' ? 'column' : 'row';
};

// Track active intervals so re-init clears the previous one.
const activeIntervals = new WeakMap();

const initCountdown = ( container ) => {
	// Clear any existing interval from a previous init (e.g. editor re-render).
	if ( activeIntervals.has( container ) ) {
		window.clearInterval( activeIntervals.get( container ) );
		activeIntervals.delete( container );
	}

	applyLayout( container );

	const dueDateRaw = container.dataset.dueDate || '';
	const dueDateMs  = new Date( dueDateRaw.replace( ' ', 'T' ) ).getTime();

	const digitNodes = {};
	UNIT_TYPES.forEach( ( unitType ) => {
		const unit = container.querySelector( `[data-unit-type="${ unitType }"]` );
		digitNodes[ unitType ] = unit
			? unit.querySelector( '.aae-a-countdown-unit-count' )
			: null;
	} );

	const setExpired = ( expired ) => {
		if ( expired ) {
			container.setAttribute( 'data-expired', 'true' );
		} else {
			container.removeAttribute( 'data-expired' );
		}
	};

	const tick = () => {
		if ( ! Number.isFinite( dueDateMs ) ) {
			setExpired( true );
			return;
		}

		const distance  = dueDateMs - Date.now();
		const fragments = computeFragments( distance );

		UNIT_TYPES.forEach( ( unitType ) => {
			const node = digitNodes[ unitType ];
			if ( node ) {
				node.textContent = pad( fragments[ unitType ] );
			}
		} );

		if ( distance <= 0 ) {
			setExpired( true );
			const id = activeIntervals.get( container );
			if ( id ) {
				window.clearInterval( id );
				activeIntervals.delete( container );
			}
		} else {
			setExpired( false );
		}
	};

	tick();

	// In the editor, stop after one tick — the interval would cause DOM
	// mutations every second which trigger Elementor's MutationObserver
	// and cause an infinite re-render loop.
	const isEditMode = typeof elementorFrontend !== 'undefined' &&
		elementorFrontend.isEditMode();

	if ( ! isEditMode ) {
		const intervalId = window.setInterval( tick, MS_PER_SECOND );
		activeIntervals.set( container, intervalId );
	}
};

register( {
	elementType: 'e-aae-a-countdown',
	id: 'e-aae-a-countdown-handler',
	callback: ( { element } ) => {
		initCountdown( element );
	},
} );
