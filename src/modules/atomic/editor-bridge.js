/* eslint-env browser */

/**
 * Animation Addons — Atomic Editor Bridge
 *
 * Three responsibilities:
 *   1. Mirror atomic widget settings into preview-iframe DOM data-attrs as the
 *      user edits — no save/reload required.
 *   2. Re-bind the runtime animation handler whenever an element re-renders.
 *   3. Inject a "Play Now" button into the panel that replays the animation
 *      on the currently-selected widget.
 *
 * Adding a new widget = add one entry to FEATURES below. Everything else
 * (live-bridge, play button, selectors, replay) flows from that table.
 *
 * Cleanup-aware: every listener / observer / timer is tracked so we can
 * tear them down when the document switches or the bridge is torn down.
 */


/* =====================================================================
 * 1. Feature registry — declare each animation feature once here.
 * =================================================================== */

const FEATURES = [
	{
		name: 'text-animation',
		widgetTypes: ['e-heading', 'e-paragraph'],
		enableSetting: 'aae_text_effect',
		autoReplaySetting: 'aae_text_enable_editor',
		attrMap: {
			aae_text_effect: 'data-aae-text-anim',
			aae_text_trigger: 'data-aae-text-trigger',
			aae_text_trigger_selector: 'data-aae-text-trigger-selector',
			aae_text_wrapper: 'data-aae-text-wrapper',
			aae_text_wrapper_selector: 'data-aae-text-wrapper-selector',
			aae_text_delay: 'data-aae-text-delay',
			aae_text_duration: 'data-aae-text-duration',
			aae_text_stagger: 'data-aae-text-stagger',
			aae_text_translate_x: 'data-aae-text-translate-x',
			aae_text_translate_y: 'data-aae-text-translate-y',
			aae_text_rotation_dir: 'data-aae-text-rotation-dir',
			aae_text_rotation: 'data-aae-text-rotation',
			aae_text_transform_origin: 'data-aae-text-transform-origin',
			aae_text_enable_editor: 'data-aae-text-enable-editor',
		},
		findTarget: (doc, id) => doc.querySelector(`[data-interaction-id="${id}"]`),
	},
	{
		name: 'regular-animation',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_anim_effect',
		autoReplaySetting: 'aae_anim_enable_editor',
		attrMap: {
			aae_anim_effect: 'data-aae-anim',
			aae_anim_trigger: 'data-aae-trigger',
			aae_anim_duration: 'data-aae-duration',
			aae_anim_delay: 'data-aae-delay',
			aae_anim_easing: 'data-aae-easing',
			aae_anim_repeat: 'data-aae-repeat',
			aae_anim_enable_editor: 'data-aae-enable-editor',
		},
		findTarget: (doc, id) => doc.querySelector(`[data-id="${id}"]`),
	},
];

/* =====================================================================
 * 2. Disposable registry — every listener / observer / timer adds a
 *    teardown function here so we can clean up in one call.
 * =================================================================== */

const disposables = new Set();

/** Register a teardown fn. Returns it so callers can dispose individually. */
function track(fn) {
	disposables.add(fn);
	return fn;
}

function disposeAll() {
	for (const fn of disposables) {
		try { fn(); } catch (_) { /* swallow */ }
	}
	disposables.clear();
}

/* =====================================================================
 * 3. Generic helpers
 * =================================================================== */

function getPreviewWindow() {
	return window.elementor
		&& window.elementor.$preview
		&& window.elementor.$preview[0]
		&& window.elementor.$preview[0].contentWindow;
}

function getSelectedContainer() {
	try {
		const els = window.elementor?.selection?.getElements?.();
		if (els && els.length) {
			return els[0];
		}
	} catch (_) { }
	return null;
}

function unwrap(v) {
	return (v && typeof v === 'object' && '$$type' in v) ? v.value : v;
}

