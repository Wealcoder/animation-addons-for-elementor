<?php
/**
 * AAE Post Content — native Atomic 4 widget.
 *
 * A 1:1 port of the legacy Elementor widget WCF_ADDONS\Widgets\Post_Content
 * (widgets/post-content.php). It renders the current post's content, honoring
 * the Elementor builder content, the `the_content` filters, the AAE Theme
 * Builder "preview" document resolution, password-protected posts and paged
 * posts (`wp_link_pages`) — identical to the original render_post_content().
 *
 * The legacy widget exposed Text Color + Typography controls that were gated
 * behind an `enable_inline_style` condition which this widget never defined, so
 * those controls never actually surfaced. Under Atomic 4 text color and
 * typography are provided natively by the Style tab (they cascade onto the
 * rendered content), so full styling parity is preserved and improved.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\PostContent;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Plugin;
use Elementor\Utils;
use WCF_ADDONS\WCF_Theme_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Post_Content extends Atomic_Widget_Base {

	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	public static function get_element_type(): string {
		return 'e-aae-a-post-content';
	}

	public function get_title() {
		return esc_html__( 'Post Content', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-content';
	}

	public function get_keywords() {
		return [ 'content', 'post', 'atomic', 'dynamic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-post'];
	}

	/**
	 * The rendered post content, exposed to the editor's client-side (twig)
	 * render so the canvas shows real content. The frontend is served by the
	 * PHP render() override below (which reproduces the legacy builder-content
	 * pipeline verbatim); this key exists purely for the editor preview.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$settings['post_content'] = $this->get_preview_content();

		return $settings;
	}

	/**
	 * Build the content HTML shown inside the editor preview iframe.
	 *
	 * Uses the SAME render_post_content() pipeline as the frontend, captured via
	 * output buffering, so the editor preview matches the published output. When
	 * the edited document has no real content of its own (a fresh template), fall
	 * back to the shared sample post; and when even that is empty, show a clearly
	 * labeled placeholder so the user recognizes this is the Post Content widget.
	 *
	 * This whole fallback is EDITOR-ONLY — the frontend render() runs the pure
	 * legacy pipeline, so a published page never shows the placeholder.
	 *
	 * @return string
	 */
	private function get_preview_content(): string {
		// Bail BEFORE rendering when this isn't the editor.
		//
		// This value is only ever read by the editor's twig render — the
		// frontend goes through render() below. Running the pipeline here
		// anyway did more than waste a render: render_post_content()'s
		// $did_posts recursion guard is one-shot per post id, and render()
		// reaches this method first (via get_render_classes() ->
		// get_atomic_settings()). The buffered call consumed the guard and the
		// real render right after it returned early, so the widget printed an
		// empty div on every theme-builder single template.
		if ( ! Plugin::$instance->editor->is_edit_mode() ) {
			return '';
		}

		ob_start();
		$this->render_post_content( false, false );
		$content = trim( (string) ob_get_clean() );

		if ( '' !== $content ) {
			return $content;
		}

		// Editor with no real content: preview the shared sample post, or the
		// labeled placeholder when none is available — same source as the baked
		// schema default, so the on-drop preview and the refreshed render match.
		return self::build_editor_preview_default();
	}

	/**
	 * Labeled placeholder shown in the editor when there is no post content to
	 * preview. Makes the empty widget recognizable on the canvas.
	 *
	 * @return string
	 */
	private static function get_editor_placeholder(): string {
		$title = esc_html__( 'AAE Post Content', 'animation-addons-for-elementor' );
		$desc  = esc_html__( 'The current post\'s content will be displayed here on the frontend.', 'animation-addons-for-elementor' );

		return sprintf(
			'<div class="aae-a-post-content-placeholder" style="border:1px dashed #c3c4c7;border-radius:6px;padding:24px;text-align:center;color:#50575e;background:#f6f7f7;">'
				. '<span class="eicon-post-content" style="font-size:28px;display:block;margin-bottom:10px;opacity:.7;"></span>'
				. '<strong style="display:block;font-size:14px;margin-bottom:4px;">%1$s</strong>'
				. '<span style="font-size:12px;line-height:1.5;">%2$s</span>'
				. '</div>',
			$title,
			$desc
		);
	}

	protected static function define_props_schema(): array {
		// Bake an editor preview into the prop DEFAULT so freshly-dropped widgets
		// render content immediately. The editor renders the widget client-side
		// from the twig using the schema defaults baked into the config — at that
		// moment get_atomic_settings() (which recomputes post_content on the
		// server) has not run for the new element, so without a non-empty default
		// the canvas would be blank on drop. get_atomic_settings() still refreshes
		// this value on every server render for accuracy.
		$preview = '';
		if ( class_exists( '\Elementor\Plugin' ) && Plugin::$instance->editor->is_edit_mode() ) {
			$preview = self::build_editor_preview_default();
		}

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			// Populated at render time from the post; never edited directly.
			'post_content' => String_Prop_Type::make()->default( $preview )->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	/**
	 * Editor-only default content for a freshly-dropped widget: the shared sample
	 * post's content, or the labeled placeholder when none is available. Static so
	 * it can seed the prop default in define_props_schema().
	 *
	 * @return string
	 */
	private static function build_editor_preview_default(): string {
		if ( class_exists( '\WCF_ADDONS\AtomicWidgets\Atomic' ) ) {
			$sample = \WCF_ADDONS\AtomicWidgets\Atomic::get_sample_post();
			if ( $sample ) {
				$content = trim( (string) apply_filters( 'the_content', $sample->post_content ) );
				if ( '' !== $content ) {
					return $content;
				}
			}
		}

		return self::get_editor_placeholder();
	}

	protected function define_atomic_controls(): array {
		// The legacy widget's only real controls (Text Color + Typography) were
		// gated behind an `enable_inline_style` condition it never defined, so no
		// content-tab controls ever appeared. Under Atomic 4 those are handled
		// natively by the Style tab, so there are no bespoke settings to expose.
		return [];
	}

	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'display' => String_Prop_Type::generate( 'block' ),
						'width'   => String_Prop_Type::generate( '100%' ),
					] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-content' => __DIR__ . '/aae-a-post-content.html.twig',
		];
	}

	public function get_style_depends(): array {
		return []; // Styling is native (base styles + Style tab). No external CSS.
	}

	/**
	 * Frontend render.
	 *
	 * Overrides the Has_Template twig render on the SERVER so the published page
	 * runs the full legacy builder-content pipeline (the editor still renders the
	 * widget client-side from the twig template + get_atomic_settings()). The
	 * post content is wrapped in the widget's root element carrying the atomic
	 * base-style + user classes so the Style tab (color, typography, spacing,
	 * border, background, alignment, …) applies exactly as it would to the twig
	 * root.
	 */
	protected function render() {
		$classes = $this->get_render_classes();

		echo '<div class="' . esc_attr( $classes ) . '" data-interaction-id="' . esc_attr( (string) $this->get_interaction_id() ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Post CSS should not be printed here because it overrides the already
		// existing post CSS (matches the legacy render(): render_post_content(false, false)).
		$this->render_post_content( false, false );

		echo '</div>';
	}

	/**
	 * The class attribute for the root element: atomic base-style class(es) +
	 * any user-assigned classes — mirrors what the twig emits
	 * (`settings.classes | merge([base_styles.base])`).
	 *
	 * @return string
	 */
	private function get_render_classes(): string {
		$dictionary = $this->get_base_styles_dictionary();
		$base_class = isset( $dictionary[ self::BASE_STYLE_KEY ] ) ? $dictionary[ self::BASE_STYLE_KEY ] : '';

		$settings     = $this->get_atomic_settings();
		$user_classes = isset( $settings['classes'] ) ? (array) $settings['classes'] : [];

		$classes = array_merge( $user_classes, array_filter( [ $base_class ] ) );

		return implode( ' ', array_filter( array_map( 'trim', $classes ) ) );
	}

	/**
	 * Render post content.
	 *
	 * Verbatim port of WCF_ADDONS\Widgets\Post_Content::render_post_content().
	 * Keeps every behavior: recursion guard, password form, Theme Builder
	 * preview-document resolution, edit-mode toggling for inline CSS, the
	 * builder content pipeline, `the_content` filtering and `wp_link_pages`.
	 *
	 * @param boolean $with_wrapper Whether to wrap the content with a div.
	 * @param boolean $with_css     Decides whether to print inline CSS before the post content.
	 *
	 * @return void
	 */
	public function render_post_content( $with_wrapper = false, $with_css = true ) {
		static $did_posts = [];
		static $level = 0;
		$post = get_post();

		if ( ! $post ) {
			return;
		}

		if ( 'wcf-addons-template' === get_post_type() ) {
			$recent_posts = wp_get_recent_posts( array(
				'numberposts' => 1,
				'post_status' => 'publish',
			) );

			$post_id = get_the_id();

			if ( isset( $recent_posts[0] ) ) {
				$post_id = $recent_posts[0]['ID'];
			}

			$post = get_post( $post_id );
		}

		if ( ! $post ) {
			return;
		}

		if ( post_password_required( $post->ID ) ) {
			// PHPCS - `get_the_password_form`. is safe.
			echo get_the_password_form( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			return;
		}

		// Avoid recursion
		if ( isset( $did_posts[ $post->ID ] ) ) {
			return;
		}

		$level ++;
		$did_posts[ $post->ID ] = true;
		// End avoid recursion

		$editor       = Plugin::$instance->editor;
		$is_edit_mode = $editor->is_edit_mode();

		if ( Plugin::$instance->preview->is_preview_mode( $post->ID ) ) {
			$content = Plugin::$instance->preview->builder_wrapper( '' ); // XSS ok
		} else {

			/**
			 * ThemeBuilder
			 */
			$document = class_exists( '\WCF_ADDONS\WCF_Theme_Builder' ) ? WCF_Theme_Builder::get_document( $post->ID ) : null;
			// On view theme document show it's preview content.
			if ( $document ) {
				$preview_type = $document->get_settings( 'preview_type' );
				$preview_id   = $document->get_settings( 'preview_id' );

				if ( ! empty( $preview_type ) && 0 === strpos( $preview_type, 'single' ) && ! empty( $preview_id ) ) {
					$post = get_post( $preview_id );

					if ( ! $post ) {
						$level --;

						return;
					}
				}
			}

			// Set edit mode as false, so don't render settings and etc. use the $is_edit_mode to indicate if we need the CSS inline
			$editor->set_edit_mode( false );

			// Print manually (and don't use `the_content()`) because it's within another `the_content` filter, and the Elementor filter has been removed to avoid recursion.
			$content = Plugin::$instance->frontend->get_builder_content( $post->ID, $with_css );

			Plugin::$instance->frontend->remove_content_filter();

			if ( empty( $content ) ) {
				// Split to pages.
				setup_postdata( $post );

				/** This filter is documented in wp-includes/post-template.php */
				// PHPCS - `get_the_content` is safe.
				//
				// $post must be passed explicitly. Inside an AAE theme-builder
				// template the GLOBAL post is the `wcf-addons-template` (whose
				// post_content is empty), and setup_postdata() does not
				// reassign $GLOBALS['post'] — so the argument-less call read
				// the template and this widget rendered an empty div on every
				// single-post template.
				echo apply_filters( 'the_content', get_the_content( null, false, $post ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				wp_link_pages( [
					'before'      => '<div class="page-links elementor-page-links"><span class="page-links-title elementor-page-links-title">' . esc_html__( 'Pages:', 'animation-addons-for-elementor' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'pagelink'    => '<span class="screen-reader-text">' . esc_html__( 'Page', 'animation-addons-for-elementor' ) . ' </span>%',
					'separator'   => '<span class="screen-reader-text">, </span>',
				] );

				Plugin::$instance->frontend->add_content_filter();

				$level --;

				// Restore edit mode state
				Plugin::$instance->editor->set_edit_mode( $is_edit_mode );

				return;
			} else {
				Plugin::$instance->frontend->remove_content_filters();
				$content = apply_filters( 'the_content', $content );
				Plugin::$instance->frontend->restore_content_filters();
			}
		} // End if().

		// Restore edit mode state
		Plugin::$instance->editor->set_edit_mode( $is_edit_mode );

		if ( $with_wrapper ) {
			// PHPCS - should not be escaped.
			echo '<div class="elementor-post__content">' . balanceTags( $content, true ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$level --;

		if ( 0 === $level ) {
			$did_posts = [];
		}
	}
}
