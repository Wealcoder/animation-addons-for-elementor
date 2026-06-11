/* eslint-env browser */
/**
 * Feature registry — declare each editor-side feature once here.
 *
 * Every feature dispatches on Elementor's built-in `data-interaction-id`
 * (universal on atomic widgets, frontend + editor). Per-feature JS maps
 * keep multiple AAE extensions independent:
 *
 *   text  → window.AAE_INTERACTIONS_TEXT
 *   anim  → window.AAE_INTERACTIONS_ANIM
 *   plx   → window.AAE_INTERACTIONS_PLX
 */

/* =====================================================================
 * Responsive helper — mirrors Render.php's dedup-aware emission
 *
 * Storage shape (Responsive_Json_Prop_Type / 'aae-rj'):
 *   settings[base] = { $$type: 'aae-rj', value: { desktop: 'char',
 *                                                 mobile: 'word', ... } }
 *
 * Bare scalars per breakpoint — no per-cell envelope nesting. This file
 * peels the envelope once and walks `.value[bp]` directly.
 * =================================================================== */

const BPS = ['widescreen', 'laptop', 'tablet_extra', 'tablet', 'mobile_extra', 'mobile'];

const BP_CASCADE = {
	mobile: ['mobile_extra', 'tablet', 'tablet_extra', 'laptop'],
	mobile_extra: ['tablet', 'tablet_extra', 'laptop'],
	tablet: ['tablet_extra', 'laptop'],
	tablet_extra: ['laptop'],
	laptop: [],
	widescreen: [],
};

function cascadeParent(bp, resolved, desktopValue) {
	const chain = BP_CASCADE[bp] || [];
	for (const step of chain) {
		if (step in resolved) return resolved[step];
	}
	return desktopValue;
}

function cascadeParentEnabled(bp, resolved, desktopValue) {
	const chain = BP_CASCADE[bp] || [];
	for (const step of chain) {
		if (step in resolved) {
			const v = resolved[step];
			const vBool = (v === 'yes' || v === 'true' || v === 1 || v === '1' || v === true);
			if (vBool) return true;
		}
	}
	const dBool = (desktopValue === 'yes' || desktopValue === 'true' || desktopValue === 1 || desktopValue === '1' || desktopValue === true);
	return dBool;
}

/** Pull the breakpoint→primitive map out of an 'aae-rj' envelope. */
function envelopeToMap(envelope) {
	if (envelope && typeof envelope === 'object' && envelope.$$type === 'aae-rj'
		&& envelope.value && typeof envelope.value === 'object') {
		return envelope.value;
	}
	return {};
}

/** Read a responsive prop's value at the given breakpoint, walking cascade. */
function readAt(settings, base, bp, defaultVal) {
	const map = envelopeToMap(settings[base]);
	if (bp === 'desktop') {
		const v = map.desktop;
		return (v === undefined || v === null || v === '') ? defaultVal : v;
	}
	const isEnableKey = base.endsWith('_enable') || base.endsWith('_enabled') || base === 'enable' || base === 'enabled';
	if (isEnableKey) {
		const resolved = resolveAllBreakpoints(settings, base, defaultVal);
		return resolved[bp];
	}
	const chain = [bp, ...(BP_CASCADE[bp] || []), 'desktop'];
	for (const step of chain) {
		const v = map[step];
		if (v !== undefined && v !== null && v !== '') return v;
	}
	return defaultVal;
}

/** Resolve every breakpoint's effective value via cascade. */
function resolveAllBreakpoints(settings, base, defaultVal) {
	const map = envelopeToMap(settings[base]);
	const desktopRaw = map.desktop;
	const desktopVal = (desktopRaw === undefined || desktopRaw === null || desktopRaw === '')
		? defaultVal
		: desktopRaw;
	const resolved = { desktop: desktopVal };
	const isEnableKey = base.endsWith('_enable') || base.endsWith('_enabled') || base === 'enable' || base === 'enabled';
	for (const bp of BPS) {
		const own = map[bp];
		const parent = isEnableKey
			? cascadeParentEnabled(bp, resolved, desktopVal)
			: cascadeParent(bp, resolved, desktopVal);
		resolved[bp] = (own === undefined || own === null || own === '') ? parent : own;
	}
	return resolved;
}

/**
 * Walk a setting-key → { configKey, default } map. Writes desktop value
 * when it differs from default, then per-bp values when they differ from
 * the cascaded parent. Mirrors Render.php's dedup so the live editor's
 * config and the published-page config are bit-identical.
 *
 * `disabledBps` (optional Set) lists breakpoints where the user has
 * turned the animation off (effect resolves to 'none' there). For those
 * bps we skip ALL per-bp emissions — no point shipping delay_mobile when
 * the mobile animation doesn't run. The `effect` key itself is the
 * exception: that's how the runtime knows the animation is disabled.
 */
function emitResponsive(cfg, settings, keys, disabledBps) {
	for (const [base, info] of Object.entries(keys)) {
		const map = envelopeToMap(settings[base]);
		const desktopRaw = map.desktop;
		const desktopVal = (desktopRaw === undefined || desktopRaw === null || desktopRaw === '')
			? info.default
			: desktopRaw;

		if (String(desktopVal) !== String(info.default)) {
			cfg[info.configKey] = desktopVal;
		}

		const resolved = { desktop: desktopVal };
		const isEnableKey = ['enable', 'enabled'].includes(info.configKey);
		for (const bp of BPS) {
			const own = map[bp];
			const parent = isEnableKey
				? cascadeParentEnabled(bp, resolved, desktopVal)
				: cascadeParent(bp, resolved, desktopVal);

			if (own === undefined || own === null || own === '') {
				resolved[bp] = parent;
				continue;
			}
			resolved[bp] = own;
			if (String(own) === String(parent)) continue;
			if (disabledBps && disabledBps.has(bp) && !['effect', 'enable', 'enabled'].includes(info.configKey)) continue;
			cfg[info.configKey + '_' + bp] = own;
		}
	}
}

