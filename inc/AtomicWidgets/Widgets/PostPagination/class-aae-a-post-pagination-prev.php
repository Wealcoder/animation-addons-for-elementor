<?php
/**
 * AAE Post Pagination Previous — the "Previous Post" button.
 *
 * Structural container (like AAE_A_Loop_Prev) seeding an arrow icon (reuses
 * AAE_A_Loop_Arrow directly rather than duplicating it) + a text label, both
 * independently restyleable/replaceable — same composability the Loop Grid
 * pagination pieces already offer.
 *
 * Renders as a REAL `<a href>` to the resolved previous post's permalink
 * (not a JS-only click target) so it works with no JS, is crawlable, and
 * supports native "open in new tab" / prefetch — unlike Loop Prev/Next,
 * which stay `<div>`s clicked via event delegation because they trigger an
 * in-place AJAX swap rather than a real page-to-page navigation.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\PostPagination;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/../LoopGrid/class-aae-a-loop-arrow.php';

use WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Arrow;

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Post_Pagination_Prev extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-post-pagination-prev';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-post-pagination-prev';
	}

	public function get_title() {
		return esc_html__( 'Previous Post', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-chevron-left';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		return [ 'e-paragraph', 'e-button', 'e-svg', 'e-aae-a-loop-arrow' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'label'      => String_Prop_Type::make()->default( __( 'Previous Post', 'animation-addons-for-elementor' ) ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	protected function define_default_children() {
		$children = [];

		if ( self::type_registered( 'e-aae-a-loop-arrow' ) ) {
			$children[] = AAE_A_Loop_Arrow::generate()
				->editor_settings( [ 'title' => 'Prev Icon' ] )
				->settings( [ 'direction' => [ '$$type' => 'string', 'value' => 'prev' ] ] )
				->build();
		}

		$children[] = [
			'elType'          => 'widget',
			'widgetType'      => 'e-paragraph',
			'settings'        => [
				'paragraph' => [
					'$$type' => 'html-v3',
					'value'  => [
						'content'  => [ '$$type' => 'string', 'value' => __( 'Previous Post', 'animation-addons-for-elementor' ) ],
						'children' => [],
					],
				],
				'tag'       => [ '$$type' => 'string', 'value' => 'span' ],
			],
			'editor_settings' => [ 'title' => 'Prev Label' ],
			'elements'        => [],
		];

		return $children;
	}

	private static function type_registered( string $type ): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		try {
			return (bool) \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $type );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'gap', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
					->add_prop( 'cursor', String_Prop_Type::generate( 'pointer' ) )
					->add_prop( 'text-decoration', String_Prop_Type::generate( 'none' ) )
					->add_prop( 'color', \Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type::generate( '#1a1a1a' ) )
					->add_prop( 'padding', \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate( [
						'block-start'  => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'block-end'    => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'inline-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
						'inline-end'   => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
					] ) )
					->add_prop( 'border-width', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ) )
					->add_prop( 'border-style', String_Prop_Type::generate( 'solid' ) )
					->add_prop( 'border-color', \Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type::generate( '#d5d8dc' ) )
					->add_prop( 'border-radius', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-pagination-prev' => __DIR__ . '/aae-a-post-pagination-item.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-post-pagination-css' ];
	}

	protected function build_template_context(): array {
		$ctx  = Render_Context::get( AAE_A_Post_Pagination::class );
		$prev = isset( $ctx['prev'] ) ? $ctx['prev'] : null;

		return array_merge( $this->build_base_template_context(), [
			'nav_role'    => 'prev',
			'nav_url'     => $prev ? $prev['url'] : '',
			'nav_title'   => $prev ? $prev['title'] : '',
			'nav_available' => (bool) $prev,
		] );
	}
}
