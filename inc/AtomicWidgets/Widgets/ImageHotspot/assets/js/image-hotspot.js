/* eslint-env browser */

/**
 * AAE Image Hotspot — frontend runtime.
 *
 * Four independent concerns, each reading its own data-attrs off the root
 * (see aae-a-image-hotspot.html.twig / aae-a-hotspot-point.html.twig):
 *   - Auto-numbering  : fills `.hotspot-number` badges from DOM order.
 *   - Tooltip (inline): CSS handles hover/"none" triggers entirely; this file
 *                       only wires the click-to-toggle case.
 *   - Lightbox        : teleports a point's content + a scrim into a shared
 *                       portal on <body>, mirroring offcanvas.js.
 *   - Guided tour      : setInterval-based auto-cycle, mirroring
 *                       nestedslider.js's startAutoplay/stopAutoplay.
 *
 * EDITOR: never teleports or wires interaction (same reasoning as
 * offcanvas.js — moving a node to <body> would break Elementor's selection/
 * editing overlay for it). Only numbering + marker-animation classes run,
 * since a builder needs to see those live, and they're polled on an interval
 * because Elementor repaints children on unrelated settings changes (the
 * exact lesson from Multi-Step Forms' resyncSteps() — computing something
 * once at init silently goes stale after any later panel edit).
 */

import { register } from '@elementor/frontend-handlers';

const ANIM_CLASSES = [ 'anim-beat', 'anim-pulse', 'anim-ripple', 'anim-ring', 'anim-glow', 'anim-bounce' ];

const getPortal = () => {
	let portal = document.querySelector( 'body > .aae-hsp-portal' );
	if ( ! portal ) {
		portal = document.createElement( 'div' );
		portal.className = 'elementor aae-hsp-portal';
		document.body.appendChild( portal );
	}
	return portal;
};

const boolAttr = ( el, name, fallback ) => {
	const v = el.getAttribute( name );
	return v === null ? fallback : v === 'true';
};

const numAttr = ( el, name, fallback ) => {
	const v = parseFloat( el.getAttribute( name ) );
	return Number.isFinite( v ) ? v : fallback;
};

// ── Auto-numbering (feature: number badges) ──────────────────────────────
const renumber = ( root ) => {
	root.querySelectorAll( '.aae-hotspot-point[data-layout="number"] .hotspot-number' )
		.forEach( ( el, index ) => { el.textContent = String( index + 1 ); } );
};

// ── Marker animation class (feature: ripple/ring/glow/bounce + beat/pulse) ─
const applyMarkerAnim = ( root, containerAnim ) => {
	root.querySelectorAll( '.aae-hotspot-point' ).forEach( ( point ) => {
		const marker = point.querySelector( '.aae-hotspot-marker' );
		if ( ! marker ) {
			return;
		}
		const override = point.dataset.aaeHotspotAnim || 'inherit';
		const anim = override !== 'inherit' ? override : containerAnim;
		marker.classList.remove( ...ANIM_CLASSES );
		if ( anim && anim !== 'none' ) {
			marker.classList.add( `anim-${ anim }` );
		}
	} );
};

// ── Inline tooltip (click trigger only — hover/"none" are pure CSS) ──────
const initTooltips = ( root, trigger ) => {
	if ( trigger !== 'click' ) {
		return;
	}
	root.querySelectorAll( '.aae-hotspot-point' ).forEach( ( point ) => {
		if ( point.dataset.aaeHotspotMode !== 'tooltip' ) {
			return;
		}
		const marker = point.querySelector( '.aae-hotspot-marker' );
		const content = point.querySelector( '.aae-hotspot-content' );
		if ( ! marker || ! content ) {
			return;
		}
		marker.addEventListener( 'click', ( ev ) => {
			ev.preventDefault();
			const isOpen = content.classList.contains( 'active' );
			root.querySelectorAll( '.aae-hotspot-content.active' ).forEach( ( c ) => c.classList.remove( 'active' ) );
			if ( ! isOpen ) {
				content.classList.add( 'active' );
			}
		} );
	} );
	document.addEventListener( 'click', ( ev ) => {
		if ( ! root.contains( ev.target ) ) {
			root.querySelectorAll( '.aae-hotspot-content.active' ).forEach( ( c ) => c.classList.remove( 'active' ) );
		}
	} );
};