/**
 * Emit responsive OBJECT values (border, shadow, etc.) that cannot use
 * emitResponsive (which relies on String() comparison for scalars).
 *
 * `keys` shape — same as emitResponsive but values are objects:
 *   { 'aae_sticky_border': { configKey: 'border', isValid: (v) => !!v.style } }
 *
 * `isValid` is an optional predicate — when provided, only values that
 * pass the test are emitted. Defaults to checking for a truthy object.
 *
 * Emits:
 *   cfg[configKey]          = desktopObject
 *   cfg[configKey + '_bp']  = bpObject
 */
function emitResponsiveObjects(cfg, settings, keys, disabledBps) {
	for (const [base, info] of Object.entries(keys)) {
		const map = envelopeToMap(settings[base]);
		const check = typeof info.isValid === 'function'
			? info.isValid
			: (v) => v && typeof v === 'object';

		const desktopVal = map.desktop || null;
		if (desktopVal && check(desktopVal)) {
			cfg[info.configKey] = desktopVal;
		}

		for (const bp of BPS) {
			if (disabledBps && disabledBps.has(bp)) continue;
			const bpVal = map[bp];
			if (bpVal && check(bpVal)) {
				cfg[info.configKey + '_' + bp] = bpVal;
			}
		}
	}
}

/** Pull a non-responsive primitive (e.g. boolean) out of its envelope. */
function plain(settings, key) {
	const v = settings[key];
	if (v && typeof v === 'object' && '$$type' in v) return v.value;
	return v;
}

/* =====================================================================
 * Text Animation feature
 * =================================================================== */

const TEXT_RESPONSIVE = {
	aae_text_effect: { configKey: 'effect', default: 'none' },
	aae_text_trigger: { configKey: 'trigger', default: 'in-view' },
	aae_text_trigger_selector: { configKey: 'triggerSelector', default: '' },
	aae_text_wrapper: { configKey: 'wrapper', default: 'default' },
	aae_text_wrapper_selector: { configKey: 'wrapperSelector', default: '' },
	aae_text_delay: { configKey: 'delay', default: 0.15 },
	aae_text_duration: { configKey: 'duration', default: 1 },
	aae_text_ease: { configKey: 'ease', default: '' },
	aae_text_translate_x: { configKey: 'translateX', default: 20 },
	aae_text_translate_y: { configKey: 'translateY', default: 0 },
	aae_text_rotation_dir: { configKey: 'rotationDir', default: 'x' },
	aae_text_rotation: { configKey: 'rotation', default: -80 },
	aae_text_transform_origin: { configKey: 'transformOrigin', default: 'top center -50' },
	aae_text_text_shadow: { configKey: 'textShadow', default: '' },
	aae_text_spin_color: { configKey: 'spinColor', default: '#000000' },
	aae_text_start_trigger: { configKey: 'startTrigger', default: '' },
	aae_text_end_trigger: { configKey: 'endTrigger', default: '' },
	aae_text_start_position: { configKey: 'startPosition', default: 'top 85%' },
	aae_text_end_position: { configKey: 'endPosition', default: 'bottom 30%' },
	aae_text_invert_start: { configKey: 'invertStart', default: 'top 85%' },
	aae_text_invert_end: { configKey: 'invertEnd', default: 'bottom center' },
	aae_text_spin_start: { configKey: 'spinStart', default: 'top 50%' },
	aae_text_spin_end: { configKey: 'spinEnd', default: 'bottom 30%' },
	aae_text_spin_toggle: { configKey: 'spinToggle', default: 'play none none reverse' },
	aae_text_scale_ease: { configKey: 'scaleEase', default: 'back' },
	aae_text_scale_num: { configKey: 'scaleNum', default: 1.5 },
	aae_text_scale_break: { configKey: 'scaleBreak', default: 'lines' },
	aae_text_markers: { configKey: 'markers', default: false },
};

const TEXT_OBJECTS = {
	aae_text_stagger: {
		configKey: 'stagger',
		isValid: (v) => v !== undefined && v !== null,
	},
};

function buildTextConfig(settings) {

	const effect = readAt(settings, 'aae_text_effect', 'desktop', 'none');
	if (!effect || effect === 'none') return null;

	const cfg = {};
	if (plain(settings, 'aae_text_enable_editor')) cfg.enableEditor = true;
	if (plain(settings, 'aae_text_markers')) cfg.markers = true;

	const resolvedEffect = resolveAllBreakpoints(settings, 'aae_text_effect', 'none');
	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEffect[bp] || resolvedEffect[bp] === 'none') disabledBps.add(bp);
	}

	emitResponsive(cfg, settings, TEXT_RESPONSIVE, disabledBps);
	emitResponsiveObjects(cfg, settings, TEXT_OBJECTS, disabledBps);

	// Effect defaults to 'none', so a non-default desktop value WILL be
	// emitted. Guarantee presence even if equal to default — the runtime
	// uses cfg.effect existence as the "animation present" signal.
	if (!('effect' in cfg)) {
		cfg.effect = effect;
	}
	return cfg;
}

