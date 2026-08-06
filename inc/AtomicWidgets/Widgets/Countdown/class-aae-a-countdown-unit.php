<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Countdown;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Box_Shadow_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Shadow_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * Shared sub-element used by AAE_A_Countdown for each of the four time
 * fragments. Identical structure (digit + label); only the `unit_type`
 * prop differs between the four instances so the JS handler knows which
 * piece of the remaining time to render into each one.
 *
 * Hidden from the widget panel — only spawnable inside an AAE_A_Countdown
 * parent via `define_default_children()`.
 */
class AAE_A_Countdown_Unit extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A single time fragment inside a Countdown — contains the digit and the label as atomic children.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-countdown-unit';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-countdown-unit';
	}

	public function get_title() {
		return esc_html__( 'Countdown Unit', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'countdown', 'unit', 'timer' ];
	}

	public function get_icon() {
		return 'eicon-clock-o';
	}

	public function should_show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Which time fragment this unit represents. Set by the parent's
			// `define_default_children()` — not user-editable through a
			// control, just surfaced as a `data-unit-type` attribute in the
			// Twig so the JS handler can target it.
			'unit_type'  => String_Prop_Type::make()->default( 'days' ),
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
	 * Wrapper-level layout for a single unit — a small card (background,
	 * border, radius, soft shadow) stacking a big digit over a muted label.
	 * Everything here is still a plain Style_Definition, so a builder can
	 * override any of it per-instance from the Style panel — these are just
	 * good-looking resting values instead of an unstyled box.
	 */
	protected function define_base_styles(): array {
		$card_shadow = Box_Shadow_Prop_Type::generate( [
			Shadow_Prop_Type::generate( [
				'hOffset' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'vOffset' => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ),
				'blur'    => Size_Prop_Type::generate( [ 'size' => 3, 'unit' => 'px' ] ),
				'spread'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'color'   => Color_Prop_Type::generate( '#0000000d' ),
			] ),
			Shadow_Prop_Type::generate( [
				'hOffset' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'vOffset' => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ),
				'blur'    => Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ),
				'spread'  => Size_Prop_Type::generate( [ 'size' => -1, 'unit' => 'px' ] ),
				'color'   => Color_Prop_Type::generate( '#0000001a' ),
			] ),
		] );

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',         String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction',  String_Prop_Type::generate( 'column' ) )
						->add_prop( 'align-items',     String_Prop_Type::generate( 'center' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',             Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ) )
						->add_prop( 'min-width',       Size_Prop_Type::generate( [ 'size' => 76, 'unit' => 'px' ] ) )
						->add_prop( 'background', Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( '#ffffff' ),
						] ) )
						->add_prop( 'border-style', String_Prop_Type::generate( 'solid' ) )
						->add_prop( 'border-color', Color_Prop_Type::generate( '#e5e7eb' ) )
						->add_prop( 'border-width', Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ) )
						->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ) )
						->add_prop( 'padding', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 18, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 18, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
						] ) )
						->add_prop( 'box-shadow', $card_shadow )
				),

			// Applied to the digit span via the '{element_type}-digit' class
			// (see build_default_inner_children()) — same convention as
			// Accordion Item's header_element/header_icon keys.
			'digit' => Style_Definition::make()
				->set_label( __( 'Digit', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()
						->add_prop( 'color', Color_Prop_Type::generate( '#111827' ) )
						->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 32, 'unit' => 'px' ] ) )
						->add_prop( 'font-weight', String_Prop_Type::generate( '700' ) )
						->add_prop( 'line-height', Size_Prop_Type::generate( [ 'size' => 34, 'unit' => 'px' ] ) )
				),

			'label' => Style_Definition::make()
				->set_label( __( 'Label', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()
						->add_prop( 'color', Color_Prop_Type::generate( '#6b7280' ) )
						->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 11, 'unit' => 'px' ] ) )
						->add_prop( 'font-weight', String_Prop_Type::generate( '600' ) )
						->add_prop( 'text-transform', String_Prop_Type::generate( 'uppercase' ) )
				),
		];
	}

	/**
	 * Static fallback children — used only when a unit is instantiated
	 * WITHOUT the parent pre-supplying a `->children([...])` tree (e.g. if
	 * a user adds a fresh Countdown Unit by hand outside the default
	 * spawn path).
	 *
	 * IMPORTANT: do NOT call `$this->get_settings()` here. This method
	 * fires while the instance is still being constructed, so settings
	 * is `null` and the chain bottoms out in
	 * `Controls_Stack::sanitize_settings(null)` → fatal. Mirror the
	 * IconList Item pattern: emit static literal defaults only. The
	 * parent passes the correctly-labeled children directly via
	 * `Element_Builder::children([...])` at spawn time.
	 *
	 * Helper exposed publicly so the parent (AAE_A_Countdown) can call
	 * it with a per-unit label when composing each locked instance.
	 */
	public static function build_default_inner_children( string $label_text = 'Label' ): array {
		// NOTE: digit + label are both Atomic_Paragraph. The digit isn't a
		// semantic heading (it's a span of frequently-changing text), and
		// Atomic_Heading's `tag` enum is restricted to `h1`-`h6` only —
		// passing `span` there fails the v4 settings validator.
		// Atomic_Paragraph's `tag` enum allows `p` and `span`. The user
		// still gets full typography control via the paragraph's Style
		// panel.
		//
		// The `-digit`/`-label` classes wear the 'digit'/'label' base-style
		// keys defined above (static, cached CSS); the `aae-a-countdown-*`
		// classes are unrelated JS/CSS hooks (countdown.js's tick selector,
		// the show/hide-on-expire rule) and must stay exactly as they are.
		$digit_class = static::get_element_type() . '-digit';
		$label_class = static::get_element_type() . '-label';

		return [
			Atomic_Paragraph::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Digit' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-countdown-unit-count', $digit_class ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '00' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Label' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-countdown-unit-label', $label_class ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $label_text ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),
		];
	}

	protected function define_default_children() {
		return self::build_default_inner_children( 'Label' );
	}

	protected function define_allowed_child_types() {
		// User can still drop arbitrary heading / paragraph widgets inside
		// a unit if they want, but the locked digit + label always remain.
		return [ 'widget', 'e-heading', 'e-paragraph', 'e-svg' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-countdown-unit' => __DIR__ . '/aae-a-countdown-unit.html.twig',
		];
	}
}
