/**
 * AAE Stack Cards — atomic (v4) frontend runtime.
 *
 * The deck root (.aae-a-stack-cards) holds N real card child elements and all
 * config on data-attributes. On the FRONTEND this stacks the cards (absolute,
 * overlapping) inside the deck box and drives a scroll-scrubbed GSAP timeline
 * pinned via ScrollTrigger. In the EDITOR the cards stay a plain, selectable
 * vertical list — until the panel's Preview control asks for the real thing.
 *
 * The animations themselves live in ./lib/animations.js and build a timeline
 * that knows nothing about ScrollTrigger, so the editor preview replays the
 * exact same code with a plain tween instead of a scroll trigger.
 *
 * GSAP + ScrollTrigger are globals (registered as the script's deps). If either
 * is missing, or reduced-motion is on, the deck degrades to the readable
 * vertical list and never hard-fails.
 */
import { register } from '@elementor/frontend-handlers';
import { ANIMATIONS, DEFAULT_ANIMATION, readMotionConfig } from './lib/animations';

const isEditMode = () =>
	( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode && elementorFrontend.isEditMode() ) ||
	!! ( document.body && document.body.classList.contains( 'elementor-editor-active' ) );

const reduceMotion = () =>
	!! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

const CARD_SELECTOR = ':scope > [data-e-type="e-aae-a-stack-card"], :scope > .aae-a-stack-card';

const getViewport = ( root ) => root.querySelector( '.aae-a-stack-cards-viewport' );

// The deck (cards' direct parent) — the sticky viewport keeps it centred.
const getDeck = ( root ) =>
	root.querySelector( ':scope > .aae-a-stack-cards-viewport > .aae-a-stack-cards-deck' )
	|| root.querySelector( '.aae-a-stack-cards-deck' )
	|| root;

// In edit-mode Elementor mounts the cards as DIRECT children of the root rather
// than inside the Twig deck, so fall back to the root when the deck is empty.
const getCards = ( root ) => {
	const inDeck = Array.from( getDeck( root ).querySelectorAll( CARD_SELECTOR ) );
	return inDeck.length ? inDeck : Array.from( root.querySelectorAll( CARD_SELECTOR ) );
};

const getGsap = () => window.gsap || null;
const getST   = () => window.ScrollTrigger || null;

const deckBox = ( root ) => ( {
	w: parseInt( root.dataset.deckWidth, 10 ) || 620,
	h: parseInt( root.dataset.deckHeight, 10 ) || 400,
} );

const animationFor = ( root ) => ANIMATIONS[ root.dataset.animation ] || ANIMATIONS[ DEFAULT_ANIMATION ];

// ── Card staging ──────────────────────────────────────────────────────────
// Overlap the cards absolutely inside the deck. Shared by the frontend scene
// and the editor preview so both animate an identically-posed DOM.
const stageCards = ( root, cards, cfg ) => {
	const deck = getDeck( root );
	if ( deck && deck !== root ) {
		deck.style.perspective   = cfg.perspective + 'px';
		deck.style.transformStyle = 'preserve-3d';
	}
	cards.forEach( ( c ) => {
		Object.assign( c.style, {
			position: 'absolute', left: '0', right: '0', top: '0', bottom: '0',
			margin: '0', willChange: 'transform',
		} );
	} );
};

const unstageCards = ( root, cards ) => {
	const gsap = getGsap();
	if ( gsap ) {
		gsap.set( cards, { clearProps: 'all' } );
	}
	const deck = getDeck( root );
	if ( deck && deck !== root ) {
		deck.style.removeProperty( 'perspective' );
		deck.style.removeProperty( 'transform-style' );
	}
	cards.forEach( ( c ) => {
		[ 'position', 'left', 'right', 'top', 'bottom', 'margin', 'will-change' ]
			.forEach( ( p ) => c.style.removeProperty( p ) );
	} );
};

