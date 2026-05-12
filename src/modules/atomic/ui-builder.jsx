/* eslint-env browser */

import * as React from 'react';
import { createRoot } from 'react-dom/client';

import { panelLabels, rowFromLabel } from './editor-bridge/panel-rows';
import { getSelectedContainer, unwrap } from './editor-bridge/helpers';
import { track } from './editor-bridge/disposables';

const { useState, useEffect, useRef, useCallback } = React;

/**
 * AAE — Atomic Editor UI Builder
 * ==============================
 *
 * Toolkit for adding custom UI controls into Elementor V4 atomic widget
 * panels without registering a new atomic control type (which currently
 * needs internal/private Elementor React APIs).
 *
 * The approach:
 *
 *   1. PHP-side: keep ANY simple atomic control bound to the prop so the
 *      schema validates and the row reserves a slot in the panel. A plain
 *      Text_Control on a `String_Array_Prop_Type` works as a placeholder.
 *   2. JS-side: use a MutationObserver to detect that placeholder row in
 *      the DOM (matched by its visible label), then mount our own React
 *      component into a fresh <div> appended inside the row.
 *
 * Our React root is fully isolated from Elementor's React tree — Elementor
 * never inspects DOM nodes we own. We read & write atomic settings via the
 * container.settings.* API, so changes are persisted and undo/redo works.
 *
 * Usage:
 *
 *   import { morphPanelRowToReact, Repeater, Select, TextInput }
 *     from '../ui-builder';
 *
 *   morphPanelRowToReact({
 *     label: 'Custom Properties',
 *     render: (container) => (
 *       <MyCustomPropsRepeater container={container} />
 *     ),
 *   });
 */

/* =====================================================================
 * Atomic settings — read / write / subscribe
 * =================================================================== */

/**
 * Read a setting from an atomic container, automatically unwrapping the
 * `{ $$type, value }` envelope. Returns null when not set.
 */
export function readSetting(container, key) {
	if (!container || !container.settings) return null;
	const attrs = container.settings.attributes || {};
	const raw = attrs[key];
	const v = unwrap(raw);
	return (v === undefined) ? null : v;
}

/**
 * Write a SCALAR setting onto an atomic container. Wraps the value in the
 * `{ $$type, value }` envelope Elementor expects. `$$type` is inferred from
 * the JS value if not provided ('string' | 'number' | 'boolean').
 *
 * For arrays use writeSettingArray() — array props need the outer
 * `{ $$type: 'string-array', value: [...] }` wrap PLUS each item wrapped.
 *
 * Going through container.settings.set() makes the change participate in
 * undo/redo and triggers the standard 'change' event that other modules
 * (live-bridge, preview-pipe) listen on.
 */
export function writeSetting(container, key, value, $$type = null) {
	if (!container || !container.settings || !container.settings.set) return;

	if (!$$type) {
		if (typeof value === 'number')       $$type = 'number';
		else if (typeof value === 'boolean') $$type = 'boolean';
		else                                  $$type = 'string';
	}

	container.settings.set(key, { $$type, value });
}

/**
 * Read an array prop, unwrapping both the outer `{ $$type, value }` envelope
 * AND each item's wrapping. Returns a flat JS array of primitives ([] when
 * unset).
 */
export function readSettingArray(container, key) {
	const raw = readSetting(container, key);
	if (!Array.isArray(raw)) return [];
	return raw.map((item) => unwrap(item));
}

/**
 * Write an array prop. Wraps each item individually plus the outer array
 * envelope. `itemType` defaults to 'string' (String_Array_Prop_Type).
 */
export function writeSettingArray(container, key, items, itemType = 'string') {
	if (!container || !container.settings || !container.settings.set) return;
	if (!Array.isArray(items)) items = [];

	const wrappedItems = items.map((v) => ({ $$type: itemType, value: v }));
	const outerType    = itemType + '-array';

	container.settings.set(key, { $$type: outerType, value: wrappedItems });
}

/**
 * Subscribe to setting changes on a container. Returns an unsubscribe fn.
 * Uses Backbone's `change` event under the hood (atomic settings still
 * live on a Backbone model in v4).
 */
