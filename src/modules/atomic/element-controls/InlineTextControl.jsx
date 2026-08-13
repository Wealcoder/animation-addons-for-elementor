/* eslint-env browser */

/**
 * AAE Inline Text — rich-text heading editing with a native colour picker.
 *
 * WHY THIS IS NOT CORE'S EDITOR (read before "simplifying" it back).
 *
 * Core ships `InlineEditor` (TipTap) and we used it first. It cannot do colour,
 * and the reason is structural, not a missing option:
 *
 *   • ProseMirror schemas are IMMUTABLE after creation, so a colour mark cannot
 *     be registered onto the instance `onEditorCreate` hands us.
 *   • `InlineEditor` hardcodes its extension list (bold/italic/underline/
 *     strike/sup/sub/link/hard-break) with no prop to extend it.
 *   • Elementor exports no TipTap primitives — no `Mark`, no `useEditor` — so we
 *     cannot build a configured editor from its copy either.
 *   • Wrapping the DOM selection by hand does not stick: `getHTML()` serialises
 *     from the doc model, so an injected span is invisible to it and is wiped on
 *     the next keystroke. That also kills any class-based fallback — core's
 *     TipTap has no generic `span` mark, so even a bare `<span>` is unwrapped.
 *
 * The remaining options were "add TipTap as a dependency" or "own a
 * contenteditable". This is the second, chosen deliberately to avoid the
 * dependency.
 *
 * WHY THE OLD COLOUR BUG DOES NOT COME BACK. The previous attempt lived on the
 * CANVAS and drove `document.execCommand`, which only acts on the focused
 * editable — and the colour picker always holds focus while open. Two rounds of
 * fixes failed on that. Here:
 *   • formatting buttons `preventDefault()` on mousedown, so focus never leaves
 *     the editable and execCommand always has a target;
 *   • colour never uses execCommand at all. It wraps a saved Range directly,
 *     which needs no focus, and re-uses one span across the picker's repeated
 *     `input` events instead of nesting a new one per event.
 *
 * The value is still an html-v3 prop, so the panel keeps showing FORMATTED text
 * rather than tags — that was the original ask and it is unchanged.
 *
 * THREE LISTS MUST AGREE. ALLOWED_TAGS/ALLOWED_ATTRS here,
 * AAE_Rich_Text_Prop_Type::allowed_tags() in PHP, and the striptags whitelist in
 * aae-a-advanced-heading.html.twig. Anything this file emits but PHP omits is
 * deleted on save, which reads as "my formatting disappeared".
 */

import * as React from 'react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useBoundProp } from '@elementor/editor-controls';
import { useElement } from '@elementor/editor-editing-panel';
import { getContainer } from '@elementor/editor-elements';
import { htmlV3PropTypeUtil, stringPropTypeUtil } from '@elementor/editor-props';
import { Box, IconButton, Stack, TextField, Tooltip } from '@elementor/ui';

const COMMIT_DEBOUNCE_MS = 250;

// Kept lowercase; compared against nodeName.toLowerCase().
//
// Hand-written HTML is a supported way to author this widget, so the list covers
// block-level structure too, not just inline formatting. Everything NOT here is
// unwrapped by cleanHtml() and refused again by wp_kses on save — that is where
// script/iframe/object/embed/form/input/style/link stay excluded.
const ALLOWED_TAGS = new Set( [
	// inline
	'span', 'b', 'strong', 'i', 'em', 'u', 's', 'del', 'ins', 'sub', 'sup',
	'mark', 'small', 'code', 'abbr', 'cite', 'q', 'br', 'a', 'img',
	// block
	'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'ul', 'ol', 'li',
	'blockquote', 'pre', 'hr', 'figure', 'figcaption',
] );
const ALLOWED_ATTRS = new Set( [
	'style', 'title', 'dir', 'lang', 'id', 'class',
	'href', 'target', 'rel',
	'src', 'alt', 'width', 'height', 'loading',
] );

// execCommand emits these in some engines; PHP's whitelist does not carry them.
// Normalised at commit time rather than refused, so the user keeps the effect.
const TAG_ALIASES = { strike: 's', font: 'span' };

const DEFAULT_COLOR = '#000000';

// Starting colour for a newly created link. Applied inline to the anchor, never
// as a CSS rule — see submitLink(). Repaintable with the colour swatch like any
// other run of text.
const DEFAULT_LINK_COLOR = '#2563eb';

// The buttons carry TEXT glyphs, not icon components, so MUI's size="tiny" only
// shrinks the padding — the glyph keeps the inherited font size and holds the
// box open. Pin both.
const BTN_SX = {
	width: 24,
	height: 24,
	minWidth: 24,
	p: 0,
	fontSize: '13px',
	lineHeight: 1,
};

const SWATCH_SIZE = 18;

// Stable reference for "no active formats". Returned as-is whenever the caret
// leaves the editable, so the identity check in syncFormatState() can short out
// instead of re-rendering the toolbar on every selection change.
const EMPTY_FORMATS = Object.freeze( {} );

const FORMAT_BUTTONS = [
	{ cmd: 'bold', label: 'Bold', glyph: 'B', sx: { fontWeight: 700 } },
	{ cmd: 'italic', label: 'Italic', glyph: 'I', sx: { fontStyle: 'italic' } },
	{ cmd: 'underline', label: 'Underline', glyph: 'U', sx: { textDecoration: 'underline' } },
	{ cmd: 'strikeThrough', label: 'Strikethrough', glyph: 'S', sx: { textDecoration: 'line-through' } },
	{ cmd: 'superscript', label: 'Superscript', glyph: 'x²' },
	{ cmd: 'subscript', label: 'Subscript', glyph: 'x₂' },
];

/* ------------------------------------------------------------------ clean */

