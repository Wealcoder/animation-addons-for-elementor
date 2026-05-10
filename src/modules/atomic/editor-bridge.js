function getPreviewWindow() {
	return window.elementor
		&& window.elementor.$preview
		&& window.elementor.$preview[0]
		&& window.elementor.$preview[0].contentWindow;
}

function pipe() {
	const win = getPreviewWindow();

	if (!win || !win.aaeAtomicAnimations) {
		return false;
	}

	win.addEventListener('elementor/element/render', (event) => {
		const el = event.detail && event.detail.element;
		if (!el) {
			return;
		}
		win.aaeAtomicAnimations.rebind(el);
		win.aaeAtomicAnimations.scan(el);
	});

	return true;
}

function whenReady() {
	console.log('[Atomic Animations] Initializing editor bridge...');
	if (pipe()) {
		return;
	}
	setTimeout(whenReady, 400);
}

if (window.elementor && window.elementor.on) {
	window.elementor.on('preview:loaded', whenReady);
} else {
	document.addEventListener('DOMContentLoaded', whenReady);
}
