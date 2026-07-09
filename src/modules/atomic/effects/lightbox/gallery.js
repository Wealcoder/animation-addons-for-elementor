/* eslint-env browser */

/**
 * Group collection. Given a clicked trigger, gather every trigger that shares
 * its group into an ordered slide list, resolving each one's config from the
 * interactions map. Triggers with no explicit group fall back to the nearest
 * ancestor carrying `data-aae-lb-group` (this is how a custom widget — e.g. the
 * Accordion — auto-groups the images inside it without per-image config).
 *
 * A standalone trigger (no group anywhere) becomes a one-slide gallery.
 */

const { configFor } = window.AAEADDON;

function groupOf(trigger) {
	if (trigger.dataset.aaeLbGroup) return trigger.dataset.aaeLbGroup;
	const anc = trigger.closest('[data-aae-lb-group]');
	return anc ? anc.getAttribute('data-aae-lb-group') : '';
}

/**
 * @returns {{ slides: object[], startIndex: number, groupId: string }}
 */
export function collectGroup(trigger, mapName) {
	const group = groupOf(trigger);

	let triggers;
	if (group) {
		// All triggers resolving to this same group, in DOM order.
		triggers = Array.prototype.slice
			.call(document.querySelectorAll('[data-aae-lb]'))
			.filter((el) => groupOf(el) === group);
	} else {
		triggers = [trigger];
	}

	const slides = [];
	let startIndex = 0;

	triggers.forEach((el) => {
		const cfg = configFor(el, mapName);
		if (!cfg || !cfg.src) return;
		if (el === trigger) startIndex = slides.length;
		slides.push(cfg);
	});

	// Safety: clicked trigger had no map entry (shouldn't happen) — bail cleanly.
	if (!slides.length) {
		const cfg = configFor(trigger, mapName);
		if (cfg && cfg.src) slides.push(cfg);
	}

	return { slides, startIndex, groupId: group || '' };
}
