<?php
/**
 * AAE Comment Form — wraps WordPress's native `comment_form()`.
 *
 * Per the project decision to keep the reply form on WP core's own
 * submission pipeline (nonces, spam checks, `wp_new_comment`, the
 * `comment-reply` moderation/threading JS) rather than re-implementing it as
 * atomic form fields — this is a thin, restyleable wrapper, not a new form
 * engine.
 *
 * Same dual render path as AAE_A_Post_Content: `render()` is overridden to
 * call the REAL `comment_form()` on the frontend (bypassing Twig, since a
 * WP hook-driven function can't run client-side); `get_atomic_settings()`
 * captures that same call via output buffering for the editor's Twig-based
 * canvas preview, refreshed on every server round-trip.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Comments;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Comment_Form extends Atomic_Widget_Base {
	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	public static function get_element_type(): string {
		return 'e-aae-a-comment-form';
	}

	public function get_title() {
		return esc_html__( 'AAE Comment Form', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_keywords() {
		return [ 'comment', 'form', 'reply', 'submit', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'             => Classes_Prop_Type::make()->default( [] ),
			'attributes'          => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'title_reply'         => String_Prop_Type::make()->default( __( 'Leave a Comment', 'animation-addons-for-elementor' ) ),
			'label_submit'        => String_Prop_Type::make()->default( __( 'Post Comment', 'animation-addons-for-elementor' ) ),
			'comment_placeholder' => String_Prop_Type::make()->default( __( 'Write your comment here…', 'animation-addons-for-elementor' ) ),
			'show_logged_in_as'   => Boolean_Prop_Type::make()->default( true ),

			// Editor-only preview HTML, refreshed every server render.
			'preview_html'        => String_Prop_Type::make()->default( '' )->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Form Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'title_reply' )
						->set_label( __( 'Form Title', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'label_submit' )
						->set_label( __( 'Submit Button Text', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'comment_placeholder' )
						->set_label( __( 'Comment Placeholder', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_logged_in_as' )
						->set_label( __( 'Show "Logged in as…" Notice', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
						->add_prop( 'width', String_Prop_Type::generate( '100%' ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-comment-form' => __DIR__ . '/aae-a-comment-form.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-comments-css' ];
	}

	/**
	 * Editor-canvas preview: capture the real `comment_form()` output so the
	 * Twig-based client render shows the actual form. The frontend never uses
	 * this — see render() below.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['preview_html'] = $this->capture_form( $settings );
		} else {
			$settings['preview_html'] = '';
		}

		return $settings;
	}

	/**
	 * Frontend render — bypasses Twig and calls the real, native
	 * `comment_form()` directly, so submissions run through WP core's actual
	 * pipeline (nonce, spam checks, `wp_new_comment`).
	 */
	protected function render() {
		$settings = $this->get_settings_for_display_raw();
		$classes  = $this->get_render_classes();

		echo '<div class="' . esc_attr( $classes ) . '" data-interaction-id="' . esc_attr( (string) $this->get_interaction_id() ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$this->print_form( $settings );

		echo '</div>';
	}

	/**
	 * Raw (unwrapped) plain settings — used by both render paths so
	 * title/label/placeholder text stays consistent whichever runs.
	 */
	private function get_settings_for_display_raw(): array {
		return $this->get_atomic_settings();
	}

	private function capture_form( array $settings ): string {
		$post_id = AAE_A_Comments_Ny::resolve_post_id();
		if ( ! $post_id ) {
			return '';
		}

		ob_start();
		$this->print_form( $settings, $post_id );
		return (string) ob_get_clean();
	}

	private function print_form( array $settings, int $post_id = 0 ): void {
		if ( ! $post_id ) {
			$post_id = (int) get_the_ID();
		}
		if ( ! $post_id ) {
			return;
		}

		if ( ! comments_open( $post_id ) ) {
			echo '<p class="aae-a-comment-form-closed">' . esc_html__( 'Comments are closed.', 'animation-addons-for-elementor' ) . '</p>';
			return;
		}

		if ( get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		comment_form( $this->build_comment_form_args( $settings ), $post_id );
	}

	private function build_comment_form_args( array $settings ): array {
		$title_reply = ! empty( $settings['title_reply'] ) ? $settings['title_reply'] : __( 'Leave a Comment', 'animation-addons-for-elementor' );
		$label_submit = ! empty( $settings['label_submit'] ) ? $settings['label_submit'] : __( 'Post Comment', 'animation-addons-for-elementor' );
		$placeholder = ! empty( $settings['comment_placeholder'] ) ? $settings['comment_placeholder'] : __( 'Write your comment here…', 'animation-addons-for-elementor' );
		$show_logged_in_as = ! isset( $settings['show_logged_in_as'] ) || $settings['show_logged_in_as'];

		$args = [
			'title_reply'  => esc_html( $title_reply ),
			'label_submit' => esc_html( $label_submit ),
			'class_form'   => 'aae-a-comment-form-inner',
			'class_submit' => 'aae-a-comment-form-submit',
			'comment_field' => '<p class="comment-form-comment"><label for="comment">'
				. esc_html__( 'Comment', 'animation-addons-for-elementor' )
				. '</label><textarea id="comment" name="comment" placeholder="'
				. esc_attr( $placeholder )
				. '" cols="45" rows="6" maxlength="65525" required="required"></textarea></p>',
		];

		if ( ! $show_logged_in_as ) {
			$args['logged_in_as'] = '';
		}

		return $args;
	}

	/**
	 * The class attribute for the root element: atomic base-style class(es) +
	 * any user-assigned classes — mirrors what the twig emits.
	 */
	private function get_render_classes(): string {
		$dictionary = $this->get_base_styles_dictionary();
		$base_class = isset( $dictionary[ self::BASE_STYLE_KEY ] ) ? $dictionary[ self::BASE_STYLE_KEY ] : '';

		$settings     = $this->get_atomic_settings();
		$user_classes = isset( $settings['classes'] ) ? (array) $settings['classes'] : [];

		$classes = array_merge( $user_classes, array_filter( [ $base_class, 'aae-a-comment-form' ] ) );

		return implode( ' ', array_filter( array_map( 'trim', $classes ) ) );
	}
}