const STICKY_RESPONSIVE = {
	aae_sticky_enable: { configKey: 'enable', default: false },
	aae_sticky_pin_trigger: { configKey: 'pinTrigger', default: '' },
	aae_sticky_custom_pin_area: { configKey: 'customPinArea', default: '' },
	aae_sticky_pin_end_trigger: { configKey: 'pinEndTrigger', default: '' },
	aae_sticky_custom_pin_end_area: { configKey: 'customPinEndArea', default: '' },
	aae_sticky_pin: { configKey: 'pin', default: '' },
	aae_sticky_custom_pin: { configKey: 'customPin', default: '' },
	aae_sticky_pin_start: { configKey: 'pinStart', default: '' },
	aae_sticky_pin_end: { configKey: 'pinEnd', default: '' },
	aae_sticky_pin_spacing: { configKey: 'pinSpacing', default: '' },
	aae_sticky_toggle_class: { configKey: 'toggleClass', default: '' },
	aae_sticky_bg_color: { configKey: 'bgColor', default: '' },
};

const STICKY_OBJECTS = {
	aae_sticky_border: {
		configKey: 'border',
		isValid: (v) => v && typeof v === 'object' && !!v.style && v.style !== 'none',
	},
};

function buildStickyConfig(settings) {
	const enabled = readAt(settings, 'aae_sticky_enable', 'desktop', false);
	const resolvedEnable = resolveAllBreakpoints(settings, 'aae_sticky_enable', false);

	// Active when desktop is on OR any breakpoint is on.
	const anyActive = enabled
		|| BPS.some((bp) => resolvedEnable[bp]);
	if (!anyActive) return null;

	const cfg = {};
	if (plain(settings, 'aae_sticky_enable_editor')) cfg.enableEditor = true;
	if (plain(settings, 'aae_sticky_pin_markers')) cfg.pinMarkers = true;

	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEnable[bp]) disabledBps.add(bp);
	}
	emitResponsive(cfg, settings, STICKY_RESPONSIVE, disabledBps);
	if (!('enable' in cfg)) {
		cfg.enable = enabled;
	}

	// --- Object-typed responsive values (border, shadow, etc.) ---
	emitResponsiveObjects(cfg, settings, STICKY_OBJECTS, disabledBps);

	return cfg;
}

/* =====================================================================
 * Regular Animation feature
 * =================================================================== */

const REGULAR_RESPONSIVE_ALWAYS = {
	aae_anim_effect: { configKey: 'effect', default: 'none' },
	aae_anim_method: { configKey: 'method', default: 'from' },
	aae_anim_trigger: { configKey: 'trigger', default: 'in-view' },
	aae_anim_trigger_selector: { configKey: 'triggerSelector', default: '' },
	aae_anim_wrapper: { configKey: 'wrapper', default: 'default' },
	aae_anim_delay: { configKey: 'delay', default: 0.15 },
	aae_anim_duration: { configKey: 'duration', default: 1.5 },
	aae_anim_easing: { configKey: 'easing', default: 'power2.out' },
};

const REGULAR_RESPONSIVE_SCROLL_CUSTOM = {
	aae_anim_start_trigger: { configKey: 'startTrigger', default: '' },
	aae_anim_end_trigger: { configKey: 'endTrigger', default: '' },
	aae_anim_start_position: { configKey: 'startPosition', default: 'top top' },
	aae_anim_end_position: { configKey: 'endPosition', default: 'bottom top' },
};

function buildRegularConfig(settings) {
	const effect = readAt(settings, 'aae_anim_effect', 'desktop', 'none');
	if (!effect || effect === 'none') return null;

	const cfg = {};

	const resolvedEffect = resolveAllBreakpoints(settings, 'aae_anim_effect', 'none');
	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEffect[bp] || resolvedEffect[bp] === 'none') disabledBps.add(bp);
	}

	emitResponsive(cfg, settings, REGULAR_RESPONSIVE_ALWAYS, disabledBps);

	if (!('effect' in cfg)) {
		cfg.effect = effect;
	}

	const wrapper = readAt(settings, 'aae_anim_wrapper', 'desktop', 'default');
	if (wrapper === 'custom') {
		emitResponsive(cfg, settings, REGULAR_RESPONSIVE_SCROLL_CUSTOM, disabledBps);

		if (readAt(settings, 'aae_anim_start_position', 'desktop', 'top top') === 'custom') {
			cfg.startCustom = String(readAt(settings, 'aae_anim_start_custom', 'desktop', '') || '');
		}
		if (readAt(settings, 'aae_anim_end_position', 'desktop', 'bottom top') === 'custom') {
			cfg.endCustom = String(readAt(settings, 'aae_anim_end_custom', 'desktop', '') || '');
		}
	}

	// Markers ships independently of wrapper/trigger — the effect-runtime
	// only honors it for scroll-tied animations, but emitting it always
	// keeps the editor toggle in sync with what the user sees.
	if (plain(settings, 'aae_anim_markers')) cfg.markers = true;

	const processRepeater = (bindName, cfgKey) => {
		const map = envelopeToMap(settings[bindName]);
		const rows = Array.isArray(map.desktop) ? map.desktop : [];
		const pairs = [];
		for (const row of rows) {
			if (row?.enabled === false) continue;
			const k = row?.property !== undefined && row?.property !== null ? String(row.property).trim() : '';
			if (!k || k === 'none') continue;
			const v = row?.value !== undefined && row?.value !== null ? String(row.value).trim() : '';
			pairs.push({ k, v });
		}
		if (pairs.length) cfg[cfgKey] = pairs;

		for (const bp of BPS) {
			if (disabledBps.has(bp)) continue;
			const bpRows = Array.isArray(map[bp]) ? map[bp] : null;
			if (!bpRows) continue;
			const bpPairs = [];
			for (const row of bpRows) {
				if (row?.enabled === false) continue;
				const k = row?.property !== undefined && row?.property !== null ? String(row.property).trim() : '';
				if (!k || k === 'none') continue;
				const v = row?.value !== undefined && row?.value !== null ? String(row.value).trim() : '';
				bpPairs.push({ k, v });
			}
			if (bpPairs.length) cfg[cfgKey + '_' + bp] = bpPairs;
		}
	};

	processRepeater('aae_anim_custom_props', 'customProps');
	processRepeater('aae_anim_custom_props_to', 'customPropsTo');

	if (plain(settings, 'aae_anim_enable_editor')) cfg.enableEditor = true;
	return cfg;
}

