/* eslint-env browser */

import {
	isAnimated,
	isCustom,
	isDurationEffect,
	isEaseEffect,
	isFade,
	isMove,
	showEnableEditor,
	showFadeOffset,
	showPlayButton,
	showScale,
	showScrollCustomBlock,
	showStartCustom,
	showEndCustom,
	showTriggerSelector,
	showWrapper,
} from './predicates';

/**
 * Declarative table for the RegularAnimation section. Each row is rendered
 * by <ResponsiveRow> with its own label, input, and dot. `when` runs against
 * (settings, activeBreakpoint); rows whose predicate returns falsy are
 * skipped entirely (no label, no slot reserved) for the active breakpoint.
 *
 * The PHP side registers every responsive prop as Responsive_Json_Prop_Type
 * (see Schema.php). Their default values live
 * server-side; this table only carries the editor-UX concerns: label,
 * options, placeholder, visibility.
 */

/* ---------- option lists (mirror Schema::*() PHP maps) ---------- */

const EFFECT_OPTIONS = [
	{ value: 'none', label: 'None' },
	{ value: 'fade', label: 'Fade animation' },
	{ value: 'move', label: '3D Move' },
	{ value: 'custom', label: 'Custom' },
];

const METHOD_OPTIONS = [
	{ value: 'from', label: 'From' },
	{ value: 'to', label: 'To' },
];

const TRIGGER_OPTIONS = [
	{ value: 'on_scroll', label: 'On Scroll' },
	{ value: 'on_page_load', label: 'On Page Load' },
	{ value: 'play_with_scroll', label: 'Play With Scroll' },
	{ value: 'mouseover', label: 'On Hover' },
	{ value: 'click', label: 'On Click' },
];

const WRAPPER_OPTIONS = [
	{ value: 'default', label: 'Default' },
	{ value: 'custom', label: 'Custom' },
];

const FADE_DIRECTION_OPTIONS = [
	{ value: 'top', label: 'Top' },
	{ value: 'bottom', label: 'Bottom' },
	{ value: 'left', label: 'Left' },
	{ value: 'right', label: 'Right' },
	{ value: 'in', label: 'In' },
	{ value: 'scale', label: 'Zoom' },
];

const ROTATION_DIR_OPTIONS = [
	{ value: 'x', label: 'X' },
	{ value: 'y', label: 'Y' },
];

const EASE_OPTIONS = [
	{ value: 'power2.out', label: 'Power2.out' },
	{ value: 'bounce', label: 'Bounce' },
	{ value: 'back', label: 'Back' },
	{ value: 'elastic', label: 'Elastic' },
	{ value: 'slowmo', label: 'Slowmo' },
	{ value: 'stepped', label: 'Stepped' },
	{ value: 'sine', label: 'Sine' },
	{ value: 'expo', label: 'Expo' },
	{ value: 'none', label: 'None' },
];

const SCROLL_POSITION_OPTIONS = [
	'top top', 'top center', 'top bottom',
	'center top', 'center center', 'center bottom',
	'bottom top', 'bottom center', 'bottom bottom',
	'custom',
].map((v) => ({
	value: v,
	label: v.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
}));

/**
 * GSAP-compatible properties for the Custom Properties repeater. Mirrors
 * the v3 dropdown order. Values match GSAP's keyword names (kept unchanged
 * for runtime compatibility); labels are display-only.
 */
export const CUSTOM_PROPERTY_OPTIONS = [
	{ value: 'none', label: 'None' },
	{ value: 'opacity', label: 'Opacity' },
	{ value: 'x', label: 'X' },
	{ value: 'y', label: 'Y' },
	{ value: 'width', label: 'Width' },
	{ value: 'height', label: 'Height' },
	{ value: 'scale', label: 'Scale' },
	{ value: 'repeat', label: 'Repeat' },
	{ value: 'rotate', label: 'Rotate' },
	{ value: 'rotateX', label: 'RotateX' },
	{ value: 'rotateY', label: 'RotateY' },
	{ value: 'transformOrigin', label: 'TransformOrigin' },
	{ value: 'color', label: 'Color' },
	{ value: 'background', label: 'Background' },
	{ value: 'border', label: 'Border' },
	{ value: 'boxShadow', label: 'BoxShadow' },
	{ value: 'force3D', label: 'Force3D' },
	{ value: 'delay', label: 'Delay' },
	{ value: 'duration', label: 'Duration' },
	{ value: 'maxWidth', label: 'Max Width' },
	{ value: 'maxHeight', label: 'Max Height' },
	{ value: 'minWidth', label: 'Min Width' },
	{ value: 'minHeight', label: 'Min Height' },
	{ value: 'mixBlendMode', label: 'Mix Blend Mode' },
	{ value: 'padding', label: 'Padding' },
	{ value: 'borderRadius', label: 'Border Radius' },
	{ value: 'repeatDelay', label: 'Repeat Delay' },
	{ value: 'scaleX', label: 'ScaleX' },
	{ value: 'scaleY', label: 'ScaleY' },
	{ value: 'xPercent', label: 'XPercent' },
	{ value: 'yPercent', label: 'YPercent' },
	{ value: 'autoAlpha', label: 'Auto Alpha' },
	{ value: 'yoyo', label: 'YoYo' },
];

