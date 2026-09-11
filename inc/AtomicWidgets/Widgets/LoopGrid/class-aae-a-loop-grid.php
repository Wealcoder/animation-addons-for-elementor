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
require_once __DIR__ . '/class-loop-filter-auth.php';
require_once __DIR__ . '/class-loop-query-woo.php';
require_once __DIR__ . '/../../Controls/class-aae-notice-control.php';
require_once __DIR__ . '/class-aae-a-loop-layout.php';
require_once __DIR__ . '/class-aae-a-loop-item.php';
require_once __DIR__ . '/class-aae-a-loop-pagination.php';
require_once __DIR__ . '/class-aae-a-loop-prev.php';
require_once __DIR__ . '/class-aae-a-loop-next.php';
require_once __DIR__ . '/../PostImage/class-aae-a-post-image.php';
require_once __DIR__ . '/../PostTitle/class-aae-a-post-title.php';

use WCF_ADDONS\AtomicWidgets\Widgets\PostImage\AAE_A_Post_Image;
use WCF_ADDONS\AtomicWidgets\Widgets\PostTitle\AAE_A_Post_Title;
use WCF_ADDONS\AtomicWidgets\Controls\AAE_Notice_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Grid extends Atomic_Element_Base {
	use Has_Element_Template;

	/**
	 * Every taxonomy slug the schema has EVER built a `tax_*` prop for, with the
	 * label and object types it had — so the prop survives the taxonomy's plugin
	 * being switched off. See known_taxonomies().
	 */
	public const KNOWN_TAXONOMIES_OPTION = 'aae_loop_grid_known_taxonomies';

	/**
	 * The element types that ARE a loop grid.
	 *
	 * The slider extends this class and carries its own type, so anything
	 * looking for "the grid on this page" has to accept both — matching the
	 * plain one alone is how a slider silently reports no grid at all.
	 */
	public const GRID_TYPES = [ 'e-aae-a-loop-grid', 'e-aae-a-loop-grid-slider' ];

	/** Per-request memo for get_query_taxonomies(). */
	private static ?array $taxonomy_memo = null;

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
		return esc_html__( 'Loop Grid', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-loop-builder';
	}

	public function get_keywords() {
		return [ 'loop', 'grid', 'posts', 'query', 'template', 'atomic', 'dynamic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-post'];
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
		// Authors — the same chips shape, `user` kind: {"id":<user id>,"label":"Name"}.
		$schema['authors']             = String_Array_Prop_Type::make()->default( [] )->set_dependencies( $special_hide );
		$schema['exclude_authors']     = String_Array_Prop_Type::make()->default( [] )->set_dependencies( $special_hide );
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
	 * Taxonomies offered as query filters — one `tax_<slug>` prop each.
	 *
	 * Three sources, unioned:
	 *
	 *  1. Every public taxonomy with a UI. post_format rides the same system
	 *     (its object_type ['post'] makes the control show only when Source =
	 *     Posts), but only when the active theme actually supports post formats
	 *     — core registers the taxonomy unconditionally, so without theme
	 *     support it would be an always-empty control.
	 *  2. WooCommerce attribute taxonomies (`pa_color`, …). WC registers them
	 *     `public => false` unless "Enable archives" is ticked, so (1) never
	 *     sees them — and Color/Size are the filters a shop needs most.
	 *  3. The KNOWN-TAXONOMY RATCHET: every slug the schema has ever built a
	 *     prop for, persisted in KNOWN_TAXONOMIES_OPTION. A prop the schema does
	 *     not declare is ERASED from `_elementor_data` on the next save
	 *     (Props_Parser::validate()), so before this, switching off the plugin
	 *     that registers `aae_color` and saving any page lost every Color
	 *     filter on every grid — measured, silently. An unregistered taxonomy
	 *     comes back as a stub object flagged `aae_unregistered`; the schema
	 *     keeps its prop (the value survives the save), the panel shows no
	 *     control for it, and the query skips it until the taxonomy returns.
	 *     Evidence only ever ADDS to the option — same ratchet shape as
	 *     `legacy_v3` and maybe_enable_used_v3_widgets().
	 *
	 * Filterable through `aae/loop_grid/query_taxonomies` for a taxonomy that
	 * is deliberately non-public but should still be offered.
	 *
	 * @return array<string, \WP_Taxonomy|object>
	 */
	public static function get_query_taxonomies(): array {
		if ( null !== self::$taxonomy_memo ) {
			return self::$taxonomy_memo;
		}

		$taxes = get_taxonomies( [ 'public' => true, 'show_ui' => true ], 'objects' );

		if ( isset( $taxes['post_format'] ) && ! current_theme_supports( 'post-formats' ) ) {
			unset( $taxes['post_format'] );
		}

		foreach ( Loop_Query_Woo::attribute_taxonomies() as $name => $tax ) {
			$taxes[ $name ] = $tax;
		}

		/**
		 * @param array<string, \WP_Taxonomy> $taxes Registered taxonomies offered as filters.
		 */
		$taxes = (array) apply_filters( 'aae/loop_grid/query_taxonomies', $taxes );
		$taxes = array_filter( $taxes, static fn( $t ) => is_object( $t ) && ! empty( $t->name ) && taxonomy_exists( $t->name ) );

		// The ratchet: remember what we saw, restore what we no longer see.
		$known   = self::known_taxonomies();
		$changed = false;
		foreach ( $taxes as $name => $tax ) {
			$entry = [
				'label'       => (string) ( $tax->label ?? $name ),
				'object_type' => array_values( array_map( 'strval', (array) ( $tax->object_type ?? [] ) ) ),
			];
			$prev = $known[ $name ] ?? null;
			// Compare the object_type ONLY. `label` is display-only — it is read
			// back just to name a stub whose taxonomy is gone — and it is
			// LOCALISED, so comparing it turns every request served in a
			// different language into an update_option(): a bilingual site would
			// write this row on every anonymous page view that holds a grid,
			// flip-flopping between the two spellings forever.
			if ( null === $prev || ( $prev['object_type'] ?? null ) !== $entry['object_type'] ) {
				$known[ $name ] = $entry;
				$changed        = true;
			}
		}
		// Only ever write from a context where a filter could actually be SAVED.
		// The evidence exists to keep a saved `tax_*` prop declared, and a prop
		// can only be saved through the editor, which is an admin request — so a
		// visitor's page view has nothing to contribute and should not be paying
		// for a DB write.
		if ( $changed && self::may_record_taxonomies() ) {
			update_option( self::KNOWN_TAXONOMIES_OPTION, $known, false );
		}
		foreach ( $known as $name => $entry ) {
			if ( isset( $taxes[ $name ] ) ) {
				continue;
			}
			$taxes[ $name ] = (object) [
				'name'             => $name,
				'label'            => (string) ( $entry['label'] ?? $name ),
				'object_type'      => (array) ( $entry['object_type'] ?? [] ),
				'aae_unregistered' => true,
			];
		}

		self::$taxonomy_memo = $taxes;
		return $taxes;
	}

	/**
	 * The persisted ratchet: slug => [ label, object_type ].
	 *
	 * Never pruned automatically — that is the point. The one escape hatch is
	 * the filter, for a site that knows a slug is gone for good (a renamed
	 * taxonomy, a test run that aborted before restoring the option):
	 *
	 *   add_filter( 'aae/loop_grid/known_taxonomies', fn( $k ) => array_diff_key( $k, [ 'old_slug' => 1 ] ) );
	 */
	public static function known_taxonomies(): array {
		$known = get_option( self::KNOWN_TAXONOMIES_OPTION, [] );
		$known = is_array( $known ) ? $known : [];
		return (array) apply_filters( 'aae/loop_grid/known_taxonomies', $known );
	}

	/** Forget the per-request taxonomy memo (a test registering a taxonomy mid-run). */
	public static function flush_taxonomy_memo(): void {
		self::$taxonomy_memo = null;
	}

	/** Is this a request in which the ratchet may record what it sees? */
	private static function may_record_taxonomies(): bool {
		if ( is_admin() ) {
			return true;
		}
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$editor = \Elementor\Plugin::$instance->editor ?? null;
			if ( $editor && method_exists( $editor, 'is_edit_mode' ) && $editor->is_edit_mode() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Prune remembered taxonomies that are BOTH unregistered and unreferenced.
	 *
	 * Deliberately not a "clear the option" button. This row is not a cache: it
	 * is the evidence that keeps a `tax_<slug>` prop declared while its taxonomy
	 * is away, and Props_Parser::validate() erases any prop the schema does not
	 * declare on the next save — so dropping a slug some page still references
	 * destroys that page's saved filter, silently, which is the exact bug the
	 * ratchet was written to prevent. A slug is therefore only forgotten when
	 * its taxonomy is gone AND no saved document mentions its prop.
	 *
	 * @return array{removed: string[], kept_registered: string[], kept_in_use: string[]}
	 */
	public static function forget_unused_taxonomies(): array {
		global $wpdb;

		$known   = self::known_taxonomies();
		$removed = [];
		$live    = [];
		$in_use  = [];

		foreach ( array_keys( $known ) as $name ) {
			$name = (string) $name;
			if ( taxonomy_exists( $name ) ) {
				$live[] = $name;
				continue;
			}

			$needle = '"' . self::tax_prop_name( $name ) . '"';
			$hit    = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE %s LIMIT 1",
					'%' . $wpdb->esc_like( $needle ) . '%'
				)
			);

			if ( $hit ) {
				$in_use[] = $name;
				continue;
			}

			unset( $known[ $name ] );
			$removed[] = $name;
		}

		if ( $removed ) {
			update_option( self::KNOWN_TAXONOMIES_OPTION, $known, false );
			self::flush_taxonomy_memo();
		}

		return [
			'removed'         => $removed,
			'kept_registered' => $live,
			'kept_in_use'     => $in_use,
		];
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
						->set_max( $this->per_page_max() ),

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

			// No set_description(): the installed editing panel (Elementor 4.2.x)
			// does not render a section description — measured, the string never
			// reaches the DOM. Builder-facing copy goes through the notice card
			// at the top of the section instead (get_filter_controls()).
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

		// Instance-specific copy ("Color is not registered right now", "no
		// taxonomy for this post type") — resolved in the panel from the
		// element's own post_type against the map class-atomic.php localizes as
		// AAE_LOOP_GRID.notices. Renders nothing when there is nothing to say.
		$items[] = AAE_Notice_Control::make()->set_source( 'loop-grid-taxonomies' );

		foreach ( self::get_query_taxonomies() as $tax ) {
			if ( ! empty( $tax->aae_unregistered ) ) {
				continue; // Prop kept (ratchet), control withheld — nothing to search.
			}
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

		$items[] = AAE_Query_Chips_Control::bind_to( 'authors' )
			->set_label( __( 'Authors', 'animation-addons-for-elementor' ) )
			->set_kind( 'user' )
			->set_placeholder( __( 'Search by name…', 'animation-addons-for-elementor' ) );

		$items[] = AAE_Query_Chips_Control::bind_to( 'exclude_authors' )
			->set_label( __( 'Exclude Authors', 'animation-addons-for-elementor' ) )
			->set_kind( 'user' )
			->set_placeholder( __( 'Search by name…', 'animation-addons-for-elementor' ) );

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

		$per_page  = isset( $s['posts_per_page'] ) ? (int) $s['posts_per_page'] : 6;
		$paged     = self::current_page();
		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
		$post_type = self::effective_post_type( $s );

		// Visitor filters off the URL, authorised against the filter widgets the
		// SAVED document declares for this grid. The editor never filters: the
		// canvas is for styling, and a stale ?aae_* on the editor URL would hide
		// the builder's own cards.
		$document_id = self::current_document_id();
		$filters     = [];
		if ( ! $is_editor && $document_id ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter state, same footing as aae_page.
			$filters = Loop_Filter_Auth::current( $document_id, $this->get_id(), $post_type, (array) $_GET );
		}

		// Build from the RAW ($$type-wrapped) settings via the shared builder —
		// the same code path the AJAX pagination and the editor preview use, so
		// all three always agree on the filters.
		$query_args = self::build_query_args( (array) $this->get_data( 'settings' ), $paged, $filters );

		// Resolve the total once so the Pagination child can render the right
		// number of page links without re-querying.
		// Only pay for the count when something will actually display it. The
		// count is a second SQL_CALC_FOUND_ROWS scan of the same result set,
		// and a grid with no pagination child has nothing to do with the
		// answer — it was previously run on every frontend render regardless.
		// A filtered render always counts: the AJAX response carries `total`
		// for the Result Count widget, and the first paint must match it.
		$total     = null;
		$max_pages = 1;
		$primed = $is_editor ? null : self::peek_summary( $document_id, $this->get_id() );
		if ( $primed ) {
			// A readout ABOVE the grid has already asked, and it computed from
			// these same saved settings through this same builder. Re-counting
			// would only risk the two disagreeing.
			$total     = (int) $primed['total'];
			$max_pages = (int) $primed['max_pages'];
		} elseif ( ! $is_editor && ( $filters || $this->has_pagination_child() || $this->has_readout_sibling( $document_id ) ) ) {
			$total     = self::count_total( (array) $this->get_data( 'settings' ), $query_args );
			$max_pages = self::pages_for_total( $total, $query_args );

			// A Result Count sitting BELOW the grid then costs nothing — and one
			// sitting above has already primed this, so the two always show the
			// same number.
			self::prime_summary(
				$document_id,
				$this->get_id(),
				[
					'grid_id'   => $this->get_id(),
					'post_type' => $post_type,
					'filters'   => $filters['active'] ?? [],
					'total'     => $total,
					'max_pages' => $max_pages,
					'paged'     => $paged,
					'per_page'  => max( 1, (int) ( $query_args['posts_per_page'] ?? 6 ) ),
				]
			);
		}

		return [
			[
				'context_key' => self::class,
				'context'     => [
					'query_args'    => $query_args,
					'paged'         => $paged,
					'max_num_pages' => $max_pages,
					'total'         => $total,
					// Active visitor filters (url_key => value) — the pagination
					// posts them back so a page change keeps the filter, and the
					// Active Filters / Result Count widgets read them.
					'filters'       => $filters['active'] ?? [],
					'document_id'   => $document_id,
					// The VIEWED post, which is not the document once a grid
					// sits in a theme-builder template. The Related source
					// anchors relatedness on this one; the AJAX handler needs
					// the document to find the grid, so both have to travel.
					'context_id'    => (int) get_the_ID(),
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
	 * Everything a browser needs to ask the server for this grid again.
	 *
	 * Published on the grid ITSELF as well as on the Pagination child, because
	 * a grid that carries a filter but no pagination is an ordinary shape — a
	 * six-item portfolio with a category filter and no second page — and until
	 * this existed, such a grid had no identity anywhere in the DOM and nothing
	 * could address it. One builder for both so the two can never describe the
	 * same grid differently.
	 *
	 * Carries no secrets: the nonce is the front-end read nonce the endpoint
	 * already checks, and every value is something the page just rendered from.
	 *
	 * @param array $ctx The Loop Grid render context.
	 * @return array<string, mixed>
	 */
	public static function endpoint_config( array $ctx ): array {
		return [
			'grid'      => isset( $ctx['grid_id'] ) ? (string) $ctx['grid_id'] : '',
			// The document whose saved data DECLARES this grid — not the post
			// being read, which is a different thing inside a theme-builder
			// template and is carried separately.
			'postId'    => ! empty( $ctx['document_id'] ) ? (int) $ctx['document_id'] : (int) get_the_ID(),
			'contextId' => ! empty( $ctx['context_id'] ) ? (int) $ctx['context_id'] : (int) get_the_ID(),
			'query'     => isset( $ctx['query'] ) ? $ctx['query'] : [],
			// (object) so an empty map encodes as {} — JS reads it as an object.
			'filters'   => (object) ( isset( $ctx['filters'] ) && is_array( $ctx['filters'] ) ? $ctx['filters'] : [] ),
			'nonce'     => wp_create_nonce( 'aae_loop_grid_front' ),
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		];
	}

	/* ------------------------------------------------------------------ */
	/* What a SIBLING widget can learn about a grid                        */
	/* ------------------------------------------------------------------ */

	/**
	 * Per-request summary cache: "<document id>|<grid id>" => summary.
	 *
	 * @var array<string, array>
	 */
	private static array $summaries = [];

	/** Facet counts, memoised per grid and per EXCLUDED filter key. */
	private static array $facets = [];

	/**
	 * The largest result set a facet count will resolve to an id list for.
	 *
	 * Above it `facet_counts()` answers null — no count at all — rather than
	 * loading tens of thousands of ids to draw a sidebar. A ceiling that
	 * refuses is honest; one that silently falls back to the site-wide term
	 * counts would change what the numbers MEAN halfway up a collection, which
	 * nobody would ever notice.
	 */
	public const MAX_FACET_SCOPE = 3000;

	/**
	 * What a widget standing OUTSIDE the grid can know about it — the filtered
	 * total, the page count, and which filters are active.
	 *
	 * The Render_Context stack cannot answer this: it reaches DESCENDANTS only,
	 * and a Result Count or an Active Filters bar is a sibling — usually ABOVE
	 * the grid, so it renders before the grid has computed anything at all.
	 *
	 * Both paths therefore go through one memo. The grid primes it from
	 * define_render_context() with the numbers it already paid for, and a
	 * sibling that asks first computes them from the SAME saved settings
	 * through the SAME builder, so whichever runs first, the readout and the
	 * grid can never disagree — and the count is paid for at most once per
	 * request per grid.
	 *
	 * @return array{grid_id: string, post_type: string, filters: array, total: int, max_pages: int, paged: int, per_page: int}|null
	 */
	public static function summary_for( int $document_id, string $grid_id ): ?array {
		if ( $document_id < 1 || '' === $grid_id ) {
			return null;
		}

		$memo_key = $document_id . '|' . $grid_id;
		if ( array_key_exists( $memo_key, self::$summaries ) ) {
			return self::$summaries[ $memo_key ];
		}

		$raw = self::grid_settings_in_document( $document_id, $grid_id );
		if ( null === $raw ) {
			self::$summaries[ $memo_key ] = null;
			return null;
		}

		$post_type = self::effective_post_type( $raw );
		$paged     = self::current_page();

		// request_args(), not $_GET: on an AJAX re-render the live request is
		// admin-ajax.php and carries none of the visitor's filter state, and the
		// endpoint has already told the authoriser which page it is rendering.
		// It is ALWAYS unslashed, hence $slashed = false.
		$filters = Loop_Filter_Auth::current(
			$document_id,
			$grid_id,
			$post_type,
			Loop_Filter_Auth::request_args(),
			false
		);

		$query_args = self::build_query_args( $raw, $paged, $filters );
		$total      = self::count_total( $raw, $query_args );

		$summary = [
			'grid_id'   => $grid_id,
			'post_type' => $post_type,
			'filters'   => $filters['active'] ?? [],
			'total'     => $total,
			'max_pages' => self::pages_for_total( $total, $query_args ),
			'paged'     => $paged,
			'per_page'  => max( 1, (int) ( $query_args['posts_per_page'] ?? 6 ) ),
		];

		self::$summaries[ $memo_key ] = $summary;
		return $summary;
	}

	/**
	 * How many results each option of ONE filter would leave, given every
	 * OTHER filter currently applied.
	 *
	 * This is the difference between a count that helps and a count that lies.
	 * A taxonomy term's own `count` is site-wide: it says how many posts carry
	 * the term anywhere, not how many are in this grid, and certainly not how
	 * many survive the three filters the visitor has already set. "Footwear
	 * (12)" beside a result set of four is not a number anyone can act on.
	 *
	 * The rule is the standard one for faceted search and it is worth stating
	 * because it looks like a bug until you know it: a filter's own key is
	 * EXCLUDED from the scope it counts against. Otherwise picking Footwear
	 * would drop every other category to zero — each option has to answer "what
	 * if I picked this instead", not "what is true now".
	 *
	 * COST, and why this is opt-in. It resolves the scope to an id list, which
	 * is a full query with no LIMIT, then one grouped count over it — per filter
	 * widget, per request. That is affordable on the collections these grids are
	 * usually pointed at and is not affordable on a very large one, so it is off
	 * unless a builder asks, and `MAX_FACET_SCOPE` is a hard ceiling above which
	 * this returns NULL rather than an answer.
	 *
	 * NULL means "not answerable", never zero. A widget must render no count at
	 * all rather than a confident 0 — the same distinction the extension usage
	 * scan draws, and for the same reason: absent is a fact, zero is a claim.
	 *
	 * @param array $decl One declaration from Loop_Filter_Auth.
	 * @return array<string, int>|null value => count, keyed by the slug or
	 *                                 nicename the filter puts in the URL.
	 */
	public static function facet_counts( int $document_id, string $grid_id, array $decl ): ?array {
		$type = (string) ( $decl['type'] ?? '' );
		if ( ! in_array( $type, [ 'tax', 'author' ], true ) ) {
			// A range, a search and a sort have no enumerable options to count.
			return null;
		}

		$key      = (string) ( $decl['url_key'] ?? '' );
		$memo_key = $document_id . '|' . $grid_id . '|' . $key;
		if ( array_key_exists( $memo_key, self::$facets ) ) {
			return self::$facets[ $memo_key ];
		}
		self::$facets[ $memo_key ] = null;

		$ids = self::facet_scope_ids( $document_id, $grid_id, $key );
		if ( null === $ids ) {
			return null;
		}
		if ( ! $ids ) {
			// A genuinely empty scope IS answerable: every option would leave
			// nothing, and saying so is more useful than saying nothing.
			self::$facets[ $memo_key ] = [];
			return [];
		}

		$counts = 'tax' === $type
			? self::count_by_term( $ids, (string) $decl['taxonomy'] )
			: self::count_by_author( $ids );

		self::$facets[ $memo_key ] = $counts;
		return $counts;
	}

	/**
	 * The post ids this grid would show with every filter applied EXCEPT one.
	 *
	 * @return int[]|null Null when the scope is too large to count against.
	 */
	private static function facet_scope_ids( int $document_id, string $grid_id, string $except_key ): ?array {
		$raw = self::grid_settings_in_document( $document_id, $grid_id );
		if ( null === $raw ) {
			return null;
		}

		$post_type = self::effective_post_type( $raw );
		$args      = Loop_Filter_Auth::request_args();
		unset( $args[ $except_key ] );

		$filters = Loop_Filter_Auth::current( $document_id, $grid_id, $post_type, $args, false );

		$query_args                   = self::build_query_args( $raw, 1, $filters );
		$query_args['fields']         = 'ids';
		$query_args['posts_per_page'] = self::MAX_FACET_SCOPE + 1;
		$query_args['no_found_rows']  = true;
		// A visitor-chosen sort cannot change WHICH posts match, only their
		// order, and ordering a list we are about to count is pure cost.
		unset( $query_args['orderby'], $query_args['order'], $query_args['meta_key'] );

		$ids = get_posts( $query_args );
		if ( ! is_array( $ids ) ) {
			return null;
		}

		// Over the ceiling: refuse rather than answer slowly. The extra row
		// requested above is what makes "more than the ceiling" detectable
		// without a second count.
		return count( $ids ) > self::MAX_FACET_SCOPE ? null : array_map( 'intval', $ids );
	}

	/**
	 * @param int[] $ids
	 * @return array<string, int> term slug => count
	 */
	private static function count_by_term( array $ids, string $taxonomy ): array {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// One grouped query rather than a count per term: a sidebar with thirty
		// categories would otherwise issue thirty queries to draw one list.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.slug, COUNT(*) AS n
				 FROM {$wpdb->term_relationships} tr
				 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				 INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
				 WHERE tt.taxonomy = %s AND tr.object_id IN ({$placeholders})
				 GROUP BY t.slug",
				array_merge( [ $taxonomy ], $ids )
			)
		);
		// phpcs:enable

		$out = [];
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row->slug ] = (int) $row->n;
		}
		return $out;
	}

	/**
	 * @param int[] $ids
	 * @return array<string, int> user_nicename => count
	 */
	private static function count_by_author( array $ids ): array {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// Keyed by NICENAME, because that is what the author filter puts in the
		// URL — ids are refused on both sides so the endpoint cannot become a
		// user-enumeration oracle, and a count keyed by id would have to be
		// translated back by whoever read it.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.user_nicename AS slug, COUNT(*) AS n
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->users} u ON u.ID = p.post_author
				 WHERE p.ID IN ({$placeholders})
				 GROUP BY u.user_nicename",
				$ids
			)
		);
		// phpcs:enable

		$out = [];
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row->slug ] = (int) $row->n;
		}
		return $out;
	}

	/** Drop the facet memo (tests, or a second render inside one request). */
	public static function reset_facets(): void {
		self::$facets = [];
	}

	/**
	 * Hand the memo the numbers the grid has already computed, so a readout
	 * below it costs nothing. Never overwrites: a summary already in the memo
	 * was built from the same settings and is the one a readout above the grid
	 * has already rendered from.
	 */
	public static function prime_summary( int $document_id, string $grid_id, array $summary ): void {
		if ( $document_id < 1 || '' === $grid_id ) {
			return;
		}
		$memo_key = $document_id . '|' . $grid_id;
		if ( ! array_key_exists( $memo_key, self::$summaries ) ) {
			self::$summaries[ $memo_key ] = $summary;
		}
	}

	/**
	 * The memoised summary for a grid, WITHOUT computing one.
	 *
	 * The grid itself asks this so that a readout rendered above it does not
	 * make the page pay for the same count twice.
	 */
	public static function peek_summary( int $document_id, string $grid_id ): ?array {
		$memo_key = $document_id . '|' . $grid_id;
		return self::$summaries[ $memo_key ] ?? null;
	}

	/** Drop the summary memo (tests, or a second render inside one request). */
	public static function reset_summaries(): void {
		self::$summaries = [];
	}

	/**
	 * Does anything on this page READ this grid's total?
	 *
	 * The count is a second scan of the result set, so it is only paid for when
	 * something will display it. Pagination was the original reason; a Result
	 * Count widget is the other one, and it is usually not a descendant, so
	 * has_pagination_child() cannot see it.
	 */
	private function has_readout_sibling( int $document_id ): bool {
		if ( $document_id < 1 ) {
			return false;
		}

		static $memo = [];
		$key = $document_id . '|' . $this->get_id();
		if ( isset( $memo[ $key ] ) ) {
			return $memo[ $key ];
		}

		$grid_id = $this->get_id();
		$hit     = false;
		$walk    = static function ( $els ) use ( &$walk, &$hit, $grid_id ) {
			foreach ( (array) $els as $el ) {
				if ( $hit || ! is_array( $el ) ) {
					continue;
				}
				$type = (string) ( $el['widgetType'] ?? ( $el['elType'] ?? '' ) );
				if ( Loop_Filter_Auth::TYPE_RESULT_COUNT === $type ) {
					$s      = self::unwrap( (array) ( $el['settings'] ?? [] ) );
					$target = trim( (string) ( $s['target_grid'] ?? '' ) );
					if ( '' === $target || $target === $grid_id ) {
						$hit = true;
						return;
					}
				}
				if ( ! empty( $el['elements'] ) ) {
					$walk( $el['elements'] );
				}
			}
		};

		if ( class_exists( '\Elementor\Plugin' ) ) {
			$doc = \Elementor\Plugin::$instance->documents->get( $document_id );
			if ( $doc ) {
				$walk( (array) $doc->get_elements_data() );
			}
		}

		$memo[ $key ] = $hit;
		return $hit;
	}

	/**
	 * The post type a grid's query really targets, asked from OUTSIDE it.
	 *
	 * Cheaper than summary_for() and answers a different question: a widget that
	 * only needs to know "posts or products" — an Author filter listing the
	 * people who publish this type — should not trigger a count of the result
	 * set to find out.
	 */
	public static function post_type_for( int $document_id, string $grid_id ): string {
		$primed = self::peek_summary( $document_id, $grid_id );
		if ( $primed ) {
			return (string) $primed['post_type'];
		}
		$raw = self::grid_settings_in_document( $document_id, $grid_id );
		return null === $raw ? 'post' : self::effective_post_type( $raw );
	}

	/**
	 * The raw ($$type-wrapped) settings of one Loop Grid inside a saved
	 * document, or null when that id is not a grid on that page.
	 *
	 * @return array|null
	 */
	private static function grid_settings_in_document( int $document_id, string $grid_id ): ?array {
		$found = null;
		$walk  = static function ( $els ) use ( &$walk, &$found, $grid_id ) {
			foreach ( (array) $els as $el ) {
				if ( null !== $found ) {
					return;
				}
				if ( ! is_array( $el ) ) {
					continue;
				}
				$type = (string) ( $el['widgetType'] ?? ( $el['elType'] ?? '' ) );
				if ( (string) ( $el['id'] ?? '' ) === $grid_id
					&& in_array( $type, self::GRID_TYPES, true ) ) {
					$found = (array) ( $el['settings'] ?? [] );
					return;
				}
				if ( ! empty( $el['elements'] ) ) {
					$walk( $el['elements'] );
				}
			}
		};

		if ( class_exists( '\Elementor\Plugin' ) ) {
			$doc = \Elementor\Plugin::$instance->documents->get( $document_id );
			if ( $doc ) {
				$walk( (array) $doc->get_elements_data() );
			}
		}

		return $found;
	}

	/**
	 * The id of the page's ONLY Loop Grid, or '' when there is none or several.
	 *
	 * This is what makes a filter or a readout work the moment it is dropped:
	 * the common page has one grid, and asking a builder to copy an element id
	 * before anything renders is a setup step for a case that usually does not
	 * exist. A page with two grids is exactly where the field gets filled in.
	 *
	 * Lives here rather than in each widget because every filter and readout
	 * asks the same question, and two implementations of "which grid" is a
	 * control that points at a different grid than the one it reports on.
	 */
	public static function only_grid_id( int $document_id ): string {
		static $memo = [];
		if ( isset( $memo[ $document_id ] ) ) {
			return $memo[ $document_id ];
		}

		$found = [];
		$walk  = static function ( $els ) use ( &$walk, &$found ) {
			foreach ( (array) $els as $el ) {
				if ( ! is_array( $el ) ) {
					continue;
				}
				$type = (string) ( $el['widgetType'] ?? ( $el['elType'] ?? '' ) );
				if ( in_array( $type, self::GRID_TYPES, true ) ) {
					$found[] = (string) ( $el['id'] ?? '' );
				}
				if ( ! empty( $el['elements'] ) ) {
					$walk( $el['elements'] );
				}
			}
		};

		if ( class_exists( '\Elementor\Plugin' ) ) {
			$doc = \Elementor\Plugin::$instance->documents->get( $document_id );
			if ( $doc ) {
				$walk( (array) $doc->get_elements_data() );
			}
		}

		$memo[ $document_id ] = ( 1 === count( $found ) ) ? $found[0] : '';
		return $memo[ $document_id ];
	}

	/**
	 * The post type the grid's query actually targets.
	 *
	 * This is NOT the `post_type` SETTING for the two special sources: those
	 * store the literal 'related' / 'current_query'. The authoriser needs the
	 * real one — it refuses WooCommerce sort keys unless the type is 'product'
	 * and counts an author's published posts OF that type — so handing it
	 * 'current_query' silently drops both on a shop archive, which is exactly
	 * where visitor filtering matters most.
	 *
	 * @param array $raw_settings The grid's settings (wrapped or plain), incl.
	 *                            the internal `_context_post_id` / `_qv` the
	 *                            AJAX handler adds.
	 */
	public static function effective_post_type( array $raw_settings ): string {
		$s    = self::unwrap( $raw_settings );
		$type = isset( $s['post_type'] ) && is_string( $s['post_type'] ) && '' !== $s['post_type']
			? sanitize_key( $s['post_type'] )
			: 'post';

		if ( 'related' === $type ) {
			$anchor = self::resolve_context_post_id( $s );
			$post   = $anchor ? get_post( $anchor ) : null;
			return $post ? (string) $post->post_type : 'post';
		}

		if ( 'current_query' !== $type ) {
			return $type;
		}

		$vars = ( isset( $s['_qv'] ) && is_array( $s['_qv'] ) )
			? self::sanitize_query_vars( $s['_qv'] )
			: self::current_query_vars();

		if ( ! empty( $vars['post_type'] ) ) {
			$types = (array) $vars['post_type'];
			$first = sanitize_key( (string) reset( $types ) );
			if ( '' !== $first ) {
				return $first;
			}
		}

		// A taxonomy archive names no post type of its own — /product-category/x/
		// parses to just [ product_cat => x ] — but the taxonomy is registered
		// against one, and that is the honest answer for a shop archive.
		foreach ( $vars as $key => $unused ) {
			$tax = is_string( $key ) ? get_taxonomy( $key ) : false;
			if ( $tax && ! empty( $tax->object_type ) ) {
				$first = sanitize_key( (string) reset( $tax->object_type ) );
				if ( '' !== $first ) {
					return $first;
				}
			}
		}

		return 'post';
	}

	/**
	 * The document this grid is being rendered in — the one whose saved
	 * `_elementor_data` declares the filter widgets. Elementor sets the current
	 * document for the duration of a document render; the queried post is the
	 * fallback for a render outside that (a shortcode, a widget area).
	 */
	public static function current_document_id(): int {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$doc = \Elementor\Plugin::$instance->documents->get_current();
			if ( $doc && method_exists( $doc, 'get_main_id' ) ) {
				return (int) $doc->get_main_id();
			}
		}
		return (int) get_the_ID();
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
	 *
	 * @param array $raw_settings The grid's settings (wrapped or plain).
	 * @param int   $paged        1-based page.
	 * @param array $filters      Visitor filters, ALREADY AUTHORISED by
	 *                            Loop_Filter_Auth::authorize(). Its own argument
	 *                            on purpose: the editor preview endpoint passes
	 *                            client-sent settings straight in here, so a
	 *                            filter key inside $raw_settings would be a way
	 *                            to hand-write a meta_query. Raw request data
	 *                            never reaches this function.
	 */
	public static function build_query_args( array $raw_settings, int $paged = 1, array $filters = [] ): array {
		$s = self::unwrap_settings( $raw_settings );

		$post_type = isset( $s['post_type'] ) && is_string( $s['post_type'] ) && '' !== $s['post_type']
			? sanitize_key( $s['post_type'] )
			: 'post';

		// Special sources — not post types. Related builds its own term query
		// from the current post; Current Query inherits the page's main
		// (archive) query. Both skip the builder's manual filters (their
		// controls are hidden for these sources too) — but NOT the visitor's:
		// a shop archive is a Current Query grid, and that is exactly where
		// filtering matters most.
		if ( 'related' === $post_type ) {
			$args = self::build_related_query_args( $s, $paged );
		} elseif ( 'current_query' === $post_type ) {
			$args = self::build_current_query_args( $s, $paged );
		} else {
			$args = self::build_default_query_args( $s, $post_type, $paged );
		}

		$args = self::merge_visitor_filters( $args, $filters );

		// WooCommerce: catalog visibility on every product query, plus the
		// lookup-table price / rating / stock / sort the authoriser allowed.
		$args = Loop_Query_Woo::apply( $args, $filters['woo'] ?? [] );

		// Sticky posts first. WP_Query's own sticky handling only applies to
		// the main home query, never to a secondary query like this one — so
		// we pin manually: pre-resolve the FULL matching id list (stickies that
		// match the filters first, then the rest in the chosen order) and turn
		// the query into `post__in` + `orderby: post__in`. Pagination then
		// flows naturally across the pinned list. Runs LAST so the id
		// pre-query carries every filter above (tax/include/date/meta AND the
		// visitor's). An explicit visitor sort wins over pinning for that
		// request: a pinned-first list sorted by price is neither.
		$visitor_sorted = self::sort_overrides_order( $s, $filters );
		if ( ! in_array( $post_type, [ 'related', 'current_query' ], true ) && ! empty( $s['sticky_first'] ) && ! $visitor_sorted ) {
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

	/**
	 * Fold authorised visitor filters into a built args array. Every clause
	 * ANDs with what the builder saved; an existing OR group (the Related
	 * source's term query) is nested, never flattened.
	 */
	private static function merge_visitor_filters( array $args, array $filters ): array {
		if ( ! $filters ) {
			return $args;
		}

		// Taxonomy terms.
		$tax_new = [];
		foreach ( (array) ( $filters['tax'] ?? [] ) as $taxonomy => $clause ) {
			if ( empty( $clause['ids'] ) || ! taxonomy_exists( (string) $taxonomy ) ) {
				continue;
			}
			$tax_new[] = [
				'taxonomy'         => (string) $taxonomy,
				'field'            => 'term_id',
				'terms'            => array_map( 'intval', (array) $clause['ids'] ),
				'operator'         => ! empty( $clause['all'] ) ? 'AND' : 'IN',
				'include_children' => ! isset( $clause['include_children'] ) || ! empty( $clause['include_children'] ),
			];
		}
		if ( $tax_new ) {
			$args['tax_query'] = Loop_Query_Woo::and_group( (array) ( $args['tax_query'] ?? [] ), $tax_new ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		// Meta clauses — each already carries key / compare / type from the widget.
		$meta_new = array_values( array_filter( (array) ( $filters['meta'] ?? [] ), 'is_array' ) );
		if ( $meta_new ) {
			$args['meta_query'] = Loop_Query_Woo::and_group( (array) ( $args['meta_query'] ?? [] ), $meta_new ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		// Search. Title-only goes through WP's OWN `search_columns` query var
		// (core since 6.2; this plugin requires 6.6), never a posts_search
		// rewrite: WP builds the same fragment with one column instead of
		// three, so everything else it puts there survives — above all the
		// `AND post_password = ''` guard it appends for a logged-out visitor.
		// Replacing the fragment dropped that guard, and a password-protected
		// post's title and link then rendered in the grid for anyone.
		if ( ! empty( $filters['s'] ) && is_string( $filters['s'] ) ) {
			$args['s'] = $filters['s'];
			if ( ! empty( $filters['title_only'] ) ) {
				$args['search_columns'] = [ 'post_title' ];
			}
		}

		// Authors — the builder's own list bounds the visitor's, BOTH ways.
		//
		// The exclude list was always subtracted. The include list was not, and
		// that was the one place in this whole merge where a visitor's value
		// WIDENED what the saved page allows: a grid scoped to two authors could
		// be filtered to a third, because this line assigned `author__in` over
		// the builder's instead of intersecting with it. Every other filter here
		// can only ever narrow (tax and meta nest under an AND, dates append),
		// and that is the property the authoriser exists to guarantee.
		if ( ! empty( $filters['author'] ) ) {
			$ids = array_map( 'intval', (array) $filters['author'] );
			if ( ! empty( $args['author__not_in'] ) ) {
				$ids = array_values( array_diff( $ids, array_map( 'intval', (array) $args['author__not_in'] ) ) );
			}
			if ( ! empty( $args['author__in'] ) ) {
				$ids = array_values( array_intersect( $ids, array_map( 'intval', (array) $args['author__in'] ) ) );
			}
			// `[0]` is "no author", i.e. match nothing — the honest answer when
			// the visitor picked somebody this grid does not offer. Leaving the
			// key off instead would show every author, which is the failure
			// being fixed rather than a fallback from it.
			$args['author__in'] = $ids ? $ids : [ 0 ];
		}

		// Date ranges (publish / modified) — appended to the builder's own.
		if ( ! empty( $filters['date'] ) ) {
			$date = isset( $args['date_query'] ) ? (array) $args['date_query'] : [];
			foreach ( (array) $filters['date'] as $clause ) {
				if ( is_array( $clause ) ) {
					$date[] = $clause;
				}
			}
			$args['date_query'] = $date;
		}

		// Sort — the Sort widget's own option, already whitelisted.
		if ( ! empty( $filters['sort']['orderby'] ) ) {
			$orderby = (string) $filters['sort']['orderby'];
			if ( 'relevance' === $orderby && empty( $args['s'] ) ) {
				$orderby = 'date';
			}
			$args['orderby'] = $orderby;
			$args['order']   = 'ASC' === strtoupper( (string) ( $filters['sort']['order'] ?? 'DESC' ) ) ? 'ASC' : 'DESC';
			if ( in_array( $orderby, [ 'meta_value', 'meta_value_num' ], true ) && ! empty( $filters['sort']['meta_key'] ) ) {
				$args['meta_key'] = (string) $filters['sort']['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery
				if ( ! empty( $filters['sort']['meta_type'] ) ) {
					$args['meta_type'] = (string) $filters['sort']['meta_type'];
				}
			}
		}

		return $args;
	}

	/**
	 * Has the visitor's sort actually CHANGED the order the builder saved?
	 *
	 * Not merely "is a sort key present": a Sort widget writes its selected
	 * option into the URL on every render, including the option that matches the
	 * grid's own order, so testing for presence would switch Sticky First off on
	 * every page that carries a Sort widget — even for a visitor who never
	 * touched it. A pinned list re-sorted by price is neither pinned nor sorted,
	 * which is the case this exists for; a sort that asks for the order the grid
	 * already had asks for nothing.
	 */
	private static function sort_overrides_order( array $s, array $filters ): bool {
		// A lookup-table sort (price / popularity / rating) has no builder
		// equivalent at all, so it always overrides.
		if ( ! empty( $filters['woo']['sort'] ) ) {
			return true;
		}
		if ( empty( $filters['sort']['orderby'] ) ) {
			return false;
		}

		$orderby = (string) $filters['sort']['orderby'];
		$order   = 'ASC' === strtoupper( (string) ( $filters['sort']['order'] ?? 'DESC' ) ) ? 'ASC' : 'DESC';

		return $orderby !== self::sanitize_order_by( $s ) || $order !== self::sanitize_order( $s );
	}

	/** The builder's own query for a plain post-type Source, up to and including the offset. */
	private static function build_default_query_args( array $s, string $post_type, int $paged ): array {
		$args = [
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => self::sanitize_per_page( $s ),
			'orderby'             => self::sanitize_order_by( $s ),
			'order'               => self::sanitize_order( $s ),
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,

			// The render query's found_posts is NEVER read — the page count
			// comes from compute_max_pages(), which runs its own query and
			// re-enables this. Without it MySQL runs SQL_CALC_FOUND_ROWS,
			// scanning every matching row to produce a number nothing uses:
			// measured on the fixture as `SELECT SQL_CALC_FOUND_ROWS … LIMIT
			// 0, 6` returning found_posts=36 that was then discarded. On a real
			// content set that scan is the most expensive thing this widget
			// does. compute_max_pages() overrides it back to false, so
			// pagination is unaffected.
			'no_found_rows'       => true,
		];

		// Taxonomy term filters — only taxonomies actually registered for the
		// selected post type (stale selections from a previous Source are kept in
		// the settings but must not leak into the query).
		$tax_query = [];
		foreach ( self::get_query_taxonomies() as $tax ) {
			if ( ! empty( $tax->aae_unregistered ) || ! taxonomy_exists( $tax->name ) ) {
				continue; // ratchet stub: the saved value waits for its taxonomy to return
			}
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

		// Authors (builder-side). Exclude wins over include on an overlap.
		$authors = self::extract_ids( $s['authors'] ?? null );
		$not_authors = self::extract_ids( $s['exclude_authors'] ?? null );
		if ( $not_authors ) {
			$args['author__not_in'] = $not_authors;
			$authors = array_values( array_diff( $authors, $not_authors ) );
		}
		if ( $authors ) {
			$args['author__in'] = $authors;
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

		return $args;
	}

	/**
	 * Upper bound offered by the Posts Per Page control.
	 *
	 * A seam, not a constant, because AAE_A_Loop_Grid_Slider lowers it on an
	 * unlicensed site — see its override. The plain grid is deliberately NOT
	 * gated and always offers the full range.
	 *
	 * PANEL ONLY. sanitize_per_page() is what the QUERY uses and is left alone
	 * on purpose: a page saved while licensed keeps rendering every item it was
	 * built with, so a lapse costs the ability to author more, never the content
	 * already on the site.
	 */
	protected function per_page_max(): int {
		return 50;
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
	 * Does this grid render anything that needs a page count?
	 *
	 * Walks the saved element tree rather than instantiating children: this is
	 * asked during render, before the subtree exists as objects, and the answer
	 * only depends on what was saved.
	 *
	 * Matches by SUFFIX, not an exact type. AAE_A_Loop_Grid_Slider extends this
	 * class and inherits define_render_context(), but its pagination child is
	 * `e-aae-a-loop-slide-pagination` — an exact match on
	 * `e-aae-a-loop-pagination` answered "no pagination" for every slider and
	 * pinned its page count to 1. Caught on the fixture: the grid reported
	 * total 9 while the slider beside it reported total 1.
	 */
	private function has_pagination_child(): bool {
		$scan = static function ( $elements ) use ( &$scan ) {
			foreach ( (array) $elements as $element ) {
				if ( ! is_array( $element ) ) {
					continue;
				}

				$type = (string) ( $element['widgetType'] ?? ( $element['elType'] ?? '' ) );

				if ( 'pagination' === substr( $type, -10 ) && 0 === strpos( $type, 'e-aae-a-loop' ) ) {
					return true;
				}

				if ( ! empty( $element['elements'] ) && $scan( $element['elements'] ) ) {
					return true;
				}
			}

			return false;
		};

		return $scan( $this->get_data( 'elements' ) );
	}

	/**
	 * Total pages for a built query — the ONE place the count is computed.
	 *
	 * Needed because WP_Query's own max_num_pages is wrong once `offset` is in
	 * play: found_posts ignores LIMIT, so it never subtracts the user's skip.
	 * Count WITHOUT the offset, subtract it, divide by per_page.
	 *
	 * DO NOT add a cache layer here. One was written and removed on 2026-08-02:
	 * WP_Query ALREADY caches its results including `found_posts`
	 * (`class-wp-query.php:3271`, group `post-queries`, salted with
	 * `wp_cache_get_last_changed( 'posts' )`), so with a persistent object
	 * cache this is free across requests and without one it is free within a
	 * request — and core's invalidation is correct, which a hand-rolled one is
	 * unlikely to be. The version-bump invalidation that layer needed hooked
	 * `updated_post_meta` / `added_post_meta`, which fire many times per post
	 * save, so it turned every save and every bulk import into a storm of
	 * `update_option()` writes to avoid a query core was already caching. It
	 * cost more than it saved.
	 */
	public static function compute_max_pages( array $raw_settings, array $query_args ): int {
		return self::pages_for_total( self::count_total( $raw_settings, $query_args ), $query_args );
	}

	/**
	 * How many posts match the built query, minus the builder's offset — the
	 * number the Result Count widget shows and the page count divides.
	 */
	public static function count_total( array $raw_settings, array $query_args ): int {
		$s      = self::unwrap_settings( $raw_settings );
		$offset = self::sanitize_offset( $s );

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

		return $total;
	}

	public static function pages_for_total( int $total, array $query_args ): int {
		$per_page = max( 1, (int) ( $query_args['posts_per_page'] ?? 6 ) );
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
			'no_found_rows'       => true, // See the note in build_query_args().
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
			'no_found_rows'       => true, // See the note in build_query_args().
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
	public static function unwrap( $value ) {
		if ( is_array( $value ) && isset( $value['$$type'] ) && array_key_exists( 'value', $value ) ) {
			return self::unwrap( $value['value'] );
		}
		if ( is_array( $value ) ) {
			return array_map( [ self::class, 'unwrap' ], $value );
		}
		return $value;
	}

	/** @deprecated internal alias kept for the call sites below. */
	private static function unwrap_settings( $value ) {
		return self::unwrap( $value );
	}

	/**
	 * Extract positive integer ids from a chips value: an array whose items are
	 * JSON strings {"id":123,"label":".."} (aae-query-chips storage format),
	 * plain numerics, or already-decoded arrays.
	 */
	public static function extract_ids( $items ): array {
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
	public static function valid_date( $value ): ?string {
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
		// Public frontend pagination: a page NUMBER off the query string,
		// cast to int and floored at 1. There is no state change to protect
		// and no nonce a search engine or a shared link could carry.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['aae_page'] ) ) {
			return max( 1, (int) $_GET['aae_page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$paged = (int) get_query_var( 'paged' );
		if ( ! $paged ) {
			$paged = (int) get_query_var( 'page' );
		}
		return max( 1, $paged );
	}
}
