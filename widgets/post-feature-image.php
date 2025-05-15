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
	exit; // Exit if accessed directly
}

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
		return [ 'wcf-single-addon' ];
	}

	public function get_keywords() {
		return [ 'feature', 'post image', 'image' ];
	}

	public function get_script_depends() {
		return [ 'swiper', 'wcf--slider' ];
	}

	public function get_style_depends() {
		return [ 'swiper' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Thumbnail', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'thumb_type',
			[
				'label'     => esc_html__( 'Thumb Type', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''            => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'post-format' => esc_html__( 'Post Format', 'animation-addons-for-elementor' ),
				],
				'separator' => 'after',
                'description' => esc_html__( 'To use Post Format option, make sure your Posts supports post formats.', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'thumbnail',
				'exclude' => [ 'custom' ],
				'include' => [],
				'default' => 'large',
			]
		);

		$this->add_responsive_control(
			'image_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf-f-image-wrapper' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// Slider Options
		$this->start_controls_section(
			'sec_slider_options',
			[
				'label'     => esc_html__( 'Slider Options', 'animation-addons-for-elementor' ),
				'condition' => [ 'thumb_type' => 'post-format' ],
			]
		);

		$this->register_slider_controls();

		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'width',
			[
				'label'          => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'size_units'     => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range'          => [
					'%'  => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'vw' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'      => [
					'{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'max-width',
			[
				'label'          => esc_html__( 'Max Width', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'size_units'     => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range'          => [
					'%'  => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'vw' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'      => [
					'{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vh', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'object-fit',
			[
				'label'     => esc_html__( 'Object Fit', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'condition' => [
					'height[size]!' => '',
				],
				'options'   => [
					''        => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'fill'    => esc_html__( 'Fill', 'animation-addons-for-elementor' ),
					'cover'   => esc_html__( 'Cover', 'animation-addons-for-elementor' ),
					'contain' => esc_html__( 'Contain', 'animation-addons-for-elementor' ),
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} img' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'object-position',
			[
				'label'     => esc_html__( 'Object Position', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'center center' => esc_html__( 'Center Center', 'animation-addons-for-elementor' ),
					'center left'   => esc_html__( 'Center Left', 'animation-addons-for-elementor' ),
					'center right'  => esc_html__( 'Center Right', 'animation-addons-for-elementor' ),
					'top center'    => esc_html__( 'Top Center', 'animation-addons-for-elementor' ),
					'top left'      => esc_html__( 'Top Left', 'animation-addons-for-elementor' ),
					'top right'     => esc_html__( 'Top Right', 'animation-addons-for-elementor' ),
					'bottom center' => esc_html__( 'Bottom Center', 'animation-addons-for-elementor' ),
					'bottom left'   => esc_html__( 'Bottom Left', 'animation-addons-for-elementor' ),
					'bottom right'  => esc_html__( 'Bottom Right', 'animation-addons-for-elementor' ),
				],
				'default'   => 'center center',
				'selectors' => [
					'{{WRAPPER}} img' => 'object-position: {{VALUE}};',
				],
				'condition' => [
					'height[size]!' => '',
					'object-fit'    => 'cover',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'image_border',
				'selector'  => '{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'image_box_shadow',
				'exclude'  => [
					'box_shadow_position',
				],
				'selector' => '{{WRAPPER}} img, {{WRAPPER}} iframe, {{WRAPPER}} audio',
			]
		);

		$this->end_controls_section();

		// Navigation
		$this->start_controls_section(
			'style_navigation',
			[
				'label'     => esc_html__( 'Navigation', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'navigation' => 'yes',
					'thumb_type' => 'post-format',
				],
			]
		);

		$this->register_slider_navigation_style_controls();

		$this->end_controls_section();

		// Pagination
		$this->start_controls_section(
			'style_pagination',
			[
				'label'     => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'pagination' => 'yes',
					'thumb_type' => 'post-format',
				],
			]
		);

		$this->register_slider_pagination_style_controls();

		$this->end_controls_section();

	}

	protected function switch_post() {
		if ( 'wcf-addons-template' === get_post_type() ) {

			$recent_posts = wp_get_recent_posts( array(
				'numberposts' => 1,
				'post_status' => 'publish'
			) );

			$post_id = get_the_id();

			if ( isset( $recent_posts[0] ) ) {
				$post_id = $recent_posts[0]['ID'];
			}

			Plugin::$instance->db->switch_to_post( $post_id );
		}
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$this->switch_post();

		$post_id    = get_the_id();
		$image_size = isset( $settings['thumbnail_size'] ) ? $settings['thumbnail_size'] : 'full';

		if ( 'post-format' === $settings['thumb_type'] ) {
			$post_format = get_post_format( $post_id );
			if ( 'video' === $post_format ) {
				$link = get_post_meta( get_the_ID(), '_video_url', true );

				// Youtube Link Checking
				if ( strpos( $link, "https://www.youtube.com/" ) === 0 ) {
//					parse_str( wp_parse_url( $link, PHP_URL_QUERY ), $query );
					parse_str( wp_parse_url( $link )['query'] ?? '', $query );

					if ( isset( $query['v'] ) ) {
						$ytVideoId = $query['v'];
						$link      = "https://www.youtube.com/embed/" . $ytVideoId;
					}
				}

				// Vimeo Link Checking
				if ( strpos( $link, "https://vimeo.com/" ) === 0 ) {
					$videoId = str_replace( "https://vimeo.com/", "", $link );
					$link    = "https://player.vimeo.com/video/" . $videoId;
				}
				?>
                <div class="wcf-f-image-wrapper video">
                    <iframe src="<?php echo esc_url($link); ?>"></iframe>
                </div>
				<?php
			} elseif ( 'audio' === $post_format ) {
				$link = get_post_meta( get_the_ID(), '_audio_url', true );
				?>
                <div class="wcf-f-image-wrapper audio">
                    <audio controls>
                        <source src="<?php echo esc_url( $link ); ?>" type="audio/mpeg">
                    </audio>
                </div>
				<?php
			} elseif ( 'gallery' === $post_format ) {
				$slider_settings = $this->get_slider_attributes();

				$this->add_render_attribute(
					'wrapper',
					[
						'class'         => [ 'wcf__t_slider-wrapper aae-post-gallery-wrapper' ],
						'data-settings' => json_encode( $slider_settings ), //phpcs:ignore
					]
				);

				$gallery_images = get_post_meta( get_the_ID(), '_gallery_images', true );
				$gallery_images = is_array( $gallery_images ) ? $gallery_images : [];
				?>
                <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
                    <div <?php $this->print_render_attribute_string( 'carousel-wrapper' ); ?>>
                        <div class="swiper-wrapper">
							<?php foreach ( $gallery_images as $img ) { ?>
                                <div class="swiper-slide">
                                    <img src="<?php echo esc_url( $img ); ?>"
                                         alt="<?php echo esc_attr__( 'Gallery Image', 'animation-addons-for-elementor' ) ?>">
                                </div>
							<?php } ?>
                        </div>

						<?php // if ( 1 < count( $settings['testimonials'] ) ) : ?>
						<?php $this->render_slider_navigation(); ?>
						<?php $this->render_slider_pagination(); ?>
						<?php // endif; ?>
                    </div>
                </div>
				<?php
			} else {
				?>
                <div class="wcf-f-image-wrapper">
					<?php echo get_the_post_thumbnail( $post_id, $image_size, array() ); ?>
                </div>
				<?php
			}
		} else {
			?>
            <div class="wcf-f-image-wrapper">
				<?php echo get_the_post_thumbnail( $post_id, $image_size, array() ); ?>
            </div>
			<?php
		}

		Plugin::$instance->db->restore_current_post();
	}
}
