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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-toggle-pane.php';
require_once __DIR__ . '/Parts/class-aae-a-toggle-switcher-tabs.php';

use WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Pane;
use WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Switcher_Tabs;

/**
 * AAE Toggle Switcher — an open dual-panel content toggle: a Tabs row (two
 * tab buttons) and two unlocked panes to fill freely. Nothing is locked and
 * there is no `ts_style` enum — pick a look (Switch / Label Highlight) and
 * its ready-made knob/highlight pieces by importing one of the ready-made
 * templates, then restyle those pieces from their own Style panel, exactly
 * like the AAE Btn wrapper pattern. Pair with AAE_A_Toggle_Switcher_Main
 * (ToggleSwitcherMain) for the locked, style-enum-driven version.
 *
 * The default Tabs/Tab/Pane-Title/Pane-Desc are each a dedicated small
 * widget type carrying real typography via their own define_base_styles() —
 * see class-aae-a-toggle-switcher-tab.php for why plain
 * e-paragraph/e-heading/Div_Block reuse can't express that (base styles are
 * owned by the widget TYPE, not a per-instance override). Tab's "active tab"
 * look is a native Style-panel state (Style_States::SELECTED, `.e--selected`
 * — the same class toggle-switcher.js toggles), deliberately with NO default
 * color baked into Tab's own define_base_styles() (a `.e--selected` rule
 * there would tie in specificity with a per-instance `:active` override and
 * the winner would depend on stylesheet load order — see
 * class-aae-a-toggle-switcher-tab.php) — entirely the builder's choice from
 * the panel. Tab is a genuine leaf widget (not a container-family element),
 * specifically so it never gets Elementor's empty-container "+" add overlay
 * in the editor canvas; the state is threaded in via a get_initial_config()
 * override since
 * leaf widgets don't call define_atomic_style_states() on their own.
 */
class AAE_A_Toggle_Switcher extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A dual-tab content switcher — Monthly/Yearly-style tabs above two editable panes, styled out of the box as underlined text tabs. Pair with the ready-made Switch / Label Highlight templates for a different look.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-toggle-switcher';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher';
	}

	public function get_title() {
		return esc_html__( 'Toggle Switcher', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'tabs', 'atomic', 'switcher', 'open', 'container' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
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
	 * Stacks the Tabs row above the panes; max-width/margin/padding center it
	 * as a standalone block on the page (matches Timeline/Progressbar's
	 * outer-wrapper treatment).
	 */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',        String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
						->add_prop( 'width',          Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
						->add_prop( 'max-width',      Size_Prop_Type::generate( [ 'size' => 560, 'unit' => 'px' ] ) )
						->add_prop( 'margin', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 48, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => null, 'unit' => 'auto' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 48, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => null, 'unit' => 'auto' ] ),
						] ) )
						->add_prop( 'padding', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
						] ) )
				),
		];
	}

	/**
	 * Out-of-the-box look for a freshly dropped instance: a Tabs row (built by
	 * AAE_A_Toggle_Switcher_Tabs's own default children — "Monthly" active,
	 * "Yearly" not) plus two panes, each with its own title/description.
	 */
	protected function define_default_children() {
		return [
			AAE_A_Toggle_Switcher_Tabs::generate()
				->editor_settings( [ 'title' => 'Tabs' ] )
				->children(
					AAE_A_Toggle_Switcher_Tabs::build_default_inner_children()
				)
				->build(),

			AAE_A_Toggle_Pane::generate()
				->editor_settings( [ 'title' => 'Pane — Monthly' ] )
				->children(
					AAE_A_Toggle_Pane::build_default_inner_children( 'Monthly plan', 'Add your content here.' )
				)
				->build(),

			AAE_A_Toggle_Pane::generate()
				->editor_settings( [ 'title' => 'Pane — Yearly' ] )
				->children(
					AAE_A_Toggle_Pane::build_default_inner_children( 'Yearly plan', 'Add your content here.' )
				)
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [
			'e-aae-a-toggle-switcher-tabs',
			'e-aae-a-toggle-pane',
			'widget',
			'e-heading',
			'e-paragraph',
			'e-svg',
			'e-image',
			'e-divider',
			'e-flexbox',
			'e-div-block',
		];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher' => __DIR__ . '/aae-a-toggle-switcher.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-toggle-switcher-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-toggle-switcher-css' ];
	}
}
