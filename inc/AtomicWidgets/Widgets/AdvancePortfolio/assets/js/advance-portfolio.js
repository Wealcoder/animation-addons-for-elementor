/**
 * AAE Advanced Portfolio — atomic (v4) frontend runtime.
 *
 * Build target: ../../../../../assets/atomic/js/advance-portfolio.js
 *
 * A direct port of animate_portfolio_content_three() from the Pro v3 bundle
 * (animation-addons-for-elementor-pro/src/js/advanced-portfolio-skins.js), kept
 * deliberately close to it: same `data-animation-settings` attribute, same JSON
 * keys, same ScrollTrigger start/end values, same pre-pose. `scrub` is the one
 * deliberate deviation — see SCRUB below. The v3
 * version is jQuery-based and hangs off `elementor/frontend/init` with a widget
 * scope; this one is a v4 frontend handler over the element root, so the only
 * real changes are the DOM lookups.
 *
 * What the animation is:
 *   - every `.item` starts pushed back and flat on its face
 *     (scale .5, opacity .7, perspective(4000px) rotateX(90deg) — the
 *     perspective via GSAP's `transformPerspective`, see below);
 *   - the `.section-title` is PINNED for the scroll span and scrubbed
 *     1 -> 3 -> 1 in scale, so the heading swells as the grid passes it;
 *   - each `.item` then rights itself on its own trigger as it comes up the
 *     viewport (scale 1, opacity 1, rotateX 0).
 *
 * GSAP and ScrollTrigger are Pro-only handles, declared as script_deps only
 * when the Pro plugin is active (see class-atomic.php). This file therefore
 * has to no-op cleanly without them rather than assume they exist — the free
 * plugin can be running on its own, in which case the portfolio still renders,
 * just without the scroll animation.
 */

import { register } from '@elementor/frontend-handlers';

const ATTR = 'data-animation-settings';

/**
 * How the tweens follow the scrollbar.
 *
 * `true` means "locked to scroll position": the tween's playhead IS the scroll
 * progress, so the frame on screen is the frame the scrollbar asks for and the
 * motion stops the instant the scroll stops.
 *
 * A NUMBER means a catch-up duration instead — the playhead eases toward the
 * scroll position over that many seconds, so the element keeps moving after the
 * visitor's hand is off the wheel. v3 used 2 for the cards and 1 for the
 * heading, and measured on the reference that is ~1.2s of motion continuing
 * against a completely frozen scrollbar. That is the "it animates by itself"
 * feel, and this is the single value that governs it.
 *
 * One constant for both triggers on purpose: with two different values the
 * heading and the cards drift apart from the scrollbar by different amounts,
 * which is what made the section feel loose rather than driven.
 */
const SCRUB = true;

/** The v3 payload, or null when it is absent/unparseable/disabled. */
function readSettings( root ) {
	const raw = root.getAttribute( ATTR );
	if ( ! raw ) {
		return null;
	}
	try {
		const s = JSON.parse( raw );
		// v3 wrote 'yes' / '' rather than booleans, and the PHP side still does
		// so this comparison stays the same on both sides.
		return 'yes' === s.enable ? s : null;
	} catch ( e ) {
		return null;
	}
}

/**
 * The min-width, in px, of the named Elementor breakpoint — the animation only
 * runs at or above it, matching the v3 `gsap.matchMedia` gate.
 *
 * Returns null when the breakpoint is unset or unknown, meaning "no gate";
 * without that, a renamed/removed breakpoint would silently disable the
 * animation everywhere rather than just skipping the media query.
 */
function breakpointWidth( name ) {
	if ( ! name ) {
		return null;
	}
	try {
		const bps = elementorFrontend.config.responsive.activeBreakpoints;
		return bps && bps[ name ] ? bps[ name ].value : null;
	} catch ( e ) {
		return null;
	}
}

