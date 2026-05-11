/* eslint-env browser */

import { getPreviewWindow, getSelectedContainer } from './helpers';
import { featureFor } from './features';
import { applySettingsToDom, replayInPreview } from './settings-bridge';
import { track } from './disposables';

/**
 * "Play Animation" row → "Play Now" button morpher.
 *
 * Controls.php emits a Switch_Control with label "Play Animation" and meta
 * { aaePlayButton: true }. Atomic widgets don't expose a Button control
 * type, so we visually swap the switch UI with our own button. Clicking it
 * runs applySettingsToDom() + replayInPreview() on the active selection.
 *
 * Observer is scoped to document.body and rAF-throttled to avoid thrashing
 * on every panel mutation.
 */

const PLAY_LABEL_TEXT = 'Play Animation';
const REPLACED_FLAG   = 'aaePlayReplaced';

function buildPlayButton() {
	const btn = document.createElement('button');
	btn.type = 'button';
	btn.textContent = 'Play Now';
	btn.className = 'aae-play-now-btn';
	btn.style.cssText = [
		'background: #0c977d',
		'color: #fff',
		'border: 0',
		'border-radius: 4px',
		'padding: 6px 14px',
		'font-weight: 600',
		'cursor: pointer',
		'min-width: 90px',
	].join(';');

	const onClick = (e) => {
		e.preventDefault();
		e.stopPropagation();

		const container = getSelectedContainer();
		const feature   = container ? featureFor(container) : null;
		const win       = getPreviewWindow();

		if (!container || !feature || !win) {
			console.warn('[AAE] Play: no selection / unsupported widget / preview not ready.');
			return;
		}

		applySettingsToDom(container);
		const target = feature.findTarget(win.document, container.id);

		if (!target) {
			console.warn('[AAE] Play: target element not found in preview.');
			return;
		}

		if (!replayInPreview(target)) {
			console.warn('[AAE] Play: animation runtime (aaeAtomicAnimations) not available in preview. Is GSAP enqueued?');
			return;
		}

		const original = btn.textContent;
		btn.textContent = 'Played';
		btn.disabled = true;
		setTimeout(() => {
			btn.textContent = original;
			btn.disabled = false;
		}, 600);
	};

	btn.addEventListener('click', onClick);
	// Listener lives with the button DOM; GC handles it when the row is removed.

	return btn;
}

function morphRowToButton(row) {
	if (row.dataset[REPLACED_FLAG]) return;
	row.dataset[REPLACED_FLAG] = '1';

	const valueCell =
		row.querySelector('.MuiSwitch-root')
		|| row.querySelector('[role="switch"]')
		|| row.querySelector('input[type="checkbox"]');

	const target = (valueCell && valueCell.closest('.MuiFormControl-root, .MuiBox-root')) || valueCell;
	if (!target || !target.parentElement) return;

	target.parentElement.replaceChild(buildPlayButton(), target);
}

function scanPanelForPlayButton() {
	const labels = document.querySelectorAll('label, .MuiFormLabel-root, .MuiTypography-root');
	for (const label of labels) {
		if ((label.textContent || '').trim() !== PLAY_LABEL_TEXT) continue;
		const row =
			label.closest('[data-control-id]')
			|| label.closest('.MuiFormControl-root')
			|| label.closest('.MuiBox-root')
			|| label.parentElement;
		if (row) morphRowToButton(row);
	}
}

let panelObserver = null;
let panelScanQueued = false;

export function startPanelObserver() {
	if (panelObserver) return;

	const queueScan = () => {
		if (panelScanQueued) return;
		panelScanQueued = true;
		requestAnimationFrame(() => {
			panelScanQueued = false;
			scanPanelForPlayButton();
		});
	};

	panelObserver = new MutationObserver(queueScan);
	panelObserver.observe(document.body, { childList: true, subtree: true });
	scanPanelForPlayButton();

	track(() => {
		panelObserver?.disconnect();
		panelObserver = null;
	});
}
