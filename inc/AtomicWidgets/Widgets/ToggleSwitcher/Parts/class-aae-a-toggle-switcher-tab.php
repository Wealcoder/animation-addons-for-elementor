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
use Elementor\Modules\AtomicWidgets\Controls\Types\Inline_Editing_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Border_Width_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transition_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Selection_Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Toggle Switcher — Tab. A single "Monthly"/"Yearly"-style tab button.
 * A genuine leaf (Atomic_Widget_Base, like e-paragraph/e-heading) — an
 * earlier version extended Atomic_Element_Base to get a native "Selected"
 * Style-panel state for the active-tab look, but ANY Atomic_Element_Base
 * (container-family) instance gets Elementor's empty-container "+" add
 * overlay in the editor canvas regardless of define_allowed_child_types()
 * being empty, which read as a stray add-icon on every tab. A real button
 * widget never gets that overlay.
 *
 * The "+" overlay turned out to be driven by the `is_container` meta flag
 * (set in the container-family constructor), not by exposing style states —
 * so the "Selected" state can be offered WITHOUT reverting to
 * Atomic_Element_Base. Atomic_Widget_Base's own get_initial_config() never
 * calls define_atomic_style_states() (only Atomic_Element_Base does, see
 * atomic-tab.php upstream), so it's added here via a get_initial_config()
 * override instead. That's what makes "Selected" a real option in the
 * Style panel's state dropdown — exactly like Elementor's own e-tab widget.
 * toggle-switcher.js already toggles the same `e--selected` class this state
 * compiles to, so no runtime change was needed — only the panel/PHP side was
 * missing the wiring.
 *
 * A default SELECTED-state look (dark pill background, white text, rounded
 * corners) ships out of the box — every fresh tab needs a visible "this is
 * the active one" indicator, not just via a builder's manual per-instance
 * styling — but it deliberately does NOT live in define_base_styles() below
 * (an earlier revision put it there). Elementor's atomic-widget BASE style
 * and a per-instance LOCAL style variant for the SAME state compile to
 * IDENTICAL specificity (`.<class>.e--selected`), so the winner is whichever
 * loads later — and the EDITOR's live style injection (unlike the frontend's
 * generated CSS file, which orders local after base correctly) was found to
 * inject base AFTER local for this exact combination, so a builder who picks
 * "Selected" in the Style panel and sets their own color sees it silently
 * overridden back to this default — ONLY in the editor, never on the
 * frontend, which is exactly what a base/local ordering bug looks like
 * rather than a build error. Moving the default into toggle-switcher.scss as
 * a plain `.aae-ts-tab.e--selected` hook-class rule sidesteps Elementor's own
 * base-style compiler entirely: that stylesheet loads once, early, via a
 * normal `<link>`, so any later per-instance local `e--selected` override
 * (editor or frontend) reliably wins on load order — matching how Track/Knob
 * already dropped their own competing base-level "on" look for the same
 * reason (see class-aae-a-toggle-switcher-track.php).
 */
class AAE_A_Toggle_Switcher_Tab extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Internal tab button used by the AAE Toggle Switcher Tabs row.';

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher-tab';
	}

	public function get_title() {
		return esc_html__( 'Toggle Switcher Tab', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'tab', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	/**
	 * Exposes "Selected" (Style_States::SELECTED, class `.e--selected`) as a
	 * real option in this widget's Style-panel state dropdown — the same
	 * class toggle-switcher.js already toggles on the active tab. Not called
	 * automatically: Atomic_Widget_Base (leaf widgets) only wires up
	 * define_atomic_pseudo_states() on its own; get_initial_config() below is
	 * what actually threads this into the config Elementor's editor reads.
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
			'text' => Html_V3_Prop_Type::make()
				->default( [
					'content'  => String_Prop_Type::generate( 'Tab' ),
					'children' => [],
				] )
				->description( 'The tab label text.' ),
			/**
			 * Structural identity, not runtime state: which of the two tabs this
			 * is (Monthly/"before" vs Yearly/"after"). Drives the
			 * aae-ts-label-before/after hook class and the default
			 * active/e--selected look, both rendered straight from this widget's
			 * own twig — deliberately NOT via the `classes` prop, which Elementor's
			 * panel audits against the style registry and flags as "missing" (see
			 * "Never put a functional hook class in the classes prop" in
			 * CLAUDE.md). toggle-switcher.js still toggles `active`/`e--selected`
			 * on click; this prop only decides the tab's fixed identity and its
			 * out-of-the-box default state.
			 */
			'is_after' => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items( [
					Inline_Editing_Control::bind_to( 'text' )
						->set_label( __( 'Label', 'animation-addons-for-elementor' ) ),
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
						'background'    => Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( 'transparent' ) ] ),
						'border-width'  => Border_Width_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ),
						'border-style' => String_Prop_Type::generate( 'solid' ),
						'border-color' => Color_Prop_Type::generate( 'transparent' ),
						'padding' => Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
						] ),
						'margin' => Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => -1, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ),
						'font-size' => Size_Prop_Type::generate( [ 'size' => 15, 'unit' => 'px' ] ),
						'color'     => Color_Prop_Type::generate( '#5f5e5a' ),
						'cursor'    => String_Prop_Type::generate( 'pointer' ),
							'transition' => Transition_Prop_Type::generate( [
							Selection_Size_Prop_Type::generate( [
								'selection' => Key_Value_Prop_Type::generate( [
									'key'   => String_Prop_Type::generate( 'All properties' ),
									'value' => String_Prop_Type::generate( 'all' ),
								] ),
								'size' => Size_Prop_Type::generate( [
									'size' => 200,
									'unit' => 'ms',
								] ),
							] ),
						] ),
					] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher-tab' => __DIR__ . '/aae-a-toggle-switcher-tab.html.twig',
		];
	}
}