/* =====================================================================
 * Image Animation feature
 * =================================================================== */

const IMG_RESPONSIVE_ALWAYS = {
	aae_img_effect: { configKey: 'effect', default: 'none' },
};

const IMG_RESPONSIVE_REVEAL = {
	aae_img_start_from: { configKey: 'startFrom', default: 'right' },
	aae_img_ease: { configKey: 'ease', default: 'power2.out' },
};

const IMG_RESPONSIVE_SCALE = {
	aae_img_scale_start: { configKey: 'scaleStart', default: 0.5 },
	aae_img_scale_end: { configKey: 'scaleEnd', default: 1 },
};

const IMG_RESPONSIVE_REVEAL_OR_SCALE = {
	aae_img_start_pos: { configKey: 'startPos', default: 'top center' },
};

function buildImgConfig(settings) {
	const effect = readAt(settings, 'aae_img_effect', 'desktop', 'none');
	const resolvedEffect = resolveAllBreakpoints(settings, 'aae_img_effect', 'none');

	const anyActive = effect !== 'none'
		|| BPS.some((bp) => resolvedEffect[bp] && resolvedEffect[bp] !== 'none');
	if (!anyActive) return null;

	const cfg = {};
	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEffect[bp] || resolvedEffect[bp] === 'none') disabledBps.add(bp);
	}

	emitResponsive(cfg, settings, IMG_RESPONSIVE_ALWAYS, disabledBps);
	if (!('effect' in cfg)) cfg.effect = effect;

	// Effect-family gated emissions. We use the DESKTOP effect to decide
	// which families to include; per-bp overrides that change family will
	// be picked up by emitResponsive's cascade and still write _<bp> keys.
	if (effect === 'reveal') {
		emitResponsive(cfg, settings, IMG_RESPONSIVE_REVEAL, disabledBps);
	}
	if (effect === 'scale') {
		emitResponsive(cfg, settings, IMG_RESPONSIVE_SCALE, disabledBps);
	}
	if (effect === 'reveal' || effect === 'scale') {
		emitResponsive(cfg, settings, IMG_RESPONSIVE_REVEAL_OR_SCALE, disabledBps);

		// customStart is gated on desktop start_pos === 'custom'. Same
		// dedup-aware emit pattern when present.
		if (readAt(settings, 'aae_img_start_pos', 'desktop', '') === 'custom') {
			emitResponsive(
				cfg, settings,
				{ aae_img_custom_start: { configKey: 'customStart', default: 'top 90%' } },
				disabledBps
			);
		}
	}

	if (plain(settings, 'aae_img_enable_marker')) cfg.enableMarker = true;
	if (plain(settings, 'aae_img_enable_editor')) cfg.enableEditor = true;

	return cfg;
}

/* =====================================================================
 * Image Hover (Reveal-on-Hover) feature
 * =================================================================== */

const IH_RESPONSIVE = {
	aae_ih_enable: { configKey: 'enabled', default: false },
	aae_ih_width: { configKey: 'width', default: 300 },
	aae_ih_height: { configKey: 'height', default: 300 },
	aae_ih_top: { configKey: 'top', default: 0 },
	aae_ih_left: { configKey: 'left', default: 0 },
	aae_ih_zindex: { configKey: 'zindex', default: 1 },
};

/**
 * Pull the URL out of an Image_Prop_Type envelope. The Image_Src_Prop_Type
 * validator forces EXACTLY ONE truthy key in {id, url}:
 *   - external image → url is set, use it directly
 *   - media library  → id is set, resolve via wp.media's attachment cache
 * Returns '' when neither resolves.
 */
function imageUrlFrom(envelope, bp = 'desktop') {
	if (!envelope || typeof envelope !== 'object') return '';

	// Support custom MediaInput control stored in a Responsive_Json_Prop_Type envelope ($$type === 'aae-rj').
	if (envelope.$$type === 'aae-rj') {
		const cell = envelope.value?.[bp];
		if (cell && typeof cell === 'object') {
			if (cell.url && typeof cell.url === 'string') {
				return cell.url;
			}
			if (cell.id && typeof window.wp?.media?.attachment === 'function') {
				const attachment = window.wp.media.attachment(cell.id);
				const attrs = attachment?.attributes || {};
				const size = cell.size || 'full';
				const sized = attrs.sizes?.[size]?.url;
				if (sized) return sized;
				if (attrs.url) return attrs.url;
			}
		}
		return '';
	}

	const src = envelope.value?.src;
	if (!src || typeof src !== 'object') return '';

	// Prefer explicit URL (external image).
	const url = src.value?.url;
	if (url && typeof url === 'object' && typeof url.value === 'string' && url.value !== '') {
		return url.value;
	}

	// Fallback: media library — id present, resolve via wp.media's attachment
	// cache. The cache is populated by Elementor's Image_Control when the
	// user picks an image, so this is synchronous in the live editor.
	const idEnv = src.value?.id;
	const id = idEnv && typeof idEnv === 'object' ? idEnv.value : null;
	if (id && typeof window.wp?.media?.attachment === 'function') {
		const attachment = window.wp.media.attachment(id);
		const attrs = attachment?.attributes || {};
		// Prefer a sized URL matching `size`, else fall back to source url.
		const size = envelope.value?.size?.value || 'full';
		const sized = attrs.sizes?.[size]?.url;
		if (sized) return sized;
		if (attrs.url) return attrs.url;
	}

	return '';
}

