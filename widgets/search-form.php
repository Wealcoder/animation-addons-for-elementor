<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Search_Form extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--blog--search--form';
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
		return esc_html__( 'WCF Search Form', 'animation-addons-for-elementor' );
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_icon() {
		return 'wcf eicon-search';
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
		return [ 'wcf-search-addon' ];
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
		return [ ];
	}

	public function get_keywords() {
		return ['search','form', 'search form' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Search Preset', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'preset',
			[
				'label'   => esc_html__( 'Preset', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'default'           => esc_html__( 'Default Theme', 'animation-addons-for-elementor' ),
					'style-classic'     => esc_html__( 'Classic', 'animation-addons-for-elementor' ),
					'style-dropdown'    => esc_html__( 'Dropdown', 'animation-addons-for-elementor' ),
					'style-full-screen' => esc_html__( 'Full Screen', 'animation-addons-for-elementor' ),
				],
				'default' => 'default',
			]
		);

		$this->end_controls_section();

		$this->register_search_controls();

		//search close
		$this->register_search_close();

		//default theme styles
		$this->register_default_theme_styles();

	}

	protected function register_search_controls() {
		$this->start_controls_section(
			'section_search',
			[
				'label'     => esc_html__( 'Search', 'animation-addons-for-elementor' ),
				'condition' => [ 'preset!' => 'default' ]
			]
		);

		$this->add_control(
			'search_position',
			[
				'label'       => esc_html__( 'Position', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options'     => [
					'left'  => [
						'title' => esc_html__( 'left', 'animation-addons-for-elementor' ),
						'icon'  => 'fa fa-angle-left',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'fa fa-angle-right',
					],
				],
				'default'     => 'left',
				'condition'   => [ 'preset' => 'style-dropdown' ]
			]
		);

		$this->add_control(
			'overlay_background_color',
			[
				'label' => esc_html__( 'Overlay Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .search--wrapper.style-full-screen .wcf-search-container' => 'background-color: {{VALUE}}',
				],
				'condition' => [
					'preset' => 'style-full-screen',
				],
			]
		);

		$this->add_responsive_control(
			'search_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-search-form' => 'min-width: {{SIZE}}{{UNIT}}',
				],
				'condition'  => [
					'preset' => [ 'style-full-screen', 'style-dropdown' ]
				],
			]
		);

		$this->add_responsive_control(
			'search_height',
			[
				'label'     => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range'     => [
					'px' => [
						'min'  => 1,
						'max'  => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form' => 'min-height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'placeholder',
			[
				'label'   => esc_html__( 'Placeholder', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Search...', 'animation-addons-for-elementor' ),
			]
		);

		//button
		$this->add_control(
			'heading_button_content',
			[
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_type',
			[
				'label'   => esc_html__( 'Type', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'icon',
				'options' => [
					'icon' => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
					'text' => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'     => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Search', 'animation-addons-for-elementor' ),
				'condition' => [
					'button_type' => 'text',
				],
			]
		);

		$this->add_control(
			'button_icon',
			[
				'label'       => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'button_type' => 'icon',
				],
				'default'     => [
					'value'   => 'fas fa-search',
					'library' => 'fa-solid',
				],
			]
		);

		$this->end_controls_section();

		//search style
        $this->register_search_style_controls();

        //toggle
        $this->register_search_toggle();

	}

	protected function register_search_style_controls() {

		//input style
		$this->start_controls_section(
			'section_input_style',
			[
				'label' => esc_html__( 'Input', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [ 'preset!' => 'default' ]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'input_typography',
				'selector' => '{{WRAPPER}} input[type="search"].wcf-search-form__input',
			]
		);

		$this->start_controls_tabs( 'tabs_input_colors' );

		$this->start_controls_tab(
			'tab_input_normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'input_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} input[type="search"].wcf-search-form__input' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'input_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'input_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => 'input_box_shadow',
				'selector'       => '{{WRAPPER}} .wcf-search-form',
				'fields_options' => [
					'box_shadow_type' => [
						'separator' => 'default',
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_input_focus',
			[
				'label' => esc_html__( 'Focus', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'input_text_color_focus',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form--focus input[type="search"].wcf-search-form__input' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'input_background_color_focus',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form.wcf-search-form--focus' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'input_border_color_focus',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form.wcf-search-form--focus' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'           => 'input_box_shadow_focus',
				'selector'       => '{{WRAPPER}} .wcf-search-form.wcf-search-form--focus',
				'fields_options' => [
					'box_shadow_type' => [
						'separator' => 'default',
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-search-form' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'default'    => [
					'size' => 3,
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-search-form' => 'border-radius: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();

		//button style
		$this->start_controls_section(
			'section_button_style',
			[
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'preset!' => 'default' ]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'button_typography',
				'selector'  => '{{WRAPPER}} .wcf-search-form__submit',
				'condition' => [
					'button_type' => 'text',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_button_colors' );

		$this->start_controls_tab(
			'tab_button_normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form__submit' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form__submit' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'button_text_color_hover',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form__submit:hover' => 'color: {{VALUE}}; fill: {{VALUE}}',
					'{{WRAPPER}} .wcf-search-form__submit:focus' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'button_background_color_hover',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form__submit:hover' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .wcf-search-form__submit:focus' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'icon_size',
			[
				'label'     => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form__submit' => 'font-size: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'button_type' => 'icon',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'search_button_width',
			[
				'label'     => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range'     => [
					'px' => [
						'min'  => 1,
						'max'  => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-search-form .wcf-search-form__submit' => 'min-width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_search_toggle() {
		//toggle
		$this->start_controls_section(
			'section_search_toggle',
			[
				'label'     => esc_html__( 'Toggle', 'animation-addons-for-elementor' ),
				'condition' => [
					'preset' => [ 'style-dropdown', 'style-full-screen' ],
				]
			]
		);

		$this->add_control(
			'toggle_open_icon',
			[
				'label'       => esc_html__( 'Open Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-search',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'toggle_close',
			[
				'label'       => esc_html__( 'Close Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-window-close',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_responsive_control(
			'toggle_size',
			[
				'label'     => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => [
					'size' => 33,
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-search-toggle' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();

		//toggle style
		$this->start_controls_section(
			'section_toggle_style',
			[
				'label'     => esc_html__( 'Toggle', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'preset' => [ 'style-dropdown', 'style-full-screen' ],
				]
			]
		);

		$this->start_controls_tabs( 'tabs_toggle_color' );

		$this->start_controls_tab(
			'tab_toggle_normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'toggle_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-toggle' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'toggle_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-toggle' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_toggle_hover',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'toggle_color_hover',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-toggle:hover' => 'color: {{VALUE}}; fill: {{VALUE}}',
					'{{WRAPPER}} .wcf-search-toggle:focus' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'toggle_background_color_hover',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-toggle:hover' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .wcf-search-toggle:focus' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'toggle_icon_size',
			[
				'label'     => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}} .wcf-search-toggle' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'toggle_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-search-toggle' => 'border-radius: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_search_close() {;

		$this->start_controls_section(
			'section_search_close_style',
			[
				'label'     => esc_html__( 'Search Close', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'preset' => [ 'style-full-screen' ],
				]
			]
		);

		$this->start_controls_tabs( 'tabs_search_close' );

		$this->start_controls_tab(
			'tab_search_close_normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'search_close_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .style-full-screen .toggle--close' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'search_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .style-full-screen .toggle--close' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_search_close_hover',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'search_close_color_hover',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .style-full-screen .toggle--close:hover' => 'color: {{VALUE}}; fill: {{VALUE}}',
					'{{WRAPPER}} .style-full-screen .toggle--close:focus' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'search_close_background_color_hover',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .style-full-screen .toggle--close:hover' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .style-full-screen .toggle--close:focus' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'close_hover_border_color',
			[
				'label' => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'close_border_border!' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .style-full-screen .toggle--close:hover, {{WRAPPER}} .style-full-screen .toggle--close:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'search_close_size',
			[
				'label'     => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}} .style-full-screen .toggle--close' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'search_close_icon_size',
			[
				'label'     => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}} .style-full-screen .toggle--close' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'close_border',
				'selector' => '{{WRAPPER}} .style-full-screen .toggle--close',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'search_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .style-full-screen .toggle--close' => 'border-radius: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_default_theme_styles() {

		$author = [ 'Crowdyflow', 'CrowdyTheme' ];
		if ( ! in_array( wp_get_theme()->get( 'Author' ), $author ) ) {
			return;
		}

		//input style
		$this->start_controls_section(
			'section_style',
			[
				'label'     => esc_html__( 'Input', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'preset' => 'default' ]
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} input' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} input',
			]
		);

		$this->add_control(
			'placeholder_color',
			[
				'label'     => esc_html__( 'Placeholder Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} input::placeholder'               => 'color: {{VALUE}};',
					'{{WRAPPER}} input::-webkit-input-placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} input::-moz-placeholder'          => 'color: {{VALUE}};',
					'{{WRAPPER}} input:-ms-input-placeholder'      => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_height',
			[
				'label'   => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => [
					'px' => [
						'max' => 150,
					],
				],
				'default' => [
					'size' => 55,
				],

				'selectors' => [
					'{{WRAPPER}} input' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [
						'max' => 150,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} input' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'contactform_input_box_background',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} input' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'contactform_input_box_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} input',
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} input' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} input[type*="text"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();

		//search icon
		$this->start_controls_section(
			'section_icon_style',
			[
				'label'     => esc_html__( 'Search Icon / Text', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'preset' => 'default' ]
			]
		);

		$this->add_control(
			'search_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} button'   => 'color: {{VALUE}};',
					'{{WRAPPER}} button i' => 'color: {{VALUE}};',
					'{{WRAPPER}} svg'      => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'search_typography',
				'selector' => '{{WRAPPER}} button',
			]
		);

		$this->add_responsive_control(
			'contactform_icon_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',

				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_icon_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'contactform_text_box_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} button',
			]
		);

		$this->add_responsive_control(
			'contactform_text_box_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} button' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'contactform_button_box_background',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_width',
			[
				'label'      => esc_html__( 'Button Width', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} button' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button__align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
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
				'default'   => 'left',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} button' => 'text-align: {{VALUE}};',
				],
				'condition' => [
					'preset' => [ 'style2' ]
				]
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute(
			'form',
			[
				'class' => 'wcf-search-form',
				'action' => home_url(),
				'method' => 'get',
				'role' => 'search',
			]
		);

		$this->add_render_attribute(
			'label',
			[
				'class' => 'elementor-screen-only',
				'for' => 'wcf-search-form-' . $this->get_id(),
			]
		);

		$this->add_render_attribute(
			'input',
			[
				'id' => 'wcf-search-form-' . $this->get_id(),
				'placeholder' => $settings['placeholder'],
				'class' => 'wcf-search-form__input',
				'type' => 'search',
				'name' => 's',
				'value' => get_search_query(),
			]
		);

		$this->add_render_attribute(
			'submit',
			[
				'id'         => 'wcf-search-form-' . $this->get_id(),
				'class'      => 'wcf-search-form__submit',
				'type'       => 'submit',
				'aria-label' => esc_html__( 'Search', 'animation-addons-for-elementor' ),
			]
		);

		?>
        <style>.elementor-widget-wcf--blog--search--form svg{height:1em;width:1em}.elementor-widget-wcf--blog--search--form .wcf-search-form button,.elementor-widget-wcf--blog--search--form .wcf-search-form input[type=search]{margin:0;border:0;outline:0;display:inline-block;vertical-align:middle;white-space:normal;background:0 0;line-height:1;min-width:0;font-size:15px;-webkit-appearance:none;-moz-appearance:none}.elementor-widget-wcf--blog--search--form .wcf-search-form__input{flex-basis:100%;padding:10px 20px}.elementor-widget-wcf--blog--search--form .wcf-search-form{display:flex;background:#f1f2f3;border-radius:3px;overflow:hidden;border:0 solid transparent;min-height:50px}.elementor-widget-wcf--blog--search--form .wcf-search-form .wcf-search-form__submit{background-color:#54595f;color:#fff;fill:#fff;min-width:60px;transition:.3s}.elementor-widget-wcf--blog--search--form .wcf-search-form button:focus,.elementor-widget-wcf--blog--search--form .wcf-search-form input[type=search]:focus{outline:0;color:inherit}.elementor-widget-wcf--blog--search--form .wcf-search-form__input::-moz-placeholder{color:inherit;font-family:inherit;opacity:.6}.elementor-widget-wcf--blog--search--form .wcf-search-form__input::placeholder{color:inherit;font-family:inherit;opacity:.6}.elementor-widget-wcf--blog--search--form .toggle--close,.elementor-widget-wcf--blog--search--form .toggle--open{line-height:1;cursor:pointer;flex:1;display:flex;align-items:center;justify-content:center}.elementor-widget-wcf--blog--search--form .toggle--close{display:none}.elementor-widget-wcf--blog--search--form .wcf-search-toggle{position:relative;display:inline-flex;justify-content:center;vertical-align:middle;background-color:rgba(0,0,0,.05);border:0 solid #33373d;border-radius:3px;transition:.3s}.elementor-widget-wcf--blog--search--form .search--wrapper.style-dropdown .wcf-search-container{position:absolute;width:300px;top:100%;opacity:0;z-index:99;visibility:hidden;transition:all .5s;transform:translateY(20px)}.elementor-widget-wcf--blog--search--form .search--wrapper.style-dropdown .wcf-search-container.search-position-right{right:0}.elementor-widget-wcf--blog--search--form .search--wrapper.style-dropdown .wcf-search-container.search-position-left{left:0}.elementor-widget-wcf--blog--search--form .search--wrapper.style-dropdown.search-visible .toggle--open{display:none}.elementor-widget-wcf--blog--search--form .search--wrapper.style-dropdown.search-visible .toggle--close{display:flex}.elementor-widget-wcf--blog--search--form .search--wrapper.style-dropdown.search-visible .wcf-search-container{opacity:1;visibility:visible;transform:translateY(0)}.elementor-widget-wcf--blog--search--form .search--wrapper.style-full-screen .wcf-search-container{position:fixed;width:100%;height:100vh;transform:scale(0);background:#ddd;top:0;left:0;z-index:99;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:all .5s}.elementor-widget-wcf--blog--search--form .search--wrapper.style-full-screen .toggle--close{display:flex;cursor:pointer;position:absolute;right:30px;top:30px;transition:.3s}.elementor-widget-wcf--blog--search--form .search--wrapper.style-full-screen .wcf-search-form{width:300px}.elementor-widget-wcf--blog--search--form .search--wrapper.style-full-screen.search-visible .wcf-search-container{opacity:1;visibility:visible;transform:scale(1)}</style>
        <?php

		if ( 'default' === $settings['preset'] ) {
			echo get_search_form();
		}

		if ( 'style-classic' === $settings['preset'] ) {
			$this->render_search_preset_one( $settings );
		}

		if ( 'style-dropdown' === $settings['preset'] ) {
			$this->render_search_preset_two( $settings );
		}

		if ( 'style-full-screen' === $settings['preset'] ) {
			$this->render_search_preset_three( $settings );
		}
	}

	protected function render_search_preset_one( $settings ) {
		?>
        <div class="search--wrapper <?php echo esc_attr( $settings['preset'] ); ?>">
	        <?php $this->render_search_form( $settings ); ?>
        </div>
		<?php
	}

	protected function render_search_preset_two( $settings ) {
		?>
        <div class="search--wrapper <?php echo esc_attr( $settings['preset'] ); ?>">
            <div class="wcf-search-toggle" role="button" tabindex="0">
                <div class="toggle--open">
					<?php Icons_Manager::render_icon( $settings['toggle_open_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                    <span class="elementor-screen-only"><?php esc_html_e( 'Search', 'animation-addons-for-elementor' ); ?></span>
                </div>
                <div class="toggle--close">
					<?php Icons_Manager::render_icon( $settings['toggle_close'], [ 'aria-hidden' => 'true' ] ); ?>
                    <span class="elementor-screen-only"><?php esc_html_e( 'Close this search box', 'animation-addons-for-elementor' ); ?></span>
                </div>
            </div>
            <div class="wcf-search-container search-position-<?php echo esc_attr( $settings['search_position'] ) ?>">
	            <?php $this->render_search_form( $settings ); ?>
            </div>
        </div>
		<?php
	}

	protected function render_search_preset_three( $settings ) {
		?>
        <div class="search--wrapper <?php echo esc_attr( $settings['preset'] ); ?>">
            <div class="wcf-search-toggle" role="button" tabindex="0">
                <div class="toggle--open">
					<?php Icons_Manager::render_icon( $settings['toggle_open_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                    <span class="elementor-screen-only"><?php esc_html_e( 'Search', 'animation-addons-for-elementor' ); ?></span>
                </div>
            </div>
            <div class="wcf-search-container">
                <div class="toggle--close" role="button" tabindex="0">
		            <?php Icons_Manager::render_icon( $settings['toggle_close'], [ 'aria-hidden' => 'true' ] ); ?>
                    <span class="elementor-screen-only"><?php esc_html_e( 'Close this search box', 'animation-addons-for-elementor' ); ?></span>
                </div>
                <?php $this->render_search_form( $settings ); ?>
            </div>
        </div>
		<?php
	}

	protected function render_search_form( $settings ) {
		?>
        <form <?php $this->print_render_attribute_string( 'form' ); ?>>
            <label <?php $this->print_render_attribute_string( 'label' ); ?>>
				<?php esc_html_e( 'Search', 'animation-addons-for-elementor' ); ?>
            </label>
            <input <?php $this->print_render_attribute_string( 'input' ); ?>>
            <button <?php $this->print_render_attribute_string( 'submit' ); ?>>
				<?php if ( 'icon' === $settings['button_type'] ) : ?>
					<?php Icons_Manager::render_icon( $settings['button_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                    <span class="elementor-screen-only"><?php esc_html_e( 'Search', 'animation-addons-for-elementor' ); ?></span>
				<?php elseif ( ! empty( $settings['button_text'] ) ) : ?>
					<?php $this->print_unescaped_setting( 'button_text' ); ?>
				<?php endif; ?>
            </button>
        </form>
		<?php
	}
}
