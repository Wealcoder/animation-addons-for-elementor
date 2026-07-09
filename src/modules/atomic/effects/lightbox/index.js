/* eslint-env browser */

/**
 * AAE Atomic Lightbox — entry bundle.
 *
 * Registers a `lightbox` kind into the shared AAEADDON runtime so it takes part
 * in the same scan/rebind/replay dispatch as every other effect. Unlike the
 * per-element effects, the lightbox needs a single shared overlay, so the kind's
 * only job on bind is to install ONE delegated document listener. That listener
 * resolves the clicked trigger's group + config at click time and opens the
 * global overlay — which means dynamically-injected triggers (Loop Grid, Popups,
 * AJAX content) work with no re-binding.
 *
 * Config lives in window.AAE_INTERACTIONS_LB[<interactionId>], published by
 * Lightbox_Manager / Render.php.
 */

import './index.css';
import { openLightbox } from './overlay';
import { collectGroup } from './gallery';
import { exposeApi } from './registry';

const { configFor } = window.AAEADDON;
const LB_MAP = 'AAE_INTERACTIONS_LB';

let delegated = false;

/**
 * In the Elementor editor preview, clicks must keep selecting elements — the
 * lightbox opening would block editing. The bundle still loads (editor blanket-
 * enqueues everything) but stays inert.
 */
function isEditMode() {
	return !!(window.elementorFrontend
		&& typeof window.elementorFrontend.isEditMode === 'function'
		&& window.elementorFrontend.isEditMode());
}

function isTrigger(el) {
	// Either an element that carries a resolvable config in the map (core
	// e-image, keyed by data-interaction-id) OR one explicitly marked as a
	// lightbox trigger by a custom widget.
	return !!(el && (el.hasAttribute('data-aae-lb') || configFor(el, LB_MAP)));
}

function findTrigger(node) {
	let el = node;
	while (el && el.nodeType === 1) {
		if (isTrigger(el)) return el;
		el = el.parentElement;
	}
	return null;
}

function open(trigger) {
	const { slides, startIndex, groupId } = collectGroup(trigger, LB_MAP);
	if (!slides.length) return;
	openLightbox(slides, startIndex, { groupId });
}

function ensureDelegation() {
	if (delegated) return;
	delegated = true;
	exposeApi();

	const onClick = (e) => {
		if (isEditMode()) return;
		if (e.button !== undefined && e.button !== 0) return;
		const trigger = findTrigger(e.target);
		if (!trigger) return;
		e.preventDefault();
		open(trigger);
	};

	const onKey = (e) => {
		if (isEditMode()) return;
		if (e.key !== 'Enter' && e.key !== ' ') return;
		const trigger = findTrigger(e.target);
		if (!trigger) return;
		e.preventDefault();
		open(trigger);
	};

	document.addEventListener('click', onClick);
	document.addEventListener('keydown', onKey);
}

function register() {
	if (!window.AAEADDON || typeof window.AAEADDON.register !== 'function') {
		// Runtime not ready yet — retry on the microtask queue.
		Promise.resolve().then(register);
		return;
	}
	window.AAEADDON.register({
		name: 'lightbox',
		mapName: LB_MAP,
		boundFlag: 'aae-lb-bound',
		playedKey: '__aaeLbBound',
		read: (el) => configFor(el, LB_MAP),
		// No per-element tween — bind installs the single delegated listener.
		bind: () => ensureDelegation(),
		play: () => {},
	});

	// Also install delegation immediately: custom widgets mark triggers with
	// data-aae-lb but may not have a map entry the scanner keys off, so we
	// can't rely solely on the scanner discovering them.
	ensureDelegation();
}

register();
