/* eslint-env browser */
import * as React from 'react';
import { useRef, useState, useCallback, useEffect } from 'react';
import { getSelectedContainer } from '../../editor-bridge/helpers';
import { applySettingsToDom, replayInPreview } from '../../editor-bridge/settings-bridge';

/**
 * CodeMirror 6 CSS editor with debounced live-preview sync — loaded on demand.
 *
 * WHY THE IMPORTS ARE DYNAMIC
 * ---------------------------
 * CodeMirror and its Lezer parsers were MORE THAN HALF of the whole editor
 * bridge. Measured from webpack's own module stats against the bundle before
 * this change:
 *
 *   @codemirror/view          464 KB     @lezer/common     81 KB
 *   @codemirror/state         142 KB     @lezer/lr         70 KB
 *   @codemirror/language       99 KB     @lezer/highlight  29 KB
 *   @codemirror/autocomplete   87 KB
 *   @codemirror/commands       81 KB     — 1,053 KB of 2,089 KB total
 *
 * All of it for ONE panel control, on ONE extension, which most editor sessions
 * never open. A static import means every builder pays for a CSS editor on
 * every editor load whether or not they ever reach for it; `import()` moves it
 * into a chunk fetched the first time this control actually mounts.
 *
 * WHY THERE IS A TEXTAREA UNDERNEATH
 * ----------------------------------
 * Not a spinner, and not an empty box. Until the chunk lands — and permanently,
 * if it never does, which is a real state on a slow connection or behind a CSP
 * that blocks the fetch — the builder gets a plain <textarea> that edits the
 * same value through the same onChange and drives the same live preview. It is
 * a worse editor, not a broken one. A spinner over a control that may never
 * arrive is the failure this whole plugin's frontend family is designed around,
 * and the editor deserves the same treatment.
 *
 * The textarea is also what makes the swap safe: the value lives in the parent's
 * state throughout, so whatever was typed before CodeMirror arrived is the doc
 * CodeMirror is created with.
 */

const PLACEHOLDER = 'selector {\n  /* Your custom CSS here */\n}';

/**
 * One shared promise for the whole editor session.
 *
 * Custom CSS can be open on several elements in one session, and re-fetching a
 * ~1 MB chunk per mount would be worse than shipping it eagerly. A module-level
 * promise also means a second mount while the first is still in flight waits on
 * the same request rather than starting another.
 */
let cmPromise = null;

function loadCodeMirror() {
	if (!cmPromise) {
		cmPromise = Promise.all([
			import('@codemirror/state'),
			import('@codemirror/view'),
			import('@codemirror/commands'),
			import('@codemirror/lang-css'),
			import('@codemirror/theme-one-dark'),
			import('@codemirror/language'),
			import('@codemirror/autocomplete'),
		]).then(([state, view, commands, langCss, theme, language, autocomplete]) => ({
			state,
			view,
			commands,
			langCss,
			theme,
			language,
			autocomplete,
		})).catch((e) => {
			// Leave the textarea in place and let a later mount try again: a
			// one-off network failure must not disable the control for the rest
			// of the session.
			cmPromise = null;
			throw e;
		});
	}
	return cmPromise;
}

