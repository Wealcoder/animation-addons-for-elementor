<?php
/**
 * AAE Comment Item — atomic container repeated once per comment.
 *
 * Reads the query published by AAE_A_Comments on the Render_Context stack,
 * runs get_comments(), and repeats this element's own authored Twig once per
 * comment — same architecture as AAE_A_Loop_Item, but walking a comment tree
 * instead of a flat post list.
 *
 * Per iteration, `$GLOBALS['comment']` is set to the current WP_Comment
 * BEFORE rendering — exactly like `the_post()` sets the global `$post` in a
 * normal loop. This is what lets the child "current comment" widgets
 * (Avatar/Author/Date/Content/Reply Link) call core template tags with no
 * arguments (`get_comment_author()`, `get_avatar( get_comment_ID() )`, …) and
 * automatically resolve to the right comment, with no custom context wiring.
 *
 * Threaded replies: when the site has "thread_comments" enabled, the query
 * runs `'hierarchical' => 'threaded'`, which makes WP_Comment_Query return
 * only top-level comments with each one's full descendant tree already
 * attached via `WP_Comment::get_children()`. We walk that tree recursively,
 * re-rendering this SAME element subtree at each depth and wrapping replies
 * in an indenting `.aae-a-comment-children` div — capped at the site's
 * "thread_comments_depth" option, matching core `wp_list_comments()`.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Comments;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Comment_Item extends Atomic_Element_Base {
	use Has_Element_Template;

	/**
	 * Depth of the comment currently being rendered (1 = top level). Read by
	 * AAE_A_Comment_Reply_Link so `get_comment_reply_link()` receives the
	 * right `depth`/`max_depth` args (core's comment-reply.js needs these to
	 * hide the link past max depth and to move the form to the right spot).
	 *
	 * @var int
	 */
	public static $current_depth = 1;

	/** @var int */
	public static $max_depth = 5;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-comment-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-comment-item';
	}

	public function get_title() {
		return esc_html__( 'Comment Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-container';
	}

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

	protected function define_default_children(): array {
		return [];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
						->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
						->add_prop( 'padding', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-comment-item' => __DIR__ . '/aae-a-comment-item.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-comments-css' ];
	}

	/**
	 * Repeat the whole comment-item card once per queried (top-level)
	 * comment, recursing into replies when threading is on.
	 *
	 * In the editor / when there's no query context (item edited in
	 * isolation), fall back to a single normal render so the card is still
	 * editable — the leaf widgets inside each fall back to sample text.
	 */
	public function print_content() {
		$ctx = Render_Context::get( AAE_A_Comments_Ny::class );

		if ( empty( $ctx ) || empty( $ctx['query_args'] ) ) {
			$this->render();
			return;
		}

		$comments = get_comments( $ctx['query_args'] );

		if ( ! $comments ) {
			echo '<div class="aae-a-comments-empty">'
				. esc_html( ! empty( $ctx['no_comments_text'] ) ? $ctx['no_comments_text'] : __( 'No comments yet.', 'animation-addons-for-elementor' ) )
				. '</div>';
			return;
		}

		self::$max_depth = ! empty( $ctx['thread_depth'] ) ? (int) $ctx['thread_depth'] : 5;

		$this->render_comment_set( $comments, 1, ! empty( $ctx['threaded'] ) );

		unset( $GLOBALS['comment'] );
	}

	/**
	 * @param \WP_Comment[] $comments
	 */
	private function render_comment_set( array $comments, int $depth, bool $threaded ): void {
		foreach ( $comments as $comment ) {
			if ( ! ( $comment instanceof \WP_Comment ) ) {
				continue;
			}

			$GLOBALS['comment']  = $comment;
			self::$current_depth = $depth;

			// Full Twig render of THIS element (wrapper + children) — not
			// parent::print_content(), which would skip the item's own
			// wrapper div and drop its atomic style class on the frontend.
			$this->render();

			if ( $threaded && $depth < self::$max_depth ) {
				$children = $comment->get_children();
				if ( $children ) {
					echo '<div class="aae-a-comment-children">';
					$this->render_comment_set( array_values( $children ), $depth + 1, $threaded );
					echo '</div>';
				}
			}
		}
	}
}
