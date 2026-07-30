<?php
/**
 * AAE Post Pagination — atomic prev/next single-post navigation.
 *
 * Root container seeding a Prev + Next button (each independently
 * restyleable/composable, mirroring AAE_A_Loop_Prev/Next). Resolves the
 * adjacent post via its OWN full-list-and-locate query builder — WordPress's
 * `get_adjacent_post()` is too limited for this widget's requirements (only
 * one taxonomy, no custom-field/menu_order ordering, no loop-around), so we
 * build the same "resolve the full ordered id list once, locate the current
 * post's index" strategy AAE_A_Loop_Grid uses for sticky-post pinning.
 *
 * Post-type agnostic by design: `get_post_type()` on the current post drives
 * everything, so the same widget works unmodified on single Posts, Pages, or
 * a WooCommerce single Product template (taxonomies like product_cat/
 * product_tag are enumerated the same way as category/post_tag).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\PostPagination;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Array_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Toggle_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Parts/class-aae-a-post-pagination-prev.php';
require_once __DIR__ . '/Parts/class-aae-a-post-pagination-next.php';
require_once __DIR__ . '/../LoopGrid/class-aae-query-chips-control.php';

use WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_Query_Chips_Control;

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Post_Pagination extends Atomic_Element_Base {
	use Has_Element_Template;

	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-post-pagination';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-post-pagination';
	}

	public function get_title() {
		return esc_html__( 'AAE Post Pagination', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-navigation';
	}

	public function get_keywords() {
		return [ 'post', 'nav', 'navigation', 'prev', 'next', 'pagination', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		// NOTE on polarity: Elementor's 4.1.x dependency evaluator fires the
		// `effect` when the where() condition does NOT match — a `hide`
		// effect is really "hide UNLESS this holds" (see AAE_A_Loop_Grid's
		// tax-prop dependencies for the same documented gotcha, verified
		// empirically there too). So every clause below states the condition
		// under which the field should be VISIBLE, not the condition under
		// which it should hide.
		$has_taxonomy = Dependency_Manager::make()
			->where( [ 'operator' => 'ne', 'path' => [ 'constrain_taxonomy' ], 'value' => 'none', 'effect' => 'hide' ] )
			->get();

		$is_meta_order = Dependency_Manager::make()
			->where( [ 'operator' => 'eq', 'path' => [ 'order_by' ], 'value' => 'meta_value', 'effect' => 'hide' ] )
			->get();

		$not_inline = Dependency_Manager::make()
			->where( [ 'operator' => 'ne', 'path' => [ 'display_mode' ], 'value' => 'inline', 'effect' => 'hide' ] )
			->get();

		// Terms/Posts each get an exclusive Exclude/Include toggle (a plain
		// boolean AND of "taxonomy chosen" + "mode matches" for terms, just
		// "mode matches" for posts) — only the active mode's field is ever
		// shown, so a user can't fill in both at once and get an ambiguous
		// (or, pre-2026-07-27, self-contradictory) combination.
		$show_include_terms = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'ne', 'path' => [ 'constrain_taxonomy' ], 'value' => 'none', 'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ 'terms_filter_mode' ], 'value' => 'include', 'effect' => 'hide' ] )
			->get();

		$show_exclude_terms = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [ 'operator' => 'ne', 'path' => [ 'constrain_taxonomy' ], 'value' => 'none', 'effect' => 'hide' ] )
			->where( [ 'operator' => 'eq', 'path' => [ 'terms_filter_mode' ], 'value' => 'exclude', 'effect' => 'hide' ] )
			->get();

		$show_include_posts = Dependency_Manager::make()
			->where( [ 'operator' => 'eq', 'path' => [ 'posts_filter_mode' ], 'value' => 'include', 'effect' => 'hide' ] )
			->get();

		$show_exclude_posts = Dependency_Manager::make()
			->where( [ 'operator' => 'eq', 'path' => [ 'posts_filter_mode' ], 'value' => 'exclude', 'effect' => 'hide' ] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Query.
			'constrain_taxonomy' => String_Prop_Type::make()->default( 'none' ),
			// Include broadens the "same term as current post" match (union,
			// not a replacement — a post's own term still counts even if not
			// separately re-picked here); Exclude narrows it. Both apply to
			// whichever taxonomy Constrain To is currently set to. Only ONE
			// is ever active at a time — see terms_filter_mode.
			'terms_filter_mode'  => String_Prop_Type::make()->default( 'exclude' )->set_dependencies( $has_taxonomy ),
			'include_terms'      => String_Array_Prop_Type::make()->default( [] )->set_dependencies( $show_include_terms ),
			'exclude_terms'      => String_Array_Prop_Type::make()->default( [] )->set_dependencies( $show_exclude_terms ),
			'posts_filter_mode'  => String_Prop_Type::make()->default( 'exclude' ),
			'include_posts'      => String_Array_Prop_Type::make()->default( [] )->set_dependencies( $show_include_posts ),
			'exclude_posts'      => String_Array_Prop_Type::make()->default( [] )->set_dependencies( $show_exclude_posts ),
			'order_by'           => String_Prop_Type::make()->default( 'date' ),
			'meta_key'           => String_Prop_Type::make()->default( '' )->set_dependencies( $is_meta_order ),
			'meta_type'          => String_Prop_Type::make()->default( 'CHAR' )->set_dependencies( $is_meta_order ),
			'order'              => String_Prop_Type::make()->default( 'asc' ),
			'loop_around'        => Boolean_Prop_Type::make()->default( false ),

			// Display.
			'display_mode'         => String_Prop_Type::make()->default( 'inline' ),
			'scroll_reveal_offset' => Number_Prop_Type::make()->default( 300 )->set_dependencies( $not_inline ),

			// Interactions.
			'enable_keyboard_nav' => Boolean_Prop_Type::make()->default( false ),
			'enable_swipe'        => Boolean_Prop_Type::make()->default( false ),
			'enable_prefetch'     => Boolean_Prop_Type::make()->default( true ),

			// Visibility. Each is an independent widget-level "hide the whole
			// nav" switch — distinct from the always-on per-button hiding
			// (.aae-pp-no-prev/.aae-pp-no-next in post-pagination.scss), and
			// distinct from EACH OTHER once Loop Around is on: with looping,
			// prev/next always resolve to something (so the no-prev/no-next
			// conditions never fire), but "is this the first/last post in the
			// sequence" is still a real, separate condition.
			'hide_if_no_prev'    => Boolean_Prop_Type::make()->default( false ),
			'hide_if_no_next'    => Boolean_Prop_Type::make()->default( false ),
			'hide_if_first_post' => Boolean_Prop_Type::make()->default( false ),
			'hide_if_last_post'  => Boolean_Prop_Type::make()->default( false ),

			// Hover Preview Card — a real, customizable element tree now (see
			// class-aae-a-post-pagination-preview.php), nested inside both
			// Prev and Next. This is the only root-level setting left for it:
			// a master on/off switch that gates whether post-pagination.js
			// binds hover/focus listeners at all. Which fields show
			// (Thumbnail/Category/Title/Date/Author/Excerpt) is no longer a
			// boolean here — it's just whichever of those child pieces the
			// user kept vs. deleted from the Preview Card's own children, and
			// excerpt length is the Excerpt piece's own control.
			'enable_hover_preview' => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		// The terms search box is scoped to 'category'. It deliberately does NOT
		// read this element's own `constrain_taxonomy`:
		//
		// Elementor builds the panel config from element TYPES, not instances —
		// Document::get_initial_config() maps over
		// elements_manager->get_element_types(), so this method runs once on a
		// type object that was constructed with no data, and the result is
		// shared by every element of this type in the document. There is no
		// "this element" to read here.
		//
		// A previous version called $this->get_atomic_settings() at this point.
		// On a data-less instance that reaches Controls_Stack::get_data() with a
		// null $this->data and emits "Trying to access array offset on null" in
		// controls-stack.php on every editor load. The try/catch around it never
		// helped: a PHP warning is not a Throwable. It also never changed the
		// outcome — with no instance data the call can only return the prop
		// default 'none', which mapped to 'category' anyway.
		$chips_taxonomy = 'category';

		return [
			Section::make()
				->set_id( 'aae_post_pagination_query' )
				->set_label( __( 'Query', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'constrain_taxonomy' )
						->set_label( __( 'Constrain To', 'animation-addons-for-elementor' ) )
						->set_options( self::get_taxonomy_options() ),

					Toggle_Control::bind_to( 'terms_filter_mode' )
						->set_label( __( 'Terms Filter Mode', 'animation-addons-for-elementor' ) )
						->add_options( [
							'exclude' => [ 'title' => __( 'Exclude', 'animation-addons-for-elementor' ) ],
							'include' => [ 'title' => __( 'Include', 'animation-addons-for-elementor' ) ],
						] )
						->set_exclusive( true )
						->set_full_width( true )
						->set_convert_options( true ),

					AAE_Query_Chips_Control::bind_to( 'include_terms' )
						->set_label( __( 'Include Terms', 'animation-addons-for-elementor' ) )
						->set_kind( 'term' )
						->set_taxonomy( $chips_taxonomy )
						->set_placeholder( __( 'Search terms…', 'animation-addons-for-elementor' ) ),

					AAE_Query_Chips_Control::bind_to( 'exclude_terms' )
						->set_label( __( 'Exclude Terms', 'animation-addons-for-elementor' ) )
						->set_kind( 'term' )
						->set_taxonomy( $chips_taxonomy )
						->set_placeholder( __( 'Search terms…', 'animation-addons-for-elementor' ) ),

					Toggle_Control::bind_to( 'posts_filter_mode' )
						->set_label( __( 'Posts Filter Mode', 'animation-addons-for-elementor' ) )
						->add_options( [
							'exclude' => [ 'title' => __( 'Exclude', 'animation-addons-for-elementor' ) ],
							'include' => [ 'title' => __( 'Include', 'animation-addons-for-elementor' ) ],
						] )
						->set_exclusive( true )
						->set_full_width( true )
						->set_convert_options( true ),

					AAE_Query_Chips_Control::bind_to( 'include_posts' )
						->set_label( __( 'Include Posts', 'animation-addons-for-elementor' ) )
						->set_kind( 'post' )
						->set_placeholder( __( 'Search by title or ID…', 'animation-addons-for-elementor' ) ),

					AAE_Query_Chips_Control::bind_to( 'exclude_posts' )
						->set_label( __( 'Exclude Posts', 'animation-addons-for-elementor' ) )
						->set_kind( 'post' )
						->set_placeholder( __( 'Search by title or ID…', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'order_by' )
						->set_label( __( 'Order By', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'date',       'label' => __( 'Date', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'title',      'label' => __( 'Title', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'menu_order', 'label' => __( 'Menu Order / Manual Sequence', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'meta_value', 'label' => __( 'Custom Field', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'meta_key' )
						->set_label( __( 'Custom Field Key', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'meta_type' )
						->set_label( __( 'Custom Field Type', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'CHAR',    'label' => __( 'Text', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'NUMERIC', 'label' => __( 'Number', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'order' )
						->set_label( __( 'Order', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'asc',  'label' => __( 'Ascending (Next = forward)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'desc', 'label' => __( 'Descending', 'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'loop_around' )
						->set_label( __( 'Loop Around', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'aae_post_pagination_display' )
				->set_label( __( 'Display', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'display_mode' )
						->set_label( __( 'Display Mode', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'inline',      'label' => __( 'Inline (Normal Flow)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'sticky_bar',  'label' => __( 'Sticky Bottom Bar', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'side_arrows', 'label' => __( 'Floating Navigation (Side Arrows)', 'animation-addons-for-elementor' ) ],
						] ),

					Number_Control::bind_to( 'scroll_reveal_offset' )
						->set_label( __( 'Reveal After Scrolling (px)', 'animation-addons-for-elementor' ) )
						->set_min( 0 )
						->set_max( 5000 ),
				] ),

			Section::make()
				->set_id( 'aae_post_pagination_interactions' )
				->set_label( __( 'Interactions', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'enable_keyboard_nav' )
						->set_label( __( 'Keyboard Arrow Navigation', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'enable_swipe' )
						->set_label( __( 'Mobile Swipe Gestures', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'enable_prefetch' )
						->set_label( __( 'Prefetch On Hover', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'aae_post_pagination_visibility' )
				->set_label( __( 'Visibility', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'hide_if_no_prev' )
						->set_label( __( 'Hide If No Previous Post', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'hide_if_no_next' )
						->set_label( __( 'Hide If No Next Post', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'hide_if_first_post' )
						->set_label( __( 'Hide If First Post', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'hide_if_last_post' )
						->set_label( __( 'Hide If Last Post', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'aae_post_pagination_hover_preview' )
				->set_label( __( 'Hover Preview Card', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'enable_hover_preview' )
						->set_label( __( 'Enable Hover Preview', 'animation-addons-for-elementor' ) )
						->set_description(
							__( 'Customize what shows by editing the "Hover Preview Card" element nested inside Prev/Next — add, remove, or restyle its Thumbnail/Category/Title/Date/Author/Excerpt pieces freely.', 'animation-addons-for-elementor' )
						),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'row' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'space-between' ) )
						->add_prop( 'flex-wrap', String_Prop_Type::generate( 'wrap' ) )
						->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ) )
						->add_prop( 'width', String_Prop_Type::generate( '100%' ) )
						->add_prop( 'padding', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
				),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-post-pagination-prev', 'e-aae-a-post-pagination-next' ];
	}

	protected function define_default_children() {
		// NOT locked, deliberately: unlike Loop Grid's structural Nav/Prev/
		// Next wrappers (pure scaffolding), these ARE the actual buttons —
		// label text, icon, and style are exactly what a user drops this
		// widget to customize.
		return [
			AAE_A_Post_Pagination_Prev::generate()
				->editor_settings( [ 'title' => 'Previous Post' ] )
				->build(),
			AAE_A_Post_Pagination_Next::generate()
				->editor_settings( [ 'title' => 'Next Post' ] )
				->build(),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-pagination' => __DIR__ . '/aae-a-post-pagination.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-post-pagination-css' ];
	}

	/**
	 * Publish the resolved prev/next posts + all interaction settings to
	 * descendants via the Render_Context stack, keyed by this class.
	 *
	 * @see \Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context
	 */
	protected function define_render_context(): array {
		$s         = $this->get_atomic_settings();
		$post_id   = self::resolve_post_id();
		$adjacent  = $post_id ? self::resolve_adjacent( $post_id, $s ) : [ 'prev' => null, 'next' => null, 'post_type' => '' ];

		return [
			[
				'context_key' => self::class,
				'context'     => array_merge(
					$adjacent,
					[
						'post_id' => $post_id,
					]
				),
			],
		];
	}

	/**
	 * Config JSON for the frontend runtime (post-pagination.js): everything it needs
	 * to wire keyboard nav / swipe / prefetch / sticky-reveal without
	 * re-deriving anything server already resolved.
	 */
	protected function build_template_context(): array {
		$ctx = Render_Context::get( self::class );
		$s   = $this->get_atomic_settings();

		$cfg = [
			'postId'           => isset( $ctx['post_id'] ) ? (int) $ctx['post_id'] : 0,
			'postType'         => isset( $ctx['post_type'] ) ? $ctx['post_type'] : '',
			'prev'             => isset( $ctx['prev'] ) ? $ctx['prev'] : null,
			'next'             => isset( $ctx['next'] ) ? $ctx['next'] : null,
			'displayMode'      => isset( $s['display_mode'] ) ? $s['display_mode'] : 'inline',
			'revealOffset'     => isset( $s['scroll_reveal_offset'] ) ? (int) $s['scroll_reveal_offset'] : 300,
			'keyboardNav'      => ! empty( $s['enable_keyboard_nav'] ),
			'swipe'            => ! empty( $s['enable_swipe'] ),
			'prefetch'         => ! empty( $s['enable_prefetch'] ),
			// Which fields show is no longer decided here — it's whichever
			// child pieces the user kept inside the real "Hover Preview Card"
			// element (see class-aae-a-post-pagination-preview.php) nested in
			// Prev/Next. This flag only gates whether post-pagination.js
			// binds the hover/focus/positioning behavior at all.
			'hoverPreviewEnabled' => ! empty( $s['enable_hover_preview'] ),
		];

		// Widget-level visibility — hides the WHOLE nav (both buttons), unlike
		// the always-on per-button .aae-pp-no-prev/.aae-pp-no-next hiding in
		// post-pagination.scss. Frontend-only (see the twig/CSS gate on
		// body:not(.elementor-editor-active)) so the editor keeps everything
		// visible/selectable regardless of these switches.
		$pn_hidden =
			( ! empty( $s['hide_if_no_prev'] ) && empty( $cfg['prev'] ) ) ||
			( ! empty( $s['hide_if_no_next'] ) && empty( $cfg['next'] ) ) ||
			( ! empty( $s['hide_if_first_post'] ) && ! empty( $ctx['is_first'] ) ) ||
			( ! empty( $s['hide_if_last_post'] ) && ! empty( $ctx['is_last'] ) );

		return array_merge( $this->build_base_template_context(), [
			'pn_config'  => wp_json_encode( $cfg ),
			'pn_mode'    => $cfg['displayMode'],
			'pn_no_prev' => empty( $cfg['prev'] ),
			'pn_no_next' => empty( $cfg['next'] ),
			'pn_hidden'  => $pn_hidden,
		] );
	}

	/**
	 * The current post. A live singular page/product resolves from the main
	 * query; the editor (e.g. editing a non-singular template) falls back to
	 * the shared sample post so Prev/Next preview with real data.
	 */
	public static function resolve_post_id(): int {
		if ( is_singular() ) {
			return (int) get_queried_object_id();
		}

		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode()
			&& class_exists( '\WCF_ADDONS\AtomicWidgets\Atomic' ) ) {
			$sample = \WCF_ADDONS\AtomicWidgets\Atomic::get_sample_post();
			if ( $sample ) {
				return (int) $sample->ID;
			}
		}

		$id = (int) get_the_ID();
		return $id > 0 ? $id : 0;
	}

	/**
	 * Resolve the previous/next post for $current_id under the given
	 * (already-unwrapped, plain) settings. Public + static so callers
	 * without an element instance can reuse it.
	 *
	 * @return array{prev: ?array, next: ?array, post_type: string}
	 */
	public static function resolve_adjacent( int $current_id, array $settings ): array {
		$result = [ 'prev' => null, 'next' => null, 'post_type' => '', 'is_first' => false, 'is_last' => false ];

		$post_type = get_post_type( $current_id );
		if ( ! $post_type ) {
			return $result;
		}
		$result['post_type'] = $post_type;

		$taxonomy = ( isset( $settings['constrain_taxonomy'] ) && is_string( $settings['constrain_taxonomy'] ) && 'none' !== $settings['constrain_taxonomy'] && taxonomy_exists( $settings['constrain_taxonomy'] ) )
			? $settings['constrain_taxonomy']
			: '';

		$term_ids = [];
		if ( $taxonomy ) {
			$terms    = wp_get_object_terms( $current_id, $taxonomy, [ 'fields' => 'ids' ] );
			$term_ids = is_wp_error( $terms ) ? [] : array_map( 'intval', $terms );

			// terms_filter_mode makes Include/Exclude mutually exclusive in
			// the panel (only one field is ever visible/fillable), so only
			// the ACTIVE mode's value is applied here — a stale value left
			// over in the hidden field from before a mode switch is never
			// read, which also means the two can no longer combine into the
			// self-contradictory "must be IN and NOT IN the same term"
			// query the exclude-only version of this code could hit.
			if ( 'include' === self::filter_mode( $settings, 'terms_filter_mode' ) ) {
				// BROADENS the match (union) — useful when the current post
				// has no terms at all, or when the admin wants the sequence
				// to also cover categories the current post doesn't itself
				// belong to.
				$include_terms_for_match = self::extract_ids( $settings['include_terms'] ?? null );
				if ( $include_terms_for_match ) {
					$term_ids = array_values( array_unique( array_merge( $term_ids, $include_terms_for_match ) ) );
				}
			} else {
				$exclude_terms_for_match = self::extract_ids( $settings['exclude_terms'] ?? null );
				if ( $exclude_terms_for_match ) {
					$term_ids = array_values( array_diff( $term_ids, $exclude_terms_for_match ) );
				}
			}

			// No (remaining) terms in the constraining taxonomy on this post
			// — constraint can't apply, fall through to the unconstrained
			// list rather than rendering an empty/broken nav.
			if ( ! $term_ids ) {
				$taxonomy = '';
			}
		}

		$ids = self::get_ordered_ids( $post_type, $taxonomy, $term_ids, $settings );

		$index = array_search( $current_id, $ids, true );
		if ( false === $index ) {
			return $result;
		}

		$loop  = ! empty( $settings['loop_around'] );
		$count = count( $ids );

		$prev_id = null;
		if ( $index > 0 ) {
			$prev_id = $ids[ $index - 1 ];
		} elseif ( $loop && $count > 1 ) {
			$prev_id = $ids[ $count - 1 ];
		}

		$next_id = null;
		if ( $index < $count - 1 ) {
			$next_id = $ids[ $index + 1 ];
		} elseif ( $loop && $count > 1 ) {
			$next_id = $ids[0];
		}

		// Distinct from prev_id/next_id being null: "first/last in the
		// sequence" stays true regardless of Loop Around, which is exactly
		// what makes hide_if_first_post/hide_if_last_post meaningfully
		// different from hide_if_no_prev/hide_if_no_next once looping is on.
		$result['is_first'] = ( 0 === $index );
		$result['is_last']  = ( $count - 1 === $index );

		$result['prev'] = $prev_id ? self::post_summary( (int) $prev_id, $settings ) : null;
		$result['next'] = $next_id ? self::post_summary( (int) $next_id, $settings ) : null;

		return $result;
	}

	/**
	 * id/title/url plus the Hover Preview Card fields (thumbnail, excerpt,
	 * category, date, author). The extra fields are cheap (one call each) so
	 * they're always resolved rather than gated behind enable_hover_preview.
	 */
	private static function post_summary( int $id, array $settings = [] ): array {
		$taxonomy = ( isset( $settings['constrain_taxonomy'] ) && is_string( $settings['constrain_taxonomy'] ) && 'none' !== $settings['constrain_taxonomy'] && taxonomy_exists( $settings['constrain_taxonomy'] ) )
			? $settings['constrain_taxonomy']
			: ( taxonomy_exists( 'category' ) ? 'category' : '' );

		$category = '';
		if ( $taxonomy ) {
			$terms = get_the_terms( $id, $taxonomy );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$category = implode( ', ', wp_list_pluck( $terms, 'name' ) );
			}
		}

		$author_id = (int) get_post_field( 'post_author', $id );

		return [
			'id'        => $id,
			'title'     => get_the_title( $id ),
			'url'       => get_permalink( $id ),
			'thumbnail' => has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'medium' ) : '',
			// Generously capped at WP core's own default excerpt length —
			// AAE_A_Post_Pagination_Preview_Excerpt::get_atomic_settings()
			// re-trims this down to its OWN "Length (words)" setting at
			// render time, so the length control lives on the piece that
			// actually displays it, not here.
			'excerpt'   => wp_trim_words( get_the_excerpt( $id ), 55 ),
			'category'  => $category,
			'date'      => get_the_date( '', $id ),
			'author'    => $author_id ? get_the_author_meta( 'display_name', $author_id ) : '',
		];
	}

	/** 'include' or 'exclude' (the only two valid values) — defaults to 'exclude'. */
	private static function filter_mode( array $settings, string $key ): string {
		return ( isset( $settings[ $key ] ) && 'include' === $settings[ $key ] ) ? 'include' : 'exclude';
	}

	/**
	 * The full ordered id list for a (post_type, taxonomy, term combination,
	 * excludes, ordering) fingerprint — cached, since the SAME list serves
	 * every post sharing that fingerprint (e.g. every post in "Category A"),
	 * not just one post. Invalidated by a per-post-type cache-version bump on
	 * save/trash/delete (see class-atomic.php), not by TTL alone.
	 */
	private static function get_ordered_ids( string $post_type, string $taxonomy, array $term_ids, array $settings ): array {
		$key = self::cache_key( $post_type, $taxonomy, $term_ids, $settings );

		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$ids = self::query_ordered_ids( $post_type, $taxonomy, $term_ids, $settings );
		set_transient( $key, $ids, self::CACHE_TTL );

		return $ids;
	}

	private static function cache_key( string $post_type, string $taxonomy, array $term_ids, array $settings ): string {
		sort( $term_ids );

		$parts = [
			self::cache_version( $post_type ),
			$post_type,
			$taxonomy,
			// $term_ids is already the FULLY RESOLVED set (current post's own
			// terms, broadened by include_terms, narrowed by exclude_terms —
			// see resolve_adjacent()), so include_terms doesn't need its own
			// entry here: any change to it that actually affects the result
			// already shows up as a different $term_ids. exclude_terms DOES
			// still need its own entry — it has a SECOND, independent effect
			// inside query_ordered_ids() (a NOT-IN filter on candidate posts,
			// not just on which terms define the match).
			implode( ',', $term_ids ),
			// Modes included explicitly (not just relied upon via $term_ids)
			// so a stale value left in whichever field is currently INACTIVE
			// can never coincidentally collide two different effective
			// queries onto the same cache key.
			self::filter_mode( $settings, 'terms_filter_mode' ),
			self::filter_mode( $settings, 'posts_filter_mode' ),
			implode( ',', self::extract_ids( $settings['exclude_terms'] ?? null ) ),
			implode( ',', self::extract_ids( $settings['include_posts'] ?? null ) ),
			implode( ',', self::extract_ids( $settings['exclude_posts'] ?? null ) ),
			isset( $settings['order_by'] ) ? $settings['order_by'] : 'date',
			isset( $settings['order'] ) ? $settings['order'] : 'asc',
			isset( $settings['meta_key'] ) ? $settings['meta_key'] : '',
			isset( $settings['meta_type'] ) ? $settings['meta_type'] : '',
		];

		return 'aae_pp_' . md5( implode( '|', $parts ) );
	}

	/** @return int[] */
	private static function query_ordered_ids( string $post_type, string $taxonomy, array $term_ids, array $settings ): array {
		$order_by = isset( $settings['order_by'] ) && in_array( $settings['order_by'], [ 'date', 'title', 'menu_order', 'meta_value' ], true )
			? $settings['order_by']
			: 'date';
		$order = ( isset( $settings['order'] ) && 'desc' === strtolower( (string) $settings['order'] ) ) ? 'DESC' : 'ASC';

		$primary_field = $order_by;
		$meta_key      = '';

		if ( 'meta_value' === $order_by ) {
			$meta_key = isset( $settings['meta_key'] ) ? sanitize_text_field( (string) $settings['meta_key'] ) : '';
			if ( '' === $meta_key ) {
				$primary_field = 'date';
			} else {
				$primary_field = ( isset( $settings['meta_type'] ) && 'NUMERIC' === $settings['meta_type'] ) ? 'meta_value_num' : 'meta_value';
			}
		}

		$args = [
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			// `ID` as a secondary key: a single-field orderby leaves ties
			// (every post's menu_order defaults to 0 until someone actually
			// sets values via Page Attributes — the common case; likewise
			// duplicate titles or a shared meta value) in whatever order
			// MySQL happens to return them, which is unstable across
			// requests and reads as "the query is broken." `ID` is always
			// unique, so the full ordering is deterministic regardless.
			'orderby'                => [ $primary_field => $order, 'ID' => $order ],
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		if ( $meta_key ) {
			$args['meta_key'] = $meta_key; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		if ( $taxonomy && $term_ids ) {
			$tax_query = [
				[ 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $term_ids ],
			];

			// This NOT-IN clause is a SEPARATE effect from the term_ids
			// cleanup in resolve_adjacent() — it filters CANDIDATE posts
			// that have an excluded term (even via a different category
			// than the one matched above), not just which terms define the
			// match. Only relevant in 'exclude' mode.
			if ( 'exclude' === self::filter_mode( $settings, 'terms_filter_mode' ) ) {
				$exclude_terms = self::extract_ids( $settings['exclude_terms'] ?? null );
				if ( $exclude_terms ) {
					$tax_query[] = [ 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $exclude_terms, 'operator' => 'NOT IN' ];
				}
			}

			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		if ( 'include' === self::filter_mode( $settings, 'posts_filter_mode' ) ) {
			$include_posts = self::extract_ids( $settings['include_posts'] ?? null );
			if ( $include_posts ) {
				$args['post__in'] = $include_posts;
			}
		}

		if ( 'exclude' === self::filter_mode( $settings, 'posts_filter_mode' ) ) {
			$exclude_posts = self::extract_ids( $settings['exclude_posts'] ?? null );
			if ( $exclude_posts ) {
				$args['post__not_in'] = $exclude_posts;
			}
		}

		return ( new \WP_Query( $args ) )->posts;
	}

	/**
	 * Extract positive integer ids from a chips value: an array whose items
	 * are JSON strings {"id":123,"label":".."} (aae-query-chips storage
	 * format), plain numerics, or already-decoded arrays. (Same shape as
	 * AAE_A_Loop_Grid::extract_ids().)
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

	/** Per-post-type cache-version bump, invalidating stale ordered-id lists. */
	public static function cache_version( string $post_type ): int {
		$versions = get_option( 'aae_pp_cache_versions', [] );
		return isset( $versions[ $post_type ] ) ? (int) $versions[ $post_type ] : 1;
	}

	public static function bump_cache_version( string $post_type ): void {
		if ( ! $post_type ) {
			return;
		}
		$versions               = get_option( 'aae_pp_cache_versions', [] );
		$versions[ $post_type ] = self::cache_version( $post_type ) + 1;
		update_option( 'aae_pp_cache_versions', $versions, false );
	}

	private static function get_taxonomy_options(): array {
		$options = [
			[ 'value' => 'none', 'label' => __( 'None (All Posts)', 'animation-addons-for-elementor' ) ],
		];

		$taxonomies = get_taxonomies( [ 'public' => true, 'show_ui' => true ], 'objects' );

		// Some taxonomies reuse a generic label like "Categories" for a
		// custom post type (e.g. this plugin's own Pro "Video Story" widget
		// registers `video-story-category` with the label hardcoded to just
		// "Categories") — indistinguishable from core's `category` in a
		// plain dropdown. Disambiguate with the taxonomy's own slug
		// whenever two labels collide, rather than trusting every
		// registered taxonomy's label to be unique.
		$label_counts = [];
		foreach ( $taxonomies as $tax ) {
			$label_counts[ $tax->label ] = ( $label_counts[ $tax->label ] ?? 0 ) + 1;
		}

		foreach ( $taxonomies as $tax ) {
			$label = $tax->label;
			if ( $label_counts[ $label ] > 1 ) {
				$label = sprintf( '%1$s (%2$s)', $label, $tax->name );
			}
			$options[] = [ 'value' => $tax->name, 'label' => $label ];
		}

		return $options;
	}
}
