import { register } from '@elementor/frontend-handlers';

/**
 * AAE Atomic Countdown — frontend handler.
 *
 * Reads the due-date from the wrapper, walks each `[data-unit-type]`
 * child unit, and writes the remaining time fragment into that unit's
 * `.aae-a-countdown-unit-count` element every second. When the due
 * date passes, flips `data-expired="true"` on the wrapper so the CSS
 * in the Twig swaps the four units out for the expire-message block.
 *
 * Why direct DOM updates and not re-rendering: the digit element is an
 * `Atomic_Heading` child with its own Style panel — replacing the
 * markup each tick would blow away any user styling that targets the
 * specific element. Updating `textContent` keeps the element identity
 * and its computed styles intact.
 */
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

const initCountdown = ( container ) => {
	if ( container.dataset.aaeCountdownReady === '1' ) return;
	container.dataset.aaeCountdownReady = '1';

	const dueDateRaw = container.dataset.dueDate || '';
	const dueDateMs  = new Date( dueDateRaw.replace( ' ', 'T' ) ).getTime();

	// Cache the digit element for each unit type once — saves a query per tick.
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
		// If due-date couldn't be parsed, show the expire block — better
		// than silently rendering "NaN" in every unit forever.
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
			window.clearInterval( intervalId );
		} else {
			setExpired( false );
		}
	};

	tick();
	const intervalId = window.setInterval( tick, MS_PER_SECOND );
};

register( {
	elementType: 'e-aae-a-countdown',
	id: 'e-aae-a-countdown-handler',
	callback: ( { element } ) => {
		element.removeAttribute( 'data-aae-countdown-ready' );
		initCountdown( element );
	},
} );