// ── Progress indicator ────────────────────────────────────────────────────
// The Twig renders an empty host only when the prop is on; the dots depend on
// the card count, which is a runtime fact, so JS fills it.
const buildProgress = ( root, cards, onSeek ) => {
	const host = root.querySelector( '.aae-a-stack-cards-progress' );
	if ( ! host ) {
		return null;
	}
	const style = host.dataset.style || 'dots';
	host.innerHTML = '';

	if ( 'bar' === style ) {
		const fill = document.createElement( 'span' );
		fill.className = 'aae-a-stack-cards-progress-fill';
		host.appendChild( fill );
		return ( p ) => { fill.style.transform = `scaleX(${ p })`; };
	}

	if ( 'counter' === style ) {
		const label = document.createElement( 'span' );
		label.className = 'aae-a-stack-cards-progress-count';
		host.appendChild( label );
		return ( p ) => {
			const i = Math.min( cards.length, Math.round( p * ( cards.length - 1 ) ) + 1 );
			label.textContent = `${ i } / ${ cards.length }`;
		};
	}

	const dots = cards.map( ( _c, i ) => {
		const dot = document.createElement( 'button' );
		dot.type = 'button';
		dot.className = 'aae-a-stack-cards-dot';
		dot.setAttribute( 'aria-label', `Card ${ i + 1 }` );
		dot.addEventListener( 'click', () => onSeek( i ) );
		host.appendChild( dot );
		return dot;
	} );
	return ( p ) => {
		const active = Math.round( p * ( cards.length - 1 ) );
		dots.forEach( ( d, i ) => d.classList.toggle( 'is-active', i === active ) );
	};
};

// ── Frontend: the pinned, scrubbed scene ──────────────────────────────────
const buildScene = ( root ) => {
	const gsap = getGsap();
	const ST   = getST();
	const anim = animationFor( root );
	const cards = getCards( root );
	if ( ! gsap || ! ST || ! anim || cards.length < 2 ) {
		return null;
	}
	gsap.registerPlugin( ST );

	const box = deckBox( root );
	const cfg = readMotionConfig( root, box.w, box.h );

	// Clear any manual scene height from an earlier build — the pin provides
	// its own scroll room via ScrollTrigger's default pinSpacing.
	root.style.removeProperty( 'height' );
	stageCards( root, cards, cfg );

	const viewport = getViewport( root ) || root;
	const deckEl   = getDeck( root );

	// WHERE the stack sits in the pinned viewport: 0 = top, 50 = centre,
	// 100 = bottom, as a fraction of the free space (viewport − deck height)
	// so the deck stays fully visible at every value.
	let posPct = parseFloat( root.dataset.startOffset );
	if ( isNaN( posPct ) ) { posPct = 50; }
	posPct = Math.max( 0, Math.min( 100, posPct ) );
	if ( viewport && viewport.style ) {
		viewport.style.alignItems = 'flex-start';
	}
	if ( deckEl && deckEl !== root ) {
		deckEl.style.marginTop = 'calc((100vh - ' + box.h + 'px) * ' + ( posPct / 100 ) + ')';
	}

	const scrollLen = parseInt( root.dataset.scrollLength, 10 ) || 100;
	let   scrub     = parseFloat( root.dataset.scrub );
	if ( isNaN( scrub ) ) { scrub = 0.6; }

	// `end` MUST be a function: ScrollTrigger re-evaluates function values on
	// every refresh, but bakes a plain '+=N' string once — which left the scrub
	// range describing the pre-resize viewport after any resize or rotation.
	const distance = () => Math.max( 1, cards.length ) * scrollLen * window.innerHeight / 100;

	let setProgress = null;
	const scrollTrigger = {
		id:      'aae-stack-' + ( root.dataset.id || '' ),
		trigger: viewport,
		start:   'top top',
		end:     () => '+=' + Math.round( distance() ),
		scrub,
		pin:     viewport,
		anticipatePin: 1,
		invalidateOnRefresh: true,
		// Default 'fixed' breaks inside a transformed ancestor — the same reason
		// CSS sticky was unusable here. 'transform' is the escape hatch.
		pinType: 'transform' === root.dataset.pinType ? 'transform' : 'fixed',
		markers: 'true' === root.dataset.markers,
		onUpdate: ( self ) => { if ( setProgress ) { setProgress( self.progress ); } },
	};

	if ( 'true' === root.dataset.snapCards && cards.length > 1 ) {
		scrollTrigger.snap = {
			snapTo: 1 / ( cards.length - 1 ),
			duration: { min: 0.1, max: 0.4 },
			delay: 0.05,
			ease: 'power1.inOut',
			directional: true,
		};
	}

	const tl = gsap.timeline( { scrollTrigger } );
	anim( gsap, cards, tl, cfg );

	const st = tl.scrollTrigger;
	setProgress = buildProgress( root, cards, ( index ) => {
		if ( ! st ) { return; }
		const target = st.start + ( index / Math.max( 1, cards.length - 1 ) ) * ( st.end - st.start );
		window.scrollTo( { top: target, behavior: 'smooth' } );
	} );

	// Recompute once layout has settled. `load` may already have fired by the
	// time a frontend handler runs, in which case the listener never would.
	if ( 'complete' === document.readyState ) {
		ST.refresh();
	} else {
		window.addEventListener( 'load', () => ST.refresh(), { once: true } );
	}

	return () => {
		if ( tl.scrollTrigger ) { tl.scrollTrigger.kill( true ); }
		tl.kill();
		unstageCards( root, cards );
		if ( viewport && viewport.style ) { viewport.style.removeProperty( 'align-items' ); }
		if ( deckEl && deckEl !== root ) { deckEl.style.removeProperty( 'margin-top' ); }
		const host = root.querySelector( '.aae-a-stack-cards-progress' );
		if ( host ) { host.innerHTML = ''; }
	};
};

