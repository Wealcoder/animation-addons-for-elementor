/* eslint-env browser */

/**
 * Access to the Elementor preview iframe window.
 *
 * The bridge runs in the editor's OUTER frame; the rendered document (where the
 * accordion/slider runtimes live) is inside the preview iframe. This resolves
 * that inner window so we can mirror live settings onto the preview DOM.
 */

/** The preview iframe's contentWindow, or null if not mounted yet. */
export function getPreviewWindow() {
	const iframe =
		document.querySelector('#elementor-preview-iframe') ||
		document.querySelector('iframe[name="elementor-preview-iframe"]');

	if (iframe?.contentWindow) {
		return iframe.contentWindow;
	}

	if (window.elementor?.$preview?.[0]?.contentWindow) {
		return window.elementor.$preview[0].contentWindow;
	}

	return null;
}
