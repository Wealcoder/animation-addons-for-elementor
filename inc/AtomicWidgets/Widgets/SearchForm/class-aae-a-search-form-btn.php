<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Search_Form_Btn extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-search-form-btn';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-search-form-btn';
	}

	public function get_title(): string {
		return esc_html__( 'Search Form Button', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-button';
	}

	public function should_show_in_panel(): bool {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	// flex: 0 0 auto prevents .e-con from stretching the button to full width.
	// Users override background, padding, border-radius, etc. via the Style panel.
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'display'      => String_Prop_Type::generate( 'inline-flex' ),
					'align-items'  => String_Prop_Type::generate( 'center' ),
					'justify-content' => String_Prop_Type::generate( 'center' ),
					'cursor'       => String_Prop_Type::generate( 'pointer' ),
					'flex-grow'    => String_Prop_Type::generate( '0' ),
					'flex-shrink'  => String_Prop_Type::generate( '0' ),
					'width'        => String_Prop_Type::generate( 'auto' ),
					'align-self'   => String_Prop_Type::generate( 'stretch' ),
				] ) ),
		];
	}

	// Icon first (left), label second (right) — flex order controls visual position.
	protected function define_default_children(): array {
		return [
			Atomic_Svg::generate()->build(),
			Atomic_Paragraph::generate()
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Search' ),
						'children' => [],
					] ),
					'tag' => String_Prop_Type::generate( 'span' ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading', 'e-image' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-form-btn' => __DIR__ . '/aae-a-search-form-btn.html.twig',
		];
	}
}
