<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\FlipBox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Inline_Editing_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Flip Box — Title. A single bold face heading. No color of its own —
 * it inherits `color` from whichever face (Front/Back) contains it, exactly
 * like the reference markup's .flipbox__title, which sets no color and
 * relies on cascading from .flipbox__front/__back. Its own widget type
 * exists only so it can carry fixed typography via define_base_styles() —
 * base styles are owned by the widget TYPE, not a per-instance override, so
 * a reused e-heading would mean styling every heading site-wide.
 */
class AAE_A_Flip_Box_Title extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Internal face title used by the AAE Flip Box front/back faces.';

	public static function get_element_type(): string {
		return 'e-aae-a-flip-box-title';
	}

	public function get_title() {
		return esc_html__( 'Flip Box Title', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'flip', 'box', 'title', 'heading', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-t-letter-bold';
	}

	public function should_show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'text' => Html_V3_Prop_Type::make()
				->default( [
					'content'  => String_Prop_Type::generate( 'Front Title' ),
					'children' => [],
				] )
				->description( 'The face title text.' ),
			'tag' => String_Prop_Type::make()->default( 'h3' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items( [
					Inline_Editing_Control::bind_to( 'text' )
						->set_label( __( 'Title', 'animation-addons-for-elementor' ) ),
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
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'     => String_Prop_Type::generate( 'block' ),
						'font-size'   => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
						'font-weight' => String_Prop_Type::generate( '600' ),
						'margin'      => Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ),
					] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-flip-box-title' => __DIR__ . '/aae-a-flip-box-title.html.twig',
		];
	}
}
