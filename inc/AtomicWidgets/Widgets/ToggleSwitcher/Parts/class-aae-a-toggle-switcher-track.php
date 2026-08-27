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
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Toggle Switcher — Track. The pill-shaped knob track in the
 * Switch-style Toggle Switcher preset, sitting between the before/after
 * Labels. Wraps a single knob element (normally an e-divider styled round).
 *
 * A genuine container (Atomic_Element_Base) for the same reason as
 * AAE_A_Toggle_Switcher_Label: it needs to render the `aae-ts-switch` hook
 * class unconditionally from its own twig, never through the `classes` prop
 * — see "Never put a functional hook class in the classes prop" in
 * CLAUDE.md. Before this widget existed, the preset built this wrapper out
 * of a plain e-div-block with the hook class stuffed into `classes`, which
 * is exactly the failure that section warns about.
 */
class AAE_A_Toggle_Switcher_Track extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-toggle-switcher-track';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher-track';
	}

	public function get_title() {
		return esc_html__( 'Toggle Switcher — Track', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-toggle';
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'track', 'knob', 'atomic' ];
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
			 * Structural identity, not runtime state — mirrors Label/Tab's own
			 * `is_after` prop. Drives the `aae-ts-switch--pill` hook class
			 * straight from this widget's own twig, deliberately never through
			 * the `classes` prop (which the panel audits against the style
			 * registry and flags/strips as "missing" — see "Never put a
			 * functional hook class in the classes prop" in CLAUDE.md). Lets
			 * the Pill-style preset scope its shared CSS to its own Track
			 * without touching the classic Switch preset's Track.
			 */
			'is_pill'    => Boolean_Prop_Type::make()->default( false ),
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
						'width'          => Size_Prop_Type::generate( [ 'size' => 40, 'unit' => 'px' ] ),
						'height'         => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
						'min-width'      => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'min-height'     => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'position'       => String_Prop_Type::generate( 'relative' ),
						'cursor'         => String_Prop_Type::generate( 'pointer' ),
						'padding'        => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'border-radius'  => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'border-width'   => Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ),
						'border-color'   => Color_Prop_Type::generate( '#999999' ),
						'border-style'   => String_Prop_Type::generate( 'solid' ),
						'background'     => Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#999999' ) ] ),
						'display'        => String_Prop_Type::generate( 'inline-block' ),
					] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher-track' => __DIR__ . '/aae-a-toggle-switcher-track.html.twig',
		];
	}
}
