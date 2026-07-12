/* eslint-env browser */

/**
 * Loop Grid — editor "Refresh Preview" button.
 *
 * WHY THIS EXISTS
 * The editor preview clones the single authored loop-item to fake the full grid
 * (see loop-grid.js). Those clones only rebuild when the QUERY changes — so when
 * the user edits a CHILD widget's content or settings (Post Terms taxonomy, Post
 * Image Object Fit / Aspect Ratio / Size, etc.) the query signature is unchanged
 * and the clones keep the stale markup. The reported symptom: "term add korle
 * onno item update hoy na; setting change korle sob update hoy."
 *
 * The button is a pure REFRESH: on click it force-rebuilds the clones from the
 * card's CURRENT markup so every preview item mirrors the latest edit. It never
 * hides items, nav, or pagination and never collapses the grid layout.
 *
 * The button lives INSIDE the preview iframe (that's where the cards render),
 * anchored to each `.aae-a-loop-grid-wrap`. It is inert preview chrome — never an
 * editor element — and is re-injected idempotently as the preview re-renders.
 */

import { state } from './state.js';
import { getPreviewWindow } from './preview.js';
import { forceResyncWrap } from './loop-grid.js';

const WRAP_SELECTOR = '.aae-a-loop-grid-wrap';
const BTN_CLASS = 'aae-lg-edit-toggle';

// Elementor editor accent (its primary-action magenta) — reads as a native
// editor control. AAE brand orange is the small logo-mark accent in the icon.
const BG = '#93003c';
const BG_HOVER = '#b30049';
const BRAND = '#FC6848';

// Inline SVG: small AAE brand-orange rounded mark + white circular-arrow refresh
// glyph + label. Inline so it renders crisp regardless of icon-font loading in
// the preview iframe.
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

/**
 * Where to anchor the button's top-left within its host (the wrap's parent),
 * defaulting to 10px inside the wrap's own top-left corner in the host's
 * coordinate space.
 */
function defaultButtonOffset( wrap, host ) {
	try {
		const w = wrap.getBoundingClientRect();
		const h = host.getBoundingClientRect();
		return { left: ( w.left - h.left ) + 10, top: ( w.top - h.top ) + 10 };
	} catch ( _e ) {
		return { left: 10, top: 10 };
	}
}

/**
 * Place the button (position:absolute within `host` — the wrap's parent). A saved
 * position wins; otherwise anchor 10px inside the wrap's top-left corner. Being
 * absolute in the parent (not fixed, not inside the wrap) means it scrolls WITH
 * the page and travels WITH the wrap, while no ancestor overflow/transform can
 * clip or bury it.
 */
function restoreButtonPosition( btn, wrap, host ) {
	const saved = loadButtonPosition( wrap );
	const pos = saved || defaultButtonOffset( wrap, host );
	btn.style.left = pos.left + 'px';
	btn.style.top = pos.top + 'px';
	btn.style.right = 'auto';
}

/** Persist an explicit {left, top} for this wrap's button. */
function saveButtonPosition( anchor, left, top ) {
	try {
		window.localStorage.setItem(
			posStorageKey( anchor ),
			JSON.stringify( { left, top } )
		);
	} catch ( _e ) { /* storage unavailable — ignore */ }
}

/** Build the refresh button (styled inline so no stylesheet dependency). */
function makeButton( doc ) {
	const btn = doc.createElement( 'button' );
	btn.type = 'button';
	btn.className = BTN_CLASS;
	// Inert preview chrome — must never be treated as an editor element. We mark it
	// `data-aae-ui` (NOT `data-aae-clone`): removeClones() strips [data-aae-clone]
	// nodes, and on the plain grid the grid container IS the wrap, so a clone-marked
	// button would get wiped on every rebuild. loop-grid.js's observer filter treats
	// data-aae-ui as inert too, so our own button never triggers a re-scan.
	btn.setAttribute( 'data-aae-ui', '1' );
	// Owner tag: the plain-grid module and the slider module BOTH use the class
	// `aae-lg-edit-toggle`. Without this marker, this module's pruner would iterate
	// the slider's buttons too, fail to match them to a grid wrap, and DELETE them
	// (~every scan) — the reported button blink. Each module only ever prunes its
	// own owner's buttons.
	btn.setAttribute( 'data-aae-owner', 'grid' );
	btn.setAttribute( 'contenteditable', 'false' );
	btn.innerHTML = ICON_MARKUP;
	btn.setAttribute( 'title', 'Refresh the preview items with your latest edits — drag to move' );
	// ABSOLUTE inside the wrap's PARENT (not fixed, not inside the wrap) so it
	// scrolls with the page and travels with the wrap, while no ancestor
	// overflow/transform can clip it or bury it once dragged past an edge.
	btn.style.cssText = [
		'position:absolute',
		'top:10px',
		'left:10px',
		'z-index:2147483000',
		'display:inline-flex',
		'align-items:center',
		'padding:6px 14px',
		'font:700 13px/1.3 sans-serif',
		'color:#fff',
		'background:' + BG,
		'border:0',
		'border-radius:6px',
		'cursor:grab',
		'box-shadow:0 2px 10px rgba(0,0,0,.3)',
		'opacity:1',
		'pointer-events:auto',
		'user-select:none',
		'touch-action:none',
	].join( ';' );
	btn.addEventListener( 'mouseenter', () => { btn.style.background = BG_HOVER; } );
	btn.addEventListener( 'mouseleave', () => { btn.style.background = BG; } );
	return btn;
}

