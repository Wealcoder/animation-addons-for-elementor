/* eslint-env browser */

import { presetRowPatch } from './presets';

/**
 * RegularAnimation section — REPEATER architecture.
 *
 * The whole section is now a single `interactions` repeater: every row is a
 * full, independent interaction (trigger + effect + method + all config +
 * its own custom-props list). Multiple interactions coexist on one element.
 *
 * Concurrency rule (enforced in InteractionsRepeaterInput, runtime, PHP):
 *   - on_page_load                  → max 1 per element
 *   - on_scroll / play_with_scroll  → one slot shared (max 1 total)
 *   - mouseover / click             → unlimited
 *
 * Each row's fields are described by `rowFields`. Their `when(rowData, bp)`
 * predicates read the FLAT row object (rowData.effect, rowData.trigger…),
 * NOT the per-bp envelope — the whole list is already per-breakpoint at the
 * outer `aae_anim_interactions` prop level.
 *
 * No legacy single-config path: storage is rows[] only.
 */

/* ---------- option lists ---------- */

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
// "Set" (instant state) is only meaningful for a custom animation — presets
// carry their own from/to, so an instant set makes no sense there. Offer it
// only when effect === 'custom'.
const SET_METHOD_OPTION = { value: 'set', label: 'Set' };
const methodOptionsFor = (r) =>
	(r?.effect === 'custom') ? [...METHOD_OPTIONS, SET_METHOD_OPTION] : METHOD_OPTIONS;

const TRIGGER_OPTIONS = [
	{ value: 'on_scroll', label: 'On Scroll' },
	{ value: 'on_page_load', label: 'On Page Load' },
	{ value: 'play_with_scroll', label: 'Play With Scroll' },
	{ value: 'mouseover', label: 'On Hover' },
	{ value: 'click', label: 'On Click' },
	{ value: 'on_slide_change', label: 'On Slide Change' },
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
];

/**
 * GSAP-compatible properties for the per-row Custom Properties repeater.
 */
