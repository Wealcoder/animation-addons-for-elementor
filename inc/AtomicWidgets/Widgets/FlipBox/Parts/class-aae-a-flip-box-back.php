<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\FlipBox;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-flip-box-title.php';
require_once __DIR__ . '/class-aae-a-flip-box-text.php';

/**
 * AAE Flip Box — Back face. Sibling of AAE_A_Flip_Box_Front — see that file
 * for why the flip mechanics (position/backface-visibility/hover-rotate)
 * stay in flip-box.scss while only background/color/radius/padding are
 * defined here.
 */
class AAE_A_Flip_Box_Back extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-flip-box-back';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-flip-box-back';
	}

	public function get_title(): string {
		return esc_html__( 'Flip Box Back', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-inner-section';
	}

	public function get_keywords(): array {
		return [ 'flip', 'box', 'back', 'face', 'atomic' ];
	}

	public function should_show_in_panel(): bool {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			// Empty on purpose — the `flip-box-back` hook class is emitted by
			// the twig. See AAE_A_Flip_Box_Front::define_props_schema().
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'text-align'    => String_Prop_Type::generate( 'center' ),
						'border-radius' => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'padding'       => Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
						] ),
						'background' => Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#1a1a1a' ) ] ),
						'color'      => Color_Prop_Type::generate( '#ffffff' ),
					] )
				),
		];
	}

	/**
	 * Exposed publicly so the parent Flip Box's define_default_children()
	 * can seed a fresh Back face's title/text directly (mirrors
	 * AAE_A_Timeline_Item::build_default_inner_children()).
	 */
	public static function build_default_inner_children(
		string $title = 'Back Title',
		string $text = 'This is back side content.'
	): array {
		return [
			AAE_A_Flip_Box_Title::generate()
				->editor_settings( [ 'title' => 'Title' ] )
				->settings( [
					'text' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $title ),
						'children' => [],
					] ),
				] )
				->build(),

			AAE_A_Flip_Box_Text::generate()
				->editor_settings( [ 'title' => 'Text' ] )
				->settings( [
					'text' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $text ),
						'children' => [],
					] ),
				] )
				->build(),
		];
	}

	protected function define_default_children(): array {
		return self::build_default_inner_children();
	}

	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-aae-a-flip-box-title', 'e-aae-a-flip-box-text', 'e-heading', 'e-paragraph', 'e-svg', 'e-image' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-flip-box-back' => __DIR__ . '/aae-a-flip-box-back.html.twig',
		];
	}
}
