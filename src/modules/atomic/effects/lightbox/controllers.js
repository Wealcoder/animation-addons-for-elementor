/* eslint-env browser */

/**
 * Overlay controllers. Each is attach-on-open / detach-on-close so no listener
 * survives a closed lightbox. Every attach() returns a disposer.
 *
 *   keyboard : ←/→ navigate, Esc close, Home/End jump
 *   wheel    : wheel navigates prev/next (throttled)
 *   touch    : horizontal swipe navigates; vertical swipe-down closes
 *   hash     : deep-link (#aae-lb-<group>-<index>); browser back closes
 */

export function attachKeyboard(api) {
	const onKey = (e) => {
		switch (e.key) {
			case 'Escape': api.close(); break;
			case 'ArrowRight': api.next(); break;
			case 'ArrowLeft': api.prev(); break;
			case 'Home': api.goTo(0); break;
			case 'End': api.goTo(api.count() - 1); break;
			default: return;
		}
		e.preventDefault();
	};
	document.addEventListener('keydown', onKey);
	return () => document.removeEventListener('keydown', onKey);
}

export function attachWheel(api, root) {
	let lock = false;
	const onWheel = (e) => {
		if (Math.abs(e.deltaY) < 8) return;
		e.preventDefault();
		if (lock) return;
		lock = true;
		if (e.deltaY > 0) api.next(); else api.prev();
		setTimeout(() => { lock = false; }, 320);
	};
	root.addEventListener('wheel', onWheel, { passive: false });
	return () => root.removeEventListener('wheel', onWheel);
}

export function attachTouch(api, root) {
	let x0 = 0;
	let y0 = 0;
	let tracking = false;

	const start = (e) => {
		if (e.touches.length !== 1) return;
		tracking = true;
		x0 = e.touches[0].clientX;
		y0 = e.touches[0].clientY;
	};
	const end = (e) => {
		if (!tracking) return;
		tracking = false;
		const t = e.changedTouches[0];
		const dx = t.clientX - x0;
		const dy = t.clientY - y0;
		if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
			if (dx < 0) api.next(); else api.prev();
		} else if (dy > 90 && Math.abs(dy) > Math.abs(dx)) {
			api.close();
		}
	};
	root.addEventListener('touchstart', start, { passive: true });
	root.addEventListener('touchend', end, { passive: true });
	return () => {
		root.removeEventListener('touchstart', start);
		root.removeEventListener('touchend', end);
	};
}

export function attachHash(api) {
	const group = api.groupId() || 'x';
	const write = () => {
		const hash = `#aae-lb-${group}-${api.index()}`;
		if (window.location.hash !== hash) {
			try { history.replaceState(null, '', hash); } catch (_) { /* ignore */ }
		}
	};
	// Push one entry on open so the browser back button closes the lightbox.
	try { history.pushState({ aaeLb: true }, '', `#aae-lb-${group}-${api.index()}`); } catch (_) { /* ignore */ }

	const onPop = () => api.close(true);
	window.addEventListener('popstate', onPop);

	const unsubscribe = api.onChange(write);

	return () => {
		window.removeEventListener('popstate', onPop);
		unsubscribe();
		// Strip our hash on close.
		if (window.location.hash.indexOf('#aae-lb-') === 0) {
			try { history.replaceState(null, '', window.location.pathname + window.location.search); } catch (_) { /* ignore */ }
		}
	};
}

/**
 * Pointer/pinch zoom on the current media element. Toggle via the toolbar or
 * double-tap; drag to pan while zoomed. Returns { toggle, reset, dispose }.
 */
export function attachZoom(getMediaEl) {
	let zoomed = false;
	let scale = 1;
	let tx = 0;
	let ty = 0;
	let dragging = false;
	let sx = 0;
	let sy = 0;

	const apply = (el) => {
		el.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
	};

	const onDown = (e) => {
		const el = getMediaEl();
		if (!el || !zoomed) return;
		dragging = true;
		el.classList.add('is-dragging');
		sx = e.clientX - tx;
		sy = e.clientY - ty;
	};
	const onMove = (e) => {
		if (!dragging) return;
		tx = e.clientX - sx;
		ty = e.clientY - sy;
		apply(getMediaEl());
	};
	const onUp = () => {
		dragging = false;
		const el = getMediaEl();
		if (el) el.classList.remove('is-dragging');
	};

	document.addEventListener('pointermove', onMove);
	document.addEventListener('pointerup', onUp);

	return {
		toggle() {
			const el = getMediaEl();
			if (!el) return;
			zoomed = !zoomed;
			scale = zoomed ? 2 : 1;
			tx = 0; ty = 0;
			el.classList.toggle('is-zoomed', zoomed);
			el.addEventListener('pointerdown', onDown);
			apply(el);
		},
		reset() {
			const el = getMediaEl();
			zoomed = false; scale = 1; tx = 0; ty = 0;
			if (el) {
				el.classList.remove('is-zoomed');
				el.style.transform = '';
			}
		},
		isZoomed() { return zoomed; },
		dispose() {
			document.removeEventListener('pointermove', onMove);
			document.removeEventListener('pointerup', onUp);
		},
	};
}
