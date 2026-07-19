/**
 * Lightbox Style — responsive React section for the container-level lightbox.
 *
 * Fields bind to `aae_lb_*` props (Schema.php). The runtime (container.js /
 * overlay.js) reads the published `style` bag from the container's LBC config,
 * resolves each value for the active breakpoint, and writes it as a CSS custom
 * property (`--aae-lb-<key>`) on the shared overlay root at OPEN time — so each
 * container styles the single global overlay independently.
 *
 * Convention:
 *   - sizing / spacing → `slider` (responsive, px) so tablet/mobile can differ.
 *   - colors           → `color`.
 *   - shadow           → `select` of presets (maps to a box-shadow at runtime).
 *   - full-width       → `switch`.
 *
 * The bind key (minus the `aae_lb_` prefix) matches the Schema constant; the
 * PHP style_map() then maps it to the short runtime key.
 */

const SHADOW_OPTIONS = [
	{ value: '',       label: 'Default' },
	{ value: 'none',   label: 'None' },
	{ value: 'soft',   label: 'Soft' },
	{ value: 'medium', label: 'Medium' },
	{ value: 'strong', label: 'Strong' },
];

const px = (size) => ({ size, unit: 'px' });

const config = {
	anchorKey: 'aae-section-aae-lightbox-style',
	bindPrefix: 'aae_lb_',
	fields: [
		/* ---------------- Overlay (backdrop) ---------------- */
		{
			bind: 'overlay_color', label: 'Overlay Color', control: 'color',
			defaultValue: '', tab: 'style',
		},
		{
			bind: 'overlay_opacity', label: 'Overlay Opacity (%)', control: 'slider',
			units: [''], defaultValue: { size: 92, unit: '' }, tab: 'style',
		},

		/* ---------------- Content container ---------------- */
		{
			bind: 'content_fullwidth', label: 'Full-Width Content', control: 'switch',
			defaultValue: false, responsive: false, tab: 'Content',
		},
		{
			bind: 'content_width', label: 'Content Width', control: 'slider',
			responsive: true, units: ['px', 'vw', '%'], defaultValue: px(''),
			tab: 'Content',
		},
		{
			bind: 'content_maxwidth', label: 'Content Max-Width', control: 'slider',
			responsive: true, units: ['px', 'vw', '%'], defaultValue: px(''),
			tab: 'Content',
		},
		{
			bind: 'content_padding', label: 'Content Padding', control: 'slider',
			responsive: true, units: ['px', 'em'], defaultValue: px(''),
			tab: 'Content',
		},
		{
			bind: 'content_radius', label: 'Content Radius', control: 'slider',
			responsive: true, units: ['px', '%'], defaultValue: px(''),
			tab: 'Content',
		},
		{
			bind: 'content_bg', label: 'Content Background', control: 'color',
			defaultValue: '', tab: 'style',
		},
		{
			bind: 'content_shadow', label: 'Content Shadow', control: 'select',
			options: SHADOW_OPTIONS, defaultValue: '', tab: 'style',
		},

		/* ---------------- Nav arrows (prev / next) ---------------- */
		{
			bind: 'arrow_size', label: 'Arrow Icon Size', control: 'slider',
			responsive: true, units: ['px'], defaultValue: px(''), tab: 'style',
		},
		{
			bind: 'arrow_box', label: 'Arrow Button Size', control: 'slider',
			responsive: true, units: ['px'], defaultValue: px(''), tab: 'style',
		},
		{
			bind: 'arrow_offset', label: 'Arrow Edge Offset', control: 'slider',
			responsive: true, units: ['px', 'vw'], defaultValue: px(''), tab: 'style',
		},
		{ bind: 'arrow_color', label: 'Arrow Color', control: 'color', defaultValue: '', tab: 'style' },
		{ bind: 'arrow_bg', label: 'Arrow Background', control: 'color', defaultValue: '', tab: 'style' },
		{
			bind: 'arrow_radius', label: 'Arrow Radius', control: 'slider',
			units: ['px', '%'], defaultValue: px(''), tab: 'style',
		},
		{
			bind: 'arrow_border_w', label: 'Arrow Border Width', control: 'slider',
			units: ['px'], defaultValue: px(''), tab: 'style',
		},
		{ bind: 'arrow_border_c', label: 'Arrow Border Color', control: 'color', defaultValue: '', tab: 'style' },
		{ bind: 'arrow_color_hover', label: 'Arrow Color (Hover)', control: 'color', defaultValue: '', tab: 'style' },
		{ bind: 'arrow_bg_hover', label: 'Arrow Background (Hover)', control: 'color', defaultValue: '', tab: 'style' },

		/* ---------------- Close button ---------------- */
		{
			bind: 'close_size', label: 'Close Icon Size', control: 'slider',
			responsive: true, units: ['px'], defaultValue: px(''), tab: 'style',
		},
		{
			bind: 'close_box', label: 'Close Button Size', control: 'slider',
			responsive: true, units: ['px'], defaultValue: px(''), tab: 'style',
		},
		{ bind: 'close_color', label: 'Close Color', control: 'color', defaultValue: '', tab: 'style' },
		{ bind: 'close_bg', label: 'Close Background', control: 'color', defaultValue: '', tab: 'style' },
		{
			bind: 'close_radius', label: 'Close Radius', control: 'slider',
			units: ['px', '%'], defaultValue: px(''), tab: 'style',
		},
		{
			bind: 'close_border_w', label: 'Close Border Width', control: 'slider',
			units: ['px'], defaultValue: px(''), tab: 'style',
		},
		{ bind: 'close_border_c', label: 'Close Border Color', control: 'color', defaultValue: '', tab: 'style' },
		{ bind: 'close_color_hover', label: 'Close Color (Hover)', control: 'color', defaultValue: '', tab: 'style' },
		{ bind: 'close_bg_hover', label: 'Close Background (Hover)', control: 'color', defaultValue: '', tab: 'style' },
	],
};

export default config;
