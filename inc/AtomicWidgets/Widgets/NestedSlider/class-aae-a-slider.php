<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Indicators;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;

use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slide;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slides_Control;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Track;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Prev;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Next;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Pagination;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Current;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Total;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Percentage;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Progress;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Progress_Fill;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Divider;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Counter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Slider extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-slider';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-slider';
	}

	public function get_title() {
		return esc_html__( 'AAE Nested Slider', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_keywords() {
		return [ 'slider', 'nested', 'atomic', 'gsap' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

    protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-slides-control.php';
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items(
					[
						AAE_A_Preset_Picker_Control::make()
							->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
							->set_meta( [ 'layout' => 'custom' ] ),
					]
				),

			Section::make()
				->set_label( __( 'Slides', 'animation-addons-for-elementor' ) )
				->set_id( 'slides' )
				->set_items( [
					AAE_A_Slides_Control::make()
						->set_label( __( 'Slides', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),
			// "Slider Settings": the anchor control is replaced in the editor by the
			// ResponsiveSection (General/Advanced tabs); the ID field lives in the
			// same section right after it, so there's one combined section instead
			// of a separate "Settings". Built here (not via Controls.php's filter)
			// because the ID control needs the protected get_css_id_control_meta().
			Section::make()
				->set_label( __( 'Slider Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'slider_settings' )
				->set_items( [
					Text_Control::bind_to( \WCF_ADDONS\Atomic\NestedSlider\Schema::SLIDER_SECTION_ANCHOR ),
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		// We apply base flex styles. Notice we don't bind dynamic gap here, we do it in twig variables.
		$wrapper_styles = [
			'display' => String_Prop_Type::generate( 'block' ),
			'overflow' => String_Prop_Type::generate( 'hidden' ),
			'position' => String_Prop_Type::generate( 'relative' ),
			'width' => String_Prop_Type::generate( '100%' ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) )	
		];
	}

	protected function define_default_children() {
		// Start with 5 empty slides; the user fills each one.
		$slides = [];
		for ( $i = 1; $i <= 1; $i++ ) {
			$slides[] = AAE_A_Slide::generate()
				->editor_settings( [ 'title' => 'Slide ' . $i ] )
				->build();
		}

		return [
			AAE_A_Slider_Track::generate()
				->editor_settings( [ 'title' => 'Slider Track' ] )
				->children( $slides )
				->build(),
			AAE_A_Slider_Nav_Prev::generate()
				->editor_settings( [ 'title' => 'Prev Nav' ] )
				->build(),
			AAE_A_Slider_Nav_Next::generate()
				->editor_settings( [ 'title' => 'Next Nav' ] )
				->build(),
			AAE_A_Slider_Pagination::generate()
				->editor_settings( [ 'title' => 'Pagination' ] )
				->build(),
			AAE_A_Slider_Indicators::generate()
				->editor_settings( [ 'title' => 'Indicators' ] )
				->children( [
					AAE_A_Slider_Counter::generate()
						->editor_settings( [ 'title' => 'Slide Counter' ] )
						->build(),
					AAE_A_Slider_Progress::generate()
						->editor_settings( [ 'title' => 'Progress Line' ] )
						->build(),
					AAE_A_Slider_Percentage::generate()
						->editor_settings( [ 'title' => 'Progress %' ] )
						->build(),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [
			'e-aae-a-slider-track',
			'e-aae-a-slider-nav-prev',
			'e-aae-a-slider-nav-next',
			'e-aae-a-slider-pagination',
			'e-aae-a-slider-current',
			'e-aae-a-slider-total',
			'e-aae-a-slider-progress',
			'e-aae-a-slider-percentage',
			'e-aae-a-slider-indicators',
			'e-aae-a-slider-counter',
			'e-aae-a-slider-divider',
			'e-aae-a-slider-progress-fill',
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slider' => __DIR__ . '/aae-a-slider.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-slider-css' ];
	}	
}
