/* eslint-env browser */

import {
	isAnimated,
	isDurationEffect,
	isEaseEffect,
	showEnableEditor,
	showPlayButton,
	showScrollCustomBlock,
	showScrollPosition,
	showTriggerSelector,
	showWrapper,
	showMarkers,
} from './predicates';
import { valueAt } from '../../responsive-section/helpers';

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
	{ value: 'custom', label: '--- Custom Animations ---' },
	{ value: 'fadeUp', label: '1. Classic Fade Up' },
	{ value: 'blurReveal', label: '2. Blur Reveal (Apple Style)' },
	{ value: 'skewUp', label: '3. Skew Up (Awwwards)' },
	{ value: 'clipReveal', label: '4. Clip-Path Unmask' },
	{ value: 'scaleIn', label: '5. Scale In Pop' },
	{ value: 'zoomOut', label: '6. Zoom Out' },
	{ value: 'flipUp3D', label: '7. 3D Flip Up' },
	{ value: 'swingDrop', label: '8. Swing Drop' },
	{ value: 'elasticPop', label: '9. Elastic Pop' },
	{ value: 'flipY', label: '10. 3D Card Flip (Y)' },
	{ value: 'spinIn', label: '11. Spin & Scale' },
	{ value: 'slideRight', label: '12. Slide Right' },
	{ value: 'cinematicFocus', label: '13. Cinematic Focus (Premium)' },
	{ value: 'maskRevealUp', label: '14. Mask Reveal Up (Luxury)' },
	{ value: 'perspectiveFall', label: '15. Perspective Fall (3D)' },
	{ value: 'unfold3D', label: '16. 3D Unfold (SaaS)' },
	{ value: 'magneticSlide', label: '17. Magnetic Slide' },
	{ value: 'luxDrift', label: '18. Luxury Drift (Colorize)' },
	{ value: 'saasDashboard', label: '19. SaaS Dashboard Build' },
	{ value: 'ecomUnbox', label: '20. E-com Product Unbox' },
	{ value: 'neonPulse', label: '21. Neon Glow Pulse (Pricing)' },
	{ value: 'floatIn', label: '22. Gentle Float In (Editorial)' },
];

const METHOD_OPTIONS = [
	{ value: 'from', label: 'From' },
	{ value: 'to', label: 'To' },
	{ value: 'fromTo', label: 'From To' },
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
	{ value: 'filter', label: 'Filter' },
	{ value: 'skewX', label: 'SkewX' },
	{ value: 'skewY', label: 'SkewY' },
	{ value: 'clipPath', label: 'ClipPath' },
	{ value: 'z', label: 'Z' },
	{ value: 'rotation', label: 'Rotation' },
	{ value: 'rotationX', label: 'RotationX' },
	{ value: 'rotationY', label: 'RotationY' },
];

/* ---------- the table ---------- */

const config = {
	anchorKey: 'aae-section-aae-animation',
	bindPrefix: 'aae_anim_',
	fields: [
		{ bind: 'effect', label: 'Animation', control: 'select', options: EFFECT_OPTIONS, defaultValue: 'none', responsive: true, play_group: 'aae_anim_'  },

		{ bind: 'method', label: 'Method', control: 'select', options: METHOD_OPTIONS, defaultValue: 'from', when: isAnimated, responsive: true },
		{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'on_scroll', when: isAnimated, responsive: true },

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
			bind: 'start_position', label: 'Start', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
			defaultValue: 'top center', placeholder: 'top 50%', when: showScrollPosition, responsive: true
		},
		{
			bind: 'end_position', label: 'End', control: 'text', datalist: SCROLL_POSITION_OPTIONS,
			defaultValue: 'bottom bottom', placeholder: 'bottom center', when: showScrollPosition, responsive: true
		},

		{ bind: 'delay', label: 'Delay', control: 'number', defaultValue: 0.15, when: isDurationEffect, responsive: true },
		{ bind: 'duration', label: 'Duration', control: 'number', defaultValue: 1.5, when: isDurationEffect, responsive: true },
		{ bind: 'easing', label: 'Ease', control: 'select', options: EASE_OPTIONS, defaultValue: 'power2.out', when: isEaseEffect, responsive: true },

		// Custom Properties repeater (effect = custom). Stored as a
		// Responsive_Json_Prop_Type so the whole list can vary per breakpoint.
		// Each row is a plain { enabled, property, value } object — JS owns
		// the shape; PHP just round-trips the payload.
		{
			bind: 'custom_props', 
			label: (s, bp) => valueAt(s, 'aae_anim_method', bp) === 'fromTo' ? 'From Properties' : 'Custom Properties', 
			control: 'repeater',
			defaultValue: [], when: isAnimated, responsive: true,
			addLabel: 'Add Property',
			innerTabGroup: (s, bp) => valueAt(s, 'aae_anim_method', bp) === 'fromTo' ? 'props' : null,
			innerTabLabel: 'From',
			rowDefaults: { property: '', value: '' },
			cells: [
				{
					bind: 'property', type: 'select', placeholder: 'Property',
					options: CUSTOM_PROPERTY_OPTIONS, width: 7, freeSolo: true, unique: true
				},
				{ bind: 'value', type: 'text', placeholder: 'value', width: 3 },
			]
		},
		{
			bind: 'custom_props_to', label: 'To Properties', control: 'repeater',
			defaultValue: [], when: (s, bp) => isAnimated(s, bp) && valueAt(s, 'aae_anim_method', bp) === 'fromTo', responsive: true,
			addLabel: 'Add Property',
			innerTabGroup: 'props',
			innerTabLabel: 'To',
			rowDefaults: { property: '', value: '' },
			cells: [
				{
					bind: 'property', type: 'select', placeholder: 'Property',
					options: CUSTOM_PROPERTY_OPTIONS, width: 7, freeSolo: true, unique: true
				},
				{ bind: 'value', type: 'text', placeholder: 'value', width: 3 },
			]
		},

		// Non-responsive control rows.
		// Markers is a ScrollTrigger debug overlay — only meaningful for the
		// scroll-tied triggers, so we only show it when the trigger is a scroll type.
		{
			bind: 'markers', label: 'Markers', control: 'switch',
			responsive: false, defaultValue: false, when: showMarkers
		},
		{
			bind: 'enable_editor', label: 'Enable On Editor', control: 'switch',
			responsive: false, defaultValue: false, when: showEnableEditor
		},
		{ control: 'play-button', when: showPlayButton, play_group: 'aae_anim_' },
	],
};

export default config;