/**
 * Rewrite every `color: rgb(...)` / `rgba(...)` as `#rrggbb`, or `#rrggbbaa`
 * when the colour carries alpha.
 *
 * NOT cosmetic — without it colour silently disappears on save. WordPress's
 * `safecss_filter_attr()` strips only var()/calc()/min()/max()/minmax()/
 * clamp()/repeat() from the string it tests (`wp-includes/kses.php`), then
 * REFUSES any declaration still containing `(`. rgb()/rgba() are not on that
 * list, so the whole style attribute is dropped. And rgb() is unavoidable on
 * the client: the CSSOM serialises `style.color = '#c00000'` back out as
 * `rgb(192, 0, 0)`, so even assigning hex produces rgb() in innerHTML.
 *
 * Done on the STRING rather than via the DOM precisely because the DOM is what
 * re-introduces rgb(): any round-trip through a CSSStyleDeclaration undoes it.
 *
 * ALPHA IS PRESERVED, as the 8-digit form. That spelling is the whole reason
 * transparency is expressible here at all: it carries the alpha byte with NO
 * parentheses, so unlike rgba() it passes safecss_filter_attr() untouched and
 * needs no core filter relaxed and no site-wide security trade. It is CSS
 * Color 4, supported by every browser this editor runs in. An earlier revision
 * discarded alpha because `<input type="color">` cannot produce it — the
 * opacity control added alongside this now can.
 */
