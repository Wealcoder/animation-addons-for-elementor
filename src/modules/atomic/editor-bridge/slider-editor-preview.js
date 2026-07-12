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
const SLIDER_TYPE = 'e-aae-a-loop-grid-slider';
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
	let maxPeek = 0;
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
			if ( key === 'peek' || key.indexOf( 'peek_' ) === 0 ) {
				const p = parseFloat( cfg[ key ] );
				if ( ! isNaN( p ) && p > maxPeek ) {
					maxPeek = p;
				}
			}
		} );
	} catch ( _e ) { /* config not ready */ }

	// The extra +1 slide exists ONLY so a Peek of the next slide is visible in the
	// preview. With Peek = 0 the user wants exactly slidesPerView slides filling
	// 100% (no sliver), so drop the +1 — otherwise the preview injects a 4th slide
	// for a 3-up slider and it overflows as a misleading sliver.
	const peekSlide = maxPeek > 0 ? 1 : 0;
	const needed = maxSpv > 0 ? Math.ceil( maxSpv ) + peekSlide : MIN_PREVIEW_SLIDES;
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

	const source = real[ 0 ];

	// IDEMPOTENCY GUARD (fixes the hover-blink / churn loop).
	//
	// This function used to clearClones()+re-clone on EVERY call. But scan() is
	// invoked from the MutationObserver, a heartbeat, settings changes AND the
	// rebind we trigger — and every rebuild is a burst of DOM add/removes that the
	// observer sees, re-schedules a scan, and rebuilds again. On hover the runtime
	// nudges inline styles, feeding the same loop → the preview visibly BLINKS and
	// churns thousands of nodes a second (a real leak). The loop-guard alone wasn't
	// enough because clone-only mutations still cost layout + the runtime's own
	// clones slipped through.
	//
	// So: only rebuild when the clone COUNT changed or the authored slide's markup
	// actually changed. A structural signature of the source's HTML + the needed
	// count is cached on the slider; if it matches, we already have correct clones
	// and return WITHOUT touching the DOM — no mutation, no observer feedback, no
	// blink. Real edits (add/remove child, setting change) change the signature and
	// still trigger exactly one rebuild.
	// The signature must be STYLE-IMMUNE: on hover the runtime mutates inline
	// styles/transforms, and if those changed the signature we'd rebuild on every
	// hover frame — the very blink we're killing. So derive it from STRUCTURE only
	// (element count + tag skeleton + visible text), never from style/attributes.
	const structuralSig = ( el ) => {
		let tags = '';
		let textLen = 0;
		const nodes = el.querySelectorAll( '*' );
		nodes.forEach( ( n ) => { tags += n.tagName; } );
		textLen = ( el.textContent || '' ).length;
		return nodes.length + ':' + tags.length + ':' + textLen;
	};
	const existingClones = track.querySelectorAll( '.' + PREVIEW_CLASS ).length;
	const sig = cloneCount + '|' + structuralSig( source );
	if ( slider.__aaePreviewSig === sig && existingClones === cloneCount ) {
		return false; // already correct — do nothing (no DOM churn)
	}
	slider.__aaePreviewSig = sig;

	clearClones( track );
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

/* =====================================================================
 * "Refresh Preview" button.
 *
 * The clones only mirror the authored slide at the moment they were built. When
 * the user edits a slide child's content/settings (Post Terms taxonomy, Post
 * Image Object Fit / Aspect Ratio / Size) the clones can keep stale markup. The
 * button is a pure REFRESH: it re-clones from the slide's CURRENT markup and
 * rebinds the runtime, so every preview slide mirrors the latest edit. It never
 * hides slides, nav, pagination, or breaks the layout.
 * =================================================================== */

const TOGGLE_CLASS = 'aae-lg-edit-toggle';
// Elementor editor accent (its primary-action magenta) — reads as a native
// editor control rather than a loud brand-orange chip. AAE brand orange is used
// as the small logo-mark accent inside the icon instead.
const BG = '#93003c';
const BG_HOVER = '#b30049';
const BRAND = '#FC6848';

// Inline SVG: a small AAE brand-orange rounded mark + a white circular-arrow
// "refresh" glyph. Inline so it renders crisp regardless of icon-font loading
// inside the preview iframe (the wcf-logo font isn't guaranteed there).
const ICON_MARKUP =
	'<span class="aae-lg-icon" style="display:inline-flex;align-items:center;gap:7px;pointer-events:none">' +
		'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="flex:0 0 auto">' +
			'<rect x="2" y="2" width="20" height="20" rx="5" fill="' + BRAND + '"/>' +
			'<path d="M7 8.5h6.2a3.3 3.3 0 1 1-3.2 4.1" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" fill="none"/>' +
			'<path d="M13.6 5.6 14.4 8.6 11.4 9" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" fill="none"/>' +
		'</svg>' +
		'<span style="pointer-events:none">Refresh</span>' +
	'</span>';

