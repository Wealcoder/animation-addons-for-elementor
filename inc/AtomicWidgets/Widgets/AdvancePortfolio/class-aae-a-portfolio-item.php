<?php
/**
 * AAE Advanced Portfolio — Item.
 *
 * The v3 skin's `article.item`, and the element that REPEATS: it reads the
 * query the root published on the Render_Context stack, runs the WP_Query, and
 * renders its own whole subtree once per post. Same mechanism as
 * AAE_A_Loop_Item against AAE_A_Loop_Grid, and for the same reasons:
 *
 *   - repeating HERE and not at the root means the root's non-repeating
 *     children (the section title) still render exactly once;
 *   - `the_post()` inside the loop sets up the global post, which is what makes
 *     the ordinary current-post parts (Post Image, Post Title) resolve to a
 *     different post on each pass without knowing anything about this widget;
 *   - it calls `$this->render()`, NOT parent::print_content() — the latter is
 *     Element_Base's bare children loop and would skip this element's own
 *     wrapper, dropping the atomic style class (its background, radius, etc.)
 *     on the frontend.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancePortfolio;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Portfolio_Item extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-portfolio-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-portfolio-item';
	}

	public function get_title() {
		return esc_html__( 'Portfolio Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post';
	}

	public function show_in_panel() {
		return false;
	}

	/**
	 * The one that actually hides an ELEMENT type from the panel.
	 *
	 * show_in_panel() alone is not enough here: it is the Widget_Base hook, so
	 * it works for the leaf parts (Section Title, Date — both Atomic_Widget_Base)
	 * but is never consulted for an Atomic_Element_Base. Verified in the live
	 * editor: with only show_in_panel(), this part still arrived in
	 * elementor.widgetsCache with show_in_panel: true, while Loop Layout and
	 * Loop Item — which declare should_show_in_panel() — arrived false.
	 */
	public function should_show_in_panel() {
		return false;
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

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
					->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
					->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
					// Load-bearing, not cosmetic. Two things need it: the
					// stylesheet's `:nth-child(even) { top: 50% }` stagger,
					// which does nothing on a static box, and any child the
					// user positions absolutely (an overlay caption being the
					// obvious one) — that child resolves against THIS box only
					// while the item is positioned. v3 states it explicitly for
					// the same reasons
					// (`.posts-list .item { position: relative; top: 0; left: 0 }`).
					// Note the frontend JS also writes position:relative in its
					// pre-pose; this keeps the box correct before GSAP runs and
					// when the animation is off.
					->add_prop( 'position', String_Prop_Type::generate( 'relative' ) )
			),
		];
	}

	/**
	 * Unrestricted — an empty list is how `Atomic_Element_Base` says "any
	 * child", and it is what core `e-flexbox` / `e-div-block` rely on.
	 *
	 * The card is the user's to compose. The previous allow-list (post image,
	 * post title, content, date, flexbox, div-block, heading, paragraph) ruled
	 * out a button, an icon, a divider, a video — none of which this element
	 * has any reason to refuse. Whatever is dropped in here is rendered once
	 * per post by print_content(), which is the only thing this class actually
	 * needs to guarantee.
	 *
	 * The restriction that DOES matter is one level up: Posts List still accepts
	 * only Portfolio Item, because a child of the grid that is not the repeating
	 * item would render a single time, outside the loop.
	 */
	protected function define_allowed_child_types() {
		return [];
	}

	protected function define_default_children() {
		// Seeded by the root, so this stays empty.
		return [];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-portfolio-item' => __DIR__ . '/aae-a-portfolio-item.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-advance-portfolio-css' ];
	}

	/**
	 * Repeat the whole card once per queried post.
	 *
	 * With no query context — the item opened on its own in the editor — fall
	 * back to a single ordinary render so the card stays editable.
	 */
	public function print_content() {
		$ctx = Render_Context::get( AAE_A_Advance_Portfolio::class );

		if ( empty( $ctx ) || empty( $ctx['query_args'] ) ) {
			$this->render();
			return;
		}

		$query = new \WP_Query( $ctx['query_args'] );

		if ( ! $query->have_posts() ) {
			echo '<div class="aae-a-advance-portfolio-empty">'
				. esc_html__( 'No posts found.', 'animation-addons-for-elementor' )
				. '</div>';
			return;
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			$this->render();
		}

		wp_reset_postdata();
	}
}