export function subscribeToSettings(container, callback) {
	if (!container || !container.settings || !container.settings.on) {
		return () => {};
	}
	container.settings.on('change', callback);
	return () => {
		try { container.settings.off('change', callback); } catch (_) {}
	};
}

/* =====================================================================
 * React hooks
 * =================================================================== */

/**
 * useContainerSetting — two-way binding for a single atomic prop.
 * Re-renders the host component whenever the prop changes (via undo,
 * via another control, or via our own write).
 *
 *   const [value, setValue] = useContainerSetting(container, 'aae_anim_effect');
 */
export function useContainerSetting(container, key) {
	const [value, setValue] = useState(() => readSetting(container, key));

	useEffect(() => {
		const unsubscribe = subscribeToSettings(container, () => {
			setValue(readSetting(container, key));
		});
		return unsubscribe;
	}, [container, key]);

	const update = useCallback((newValue) => {
		writeSetting(container, key, newValue);
		setValue(newValue);
	}, [container, key]);

	return [value, update];
}

/**
 * useContainerSettingArray — same as useContainerSetting but for array props.
 * Returns a flat array of primitives plus a setter that takes a plain array.
 */
export function useContainerSettingArray(container, key, itemType = 'string') {
	const [value, setValue] = useState(() => readSettingArray(container, key));

	useEffect(() => {
		const unsubscribe = subscribeToSettings(container, () => {
			setValue(readSettingArray(container, key));
		});
		return unsubscribe;
	}, [container, key]);

	const update = useCallback((newArr) => {
		writeSettingArray(container, key, newArr, itemType);
		setValue(Array.isArray(newArr) ? newArr : []);
	}, [container, key, itemType]);

	return [value, update];
}

/* =====================================================================
 * Panel row morphing — find by label, mount React inside
 * =================================================================== */

const MORPHED_FLAG = 'aaeReactMorphed';
const REACT_ROOTS  = new WeakMap();  // row → { root, container, cleanup }

/**
 * Find every panel row whose label equals `label` and replace its
 * editable area with a React component rendered by `render(container)`.
 *
 * `render` is called once per active selection; if the user selects a
 * different widget, the component remounts with the new container. The
 * MutationObserver re-runs on panel mutations to handle re-renders.
 *
 * Returns a cleanup fn (also auto-tracked via disposables).
 */
export function morphPanelRowToReact({ label: targetLabel, render }) {
	let observer = null;
	let scanQueued = false;

	function mountInto(row, container) {
		// Tear down a previous mount on this row first.
		unmountRow(row);

		const host = document.createElement('div');
		host.className = 'aae-react-control-host';
		host.style.width = '100%';
		host.style.flex  = '1 1 100%';

		// Replace the row's existing editable area, but keep the label.
		const editable = pickEditableCell(row);
		if (!editable || !editable.parentElement) return;
		const parent = editable.parentElement;
		parent.replaceChild(host, editable);

		// Stack label above the React host. By default atomic rows are
		// flex-direction:row (label left, control right) — but our React
		// content is wider than a single input so we lay it out as a column
		// and let the host take the full row width.
		const previousStyles = {
			flexDirection: parent.style.flexDirection,
			alignItems:    parent.style.alignItems,
			gap:           parent.style.gap,
		};
		parent.style.flexDirection = 'column';
		parent.style.alignItems    = 'stretch';
		if (!parent.style.gap) parent.style.gap = '8px';

		const root = createRoot(host);
		root.render(render(container));

		REACT_ROOTS.set(row, {
			root,
			container,
			host,
			previousNode: editable,
			parent,
			previousStyles,
		});
		row.dataset[MORPHED_FLAG] = '1';
	}

	function unmountRow(row) {
		const entry = REACT_ROOTS.get(row);
		if (!entry) return;
		try { entry.root.unmount(); } catch (_) {}

		// Restore the original editable node and the parent's pre-morph styles
		// (so PHP's original control would still work if we toggle off the morph).
		if (entry.host && entry.host.parentElement && entry.previousNode) {
			entry.host.parentElement.replaceChild(entry.previousNode, entry.host);
		}
		if (entry.parent && entry.previousStyles) {
			entry.parent.style.flexDirection = entry.previousStyles.flexDirection || '';
			entry.parent.style.alignItems    = entry.previousStyles.alignItems    || '';
			entry.parent.style.gap           = entry.previousStyles.gap           || '';
		}

		REACT_ROOTS.delete(row);
		delete row.dataset[MORPHED_FLAG];
	}

	function scan() {
		const container = getSelectedContainer();
		if (!container) return;

		for (const labelEl of panelLabels()) {
			const text = (labelEl.dataset.aaeOrigLabel || labelEl.textContent || '').trim();
			if (text !== targetLabel) continue;

			const row = rowFromLabel(labelEl);
			if (!row) continue;

			// Re-mount only if container changed or first time.
			const entry = REACT_ROOTS.get(row);
			if (entry && entry.container === container) continue;

			mountInto(row, container);
		}
	}

	function queueScan() {
		if (scanQueued) return;
		scanQueued = true;
		requestAnimationFrame(() => {
			scanQueued = false;
			scan();
		});
	}

	observer = new MutationObserver(queueScan);
	observer.observe(document.body, { childList: true, subtree: true });
	scan();

	const cleanup = () => {
		if (observer) {
			observer.disconnect();
			observer = null;
		}
		// Unmount every active root.
		document.querySelectorAll(`[data-${kebab(MORPHED_FLAG)}="1"]`).forEach(unmountRow);
	};
	track(cleanup);
	return cleanup;
}

