<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Counter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Counter extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'Display an animated number counter that counts up when it scrolls into view. Prefix, animated number, and suffix are independently styleable.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-counter';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-counter';
	}

	public function get_title() {
		return esc_html__( 'Counter', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'counter', 'number', 'animation', 'gsap' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	/**
	 * Panel category for the Elements panel.
	 *
	 * Atomic_Element_Base reads the panel category from HERE — get_categories()
	 * is Widget_Base's hook and is never called for an element type, so a
	 * category declared only there silently falls back to Elementor's own
	 * 'v4-elements' ("Atomic Elements") bucket. Delegate so both stay in sync.
	 */
	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'      => Classes_Prop_Type::make()->default( [] ),
			'attributes'   => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'start_number' => Number_Prop_Type::make()->default( 0 ),
			'duration'     => Number_Prop_Type::make()->default( 2000 ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Counter', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Number_Control::bind_to( 'start_number' )
						->set_label( __( 'Start Number', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'duration' )
						->set_label( __( 'Duration (ms)', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 100, 'max' => 10000, 'step' => 100 ] ),
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
	 * Typography lives on the ROOT, not on the three children.
	 *
	 * Atomic_Paragraph's own base style sets nothing but `margin: 0`, so
	 * font-size/weight/line-height/colour declared here inherit down into
	 * Prefix, Number and Suffix — and a builder restyling the Counter
	 * restyles all three at once, which is what "one counter" should mean.
	 * Declaring them per child would instead need three separate style edits
	 * to change one number's size.
	 *
	 * Without these the counter rendered at the theme's body copy size — a
	 * 16px "0 +" that reads as a stray paragraph rather than a stat.
	 *
	 * `line-height` uses the `custom` unit so it emits the unitless `1.2`
	 * verbatim; a unit here would stop it scaling with font-size.
	 */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',         String_Prop_Type::generate( 'inline-flex' ) )
						->add_prop( 'align-items',     String_Prop_Type::generate( 'baseline' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',             Size_Prop_Type::generate( [ 'size' => 5, 'unit' => 'px' ] ) )
						->add_prop( 'font-size',       Size_Prop_Type::generate( [ 'size' => 48, 'unit' => 'px' ] ) )
						->add_prop( 'font-weight',     String_Prop_Type::generate( '700' ) )
						->add_prop( 'line-height',     Size_Prop_Type::generate( [ 'size' => 1.2, 'unit' => 'custom' ] ) )
						->add_prop( 'color',           Color_Prop_Type::generate( '#0C0D0E' ) )
				),
		];
	}

	protected function define_default_children() {
		return [
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Prefix' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-counter-prefix' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			// The Number child's TEXT is the end value — there is no end-value
			// prop (see counter.js's header). It defaults to 50, not 0: with
			// `start_number` also defaulting to 0 the two matched, play()'s
			// `from === info.value` short-circuit fired, and a freshly dropped
			// Counter sat at "0 +" forever looking like a dead widget.
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Number' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-counter-number' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '50' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Suffix' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-counter-suffix' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '+' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-paragraph', 'e-heading' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-counter' => __DIR__ . '/aae-a-counter.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-counter-js' ];
	}
}
