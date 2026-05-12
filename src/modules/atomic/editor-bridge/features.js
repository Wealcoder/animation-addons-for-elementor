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
 *   tilt  → window.AAE_INTERACTIONS_TILT  (future)
 *
 * Each entry shape:
 *   {
 *     name:              'unique-id-for-logging',
 *     widgetTypes:       ['e-heading', ...],
 *     enableSetting:     'aae_text_effect',    // null/'none' = feature off
 *     autoReplaySetting: 'aae_text_enable_editor',
 *     mapName:           'AAE_INTERACTIONS_TEXT',
 *     buildConfig: (settings, unwrap) => { ...config or null... },
 *     findTarget:  (doc, id) => HTMLElement | null,
 *   }
 */

/* =====================================================================
 * Responsive helper — mirrors Render.php's dedup-aware emission
 * =================================================================== */

const BPS = [ 'widescreen', 'laptop', 'tablet_extra', 'tablet', 'mobile_extra', 'mobile' ];

const BP_CASCADE = {
	mobile_extra: [ 'mobile', 'tablet' ],
	mobile:       [ 'tablet' ],
	tablet_extra: [ 'tablet' ],
	tablet:       [],
	laptop:       [],
	widescreen:   [],
};

function cascadeParent(bp, resolved, desktopValue) {
	const chain = BP_CASCADE[bp] || [];
	for (const step of chain) {
		if (step in resolved) return resolved[step];
	}
	return desktopValue;
}

/**
 * Walk a setting-key → { configKey, default } map. Writes desktop value
 * when it differs from default, then per-bp values when they differ from
 * the cascaded parent. Matches Render.php's dedup behaviour so the live
 * editor's config and the published-page config are bit-identical.
 */
function emitResponsive(cfg, settings, unwrap, keys) {
	for (const [base, info] of Object.entries(keys)) {
		const desktop = unwrap(settings[base]);
		const desktopVal = (desktop === undefined || desktop === null || desktop === '')
			? info.default
			: desktop;

		if (String(desktopVal) !== String(info.default)) {
			cfg[info.configKey] = desktopVal;
		}

		const resolved = { desktop: desktopVal };
		for (const bp of BPS) {
			const own = unwrap(settings[base + '_' + bp]);
			const parent = cascadeParent(bp, resolved, desktopVal);

			if (own === undefined || own === null || own === '') {
				resolved[bp] = parent;
				continue;
			}
			resolved[bp] = own;
			if (String(own) === String(parent)) continue;
			cfg[info.configKey + '_' + bp] = own;
		}
	}
}

/* =====================================================================
 * Text Animation feature
 * =================================================================== */

const TEXT_RESPONSIVE = {
	aae_text_trigger:          { configKey: 'trigger',         default: 'on_scroll' },
	aae_text_trigger_selector: { configKey: 'triggerSelector', default: '' },
	aae_text_wrapper:          { configKey: 'wrapper',         default: 'default' },
	aae_text_wrapper_selector: { configKey: 'wrapperSelector', default: '' },
	aae_text_delay:            { configKey: 'delay',           default: 0.15 },
	aae_text_duration:         { configKey: 'duration',        default: 1 },
	aae_text_stagger:          { configKey: 'stagger',         default: 0.02 },
	aae_text_translate_x:      { configKey: 'translateX',      default: 20 },
	aae_text_translate_y:      { configKey: 'translateY',      default: 0 },
	aae_text_rotation_dir:     { configKey: 'rotationDir',     default: 'x' },
	aae_text_rotation:         { configKey: 'rotation',        default: -80 },
	aae_text_transform_origin: { configKey: 'transformOrigin', default: 'top center -50' },
};

function buildTextConfig(settings, unwrap) {
	const effect = unwrap(settings.aae_text_effect);
	if (!effect || effect === 'none') return null;

	const cfg = { effect };
	if (unwrap(settings.aae_text_enable_editor)) cfg.enableEditor = true;

	emitResponsive(cfg, settings, unwrap, TEXT_RESPONSIVE);
	return cfg;
}

/* =====================================================================
 * Regular Animation feature
 * =================================================================== */

