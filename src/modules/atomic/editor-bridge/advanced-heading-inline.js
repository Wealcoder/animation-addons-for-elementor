/* eslint-env browser */

/**
 * Canvas inline editing for the AAE Advanced Heading — the floating format
 * toolbar you get on a core `e-paragraph` / `e-heading`, plus a TEXT COLOUR
 * field core doesn't have.
 *
 * Why this is hand-rolled instead of reusing core's inline editing
 * ---------------------------------------------------------------
 * Core's canvas inline editor (editor-canvas → `InlineEditingReplacement`) is
 * closed to us on three independent counts:
 *   1. The widget→prop map it drives off (`INLINE_EDITING_PROPERTY_PER_TYPE`)
 *      is a hard-coded object of five core types, and neither it nor
 *      `registerReplacement` is exported from the package's public API.
 *   2. Its write path always re-wraps the value in an `html-v3` envelope; our
 *      `content` prop is AAE_Html_Rich_Prop_Type (reuses the plain 'string'
 *      key) precisely so `class` / `style` survive save + render.
 *   3. Its TipTap instance registers a fixed extension list with NO
 *      TextStyle/Color mark and no way to add one, so a colour button is
 *      impossible there — and html-v3's wp_kses would strip the style anyway.
 *
 * So: `contenteditable` on the rendered heading + `execCommand` + our own
 * toolbar, and one settings write when the session ends.
 *
 * Markup contract (matters — AAE_Html_Rich_Prop_Type::allowed_tags() is the
 * wp_kses whitelist the value must survive on save):
 *   - `styleWithCSS` is left OFF for the tag commands, so bold/italic/underline
 *     /sup/sub produce `<b> <i> <u> <sup> <sub>` — all whitelisted.
 *   - It is turned ON only around `foreColor`, so colour lands as
 *     `<span style="color: …">` (span + style are whitelisted) and never as a
 *     `<font>` tag, which is not.
 *   - Chrome still emits `<strike>` for strikeThrough (not whitelisted), and
 *     Enter would create `<div>` blocks, so commitHtml() normalises
 *     `strike → s`, `font[color] → span[style]`, and Enter is remapped to a
 *     `<br>`.
 *
 * Why the value is written only when editing ENDS: an atomic element view
 * re-renders on every settings change, which would replace the very node the
 * caret lives in. Core dodges that by suppressing its own re-render while
 * TipTap owns the DOM — a hook we don't have. One write per session also means
 * one undo step, which is the behaviour you want here anyway.
 */

import { getContainer, getElementSetting, updateElementSettings } from '@elementor/editor-elements';
import { getCurrentEditMode } from '@elementor/editor-v1-adapters';

import { track } from './disposables';
import { getPreviewWindow, unwrap } from './helpers';

const WIDGET_TYPE = 'e-aae-a-advanced-heading';
const CONTENT_PROP = 'content';
const TOOLBAR_FLAG = 'data-aae-ah-toolbar';
const TOOLBAR_GAP = 10;

/** Commands that must NOT run under styleWithCSS (we want real tags). */
const TAG_COMMANDS = [ 'bold', 'italic', 'underline', 'strikeThrough', 'superscript', 'subscript' ];

/* -------------------------------------------------------------------------
 * Session state. One editing session at a time, mirroring core.
 * ---------------------------------------------------------------------- */

let session = null; // { doc, win, el, id, startHtml, prevStyle }
let toolbar = null; // { root, row, linkRow, linkInput, colorInput, colorBar, states }
let savedRange = null;

/* =========================================================================
 * Element resolution
 * ====================================================================== */

/** True when `id` is an Advanced Heading element in the current document. */
function isAdvancedHeading( id ) {
	const model = getContainer( id )?.model;
	const type = model?.get?.( 'widgetType' ) || model?.get?.( 'elType' );

	return type === WIDGET_TYPE;
}

