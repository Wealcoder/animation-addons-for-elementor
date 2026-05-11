/* eslint-env browser */

/**
 * Responsive metadata — shared by the live-bridge (writes per-breakpoint
 * data-attrs), responsive-visibility (toggles row display per device), and
 * responsive-placeholders (shows the cascaded parent value as a hint).
 *
 * Single source of truth so adding a new breakpoint or a new responsive
 * setting is a one-place change.
 */

/** Every breakpoint that may carry per-device variants, largest → smallest. */
export const ALL_BREAKPOINT_KEYS = [
	'widescreen', 'laptop', 'tablet_extra', 'tablet', 'mobile_extra', 'mobile',
];

/**
 * Settings that have per-breakpoint variants. Must match the PHP-side
 * responsive-prop registrations. enable_editor and play_token are
 * intentionally excluded (per-device toggle / button don't make sense).
 */
export const AAE_RESPONSIVE_BASES = [
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

/**
 * Suffix appended to the label text per device mode. Render.php emits labels
 * like "Animation (Mobile)"; the responsive-visibility module reads these
 * suffixes to gate row display by active device.
 */
export const RESPONSIVE_SUFFIX_BY_MODE = {
	widescreen:   '(Widescreen)',
	laptop:       '(Laptop)',
	tablet_extra: '(Tablet Extra)',
	tablet:       '(Tablet)',
	mobile_extra: '(Mobile Extra)',
	mobile:       '(Mobile)',
};

/** Every suffix string, ordered longest-first so "(Tablet Extra)" matches before "(Tablet)". */
export const ALL_SUFFIXES = Object.values(RESPONSIVE_SUFFIX_BY_MODE)
	.slice()
	.sort((a, b) => b.length - a.length);

/**
 * Maps base label text → schema prop name. Used by placeholder + visibility
 * modules to identify which responsive base a row represents.
 */
export const LABEL_TO_BASE = {
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

/** Human-readable labels for select option values — used in inherit hints. */
export const HINT_VALUE_LABELS = {
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

export function humanLabelFor(base, value) {
	return HINT_VALUE_LABELS[base]?.[value] ?? String(value);
}

/**
 * For each non-desktop mode, the parent chain to walk for a non-empty value.
 * Matches Schema::BREAKPOINT_LABELS order.
 */
export const PARENT_CASCADE = {
	mobile:       ['mobile_extra', 'tablet', 'tablet_extra', 'laptop', 'desktop'],
	mobile_extra: ['tablet', 'tablet_extra', 'laptop', 'desktop'],
	tablet:       ['tablet_extra', 'laptop', 'desktop'],
	tablet_extra: ['laptop', 'desktop'],
	laptop:       ['desktop'],
	widescreen:   ['desktop'],
};

/* =====================================================================
 * Setting readers — share the unwrap logic used by every module that
 * reaches into container.settings.attributes.
 * =================================================================== */

export function isEmptyValue(v) {
	if (v === undefined || v === null || v === '') return true;
	if (typeof v === 'number' && Number.isNaN(v)) return true;
	return false;
}

/** Return the raw value for a setting key, unwrapping the atomic { $$type, value } shape. */
export function readSetting(container, key) {
	const wrapped = container?.settings?.attributes?.[key];
	return (wrapped && typeof wrapped === 'object' && '$$type' in wrapped) ? wrapped.value : wrapped;
}

/** Return the wrapped value object: { $$type, value } or undefined. */
export function readWrapped(container, key) {
	return container?.settings?.attributes?.[key];
}

/** Return the active device mode from Elementor's channel, defaulting to 'desktop'. */
export function currentDeviceMode() {
	try {
		return window.elementor?.channels?.deviceMode?.request?.('currentMode') || 'desktop';
	} catch (_) {
		return 'desktop';
	}
}

/** Walk the parent cascade for a base setting; return the first non-empty value. */
export function cascadedParentValue(container, base, mode) {
	const chain = PARENT_CASCADE[mode] || [];
	for (const parent of chain) {
		const key = (parent === 'desktop') ? base : (base + '_' + parent);
		const v = readSetting(container, key);
		if (!isEmptyValue(v)) return v;
	}
	return undefined;
}
