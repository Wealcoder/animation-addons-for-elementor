/* eslint-env browser */
import * as React from 'react';
import { useRef, useCallback, useEffect } from 'react';
import { EditorState, Compartment } from '@codemirror/state';
import { EditorView, keymap, placeholder as cmPlaceholder } from '@codemirror/view';
import { defaultKeymap, indentWithTab } from '@codemirror/commands';
import { css } from '@codemirror/lang-css';
import { oneDark } from '@codemirror/theme-one-dark';
import { syntaxHighlighting, defaultHighlightStyle, bracketMatching } from '@codemirror/language';
import { autocompletion, closeBrackets } from '@codemirror/autocomplete';
import { lineNumbers, highlightActiveLineGutter, highlightActiveLine } from '@codemirror/view';
import { getSelectedContainer } from '../../editor-bridge/helpers';
import { applySettingsToDom } from '../../editor-bridge/settings-bridge';
import { replayInPreview } from '../../editor-bridge/settings-bridge';

/**
 * CodeMirror 6 CSS editor with debounced live-preview sync.
 *
 * Provides syntax highlighting, auto-completion, bracket matching,
 * and line numbers. Changes are debounced (400ms) before syncing
 * to the preview iframe.
 */
export function CodeInput({ value, onChange, disabled, placeholder, play_group = '' }) {
	const containerRef = useRef(null);
	const viewRef = useRef(null);
	const timerRef = useRef(null);
	const onChangeRef = useRef(onChange);
	const playGroupRef = useRef(play_group);
	const readOnlyComp = useRef(new Compartment());

	// Keep refs current so the EditorView listener always reads fresh callbacks.
	onChangeRef.current = onChange;
	playGroupRef.current = play_group;

	// Debounced preview sync â€” shared by the update listener.
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

	// Create the editor once.
	useEffect(() => {
		if (!containerRef.current) return;

		const updateListener = EditorView.updateListener.of((update) => {
			if (update.docChanged) {
				const doc = update.state.doc.toString();
				onChangeRef.current(doc);
				syncToPreview();
			}
		});

		const state = EditorState.create({
			doc: value ?? '',
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
				cmPlaceholder(placeholder || 'selector {\n  /* Your custom CSS here */\n}'),
				EditorView.lineWrapping,
				readOnlyComp.current.of(EditorState.readOnly.of(!!disabled)),
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

		const view = new EditorView({
			state,
			parent: containerRef.current,
		});

		viewRef.current = view;

		return () => {
			if (timerRef.current) clearTimeout(timerRef.current);
			view.destroy();
			viewRef.current = null;
		};
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, []);  // Mount once â€” value changes are handled via the transaction below.

	// Sync external value changes into the editor (e.g. breakpoint switch,
	// undo/redo from Elementor). Skip when the editor itself is the source
	// of the change (the doc will already match).
	useEffect(() => {
		const view = viewRef.current;
		if (!view) return;
		const current = view.state.doc.toString();
		const next = value ?? '';
		if (current !== next) {
			view.dispatch({
				changes: { from: 0, to: current.length, insert: next },
			});
		}
	}, [value]);

	// Sync disabled state.
	useEffect(() => {
		const view = viewRef.current;
		if (!view) return;
		view.dispatch({
			effects: readOnlyComp.current.reconfigure(EditorState.readOnly.of(!!disabled)),
		});
	}, [disabled]);

	return <div ref={containerRef} style={{ width: '100%' }} />;
}
