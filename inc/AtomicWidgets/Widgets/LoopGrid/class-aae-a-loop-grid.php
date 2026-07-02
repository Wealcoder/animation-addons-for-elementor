<?php
/**
 * AAE Loop Grid — atomic NESTED element.
 *
 * Queries WP posts and repeats its own authored child subtree (the "loop item")
 * once per post — like Elementor Pro's Loop Grid, but the loop item is a real
 * atomic element tree living INSIDE this element (nested), so every div is a
 * selectable / styleable atomic element.
 *
 * Render strategy:
 *   This is an Atomic_Element_Base container (Has_Element_Template). The authored
 *   children are rendered once per queried post by overriding
 *   render_children_to_html(): WP_Query -> the_post() -> render the children. The
 *   atomic "current post" widgets inside (post title/image) read the global WP
 *   loop context, so each card automatically shows the right post — no separate
 *   document, no print_elements(), no element cache, no dynamic-tag wiring.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-loop-layout.php';
require_once __DIR__ . '/class-aae-a-loop-item.php';
require_once __DIR__ . '/class-aae-a-loop-pagination.php';
require_once __DIR__ . '/class-aae-a-loop-prev.php';
require_once __DIR__ . '/class-aae-a-loop-next.php';
require_once __DIR__ . '/../PostImage/class-aae-a-post-image.php';
require_once __DIR__ . '/../PostTitle/class-aae-a-post-title.php';
require_once __DIR__ . '/../PostMeta/class-aae-a-post-meta.php';

use WCF_ADDONS\AtomicWidgets\Widgets\PostImage\AAE_A_Post_Image;
use WCF_ADDONS\AtomicWidgets\Widgets\PostTitle\AAE_A_Post_Title;
use WCF_ADDONS\AtomicWidgets\Widgets\PostMeta\AAE_A_Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Grid extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-loop-grid';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-grid';
	}

	public function get_title() {
		return esc_html__( 'AAE Loop Grid', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-loop-builder';
	}

	public function get_keywords() {
		return [ 'loop', 'grid', 'posts', 'query', 'template', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'        => Classes_Prop_Type::make()->default( [] ),
			'attributes'     => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Query.
			'post_type'      => String_Prop_Type::make()->default( 'post' ),
			'posts_per_page' => Number_Prop_Type::make()->default( 6 ),
			'order_by'       => String_Prop_Type::make()->default( 'date' ),
			'order'          => String_Prop_Type::make()->default( 'desc' ),

			// Layout.
			'columns'        => Number_Prop_Type::make()->default( 3 ),

			// NOTE: the load_method setting lives on the Pagination child
			// (e-aae-a-loop-pagination); there is no pagination "type" — the bar
			// is DOM-driven and pieces are shown/hidden from the Style panel.
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Query', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_loop_query' )
				->set_items( [
					Select_Control::bind_to( 'post_type' )
						->set_label( __( 'Source', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_post_type_options() ),

					Number_Control::bind_to( 'posts_per_page' )
						->set_label( __( 'Posts Per Page', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 50 ),

					Select_Control::bind_to( 'order_by' )
						->set_label( __( 'Order By', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'date',       'label' => __( 'Date', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'title',      'label' => __( 'Title', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'menu_order', 'label' => __( 'Menu Order', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'rand',       'label' => __( 'Random', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'order' )
						->set_label( __( 'Order', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'desc', 'label' => __( 'Descending', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'asc',  'label' => __( 'Ascending', 'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_loop_layout' )
				->set_items( [
					Number_Control::bind_to( 'columns' )
						->set_label( __( 'Columns', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 12 ),

					// Pagination Type + Load Method moved to the Pagination child
					// widget — select the Pagination element to edit them.
				] ),
		];
	}

	private function get_post_type_options(): array {
		$types   = get_post_types( [ 'public' => true ], 'objects' );
		$options = [];
		foreach ( $types as $slug => $obj ) {
			if ( 'attachment' === $slug ) {
				continue;
			}
			$options[] = [ 'value' => $slug, 'label' => $obj->label ];
		}
		return $options;
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()				
				
				)
		];
	}

	protected function set_initial_state(): void {
		parent::set_initial_state();
	}

	/**
	 * Whether an atomic widget/element type is actually registered in this
	 * request. A dropped child whose type the editor doesn't know throws
	 * "ElementTypeNotFound" — e.g. the AAE post widgets are dashboard-toggleable
	 * and may be disabled. So we only seed children whose type resolves.
	 */
	private static function type_registered( string $type ): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		$plugin = \Elementor\Plugin::$instance;
		try {
			if ( isset( $plugin->widgets_manager ) && $plugin->widgets_manager->get_widget_types( $type ) ) {
				return true;
			}
		} catch ( \Throwable $e ) { /* ignore */ }
		try {
			if ( isset( $plugin->elements_manager ) && $plugin->elements_manager->get_element_types( $type ) ) {
				return true;
			}
		} catch ( \Throwable $e ) { /* ignore */ }
		return false;
	}

	/**
	 * Seed one loop-item card so the element is usable the moment it's dropped.
	 * The user edits this subtree in the canvas; it's repeated per post at render.
	 *
	 *   e-flexbox
	 *     ├─ e-aae-a-post-image   (only if registered)
	 *     ├─ e-aae-a-post-title   (only if registered)
	 *     └─ e-heading / e-button (always-present core fallback)
	 *
	 * Only registered types are seeded — an unknown child type makes the editor
	 * throw ElementTypeNotFound on drop.
	 */
	protected function define_default_children() {
		$children = [];

		if ( self::type_registered( 'e-aae-a-post-image' ) ) {
			$children[] = AAE_A_Post_Image::generate()
				->editor_settings( [ 'title' => 'Post Image' ] )
				->build();
		} 

		if ( self::type_registered( 'e-aae-a-post-title' ) ) {
			$children[] = AAE_A_Post_Title::generate()
				->editor_settings( [ 'title' => 'Post Title' ] )
				->build();
		} 	

		// Full Pro-style tree:
		//   Loop Layout (grid)  ->  Loop Item (repeats per post)  ->  card children
		//   Pagination          ->  Previous / Next (each seeds a Paragraph)
		$tree = [
			AAE_A_Loop_Layout::generate()
				->editor_settings( [ 'title' => 'Loop Layout' ] )
				->is_locked( true )
				->children( [
					AAE_A_Loop_Item::generate()
						->editor_settings( [ 'title' => 'Loop Item' ] )
						->is_locked( true )
						->children( $children )
						->build(),
				] )
				->build(),
			// Pagination self-seeds its atomic pieces (Prev / Numbers / Next /
			// Load More) — see AAE_A_Loop_Pagination::define_default_children().
			AAE_A_Loop_Pagination::generate()
				->editor_settings( [ 'title' => 'Pagination' ] )
				->is_locked( true )
				->build(),
		];

		return $tree;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-loop-layout', 'e-aae-a-loop-pagination' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-grid' => __DIR__ . '/aae-a-loop-grid.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-loop-grid-css' ];
	}

	/**
	 * Publish the query to descendants via the atomic Render_Context stack.
	 *
	 * The per-post REPEAT now lives on the Loop Item (e-aae-a-loop-item), so the
	 * repeat wraps the whole card — and any non-repeating siblings (e.g. a future
	 * Pagination) render only once. Loop Item reads this context to build its
	 * WP_Query. Keyed by this class so only our descendants read it.
	 *
	 * @see \Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context
	 */
	protected function define_render_context(): array {
		$s = $this->get_atomic_settings();

		$per_page = isset( $s['posts_per_page'] ) ? (int) $s['posts_per_page'] : 6;
		$paged    = self::current_page();

		$query_args = [
			'post_type'           => isset( $s['post_type'] ) ? $s['post_type'] : 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'orderby'             => isset( $s['order_by'] ) ? $s['order_by'] : 'date',
			'order'               => isset( $s['order'] ) ? strtoupper( $s['order'] ) : 'DESC',
			'paged'               => $paged,
			'ignore_sticky_posts' => true,
		];

		// Resolve total pages once so the Pagination child can render the right
		// number of page links without re-querying.
		$max_pages = 1;
		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$count = new \WP_Query( array_merge( $query_args, [ 'fields' => 'ids', 'no_found_rows' => false ] ) );
			$max_pages = max( 1, (int) $count->max_num_pages );
			wp_reset_postdata();
		}

		return [
			[
				'context_key' => self::class,
				'context'     => [
					'query_args'    => $query_args,
					'columns'       => isset( $s['columns'] ) ? (int) $s['columns'] : 3,
					'paged'         => $paged,
					'max_num_pages' => $max_pages,
					// The load_method setting is the Pagination child's own —
					// intentionally NOT published here, so changing it doesn't
					// invalidate this element's render context (which would re-render
					// the repeating Loop Item subtree).
					'grid_id'       => $this->get_id(),
					'query'         => [
						'post_type' => isset( $s['post_type'] ) ? $s['post_type'] : 'post',
						'per_page'  => $per_page,
						'order_by'  => isset( $s['order_by'] ) ? $s['order_by'] : 'date',
						'order'     => isset( $s['order'] ) ? $s['order'] : 'desc',
						'columns'   => isset( $s['columns'] ) ? (int) $s['columns'] : 3,
					],
				],
			],
		];
	}

	/**
	 * Current paged value from the URL. Uses `aae_page` (our own query var so
	 * multiple loop grids / the main query don't collide) and falls back to
	 * WordPress's `paged` / `page`.
	 */
	public static function current_page(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['aae_page'] ) ) {
			return max( 1, (int) $_GET['aae_page'] );
		}
		$paged = (int) get_query_var( 'paged' );
		if ( ! $paged ) {
			$paged = (int) get_query_var( 'page' );
		}
		return max( 1, $paged );
	}
}
