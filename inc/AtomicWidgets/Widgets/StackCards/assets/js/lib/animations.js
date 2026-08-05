/**
 * AAE Stack Cards — the animation registry.
 *
 * Each entry builds a scrubbed GSAP timeline for one deck animation:
 *   ( gsap, cards, tl, cfg ) => void
 *
 * The timeline is deliberately ScrollTrigger-AGNOSTIC — the caller attaches the
 * trigger (frontend) or drives `tl.progress()` by hand (editor preview). That is
 * what lets the editor preview run the exact same code as the live page.
 *
 * Only GSAP core is used. No plugin may be referenced here: `gsap` and
 * `ScrollTrigger` are the only handles registered unconditionally, and an
 * undeclared dep makes WordPress silently drop the whole bundle.
 *
 * NOTE: this folder is `assets/js/lib/`, one level below the `assets/js/*.js`
 * webpack scans for entry points — so this file is bundled INTO stack-cards.js
 * rather than emitted as its own (broken, handler-less) script.
 */

// Unit vector the cards travel ALONG. `up` = rises into view, so it starts
// below (+y); the pile then settles back the same way it came.
const AXIS = {
	up:    { x:  0, y:  1 },
	down:  { x:  0, y: -1 },
	left:  { x:  1, y:  0 },
	right: { x: -1, y:  0 },
};

// clip-reveal wipe origins, matched to the travel direction.
const FULL_CLIP  = 'inset(0% 0% 0% 0%)';
const CLIP_START = {
	up:    'inset(100% 0% 0% 0%)',
	down:  'inset(0% 0% 100% 0%)',
	left:  'inset(0% 0% 0% 100%)',
	right: 'inset(0% 100% 0% 0%)',
};

const axis     = ( cfg ) => AXIS[ cfg.direction ] || AXIS.up;
// `depth` = how many cards now sit in front of this one.
const scaleFor = ( depth, cfg ) => Math.max( 0.1, 1 - ( depth * cfg.scaleStep ) / 100 );
const fadeFor  = ( depth, cfg ) => Math.max( 0, 1 - ( depth * cfg.fadeBack ) / 100 );

// The resting pose of a card that has been buried `depth` deep in the pile.
const buried = ( depth, cfg ) => {
	const v = axis( cfg );
	return {
		x:       v.x * depth * cfg.offsetStep,
		y:       v.y * depth * cfg.offsetStep,
		scale:   scaleFor( depth, cfg ),
		rotate:  depth * cfg.rotateStep,
		opacity: fadeFor( depth, cfg ),
	};
};

// Offscreen start pose for card `i` (card 0 always starts in place).
const offstage = ( i, cfg ) => {
	const v = axis( cfg );
	return i === 0
		? { x: 0, y: 0 }
		: { x: v.x * cfg.travel, y: v.y * cfg.travel };
};

// Re-pose every card already in the pile when card `i` lands.
const settlePile = ( tl, cards, i, cfg, at ) => {
	for ( let j = 0; j < i; j++ ) {
		tl.to( cards[ j ], { ...buried( i - j, cfg ), duration: 1, ease: cfg.ease }, at );
	}
};

