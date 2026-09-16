<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

use Elementor\Modules\AtomicWidgets\Styles\Atomic_Widget_Styles;
use Elementor\Modules\AtomicWidgets\Styles\Styles_Renderer;
use Elementor\Modules\AtomicWidgets\Utils\Utils as Atomic_Utils;
use Elementor\Plugin;
use WP_Query;


defined( 'ABSPATH' ) || die();

class Ajax_Handler {

	public static function init() {
		add_action( 'wp_ajax_live_search', array( __CLASS__, 'handle_live_search' ) );
		add_action( 'wp_ajax_nopriv_live_search', array( __CLASS__, 'handle_live_search' ) );

		// Mailchimp AJAX handlers (editor configuration).
		add_action( 'wp_ajax_mailchimp_api', array( __CLASS__, 'mailchimp_lists' ) );
		add_action( 'wp_ajax_wcf_mailchimp_list_fields', array( __CLASS__, 'wcf_mailchimp_list_fields' ) );

		// Mailchimp frontend subscription.
		add_action( 'wp_ajax_wcf_mailchimp_ajax', array( __CLASS__, 'mailchimp_prepare_ajax' ) );
		add_action( 'wp_ajax_nopriv_wcf_mailchimp_ajax', array( __CLASS__, 'mailchimp_prepare_ajax' ) );

		add_action( 'wp_ajax_wcf_load_popup_content', array( __CLASS__, 'wcf__popup_content' ) );
		add_action( 'wp_ajax_nopriv_wcf_load_popup_content', array( __CLASS__, 'wcf__popup_content' ) );
	}

