/* eslint-env browser */

import { applySettingsToDom } from './settings-bridge';
import { featuresFor } from './features';

/**
 * On editor first-load, atomic widgets in the canvas are React-rendered
 * client-side. The PHP `elementor/widget/render_content` filter (which
 * normally injects InteractionsMap scripts) NEVER runs for those — so the
 * iframe ships without any of our `window.AAE_INTERACTIONS_*` maps.
 *
 * Solution: walk Elementor's container tree and call applySettingsToDom()
 * for every container with an active effect. That populates the maps on
 * the preview-iframe window, mirroring what server-side Render.php does
 * on publish.
 *
 * Tricky bit: `preview:loaded` fires before atomic widgets finish React-
 * mounting their settings into the container model. On the first poll
 * the children array can be empty even though widgets render shortly
 * after. We retry a few times until either:
 *   - we successfully seed at least one container, OR
 *   - we hit MAX_ATTEMPTS (means the document genuinely has no animated
 *     widgets — fine, stop polling).
 */

const MAX_ATTEMPTS = 10;
const RETRY_MS     = 250;

let seedDone        = false;
let seedAttempts    = 0;
let seedTimer       = null;
let onSeedSuccess   = null;

/** Recursively visit every container in Elementor's model tree. */
function walkContainers(container, visit) {
	if (!container) return;
	visit(container);
	const children = container.children;
	if (!children) return;
	// children is a Backbone collection on legacy containers, or an array
	// on atomic containers. Handle both.
	if (typeof children.each === 'function') {
		children.each((child) => walkContainers(child, visit));
	} else if (Array.isArray(children)) {
		children.forEach((child) => walkContainers(child, visit));
	}
}

function trySeed() {
	if (seedDone) return;
	seedAttempts++;

	const documents = window.elementor && window.elementor.documents;
	const doc = documents?.getCurrent?.();
	const root = doc?.container;
	if (!root) {
		scheduleRetry();
		return;
	}

	let seededCount = 0;
	let visitedCount = 0;

	walkContainers(root, (container) => {
		visitedCount++;
		const features = featuresFor(container);
		if (!features.length) return;

		// Quick "is any feature × any breakpoint animated?" gate. The full
		// cfg build happens in applySettingsToDom; here we just skip widgets
		// where every applicable feature has effect=none on every bp.
		//
		// For `aae-rj` enableSettings we walk the per-bp map. For non-rj
		// shapes (e.g. image-hover's enableSetting points at the image
		// prop) we pass through and let the feature's buildConfig decide.
		const settings = container.settings?.attributes || {};
		const anyActive = features.some((feature) => {
			const env = settings[feature.enableSetting];
			if (!env || typeof env !== 'object') return false;
			if (env.$$type !== 'aae-rj') return true;
			const map = env.value && typeof env.value === 'object' ? env.value : {};
			return Object.values(map).some((v) => v && v !== 'none');
		});
		if (!anyActive) return;

		applySettingsToDom(container);
		seededCount++;
	});

	// Tree only has the root container? Atomic widgets haven't mounted yet
	// — retry. Tree has descendants but none animated? Done.
	if (visitedCount <= 1) {
		scheduleRetry();
		return;
	}

	seedDone = true;

	// The caller (preview-pipe) wants to know when maps are populated so it
	// can kick off the first-load replay. We call back here instead of having
	// preview-pipe race with our retry timer.
	if (typeof onSeedSuccess === 'function') {
		try { onSeedSuccess(); } catch (_) { /* never let the callback throw */ }
	}
}

function scheduleRetry() {
	if (seedAttempts >= MAX_ATTEMPTS) {
		seedDone = true;
		return;
	}
	if (seedTimer) clearTimeout(seedTimer);
	seedTimer = setTimeout(trySeed, RETRY_MS);
}

export function seedAllAnimatedContainers(onSuccess) {
	onSeedSuccess = typeof onSuccess === 'function' ? onSuccess : null;
	trySeed();
}

export function resetSeedFlag() {
	seedDone = false;
	seedAttempts = 0;
	if (seedTimer) clearTimeout(seedTimer);
	seedTimer = null;
	onSeedSuccess = null;
}
