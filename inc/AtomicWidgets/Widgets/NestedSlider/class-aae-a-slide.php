<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Slide extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-slide';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slide';
	}

	public function get_title() {
		return esc_html__( 'Slide', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-document-file';
	}

	public function get_keywords() {
		return [ 'slide', 'nested', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false; // Should only be inserted via Slider container, not dragged manually from panel
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
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

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slide' => __DIR__ . '/aae-a-slide.html.twig',
		];
	}
}
