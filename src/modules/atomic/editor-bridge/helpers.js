/* eslint-env browser */

/**
 * Editor / preview-iframe helpers shared across modules.
 */

/** The preview iframe's window, or null if Elementor hasn't booted yet. */
export function getPreviewWindow() {
	return window.elementor
		&& window.elementor.$preview
		&& window.elementor.$preview[0]
		&& window.elementor.$preview[0].contentWindow;
}

/** The first currently-selected atomic container, or null if none. */
export function getSelectedContainer() {
	try {
		const els = window.elementor?.selection?.getElements?.();
		if (els && els.length) {
			return els[0];
		}
	} catch (_) { /* swallow — selection API not yet ready */ }
	return null;
}

/**
 * Atomic settings are stored wrapped as { $$type, value }. unwrap() returns
 * the raw value, passing through non-wrapped values unchanged.
 */
export function unwrap(v) {
	return (v && typeof v === 'object' && '$$type' in v) ? v.value : v;
}
