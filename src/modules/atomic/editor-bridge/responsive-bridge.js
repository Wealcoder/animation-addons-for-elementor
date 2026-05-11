/* eslint-env browser */

import { track } from './disposables';
import { applyResponsiveVisibility } from './responsive-visibility';
import { applyResponsivePlaceholders } from './responsive-placeholders';
import { allowFloatStepsOnNumberInputs } from './float-step-fix';

/**
 * Coordinates the three responsive panel modules:
 *   1. visibility — show/hide rows per active device
 *   2. placeholders — display cascaded parent value as input hint
 *   3. float-step-fix — let MUI number inputs accept decimals
 *
 * Each is rAF-throttled into a single scan so a burst of DOM mutations
 * (which Elementor emits on every panel re-render) costs one pass, not many.
 */

let responsiveScanQueued = false;

export function queueResponsiveScan() {
	if (responsiveScanQueued) return;
	responsiveScanQueued = true;
	requestAnimationFrame(() => {
		responsiveScanQueued = false;
		applyResponsiveVisibility();
		applyResponsivePlaceholders();
		allowFloatStepsOnNumberInputs();
	});
}

export function startResponsiveBridge() {
	// Re-scan on every panel mutation so newly-rendered rows are gated correctly.
	const observer = new MutationObserver(queueResponsiveScan);
	observer.observe(document.body, { childList: true, subtree: true });
	track(() => observer.disconnect());

	// React to the user switching Desktop/Tablet/Mobile/etc. in the editor toolbar.
	const onDeviceChange = () => queueResponsiveScan();
	window.addEventListener('elementor/device-mode/change', onDeviceChange);
	track(() => window.removeEventListener('elementor/device-mode/change', onDeviceChange));

	// Legacy channel as a belt-and-suspenders fallback.
	const dm = window.elementor?.channels?.deviceMode;
	if (dm?.on) {
		dm.on('change', onDeviceChange);
		track(() => dm.off?.('change', onDeviceChange));
	}

	queueResponsiveScan();
}