function pickEditableCell(row) {
	// Prefer the MUI control wrapper, fall back to the right-most child.
	return (
		row.querySelector('.MuiFormControl-root')
		|| row.querySelector('.MuiInputBase-root')
		|| row.querySelector('.MuiBox-root:not(:first-child)')
		|| row.lastElementChild
	);
}

function kebab(camel) {
	return camel.replace(/[A-Z]/g, (m) => '-' + m.toLowerCase());
}

/* =====================================================================
 * Primitive components — styled to feel at home in Elementor's panel.
 *
 * No external CSS — inline styles only, so the toolkit drops into any
 * bundle without a separate stylesheet enqueue.
 * =================================================================== */

const PANEL_BG    = '#fff';
const PANEL_BORDER = 'rgba(0, 0, 0, 0.23)';
const PANEL_BORDER_HOVER = 'rgba(0, 0, 0, 0.87)';
const ACCENT      = '#0c977d';
const ACCENT_DARK = '#0a7e69';
const TEXT_COLOR  = '#0c0d0e';
const MUTED       = '#7f7f7f';

const baseInputStyle = {
	width: '100%',
	boxSizing: 'border-box',
	padding: '6px 10px',
	fontSize: '13px',
	lineHeight: '1.4',
	color: TEXT_COLOR,
	background: PANEL_BG,
	border: `1px solid ${PANEL_BORDER}`,
	borderRadius: '4px',
	outline: 'none',
};

/** Plain text input. Controlled. */
export function TextInput({ value, placeholder, onChange, type = 'text', disabled = false }) {
	return (
		<input
			type={type}
			value={value ?? ''}
			placeholder={placeholder}
			disabled={disabled}
			onChange={(e) => onChange?.(e.target.value)}
			style={{
				...baseInputStyle,
				opacity: disabled ? 0.5 : 1,
			}}
			onFocus={(e) => { e.target.style.borderColor = ACCENT; }}
			onBlur={(e)  => { e.target.style.borderColor = PANEL_BORDER; }}
		/>
	);
}

/** Native <select> styled to match. Options: array of { value, label }. */
export function Select({ value, options, onChange, placeholder = '', disabled = false }) {
	return (
		<select
			value={value ?? ''}
			disabled={disabled}
			onChange={(e) => onChange?.(e.target.value)}
			style={{
				...baseInputStyle,
				cursor: disabled ? 'not-allowed' : 'pointer',
				appearance: 'none',
				WebkitAppearance: 'none',
				MozAppearance: 'none',
				paddingRight: '24px',
				backgroundImage:
					'url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'10\' height=\'6\' viewBox=\'0 0 10 6\'><path fill=\'%237f7f7f\' d=\'M0 0l5 6 5-6z\'/></svg>")',
				backgroundRepeat: 'no-repeat',
				backgroundPosition: 'right 8px center',
			}}
		>
			{placeholder ? <option value="" disabled hidden>{placeholder}</option> : null}
			{(options || []).map((opt) => (
				<option key={opt.value} value={opt.value}>{opt.label}</option>
			))}
		</select>
	);
}

