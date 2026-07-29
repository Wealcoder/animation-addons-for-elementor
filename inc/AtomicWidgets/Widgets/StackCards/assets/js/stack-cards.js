/**
 * AAE Stack Cards — atomic (v4) frontend runtime.
 *
 * The deck root (.aae-a-stack-cards) holds N real card child elements and all
 * config on data-attributes. On the FRONTEND this stacks the cards (absolute,
 * overlapping) inside the deck box and drives a scroll-scrubbed GSAP timeline
 * pinned via ScrollTrigger. In the EDITOR it does nothing — the cards stay a
 * plain, selectable vertical list (styled by editor-only CSS in the Twig).
 *
 * GSAP + ScrollTrigger are provided as globals (registered by the plugin as the
 * script's deps). If they're missing, or reduced-motion is on, the deck degrades
 * to the readable vertical list and never hard-fails.
 */
import { register } from '@elementor/frontend-handlers';

const isEditMode = () =>
	( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode && elementorFrontend.isEditMode() ) ||
	!! ( document.body && document.body.classList.contains( 'elementor-editor-active' ) );

const reduceMotion = () =>
	!! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

// The deck (cards' direct parent) — the sticky viewport keeps it centred.
const getDeck = ( root ) =>
	root.querySelector( ':scope > .aae-a-stack-cards-viewport > .aae-a-stack-cards-deck' )
	|| root.querySelector( '.aae-a-stack-cards-deck' )
	|| root;

const getCards = ( root ) =>
	Array.from( getDeck( root ).querySelectorAll( ':scope > [data-e-type="e-aae-a-stack-card"], :scope > .aae-a-stack-card' ) );

// ── Animation registry ────────────────────────────────────────────────────
// Each entry builds a scrubbed GSAP timeline for one animation. More arrive as
// the other reference effects ship (delivered as presets that set `animation`).
const ANIMATIONS = {
	// 01 — Scroll Stack: cards fly up from below and stack. The card that just
	// arrived is ALWAYS the clean front (highest z, full size, y:0); the ones
	// already stacked shrink a little and slide DOWN-and-behind so their bottom
	// edge peeks out BELOW the front card — a stacked pile — and NONE ever pokes
	// ABOVE the current top card (the oldest was showing above before, which read
	// as "the 1st card appears on top" mid-scroll).
	'scroll-stack': ( gsap, cards, tl ) => {
		const n = cards.length;
		cards.forEach( ( c, i ) => gsap.set( c, { y: i === 0 ? 0 : 800, scale: 1, transformOrigin: 'center center', zIndex: i } ) );
		for ( let i = 1; i < n; i++ ) {
			tl.to( cards[ i ], { y: 0, duration: 1, ease: 'power2.out' }, i * 0.7 );
			for ( let j = 0; j < i; j++ ) {
				tl.to( cards[ j ], { scale: 1 - ( i - j ) * 0.05, y: ( i - j ) * 34, duration: 1, ease: 'power2.out' }, i * 0.7 );
			}
		}
	},
};

const initStackCards = ( root ) => {
	if ( root.dataset.aaeStackInit === 'true' ) {
		return;
	}
	root.dataset.aaeStackInit = 'true';

	// Editor: leave the cards as a flat, selectable list (editor CSS lays them
	// out). The scroll effect is frontend-only.
	if ( isEditMode() ) {
		return;
	}

	const cards = getCards( root );
	if ( cards.length < 2 ) {
		return; // nothing to stack
	}

	const anim = ANIMATIONS[ root.dataset.animation ] || ANIMATIONS[ 'scroll-stack' ];
	const gsap = window.gsap;
	const ST   = window.ScrollTrigger;

	// No GSAP / reduced-motion → keep the readable vertical list, don't stack.
	if ( ! gsap || ! ST || reduceMotion() || ! anim ) {
		return;
	}

	gsap.registerPlugin( ST );

	const scrollLen = parseInt( root.dataset.scrollLength, 10 ) || 100;
	let   scrub     = parseFloat( root.dataset.scrub );
	if ( isNaN( scrub ) ) { scrub = 0.6; }

	// Clear any manual scene height from an earlier build/run — the pin now
	// provides its own scroll room (see below).
	root.style.removeProperty( 'height' );

	// Overlap the cards absolutely inside the deck.
	cards.forEach( ( c ) => {
		Object.assign( c.style, {
			position: 'absolute', left: '0', right: '0', top: '0', bottom: '0',
			margin: '0', willChange: 'transform',
		} );
	} );

	const viewport = root.querySelector( '.aae-a-stack-cards-viewport' ) || root;
	const deckEl   = root.querySelector( '.aae-a-stack-cards-deck' );
	const deckH    = parseInt( root.dataset.deckHeight, 10 ) || 400;

	// WHERE the stack sits in the pinned viewport: 0 = top, 50 = centre,
	// 100 = bottom. Positions the deck at that fraction of the FREE space
	// (viewport height − deck height), so the deck stays FULLY visible at every
	// value. Default 50 = centred.
	let posPct = parseFloat( root.dataset.startOffset );
	if ( isNaN( posPct ) ) { posPct = 50; }
	posPct = Math.max( 0, Math.min( 100, posPct ) );
	if ( viewport && viewport.style ) {
		viewport.style.alignItems = 'flex-start';
	}
	if ( deckEl ) {
		deckEl.style.marginTop = 'calc((100vh - ' + deckH + 'px) * ' + ( posPct / 100 ) + ')';
	}

	// PIN the full-viewport stage while the timeline scrubs across `distance`
	// px of scroll. ScrollTrigger's DEFAULT pinSpacing adds that scroll room as a
	// spacer itself — so we do NOT set the scene height manually. (Mixing a manual
	// height with pinSpacing:false made the pinned deck briefly render in two
	// places when the pin released — the "two decks" glitch.)
	const distance = Math.max( 1, cards.length ) * scrollLen * window.innerHeight / 100;
	const tl = gsap.timeline( {
		scrollTrigger: {
			trigger: viewport,
			start:   'top top',
			end:     '+=' + Math.round( distance ),
			scrub:   scrub,
			pin:     viewport,
			anticipatePin: 1,
		},
	} );

	anim( gsap, cards, tl );

	// Recompute after fonts/images settle so the scrub range is accurate.
	if ( ST && typeof ST.refresh === 'function' ) {
		window.addEventListener( 'load', () => ST.refresh() );
	}
};

