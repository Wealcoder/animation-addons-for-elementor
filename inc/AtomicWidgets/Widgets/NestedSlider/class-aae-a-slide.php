<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Heading\Atomic_Heading;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

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
		require_once __DIR__ . '/class-aae-a-slide-preset-picker-control.php';

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items(
					[
						AAE_A_Slide_Preset_Picker_Control::make()
							->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
							->set_meta( [ 'layout' => 'custom' ] ),
					]
				),

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

	/**
	 * A slide with no children renders as Elementor's empty-container drop
	 * placeholder - a dashed box with a "+" - which is most of why a freshly
	 * dropped slider looked broken. Seeding a heading and a line of copy gives
	 * the slide real content to show, and the user replaces the text rather
	 * than having to build the slide from nothing.
	 */
	protected function define_default_children() {
		return [
			Atomic_Heading::generate()
				->editor_settings( [ 'title' => 'Slide Title' ] )
				->settings( [
					'tag'   => String_Prop_Type::generate( 'h3' ),
					'title' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Slide title' ),
						'children' => [],
					] ),
				] )
				->build(),
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Slide Text' ] )
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate(
							'Replace this with your own content. Drag any element in here to build the slide.'
						),
						'children' => [],
					] ),
				] )
				->build(),
		];
	}

	/**
	 * Minimal default look: a centred card with a light face, so the slide has
	 * presence on the canvas the moment it is dropped. Neutral on purpose -
	 * it reads on a light page and on a dark one.
	 */
	protected function define_base_styles(): array {
		$styles = [
			'display'         => String_Prop_Type::generate( 'flex' ),
			'flex-direction'  => String_Prop_Type::generate( 'column' ),
			'justify-content' => String_Prop_Type::generate( 'center' ),
			'align-items'     => String_Prop_Type::generate( 'flex-start' ),
			'gap'             => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
			'min-height'      => Size_Prop_Type::generate( [ 'size' => 280, 'unit' => 'px' ] ),
			// A string here is dropped - `padding` wants dimensions or size.
			'padding'         => Dimensions_Prop_Type::generate( [
				'block-start'  => Size_Prop_Type::generate( [ 'size' => 40, 'unit' => 'px' ] ),
				'inline-end'   => Size_Prop_Type::generate( [ 'size' => 40, 'unit' => 'px' ] ),
				'block-end'    => Size_Prop_Type::generate( [ 'size' => 40, 'unit' => 'px' ] ),
				'inline-start' => Size_Prop_Type::generate( [ 'size' => 40, 'unit' => 'px' ] ),
			] ),
			'background'      => Background_Prop_Type::generate( [
				'color' => Color_Prop_Type::generate( '#f4f4f6' ),
			] ),
			'border-radius'   => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
		];

		return [
			// Literal 'base', not self::BASE_STYLE_KEY - this class does not
			// declare that constant (nor does the progress/counter sibling).
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $styles ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-slide' => __DIR__ . '/aae-a-slide.html.twig',
		];
	}
}