export const CUSTOM_PROPERTY_OPTIONS = [
	{ value: 'none', label: 'None', category: 'General' },
	{ value: 'opacity', label: 'Opacity', category: 'General' },
	{ value: 'x', label: 'X', category: 'Transform' },
	{ value: 'y', label: 'Y', category: 'Transform' },
	{ value: 'z', label: 'Z', category: 'Transform' },
	{ value: 'width', label: 'Width', category: 'Dimensions' },
	{ value: 'height', label: 'Height', category: 'Dimensions' },
	{ value: 'maxWidth', label: 'Max Width', category: 'Dimensions' },
	{ value: 'maxHeight', label: 'Max Height', category: 'Dimensions' },
	{ value: 'minWidth', label: 'Min Width', category: 'Dimensions' },
	{ value: 'minHeight', label: 'Min Height', category: 'Dimensions' },
	{ value: 'scale', label: 'Scale', category: 'Transform' },
	{ value: 'scaleX', label: 'ScaleX', category: 'Transform' },
	{ value: 'scaleY', label: 'ScaleY', category: 'Transform' },
	{ value: 'rotate', label: 'Rotate', category: 'Transform' },
	{ value: 'rotateX', label: 'RotateX', category: 'Transform' },
	{ value: 'rotateY', label: 'RotateY', category: 'Transform' },
	{ value: 'rotation', label: 'Rotation', category: 'Transform' },
	{ value: 'rotationX', label: 'RotationX', category: 'Transform' },
	{ value: 'rotationY', label: 'RotationY', category: 'Transform' },
	{ value: 'skewX', label: 'SkewX', category: 'Transform' },
	{ value: 'skewY', label: 'SkewY', category: 'Transform' },
	{ value: 'xPercent', label: 'XPercent', category: 'Transform' },
	{ value: 'yPercent', label: 'YPercent', category: 'Transform' },
	{ value: 'transformOrigin', label: 'TransformOrigin', category: 'Transform' },
	{ value: 'perspective', label: 'Perspective', category: 'Transform' },
	{ value: 'transformPerspective', label: 'Transform Perspective', category: 'Transform' },
	{ value: 'backfaceVisibility', label: 'Backface Visibility', category: 'Transform' },
	{ value: 'transformStyle', label: 'Transform Style', category: 'Transform' },
	{ value: 'padding', label: 'Padding', category: 'Spacing' },
	{ value: 'margin', label: 'Margin', category: 'Spacing' },
	{ value: 'color', label: 'Color', category: 'Design' },
	{ value: 'background', label: 'Background', category: 'Design' },
	{ value: 'backgroundColor', label: 'Background Color', category: 'Design' },
	{ value: 'backgroundPosition', label: 'Background Position', category: 'Design' },
	{ value: 'backgroundPositionX', label: 'Background Position X', category: 'Design' },
	{ value: 'backgroundPositionY', label: 'Background Position Y', category: 'Design' },
	{ value: 'border', label: 'Border', category: 'Design' },
	{ value: 'borderColor', label: 'Border Color', category: 'Design' },
	{ value: 'borderRadius', label: 'Border Radius', category: 'Design' },
	{ value: 'boxShadow', label: 'BoxShadow', category: 'Design' },
	{ value: 'outline', label: 'Outline', category: 'Design' },
	{ value: 'outlineColor', label: 'Outline Color', category: 'Design' },
	{ value: 'outlineWidth', label: 'Outline Width', category: 'Design' },
	{ value: 'outlineOffset', label: 'Outline Offset', category: 'Design' },
	{ value: 'mixBlendMode', label: 'Mix Blend Mode', category: 'Design' },
	{ value: 'filter', label: 'Filter', category: 'Design' },
	{ value: 'backdropFilter', label: 'Backdrop Filter', category: 'Design' },
	{ value: 'clipPath', label: 'ClipPath', category: 'Design' },
	{ value: 'fontSize', label: 'Font Size', category: 'Typography' },
	{ value: 'lineHeight', label: 'Line Height', category: 'Typography' },
	{ value: 'letterSpacing', label: 'Letter Spacing', category: 'Typography' },
	{ value: 'wordSpacing', label: 'Word Spacing', category: 'Typography' },
	{ value: 'stroke', label: 'Stroke', category: 'SVG' },
	{ value: 'strokeWidth', label: 'Stroke Width', category: 'SVG' },
	{ value: 'fill', label: 'Fill', category: 'SVG' },
	{ value: 'strokeDashoffset', label: 'Stroke Dashoffset', category: 'SVG' },
	{ value: 'strokeDasharray', label: 'Stroke Dasharray', category: 'SVG' },
	{ value: 'top', label: 'Top', category: 'Positioning' },
	{ value: 'left', label: 'Left', category: 'Positioning' },
	{ value: 'right', label: 'Right', category: 'Positioning' },
	{ value: 'bottom', label: 'Bottom', category: 'Positioning' },
	{ value: 'zIndex', label: 'Z Index', category: 'Positioning' },
	{ value: 'overflow', label: 'Overflow', category: 'Overflow' },
	{ value: 'overflowX', label: 'Overflow X', category: 'Overflow' },
	{ value: 'overflowY', label: 'Overflow Y', category: 'Overflow' },
	{ value: 'visibility', label: 'Visibility', category: 'Overflow' },
	{ value: 'autoAlpha', label: 'Auto Alpha', category: 'GSAP Core' },
	{ value: 'delay', label: 'Delay', category: 'GSAP Core' },
	{ value: 'duration', label: 'Duration', category: 'GSAP Core' },
	{ value: 'repeat', label: 'Repeat', category: 'GSAP Core' },
	{ value: 'repeatDelay', label: 'Repeat Delay', category: 'GSAP Core' },
	{ value: 'yoyo', label: 'YoYo', category: 'GSAP Core' },
	{ value: 'ease', label: 'Ease', category: 'GSAP Core' },
	{ value: 'overwrite', label: 'Overwrite', category: 'GSAP Core' },
	{ value: 'force3D', label: 'Force3D', category: 'GSAP Core' },
];

/* ---------- per-row predicates (flat rowData, not envelope) ---------- */

const SCROLL_TRIGGERS = ['on_scroll', 'play_with_scroll'];
const SELECTOR_TRIGGERS = ['mouseover', 'click'];

const rowIsAnimated = (r) => !!r?.effect && r.effect !== 'none';
const rowTrigger = (r) => r?.trigger || 'on_scroll';
const rowIsScroll = (r) => SCROLL_TRIGGERS.includes(rowTrigger(r));
const rowIsSelector = (r) => SELECTOR_TRIGGERS.includes(rowTrigger(r));
const rowWrapperCustom = (r) => r?.wrapper === 'custom';

/* ---------- per-row field schema ---------- */

const SCROLL_DATALIST = SCROLL_POSITION_OPTIONS;

export const CUSTOM_PROPS_CELLS = [
	{
		bind: 'property', type: 'select', placeholder: 'Property',
		options: CUSTOM_PROPERTY_OPTIONS, width: 7, freeSolo: true, unique: true,
	},
	{ bind: 'value', type: 'dynamic-value', placeholder: 'value', width: 5 },
];

