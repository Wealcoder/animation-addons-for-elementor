<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Advanced_Heading extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	/** Class added to a child to mark it as a highlight part. */
	const HIGHLIGHT_CLASS = 'aae-ah-highlight';

	public static $widget_description = 'Advanced heading with editable text and highlight parts. Highlight treatment: gradient, bracket, divider+dot, or animated underline.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-advanced-heading';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-advanced-heading';
	}

	public function get_title() {
		return esc_html__( 'AAE Advanced Heading', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'heading', 'title', 'highlight', 'gradient', 'advanced' ];
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// HTML tag for the heading wrapper.
			'ah_tag'   => String_Prop_Type::make()->default( 'h2' ),

			// Highlight treatment applied to .aae-ah-highlight children.
			'ah_style' => String_Prop_Type::make()->default( 'gradient' ),

			// Alignment of the heading line.
			'ah_align' => String_Prop_Type::make()->default( 'left' ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items( [
					AAE_A_Preset_Picker_Control::make()
						->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_label( __( 'Heading', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'ah_tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'h1', 'label' => 'H1' ],
							[ 'value' => 'h2', 'label' => 'H2' ],
							[ 'value' => 'h3', 'label' => 'H3' ],
							[ 'value' => 'h4', 'label' => 'H4' ],
							[ 'value' => 'h5', 'label' => 'H5' ],
							[ 'value' => 'h6', 'label' => 'H6' ],
							[ 'value' => 'div', 'label' => __( 'div', 'animation-addons-for-elementor' ) ],
						] ),

					// NOTE: 'ah_style' (Highlight Style) is intentionally NOT exposed
					// as a panel control. The prop still exists in the schema and
					// drives the .aae-ah-style-<value> class in the Twig template,
					// but the value is meant to be baked into presets by the plugin
					// author (set it in the exported preset JSON), not changed by
					// end users. To temporarily re-expose it for authoring, restore
					// a Select_Control::bind_to( 'ah_style' ) here.

					Select_Control::bind_to( 'ah_align' )
						->set_label( __( 'Alignment', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'left',   'label' => __( 'Left',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'center', 'label' => __( 'Center', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'right',  'label' => __( 'Right',  'animation-addons-for-elementor' ) ],
						] ),
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
						->add_prop( 'display',     String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-wrap',   String_Prop_Type::generate( 'wrap' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',         Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
						->add_prop( 'font-size',   String_Prop_Type::generate( '40px' ) )
						->add_prop( 'font-weight', String_Prop_Type::generate( '700' ) )
						->add_prop( 'line-height', String_Prop_Type::generate( '1.2' ) )
				),
		];
	}

	protected function define_default_children() {
		return [
			$this->make_part( 'Build your', false, 'Text' ),
			$this->make_part( 'Innovate', true, 'Highlight' ),
			$this->make_part( 'Our Core Solution', false, 'Text' ),
		];
	}

	/**
	 * Build a single editable text part as an atomic Paragraph <span>.
	 *
	 * @param string $text        Initial text content.
	 * @param bool   $is_highlight Whether this part gets the highlight class.
	 * @param string $title       Editor navigator title.
	 */
	private function make_part( string $text, bool $is_highlight, string $title ) {
		$classes = $is_highlight ? [ self::HIGHLIGHT_CLASS ] : [ 'aae-ah-text' ];

		return Atomic_Paragraph::generate()
			->editor_settings( [ 'title' => $title ] )
			->settings( [
				'classes'   => Classes_Prop_Type::generate( $classes ),
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( $text ),
					'children' => [],
				] ),
				'tag'       => String_Prop_Type::generate( 'span' ),
			] )
			->build();
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-paragraph', 'e-heading', 'e-svg' ];
	}

	protected function define_default_html_tag() {
		return 'h2';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-advanced-heading' => __DIR__ . '/aae-a-advanced-heading.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-advanced-heading-css' ];
	}
}
