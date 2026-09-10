/* eslint-env browser */

/**
 * Coalesce the preview push that follows a settings write into one per frame.
 *
 * The Elementor command in a cell's setValue() stays SYNCHRONOUS — it is the
 * model write, and undo/redo plus the saved document depend on every value
 * landing in order. Only the preview-side push is coalesced here: dragging a
 * slider calls setValue() per pointer move, and applySettingsToDom() rebuilds
 * the element's config and rebinds its GSAP tween on each one, so a fast drag
 * queues rebuilds faster than a frame can paint them.
 *
 * Keyed by caller so two different controls can never cancel each other, and
 * the LAST call booked before the frame always wins — dropping the trailing
 * call would leave the canvas showing the value before the one the user
 * released on.
 *
 * The callback re-resolves whatever it needs when it runs: a container held
 * across the frame can be stale if Elementor re-rendered the element in
 * between.
 */
const pending = new Map();

export function schedulePreviewSync(key, fn) {
	const booked = pending.get(key);
	if (booked) {
		booked.fn = fn; // frame already booked — just carry the newest work
		return;
	}
	const entry = { fn };
	pending.set(key, entry);
	requestAnimationFrame(() => {
		pending.delete(key);
		try {
			entry.fn();
		} catch (_) {
			// A preview push racing a canvas teardown is not worth breaking the
			// panel over; the next write re-issues it.
		}
	});
}