const ROW_FIELDS = [
	{
		bind: 'effect', label: 'Animation', control: 'select', options: EFFECT_OPTIONS, defaultValue: 'fadeUp',
		// Selecting a preset effect auto-fills the custom-props rows + sets
		// method='fromTo'. 'custom' / unknown returns null → no auto-fill.
		onSet: (_row, val) => presetRowPatch(val),
	},
	{ bind: 'method', label: 'Method', control: 'select', options: methodOptionsFor, defaultValue: 'fromTo', when: rowIsAnimated },
	{ bind: 'trigger', label: 'Trigger', control: 'select', options: TRIGGER_OPTIONS, defaultValue: 'on_scroll', when: rowIsAnimated },

	{
		bind: 'trigger_selector', label: 'Trigger Selector', control: 'text',
		placeholder: '.my-class', when: (r) => rowIsAnimated(r) && rowIsSelector(r),
	},
	{
		bind: 'wrapper', label: 'Wrapper', control: 'select', options: WRAPPER_OPTIONS, defaultValue: 'default',
		when: (r) => rowIsAnimated(r) && rowIsScroll(r),
	},
	{
		bind: 'start_trigger', label: 'Start Trigger', control: 'text', placeholder: '.start_area',
		when: (r) => rowIsAnimated(r) && rowIsScroll(r) && rowWrapperCustom(r),
	},
	{
		bind: 'end_trigger', label: 'End Trigger', control: 'text', placeholder: '.end_area',
		when: (r) => rowIsAnimated(r) && rowIsScroll(r) && rowWrapperCustom(r),
	},
	{
		bind: 'start_position', label: 'Start', control: 'text', datalist: SCROLL_DATALIST,
		placeholder: 'top 50%', when: (r) => rowIsAnimated(r) && rowIsScroll(r),
	},
	{
		bind: 'end_position', label: 'End', control: 'text', datalist: SCROLL_DATALIST,
		placeholder: 'bottom center', when: (r) => rowIsAnimated(r) && rowIsScroll(r),
	},

	{ bind: 'delay', label: 'Delay', control: 'slider', min: 0, max: 10, step: 0.05, defaultValue: 0.15, when: rowIsAnimated },
	{ bind: 'duration', label: 'Duration', control: 'slider', min: 0, max: 10, step: 0.1, defaultValue: 1.5, when: rowIsAnimated },
	{ bind: 'easing', label: 'Ease', control: 'select', options: EASE_OPTIONS, defaultValue: 'power2.out', when: rowIsAnimated },

	{
		bind: 'custom_props',
		label: (r) => (r?.method === 'fromTo' ? 'From Properties' : (r?.method === 'set' ? 'Set Properties' : 'Custom Properties')),
		control: 'repeater', addLabel: 'Add Property',
		rowDefaults: { property: '', value: '' },
		cells: CUSTOM_PROPS_CELLS,
		when: rowIsAnimated,
	},
	{
		bind: 'custom_props_to', label: 'To Properties', control: 'repeater', addLabel: 'Add Property',
		rowDefaults: { property: '', value: '' },
		cells: CUSTOM_PROPS_CELLS,
		when: (r) => rowIsAnimated(r) && r?.method === 'fromTo',
	},

	{ bind: 'markers', label: 'Markers', control: 'switch', defaultValue: false, when: (r) => rowIsAnimated(r) && rowIsScroll(r) },
];

const ROW_DEFAULTS = {
	effect: 'fadeUp',
	trigger: 'on_scroll',
	delay: 0.15,
	duration: 1.5,
	easing: 'power2.out',
	wrapper: 'default',
	start_position: 'top center',
	end_position: 'bottom bottom',
	// Seed the fadeUp preset so a freshly-added interaction animates
	// immediately (method + custom-props pre-filled). Mirrors presetRowPatch.
	...presetRowPatch('fadeUp'),
};

/* ---------- the section table ---------- */

const config = {
	anchorKey: 'aae-section-aae-animation',
	bindPrefix: 'aae_anim_',
	fields: [
		{
			bind: 'interactions',
			label: 'Interactions',
			control: 'interactions',
			defaultValue: [],
			responsive: true,
			play_group: 'aae_anim_',
			// No live_change auto-replay: editing a row (effect, trigger,
			// props) just saves. The user previews via the per-row ▶ play
			// button, which runs that one interaction in isolation. This
			// avoids stale/auto-firing animations while editing.
			live_change: false,
			addLabel: 'Add Interaction',
			rowFields: ROW_FIELDS,
			rowDefaults: ROW_DEFAULTS,
			help: 'Each interaction is an independent animation: trigger + effect + config. Page-load and scroll triggers allow one each; click and hover are unlimited.',
		},
		// No global "Enable On Editor" / "Play" — each interaction row has its
		// own ▶ play button for isolated preview.
	],
};

export default config;