/**
 * The two Elementor duration custom properties that drive its built-in
 * `transition: … transform …` on containers and atomic elements.
 */
const TRANSITION_VARS = [
	'--e-con-transform-transition-duration',
	'--e-transform-transition-duration',
];

/**
 * Suppress Elementor's own transform transition on the given elements, and
 * return the undo.
 *
 * This is NOT cosmetic. Elementor core CSS gives every `.e-con` /
 * `.e-atomic-element` a `transform` transition of 0.4s with no user setting
 * involved, so a scrubbed GSAP tween gets a second, independent 0.4s ease
 * stacked on top of the one the scroll position dictates. The rendered
 * (on-screen) transform then trails the value GSAP wrote, and it keeps easing
 * for 0.4s after the visitor stops scrolling — the animation reads as
 * self-driven rather than scroll-tied.
 *
 * Measured on this page, sampling inline vs. computed rotateX while scrolling:
 *   with the transition     rendered trails by up to 3.27deg (avg 1.64deg)
 *   duration vars zeroed    gap is exactly 0.00deg
 * The v3 reference has no such transition at all — its `.item` is a bare
 * `<article class="item">` and computes `transition-duration: 0s` — which is
 * why the same GSAP code feels tight there and mushy here.
 *
 * Only the two duration vars are zeroed, never `transition` itself: the same
 * declaration also carries background / border / box-shadow, and those should
 * keep working. Doing it here rather than in the stylesheet also means an item
 * whose animation never runs (disabled, or below the breakpoint) keeps its
 * hover transform transition.
 */
function suppressTransformTransition( elements ) {
	const touched = elements.filter( Boolean );

	touched.forEach( ( el ) => {
		TRANSITION_VARS.forEach( ( name ) => el.style.setProperty( name, '0s' ) );
	} );

	return () => {
		touched.forEach( ( el ) => {
			TRANSITION_VARS.forEach( ( name ) => el.style.removeProperty( name ) );
		} );
	};
}

