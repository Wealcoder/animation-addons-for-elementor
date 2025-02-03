<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Elementor\Plugin;
use WCF_ADDONS\WCF_Theme_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Post_Content extends Widget_Base {

	public function get_name() {
		return 'wcf--theme-post-content';
	}

	public function get_title() {
		return esc_html__( 'Post Content', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-post-content';
	}

	public function get_categories() {
		return [ 'wcf-single-addon' ];
	}

	public function get_keywords() {
		return [ 'content', 'post' ];
	}

	public function show_in_panel() {
		$tmpType = get_post_meta( get_the_ID(), 'wcf-addons-template-meta_type', true );

		if ( 'single' === $tmpType ) {
			return true;
		}
		// By default don't show.
		return false;
	}

	/**
	 * Render post content.
	 *
	 * @param boolean $with_wrapper - Whether to wrap the content with a div.
	 * @param boolean $with_css - Decides whether to print inline CSS before the post content.
	 *
	 * @return void
	 */
	public function render_post_content( $with_wrapper = false, $with_css = true ) {
		static $did_posts = [];
		static $level = 0;		
		$post = get_post();		
		
		if ( 'wcf-addons-template' === get_post_type() ) {
			$recent_posts = wp_get_recent_posts( array(
				'numberposts' => 1,
				'post_status' => 'publish'
			) );

			$post_id = get_the_id();

			if ( isset( $recent_posts[0] ) ) {
				$post_id = $recent_posts[0]['ID'];
			}

			$post = get_post( $post_id );
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
			$document = WCF_Theme_Builder::get_document( $post->ID );
			// On view theme document show it's preview content.
			if ( $document ) {
				$preview_type = $document->get_settings( 'preview_type' );
				$preview_id   = $document->get_settings( 'preview_id' );

				if ( 0 === strpos( $preview_type, 'single' ) && ! empty( $preview_id ) ) {
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
				echo apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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

	protected function register_controls() {
		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}}' => 'color: {{VALUE}};',
				],
				'global'    => [
					'default' => Global_Colors::COLOR_TEXT,
				],
				'condition' => [
					'enable_inline_style' => [ 'yes' ]
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'typography',
				'global'    => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
				'condition' => [
					'enable_inline_style' => [ 'yes' ]
				]
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		// Post CSS should not be printed here because it overrides the already existing post CSS.
		$this->render_post_content( false, false );
	}

	public function render_plain_content() {
	}
}
