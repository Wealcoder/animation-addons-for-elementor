<?php
/**
 * AAE Loop Load More — atomic "Load More" button container.
 *
 * Seeds an editable paragraph label; clicking it (frontend) appends the next
 * page of posts to the grid via AJAX. Fully atomic / styleable.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_LoadMore extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-loop-loadmore';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-loadmore';
	}

	public function get_title() {
		return esc_html__( 'Load More', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-plus-circle';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		// Nested: user can add an icon, heading, divider, etc. inside Load More.
		return [ 'e-paragraph', 'e-button', 'e-svg', 'e-heading', 'e-divider', 'e-aae-a-loop-arrow' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	/** Default button look — fully overridable from the Style panel. */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ) )
					->add_prop( 'cursor', String_Prop_Type::generate( 'pointer' ) )
					->add_prop( 'padding', Dimensions_Prop_Type::generate( [
						'block-start'  => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'block-end'    => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'inline-start' => Size_Prop_Type::generate( [ 'size' => 22, 'unit' => 'px' ] ),
						'inline-end'   => Size_Prop_Type::generate( [ 'size' => 22, 'unit' => 'px' ] ),
					] ) )
					->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ) )
					->add_prop( 'background', Background_Prop_Type::generate( [
						'color' => Color_Prop_Type::generate( '#515962' ),
					] ) )
					->add_prop( 'color', Color_Prop_Type::generate( '#ffffff' ) )
			),
		];
	}

	protected function define_default_children() {
		return [
			[
				'elType'          => 'widget',
				'widgetType'      => 'e-paragraph',
				'settings'        => [
					'paragraph' => [
						'$$type' => 'html-v3',
						'value'  => [
							'content'  => [ '$$type' => 'string', 'value' => __( 'Load More', 'animation-addons-for-elementor' ) ],
							'children' => [],
						],
					],
					'tag'       => [ '$$type' => 'string', 'value' => 'span' ],
				],
				'editor_settings' => [ 'title' => 'Load More Label' ],
				'elements'        => [],
			],
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-loadmore' => __DIR__ . '/aae-a-loop-loadmore.html.twig',
		];
	}
}
