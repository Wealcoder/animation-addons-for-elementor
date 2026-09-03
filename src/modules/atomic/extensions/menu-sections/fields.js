/* eslint-env browser */

/**
 * WP Menu — the one JS description of every responsive style row.
 *
 * Read by BOTH sides of the editor, which is the point of keeping it here:
 *   - config.js               builds the panel sections from it
 *   - menu-responsive-preview builds the canvas <style> block from it
 *
 * MIRRORED in Widgets/Menu/class-aae-a-menu-responsive.php (`SECTIONS`),
 * which does the same job for the frontend. `bind` + `cssVar` + `kind` must
 * match that table exactly — scratchpad/parity.mjs diffs the two and the CSS
 * output they produce. Change one, change both.
 *
 * `legacy` names the widget's ORIGINAL single-value prop for this variable.
 * It is never written to; it only supplies each row's display default, so an
 * existing menu opens showing the value it has always rendered with instead
 * of an empty box. `legacyDefault` is the Twig's own fallback, used when that
 * prop was never set either.
 */

export const BORDER_STYLE_OPTIONS = [
	{ value: 'solid',  label: 'Solid' },
	{ value: 'dashed', label: 'Dashed' },
	{ value: 'dotted', label: 'Dotted' },
	{ value: 'double', label: 'Double' },
	{ value: 'none',   label: 'None' },
];

const ALIGN_OPTIONS = [
	{ value: 'flex-start',    label: 'Left' },
	{ value: 'center',        label: 'Center' },
	{ value: 'flex-end',      label: 'Right' },
	{ value: 'space-between', label: 'Justify' },
];

const FONT_WEIGHT_OPTIONS = [
	{ value: '400', label: 'Normal' },
	{ value: '500', label: 'Medium' },
	{ value: '600', label: 'Semi Bold' },
	{ value: '700', label: 'Bold' },
];

/**
 * Routes the post-write canvas refresh to menu-responsive-preview.js and, by
 * matching no animation feature, keeps the write from rebuilding unrelated
 * interaction configs on every keystroke.
 */
export const MENU_PLAY_GROUP = 'aae_menu_';

/**
 * PADDING AND MARGIN — why some rows are `hidden` rather than deleted.
 *
 * Padding shipped as pairs of plain numbers (Item Padding X / Y) and, on the
 * panel and the toggle, as one number covering all four sides. Those are what
 * the stylesheet reads, and a saved menu may hold a value — including a
 * per-breakpoint one — for any of them.
 *
 * So the X / Y rows are `hidden`, never removed: they stay in this table, keep
 * their prop, and keep emitting their CSS variable, so nothing a builder saved
 * stops applying. They simply no longer draw a row, because the 4-side Padding
 * control covers the same ground and opens showing their current value as its
 * own starting point (see `legacyPair` and config.js's `paddingFromLegacy`).
 *
 * Toggle Padding and Dropdown Panel Padding were ALREADY single values on a
 * variable of their own, so those two are converted in place to `dimensions`
 * instead of being duplicated — the sanitiser reads a bare number as "all four
 * sides", which is exactly what the stored value already meant.
 *
 * `sideVars` publishes ONE side on a variable of its own. Two rules in
 * menu.scss need a single length rather than the shorthand — the arrow-gap calc
 * needs the right side, the vertical indent calc needs the leading side — and
 * `calc()` cannot take a 4-value shorthand.
 */