export const ANIMATIONS = {
	/**
	 * 01 — Scroll Stack. Cards fly up from below and pile. The card that just
	 * arrived is always the clean front (full size, y:0); the ones already
	 * stacked shrink and slide back so their edges peek out BEHIND the front
	 * card, and none ever pokes past it.
	 */
	'scroll-stack': ( gsap, cards, tl, cfg ) => {
		cards.forEach( ( c, i ) => gsap.set( c, {
			...offstage( i, cfg ), scale: 1, rotate: 0, opacity: 1,
			transformOrigin: 'center center', zIndex: i,
		} ) );
		for ( let i = 1; i < cards.length; i++ ) {
			const at = i * cfg.step;
			tl.to( cards[ i ], { x: 0, y: 0, duration: 1, ease: cfg.ease }, at );
			settlePile( tl, cards, i, cfg, at );
		}
	},

	/**
	 * 02 — Fan Deck. The pile spreads into a hand of cards, pivoting from a
	 * shared bottom origin. `rotateStep` is the angle between neighbours.
	 */
	'fan-deck': ( gsap, cards, tl, cfg ) => {
		const n      = cards.length;
		const spread = cfg.rotateStep || 8;
		cards.forEach( ( c, i ) => gsap.set( c, {
			x: 0, y: 0, rotate: 0, scale: 1, opacity: 1,
			transformOrigin: 'bottom center', zIndex: i,
		} ) );
		cards.forEach( ( c, i ) => {
			const angle = ( i - ( n - 1 ) / 2 ) * spread;
			tl.to( c, {
				rotate: angle, x: angle * ( cfg.offsetStep / 4 ),
				duration: 1, ease: cfg.ease,
			}, i * cfg.step * 0.4 );
		} );
	},

	/**
	 * 03 — Peel Away. The front card slides off against the travel direction
	 * and fades, revealing the next. Stacking order is reversed: card 1 on top.
	 */
	'peel-away': ( gsap, cards, tl, cfg ) => {
		const n = cards.length;
		const v = axis( cfg );
		cards.forEach( ( c, i ) => gsap.set( c, {
			x: 0, y: 0, scale: 1, rotate: 0, opacity: 1,
			transformOrigin: 'center center', zIndex: n - i,
		} ) );
		for ( let i = 0; i < n - 1; i++ ) {
			tl.to( cards[ i ], {
				x: -v.x * cfg.travel, y: -v.y * cfg.travel,
				rotate: -( cfg.rotateStep || 8 ), opacity: 0,
				duration: 1, ease: cfg.ease,
			}, i * cfg.step );
		}
	},

	/**
	 * 04 — Cascade. A staircase: each card lands offset from the last instead
	 * of centring, so the whole deck stays readable at once.
	 */
	'cascade': ( gsap, cards, tl, cfg ) => {
		cards.forEach( ( c, i ) => gsap.set( c, {
			...offstage( i, cfg ), scale: 1, rotate: 0, opacity: 1,
			transformOrigin: 'center center', zIndex: i,
		} ) );
		for ( let i = 1; i < cards.length; i++ ) {
			tl.to( cards[ i ], {
				x: i * cfg.offsetStep, y: i * cfg.offsetStep * 0.6,
				rotate: i * cfg.rotateStep, duration: 1, ease: cfg.ease,
			}, i * cfg.step );
		}
	},

	/**
	 * 05 — Depth Push. Each card arrives from z-back, sharpening as it comes;
	 * the pile behind recedes and blurs. Needs `perspective` on the deck.
	 */
	'depth-push': ( gsap, cards, tl, cfg ) => {
		cards.forEach( ( c, i ) => gsap.set( c, {
			x: 0, y: 0, rotate: 0,
			scale:   i === 0 ? 1 : 0.6,
			opacity: i === 0 ? 1 : 0,
			filter:  i === 0 ? 'blur(0px)' : 'blur(10px)',
			transformOrigin: 'center center', zIndex: i,
		} ) );
		for ( let i = 1; i < cards.length; i++ ) {
			const at = i * cfg.step;
			tl.to( cards[ i ], {
				scale: 1, opacity: 1, filter: 'blur(0px)', duration: 1, ease: cfg.ease,
			}, at );
			for ( let j = 0; j < i; j++ ) {
				const d = i - j;
				tl.to( cards[ j ], {
					scale: scaleFor( d, cfg ), opacity: fadeFor( d, cfg ),
					y: -d * cfg.offsetStep * 0.5,
					filter: `blur(${ Math.min( d * 2, 10 ) }px)`,
					duration: 1, ease: cfg.ease,
				}, at );
			}
		}
	},

	/**
	 * 06 — Card Flip. Each card flips in on Y as the previous flips out. Needs
	 * `perspective` on the deck or it reads as a flat squash.
	 */
	'card-flip': ( gsap, cards, tl, cfg ) => {
		cards.forEach( ( c, i ) => gsap.set( c, {
			x: 0, y: 0, scale: 1,
			rotationY: i === 0 ? 0 : -180,
			opacity:   i === 0 ? 1 : 0,
			transformOrigin: 'center center', backfaceVisibility: 'hidden', zIndex: i,
		} ) );
		for ( let i = 1; i < cards.length; i++ ) {
			const at = i * cfg.step;
			tl.to( cards[ i - 1 ], { rotationY: 180, opacity: 0, duration: 1, ease: cfg.ease }, at );
			tl.to( cards[ i ],     { rotationY: 0,   opacity: 1, duration: 1, ease: cfg.ease }, at );
		}
	},

	/**
	 * 07 — Scatter. Cards fly in from alternating sides with a tilt, then
	 * settle square into the pile.
	 */
	'scatter': ( gsap, cards, tl, cfg ) => {
		cards.forEach( ( c, i ) => {
			const side = i % 2 ? 1 : -1;
			gsap.set( c, {
				x:       i === 0 ? 0 : side * cfg.travel,
				y:       i === 0 ? 0 : cfg.travel * 0.35,
				rotate:  i === 0 ? 0 : side * 25,
				opacity: i === 0 ? 1 : 0,
				scale: 1, transformOrigin: 'center center', zIndex: i,
			} );
		} );
		for ( let i = 1; i < cards.length; i++ ) {
			const at = i * cfg.step;
			tl.to( cards[ i ], { x: 0, y: 0, rotate: 0, opacity: 1, duration: 1, ease: cfg.ease }, at );
			settlePile( tl, cards, i, cfg, at );
		}
	},

	/**
	 * 08 — Slide Over. Each card slides fully over the previous one; only the
	 * card directly underneath reacts. The cleanest, most "app-like" of the set.
	 */
	'slide-over': ( gsap, cards, tl, cfg ) => {
		cards.forEach( ( c, i ) => gsap.set( c, {
			...offstage( i, cfg ), scale: 1, rotate: 0, opacity: 1,
			transformOrigin: 'center center', zIndex: i,
		} ) );
		for ( let i = 1; i < cards.length; i++ ) {
			const at = i * cfg.step;
			tl.to( cards[ i ], { x: 0, y: 0, duration: 1, ease: cfg.ease }, at );
			tl.to( cards[ i - 1 ], {
				scale: scaleFor( 1, cfg ), opacity: fadeFor( 1, cfg ),
				duration: 1, ease: cfg.ease,
			}, at );
		}
	},

	/**
	 * 09 — Rotate Stack. A hand-dropped pile of paper: cards arrive straight
	 * but each one already buried takes an alternating tilt.
	 */
	'rotate-stack': ( gsap, cards, tl, cfg ) => {
		const v    = axis( cfg );
		const tilt = cfg.rotateStep || 4;
		cards.forEach( ( c, i ) => gsap.set( c, {
			...offstage( i, cfg ), scale: 1, opacity: 1,
			rotate: i === 0 ? 0 : ( i % 2 ? tilt * 2 : -tilt * 2 ),
			transformOrigin: 'center center', zIndex: i,
		} ) );
		for ( let i = 1; i < cards.length; i++ ) {
			const at = i * cfg.step;
			tl.to( cards[ i ], { x: 0, y: 0, rotate: 0, duration: 1, ease: cfg.ease }, at );
			for ( let j = 0; j < i; j++ ) {
				const d = i - j;
				tl.to( cards[ j ], {
					rotate: ( j % 2 ? 1 : -1 ) * tilt * d,
					scale:  scaleFor( d, cfg ), opacity: fadeFor( d, cfg ),
					x: v.x * d * cfg.offsetStep * 0.4, y: v.y * d * cfg.offsetStep,
					duration: 1, ease: cfg.ease,
				}, at );
			}
		}
	},

	/**
	 * 10 — Clip Reveal. Nothing moves; each card is wiped into view over the
	 * one below with an animated clip-path. Cheap and very crisp.
	 */
	'clip-reveal': ( gsap, cards, tl, cfg ) => {
		const start = CLIP_START[ cfg.direction ] || CLIP_START.up;
		cards.forEach( ( c, i ) => gsap.set( c, {
			x: 0, y: 0, scale: 1, rotate: 0, opacity: 1,
			clipPath: i === 0 ? FULL_CLIP : start,
			transformOrigin: 'center center', zIndex: i,
		} ) );
		for ( let i = 1; i < cards.length; i++ ) {
			tl.to( cards[ i ], { clipPath: FULL_CLIP, duration: 1, ease: cfg.ease }, i * cfg.step );
		}
	},
};