/**
 * Walk up from a clicked node to the Advanced Heading element view that owns
 * it. Keyed on `data-id` + the element model — NOT on our own base classes,
 * which the editor canvas is known to drop in edit mode.
 */
function resolveWidget( node ) {
	let el = node?.nodeType === 3 ? node.parentElement : node;

	while ( el && el.nodeType === 1 ) {
		const id = el.getAttribute?.( 'data-id' );

		if ( id && isAdvancedHeading( id ) ) {
			return { wrapper: el, id };
		}

		el = el.parentElement;
	}

	return null;
}

/**
 * The node whose innerHTML IS the `content` prop. In the canvas the element
 * view's own `el` (the one carrying data-id) WRAPS the Twig root, so prefer the
 * Twig root — it is the only node whose children map 1:1 to the stored value.
 */
function editableOf( wrapper ) {
	if ( wrapper.getAttribute( 'data-e-type' ) === WIDGET_TYPE ) {
		return wrapper;
	}

	return wrapper.querySelector( `[data-e-type="${ WIDGET_TYPE }"]` )
		|| wrapper.querySelector( '.aae-a-advanced-heading' )
		|| wrapper.firstElementChild
		|| wrapper;
}

/* =========================================================================
 * Selection helpers
 * ====================================================================== */

function saveRange() {
	const sel = session?.doc?.getSelection?.();

	savedRange = ( sel && sel.rangeCount ) ? sel.getRangeAt( 0 ).cloneRange() : null;
}

/**
 * Put the caret/selection back. Needed after anything that can steal focus
 * (the colour picker dialog, the link input) — the format buttons themselves
 * preventDefault on mousedown, so they never lose it in the first place.
 */
function restoreRange() {
	if ( ! session ) {
		return;
	}

	session.el.focus( { preventScroll: true } );

	if ( ! savedRange ) {
		return;
	}

	const sel = session.doc.getSelection();

	sel.removeAllRanges();
	sel.addRange( savedRange );
}

/** Caret from click coordinates, so entering edit mode lands where you clicked. */
function placeCaretFromPoint( doc, x, y ) {
	let range = null;

	if ( doc.caretRangeFromPoint ) {
		range = doc.caretRangeFromPoint( x, y );
	} else if ( doc.caretPositionFromPoint ) {
		const pos = doc.caretPositionFromPoint( x, y );

		if ( pos ) {
			range = doc.createRange();
			range.setStart( pos.offsetNode, pos.offset );
			range.collapse( true );
		}
	}

	if ( ! range ) {
		return;
	}

	const sel = doc.getSelection();

	sel.removeAllRanges();
	sel.addRange( range );
}

/* =========================================================================
 * Commands
 * ====================================================================== */

function setStyleWithCss( doc, on ) {
	try {
		doc.execCommand( 'styleWithCSS', false, on );
	} catch ( _e ) { /* Firefox throws on repeat calls — harmless. */ }
}

/**
 * Make sure a usable selection is live before running a command: keep the
 * CURRENT one when the caret is still inside the heading (it is fresher than
 * anything we stashed), and only fall back to the saved range when focus was
 * stolen — by the colour picker dialog or the link input.
 */
function ensureSelection() {
	if ( ! session ) {
		return;
	}

	const sel = session.doc.getSelection();

	if ( sel && sel.rangeCount && session.el.contains( sel.anchorNode ) ) {
		savedRange = sel.getRangeAt( 0 ).cloneRange();
		return;
	}

	restoreRange();
}

function exec( cmd, value = null ) {
	if ( ! session ) {
		return;
	}

	const { doc } = session;

	ensureSelection();
	setStyleWithCss( doc, ! TAG_COMMANDS.includes( cmd ) );

	try {
		doc.execCommand( cmd, false, value );
	} catch ( _e ) { /* unsupported command — nothing to do */ }

	saveRange();
	syncToolbar();
}

