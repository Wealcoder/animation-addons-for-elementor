/* eslint-env browser */

/**
 * Editor-only multi-slide preview for the Loop Grid Slider.
 *
 * On the frontend the Loop Slide Item repeats per queried post, so the track has
 * N real `.aae-a-slide` nodes and the runtime lays them out at slidesPerView /
 * center / coverflow etc. In the EDITOR there is no WP_Query — atomic elements
 * are rendered client-side from their Twig once — so the track has exactly ONE
 * slide, and a 3-up / coverflow / center layout can't be previewed.
 *
 * PHP can't help here: in the editor the element is rendered by the JS canvas
 * (twig_main_template), so the widget's server-side print_content() never runs.
 * So we duplicate the single authored slide CLIENT-SIDE, purely for preview:
 * clone it a few times, strip Elementor ids (no duplicate data-id in the DOM),
 * mark the clones aria-hidden + `.aae-slide-editor-preview`, and ask the shared
 * slider runtime to re-bind so it measures the now-multiple slides.
 *
 * The clones are never saved (they live only in the preview DOM) and are
 * regenerated whenever Elementor re-renders the slider. They're also excluded
 * from the runtime's own slide count logic by the `.aae-a-slide` match — but we
 * intentionally KEEP the `aae-a-slide` class so the runtime counts them, since
 * that's the whole point (multi-up preview).
 */

import { track } from './disposables';
import { getPreviewWindow } from './helpers';

const SLIDER_SEL = '.aae-a-loop-grid-slider';
const TRACK_SEL = '.aae-slider-track';
const SLIDE_SEL = '.aae-a-slide';
const PREVIEW_CLASS = 'aae-slide-editor-preview';
const MIN_PREVIEW_SLIDES = 3; // never fewer than this many total (1 real + clones).

/**
 * How many TOTAL preview slides this slider needs. The runtime clamps
 * slidesPerView to the number of slides present, so a 4-up slider with only 3
 * slides in the DOM silently shows 3. Read the configured slidesPerView (across
 * every responsive breakpoint, so switching device modes still previews right)
 * and make sure there are at least ceil(maxSpv)+1 slides — the +1 lets a peek of
 * the next slide show. Falls back to MIN_PREVIEW_SLIDES.
 */
function desiredSlideCount( win, slider ) {
	let maxSpv = 0;
	try {
		const id = slider.getAttribute( 'data-id' );
		const cfg = ( win.AAE_INTERACTIONS_NS || {} )[ id ] || {};
		// slidesPerView plus any slidesPerView_<bp> responsive overrides.
		Object.keys( cfg ).forEach( ( key ) => {
			if ( key === 'slidesPerView' || key.indexOf( 'slidesPerView_' ) === 0 ) {
				const v = parseFloat( cfg[ key ] );
				if ( ! isNaN( v ) && v > maxSpv ) {
					maxSpv = v;
				}
			}
		} );
	} catch ( _e ) { /* config not ready */ }

	const needed = maxSpv > 0 ? Math.ceil( maxSpv ) + 1 : MIN_PREVIEW_SLIDES;
	return Math.max( MIN_PREVIEW_SLIDES, needed );
}

/** Remove any previously-injected preview clones from a track. */
function clearClones( track ) {
	track.querySelectorAll( '.' + PREVIEW_CLASS ).forEach( ( n ) => n.remove() );
}

/**
 * Fetch real, VARIED post data for the editor preview (title / image / url),
 * so cloned slides show different actual posts instead of repeating the one
 * authored card (and never the edited page's own title). Uses the editor-only
 * `aae_loop_post_data` endpoint (localized as window.AAE_LOOP_GRID). Cached per
 * slider id + count on the slider node so we don't refetch on every re-scan.
 */
