<?php
/**
 * AAE Advanced Portfolio — atomic (v4) port of the Pro v3 widget
 * `wcf--a-portfolio` (animation-addons-for-elementor-pro/widgets/
 * advance-portfolio/), specifically its Portfolio Three skin.
 *
 * WHY THIS IS NOT A 1:1 PORT OF THE v3 CLASS
 * ------------------------------------------
 * The v3 widget is a thin shell (posts-per-page, image size, title tag, query,
 * plus Title/Image style sections) and NINE `Skin_Base` subclasses that each
 * supply their own DOM, controls and render(). Atomic v4 has no skin concept at
 * all — there is nothing for that "Skin" dropdown to bind to. The equivalent in
 * this codebase is the Loop Grid: ONE widget built from part elements, with each
 * look shipped as a PRESET (see Widgets/LoopGrid/presets/). So the nine skins
 * become nine presets over this one widget, and Portfolio Three is the shape
 * seeded by define_default_children() below.
 *
 * What the v3 skin rendered, and what this reproduces:
 *
 *   .wcf--advance-portfolio.skin-portfolio-three
 *     h2.section-title                 -> e-aae-a-portfolio-title
 *     .posts-list                      -> e-aae-a-portfolio-list
 *       article.item        (per post) -> e-aae-a-portfolio-item
 *         .thumb > a > img             -> e-aae-a-post-image   (existing part)
 *
 * The seed stops at the thumbnail on purpose — see define_default_children().
 * v3's `.content` block (post title + date) is NOT seeded: what a card says is
 * the user's decision, and Portfolio Item takes any child type, so a Flexbox
 * with a heading, a date, a button or nothing at all are all one drag away.
 *
 * WHAT DELIBERATELY DID NOT COME ACROSS
 * -------------------------------------
 *   - The Skin select. No v4 equivalent; presets replace it (above).
 *   - Image Resolution. AAE_A_Post_Image already owns an `image_size` prop, so
 *     duplicating it here would give two controls fighting over one value.
 *   - The Title / Image style sections, and the skin's own layout/content/
 *     section-title style controls. In v3 those existed because a skin renders
 *     fixed markup that can only be styled through `selectors`. Here every
 *     piece is a real element with its own Style panel, which is strictly more
 *     capable — a v4 port that re-added them would be fighting the platform.
 *   - The base skin's ~600 lines of Swiper controls. Those belong to the
 *     slider-type skins (Two, Seven, Nine); Portfolio Three is a plain grid.
 *     A slider look here is the Loop Grid Slider's job.
 *
 * The query itself is NOT reimplemented: AAE_A_Loop_Grid::build_query_args() is
 * already the one place this plugin assembles a loop query (frontend render,
 * AJAX pagination and editor preview all go through it), and it accepts both
 * $$type-wrapped and plain settings. Reusing it keeps this widget's results
 * identical to the Loop Grid's for the same inputs instead of drifting from a
 * second copy of the same sanitising.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancePortfolio;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid;
use WCF_ADDONS\AtomicWidgets\Widgets\PostImage\AAE_A_Post_Image;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Advance_Portfolio extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-advance-portfolio';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-advance-portfolio';
	}

	public function get_title() {
		return esc_html__( 'Advanced Portfolio', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_keywords() {
		return [ 'portfolio', 'project', 'work', 'post', 'loop', 'grid', 'atomic' ];
	}

	public function get_categories(): array {
		return [ 'aae-atomic-general' ];
	}

	/**
	 * Atomic_Element_Base reads the panel category from HERE — get_categories()
	 * is Widget_Base's hook and is never called for an element type, so a
	 * category declared only there silently falls back to Elementor's own
	 * 'v4-elements' bucket. Delegate so both stay in sync.
	 */
	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	protected static function define_props_schema(): array {
		return [
			// Snapshot of this element's own full model, captured by the JS
			// preset-apply engine the first time a preset is applied — see
			// preset-apply.js's SNAPSHOT_REVERT_TYPES / "Reset to Default".
			'aae_preset_snapshot' => String_Prop_Type::make()->default( '' ),

			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Query. Names match AAE_A_Loop_Grid's on purpose — its
			// build_query_args() reads these exact keys, and reusing them is
			// what lets this widget share that one query builder.
			'post_type'      => String_Prop_Type::make()->default( 'post' ),
			'posts_per_page' => Number_Prop_Type::make()->default( 6 ),
			'order_by'       => String_Prop_Type::make()->default( 'date' ),
			'order'          => String_Prop_Type::make()->default( 'desc' ),
			'offset'         => Number_Prop_Type::make()->default( 0 ),

			// The v3 skin read both of these off the parent widget
			// (get_instance_value), so they stay on the root here too and the
			// title part reads them from the render context — the same
			// arrangement AAE_A_Post_Pagination_Preview_Date uses.
			'section_title'     => String_Prop_Type::make()->default( 'WORK' ),
			'section_title_tag' => String_Prop_Type::make()->default( 'h2' ),

			// Applied by the Post Title part inside the repeating item.
			'title_tag' => String_Prop_Type::make()->default( 'h3' ),

			// Scroll animation — the v3 skin's own Animations section. Nothing in
			// PHP reads these beyond serialising them onto the wrapper as
			// `data-animation-settings`, exactly as the v3 skin did, because the
			// animation itself is GSAP in advance-portfolio.js. Keeping the same
			// attribute name and JSON keys makes the ported JS a direct
			// translation of animate_portfolio_content_three() rather than a
			// reinterpretation of it.
			'anim_enable'        => Boolean_Prop_Type::make()->default( false ),
			'anim_enable_editor' => Boolean_Prop_Type::make()->default( false ),
			'anim_pin_start'     => String_Prop_Type::make()->default( 'top top' ),
			'anim_pin_end'       => String_Prop_Type::make()->default( 'bottom bottom' ),
			'anim_breakpoint'    => String_Prop_Type::make()->default( 'mobile' ),

			// The serialised payload the twig prints. Filled in by
			// get_atomic_settings() below, never edited: a twig can only reach
			// values under `settings`, so a render-context value has to be put
			// there first — the same route AAE_A_Post_Pagination_Preview_Date
			// takes for its own text.
			'anim_settings_json' => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		$heading_tags = [
			[ 'value' => 'h1', 'label' => 'H1' ],
			[ 'value' => 'h2', 'label' => 'H2' ],
			[ 'value' => 'h3', 'label' => 'H3' ],
			[ 'value' => 'h4', 'label' => 'H4' ],
			[ 'value' => 'h5', 'label' => 'H5' ],
			[ 'value' => 'h6', 'label' => 'H6' ],
		];

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items(
					[
						AAE_A_Portfolio_Preset_Picker_Control::make()
							->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
							->set_meta( [ 'layout' => 'custom' ] ),
					]
				),

			Section::make()
				->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
				->set_id( 'portfolio_layout' )
				->set_items( [
					Number_Control::bind_to( 'posts_per_page' )
						->set_label( __( 'Posts Per Page', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'title_tag' )
						->set_label( __( 'Post Title Tag', 'animation-addons-for-elementor' ) )
						->set_options( $heading_tags ),
					Text_Control::bind_to( 'section_title' )
						->set_label( __( 'Section Title', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'section_title_tag' )
						->set_label( __( 'Section Title Tag', 'animation-addons-for-elementor' ) )
						->set_options( $heading_tags ),
				] ),

			Section::make()
				->set_label( __( 'Animations', 'animation-addons-for-elementor' ) )
				->set_id( 'portfolio_animations' )
				->set_items( [
					Switch_Control::bind_to( 'anim_enable' )
						->set_label( __( 'Enable', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'anim_enable_editor' )
						->set_label( __( 'Enable Editor Mode', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'anim_pin_start' )
						->set_label( __( 'Title Pin Area Start', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'anim_pin_end' )
						->set_label( __( 'Title Pin Area End', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'anim_breakpoint' )
						->set_label( __( 'Breakpoint', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '', 'label' => __( 'None', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'mobile', 'label' => __( 'Mobile', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'tablet', 'label' => __( 'Tablet', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'desktop', 'label' => __( 'Desktop', 'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_label( __( 'Query', 'animation-addons-for-elementor' ) )
				->set_id( 'portfolio_query' )
				->set_items( [
					Select_Control::bind_to( 'post_type' )
						->set_label( __( 'Source', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_post_type_options() ),
					Select_Control::bind_to( 'order_by' )
						->set_label( __( 'Order By', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'date', 'label' => __( 'Date', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'title', 'label' => __( 'Title', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'menu_order', 'label' => __( 'Menu Order', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'rand', 'label' => __( 'Random', 'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'order' )
						->set_label( __( 'Order', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'desc', 'label' => __( 'Descending', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'asc', 'label' => __( 'Ascending', 'animation-addons-for-elementor' ) ],
						] ),
					Number_Control::bind_to( 'offset' )
						->set_label( __( 'Offset', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	/**
	 * Public post types, for the Source select. Mirrors the Loop Grid's own
	 * list so the two widgets offer the same sources.
	 */
	private function get_post_type_options(): array {
		$options = [];

		if ( ! function_exists( 'get_post_types' ) ) {
			return [ [ 'value' => 'post', 'label' => 'Post' ] ];
		}

		$types = get_post_types( [ 'public' => true ], 'objects' );
		foreach ( $types as $type ) {
			if ( in_array( $type->name, [ 'attachment', 'e-landing-page' ], true ) ) {
				continue;
			}
			$options[] = [
				'value' => $type->name,
				'label' => $type->labels->singular_name ?? $type->name,
			];
		}

		return $options ?: [ [ 'value' => 'post', 'label' => 'Post' ] ];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
			),
		];
	}

	/**
	 * Only seed types that are actually registered — an unknown child type
	 * makes the editor throw ElementTypeNotFound on drop. Copied from
	 * AAE_A_Loop_Grid for the same reason.
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
	 * Seed the STRUCTURE, not a design: section title, the grid, and one card
	 * holding the thumbnail. Nothing else.
	 *
	 * The card deliberately stops at the thumbnail. An earlier revision also
	 * seeded a Content wrapper carrying a Post Title and a Date, i.e. it decided
	 * what every card says and how it is arranged. That is a prebuilt look, and
	 * removing seeded children is more annoying than adding the ones you want —
	 * so the card starts as an image the user builds on, with a Flexbox or
	 * anything else dropped in (Portfolio Item accepts any child type now).
	 *
	 * Everything seeded here is load-bearing: without the list there is no loop
	 * container, and without an item inside it there is nothing to repeat per
	 * post.
	 */
	protected function define_default_children() {
		$card = [];

		if ( self::type_registered( 'e-aae-a-post-image' ) ) {
			$card[] = AAE_A_Post_Image::generate()
				->editor_settings( [ 'title' => 'Thumbnail' ] )
				->build();
		}

		return [
			AAE_A_Portfolio_Title::generate()
				->editor_settings( [ 'title' => 'Section Title' ] )
				->build(),
			AAE_A_Portfolio_List::generate()
				->editor_settings( [ 'title' => 'Posts List' ] )
				->children( [
					AAE_A_Portfolio_Item::generate()
						->editor_settings( [ 'title' => 'Portfolio Item' ] )
						->children( $card )
						->build(),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [
			'e-aae-a-portfolio-title',
			'e-aae-a-portfolio-list',
			'e-flexbox',
			'e-div-block',
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-advance-portfolio' => __DIR__ . '/aae-a-advance-portfolio.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-advance-portfolio-css' ];
	}

	/**
	 * Publish the query for the repeating item, plus the section-title text the
	 * title part renders.
	 *
	 * The item reads `query_args` off the Render_Context stack keyed by THIS
	 * class and runs the loop itself, exactly as AAE_A_Loop_Item does against
	 * AAE_A_Loop_Grid — repeating at the item level rather than the root keeps
	 * non-repeating siblings (the section title) rendering only once.
	 */
	/**
	 * The wrapper's `data-animation-settings` payload.
	 *
	 * Same attribute and same JSON keys the v3 skin emitted
	 * (Skin_Portfolio_Three::get_animation_settings()), `skin` included, so the
	 * ported GSAP in advance-portfolio.js reads exactly what it read before.
	 * `enable`/`enable_editor` are re-encoded as v3's 'yes'/'' strings rather
	 * than booleans for the same reason: the JS compares against 'yes'.
	 */
	public function get_animation_settings_json(): string {
		// parent::get_atomic_settings(), NOT $this->get_atomic_settings(): the
		// override above calls this method, so going through $this would recurse
		// until the stack blew.
		$s = parent::get_atomic_settings();

		$payload = [
			'enable'         => ! empty( $s['anim_enable'] ) ? 'yes' : '',
			'enable_editor'  => ! empty( $s['anim_enable_editor'] ) ? 'yes' : '',
			'pin_area_start' => isset( $s['anim_pin_start'] ) ? (string) $s['anim_pin_start'] : 'top top',
			'pin_area_end'   => isset( $s['anim_pin_end'] ) ? (string) $s['anim_pin_end'] : 'bottom bottom',
			'breakpoint'     => isset( $s['anim_breakpoint'] ) ? (string) $s['anim_breakpoint'] : 'mobile',
			'skin'           => 'skin-portfolio-three',
		];

		return (string) wp_json_encode( $payload );
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$settings['anim_settings_json'] = $this->get_animation_settings_json();

		return $settings;
	}

	protected function define_render_context(): array {
		$s = $this->get_atomic_settings();

		return [
			[
				'context_key' => self::class,
				'context'     => [
					'query_args'        => AAE_A_Loop_Grid::build_query_args(
						(array) $this->get_data( 'settings' ),
						1
					),
					'section_title'     => isset( $s['section_title'] ) ? $s['section_title'] : '',
					'section_title_tag' => isset( $s['section_title_tag'] ) ? $s['section_title_tag'] : 'h2',
					'title_tag'         => isset( $s['title_tag'] ) ? $s['title_tag'] : 'h3',
					'portfolio_id'      => $this->get_id(),
				],
			],
		];
	}
}
