<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Counter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Counter extends Atomic_Widget_Base {
	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'Display an animated counter using GSAP.';

	public static function get_element_type(): string {
		return 'e-aae-a-counter';
	}

	public function get_title() {
		return esc_html__( 'Atomic Counter', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'counter', 'number', 'animation', 'gsap' ];
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()
				->default( [] ),
			
			'startNumber' => Number_Prop_Type::make()
				->default( 0 )
				->description( 'Starting number' ),

			'endNumber' => Number_Prop_Type::make()
				->default( 100 )
				->description( 'Ending number' ),

			'prefix' => String_Prop_Type::make()
				->default( '' )
				->description( 'Prefix before the number' ),

			'suffix' => String_Prop_Type::make()
				->default( '+' )
				->description( 'Suffix after the number' ),

			'duration' => Number_Prop_Type::make()
				->default( 2000 )
				->description( 'Animation duration in milliseconds' ),

			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Number_Control::bind_to( 'startNumber' )
						->set_label( __( 'Starting Number', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'endNumber' )
						->set_label( __( 'Ending Number', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'prefix' )
						->set_label( __( 'Prefix', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'suffix' )
						->set_label( __( 'Suffix', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'duration' )
						->set_label( __( 'Animation Duration (ms)', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 100, 'max' => 10000, 'step' => 100 ] ),
				] ),
			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', 'inline-flex' )
						->add_prop( 'align-items', 'center' )
						->add_prop( 'justify-content', 'center' )
						->add_prop( 'gap', '5px' )
						->add_prop( 'font-size', '2rem' )
						->add_prop( 'font-weight', 'bold' )
						->add_prop( 'font-family', 'inherit' )
						->add_prop( 'line-height', '1' )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-counter' => __DIR__ . '/aae-a-counter.html.twig',
		];
	}

	public function render_markdown(): string {
		$settings = $this->get_atomic_settings();
		$endNumber = $settings['endNumber'] ?? 100;
		$prefix = $settings['prefix'] ?? '';
		$suffix = $settings['suffix'] ?? '';

		return $prefix . $endNumber . $suffix;
	}
}