/**
 * Make a button draggable within its anchor by top/left while keeping it
 * clickable. A press that moves > a few px is a DRAG (repositions, persists the
 * new position, suppresses the click); a barely-moved press is a CLICK.
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

	btn.addEventListener( 'pointerdown', ( e ) => {
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
	}, true );

	btn.addEventListener( 'pointermove', ( e ) => {
		if ( ! dragging ) return;
		const dx = e.clientX - startX;
		const dy = e.clientY - startY;
		if ( ! moved && Math.abs( dx ) + Math.abs( dy ) > 4 ) {
			moved = true;
			btn.style.cursor = 'grabbing';
		}
		if ( moved ) {
			// Track live position ourselves — getComputedStyle can lag on pointerup.
			curLeft = originLeft + dx;
			curTop = originTop + dy;
			btn.style.left = curLeft + 'px';
			btn.style.top = curTop + 'px';
			btn.style.right = 'auto';
		}
	}, true );

	btn.addEventListener( 'pointerup', ( e ) => {
		if ( ! dragging ) return;
		dragging = false;
		btn.style.cursor = 'grab';
		btn.releasePointerCapture?.( e.pointerId );
		e.stopPropagation();
		if ( moved ) {
			saveButtonPosition( anchor, curLeft, curTop ); // persist across reloads
		} else {
			e.preventDefault();
			onClick();
		}
	}, true );

	// Swallow the native click / mousedown so Elementor's canvas selection never
	// fires and we drive the action from pointerup instead.
	btn.addEventListener( 'click', ( e ) => { e.preventDefault(); e.stopPropagation(); }, true );
	btn.addEventListener( 'mousedown', ( e ) => e.stopPropagation(), true );
}

/**
 * Ensure one refresh button exists on a wrap, wired to force-resync + drag.
 * Idempotent: re-runs harmlessly as the preview re-renders.
 */
function ensureButton( wrap ) {
	const win = wrap.ownerDocument.defaultView;
	const doc = wrap.ownerDocument;

	// The button is absolute inside the wrap's PARENT (not fixed, not inside the
	// wrap) so it scrolls with the page and travels with the wrap, while no
	// ancestor overflow/transform can clip or bury it.
	const host = wrap.parentElement;
	if ( ! host ) {
		return;
	}
	const wrapId = wrap.getAttribute( 'data-id' ) || wrap.id || '';
	if ( wrap.__aaeToggleBtn && wrap.__aaeToggleBtn.isConnected ) {
		return;
	}
	if ( wrapId ) {
		const existing = doc.querySelector( '.' + BTN_CLASS + '[data-aae-for="' + wrapId + '"]' );
		if ( existing ) {
			wrap.__aaeToggleBtn = existing;
			return;
		}
	}

	// The host must be a positioning context so `position:absolute` anchors to it.
	const hostPos = win.getComputedStyle( host ).position;
	if ( hostPos === 'static' || ! hostPos ) {
		host.style.position = 'relative';
	}

	const btn = makeButton( doc );
	if ( wrapId ) {
		btn.setAttribute( 'data-aae-for', wrapId );
	}

	// Restore a dragged position; else anchor to the wrap's top-left corner (in
	// the host's coordinate space).
	restoreButtonPosition( btn, wrap, host );

	// Refresh: rebuild the clones from the card's CURRENT markup so they mirror
	// the latest child edits (Post Terms, Post Image settings, …). Never hides
	// items, nav, or pagination — the grid layout stays intact.
	makeButtonDraggable( btn, wrap, () => forceResyncWrap( wrap ) );

	host.appendChild( btn );
	wrap.__aaeToggleBtn = btn;
}

/**
 * Remove any refresh button whose wrap is gone. The button lives in the wrap's
 * PARENT (not inside the wrap), so deleting the Loop Grid widget orphans the
 * button in the parent — prune it by matching `data-aae-for` back to a live wrap.
 */
function pruneOrphanButtons( doc ) {
	// Only prune buttons THIS module owns (data-aae-owner="grid"). The slider
	// module's buttons share the class but belong to sliders, not grid wraps —
	// touching them here would wrongly delete them.
	doc.querySelectorAll( '.' + BTN_CLASS + '[data-aae-owner="grid"]' ).forEach( ( btn ) => {
		const forId = btn.getAttribute( 'data-aae-for' );
		const stillHasWrap = forId
			? doc.querySelector( WRAP_SELECTOR + '[data-id="' + forId + '"]' )
			: btn.closest( WRAP_SELECTOR ); // legacy button with no id — keep only if inside a wrap
		if ( ! stillHasWrap ) {
			btn.remove();
		}
	} );
}

/** Scan the preview and make sure every loop-grid wrap has its toggle button. */
function scanButtons() {
	const win = getPreviewWindow();
	const doc = win && win.document;
	if ( ! doc ) {
		return;
	}
	pruneOrphanButtons( doc );
	const wraps = doc.querySelectorAll( WRAP_SELECTOR );
	wraps.forEach( ensureButton );
}

/**
 * Install the Edit/Back toggle. Idempotent. Rides a ~1.5s heartbeat so the button
 * survives preview re-renders and iframe swaps.
 *
 * setInterval (not requestAnimationFrame): the button's existence is load-bearing
 * UI that must be maintained even when the frame isn't actively painting — a rAF
 * heartbeat pauses while the tab/iframe is hidden and would leave the button
 * un-injected. The per-tick cost is trivial (idempotent scan), so setInterval's
 * always-on cadence is the right tradeoff.
 */
export function installLoopGridEditToggle() {
	if ( state.loopGridEditToggleInstalled ) {
		return;
	}
	state.loopGridEditToggleInstalled = true;

	scanButtons();
	setInterval( scanButtons, 1500 );
}