/** Replace an element with its own children. */
function unwrapNode( node ) {
	const parent = node.parentNode;

	if ( ! parent ) {
		return;
	}

	while ( node.firstChild ) {
		parent.insertBefore( node.firstChild, node );
	}

	parent.removeChild( node );
}

/**
 * Drop the colour from the selection. Deliberately whole-node: every coloured
 * element the selection touches loses its `color`, and a `<span>` left with no
 * attributes at all is unwrapped. It does not split a partially-selected span —
 * predictable beats clever here, and a user's own highlight class survives.
 */
function clearColor() {
	if ( ! session ) {
		return;
	}

	ensureSelection();

	const range = savedRange;
	const nodes = session.el.querySelectorAll( '[style*="color"], font[color]' );

	nodes.forEach( ( node ) => {
		if ( range && range.intersectsNode && ! range.intersectsNode( node ) ) {
			return;
		}

		if ( 'FONT' === node.tagName ) {
			unwrapNode( node );
			return;
		}

		node.style.removeProperty( 'color' );

		if ( ! node.getAttribute( 'style' ) ) {
			node.removeAttribute( 'style' );
		}

		if ( 'SPAN' === node.tagName && ! node.attributes.length ) {
			unwrapNode( node );
		}
	} );

	syncToolbar();
}

/* =========================================================================
 * Toolbar
 * ====================================================================== */

const ICONS = {
	clear: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/></svg>',
	link: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.07 0l2.5-2.5A5 5 0 0 0 12.5 3.4L11 4.9"/><path d="M14 11a5 5 0 0 0-7.07 0l-2.5 2.5A5 5 0 0 0 11.5 20.6L13 19.1"/></svg>',
	unlink: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M6 18L18 6"/></svg>',
};

const BUTTONS = [
	{ id: 'removeFormat', cmd: 'removeFormat', label: 'Clear', html: ICONS.clear, separator: true },
	{ id: 'bold', cmd: 'bold', label: 'Bold', html: '<span style="font-weight:800">B</span>' },
	{ id: 'italic', cmd: 'italic', label: 'Italic', html: '<span style="font-style:italic;font-family:Georgia,serif">I</span>' },
	{ id: 'underline', cmd: 'underline', label: 'Underline', html: '<span style="text-decoration:underline">U</span>' },
	{ id: 'strikeThrough', cmd: 'strikeThrough', label: 'Strikethrough', html: '<span style="text-decoration:line-through">S</span>' },
	{ id: 'superscript', cmd: 'superscript', label: 'Superscript', html: '<span>x<sup style="font-size:8px">2</sup></span>' },
	{ id: 'subscript', cmd: 'subscript', label: 'Subscript', html: '<span>x<sub style="font-size:8px">2</sub></span>' },
];

const BTN_CSS = 'box-sizing:border-box;display:inline-flex;align-items:center;justify-content:center;'
	+ 'width:28px;height:28px;padding:0;margin:0;border:0;border-radius:6px;background:transparent;'
	+ 'color:#0C0D0E;font:600 13px/1 Roboto,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;'
	+ 'cursor:pointer;text-decoration:none;box-shadow:none;flex:0 0 auto;';

/** A toolbar button that never steals the caret (preventDefault on mousedown). */
function makeButton( doc, { label, html, onClick } ) {
	const btn = doc.createElement( 'button' );

	btn.type = 'button';
	btn.title = label;
	btn.setAttribute( 'aria-label', label );
	btn.setAttribute( 'style', BTN_CSS );
	btn.innerHTML = html;

	btn.addEventListener( 'mousedown', ( event ) => event.preventDefault() );
	btn.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		event.stopPropagation();
		onClick();
	} );

	return btn;
}

function makeSeparator( doc ) {
	const sep = doc.createElement( 'span' );

	sep.setAttribute( 'style', 'width:1px;align-self:stretch;margin:2px 2px;background:#E6E8EA;flex:0 0 auto;' );

	return sep;
}

