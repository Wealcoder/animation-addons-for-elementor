<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Slider_Progress extends Atomic_Element_Base {
	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'Shows a progress bar that fills as slides advance.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public function should_show_in_panel() {
		return false;
	}

	public static function get_type() {
		return 'e-aae-a-slider-progress';
	}	

	public static function get_element_type(): string {
		return 'e-aae-a-slider-progress';
	}

	public function get_title() {
		return esc_html__( 'Slider Progress', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-full-screen';
	}

	public function get_keywords() {
		return [ 'slider', 'progress', 'bar', 'atomic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
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
		$track_styles = [
			'position'      => String_Prop_Type::generate( 'relative' ),
			'width'         => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
			'height'        => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
			'background'    => Background_Prop_Type::generate( [
				'color' => Color_Prop_Type::generate( 'rgba(255,255,255,0.15)' ),
			] ),
			'border-radius' => Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ),
			'overflow'      => String_Prop_Type::generate( 'hidden' ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $track_styles ) ),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Slider_Progress_Fill::generate()
				->editor_settings( [ 'title' => 'Progress Fill' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'e-aae-a-slider-progress-fill' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slider-progress' => __DIR__ . '/aae-a-slider-progress.html.twig',
		];
	}
}
