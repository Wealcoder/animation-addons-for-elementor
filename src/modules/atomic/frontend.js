import { PRESETS, RESET_TO } from './presets';

const PLAYED_KEY = '__aaeAnimPlayed';
const BOUND_CLASS = 'aae-anim-bound';
const PLAYED_CLASS = 'aae-anim-played';

function getGsap() {
	return typeof window !== 'undefined' ? window.gsap : null;
}

function getScrollTrigger() {
	return typeof window !== 'undefined' ? window.ScrollTrigger : null;
}

function readConfig( el ) {
	const effect = el.dataset.aaeAnim;
	if ( ! effect || 'none' === effect || ! PRESETS[ effect ] ) {
		return null;
	}

	return {
		effect,
		trigger:  el.dataset.aaeTrigger  || 'in-view',
		duration: ( parseInt( el.dataset.aaeDuration, 10 ) || 600 ) / 1000,
		delay:    ( parseInt( el.dataset.aaeDelay, 10 )    || 0 )   / 1000,
		easing:   el.dataset.aaeEasing   || 'power2.out',
		repeat:   parseInt( el.dataset.aaeRepeat, 10 ) || 0,
	};
}

function play( el, config ) {
	const gsap = getGsap();
	if ( ! gsap ) {
		return;
	}

	if ( el[ PLAYED_KEY ] ) {
		el[ PLAYED_KEY ].kill();
	}

	const preset = PRESETS[ config.effect ];

	el[ PLAYED_KEY ] = gsap.fromTo( el, preset.from, {
		...RESET_TO,
		duration:   config.duration,
		delay:      config.delay,
		ease:       config.easing,
		repeat:     config.repeat,
		yoyo:       config.repeat !== 0,
		clearProps: 'all',
	} );

	el.classList.add( PLAYED_CLASS );
}

function bind( el ) {
	const gsap = getGsap();
	if ( ! gsap ) {
		return;
	}

	const config = readConfig( el );
	if ( ! config ) {
		return;
	}

	if ( 'page-load' === config.trigger ) {
		play( el, config );
		return;
	}

	const ScrollTrigger = getScrollTrigger();

	if ( 'scroll-progress' === config.trigger && ScrollTrigger ) {
		const preset = PRESETS[ config.effect ];
		gsap.fromTo( el, preset.from, {
			...RESET_TO,
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start:   'top bottom',
				end:     'bottom top',
				scrub:   true,
			},
		} );
		return;
	}

	const observer = new IntersectionObserver( ( entries ) => {
		entries.forEach( ( entry ) => {
			if ( ! entry.isIntersecting ) {
				return;
			}
			play( entry.target, config );
			observer.unobserve( entry.target );
		} );
	}, { threshold: 0.15 } );

	observer.observe( el );
}

export function scan( root ) {
	const scope = root && root.querySelectorAll ? root : document;
	scope.querySelectorAll( `[data-aae-anim]:not(.${ BOUND_CLASS })` ).forEach( ( el ) => {
		el.classList.add( BOUND_CLASS );
		bind( el );
	} );
}

export function rebind( el ) {
	if ( ! el ) {
		return;
	}
	el.classList.remove( BOUND_CLASS, PLAYED_CLASS );
	if ( el[ PLAYED_KEY ] ) {
		el[ PLAYED_KEY ].kill();
		delete el[ PLAYED_KEY ];
	}
	bind( el );
}

function init() {
	const gsap = getGsap();
	if ( ! gsap ) {
		return;
	}

	const ScrollTrigger = getScrollTrigger();
	if ( ScrollTrigger && gsap.registerPlugin ) {
		gsap.registerPlugin( ScrollTrigger );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', () => scan() );
	} else {
		scan();
	}

	window.addEventListener( 'elementor/element/render', ( event ) => {
		const el = event.detail && event.detail.element;
		if ( el && el.matches && el.matches( '[data-aae-anim]' ) ) {
			el.classList.add( BOUND_CLASS );
			rebind( el );
		}
		scan( el );
	} );

	window.addEventListener( 'elementor/frontend/init', () => {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', ( $scope ) => {
				scan( $scope && $scope[ 0 ] );
			} );
		}
	} );

	window.aaeAtomicAnimations = { scan, rebind };
}

init();