function buildToolbar( doc ) {
	const root = doc.createElement( 'div' );

	root.setAttribute( TOOLBAR_FLAG, 'true' );
	// `fixed`, like core's own inline toolbar: viewport coordinates can't be
	// thrown off by a themed <body> that is offset, relatively positioned, or
	// margin-collapsed the way page coordinates can.
	root.setAttribute( 'style',
		'position:fixed;top:-9999px;left:-9999px;z-index:2147483000;display:flex;flex-direction:column;'
		+ 'gap:4px;padding:4px;border-radius:8px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.2);'
		+ 'direction:ltr;text-align:left;line-height:1;'
	);

	// Toolbars live in the canvas document, where the site's own stylesheet can
	// reach them — keep every rule that matters on the elements themselves and
	// neutralise inherited button styling.
	const row = doc.createElement( 'div' );
	row.setAttribute( 'style', 'display:inline-flex;align-items:center;gap:4px;' );
	root.appendChild( row );

	const states = new Map();

	BUTTONS.forEach( ( def ) => {
		const btn = makeButton( doc, { ...def, onClick: () => exec( def.cmd ) } );

		row.appendChild( btn );
		states.set( def.id, btn );

		if ( def.separator ) {
			row.appendChild( makeSeparator( doc ) );
		}
	} );

	row.appendChild( makeSeparator( doc ) );

	// ---- Link ------------------------------------------------------------
	const linkBtn = makeButton( doc, {
		label: 'Link',
		html: ICONS.link,
		onClick: () => toggleLinkRow(),
	} );
	row.appendChild( linkBtn );
	states.set( 'link', linkBtn );

	// ---- Colour ----------------------------------------------------------
	row.appendChild( makeSeparator( doc ) );

	const colorInput = doc.createElement( 'input' );
	colorInput.type = 'color';
	colorInput.value = '#000000';
	colorInput.setAttribute( 'style', 'position:absolute;width:1px;height:1px;padding:0;border:0;opacity:0;pointer-events:none;' );
	// `change` (not `input`): one clean apply when the native picker closes,
	// instead of a nested <span> per mouse-move inside it.
	colorInput.addEventListener( 'change', () => applyColor( colorInput.value ) );

	const colorBar = doc.createElement( 'span' );
	colorBar.setAttribute( 'style', 'display:block;width:16px;height:3px;border-radius:2px;background:#000;' );

	const colorBtn = makeButton( doc, {
		label: 'Text color',
		// An "A" over a swatch of the current colour — the familiar affordance.
		html: '<span style="font:700 12px/1 Roboto,sans-serif">A</span>',
		onClick: () => {
			// The native picker takes focus, so stash the range before it opens.
			saveRange();
			colorInput.click();
		},
	} );
	colorBtn.style.flexDirection = 'column';
	colorBtn.style.gap = '2px';
	colorBtn.appendChild( colorBar );

	row.appendChild( colorBtn );
	// Sibling, not a child of the button: an <input> inside a <button> is invalid
	// markup and browsers may swallow the programmatic click that opens the picker.
	row.appendChild( colorInput );

	row.appendChild( makeButton( doc, {
		label: 'Remove color',
		html: ICONS.unlink,
		onClick: () => clearColor(),
	} ) );

	// ---- Link row (hidden until the link button is pressed) --------------
	const linkRow = doc.createElement( 'div' );
	linkRow.setAttribute( 'style', 'display:none;align-items:center;gap:4px;padding-top:2px;border-top:1px solid #E6E8EA;' );

	const linkInput = doc.createElement( 'input' );
	linkInput.type = 'url';
	linkInput.placeholder = 'Paste or type a URL';
	linkInput.setAttribute( 'style',
		'box-sizing:border-box;width:190px;height:26px;padding:0 6px;border:1px solid #C2CBD2;border-radius:4px;'
		+ 'background:#fff;color:#0C0D0E;font:400 12px/1 Roboto,sans-serif;'
	);
	linkInput.addEventListener( 'keydown', ( event ) => {
		event.stopPropagation();

		if ( 'Enter' === event.key ) {
			event.preventDefault();
			applyLink( linkInput.value );
		}

		if ( 'Escape' === event.key ) {
			event.preventDefault();
			toggleLinkRow( false );
		}
	} );

	linkRow.appendChild( linkInput );
	linkRow.appendChild( makeButton( doc, {
		label: 'Apply link',
		html: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>',
		onClick: () => applyLink( linkInput.value ),
	} ) );
	linkRow.appendChild( makeButton( doc, {
		label: 'Remove link',
		html: ICONS.unlink,
		onClick: () => {
			exec( 'unlink' );
			toggleLinkRow( false );
		},
	} ) );

	root.appendChild( linkRow );

	doc.body.appendChild( root );

	return { root, row, linkRow, linkInput, colorInput, colorBar, states };
}