function colorsToHex( html ) {
	// The leading boundary keeps this off `background-color:` and friends.
	return html.replace( /(^|[;\s"'{])color:\s*rgba?\(([^)]*)\)/gi, ( full, prefix, inner ) => {
		const parts = inner.split( /[,\s/]+/ ).filter( Boolean ).map( parseFloat );

		if ( parts.length < 3 || parts.slice( 0, 3 ).some( Number.isNaN ) ) {
			return full;
		}

		const hex = parts
			.slice( 0, 3 )
			.map( ( n ) => Math.max( 0, Math.min( 255, Math.round( n ) ) ).toString( 16 ).padStart( 2, '0' ) )
			.join( '' );

		// The 4th component is alpha, 0–1. Appended ONLY when it is genuinely
		// below 1, so an opaque colour keeps the plain 6-digit spelling every
		// already-saved heading holds — this must not rewrite existing content.
		const alpha = parts.length > 3 && ! Number.isNaN( parts[ 3 ] ) ? parts[ 3 ] : 1;
		const alphaHex = alpha >= 1
			? ''
			: Math.max( 0, Math.min( 255, Math.round( alpha * 255 ) ) ).toString( 16 ).padStart( 2, '0' );

		return `${ prefix }color:#${ hex }${ alphaHex }`;
	} );
}

/**
 * Parse anything CSS accepts — `rgba(0,0,0,.5)`, `transparent`, `hsl(…)`, a
 * named colour, `#rgb`, `#rrggbbaa` — into the spelling that survives the save
 * path, or null when the value is not a colour at all.
 *
 * The BROWSER is the parser rather than a regex of ours: assigning to a
 * detached element's style silently rejects an invalid value (the property
 * stays empty), and reading it back gives the canonical serialisation, which
 * colorsToHex() then folds to hex. So a typed value and a picked one can never
 * end up in two different spellings.
 *
 * Returning null for unparseable input is deliberate: guessing would send
 * something to wp_kses that it drops on save, and the user would see the
 * colour simply not apply with nothing explaining why.
 *
 * `transparent` and named colours survive as keywords — no parentheses, so
 * they were always kses-safe; only the functional forms ever needed folding.
 */
function normaliseColor( input ) {
	const text = String( input ?? '' ).trim();

	if ( ! text ) {
		return null;
	}

	const probe = document.createElement( 'span' );
	probe.style.color = text;

	if ( ! probe.style.color ) {
		return null;
	}

	return colorsToHex( `color:${ probe.style.color }` ).replace( /^color:/, '' );
}

/**
 * Combine the native picker's opaque `#rrggbb` with an opacity percentage.
 *
 * 100% returns the 6-digit value unchanged rather than appending `ff`, for the
 * same reason colorsToHex() does: an opaque colour must keep the spelling it
 * has always had.
 */
function withOpacity( hex6, percent ) {
	const pct = Math.max( 0, Math.min( 100, Number( percent ) ) );

	if ( pct >= 100 ) {
		return hex6;
	}

	return hex6 + Math.round( ( pct / 100 ) * 255 ).toString( 16 ).padStart( 2, '0' );
}

/**
 * Rewrite `text-decoration-line:` as the `text-decoration:` shorthand, and drop
 * the other text-decoration longhands.
 *
 * THIS IS WHY STRIKETHROUGH DID NOTHING. WordPress's `safecss_filter_attr()`
 * whitelists `text-decoration` but not `text-decoration-line` — and one
 * unrecognised declaration makes it discard the WHOLE style attribute, so
 * `<span style="text-decoration-line: line-through">x</span>` came back from a
 * save as `<span>x</span>`. Measured against the real sanitiser, not assumed:
 *
 *     text-decoration: line-through        => text-decoration: line-through
 *     text-decoration-line: line-through   => REJECTED
 *
 * Chrome emits the longhand whenever execCommand runs with styleWithCSS on, so
 * this is the ordinary path, not an edge case. Underline via CSS falls into the
 * identical trap and is fixed by the same rewrite.
 *
 * `-color`/`-style`/`-thickness` are DROPPED rather than converted: they have no
 * safe shorthand equivalent to fold into, and leaving one in place would take
 * the entire attribute — and with it the strike itself — down with it. Losing a
 * decoration's colour is a far smaller loss than losing the decoration.
 *
 * String-level, alongside colorsToHex(), because any round-trip through a
 * CSSStyleDeclaration re-expands the shorthand back into longhands.
 */
function decorationToShorthand( html ) {
	return html
		.replace( /(^|[;\s"'{])text-decoration-line\s*:/gi, '$1text-decoration:' )
		.replace( /(^|[;\s"'{])text-decoration-(?:color|style|thickness)\s*:[^;"']*;?/gi, '$1' );
}

/**
 * Strip everything the server would strip anyway, so what the panel shows is
 * what survives a save. Operates on a DETACHED clone — never on the live
 * editable, because rewriting the DOM under the caret makes it jump.
 */
function cleanHtml( html ) {
	const holder = document.createElement( 'div' );
	holder.innerHTML = html;

	// Snapshot first: the walk mutates the tree, and a live NodeList would skip
	// siblings as nodes are unwrapped.
	const elements = Array.from( holder.querySelectorAll( '*' ) );

	elements.forEach( ( el ) => {
		// Still part of the working tree?
		//
		// `holder.contains(el)`, NEVER `el.isConnected`. `holder` is a DETACHED
		// div, and isConnected asks whether a node is in the live DOCUMENT — which
		// is false for everything inside a detached tree. That made this entire
		// loop dead code: every element returned on the first line, so nothing was
		// aliased, unwrapped or attribute-filtered, and the only surviving work was
		// the string passes below.
		//
		// The visible symptom was Strikethrough. Chrome's execCommand emits
		// `<strike>` (measured: bold/italic/underline/strike produce
		// `<b><i><u><strike>`), the alias below rewrites it to `<s>` — and with the
		// loop inert it never ran, so `<strike>` reached wp_kses, which does not
		// whitelist it and deleted the tag. The strike showed in the panel and
		// nowhere else.
		//
		// The check itself is needed: the walk MUTATES the tree while iterating a
		// snapshot, so an element unwrapped by an earlier pass must be skipped.
		// `contains()` answers that question correctly for a detached root.
		if ( ! holder.contains( el ) ) {
			return;
		}

		const tag = el.nodeName.toLowerCase();
		const alias = TAG_ALIASES[ tag ];

		if ( alias ) {
			const replacement = document.createElement( alias );

			// <font color> is the styleWithCSS=false spelling of a colour.
			// Carry it across rather than dropping the user's choice.
			const legacyColor = el.getAttribute( 'color' );
			if ( legacyColor ) {
				replacement.style.color = legacyColor;
			}

			while ( el.firstChild ) {
				replacement.appendChild( el.firstChild );
			}

			el.replaceWith( replacement );
			return;
		}

		if ( ! ALLOWED_TAGS.has( tag ) ) {
			// Unwrap, never delete — the TEXT is the user's content, only the
			// wrapper is unwanted.
			el.replaceWith( ...el.childNodes );
			return;
		}

		Array.from( el.attributes ).forEach( ( attr ) => {
			if ( ! ALLOWED_ATTRS.has( attr.name.toLowerCase() ) ) {
				el.removeAttribute( attr.name );
			}
		} );

		// The style attribute is reduced to the declarations the TOOLBAR can
		// produce; anything else is execCommand noise we would rather not store.
		//
		// text-decoration MUST be on this list. Leaving it off is what made
		// Strikethrough (and CSS underline) look broken in the most confusing way
		// possible: the strike showed in the panel and vanished everywhere else.
		// cleanHtml works on a DETACHED CLONE, so the live editable kept the
		// decoration while the value on its way to the database lost it — the box
		// and the page disagreeing, with nothing logged.
		//
		// Read the LONGHAND first: Chrome sets `text-decoration-line` under
		// styleWithCSS, and the shorthand property can serialise back out with
		// colour and style components attached, which safecss_filter_attr()
		// rejects — taking the whole attribute with it. Assigning through
		// `textDecoration` re-serialises it as the bare shorthand WordPress
		// accepts.
		if ( el.hasAttribute( 'style' ) ) {
			const color = el.style.color;
			const decoration = el.style.textDecorationLine || el.style.textDecoration;

			el.removeAttribute( 'style' );

			if ( color ) {
				el.style.color = color;
			}

			if ( decoration ) {
				el.style.textDecoration = decoration;
			}
		}

		// A span that ended up carrying nothing is pure clutter in the output.
		if ( 'span' === tag && 0 === el.attributes.length ) {
			el.replaceWith( ...el.childNodes );
		}
	} );

	// Must be the LAST step and must operate on the serialised string — see
	// colorsToHex(). Anything that touches the DOM after this puts rgb() back.
	return decorationToShorthand( colorsToHex( holder.innerHTML ) );
}

/* -------------------------------------------------- source interpretation */

// Attribute group is a TEMPERED match — "any character that does not begin
// `&gt;`" — not a negated class. `[^&]` also rejects a legitimate `&amp;` inside
// an attribute, so `<a href="/x?a=1&amp;b=2">` would silently fail to decode.
// Built FROM ALLOWED_TAGS so the tag list cannot drift into a second copy.
const TYPED_TAG_RE = new RegExp(
	`&lt;(\\/?)(${ [ ...ALLOWED_TAGS ].join( '|' ) })((?:\\s(?:(?!&gt;).)*?)?)(\\/?)&gt;`,
	'gi'
);
/**
 * Turn the verbatim source into what should actually be displayed: tags the user
 * typed become real elements, rendered AS TYPED — an `<h2>` stays an `<h2>`.
 *
 * DELIBERATE DUPLICATE of AAE_A_Advanced_Heading::interpret_source(). The two
 * exist because they serve different renderers: PHP feeds the FRONTEND, this
 * feeds the editor CANVAS, which renders the same Twig client-side and never
 * runs PHP. Without this the canvas shows raw `<h2>` angle brackets while the
 * published page shows the interpreted result.
 *
 * PHP is the authority: get_atomic_settings() RECOMPUTES this value on every
 * render and ignores whatever was stored, so drift between the two shows up as a
 * preview mismatch and can never reach the page — and a hand-crafted
 * `content_html` in the database is inert.
 */
function interpretSource( html ) {
	const decoded = html.replace( TYPED_TAG_RE, ( _full, slash, tag, attrs, selfClose ) =>
		`<${ slash }${ tag }${ attrs.replace( /&amp;/g, '&' ) }${ selfClose }>`
	);

	return collapseSpaces( decoded );
}

/**
 * Collapse every run of spaces to ONE, in the RENDERED value only.
 *
 * The panel field keeps whatever was typed — that is the point of `content`
 * being the verbatim source — while `content_html`, which is what the canvas and
 * the front end render, gets a single space. So the box can be laid out for
 * readability without the heading inheriting the gaps.
 *
 * NBSP is collapsed alongside the plain space. Ordinary spaces would fold on
 * their own (HTML collapses whitespace), but a contenteditable inserts U+00A0
 * for consecutive spaces and those DO render — which is exactly why multiple
 * spaces were reaching the output before.
 *
 * Splitting on tags first is what keeps this out of attribute values: collapsing
 * the spaces inside `class="a  b"` or a `style` rule would silently change what
 * a selector matches. preg_split's JS equivalent — a capturing split — puts the
 * tags on the odd indices, so only the even ones are touched.
 */
function collapseSpaces( html ) {
	return html
		.split( /(<[^>]*>)/ )
		.map( ( part, index ) =>
			index % 2
				? part
				: part.replace( /(?:&nbsp;|[ \t\u00a0]){2,}/gi, ' ' )
		)
		.join( '' );
}

// Inline wrappers a space should be able to step out of — everything the
// toolbar can put around a run of text.
const FORMAT_ELEMENTS = new Set( [
	'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'del', 'ins',
	'sub', 'sup', 'mark', 'a',
] );

/**
 * Is this element one the caret should escape when a space is typed at its end?
 *
 * A `span` counts only when it carries a style — that is the colour swatch's
 * output. A bare span is structural and stepping out of it would change nothing
 * visible while moving the caret somewhere the user did not ask for.
 */
function isFormatElement( el ) {
	if ( ! el || el.nodeType !== Node.ELEMENT_NODE ) {
		return false;
	}

	const tag = el.nodeName.toLowerCase();

	if ( 'span' === tag ) {
		return el.hasAttribute( 'style' );
	}

	return FORMAT_ELEMENTS.has( tag );
}

/** Remove any colour already set inside `root`, so re-colouring replaces. */
function stripColorsWithin( root ) {
	root.querySelectorAll( '[style*="color"], font[color]' ).forEach( ( el ) => {
		el.removeAttribute( 'color' );
		el.style.removeProperty( 'color' );

		if ( 'SPAN' === el.nodeName && ! el.getAttribute( 'style' ) ) {
			el.removeAttribute( 'style' );
		}
	} );
}

/* ---------------------------------------------------------------- control */

export function InlineTextControl() {
	const { value, setValue, placeholder } = useBoundProp( htmlV3PropTypeUtil );
	// Needed only to write the derived `content_html` sibling prop — useBoundProp
	// binds exactly one prop, so the canvas-facing value has to go through the
	// settings command like every other multi-prop control here.
	const { element } = useElement();

	const editableRef = useRef( null );
	const colorInputRef = useRef( null );
	const commitTimer = useRef( null );

	// Which format commands are active at the caret, so the toolbar shows what
	// the cursor is standing in. Kept in state, not a ref — the buttons render
	// from it. See syncFormatState().
	const [ activeFormats, setActiveFormats ] = useState( EMPTY_FORMATS );

	// The last HTML this component itself put into, or read out of, the DOM.
	// Guards the value→DOM sync below: without it every commit round-trips back
	// through props and rewrites innerHTML, destroying the caret on each key.
	const lastHtml = useRef( null );

	// The selection to colour. Captured on the swatch's mousedown, because
	// opening the native picker collapses/loses the live selection.
	const savedRange = useRef( null );
	// The span produced by the current picker session. The colour input fires
	// `input` continuously while dragging; without this each event would wrap a
	// new span around the previous one.
	const colorSpan = useRef( null );

	const [ color, setColor ] = useState( DEFAULT_COLOR );
	const [ linkOpen, setLinkOpen ] = useState( false );
	const [ linkUrl, setLinkUrl ] = useState( '' );

	// Opacity + free-text colour. The native `<input type="color">` is opaque by
	// construction — it has no alpha channel and no way to express `transparent`
	// — so transparency needs its own input. Kept as a separate row rather than
	// crowded into the toolbar, mirroring how the link field opens.
	const [ colorOpen, setColorOpen ] = useState( false );
	const [ colorText, setColorText ] = useState( '' );
	const [ opacity, setOpacity ] = useState( 100 );

	const content = stringPropTypeUtil.extract( value?.content ?? null ) ?? '';

	useEffect( () => () => {
		if ( commitTimer.current ) {
			clearTimeout( commitTimer.current );
		}
	}, [] );

	// Incoming value → DOM. Only when it genuinely differs from what we last
	// saw, so typing never fights the caret.
	useEffect( () => {
		const el = editableRef.current;

		if ( ! el || content === lastHtml.current ) {
			return;
		}

		// NEVER rewrite while the box has focus. Assigning innerHTML destroys the
		// selection, and the browser then puts the caret at the START of the
		// element — the "cursor jumps to the beginning while typing" bug.
		//
		// The `content === lastHtml` guard above was supposed to prevent this, but
		// it only holds while the value survives its round-trip through the prop
		// system byte-for-byte. Any normalisation on the way back — entity
		// encoding, attribute order, quote style — makes the strings differ and
		// this effect repaints mid-keystroke. Comparing strings is guessing at the
		// cause; refusing to touch a focused editable removes it outright.
		//
		// Nothing is lost: on blur, syncFromDom({ settle: true }) repaints from the
		// committed value anyway, so an external change (undo, switching element)
		// lands as soon as the field is not being typed into.
		if ( document.activeElement === el || el.contains( document.activeElement ) ) {
			return;
		}

		el.innerHTML = content;
		lastHtml.current = content;
	}, [ content ] );

	const commit = useCallback( ( html ) => {
		// `children` stays empty on purpose. It is editor-side metadata for
		// component overrides; the render path never reads it, the schema marks
		// it optional, and deriving it would mean pulling in core's
		// parseHtmlChildren for no visible gain.
		setValue( {
			content: html ? stringPropTypeUtil.create( html ) : null,
			children: [],
		} );

		// Mirror the interpreted form into `content_html`, which is what the Twig
		// renders. The CANVAS runs that Twig client-side straight off the model, so
		// without this write the preview shows raw `<h2>` angle brackets while the
		// published page shows the heading — the two disagreeing is exactly the
		// thing this widget is judged on.
		//
		// PHP recomputes the same value at render and ignores what is stored, so
		// this is a preview cache, not a second source of truth: if the two
		// implementations ever drift, the page still comes out right.
		const container = element?.id ? getContainer( element.id ) : null;

		if ( container && window.$e?.run ) {
			window.$e.run( 'document/elements/settings', {
				container,
				settings: {
					// Same envelope as `content` — content_html is an
					// AAE_Rich_Text_Prop_Type (`html-v3`), NOT a string prop. It was a
					// string prop once, and String_Prop_Type sanitises with
					// sanitize_text_field(), so every tag written here was stripped on
					// save: the canvas came back as bare text after a reload while the
					// front end stayed right, because PHP recomputes the value and only
					// the preview reads what was stored.
					content_html: htmlV3PropTypeUtil.create( {
						content: stringPropTypeUtil.create( interpretSource( html || '' ) ),
						children: [],
					} ),
				},
				// `external: true` keeps this out of the control's own change
				// pipeline — it is a derived write, not something the user did.
				options: { external: true },
			} );
		}
	}, [ setValue, element ] );

	/**
	 * Read the editable, clean it, and schedule a debounced write.
	 *
	 * @param {Object}  options
	 * @param {boolean} options.settle Blur-time pass: commit immediately instead
	 *                                 of after the debounce.
	 */
	const syncFromDom = useCallback( ( { settle = false } = {} ) => {
		const el = editableRef.current;

		if ( ! el ) {
			return;
		}

		// Typed markup is stored VERBATIM — it is not decoded into real elements
		// here, and the box is never repainted with a parsed result. What you type
		// is what stays in the field, so `<h2 class="x">Solution</h2>` remains
		// visible and re-editable instead of turning into styled text you can no
		// longer take apart.
		//
		// The interpretation happens once, on the render path, in
		// AAE_A_Advanced_Heading::get_atomic_settings() — see that method for the
		// tag-unwrapping rule. cleanHtml() still runs, because the TOOLBAR emits
		// real markup (bold, colour, links) and that half must stay whitelisted.
		const html = cleanHtml( el.innerHTML );

		// Record the CLEANED html as what we last saw. Mid-typing the live DOM
		// may still hold the dirtier original, and that is fine — the two only
		// have to agree well enough that the sync effect does not rewrite
		// innerHTML and move the caret.
		lastHtml.current = html;

		if ( commitTimer.current ) {
			clearTimeout( commitTimer.current );
			commitTimer.current = null;
		}

		if ( settle ) {
			// Deliberately NO repaint here. Rewriting the box on blur is what used
			// to replace typed markup with its parsed result; the field is now the
			// verbatim source and must survive losing focus untouched.
			commit( html );
			return;
		}

		commitTimer.current = setTimeout( () => commit( html ), COMMIT_DEBOUNCE_MS );
	}, [ commit ] );

	/**
	 * Light up the toolbar buttons that describe the caret's current position, so
	 * putting the cursor inside bold text shows B as active.
	 *
	 * Read through `queryCommandState` rather than by walking ancestors for
	 * `<b>`/`<strong>`: the browser already answers for every spelling its own
	 * execCommand produces (b vs strong, i vs em, and a `style="font-weight:700"`
	 * span pasted in), and an ancestor walk would have to re-derive all of it and
	 * would still disagree with the button it is labelling.
	 *
	 * Scoped to OUR editable. `queryCommandState` answers for the document
	 * selection wherever it is, so without the containment test a bold selection
	 * anywhere else in the panel would light these up.
	 */
	const syncFormatState = useCallback( () => {
		const el = editableRef.current;
		const selection = document.getSelection();

		if (
			! el ||
			! selection ||
			! selection.rangeCount ||
			! el.contains( selection.getRangeAt( 0 ).commonAncestorContainer )
		) {
			setActiveFormats( EMPTY_FORMATS );
			return;
		}

		const next = {};

		FORMAT_BUTTONS.forEach( ( { cmd } ) => {
			try {
				next[ cmd ] = document.queryCommandState( cmd );
			} catch {
				// Engines throw rather than return false for a command they do not
				// implement. An unknown state is "not active"; never let it break
				// the whole toolbar.
				next[ cmd ] = false;
			}
		} );

		// Bail when nothing changed. `selectionchange` fires on every keystroke and
		// every caret move, and handing back a fresh object each time would
		// re-render the whole toolbar continuously while someone is typing.
		setActiveFormats( ( prev ) =>
			FORMAT_BUTTONS.every( ( { cmd } ) => !! prev[ cmd ] === next[ cmd ] ) ? prev : next
		);
	}, [] );

	// `selectionchange` is the only event that catches every way the caret can
	// move — typing, clicking, arrow keys, Ctrl+B, and the execCommand calls
	// below. It fires on the DOCUMENT, so it must be torn down with the control.
	useEffect( () => {
		document.addEventListener( 'selectionchange', syncFormatState );
		return () => document.removeEventListener( 'selectionchange', syncFormatState );
	}, [ syncFormatState ] );

	const runCommand = ( cmd ) => {
		// Focus is guaranteed by the mousedown preventDefault on the buttons,
		// but a keyboard-driven click has no mousedown at all.
		editableRef.current?.focus();
		document.execCommand( cmd, false, null );
		syncFromDom();
		// Explicit, not left to selectionchange: toggling a format at a COLLAPSED
		// caret changes the pending state without moving the selection, so no
		// selectionchange is guaranteed to fire.
		syncFormatState();
	};

	const saveSelection = () => {
		const selection = document.getSelection();

		if ( ! selection || ! selection.rangeCount ) {
			return;
		}

		const range = selection.getRangeAt( 0 );

		if ( editableRef.current?.contains( range.commonAncestorContainer ) ) {
			savedRange.current = range.cloneRange();
		}
	};

	/**
	 * Colour the saved selection. Deliberately DOM-only: no execCommand, so it
	 * does not care that the colour picker currently owns focus.
	 */
	const applyColor = ( nextColor ) => {
		// Same picker session — repaint the existing span instead of nesting.
		if ( colorSpan.current && colorSpan.current.isConnected ) {
			colorSpan.current.style.color = nextColor;
			syncFromDom();
			return;
		}

		const range = savedRange.current;

		if ( ! range || range.collapsed ) {
			return;
		}

		const span = document.createElement( 'span' );
		span.style.color = nextColor;

		try {
			// Fails whenever the selection straddles element boundaries
			// (half of a <b> plus plain text, say) — extremely common here.
			range.surroundContents( span );
		} catch ( _e ) {
			const contents = range.extractContents();
			stripColorsWithin( contents );
			span.appendChild( contents );
			range.insertNode( span );
		}

		// Nested colours inside the new span would win over it.
		stripColorsWithin( span );
		colorSpan.current = span;

		// Keep the text selected so the next drag of the picker keeps targeting
		// it, and so the user can see what they are colouring.
		const next = document.createRange();
		next.selectNodeContents( span );
		savedRange.current = next.cloneRange();

		const selection = document.getSelection();
		selection?.removeAllRanges();
		selection?.addRange( next );

		syncFromDom();
	};

	/**
	 * Open the opacity / colour-code row.
	 *
	 * Captures the selection FIRST, for the same reason the swatch does: the
	 * moment focus moves to the field or the slider the live selection is gone,
	 * and everything in this row applies to whatever was highlighted when it
	 * opened. Always opens rather than toggling, so clicking it again after
	 * selecting different text re-captures instead of closing.
	 */
	const openColorRow = () => {
		saveSelection();
		colorSpan.current = null;

		// Seed the field with the colour already on the selection, so the row
		// opens showing what is there rather than empty.
		const node = savedRange.current?.commonAncestorContainer;
		const el = 1 === node?.nodeType ? node : node?.parentElement;
		const existing = el?.closest?.( '[style*="color"]' )?.style?.color ?? '';

		setColorText( existing ? normaliseColor( existing ) ?? '' : '' );
		setColorOpen( true );
	};

	/**
	 * Repaint the selection at a new opacity, keeping the hue the swatch holds.
	 *
	 * `colorSpan` is deliberately NOT reset here: the whole row is one session,
	 * so dragging repaints the span it already made instead of wrapping a fresh
	 * one on every drag. The cost is that re-selecting different text needs
	 * another click on the opacity button to re-capture — which is what that
	 * button does.
	 */
	const applyOpacity = ( pct ) => {
		setOpacity( pct );
		applyColor( withOpacity( color, pct ) );
	};

	/**
	 * Apply a typed CSS colour. Anything the browser can parse is accepted —
	 * `transparent`, `rgba(…)`, `hsl(…)`, `#rrggbbaa`, a named colour — and
	 * normaliseColor() folds it to the spelling that survives wp_kses.
	 *
	 * An unparseable value applies nothing and leaves the field alone, so the
	 * user can see and correct what they typed.
	 */
	const submitColorText = () => {
		const parsed = normaliseColor( colorText );

		if ( ! parsed ) {
			return;
		}

		// Keep the swatch and the slider showing what was just applied so the
		// three inputs cannot drift apart. Only a hex value can drive the native
		// input; a keyword like `transparent` leaves it on its previous colour.
		const hex = /^#([0-9a-f]{6})([0-9a-f]{2})?$/i.exec( parsed );

		if ( hex ) {
			setColor( `#${ hex[ 1 ] }` );
			setOpacity( hex[ 2 ] ? Math.round( ( parseInt( hex[ 2 ], 16 ) / 255 ) * 100 ) : 100 );
		}

		setColorText( parsed );
		applyColor( parsed );
	};

	const openLink = () => {
		saveSelection();

		const anchor = savedRange.current?.commonAncestorContainer?.parentElement?.closest?.( 'a' );
		setLinkUrl( anchor?.getAttribute( 'href' ) ?? '' );
		setLinkOpen( true );
	};

	const submitLink = () => {
		const range = savedRange.current;

		if ( range ) {
			const selection = document.getSelection();
			selection?.removeAllRanges();
			selection?.addRange( range );
		}

		editableRef.current?.focus();
		document.execCommand( linkUrl ? 'createLink' : 'unlink', false, linkUrl || null );

		// Give a new link a visible colour of its own.
		//
		// An <a> here inherits the heading's colour, so a link was indistinguishable
		// from the text around it. Painted as an INLINE COLOUR on the anchor rather
		// than as a stylesheet rule: this widget deliberately ships no CSS, and an
		// inline value is a plain starting point the user can then repaint with the
		// colour swatch — a rule with `!important` could not be overridden at all,
		// which is the opposite of a *default*.
		//
		// Only anchors with NO colour of their own are touched, so re-editing the
		// URL of a link somebody has already recoloured leaves that colour alone.
		if ( linkUrl && editableRef.current ) {
			editableRef.current.querySelectorAll( 'a' ).forEach( ( anchor ) => {
				if ( ! anchor.style.color ) {
					anchor.style.color = DEFAULT_LINK_COLOR;
				}
			} );
		}

		setLinkOpen( false );
		syncFromDom();
	};

	const clearFormatting = () => {
		editableRef.current?.focus();
		document.execCommand( 'removeFormat', false, null );
		document.execCommand( 'unlink', false, null );

		// removeFormat does NOT drop an inline `style="color"` in Chrome, so the
		// span we created survives it. Strip colour out of the selection
		// in place: extract, clean the fragment, put it back.
		const selection = document.getSelection();

		if ( selection?.rangeCount ) {
			const range = selection.getRangeAt( 0 );

			if ( ! range.collapsed ) {
				const contents = range.extractContents();
				stripColorsWithin( contents );
				range.insertNode( contents );

				// extractContents leaves the range collapsed; re-select what we
				// just reinserted so a second click is not a no-op.
				selection.removeAllRanges();
				selection.addRange( range );
			}
		}

		colorSpan.current = null;
		syncFromDom();
	};

	// Toolbar buttons must not take focus, or execCommand has no editable to act
	// on and the selection is gone by the time the click handler runs.
	const keepFocus = ( event ) => event.preventDefault();

	return (
		<Box>
			<Stack direction="row" alignItems="center" gap={ 0.25 } sx={ { mb: 0.5, flexWrap: 'wrap' } }>
				<Tooltip title="Clear formatting" placement="top">
					<IconButton size="tiny" onMouseDown={ keepFocus } onClick={ clearFormatting } sx={ BTN_SX }>—</IconButton>
				</Tooltip>

				{ FORMAT_BUTTONS.map( ( { cmd, label, glyph, sx } ) => (
					<Tooltip key={ cmd } title={ label } placement="top">
						<IconButton
							size="tiny"
							// Announced, not just painted — a colour-only "on" state is
							// invisible to a screen reader and to anyone who cannot
							// separate the two greys.
							aria-pressed={ !! activeFormats[ cmd ] }
							onMouseDown={ keepFocus }
							onClick={ () => runCommand( cmd ) }
							sx={ {
								...BTN_SX,
								...sx,
								...( activeFormats[ cmd ]
									? {
										bgcolor: 'action.selected',
										color: 'text.primary',
										// Hold the pressed look through hover, which would
										// otherwise repaint the background and make an
										// active button read as inactive under the pointer.
										'&:hover': { bgcolor: 'action.selected' },
									}
									: {} ),
							} }
						>
							{ glyph }
						</IconButton>
					</Tooltip>
				) ) }

				<Tooltip title="Link" placement="top">
					<IconButton size="tiny" onMouseDown={ keepFocus } onClick={ openLink } sx={ BTN_SX }>🔗</IconButton>
				</Tooltip>

				<Tooltip title="Text colour" placement="top">
					{ /* A real <input type="color"> — the OS/browser palette, no
					     library. The visible swatch is the input itself so the
					     native picker anchors to it. */ }
					<Box
						component="label"
						sx={ {
							width: SWATCH_SIZE,
							height: SWATCH_SIZE,
							ml: 0.25,
							borderRadius: '3px',
							border: '1px solid',
							borderColor: 'grey.300',
							backgroundColor: color,
							cursor: 'pointer',
							display: 'inline-block',
							flex: '0 0 auto',
						} }
						onMouseDown={ () => {
							// Capture BEFORE the picker opens and the selection
							// is lost, and start a fresh span for this session.
							saveSelection();
							colorSpan.current = null;
						} }
					>
						<input
							ref={ colorInputRef }
							type="color"
							value={ color }
							style={ { opacity: 0, width: 0, height: 0, border: 0, padding: 0, display: 'block' } }
							// Chrome fires `input` live while dragging and
							// `change` on commit; Firefox only fires `change`.
							// Both are wired, and the colorSpan guard makes the
							// duplicate harmless.
							onInput={ ( event ) => {
								setColor( event.target.value );
								applyColor( event.target.value );
							} }
							onChange={ ( event ) => {
								setColor( event.target.value );
								applyColor( event.target.value );
							} }
						/>
					</Box>
				</Tooltip>

				{ /* The native picker above is opaque by construction — no alpha
				     channel, no way to say `transparent`. This opens the row that
				     can express both. */ }
				<Tooltip title="Opacity / colour code" placement="top">
					<IconButton size="tiny" onMouseDown={ keepFocus } onClick={ openColorRow } sx={ BTN_SX }>◑</IconButton>
				</Tooltip>
			</Stack>

			{ linkOpen ? (
				// `alignItems: center` is what puts the ✓ on the input's centre line.
				// Without it the row stretches both children to its own height and
				// the button, being shorter than the field, sat at the top.
				<Stack direction="row" gap={ 0.5 } alignItems="center" sx={ { mb: 0.5 } }>
					<TextField
						size="tiny"
						fullWidth
						autoFocus
						placeholder="https://…"
						value={ linkUrl }
						onChange={ ( event ) => setLinkUrl( event.target.value ) }
						onKeyDown={ ( event ) => {
							if ( 'Enter' === event.key ) {
								event.preventDefault();
								submitLink();
							}

							if ( 'Escape' === event.key ) {
								setLinkOpen( false );
							}
						} }
					/>
					{ /* Smaller than the format buttons: this one sits beside a text
					     field rather than in the toolbar row, and at BTN_SX's 24px it
					     towered over the tiny input. The glyph is sized explicitly
					     because MUI's size="tiny" only trims padding — the ✓ would
					     otherwise keep the inherited font size and hold the box
					     open. */ }
					<IconButton
						size="tiny"
						onMouseDown={ keepFocus }
						onClick={ submitLink }
						sx={ {
							width: 18,
							height: 18,
							minWidth: 18,
							p: 0,
							fontSize: '11px',
							lineHeight: 1,
							flexShrink: 0,
						} }
					>✓</IconButton>
				</Stack>
			) : null }

			{ colorOpen ? (
				<Stack direction="row" gap={ 0.5 } alignItems="center" sx={ { mb: 0.5 } }>
					<TextField
						size="tiny"
						fullWidth
						autoFocus
						placeholder="transparent, rgba(0,0,0,.5), #rrggbbaa"
						value={ colorText }
						onChange={ ( event ) => setColorText( event.target.value ) }
						onKeyDown={ ( event ) => {
							if ( 'Enter' === event.key ) {
								event.preventDefault();
								submitColorText();
							}

							if ( 'Escape' === event.key ) {
								setColorOpen( false );
							}
						} }
					/>
					{ /* A raw range input, matching how the swatch uses a raw
					     <input type="color"> — no component dependency for one
					     slider, and it styles consistently with it. */ }
					<Tooltip title={ `Opacity ${ opacity }%` } placement="top">
						<input
							type="range"
							min="0"
							max="100"
							value={ opacity }
							onChange={ ( event ) => applyOpacity( Number( event.target.value ) ) }
							style={ { width: 64, flexShrink: 0 } }
						/>
					</Tooltip>
					<IconButton
						size="tiny"
						onMouseDown={ keepFocus }
						onClick={ submitColorText }
						sx={ {
							width: 18,
							height: 18,
							minWidth: 18,
							p: 0,
							fontSize: '11px',
							lineHeight: 1,
							flexShrink: 0,
						} }
					>✓</IconButton>
				</Stack>
			) : null }

			<Box
				ref={ editableRef }
				contentEditable
				suppressContentEditableWarning
				role="textbox"
				aria-multiline="false"
				data-placeholder={ placeholder?.content?.value ?? '' }
				// Wrapped, not passed by reference: React would hand the event
				// in as the options object.
				onInput={ () => syncFromDom() }
				onBlur={ () => syncFromDom( { settle: true } ) }
				onKeyUp={ saveSelection }
				onMouseUp={ saveSelection }
				onPaste={ ( event ) => {
					// Paste as PLAIN TEXT. The browser's own rich paste drags in
					// fonts, colours and classes from wherever it came from, and
					// most of that is stripped on save anyway — pasting
					// something that silently loses half its formatting is worse
					// than pasting text. Pasted MARKUP is not lost either: it
					// lands as literal text, is stored verbatim, and is
						// interpreted on the render path — same as if typed.
					event.preventDefault();

					// Flatten to ONE LINE before inserting.
					//
					// `insertText` turns every newline in the clipboard into a real
					// line break, and a contenteditable spells that as a <div> — so
					// pasting
					//
					//     First
					//       Second
					//         Thir
					//
					// produced `First<div> Second</div><div> Thir</div>` INSIDE the
					// heading. Those divs are block-level, so the Twig's block
					// detection then demoted the whole widget from <h1> to <div>, and
					// one paste silently changed the element's tag.
					//
					// A heading is a single line — Enter is already refused for the
					// same reason — so newlines have no meaning here and collapsing
					// them is the honest interpretation of the paste, not a loss.
					//
					// `\s+` also folds tabs and the leading indentation of pasted
					// code/lists into the single space the one-space rule requires,
					// which is why this runs BEFORE insertion rather than being left
					// to the cleanHtml pass: by then the divs already exist.
					const text = ( event.clipboardData?.getData( 'text/plain' ) ?? '' )
						.replace( /\s+/g, ' ' );

					if ( ! text ) {
						return;
					}

					// Insert UNFORMATTED. `insertText` alone adopts whatever the caret
					// is standing in, so pasting at the end of an italic run came out
					// italic — and pasting into a styled span silently inherited that
					// span's tags too. Selecting what we just inserted and clearing the
					// formatting on that range is what makes a plain-text paste
					// actually plain.
					document.execCommand( 'insertText', false, text );

					const selection = document.getSelection();

					if ( selection && selection.rangeCount ) {
						// insertText leaves the caret collapsed at the END of the run,
						// so walking back its length reselects exactly the inserted
						// text and nothing else.
						const end = selection.getRangeAt( 0 );
						const range = document.createRange();

						try {
							range.setEnd( end.endContainer, end.endOffset );
							range.setStart( end.endContainer, Math.max( 0, end.endOffset - text.length ) );
							selection.removeAllRanges();
							selection.addRange( range );
							document.execCommand( 'removeFormat', false, null );
							document.execCommand( 'unlink', false, null );
							selection.collapseToEnd();
						} catch {
							// A paste that straddles nodes can leave offsets that do not
							// resolve. The text is already in — leaving it with inherited
							// formatting is a far better outcome than throwing here and
							// losing the paste altogether.
						}
					}

					syncFromDom();
				} }
				onKeyDown={ ( event ) => {
					// A heading is one line; Enter would create a <div> or <br>
					// the whitelist does not want.
					if ( 'Enter' === event.key ) {
						event.preventDefault();
					}

					// One space only — refuse a second consecutive one.
					//
					// Blocked at the KEY rather than cleaned up afterwards because
					// cleanHtml works on a detached clone and the box is never
					// repainted while it has focus (repainting is what used to throw
					// the caret to the start). Cleaning alone would leave the field
					// showing spaces that the saved value does not have.
					//
					// NBSP counts as a space here: the browser inserts U+00A0 by
					// itself when you type a space next to another one, so testing
					// for ' ' alone would miss exactly the case this exists to catch.
					if ( ' ' === event.key && ! event.ctrlKey && ! event.metaKey ) {
						// A space at the END of a formatted run steps OUT of it.
						//
						// Without this the caret stays inside the <s> (or <b>, <a>...), so
						// the space and everything typed after it joined the strike and it
						// ran on to the end of the line.
						//
						// The space is inserted as a plain text node AFTER the element
						// rather than by moving the caret and letting the browser type into
						// the boundary: browsers routinely re-enter the element at that
						// position, which would put the character straight back inside the
						// formatting.
						//
						// Only fires when the caret is at the very END of the run, so
						// editing INSIDE formatted text is untouched -- and it walks up
						// through nested wrappers (<b><i><s>) so one space escapes all of
						// them at once, not one layer per keypress.
						const escSel = document.getSelection();

						if ( escSel?.isCollapsed && escSel.rangeCount ) {
							const escRange = escSel.getRangeAt( 0 );
							const escNode = escRange.startContainer;

							if (
								escNode.nodeType === Node.TEXT_NODE &&
								escRange.startOffset === escNode.nodeValue.length
							) {
								let child = escNode;
								let parent = escNode.parentNode;
								let outermost = null;

								while (
									parent &&
									parent !== editableRef.current &&
									isFormatElement( parent ) &&
									parent.lastChild === child
								) {
									outermost = parent;
									child = parent;
									parent = parent.parentNode;
								}

								if ( outermost?.parentNode ) {
									event.preventDefault();

									const space = document.createTextNode( ' ' );
									outermost.parentNode.insertBefore( space, outermost.nextSibling );

									const after = document.createRange();
									after.setStart( space, 1 );
									after.collapse( true );
									escSel.removeAllRanges();
									escSel.addRange( after );

									syncFromDom();
								}
							}
						}
					}
				} }
				sx={ {
					p: 0.8,
					minHeight: '70px',
					fontSize: '12px',
					// Editing-surface only — the panel field, never the rendered
					// heading. Typed markup makes these lines long and they wrap
					// several times, so the extra leading is what separates one
					// wrapped line from the next while you are reading the source.
					lineHeight: 2,
					border: '1px solid',
					borderColor: 'grey.200',
					borderRadius: '8px',
					outline: 'none',
					overflowWrap: 'anywhere',
					// Drag the bottom-right corner to make the box taller.
					// `resize` is ignored while overflow is `visible`, which is the
					// default for a div — so the pair below is one setting, not two,
					// and dropping the overflow line silently kills the grip.
					// `vertical` rather than `both`: the panel column is a fixed
					// width and a wider box would just overflow it.
					resize: 'vertical',
					overflow: 'auto',
					transition: 'border-color .2s ease, box-shadow .2s ease',
					'&:hover': { borderColor: 'black' },
					'&:focus': { borderColor: 'black', boxShadow: '0 0 0 1px black' },
					'&:empty::before': {
						content: 'attr(data-placeholder)',
						color: 'text.tertiary',
						opacity: 0.6,
						pointerEvents: 'none',
					},
				} }
			/>
		</Box>
	);
}