// ── Editor: "Edit Cards" switch reconciler ────────────────────────────────
// Mirrors the offcanvas editor reconciler. An atomic Switch doesn't re-render
// the Twig on a live toggle, so we poll the model and toggle a class that the
// Twig's editor CSS reads: ON → show ALL cards (flat, editable); OFF → show only
// the first card. Editor-only; the frontend always stacks every card.
const stackReconcilers = new Map();

const readEditMode = ( id ) => {
	try {
		const w = ( window.parent && window.parent !== window ) ? window.parent : window;
		const c = w.elementor && w.elementor.getContainer ? w.elementor.getContainer( id ) : null;
		let v;
		if ( c && c.settings && c.settings.get ) {
			v = c.settings.get( 'editor_edit_mode' );
		}
		if ( v === undefined && c && c.model && c.model.get ) {
			const s = c.model.get( 'settings' );
			v = s && s.get ? s.get( 'editor_edit_mode' ) : ( s ? s.editor_edit_mode : undefined );
		}
		if ( v === undefined ) return true; // default ON
		return ( v && typeof v === 'object' ) ? !! v.value : !! v;
	} catch ( e ) {
		return true;
	}
};

const initStackCardsEditor = ( root ) => {
	const id = root.getAttribute( 'data-id' );
	if ( ! id ) return;
	const apply = () => {
		if ( ! document.body.contains( root ) ) {
			window.clearInterval( stackReconcilers.get( id ) );
			stackReconcilers.delete( id );
			return;
		}
		// Flatten to CONTENT height for editing. The base CSS gives the viewport
		// `height:100vh` (needed to centre the pinned deck on the frontend), which
		// would otherwise reserve a full screen of empty space on the canvas.
		// Inline `!important` beats the base atomic CSS reliably — no dependency on
		// an editor body-class matching. Re-asserted each tick (Elementor repaints
		// the widget on any settings change).
		root.style.setProperty( 'height', 'auto', 'important' );
		root.style.setProperty( 'min-height', '0', 'important' );
		// In edit-mode Elementor mounts the real card elements as DIRECT children of
		// the root, NOT inside the Twig viewport/deck wrappers — so those wrappers
		// render EMPTY yet still reserve the deck's 100vh/height, ballooning the
		// element on the canvas. Hide the empty wrapper; the cards then flow as a
		// plain, selectable list directly under the root (content height only).
		const viewport = root.querySelector( '.aae-a-stack-cards-viewport' );
		if ( viewport ) {
			viewport.style.setProperty( 'display', 'none', 'important' );
		}
		// `aae-stack-collapsed` on the root → editor CSS hides all but the first card.
		root.classList.toggle( 'aae-stack-collapsed', ! readEditMode( id ) );
	};
	if ( stackReconcilers.has( id ) ) {
		window.clearInterval( stackReconcilers.get( id ) );
	}
	apply();
	stackReconcilers.set( id, window.setInterval( apply, 300 ) );
};

register( {
	elementType: 'e-aae-a-stack-cards',
	id: 'aae-a-stack-cards-handler',
	callback: ( { element } ) => {
		if ( isEditMode() ) {
			initStackCardsEditor( element );
		} else {
			initStackCards( element );
		}
	},
} );