/** localStorage key for a wrap's saved button position (keyed by its data-id). */
function posStorageKey( anchor ) {
	const id = anchor.getAttribute( 'data-id' ) || anchor.id || 'default';
	return 'aae_lg_refresh_btn_pos:' + id;
}

/** The saved {left, top} for a wrap's button, or null. */
function loadButtonPosition( anchor ) {
	try {
		const raw = window.localStorage.getItem( posStorageKey( anchor ) );
		if ( ! raw ) return null;
		const pos = JSON.parse( raw );
		if ( pos && typeof pos.left === 'number' && typeof pos.top === 'number' ) {
			return pos;
		}
	} catch ( _e ) { /* storage unavailable / bad JSON — ignore */ }
	return null;
}

// The saved position is now a slider-RELATIVE offset (dx, dy from the slider's
// top-left corner), NOT an absolute page coordinate. Because the button lives in
// <body> but must travel WITH the slider, we store where it sits relative to the
// slider and re-derive its body-absolute position from the slider's live rect on
// every reposition. Default: 10px inside the slider's top-left.
const DEFAULT_OFFSET = { left: 10, top: 10 };

/** The saved slider-relative {left, top} offset, or the default. */
function buttonOffset( slider ) {
	return loadButtonPosition( slider ) || DEFAULT_OFFSET;
}

/**
 * Glue the (body-parented, position:absolute) button to the slider: its page
 * position = the slider's top-left in the document + the saved slider-relative
 * offset. Called on every heartbeat/scan + on scroll/resize, so the button tracks
 * the slider as the layout shifts, without ever being an Elementor-managed node
 * that could get wiped (the blink cause).
 */
function positionSliderToggle( win, slider, btn ) {
	try {
		const doc = win.document;
		const rect = slider.getBoundingClientRect();
		const scrollX = win.pageXOffset || doc.documentElement.scrollLeft || 0;
		const scrollY = win.pageYOffset || doc.documentElement.scrollTop || 0;
		const off = buttonOffset( slider );
		btn.style.left = ( rect.left + scrollX + off.left ) + 'px';
		btn.style.top = ( rect.top + scrollY + off.top ) + 'px';
		btn.style.right = 'auto';
	} catch ( _e ) { /* layout unavailable */ }
}

/** Persist a slider-relative {left, top} offset for this slider's button. */
function saveButtonPosition( anchor, left, top ) {
	try {
		window.localStorage.setItem(
			posStorageKey( anchor ),
			JSON.stringify( { left, top } )
		);
	} catch ( _e ) { /* storage unavailable — ignore */ }
}

/**
 * Make a button draggable within its anchor by top/left, while keeping it
 * clickable. A press that moves more than a few px is a DRAG (repositions the
 * button, persists the new position, and suppresses the click that would
 * follow); a press that barely moves is a CLICK (runs `onClick`).
 */
