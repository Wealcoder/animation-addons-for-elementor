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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Array_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Date_Range_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Date_Range_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-query-chips-control.php';
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
		$schema = [
			'classes'        => Classes_Prop_Type::make()->default( [] ),
			'attributes'     => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Query.
			'post_type'      => String_Prop_Type::make()->default( 'post' ),
			'posts_per_page' => Number_Prop_Type::make()->default( 6 ),
			'order_by'       => String_Prop_Type::make()->default( 'date' ),
			'order'          => String_Prop_Type::make()->default( 'desc' ),

			// NOTE: no `columns` prop — the layout is flexbox (Loop Item base
			// style `flex: 1 1 32%`), tuned responsively from the Style panel.
			// The load_method setting lives on the Pagination child
			// (e-aae-a-loop-pagination); there is no pagination "type" — the bar
			// is DOM-driven and pieces are shown/hidden from the Style panel.
		];

		// Advanced query filters. Multi-value selections (terms / posts) are
		// String_Array props whose items are JSON strings {"id":123,"label":".."}
		// written by the custom `aae-query-chips` control — the id feeds the
		// query, the label re-hydrates the chip when the panel reopens.

		// One term-filter prop per public taxonomy, shown only while the
		// selected Source post type actually has that taxonomy registered.
		// NOTE the operator is 'in', not 'nin': the 4.1.x client evaluator
		// applies the effect when the term is NOT met (terms describe when the
		// hide fires against the met-result inverted) — verified empirically:
		// 'nin' hid the control when post_type WAS in the list. 'in' + hide =
		// hidden unless post_type is one of the taxonomy's object types.
		foreach ( self::get_query_taxonomies() as $tax ) {
			$schema[ self::tax_prop_name( $tax->name ) ] = String_Array_Prop_Type::make()
				->default( [] )
				->set_dependencies(
					Dependency_Manager::make()
						->where( [
							'operator' => 'in',
							'path'     => [ 'post_type' ],
							'value'    => array_values( (array) $tax->object_type ),
							'effect'   => 'hide',
						] )
						->get()
				);
		}

		$schema['include_posts']       = String_Array_Prop_Type::make()->default( [] );
		$schema['exclude_posts']       = String_Array_Prop_Type::make()->default( [] );
		$schema['date_range']          = Date_Range_Prop_Type::make();
		$schema['meta_key_exists']     = String_Prop_Type::make()->default( '' );
		$schema['only_featured_image'] = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}

	/**
	 * Public taxonomies offered as query filters (post_format excluded — it's
	 * technically public but not a user-facing filter).
	 *
	 * @return array<string, \WP_Taxonomy>
	 */
	public static function get_query_taxonomies(): array {
		$taxes = get_taxonomies( [ 'public' => true, 'show_ui' => true ], 'objects' );
		unset( $taxes['post_format'] );
		return $taxes;
	}

	/** Prop name for a taxonomy's term filter, e.g. `tax_category`. */
	public static function tax_prop_name( string $taxonomy ): string {
		return 'tax_' . $taxonomy;
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
				->set_label( __( 'Query Filters', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_loop_query_filters' )
				->set_items( $this->get_filter_controls() ),

			// No Layout section: the column layout is flexbox-driven — the Loop
			// Item's base style (`flex: 1 1 32%`) sets the default 3-up grid and
			// the user tunes it responsively from the item's Style panel. The old
			// "Columns" number control wasn't responsive and did nothing in the
			// flex layout, so it was removed. Load Method lives on the Pagination
			// child widget.
		];
	}

	/**
	 * The "Query Filters" section items: one AJAX term-chips control per public
	 * taxonomy (visibility driven by the tax prop's dependency on post_type),
	 * include/exclude specific posts, date range, meta-key-exists and
	 * featured-image-only filters.
	 */
	private function get_filter_controls(): array {
		$items = [];

		foreach ( self::get_query_taxonomies() as $tax ) {
			$items[] = AAE_Query_Chips_Control::bind_to( self::tax_prop_name( $tax->name ) )
				->set_label( $tax->label )
				->set_kind( 'term' )
				->set_taxonomy( $tax->name )
				->set_placeholder( __( 'Search terms…', 'animation-addons-for-elementor' ) );
		}

		$items[] = AAE_Query_Chips_Control::bind_to( 'include_posts' )
			->set_label( __( 'Include Posts', 'animation-addons-for-elementor' ) )
			->set_kind( 'post' )
			->set_placeholder( __( 'Search by title or ID…', 'animation-addons-for-elementor' ) );

		$items[] = AAE_Query_Chips_Control::bind_to( 'exclude_posts' )
			->set_label( __( 'Exclude Posts', 'animation-addons-for-elementor' ) )
			->set_kind( 'post' )
			->set_placeholder( __( 'Search by title or ID…', 'animation-addons-for-elementor' ) );

		$items[] = Date_Range_Control::bind_to( 'date_range' )
			->set_label( __( 'Date Range', 'animation-addons-for-elementor' ) );

		$items[] = Text_Control::bind_to( 'meta_key_exists' )
			->set_label( __( 'Meta Key Exists', 'animation-addons-for-elementor' ) );

		$items[] = Switch_Control::bind_to( 'only_featured_image' )
			->set_label( __( 'Only With Featured Image', 'animation-addons-for-elementor' ) );

		return $items;
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

		// Build from the RAW ($$type-wrapped) settings via the shared builder —
		// the same code path the AJAX pagination and the editor preview use, so
		// all three always agree on the filters.
		$query_args = self::build_query_args( (array) $this->get_data( 'settings' ), $paged );

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
					],
				],
			],
		];
	}

	/**
	 * Build the WP_Query args from this element's settings — the ONE place the
	 * loop query is assembled. Used by:
	 *   - define_render_context() (frontend + server render), from raw element data
	 *   - Atomic::ajax_loop_grid_page() (frontend pagination), from saved doc data
	 *   - Atomic::ajax_loop_post_data() (editor preview), from client-sent plain values
	 *
	 * Accepts both $$type-wrapped atomic settings and already-plain values —
	 * everything is unwrapped and defensively sanitized here, so callers can pass
	 * whatever shape they have.
	 */
	public static function build_query_args( array $raw_settings, int $paged = 1 ): array {
		$s = self::unwrap_settings( $raw_settings );

		$post_type = isset( $s['post_type'] ) && is_string( $s['post_type'] ) && '' !== $s['post_type']
			? sanitize_key( $s['post_type'] )
			: 'post';

		$order_by = isset( $s['order_by'] ) && is_string( $s['order_by'] ) ? $s['order_by'] : 'date';
		if ( ! in_array( $order_by, [ 'date', 'title', 'menu_order', 'rand', 'ID', 'modified' ], true ) ) {
			$order_by = 'date';
		}

		$args = [
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, (int) ( $s['posts_per_page'] ?? 6 ) ),
			'orderby'             => $order_by,
			'order'               => ( isset( $s['order'] ) && 'asc' === strtolower( (string) $s['order'] ) ) ? 'ASC' : 'DESC',
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,
		];

		// Taxonomy term filters — only taxonomies actually registered for the
		// selected post type (stale selections from a previous Source are kept in
		// the settings but must not leak into the query).
		$tax_query = [];
		foreach ( self::get_query_taxonomies() as $tax ) {
			if ( ! in_array( $post_type, (array) $tax->object_type, true ) ) {
				continue;
			}
			$term_ids = self::extract_ids( $s[ self::tax_prop_name( $tax->name ) ] ?? null );
			if ( $term_ids ) {
				$tax_query[] = [
					'taxonomy' => $tax->name,
					'field'    => 'term_id',
					'terms'    => $term_ids,
				];
			}
		}
		if ( $tax_query ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		// Specific posts — manual selection. The include search offers every
		// public post type, so when it's used the query opens up to all public
		// types (like Pro's Manual Selection): the picked posts define the
		// result, not the Source dropdown. An explicit type list, not 'any' —
		// 'any' silently drops types flagged exclude_from_search.
		$include = self::extract_ids( $s['include_posts'] ?? null );
		if ( $include ) {
			$args['post__in']  = $include;
			$args['post_type'] = array_values( array_diff( array_keys( get_post_types( [ 'public' => true ] ) ), [ 'attachment' ] ) );
		}
		$exclude = self::extract_ids( $s['exclude_posts'] ?? null );
		if ( $exclude ) {
			$args['post__not_in'] = $exclude;
		}

		// Publish date range (inclusive; min/max are Y-m-d strings).
		$range = isset( $s['date_range'] ) && is_array( $s['date_range'] ) ? $s['date_range'] : [];
		$after  = self::valid_date( $range['min'] ?? null );
		$before = self::valid_date( $range['max'] ?? null );
		if ( $after || $before ) {
			$date_query = [ 'inclusive' => true ];
			if ( $after ) {
				$date_query['after'] = $after;
			}
			if ( $before ) {
				$date_query['before'] = $before . ' 23:59:59';
			}
			$args['date_query'] = [ $date_query ];
		}

		// Meta filters.
		$meta_query = [];
		$meta_key   = isset( $s['meta_key_exists'] ) && is_string( $s['meta_key_exists'] )
			? sanitize_text_field( $s['meta_key_exists'] )
			: '';
		if ( '' !== $meta_key ) {
			$meta_query[] = [
				'key'     => $meta_key,
				'compare' => 'EXISTS',
			];
		}
		if ( ! empty( $s['only_featured_image'] ) ) {
			$meta_query[] = [
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS',
			];
		}
		if ( $meta_query ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		return $args;
	}

	/** Recursively unwrap { $$type, value } atomic prop shapes to plain values. */
	private static function unwrap_settings( $value ) {
		if ( is_array( $value ) && isset( $value['$$type'] ) && array_key_exists( 'value', $value ) ) {
			return self::unwrap_settings( $value['value'] );
		}
		if ( is_array( $value ) ) {
			return array_map( [ self::class, 'unwrap_settings' ], $value );
		}
		return $value;
	}

	/**
	 * Extract positive integer ids from a chips value: an array whose items are
	 * JSON strings {"id":123,"label":".."} (aae-query-chips storage format),
	 * plain numerics, or already-decoded arrays.
	 */
	private static function extract_ids( $items ): array {
		if ( ! is_array( $items ) ) {
			return [];
		}
		$ids = [];
		foreach ( $items as $item ) {
			if ( is_numeric( $item ) ) {
				$ids[] = (int) $item;
				continue;
			}
			if ( is_string( $item ) ) {
				$decoded = json_decode( $item, true );
				if ( is_array( $decoded ) && isset( $decoded['id'] ) ) {
					$ids[] = (int) $decoded['id'];
				}
				continue;
			}
			if ( is_array( $item ) && isset( $item['id'] ) ) {
				$ids[] = (int) ( is_array( $item['id'] ) ? ( $item['id']['value'] ?? 0 ) : $item['id'] );
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/** Return the value if it's a valid Y-m-d date string, else null. */
	private static function valid_date( $value ): ?string {
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		$date = date_create_from_format( 'Y-m-d', $value );
		return $date ? $value : null;
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