function fetchPreviewPosts( win, slider, count ) {
	const cfg = win.AAE_LOOP_GRID || window.AAE_LOOP_GRID;
	if ( ! cfg || ! cfg.ajaxUrl || ! cfg.nonce ) {
		return Promise.resolve( [] );
	}

	const id = slider.getAttribute( 'data-id' ) || '';
	const cacheKey = id + ':' + count;
	if ( slider._aaePreviewPostsKey === cacheKey && Array.isArray( slider._aaePreviewPosts ) ) {
		return Promise.resolve( slider._aaePreviewPosts );
	}

	const body = new win.FormData();
	body.append( 'action', 'aae_loop_post_data' );
	body.append( 'nonce', cfg.nonce );
	body.append( 'posts_per_page', String( Math.max( 1, count ) ) );

	return win.fetch( cfg.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
		.then( ( r ) => r.json() )
		.then( ( res ) => {
			const posts = ( res && res.success && res.data && Array.isArray( res.data.posts ) ) ? res.data.posts : [];
			slider._aaePreviewPostsKey = cacheKey;
			slider._aaePreviewPosts = posts;
			return posts;
		} )
		.catch( () => [] );
}

/**
 * Rewrite a clone's Post Title text, Post Image src, and any post links to the
 * given real post's data — turning identical clones into a varied real preview.
 * Purely visual; the clone carries no element ids.
 */
function applyPostToClone( clone, post ) {
	if ( ! post ) {
		return;
	}

	// Post Title — replace the visible text (may be wrapped in an <a>).
	clone.querySelectorAll( '[data-e-type="e-aae-a-post-title"], .e-aae-a-post-title-base' ).forEach( ( titleEl ) => {
		const link = titleEl.querySelector( 'a' );
		const target = link || titleEl;
		if ( typeof post.title === 'string' && post.title !== '' ) {
			target.textContent = post.title;
		}
	} );

	// Post Image — swap the src (+ alt).
	if ( typeof post.image === 'string' && post.image !== '' ) {
		clone.querySelectorAll( 'img.aae-a-post-image, .aae-a-post-image img, .aae-a-post-image' ).forEach( ( img ) => {
			if ( img.tagName === 'IMG' ) {
				img.setAttribute( 'src', post.image );
				if ( post.title ) {
					img.setAttribute( 'alt', post.title );
				}
			}
		} );
	}

	// Post links — point wrapper/title anchors at the real permalink.
	if ( typeof post.url === 'string' && post.url !== '' ) {
		clone.querySelectorAll( 'a[href]' ).forEach( ( a ) => a.setAttribute( 'href', post.url ) );
	}
}

/** Strip identifying attributes so a clone can't collide with real elements. */
function sanitizeClone( node ) {
	const strip = ( el ) => {
		el.removeAttribute( 'data-id' );
		el.removeAttribute( 'data-element-id' );
		el.removeAttribute( 'data-interaction-id' );
		el.removeAttribute( 'id' );
	};
	strip( node );
	node.querySelectorAll( '[data-id],[data-element-id],[data-interaction-id],[id]' ).forEach( strip );
	node.classList.add( PREVIEW_CLASS );
	node.setAttribute( 'aria-hidden', 'true' );
	// Neutralize editor overlay bits that shouldn't appear on a fake slide.
	node.querySelectorAll( '.elementor-editor-element-settings, .ui-resizable-handle' ).forEach( ( e ) => e.remove() );
	return node;
}

/** Ensure the given slider has PREVIEW_COUNT clones after its single real slide. */
function ensurePreviewSlides( win, slider ) {
	const track = slider.querySelector( TRACK_SEL );
	if ( ! track ) {
		return false;
	}

	const real = Array.from( track.children ).filter(
		( c ) => c.classList && c.classList.contains( 'aae-a-slide' ) && ! c.classList.contains( PREVIEW_CLASS )
	);

	// Only synthesize when there's exactly one authored slide (the editor case).
	// If the user somehow has multiple real slides, leave it alone.
	if ( real.length !== 1 ) {
		// If real slides changed away from 1, drop any stale clones.
		if ( track.querySelector( '.' + PREVIEW_CLASS ) ) {
			clearClones( track );
			return true;
		}
		return false;
	}

	// Clones needed = desired total minus the one real slide, driven by the
	// configured slidesPerView so a 4-up (or higher) slider gets enough slides.
	const cloneCount = Math.max( 0, desiredSlideCount( win, slider ) - 1 );

	// Always rebuild: drop any existing clones and re-clone the authored slide
	// from scratch on every re-render. This is the bulletproof path — when the
	// user adds/removes/edits a child element (e.g. a Post Date button), the
	// clones must mirror the CURRENT markup, and re-cloning guarantees that
	// without any structural-diff bookkeeping. scan() is debounced (200ms) and
	// skips mutations caused only by our own clones, so this doesn't loop.
	clearClones( track );
	const source = real[ 0 ];
	const clones = [];
	for ( let i = 0; i < cloneCount; i++ ) {
		const clone = sanitizeClone( source.cloneNode( true ) );
		track.appendChild( clone );
		clones.push( clone );
	}

	// Async: fetch real varied posts and stamp each clone with a different one, so
	// preview slides show actual content (not the repeated authored card / page
	// title). We fetch cloneCount+1 so we can also fix the FIRST (real) slide's
	// title if it fell back to the edited page's title. Layout already happened
	// with the clones present, so this is a pure content swap.
	fetchPreviewPosts( win, slider, cloneCount + 1 ).then( ( posts ) => {
		if ( ! posts.length ) {
			return;
		}
		// Fix the FIRST (real) slide's title when it fell back to the edited PAGE's
		// title (the reported bug — happens when the server-side sample-post lookup
		// returns nothing). Detect by comparing to document.title; if it matches,
		// stamp the real post so the user never sees the page title in a slide.
		const pageTitle = ( win.document.title || '' ).split( '–' )[ 0 ].trim();
		const realTitleEl = real[ 0 ].querySelector( '[data-e-type="e-aae-a-post-title"], .e-aae-a-post-title-base' );
		if ( realTitleEl ) {
			const cur = realTitleEl.textContent.trim();
			if ( cur && pageTitle && cur === pageTitle ) {
				applyPostToClone( real[ 0 ], posts[ 0 ] );
			}
		}

		// Each clone gets a DIFFERENT real post (offset by 1 so it differs from the
		// real slide's post[0]).
		clones.forEach( ( clone, i ) => {
			applyPostToClone( clone, posts[ ( i + 1 ) % posts.length ] );
		} );
	} );

	return true;
}

/** Rebind the shared slider runtime for one slider node. */
function rebind( win, slider ) {
	const api = win.AAEADDON || win.aaeAtomicAnimations;
	if ( api && typeof api.rebind === 'function' ) {
		try {
			api.rebind( slider );
		} catch ( _e ) { /* never break the editor */ }
	}
}

/** Scan the preview for loop-grid sliders and top them up with preview clones. */
function scan( win ) {
	const sliders = win.document.querySelectorAll( SLIDER_SEL );
	sliders.forEach( ( slider ) => {
		const changed = ensurePreviewSlides( win, slider );
		if ( changed ) {
			rebind( win, slider );
		}
	} );
}

/**
 * Top up the preview clones for ONE slider node WITHOUT rebinding — for callers
 * (settings-bridge) that update the config then rebind themselves. Called when a
 * setting like slidesPerView changes: the clone count is config-driven, so a
 * bare config change (no DOM mutation) must still re-sync the slide count before
 * the runtime re-measures. `node` may be the slider or any element inside it.
 */
export function syncSliderPreviewForElement( win, node ) {
	if ( ! win || ! node || ! node.closest ) {
		return;
	}
	const slider = node.classList && node.classList.contains( 'aae-a-loop-grid-slider' )
		? node
		: node.closest( SLIDER_SEL );
	if ( slider ) {
		ensurePreviewSlides( win, slider );
	}
}

/**
 * Install the editor multi-slide preview. Watches the preview DOM (debounced)
 * so it re-synthesizes clones after every Elementor re-render. Idempotent;
 * tracked for teardown on document switch / unload.
 */
export function startSliderEditorPreview() {
	const win = getPreviewWindow();
	if ( ! win || ! win.document || ! win.document.body ) {
		return;
	}

	// Debounced scan so a burst of mutations (re-render, slide edit) coalesces.
	let timer = null;
	const schedule = () => {
		win.clearTimeout( timer );
		timer = win.setTimeout( () => scan( win ), 200 );
	};

	// First pass once layout settles.
	schedule();

	const observer = new win.MutationObserver( ( mutations ) => {
		// Ignore mutations we caused (adding/removing our own clones) to avoid a loop.
		const relevant = mutations.some( ( m ) => {
			if ( m.type !== 'childList' ) {
				return true;
			}
			const touched = [ ...m.addedNodes, ...m.removedNodes ];
			// If every touched node is one of our clones, skip.
			return ! touched.every(
				( n ) => n.nodeType === 1 && n.classList && n.classList.contains( PREVIEW_CLASS )
			);
		} );
		if ( relevant ) {
			schedule();
		}
	} );
	observer.observe( win.document.body, { childList: true, subtree: true } );

	track( () => {
		try {
			win.clearTimeout( timer );
			observer.disconnect();
			// Leave the DOM clean on teardown.
			win.document.querySelectorAll( SLIDER_SEL + ' ' + TRACK_SEL ).forEach( ( t ) => clearClones( t ) );
		} catch ( _e ) { /* preview gone */ }
	} );
}
