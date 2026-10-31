<?php
/**
 * Lightbox Dynamic Tag
 *
 * Creates lightbox links for images and videos.
 * Works with Elementor core (no Elementor Pro dependency).
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Site;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Tag_Base;
use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Elementor\Embed;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Lightbox Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Site
 */
class Lightbox_Tag extends Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-lightbox';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Lightbox', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array
	 */
	public function get_group() {
		return array( 'aae-site' );
	}

	/**
	 * Supported categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( TagsModule::URL_CATEGORY );
	}

	/**
	 * Keep empty to avoid a default advanced section.
	 *
	 * @return void
	 */
	protected function register_advanced_section() {}

	/**
	 * Register tag controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'type',
			array(
				'label'   => esc_html__( 'Type', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => array(
					'video' => array(
						'title' => esc_html__( 'Video', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-video-camera',
					),
					'image' => array(
						'title' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-image-bold',
					),
				),
			)
		);

		$this->add_control(
			'image',
			array(
				'label'     => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array(
					'type' => 'image',
				),
			)
		);

		$this->add_control(
			'video_url',
			array(
				'label'       => esc_html__( 'Video URL', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'condition'   => array(
					'type' => 'video',
				),
				'ai'          => array(
					'active' => false,
				),
			)
		);
	}

	/**
	 * Get image settings for lightbox.
	 *
	 * @param array $settings Tag settings.
	 * @return array
	 */
	private function get_image_settings( $settings ) {
		$image_settings = array(
			'type' => 'image',
			'url'  => isset( $settings['image']['url'] ) ? $settings['image']['url'] : '',
		);

		$image_id = isset( $settings['image']['id'] ) ? $settings['image']['id'] : 0;

		if ( $image_id ) {
			// Get full image URL.
			$image_data = wp_get_attachment_image_src( $image_id, 'full' );
			if ( $image_data ) {
				$image_settings['url'] = $image_data[0];

				// Add image metadata.
				$attachment = get_post( $image_id );
				if ( $attachment ) {
					$image_settings['title']       = $attachment->post_title;
					$image_settings['description'] = $attachment->post_content;
					$image_settings['caption']     = $attachment->post_excerpt;
				}
			}
		}

		return $image_settings;
	}

	/**
	 * Get video settings for lightbox.
	 *
	 * @param array $settings Tag settings.
	 * @return array|string
	 */
	private function get_video_settings( $settings ) {
		$video_url = isset( $settings['video_url'] ) ? $settings['video_url'] : '';

		if ( empty( $video_url ) ) {
			return '';
		}

		$video_properties = Embed::get_video_properties( $video_url );

		if ( ! $video_properties ) {
			$video_type = 'hosted';
			$embed_url  = $video_url;
		} else {
			$video_type = $video_properties['provider'];
			$embed_url  = Embed::get_embed_url( $video_url );
		}

		if ( null === $embed_url ) {
			return '';
		}

		$video_settings = array(
			'type'      => 'video',
			'videoType' => $video_type,
			'url'       => $embed_url,
		);

		// A self-hosted file is rendered by core as a bare `<video src autoplay>`:
		// setVideoContent() in elementor/assets/js/chunks/lightbox-lightbox.js adds
		// nothing else unless `videoParams` says so. Without this the visitor gets a
		// clip with no pause, no seek bar and no volume. Elementor's own Video widget
		// passes the same keys from Video::get_hosted_params(); `controls` and
		// `playsinline` are the two this tag can know about without inventing new
		// controls -- `playsinline` keeps iOS from hijacking the lightbox into the
		// native fullscreen player.
		//
		// Providers are deliberately excluded: YouTube and Vimeo draw their own
		// controls inside the iframe, and that branch of setVideoContent() ignores
		// `videoParams` entirely.
		if ( 'hosted' === $video_type ) {
			$video_settings['videoParams'] = array(
				'controls'    => '',
				'playsinline' => '',
			);
		}

		return $video_settings;
	}

	/**
	 * Render lightbox URL.
	 *
	 * @return void
	 */
	public function render() {
		$settings = $this->get_settings();
		$value    = array();

		if ( ! isset( $settings['type'] ) || ! $settings['type'] ) {
			return;
		}

		if ( 'image' === $settings['type'] && isset( $settings['image'] ) && ! empty( $settings['image'] ) ) {
			$value = $this->get_image_settings( $settings );
		} elseif ( 'video' === $settings['type'] && isset( $settings['video_url'] ) && ! empty( $settings['video_url'] ) ) {
			$value = $this->get_video_settings( $settings );
		}

		if ( ! $value || empty( $value['url'] ) ) {
			return;
		}

		// The lightbox, and the handler that turns this '#elementor-action:...' hash
		// into an open modal, both live in the legacy `elementor-frontend` bundle
		// (`elementorFrontend.utils.urlActions`). A page built only from V4 atomic
		// elements never enqueues that bundle, so the link was inert -- clicking it
		// only put the hash in the address bar. Core's newer `[data-action-link]`
		// handler bails out on the same missing global, so correct markup alone
		// would not help either.
		//
		// Enqueued from render() rather than globally, and only past the guard
		// above: the weight (~300KB of JS, swiper included) then lands solely on
		// pages where this tag resolved to a real image or video. On a page that
		// already loads Elementor's frontend this is a no-op.
		if ( wp_script_is( 'elementor-frontend', 'registered' ) ) {
			wp_enqueue_script( 'elementor-frontend' );
		}

		// Use Elementor core lightbox.
		if ( class_exists( '\Elementor\Plugin' ) && method_exists( \Elementor\Plugin::instance()->frontend, 'create_action_hash' ) ) {
			// create_action_hash() returns '#' + rawurlencode()d text, so it passes esc_url_raw() untouched.
			echo esc_url_raw( \Elementor\Plugin::instance()->frontend->create_action_hash( 'lightbox', $value ) );
		} else {
			// Fallback: output the URL directly.
			echo esc_url( $value['url'] );
		}
	}
}