export const MENU_SECTIONS = [
	{
		id: 'items',
		anchorKey: 'aae-section-aae-menu-items',
		prefix: 'aae_mi_',
		fields: [
			{ bind: 'text_color',    cssVar: '--aae-menu-color',          kind: 'color', legacy: 'text_color',    legacyDefault: null, label: 'Text Color',        placeholder: '#1f2937' },
			{ bind: 'hover_color',   cssVar: '--aae-menu-hover-color',    kind: 'color', legacy: 'hover_color',   legacyDefault: null, label: 'Hover Text Color',  placeholder: '#2563eb' },
			{ bind: 'item_hover_bg', cssVar: '--aae-menu-item-hover-bg',  kind: 'color', legacy: 'item_hover_bg', legacyDefault: null, label: 'Hover Background',  placeholder: 'rgba(0,0,0,0.05)' },
			{ bind: 'active_color',  cssVar: '--aae-menu-active-color',   kind: 'color', legacy: 'active_color',  legacyDefault: null, label: 'Active Color',      placeholder: '#2563eb' },
			// The active row's weight, previously a hardcoded 600 in menu.scss. Sits
			// next to Active Color because the two describe the same state, and it is
			// the only typography row on the widget — everything else is the Style
			// tab's, which cannot target .current-menu-item on its own.
			{ bind: 'active_weight', cssVar: '--aae-menu-active-weight',  kind: 'enum',  allowed: FONT_WEIGHT_OPTIONS, legacy: 'active_weight', legacyDefault: '600', label: 'Active Font Weight' },
			{ bind: 'padding_x',     cssVar: '--aae-menu-item-padding-x', kind: 'px',    legacy: 'padding_x',     legacyDefault: 14,   label: 'Item Padding X (px)', hidden: true },
			{ bind: 'padding_y',     cssVar: '--aae-menu-item-padding-y', kind: 'px',    legacy: 'padding_y',     legacyDefault: 10,   label: 'Item Padding Y (px)', hidden: true },
			{
				bind: 'padding', cssVar: '--aae-menu-item-padding', kind: 'dimensions',
				sideVars: { right: '--aae-menu-item-padding-right' },
				legacyPair: { x: 'padding_x', y: 'padding_y' }, legacyPairDefault: { x: 14, y: 10 },
				label: 'Padding',
				help: 'Opens showing the padding this menu already has. The mobile drawer keeps its own 12/14 spacing \u2014 a tap target is not the desktop metric.',
			},
			{
				bind: 'margin', cssVar: '--aae-menu-item-margin', kind: 'dimensions',
				label: 'Margin',
				help: 'Space OUTSIDE each item, on top of Item Gap. Reach for Gap first \u2014 it spaces items evenly without changing the row height.',
			},
			{ bind: 'item_gap',      cssVar: '--aae-menu-item-gap',       kind: 'px',    legacy: 'item_gap',      legacyDefault: 4,    label: 'Item Gap (px)' },
			{ bind: 'link_radius',   cssVar: '--aae-menu-link-radius',    kind: 'px',    legacy: 'link_radius',   legacyDefault: 6,    label: 'Item Radius (px)' },
			{
				bind: 'item_border_width', cssVar: '--aae-menu-item-border-width', kind: 'px',
				legacy: 'item_border_width', legacyDefault: 0, label: 'Border Width (px)',
				help: 'Drawn around each menu item. Raising it makes the items slightly larger, since the border sits outside the padding.',
			},
			{ bind: 'item_border_style', cssVar: '--aae-menu-item-border-style', kind: 'enum',  allowed: BORDER_STYLE_OPTIONS, legacy: 'item_border_style', legacyDefault: 'solid', label: 'Border Style' },
			{ bind: 'item_border_color', cssVar: '--aae-menu-item-border-color', kind: 'color', legacy: 'item_border_color', legacyDefault: null, label: 'Border Color', placeholder: 'rgba(0,0,0,0.08)' },
		],
	},

	{
		id: 'layout',
		anchorKey: 'aae-section-aae-menu-layout',
		prefix: 'aae_ml_',
		fields: [
			{
				bind: 'align', cssVar: '--aae-menu-align', kind: 'enum', allowed: ALIGN_OPTIONS,
				legacy: 'align', legacyDefault: 'center', label: 'Alignment',
				help: 'Applies to the Horizontal layout. A Vertical menu always aligns to the start, so its indented sub-items line up on one edge.',
			},
		],
	},

	{
		id: 'dropdown_panel',
		anchorKey: 'aae-section-aae-menu-dropdown-panel',
		prefix: 'aae_mdp_',
		fields: [
			{ bind: 'bg', cssVar: '--aae-menu-dropdown-bg', kind: 'color', legacy: 'dropdown_bg', legacyDefault: null, label: 'Background', placeholder: '#ffffff' },
			{
				bind: 'panel_padding', cssVar: '--aae-menu-dropdown-panel-padding', kind: 'dimensions',
				sideVars: { left: '--aae-menu-dropdown-panel-padding-left' },
				legacy: 'dropdown_panel_padding', legacyDefault: 6, label: 'Padding',
				help: 'Inset between the panel edge and the items inside it. In a Vertical layout it stacks with the indent rather than replacing it.',
			},
			{ bind: 'min_width', cssVar: '--aae-menu-dropdown-min-width', kind: 'px', legacy: 'dropdown_min_width', legacyDefault: 220, label: 'Min Width (px)' },
			{ bind: 'radius',    cssVar: '--aae-menu-dropdown-radius',    kind: 'px', legacy: 'dropdown_radius',    legacyDefault: 8,   label: 'Border Radius (px)' },
			{
				bind: 'border_width', cssVar: '--aae-menu-dropdown-border-width', kind: 'px',
				legacy: 'dropdown_border_width', legacyDefault: 1, label: 'Border Width (px)',
				help: 'The panel ships with a 1px hairline. Set to 0 to remove it — with the drop shadow gone, that leaves the panel with no edge at all, so give it a background first.',
			},
			{ bind: 'border_style', cssVar: '--aae-menu-dropdown-border-style', kind: 'enum',  allowed: BORDER_STYLE_OPTIONS, legacy: 'dropdown_border_style', legacyDefault: 'solid', label: 'Border Style' },
			{ bind: 'border_color', cssVar: '--aae-menu-dropdown-border-color', kind: 'color', legacy: 'dropdown_border_color', legacyDefault: null, label: 'Border Color', placeholder: 'rgba(0,0,0,0.08)' },
		],
	},

	{
		id: 'dropdown_items',
		anchorKey: 'aae-section-aae-menu-dropdown-items',
		prefix: 'aae_mdi_',
		// Resting pair first, then the matching hover pair, so the two read as
		// before/after of the same two properties rather than four unrelated
		// colour fields; then padding → gap → radius, mirroring Menu Items Style.
		fields: [
			{ bind: 'bg',               cssVar: '--aae-menu-dropdown-item-bg',          kind: 'color', legacy: 'dropdown_item_bg',          legacyDefault: null, label: 'Background',       placeholder: 'transparent' },
			{ bind: 'text_color',       cssVar: '--aae-menu-dropdown-text-color',       kind: 'color', legacy: 'dropdown_text_color',       legacyDefault: null, label: 'Text Color',       placeholder: '#1a1a18' },
			// Placeholder says "None", not a colour: there is no default hover wash
			// to hint at, and a colour here would promise a highlight that never
			// appears until this is set.
			{ bind: 'hover_bg',         cssVar: '--aae-menu-dropdown-hover-bg',         kind: 'color', legacy: 'dropdown_hover_bg',         legacyDefault: null, label: 'Hover Background', placeholder: 'None' },
			{ bind: 'hover_text_color', cssVar: '--aae-menu-dropdown-hover-text-color', kind: 'color', legacy: 'dropdown_hover_text_color', legacyDefault: null, label: 'Hover Text Color', placeholder: '#2563eb' },
			{ bind: 'padding_x',        cssVar: '--aae-menu-dropdown-padding-x',        kind: 'px',    legacy: 'dropdown_padding_x',        legacyDefault: 14,   label: 'Padding X (px)', hidden: true },
			{ bind: 'padding_y',        cssVar: '--aae-menu-dropdown-padding-y',        kind: 'px',    legacy: 'dropdown_padding_y',        legacyDefault: 9,    label: 'Padding Y (px)', hidden: true },
			{
				bind: 'padding', cssVar: '--aae-menu-dropdown-item-padding', kind: 'dimensions',
				sideVars: { right: '--aae-menu-dropdown-item-padding-right' },
				legacyPair: { x: 'dropdown_padding_x', y: 'dropdown_padding_y' }, legacyPairDefault: { x: 14, y: 9 },
				label: 'Padding',
			},
			{
				bind: 'margin', cssVar: '--aae-menu-dropdown-item-margin', kind: 'dimensions',
				label: 'Margin',
				help: 'Space OUTSIDE each dropdown row. Gap below spaces them evenly; use this when one edge needs to differ.',
			},
			{ bind: 'gap',              cssVar: '--aae-menu-dropdown-item-gap',         kind: 'px',    legacy: 'dropdown_item_gap',         legacyDefault: 2,    label: 'Gap (px)' },
			{ bind: 'radius',           cssVar: '--aae-menu-dropdown-item-radius',      kind: 'px',    legacy: 'dropdown_item_radius',      legacyDefault: 4,    label: 'Border Radius (px)' },
		],
	},

	{
		id: 'toggle',
		anchorKey: 'aae-section-aae-menu-toggle',
		prefix: 'aae_mtg_',
		fields: [
			{ bind: 'color',    cssVar: '--aae-menu-toggle-color',    kind: 'color', legacy: 'toggle_color',    legacyDefault: null, label: 'Icon Color',       placeholder: 'Inherits menu text color' },
			{ bind: 'bg',       cssVar: '--aae-menu-toggle-bg',       kind: 'color', legacy: 'toggle_bg',       legacyDefault: null, label: 'Background',       placeholder: 'transparent' },
			{ bind: 'hover_bg', cssVar: '--aae-menu-toggle-hover-bg', kind: 'color', legacy: 'toggle_hover_bg', legacyDefault: null, label: 'Hover Background', placeholder: 'rgba(0,0,0,0.05)' },
			{ bind: 'size',     cssVar: '--aae-menu-toggle-size',     kind: 'px',    legacy: 'toggle_size',     legacyDefault: 28,   label: 'Button Size (px)' },
			{
				bind: 'padding', cssVar: '--aae-menu-toggle-padding', kind: 'dimensions',
				legacy: 'toggle_padding', legacyDefault: 0, label: 'Padding',
				help: 'Insets the glyph inside the button. Button Size still governs the outer box, so this trades icon area for breathing room rather than growing the button.',
			},
			{
				bind: 'gap', cssVar: '--aae-menu-toggle-gap', kind: 'px',
				legacy: 'toggle_gap', legacyDefault: 10, label: 'Gap (px)',
				help: 'Distance from the menu label to the toggle button. Measured from the text, so changing Item Padding X no longer drags the icon along with it.',
			},
			{ bind: 'icon_size', cssVar: '--aae-menu-toggle-icon-size', kind: 'px', legacy: 'toggle_icon_size', legacyDefault: 10, label: 'Icon Size (px)' },
			{ bind: 'radius',    cssVar: '--aae-menu-toggle-radius',    kind: 'px', legacy: 'toggle_radius',    legacyDefault: 50, label: 'Border Radius (px)' },
		],
	},

	{
		id: 'hamburger',
		anchorKey: 'aae-section-aae-menu-hamburger',
		prefix: 'aae_mhb_',
		fields: [
			{ bind: 'color',    cssVar: '--aae-menu-hamburger-color',    kind: 'color', legacy: 'hamburger_color',    legacyDefault: null, label: 'Icon Color',       placeholder: 'Inherits menu text color' },
			{ bind: 'bg',       cssVar: '--aae-menu-hamburger-bg',       kind: 'color', legacy: 'hamburger_bg',       legacyDefault: null, label: 'Background',       placeholder: 'transparent' },
			{ bind: 'hover_bg', cssVar: '--aae-menu-hamburger-hover-bg', kind: 'color', legacy: 'hamburger_hover_bg', legacyDefault: null, label: 'Hover Background', placeholder: 'rgba(0,0,0,0.05)' },
			{ bind: 'size',     cssVar: '--aae-menu-hamburger-size',     kind: 'px',    legacy: 'hamburger_size',     legacyDefault: 40,   label: 'Button Size (px)' },
			{ bind: 'radius',   cssVar: '--aae-menu-hamburger-radius',   kind: 'px',    legacy: 'hamburger_radius',   legacyDefault: 6,    label: 'Border Radius (px)' },
			{
				bind: 'border_width', cssVar: '--aae-menu-hamburger-border-width', kind: 'px',
				legacy: 'hamburger_border_width', legacyDefault: 1, label: 'Border Width (px)',
				help: 'Set to 0 for no border.',
			},
			{ bind: 'border_color',  cssVar: '--aae-menu-hamburger-border-color',  kind: 'color', legacy: 'hamburger_border_color',  legacyDefault: null, label: 'Border Color', placeholder: 'rgba(0,0,0,0.08)' },
			{ bind: 'bar_width',     cssVar: '--aae-menu-hamburger-bar-width',     kind: 'px',    legacy: 'hamburger_bar_width',     legacyDefault: 18,   label: 'Bar Width (px)' },
			{ bind: 'bar_thickness', cssVar: '--aae-menu-hamburger-bar-thickness', kind: 'px',    legacy: 'hamburger_bar_thickness', legacyDefault: 2,    label: 'Bar Thickness (px)' },
			{
				bind: 'bar_gap', cssVar: '--aae-menu-hamburger-bar-gap', kind: 'px',
				legacy: 'hamburger_bar_gap', legacyDefault: 4, label: 'Bar Gap (px)',
				help: 'Also sets how far the top and bottom bars travel when they cross into the close (X) state.',
			},
		],
	},

	{
		id: 'drawer_header',
		anchorKey: 'aae-section-aae-menu-drawer-header',
		prefix: 'aae_mdh_',
		fields: [
			{
				bind: 'logo_width', cssVar: '--aae-menu-drawer-logo-width', kind: 'px',
				legacy: 'drawer_logo_width', legacyDefault: 120, label: 'Logo Width (px)',
				help: 'Height follows the image’s own aspect ratio.',
			},
			{ bind: 'label_color',  cssVar: '--aae-menu-drawer-label-color',  kind: 'color', legacy: 'drawer_label_color',  legacyDefault: null,  label: 'Label Color', placeholder: 'Inherits menu text color' },
			{ bind: 'label_size',   cssVar: '--aae-menu-drawer-label-size',   kind: 'px',    legacy: 'drawer_label_size',   legacyDefault: 17,    label: 'Label Font Size (px)' },
			{ bind: 'label_weight', cssVar: '--aae-menu-drawer-label-weight', kind: 'enum',  allowed: FONT_WEIGHT_OPTIONS, legacy: 'drawer_label_weight', legacyDefault: '600', label: 'Label Font Weight' },
			{
				bind: 'border_width', cssVar: '--aae-menu-drawer-header-border-width', kind: 'px',
				legacy: 'drawer_header_border_width', legacyDefault: 1, label: 'Border Width (px)',
				help: 'The divider under the header. Set to 0 to remove it.',
			},
			{ bind: 'border_style', cssVar: '--aae-menu-drawer-header-border-style', kind: 'enum',  allowed: BORDER_STYLE_OPTIONS, legacy: 'drawer_header_border_style', legacyDefault: 'solid', label: 'Border Style' },
			{ bind: 'border_color', cssVar: '--aae-menu-drawer-header-border-color', kind: 'color', legacy: 'drawer_header_border_color', legacyDefault: null, label: 'Border Color', placeholder: 'rgba(0,0,0,0.08)' },
		],
	},

	{
		id: 'drawer',
		anchorKey: 'aae-section-aae-menu-drawer',
		prefix: 'aae_mdr_',
		fields: [
			{ bind: 'width',   cssVar: '--aae-menu-drawer-width',   kind: 'px',    legacy: 'drawer_width',   legacyDefault: 320,  label: 'Width (px)' },
			{ bind: 'bg',      cssVar: '--aae-menu-drawer-bg',      kind: 'color', legacy: 'drawer_bg',      legacyDefault: null, label: 'Background',    placeholder: '#ffffff' },
			{ bind: 'overlay', cssVar: '--aae-menu-drawer-overlay', kind: 'color', legacy: 'overlay_color',  legacyDefault: null, label: 'Overlay Color', placeholder: 'rgba(0,0,0,0.5)' },
			{
				bind: 'border_width', cssVar: '--aae-menu-drawer-border-width', kind: 'px',
				legacy: 'drawer_border_width', legacyDefault: 0, label: 'Border Width (px)',
				help: 'Drawn around the whole drawer panel. Only the edge facing the page is normally visible — the other three sit against the viewport.',
			},
			{ bind: 'border_style', cssVar: '--aae-menu-drawer-border-style', kind: 'enum',  allowed: BORDER_STYLE_OPTIONS, legacy: 'drawer_border_style', legacyDefault: 'solid', label: 'Border Style' },
			{ bind: 'border_color', cssVar: '--aae-menu-drawer-border-color', kind: 'color', legacy: 'drawer_border_color', legacyDefault: null, label: 'Border Color', placeholder: 'rgba(0,0,0,0.08)' },
		],
	},

	{
		id: 'motion',
		anchorKey: 'aae-section-aae-menu-motion',
		prefix: 'aae_mmo_',
		fields: [
			{ bind: 'transition_ms', cssVar: '--aae-menu-transition', kind: 'transition', legacy: 'transition_ms', legacyDefault: 250, label: 'Transition Duration (ms)' },
		],
	},
];

/**
 * Flat `prop key => { cssVar, kind, allowed }` for the CSS builder, which
 * only cares about what a value becomes, never about how it is edited.
 */
export const CSS_FIELDS = MENU_SECTIONS.reduce((acc, section) => {
	section.fields.forEach((field) => {
		acc[section.prefix + field.bind] = {
			cssVar: field.cssVar,
			kind: field.kind,
			allowed: field.allowed ? field.allowed.map((o) => o.value) : null,
			// `dimensions` rows only. A hidden row still lands here: it draws no
			// control any more, but it must keep emitting what it already stores.
			sideVars: field.sideVars || null,
		};
	});
	return acc;
}, {});
