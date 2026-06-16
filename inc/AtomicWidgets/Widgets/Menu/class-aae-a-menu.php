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
		return esc_html__( 'AAE Menu', self::TD );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function get_keywords() {
		return [ 'menu', 'nav', 'navigation', 'atomic', 'gsap' ];
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
			'text_color'   => String_Prop_Type::make()->default( '' ),
			'hover_color'  => String_Prop_Type::make()->default( '' ),
			'active_color' => String_Prop_Type::make()->default( '' ),
			'font_size'    => Number_Prop_Type::make()->default( 15 ),
			'font_weight'  => String_Prop_Type::make()->default( '500' ),
			'padding_x'    => Number_Prop_Type::make()->default( 14 ),
			'padding_y'    => Number_Prop_Type::make()->default( 10 ),
			'item_gap'     => Number_Prop_Type::make()->default( 4 ),
			'link_radius'  => Number_Prop_Type::make()->default( 6 ),

			// Dropdown
			'dropdown_bg'        => String_Prop_Type::make()->default( '' ),
			'dropdown_hover_bg'  => String_Prop_Type::make()->default( '' ),
			'dropdown_min_width' => Number_Prop_Type::make()->default( 220 ),
			'dropdown_radius'    => Number_Prop_Type::make()->default( 8 ),

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
		$options = [ [ 'value' => '', 'label' => esc_html__( 'Select Menu', self::TD ) ] ];
		foreach ( (array) wp_get_nav_menus() as $menu ) {
			$options[] = [ 'value' => (string) $menu->slug, 'label' => $menu->name ];
		}
		return $options;
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', self::TD ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'menu' )
						->set_label( __( 'Select Menu', self::TD ) )
						->set_options( $this->get_available_menus() ),
				] ),

			Section::make()
				->set_label( __( 'Layout', self::TD ) )
				->set_id( 'layout' )
				->set_items( [
					Select_Control::bind_to( 'layout' )
						->set_label( __( 'Layout', self::TD ) )
						->set_options( [
							[ 'value' => 'horizontal', 'label' => __( 'Horizontal', self::TD ) ],
							[ 'value' => 'vertical',   'label' => __( 'Vertical',   self::TD ) ],
						] ),
					Select_Control::bind_to( 'align' )
						->set_label( __( 'Alignment', self::TD ) )
						->set_options( [
							[ 'value' => 'flex-start',    'label' => __( 'Left',    self::TD ) ],
							[ 'value' => 'center',        'label' => __( 'Center',  self::TD ) ],
							[ 'value' => 'flex-end',      'label' => __( 'Right',   self::TD ) ],
							[ 'value' => 'space-between', 'label' => __( 'Justify', self::TD ) ],
						] ),
				Switch_Control::bind_to( 'hamburger' )
						->set_label( __( 'Mobile Hamburger', self::TD ) ),
					Number_Control::bind_to( 'breakpoint' )
						->set_label( __( 'Mobile Breakpoint (px)', self::TD ) ),
					Text_Control::bind_to( 'mobile_label' )
						->set_label( __( 'Mobile Header Label', self::TD ) )
						->set_placeholder( __( 'Menu', self::TD ) ),
				] ),

			Section::make()
				->set_label( __( 'Menu Items Style', self::TD ) )
				->set_id( 'items_style' )
				->set_items( [
					Text_Control::bind_to( 'text_color' )
						->set_label( __( 'Text Color', self::TD ) )
						->set_placeholder( '#1f2937' ),
					Text_Control::bind_to( 'hover_color' )
						->set_label( __( 'Hover Color', self::TD ) )
						->set_placeholder( '#2563eb' ),
					Text_Control::bind_to( 'active_color' )
						->set_label( __( 'Active Color', self::TD ) )
						->set_placeholder( '#2563eb' ),
					Number_Control::bind_to( 'font_size' )
						->set_label( __( 'Font Size (px)', self::TD ) ),
					Select_Control::bind_to( 'font_weight' )
						->set_label( __( 'Font Weight', self::TD ) )
						->set_options( [
							[ 'value' => '400', 'label' => __( 'Normal',    self::TD ) ],
							[ 'value' => '500', 'label' => __( 'Medium',    self::TD ) ],
							[ 'value' => '600', 'label' => __( 'Semi Bold', self::TD ) ],
							[ 'value' => '700', 'label' => __( 'Bold',      self::TD ) ],
						] ),
					Number_Control::bind_to( 'padding_x' )
						->set_label( __( 'Item Padding X (px)', self::TD ) ),
					Number_Control::bind_to( 'padding_y' )
						->set_label( __( 'Item Padding Y (px)', self::TD ) ),
					Number_Control::bind_to( 'item_gap' )
						->set_label( __( 'Item Gap (px)', self::TD ) ),
					Number_Control::bind_to( 'link_radius' )
						->set_label( __( 'Item Radius (px)', self::TD ) ),
				] ),

			Section::make()
				->set_label( __( 'Dropdown Style', self::TD ) )
				->set_id( 'dropdown_style' )
				->set_items( [
					Text_Control::bind_to( 'dropdown_bg' )
						->set_label( __( 'Background', self::TD ) )
						->set_placeholder( '#ffffff' ),
					Text_Control::bind_to( 'dropdown_hover_bg' )
						->set_label( __( 'Item Hover Background', self::TD ) )
						->set_placeholder( 'rgba(15,23,42,0.05)' ),
					Number_Control::bind_to( 'dropdown_min_width' )
						->set_label( __( 'Min Width (px)', self::TD ) ),
					Number_Control::bind_to( 'dropdown_radius' )
						->set_label( __( 'Border Radius (px)', self::TD ) ),
				] ),

			Section::make()
				->set_label( __( 'Hamburger & Drawer', self::TD ) )
				->set_id( 'drawer_style' )
				->set_items( [
					Text_Control::bind_to( 'hamburger_color' )
						->set_label( __( 'Hamburger Color', self::TD ) )
						->set_placeholder( '#1f2937' ),
					Number_Control::bind_to( 'drawer_width' )
						->set_label( __( 'Drawer Width (px)', self::TD ) ),
					Text_Control::bind_to( 'drawer_bg' )
						->set_label( __( 'Drawer Background', self::TD ) )
						->set_placeholder( '#ffffff' ),
					Text_Control::bind_to( 'overlay_color' )
						->set_label( __( 'Overlay Color', self::TD ) )
						->set_placeholder( 'rgba(0,0,0,0.5)' ),
				] ),

			Section::make()
				->set_label( __( 'Motion', self::TD ) )
				->set_id( 'motion' )
				->set_items( [
					Number_Control::bind_to( 'transition_ms' )
						->set_label( __( 'Transition Duration (ms)', self::TD ) ),
					Select_Control::bind_to( 'drawer_animation' )
						->set_label( __( 'Mobile Drawer Effect', self::TD ) )
						->set_options( [
							[ 'value' => 'slide-left',   'label' => __( 'Slide from Left',   self::TD ) ],
							[ 'value' => 'slide-right',  'label' => __( 'Slide from Right',  self::TD ) ],
							[ 'value' => 'slide-top',    'label' => __( 'Slide from Top',    self::TD ) ],
							[ 'value' => 'slide-bottom', 'label' => __( 'Slide from Bottom', self::TD ) ],
							[ 'value' => 'fade',         'label' => __( 'Fade',              self::TD ) ],
							[ 'value' => 'scale',        'label' => __( 'Scale',             self::TD ) ],
							[ 'value' => 'zoom-in',      'label' => __( 'Zoom In',           self::TD ) ],
							[ 'value' => 'flip',         'label' => __( 'Flip',              self::TD ) ],
						] ),
					Select_Control::bind_to( 'dropdown_animation' )
						->set_label( __( 'Sub-menu Dropdown Effect', self::TD ) )
						->set_options( [
							[ 'value' => 'slide',      'label' => __( 'Slide Down',     self::TD ) ],
							[ 'value' => 'fade',       'label' => __( 'Fade',           self::TD ) ],
							[ 'value' => 'slide-fade', 'label' => __( 'Slide + Fade',   self::TD ) ],
							[ 'value' => 'scale',      'label' => __( 'Scale (Origin)', self::TD ) ],
							[ 'value' => 'zoom',       'label' => __( 'Zoom',           self::TD ) ],
							[ 'value' => 'flip',       'label' => __( 'Flip',           self::TD ) ],
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
			$settings['rendered_menu'] = '<div class="aae-a-menu-placeholder">' . esc_html__( 'Please select a menu', self::TD ) . '</div>';
		}

		return $settings;
	}
}
