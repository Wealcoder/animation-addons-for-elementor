<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transition_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Selection_Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Transform_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Transform_Functions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Functions\Transform_Move_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Toggle Switcher — Knob. The round dot that slides inside the Track in
 * the Switch-style Toggle Switcher preset. A genuine leaf widget (like
 * AAE_A_Toggle_Switcher_Tab), never a container — it has no children of its
 * own.
 *
 * Renders `aae-ts-knob` unconditionally from its own twig, deliberately
 * never through the `classes` prop — see "Never put a functional hook class
 * in the classes prop" in CLAUDE.md. Before this widget existed, the preset
 * built the knob out of a plain e-divider with the hook class stuffed into
 * `classes`, which is exactly the failure that section warns about (the
 * knob has no runtime behaviour of its own — toggle-switcher.js only reads
 * `.aae-ts-switch.active .aae-ts-knob` in CSS — but it carried the same
 * "Some classes are missing" exposure as the Label/Track wrappers).
 */
class AAE_A_Toggle_Switcher_Knob extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Internal knob dot used by the Switch-style Toggle Switcher preset.';

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher-knob';
	}

	public function get_title() {
		return esc_html__( 'Toggle Switcher — Knob', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'knob', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-dot-circle-o';
	}

	public function show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	/**
	 * Exposes "Selected" (Style_States::SELECTED, class `.e--selected`) as a
	 * real option in this widget's Style-panel state dropdown — the same
	 * class toggle-switcher.js now toggles on the Knob alongside Track (see
	 * applyTsState() in toggle-switcher.js), so the knob's own look can also
	 * change when the switch is on, fully editable per-instance.
	 *
	 * A genuine leaf widget (Atomic_Widget_Base), same situation as
	 * AAE_A_Toggle_Switcher_Tab: Atomic_Widget_Base's own get_initial_config()
	 * never calls define_atomic_style_states() on its own (only
	 * Atomic_Element_Base does — see Track's identical override), so it must
	 * be threaded in manually via the get_initial_config() override below.
	 */
	protected function define_atomic_style_states(): array {
		return [ Style_States::get_class_states_map()['selected'] ];
	}

	public function get_initial_config() {
		$config = parent::get_initial_config();
		$config['atomic_style_states'] = $this->define_atomic_style_states();

		return $config;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			/**
			 * Structural identity, not runtime state — mirrors Label/Tab's own
			 * `is_after` prop and Track's own `is_pill`. Drives the
			 * `aae-ts-knob--pill` hook class straight from this widget's own
			 * twig, deliberately never through the `classes` prop (which the
			 * panel audits against the style registry and flags/strips as
			 * "missing" — see "Never put a functional hook class in the
			 * classes prop" in CLAUDE.md). Lets the Pill-style preset scope its
			 * shared sliding-transform CSS to its own Knob without touching
			 * the classic Switch preset's Knob (fixed 20px dot travel).
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
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'width'              => Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
						'height'             => Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
						'min-width'          => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'position'           => String_Prop_Type::generate( 'absolute' ),
						'inset-block-start'  => Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ),
						'inset-inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'padding'            => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'border-radius'      => Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ),
						'background'         => Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#ffffff' ) ] ),
						'transform'          => Transform_Prop_Type::generate( [
							'transform-functions' => Transform_Functions_Prop_Type::generate( [
								Transform_Move_Prop_Type::generate( [
									'x' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
									'y' => Size_Prop_Type::generate( [ 'size' => -50, 'unit' => '%' ] ),
									'z' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
								] ),
							] ),
						] ),
						'transition'         => Transition_Prop_Type::generate( [
							Selection_Size_Prop_Type::generate( [
								'selection' => Key_Value_Prop_Type::generate( [
									'key'   => String_Prop_Type::generate( 'All properties' ),
									'value' => String_Prop_Type::generate( 'all' ),
								] ),
								'size'      => Size_Prop_Type::generate( [ 'size' => 400, 'unit' => 'ms' ] ),
							] ),
						] ),
						'display'            => String_Prop_Type::generate( 'inline-block' ),
					] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher-knob' => __DIR__ . '/aae-a-toggle-switcher-knob.html.twig',
		];
	}
}
