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
import { containerFor, collectContainer } from './container';
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

/**
 * Resolve a click into a lightbox open, trying both models:
 *   1. Per-element trigger (e-image / custom widget) — precise, config-backed.
 *   2. Container-level     (parent enabled) — discover child images from DOM.
 * Per-element wins when both match (a configured image inside a lightbox
 * container opens as its own precise slide set).
 *
 * @returns {boolean} whether a lightbox was opened.
 */
function resolveAndOpen(target) {
	// 1) Per-element.
	const trigger = findTrigger(target);
	if (trigger) {
		const { slides, startIndex, groupId } = collectGroup(trigger, LB_MAP);
		if (slides.length) {
			openLightbox(slides, startIndex, { groupId });
			return true;
		}
	}

	// 2) Container-level.
	const hit = containerFor(target);
	if (hit) {
		const { slides, startIndex, groupId } = collectContainer(hit.el, hit.cfg, target);
		if (slides.length) {
			// Per-container style bag → CSS vars on the shared overlay (opts.style).
			openLightbox(slides, startIndex, { groupId, style: hit.cfg.style || null });
			return true;
		}
	}

	return false;
}

function ensureDelegation() {
	if (delegated) return;
	delegated = true;
	exposeApi();

	const onClick = (e) => {
		if (isEditMode()) return;
		if (e.button !== undefined && e.button !== 0) return;
		// resolveAndOpen only opens on a real match, so ordinary links/buttons
		// are left untouched; we preventDefault only when we actually open.
		if (resolveAndOpen(e.target)) {
			e.preventDefault();
		}
	};

	const onKey = (e) => {
		if (isEditMode()) return;
		if (e.key !== 'Enter' && e.key !== ' ') return;
		if (resolveAndOpen(e.target)) {
			e.preventDefault();
		}
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
