<?php
/**
 * AAE Form Range Group — atomic CONTAINER element. One labelled slider row:
 * heading + live value readout + the slider itself, each a REAL child the
 * builder selects and styles on its own.
 *
 * Composite, not monolithic (the "Nav way", see the aae-v4-complex-widget
 * skill): this class renders nothing but a wrapper and
 * `{{ children_placeholder }}`. Its three default children are
 *
 *   1. `e-heading`               — core Atomic_Heading, the caption
 *   2. `e-aae-a-form-range-value` — the live readout ("250 Sq.")
 *   3. `e-aae-a-form-range`       — the EXISTING slider widget, reused whole
 *
 * so the Style tab a builder already knows styles each part, and deleting or
 * reordering any of them is a normal editor action rather than something the
 * widget has to expose as a prop. Reusing the standalone Range widget for the
 * slider is what keeps submission behaviour identical: Schema_Walker still
 * sees `e-aae-a-form-range` and Validator.php still re-checks min/max
 * server-side through the exact same path, with no new field type.
 *
 * BASE-STYLE-FIRST + DESIGN-LESS: the only styling here is the layout that
 * makes the row a row — flex, wrap, space-between — mirroring how the parent
 * Form widget already lays out label+field pairs (row + wrap, each field
 * 100% wide, so it wraps to its own line). The slider's own base style is
 * `width: 100%`, so heading and value share line one and the slider takes
 * line two, with no wrapper element and no external CSS. Colours, spacing
 * and typography are deliberately absent — they belong to the builder.
 *
 * Everything the runtime needs is rendered markup (`data-aae-range-group` on
 * the root, `data-aae-range` on the slider, `data-aae-range-value` on the
 * readout), never a `classes` entry — a functional hook class in that prop is
 * flagged as "missing" by the panel and its ✕ unapplies it (CLAUDE.md).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Heading\Atomic_Heading;
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

require_once __DIR__ . '/class-aae-a-form-range.php';
require_once __DIR__ . '/class-aae-a-form-range-value.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Range;
use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Range_Value;

class AAE_A_Form_Range_Group extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'A labelled slider row — heading, live value readout and slider, each a separate child you select and style on its own.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-form-range-group';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-form-range-group';
	}

	public function get_title() {
		return esc_html__( 'Range Group (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Container pair (Atomic_Element_Base): should_show_in_panel() +
	 * define_panel_categories(). The classic show_in_panel()/get_categories()
	 * pair the leaf field widgets use is silently never consulted here — see
	 * class-aae-a-form-step.php.
	 */
	public function should_show_in_panel() {
		return true;
	}

	protected function define_panel_categories(): array {
		return [ 'aae-atomic-form' ];
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'range', 'slider', 'group', 'label', 'value', 'amount' ];
	}

	/**
	 * Nothing but the container basics. Every authored value — the caption,
	 * the prefix/suffix, min/max/step, the field name — lives on the child
	 * that owns it, which is the whole point of the composite pattern: one
	 * panel per part, no parent prop shadowing a child's own control.
	 */
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
				->set_items(
					[
						Text_Control::bind_to( '_cssid' )
							->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
							->set_meta( $this->get_css_id_control_meta() ),
					]
				),
		];
	}

	/**
	 * Leaf widgets (elType `widget` — the readout, the slider, and every other
	 * AAE field) plus the core layout containers, so a builder can wrap the
	 * caption row in a flexbox or add an icon without fighting the widget.
	 */
	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-heading', 'e-paragraph', 'e-svg', 'e-flexbox', 'e-div-block' ];
	}

	/**
	 * Heading + readout + slider, in DOM order.
	 *
	 * No wrapper element for the caption row: the group is `flex-wrap: wrap`
	 * and the slider's own base style is `width: 100%`, so the slider wraps to
	 * its own line and `justify-content: space-between` pushes the heading and
	 * the readout to opposite ends of line one. That is one fewer node in the
	 * Structure panel for the builder to navigate — and it is the same
	 * mechanism the Form widget already uses for label+field pairs.
	 *
	 * `h6` rather than the core default `h2`: a field caption should not
	 * outrank real page headings in the document outline. The builder can
	 * change the tag (or swap the whole child for a Paragraph) from its panel.
	 */
	protected function define_default_children(): array {
		return [
			Atomic_Heading::generate()
				->editor_settings( [ 'title' => __( 'Label', 'animation-addons-for-elementor' ) ] )
				->settings(
					[
						'title' => Html_V3_Prop_Type::generate(
							[
								'content'  => String_Prop_Type::generate( __( 'Total area to be cleaned:', 'animation-addons-for-elementor' ) ),
								'children' => [],
							]
						),
						'tag'   => String_Prop_Type::generate( 'h6' ),
					]
				)
				->build(),

			AAE_A_Form_Range_Value::generate()
				->editor_settings( [ 'title' => __( 'Value', 'animation-addons-for-elementor' ) ] )
				->build(),

			AAE_A_Form_Range::generate()
				->editor_settings( [ 'title' => __( 'Slider', 'animation-addons-for-elementor' ) ] )
				->build(),
		];
	}

	/**
	 * The row, and nothing else.
	 *
	 * `width: 100%` for the same reason Rating needs it: inside the form's own
	 * wrapping flex row a shrink-to-fit field lets the next control tuck in
	 * beside it instead of starting a new line.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'width'           => Size_Prop_Type::generate(
								[
									'size' => 100,
									'unit' => '%',
								]
							),
							'display'         => String_Prop_Type::generate( 'flex' ),
							'flex-wrap'       => String_Prop_Type::generate( 'wrap' ),
							'align-items'     => String_Prop_Type::generate( 'center' ),
							'justify-content' => String_Prop_Type::generate( 'space-between' ),
							'gap'             => Size_Prop_Type::generate(
								[
									'size' => 8,
									'unit' => 'px',
								]
							),
						]
					)
				),
		];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-range-group' => __DIR__ . '/aae-a-form-range-group.html.twig',
		];
	}
}
