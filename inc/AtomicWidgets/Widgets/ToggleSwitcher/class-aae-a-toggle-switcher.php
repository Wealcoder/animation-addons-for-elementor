<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Div_Block\Div_Block;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-toggle-pane.php';

use WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Pane;

/**
 * AAE Toggle Switcher — an open dual-panel content toggle: two unlocked
 * before/after labels, an empty switch placeholder, and two unlocked panes
 * to fill freely. Nothing is locked and there is no `ts_style` enum — pick
 * a look (Switch / Label Highlight) and its ready-made knob/highlight
 * pieces by importing one of the ready-made templates, then restyle those
 * pieces from their own Style panel, exactly like the AAE Btn wrapper
 * pattern. Pair with AAE_A_Toggle_Switcher_Main (ToggleSwitcherMain) for the
 * locked, style-enum-driven version.
 */
class AAE_A_Toggle_Switcher extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'An open dual-panel content toggle you build yourself: two unlocked before/after labels, a switch marker, and two unlocked panes. Pair with the ready-made Switch / Label Highlight templates.';

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
		return esc_html__( 'AAE Toggle Switcher', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'tabs', 'atomic', 'switcher', 'open', 'container' ];
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
	 * MINIMAL wrapper base styles only (Btn pattern). Header row vs. stacked
	 * panes comes from flex-wrap here plus AAE_A_Toggle_Pane's own base style
	 * (flex: 1 0 100%, forcing each pane onto its own row) — no nested
	 * flexbox wrapper element is needed just to lay out the label/switch row.
	 */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',        String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'row' ) )
						->add_prop( 'flex-wrap',      String_Prop_Type::generate( 'wrap' ) )
						->add_prop( 'align-items',    String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',            Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ) )
				),
		];
	}

	/**
	 * Plain, marker-free starting point — two text labels and an empty
	 * Div_Block placeholder for the switch, plus two panes. No aae-ts-*
	 * classes and no knob/highlight children are baked in here: those live
	 * entirely in the ready-made templates (each one is a self-contained
	 * element tree, independent of these defaults), matching the Btn
	 * wrapper's plain, marker-free defaults.
	 */
	protected function define_default_children() {
		return [
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Label Before' ] )
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Monthly' ),
						'children' => [],
					] ),
					'tag' => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			Div_Block::generate()
				->editor_settings( [ 'title' => 'Switch' ] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Label After' ] )
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Yearly' ),
						'children' => [],
					] ),
					'tag' => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			AAE_A_Toggle_Pane::generate()
				->editor_settings( [ 'title' => 'Pane 1' ] )
				->build(),

			AAE_A_Toggle_Pane::generate()
				->editor_settings( [ 'title' => 'Pane 2' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [
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
