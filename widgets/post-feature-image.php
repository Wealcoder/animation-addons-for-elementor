<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Plugin;
use Elementor\Widget_Base;
use WCF_ADDONS\WCF_Slider_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Post Featured Image Widget.
 *
 * Elementor widget that displays a post-featured image.
 *
 * @since 1.0.0
 * @package WCF_ADDONS\Widgets
 */
class Post_Feature_Image extends Widget_Base {

	use WCF_Slider_Trait;

	public function get_name() {
		return 'wcf--theme-post-image';
	}

	public function get_title() {
		return esc_html__( 'Post Featured Image', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-featured-image';
	}

	public function get_categories() {
		return array( 'wcf-single-addon' );
	}

	public function get_keywords() {
		return array( 'feature', 'post image', 'image' );
	}

	public function get_script_depends() {
		return array( 'swiper', 'wcf--slider' );
	}

	public function get_style_depends() {
		return array( 'swiper' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Thumbnail', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'thumb_type',
			array(
				'label'       => esc_html__( 'Thumb Type', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''            => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'post-format' => esc_html__( 'Post Format', 'animation-addons-for-elementor' ),
				),
				'separator'   => 'after',
				'description' => esc_html__( 'To use Post Format option, make sure your Posts supports post formats.', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'    => 'thumbnail',
				'exclude' => array( 'custom' ),
				'include' => array(),
				'default' => 'large',
			)
		);

		$this->add_responsive_control(
			'image_align',
			array(
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => array(
					'{{WRAPPER}} .wcf-f-image-wrapper' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'link_type',
			array(
				'label'     => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'none',
				'options'   => array(
					'none'       => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'post'       => esc_html__( 'Post URL', 'animation-addons-for-elementor' ),
					'media'      => esc_html__( 'Media File', 'animation-addons-for-elementor' ),
					'custom'     => esc_html__( 'Custom URL', 'animation-addons-for-elementor' ),
					'lightbox'   => esc_html__( 'Lightbox', 'animation-addons-for-elementor' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'custom_link',
			array(
				'label'       => esc_html__( 'Custom URL', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array(
					'active' => true,
				),
				'placeholder' =>  'https://your-link.com',
				'condition'   => array(
					'link_type' => 'custom',
				),
			)
		);

		$this->add_control(
			'link_target',
			array(
				'label'     => esc_html__( 'Open in New Tab', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array(
					'link_type!' => array( 'none', 'lightbox' ),
				),
			)
		);

		$this->add_control(
			'link_nofollow',
			array(
				'label'     => esc_html__( 'Add nofollow', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array(
					'link_type!' => array( 'none', 'lightbox' ),
				),
			)
		);

		$this->end_controls_section();

		// Slider Options.
		$this->start_controls_section(
			'sec_slider_options',
			array(
				'label'     => esc_html__( 'Slider Options', 'animation-addons-for-elementor' ),
				'condition' => array( 'thumb_type' => 'post-format' ),
			)
		);

		$this->register_slider_controls();

		$this->end_controls_section();

		// Style.
		$this->start_controls_section(
			'section_style_image',
			array(
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'width',
			array(
				'label'          => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => array(
					'unit' => '%',
				),
				'tablet_default' => array(
					'unit' => '%',
				),
				'mobile_default' => array(
					'unit' => '%',
				),
				'size_units'     => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'range'          => array(
					'%'  => array(
						'min' => 1,
						'max' => 100,
					),
					'px' => array(
						'min' => 1,
						'max' => 1000,
					),
					'vw' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'      => array(
					'{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'max-width',
			array(
				'label'          => esc_html__( 'Max Width', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => array(
					'unit' => '%',
				),
				'tablet_default' => array(
					'unit' => '%',
				),
				'mobile_default' => array(
					'unit' => '%',
				),
				'size_units'     => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'range'          => array(
					'%'  => array(
						'min' => 1,
						'max' => 100,
					),
					'px' => array(
						'min' => 1,
						'max' => 1000,
					),
					'vw' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'      => array(
					'{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'height',
			array(
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vh', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 1,
						'max' => 500,
					),
					'vh' => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'object-fit',
			array(
				'label'     => esc_html__( 'Object Fit', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'condition' => array(
					'height[size]!' => '',
				),
				'options'   => array(
					''        => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'fill'    => esc_html__( 'Fill', 'animation-addons-for-elementor' ),
					'cover'   => esc_html__( 'Cover', 'animation-addons-for-elementor' ),
					'contain' => esc_html__( 'Contain', 'animation-addons-for-elementor' ),
				),
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'object-position',
			array(
				'label'     => esc_html__( 'Object Position', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'center center' => esc_html__( 'Center Center', 'animation-addons-for-elementor' ),
					'center left'   => esc_html__( 'Center Left', 'animation-addons-for-elementor' ),
					'center right'  => esc_html__( 'Center Right', 'animation-addons-for-elementor' ),
					'top center'    => esc_html__( 'Top Center', 'animation-addons-for-elementor' ),
					'top left'      => esc_html__( 'Top Left', 'animation-addons-for-elementor' ),
					'top right'     => esc_html__( 'Top Right', 'animation-addons-for-elementor' ),
					'bottom center' => esc_html__( 'Bottom Center', 'animation-addons-for-elementor' ),
					'bottom left'   => esc_html__( 'Bottom Left', 'animation-addons-for-elementor' ),
					'bottom right'  => esc_html__( 'Bottom Right', 'animation-addons-for-elementor' ),
				),
				'default'   => 'center center',
				'selectors' => array(
					'{{WRAPPER}} img' => 'object-position: {{VALUE}};',
				),
				'condition' => array(
					'height[size]!' => '',
					'object-fit'    => 'cover',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'image_border',
				'selector'  => '{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'image_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'image_box_shadow',
				'exclude'  => array(
					'box_shadow_position',
				),
				'selector' => '{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio',
			)
		);

		$this->end_controls_section();

		// Navigation.
		$this->start_controls_section(
			'style_navigation',
			array(
				'label'     => esc_html__( 'Navigation', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'navigation' => 'yes',
					'thumb_type' => 'post-format',
				),
			)
		);

		$this->register_slider_navigation_style_controls();

		$this->end_controls_section();

		// Pagination.
		$this->start_controls_section(
			'style_pagination',
			array(
				'label'     => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'pagination' => 'yes',
					'thumb_type' => 'post-format',
				),
			)
		);

		$this->register_slider_pagination_style_controls();

		$this->end_controls_section();
	}

	protected function switch_post() {
		if ( 'wcf-addons-template' === get_post_type() ) {

			$recent_posts = wp_get_recent_posts(
				array(
					'numberposts' => 1,
					'post_status' => 'publish',
				)
			);

			$post_id = get_the_id();

			if ( isset( $recent_posts[0] ) ) {
				$post_id = $recent_posts[0]['ID'];
			}

			Plugin::$instance->db->switch_to_post( $post_id );
		}
	}

	/**
	 * Get link attributes based on settings
	 *
	 * @return array Array containing 'url' and 'attributes'
	 */
	protected function get_link_attributes() {
		$settings = $this->get_settings_for_display();
		$link_type = isset( $settings['link_type'] ) ? $settings['link_type'] : 'none';

		$link_data = array(
			'url'        => '',
			'attributes' => array(),
		);

		// Return early if no link
		if ( 'none' === $link_type ) {
			return $link_data;
		}

		// Get URL based on link type
		switch ( $link_type ) {
			case 'post':
				$link_data['url'] = get_permalink();
				break;

			case 'media':
				$attachment_id = get_post_thumbnail_id();
				if ( $attachment_id ) {
					$link_data['url'] = wp_get_attachment_url( $attachment_id );
				}
				break;

			case 'custom':
				if ( ! empty( $settings['custom_link']['url'] ) ) {
					$link_data['url'] = $settings['custom_link']['url'];
					// Handle custom link is_external and nofollow from URL control
					if ( ! empty( $settings['custom_link']['is_external'] ) ) {
						$link_data['attributes']['target'] = '_blank';
					}
					if ( ! empty( $settings['custom_link']['nofollow'] ) ) {
						$link_data['attributes']['rel'] = 'nofollow';
					}
				}
				break;

			case 'lightbox':
				$attachment_id = get_post_thumbnail_id();
				if ( $attachment_id ) {
					$link_data['url'] = wp_get_attachment_url( $attachment_id );
					$link_data['attributes']['data-elementor-open-lightbox'] = 'yes';
					$link_data['attributes']['data-elementor-lightbox-slideshow'] = $this->get_id();
				}
				break;
		}

		// Add target attribute (for non-custom links)
		if ( 'custom' !== $link_type && ! empty( $settings['link_target'] ) && 'yes' === $settings['link_target'] ) {
			$link_data['attributes']['target'] = '_blank';
		}

		// Add nofollow attribute (for non-custom links)
		if ( 'custom' !== $link_type && ! empty( $settings['link_nofollow'] ) && 'yes' === $settings['link_nofollow'] ) {
			$rel = isset( $link_data['attributes']['rel'] ) ? $link_data['attributes']['rel'] . ' nofollow' : 'nofollow';
			$link_data['attributes']['rel'] = $rel;
		}

		return $link_data;
	}

	/**
	 * Render opening link tag
	 *
	 * @param array $link_data Link data array with url and attributes
	 */
	protected function render_link_open( $link_data ) {
		if ( empty( $link_data['url'] ) ) {
			return;
		}

		echo '<a href="' . esc_url( $link_data['url'] ) . '"';
		foreach ( $link_data['attributes'] as $attr => $value ) {
			echo ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
		}
		echo '>';
	}

	/**
	 * Render closing link tag
	 *
	 * @param array $link_data Link data array with url and attributes
	 */
	protected function render_link_close( $link_data ) {
		if ( empty( $link_data['url'] ) ) {
			return;
		}

		echo '</a>';
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$this->switch_post();

		$post_id    = get_the_id();
		$image_size = isset( $settings['thumbnail_size'] ) ? $settings['thumbnail_size'] : 'full';
		$link_data  = $this->get_link_attributes();

		if ( 'post-format' === $settings['thumb_type'] ) {
			$post_format = get_post_format( $post_id );
			if ( 'video' === $post_format ) {
				$link = get_post_meta( get_the_ID(), '_video_url', true );

				// Youtube Link Checking.
				if ( ! empty( $link ) && strpos( $link, 'https://www.youtube.com/' ) === 0 ) {
					// parse_str( wp_parse_url( $link, PHP_URL_QUERY ), $query );
					parse_str( wp_parse_url( $link )['query'] ?? '', $query );

					if ( isset( $query['v'] ) ) {
						$ytVideoId = $query['v'];
						$link      = 'https://www.youtube.com/embed/' . $ytVideoId;
					}
				}

				// Vimeo Link Checking.
				if ( ! empty( $link ) && strpos( $link, 'https://vimeo.com/' ) === 0 ) {
					$videoId = str_replace( 'https://vimeo.com/', '', $link );
					$link    = 'https://player.vimeo.com/video/' . $videoId;
				}
				?>
				<div class="wcf-f-image-wrapper video">
					<iframe src="<?php echo esc_url( $link ); ?>"></iframe>
				</div>
				<?php
			} elseif ( 'audio' === $post_format ) {
				$link = get_post_meta( get_the_ID(), '_audio_url', true );
				?>
				<div class="wcf-f-image-wrapper audio">
					<?php echo wp_kses_post( wp_oembed_get( $link ) ); ?>
				</div>
				<?php
			} elseif ( 'gallery' === $post_format ) {
				$slider_settings = $this->get_slider_attributes();

				$this->add_render_attribute(
					'wrapper',
					array(
						'class'         => array( 'wcf__t_slider-wrapper aae-post-gallery-wrapper' ),
                        'data-settings' => json_encode($slider_settings), //phpcs:ignore
					)
				);

				$gallery_images = get_post_meta( get_the_ID(), '_gallery_images', true );
				$gallery_images = is_array( $gallery_images ) ? $gallery_images : array();
				?>
				<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
					<div <?php $this->print_render_attribute_string( 'carousel-wrapper' ); ?>>
						<div class="swiper-wrapper">
							<?php foreach ( $gallery_images as $img ) { ?>
								<div class="swiper-slide">
									<?php $this->render_link_open( $link_data ); ?>
									<img src="<?php echo esc_url( $img ); ?>"
										alt="<?php echo esc_attr__( 'Gallery Image', 'animation-addons-for-elementor' ); ?>">
									<?php $this->render_link_close( $link_data ); ?>
								</div>
							<?php } ?>
						</div>

						<?php
						// if ( 1 < count( $settings['testimonials'] ) ) :
						?>
						<?php $this->render_slider_navigation(); ?>
						<?php $this->render_slider_pagination(); ?>
						<?php
						// endif;
						?>
					</div>
				</div>
				<?php
			} else {
				?>
				<div class="wcf-f-image-wrapper">
					<?php
					$this->render_link_open( $link_data );
					echo get_the_post_thumbnail( $post_id, $image_size, array() );
					$this->render_link_close( $link_data );
					?>
				</div>
				<?php
			}
		} else {
			?>
			<div class="wcf-f-image-wrapper">
				<?php
				$this->render_link_open( $link_data );
				echo get_the_post_thumbnail( $post_id, $image_size, array() );
				$this->render_link_close( $link_data );
				?>
			</div>
			<?php
		}

		Plugin::$instance->db->restore_current_post();
	}
}