/* ---------- the table ---------- */

const config = {
	anchorKey: 'aae-section-aae-animation',
	bindPrefix: 'aae_anim_',
	fields: [
		{ bind: 'effect', label: 'Animation', control: 'select', options: EFFECT_OPTIONS, defaultValue: 'none', responsive: true },

		{ bind: 'method', label: 'Method', control: 'select', options: METHOD_OPTIONS, defaultValue: 'from', when: isAnimated, responsive: true },
		{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'on_page_load', when: isAnimated, responsive: true },

		{
			bind: 'trigger_selector', label: 'Trigger Selector', control: 'text',
			defaultValue: '', placeholder: '.my-class', when: showTriggerSelector, responsive: true
		},

		{ bind: 'wrapper', label: 'Wrapper', control: 'select', options: WRAPPER_OPTIONS, defaultValue: 'default', when: showWrapper, responsive: true },

		{
			bind: 'start_trigger', label: 'Start Trigger', control: 'text',
			defaultValue: '', placeholder: '.start_area', when: showScrollCustomBlock, responsive: true
		},
		{
			bind: 'end_trigger', label: 'End Trigger', control: 'text',
			defaultValue: '', placeholder: '.end_area', when: showScrollCustomBlock, responsive: true
		},
		{
			bind: 'start_position', label: 'Start', control: 'select',
			options: SCROLL_POSITION_OPTIONS, defaultValue: 'top top', when: showScrollCustomBlock, responsive: true
		},
		{
			bind: 'start_custom', label: 'Custom Start', control: 'text',
			defaultValue: 'top top', placeholder: 'top top+=100', when: showStartCustom, responsive: true
		},
		{
			bind: 'end_position', label: 'End', control: 'select',
			options: SCROLL_POSITION_OPTIONS, defaultValue: 'bottom top', when: showScrollCustomBlock, responsive: true
		},
		{
			bind: 'end_custom', label: 'Custom End', control: 'text',
			defaultValue: 'bottom top', placeholder: 'bottom top+=100', when: showEndCustom, responsive: true
		},

		{ bind: 'delay', label: 'Delay', control: 'number', defaultValue: 0.15, when: isDurationEffect, responsive: true },
		{ bind: 'duration', label: 'Duration', control: 'number', defaultValue: 1.5, when: isDurationEffect, responsive: true },
		{ bind: 'easing', label: 'Ease', control: 'select', options: EASE_OPTIONS, defaultValue: 'power2.out', when: isEaseEffect, responsive: true },

		{
			bind: 'fade_from', label: 'Fade From', control: 'select',
			options: FADE_DIRECTION_OPTIONS, defaultValue: 'bottom', when: isFade, responsive: true
		},
		{
			bind: 'fade_offset', label: 'Fade Offset', control: 'number',
			defaultValue: 50, when: showFadeOffset, responsive: true
		},
		{
			bind: 'scale', label: 'Start Scale', control: 'number',
			defaultValue: 0.7, when: showScale, responsive: true
		},

		{
			bind: 'rotation_dir', label: 'Rotation Direction', control: 'select',
			options: ROTATION_DIR_OPTIONS, defaultValue: 'x', when: isMove, responsive: true
		},
		{
			bind: 'rotation', label: 'Rotation Value', control: 'number',
			defaultValue: -80, when: isMove, responsive: true
		},
		{
			bind: 'transform_origin', label: 'Transform Origin', control: 'text',
			defaultValue: 'top center -50', placeholder: 'top center -50', when: isMove, responsive: true
		},

		// Custom Properties repeater (effect = custom). Stored as a
		// Responsive_Json_Prop_Type so the whole list can vary per breakpoint.
		// Each row is a plain { enabled, property, value } object — JS owns
		// the shape; PHP just round-trips the payload.
		{
			bind: 'custom_props', label: 'Custom Properties', control: 'repeater',
			defaultValue: [], when: isCustom, responsive: true,
			addLabel: 'Add Property',
			rowDefaults: { property: 'opacity', value: '' },
			cells: [
				{
					bind: 'property', type: 'select', placeholder: 'Property',
					options: CUSTOM_PROPERTY_OPTIONS
				},
				{ bind: 'value', type: 'text', placeholder: 'value' },
			]
		},

		// Non-responsive control rows.
		// Markers is a ScrollTrigger debug overlay — only meaningful for the
		// scroll-tied triggers, but we surface it whenever an effect is
		// selected (matches Animation/Method/Trigger visibility).
		{
			bind: 'markers', label: 'Markers', control: 'switch',
			responsive: false, defaultValue: false, when: isAnimated
		},
		{
			bind: 'enable_editor', label: 'Enable On Editor', control: 'switch',
			responsive: false, defaultValue: false, when: showEnableEditor
		},
		{ control: 'play-button', when: showPlayButton, play_group: 'aae_anim_' },
	],
};

export default config;