function toggleLinkRow( force ) {
	if ( ! toolbar || ! session ) {
		return;
	}

	const show = ( 'undefined' === typeof force )
		? ( 'none' === toolbar.linkRow.style.display )
		: force;

	if ( show ) {
		saveRange();

		const link = currentLink();

		toolbar.linkRow.style.display = 'flex';
		toolbar.linkInput.value = link ? link.getAttribute( 'href' ) || '' : '';
		toolbar.linkInput.focus();
	} else {
		toolbar.linkRow.style.display = 'none';
		restoreRange();
	}

	positionToolbar();
}

/** The <a> the caret sits inside, if any. */
function currentLink() {
	const node = savedRange?.startContainer;
	const el = node?.nodeType === 3 ? node.parentElement : node;

	return el?.closest?.( 'a' ) || null;
}

function applyLink( url ) {
	const href = ( url || '' ).trim();

	if ( href ) {
		exec( 'createLink', href );
	} else {
		exec( 'unlink' );
	}

	toggleLinkRow( false );
}

function applyColor( value ) {
	exec( 'foreColor', value );

	if ( toolbar ) {
		toolbar.colorBar.style.background = value;
	}
}

/** Reflect the caret's formatting in the toolbar (pressed states + swatch). */
function syncToolbar() {
	if ( ! toolbar || ! session ) {
		return;
	}

	const { doc } = session;

	BUTTONS.forEach( ( def ) => {
		const btn = toolbar.states.get( def.id );

		if ( ! btn || 'removeFormat' === def.id ) {
			return;
		}

		let active = false;

		try {
			active = doc.queryCommandState( def.cmd );
		} catch ( _e ) { /* unsupported — leave it unpressed */ }

		btn.style.background = active ? '#E6E8EA' : 'transparent';
	} );

	const linkBtn = toolbar.states.get( 'link' );

	if ( linkBtn ) {
		linkBtn.style.background = currentLink() ? '#E6E8EA' : 'transparent';
	}

	try {
		const color = doc.queryCommandValue( 'foreColor' );

		if ( color ) {
			toolbar.colorBar.style.background = color;
		}
	} catch ( _e ) { /* no colour info — keep the last swatch */ }

	positionToolbar();
}

/** Park the toolbar above the selection (below it when there's no room). */
function positionToolbar() {
	if ( ! toolbar || ! session ) {
		return;
	}

	const { doc, el } = session;
	const sel = doc.getSelection();
	let rect = null;

	if ( sel && sel.rangeCount ) {
		rect = sel.getRangeAt( 0 ).getBoundingClientRect();
	}

	if ( ! rect || ( ! rect.width && ! rect.height ) ) {
		rect = el.getBoundingClientRect();
	}

	const { offsetWidth: width, offsetHeight: height } = toolbar.root;
	const viewport = doc.documentElement.clientWidth;

	let left = rect.left + ( rect.width / 2 ) - ( width / 2 );
	left = Math.max( 8, Math.min( left, viewport - width - 8 ) );

	let top = rect.top - height - TOOLBAR_GAP;

	if ( top < 8 ) {
		top = rect.bottom + TOOLBAR_GAP;
	}

	toolbar.root.style.left = `${ Math.round( left ) }px`;
	toolbar.root.style.top = `${ Math.round( top ) }px`;
}