function makeButtonDraggable( btn, anchor, onClick ) {
	let dragging = false;
	let moved = false;
	let startX = 0;
	let startY = 0;
	let originLeft = 0;
	let originTop = 0;
	let curLeft = 0;
	let curTop = 0;

	const onPointerDown = ( e ) => {
		if ( e.button !== undefined && e.button !== 0 ) return;
		e.stopPropagation();
		e.preventDefault();
		dragging = true;
		moved = false;
		startX = e.clientX;
		startY = e.clientY;
		const cs = btn.ownerDocument.defaultView.getComputedStyle( btn );
		originLeft = parseFloat( cs.left ) || 0;
		originTop = parseFloat( cs.top ) || 0;
		curLeft = originLeft;
		curTop = originTop;
		btn.setPointerCapture?.( e.pointerId );
	};

	const onPointerMove = ( e ) => {
		if ( ! dragging ) return;
		const dx = e.clientX - startX;
		const dy = e.clientY - startY;
		if ( ! moved && Math.abs( dx ) + Math.abs( dy ) > 4 ) {
			moved = true;
			btn.style.cursor = 'grabbing';
		}
		if ( moved ) {
			// Track the live position ourselves — reading getComputedStyle back on
			// pointerup can lag the inline write (it saved the stale 10px default).
			curLeft = originLeft + dx;
			curTop = originTop + dy;
			btn.style.left = curLeft + 'px';
			btn.style.top = curTop + 'px';
			btn.style.right = 'auto';
		}
	};

	const onPointerUp = ( e ) => {
		if ( ! dragging ) return;
		dragging = false;
		btn.style.cursor = 'grab';
		btn.releasePointerCapture?.( e.pointerId );
		e.stopPropagation();
		if ( moved ) {
			// Persist a slider-RELATIVE offset (not the absolute body coords), so the
			// button keeps its position relative to the slider across reloads/reflows.
			// curLeft/curTop are the button's body-absolute page position; subtract the
			// slider's document-space top-left to get the offset.
			try {
				const win = btn.ownerDocument.defaultView;
				const doc = btn.ownerDocument;
				const rect = anchor.getBoundingClientRect();
				const scrollX = win.pageXOffset || doc.documentElement.scrollLeft || 0;
				const scrollY = win.pageYOffset || doc.documentElement.scrollTop || 0;
				const offLeft = curLeft - ( rect.left + scrollX );
				const offTop = curTop - ( rect.top + scrollY );
				saveButtonPosition( anchor, offLeft, offTop );
			} catch ( _e ) {
				saveButtonPosition( anchor, curLeft, curTop ); // fallback
			}
		} else {
			e.preventDefault();
			onClick(); // genuine click → refresh
		}
	};

	btn.addEventListener( 'pointerdown', onPointerDown, true );
	btn.addEventListener( 'pointermove', onPointerMove, true );
	btn.addEventListener( 'pointerup', onPointerUp, true );
	btn.addEventListener( 'click', ( e ) => { e.preventDefault(); e.stopPropagation(); }, true );
	btn.addEventListener( 'mousedown', ( e ) => e.stopPropagation(), true );
}

function ensureSliderToggle( win, slider ) {
	// WHERE the button lives — and why it kept BLINKING before.
	//
	// Earlier it was an absolute child of the slider's PARENT
	// (.elementor-section-wrap). But that element is Elementor-MANAGED: Elementor
	// re-renders it constantly and wipes our button, so the heartbeat kept
	// re-injecting it → the button flashed in and out (the reported blink).
	//
	// Fix: parent the button to the preview <body> — which Elementor never wipes —
	// as `position:absolute`, and keep its screen position in sync with the slider
	// ourselves. <body> is the scroll container, so an absolute child there scrolls
	// WITH the page (unlike position:fixed) yet is never clipped/buried/wiped.
	const doc = win.document;
	const sliderId = slider.getAttribute( 'data-id' ) || slider.id || '';

	// One button per slider; re-query the live DOM (a stale prop could point at a
	// removed node).
	const live = sliderId
		? doc.querySelector( '.' + TOGGLE_CLASS + '[data-aae-for="' + sliderId + '"]' )
		: ( slider.__aaeToggleBtn && slider.__aaeToggleBtn.isConnected ? slider.__aaeToggleBtn : null );
	if ( live && live.isConnected ) {
		slider.__aaeToggleBtn = live;
		positionSliderToggle( win, slider, live ); // keep it glued to the slider
		return;
	}

	const btn = doc.createElement( 'button' );
	btn.type = 'button';
	btn.className = TOGGLE_CLASS;
	btn.innerHTML = ICON_MARKUP;
	btn.setAttribute( 'title', 'Refresh the preview slides with your latest edits — drag to move' );
	// Inert preview chrome — never an editor element.
	btn.setAttribute( 'data-aae-ui', '1' );
	// Owner tag: the plain-grid module shares the class `aae-lg-edit-toggle`. Each
	// module prunes only its own owner's buttons, so the grid module's pruner can't
	// delete this slider button (which used to cause the blink).
	btn.setAttribute( 'data-aae-owner', 'slider' );
	btn.setAttribute( 'contenteditable', 'false' );
	if ( sliderId ) {
		btn.setAttribute( 'data-aae-for', sliderId );
	}
	btn.style.cssText = [
		'position:absolute', 'top:0', 'left:0', 'z-index:2147483000',
		'display:inline-flex', 'align-items:center',
		'padding:6px 14px', 'font:700 13px/1.3 sans-serif', 'color:#fff',
		'background:' + BG, 'border:0', 'border-radius:6px', 'cursor:grab',
		'box-shadow:0 2px 10px rgba(0,0,0,.3)', 'opacity:1', 'pointer-events:auto',
		'user-select:none', 'touch-action:none',
	].join( ';' );
	btn.addEventListener( 'mouseenter', () => { btn.style.background = BG_HOVER; } );
	btn.addEventListener( 'mouseleave', () => { btn.style.background = BG; } );

	makeButtonDraggable( btn, slider, () => {
		// Refresh: FORCE a rebuild from the authored slide's CURRENT markup by
		// clearing the idempotency signature, so a manual refresh always re-clones
		// even if the structural signature looks unchanged, then rebind. (The
		// automatic scan path is idempotent to avoid the hover-blink loop; the
		// button is the explicit "pick up my latest edits now" escape hatch.)
		slider.__aaePreviewSig = null;
		ensurePreviewSlides( win, slider );
		rebind( win, slider );
	} );

	doc.body.appendChild( btn );
	slider.__aaeToggleBtn = btn;
	positionSliderToggle( win, slider, btn ); // initial placement
}