function resolveImageAllBreakpoints(settings) {
	const envelope = settings.aae_ih_image;
	const desktopUrl = imageUrlFrom(envelope, 'desktop');
	const resolved = { desktop: desktopUrl };

	for (const bp of BPS) {
		const ownUrl = imageUrlFrom(envelope, bp);
		const parent = cascadeParent(bp, resolved, desktopUrl);
		resolved[bp] = (ownUrl === '') ? parent : ownUrl;
	}
	return resolved;
}

/** Elementor's default placeholder image — schema sets this as the
 *  initial value, so we treat its URL as "image not picked yet". */
const IH_PLACEHOLDER_BASENAME = 'placeholder-v4.svg';

function isPlaceholderUrl(url) {
	return typeof url === 'string' && url.endsWith(IH_PLACEHOLDER_BASENAME);
}

function buildImageHoverConfig(settings) {

	// Gate on the responsive enable switch — active if any BP is enabled.
	const enabled = readAt(settings, 'aae_ih_enable', 'desktop', false);
	const resolvedEnable = resolveAllBreakpoints(settings, 'aae_ih_enable', false);
	const anyActive = enabled || BPS.some((bp) => resolvedEnable[bp]);
	if (!anyActive) return null;

	const resolvedImages = resolveImageAllBreakpoints(settings);
	const desktopUrl = resolvedImages.desktop;

	// An image must be picked on at least one active/enabled breakpoint.
	let hasAnyImage = false;
	if (desktopUrl && !isPlaceholderUrl(desktopUrl)) {
		hasAnyImage = true;
	} else {
		for (const bp of BPS) {
			if (resolvedEnable[bp] && resolvedImages[bp] && !isPlaceholderUrl(resolvedImages[bp])) {
				hasAnyImage = true;
				break;
			}
		}
	}
	if (!hasAnyImage) return null;

	const cfg = {};
	if (desktopUrl) {
		cfg.imageUrl = desktopUrl;
	}

	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEnable[bp]) disabledBps.add(bp);
	}

	// Emit responsive image URLs.
	for (const bp of BPS) {
		if (disabledBps.has(bp)) continue;
		const ownUrl = resolvedImages[bp];
		const parent = cascadeParent(bp, resolvedImages, desktopUrl);
		if (ownUrl !== parent) {
			cfg['imageUrl_' + bp] = ownUrl;
		}
	}

	emitResponsive(cfg, settings, IH_RESPONSIVE, disabledBps);

	// Always ensure the desktop enabled flag is present.
	if (!('enabled' in cfg)) cfg.enabled = enabled;

	return cfg;
}

/* =====================================================================
 * Parallax feature
 * =================================================================== */

const PLX_RESPONSIVE = {
	aae_plx_enable: { configKey: 'enabled', default: false },
	aae_plx_speed: { configKey: 'speed', default: 0.9 },
	aae_plx_lag: { configKey: 'lag', default: 0 },
};

function buildParallaxConfig(settings) {
	const enabled = readAt(settings, 'aae_plx_enable', 'desktop', false);
	const resolvedEnable = resolveAllBreakpoints(settings, 'aae_plx_enable', false);

	// Active when desktop is on OR any breakpoint is on.
	const anyActive = enabled
		|| BPS.some((bp) => resolvedEnable[bp]);
	if (!anyActive) return null;

	const cfg = {
		'lag': readAt(settings, 'aae_plx_lag', 'desktop', 0),
		'speed': readAt(settings, 'aae_plx_speed', 'desktop', 0.9),
	};

	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEnable[bp]) disabledBps.add(bp);
	}

	emitResponsive(cfg, settings, PLX_RESPONSIVE, disabledBps);

	// Ensure the enabled flag is always present for runtime.
	if (!('enabled' in cfg)) cfg.enabled = enabled;

	if (plain(settings, 'aae_plx_enable_editor')) cfg.enableEditor = true;

	return cfg;
}

/* =====================================================================
 * Horizontal Scroll feature
 * =================================================================== */

const HORIZONTAL_RESPONSIVE = {
	aae_horizontal_enable: { configKey: 'enabled', default: false },
	aae_horizontal_start: { configKey: 'start', default: 'top top' },
	aae_horizontal_speed: { configKey: 'speed', default: 1 },
};

function buildHorizontalConfig(settings) {
	const enabled = readAt(settings, 'aae_horizontal_enable', 'desktop', false);
	const resolvedEnable = resolveAllBreakpoints(settings, 'aae_horizontal_enable', false);

	// Active when desktop is on OR any breakpoint is on.
	const anyActive = enabled
		|| BPS.some((bp) => resolvedEnable[bp]);
	if (!anyActive) return null;

	const cfg = {
		enabled: false,
		start: readAt(settings, 'aae_horizontal_start', 'desktop', 'top top'),
		speed: readAt(settings, 'aae_horizontal_speed', 'desktop', 1),
	};

	// In case you later add responsive breakpoints, emit them here.
	const disabledBps = new Set(); // no breakpoints disabled for now
	for (const bp of BPS) {
		if (!resolvedEnable[bp]) disabledBps.add(bp);
	}
	emitResponsive(cfg, settings, HORIZONTAL_RESPONSIVE, disabledBps);

	// Guarantee the enable flag is always present for the runtime.
	//if (!('enabled' in cfg)) cfg.enabled = true;

	// Editor‑only replay flag.
	if (plain(settings, 'aae_horizontal_enable_editor')) cfg.enableEditor = true;

	return cfg;
}

/* =====================================================================
 * Advance tooltip feature
 * =================================================================== */