export function CodeInput({ value, onChange, disabled, placeholder, play_group = '' }) {
	const containerRef = useRef(null);
	const viewRef = useRef(null);
	const timerRef = useRef(null);
	const onChangeRef = useRef(onChange);
	const playGroupRef = useRef(play_group);
	const readOnlyComp = useRef(null);
	const cmRef = useRef(null);
	const [ready, setReady] = useState(false);

	// Keep refs current so the EditorView listener always reads fresh callbacks.
	onChangeRef.current = onChange;
	playGroupRef.current = play_group;

	// The live value and disabled state, readable from inside the async gap
	// without re-running the effect that owns the editor.
	const valueRef = useRef(value);
	valueRef.current = value;
	const disabledRef = useRef(disabled);
	disabledRef.current = disabled;

	// Debounced preview sync — shared by the update listener and the textarea.
	const syncToPreview = useCallback(() => {
		const pg = playGroupRef.current;
		if (!pg) return;
		if (timerRef.current) clearTimeout(timerRef.current);
		timerRef.current = setTimeout(() => {
			const container = getSelectedContainer();
			if (!container) return;
			const dom_settings = applySettingsToDom(container, pg);
			if (dom_settings?.target) {
				replayInPreview(dom_settings.target, pg);
			}
		}, 400);
	}, []);

	// Fetch the chunk, then create the editor once it is here. `alive` guards
	// the async gap: the panel can be closed, or the selection changed, while
	// a megabyte is still in flight.
	useEffect(() => {
		let alive = true;

		loadCodeMirror().then((cm) => {
			if (!alive || !containerRef.current) return;

			const { EditorState, Compartment } = cm.state;
			const { EditorView, keymap, placeholder: cmPlaceholder, lineNumbers,
				highlightActiveLineGutter, highlightActiveLine } = cm.view;
			const { defaultKeymap, indentWithTab } = cm.commands;
			const { css } = cm.langCss;
			const { oneDark } = cm.theme;
			const { syntaxHighlighting, defaultHighlightStyle, bracketMatching } = cm.language;
			const { autocompletion, closeBrackets } = cm.autocomplete;

			cmRef.current = { EditorState };
			readOnlyComp.current = new Compartment();

			const updateListener = EditorView.updateListener.of((update) => {
				if (update.docChanged) {
					onChangeRef.current(update.state.doc.toString());
					syncToPreview();
				}
			});

			const state = EditorState.create({
				// The PARENT's value, read at this moment rather than closed
				// over at mount: anything typed into the textarea while the
				// chunk was loading has to be the document CodeMirror opens on.
				doc: valueRef.current ?? '',
				extensions: [
					lineNumbers(),
					highlightActiveLineGutter(),
					highlightActiveLine(),
					bracketMatching(),
					closeBrackets(),
					autocompletion(),
					css(),
					oneDark,
					syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
					keymap.of([...defaultKeymap, indentWithTab]),
					cmPlaceholder(placeholder || PLACEHOLDER),
					EditorView.lineWrapping,
					readOnlyComp.current.of(EditorState.readOnly.of(!!disabledRef.current)),
					updateListener,
					// Compact sizing to fit the Elementor panel.
					EditorView.theme({
						'&': {
							fontSize: '12px',
							border: '1px solid #3a3a3a',
							borderRadius: '4px',
							overflow: 'hidden',
						},
						'.cm-scroller': {
							minHeight: '180px',
							maxHeight: '400px',
							overflow: 'auto',
							fontFamily: '"Fira Code", "Consolas", "Monaco", monospace',
							lineHeight: '1.5',
						},
						'.cm-gutters': {
							fontSize: '11px',
						},
						'&.cm-focused': {
							outline: '1px solid #6366f1',
						},
					}),
				],
			});

			viewRef.current = new EditorView({ state, parent: containerRef.current });
			setReady(true);
		}).catch(() => {
			// Stay on the textarea. Deliberately silent: a builder cannot act on
			// "the syntax highlighter did not load", and the control in front of
			// them still works.
		});

		return () => {
			alive = false;
			if (timerRef.current) clearTimeout(timerRef.current);
			if (viewRef.current) {
				viewRef.current.destroy();
				viewRef.current = null;
			}
		};
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, []); // Mount once — value changes are handled by the transaction below.

	// Sync external value changes into the editor (a breakpoint switch, undo/redo
	// from Elementor). Skipped when the editor itself was the source — the doc
	// already matches.
	useEffect(() => {
		const view = viewRef.current;
		if (!view) return;
		const current = view.state.doc.toString();
		const next = value ?? '';
		if (current !== next) {
			view.dispatch({ changes: { from: 0, to: current.length, insert: next } });
		}
	}, [value]);

	useEffect(() => {
		const view = viewRef.current;
		const cm = cmRef.current;
		if (!view || !cm || !readOnlyComp.current) return;
		view.dispatch({
			effects: readOnlyComp.current.reconfigure(cm.EditorState.readOnly.of(!!disabled)),
		});
	}, [disabled]);

	const onTextarea = useCallback((e) => {
		onChangeRef.current(e.target.value);
		syncToPreview();
	}, [syncToPreview]);

	return (
		<div style={{ width: '100%' }}>
			<div ref={containerRef} />
			{!ready && (
				<textarea
					value={value ?? ''}
					onChange={onTextarea}
					disabled={!!disabled}
					placeholder={placeholder || PLACEHOLDER}
					spellCheck={false}
					style={{
						width: '100%',
						minHeight: '180px',
						maxHeight: '400px',
						boxSizing: 'border-box',
						padding: '8px',
						fontSize: '12px',
						lineHeight: 1.5,
						fontFamily: '"Fira Code", "Consolas", "Monaco", monospace',
						color: '#e6e6e6',
						background: '#282c34',
						border: '1px solid #3a3a3a',
						borderRadius: '4px',
						resize: 'vertical',
					}}
				/>
			)}
		</div>
	);
}