/**
 * Remove any refresh button whose slider is gone from the DOM. The button lives
 * in the slider's PARENT (not inside the slider), so when the user deletes the
 * slider widget the button is orphaned in the parent and would otherwise linger.
 * We match each button back to its slider by `data-aae-for`.
 */
function pruneOrphanToggles( win ) {
	// Only prune buttons THIS module owns (data-aae-owner="slider"). The plain-grid
	// module's buttons share the class but belong to grid wraps — touching them
	// here would wrongly delete them.
	win.document.querySelectorAll( '.' + TOGGLE_CLASS + '[data-aae-owner="slider"]' ).forEach( ( btn ) => {
		const forId = btn.getAttribute( 'data-aae-for' );
		const stillHasSlider = forId
			? win.document.querySelector( SLIDER_SEL + '[data-id="' + forId + '"]' )
			: btn.closest( SLIDER_SEL ); // legacy button with no id — keep only if inside a slider
		if ( stillHasSlider ) {
			btn.__aaeMiss = 0; // slider present — reset the grace counter
			return;
		}
		// Grace period: Elementor briefly detaches/replaces the slider node during a
		// re-render, so a single "missing" scan is NOT a real delete — removing the
		// button on that transient made it flash out (the residual blink). Only prune
		// after the slider has been gone for several consecutive scans.
		btn.__aaeMiss = ( btn.__aaeMiss || 0 ) + 1;
		if ( btn.__aaeMiss >= 5 ) {
			btn.remove();
		}
	} );
}

/**
 * Immediately remove every toggle whose slider is gone — NO grace period. Used on
 * a real widget-delete command (a permanent removal), so the button vanishes at
 * once instead of after the grace-guarded heartbeat catches up.
 */
function pruneOrphanTogglesNow( win ) {
	win.document.querySelectorAll( '.' + TOGGLE_CLASS + '[data-aae-owner="slider"]' ).forEach( ( btn ) => {
		const forId = btn.getAttribute( 'data-aae-for' );
		const stillHasSlider = forId
			? win.document.querySelector( SLIDER_SEL + '[data-id="' + forId + '"]' )
			: btn.closest( SLIDER_SEL );
		if ( ! stillHasSlider ) {
			btn.remove();
		}
	} );
}