const REGULAR_RESPONSIVE_ALWAYS = {
	aae_anim_method:           { configKey: 'method',          default: 'from' },
	aae_anim_trigger:          { configKey: 'trigger',         default: 'on_scroll' },
	aae_anim_trigger_selector: { configKey: 'triggerSelector', default: '' },
	aae_anim_wrapper:          { configKey: 'wrapper',         default: 'default' },
	aae_anim_delay:            { configKey: 'delay',           default: 0.15 },
	aae_anim_duration:         { configKey: 'duration',        default: 1.5 },
	aae_anim_easing:           { configKey: 'easing',          default: 'power2.out' },
};

const REGULAR_RESPONSIVE_FADE = {
	aae_anim_fade_from:   { configKey: 'fadeFrom',   default: 'bottom' },
	aae_anim_fade_offset: { configKey: 'fadeOffset', default: 50 },
	aae_anim_scale:       { configKey: 'scale',      default: 0.7 },
};

const REGULAR_RESPONSIVE_MOVE = {
	aae_anim_rotation_dir:     { configKey: 'rotationDir',     default: 'x' },
	aae_anim_rotation:         { configKey: 'rotation',        default: -80 },
	aae_anim_transform_origin: { configKey: 'transformOrigin', default: 'top center -50' },
};

const REGULAR_RESPONSIVE_SCROLL_CUSTOM = {
	aae_anim_start_trigger:  { configKey: 'startTrigger',  default: '' },
	aae_anim_end_trigger:    { configKey: 'endTrigger',    default: '' },
	aae_anim_start_position: { configKey: 'startPosition', default: 'top top' },
	aae_anim_end_position:   { configKey: 'endPosition',   default: 'bottom top' },
};

function buildRegularConfig(settings, unwrap) {
	const effect = unwrap(settings.aae_anim_effect);
	if (!effect || effect === 'none') return null;

	const cfg = { effect };
	emitResponsive(cfg, settings, unwrap, REGULAR_RESPONSIVE_ALWAYS);

	const wrapper = unwrap(settings.aae_anim_wrapper) || 'default';
	if (wrapper === 'custom') {
		emitResponsive(cfg, settings, unwrap, REGULAR_RESPONSIVE_SCROLL_CUSTOM);

		if (unwrap(settings.aae_anim_start_position) === 'custom') {
			cfg.startCustom = String(unwrap(settings.aae_anim_start_custom) || '');
		}
		if (unwrap(settings.aae_anim_end_position) === 'custom') {
			cfg.endCustom = String(unwrap(settings.aae_anim_end_custom) || '');
		}
		if (unwrap(settings.aae_anim_markers)) cfg.markers = true;
	}

	if (effect === 'fade') {
		emitResponsive(cfg, settings, unwrap, REGULAR_RESPONSIVE_FADE);
	}

	if (effect === 'move') {
		emitResponsive(cfg, settings, unwrap, REGULAR_RESPONSIVE_MOVE);
	}

	if (effect === 'custom') {
		const keys   = unwrap(settings.aae_anim_custom_prop_keys)   || [];
		const values = unwrap(settings.aae_anim_custom_prop_values) || [];
		if (Array.isArray(keys) && keys.length) {
			const pairs = [];
			for (let i = 0; i < keys.length; i++) {
				const k = typeof keys[i] === 'string' ? keys[i].trim() : '';
				if (!k || k === 'none') continue;
				const v = typeof values[i] === 'string' ? values[i].trim() : '';
				pairs.push({ k, v });
			}
			if (pairs.length) cfg.customProps = pairs;
		}
	}

	if (unwrap(settings.aae_anim_enable_editor)) cfg.enableEditor = true;
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

export const FEATURES = [
	{
		name: 'text-animation',
		widgetTypes: ['e-heading', 'e-paragraph'],
		enableSetting: 'aae_text_effect',
		autoReplaySetting: 'aae_text_enable_editor',
		mapName:    'AAE_INTERACTIONS_TEXT',
		buildConfig: buildTextConfig,
		findTarget: findByInteractionId,
	},
	{
		name: 'regular-animation',
		widgetTypes: ['e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg', 'e-flexbox', 'e-div-block', 'e-grid'],
		enableSetting: 'aae_anim_effect',
		autoReplaySetting: 'aae_anim_enable_editor',
		mapName:    'AAE_INTERACTIONS_ANIM',
		buildConfig: buildRegularConfig,
		findTarget: findByInteractionId,
	},
];

/** Find the feature for a given atomic container, or null if unsupported. */
export function featureFor(container) {
	const type = container?.model?.get?.('widgetType')
		|| container?.model?.get?.('elType');
	return FEATURES.find((f) => f.widgetTypes.includes(type)) || null;
}
