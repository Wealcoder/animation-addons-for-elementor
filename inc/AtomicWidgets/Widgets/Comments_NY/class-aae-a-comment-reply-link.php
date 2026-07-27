<?php
/**
 * AAE Comment Reply Link — "current comment" leaf widget.
 *
 * Outputs core's `get_comment_reply_link()` for the current comment, passing
 * the depth/max_depth AAE_A_Comment_Item is currently tracking so core's
 * `comment-reply` script (enqueued from AAE_A_Comment_Form) correctly moves
 * the reply form under the clicked comment and hides the link past max
 * thread depth — identical behavior to a classic theme's comments.php.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Comments;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Comment_Reply_Link extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-comment-reply-link';
	}

	public function get_title() {
		return esc_html__( 'AAE Comment Reply Link', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-reply';
	}

	public function get_keywords() {
		return [ 'comment', 'reply', 'link', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'reply_text' => String_Prop_Type::make()->default( __( 'Reply', 'animation-addons-for-elementor' ) ),
			'login_text' => String_Prop_Type::make()->default( __( 'Log in to Reply', 'animation-addons-for-elementor' ) ),
			'reply_html' => String_Prop_Type::make()->default( '' )->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Reply Link Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'reply_text' )
						->set_label( __( 'Reply Text', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'login_text' )
						->set_label( __( 'Login Prompt Text', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_prop( 'display', String_Prop_Type::generate( 'block' ) ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-comment-reply-link' => __DIR__ . '/aae-a-comment-reply-link.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-comments-css' ];
	}

	public function get_atomic_settings(): array {
		$settings   = parent::get_atomic_settings();
		$comment_id = get_comment_ID();

		$reply_text = ! empty( $settings['reply_text'] ) ? $settings['reply_text'] : __( 'Reply', 'animation-addons-for-elementor' );
		$login_text = ! empty( $settings['login_text'] ) ? $settings['login_text'] : __( 'Log in to Reply', 'animation-addons-for-elementor' );

		if ( $comment_id && function_exists( 'get_comment_reply_link' ) ) {
			wp_enqueue_script( 'comment-reply' );

			$settings['reply_html'] = (string) get_comment_reply_link( [
				'reply_text' => $reply_text,
				'login_text' => $login_text,
				'depth'      => AAE_A_Comment_Item::$current_depth,
				'max_depth'  => AAE_A_Comment_Item::$max_depth,
			] );
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['reply_html'] = '<a class="comment-reply-link" href="#">' . esc_html( $reply_text ) . '</a>';
		} else {
			$settings['reply_html'] = '';
		}

		return $settings;
	}
}
