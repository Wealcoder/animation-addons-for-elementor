<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Weather extends Widget_Base {

	public function get_name() {
		return 'aae--weather';
	}

	public function get_title() {
		return esc_html__( 'Weather', 'animation-addons-for-elementor-pro' );
	}

	public function get_icon() {
		return 'wcf eicon-globe';
	}

	public function get_categories() {
		return [ 'animation-addons-for-elementor-pro' ];
	}

	public function get_keywords() {
		return [ 'weather', 'rain', 'cloud' ];
	}

	public function get_style_depends() {
		return [ 'aae--weather' ];
	}

	protected function register_controls() {
		$this->register_weather_layout();

		$this->register_weather_settings();

		$this->style_weather_layout();

		$this->style_weather_time_info();

		$this->style_weather_location();

		$this->style_weather_temperature();

		$this->style_weather_feels_like();

		$this->style_weather_image();

		$this->style_weather_description();

		$this->style_hourly_weather();

		$this->style_forecast_weather();
	}

	protected function register_weather_layout() {
		$this->start_controls_section(
			'section_layout',
			[
				'label' => __( 'Layout', 'animation-addons-for-elementor-pro' ),
			]
		);

		$this->add_control(
			'weather_style',
			[
				'label'   => esc_html__( 'Style', 'animation-addons-for-elementor-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__( 'One', 'animation-addons-for-elementor-pro' ),
					'2' => esc_html__( 'Two', 'animation-addons-for-elementor-pro' ),
				],
			]
		);

		$this->add_control(
			'move_direction',
			[
				'label'     => esc_html__( 'Animation Direction', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'left-right',
				'options'   => [
					'left-right' => esc_html__( 'Left to Right', 'animation-addons-for-elementor-pro' ),
					'right-left' => esc_html__( 'Right to Left', 'animation-addons-for-elementor-pro' ),
					'top-bottom' => esc_html__( 'Top to Bottom', 'animation-addons-for-elementor-pro' ),
					'bottom-top' => esc_html__( 'Bottom to Top', 'animation-addons-for-elementor-pro' ),
				],
				'condition' => [ 'weather_style' => '1' ],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'move_anim_duration',
			[
				'label'     => esc_html__( 'Animation Duration', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 100,
				'default'   => 10,
				'selectors' => [
					'{{WRAPPER}} .aae--weather' => '--duration: {{VALUE}}s',
				],
				'condition' => [ 'weather_style' => '1' ],
			]
		);

		$this->add_responsive_control(
			'layout_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor-pro' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor-pro' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor-pro' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .time-info'                             => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .location-wrap, {{WRAPPER}} .temp-wrap' => 'justify-content: {{VALUE}};',
				],
				'condition' => [ 'weather_style' => '1' ],
			]
		);

		$this->end_controls_section();
	}

	protected function register_weather_settings() {
		$this->start_controls_section(
			'section_settings',
			[
				'label' => __( 'Settings', 'animation-addons-for-elementor-pro' ),
			]
		);

		$this->add_control(
			'show_location',
			[
				'label'        => esc_html__( 'Show Location', 'animation-addons-for-elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor-pro' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_feel_like',
			[
				'label'        => esc_html__( 'Show Feels Like', 'animation-addons-for-elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor-pro' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'weather_style' => '2' ],
			]
		);

		$this->add_control(
			'show_weather_icon',
			[
				'label'        => esc_html__( 'Show Weather Icon', 'animation-addons-for-elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor-pro' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_desc',
			[
				'label'        => esc_html__( 'Show Description', 'animation-addons-for-elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor-pro' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_hourly_weather',
			[
				'label'        => esc_html__( 'Show Hourly Weather', 'animation-addons-for-elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor-pro' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor-pro' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'condition'    => [ 'weather_style' => '2' ],
			]
		);

		$this->add_control(
			'show_forecast_weather',
			[
				'label'        => esc_html__( 'Show Days Weather', 'animation-addons-for-elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor-pro' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor-pro' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'condition'    => [ 'weather_style' => '2' ],
			]
		);

		$this->add_control(
			'location_icon',
			[
				'label'       => esc_html__( 'Location Icon', 'animation-addons-for-elementor-pro' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-map-marker-alt',
					'library' => 'fa-solid',
				],
				'separator'   => 'before',
				'condition'   => [ 'show_location' => 'yes' ],
			]
		);

		$this->add_control(
			'feel_like_text',
			[
				'label'       => esc_html__( 'Feels Like Text', 'animation-addons-for-elementor-pro' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Feels Like', 'animation-addons-for-elementor-pro' ),
				'placeholder' => esc_html__( 'Type your text here', 'animation-addons-for-elementor-pro' ),
				'condition'   => [
					'show_feel_like' => 'yes',
					'weather_style'  => '2',
				],
				'dynamic'     => [
                    'active' => true,
                ],
			]
		);


		$this->end_controls_section();
	}

	protected function style_weather_layout() {
		$this->start_controls_section(
			'style_layout',
			[
				'label' => __( 'Layout', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'layout_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
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
					'{{WRAPPER}} .aae--weather.style-1' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'weather_style' => '1' ],
			]
		);

		$this->add_responsive_control(
			'layout_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .aae--weather.style-1' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'weather_style' => '1' ],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'layout_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aae--weather',
			]
		);

		$this->add_responsive_control(
			'layout_b_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .aae--weather' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'layout_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .aae--weather' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_weather_location() {
		$this->start_controls_section(
			'style_location',
			[
				'label' => __( 'Location', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'location_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .location' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'location_typo',
				'selector' => '{{WRAPPER}} .location',
			]
		);

		$this->add_responsive_control(
			'location_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .location' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator_color',
			[
				'label'     => esc_html__( 'Separator Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .location::after' => 'background-color: {{VALUE}};',
				],
				'condition' => [ 'weather_style' => '1' ],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function style_weather_temperature() {
		$this->start_controls_section(
			'style_temperature',
			[
				'label' => __( 'Temperature', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'temp_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .temp' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'temp_typo',
				'selector' => '{{WRAPPER}} .temp',
			]
		);

		$this->add_responsive_control(
			'temp_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .temp' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'temp_symbol_heading',
			[
				'label'     => esc_html__( 'Symbol', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'temp_symbol_typo',
				'selector' => '{{WRAPPER}} .temp sup',
			]
		);

		$this->end_controls_section();
	}

	protected function style_weather_feels_like() {
		$this->start_controls_section(
			'style_feels_like',
			[
				'label'     => __( 'Feels Like', 'animation-addons-for-elementor-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_feel_like' => 'yes',
					'weather_style'  => '2',
				],
			]
		);

		$this->add_control(
			'feels_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .feels-like' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'feels_typo',
				'selector' => '{{WRAPPER}} .feels-like',
			]
		);

		$this->add_responsive_control(
			'feels_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .feels-like' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'feel_symbol_heading',
			[
				'label'     => esc_html__( 'Symbol', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'feel_symbol_typo',
				'selector' => '{{WRAPPER}} .feels-like sup',
			]
		);

		$this->end_controls_section();
	}

	protected function style_weather_image() {
		$this->start_controls_section(
			'style_weather_img',
			[
				'label' => __( 'Weather Image', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'w_img_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .weather-img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'w_img_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .weather-img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_weather_description() {
		$this->start_controls_section(
			'style_description',
			[
				'label'     => __( 'Description', 'animation-addons-for-elementor-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_desc' => 'yes' ],
			]
		);

		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desc' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typo',
				'selector' => '{{WRAPPER}} .desc',
			]
		);

		$this->add_responsive_control(
			'desc_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .desc' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_hourly_weather() {
		$this->start_controls_section(
			'style_hourly_weather',
			[
				'label'     => __( 'Hourly Weather', 'animation-addons-for-elementor-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_hourly_weather' => 'yes',
					'weather_style'       => '2',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'hourly_border',
				'selector' => '{{WRAPPER}} .hourly',
			]
		);

		$this->add_responsive_control(
			'hourly_b_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .hourly' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'hourly_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .hourly' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'hour_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .hourly' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'hour_time_heading',
			[
				'label'     => esc_html__( 'Time', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'hour_time_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hour-time' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'hour_time_typo',
				'selector' => '{{WRAPPER}} .hour-time',
			]
		);

		$this->add_responsive_control(
			'hour_time_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .hour-time' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'hour_temp_heading',
			[
				'label'     => esc_html__( 'Temp', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'hour_temp_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hour-temp' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'hour_temp_typo',
				'selector' => '{{WRAPPER}} .hour-temp',
			]
		);

		$this->add_responsive_control(
			'hour_temp_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .hour-temp' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'hour_symbol_heading',
			[
				'label'     => esc_html__( 'Symbol', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'hour_symbol_typo',
				'selector' => '{{WRAPPER}} .hour-temp sup',
			]
		);

		$this->add_control(
			'hour_img_heading',
			[
				'label'     => esc_html__( 'Image', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'hour_temp_img_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .hour-block img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'hour_temp_img_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .hour-block img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_forecast_weather() {
		$this->start_controls_section(
			'style_forecast',
			[
				'label'     => __( 'Forecast', 'animation-addons-for-elementor-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_forecast_weather' => 'yes',
					'weather_style'         => '2',
				],
			]
		);

		$this->add_responsive_control(
			'fc_item_gap',
			[
				'label'      => esc_html__( 'Item Gap', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .forecast' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'forecast_border',
				'selector' => '{{WRAPPER}} .forecast',
			]
		);

		$this->add_responsive_control(
			'forecast_b_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .forecast' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'forecast_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .forecast' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'forecast_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .forecast' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'fc_day_heading',
			[
				'label'     => esc_html__( 'Day', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'fc_day_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .fc-day' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'fc_day_typo',
				'selector' => '{{WRAPPER}} .fc-day',
			]
		);

		$this->add_control(
			'max_temp_heading',
			[
				'label'     => esc_html__( 'Max Temp', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'max_temp_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .max-temp' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'max_temp_typo',
				'selector' => '{{WRAPPER}} .max-temp',
			]
		);

		$this->add_control(
			'min_temp_heading',
			[
				'label'     => esc_html__( 'Min Temp', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'min_temp_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .min-temp' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'min_temp_typo',
				'selector' => '{{WRAPPER}} .min-temp',
			]
		);

		$this->add_responsive_control(
			'min_temp_gap',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .min-temp' => 'margin-left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'fc_symbol_heading',
			[
				'label'     => esc_html__( 'Symbol', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'fc_symbol_typo',
				'selector' => '{{WRAPPER}} .fc-temp sup',
			]
		);

		$this->add_control(
			'fc_img_heading',
			[
				'label'     => esc_html__( 'Image', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'fc_img_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .fc-block img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'fc_img_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .fc-block img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_weather_time_info() {
		$this->start_controls_section(
			'style_time_info',
			[
				'label'     => __( 'Time Info', 'animation-addons-for-elementor-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'weather_style' => '1' ],
			]
		);

		$this->add_control(
			'today_heading',
			[
				'label'     => esc_html__( 'Today', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'today_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .today' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'today_typo',
				'selector' => '{{WRAPPER}} .today',
			]
		);

		$this->add_control(
			'date_heading',
			[
				'label'     => esc_html__( 'Date', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'date_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .date' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'date_typo',
				'selector' => '{{WRAPPER}} .date',
			]
		);

		$this->add_responsive_control(
			'date_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .date' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// Render
	protected function render() {
		$settings = $this->get_settings_for_display();

		$weather_api_key  = get_option( 'aae_weather_api_advanced_settings' );
		$weather_settings = json_decode( $weather_api_key, true );

		if ( !isset( $weather_settings['api_key'] ) || empty( $weather_settings['api_key'] ) ) {
			// if elementor editor mode, show a message
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="aae--weather-error">' . esc_html__( 'Please add your OpenWeatherMap API key in the dashboard settings.', 'animation-addons-for-elementor-pro' ) . '</div>';
			}			
			return;
		}

		$city   = $weather_settings['country_name'];
		$unit   = $weather_settings['unit'];
		$apiKey = $weather_settings['api_key'];

		if ( $unit === 'metric' ) {
			$unit_symbol = '°C';
		} elseif ( $unit === 'imperial' ) {
			$unit_symbol = '°F';
		} else {
			$unit_symbol = 'K';
		}

		$cache_key_current  = 'weather_current_' . md5( $city . $unit );
		$cache_key_forecast = 'weather_forecast_' . md5( $city . $unit );

		// Try to get cached data (3 hours = 10800 seconds)
		$current_data  = get_transient( $cache_key_current );
		$forecast_data = get_transient( $cache_key_forecast );

		if ( false === $current_data || false === $forecast_data ) {
			$current_url  = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units={$unit}";
			$forecast_url = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$apiKey}&units={$unit}";

			$current_res  = wp_remote_get( $current_url );
			$forecast_res = wp_remote_get( $forecast_url );

			if ( is_wp_error( $current_res ) || is_wp_error( $forecast_res ) ) {
				echo '<div>Unable to load weather data.</div>';

				return;
			}

			$current_data  = json_decode( wp_remote_retrieve_body( $current_res ) );
			$forecast_data = json_decode( wp_remote_retrieve_body( $forecast_res ) );

			// Save in cache for 3 hours (10800 seconds)
			set_transient( $cache_key_current, $current_data, 10800 );
			set_transient( $cache_key_forecast, $forecast_data, 10800 );
		}

		if ( empty( $current_data ) || empty( $forecast_data ) ) {
			echo '<div>Unable to load weather data.</div>';

			return;
		}

		$current_temp = round( $current_data->main->temp );
		$feels_like   = $current_data->main->feels_like;
		$current_desc = $current_data->weather[0]->description;
		$current_icon = $current_data->weather[0]->icon;
		$icon_url     = "https://openweathermap.org/img/wn/{$current_icon}.png";
		?>

        <div class="aae--weather style-<?php echo esc_attr( $settings['weather_style'] . ' ' . $settings['move_direction'] ); ?>">
			<?php if ( '2' === $settings['weather_style'] ) { ?>
                <div class="weather-head">
                    <div class="left-wrap">
						<?php
						if ( 'yes' === $settings['show_location'] ) {
							?>
                            <div class="location">
                                <span class="icon"><?php Icons_Manager::render_icon( $settings['location_icon'], [ 'aria-hidden' => 'true' ] ); ?></span>
								<?php echo esc_html( $city ); ?>
                            </div>
							<?php
						}
						?>
                        <div class="temp">
							<?php echo esc_html( $current_temp ); ?>
                            <sup><?php echo esc_html( $unit_symbol ); ?></sup>
                        </div>
						<?php
						if ( 'yes' === $settings['show_feel_like'] ) {
							?>
                            <div class="feels-like">
								<?php if ( ! empty( $settings['feel_like_text'] ) ) { ?>
                                    <span><?php echo esc_html( $settings['feel_like_text'] ); ?></span>
								<?php } ?>
								<?php echo esc_html( $feels_like ); ?> <sup><?php echo esc_html( $unit_symbol ); ?></sup>
                            </div>
							<?php
						}
						?>
                    </div>
                    <div class="right-wrap">
						<?php
						if ( 'yes' === $settings['show_weather_icon'] ) {
							?>
                            <div class="weather-img">
                                <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $current_desc ); ?>">
                            </div>
							<?php
						}
						if ( 'yes' === $settings['show_desc'] ) {
							?>
                            <div class="desc"><?php echo esc_html( $current_desc ); ?></div>
							<?php
						}
						?>
                    </div>
                </div>
				<?php
				if ( 'yes' === $settings['show_hourly_weather'] ) {
					$this->render_hourly_weather( $forecast_data, $unit_symbol );
				}

				if ( 'yes' === $settings['show_forecast_weather'] ) {
					$this->render_forecast_weather( $forecast_data, $unit_symbol );
				}
				?>
			<?php } else { ?>
                <div class="time-info">
                    <div class="today"><?php echo esc_html( gmdate( 'l' ) ); ?></div>
                    <div class="date"><?php echo esc_html( gmdate( 'F j, Y' ) ); ?></div>
                </div>
                <div class="weather-info">
                    <div class="temp-wrap">
						<?php
						if ( 'yes' === $settings['show_weather_icon'] ) {
							?>
                            <div class="weather-img">
                                <img src="<?php echo esc_url( $icon_url ); ?>"
                                     alt="<?php echo esc_attr( $current_desc ); ?>">
                            </div>
							<?php
						}
						?>
                        <div class="temp">
							<?php echo esc_html( $current_temp ); ?>
                            <sup><?php echo esc_html( $unit_symbol ); ?></sup>
                        </div>
                    </div>
                    <div class="location-wrap">
						<?php
						if ( 'yes' === $settings['show_desc'] ) {
							?>
                            <div class="desc"><?php echo esc_html( $current_desc ); ?></div>
							<?php
						}
						if ( 'yes' === $settings['show_location'] ) {
							?>
                            <div class="location">
                                <span class="icon"><?php Icons_Manager::render_icon( $settings['location_icon'], [ 'aria-hidden' => 'true' ] ); ?></span>
								<?php echo esc_html( $city ); ?>
                            </div>
							<?php
						}
						?>
                    </div>
                </div>
			<?php } ?>
        </div>
		<?php
	}

	protected function render_hourly_weather( $forecast_data, $unit_symbol ) {
		$today = gmdate( 'Y-m-d' );
		$count = 0;
		?>
        <div class="hourly">
			<?php
			foreach ( $forecast_data->list as $entry ) {
				$entry_date = substr( $entry->dt_txt, 0, 10 );

				if ( $entry_date === $today && $count < 4 ) {
					$time = gmdate( 'h:i A', strtotime( $entry->dt_txt ) );
					$temp = round( $entry->main->temp );
					$icon = $entry->weather[0]->icon;
					?>
                    <div class='hour-block'>
                        <div class='hour-time'><?php echo esc_html( $time ); ?></div>
                        <img src='<?php echo esc_url( 'https://openweathermap.org/img/wn/' . $icon . '.png' ); ?>' alt='icon'/>
                        <div class='hour-temp'><?php echo esc_html( $temp ); ?> <sup><?php echo esc_html( $unit_symbol ); ?></sup></div>
                    </div>
					<?php
					$count ++;
				}
			}
			?>
        </div>
		<?php
	}

	protected function render_forecast_weather( $forecast_data, $unit_symbol ) {
		?>
        <div class="forecast">
			<?php
			$days = [];
			foreach ( $forecast_data->list as $entry ) {
				$date = gmdate( 'Y-m-d', strtotime( $entry->dt_txt ) );
				if ( ! isset( $days[ $date ] ) ) {
					$days[ $date ] = [
						'temps' => [],
						'icons' => [],
					];
				}
				$days[ $date ]['temps'][] = $entry->main->temp;
				$days[ $date ]['icons'][] = $entry->weather[0]->icon;
			}

			$i = 0;
			foreach ( $days as $date => $data ) {
				if ( $i ++ >= 4 ) {
					break;
				}

				$day_label = ( $i === 1 ) ? 'Today' : gmdate( 'l', strtotime( $date ) );
				$avg_temp  = round( array_sum( $data['temps'] ) / count( $data['temps'] ) );
				$min_temp  = round( min( $data['temps'] ) );
				$max_temp  = round( max( $data['temps'] ) );
				$icon      = $data['icons'][0];

				?>
                <div class='fc-block'>
                    <div class='fc-day'><?php echo esc_html( $day_label ); ?></div>
                    <img src='<?php echo esc_url( 'https://openweathermap.org/img/wn/' . $icon . '.png' ); ?>'
                         alt='<?php echo esc_attr( $day_label ); ?>'/>
                    <div class='fc-temp'>
                        <span class="max-temp"><?php echo esc_html( $max_temp ); ?> <sup><?php echo esc_html( $unit_symbol ); ?></sup></span>
                        <span class="min-temp"><?php echo esc_html( $min_temp ); ?> <sup><?php echo esc_html( $unit_symbol ); ?></sup></span>
                    </div>
                </div>
				<?php
			}
			?>
        </div>
		<?php
	}
}