// ── Lightbox (feature: modal mode) ────────────────────────────────────────
const initLightboxes = ( root ) => {
	const portal = getPortal();
	root.querySelectorAll( '.aae-hotspot-point' ).forEach( ( point ) => {
		if ( point.dataset.aaeHotspotMode !== 'lightbox' || point.dataset.aaeHspLightboxInit === 'true' ) {
			return;
		}
		point.dataset.aaeHspLightboxInit = 'true';

		const marker = point.querySelector( '.aae-hotspot-marker' );
		const content = point.querySelector( '.aae-hotspot-content' );
		const closeBtn = content?.querySelector( '.aae-hotspot-close' );
		if ( ! marker || ! content ) {
			return;
		}

		const scrim = document.createElement( 'div' );
		scrim.className = 'aae-hotspot-scrim';

		// Teleport into the shared portal — same reasoning as offcanvas.js:
		// `position: fixed` must escape any transformed ancestor, and parking
		// under an `.elementor`-classed host keeps the content's atomic base
		// styles matching (they're scoped `.elementor .e-xxx`).
		portal.appendChild( scrim );
		portal.appendChild( content );

		const open = () => {
			scrim.classList.add( 'active' );
			content.classList.add( 'active' );
			marker.setAttribute( 'aria-expanded', 'true' );
			document.body.style.overflow = 'hidden';
		};
		const close = () => {
			scrim.classList.remove( 'active' );
			content.classList.remove( 'active' );
			marker.setAttribute( 'aria-expanded', 'false' );
			document.body.style.overflow = '';
		};

		marker.addEventListener( 'click', ( ev ) => {
			ev.preventDefault();
			open();
		} );
		scrim.addEventListener( 'click', close );
		closeBtn?.addEventListener( 'click', close );
		document.addEventListener( 'keydown', ( ev ) => {
			if ( ev.key === 'Escape' && content.classList.contains( 'active' ) ) {
				close();
			}
		} );
	} );
};

// ── Guided tour (feature: auto-cycle, pause on hover/interaction) ────────
// setInterval-based, modeled on nestedslider.js's own startAutoplay/
// stopAutoplay — not requestAnimationFrame, which that file reserves for a
// different (progress-bar) effect.
const initTour = ( root ) => {
	if ( ! boolAttr( root, 'data-aae-hsp-tour-enabled', false ) ) {
		return;
	}
	const delay = numAttr( root, 'data-aae-hsp-tour-delay', 3000 );
	const pauseOnInteraction = boolAttr( root, 'data-aae-hsp-tour-pause', true );
	const loop = boolAttr( root, 'data-aae-hsp-tour-loop', true );
	const openTooltip = boolAttr( root, 'data-aae-hsp-tour-open', true );

	let index = -1;
	let timer = null;
	let stopped = false;

	const points = () => Array.from( root.querySelectorAll( '.aae-hotspot-point' ) );

	const clearActive = () => {
		points().forEach( ( p ) => {
			p.classList.remove( 'aae-hotspot-tour-active' );
			if ( p.dataset.aaeHotspotMode === 'tooltip' ) {
				p.querySelector( '.aae-hotspot-content' )?.classList.remove( 'active' );
			}
		} );
	};

	const pause = () => {
		window.clearInterval( timer );
		timer = null;
	};

	const stop = () => {
		stopped = true;
		pause();
		clearActive();
	};

	const step = () => {
		const list = points();
		if ( ! list.length ) {
			return;
		}
		index += 1;
		if ( index >= list.length ) {
			if ( ! loop ) {
				stop();
				return;
			}
			index = 0;
		}
		clearActive();
		const point = list[ index ];
		point.classList.add( 'aae-hotspot-tour-active' );
		// Lightboxes are never auto-opened — only highlighted — so the tour
		// can't spam modals; inline tooltips may auto-open per tour_open_tooltip.
		if ( openTooltip && point.dataset.aaeHotspotMode === 'tooltip' ) {
			point.querySelector( '.aae-hotspot-content' )?.classList.add( 'active' );
		}
	};

	const start = () => {
		if ( timer || stopped ) {
			return;
		}
		step();
		timer = window.setInterval( step, delay );
	};

	root.addEventListener( 'mouseenter', pause );
	root.addEventListener( 'mouseleave', () => { if ( ! stopped ) start(); } );
	root.addEventListener( 'focusin', pause );
	root.addEventListener( 'focusout', () => { if ( ! stopped ) start(); } );
	if ( pauseOnInteraction ) {
		root.addEventListener( 'click', stop );
	}

	start();
};

const initImageHotspot = ( root ) => {
	if ( root.dataset.aaeHspInit === 'true' ) {
		return;
	}
	root.dataset.aaeHspInit = 'true';

	const refreshVisuals = () => {
		renumber( root );
		applyMarkerAnim( root, root.dataset.aaeHspMarkerAnim || 'pulse' );
	};

	if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
		refreshVisuals();
		// Elementor repaints child nodes on unrelated settings changes, which
		// would otherwise leave numbering/animation classes stale until reload.
		window.setInterval( refreshVisuals, 1000 );
		return;
	}

	refreshVisuals();
	initTooltips( root, root.dataset.aaeHspTrigger || 'hover' );
	initLightboxes( root );
	initTour( root );
};

register( {
	elementType: 'e-aae-a-image-hotspot',
	id: 'aae-a-image-hotspot-handler',
	callback: ( { element } ) => initImageHotspot( element ),
} );
