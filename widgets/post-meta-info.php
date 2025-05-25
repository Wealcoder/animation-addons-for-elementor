<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Post_Meta_Info extends Widget_Base {

	public function get_name() {
		return 'wcf--blog--post--meta-info';
	}

	public function get_title() {
		return esc_html__( 'Post Meta', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-meta-data';
	}

	public function get_categories() {
		return [ 'wcf-single-addon' ];
	}

	public function get_keywords() {
		return [ 'meta data', 'post meta' ];
	}

	public function get_style_depends() {
		return [ 'wcf--button', 'wcf--meta-info' ];
	}

	protected function register_controls() {
		// Layout
		$this->start_controls_section(
			'section_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'layout_style',
			[
				'label'   => esc_html__( 'Layout Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__( 'One', 'animation-addons-for-elementor' ),
					'2' => esc_html__( 'Two', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_responsive_control(
			'layout_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'   => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => '',
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'share_separator',
			[
				'label'   => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'no',
				'options' => [
					'yes' => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'no'  => esc_html__( 'No', 'animation-addons-for-elementor' ),
				]
			]
		);

		$this->add_control(
			'share_separator_icons',
			[
				'label'     => esc_html__( 'Separator Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::ICONS,
				'condition' => [ 'share_separator' => [ 'yes' ] ],
				'default'   => [
					'value'   => 'fa-solid fa-share-nodes',
					'library' => 'fa-solid',
				]
			]
		);

		$this->end_controls_section();

		// Content
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'list_title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'List Title', 'animation-addons-for-elementor' ),
				'label_block' => true,
			]
		);

		$meta_types = [
			'category'     => esc_html__( 'Category', 'animation-addons-for-elementor' ),
			'date'         => esc_html__( 'Date', 'animation-addons-for-elementor' ),
			'view'         => esc_html__( 'View', 'animation-addons-for-elementor' ),
			'author'       => esc_html__( 'Author', 'animation-addons-for-elementor' ),
			'reading_time' => esc_html__( 'Reading Time', 'animation-addons-for-elementor' ),
			'comment'      => esc_html__( 'Comment', 'animation-addons-for-elementor' ),
			'time-ago'     => esc_html__( 'Post Time Ago', 'animation-addons-for-elementor' ),
		];

		$repeater->add_control(
			'list_type',
			[
				'label'       => esc_html__( 'Meta', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $meta_types,
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'list_icon',
			[
				'label' => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'  => Controls_Manager::ICONS,
			]
		);

		$repeater->add_control(
			'meta_separator',
			[
				'label'       => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '|', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Enter your separator', 'animation-addons-for-elementor' ),
			]
		);

		$repeater->add_control(
			'multiple_category',
			[
				'label'        => esc_html__( 'Multiple Category', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'list_type' => [ 'category' ]
				]
			]
		);

		$repeater->add_responsive_control(
			'category_limit',
			[
				'label'     => esc_html__( 'Category Limit', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 10,
				'step'      => 1,
				'condition' => [
					'list_type'         => [ 'category' ],
					'multiple_category' => 'yes',
				]
			]
		);

		$this->add_control(
			'list',
			[
				'label'       => esc_html__( 'Social List', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ list_title }}}',
			]
		);

		$this->end_controls_section();

		// Style Tab
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
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list li, {{WRAPPER}} .wcf--meta-list li a' => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--meta-date, {{WRAPPER}} .wcf--meta-view'         => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--meta-list li svg'                               => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'text_typo',
				'selector' => '{{WRAPPER}} .wcf--meta-list li, {{WRAPPER}} .wcf--meta-list li a, {{WRAPPER}} .wcf--meta-date, {{WRAPPER}} .wcf--meta-view',
			]
		);

		// Label Style
		$this->add_control(
			'label_color',
			[
				'label'     => esc_html__( 'Label Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list li .label'     => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--meta-list li .label svg' => 'fill: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'label_typo',
				'selector'  => '{{WRAPPER}} .wcf--meta-list li .label',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		// Gap
		$this->add_responsive_control(
			'meta_col_gap',
			[
				'label'      => esc_html__( 'Column Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-list' => 'column-gap: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'meta_row_gap',
			[
				'label'      => esc_html__( 'Row Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-list' => 'row-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'meta_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-list' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();

		// Separator Style
		$this->start_controls_section(
			'separator_style',
			[
				'label' => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'separator_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list.style-2 > li::after' => 'background-color: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'separator_width',
			[
				'label'      => esc_html__( 'width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 20,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-list.style-2 > li::after' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'separator_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-list.style-2 > li::after' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'separator_position_2',
			[
				'label'      => esc_html__( 'Position', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-list.style-2 > li::after' => 'inset-inline-end: -{{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'separator_position',
			[
				'label'      => esc_html__( 'Position', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-separator::after' => 'margin-inline-start: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '1',
				],
			]
		);

		$this->add_responsive_control(
			'seperator_size_info',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],
				'selectors'  => [
					'{{WRAPPER}} span.wcf_separator_icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'default'    => [
					'unit' => 'px',
					'size' => 16,
				],
			]
		);

		$this->add_control(
			'seperator_icon_color',
			[
				'label'     => esc_html__( 'Icon Color', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} span.wcf_separator_icon' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();

		// Category Styles
		$this->category_styles();

		// Author Styles
		$this->author_styles();

		// Date Styles
		$this->date_styles();

		// View Styles
		$this->view_count_styles();

		// Comment Styles
		$this->comment_styles();

		// Post Time Ago
		$this->style_post_time_ago();
	}

	// Category Styles Control
	protected function category_styles() {
		$this->start_controls_section(
			'category_styles_section',
			[
				'label' => esc_html__( 'Category', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'category_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-category' => 'align-items: {{VALUE}}; text-align: {{VALUE}};',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'category_col_gap',
			[
				'label'      => esc_html__( 'Column Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--category-list' => 'column-gap: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'category_row_gap',
			[
				'label'      => esc_html__( 'Row Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--category-list' => 'row-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'category_separator_position',
			[
				'label'      => esc_html__( 'Separator Position', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-separator::after' => 'margin-inline-start: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'after',
			]
		);

		$this->add_control(
			'category_hover_list',
			[
				'label'   => esc_html__( 'Hover Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'hover-none',
				'options' => [
					'hover-none'      => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'hover-divide'    => esc_html__( 'Divided', 'animation-addons-for-elementor' ),
					'hover-cross'     => esc_html__( 'Cross', 'animation-addons-for-elementor' ),
					'hover-cropping'  => esc_html__( 'Cropping', 'animation-addons-for-elementor' ),
					'rollover-top'    => esc_html__( 'Rollover Top', 'animation-addons-for-elementor' ),
					'rollover-left'   => esc_html__( 'Rollover Left', 'animation-addons-for-elementor' ),
					'parallal-border' => esc_html__( 'Parallel Border', 'animation-addons-for-elementor' ),
					'rollover-cross'  => esc_html__( 'Rollover Cross', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'category_typo',
				'selector' => '{{WRAPPER}} .wcf--meta-list .wcf--meta-category a',
			]
		);

		$this->add_control(
			'category_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-category a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '1',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'category_border',
				'selector'  => '{{WRAPPER}} a, {{WRAPPER}} a.btn-parallal-border:before, {{WRAPPER}} a.btn-parallal-border:after, {{WRAPPER}} a.btn-rollover-cross:before, {{WRAPPER}} a.btn-rollover-cross:after',
				'condition' => [
					'layout_style' => '1',
				],
			]
		);

		$this->add_control(
			'category_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-category a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '1',
				],
			]
		);

		$this->add_control(
			'category_transition',
			[
				'label'      => esc_html__( 'Transition', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => .1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-category a' => 'transition: all {{SIZE}}s;',
				],
			]
		);

		$this->start_controls_tabs(
			'category_tabs'
		);

		$this->start_controls_tab(
			'category_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'category_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-category a, {{WRAPPER}} .wcf--meta-list .wcf--meta-category' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'category_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector'  => '{{WRAPPER}} a:not(.wcf-btn-ellipse), {{WRAPPER}} a.wcf-btn-mask:after, {{WRAPPER}} a.wcf-btn-ellipse:before',
				'condition' => [
					'layout_style' => '1',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'category_shadow',
				'selector'  => '{{WRAPPER}} .wcf--meta-category a',
				'condition' => [
					'layout_style' => '1',
				],
			]
		);

		$this->end_controls_tab();


		$this->start_controls_tab(
			'category_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'category_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-category a:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'category_hover_bg',
				'types'     => [ 'classic', 'gradient' ],
				'selector'  => '{{WRAPPER}} a:not(.btn-item, .btn-parallal-border, .btn-rollover-cross, .wcf-btn-ellipse):after, {{WRAPPER}} .btn-hover-bgchange span, {{WRAPPER}} .btn-rollover-cross:hover, {{WRAPPER}} .btn-parallal-border:hover, {{WRAPPER}} a.wcf-btn-ellipse:hover:before,{{WRAPPER}} a.btn-hover-none:hover',
				'condition' => [
					'layout_style' => '1',
				],
			]
		);

		$this->add_control(
			'category_hover_border',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-category a:hover, {{WRAPPER}} a:hover, {{WRAPPER}} a:focus, {{WRAPPER}} a:hover.btn-parallal-border:before, {{WRAPPER}} a:hover.btn-parallal-border:after, {{WRAPPER}} a:hover.btn-rollover-cross:before, {{WRAPPER}} a:hover.btn-rollover-cross:after, {{WRAPPER}} a.btn-hover-none:hover' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '1',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'category_hover_shadow',
				'selector'  => '{{WRAPPER}} .wcf--meta-category a:hover',
				'condition' => [
					'layout_style' => '1',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		// Label
		$this->add_control(
			'category_label_heading',
			[
				'label'     => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'category_label_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--category-title' => 'color: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'category_label_typo',
				'selector'  => '{{WRAPPER}} .wcf--category-title',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'category_label_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--category-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'category_icon_heading',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'category_icon',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--category-wrap i, {{WRAPPER}} li.wcf--category-wrap svg' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf--category-title i, {{WRAPPER}} .wcf--category-title svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'category_icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--category-wrap i, {{WRAPPER}} .wcf--category-title i'     => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--category-wrap svg, {{WRAPPER}} .wcf--category-title svg' => 'fill: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'category_icon_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 150,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--category-wrap i, {{WRAPPER}} .wcf--category-wrap svg'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf--category-title i, {{WRAPPER}} .wcf--category-title svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// Author Styles Control
	protected function author_styles() {
		$this->start_controls_section(
			'author_styles_section',
			[
				'label' => esc_html__( 'Author', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'author_typo',
				'selector' => '{{WRAPPER}} .wcf--meta-list .wcf--meta-author a',
			]
		);

		$this->add_control(
			'author_transition',
			[
				'label'      => esc_html__( 'Transition', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => .1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-author a' => 'transition: all {{SIZE}}s;',
				],
			]
		);

		$this->start_controls_tabs(
			'author_tabs'
		);

		$this->start_controls_tab(
			'author_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'author_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-author a, {{WRAPPER}} .wcf--meta-list .wcf--meta-author' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();


		$this->start_controls_tab(
			'author_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'author_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-author a:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		// Label
		$this->add_control(
			'author_label_heading',
			[
				'label'     => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'author_label_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--author-title' => 'color: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'author_label_typo',
				'selector'  => '{{WRAPPER}} .wcf--author-title',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'author_label_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--author-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'author_icon_heading',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'author_icon',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-author i, {{WRAPPER}} .wcf--meta-author svg' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf-author-img img'                                    => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'author_icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-author i'   => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--meta-author svg' => 'fill: {{VALUE}}',

				],
				'condition' => [
					'layout_style' => '1',
				],
			]
		);

		$this->add_responsive_control(
			'author_icon_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 150,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-author i, {{WRAPPER}} .wcf--meta-author svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf-author-img'                                        => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'author_img_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-author-img img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->end_controls_section();
	}

	// Date Styles Control
	protected function date_styles() {
		$this->start_controls_section(
			'date_styles_section',
			[
				'label' => esc_html__( 'Date', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'date_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf--date-wrap' => 'align-items: {{VALUE}}; text-align: {{VALUE}};',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'date_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-date' => 'color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'date_typo',
				'selector' => '{{WRAPPER}} .wcf--meta-list .wcf--meta-date',
			]
		);

		$this->add_control(
			'date_label_heading',
			[
				'label'     => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'date_label_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--date-title' => 'color: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'date_label_typo',
				'selector'  => '{{WRAPPER}} .wcf--date-title',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'date_label_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--date-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'date_icon_heading',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'date_icon',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-date i, {{WRAPPER}} .wcf--meta-date svg'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf--date-title i, {{WRAPPER}} .wcf--date-title svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'date_icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-date i, {{WRAPPER}} .wcf--meta-list .wcf--date-title i'     => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-date svg, {{WRAPPER}} .wcf--meta-list .wcf--date-title svg' => 'fill: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'date_icon_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 150,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-date i, {{WRAPPER}} .wcf--meta-date svg'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf--date-title i, {{WRAPPER}} .wcf--date-title svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// Post Time Ago Styles Control
	protected function style_post_time_ago() {
		$this->start_controls_section(
			'style_p_time',
			[
				'label' => esc_html__( 'Post Time Ago', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'ptime_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .post-time-ago-wrap' => 'align-items: {{VALUE}}; text-align: {{VALUE}};',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'ptime_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .post-time-ago' => 'color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ptime_typo',
				'selector' => '{{WRAPPER}} .wcf--meta-list .post-time-ago',
			]
		);

		$this->add_control(
			'ptime_label_heading',
			[
				'label'     => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'ptime_label_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .time-ago-title' => 'color: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'ptime_label_typo',
				'selector'  => '{{WRAPPER}} .time-ago-title',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'ptime_label_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .time-ago-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'ptime_icon_heading',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'ptime_icon',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .post-time-ago i, {{WRAPPER}} .post-time-ago svg'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .time-ago-title i, {{WRAPPER}} .time-ago-title svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ptime_icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .post-time-ago i, {{WRAPPER}} .wcf--meta-list .time-ago-title i'     => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--meta-list .post-time-ago svg, {{WRAPPER}} .wcf--meta-list .time-ago-title svg' => 'fill: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'ptime_icon_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 150,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .post-time-ago i, {{WRAPPER}} .post-time-ago svg'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .time-ago-title i, {{WRAPPER}} .time-ago-title svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// View Count Styles Control
	protected function view_count_styles() {
		$this->start_controls_section(
			'view_styles_section',
			[
				'label' => esc_html__( 'View Count', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'view_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf--view-wrap' => 'align-items: {{VALUE}}; text-align: {{VALUE}};',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'view_direction',
			[
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'row'            => [
						'title' => esc_html__( 'Row', 'animation-addons-for-elementor' ),
						'icon'  => ' eicon-arrow-right',
					],
					'column'         => [
						'title' => esc_html__( 'Column', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
					'row-reverse'    => [
						'title' => esc_html__( 'Row Reverse', 'animation-addons-for-elementor' ),
						'icon'  => ' eicon-arrow-left',
					],
					'column-reverse' => [
						'title' => esc_html__( 'Column Reverse', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-up',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-view' => 'flex-direction: {{VALUE}};',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'view_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-view' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		// Typo
		$this->add_control(
			'view_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-view' => 'color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'view_typo',
				'selector' => '{{WRAPPER}} .wcf--meta-list .wcf--meta-view',
			]
		);

		$this->add_control(
			'view_label_heading',
			[
				'label'     => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'view_label_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--view-title' => 'color: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'view_label_typo',
				'selector'  => '{{WRAPPER}} .wcf--view-title',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'view_label_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--view-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'view_icon_heading',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'view_icon',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-view i, {{WRAPPER}} .wcf--meta-view svg'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf--view-title i, {{WRAPPER}} .wcf--view-title svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'view_icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-view i, {{WRAPPER}} .wcf--meta-list .wcf--view-title i'     => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-view svg, {{WRAPPER}} .wcf--meta-list .wcf--view-title svg' => 'fill: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'view_icon_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 150,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-view i, {{WRAPPER}} .wcf--meta-view svg'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf--view-title i, {{WRAPPER}} .wcf--view-title svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// Comment Styles Control
	protected function comment_styles() {
		$this->start_controls_section(
			'comment_styles_section',
			[
				'label' => esc_html__( 'Comment', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'comment_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf--comment-wrap' => 'align-items: {{VALUE}}; text-align: {{VALUE}};',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		// Typo
		$this->add_control(
			'comment_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-list .wcf--meta-comment' => 'color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'comment_typo',
				'selector' => '{{WRAPPER}} .wcf--meta-list .wcf--meta-comment',
			]
		);

		$this->add_control(
			'comment_label_heading',
			[
				'label'     => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'comment_label_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--comment-title' => 'color: {{VALUE}}',
				],
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'comment_label_typo',
				'selector'  => '{{WRAPPER}} .wcf--comment-title',
				'condition' => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_responsive_control(
			'comment_label_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--comment-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'layout_style' => '2',
				],
			]
		);

		$this->add_control(
			'comment_icon_heading',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'comment_icon',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-comment i, {{WRAPPER}} .wcf--meta-comment svg'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf--comment-title i, {{WRAPPER}} .wcf--comment-title svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'comment_icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--meta-comment i, {{WRAPPER}} .wcf--comment-title i'     => 'color: {{VALUE}}',
					'{{WRAPPER}} .wcf--meta-comment svg, {{WRAPPER}} .wcf--comment-title svg' => 'fill: {{VALUE}}',

				],
			]
		);

		$this->add_responsive_control(
			'comment_icon_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 150,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--meta-comment i, {{WRAPPER}} .wcf--meta-comment svg'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf--comment-title i, {{WRAPPER}} .wcf--comment-title svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


		$this->add_control(
			'comment_separator_heading',
			[
				'label'     => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'separator_icon',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} span.separator' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator_icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} span.separator' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'separator_icon_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 150,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} span.separator' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

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
		$settings  = $this->get_settings_for_display();
		$meta_list = $settings['list'];

		if ( empty( $meta_list ) ) {
			return;
		}

		$this->switch_post();

		?>
        <ul class="wcf--meta-list style-<?php echo esc_html( $settings['layout_style'] ); ?>">
			<?php
			foreach ( $meta_list as $meta ) {
				$this->render_date( $meta, $settings );
				$this->render_categories( $meta, $settings );
				$this->render_author( $meta, $settings );
				$this->render_view_count( $meta, $settings );
				$this->render_reading_time( $meta, $settings );
				$this->render_comments( $meta, $settings );
				$this->render_post_time_ago( $meta, $settings );
			}
			?>
        </ul>
		<?php

		Plugin::$instance->db->restore_current_post();
	}

	protected function render_date( $meta, $settings ) {
		if ( $meta['list_type'] == 'date' ) { ?>
			<?php if ( '1' == $settings['layout_style'] ): ?>
                <li class="wcf--meta-date wcf-separator"
                    data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
					<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					<?php echo get_the_date( get_option( 'date_format' ) ); ?>
                </li>
			<?php endif; ?>

			<?php if ( '2' == $settings['layout_style'] ): ?>
                <li class="wcf--date-wrap">
                    <div class="wcf--date-title label">
						<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
						<?php echo esc_html( $meta['list_title'] ); ?>
                    </div>
                    <div class="wcf--meta-date">
						<?php echo get_the_date( get_option( 'date_format' ) ); ?>
                    </div>
                </li>
			<?php endif; ?>
		<?php }
	}

	protected function render_categories( $meta, $settings ) {
		if ( $meta['list_type'] == 'category' ) {
			$cat = get_the_category();
			shuffle( $cat );
			?>

			<?php if ( '1' == $settings['layout_style'] ): ?>
				<?php if ( 'yes' === $meta['multiple_category'] ): ?>
                    <li class="wcf--category-wrap">
						<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                        <ul class="wcf--category-list">
							<?php foreach ( $cat as $key => $term ) { ?>
                                <li class="wcf--meta-category wcf-separator"
                                    data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
                                    <a class="wcf-btn-default btn-<?php echo esc_attr( $settings['category_hover_list'] ); ?>"
                                       href="<?php echo esc_url( get_category_link( $term->term_id ) ); ?>">
										<?php echo esc_html( get_cat_name( $term->term_id ) ); ?>
                                    </a>
                                </li>
								<?php
								if ( isset( $meta['category_limit'] ) && is_numeric( $meta['category_limit'] ) ) {
									if ( $meta['category_limit'] == $key + 1 ) {
										break;
									}
								}
							}
							?>
                        </ul>
                    </li>
				<?php else: ?>
					<?php if ( isset( $cat[0] ) ) { ?>
                        <li class="wcf--meta-category wcf-separator"
                            data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
							<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                            <a class="wcf-btn-default btn-<?php echo esc_attr( $settings['category_hover_list'] ); ?>"
                               href="<?php echo esc_url( get_category_link( $cat[0]->term_id ) ); ?>">
								<?php echo esc_html( get_cat_name( $cat[0]->term_id ) ); ?>
                            </a>
                        </li>
					<?php } ?>
				<?php
				endif;
				?>
			<?php endif; ?>

			<?php if ( '2' == $settings['layout_style'] ): ?>
				<?php if ( 'yes' === $meta['multiple_category'] ): ?>
                    <li class="wcf--category-wrap">
                        <div class="wcf--category-title label">
							<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
							<?php echo esc_html( $meta['list_title'] ); ?>
                        </div>
                        <ul class="wcf--category-list">
							<?php foreach ( $cat as $key => $term ) { ?>
                                <li class="wcf--meta-category wcf-separator"
                                    data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
                                    <a class="wcf-btn-default btn-<?php echo esc_attr( $settings['category_hover_list'] ); ?>"
                                       href="<?php echo esc_url( get_category_link( $term->term_id ) ); ?>">
										<?php echo esc_html( get_cat_name( $term->term_id ) ); ?>
                                    </a>
                                </li>
								<?php
								if ( isset( $meta['category_limit'] ) && is_numeric( $meta['category_limit'] ) ) {
									if ( $meta['category_limit'] == $key + 1 ) {
										break;
									}
								}
							} ?>
                        </ul>
                    </li>
				<?php else: ?>
					<?php if ( isset( $cat[0] ) ) { ?>
                        <li class="wcf--meta-category">
                            <div class="wcf--category-title label">
								<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
								<?php echo esc_html( $meta['list_title'] ); ?>
                            </div>
                            <a class="wcf-btn-default btn-<?php echo esc_attr( $settings['category_hover_list'] ); ?>"
                               href="<?php echo esc_url( get_category_link( $cat[0]->term_id ) ); ?>">
								<?php echo esc_html( get_cat_name( $cat[0]->term_id ) ); ?>
                            </a>
                        </li>
					<?php } ?>
				<?php
				endif;
				?>
			<?php endif; ?>
		<?php }
	}

	protected function render_author( $meta, $settings ) {
		global $post;
		$author_id = $post->post_author;

		if ( $meta['list_type'] == 'author' ) {
			$get_author = get_the_author_meta( 'display_name', $author_id );
			$avatar     = get_avatar( $author_id, 55 );
			$_posts_url = esc_url( get_author_posts_url( $author_id ) );
			?>
			<?php if ( '1' == $settings['layout_style'] ): ?>
                <li class="wcf--meta-author wcf-separator"
                    data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
					<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                    <a href="<?php echo esc_url( $_posts_url ); ?>"><?php echo esc_html( $get_author ); ?></a>
                </li>
			<?php endif; ?>

			<?php if ( '2' == $settings['layout_style'] ): ?>
                <li class="wcf--author-wrap">
                    <div class="wcf-author-img">
						<?php echo wp_kses_post( $avatar ); ?>
                    </div>
                    <div class="wcf--author-info">
                        <div class="wcf--author-title label">
							<?php echo esc_html( $meta['list_title'] ); ?>
                        </div>
                        <div class="wcf--meta-author">
                            <a href="<?php echo esc_url( $_posts_url ); ?>"><?php echo esc_html( $get_author ); ?></a>
                        </div>
                    </div>
                </li>
			<?php endif; ?>
		<?php }
	}

	protected function render_view_count( $meta, $settings ) {
		if ( $meta['list_type'] == 'view' ) { ?>
			<?php if ( '1' == $settings['layout_style'] ): ?>
                <li class="wcf--meta-view wcf-separator"
                    data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
					<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					<?php echo esc_html( get_post_meta( get_the_id(), 'wcf_post_views_count', true ) ); ?>&nbsp;
					<?php echo esc_html__( 'Views', 'animation-addons-for-elementor' ); ?>
                </li>
			<?php endif; ?>

			<?php if ( '2' == $settings['layout_style'] ): ?>
                <li class="wcf--view-wrap">
                    <div class="wcf--view-title label">
						<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
						<?php echo esc_html( $meta['list_title'] ); ?>
                    </div>
                    <div class="wcf--meta-view">
						<?php echo esc_html( get_post_meta( get_the_id(), 'wcf_post_views_count', true ) ); ?>&nbsp;
                        <span><?php echo esc_html__( 'Views', 'animation-addons-for-elementor' ); ?></span>
                    </div>
                </li>
			<?php endif; ?>
		<?php }
	}

	protected function render_reading_time( $meta, $settings ) {
		$time          = 0;
		$content       = get_the_content();
		$clean_content = esc_html( $content );
		$word_count    = str_word_count( $clean_content );
		$time          = ceil( $word_count / 200 );

		if ( $meta['list_type'] == 'reading_time' ) { ?>
			<?php if ( '1' == $settings['layout_style'] ): ?>
                <li class="wcf--meta-view wcf-separator"
                    data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
					<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					<?php echo esc_html( $time ); ?>
					<?php echo $time <= 1 ? esc_html__( 'minute read', 'animation-addons-for-elementor' ) : esc_html__( 'minutes read', 'animation-addons-for-elementor' ); ?>
                </li>
			<?php endif; ?>

			<?php if ( '2' == $settings['layout_style'] ): ?>
                <li class="wcf--view-wrap">
                    <div class="wcf--view-title label">
						<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
						<?php echo esc_html( $meta['list_title'] ); ?>
                    </div>
                    <div class="wcf--meta-view">
						<?php echo esc_html( $time ); ?>&nbsp;
                        <span><?php echo $time <= 1 ? esc_html__( 'minute read', 'animation-addons-for-elementor' ) : esc_html__( 'minutes read', 'animation-addons-for-elementor' ); ?></span>
                    </div>
                </li>
			<?php endif; ?>
		<?php }
	}

	protected function render_comments( $meta, $settings ) {
		if ( $meta['list_type'] == 'comment' ) { ?>
			<?php if ( '1' == $settings['layout_style'] ): ?>
                <li class="wcf--meta-comment wcf-separator"
                    data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
					<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>

                    <span class="separator"><?php Icons_Manager::render_icon( $settings['share_separator_icons'], [ 'aria-hidden' => 'true' ] ); ?></span>
					<?php comments_number(); ?>
                </li>
			<?php endif; ?>

			<?php if ( '2' == $settings['layout_style'] ): ?>
                <li class="wcf--comment-wrap">
                    <div class="wcf--comment-title label">
						<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
						<?php echo esc_html( $meta['list_title'] ); ?>
                    </div>
                    <div class="wcf--meta-comment">
						<?php comments_number(); ?>
                    </div>
                </li>
			<?php endif; ?>
		<?php }
	}

	protected function render_post_time_ago( $meta, $settings ) {
		if ( $meta['list_type'] == 'time-ago' ) {
			$posted_time     = get_the_date( 'c' );
			$current_time    = current_time( 'timestamp' );
			$time_difference = $current_time - strtotime( $posted_time );

			if ( $time_difference < MINUTE_IN_SECONDS ) {
				$seconds  = $time_difference;
				$time_ago = $seconds . ' second' . ( $seconds > 1 ? 's' : '' ) . ' ago';
			} elseif ( $time_difference < HOUR_IN_SECONDS ) {
				$minutes  = floor( $time_difference / MINUTE_IN_SECONDS );
				$time_ago = $minutes . ' minute' . ( $minutes > 1 ? 's' : '' ) . ' ago';
			} elseif ( $time_difference < DAY_IN_SECONDS ) {
				$hours    = floor( $time_difference / HOUR_IN_SECONDS );
				$time_ago = $hours . ' hour' . ( $hours > 1 ? 's' : '' ) . ' ago';
			} elseif ( $time_difference < WEEK_IN_SECONDS ) {
				$days     = floor( $time_difference / DAY_IN_SECONDS );
				$time_ago = $days . ' day' . ( $days > 1 ? 's' : '' ) . ' ago';
			} elseif ( $time_difference < ( 30 * DAY_IN_SECONDS ) ) {
				$weeks    = floor( $time_difference / WEEK_IN_SECONDS );
				$time_ago = $weeks . ' week' . ( $weeks > 1 ? 's' : '' ) . ' ago';
			} elseif ( $time_difference < ( 365 * DAY_IN_SECONDS ) ) {
				$months   = floor( $time_difference / ( 30 * DAY_IN_SECONDS ) );
				$time_ago = $months . ' month' . ( $months > 1 ? 's' : '' ) . ' ago';
			} else {
				$years    = floor( $time_difference / ( 365 * DAY_IN_SECONDS ) );
				$time_ago = $years . ' year' . ( $years > 1 ? 's' : '' ) . ' ago';
			}
			?>

			<?php if ( '1' == $settings['layout_style'] ): ?>
                <li class="post-time-ago wcf-separator"
                    data-separator="<?php echo esc_attr( $meta['meta_separator'] ); ?>">
					<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					<?php echo esc_html( $meta['list_title'] ); ?>
					<?php echo esc_html( $time_ago ); ?>
                </li>
			<?php endif; ?>

			<?php if ( '2' == $settings['layout_style'] ): ?>
                <li class="post-time-ago-wrap">
                    <div class="time-ago-title label">
						<?php Icons_Manager::render_icon( $meta['list_icon'], [ 'aria-hidden' => 'true' ] ); ?>
						<?php echo esc_html( $meta['list_title'] ); ?>
                    </div>
                    <div class="post-time-ago">
						<?php echo esc_html( $time_ago ); ?>
                    </div>
                </li>
			<?php endif; ?>
			<?php
		}
	}
}
