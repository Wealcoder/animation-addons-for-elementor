<?php
/**
 * AAE Loop Pagination — atomic pagination container (Pro replica).
 *
 * A real atomic nested element (like the Nested Slider's nav): it holds the
 * Prev / Page Numbers / Next / Load More atomic children, all individually
 * editable + styleable. It renders ONCE (sibling of the repeating Loop Layout).
 *
 * There is no pagination "type" control: every child (Prev/Next, Page Numbers,
 * Load More) is seeded and rendered by default, and the user hides the ones they
 * don't want straight from the Style panel (display: none) — like any atomic
 * element. The only setting is the load method (AJAX vs page reload). The
 * frontend JS is DOM-driven: it wires clicks for whichever pieces are present +
 * visible, using the data-* attrs published on this wrapper.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-loop-nav-wrap.php';
require_once __DIR__ . '/class-aae-a-loop-prev.php';
require_once __DIR__ . '/class-aae-a-loop-next.php';
require_once __DIR__ . '/class-aae-a-loop-numbers.php';
require_once __DIR__ . '/class-aae-a-loop-loadmore.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Pagination extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-loop-pagination';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-pagination';
	}

	public function get_title() {
		return esc_html__( 'Pagination', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-ellipsis-h';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-loop-nav-wrap', 'e-aae-a-loop-numbers', 'e-aae-a-loop-loadmore' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// No "type" control: the pagination bar is DOM-driven. Every piece
			// (Prev/Next, Numbers, Load More) renders by default; the user hides the
			// ones they don't want straight from the Style panel (display: none) —
			// like any atomic element. The frontend runtime wires whichever pieces
			// are present + visible. Only the load behaviour stays a setting.
			//
			// Number styling now lives on the Page Number TEMPLATE itself (one
			// authored, selectable, styleable element that repeats per page link),
			// so there are no number-theming props here anymore.
			'load_method' => String_Prop_Type::make()->default( 'ajax' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Pagination', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_pagination_settings' )
				->set_items( [
					Select_Control::bind_to( 'load_method' )
						->set_label( __( 'Load Method', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'ajax',   'label' => __( 'AJAX (no reload)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'reload', 'label' => __( 'Page Reload', 'animation-addons-for-elementor' ) ],
						] ),
				] ),
		];
	}

	/**
	 * Default layout for the pagination bar. All editable from the Style panel.
	 * Prev far-left / Next far-right (space-between), numbers centered.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
					->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
					// Without this, a flex COLUMN defaults to align-items:stretch, so
					// Nav / Numbers / Load More each span the full width. Their editor
					// overlay then anchors to that full-width box's top-right — far from
					// the visible content — so the toolbar is unreachable ("kape").
					// flex-start keeps each piece content-width, overlay stays close.
					->add_prop( 'align-items', String_Prop_Type::generate( 'flex-start' ) )
					->add_prop( 'flex-wrap', String_Prop_Type::generate( 'wrap' ) )
					->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ) )
					->add_prop( 'margin', Dimensions_Prop_Type::generate( [
						'block-start' => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
					] ) )
			),
		];
	}

	/**
	 * Seed the Pro-style structure:
	 *   Pagination
	 *     ├─ Nav      (wrapper)  →  Prev + Next
	 *     ├─ Numbers  (runtime page links)
	 *     └─ Load More
	 * The Nav wrapper keeps Prev/Next together; Numbers + Load More are siblings.
	 * All structural pieces are locked; their inner labels/icons stay editable.
	 */
	protected function define_default_children() {
		return [
			AAE_A_Loop_Nav_Wrap::generate()
				->editor_settings( [ 'title' => 'Nav' ] )
				->is_locked( true )
				->children( [
					AAE_A_Loop_Prev::generate()
						->editor_settings( [ 'title' => 'Previous' ] )
						->is_locked( true )
						->build(),
					AAE_A_Loop_Next::generate()
						->editor_settings( [ 'title' => 'Next' ] )
						->is_locked( true )
						->build(),
				] )
				->build(),
			AAE_A_Loop_Numbers::generate()
				->editor_settings( [ 'title' => 'Page Numbers' ] )
				->children( AAE_A_Loop_Numbers::build_number_template() )
				->build(),
			AAE_A_Loop_LoadMore::generate()
				->editor_settings( [ 'title' => 'Load More' ] )
				->is_locked( true )
				->build(),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-pagination' => __DIR__ . '/aae-a-loop-pagination.html.twig',
		];
	}

	/**
	 * Publish the query + AJAX config to the twig so the wrapper carries the
	 * data-* attrs the frontend JS reads.
	 *
	 * There is no pagination "type" any more — the runtime is DOM-driven: it
	 * wires whichever pieces (Prev/Next, Numbers, Load More) are present and
	 * visible. Only the load method (ajax vs page reload) is a setting.
	 */
	protected function build_template_context(): array {
		$ctx = Render_Context::get( AAE_A_Loop_Grid::class );

		// Load method is THIS widget's own setting. The query-derived values
		// (current page, total pages, query params, grid id) come from the root
		// grid's render context.
		$s = $this->get_atomic_settings();

		$method  = isset( $s['load_method'] ) ? $s['load_method'] : 'ajax';
		$current = isset( $ctx['paged'] ) ? (int) $ctx['paged'] : 1;
		$total   = isset( $ctx['max_num_pages'] ) ? (int) $ctx['max_num_pages'] : 1;
		$query   = isset( $ctx['query'] ) ? $ctx['query'] : [];
		$grid_id = isset( $ctx['grid_id'] ) ? $ctx['grid_id'] : '';

		$cfg = [
			'method'  => $method,
			'current' => $current,
			'total'   => $total,
			'grid'    => $grid_id,
			'postId'  => get_the_ID(),
			'query'   => $query,
			'nonce'   => wp_create_nonce( 'aae_loop_grid_front' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		];

		return array_merge( $this->build_base_template_context(), [
			'pg_method'  => $method,
			'pg_current' => $current,
			'pg_total'   => $total,
			'pg_config'  => wp_json_encode( $cfg ),
		] );
	}
}
