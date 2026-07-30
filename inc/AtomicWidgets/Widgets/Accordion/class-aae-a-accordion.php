<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Accordion;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Controls\Types\Toggle_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

require_once __DIR__ . '/class-aae-a-accordion-item.php';
require_once __DIR__ . '/class-aae-a-items-control.php';
use WCF_ADDONS\AtomicWidgets\Widgets\Accordion\AAE_A_Accordion_Item;
use WCF_ADDONS\AtomicWidgets\Widgets\Accordion\AAE_A_Items_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Accordion extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-accordion';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-accordion';
	}

	public function get_title() {
		return esc_html__( 'Accordion', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_keywords() {
		return [ 'accordion', 'tabs', 'toggle', 'atomic', 'gsap' ];
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

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'default_state' => String_Prop_Type::make()->enum( [ 'first', 'none' ] )->default( 'first' ),
			'max_items_expanded' => String_Prop_Type::make()->enum( [ 'one', 'multiple' ] )->default( 'one' ),
			'animation_duration' => Size_Prop_Type::make()->default( [ 'size' => 400, 'unit' => 'ms' ] ),
			'faq_schema' => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			// "Items": a live projection of the accordion's real
			// <e-aae-a-accordion-item> children — one repeater row each, with
			// drag-reorder, duplicate, remove and rename. Mirrors the Nested
			// Slider's "Slides" element-control. Rendered by the React component
			// registered under 'aae-items' (src/modules/atomic/element-controls).
			Section::make()
				->set_id( 'items' )
				->set_label( __( 'Items', 'animation-addons-for-elementor' ) )
				->set_items( [
					AAE_A_Items_Control::make()
						->set_label( __( 'Items', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Accordion Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'faq_schema' )
						->set_label( __( 'FAQ Schema', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'behavior_settings' )
				->set_label( __( 'Behavior', 'animation-addons-for-elementor' ) )
				->set_items( [
					Toggle_Control::bind_to( 'default_state' )
						->set_label( __( 'Default State', 'animation-addons-for-elementor' ) )
						->add_options( [
							'first' => [ 'title' => __( 'First Open', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-plus' ],
							'none'  => [ 'title' => __( 'All Closed', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-minus' ],
						] )
						->set_exclusive( true )
						->set_convert_options( true ),

					Toggle_Control::bind_to( 'max_items_expanded' )
						->set_label( __( 'Max Items Expanded', 'animation-addons-for-elementor' ) )
						->add_options( [
							'one'      => [ 'title' => __( 'One', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-number-1' ],
							'multiple' => [ 'title' => __( 'Multiple', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-copy' ],
						] )
						->set_exclusive( true )
						->set_convert_options( true ),

					Number_Control::bind_to( 'animation_duration.size' )
						->set_label( __( 'Animation Speed (ms)', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display' => String_Prop_Type::generate( 'flex' ),
			'flex-direction' => String_Prop_Type::generate( 'column' ),
			'width' => String_Prop_Type::generate( '100%' ),
			'gap' => Size_Prop_Type::generate( [
				'size' => 10,
				'unit' => 'px',
			] ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Accordion_Item::generate()
				->editor_settings( [ 'title' => 'Accordion Item 1' ] )
				->build(),
			AAE_A_Accordion_Item::generate()
				->editor_settings( [ 'title' => 'Accordion Item 2' ] )
				->build(),
			AAE_A_Accordion_Item::generate()
				->editor_settings( [ 'title' => 'Accordion Item 3' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-accordion-item' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-accordion' => __DIR__ . '/aae-a-accordion.html.twig',
		];
	}

	protected function build_template_context(): array {
		$context = $this->build_base_template_context();
		$settings = $this->get_atomic_settings();

		if ( ! empty( $settings['faq_schema'] ) ) {
			$faq_data = [
				'@context' => 'https://schema.org',
				'@type' => 'FAQPage',
				'mainEntity' => [],
			];

			$children = $this->get_children();
			foreach ( $children as $child ) {
				$child_settings = $child->get_atomic_settings();
				$title = $child_settings['item_title'] ?? '';
				
				ob_start();
				$grand_children = $child->get_children();
				foreach ( $grand_children as $grandchild ) {
					$grandchild->print_element();
				}
				$answer_html = ob_get_clean();
				
				$answer_text = wp_strip_all_tags( $answer_html );

				if ( $title && $answer_text ) {
					$faq_data['mainEntity'][] = [
						'@type' => 'Question',
						'name' => wp_strip_all_tags( $title ),
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text' => trim( $answer_text ),
						],
					];
				}
			}

			if ( ! empty( $faq_data['mainEntity'] ) ) {
				$context['faq_schema_json'] = wp_json_encode( $faq_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		}

		return $context;
	}

	public function get_script_depends(): array {
		return [ 'aae-a-accordion-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-accordion-css' ];
	}
}