function featureFor(container) {
	const type = container?.model?.get?.('widgetType')
		|| container?.model?.get?.('elType');
	return FEATURES.find((f) => f.widgetTypes.includes(type)) || null;
}

/* =====================================================================
 * 4. Apply settings → preview DOM data-attrs
 * =================================================================== */

// Every breakpoint that may carry per-device variants. Listed in PARENT_CASCADE
// keys; we duplicate here so this function doesn't need to look it up.
const ALL_BREAKPOINT_KEYS = [
	'widescreen', 'laptop', 'tablet_extra', 'tablet', 'mobile_extra', 'mobile',
];

/** Clear every breakpoint variant of a data-attr we may have set previously. */
function clearAllVariantAttrs(target, baseAttr) {
	for (const bp of ALL_BREAKPOINT_KEYS) {
		target.removeAttribute(baseAttr + '-' + bp);
	}
}

function applySettingsToDom(container) {
	const feature = featureFor(container);
	if (!feature) return null;

	const win = getPreviewWindow();
	if (!win) return null;

	const target = feature.findTarget(win.document, container.id);
	if (!target) return null;

	const settings = container.settings.attributes || {};
	const enableValue = unwrap(settings[feature.enableSetting]);

	// Strip stale attrs first — base + every per-breakpoint variant.
	for (const attr of Object.values(feature.attrMap)) {
		target.removeAttribute(attr);
		clearAllVariantAttrs(target, attr);
	}

	if (!enableValue || enableValue === 'none') {
		return { feature, target, active: false };
	}

	// Cache desktop values so variants can inherit from them.
	const desktopByAttr = {};

	for (const [key, attr] of Object.entries(feature.attrMap)) {
		let value = unwrap(settings[key]);
		if (value === undefined || value === null) continue;
		if (typeof value === 'boolean') value = value ? '1' : '0';
		target.setAttribute(attr, String(value));
		desktopByAttr[attr] = value;
	}

	// Now write per-breakpoint variants for responsive settings. Cascade:
	// empty variant → use the desktop value (same logic Render.php applies).
	for (const base of AAE_RESPONSIVE_BASES) {
		const baseAttr = feature.attrMap[base];
		if (!baseAttr) continue; // Not a feature-tracked attr (e.g. wrapper isn't in attrMap)

		const desktopValue = desktopByAttr[baseAttr];

		for (const bp of ALL_BREAKPOINT_KEYS) {
			const variantKey  = base + '_' + bp;
			const variantAttr = baseAttr + '-' + bp;

			let value = unwrap(settings[variantKey]);
			if (value === undefined || value === null || value === '') {
				value = desktopValue; // inherit
			}
			if (value === undefined || value === null) continue;
			if (typeof value === 'boolean') value = value ? '1' : '0';

			target.setAttribute(variantAttr, String(value));
		}
	}

	return { feature, target, active: true };
}

function replayInPreview(target) {
	const win = getPreviewWindow();
	const api = win && win.aaeAtomicAnimations;
	if (!api || !target) return false;

	if (typeof api.replay === 'function') {
		api.replay(target);
	} else if (typeof api.rebind === 'function') {
		api.rebind(target);
	}
	return true;
}

/* =====================================================================
 * 5. Live-edit bridge — auto-detaches on container destroy or when the
 *    selection moves to a different container.
 * =================================================================== */

let activeContainer = null;
let activeChangeHandler = null;
let activeDestroyHandler = null;

function detachLiveBridge() {
	if (activeContainer) {
		if (activeChangeHandler) {
			activeContainer.settings?.off?.('change', activeChangeHandler);
		}
		if (activeDestroyHandler) {
			activeContainer.model?.off?.('destroy', activeDestroyHandler);
		}
	}
	activeContainer = null;
	activeChangeHandler = null;
	activeDestroyHandler = null;
}

