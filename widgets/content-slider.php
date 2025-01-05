<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;
use WCF_ADDONS\WCF_Slider_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Elementor Content slider widget.
 *
 * Elementor widget that displays elementor template as slide item.
 * pieces of content.
 *
 * @since 1.0.0
 */
class Content_Slider extends Widget_Base {

	use WCF_Slider_Trait;

	/**
	 * Get widget name.
	 *
	 * Retrieve tabs widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 * @access public
	 */
	public function get_name() {
		return 'wcf--content-slider';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve tabs widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 * @access public
	 */
	public function get_title() {
		return esc_html__( 'Content Slider', 'animation-addons-for-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve tabs widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 * @access public
	 */
	public function get_icon() {
		return 'wcf eicon-post-slider';
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
		return [ 'swiper', 'wcf--slider'];
	}
	
	public function get_style_depends() {
		return [ 'swiper'];
	}

	/**
	 * Register tabs widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'section_content_slider',
			[
				'label' => esc_html__( 'Content Slider', 'animation-addons-for-elementor' ),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'content_type',
			[
				'label'   => esc_html__('Content Type', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'content'  => esc_html__('Content', 'animation-addons-for-elementor'),
					'template' => esc_html__('Saved Templates', 'animation-addons-for-elementor'),
				],
				'default' => 'content',
			]
		);

		$repeater->add_control(
			'elementor_templates',
			[
				'label'       => esc_html__( 'Save Template', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => false,
				'multiple'    => false,
				'options'     => wcf_addons_get_saved_template_list(),
				'condition'   => [
					'content_type' => 'template',
				],
			]
		);

		$repeater->add_control(
			'slide_content',
			[
				'label'       => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'default'     => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::WYSIWYG,
				'condition'   => [
					'content_type' => 'content',
				],
			]
		);

		$this->add_control(
			'content_slider',
			[
				'label'       => esc_html__( 'Content Slides', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [ [], [], [], [], [] ],
				'title_field' => '{{{ content_type }}}',
			]
		);

		$this->add_responsive_control(
			'slider-max-width',
			[
				'label'      => esc_html__( 'Max Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf__slider' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'slide_align',
			[
				'label'     => esc_html__( 'Align Items', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => '',
				'options'   => [
					'flex-start' => [
						'title' => esc_html__( 'Start', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-start-v',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-center-v',
					],
					'flex-end'   => [
						'title' => esc_html__( 'End', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-end-v',
					],
					'stretch'    => [
						'title' => esc_html__( 'Stretch', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-stretch-v',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .swiper-wrapper' => 'align-items: {{VALUE}};',
					'{{WRAPPER}} .swiper-slide'   => 'height: auto',
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
			'slides_to_show' => 3,
			'autoplay'       => 'no',
		];
		$this->register_slider_controls( $default );

		$this->add_control(
			'center_slide',
			[
				'label'        => esc_html__( 'Center Slide', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'effect',
			[
				'label'   => esc_html__( 'Effect', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => [
					'slide'     => esc_html__( 'Slide', 'animation-addons-for-elementor' ),
					'fade'      => esc_html__( 'Fade', 'animation-addons-for-elementor' ),
					'coverflow' => esc_html__( 'Coverflow', 'animation-addons-for-elementor' ),
					'flip'      => esc_html__( 'Flip', 'animation-addons-for-elementor' ),
					'cube'      => esc_html__( 'Cube', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'slide_popover_toggle',
			[
				'label'     => esc_html__( 'slide Scale', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'center_slide' => 'yes' ]
			]
		);

		$this->start_popover();

		$this->add_control(
			'slide_scale_x',
			[
				'label'     => esc_html__( 'Slide Scale X', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max'  => 1,
						'step' => 0.1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .swiper-slide' => '--scale-x:{{SIZE}};',
				],
			]
		);

		$this->add_control(
			'slide_scale_y',
			[
				'label'     => esc_html__( 'Slide Scale Y', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max'  => 1,
						'step' => 0.1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .swiper-slide' => '--scale-y:{{SIZE}};',
				],
			]
		);

		$this->end_popover();

		$this->end_controls_section();

		//slide style
		$this->start_controls_section( 'section_slide_style', [
			'label' => esc_html__( 'Slide', 'animation-addons-for-elementor' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'box_background_color',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .swiper-slide',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'box_border',
				'selector' => '{{WRAPPER}} .swiper-slide',
			]
		);

		$this->add_responsive_control(
			'box_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .swiper-slide' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .swiper-slide' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

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

		$this->add_responsive_control(
			'slider_pagination_bullet_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}}.wcf--slider-pagination-top .swiper-pagination-bullet'=> 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wcf--slider-pagination-bottom .swiper-pagination-bullet'=> 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wcf--slider-pagination-left .swiper-pagination'   => 'gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wcf--slider-pagination-right .swiper-pagination ' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'pagination_type' => 'bullets' ]
			]
		);

		$this->add_control(
			'slider_pagination_position',
			[
				'label'        => esc_html__( 'Pagination Position', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'bottom',
				'separator'    => 'before',
				'options'      => [
					'bottom' => esc_html__( 'Bottom', 'animation-addons-for-elementor' ),
					'top'    => esc_html__( 'Top', 'animation-addons-for-elementor' ),
					'left'   => esc_html__( 'Left', 'animation-addons-for-elementor' ),
					'right'  => esc_html__( 'Right', 'animation-addons-for-elementor' ),
				],
				'prefix_class' => 'wcf--slider-pagination-',
				'condition'    => [ 'pagination_type!' => 'progressbar' ]
			]
		);

		$this->add_responsive_control(
			'slider_pagination_offset',
			[
				'label'      => esc_html__( 'Pagination Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => - 500,
						'max' => 500,
					],
					'%'  => [
						'min' => - 100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}.wcf--slider-pagination-top .swiper-pagination'    => 'top: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wcf--slider-pagination-bottom .swiper-pagination' => 'bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wcf--slider-pagination-left .swiper-pagination'   => 'left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wcf--slider-pagination-right .swiper-pagination ' => 'right: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'pagination_type!' => 'progressbar' ]
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render tabs widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['content_slider'] ) ) {
			return;
		}

		$slider_settings = $this->get_slider_attributes();

		$slider_settings['centeredSlides'] = $settings['center_slide'];
		$slider_settings['effect'] = $settings['effect'];

		$this->add_render_attribute(
			'wrapper',
			[
				'class'         => [ 'wcf__slider-wrapper wcf__content_slider' ],
				'data-settings' => json_encode( $slider_settings ), //phpcs:ignore
			]
		);
		?>
        <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
            <div <?php $this->print_render_attribute_string( 'carousel-wrapper' ) ?>>
                <div class="swiper-wrapper">
					<?php
					foreach ( $settings['content_slider'] as $index => $item ) {
						$this->render_slide( $settings, $index, $item );
					}
					?>
                </div>
            </div>

            <!--navigation -->
			<?php $this->render_slider_navigation(); ?>

            <!--pagination -->
			<?php $this->render_slider_pagination(); ?>
        </div>
		<?php
	}

	protected function render_slide($settings, $index, $item){
		?>
        <div class="swiper-slide">
	        <?php
	        if ( 'content' === $item['content_type'] ) {
		        $this->print_text_editor( $item['slide_content'] );
	        } else {
		        if ( ! empty( $item['elementor_templates'] ) ) {
					if('publish' === get_post_status( $item['elementor_templates'] )){
			            echo Plugin::$instance->frontend->get_builder_content( $item['elementor_templates'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		            }
		        }
	        }
	        ?>
        </div>
		<?php
	}
}
