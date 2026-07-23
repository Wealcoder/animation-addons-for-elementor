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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-aae-query-chips-control.php';
require_once __DIR__ . '/class-aae-a-loop-layout.php';
require_once __DIR__ . '/class-aae-a-loop-item.php';
require_once __DIR__ . '/class-aae-a-loop-pagination.php';
require_once __DIR__ . '/class-aae-a-loop-prev.php';
require_once __DIR__ . '/class-aae-a-loop-next.php';
require_once __DIR__ . '/../PostImage/class-aae-a-post-image.php';
require_once __DIR__ . '/../PostTitle/class-aae-a-post-title.php';

use WCF_ADDONS\AtomicWidgets\Widgets\PostImage\AAE_A_Post_Image;
use WCF_ADDONS\AtomicWidgets\Widgets\PostTitle\AAE_A_Post_Title;

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
			// Skip the first N matches. Hidden for Current Query (the archive
			// defines its own query); applies to post types AND Related.
			'offset'         => Number_Prop_Type::make()->default( 0 ),

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

		// The manual filters don't apply to the special sources: Related builds
		// its own term query and Current Query inherits the archive's. Hidden
		// via 'nin' — the 4.1.x client evaluator applies the hide when the
		// actual value IS in the list (see the tax-prop note above).
		$special_hide = Dependency_Manager::make()
			->where( [
				'operator' => 'nin',
				'path'     => [ 'post_type' ],
				'value'    => [ 'related', 'current_query' ],
				'effect'   => 'hide',
			] )
			->get();

		// Offset stays available for Related (unlike the manual filters) but
		// not for Current Query.
		$schema['offset']->set_dependencies(
			Dependency_Manager::make()
				->where( [
					'operator' => 'nin',
					'path'     => [ 'post_type' ],
					'value'    => [ 'current_query' ],
					'effect'   => 'hide',
				] )
				->get()
		);

		$schema['include_posts']       = String_Array_Prop_Type::make()->default( [] )->set_dependencies( $special_hide );
		$schema['exclude_posts']       = String_Array_Prop_Type::make()->default( [] )->set_dependencies( $special_hide );
		$schema['date_range']          = Date_Range_Prop_Type::make()->set_dependencies( $special_hide );
		$schema['meta_key_exists']     = String_Prop_Type::make()->default( '' )->set_dependencies( $special_hide );
		$schema['only_featured_image'] = Boolean_Prop_Type::make()->default( false )->set_dependencies( $special_hide );
		$schema['sticky_first']        = Boolean_Prop_Type::make()->default( false )->set_dependencies( $special_hide );

		// Related-source options: which taxonomy relates posts. Visible only
		// while Source = Related Posts ('in' + hide = hidden unless in list).
		$schema['related_by'] = String_Prop_Type::make()
			->default( 'both' )
			->set_dependencies(
				Dependency_Manager::make()
					->where( [
						'operator' => 'in',
						'path'     => [ 'post_type' ],
						'value'    => [ 'related' ],
						'effect'   => 'hide',
					] )
					->get()
			);

		return $schema;
	}

	/**
	 * Public taxonomies offered as query filters. post_format rides the same
	 * per-taxonomy chips system (its object_type ['post'] makes the control
	 * show only when Source = Posts), but only when the active theme actually
	 * supports post formats — core registers the taxonomy unconditionally, so
	 * without theme support it would be an always-empty control.
	 *
	 * @return array<string, \WP_Taxonomy>
	 */
	public static function get_query_taxonomies(): array {
		$taxes = get_taxonomies( [ 'public' => true, 'show_ui' => true ], 'objects' );

		if ( isset( $taxes['post_format'] ) && ! current_theme_supports( 'post-formats' ) ) {
			unset( $taxes['post_format'] );
		}

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

					Select_Control::bind_to( 'related_by' )
						->set_label( __( 'Related By', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'both',     'label' => __( 'Categories & Tags', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'category', 'label' => __( 'Categories', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'post_tag', 'label' => __( 'Tags', 'animation-addons-for-elementor' ) ],
						] ),

					Number_Control::bind_to( 'posts_per_page' )
						->set_label( __( 'Posts Per Page', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 50 ),

					Number_Control::bind_to( 'offset' )
						->set_label( __( 'Offset', 'animation-addons-for-elementor' ) )
						->set_min( 0 ),

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

		$items[] = Switch_Control::bind_to( 'sticky_first' )
			->set_label( __( 'Sticky Posts First', 'animation-addons-for-elementor' ) );

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

		// Special sources (not post types):
		//   related       — posts sharing terms with the current post (single templates)
		//   current_query — inherit the page's main query (archive templates)
		$options[] = [ 'value' => 'related',       'label' => __( 'Related Posts', 'animation-addons-for-elementor' ) ];
		$options[] = [ 'value' => 'current_query', 'label' => __( 'Current Query (Archive)', 'animation-addons-for-elementor' ) ];

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
	protected static function type_registered( string $type ): bool {
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
			$max_pages = self::compute_max_pages( (array) $this->get_data( 'settings' ), $query_args );
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
						// Current Query source: capture the archive's query vars
						// at render time — the pagination JS posts them back so
						// the AJAX request (which has no archive context) can
						// rebuild the same query.
						'qv'        => ( 'current_query' === ( $s['post_type'] ?? '' ) ) ? self::current_query_vars() : null,
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

		// Special sources — not post types. Related builds its own term query
		// from the current post; Current Query inherits the page's main
		// (archive) query. Both skip the manual filters below (their controls
		// are hidden for these sources too).
		if ( 'related' === $post_type ) {
			return self::build_related_query_args( $s, $paged );
		}
		if ( 'current_query' === $post_type ) {
			return self::build_current_query_args( $s, $paged );
		}

		$args = [
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => self::sanitize_per_page( $s ),
			'orderby'             => self::sanitize_order_by( $s ),
			'order'               => self::sanitize_order( $s ),
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

		// Specific posts. Include stays SOURCE-scoped — the panel's include
		// search only offers posts of the selected Source type, and the query
		// keeps that post_type too, so every filter follows the Source.
		$include = self::extract_ids( $s['include_posts'] ?? null );
		if ( $include ) {
			$args['post__in'] = $include;
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

		// Offset — skip the first N matches. WP_Query ignores `paged` once
		// `offset` is set, so fold the page into a combined offset. The count
		// correction lives in compute_max_pages().
		$offset = self::sanitize_offset( $s );
		if ( $offset ) {
			$args['offset'] = $offset + ( max( 1, $paged ) - 1 ) * $args['posts_per_page'];
		}

		// Sticky posts first. WP_Query's own sticky handling only applies to
		// the main home query, never to a secondary query like this one — so
		// we pin manually: pre-resolve the FULL matching id list (stickies that
		// match the filters first, then the rest in the chosen order) and turn
		// the query into `post__in` + `orderby: post__in`. Pagination then
		// flows naturally across the pinned list. Runs LAST so the id
		// pre-query carries every filter above (tax/include/date/meta).
		if ( ! empty( $s['sticky_first'] ) ) {
			$sticky = array_map( 'intval', (array) get_option( 'sticky_posts', [] ) );
			if ( $sticky ) {
				$id_args = array_merge( $args, [
					'posts_per_page' => -1,
					'paged'          => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				] );
				// The final paged query applies the offset — the id list must
				// stay complete or the skip would happen twice.
				unset( $id_args['offset'] );
				$all_ids = ( new \WP_Query( $id_args ) )->posts;

				$pinned = array_values( array_intersect( $sticky, $all_ids ) );
				if ( $pinned ) {
					$rest             = array_values( array_diff( $all_ids, $sticky ) );
					$args['post__in'] = array_merge( $pinned, $rest );
					$args['orderby']  = 'post__in';
					unset( $args['order'] );
				}
			}
		}

		return $args;
	}

	private static function sanitize_per_page( array $s ): int {
		return max( 1, (int) ( $s['posts_per_page'] ?? 6 ) );
	}

	private static function sanitize_order_by( array $s ): string {
		$order_by = isset( $s['order_by'] ) && is_string( $s['order_by'] ) ? $s['order_by'] : 'date';
		return in_array( $order_by, [ 'date', 'title', 'menu_order', 'rand', 'ID', 'modified' ], true ) ? $order_by : 'date';
	}

	private static function sanitize_order( array $s ): string {
		return ( isset( $s['order'] ) && 'asc' === strtolower( (string) $s['order'] ) ) ? 'ASC' : 'DESC';
	}

	/** User offset — 0 for Current Query (the archive defines its own query). */
	private static function sanitize_offset( array $s ): int {
		if ( 'current_query' === ( $s['post_type'] ?? '' ) ) {
			return 0;
		}
		return max( 0, (int) ( $s['offset'] ?? 0 ) );
	}

	/**
	 * Total pages for a built query — the ONE place the count is computed.
	 *
	 * Needed because WP_Query's own max_num_pages is wrong once `offset` is in
	 * play: found_posts ignores LIMIT, so it never subtracts the user's skip.
	 * Count WITHOUT the offset, subtract it, divide by per_page.
	 */
	public static function compute_max_pages( array $raw_settings, array $query_args ): int {
		$s        = self::unwrap_settings( $raw_settings );
		$offset   = self::sanitize_offset( $s );
		$per_page = max( 1, (int) ( $query_args['posts_per_page'] ?? 6 ) );

		$count_args = array_merge( $query_args, [
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'paged'          => 1,
			'no_found_rows'  => false,
		] );
		unset( $count_args['offset'] );

		$count = new \WP_Query( $count_args );
		$total = max( 0, (int) $count->found_posts - $offset );
		wp_reset_postdata();

		return max( 1, (int) ceil( $total / $per_page ) );
	}

	/**
	 * The post the Related source relates FROM. Callers running outside a real
	 * frontend request (AJAX pagination, editor preview) inject it as the
	 * internal `_context_post_id` setting; a live singular page resolves from
	 * the main query.
	 */
	private static function resolve_context_post_id( array $s ): int {
		if ( ! empty( $s['_context_post_id'] ) ) {
			return (int) $s['_context_post_id'];
		}
		if ( is_singular() ) {
			return (int) get_queried_object_id();
		}
		return 0;
	}

	/**
	 * Source: Related Posts — same post type as the current post, sharing at
	 * least one term (categories/tags/both per `related_by`), current post
	 * excluded. No context or no shared terms -> recent posts of that type,
	 * so the grid never renders empty on a valid page.
	 */
	private static function build_related_query_args( array $s, int $paged ): array {
		$current_id = self::resolve_context_post_id( $s );
		$current    = $current_id ? get_post( $current_id ) : null;

		$args = [
			'post_type'           => $current ? $current->post_type : 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => self::sanitize_per_page( $s ),
			'orderby'             => self::sanitize_order_by( $s ),
			'order'               => self::sanitize_order( $s ),
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,
		];

		if ( ! $current ) {
			$offset = self::sanitize_offset( $s );
			if ( $offset ) {
				$args['offset'] = $offset + ( max( 1, $paged ) - 1 ) * $args['posts_per_page'];
			}
			return $args;
		}

		$args['post__not_in'] = [ $current->ID ];

		$related_by = isset( $s['related_by'] ) && is_string( $s['related_by'] ) ? $s['related_by'] : 'both';
		if ( ! in_array( $related_by, [ 'both', 'category', 'post_tag' ], true ) ) {
			$related_by = 'both';
		}

		if ( 'both' === $related_by ) {
			// Every taxonomy of the post's type (covers CPT taxonomies too);
			// formats aren't a relatedness signal.
			$taxonomies = array_diff( get_object_taxonomies( $current->post_type ), [ 'post_format' ] );
		} else {
			$taxonomies = [ $related_by ];
		}

		$tax_query = [];
		foreach ( $taxonomies as $tax ) {
			$term_ids = wp_get_object_terms( $current->ID, $tax, [ 'fields' => 'ids' ] );
			if ( ! is_wp_error( $term_ids ) && $term_ids ) {
				$tax_query[] = [
					'taxonomy' => $tax,
					'field'    => 'term_id',
					'terms'    => array_map( 'intval', $term_ids ),
				];
			}
		}
		if ( $tax_query ) {
			$tax_query['relation'] = 'OR';
			$args['tax_query']     = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		$offset = self::sanitize_offset( $s );
		if ( $offset ) {
			$args['offset'] = $offset + ( max( 1, $paged ) - 1 ) * $args['posts_per_page'];
		}

		return $args;
	}

	/**
	 * Source: Current Query — inherit the page's main query (category / tag /
	 * author / date / search / custom-tax archives). AJAX pagination posts the
	 * archive's query vars back (`_qv`, captured into the pagination config at
	 * render time) since admin-ajax has no archive context of its own. Outside
	 * any archive (editor preview, a regular page) -> recent posts fallback.
	 */
	private static function build_current_query_args( array $s, int $paged ): array {
		$overrides = [
			'post_status'         => 'publish',
			'posts_per_page'      => self::sanitize_per_page( $s ),
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,
		];

		$vars = ( isset( $s['_qv'] ) && is_array( $s['_qv'] ) )
			? self::sanitize_query_vars( $s['_qv'] )
			: self::current_query_vars();

		if ( ! $vars ) {
			return array_merge(
				[
					'post_type' => 'post',
					'orderby'   => 'date',
					'order'     => 'DESC',
				],
				$overrides
			);
		}

		return array_merge( $vars, $overrides );
	}

	/**
	 * The main query's ORIGINAL parsed vars ($wp_query->query — compact, e.g.
	 * ['category_name' => 'design']) when the request is an archive-like page.
	 */
	public static function current_query_vars(): array {
		global $wp_query;

		if ( ! $wp_query instanceof \WP_Query ) {
			return [];
		}
		if ( ! ( $wp_query->is_archive() || $wp_query->is_home() || $wp_query->is_search() ) ) {
			return [];
		}

		return self::sanitize_query_vars( (array) $wp_query->query );
	}

	/**
	 * Whitelist + sanitize main-query vars so a posted-back `_qv` blob can
	 * never smuggle arbitrary WP_Query args (meta_query, post_status…).
	 */
	private static function sanitize_query_vars( array $vars ): array {
		$allowed = [
			'cat'           => 'int',
			'category_name' => 'string',
			'tag'           => 'string',
			'tag_id'        => 'int',
			's'             => 'string',
			'author'        => 'int',
			'author_name'   => 'string',
			'year'          => 'int',
			'monthnum'      => 'int',
			'day'           => 'int',
		];

		$out = [];
		foreach ( $allowed as $key => $type ) {
			if ( ! isset( $vars[ $key ] ) || '' === $vars[ $key ] ) {
				continue;
			}
			$out[ $key ] = 'int' === $type ? (int) $vars[ $key ] : sanitize_text_field( (string) $vars[ $key ] );
		}

		// Post type: string or array of registered public types only.
		if ( ! empty( $vars['post_type'] ) ) {
			$public = array_keys( get_post_types( [ 'public' => true ] ) );
			$types  = array_values( array_intersect( array_map( 'sanitize_key', (array) $vars['post_type'] ), $public ) );
			if ( $types ) {
				$out['post_type'] = $types;
			}
		}

		// Custom taxonomy archives arrive as { <taxonomy>: <term-slug> }.
		foreach ( get_taxonomies( [ 'public' => true ] ) as $tax ) {
			if ( isset( $vars[ $tax ] ) && is_string( $vars[ $tax ] ) && '' !== $vars[ $tax ] && ! isset( $out[ $tax ] ) ) {
				$out[ $tax ] = sanitize_text_field( $vars[ $tax ] );
			}
		}

		return $out;
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