/** Solid accent button. */
export function Button({ children, onClick, variant = 'primary', disabled = false, title }) {
	const palette = variant === 'primary'
		? { bg: ACCENT,       bgHover: ACCENT_DARK, color: '#fff' }
		: { bg: 'transparent', bgHover: '#f1f1f1',   color: TEXT_COLOR };

	const [hover, setHover] = useState(false);
	return (
		<button
			type="button"
			title={title}
			disabled={disabled}
			onClick={onClick}
			onMouseEnter={() => setHover(true)}
			onMouseLeave={() => setHover(false)}
			style={{
				background: hover ? palette.bgHover : palette.bg,
				color: palette.color,
				border: variant === 'primary' ? '0' : `1px solid ${PANEL_BORDER}`,
				borderRadius: '4px',
				padding: '6px 12px',
				fontSize: '12px',
				fontWeight: 600,
				cursor: disabled ? 'not-allowed' : 'pointer',
				opacity: disabled ? 0.5 : 1,
				lineHeight: 1.4,
			}}
		>
			{children}
		</button>
	);
}

/** Icon-only button — for + / × on repeater rows. */
export function IconButton({ icon, onClick, title, disabled = false }) {
	const [hover, setHover] = useState(false);
	return (
		<button
			type="button"
			title={title}
			disabled={disabled}
			onClick={onClick}
			onMouseEnter={() => setHover(true)}
			onMouseLeave={() => setHover(false)}
			style={{
				background: hover ? '#f1f1f1' : 'transparent',
				color: MUTED,
				border: `1px solid ${PANEL_BORDER}`,
				borderRadius: '4px',
				width: '28px',
				height: '28px',
				display: 'inline-flex',
				alignItems: 'center',
				justifyContent: 'center',
				cursor: disabled ? 'not-allowed' : 'pointer',
				fontSize: '14px',
				lineHeight: 1,
				flexShrink: 0,
			}}
		>
			{icon}
		</button>
	);
}

/* =====================================================================
 * Repeater — higher-order primitive
 *
 * Owns row add / remove / reorder. Calls `renderRow(row, index, update)`
 * for each entry. `row` is the current item (any shape). `update(next)`
 * replaces the entire row. `onChange` fires with the full updated list.
 * =================================================================== */

export function Repeater({
	items,
	renderRow,
	onChange,
	addLabel = 'Add Item',
	makeEmpty = () => ({}),
	maxItems = null,
}) {
	const safeItems = Array.isArray(items) ? items : [];

	const updateAt = (index, next) => {
		const out = [...safeItems];
		out[index] = next;
		onChange?.(out);
	};

	const removeAt = (index) => {
		const out = safeItems.filter((_, i) => i !== index);
		onChange?.(out);
	};

	const addOne = () => {
		if (maxItems !== null && safeItems.length >= maxItems) return;
		onChange?.([...safeItems, makeEmpty()]);
	};

	return (
		<div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
			{safeItems.map((row, index) => (
				<div
					key={index}
					style={{
						display: 'flex',
						alignItems: 'center',
						gap: '6px',
						padding: '6px',
						background: '#f9f9f9',
						border: `1px solid ${PANEL_BORDER}`,
						borderRadius: '4px',
					}}
				>
					<div style={{ flex: 1, display: 'flex', gap: '6px', alignItems: 'center' }}>
						{renderRow(row, index, (next) => updateAt(index, next))}
					</div>
					<IconButton
						icon="×"
						title="Remove"
						onClick={() => removeAt(index)}
					/>
				</div>
			))}

			<Button
				variant="secondary"
				onClick={addOne}
				disabled={maxItems !== null && safeItems.length >= maxItems}
			>
				+ {addLabel}
			</Button>
		</div>
	);
}

/* =====================================================================
 * Re-export convenience — so feature files only need one import.
 * =================================================================== */

export { panelLabels, rowFromLabel } from './editor-bridge/panel-rows';
export { getSelectedContainer, getPreviewWindow, unwrap } from './editor-bridge/helpers';