const ADVANCED_TOOLTIP_RESPONSIVE = {
	aae_advance_tooltip_enable: {
		configKey: 'enabled',
		default: false,
	},

	aae_advance_tooltip_text: {
		configKey: 'text',
		default: 'view tooltip',
	},

	aae_advance_tooltip_position: {
		configKey: 'position',
		default: 'top',
	},

	aae_advance_tooltip_animation: {
		configKey: 'animation',
		default: 'fade',
	},

	aae_advance_tooltip_trigger: {
		configKey: 'trigger',
		default: 'hover',
	},

	aae_advance_tooltip_offset: {
		configKey: 'offset',
		default: 10,
	},

	aae_advance_tooltip_alignment: {
		configKey: 'alignment',
		default: 'center',
	},

	aae_advance_tooltip_width: {
		configKey: 'width',
		default: '200px',
	},

	aae_advance_tooltip_arrow_enable: {
		configKey: 'arrowEnable',
		default: false,
	},

	aae_advance_tooltip_arrow_size: {
		configKey: 'arrowSize',
		default: 10,
	},

	aae_advance_tooltip_bg: {
		configKey: 'bg',
		default: '#000000',
	},

	aae_advance_tooltip_color: {
		configKey: 'color',
		default: '#ffffff',
	},

	// New controls added to features.js so editor live-preview bridge
	// syncs them to the iframe map on every settings change.
	aae_advance_tooltip_show_delay: {
		configKey: 'showDelay',
		default: 0,
	},

	aae_advance_tooltip_hide_delay: {
		configKey: 'hideDelay',
		default: 0,
	},

	};

const ADVANCED_TOOLTIP_OBJECTS = {
	aae_advance_tooltip_padding: {
		configKey: 'padding',
		isValid: (v) => v && typeof v === 'object',
	},
	aae_advance_tooltip_border: {
		configKey: 'border',

		isValid: (v) =>
			v &&
			typeof v === 'object',
	},
	aae_advance_tooltip_font_size: {
		configKey: 'font_size',
		isValid: (v) => v && typeof v === 'object',
	},
	aae_advance_tooltip_line_height: {
		configKey: 'line_height',
		isValid: (v) => v && typeof v === 'object',
	},
};

function buildAdvancedTooltipConfig(settings) {
	
	const enabled = readAt(
		settings,
		'aae_advance_tooltip_enable',
		'desktop',
		false
	);

	const resolvedEnable = resolveAllBreakpoints(settings,	'aae_advance_tooltip_enable',	false);

	const anyActive =	enabled || BPS.some((bp) => resolvedEnable[bp]);

	if (!anyActive) {
		return null;
	}

	const cfg = {};
	if (plain(settings, 'aae_advance_tooltip_enable_editor')) {
		cfg.enableEditor = true;
	}

	const disabledBps = new Set();

	for (const bp of BPS) {

		if (!resolvedEnable[bp]) {
			disabledBps.add(bp);
		}
	}

	emitResponsive(	cfg, settings,	ADVANCED_TOOLTIP_RESPONSIVE, disabledBps);

	emitResponsiveObjects(cfg,settings,	ADVANCED_TOOLTIP_OBJECTS,disabledBps);

	if (!('enabled' in cfg)) {

		cfg.enabled = enabled;
	}

	return cfg;
}

const TILT_RESPONSIVE = {
	aae_tilt_enable: { configKey: 'enabled', default: false },
	aae_tilt_max: { configKey: 'max', default: 15 },
	aae_tilt_speed: { configKey: 'speed', default: 300 },
	aae_tilt_scale: { configKey: 'scale', default: 1 },
	aae_tilt_perspective: { configKey: 'perspective', default: 1000 },
	aae_tilt_glare: { configKey: 'glare', default: false },
	aae_tilt_max_glare: { configKey: 'maxGlare', default: 0.5 },
	aae_tilt_reset: { configKey: 'reset', default: true },
	aae_tilt_transition: { configKey: 'transition', default: true },
	aae_tilt_gyroscope: { configKey: 'gyroscope', default: true },
};

function buildTiltConfig(settings) {
	const enabled = readAt(settings, 'aae_tilt_enable', 'desktop', false);
	const resolvedEnable = resolveAllBreakpoints(settings, 'aae_tilt_enable', false);

	const anyActive = enabled || BPS.some((bp) => resolvedEnable[bp]);
	if (!anyActive) return null;

	const cfg = {};
	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEnable[bp]) {
			disabledBps.add(bp);
		}
	}

	emitResponsive(cfg, settings, TILT_RESPONSIVE, disabledBps);

	if (!('enabled' in cfg)) {
		cfg.enabled = enabled;
	}

	if (plain(settings, 'aae_tilt_enable_editor')) {
		cfg.enableEditor = true;
	}

	return cfg;
}

function buildWrapperLinkConfig(settings) {
	if (!plain(settings, 'aae_wrapper_link_enable')) {
		return null;
	}

	const raw = settings.aae_wrapper_link;
	if (!raw) return null;

	const val = (raw && typeof raw === 'object' && '$$type' in raw) ? raw.value : raw;
	if (!val) return null;

	let url = typeof val === 'string' ? val : '';
	if (!url) return null;

	const isExternalRaw = settings.aae_wrapper_link_is_external;
	let isExternal = false;
	if (isExternalRaw !== undefined) {
		if (typeof isExternalRaw === 'object' && '$$type' in isExternalRaw) {
			isExternal = !!isExternalRaw.value;
		} else {
			isExternal = !!isExternalRaw;
		}
	}

	const cfg = {
		url,
		isExternal,
		nofollow: false,
	};

	return cfg;
}

/* =====================================================================
 * Registry
 * =================================================================== */

/**
 * `findTarget` uses Elementor's universal data-interaction-id with
 * data-id as a fallback for freshly-inserted widgets that may not have
 * the interaction-id set yet during React's initial render pass.
 */
function findByInteractionId(doc, id) {
	return doc.querySelector(`[data-interaction-id="${id}"]`)
		|| doc.querySelector(`[data-id="${id}"]`);
}

// cursor hover effect config will be built in a similar way when needed, following the pattern above.