function attachLiveBridge(container) {
	if (!container || !container.settings) return;

	if (activeContainer === container) {
		applySettingsToDom(container);
		return;
	}

	detachLiveBridge();
	activeContainer = container;

	activeChangeHandler = () => {
		// Refresh placeholder hints — parent value may have changed.
		queueResponsiveScan();

		const result = applySettingsToDom(container);
		if (!result || !result.active) return;

		const autoKey = result.feature.autoReplaySetting;
		if (autoKey && unwrap(container.settings.attributes[autoKey])) {
			replayInPreview(result.target);
		}
	};

	activeDestroyHandler = () => detachLiveBridge();

	container.settings.on('change', activeChangeHandler);
	container.model?.on?.('destroy', activeDestroyHandler);

	applySettingsToDom(container);
}

let liveBridgeStarted = false;

function startLiveBridge() {
	if (liveBridgeStarted) return;
	liveBridgeStarted = true;

	const tryAttach = () => attachLiveBridge(getSelectedContainer());

	const editorChannel = window.elementor?.channels?.editor;
	if (editorChannel?.on) {
		editorChannel.on('section:activated', tryAttach);
		track(() => editorChannel.off?.('section:activated', tryAttach));
	}

	const selection = window.elementor?.selection;
	if (selection?.on) {
		selection.on('change:added change:removed', tryAttach);
		track(() => selection.off?.('change:added change:removed', tryAttach));
	}

	track(detachLiveBridge);
	tryAttach();
}

/* =====================================================================
 * 6. Preview-iframe pipe — listener is tracked so it can be removed
 *    when the document switches.
 * =================================================================== */

function pipePreviewRenderEvents() {
	const win = getPreviewWindow();
	if (!win || !win.aaeAtomicAnimations) return false;

	const handler = (event) => {
		const el = event.detail && event.detail.element;
		if (!el) return;
		win.aaeAtomicAnimations.rebind(el);
		win.aaeAtomicAnimations.scan(el);
	};

	win.addEventListener('elementor/element/render', handler);
	track(() => {
		try { win.removeEventListener('elementor/element/render', handler); } catch (_) { }
	});

	return true;
}

const MAX_PIPE_ATTEMPTS = 50;
let pipeAttempts = 0;
let pipeTimer = null;

function tryPipe() {
	pipeAttempts++;

	if (pipePreviewRenderEvents()) {
		return;
	}

	if (pipeAttempts >= MAX_PIPE_ATTEMPTS) {
		console.warn('[AAE] Gave up waiting for preview iframe. Is GSAP enqueued?');
		return;
	}

	pipeTimer = setTimeout(tryPipe, 400);
}

// Cancel any in-flight retry on teardown.
track(() => {
	if (pipeTimer) clearTimeout(pipeTimer);
	pipeTimer = null;
});

/* =====================================================================
 * 7. Play button injector — observer is scoped to the panel root when
 *    findable and rAF-throttled to avoid thrashing on every DOM mutation.
 * =================================================================== */

const PLAY_LABEL_TEXT = 'Play Animation';
const REPLACED_FLAG = 'aaePlayReplaced';

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
		const feature = container ? featureFor(container) : null;
		const win = getPreviewWindow();

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