// Mobile fallback: no pin, no stack — each card just fades up on its own as it
// scrolls into view, which is what a narrow screen actually wants.
const buildSimpleFade = ( root ) => {
	const gsap = getGsap();
	const ST   = getST();
	const cards = getCards( root );
	if ( ! gsap || ! ST || ! cards.length ) {
		return null;
	}
	gsap.registerPlugin( ST );
	const tweens = cards.map( ( c ) => gsap.fromTo( c,
		{ y: 40, opacity: 0 },
		{ y: 0, opacity: 1, duration: 0.6, ease: 'power2.out',
			scrollTrigger: { trigger: c, start: 'top 85%', toggleActions: 'play none none reverse' } }
	) );
	return () => tweens.forEach( ( t ) => {
		if ( t.scrollTrigger ) { t.scrollTrigger.kill( true ); }
		t.kill();
		gsap.set( t.targets(), { clearProps: 'all' } );
	} );
};

// ── Frontend entry ────────────────────────────────────────────────────────
const initStackCards = ( root ) => {
	// No GSAP / reduced-motion → keep the readable vertical list, don't stack.
	if ( reduceMotion() ) {
		return () => {};
	}

	const bp = parseInt( root.dataset.mobileBreakpoint, 10 ) || 767;
	const behavior = root.dataset.mobileBehavior || 'stack';
	const mq = window.matchMedia( `(max-width: ${ bp }px)` );

	let teardown = null;
	let current  = null;

	const wanted = () => ( mq.matches && 'stack' !== behavior ) ? behavior : 'stack';

	const sync = () => {
		const want = wanted();
		if ( want === current ) {
			return;
		}
		if ( teardown ) { teardown(); teardown = null; }
		current = want;
		if ( 'list' === want ) {
			return; // plain vertical list, nothing to run
		}
		teardown = 'simple-fade' === want ? buildSimpleFade( root ) : buildScene( root );
	};

	sync();

	// Re-decide when the viewport crosses the breakpoint. ScrollTrigger handles
	// plain resizes itself; this is only for switching MODE.
	const onChange = () => sync();
	if ( mq.addEventListener ) { mq.addEventListener( 'change', onChange ); }
	else if ( mq.addListener ) { mq.addListener( onChange ); }

	return () => {
		if ( mq.removeEventListener ) { mq.removeEventListener( 'change', onChange ); }
		else if ( mq.removeListener ) { mq.removeListener( onChange ); }
		if ( teardown ) { teardown(); teardown = null; }
	};
};

// ── Editor: live preview ──────────────────────────────────────────────────
// The panel's Preview control drives this cross-frame via window.AAEStackCards.
// It builds the SAME timeline the frontend does, minus ScrollTrigger, and
// scrubs it by hand — so what the user previews is what they ship.
const previewSessions = new Map();

const findRoot = ( id ) =>
	document.querySelector( `.aae-a-stack-cards[data-id="${ id }"]` )
	|| document.querySelector( `[data-id="${ id }"]` );

const openPreview = ( id ) => {
	if ( previewSessions.has( id ) ) {
		return previewSessions.get( id );
	}
	const root = findRoot( id );
	const gsap = getGsap();
	const anim = root ? animationFor( root ) : null;
	if ( ! root || ! gsap || ! anim ) {
		return null;
	}
	const cards = getCards( root );
	if ( cards.length < 2 ) {
		return null;
	}

	const box = deckBox( root );
	const cfg = readMotionConfig( root, box.w, box.h );

	// Give the cards a real box to animate in: the editor CSS has flattened the
	// root to content height and hidden the Twig viewport.
	const prev = {
		height: root.style.height, minHeight: root.style.minHeight,
		position: root.style.position,
	};
	root.style.setProperty( 'height', box.h + 'px', 'important' );
	root.style.setProperty( 'min-height', box.h + 'px', 'important' );
	root.style.setProperty( 'position', 'relative', 'important' );
	root.classList.add( 'aae-stack-previewing' );

	stageCards( root, cards, cfg );
	const tl = gsap.timeline( { paused: true } );
	anim( gsap, cards, tl, cfg );

	const session = { root, cards, tl, prev, driver: null };
	previewSessions.set( id, session );
	return session;
};

