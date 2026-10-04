/* eslint-env browser */

/**
 * Nav — the one JS description of every dropdown-icon style row.
 *
 * Read by BOTH sides of the editor, which is the point of keeping it here:
 *   - config.js              builds the panel section from it
 *   - nav-responsive-preview builds the canvas <style> block from it
 *
 * MIRRORED in the Nav's `SECTIONS` table, which does the same job for the
 * frontend — and which lives in BOTH plugins:
 *   PRO  (live)     inc/AtomicV4/Widgets/Nav/class-aae-a-nav-responsive.php
 *   FREE (fallback) inc/AtomicWidgets/Widgets/Nav/class-aae-a-nav-responsive.php
 * `prefix` + `bind` + `sel` + `props` + `kind` must match those tables exactly.
 * Change one, change all three.
 *
 * WHY ONLY THE DESKTOP DROPDOWN ICON
 * ----------------------------------
 * The Nav injects four icons, and three of them — hamburger, close, back — are
 * real Atomic SVG children of the mobile companion, so they already have their
 * own Style tabs (select the icon in the Navigator: Width, Height and Color are
 * all there). They need no panel section here.
 *
 * The desktop dropdown indicator is the one that genuinely cannot be reached:
 * nav.js FETCHES the chosen SVG and inlines it into each has-dropdown label at
 * runtime (see injectDropdownIcons), so it is not a model, has no Style tab,
 * and nothing in the panel could size or colour it. That is what these rows fix.
 *
 * Unlike the WP Menu's table, rows here name a SELECTOR and a list of CSS
 * PROPERTIES rather than a single CSS variable: the injected icon has no
 * variable plumbing to re-set, so both builders emit complete declarations.
 * The reasoning is in the PHP file's docblock.
 *
 * `sel` is appended to the section's root selector — a leading space means "a
 * descendant of the root". `roots` picks which root: 'nav' is the Nav element
 * itself, which is where this icon lives.
 *
 * No `legacy` column, unlike the Menu's table: these rows are the FIRST way
 * this icon has ever been styleable, so there is no single-value prop for them
 * to inherit a display default from. Every row starts empty and emits nothing
 * until it is filled in.
 */

/**
 * Routes the post-write canvas refresh to nav-responsive-preview.js and, by
 * matching no animation feature, keeps the write from rebuilding unrelated
 * interaction configs on every keystroke.
 */
export const NAV_PLAY_GROUP = 'aae_nav_';

/* The two selectors the indicator's open state uses: the frontend class and the
 * editor's own dropdown-preview class — the same pair nav.scss rotates. */
const DROPDOWN_OPEN_SEL = [
	' .aae-a-nav-item.is-open > .aae-a-nav-item-label .aae-a-nav-dropdown-icon',
	' .aae-a-nav-item.aae-editor-dropdown-open > .aae-a-nav-item-label .aae-a-nav-dropdown-icon',
];

export const NAV_SECTIONS = [
	{
		id: 'dropdown_icon',
		anchorKey: 'aae-section-aae-nav-dropdown-icon',
		prefix: 'aae_ndi_',
		roots: [ 'nav' ],
		fields: [
			{
				bind: 'icon_size', label: 'Icon Size (px)', kind: 'px',
				sel: [ ' .aae-a-nav-dropdown-icon' ], props: [ 'inline-size', 'block-size' ],
				help: 'Left empty the icon scales with the menu text (0.7em), so it tracks Typography on its own. A value here pins it instead.',
			},
			{
				bind: 'gap', label: 'Gap from Label (px)', kind: 'px',
				sel: [ ' .aae-a-nav-dropdown-icon' ], props: [ 'margin-inline-start' ],
			},
			{
				bind: 'icon_color', label: 'Icon Color', kind: 'color', tab: 'Normal',
				sel: [ ' .aae-a-nav-dropdown-icon' ], props: [ 'color' ],
				help: 'Left empty the icon inherits the menu item’s own text color.',
			},
			{
				bind: 'hover_icon_color', label: 'Icon Color', kind: 'color', tab: 'Hover',
				sel: [ ' .aae-a-nav-item:hover > .aae-a-nav-item-label .aae-a-nav-dropdown-icon' ],
				props: [ 'color' ],
			},
			{
				bind: 'open_icon_color', label: 'Icon Color', kind: 'color', tab: 'Open',
				sel: DROPDOWN_OPEN_SEL, props: [ 'color' ],
			},
			{
				bind: 'open_rotate', label: 'Rotation (deg)', kind: 'deg', tab: 'Open',
				sel: DROPDOWN_OPEN_SEL, props: [ 'transform' ],
				min: -360, max: 360,
				help: 'How far the icon turns while its dropdown is open. Defaults to a 180° flip; set 0 to leave it still.',
			},
		],
	},
];

/**
 * Flat `prop key => { roots, sel, props, kind }` for the CSS builder, which
 * only cares about what a value becomes and where it lands, never about how
 * it is edited.
 */
export const CSS_FIELDS = NAV_SECTIONS.reduce( ( acc, section ) => {
	section.fields.forEach( ( field ) => {
		acc[ section.prefix + field.bind ] = {
			roots: section.roots,
			sel: field.sel,
			props: field.props,
			kind: field.kind,
		};
	} );
	return acc;
}, {} );