function startPanelObserver() {
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

/* =====================================================================
 * 7b. Responsive visibility — hide (Tablet)/(Mobile) control rows when
 *     the editor's device mode doesn't match.
 *
 * Convention: each responsive control's label ends with " (Tablet)" or
 * " (Mobile)". Desktop controls have no suffix. We read the editor's
 * current device mode from elementor.channels.deviceMode and toggle the
 * rows accordingly. Runs on initial scan + on every device-mode change.
 * =================================================================== */

/**
 * All possible breakpoint suffix labels — must match the strings produced by
 * Controls.php (which reads Schema::BREAKPOINT_LABELS server-side). Mode key
 * is the device-mode value Elementor's channel emits.
 */
const RESPONSIVE_SUFFIX_BY_MODE = {
	widescreen:   '(Widescreen)',
	laptop:       '(Laptop)',
	tablet_extra: '(Tablet Extra)',
	tablet:       '(Tablet)',
	mobile_extra: '(Mobile Extra)',
	mobile:       '(Mobile)',
};

/** Every suffix string we might encounter, ordered longest-first so " (Tablet Extra)"
 *  is matched before " (Tablet)". */
const ALL_SUFFIXES = Object.values(RESPONSIVE_SUFFIX_BY_MODE)
	.slice()
	.sort((a, b) => b.length - a.length);

function currentDeviceMode() {
	try {
		return window.elementor?.channels?.deviceMode?.request?.('currentMode') || 'desktop';
	} catch (_) {
		return 'desktop';
	}
}

/**
 * Walk up from a label DOM node to the *full row container* — the outer
 * settings-field wrapper that holds both the label cell and the input cell.
 * Atomic widgets render each control as:
 *   <span data-type="settings-field">
 *     <div class="MuiBox-root">         ← display: none flips here when hidden
 *       <div class="MuiStack-root"><label/></div>
 *       <span class="eui-..."> ...input/select... </span>
 *     </div>
 *   </span>
 * We target the inner MuiBox-root (it's the one Elementor toggles), falling
 * back to the legacy selectors for non-atomic panels.
 */
function rowFromLabel(label) {
	const fieldWrap = label.closest('[data-type="settings-field"]');
	if (fieldWrap) {
		return fieldWrap.querySelector(':scope > .MuiBox-root') || fieldWrap;
	}
	return label.closest('[data-control-id]')
		|| label.closest('.MuiFormControl-root')
		|| label.closest('.MuiBox-root')
		|| label.parentElement;
}

/** Find which breakpoint suffix (if any) a label ends with. Returns null for desktop rows. */
function matchSuffix(text) {
	for (const suffix of ALL_SUFFIXES) {
		if (text.endsWith(suffix)) return suffix;
	}
	return null;
}

/**
 * Visual cleanup: PHP emits labels like "Animation (Laptop)" because the JS
 * needs the suffix to identify which breakpoint a row belongs to. Cache the
 * original on a data-attr the first time we see it, then strip the suffix
 * from the visible text. If MUI re-renders the label, the data-attr might be
 * wiped — so we also stash the original in a WeakMap as a fallback.
 */
const originalLabelCache = new WeakMap();

function stripVisibleSuffix(label, originalText, suffix) {
	originalLabelCache.set(label, originalText);
	if (!label.dataset.aaeOrigLabel) {
		label.dataset.aaeOrigLabel = originalText;
	}
	const cleaned = originalText.slice(0, -suffix.length).trimEnd();
	if (label.textContent !== cleaned) {
		label.textContent = cleaned;
	}
}

/** Returns the original (suffix-bearing) label text — what PHP emitted. */
function originalLabelText(label) {
	const fromMap = originalLabelCache.get(label);
	if (fromMap) return fromMap;
	if (label.dataset.aaeOrigLabel) return label.dataset.aaeOrigLabel;
	return (label.textContent || '').trim();
}

function applyResponsiveVisibility() {
	const mode = currentDeviceMode();
	const activeSuffix = RESPONSIVE_SUFFIX_BY_MODE[mode] || null; // null = desktop

	const labels = document.querySelectorAll('label, .MuiFormLabel-root, .MuiTypography-root');

	// Pass 1: collect base labels that have any breakpoint variant, so we can
	// hide the corresponding desktop row when a non-desktop mode is active.
	const baseLabelsWithVariants = new Set();
	for (const label of labels) {
		const text = originalLabelText(label);
		const suffix = matchSuffix(text);
		if (suffix) {
			baseLabelsWithVariants.add(text.slice(0, -suffix.length).trim());
		}
	}

	// Pass 2: toggle every row's display based on the active mode.
	for (const label of labels) {
		const text = originalLabelText(label);
		const suffix = matchSuffix(text);
		const row = rowFromLabel(label);
		if (!row) continue;

		if (suffix) {
			// Per-breakpoint row — show only when it matches the active mode,
			// and strip the "(Laptop)" / "(Mobile)" suffix from the visible
			// label. Original text is cached so subsequent scans still match.
			stripVisibleSuffix(label, text, suffix);
			row.style.display = (suffix === activeSuffix) ? '' : 'none';
		} else if (mode !== 'desktop' && baseLabelsWithVariants.has(text)) {
			// Desktop row of a setting that has variants — hide when off-desktop.
			row.style.display = 'none';
		} else {
			// Anything else (non-responsive row) — leave alone, but reset if we set it earlier.
			if (row.style.display === 'none' && baseLabelsWithVariants.has(text)) {
				row.style.display = '';
			}
		}
	}
}

let responsiveScanQueued = false;
function queueResponsiveScan() {
	if (responsiveScanQueued) return;
	responsiveScanQueued = true;
	requestAnimationFrame(() => {
		responsiveScanQueued = false;
		applyResponsiveVisibility();
		applyResponsivePlaceholders();
		allowFloatStepsOnNumberInputs();
	});
}

/**
 * Elementor's atomic Number_Control ships with `step="1"` on its native input,
 * which makes the browser round typed decimals to integers on blur. Our schema
 * declares these props as float, and the React reader parses via Number() when
 * `shouldForceInt: false` — but the HTML constraint wins first.
 *
 * Fix: rewrite step="1" → step="any" on the inputs of every label we know
 * should accept floats. Targets Duration / Delay / Stagger / Transform-X /
 * Transform-Y / Rotation Value / Repeat etc. — every numeric AAE field.
 */
const FLOAT_LABEL_BASES = new Set([
	'Duration', 'Duration (ms)',
	'Delay', 'Delay (ms)',
	'Stagger',
	'Transform-X', 'Transform-Y',
	'Rotation Value',
]);

function allowFloatStepsOnNumberInputs() {
	const labels = document.querySelectorAll('label, .MuiFormLabel-root, .MuiTypography-root');
	for (const label of labels) {
		const text = originalLabelText(label);
		if (!FLOAT_LABEL_BASES.has(text)) continue;

		const row = rowFromLabel(label);
		if (!row) continue;

		const inputs = row.querySelectorAll('input[type="number"]');
		for (const input of inputs) {
			if (input.getAttribute('step') !== 'any') {
				input.setAttribute('step', 'any');
			}
		}
	}
}

/* =====================================================================
 * Placeholder hints: when a non-desktop row's input is empty, show the
 * cascaded parent value as the input's native HTML placeholder. As soon
 * as the user types something, the browser hides the placeholder; clearing
 * the input brings it back. The stored prop stays null until explicitly set.
 * =================================================================== */

/** True if the row contains a select-like control (MUI / native / aria combobox). */
function isSelectRow(row) {
	return !! (
		row.querySelector('[role="combobox"]')
		|| row.querySelector('[role="listbox"]')
		|| row.querySelector('.MuiSelect-select')
		|| row.querySelector('.MuiSelect-root')
		|| row.querySelector('select')
	);
}

/**
 * Find an editable input/textarea inside a control row. Skips hidden / checkbox /
 * radio types AND skips inputs that are part of a MUI Select (they're hidden
 * form-data carriers, not what the user types into).
 */
function inputFromRow(row) {
	const candidates = row.querySelectorAll('input, textarea');
	for (const el of candidates) {
		if (el.tagName === 'TEXTAREA') return el;

		const t = (el.getAttribute('type') || 'text').toLowerCase();
		if (t === 'checkbox' || t === 'hidden' || t === 'radio') continue;

		// Skip MUI Select's internal native input.
		if (el.classList.contains('MuiSelect-nativeInput')) continue;
		if (el.closest('.MuiSelect-root, .MuiSelect-select')) continue;
		if (el.getAttribute('aria-hidden') === 'true') continue;

		return el;
	}
	return null;
}

/** Walk the parent cascade for a base setting; return the first non-empty value. */
function cascadedParentValue(container, base, mode) {
	const chain = PARENT_CASCADE[mode] || [];
	for (const parent of chain) {
		const key = (parent === 'desktop') ? base : (base + '_' + parent);
		const v = readSetting(container, key);
		if (!isEmptyValue(v)) return v;
	}
	return undefined;
}

/**
 * Find the MUI Select's visible "value" cell — the box that shows the selected
 * option (or is empty when nothing is picked). We overlay our placeholder there.
 */
function selectDisplayCell(row) {
	return row.querySelector('.MuiSelect-select')
		|| row.querySelector('[role="combobox"]')
		|| null;
}

/** Remove the in-field placeholder overlay from a select row. */
function clearInheritHint(row) {
	const cell = selectDisplayCell(row);
	if (!cell) return;
	const overlay = cell.querySelector(':scope > .aae-select-placeholder');
	if (overlay) overlay.remove();
}

/**
 * Overlay an italic placeholder string inside an *empty* MUI Select cell so
 * the inherited value reads exactly like a native input placeholder. We only
 * add the overlay when the cell has no real selected text — the moment the
 * user picks an option, MUI fills the cell and this overlay is removed on
 * the next scan.
 */
function setInheritHint(row, hintText) {
	const cell = selectDisplayCell(row);
	if (!cell) return;

	// If the select already has a real value, MUI puts it in the cell as
	// (mostly) text nodes. Detect this by looking at non-overlay text content.
	// MUI also inserts zero-width / non-breaking spaces to keep line height —
	// strip those before deciding "is the cell empty".
	const ownText = Array.from(cell.childNodes)
		.filter((n) => !(n.nodeType === 1 && n.classList?.contains('aae-select-placeholder')))
		.map((n) => (n.textContent || '').replace(/[​ \s]+/g, ''))
		.join('');

	if (ownText) {
		// User-chosen value present — strip overlay and bail.
		const stale = cell.querySelector(':scope > .aae-select-placeholder');
		if (stale) stale.remove();
		return;
	}

	let overlay = cell.querySelector(':scope > .aae-select-placeholder');
	if (!overlay) {
		overlay = document.createElement('span');
		overlay.className = 'aae-select-placeholder';
		overlay.setAttribute('aria-hidden', 'true');
		overlay.style.cssText = [
			'color: rgba(255, 255, 255, 0.4)',
			'font-style: italic',
			'pointer-events: none',
			'user-select: none',
		].join(';');
		// Ensure the cell can host us without breaking MUI's flex layout.
		if (getComputedStyle(cell).position === 'static') {
			cell.style.position = 'relative';
		}
		cell.appendChild(overlay);
	}
	overlay.textContent = hintText;
}

function applyResponsivePlaceholders() {
	const mode = currentDeviceMode();
	if (mode === 'desktop') return; // Desktop rows don't inherit.

	const activeSuffix = RESPONSIVE_SUFFIX_BY_MODE[mode];
	if (!activeSuffix) return;

	const container = getSelectedContainer();
	if (!container?.settings) return;

	const labels = document.querySelectorAll('label, .MuiFormLabel-root, .MuiTypography-root');
	for (const label of labels) {
		const text = originalLabelText(label);
		if (!text.endsWith(activeSuffix)) continue;

		const baseLabel = text.slice(0, -activeSuffix.length).trim();
		const base = LABEL_TO_BASE[baseLabel];
		if (!base) continue;

		const row = rowFromLabel(label);
		if (!row) continue;

		const ownKey   = base + '_' + mode;
		const ownValue = readSetting(container, ownKey);
		const hasOwn   = !isEmptyValue(ownValue);

		const parentVal = cascadedParentValue(container, base, mode);

		// Select rows take precedence — MUI Selects also contain a hidden <input>
		// for form data which would otherwise be misdetected as a text input.
		if (isSelectRow(row)) {
			if (hasOwn || parentVal === undefined) {
				clearInheritHint(row);
			} else {
				setInheritHint(row, humanLabelFor(base, parentVal));
			}
			continue;
		}

		// Text / number / textarea — native HTML placeholder.
		const input = inputFromRow(row);
		if (input) {
			if (parentVal === undefined) {
				input.removeAttribute('placeholder');
			} else {
				input.setAttribute('placeholder', String(parentVal));
			}
			clearInheritHint(row); // never both
		}
	}
}

/* =====================================================================
 * 7c. Auto-inherit on device-mode change.
 *     When user switches to a breakpoint whose per-breakpoint prop is
 *     null / unset, copy the cascaded parent value into it. Once a value
 *     exists (even 0), it stays — no further auto-overwrite.
 * =================================================================== */

// All settings that have per-breakpoint variants. Must match the PHP-side
// responsive-prop registrations. enable_editor and play_token are intentionally
// excluded (per-device toggle / button don't make sense).
const AAE_RESPONSIVE_BASES = [
	'aae_text_effect',
	'aae_text_trigger',
	'aae_text_trigger_selector',
	'aae_text_wrapper',
	'aae_text_wrapper_selector',
	'aae_text_delay',
	'aae_text_duration',
	'aae_text_stagger',
	'aae_text_translate_x',
	'aae_text_translate_y',
	'aae_text_rotation_dir',
	'aae_text_rotation',
	'aae_text_transform_origin',
];

// Maps the base-label (label text minus " (Tablet)"/"(Mobile)" suffix) to its
// schema prop name. Used to look up the cascaded parent value for placeholders.
const LABEL_TO_BASE = {
	'Animation':                'aae_text_effect',
	'Trigger':                  'aae_text_trigger',
	'Trigger Selector':         'aae_text_trigger_selector',
	'Text Wrapper':             'aae_text_wrapper',
	'Custom Wrapper Selector':  'aae_text_wrapper_selector',
	'Delay':                    'aae_text_delay',
	'Duration':                 'aae_text_duration',
	'Stagger':                  'aae_text_stagger',
	'Transform-X':              'aae_text_translate_x',
	'Transform-Y':              'aae_text_translate_y',
	'Rotation Direction':       'aae_text_rotation_dir',
	'Rotation Value':           'aae_text_rotation',
	'Transform Origin':         'aae_text_transform_origin',
};

// Human-readable labels for select option values — used to render the
// "↳ Inherits: <label>" hint under select rows.
const HINT_VALUE_LABELS = {
	aae_text_effect: {
		none: 'None', char: 'Character', word: 'Word',
		text_move: 'Text Move', text_reveal: 'Text Reveal',
		text_scale: 'Text Scale', text_invert: 'Text Invert',
		text_spin: '3D Spin',
	},
	aae_text_trigger: {
		on_scroll: 'On Scroll', on_page_load: 'On Page Load',
		play_with_scroll: 'Play With Scroll',
		mouseover: 'On Hover', click: 'On Click',
	},
	aae_text_wrapper:      { default: 'Default', custom: 'Custom' },
	aae_text_rotation_dir: { x: 'X', y: 'Y' },
};

function humanLabelFor(base, value) {
	return HINT_VALUE_LABELS[base]?.[value] ?? String(value);
}

// Cascade: for each non-desktop mode, list of parent breakpoint keys to walk
// looking for the first non-empty value. Matches Schema::BREAKPOINT_LABELS order.
const PARENT_CASCADE = {
	mobile:       [ 'mobile_extra', 'tablet', 'tablet_extra', 'laptop', 'desktop' ],
	mobile_extra: [ 'tablet', 'tablet_extra', 'laptop', 'desktop' ],
	tablet:       [ 'tablet_extra', 'laptop', 'desktop' ],
	tablet_extra: [ 'laptop', 'desktop' ],
	laptop:       [ 'desktop' ],
	widescreen:   [ 'desktop' ],
};

function isEmptyValue(v) {
	if (v === undefined || v === null || v === '') return true;
	if (typeof v === 'number' && Number.isNaN(v)) return true;
	return false;
}

function readSetting(container, key) {
	const wrapped = container?.settings?.attributes?.[key];
	return (wrapped && typeof wrapped === 'object' && '$$type' in wrapped) ? wrapped.value : wrapped;
}

/** Read the wrapped value object: { $$type, value } or undefined. */
function readWrapped(container, key) {
	return container?.settings?.attributes?.[key];
}

function autoInheritOnDeviceChange() {
	const mode = currentDeviceMode();
	if (mode === 'desktop') return;

	const container = getSelectedContainer();
	if (!container?.settings) return;

	const cascade = PARENT_CASCADE[mode];
	if (!cascade) return;

	for (const base of AAE_RESPONSIVE_BASES) {
		const targetKey = base + '_' + mode;
		const targetValue = readSetting(container, targetKey);

		// Skip if user has already set an explicit value (any non-empty value).
		if (!isEmptyValue(targetValue)) continue;

		// Walk parent cascade to find the first non-empty value to inherit.
		let inheritedValue;
		let inheritedType = 'string';
		for (const parent of cascade) {
			const parentKey = (parent === 'desktop') ? base : (base + '_' + parent);
			const wrapped   = readWrapped(container, parentKey);
			const unwrapped = (wrapped && typeof wrapped === 'object' && '$$type' in wrapped) ? wrapped.value : wrapped;
			if (!isEmptyValue(unwrapped)) {
				inheritedValue = unwrapped;
				if (wrapped && typeof wrapped === 'object' && '$$type' in wrapped) {
					inheritedType = wrapped.$$type;
				} else if (typeof unwrapped === 'number') {
					inheritedType = 'number';
				} else if (typeof unwrapped === 'boolean') {
					inheritedType = 'boolean';
				}
				break;
			}
		}

		if (inheritedValue === undefined) continue;

		// Coerce the value to match the inherited $$type so atomic resolver accepts it.
		let castValue;
		switch (inheritedType) {
			case 'number':  castValue = Number(inheritedValue); break;
			case 'boolean': castValue = Boolean(inheritedValue); break;
			default:        castValue = String(inheritedValue);
		}

		container.settings.set(targetKey, { $$type: inheritedType, value: castValue });
	}
}

function startResponsiveBridge() {
	// Re-scan on every panel mutation so newly-rendered rows are gated correctly.
	const observer = new MutationObserver(queueResponsiveScan);
	observer.observe(document.body, { childList: true, subtree: true });
	track(() => observer.disconnect());

	// React to the user switching Desktop/Tablet/Mobile/etc. in the editor toolbar.
	// Placeholders update via queueResponsiveScan (no auto-write).
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



/* =====================================================================
 * 8. Bootstrap — idempotent. Tears down on document switch / unload.
 * =================================================================== */

let bootstrapped = false;

function bootstrap() {
	if (bootstrapped) {
		// Document switched — tear down old listeners and re-init.
		disposeAll();
		bootstrapped = false;
		liveBridgeStarted = false;
		pipeAttempts = 0;
	}
	bootstrapped = true;

	tryPipe();
	startLiveBridge();
	startResponsiveBridge();
}

if (window.elementor && window.elementor.on) {
	window.elementor.on('preview:loaded', bootstrap);
} else {
	document.addEventListener('DOMContentLoaded', bootstrap);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', startPanelObserver);
} else {
	startPanelObserver();
}

// Tear down on page unload as a final safety net.
window.addEventListener('beforeunload', disposeAll);

// Expose for manual debugging / cleanup from console.
window.__aaeAtomicBridge = {
	disposeAll,
	getActiveContainer: () => activeContainer,
	getFeatures: () => FEATURES,
};