function animatePortfolioThree( root, settings ) {
	const gsap = window.gsap;
	const title = root.querySelector( '.section-title' );
	const items = Array.from( root.querySelectorAll( '.item' ) );

	if ( ! items.length ) {
		return () => {};
	}

	// Pre-pose. These MUST be GSAP's own transform properties, not a raw
	// `transform` string: GSAP composes the string itself, in its own order
	// (perspective, translate, rotate, skew, scale), and simultaneously blanks
	// the standalone `scale`/`translate`/`rotate` CSS properties so they cannot
	// fight what it writes. Handing it `transform: 'perspective(4000px)
	// rotateX(90deg)'` alongside a `scale` — which the first version did,
	// mirroring v3's jQuery .css() call too literally — makes GSAP parse and
	// re-emit that string, and the element does not land on the reference
	// output.
	//
	// Measured on the live template, this reproduces it byte for byte:
	//   rest  transform: perspective(4000px) rotateX(90deg) scale(0.5, 0.5)
	//   mid   transform: perspective(4000px) translate3d(0px, 0px, 0px)
	//                    rotateX(25.7631deg) scale(0.8569, 0.8569)
	//   done  transform: perspective(4000px)
	// with scale/translate/rotate all reading `none` throughout, and the
	// translate3d appearing only mid-tween (GSAP's force3D:'auto').
	//
	// opacity is 0.7, not 0. v3 wrote 0 in the pre-pose and then immediately
	// overrode it per item with gsap.set(portfolio, { opacity: 0.7 }), so 0.7 is
	// the state that actually renders — starting at 0 would make the cards
	// invisible rather than ghosted.
	// Before the first transform is written, not after: the pre-pose below is a
	// large jump (rotateX 90, scale .5) and with the transition still live the
	// cards would visibly swing into their start pose on load.
	const restoreTransition = suppressTransformTransition( items.concat( title ? [ title ] : [] ) );

	gsap.set( items, {
		// transformPerspective, NOT perspective. GSAP treats them as two
		// different things and only one of them is what the reference output
		// has:
		//   perspective: 4000          -> the CSS `perspective` property, which
		//                                 applies to the element's CHILDREN and
		//                                 leaves its own transform as
		//                                 `rotateX(45deg) scale(...)`
		//   transformPerspective: 4000 -> `perspective(4000px)` INSIDE the
		//                                 element's own transform, i.e.
		//                                 `perspective(4000px) rotateX(45deg) ...`
		// Verified both in the browser side by side. v3 got the second form for
		// free because it wrote the raw CSS transform string and GSAP parsed the
		// perspective out of it into this same slot.
		transformPerspective: 4000,
		rotateX: 90,
		scale: 0.5,
		opacity: 0.7,
		position: 'relative',
	} );

	const made = [];

	// The heading pin + swell. Only worth building when there IS a heading:
	// pinning `null` throws inside ScrollTrigger.
	if ( title ) {
		const tl = gsap.timeline( {
			scrollTrigger: {
				trigger: root,
				start: settings.pin_area_start || 'top top',
				end: settings.pin_area_end || 'bottom bottom',
				pin: title,
				pinSpacing: false,
				scrub: SCRUB,
				markers: false,
			},
		} );
		tl.to( title, { scale: 3, duration: 1 } );
		tl.to( title, { scale: 1, duration: 1 }, '+=2' );
		made.push( tl );
	}

	// Each card rights itself on its own trigger.
	items.forEach( ( item ) => {
		const t = gsap.to( item, {
			scrollTrigger: {
				trigger: item,
				start: 'top bottom+=100',
				end: 'bottom center',
				scrub: SCRUB,
				markers: false,
			},
			scale: 1,
			opacity: 1,
			rotateX: 0,
		} );
		made.push( t );
	} );

	return () => {
		made.forEach( ( t ) => {
			if ( t.scrollTrigger ) {
				t.scrollTrigger.kill( true );
			}
			t.kill();
		} );
		gsap.set( items.concat( title ? [ title ] : [] ), { clearProps: 'all' } );

		// Deferred one frame on purpose. clearProps strips the transform in this
		// same tick, so handing the transition back now would let the browser
		// animate that removal — the exact 0.4s drift this suppression exists to
		// avoid, played once on teardown (breakpoint crossing, editor re-render).
		requestAnimationFrame( restoreTransition );
	};
}

register( {
	elementType: 'e-aae-a-advance-portfolio',
	id: 'e-aae-a-advance-portfolio-handler',
	callback: ( { element } ) => {
		const root = element;
		if ( ! root ) {
			return;
		}

		const settings = readSettings( root );
		if ( ! settings ) {
			return;
		}

		// No GSAP (free plugin on its own) — leave the grid alone rather than
		// half-applying a pre-pose the timeline would never undo, which would
		// leave every card invisible.
		if ( 'object' !== typeof window.gsap || ! window.ScrollTrigger ) {
			return;
		}
		window.gsap.registerPlugin( window.ScrollTrigger );

		// v3 skipped the animation inside the editor unless "Enable Editor Mode"
		// was on, because a pinned, scrubbed heading fights the canvas. Same
		// here; the class is the one Elementor puts on the edited element.
		const inEditor = root.classList.contains( 'elementor-element-edit-mode' );
		if ( inEditor && 'yes' !== settings.enable_editor ) {
			return;
		}

		const min = breakpointWidth( settings.breakpoint );

		// No gate to apply — run it directly.
		if ( null === min ) {
			return animatePortfolioThree( root, settings );
		}

		// gsap.matchMedia handles teardown/rebuild across the boundary itself,
		// which is why v3 used it rather than a resize listener.
		const mm = window.gsap.matchMedia();
		mm.add( `(min-width: ${ min }px)`, () => animatePortfolioThree( root, settings ) );

		return () => mm.kill();
	},
} );
