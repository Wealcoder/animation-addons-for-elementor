<?php

namespace WCF_ADDONS;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

trait AAE_Nested_Slider_Trait {

	/**
	 * Register the slider controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_slider_controls( $default_value = array() ) {
		$default = array(
			'slides_to_show'       => 'auto',
			'autoplay'             => 'yes',
			'autoplay_delay'       => 3000,
			'autoplay_interaction' => 'true',
			'allow_touch_move'     => 'false',
			'loop'                 => 'true',
			'mousewheel'           => '',
			'speed'                => 500,
			'space_between'        => 20,
			// navigation
			'navigation'           => 'yes',
			// pagination
			'pagination'           => 'yes',
			'pagination_type'      => 'bullets',
			'direction'            => 'ltr',
		);

		$default = array_merge( $default, $default_value );

		$slides_to_show = range( 1, 10 );
		$slides_to_show = array_combine( $slides_to_show, $slides_to_show );

		// $this->add_control(
		// 'aaee_slider_refresh',
		// [
		// 'label' => '',
		// 'type' => \Elementor\Controls_Manager::BUTTON,
		// 'separator' => 'before',
		// 'button_type' => 'warning',
		// 'text' => esc_html__( 'Save Change', 'animation-addons-for-elementor' ),
		// 'event' => 'aae_nsslider:editor:savechnage',
		// ]
		// );

		$this->add_responsive_control(
			'slides_to_show',
			array(
				'label'              => esc_html__( 'Slides to Show', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => $default['slides_to_show'],
				'required'           => true,
				'options'            => array(
					'auto' => esc_html__( 'Auto', 'animation-addons-for-elementor' ),
				) + $slides_to_show,
				'render_type'        => 'none', // template
				'frontend_available' => true,
				'selectors'          => array(
					'{{WRAPPER}} .wcf__slider' => '--slides-to-show: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'              => esc_html__( 'Autoplay', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SELECT,
				'frontend_available' => true,
				'default'            => $default['autoplay'],
				'options'            => array(
					'yes' => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'no'  => esc_html__( 'No', 'animation-addons-for-elementor' ),
				),
				'render_type'        => 'none', // template
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'              => esc_html__( 'Autoplay delay', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::NUMBER,
				'default'            => $default['autoplay_delay'],
				'condition'          => array(
					'autoplay' => 'yes',
				),
				'render_type'        => 'none', // template
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'autoplay_interaction',
			array(
				'label'       => esc_html__( 'Autoplay Interaction', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => $default['autoplay_interaction'],
				'options'     => array(
					'true'  => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'false' => esc_html__( 'No', 'animation-addons-for-elementor' ),
				),
				'condition'   => array(
					'autoplay' => 'yes',
				),
				'render_type' => 'none', // template
			)
		);

		$this->add_control(
			'allow_touch_move',
			array(
				'label'       => esc_html__( 'Allow Touch Move', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'separator'   => 'before',
				'default'     => $default['allow_touch_move'],
				'options'     => array(
					'true'  => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'false' => esc_html__( 'No', 'animation-addons-for-elementor' ),
				),
				'render_type' => 'none', // template
			)
		);

		// Loop requires a re-render so no 'render_type = none'
		$this->add_control(
			'loop',
			array(
				'label'              => esc_html__( 'Loop', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => $default['loop'],
				'options'            => array(
					'true'  => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'false' => esc_html__( 'No', 'animation-addons-for-elementor' ),
				),
				'render_type'        => 'none', // template
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'mousewheel',
			array(
				'label'       => esc_html__( 'Mousewheel', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'If you want to use mousewheel, please disable loop.', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SWITCHER,
				'label_on'    => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'   => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'     => $default['mousewheel'],
				'render_type' => 'none',                                                                                                 // template

			)
		);

		$this->add_control(
			'speed',
			array(
				'label'              => esc_html__( 'Animation Speed', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::NUMBER,
				'default'            => $default['speed'],
				'render_type'        => 'none', // template
				'frontend_available' => true,
			)
		);

		$this->add_responsive_control(
			'space_between',
			array(
				'label'              => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::NUMBER,
				'default'            => $default['space_between'],
				'render_type'        => 'none',                                                            // template
				'selectors'          => array(
					'{{WRAPPER}} .wcf__slider' => '--space-between: {{VALUE}}px;',
				),
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'grid_rows',
			array(
				'label'              => esc_html__( 'Grid Rows', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 1,
				'max'                => 10,
				'default'            => 1,
				'condition'          => array( 'enable_grid' => 'yes' ),
				'render_type'        => 'none', // template
				'frontend_available' => true,
			)
		);

		// slider navigation
		$this->add_control(
			'navigation',
			array(
				'label'              => esc_html__( 'Navigation', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SWITCHER,
				'separator'          => 'before',
				'label_on'           => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'          => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'            => $default['navigation'],
				'render_type'        => 'ui', // template
				'frontend_available' => true,

			)
		);

		$this->add_control(
			'navigation_previous_icon',
			array(
				'label'              => esc_html__( 'Previous Arrow Icon', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::ICONS,
				'fa4compatibility'   => 'icon',
				'skin'               => 'inline',
				'render_type'        => 'none', // template
				'frontend_available' => true,
				'label_block'        => false,
				'skin_settings'      => array(
					'inline' => array(
						'none' => array(
							'label' => 'Default',
							'icon'  => 'eicon-chevron-left',
						),
						'icon' => array(
							'icon' => 'eicon-star',
						),
					),
				),
				'recommended'        => array(
					'fa-regular' => array(
						'arrow-alt-circle-left',
						'caret-square-left',
					),
					'fa-solid'   => array(
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
					),
				),
				'condition'          => array( 'navigation' => 'yes' ),

			)
		);

		$this->add_control(
			'navigation_next_icon',
			array(
				'label'              => esc_html__( 'Next Arrow Icon', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::ICONS,
				'fa4compatibility'   => 'icon',
				'skin'               => 'inline',
				'label_block'        => false,
				'render_type'        => 'none', // template
				'frontend_available' => true,
				'skin_settings'      => array(
					'inline' => array(
						'none' => array(
							'label' => 'Default',
							'icon'  => 'eicon-chevron-right',
						),
						'icon' => array(
							'icon' => 'eicon-star',
						),
					),
				),
				'recommended'        => array(
					'fa-regular' => array(
						'arrow-alt-circle-right',
						'caret-square-right',
					),
					'fa-solid'   => array(
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
					),
				),
				'condition'          => array( 'navigation' => 'yes' ),
			)
		);

		// slider pagination
		$this->add_control(
			'pagination',
			array(
				'label'              => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SWITCHER,
				'separator'          => 'before',
				'label_on'           => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'          => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'            => $default['navigation'],
				'render_type'        => 'ui', // template
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'pagination_type',
			array(
				'label'              => esc_html__( 'Pagination Type', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => $default['pagination_type'],
				'options'            => array(
					'bullets'     => esc_html__( 'Bullets', 'animation-addons-for-elementor' ),
					'fraction'    => esc_html__( 'Fraction', 'animation-addons-for-elementor' ),
					'progressbar' => esc_html__( 'Progressbar', 'animation-addons-for-elementor' ),
				),
				'condition'          => array( 'pagination' => 'yes' ),
				'render_type'        => 'ui', // template
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'              => esc_html__( 'Left/Right Direction', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SELECT,
				'separator'          => 'before',
				'default'            => $default['direction'],
				'options'            => array(
					'ltr' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
					'rtl' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
				),
				'render_type'        => 'ui', // template
				'frontend_available' => true,
			)
		);
	}

	/**
	 * Register the slider navigation style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	protected function register_slider_navigation_style_controls( $default_selectors = array() ) {
		$selectors = array(
			'arrow_size'               => array( '{{WRAPPER}} .wcf-arrow' => 'font-size: {{SIZE}}{{UNIT}};' ),
			'arrow_circle_size'        => array( '{{WRAPPER}} .wcf-arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			'svg'                      => array( '{{WRAPPER}} .wcf-arrow svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			'arrow_padding'            => array( '{{WRAPPER}} .wcf-arrow' => 'padding: {{SIZE}}{{UNIT}};' ),
			'arrow_color'              => array( '{{WRAPPER}} .wcf-arrow' => 'color: {{VALUE}}; fill: {{VALUE}};' ),
			'arrow_background'         => '{{WRAPPER}} .wcf-arrow',
			'arrow_hover_color'        => array( '{{WRAPPER}} .wcf-arrow:hover, {{WRAPPER}} .wcf-arrow:focus' => 'color: {{VALUE}}; fill: {{VALUE}};' ),
			'arrow_hover_background'   => '{{WRAPPER}} .wcf-arrow:hover, {{WRAPPER}} .wcf-arrow:focus',
			'arrow_hover_border_color' => array( '{{WRAPPER}} .wcf-arrow:hover, {{WRAPPER}} .wcf-arrow:focus' => 'border-color: {{VALUE}};' ),
			'arrow_border'             => '{{WRAPPER}} .wcf-arrow',
			'arrow_border_radius'      => array( '{{WRAPPER}} .wcf-arrow' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		);

		$selectors = array_merge( $selectors, $default_selectors );

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'     => esc_html__( 'Arrow Font Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 5,
						'max' => 100,
					),
				),
				'selectors' => $selectors['arrow_size'],
			)
		);

		$this->add_responsive_control(
			'arrow_svg_size',
			array(
				'label'     => esc_html__( ' Svg Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 5,
						'max' => 100,
					),
				),
				'selectors' => $selectors['svg'],
			)
		);

		$this->add_responsive_control(
			'arrow_circle_size',
			array(
				'label'     => esc_html__( 'Circle Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'selectors' => $selectors['arrow_circle_size'],
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'arrow_border',
				'selector' => $selectors['arrow_border'],
			)
		);

		$this->add_responsive_control(
			'arrow_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => $selectors['arrow_border_radius'],
			)
		);

		$this->add_responsive_control(
			'arrow_padding',
			array(
				'label'     => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'selectors' => $selectors['arrow_padding'],
			)
		);

		$this->start_controls_tabs( 'tabs_arrow_style' );

		$this->start_controls_tab(
			'tab_arrow_normal',
			array(
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['arrow_color'],
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'arrow_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $selectors['arrow_background'],
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_arrow_hover',
			array(
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'arrow_hover_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['arrow_hover_color'],
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'arrow_hover_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $selectors['arrow_hover_background'],
			)
		);

		$this->add_control(
			'arrow_hover_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['arrow_hover_border_color'],
				'condition' => array( 'arrow_border_border!' => '' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'navigation_position',
			array(
				'label'     => esc_html__( 'Position Type', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''         => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'absolute' => esc_html__( 'Absolute', 'animation-addons-for-elementor' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ts-navigation' => 'position: {{VALUE}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'navigation_align',
			array(
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start'    => array(
						'title' => esc_html__( 'Start', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-justify-start-h',
					),
					'center'        => array(
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-justify-center-h',
					),
					'flex-end'      => array(
						'title' => esc_html__( 'End', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-justify-end-h',
					),
					'space-between' => array(
						'title' => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-justify-space-between-h',
					),
				),
				'toggle'    => true,
				'selectors' => array(
					'{{WRAPPER}} .ts-navigation' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'navigation_gap',
			array(
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ts-navigation' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'navigation_width',
			array(
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 2000,
					),
					'%'  => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ts-navigation' => 'width: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'navigation_position' => 'absolute' ),
			)
		);

		$this->add_control(
			'navigation_hr_orn',
			array(
				'label'        => esc_html__( 'Horizontal Orientation', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'prefix_class' => 'aae--slider-hrp-',
				'options'      => array(
					'left'  => array(
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-left',
					),
					'right' => array(
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'      => 'left',
				'condition'    => array( 'navigation_position' => 'absolute' ),
			)
		);

		$this->add_responsive_control(
			'navigation_hr_offset',
			array(
				'label'      => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => - 1000,
						'max' => 1000,
					),
					'%'  => array(
						'min' => - 100,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ts-navigation' => '--hr-offset: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'navigation_position' => 'absolute' ),
			)
		);

		$this->add_control(
			'navigation_vr_orn',
			array(
				'label'        => esc_html__( 'Vertical Orientation', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'prefix_class' => 'aae--slider-vrp-',
				'options'      => array(
					'top'    => array(
						'title' => esc_html__( 'Top', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-top',
					),
					'bottom' => array(
						'title' => esc_html__( 'Bottom', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-bottom',
					),
				),
				'default'      => 'bottom',
				'condition'    => array( 'navigation_position' => 'absolute' ),
			)
		);

		$this->add_responsive_control(
			'navigation_vr_offset',
			array(
				'label'      => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => - 1000,
						'max' => 1000,
					),
					'%'  => array(
						'min' => - 100,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ts-navigation' => '--vr-offset: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'navigation_position' => 'absolute' ),
			)
		);
	}

	/**
	 * Register the slider pagination style controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_slider_pagination_style_controls( $default_selectors = array() ) {
		$selectors = array(
			'bullets_size'           => array(
				'{{WRAPPER}} .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
			'bullets_inactive_color' => array(
				'{{WRAPPER}} .swiper-pagination-bullet:not(.swiper-pagination-bullet-active)' => 'background: {{VALUE}}; opacity: 1',
			),
			'bullets_color'          => array(
				'{{WRAPPER}} .swiper-pagination-bullet' => 'background: {{VALUE}};',
			),
			'bullets_border'         => '{{WRAPPER}} .swiper-pagination-bullet',
			// fraction
			'fraction_current_color' => array(
				'{{WRAPPER}} .swiper-pagination-current' => 'color: {{VALUE}}',
			),
			'fraction_total_color'   => array(
				'{{WRAPPER}} .swiper-pagination-total' => 'color: {{VALUE}}',
			),
			'fraction_midline_color' => array(
				'{{WRAPPER}} .mid-line' => 'background-color: {{VALUE}}',
			),
			// progressbar
			'progress_color'         => array( '{{WRAPPER}} .swiper-pagination-progressbar' => 'background-color: {{VALUE}}' ),
			'progress_fill_color'    => array( '{{WRAPPER}} .swiper-pagination-progressbar-fill' => 'background-color: {{VALUE}}' ),
		);

		$selectors = array_merge( $selectors, $default_selectors );

		// Pagination Bullets
		$this->add_control(
			'bullets_inactive_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['bullets_inactive_color'],
				'condition' => array( 'pagination_type' => 'bullets' ),
			)
		);

		$this->add_control(
			'bullets_color',
			array(
				'label'     => esc_html__( 'Active Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['bullets_color'],
				'condition' => array( 'pagination_type' => 'bullets' ),
			)
		);

		$this->add_responsive_control(
			'bullets_size',
			array(
				'label'     => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 2,
						'max' => 100,
					),
				),
				'selectors' => $selectors['bullets_size'],
				'condition' => array( 'pagination_type' => 'bullets' ),
			)
		);

		$this->add_responsive_control(
			'bullets_gap',
			array(
				'label'     => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullets' => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'pagination_type' => 'bullets' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'bullets_border',
				'selector'  => $selectors['bullets_border'],
				'condition' => array( 'pagination_type' => 'bullets' ),
			)
		);

		$this->add_control(
			'bullets_b_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .swiper-pagination-bullet' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array( 'pagination_type' => 'bullets' ),
			)
		);

		$this->add_control(
			'bullets_direction',
			array(
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'row',
				'options'   => array(
					'row'    => esc_html__( 'Row', 'animation-addons-for-elementor' ),
					'column' => esc_html__( 'Column', 'animation-addons-for-elementor' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullets' => 'flex-direction: {{VALUE}};',
				),
				'condition' => array( 'pagination_type' => 'bullets' ),
			)
		);

		// Pagination Fraction
		$this->add_control(
			'fraction_current_color',
			array(
				'label'     => esc_html__( 'Current Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['fraction_current_color'],
				'condition' => array( 'pagination_type' => 'fraction' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'fraction_current_typo',
				'selector'  => '{{WRAPPER}} .swiper-pagination-current',
				'condition' => array( 'pagination_type' => 'fraction' ),
			)
		);

		$this->add_control(
			'fraction_total_color',
			array(
				'label'     => esc_html__( 'Total Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['fraction_total_color'],
				'condition' => array( 'pagination_type' => 'fraction' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'fraction_total_typo',
				'selector'  => '{{WRAPPER}} .swiper-pagination-total',
				'condition' => array( 'pagination_type' => 'fraction' ),
			)
		);

		$this->add_control(
			'fraction_midline_color',
			array(
				'label'     => esc_html__( 'Mid Line Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['fraction_midline_color'],
				'condition' => array( 'pagination_type' => 'fraction' ),
			)
		);

		$this->add_responsive_control(
			'fraction_line_width',
			array(
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 1000,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mid-line, {{WRAPPER}} .swiper-pagination-progressbar' => 'width: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'pagination_type!' => 'bullets' ),
			)
		);

		$this->add_responsive_control(
			'fraction_line_height',
			array(
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 500,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),

				'selectors'  => array(
					'{{WRAPPER}} .mid-line, {{WRAPPER}} .swiper-pagination-progressbar, {{WRAPPER}} .ts-pagination' => 'height: {{SIZE}}{{UNIT}};',
				),
				'default'    => array(
					'unit' => 'px',   // one of the size_units array
					'size' => 3,     // the starting number
				),
				'condition'  => array( 'pagination_type!' => 'bullets' ),
			)
		);

		$this->add_responsive_control(
			'fraction_gap',
			array(
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .swiper-pagination-fraction' => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'pagination_type' => 'fraction' ),
			)
		);

		// Pagination Progressbar
		$this->add_control(
			'progress_color',
			array(
				'label'     => esc_html__( 'Progressbar Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['progress_color'],
				'condition' => array( 'pagination_type' => 'progressbar' ),
			)
		);

		$this->add_control(
			'progress_fill_color',
			array(
				'label'     => esc_html__( 'Progressbar Fill Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors['progress_fill_color'],
				'condition' => array( 'pagination_type' => 'progressbar' ),
			)
		);

		// Position Style For All Style
		$this->add_control(
			'pagination_position',
			array(
				'label'     => esc_html__( 'Position', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'relative',
				'options'   => array(
					'relative' => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'absolute' => esc_html__( 'Absolute', 'animation-addons-for-elementor' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ts-pagination' => 'position: {{VALUE}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'progressbar_rotate',
			array(
				'label'      => esc_html__( 'Rotate', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 360,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .swiper-pagination-progressbar' => 'transform: rotate({{SIZE}}deg);',
				),
				'condition'  => array( 'pagination_type' => 'progressbar' ),
			)
		);

		$this->add_responsive_control(
			'pagination_align',
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
				'toggle'    => true,
				'selectors' => array(
					'{{WRAPPER}} .ts-pagination' => 'text-align: {{VALUE}};',
				),
				'condition' => array( 'pagination_position' => 'relative' ),
			)
		);

		$this->add_control(
			'pagination_hr_orn',
			array(
				'label'        => esc_html__( 'Horizontal Orientation', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'prefix_class' => 'aae--slider-pg-hr-',
				'options'      => array(
					'left'  => array(
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-left',
					),
					'right' => array(
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'condition'    => array( 'pagination_position' => 'absolute' ),
			)
		);

		$this->add_responsive_control(
			'pagination_hr_offset',
			array(
				'label'      => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => - 1000,
						'max' => 1000,
					),
					'%'  => array(
						'min' => - 100,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ts-pagination' => '--pg-hr-offset: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'pagination_position' => 'absolute' ),
			)
		);

		$this->add_control(
			'pagination_vr_orn',
			array(
				'label'        => esc_html__( 'Vertical Orientation', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'prefix_class' => 'aae--slider-pg-vr-',
				'options'      => array(
					'top'    => array(
						'title' => esc_html__( 'Top', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-top',
					),
					'bottom' => array(
						'title' => esc_html__( 'Bottom', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-bottom',
					),
				),
				'condition'    => array( 'pagination_position' => 'absolute' ),
			)
		);

		$this->add_responsive_control(
			'pagination_vr_offset',
			array(
				'label'      => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => - 1000,
						'max' => 1000,
					),
					'%'  => array(
						'min' => - 100,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ts-pagination' => '--pg-vr-offset: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'pagination_position' => 'absolute' ),
			)
		);
	}

	/**
	 * get slider settings.
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function get_slider_attributes( $settings = array() ) {
		if ( empty( $settings ) ) {
			$settings = $this->get_settings_for_display();
		}

		// slider settings
		$slider_settings = array(
			'loop'           => 'true' === $settings['loop'],
			'speed'          => $settings['speed'],
			'allowTouchMove' => 'true' === $settings['allow_touch_move'],
			'slidesPerView'  => $settings['slides_to_show'],
			'spaceBetween'   => $settings['space_between'],
		);

		if ( 'yes' === $settings['autoplay'] ) {
			$slider_settings['autoplay'] = array(
				'delay'                => $settings['autoplay_delay'],
				'disableOnInteraction' => $settings['autoplay_interaction'],
			);
		}

		if ( ! empty( $settings['navigation'] ) ) {
			$slider_settings['navigation'] = array(
				'nextEl' => '.elementor-element-' . $this->get_id() . ' .wcf-arrow-next',
				'prevEl' => '.elementor-element-' . $this->get_id() . ' .wcf-arrow-prev',
			);
		}

		if ( ! empty( $settings['pagination'] ) ) {
			$slider_settings['pagination'] = array(
				'el'        => '.elementor-element-' . $this->get_id() . ' .swiper-pagination',
				'clickable' => true,
				'type'      => $settings['pagination_type'],
			);
		}

		if ( ! empty( $settings['mousewheel'] ) ) {
			$slider_settings['mousewheel'] = array(
				'releaseOnEdges' => true,
			);
		}

		if ( ! empty( $settings['enable_grid'] ) ) {
			$slider_settings['grid'] = array(
				'rows' => $settings['grid_rows'],
				'fill' => 'row',
			);
		}

		// slider breakpoints
		$active_breakpoints = Plugin::$instance->breakpoints->get_active_breakpoints();

		foreach ( $active_breakpoints as $breakpoint_name => $breakpoint ) {
			$slides_to_show = ! empty( $settings[ 'slides_to_show_' . $breakpoint_name ] ) ? $settings[ 'slides_to_show_' . $breakpoint_name ] : $settings['slides_to_show'];
			$space_between  = ! empty( $settings[ 'space_between_' . $breakpoint_name ] ) ? $settings[ 'space_between_' . $breakpoint_name ] : $settings['space_between'];

			$slider_settings['breakpoints'][ $breakpoint->get_value() ]['slidesPerView'] = $slides_to_show;
			$slider_settings['breakpoints'][ $breakpoint->get_value() ]['spaceBetween']  = $space_between;
		}

		$swiper_class = Plugin::$instance->experiments->is_feature_active( 'e_swiper_latest' ) ? 'swiper' : 'swiper-container';

		$this->add_render_attribute(
			'carousel-wrapper',
			array(
				'class' => 'wcf__slider swiper',
				'dir'   => $settings['direction'],
				'style' => 'position: static',
			)
		);

		return $slider_settings;
	}

	protected function render_slider_navigation() {
		if ( empty( $this->get_settings( 'navigation' ) ) ) {
			return;
		}
		?>
		<div class="ts-navigation">
			<div class="wcf-arrow wcf-arrow-prev" role="button" tabindex="0">
				<?php $this->render_swiper_button( 'previous' ); ?>
			</div>
			<div class="wcf-arrow wcf-arrow-next" role="button" tabindex="0">
				<?php $this->render_swiper_button( 'next' ); ?>
			</div>
		</div>
		<?php
	}

	protected function render_slider_pagination() {
		if ( empty( $this->get_settings( 'pagination' ) ) ) {
			return;
		}
		?>
		<div class="ts-pagination">
			<div class="swiper-pagination"></div>
		</div>
		<?php
	}

	/**
	 * Render swiper button.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function render_swiper_button( $type ) {
		$direction     = 'next' === $type ? 'right' : 'left';
		$icon_settings = $this->get_settings( 'navigation_' . $type . '_icon' );

		if ( empty( $icon_settings['value'] ) ) {
			$icon_settings = array(
				'library' => 'eicons',
				'value'   => 'eicon-chevron-' . $direction,
			);
		}

		Icons_Manager::render_icon( $icon_settings, array( 'aria-hidden' => 'true' ) );
	}
}

