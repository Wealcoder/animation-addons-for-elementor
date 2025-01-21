<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Control_Media;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Timeline
 *
 * Elementor widget for timeline.
 *
 * @since 1.0.0
 */
class Timeline extends Widget_Base {


	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--timeline';
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
		return esc_html__( 'Timeline', 'animation-addons-for-elementor' );
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
		return 'wcf  eicon-time-line';
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

	public function get_style_depends() {
		return [ 'wcf--timeline' ];
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
		//layout
		$this->start_controls_section(
			'section_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'element_list',
			[
				'label'   => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__( 'One', 'animation-addons-for-elementor' ),
					'2' => esc_html__( 'Two', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'   => esc_html__( 'Title HTML Tag', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
				'default' => 'h3',
			]
		);

		$this->add_control(
			'date_icon',
			[
				'label'            => esc_html__( 'Date Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'd_icon',
				'default'          => [
					'value'   => 'fas fa-calendar-alt',
					'library' => 'fa-solid',
				],
				'skin'             => 'inline',
				'label_block'      => false,
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image_size',
				'default'   => 'full',
				'separator' => 'none',
			]
		);

		$this->add_responsive_control(
			'image_position',
			[
				'label'          => esc_html__( 'Image Position', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => 'column',
				'tablet_default' => 'column',
				'mobile_default' => 'column',
				'options'        => [
					'column' => esc_html__( 'Top', 'animation-addons-for-elementor' ),
					'row'    => esc_html__( 'Aside', 'animation-addons-for-elementor' ),
				],
				'prefix_class'   => 'wcf-image-position-',
				'selectors'      => [
					'{{WRAPPER}} .content-wrap' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'align',
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
				'default'   => '',
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'show_indicator',
			[
				'label'        => esc_html__( 'Indicator', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		//timeline
		$this->start_controls_section(
			'section_timeline',
			[
				'label' => esc_html__( 'Timeline', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'step_type',
			[
				'label'   => esc_html__( 'Step Type', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'icon',
				'options' => [
					'icon' => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
					'text' => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				],
			]
		);

		$repeater->add_control(
			'step_icon',
			[
				'label'            => esc_html__( 'Step Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'default'          => [
					'value'   => 'fas fa-dot-circle',
					'library' => 'fa-solid',
				],
				'skin'             => 'inline',
				'label_block'      => false,
				'condition'        => [ 'step_type' => 'icon' ],
			]
		);

		$repeater->add_control(
			'step_text',
			[
				'label'     => esc_html__( 'Step Text', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [
					'active' => true,
				],
				'default'   => '01',
				'condition' => [ 'step_type' => 'text' ],
			]
		);

		$repeater->add_control(
			'timeline_image',
			[
				'label'     => esc_html__( 'Choose Image', 'animation-addons-for-elementor' ),
				'separator' => 'before',
				'type'      => Controls_Manager::MEDIA,
				'dynamic'   => [
					'active' => true,
				],
				'default'   => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'timeline_date',
			[
				'label'   => esc_html__( 'Date', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => 'Jan 01, 2021',
			]
		);

		$repeater->add_control(
			'timeline_title',
			[
				'label'   => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => 'Journey Started at New York',
			]
		);

		$repeater->add_control(
			'timeline_sub',
			[
				'label'   => esc_html__( 'Sub Title', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => 'Designer',
			]
		);

		$repeater->add_control(
			'timeline_desc',
			[
				'label'   => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXTAREA,
				'dynamic' => [
					'active' => true,
				],
				'rows'    => '10',
				'default' => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'animation-addons-for-elementor' ),
			]
		);

		$repeater->add_control(
			'link',
			[
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [
					'active' => true,
				],
				'placeholder' => esc_html__( 'https://your-link.com', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'timelines',
			[
				'label'       => esc_html__( 'Timelines', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [ [], [], [] ],
				'title_field' => '{{{ timeline_title }}}',
			]
		);

		$this->end_controls_section();

		$this->timeline_style_controls();

		$this->content_style_controls();

		$this->step_style_controls();
	}

	protected function timeline_style_controls() {
		$this->start_controls_section(
			'section_timeline_style',
			[
				'label' => esc_html__( 'Timeline', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'content_wrap_space',
			[
				'label'          => esc_html__( 'Space', 'animation-addons-for-elementor' ),
				'description'    => esc_html__( 'Added space between step line and content', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px' ],
				'default'        => [
					'unit' => 'px',
					'size' => 60,
				],
				'mobile_default' => [
					'unit' => 'px',
					'size' => 30,
				],
				'range'          => [
					'px' => [
						'min' => 1,
						'max' => 300,
					],
				],
				'selectors'      => [
					'{{WRAPPER}} .wcf--timeline' => '--content-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_wrap_bottom_space',
			[
				'label'      => esc_html__( 'Content bottom space', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'    => [
					'unit' => 'px',
					'size' => 30,
				],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .timeline-item:not(:last-child) .content-wrap' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function content_style_controls() {
		$this->start_controls_section(
			'section_content_style',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'content_wrapper_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .content-wrap',
			]
		);

		$this->add_responsive_control(
			'content_wrapper_padding',
			[
				'label'      => esc_html__( 'Wrapper padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .content-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__( 'Content padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .content-wrap .content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


		$this->add_responsive_control(
			'content_wrapper_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .content-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		//image
		$this->add_control(
			'heading_image',
			[
				'label'     => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'image_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => 'px',
				],
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
					'%'  => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_height',
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
					'{{WRAPPER}} img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'object-fit',
			[
				'label'     => esc_html__( 'Object Fit', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'condition' => [
					'image_height[size]!' => '',
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
					'object-fit' => 'cover',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'image_border',
				'selector'  => '{{WRAPPER}} img',
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
					'{{WRAPPER}} img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_space',
			[
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'   => [
					'size' => 30,
				],
				'selectors' => [
					'{{WRAPPER}} .content-wrap' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		//date
		$this->add_control(
			'heading_date',
			[
				'label'     => esc_html__( 'Date', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'date_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .date' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'date_typography',
				'selector' => '{{WRAPPER}} .date',
			]
		);

		$this->add_responsive_control(
			'date_bottom_space',
			[
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'   => [
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}} .date' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		//title
		$this->add_control(
			'heading_title',
			[
				'label'     => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .title',
			]
		);

		$this->add_responsive_control(
			'title_bottom_space',
			[
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'   => [
					'size' => 5,
				],
				'selectors' => [
					'{{WRAPPER}} .title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		//subtitle
		$this->add_control(
			'heading_subtitle',
			[
				'label'     => esc_html__( 'Subtitle', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'subtitle_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .subtitle' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'subtitle_typography',
				'selector' => '{{WRAPPER}} .subtitle',
			]
		);

		$this->add_responsive_control(
			'subtitle_bottom_space',
			[
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'   => [
					'size' => 30,
				],
				'selectors' => [
					'{{WRAPPER}} .subtitle' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		//Description
		$this->add_control(
			'heading_description',
			[
				'label'     => esc_html__( 'Description', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'description_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .description',
			]
		);

		$this->end_controls_section();
	}

	protected function step_style_controls() {
		$this->start_controls_section(
			'section_step_style',
			[
				'label' => esc_html__( 'Step', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'step_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .step-box .icon' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .step-box .icon' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'step_typography',
				'exclude' => ['font_size', 'text_decoration', 'line_height'],
				'selector' => '{{WRAPPER}} .step-box .icon',
			]
		);

		$this->add_responsive_control(
			'step_size',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				// The `%' and `em` units are not supported as the widget implements icons differently then other icons.
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 6,
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--timeline' => '--icon-size: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'step_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				// The `%' and `em` units are not supported as the widget implements icons differently then other icons.
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--timeline' => '--icon-padding: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'step_border',
				'selector' => '{{WRAPPER}} .step-box .icon',
			]
		);

		$this->add_responsive_control(
			'step_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .step-box .icon' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'step_position_x',
			[
				'label'      => esc_html__( 'Step icon position-x', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .step-box .icon' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_step_line',
			[
				'label'     => esc_html__( 'Step Line', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'step_line_style',
			[
				'label'     => esc_html__( 'Line Style', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'dashed',
				'options'   => [
					''       => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'none'   => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'solid'  => esc_html__( 'Solid', 'animation-addons-for-elementor' ),
					'dashed' => esc_html__( 'Dashed', 'animation-addons-for-elementor' ),
					'dotted' => esc_html__( 'Dotted', 'animation-addons-for-elementor' ),
					'double' => esc_html__( 'Double', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .line' => 'border-left-style: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'step_line_thickness',
			[
				'label'      => esc_html__( 'line Thickness', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'condition'  => [ 'step_line_style!' => 'none' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .line' => 'border-left-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'step_line_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => [ 'step_line_style!' => 'none' ],
				'selectors' => [
					'{{WRAPPER}} .line' => 'border-left-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'heading_indicator',
			[
				'label'     => esc_html__( 'Indicator', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [ 'show_indicator' => 'yes' ]
			]
		);

		$this->add_control(
			'indicator_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => [ 'show_indicator' => 'yes' ],
				'selectors' => [
					'{{WRAPPER}} .indicator' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'indicator_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .indicator' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition' => [ 'show_indicator' => 'yes' ]
			]
		);

		$this->add_responsive_control(
			'indicator_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 50,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .indicator' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'show_indicator' => 'yes' ]
			]
		);

		$this->add_responsive_control(
			'indicator_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .indicator' => '--indicator-gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'show_indicator' => 'yes' ]
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

		if ( empty( $settings['timelines'] ) ) {
			return;
		}

		//wrapper class
		$this->add_render_attribute( 'wrapper', 'class', [ 'wcf--timeline', 'style-' . $settings['element_list'] ] );

		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php
			foreach ( $settings['timelines'] as $index => $item ) {
				$this->render_timeline( $settings, $item, $index );
			}
			?>
		</div>
		<?php
	}

	protected function render_timeline( $settings, $item, $index ) {
		?>
		<div class="timeline-item">
			<?php
			$this->render_timeline_step( $item, $index );
			$this->render_timeline_content( $settings, $item, $index );
			?>
		</div>
		<?php
	}

	protected function render_timeline_content( $settings, $item, $index ) {
		$link_key = 'link_' . $index;
		if ( ! empty( $item['link']['url'] ) ) {
			$this->add_link_attributes( $link_key, $item['link'] );
		}
		$migration_allowed = Icons_Manager::is_migration_allowed();
		?>
		<div class="content-wrap">
			<?php $this->render_thumbnail( $settings, $item, $index, $link_key ); ?>
			<div class="content">
				<?php if ( ! empty( $item['timeline_date'] ) ) : ?>
					<div class="date">
						<?php
						$migrated = isset( $settings['__fa4_migrated']['date_icon'] );
						$is_new   = empty( $settings['d_icon'] ) && $migration_allowed;
						if ( $is_new || $migrated ) {
							Icons_Manager::render_icon( $settings['date_icon'] );
						} else { ?>
							<i class="<?php echo esc_attr( $settings['d_icon'] ); ?>"></i>
						<?php }
						?>
						<?php $this->print_unescaped_setting( 'timeline_date', 'timelines', $index ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $item['timeline_title'] ) ) : ?>
					<<?php Utils::print_validated_html_tag( $settings['title_tag'] ); ?> class="title">
						<?php
						if ( ! empty( $item['link']['url'] ) ) :
							echo '<a ' . $this->get_render_attribute_string( $link_key ) . '>'; // phpcs:ignore
						endif;
							$this->print_unescaped_setting( 'timeline_title', 'timelines', $index );
						if ( ! empty( $item['link']['url'] ) ) :
							echo '</a>';
						endif;
						?>
					</<?php Utils::print_validated_html_tag( $settings['title_tag'] ); ?>>
				<?php endif; ?>

				<?php if ( ! empty( $item['timeline_sub'] ) ) : ?>
					<div class="subtitle">
						<?php $this->print_unescaped_setting( 'timeline_sub', 'timelines', $index ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $item['timeline_desc'] ) ) : ?>
					<div class="description">
						<?php $this->print_unescaped_setting( 'timeline_desc', 'timelines', $index ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	protected function render_timeline_step( $item, $index ) {
		$migration_allowed = Icons_Manager::is_migration_allowed();
		?>
		<div class="step-box">
			<div class="icon">
				<?php
				if ( ! empty( $this->get_settings( 'show_indicator' ) ) ) {
					?><div class="indicator"></div><?php
				}
				?>

				<?php
				if ( 'icon' === $item['step_type'] ) {
					$migrated = isset( $item['__fa4_migrated']['step_icon'] );
					$is_new   = empty( $item['icon'] ) && $migration_allowed;
					if ( $is_new || $migrated ) {
						Icons_Manager::render_icon( $item['step_icon'] );
					} else { ?>
						<i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
					<?php }
				} else {
					$this->print_unescaped_setting( 'step_text', 'timelines', $index );
				}
				?>
			</div>
			<div class="line"></div>
		</div>
		<?php
	}

	protected function render_thumbnail( $settings, $item, $index, $link_key ) {
		if ( empty( $item['timeline_image']['url'] ) ) {
			return;
		}

		$image_url = Group_Control_Image_Size::get_attachment_image_src( $item['timeline_image']['id'], 'image_size', $settings );

		if ( ! $image_url && isset( $item['timeline_image']['url'] ) ) {
			$image_url = $item['timeline_image']['url'];
		}
		$image_html = '<img  src="' . esc_url( $image_url ) . '" alt="' . esc_attr( Control_Media::get_image_alt( $item['timeline_image'] ) ) . '" />';
		if ( ! empty( $item['link']['url'] ) ) :
			$image_html = '<a ' . $this->get_render_attribute_string( $link_key ) . '>' . $image_html . '</a>';
		endif;
		?>
		<div class="thumb">
			<?php echo wp_kses_post( $image_html ); ?>
		</div>
		<?php
	}
}
