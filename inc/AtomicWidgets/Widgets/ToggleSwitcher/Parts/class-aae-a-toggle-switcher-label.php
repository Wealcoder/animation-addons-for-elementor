<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Toggle Switcher — Label. The clickable "Monthly"/"Yearly" wrapper that
 * sits either side of the Track in the Switch-style Toggle Switcher preset.
 *
 * A genuine container (Atomic_Element_Base, not a leaf widget) because it
 * wraps arbitrary content (normally an e-paragraph) rather than owning fixed
 * text of its own — unlike AAE_A_Toggle_Switcher_Tab, which is a self-
 * contained button.
 *
 * `is_after` is structural identity, not runtime state — mirrors Tab's own
 * prop of the same name and the same reasoning: it drives the
 * aae-ts-label-before/after hook class straight from THIS widget's own twig,
 * deliberately never through the `classes` prop, which Elementor's panel
 * audits against the style registry and flags as "missing" (see "Never put a
 * functional hook class in the classes prop" in CLAUDE.md). Before this
 * widget existed, the Switch-style preset built these wrappers out of plain
 * e-div-block with the hook class stuffed into `classes` — exactly the
 * failure that section warns about, and the reason this type exists.
 * toggle-switcher.js still toggles `active`/`e--selected` on click; this
 * prop only decides which side of the Track the label renders on.
 */
class AAE_A_Toggle_Switcher_Label extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-toggle-switcher-label';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher-label';
	}

	public function get_title() {
		return esc_html__( 'Toggle Switcher — Label', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'label', 'atomic' ];
	}

	public function should_show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			/**
			 * Structural identity, not runtime state: which side of the Track
			 * this label sits on (Monthly/"before" vs Yearly/"after"). See the
			 * class docblock.
			 */
			'is_after'   => Boolean_Prop_Type::make()->default( false ),
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
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'width'  => Size_Prop_Type::generate( [ 'size' => '', 'unit' => 'auto' ] ),
						'cursor' => String_Prop_Type::generate( 'pointer' ),
					] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher-label' => __DIR__ . '/aae-a-toggle-switcher-label.html.twig',
		];
	}
}