function removeToolbar() {
	toolbar?.root?.remove?.();
	toolbar = null;
}

/* =========================================================================
 * Commit
 * ====================================================================== */

/**
 * Bring the contenteditable output in line with the prop's wp_kses whitelist
 * (see AAE_Html_Rich_Prop_Type::allowed_tags()): `strike` → `s`, `font[color]`
 * → `span[style="color:…"]`, and any block wrapper Chrome may have inserted is
 * flattened to a `<br>`-separated inline run.
 */
function commitHtml( doc, html ) {
	const box = doc.createElement( 'div' );

	box.innerHTML = html;

	box.querySelectorAll( 'strike' ).forEach( ( node ) => {
		const s = doc.createElement( 's' );

		s.innerHTML = node.innerHTML;
		node.replaceWith( s );
	} );

	box.querySelectorAll( 'font' ).forEach( ( node ) => {
		const span = doc.createElement( 'span' );
		const color = node.getAttribute( 'color' );

		span.innerHTML = node.innerHTML;

		if ( color ) {
			span.style.color = color;
		}

		node.replaceWith( span );
	} );

	box.querySelectorAll( 'div, p' ).forEach( ( node, index ) => {
		if ( index > 0 ) {
			node.parentNode?.insertBefore( doc.createElement( 'br' ), node );
		}

		unwrapNode( node );
	} );

	box.querySelectorAll( '[contenteditable]' ).forEach( ( node ) => node.removeAttribute( 'contenteditable' ) );

	return box.innerHTML.replace( /(<br\s*\/?>)+$/i, '' ).trim();
}

/** Current stored value, so we can skip a no-op write (and a no-op undo step). */
function storedContent( id ) {
	try {
		return unwrap( getElementSetting( id, CONTENT_PROP ) ) || '';
	} catch ( _e ) {
		return '';
	}
}

/* =========================================================================
 * Session lifecycle
 * ====================================================================== */

/**
 * Bound to the editable node itself, NOT the document: stopping propagation
 * here is what keeps Backspace / Delete / Ctrl+Z away from Elementor's canvas
 * shortcuts (which would delete or undo the whole element). A document-level
 * listener could not do it — Elementor's own handlers are registered first on
 * that node, so they would still run.
 */
function onEditableKeyDown( event ) {
	if ( ! session ) {
		return;
	}

	if ( 'Escape' === event.key ) {
		event.preventDefault();
		endEdit();
		return;
	}

	event.stopPropagation();

	if ( 'Enter' === event.key && ! event.shiftKey ) {
		// The prop allows inline tags only — a <div>/<p> block would be stripped
		// on save, so Enter is a line break.
		event.preventDefault();
		exec( 'insertLineBreak' );
	}
}

function beginEdit( wrapper, id, point ) {
	const el = editableOf( wrapper );

	if ( session && session.el === el ) {
		return;
	}

	endEdit();

	const doc = el.ownerDocument;
	const win = doc.defaultView;

	session = {
		doc,
		win,
		el,
		id,
		startHtml: el.innerHTML,
		prevStyle: el.getAttribute( 'style' ),
	};

	el.setAttribute( 'contenteditable', 'true' );
	el.setAttribute( 'spellcheck', 'false' );
	el.style.outline = 'none';
	el.addEventListener( 'keydown', onEditableKeyDown );

	el.focus( { preventScroll: true } );

	if ( point ) {
		placeCaretFromPoint( doc, point.x, point.y );
	}

	saveRange();

	toolbar = buildToolbar( doc );
	syncToolbar();
}

