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
import { htmlV3PropTypeUtil, stringPropTypeUtil } from '@elementor/editor-props';
import { Box, IconButton, Stack, TextField, Tooltip } from '@elementor/ui';

const COMMIT_DEBOUNCE_MS = 250;

// Kept lowercase; compared against nodeName.toLowerCase().
const ALLOWED_TAGS = new Set( [ 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'del', 'sub', 'sup', 'mark', 'small', 'a', 'br' ] );
const ALLOWED_ATTRS = new Set( [ 'style', 'title', 'dir', 'href', 'target', 'rel' ] );

// execCommand emits these in some engines; PHP's whitelist does not carry them.
// Normalised at commit time rather than refused, so the user keeps the effect.
const TAG_ALIASES = { strike: 's', font: 'span' };

const DEFAULT_COLOR = '#000000';

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
 * Rewrite every `color: rgb(...)` / `rgba(...)` as `#rrggbb`.
 *
 * NOT cosmetic — without it colour silently disappears on save. WordPress's
 * `safecss_filter_attr()` accepts hex, named colours and `var()` for `color`
 * but REJECTS the rgb()/rgba() functional forms, dropping the whole style
 * attribute. And rgb() is unavoidable on the client: the CSSOM serialises
 * `style.color = '#c00000'` back out as `rgb(192, 0, 0)`, so even assigning
 * hex produces rgb() in innerHTML.
 *
 * Done on the STRING rather than via the DOM precisely because the DOM is what
 * re-introduces rgb(): any round-trip through a CSSStyleDeclaration undoes it.
 *
 * Alpha is discarded. `<input type="color">` cannot produce it, and a colour
 * that silently loses its transparency on save would be worse than one that
 * never had it.
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

		return `${ prefix }color:#${ hex }`;
	} );
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
		if ( ! el.isConnected ) {
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

		// A style attribute holding anything but colour is noise we would rather
		// not store; PHP's safecss pass would keep some of it silently.
		if ( el.hasAttribute( 'style' ) ) {
			const color = el.style.color;
			el.removeAttribute( 'style' );

			if ( color ) {
				el.style.color = color;
			}
		}

		// A span that ended up carrying nothing is pure clutter in the output.
		if ( 'span' === tag && 0 === el.attributes.length ) {
			el.replaceWith( ...el.childNodes );
		}
	} );

	// Must be the LAST step and must operate on the serialised string — see
	// colorsToHex(). Anything that touches the DOM after this puts rgb() back.
	return colorsToHex( holder.innerHTML );
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

	const editableRef = useRef( null );
	const colorInputRef = useRef( null );
	const commitTimer = useRef( null );

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
	}, [ setValue ] );

	/** Read the editable, clean it, and schedule a debounced write. */
	const syncFromDom = useCallback( () => {
		const el = editableRef.current;

		if ( ! el ) {
			return;
		}

		const html = cleanHtml( el.innerHTML );

		// Record the CLEANED html as what we last saw. The live DOM may still
		// hold the dirtier original, and that is fine — the two only have to
		// agree well enough that the sync effect does not rewrite innerHTML and
		// move the caret. It is reconciled on the next mount.
		lastHtml.current = html;

		if ( commitTimer.current ) {
			clearTimeout( commitTimer.current );
		}

		commitTimer.current = setTimeout( () => commit( html ), COMMIT_DEBOUNCE_MS );
	}, [ commit ] );

	const runCommand = ( cmd ) => {
		// Focus is guaranteed by the mousedown preventDefault on the buttons,
		// but a keyboard-driven click has no mousedown at all.
		editableRef.current?.focus();
		document.execCommand( cmd, false, null );
		syncFromDom();
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
					<IconButton size="tiny" onMouseDown={ keepFocus } onClick={ clearFormatting }>—</IconButton>
				</Tooltip>

				{ FORMAT_BUTTONS.map( ( { cmd, label, glyph, sx } ) => (
					<Tooltip key={ cmd } title={ label } placement="top">
						<IconButton size="tiny" onMouseDown={ keepFocus } onClick={ () => runCommand( cmd ) } sx={ sx }>
							{ glyph }
						</IconButton>
					</Tooltip>
				) ) }

				<Tooltip title="Link" placement="top">
					<IconButton size="tiny" onMouseDown={ keepFocus } onClick={ openLink }>🔗</IconButton>
				</Tooltip>

				<Tooltip title="Text colour" placement="top">
					{ /* A real <input type="color"> — the OS/browser palette, no
					     library. The visible swatch is the input itself so the
					     native picker anchors to it. */ }
					<Box
						component="label"
						sx={ {
							width: 22,
							height: 22,
							ml: 0.25,
							borderRadius: '4px',
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
			</Stack>

			{ linkOpen ? (
				<Stack direction="row" gap={ 0.5 } sx={ { mb: 0.5 } }>
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
					<IconButton size="tiny" onMouseDown={ keepFocus } onClick={ submitLink }>✓</IconButton>
				</Stack>
			) : null }

			<Box
				ref={ editableRef }
				contentEditable
				suppressContentEditableWarning
				role="textbox"
				aria-multiline="false"
				data-placeholder={ placeholder?.content?.value ?? '' }
				onInput={ syncFromDom }
				onBlur={ syncFromDom }
				onKeyUp={ saveSelection }
				onMouseUp={ saveSelection }
				onPaste={ ( event ) => {
					// Paste as PLAIN TEXT. The browser's own rich paste drags in
					// fonts, colours and classes from wherever it came from, and
					// most of that is stripped on save anyway — pasting
					// something that silently loses half its formatting is worse
					// than pasting text.
					event.preventDefault();
					const text = event.clipboardData?.getData( 'text/plain' ) ?? '';
					document.execCommand( 'insertText', false, text );
				} }
				onKeyDown={ ( event ) => {
					// A heading is one line; Enter would create a <div> or <br>
					// the whitelist does not want.
					if ( 'Enter' === event.key ) {
						event.preventDefault();
					}
				} }
				sx={ {
					p: 0.8,
					minHeight: '70px',
					fontSize: '12px',
					border: '1px solid',
					borderColor: 'grey.200',
					borderRadius: '8px',
					outline: 'none',
					overflowWrap: 'anywhere',
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
