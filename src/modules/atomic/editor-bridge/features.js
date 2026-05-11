/* eslint-env browser */

/**
 * Feature registry — declare each editor-side feature (live-bridge target)
 * once here. Everything else in editor-bridge consumes this:
 *   - applySettingsToDom uses attrMap to mirror settings → preview DOM
 *   - live-bridge subscribes to settings change events
 *   - play-button uses autoReplaySetting to decide whether to auto-replay
 *   - findTarget tells us which preview-iframe node carries the data-attrs
 *
 * Adding a new feature (e.g. tilt) = push one entry here. Nothing else.
 *
 * Each entry shape:
 *   {
 *     name:              'unique-id-for-logging',
 *     widgetTypes:       ['e-heading', 'e-paragraph', ...],
 *     enableSetting:     'aae_text_effect',          // null/none = feature off
 *     autoReplaySetting: 'aae_text_enable_editor',   // optional Boolean
 *     attrMap: {                                     // setting key → data-attr
 *       aae_text_effect: 'data-aae-text-anim',
 *       …
 *     },
 *     findTarget: (doc, id) => HTMLElement | null,
 *   }
 */
export const FEATURES = [
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

/** Find the feature for a given atomic container, or null if unsupported. */
export function featureFor(container) {
	const type = container?.model?.get?.('widgetType')
		|| container?.model?.get?.('elType');
	return FEATURES.find((f) => f.widgetTypes.includes(type)) || null;
}
