<?php
/**
 * AAE Loop Slide Track — the `.aae-slider-track` inside the Loop Grid Slider.
 *
 * Structural container (mirrors NestedSlider's AAE_A_Slider_Track). Holds exactly
 * one Loop Slide Item, which repeats per queried post at render time. The shared
 * nested-slider runtime finds this element by its `.aae-slider-track` class and
 * drives the transform / autoplay / effect on its `.aae-a-slide` children.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Slide_Track extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
		$this->meta( 'permanently_locked', true );
	}

	public static function generate() {
		return parent::generate()->is_locked( true );
	}

	public static function get_type() {
		return 'e-aae-a-loop-slide-track';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-slide-track';
	}

	public function get_title() {
		return esc_html__( 'Slider Track', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-loop-slide-item' ];
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
				->set_items( [] ),
		];
	}

	/**
	 * The track is a pure layout rail. It carries the `e-con` class, so it would
	 * otherwise inherit Elementor's default 10px container padding — and that
	 * padding shrinks the track's CONTENT box, which is the reference the slide
	 * width (`100% / slidesPerView`) is measured against. The runtime, meanwhile,
	 * left-aligns slides to the SLIDER root's content box. When the two boxes
	 * disagree (because the track has padding the slider doesn't), the row of
	 * slides no longer fits the space it's aligned within, so the first slide gets
	 * clipped and a sliver of the next one shows. Zeroing the track's padding keeps
	 * the width-reference box and the positioning box in agreement regardless of
	 * any padding the user sets on the slider or the slides.
	 */
	protected function define_base_styles(): array {
		$zero = Dimensions_Prop_Type::generate( [
			'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
		] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_prop( 'padding', $zero )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-slide-track' => __DIR__ . '/aae-a-loop-slide-track.html.twig',
		];
	}
}