const closePreview = ( id ) => {
	const s = previewSessions.get( id );
	if ( ! s ) {
		return;
	}
	if ( s.driver ) { s.driver.kill(); }
	s.tl.kill();
	unstageCards( s.root, s.cards );
	s.root.classList.remove( 'aae-stack-previewing' );
	[ 'height', 'min-height', 'position' ].forEach( ( p ) => s.root.style.removeProperty( p ) );
	previewSessions.delete( id );
	// Hand the canvas back to the reconciler, which restores the flat list.
	const reconcile = reconcilers.get( id );
	if ( reconcile ) { reconcile.apply(); }
};

const AAEStackCards = {
	// Play the animation through once, then restore the editing canvas.
	play( id ) {
		const s = openPreview( id );
		if ( ! s ) { return false; }
		if ( s.driver ) { s.driver.kill(); }
		s.tl.progress( 0 );
		s.driver = getGsap().to( s.tl, {
			progress: 1, duration: 2.2, ease: 'none',
			onComplete: () => window.setTimeout( () => closePreview( id ), 450 ),
		} );
		return true;
	},
	// Drag-to-scrub from the panel slider. `pct` is 0–100.
	scrub( id, pct ) {
		const s = openPreview( id );
		if ( ! s ) { return false; }
		if ( s.driver ) { s.driver.kill(); s.driver = null; }
		s.tl.progress( Math.max( 0, Math.min( 100, pct ) ) / 100 );
		return true;
	},
	stop( id ) { closePreview( id ); },
	isPreviewing( id ) { return previewSessions.has( id ); },
};

// ── Editor: "Edit Cards" switch reconciler ────────────────────────────────
// An atomic Switch doesn't re-render the Twig on a live toggle, so poll the
// model and toggle a class the Twig's editor CSS reads: ON → show ALL cards
// (flat, editable); OFF → show only the first. Editor-only.
const reconcilers = new Map();

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
	if ( ! id ) {
		return () => {};
	}

	const apply = () => {
		if ( ! document.body.contains( root ) ) {
			stop();
			return;
		}
		// A preview owns the canvas while it runs — don't fight it every tick.
		if ( previewSessions.has( id ) ) {
			return;
		}
		// Flatten to CONTENT height for editing. The base CSS gives the viewport
		// `height:100vh` (needed to centre the pinned deck on the frontend), which
		// would otherwise reserve a full screen of empty space on the canvas.
		// Inline `!important` beats the base atomic CSS without depending on an
		// editor body-class matching.
		root.style.setProperty( 'height', 'auto', 'important' );
		root.style.setProperty( 'min-height', '0', 'important' );
		// In edit-mode Elementor mounts the cards as DIRECT children of the root,
		// NOT inside the Twig viewport/deck — so those wrappers render empty yet
		// still reserve the deck's 100vh, ballooning the element on the canvas.
		const viewport = getViewport( root );
		if ( viewport ) {
			viewport.style.setProperty( 'display', 'none', 'important' );
		}
		// `aae-stack-collapsed` → editor CSS hides all but the first card.
		root.classList.toggle( 'aae-stack-collapsed', ! readEditMode( id ) );
	};

	const stop = () => {
		const entry = reconcilers.get( id );
		if ( entry ) {
			window.clearInterval( entry.timer );
			reconcilers.delete( id );
		}
	};

	stop();
	apply();
	reconcilers.set( id, { apply, timer: window.setInterval( apply, 300 ) } );

	return () => {
		closePreview( id );
		stop();
	};
};

// Cross-frame handle for the panel control (the panel runs in the top window,
// this runtime inside the preview iframe). Mirrors window.AAEDrawSvg.
if ( typeof window !== 'undefined' ) {
	window.AAEStackCards = AAEStackCards;
}

register( {
	elementType: 'e-aae-a-stack-cards',
	id: 'aae-a-stack-cards-handler',
	// Returning a function registers it as the element's unmount callback —
	// Elementor calls it on re-render and on delete, which is what finally
	// stops orphan ScrollTriggers accumulating against detached DOM.
	callback: ( { element } ) =>
		isEditMode() ? initStackCardsEditor( element ) : initStackCards( element ),
} );
