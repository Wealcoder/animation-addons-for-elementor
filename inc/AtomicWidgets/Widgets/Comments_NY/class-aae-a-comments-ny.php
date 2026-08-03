<?php
/**
 * AAE Post Comments — atomic NESTED element.
 *
 * DISABLED — 2026-07-27: the senior dev is building the comments/reply-form
 * feature himself. This whole family is kept (not deleted) for reference;
 * registration is commented out in class-atomic.php (all 3 spots). Renamed
 * to the "_Ny" / "-ny" suffix so it can't collide with whatever the senior
 * dev's own implementation ends up calling itself.
 *
 * Queries the current post's comments and repeats its own authored child
 * subtree (the "comment item") once per comment — same architecture as
 * AAE_A_Loop_Grid, but querying WP_Comment_Query instead of WP_Query. The
 * comment item is a real atomic element tree living INSIDE this element, so
 * every piece (avatar/author/date/content/reply-link) is a selectable,
 * restyleable, and freely-composable atomic child — users can drop in extra
 * Paragraph / Image / Button / Heading widgets alongside the comment pieces.
 *
 * A "Comment Form" child (native `comment_form()`, see class-aae-a-comment-
 * form.php) is seeded alongside the list so replies can actually be posted.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Comments;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-aae-a-comment-list.php';
require_once __DIR__ . '/class-aae-a-comment-item.php';
require_once __DIR__ . '/class-aae-a-comment-avatar.php';
require_once __DIR__ . '/class-aae-a-comment-author.php';
require_once __DIR__ . '/class-aae-a-comment-date.php';
require_once __DIR__ . '/class-aae-a-comment-content.php';
require_once __DIR__ . '/class-aae-a-comment-reply-link.php';
require_once __DIR__ . '/class-aae-a-comment-form.php';

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Comments_Ny extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-comments-ny';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-comments-ny';
	}

	public function get_title() {
		return esc_html__( 'Post Comments', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-comments';
	}

	public function get_keywords() {
		return [ 'comment', 'comments', 'discussion', 'reply', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'         => Classes_Prop_Type::make()->default( [] ),
			'attributes'      => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'comment_order'   => String_Prop_Type::make()->default( 'asc' ),
			'show_pings'      => Boolean_Prop_Type::make()->default( false ),
			'no_comments_text' => String_Prop_Type::make()->default(
				__( 'No comments yet. Be the first to comment!', 'animation-addons-for-elementor' )
			),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'aae_comments_query' )
				->set_label( __( 'Comments', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'comment_order' )
						->set_label( __( 'Order', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'asc',  'label' => __( 'Oldest First', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'desc', 'label' => __( 'Newest First', 'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'show_pings' )
						->set_label( __( 'Show Pingbacks/Trackbacks', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'no_comments_text' )
						->set_label( __( 'No Comments Text', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
						->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 32, 'unit' => 'px' ] ) )
						->add_prop( 'padding', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
				),
		];
	}

	/**
	 * Whether an atomic widget/element type is actually registered in this
	 * request. A dropped child whose type the editor doesn't know throws
	 * "ElementTypeNotFound" — e.g. the AAE comment pieces are dashboard-
	 * toggleable and may be disabled. So we only seed children whose type
	 * resolves. (Same helper as AAE_A_Loop_Grid.)
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
	 * Seed one comment-item card (avatar/author/date/content/reply-link) plus
	 * a native reply form, so the element is usable the moment it's dropped.
	 * The user edits this subtree in the canvas; the item is repeated once
	 * per comment at render (see AAE_A_Comment_Item::print_content()).
	 */
	protected function define_default_children() {
		$item_children = [];

		foreach ( [
			'e-aae-a-comment-avatar'     => AAE_A_Comment_Avatar::class,
			'e-aae-a-comment-author'     => AAE_A_Comment_Author::class,
			'e-aae-a-comment-date'       => AAE_A_Comment_Date::class,
			'e-aae-a-comment-content'    => AAE_A_Comment_Content::class,
			'e-aae-a-comment-reply-link' => AAE_A_Comment_Reply_Link::class,
		] as $type => $class ) {
			if ( self::type_registered( $type ) ) {
				$item_children[] = $class::generate()
					->editor_settings( [ 'title' => $class::get_element_type() ] )
					->build();
			}
		}

		$tree = [
			AAE_A_Comment_List::generate()
				->editor_settings( [ 'title' => 'Comment List' ] )
				->is_locked( true )
				->children( [
					AAE_A_Comment_Item::generate()
						->editor_settings( [ 'title' => 'Comment Item' ] )
						->is_locked( true )
						->children( $item_children )
						->build(),
				] )
				->build(),
		];

		if ( self::type_registered( 'e-aae-a-comment-form' ) ) {
			$tree[] = AAE_A_Comment_Form::generate()
				->editor_settings( [ 'title' => 'Comment Form' ] )
				->build();
		}

		return $tree;
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-comments-ny' => __DIR__ . '/aae-a-comments-ny.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-comments-css' ];
	}

	/**
	 * Publish the comment query to descendants via the atomic Render_Context
	 * stack. AAE_A_Comment_Item reads this to build its get_comments() call.
	 * Keyed by this class so only our descendants read it.
	 *
	 * @see \Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context
	 */
	protected function define_render_context(): array {
		$s = $this->get_atomic_settings();

		$post_id = self::resolve_post_id();

		$order = ( isset( $s['comment_order'] ) && 'desc' === strtolower( (string) $s['comment_order'] ) ) ? 'DESC' : 'ASC';
		$threaded    = (bool) get_option( 'thread_comments', true );
		$thread_depth = max( 1, (int) get_option( 'thread_comments_depth', 5 ) );

		$query_args = [];
		if ( $post_id ) {
			$query_args = [
				'post_id' => $post_id,
				'status'  => 'approve',
				'order'   => $order,
			];
			if ( empty( $s['show_pings'] ) ) {
				$query_args['type'] = 'comment';
			}
			if ( $threaded ) {
				$query_args['hierarchical'] = 'threaded';
			}
		}

		return [
			[
				'context_key' => self::class,
				'context'     => [
					'query_args'       => $query_args,
					'threaded'         => $threaded,
					'thread_depth'     => $thread_depth,
					'no_comments_text' => isset( $s['no_comments_text'] ) ? $s['no_comments_text'] : '',
				],
			],
		];
	}

	/**
	 * The post whose comments this element shows. A live singular page
	 * resolves from the main query; the editor (e.g. editing a non-singular
	 * template) falls back to the shared sample post so the list previews
	 * with real data instead of rendering empty.
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
}
