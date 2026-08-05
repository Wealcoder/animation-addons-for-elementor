<?php
/**
 * AAE Stack Card — atomic (v4) container element, a single card in the deck.
 *
 * A REAL selectable/styleable child of AAE_A_Stack_Cards (not markup baked into
 * the parent) so every card is stylable on its own Style tab and holds any
 * content the user drops in. Its base style is the neutral "card" look; the
 * frontend JS positions/animates it — the card itself knows nothing about the
 * animation.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\StackCards;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

class AAE_A_Stack_Card extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-stack-card';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-stack-card';
	}

	public function get_title() {
		return esc_html__( 'Stack Card', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-single-post';
	}

	public function get_keywords() {
		return [ 'stack', 'card', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false; // seeded by the Stack Cards deck, not dragged manually
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
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	/**
	 * The neutral card look — only props verified safe in the installed schema
	 * (one bad key/value voids the WHOLE definition). The border is deliberately
	 * left to the deck's scoped inline CSS / presets so an unverified generate()
	 * shape can't silently void the whole card style; there is no box-shadow
	 * default at all any more (it painted a grey halo on light pages — users add
	 * their own from the Style tab). The frontend JS handles positioning/stacking,
	 * so no `position` lives here (on the canvas the card is a normal flow block).
	 */
	protected function define_base_styles(): array {
		$pad = Size_Prop_Type::generate( [ 'size' => 48, 'unit' => 'px' ] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'         => String_Prop_Type::generate( 'flex' ),
						'flex-direction'  => String_Prop_Type::generate( 'column' ),
						'justify-content' => String_Prop_Type::generate( 'flex-end' ),
						'width'           => String_Prop_Type::generate( '100%' ),
						'min-height'      => Size_Prop_Type::generate( [ 'size' => 300, 'unit' => 'px' ] ),
						'border-radius'   => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
						'color'           => Color_Prop_Type::generate( '#f0eef5' ),
						'background'      => Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( '#15152a' ),
						] ),
						'padding'         => Dimensions_Prop_Type::generate( [
							'block-start'  => $pad,
							'block-end'    => $pad,
							'inline-start' => $pad,
							'inline-end'   => $pad,
						] ),
					] )
				),
		];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-stack-card' => __DIR__ . '/aae-a-stack-card.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
