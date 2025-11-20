<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Control_Media;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WCF_ADDONS\WCF_Slider_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * BrandSlider
 *
 * Elementor widget for brand slider.
 *
 * @since 1.0.0
 */
class Brand_Slider extends Widget_Base {
	use WCF_Slider_Trait;

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--brand-slider';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_title() {
		return esc_html__( 'Brand Slider', 'animation-addons-for-elementor' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_icon() {
		return 'wcf eicon-slides';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @return array Widget categories.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_categories() {
		return array( 'weal-coder-addon' );
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @return array Widget scripts dependencies.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_script_depends() {
		return array( 'swiper', 'wcf--slider' );
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @return array Widget scripts dependencies.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_style_depends() {
		return array( 'swiper', 'wcf--brand-slider' );
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Brand Slider', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'slide_content',
			array(
				'label'   => esc_html__( 'Slide Content', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'text',
				'options' => array(
					'text'  => esc_html__( 'Text', 'animation-addons-for-elementor' ),
					'image' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				),
			)
		);

		$this->add_control(
			'wcf_brand_carousel',
			array(
				'label'      => esc_html__( 'Add Images', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::GALLERY,
				'default'    => array(),
				'show_label' => false,
				'dynamic'    => array(
					'active' => true,
				),
				'condition'  => array(
					'slide_content' => 'image',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'thumbnail',
				'separator' => 'none',
				'condition' => array(
					'slide_content' => 'image',
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'list_text',
			array(
				'label'       => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Designer', 'animation-addons-for-elementor' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'repeat_list_text',
			array(
				'label'       => esc_html__( 'Text List', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'list_text' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
					),
					array(
						'list_text' => esc_html__( '(Health Advisor & Coach)', 'animation-addons-for-elementor' ),
					),
					array(
						'list_text' => esc_html__( 'News', 'animation-addons-for-elementor' ),
					),
					array(
						'list_text' => esc_html__( 'Creative Director', 'animation-addons-for-elementor' ),
					),
				),
				'title_field' => '{{{ list_text }}}',//phpcs:ignore
				'condition'   => array(
					'slide_content' => 'text',
				),
			)
		);

		$this->add_control(
			'separator_icon',
			array(
				'label'     => esc_html__( 'Text Separator', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'far fa-star',
					'library' => 'fa-brands',
				),
				'condition' => array(
					'slide_content' => 'text',
				),
			)
		);

		$this->end_controls_section();

		// slide controls.
		$this->start_controls_section(
			'section_slider_options',
			array(
				'label' => esc_html__( 'Slider Options', 'animation-addons-for-elementor' ),
			)
		);

		$default = array(
			'autoplay_delay' => 1,
			'speed'          => 5000,
		);

		$this->register_slider_controls( $default );

		$this->end_controls_section();

		// image style control.
		$this->slider_image_style_controls();

		// text style control.
		$this->slider_text_style_controls();

		// slider navigation style controls.
		$this->start_controls_section(
			'section_slider_navigation_style',
			array(
				'label'     => esc_html__( 'Slider Navigation', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'navigation' => 'yes' ),
			)
		);

		$this->register_slider_navigation_style_controls();

		$this->end_controls_section();

		// slider pagination style controls.
		$this->start_controls_section(
			'section_slider_pagination_style',
			array(
				'label'     => esc_html__( 'Slider Pagination', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'pagination' => 'yes' ),
			)
		);

		$this->register_slider_pagination_style_controls();

		$this->end_controls_section();
	}

	/**
	 * Register the slider image style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function slider_image_style_controls() {
		$this->start_controls_section(
			'section_style_image',
			array(
				'label'     => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'slide_content' => 'image',
				),
			)
		);

		$this->add_responsive_control(
			'image_width',
			array(
				'label'      => esc_html__( 'Image Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vh', 'vw' ),
				'range'      => array(
					'px'  => array(
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					),
					'%'   => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'em'  => array(
						'min'  => 0,
						'max'  => 20,
						'step' => .1,
					),
					'rem' => array(
						'min'  => 0,
						'max'  => 20,
						'step' => .1,
					),
					'vh'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'vw'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--brand-slider-wrapper .swiper-wrapper .swiper-slide img' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_height',
			array(
				'label'      => esc_html__( 'Image Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vh', 'vw' ),
				'range'      => array(
					'px'  => array(
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					),
					'%'   => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'em'  => array(
						'min'  => 0,
						'max'  => 20,
						'step' => .1,
					),
					'rem' => array(
						'min'  => 0,
						'max'  => 20,
						'step' => .1,
					),
					'vh'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'vw'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--brand-slider-wrapper .swiper-wrapper .swiper-slide img' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_object_fit',
			array(
				'label'     => esc_html__( 'Image Object Fit', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					''           => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'fill'       => esc_html__( 'Fill', 'animation-addons-for-elementor' ),
					'contain'    => esc_html__( 'Contain', 'animation-addons-for-elementor' ),
					'cover'      => esc_html__( 'Cover', 'animation-addons-for-elementor' ),
					'none'       => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'scale-down' => esc_html__( 'Scale Down', 'animation-addons-for-elementor' ),
					'inherit'    => esc_html__( 'Inherit', 'animation-addons-for-elementor' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf--brand-slider-wrapper .swiper-slide img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'item_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--brand-slider-wrapper .swiper-slide' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'image_border',
				'selector' => '{{WRAPPER}} .wcf--brand-slider-wrapper .swiper-slide',
			)
		);

		$this->add_responsive_control(
			'image_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--brand-slider-wrapper .swiper-slide' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--brand-slider-wrapper .swiper-slide' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the slider text style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function slider_text_style_controls() {
		$this->start_controls_section(
			'section_style_text',
			array(
				'label'     => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'slide_content' => 'text',
				),
			)
		);
		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .title' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .title',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			array(
				'name'     => 'text_stroke',
				'selector' => '{{WRAPPER}} .title',
			)
		);

		$this->add_control(
			'separator_color',
			array(
				'label'     => esc_html__( 'Separator Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .elementor-icon i'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .elementor-icon svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'separator_size',
			array(
				'label'      => esc_html__( 'Separator Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				// The `%' and `em` units are not supported as the widget implements icons differently then other icons.
				'size_units' => array( 'px', 'rem', 'vw', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 6,
						'max' => 300,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .elementor-icon' => 'font-size: {{SIZE}}{{UNIT}}',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['wcf_brand_carousel'] ) && empty( $settings['repeat_list_text'] ) ) {
			return;
		}

		$class_slide_width = '';
		if ( 'auto' === $settings['slides_to_show'] ) {
			$class_slide_width = 'slide-width-auto';
		}

		$slider_settings = $this->get_slider_attributes();

		$this->add_render_attribute(
			'wrapper',
			array(
				'class'         => array( 'wcf__slider-wrapper wcf--brand-slider-wrapper', $class_slide_width ),
				'data-settings' => json_encode( $slider_settings ), //phpcs:ignore
			)
		);

		$slides       = array();
		$slides_count = 0;
		if ( 'image' === $settings['slide_content'] ) {
			$slides_count = count( $settings['wcf_brand_carousel'] );
			foreach ( $settings['wcf_brand_carousel'] as $index => $attachment ) {
				$image_url = Group_Control_Image_Size::get_attachment_image_src( $attachment['id'], 'thumbnail', $settings );

				if ( ! $image_url && isset( $attachment['url'] ) ) {
					$image_url = $attachment['url'];
				}

				$image_html = '<img class="swiper-slide-image" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( Control_Media::get_image_alt( $attachment ) ) . '" />';

				$slide_html = '<div  class="swiper-slide">' . $image_html . '</div>';

				$slides[] = $slide_html;
			}
		} else {
			$slides_count = count( $settings['repeat_list_text'] );
			foreach ( $settings['repeat_list_text'] as $index => $item ) {
				$title     = '<div class="title">' . $item['list_text'] . '</div>';
				$separator = '<div class="elementor-icon">' . Icons_Manager::try_get_icon_html( $settings['separator_icon'], array( 'aria-hidden' => 'true' ) ) . '</div>';

				$slide_html = '<div  class="swiper-slide"><div class="text-slide-content">' . $title . $separator . '</div></div>';

				$slides[] = $slide_html;
			}
		}

		if ( empty( $slides ) ) {
			return;
		}

		$svg_args = array(
			'svg'   => array(
				'class'           => true,
				'aria-hidden'     => true,
				'aria-labelledby' => true,
				'role'            => true,
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true, // <= Must be lower case!
			),
			'g'     => array( 'fill' => true ),
			'title' => array( 'title' => true ),
			'path'  => array(
				'd'    => true,
				'fill' => true,
			),
		);

		$allowed_tags = array_merge( wp_kses_allowed_html( 'post' ), $svg_args );
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<!-- Slider main container -->
			<div <?php $this->print_render_attribute_string( 'carousel-wrapper' ); ?>>
				<!-- Additional required wrapper -->
				<div class="swiper-wrapper">
					<!-- Slides -->
					<?php echo wp_kses( implode( '', $slides ), $allowed_tags ); ?>
				</div>
				<!-- navigation and pagination -->
				<?php if ( 1 < $slides_count ) : ?>
					<?php $this->render_slider_navigation(); ?>

					<?php $this->render_slider_pagination(); ?>
				<?php endif; ?>

			</div>
		</div>
		<?php
	}
}