/** Scan the preview for loop-grid sliders and top them up with preview clones. */
function scan( win ) {
	pruneOrphanToggles( win );
	const sliders = win.document.querySelectorAll( SLIDER_SEL );
	sliders.forEach( ( slider ) => {
		ensureSliderToggle( win, slider );
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
		// A setting just changed (slidesPerView / a child edit) — force one rebuild
		// past the idempotency guard so the change is reflected, then it settles.
		slider.__aaePreviewSig = null;
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

	// Light heartbeat: ensure the toggle button + clones exist even when the
	// observer sees no relevant mutation (e.g. the slider's parent/host wasn't
	// ready on the first scan). This is CHEAP now that ensurePreviewSlides() is
	// idempotent — a matching signature returns instantly with zero DOM churn, so
	// this can't reintroduce the blink loop.
	//
	// This MUST be setInterval, NOT rAF: requestAnimationFrame is paused entirely
	// while the tab/iframe is hidden (backgrounded editor, headless). The button's
	// existence is load-bearing UI that must be maintained regardless of visibility
	// — a rAF heartbeat left the button un-injected whenever the frame wasn't
	// actively painting. The per-tick cost is trivial thanks to the idempotency
	// guard, so setInterval's always-on cadence is the right tradeoff here.
	const heartbeatId = win.setInterval( () => scan( win ), 1500 );
	const stopHeartbeat = () => win.clearInterval( heartbeatId );

	const observer = new win.MutationObserver( ( mutations ) => {
		// Ignore mutations we caused (adding/removing our own clones) to avoid a loop.
		const relevant = mutations.some( ( m ) => {
			if ( m.type !== 'childList' ) {
				return true;
			}
			const touched = [ ...m.addedNodes, ...m.removedNodes ];
			// If every touched node is a clone, skip. This covers BOTH our editor
			// preview clones (PREVIEW_CLASS) AND the runtime's own seamless-loop
			// clones (aae-slide-clone) — the latter get added/removed by the
			// slider's bind()/cleanup during the rebind WE trigger, so counting
			// them as "relevant" would re-schedule a scan and loop indefinitely.
			return ! touched.every(
				( n ) => n.nodeType === 1 && n.classList && (
					n.classList.contains( PREVIEW_CLASS ) ||
					n.classList.contains( 'aae-slide-clone' ) ||
					// Our own injected Edit/Back toggle button — inert UI chrome.
					n.classList.contains( TOGGLE_CLASS )
				)
			);
		} );
		if ( relevant ) {
			schedule();
		}
	} );
	observer.observe( win.document.body, { childList: true, subtree: true } );

	// Setting-change re-sync (the fix for "editing a slide child — e.g. Post Image
	// Object Fit / Aspect Ratio / Image Size — doesn't update the preview clones").
	//
	// Those settings change the real slide's image via an inline-style / attribute
	// edit on the SAME node — an ATTRIBUTE mutation, not a childList one. The DOM
	// observer above only watches childList (watching attributes on the whole
	// preview subtree would fire constantly and fight the runtime's own inline
	// style writes), so it never sees these edits and the clones keep the stale
	// markup. Elementor's command bus, however, fires `document/elements/settings`
	// on every panel change. We hang off it and, when the changed element lives
	// inside a loop-grid slider, re-scan — which re-clones from the now-updated
	// real slide, so the clones pick up the new image settings. Debounced through
	// the same `schedule()` so a burst of edits coalesces and the loop-guard above
	// still applies.
	const $e = window.$e;
	if ( $e?.commands?.on ) {
		// Elementor's command-bus signature is (component, command, args) — the
		// command NAME is the second argument.
		const onCommand = ( _component, command ) => {
			// A widget DELETE is a real, permanent removal (unlike Elementor's
			// transient re-render detaches). Prune orphaned buttons IMMEDIATELY on
			// delete — bypassing the grace period — so the refresh button vanishes the
			// instant its slider is deleted, not several heartbeats later.
			if ( command === 'document/elements/delete' ) {
				pruneOrphanTogglesNow( win );
				return;
			}
			if ( command !== 'document/elements/settings' ) {
				return;
			}
			// The settings command acts on the current selection; if any selected
			// element is inside a loop-grid slider, a re-scan is warranted.
			let inSlider = false;
			try {
				const els = window.elementor?.selection?.getElements?.() || [];
				inSlider = els.some( ( c ) => {
					let node = c;
					while ( node ) {
						const elType = node.model?.get?.( 'elType' ) || node.type;
						if ( elType === SLIDER_TYPE ) {
							return true;
						}
						node = node.parent;
					}
					return false;
				} );
			} catch ( _e ) {
				// Selection unavailable — fall back to re-scanning (cheap, guarded).
				inSlider = true;
			}
			if ( inSlider ) {
				schedule();
			}
		};
		$e.commands.on( 'run:after', onCommand );
		track( () => {
			try {
				$e.commands.off( 'run:after', onCommand );
			} catch ( _e ) { /* command bus gone */ }
		} );
	}

	track( () => {
		try {
			win.clearTimeout( timer );
			stopHeartbeat();
			observer.disconnect();
			// Leave the DOM clean on teardown.
			win.document.querySelectorAll( SLIDER_SEL + ' ' + TRACK_SEL ).forEach( ( t ) => clearClones( t ) );
			// NOTE: we deliberately DO NOT remove the toggle buttons here. This
			// teardown runs on Elementor's frequent document/preview re-inits (not
			// just on unload), and wiping the buttons on every such churn was exactly
			// what made them BLINK (create → ~380ms later teardown removes → heartbeat
			// re-creates → repeat). The buttons are idempotent (one per slider,
			// re-queried live) and get pruned by pruneOrphanToggles() once their
			// slider is genuinely gone, so leaving them is safe and blink-free.
		} catch ( _e ) { /* preview gone */ }
	} );
}