const CURSOR_RESPONSIVE = {
	aae_cursor_hover_enable: { configKey: 'enabled', default: false },
	aae_cursor_hover_text: { configKey: 'text', default: '' },
	aae_cursor_hover_color: { configKey: 'color', default: '#ffffff' },
	aae_cursor_hover_background: { configKey: 'background', default: '#000000' },
	aae_cursor_hover_width: { configKey: 'width', default: '' },
	aae_cursor_hover_height: { configKey: 'height', default: '' },
	aae_cursor_hover_font_size: { configKey: 'fontSize', default: '' },
	aae_cursor_hover_padding: { configKey: 'padding', default: '' },
};

const CURSOR_OBJECTS = {
	aae_cursor_hover_border: {
		configKey: 'border',
		isValid: (v) => v && typeof v === 'object',
	},
};

function dimensionToString(val) {
	if (val && typeof val === 'object') {
		// 4-sided dimension box (padding/margin): {top, right, bottom, left, unit}
		if ('top' in val || 'bottom' in val) {
			const top    = val.top    ?? '';
			const right  = val.right  ?? '';
			const bottom = val.bottom ?? '';
			const left   = val.left   ?? '';
			const unit   = val.unit || 'px';
			if (top === '' && right === '' && bottom === '' && left === '') return '';
			return `${top}${unit} ${right}${unit} ${bottom}${unit} ${left}${unit}`;
		}
		// Single-value dimension: {size, unit} or {value, unit}
		const size = val.size !== undefined ? val.size : (val.value !== undefined ? val.value : '');
		const unit = val.unit || 'px';
		if (size === '' || size === null || size === undefined) return '';
		return size + unit;
	}
	if (typeof val === 'number') {
		return val + 'px';
	}
	return (val !== undefined && val !== null && val !== '') ? String(val) : '';
}

function translateDimensionSettings(settings, keys) {
	const translated = { ...settings };
	for (const key of keys) {
		const original = settings[key];
		if (original && typeof original === 'object' && original.$$type === 'aae-rj' && original.value) {
			const newValue = {};
			for (const [bp, val] of Object.entries(original.value)) {
				newValue[bp] = dimensionToString(val);
			}
			translated[key] = {
				...original,
				value: newValue
			};
		} else if (original && typeof original === 'object' && '$$type' in original) {
			translated[key] = {
				...original,
				value: dimensionToString(original.value)
			};
		} else if (original !== undefined && original !== null) {
			translated[key] = dimensionToString(original);
		}
	}
	return translated;
}

function buildCursorHoverEffectConfig(settings) {
	const enabled = readAt(settings, 'aae_cursor_hover_enable', 'desktop', false);
	const resolvedEnable = resolveAllBreakpoints(settings, 'aae_cursor_hover_enable', false);

	const anyActive = enabled || BPS.some((bp) => resolvedEnable[bp]);
	if (!anyActive) return null;

	const cfg = {
		enable_editor: plain(settings, 'aae_cursor_hover_enable_editor') || false,
	};

	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEnable[bp]) disabledBps.add(bp);
	}

	const translated = translateDimensionSettings(settings, [
		'aae_cursor_hover_width',
		'aae_cursor_hover_height',
		'aae_cursor_hover_font_size',
		'aae_cursor_hover_padding',
	]);

	emitResponsive(cfg, translated, CURSOR_RESPONSIVE, disabledBps);
	emitResponsiveObjects(cfg, translated, CURSOR_OBJECTS, disabledBps);

	if (!('enabled' in cfg)) {
		cfg.enabled = enabled;
	}

	return cfg;
}

const MOUSE_MOVE_EFFECT_RESPONSIVE = {
	aae_mouse_move_effect_enable: { configKey: 'enable', default: false },
	aae_mouse_move_effect_movement_wrapper: { configKey: 'movement_wrapper', default: 'default' },
	aae_mouse_move_effect_move_x: { configKey: 'move_x', default: '100' },
	aae_mouse_move_effect_move_y: { configKey: 'move_y', default: '100' },
	aae_mouse_move_effect_duration: { configKey: 'duration', default: '1' },
	aae_mouse_move_effect_customs: { configKey: 'customs', default: '' },
};

function buildMouseMoveConfig(settings) {
	const enabled = readAt(settings, 'aae_mouse_move_effect_enable', 'desktop', false);
	const resolvedEnable = resolveAllBreakpoints(settings, 'aae_mouse_move_effect_enable', false);

	// Active when desktop is on OR any breakpoint is on.
	const anyActive = enabled || BPS.some((bp) => resolvedEnable[bp]);
	if (!anyActive) return null;

	const cfg = {
		enable_editor: plain(settings, 'aae_mouse_move_effect_enable_editor') || false,
		move_y : 100,
		move_x : 100,
		duration : 1,
		customs : '',
		movement_wrapper : 'default',
	};

	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEnable[bp]) disabledBps.add(bp);
	}

	emitResponsive(cfg, settings, MOUSE_MOVE_EFFECT_RESPONSIVE, disabledBps);

	// Custom Properties repeater
	const map = envelopeToMap(settings.aae_mouse_move_effect_custom_props);
	const rows = Array.isArray(map.desktop) ? map.desktop : [];
	const pairs = [];
	for (const row of rows) {
		const k = typeof row?.property === 'string' ? row.property.trim() : '';
		if (!k || k === 'none') continue;
		const v = typeof row?.value === 'string' ? row.value.trim() : '';
		pairs.push({ k, v });
	}
	if (pairs.length) cfg.customProps = pairs;

	for (const bp of BPS) {
		if (disabledBps.has(bp)) continue;
		const bpRows = Array.isArray(map[bp]) ? map[bp] : null;
		if (!bpRows) continue;
		const bpPairs = [];
		for (const row of bpRows) {
			const k = typeof row?.property === 'string' ? row.property.trim() : '';
			if (!k || k === 'none') continue;
			const v = typeof row?.value === 'string' ? row.value.trim() : '';
			bpPairs.push({ k, v });
		}
		if (bpPairs.length) cfg['customProps_' + bp] = bpPairs;
	}

	// Ensure the enabled flag is always present for runtime.
	if (!('enable' in cfg)) {
		cfg.enable = enabled;
	}

	return cfg;
}

