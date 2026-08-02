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
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Menu extends Atomic_Widget_Base {
	use Has_Template;

	const TD = 'animation-addons-for-elementor';

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
			'font_size'     => Number_Prop_Type::make()->default( 15 ),
			'font_weight'   => String_Prop_Type::make()->default( '500' ),
			'padding_x'     => Number_Prop_Type::make()->default( 14 ),
			'padding_y'     => Number_Prop_Type::make()->default( 10 ),
			'item_gap'      => Number_Prop_Type::make()->default( 4 ),
			'link_radius'   => Number_Prop_Type::make()->default( 6 ),

			// Dropdown
			'dropdown_bg'               => String_Prop_Type::make()->default( '' ),
			'dropdown_hover_bg'         => String_Prop_Type::make()->default( '' ),
			'dropdown_hover_text_color' => String_Prop_Type::make()->default( '' ),
			'dropdown_min_width'        => Number_Prop_Type::make()->default( 220 ),
			'dropdown_radius'           => Number_Prop_Type::make()->default( 8 ),

			// Hamburger / Drawer
			'hamburger_color' => String_Prop_Type::make()->default( '' ),
			'drawer_width'    => Number_Prop_Type::make()->default( 320 ),
			'drawer_bg'       => String_Prop_Type::make()->default( '' ),
			'overlay_color'   => String_Prop_Type::make()->default( '' ),

			// Motion
			'transition_ms'      => Number_Prop_Type::make()->default( 250 ),
			'drawer_animation'   => String_Prop_Type::make()->default( 'slide-left' ),
			'dropdown_animation' => String_Prop_Type::make()->default( 'slide' ),
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
						->set_options( [
							[ 'value' => 'flex-start',    'label' => __( 'Left',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'center',        'label' => __( 'Center',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'flex-end',      'label' => __( 'Right',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'space-between', 'label' => __( 'Justify', 'animation-addons-for-elementor' ) ],
						] ),
				Switch_Control::bind_to( 'hamburger' )
						->set_label( __( 'Mobile Hamburger', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'breakpoint' )
						->set_label( __( 'Mobile Breakpoint (px)', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'mobile_label' )
						->set_label( __( 'Mobile Header Label', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Menu', 'animation-addons-for-elementor' ) ),
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
					Number_Control::bind_to( 'font_size' )
						->set_label( __( 'Font Size (px)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'font_weight' )
						->set_label( __( 'Font Weight', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '400', 'label' => __( 'Normal',    'animation-addons-for-elementor' ) ],
							[ 'value' => '500', 'label' => __( 'Medium',    'animation-addons-for-elementor' ) ],
							[ 'value' => '600', 'label' => __( 'Semi Bold', 'animation-addons-for-elementor' ) ],
							[ 'value' => '700', 'label' => __( 'Bold',      'animation-addons-for-elementor' ) ],
						] ),
					Number_Control::bind_to( 'padding_x' )
						->set_label( __( 'Item Padding X (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'padding_y' )
						->set_label( __( 'Item Padding Y (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'item_gap' )
						->set_label( __( 'Item Gap (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'link_radius' )
						->set_label( __( 'Item Radius (px)', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Dropdown Style', 'animation-addons-for-elementor' ) )
				->set_id( 'dropdown_style' )
				->set_items( [
					Text_Control::bind_to( 'dropdown_bg' )
						->set_label( __( 'Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#ffffff' ),
					Text_Control::bind_to( 'dropdown_hover_bg' )
						->set_label( __( 'Item Hover Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(15,23,42,0.05)' ),
					Text_Control::bind_to( 'dropdown_hover_text_color' )
						->set_label( __( 'Item Hover Text Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#2563eb' ),
					Number_Control::bind_to( 'dropdown_min_width' )
						->set_label( __( 'Min Width (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'dropdown_radius' )
						->set_label( __( 'Border Radius (px)', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Hamburger & Drawer', 'animation-addons-for-elementor' ) )
				->set_id( 'drawer_style' )
				->set_items( [
					Text_Control::bind_to( 'hamburger_color' )
						->set_label( __( 'Hamburger Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#1f2937' ),
					Number_Control::bind_to( 'drawer_width' )
						->set_label( __( 'Drawer Width (px)', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'drawer_bg' )
						->set_label( __( 'Drawer Background', 'animation-addons-for-elementor' ) )
						->set_placeholder( '#ffffff' ),
					Text_Control::bind_to( 'overlay_color' )
						->set_label( __( 'Overlay Color', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'rgba(0,0,0,0.5)' ),
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
			'width'    => String_Prop_Type::generate( '100%' ),
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
