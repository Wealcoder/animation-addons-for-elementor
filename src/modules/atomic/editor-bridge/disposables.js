/* eslint-env browser */

/**
 * Disposable registry — every listener / observer / timer registers a
 * teardown function here so the whole bridge can be torn down in one call
 * (used on document switch and beforeunload).
 *
 * Usage:
 *   const off = track(() => button.removeEventListener('click', onClick));
 *   // later: off() to dispose this one early
 *   // or: disposeAll() to clear everything
 */

const disposables = new Set();

/** Register a teardown fn. Returns it so callers can dispose individually. */
export function track(fn) {
	disposables.add(fn);
	return fn;
}

/** Run every tracked teardown fn and clear the registry. */
export function disposeAll() {
	for (const fn of disposables) {
		try { fn(); } catch (_) { /* swallow — one failed teardown shouldn't stop the rest */ }
	}
	disposables.clear();
}
