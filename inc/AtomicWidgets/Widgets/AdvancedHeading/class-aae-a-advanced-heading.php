<?php
/**
 * AAE Advanced Heading — atomic leaf widget.
 *
 * A heading whose text is a single raw-HTML field. Unlike Elementor's stock
 * heading (which strips `class` from inline tags), this widget keeps inline
 * markup WITH your own classes — so you can wrap any word in
 * `<span class="…">`, `<mark>`, `<b>` etc. and style / highlight it yourself.
 *
 * Design-less by intent: the plugin ships NO CSS. You add classes in the
 * content and style them via the Elementor Style panel or your own CSS.
 * (See AAE_Html_Rich_Prop_Type for why a custom prop type is needed to let
 * the classes survive save + render.)
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

require_once __DIR__ . '/class-aae-html-rich-prop-type.php';

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Textarea_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Advanced_Heading extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Heading that accepts raw inline HTML (span, mark, b, i, a …) with your own classes — highlight and style any part of the text yourself.';

	public static function get_element_type(): string {
		return 'e-aae-a-advanced-heading';
	}

	public function get_title() {
		return esc_html__( 'AAE Advanced Heading', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'heading', 'title', 'highlight', 'html', 'span', 'advanced' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// HTML tag for the heading wrapper.
			'ah_tag'  => String_Prop_Type::make()->default( 'h2' ),

			// Raw inline HTML. Sanitised by AAE_Html_Rich_Prop_Type so classes
			// on inline tags survive; rendered with `| raw` (no striptags).
			'content' => AAE_Html_Rich_Prop_Type::make()->default(
				'Build your <span class="highlight">Innovate</span> Our Core Solution'
			),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Heading', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'ah_tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'h1', 'label' => 'H1' ],
							[ 'value' => 'h2', 'label' => 'H2' ],
							[ 'value' => 'h3', 'label' => 'H3' ],
							[ 'value' => 'h4', 'label' => 'H4' ],
							[ 'value' => 'h5', 'label' => 'H5' ],
							[ 'value' => 'h6', 'label' => 'H6' ],
							[ 'value' => 'div', 'label' => __( 'div', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'p', 'label' => __( 'p', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'span', 'label' => __( 'span', 'animation-addons-for-elementor' ) ],
						] ),

					Textarea_Control::bind_to( 'content' )
						->set_label( __( 'Content (HTML allowed)', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'Build your <span class="highlight">word</span> here' ),
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

	// No define_base_styles() override → inherits the empty default: the plugin
	// ships ZERO CSS for this widget. Style everything via the panel / your own
	// classes.

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-advanced-heading' => __DIR__ . '/aae-a-advanced-heading.html.twig',
		];
	}
}