function endEdit( commit = true ) {
	if ( ! session ) {
		return;
	}

	const { doc, el, id, startHtml, prevStyle } = session;

	session = null;
	savedRange = null;
	removeToolbar();

	el.removeEventListener( 'keydown', onEditableKeyDown );
	el.removeAttribute( 'contenteditable' );
	el.removeAttribute( 'spellcheck' );

	if ( null === prevStyle ) {
		el.removeAttribute( 'style' );
	} else {
		el.setAttribute( 'style', prevStyle );
	}

	if ( ! commit || ! el.isConnected ) {
		return;
	}

	const html = commitHtml( doc, el.innerHTML );

	if ( html === commitHtml( doc, startHtml ) || html === storedContent( id ) ) {
		return;
	}

	// Deferred: the write re-renders this element, and doing that from inside
	// the very event (mousedown / keydown) that triggered it has bitten this
	// plugin before — see the v4 "defer document mutations" note.
	requestAnimationFrame( () => {
		updateElementSettings( {
			id,
			props: { [ CONTENT_PROP ]: { $$type: 'string', value: html } },
			withHistory: true,
		} );
	} );
}

/* =========================================================================
 * Wiring
 * ====================================================================== */

function insideToolbar( node ) {
	const el = node?.nodeType === 3 ? node.parentElement : node;

	return !! el?.closest?.( `[${ TOOLBAR_FLAG }]` );
}

export function startAdvancedHeadingInline() {
	const win = getPreviewWindow();
	const doc = win?.document;

	if ( ! doc || ! doc.body ) {
		return;
	}

	const onClick = ( event ) => {
		if ( 0 !== event.button || insideToolbar( event.target ) ) {
			return;
		}

		if ( 'edit' !== getCurrentEditMode() ) {
			return;
		}

		const found = resolveWidget( event.target );

		if ( ! found ) {
			return;
		}

		const point = { x: event.clientX, y: event.clientY };

		// Let Elementor finish selecting (and any re-render it triggers) before
		// we take the node over — then re-resolve it, since it may be a new node.
		setTimeout( () => {
			const fresh = doc.querySelector( `[data-id="${ found.id }"]` ) || found.wrapper;

			if ( fresh?.isConnected ) {
				beginEdit( fresh, found.id, point );
			}
		}, 0 );
	};

	const onMouseDown = ( event ) => {
		if ( ! session || insideToolbar( event.target ) ) {
			return;
		}

		if ( ! session.el.contains( event.target ) ) {
			endEdit();
		}
	};

	const onSelectionChange = () => {
		if ( ! session ) {
			return;
		}

		if ( ! session.el.isConnected ) {
			endEdit( false );
			return;
		}

		const sel = session.doc.getSelection();

		if ( sel && sel.rangeCount && session.el.contains( sel.anchorNode ) ) {
			saveRange();
			syncToolbar();
		}
	};

	const onReposition = () => positionToolbar();

	// A click in the panel / navigator / top bar never reaches the iframe, so
	// commit from the main document too (core does the same for its toolbar).
	const onOuterMouseDown = () => endEdit();

	doc.addEventListener( 'click', onClick );
	doc.addEventListener( 'mousedown', onMouseDown, true );
	doc.addEventListener( 'selectionchange', onSelectionChange );
	win.addEventListener( 'scroll', onReposition, true );
	win.addEventListener( 'resize', onReposition );
	document.addEventListener( 'mousedown', onOuterMouseDown, true );

	track( () => {
		endEdit( false );
		removeToolbar();
		doc.removeEventListener( 'click', onClick );
		doc.removeEventListener( 'mousedown', onMouseDown, true );
		doc.removeEventListener( 'selectionchange', onSelectionChange );
		win.removeEventListener( 'scroll', onReposition, true );
		win.removeEventListener( 'resize', onReposition );
		document.removeEventListener( 'mousedown', onOuterMouseDown, true );
	} );
}