	/**
	 * wcf popup content Ajax call.
	 *
	 * @return void
	 */
	public static function wcf__popup_content() {
		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'wcf-addons-frontend' ) ) {
			wp_send_json_error( 'Missing or Invalid nonce' );
		}

		$post_id    = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
		$element_id = isset( $_REQUEST['element_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['element_id'] ) ) : '';

		if ( ! $post_id || '' === $element_id ) {
			wp_send_json_error( 'Missing post_id or element_id', 400 );
		}

		// The nonce is printed on every public page, so it proves nothing about
		// WHICH document this visitor may read -- `post_id` is whatever the
		// request asked for. Without this check any visitor could name a draft,
		// pending, private or password-protected post and receive its rendered
		// popup content. `wcf_addons_get_widget_settings()` reads
		// `_elementor_data` straight off the document and applies no guard of
		// its own, so the guard belongs here, at the request boundary.
		// Same test as `Atomic::ajax_loop_grid_page()`.
		$post = get_post( $post_id );
		if ( ! $post
			|| ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post_id ) )
			|| post_password_required( $post ) ) {
			wp_send_json_error( 'Access denied', 403 );
		}

		$settings = wcf_addons_get_widget_settings( $post_id, $element_id );

		// The SAVED document is the authority, and that is what makes this
		// endpoint safe to serve to the public. But in the Elementor editor
		// nothing is saved yet: the builder picks a template in the panel and
		// this request still renders the one on disk, so the canvas keeps
		// showing the previous popup and the change reads as doing nothing.
		// Measured on the live editor -- panel said 3270, the response carried
		// 2745.
		//
		// So a live override is accepted, and only from somebody who may EDIT
		// this document. They can already choose any template from that same
		// panel, so it grants no reach they did not already have. A visitor
		// never sends it (the runtime adds it in edit mode only) and would be
		// refused here in any case.
		$inline_css    = '';
		$live_template = isset( $_REQUEST['template_id'] ) ? absint( $_REQUEST['template_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked above.

		if ( $live_template
			&& current_user_can( 'edit_post', $post_id )
			&& get_post( $live_template )
			&& current_user_can( 'read_post', $live_template ) ) {

			$settings['popup_content_type']        = 'template';
			$settings['popup_elementor_templates'] = $live_template;

			// A template the page never knew was coming has no stylesheet on
			// the page and no enqueue pass left to build one, so its CSS has
			// to travel with the markup. Editor only -- see the helper.
			$inline_css = self::atomic_inline_css( $live_template );
		}

		// Everything below renders content this plugin does not control -- an
		// Elementor template and its widgets, plus every shortcode on the page
		// -- so the close is in a finally and unwinds only to our own level.
		$ob_level = ob_get_level();
		$html     = '';
		ob_start();

		try {
			if ( isset( $settings['popup_content_type'] ) && 'template' === $settings['popup_content_type'] ) {
				if ( '' !== $inline_css ) {
					printf(
						'<style id="aae-popup-live-css-%d">%s</style>',
						(int) $live_template,
						wp_strip_all_tags( $inline_css )
					);
				}

				echo aaeaddon_kses_builder_html( \Elementor\Plugin::$instance->frontend->get_builder_content( $settings['popup_elementor_templates'], true ) );
			} else {

				$content = $settings['popup_content'] ?? 'Nothing to show.';

				$content = shortcode_unautop( $content );
				$content = do_shortcode( $content );
				$content = wptexturize( $content );

				if ( $GLOBALS['wp_embed'] instanceof \WP_Embed ) {
					$content = $GLOBALS['wp_embed']->autoembed( $content );
				}

				echo wp_kses_post( $content );
			}

		} finally {
			while ( ob_get_level() > $ob_level ) {
				$html = (string) ob_get_clean() . $html;
			}
		}

		wp_send_json_success(
			array(
				'html'        => $html,
				'widget_attr' => 'AAE Popup Content',
			)
		);
	}

	/**
	 * A document's atomic element styles, rendered as inline CSS.
	 *
	 * Used only for the editor's live-template override above. The normal path
	 * needs nothing like this: the saved document is known before the page
	 * renders, so `elementor/post/render` gets the template's CSS built and
	 * linked as an ordinary cached file, which is cheaper and cacheable. A
	 * template chosen in the panel a second ago has none of that -- no file was
	 * built because nothing knew it was coming, and admin-ajax has no enqueue
	 * pass left to run.
	 *
	 * `get_builder_content()` does not cover it either. It forces `$with_css`
	 * under `wp_doing_ajax()`, but what it prints is `Post_CSS`, the CLASSIC
	 * per-document generator; a template built from V4 atomic elements keeps
	 * its styles in the atomic pipeline, so that emits an empty `<style></style>`.
	 * Elementor's own `Styles_Renderer` builds the real thing straight from the
	 * document's saved element styles.
	 *
	 * The atomic BASE styles are deliberately not included: this only ever runs
	 * inside the editor canvas, which re-renders them itself on every load.
	 *
	 * @param int $post_id Document whose styles to render.
	 * @return string CSS, or '' when there is nothing to say.
	 */
	private static function atomic_inline_css( int $post_id ): string {
		if ( ! class_exists( Styles_Renderer::class ) || ! class_exists( Atomic_Utils::class ) ) {
			return '';
		}

		$styles = array();

		Atomic_Utils::traverse_post_elements(
			(string) $post_id,
			static function ( $element_data ) use ( &$styles ) {
				if ( ! empty( $element_data['styles'] ) && is_array( $element_data['styles'] ) ) {
					$styles = array_merge( $styles, $element_data['styles'] );
				}
			}
		);

		if ( empty( $styles ) ) {
			return '';
		}

		if ( class_exists( Atomic_Widget_Styles::class ) ) {
			$styles = Atomic_Widget_Styles::get_license_based_filtered_styles( $styles );
		}

		return Styles_Renderer::make( Plugin::$instance->breakpoints->get_breakpoints_config() )
			->render( array_values( $styles ) );
	}

	/**
	 * Live search handler Ajax call.
	 *
	 * @return void
	 */
	public static function handle_live_search() {
		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'wcf-addons-frontend' ) ) {
			wp_send_json_error( 'Missing or Invalid nonce' );
		}

		$keyword    = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		$from_date  = isset( $_POST['from_date'] ) ? sanitize_text_field( wp_unslash( $_POST['from_date'] ) ) : '';
		$to_date    = isset( $_POST['to_date'] ) ? sanitize_text_field( wp_unslash( $_POST['to_date'] ) ) : '';
		$categories = isset( $_POST['category'] ) ? array_map( 'intval', wp_unslash( $_POST['category'] ) ) : array();

		$args = array(
			'post_type'      => 'post',
			's'              => $keyword,
			'posts_per_page' => 10,
		);

		// Apply date filter if both dates provided.
		if ( ! empty( $from_date ) && ! empty( $to_date ) ) {
			$args['date_query'] = array(
				array(
					'after'     => $from_date,
					'before'    => $to_date,
					'inclusive' => true,
				),
			);
		}

		// Apply category filter only if category array is not empty.
		if ( ! empty( $categories ) && ! in_array( '0', $categories ) ) {
			$args['category__in'] = array_map( 'intval', $categories );
		}

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$title = get_the_title();
				$thumb = get_the_post_thumbnail_url( get_the_ID(), 'full' );

				$date = get_the_date();
				?>
				<div class="search-item">
					<?php if ( '' !== $thumb ) { ?>
					<div class="thumb AAE-no-image">
						<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					</div>
					<?php } ?>
					<div class="content">
						<a class="title" href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( $title ); ?></a>
						<div class="date"><?php echo esc_html( $date ); ?></div>
					</div>
				</div>
				<?php
			}
			wp_reset_postdata();
		} else {
			echo '<div class="search-no-result">No results found.</div>';
		}

		wp_die();
	}

	/**
	 * Mailchimp subscriber all list handler Ajax call
	 */
	public static function mailchimp_lists() {

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( esc_html__( 'Permission denied.', 'animation-addons-for-elementor' ), 403 );
		}

		if ( ! isset( $_REQUEST['nonce'] ) || empty( $_REQUEST['nonce'] ) ) {
			wp_send_json_error( 'Missing nonce' );
		}

		// Verify nonce

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'wcf-addons-editor' ) ) {
			exit( 'No naughty business please' );
		}
		$api = isset( $_REQUEST['api'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['api'] ) ) : '';
		update_option( 'aae_mailchimp_api', $api );
		$response = \WCF_ADDONS\Widgets\Mailchimp\Mailchimp_Api::get_mailchimp_lists( $api );

		wp_send_json( $response );
	}

	public static function wcf_mailchimp_list_fields() {

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( esc_html__( 'Permission denied.', 'animation-addons-for-elementor' ), 403 );
		}

		if ( ! isset( $_REQUEST['nonce'] ) || empty( $_REQUEST['nonce'] ) ) {
			wp_send_json_error( 'Missing nonce' );
		}
		// Verify nonce
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'wcf-addons-editor' ) ) {
			exit( 'No naughty business please' );
		}
		$api     = isset( $_REQUEST['api'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['api'] ) ) : '';
		$list_id = ! empty( $_REQUEST['list_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['list_id'] ) ) : '';

		$response = \WCF_ADDONS\Widgets\Mailchimp\Mailchimp_Api::get_form_fields( $api, $list_id );

		wp_send_json( $response );
	}

	/**
	 * Mailchimp subscriber handler Ajax call
	 */
	public static function mailchimp_prepare_ajax() {

		if ( ! isset( $_REQUEST['nonce'] ) || empty( $_REQUEST['nonce'] ) ) {
			wp_send_json_error( 'Missing nonce' );
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'wcf-addons-frontend' ) ) {
			exit( 'No naughty business please' );
		}

		$query           = isset( $_POST['subscriber_info'] ) ? wp_kses_post( wp_unslash( $_POST['subscriber_info'] ) ) : '';
		$subscriber_info = html_entity_decode( $query );
		parse_str( $subscriber_info, $subscriber );
		$response = \WCF_ADDONS\Widgets\Mailchimp\Mailchimp_Api::insert_subscriber_to_mailchimp( $subscriber );

		wp_send_json( $response );
	}
}

Ajax_Handler::init();
