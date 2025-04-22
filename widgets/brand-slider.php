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
		return [ 'weal-coder-addon' ];
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
		return [ 'swiper', 'wcf--slider' ];
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
		return [ 'swiper', 'wcf--brand-slider' ];
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
			[
				'label' => esc_html__( 'Brand Slider', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'slide_content',
			[
				'label'   => esc_html__( 'Slide Content', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'text',
				'options' => [
					'text'  => esc_html__( 'Text', 'animation-addons-for-elementor' ),
					'image' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'wcf_brand_carousel',
			[
				'label'      => esc_html__( 'Add Images', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::GALLERY,
				'default'    => [],
				'show_label' => false,
				'dynamic'    => [
					'active' => true,
				],
				'condition'  => [
					'slide_content' => 'image',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'thumbnail',
				'separator' => 'none',
				'condition' => [
					'slide_content' => 'image',
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'list_text',
			[
				'label'       => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Designer', 'animation-addons-for-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'repeat_list_text',
			[
				'label'       => esc_html__( 'Text List', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'list_text' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
					],
					[
						'list_text' => esc_html__( '(Health Advisor & Coach)', 'animation-addons-for-elementor' ),
					],
					[
						'list_text' => esc_html__( 'News', 'animation-addons-for-elementor' ),
					],
					[
						'list_text' => esc_html__( 'Creative Director', 'animation-addons-for-elementor' ),
					],
				],
				'title_field' => '{{{ list_text }}}',//phpcs:ignore
				'condition'   => [
					'slide_content' => 'text',
				],
			]
		);

		$this->add_control(
			'separator_icon',
			[
				'label'     => esc_html__( 'Text Separator', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'far fa-star',
					'library' => 'fa-brands',
				],
				'condition' => [
					'slide_content' => 'text',
				],
			]
		);

		$this->end_controls_section();

		//slide controls
		$this->start_controls_section(
			'section_slider_options',
			[
				'label' => esc_html__( 'Slider Options', 'animation-addons-for-elementor' ),
			]
		);

		$default = [
			'autoplay_delay' => 1,
			'speed'          => 5000,
		];

		$this->register_slider_controls( $default );

		$this->end_controls_section();

		//image style control
		$this->slider_image_style_controls();

		//text style control
		$this->slider_text_style_controls();

		//slider navigation style controls
		$this->start_controls_section(
			'section_slider_navigation_style',
			[
				'label'     => esc_html__( 'Slider Navigation', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'navigation' => 'yes' ],
			]
		);

		$this->register_slider_navigation_style_controls();

		$this->end_controls_section();

		//slider pagination style controls
		$this->start_controls_section(
			'section_slider_pagination_style',
			[
				'label'     => esc_html__( 'Slider Pagination', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'pagination' => 'yes' ],
			]
		);

		$this->register_slider_pagination_style_controls();

		$this->end_controls_section();
	}

	/**
	 * Register the slider controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function slider_controls() {
		$this->start_controls_section(
			'section_slider_options',
			[
				'label' => esc_html__( 'Slider Options', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'   => esc_html__( 'Autoplay', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'yes',
				'options' => [
					'yes' => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'no'  => esc_html__( 'No', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'autoplay_delay',
			[
				'label'     => esc_html__( 'Autoplay delay', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1,
				'condition' => [
					'autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'autoplay_interaction',
			[
				'label'     => esc_html__( 'Autoplay Interaction', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'true',
				'options'   => [
					'true'  => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'false' => esc_html__( 'No', 'animation-addons-for-elementor' ),
				],
				'condition' => [
					'autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'allow_touch_move',
			[
				'label'     => esc_html__( 'Allow Touch Move', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'separator' => 'before',
				'default'   => 'false',
				'options'   => [
					'true'  => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'false' => esc_html__( 'No', 'animation-addons-for-elementor' ),
				],
			]
		);

		// Loop requires a re-render so no 'render_type = none'
		$this->add_control(
			'loop',
			[
				'label'   => esc_html__( 'Loop', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'true',
				'options' => [
					'true'  => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'false' => esc_html__( 'No', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'speed',
			[
				'label'   => esc_html__( 'Animation Speed', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5000,
			]
		);

		$this->add_control(
			'space_between',
			[
				'label'       => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 20,
				'render_type' => 'template',
				'selectors'   => [
					'{{WRAPPER}} .text-slide-content' => 'gap: {{VALUE}}px;',
				],
			]
		);

		//slider navigation
		$this->add_control(
			'navigation',
			[
				'label'     => esc_html__( 'Navigation', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'separator' => 'before',
				'default'   => 'both',
				'options'   => [
					'both'   => esc_html__( 'Arrows and Dots', 'animation-addons-for-elementor' ),
					'arrows' => esc_html__( 'Arrows', 'animation-addons-for-elementor' ),
					'dots'   => esc_html__( 'Dots', 'animation-addons-for-elementor' ),
					'none'   => esc_html__( 'None', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'navigation_previous_icon',
			[
				'label'            => esc_html__( 'Previous Arrow Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'skin_settings'    => [
					'inline' => [
						'none' => [
							'label' => 'Default',
							'icon'  => 'eicon-chevron-left',
						],
						'icon' => [
							'icon' => 'eicon-star',
						],
					],
				],
				'recommended'      => [
					'fa-regular' => [
						'arrow-alt-circle-left',
						'caret-square-left',
					],
					'fa-solid'   => [
						'angle-double-left',
						'angle-left',
						'arrow-alt-circle-left',
						'arrow-circle-left',
						'arrow-left',
						'caret-left',
						'caret-square-left',
						'chevron-circle-left',
						'chevron-left',
						'long-arrow-alt-left',
					],
				],
				'conditions'       => [
					'relation' => 'or',
					'terms'    => [
						[
							'name'     => 'navigation',
							'operator' => '=',
							'value'    => 'both',
						],
						[
							'name'     => 'navigation',
							'operator' => '=',
							'value'    => 'arrows',
						],
					],
				],
			]
		);

		$this->add_control(
			'navigation_next_icon',
			[
				'label'            => esc_html__( 'Next Arrow Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'skin_settings'    => [
					'inline' => [
						'none' => [
							'label' => 'Default',
							'icon'  => 'eicon-chevron-right',
						],
						'icon' => [
							'icon' => 'eicon-star',
						],
					],
				],
				'recommended'      => [
					'fa-regular' => [
						'arrow-alt-circle-right',
						'caret-square-right',
					],
					'fa-solid'   => [
						'angle-double-right',
						'angle-right',
						'arrow-alt-circle-right',
						'arrow-circle-right',
						'arrow-right',
						'caret-right',
						'caret-square-right',
						'chevron-circle-right',
						'chevron-right',
						'long-arrow-alt-right',
					],
				],
				'conditions'       => [
					'relation' => 'or',
					'terms'    => [
						[
							'name'     => 'navigation',
							'operator' => '=',
							'value'    => 'both',
						],
						[
							'name'     => 'navigation',
							'operator' => '=',
							'value'    => 'arrows',
						],
					],
				],
			]
		);

		$this->add_control(
			'direction',
			[
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'separator' => 'before',
				'default'   => 'ltr',
				'options'   => [
					'ltr' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
					'rtl' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
				],
			]
		);

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
			[
				'label'     => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'slide_content' => 'image',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'image_border',
				'selector' => '{{WRAPPER}} .wcf--brand-slider img',
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--brand-slider img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
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
			[
				'label'     => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'slide_content' => 'text',
				],
			]
		);
		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'text_stroke',
				'selector' => '{{WRAPPER}} .title',
			]
		);

		$this->add_control(
			'separator_color',
			[
				'label'     => esc_html__( 'Separator Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .elementor-icon i'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .elementor-icon svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'separator_size',
			[
				'label'      => esc_html__( 'Separator Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				// The `%' and `em` units are not supported as the widget implements icons differently then other icons.
				'size_units' => [ 'px', 'rem', 'vw', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 6,
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .elementor-icon' => 'font-size: {{SIZE}}{{UNIT}}',
				],
			]
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
			[
				'class'         => [ 'wcf__slider-wrapper wcf--brand-slider-wrapper', $class_slide_width ],
				'data-settings' => json_encode( $slider_settings ), //phpcs:ignore
			]
		);

		$slides       = [];
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
				$separator = '<div class="elementor-icon">' . Icons_Manager::try_get_icon_html( $settings['separator_icon'], [ 'aria-hidden' => 'true' ] ) . '</div>';

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
				'viewbox'         => true // <= Must be lower case!
			),
			'g'     => array( 'fill' => true ),
			'title' => array( 'title' => true ),
			'path'  => array(
				'd'    => true,
				'fill' => true
			)
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
