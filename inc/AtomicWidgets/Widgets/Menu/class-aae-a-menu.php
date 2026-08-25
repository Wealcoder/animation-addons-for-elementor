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

// Responsive "Menu Items Style" overrides. Not PSR-4 (this whole folder is
// loaded by class-atomic.php's registry, not the autoloader), so it has to be
// required explicitly. Registering here rather than in class-atomic.php keeps
// the widget self-contained; register() is idempotent.
require_once __DIR__ . '/class-aae-a-menu-responsive.php';
AAE_A_Menu_Responsive::register();

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
		// The responsive `aae_m*_` props are MERGED IN, never a replacement:
		// every legacy prop below keeps its key, its type and its stored value,
		// so an existing menu renders exactly as it did. See
		// class-aae-a-menu-responsive.php for why retyping them in place would
		// break both saving and rendering.
		return AAE_A_Menu_Responsive::props_schema() + [
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
			// Active items have always rendered at 600 — the weight was hardcoded in
			// menu.scss with no way to reach it from the panel. Same value as the
			// default here, so no existing menu shifts. Unlike font_size/font_weight
			// this one belongs to the widget rather than the Style tab: Typography
			// there paints the whole list and cannot single out .current-menu-item.
			'active_weight' => String_Prop_Type::make()->enum( AAE_A_Menu_Responsive::FONT_WEIGHTS )->default( '600' ),
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
					// Alignment only — it is the one field in this section backed by a
					// CSS variable. Layout, Mobile Hamburger and Mobile Breakpoint are
					// attribute/JS-driven and stay single-value; the reasons are in
					// class-aae-a-menu-responsive.php's docblock.
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'layout' ) ),
				Switch_Control::bind_to( 'hamburger' )
						->set_label( __( 'Mobile Hamburger', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'breakpoint' )
						->set_label( __( 'Mobile Breakpoint (px)', 'animation-addons-for-elementor' ) )
						->set_description( __( '0 means never switch to the mobile drawer, at any width.', 'animation-addons-for-elementor' ) ),
					// The header label moved to its own Drawer Header section, next to
					// the logo and typography that style it.
				] ),

			/**
			 * Menu Items Style — rendered by the AAE responsive section.
			 *
			 * The single anchor control below is replaced in the editor by
			 * <ResponsiveSection>, which draws the same eleven rows with a
			 * per-breakpoint dot on each. Their labels, order and placeholders are
			 * declared in src/modules/atomic/extensions/menu-sections/fields.js,
		 * which also drives every other responsive section on this widget.
			 *
			 * The ELEVEN LEGACY PROPS THIS SECTION USED TO BIND (text_color,
			 * padding_x, …) are deliberately still in define_props_schema():
			 * dropping them would make Props_Parser strip every stored value on
			 * the next save. They keep feeding the Twig's inline CSS variables and
			 * act as the desktop baseline; a responsive value overrides them from
			 * the per-element <style> block. Each row shows its legacy value as
			 * the display default, so an existing menu opens looking unchanged.
			 */
			Section::make()
				->set_label( __( 'Menu Items Style', 'animation-addons-for-elementor' ) )
				->set_id( 'items_style' )
				->set_items( [
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'items' ) ),
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
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'dropdown_panel' ) ),
				] ),

			Section::make()
				->set_label( __( 'Dropdown Items', 'animation-addons-for-elementor' ) )
				->set_id( 'dropdown_items' )
				->set_items( [
					// Row order — resting pair, then the matching hover pair, then
					// padding -> gap -> radius, mirroring Menu Items Style — is
					// declared in menu-sections/fields.js now.
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'dropdown_items' ) ),
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
					// The two Icon pickers above stay single-value (media, not
					// style); everything below them is a CSS variable.
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'toggle' ) ),
				] ),

			// Split out of the old combined "Hamburger & Drawer" section. The button
			// and the panel it opens are separate objects with separate colours and
			// sizes, and the hamburger now has ten fields of its own — enough that
			// sharing one heading with the drawer stopped being readable.
			Section::make()
				->set_label( __( 'Hamburger', 'animation-addons-for-elementor' ) )
				->set_id( 'hamburger_style' )
				->set_items( [
					// Every field here is a CSS variable, so the whole section is
					// per-breakpoint — which matters more here than anywhere else on
					// the widget: the hamburger only exists below the mobile
					// breakpoint in the first place.
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'hamburger' ) ),
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
					Text_Control::bind_to( 'mobile_label' )
						->set_label( __( 'Label', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Menu', 'animation-addons-for-elementor' ) ),
					// The two CONTENT fields stay above, single-value: an image and a
					// string have no per-breakpoint meaning here. Everything that
					// sizes or colours them follows, per breakpoint — Logo Width
					// included, which is the field most likely to need one.
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'drawer_header' ) ),
				] ),

			Section::make()
				->set_label( __( 'Drawer', 'animation-addons-for-elementor' ) )
				->set_id( 'drawer_style' )
				->set_items( [
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'drawer' ) ),
				] ),

			Section::make()
				->set_label( __( 'Motion', 'animation-addons-for-elementor' ) )
				->set_id( 'motion' )
				->set_items( [
					// Duration only. The two Effect selects below are data-attributes
					// menu.js branches on, so they cannot vary by media query.
					Text_Control::bind_to( AAE_A_Menu_Responsive::anchor( 'motion' ) ),
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
