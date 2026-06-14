<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Menu;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Menu extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-menu';
	}

	public function get_title() {
		return esc_html__( 'AAE Menu', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function get_keywords() {
		return [ 'menu', 'nav', 'navigation', 'atomic', 'gsap' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'menu' => String_Prop_Type::make()->default( '' ),
			'layout' => String_Prop_Type::make()->default( 'horizontal' ),
			'align' => String_Prop_Type::make()->default( 'center' ),
			'hamburger' => Boolean_Prop_Type::make()->default( true ),
		];
	}

	private function get_available_menus() {
		$menus = wp_get_nav_menus();
		$options = [
			[
				'value' => '',
				'label' => esc_html__( 'Select Menu', 'animation-addons-for-elementor' ),
			]
		];

		if ( ! empty( $menus ) ) {
			foreach ( $menus as $menu ) {
				$options[] = [
					'value' => (string) $menu->slug,
					'label' => $menu->name,
				];
			}
		}

		return $options;
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Menu Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'menu' )
						->set_label( __( 'Select Menu', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_available_menus() ),

					Select_Control::bind_to( 'layout' )
						->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'horizontal', 'label' => __( 'Horizontal', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'vertical', 'label' => __( 'Vertical', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'align' )
						->set_label( __( 'Alignment', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'flex-start', 'label' => __( 'Left', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'center', 'label' => __( 'Center', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'flex-end', 'label' => __( 'Right', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'space-between', 'label' => __( 'Justify', 'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'hamburger' )
						->set_label( __( 'Mobile Hamburger', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display' => String_Prop_Type::generate( 'block' ),
			'position' => String_Prop_Type::generate( 'relative' ),
			'width' => String_Prop_Type::generate( '100%' ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-menu' => __DIR__ . '/aae-a-menu.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-menu-js' ]; // GSAP core should already be loaded via standard plugin enqueues if needed, but we'll enqueue GSAP explicitly here if not.
	}

	public function get_style_depends(): array {
		return [ 'aae-a-menu-css' ];
	}

	// This method makes wp_nav_menu output available to the Twig template natively
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		// Render the WP menu
		if ( ! empty( $settings['menu'] ) ) {
			$args = [
				'menu' => $settings['menu'],
				'menu_class' => 'aae-a-menu-list',
				'container' => false,
				'echo' => false,
				'fallback_cb' => false,
			];
			$settings['rendered_menu'] = wp_nav_menu( $args );
		} else {
			$settings['rendered_menu'] = '<div class="aae-a-menu-placeholder">' . esc_html__( 'Please select a menu', 'animation-addons-for-elementor' ) . '</div>';
		}

		return $settings;
	}
}
