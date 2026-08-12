<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Menu;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Image_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Menu extends Atomic_Widget_Base {
	use Has_Template;

	const TD = 'animation-addons-for-elementor';

	/**
	 * Default sub-menu toggle icons, relative to WCF_ADDONS_URL.
	 *
	 * The FILENAMES are load-bearing: aae-a-menu.html.twig decides whether the
	 * builder picked a custom icon by testing the resolved url against these
	 * paths. It has to compare strings rather than read a constant because the
	 * template also renders client-side in the editor, where no PHP value
	 * exists. Rename a file and the widget silently starts treating its own
	 * default as a custom upload — keep the two in sync.
	 */
	const TOGGLE_ICON_DEFAULT      = 'inc/AtomicWidgets/Widgets/Menu/assets/icons/chevron-down.svg';
	const TOGGLE_ICON_OPEN_DEFAULT = 'inc/AtomicWidgets/Widgets/Menu/assets/icons/chevron-up.svg';

	public static function get_element_type(): string {
		return 'e-aae-a-menu';
	}

	public function get_title() {
		return esc_html__( 'WP Menu', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function get_keywords() {
		return [ 'menu', 'wp', 'navigation' ];
	}

	/**
	 * Panel categories.
	 *
	 * This is a leaf (Atomic_Widget_Base), so get_categories() IS the hook
	 * Elementor reads — no define_panel_categories() override needed. Sits with
	 * Nav and Site Logo under "AAE Header & Footer" (`wcf-hf-addon`, registered
	 * in class-plugin.php::widget_categories()), matching the 'header-footer'
	 * category its dashboard card already declares.
	 */
	public function get_categories(): array {
		return [ 'aae-atomic-general', 'wcf-hf-addon' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Content
			'menu'      => String_Prop_Type::make()->default( '' ),

			// Layout
			'layout'    => String_Prop_Type::make()->default( 'horizontal' ),
			'align'     => String_Prop_Type::make()->default( 'center' ),
			'hamburger' => Boolean_Prop_Type::make()->default( true ),
			'breakpoint' => Number_Prop_Type::make()->default( 768 ),
			'mobile_label' => String_Prop_Type::make()->default( 'Menu' ),

			// Items
			'text_color'    => String_Prop_Type::make()->default( '' ),
			'hover_color'   => String_Prop_Type::make()->default( '' ),
			'item_hover_bg' => String_Prop_Type::make()->default( '' ),
			'active_color'  => String_Prop_Type::make()->default( '' ),
			// No font_size / font_weight: typography is the Style tab's job. A
			// widget-level copy fought it and won — our stylesheet is printed
			// AFTER Elementor's per-document CSS on purpose (see
			// fix_frontend_atomic_css_order()), so a hardcoded 15px/500 here beat
			// whatever Typography the builder set and looked like a broken panel.
			'padding_x'     => Number_Prop_Type::make()->default( 14 ),
			'padding_y'     => Number_Prop_Type::make()->default( 10 ),
			'item_gap'      => Number_Prop_Type::make()->default( 4 ),
			'link_radius'   => Number_Prop_Type::make()->default( 6 ),
			// Width 0 = the current look (menu items have never had a border), so
			// nothing gains an outline until this is raised.
			'item_border_width' => Number_Prop_Type::make()->default( 0 ),
			'item_border_style' => String_Prop_Type::make()->enum( [ 'solid', 'dashed', 'dotted', 'double', 'none' ] )->default( 'solid' ),
			'item_border_color' => String_Prop_Type::make()->default( '' ),

			// Dropdown
			'dropdown_trigger'          => String_Prop_Type::make()->enum( [ 'hover', 'click' ] )->default( 'hover' ),
			'dropdown_bg'               => String_Prop_Type::make()->default( '' ),
			'dropdown_text_color'       => String_Prop_Type::make()->default( '' ),
			'dropdown_item_bg'          => String_Prop_Type::make()->default( '' ),
			'dropdown_hover_bg'         => String_Prop_Type::make()->default( '' ),
			'dropdown_hover_text_color' => String_Prop_Type::make()->default( '' ),
			'dropdown_panel_padding'    => Number_Prop_Type::make()->default( 6 ),
			'dropdown_padding_x'        => Number_Prop_Type::make()->default( 14 ),
			'dropdown_padding_y'        => Number_Prop_Type::make()->default( 9 ),
			// 2 matches the gap the panel was hardcoded to, so nothing shifts.
			'dropdown_item_gap'         => Number_Prop_Type::make()->default( 2 ),
			// 4 is what the items already rendered at — the old rule derived them
			// from the top-level Item Radius as calc(6px - 2px). Defaulting to
			// anything else would silently reshape every existing menu.
			'dropdown_item_radius'      => Number_Prop_Type::make()->default( 4 ),
			'dropdown_min_width'        => Number_Prop_Type::make()->default( 220 ),
			'dropdown_radius'           => Number_Prop_Type::make()->default( 8 ),
			// Width 1 + the rgba fallback below reproduce the hardcoded
			// `border: var(--border)` the panel used to carry, so an untouched
			// dropdown is unchanged — but the border can now be recoloured or
			// removed, which it could not be before.
			'dropdown_border_width'     => Number_Prop_Type::make()->default( 1 ),
			'dropdown_border_style'     => String_Prop_Type::make()->enum( [ 'solid', 'dashed', 'dotted', 'double', 'none' ] )->default( 'solid' ),
			'dropdown_border_color'     => String_Prop_Type::make()->default( '' ),

			// Sub-menu toggle (the +/− or chevron button menu.js injects on any item
			// that has children). Icons are optional — leave them empty to keep the
			// built-in glyph.
			// Given real defaults so the panel shows a chevron thumbnail instead of
			// an empty Svg control, which renders as a broken image and reads as
			// "this field is broken". Matches how Nav, Accordion, Offcanvas and
			// ImageHotspot already seed their icon props.
			//
			// A default does NOT switch the widget to custom-icon rendering — the
			// Twig compares against these paths, so the built-in glyphs (CSS
			// chevron on flyouts, +/− in Vertical) are untouched until the builder
			// actually picks a different file. See the `tg_is_custom` note there.
			'toggle_icon'       => Svg_Src_Prop_Type::make()
				->default_url( WCF_ADDONS_URL . self::TOGGLE_ICON_DEFAULT ),
			'toggle_icon_open'  => Svg_Src_Prop_Type::make()
				->default_url( WCF_ADDONS_URL . self::TOGGLE_ICON_OPEN_DEFAULT ),
			'toggle_color'      => String_Prop_Type::make()->default( '' ),
			'toggle_bg'         => String_Prop_Type::make()->default( '' ),
			'toggle_hover_bg'   => String_Prop_Type::make()->default( '' ),
			'toggle_padding'    => Number_Prop_Type::make()->default( 0 ),
			'toggle_size'       => Number_Prop_Type::make()->default( 28 ),
			'toggle_icon_size'  => Number_Prop_Type::make()->default( 10 ),
			'toggle_radius'     => Number_Prop_Type::make()->default( 50 ),

			// Hamburger button. Every default below is the value that was hardcoded
			// (or borrowed from an unrelated control) in menu.scss, so the button
			// renders identically until a field is touched:
			//   size 40, radius 6 (was --aae-r, i.e. the top-level Item Radius),
			//   border 1px rgba(0,0,0,.08), bars 18 x 2 with a 4px gap.
			'hamburger_color'         => String_Prop_Type::make()->default( '' ),
			'hamburger_bg'            => String_Prop_Type::make()->default( '' ),
			'hamburger_hover_bg'      => String_Prop_Type::make()->default( '' ),
			'hamburger_size'          => Number_Prop_Type::make()->default( 40 ),
			'hamburger_radius'        => Number_Prop_Type::make()->default( 6 ),
			'hamburger_border_width'  => Number_Prop_Type::make()->default( 1 ),
			'hamburger_border_color'  => String_Prop_Type::make()->default( '' ),
			// The three bars. Thickness + gap also drive the open-state X: the
			// rotate offset is their sum, so it stays a clean X at any size.
			'hamburger_bar_width'     => Number_Prop_Type::make()->default( 18 ),
			'hamburger_bar_thickness'  => Number_Prop_Type::make()->default( 2 ),
			'hamburger_bar_gap'       => Number_Prop_Type::make()->default( 4 ),

			// Drawer
			'drawer_width'    => Number_Prop_Type::make()->default( 320 ),
			'drawer_bg'       => String_Prop_Type::make()->default( '' ),
			'overlay_color'   => String_Prop_Type::make()->default( '' ),
			// Drawer header — the bar holding the label/logo and the close button.
			//
			// `drawer_logo` is intentionally left with NO default: Image_Transformer
			// throws "Invalid image URL" when it resolves an image with neither an
			// attachment id nor a url, so seeding it the way the toggle icons are
			// seeded would break rendering rather than show a placeholder.
			'drawer_logo'                => Image_Prop_Type::make(),
			'drawer_logo_width'          => Number_Prop_Type::make()->default( 120 ),
			// 17px ≈ the 1.05em the label was hardcoded to at a 16px base, and 600
			// is its previous weight, so the header reads the same until touched.
			'drawer_label_color'         => String_Prop_Type::make()->default( '' ),
			'drawer_label_size'          => Number_Prop_Type::make()->default( 17 ),
			'drawer_label_weight'        => String_Prop_Type::make()->default( '600' ),
			// The header's divider rule, previously a fixed `border-bottom: var(--border)`.
			'drawer_header_border_width' => Number_Prop_Type::make()->default( 1 ),
			'drawer_header_border_style' => String_Prop_Type::make()->enum( [ 'solid', 'dashed', 'dotted', 'double', 'none' ] )->default( 'solid' ),
			'drawer_header_border_color' => String_Prop_Type::make()->default( '' ),

			// The drawer panel had no border at all, so 0 keeps it that way.
			'drawer_border_width' => Number_Prop_Type::make()->default( 0 ),
			'drawer_border_style' => String_Prop_Type::make()->enum( [ 'solid', 'dashed', 'dotted', 'double', 'none' ] )->default( 'solid' ),
			'drawer_border_color' => String_Prop_Type::make()->default( '' ),

			// Motion
			'transition_ms'      => Number_Prop_Type::make()->default( 250 ),
			'drawer_animation'   => String_Prop_Type::make()->default( 'slide-left' ),
			'dropdown_animation' => String_Prop_Type::make()->default( 'slide' ),
		];
	}

	/**
	 * Shared option list for the three Border Style selects (items, dropdown
	 * panel, drawer). `none` is included even though Border Width 0 also hides a
	 * border — it lets a builder park a width/colour they are experimenting with
	 * instead of zeroing and retyping it.
	 *
	 * Text domain is spelled out literally rather than via self::TD because
	 * `wp i18n make-pot` only extracts literal domains.
	 */
	private function get_border_style_options(): array {
		return [
			[ 'value' => 'solid',  'label' => __( 'Solid',  'animation-addons-for-elementor' ) ],
			[ 'value' => 'dashed', 'label' => __( 'Dashed', 'animation-addons-for-elementor' ) ],
			[ 'value' => 'dotted', 'label' => __( 'Dotted', 'animation-addons-for-elementor' ) ],
			[ 'value' => 'double', 'label' => __( 'Double', 'animation-addons-for-elementor' ) ],
			[ 'value' => 'none',   'label' => __( 'None',   'animation-addons-for-elementor' ) ],
		];
	}

	private function get_available_menus(): array {
		$options = [ [ 'value' => '', 'label' => esc_html__( 'Select Menu', 'animation-addons-for-elementor' ) ] ];
		foreach ( (array) wp_get_nav_menus() as $menu ) {
			$options[] = [ 'value' => (string) $menu->slug, 'label' => $menu->name ];
		}
		return $options;
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'menu' )
						->set_label( __( 'Select Menu', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_available_menus() ),
				] ),

			Section::make()
				->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
				->set_id( 'layout' )
				->set_items( [
					Select_Control::bind_to( 'layout' )
						->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'horizontal', 'label' => __( 'Horizontal', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'vertical',   'label' => __( 'Vertical',   'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'align' )
						->set_label( __( 'Alignment', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Applies to the Horizontal layout. A Vertical menu always aligns to the start, so its indented sub-items line up on one edge.', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'flex-start',    'label' => __( 'Left',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'center',        'label' => __( 'Center',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'flex-end',      'label' => __( 'Right',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'space-between', 'label' => __( 'Justify', 'animation-addons-for-elementor' ) ],
						] ),
				Switch_Control::bind_to( 'hamburger' )
						->set_label( __( 'Mobile Hamburger', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'breakpoint' )
						->set_label( __( 'Mobile Breakpoint (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( '0 means never switch to the mobile drawer, at any width.', 'animation-addons-for-elementor' ) ),
					// The header label moved to its own Drawer Header section, next to
					// the logo and typography that style it.
				] ),

			Section::make()
				->set_label( __( 'Menu Items Style', 'animation-addons-for-elementor' ) )
				->set_id( 'items_style' )
				->set_items( [
					Text_Control::bind_to( 'text_color' )
						->set_label( __( 'Text Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#1f2937' ),
					Text_Control::bind_to( 'hover_color' )
						->set_label( __( 'Hover Text Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#2563eb' ),
					Text_Control::bind_to( 'item_hover_bg' )
						->set_label( __( 'Hover Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.05)' ),
					Text_Control::bind_to( 'active_color' )
						->set_label( __( 'Active Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#2563eb' ),
					Number_Control::bind_to( 'padding_x' )
						->set_label( __( 'Item Padding X (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'padding_y' )
						->set_label( __( 'Item Padding Y (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'item_gap' )
						->set_label( __( 'Item Gap (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'link_radius' )
						->set_label( __( 'Item Radius (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'item_border_width' )
						->set_label( __( 'Border Width (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Drawn around each menu item. Raising it makes the items slightly larger, since the border sits outside the padding.', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'item_border_style' )
						->set_label( __( 'Border Style', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_border_style_options() ),
					Text_Control::bind_to( 'item_border_color' )
						->set_label( __( 'Border Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.08)' ),
				] ),

			/**
			 * The dropdown is TWO sections, not one flat list.
			 *
			 * The panel (the floating box) and the items (the rows inside it) own
			 * separate copies of the same properties — two Backgrounds, two
			 * Paddings — and interleaved they read as duplicates, which is why the
			 * old list needed "Item" prefixes and inline notes to stay legible. The
			 * section heading carries that distinction now, so each label can say
			 * what the property IS without re-stating which box it lands on.
			 *
			 * Two TOP-LEVEL sections rather than one section with sub-headings,
			 * because a nested section is silently DISCARDED: renderSectionItems()
			 * in editor-editing-panel drops every non-control item with a literal
			 * `// TODO: Handle 2nd level sections`, so the fields inside would
			 * simply never render — no error, no clue.
			 */
			Section::make()
				->set_label( __( 'Dropdown Panel', 'animation-addons-for-elementor' ) )
				->set_id( 'dropdown_panel' )
				->set_items( [
					// Behaviour, so it leads — a builder decides HOW the panel opens
					// before styling it. Applies to FLYOUT dropdowns only: the mobile
					// drawer and Layout = Vertical both expand inline from the toggle,
					// so "Hover" has nothing to act on there.
					Select_Control::bind_to( 'dropdown_trigger' )
						->set_label( __( 'Open On', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Applies to flyout dropdowns. With Layout set to Vertical, sub-menus always expand from the +/− toggle — and from the item itself when this is set to Click.', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'hover', 'label' => __( 'Hover', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'click', 'label' => __( 'Click', 'animation-addons-for-elementor' ) ],
						] ),
					Text_Control::bind_to( 'dropdown_bg' )
						->set_label( __( 'Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#ffffff' ),
					Number_Control::bind_to( 'dropdown_panel_padding' )
						->set_label( __( 'Padding (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Inset between the panel edge and the items inside it.', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'dropdown_min_width' )
						->set_label( __( 'Min Width (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'dropdown_radius' )
						->set_label( __( 'Border Radius (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'dropdown_border_width' )
						->set_label( __( 'Border Width (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'The panel ships with a 1px hairline. Set to 0 to remove it — with the drop shadow gone, that leaves the panel with no edge at all, so give it a background first.', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'dropdown_border_style' )
						->set_label( __( 'Border Style', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_border_style_options() ),
					Text_Control::bind_to( 'dropdown_border_color' )
						->set_label( __( 'Border Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.08)' ),
				] ),

			Section::make()
				->set_label( __( 'Dropdown Items', 'animation-addons-for-elementor' ) )
				->set_id( 'dropdown_items' )
				->set_items( [
					// Resting pair first, then the matching hover pair, so the two
					// read as before/after of the same two properties rather than
					// four unrelated colour fields.
					Text_Control::bind_to( 'dropdown_item_bg' )
						->set_label( __( 'Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'transparent' ),
					Text_Control::bind_to( 'dropdown_text_color' )
						->set_label( __( 'Text Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#1a1a18' ),
					// Placeholder says "None", not a colour: there is no longer a
					// default hover wash to hint at. Showing rgba(15,23,42,0.05) here
					// would promise a highlight that never appears until this is set.
					Text_Control::bind_to( 'dropdown_hover_bg' )
						->set_label( __( 'Hover Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'None', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'dropdown_hover_text_color' )
						->set_label( __( 'Hover Text Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#2563eb' ),
					Number_Control::bind_to( 'dropdown_padding_x' )
						->set_label( __( 'Padding X (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'dropdown_padding_y' )
						->set_label( __( 'Padding Y (px)', 'animation-addons-for-elementor' ) ),
					// padding → gap → radius, the same order the top-level Menu Items
					// Style section uses, so the two sections read the same way.
					Number_Control::bind_to( 'dropdown_item_gap' )
						->set_label( __( 'Gap (px)', 'animation-addons-for-elementor' ) ),
					// Independent of the panel's own Border Radius, and of the
					// top-level Item Radius the items used to be derived from.
					Number_Control::bind_to( 'dropdown_item_radius' )
						->set_label( __( 'Border Radius (px)', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Sub-menu Toggle', 'animation-addons-for-elementor' ) )
				->set_id( 'toggle_style' )
				->set_items( [
					// Optional. Empty keeps the built-in glyph: +/− in Vertical, a
					// chevron for flyouts and the drawer. An uploaded icon replaces it
					// in every mode and is painted with the Icon Color below (it is
					// applied as a CSS mask, not an <img>, so it stays recolourable).
					Svg_Control::bind_to( 'toggle_icon' )
						->set_label( __( 'Icon (Collapsed)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Showing the built-in chevron. Vertical layout draws a +/− instead until you upload your own icon, which then applies to every layout.', 'animation-addons-for-elementor' ) ),
					Svg_Control::bind_to( 'toggle_icon_open' )
						->set_label( __( 'Icon (Expanded)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Optional. Falls back to the collapsed icon when you upload one here.', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'toggle_color' )
						->set_label( __( 'Icon Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Inherits menu text color', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'toggle_bg' )
						->set_label( __( 'Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'transparent' ),
					Text_Control::bind_to( 'toggle_hover_bg' )
						->set_label( __( 'Hover Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.05)' ),
					Number_Control::bind_to( 'toggle_size' )
						->set_label( __( 'Button Size (px)', 'animation-addons-for-elementor' ) ),
					// Insets the glyph inside the button. Button Size still governs the
					// outer box, so this trades icon area for breathing room rather
					// than growing the button.
					Number_Control::bind_to( 'toggle_padding' )
						->set_label( __( 'Padding (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'toggle_icon_size' )
						->set_label( __( 'Icon Size (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'toggle_radius' )
						->set_label( __( 'Border Radius (px)', 'animation-addons-for-elementor' ) ),
				] ),

			// Split out of the old combined "Hamburger & Drawer" section. The button
			// and the panel it opens are separate objects with separate colours and
			// sizes, and the hamburger now has ten fields of its own — enough that
			// sharing one heading with the drawer stopped being readable.
			Section::make()
				->set_label( __( 'Hamburger', 'animation-addons-for-elementor' ) )
				->set_id( 'hamburger_style' )
				->set_items( [
					Text_Control::bind_to( 'hamburger_color' )
						->set_label( __( 'Icon Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Inherits menu text color', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'hamburger_bg' )
						->set_label( __( 'Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'transparent' ),
					Text_Control::bind_to( 'hamburger_hover_bg' )
						->set_label( __( 'Hover Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.05)' ),
					Number_Control::bind_to( 'hamburger_size' )
						->set_label( __( 'Button Size (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'hamburger_radius' )
						->set_label( __( 'Border Radius (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'hamburger_border_width' )
						->set_label( __( 'Border Width (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Set to 0 for no border.', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'hamburger_border_color' )
						->set_label( __( 'Border Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.08)' ),
					Number_Control::bind_to( 'hamburger_bar_width' )
						->set_label( __( 'Bar Width (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'hamburger_bar_thickness' )
						->set_label( __( 'Bar Thickness (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'hamburger_bar_gap' )
						->set_label( __( 'Bar Gap (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Also sets how far the top and bottom bars travel when they cross into the close (X) state.', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Drawer Header', 'animation-addons-for-elementor' ) )
				->set_id( 'drawer_header_style' )
				->set_items( [
					// A logo REPLACES the text label when uploaded — the header is a
					// single-slot bar next to the close button, and rendering both
					// crowds it. The label is still used as the logo's alt text, so
					// filling in both is useful rather than redundant.
					Image_Control::bind_to( 'drawer_logo' )
						->set_label( __( 'Logo', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Replaces the label below when set. The label is still used as the logo’s alt text.', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'drawer_logo_width' )
						->set_label( __( 'Logo Width (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Height follows the image’s own aspect ratio.', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'mobile_label' )
						->set_label( __( 'Label', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Menu', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'drawer_label_color' )
						->set_label( __( 'Label Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Inherits menu text color', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'drawer_label_size' )
						->set_label( __( 'Label Font Size (px)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'drawer_label_weight' )
						->set_label( __( 'Label Font Weight', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '400', 'label' => __( 'Normal',    'animation-addons-for-elementor' ) ],
							[ 'value' => '500', 'label' => __( 'Medium',    'animation-addons-for-elementor' ) ],
							[ 'value' => '600', 'label' => __( 'Semi Bold', 'animation-addons-for-elementor' ) ],
							[ 'value' => '700', 'label' => __( 'Bold',      'animation-addons-for-elementor' ) ],
						] ),
					Number_Control::bind_to( 'drawer_header_border_width' )
						->set_label( __( 'Border Width (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'The divider under the header. Set to 0 to remove it.', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'drawer_header_border_style' )
						->set_label( __( 'Border Style', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_border_style_options() ),
					Text_Control::bind_to( 'drawer_header_border_color' )
						->set_label( __( 'Border Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.08)' ),
				] ),

			Section::make()
				->set_label( __( 'Drawer', 'animation-addons-for-elementor' ) )
				->set_id( 'drawer_style' )
				->set_items( [
					Number_Control::bind_to( 'drawer_width' )
						->set_label( __( 'Width (px)', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'drawer_bg' )
						->set_label( __( 'Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#ffffff' ),
					Text_Control::bind_to( 'overlay_color' )
						->set_label( __( 'Overlay Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.5)' ),
					Number_Control::bind_to( 'drawer_border_width' )
						->set_label( __( 'Border Width (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Drawn around the whole drawer panel. Only the edge facing the page is normally visible — the other three sit against the viewport.', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'drawer_border_style' )
						->set_label( __( 'Border Style', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_border_style_options() ),
					Text_Control::bind_to( 'drawer_border_color' )
						->set_label( __( 'Border Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.08)' ),
				] ),

			Section::make()
				->set_label( __( 'Motion', 'animation-addons-for-elementor' ) )
				->set_id( 'motion' )
				->set_items( [
					Number_Control::bind_to( 'transition_ms' )
						->set_label( __( 'Transition Duration (ms)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'drawer_animation' )
						->set_label( __( 'Mobile Drawer Effect', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'slide-left',   'label' => __( 'Slide from Left',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide-right',  'label' => __( 'Slide from Right',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide-top',    'label' => __( 'Slide from Top',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide-bottom', 'label' => __( 'Slide from Bottom', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'fade',         'label' => __( 'Fade',              'animation-addons-for-elementor' ) ],
							[ 'value' => 'scale',        'label' => __( 'Scale',             'animation-addons-for-elementor' ) ],
							[ 'value' => 'zoom-in',      'label' => __( 'Zoom In',           'animation-addons-for-elementor' ) ],
							[ 'value' => 'flip',         'label' => __( 'Flip',              'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'dropdown_animation' )
						->set_label( __( 'Sub-menu Dropdown Effect', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'slide',      'label' => __( 'Slide Down',     'animation-addons-for-elementor' ) ],
							[ 'value' => 'fade',       'label' => __( 'Fade',           'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide-fade', 'label' => __( 'Slide + Fade',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'scale',      'label' => __( 'Scale (Origin)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'zoom',       'label' => __( 'Zoom',           'animation-addons-for-elementor' ) ],
							[ 'value' => 'flip',       'label' => __( 'Flip',           'animation-addons-for-elementor' ) ],
						] ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		$wrapper = [
			'display'  => String_Prop_Type::generate( 'block' ),
			'position' => String_Prop_Type::generate( 'relative' ),
			// Hugs the menu instead of stretching the container. `fit-content`
			// resolves to min(max-content, available), so a long menu still
			// cannot overflow its container and needs no max-width guard.
			'width'    => String_Prop_Type::generate( 'fit-content' ),
		];

		return [
			'base' => Style_Definition::make()->add_variant( Style_Variant::make()->add_props( $wrapper ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-menu' => __DIR__ . '/aae-a-menu.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-menu-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-menu-css' ];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		if ( ! empty( $settings['menu'] ) ) {
			$settings['rendered_menu'] = wp_nav_menu( [
				'menu'        => $settings['menu'],
				'menu_class'  => 'aae-a-menu-list',
				'container'   => false,
				'echo'        => false,
				'fallback_cb' => false,
			] );
		} else {
			$settings['rendered_menu'] = '<div class="aae-a-menu-placeholder">' . esc_html__( 'Please select a menu', 'animation-addons-for-elementor' ) . '</div>';
		}

		return $settings;
	}
}