export const DEFAULT_ANIMATION = 'scroll-stack';

// Eases offered in the panel. Whitelisted because GSAP warns (and the timeline
// silently flattens) on an unknown ease string.
export const SAFE_EASES = [
	'none', 'sine.out', 'power1.out', 'power2.out', 'power3.out', 'power4.out',
	'expo.out', 'circ.out', 'back.out(1.7)', 'elastic.out(1, 0.5)',
	'power2.inOut', 'power3.inOut',
];

/**
 * Read the motion config off the deck's data-attributes. Every default here
 * reproduces the original hardcoded Scroll Stack exactly, so a page saved
 * before these props existed renders identically.
 */
export const readMotionConfig = ( root, deckW, deckH ) => {
	const d   = root.dataset;
	const num = ( raw, fallback ) => {
		const n = parseFloat( raw );
		return isNaN( n ) ? fallback : n;
	};

	return {
		direction:   AXIS[ d.direction ] ? d.direction : 'up',
		offsetStep:  num( d.offsetStep, 34 ),
		scaleStep:   num( d.scaleStep, 5 ),
		rotateStep:  num( d.rotateStep, 0 ),
		fadeBack:    num( d.fadeBack, 0 ),
		// Panel stores overlap as a percentage; the timeline wants time units.
		step:        Math.max( 0.05, num( d.overlap, 70 ) / 100 ),
		ease:        SAFE_EASES.includes( d.ease ) ? d.ease : 'power2.out',
		perspective: num( d.perspective, 1200 ),
		// Far enough that a card is fully clear of the deck box before it flies in.
		travel:      Math.max( 800, deckW + 120, deckH + 120 ),
	};
};