/* =====================================================================
 * Custom CSS feature
 * =================================================================== */

const CUSTOM_CSS_RESPONSIVE = {
	aae_custom_css_enable: { configKey: 'enabled', default: false },
	aae_custom_css_css: { configKey: 'css', default: '' },
};

function buildCustomCssConfig(settings) {
	const enabled = readAt(settings, 'aae_custom_css_enable', 'desktop', false);
	const resolvedEnable = resolveAllBreakpoints(settings, 'aae_custom_css_enable', false);

	const anyActive = enabled || BPS.some((bp) => resolvedEnable[bp]);
	if (!anyActive) return null;

	const cfg = {};
	const disabledBps = new Set();
	for (const bp of BPS) {
		if (!resolvedEnable[bp]) disabledBps.add(bp);
	}

	emitResponsive(cfg, settings, CUSTOM_CSS_RESPONSIVE, disabledBps);

	if (!('enabled' in cfg)) {
		cfg.enabled = enabled;
	}

	if (plain(settings, 'aae_custom_css_enable_editor')) cfg.enableEditor = true;

	return cfg;
}

export const FEATURES = [
	{
		name: 'mouse-move-effect',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_mouse_move_effect_enable',
		autoReplaySetting: 'aae_mouse_move_effect_enable_editor',
		mapName: 'AAE_INTERACTIONS_MOUSE_MOVE_EFFECT',
		buildConfig: buildMouseMoveConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'text-animation',
		widgetTypes: ['e-heading', 'e-paragraph'],
		enableSetting: 'aae_text_effect',
		autoReplaySetting: 'aae_text_enable_editor',
		mapName: 'AAE_INTERACTIONS_TEXT',
		buildConfig: buildTextConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'regular-animation',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_anim_effect',
		autoReplaySetting: 'aae_anim_enable_editor',
		mapName: 'AAE_INTERACTIONS_ANIM',
		buildConfig: buildRegularConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'image-animation',
		widgetTypes: ['e-image', 'e-svg'],
		enableSetting: 'aae_img_effect',
		autoReplaySetting: 'aae_img_enable_editor',
		mapName: 'AAE_INTERACTIONS_IMG',
		buildConfig: buildImgConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'image-hover',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_ih_enable',
		autoReplaySetting: 'aae_ih_enable_editor',
		mapName: 'AAE_INTERACTIONS_IMGHOVER',
		buildConfig: buildImageHoverConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'cursor-hover-effect',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_cursor_hover_enable',
		autoReplaySetting: 'aae_cursor_hover_enable_editor',
		mapName: 'AAE_INTERACTIONS_CURSOR_HOVER_EFFECT',
		buildConfig: buildCursorHoverEffectConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'sticky',
		widgetTypes: ['e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_sticky_enable',
		autoReplaySetting: null,
		mapName: 'AAE_INTERACTIONS_STICKY',
		buildConfig: buildStickyConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'parallax',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_plx_enable',
		autoReplaySetting: 'aae_plx_enable_editor',
		mapName: 'AAE_INTERACTIONS_PLX',
		buildConfig: buildParallaxConfig,
		findTarget: findByInteractionId,
	},

	{
		name: 'horizontal',
		widgetTypes: ['e-flexbox', 'e-grid'],
		enableSetting: 'aae_horizontal_enable',
		autoReplaySetting: null,
		mapName: 'AAE_INTERACTIONS_HORIZONTAL',
		buildConfig: buildHorizontalConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'advance-tooltip',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_advance_tooltip_enable',
		autoReplaySetting: 'aae_advance_tooltip_enable_editor',
		mapName: 'AAE_INTERACTIONS_ADVANCE_TOOLTIP',
		buildConfig: buildAdvancedTooltipConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'tilt',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_tilt_enable',
		autoReplaySetting: 'aae_tilt_enable_editor',
		mapName: 'AAE_INTERACTIONS_TILT',
		buildConfig: buildTiltConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'wrapper-link',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_wrapper_link_enable',
		autoReplaySetting: 'aae_wrapper_link_enable_editor',
		mapName: 'AAE_INTERACTIONS_WRAPPER_LINK',
		buildConfig: buildWrapperLinkConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'custom-css',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_custom_css_enable',
		autoReplaySetting: 'aae_custom_css_enable_editor',
		mapName: 'AAE_INTERACTIONS_CUSTOM_CSS',
		buildConfig: buildCustomCssConfig,
		findTarget: findByInteractionId,
	},
];

/**
 * Every feature whose target list includes the given container's widget type.
 * A heading carries BOTH text-animation and regular-animation features, so
 * the editor must apply both maps when the user has either set — text alone,
 * regular alone, or both simultaneously.
 */
export function featuresFor(container) {
	// Atomic widgets sometimes report `widgetType` as the bare name
	// (`button`, `heading`) and sometimes pre-prefixed (`e-button`,
	// `e-heading`). Our feature manifest uses the `e-` form, so add
	// the prefix when missing before matching.
	const raw = container?.model?.get?.('widgetType')
		|| container?.model?.get?.('elType');
		
	if (!raw) return [];
	const type = raw.startsWith('e-') ? raw : `e-${raw}`;
	return FEATURES.filter((f) => f.widgetTypes.includes(type));
}