<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Posts
 *
 * Elementor widget for Posts.
 *
 * @since 1.0.0
 */
class Video_Posts_Tab extends Widget_Base {

	/**
	 * @var \WP_Query
	 */
	protected $query = null;

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_name() {
		return 'aae--video-posts-tab';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_title() {
		return esc_html__( 'Video Posts Tab', 'animation-addons-for-elementor' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_icon() {
		return 'wcf eicon-post-list';
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
	 *
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
		return [ 'aae-video-posts-tab' ];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'aae-video-posts-tab' ];
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
		// Query
		$this->register_query_controls();

		$this->start_controls_section(
			'section_settings',
			[
				'label' => esc_html__( 'Settings', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label'   => esc_html__( 'Posts Per Page', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6,
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'         => 'thumbnail_size',
				'exclude'      => [ 'custom' ],
				'default'      => 'medium',
				'prefix_class' => 'elementor-portfolio--thumbnail-size-',
			]
		);

		$this->add_control(
			'show_thumb',
			[
				'label'     => esc_html__( 'Show Thumb', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'no',
				'label_off' => esc_html__( 'Off', 'animation-addons-for-elementor' ),
				'label_on'  => esc_html__( 'On', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'show_title',
			[
				'label'     => esc_html__( 'Show Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_off' => esc_html__( 'Off', 'animation-addons-for-elementor' ),
				'label_on'  => esc_html__( 'On', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'title_length',
			[
				'label'     => esc_html__( 'Title Length', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 5,
				'max'       => 100,
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'     => esc_html__( 'Title HTML Tag', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
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
				'default'   => 'h3',
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_highlited_title',
			[
				'label'     => esc_html__( 'Highlited Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'highlight_title_length',
			[
				'label'     => esc_html__( 'Title Length', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 100,
				'condition' => [
					'show_highlited_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_excerpt',
			[
				'label'     => esc_html__( 'Show Excerpt', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'excerpt_length',
			[
				'label'     => esc_html__( 'Excerpt Length', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 5,
				'max'       => 100,
				'default'   => 30,
				'condition' => [
					'show_excerpt' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_date',
			[
				'label'     => esc_html__( 'Show Date', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'show_comment',
			[
				'label'     => esc_html__( 'Show Comment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'show_author',
			[
				'label'     => esc_html__( 'Show Author', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'show_taxonomy',
			[
				'label'     => esc_html__( 'Show Taxonomy', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'show_read_more',
			[
				'label'     => esc_html__( 'Read More', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'   => 'yes',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'read_more_text',
			[
				'label'     => esc_html__( 'Read More Text', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => [
					'active' => true,
				],
				'default'   => esc_html__( 'Read More', 'animation-addons-for-elementor' ),
				'condition' => [ 'show_read_more' => 'yes' ],
			]
		);

		$this->add_control(
			'selected_icon',
			[
				'label'            => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'condition'        => [ 'show_read_more' => 'yes' ],
			]
		);

		$this->add_control(
			'icon_align',
			[
				'label'     => esc_html__( 'Icon Position', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'left',
				'options'   => [
					'left'  => esc_html__( 'Before', 'animation-addons-for-elementor' ),
					'right' => esc_html__( 'After', 'animation-addons-for-elementor' ),
				],
				'condition' => [ 'show_read_more' => 'yes' ],
			]
		);

		$this->add_control(
			'icon_indend',
			[
				'label'     => esc_html__( 'Icon Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .link' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition' => [ 'show_read_more' => 'yes' ],
			]
		);

		$this->add_control(
			'video_play_icon',
			[
				'label'       => esc_html__( 'Play Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-play',
					'library' => 'fa-solid',
				],
				'separator'   => 'before',
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'section_button',
			[
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_view_more',
			[
				'label'     => esc_html__( 'View All', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'   => 'yes',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_view_more_text',
			[
				'label'     => esc_html__( 'View Text', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'View All',
				'condition' => [
					'show_view_more' => 'yes',
				],
			]
		);

		$this->add_control(
			'view_more_link',
			[
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'default'     => [
					'is_external' => 'true',
				],
				'dynamic'     => [
					'active' => true,
				],
				'placeholder' => esc_html__( 'https://your-link.com', 'animation-addons-for-elementor' ),
				'condition'   => [
					'show_view_more' => 'yes',
				],
			]
		);

		$this->add_control(
			'view_more_icon',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-circle',
					'library' => 'fa-solid',
				],
				'condition' => [
					'show_view_more' => 'yes',
				],
			]
		);


		$this->end_controls_section();


		//layout style
		$this->register_design_layout_controls();


		// Banner
		$this->start_controls_section(
			'style_banner_image',
			[
				'label' => esc_html__( 'Banner', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'banner_overlay',
			[
				'label' => esc_html__( 'Overlay Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .posts-banner::after' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'banner_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vh', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .posts-banner' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'banner_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .posts-banner' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

        // Play Icon
		$this->start_controls_section(
			'style_play_icon',
			[
				'label' => esc_html__( 'Play Icon', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'play_color',
			[
				'label' => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .play-icon' => 'fill: {{VALUE}}; color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'play_border',
				'selector' => '{{WRAPPER}} .play-icon',
			]
		);

		$this->add_responsive_control(
			'Play_b_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .play-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'play_icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .play-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'play_circle_size',
			[
				'label'      => esc_html__( 'Circle Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .play-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'play_icon_position',
			[
				'label'      => esc_html__( 'Icon Position', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .play-icon' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();


		//Thumbnail style
		$this->start_controls_section(
			'section_style_post_image',
			[
				'label' => esc_html__( 'Thumbnail', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'border_style',
			[
				'label'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'block',
				'options'   => [
					'block' => esc_html__( 'Show', 'animation-addons-for-elementor' ),
					'none'  => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .aae-posts .thumb' => 'display: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'thumb_height',
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
					'{{WRAPPER}} .aae-posts .thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'thumb_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
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
					'{{WRAPPER}} .aae-posts .thumb img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Title
		$this->start_controls_section(
			'style_post_title',
			[
				'label'     => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typo',
				'label'    => esc_html__( 'Typography', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .title, {{WRAPPER}} .title a',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'hl_title_typo',
				'label'    => esc_html__( 'Highlight Typography', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .title span.highlight',
			]
		);

		$this->start_controls_tabs( 'tabs_title_style' );

		$this->start_controls_tab(
			'tab_title_normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title, {{WRAPPER}} .title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'hl_title_color',
			[
				'label'     => esc_html__( 'Highlight Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title span.highlight' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_title_hover',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'title_color_hover',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title:hover, {{WRAPPER}} .title a:hover, {{WRAPPER}} .title span.highlight:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before'
			]
		);

		$this->end_controls_section();


		//Excerpt
		$this->start_controls_section(
			'section_style_post_excerpt',
			[
				'label'     => esc_html__( 'Excerpt', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_excerpt' => 'yes',
				],
			]
		);

		$this->add_control(
			'excerpt_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desc, {{WRAPPER}} .desc p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'excerpt_typo',
				'selector' => '{{WRAPPER}} .desc, {{WRAPPER}} .desc p',
			]
		);

		$this->add_responsive_control(
			'excerpt_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .desc' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		//Meta
		$this->start_controls_section(
			'style_post_meta',
			[
				'label' => esc_html__( 'Meta', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'meta_typo',
				'selector' => '{{WRAPPER}} .aae-meta a, {{WRAPPER}} .aae-meta span',
			]
		);

		$this->add_control(
			'separator_color',
			[
				'label'     => esc_html__( 'Separator Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .style-1 .aae-meta li::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->start_controls_tabs(
			'meta_style_tabs'
		);

		$this->start_controls_tab(
			'meta_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'meta_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aae-meta a, {{WRAPPER}} .wcf-meta span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'meta_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'meta_h_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aae-meta a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'meta_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .aae-meta' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before'
			]
		);

		$this->end_controls_section();


		// Date style 
		$this->start_controls_section(
			'style_post_date',
			[
				'label' => esc_html__( 'Date', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'date_typo',
				'selector' => '{{WRAPPER}} span.post-date',
			]
		);

		$this->start_controls_tabs(
			'date_style_tabs'
		);

		$this->start_controls_tab(
			'date_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'date_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} span.post-date' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'date_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'date_h_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} span.post-date:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();


		// Date style 
		$this->start_controls_section(
			'style_post_view_all',
			[
				'label'     => esc_html__( 'View all', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_view_more' => 'yes',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'     => 'vvaaebackground',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aae--posts-tab .aae-view-all',
			]
		);

		$this->add_responsive_control(
			'view_all_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .aae--posts-tab .aae-view-all' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'view_all_typo',
				'selector' => '{{WRAPPER}} .aae--posts-tab .aae-view-all a',
			]
		);

		$this->start_controls_tabs(
			'view_all_style_tabs'
		);

		$this->start_controls_tab(
			'view_all_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'view_all_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aae--posts-tab .aae-view-all a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'view_all_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'view_all_h_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aae--posts-tab .aae-view-all a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();


		//Read More
		$this->start_controls_section(
			'style_read_more',
			[
				'label'     => esc_html__( 'Read More', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_read_more' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'read_more_typo',
				'selector' => '{{WRAPPER}} .link',
			]
		);

		$this->add_responsive_control(
			'read_more_icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					],
					'%'  => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .link i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .link svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'tabs_read_more_style',
		);

		$this->start_controls_tab(
			'tab_read_more_normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'read_more_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .link'       => 'color: {{VALUE}};',
					'{{WRAPPER}} .link svg'   => 'fill: {{VALUE}};',
					'{{WRAPPER}} .link:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'read_more_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .link',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_read-more_hover',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'read_more_text_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .link:hover'       => 'color: {{VALUE}};',
					'{{WRAPPER}} .link:hover svg'   => 'fill: {{VALUE}};',
					'{{WRAPPER}} .link:hover:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'read_more_hover_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .link:hover',
			]
		);

		$this->add_control(
			'read_more_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .link:hover' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'read_more_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'read_more_border',
				'selector'  => '{{WRAPPER}} .link',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'read_more_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'read_more_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'read_more_shadow',
				'selector' => '{{WRAPPER}} .link',
			]
		);

		$this->add_control(
			'read_more_line',
			[
				'label'     => esc_html__( 'Animated Border', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
					'0' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .link:after' => 'width: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'read_more_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .link' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_design_layout_controls() {
		$this->start_controls_section(
			'section_design_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'post_col',
			[
				'label'          => esc_html__( 'Columns', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SELECT,
				'render_type'    => 'template',
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
				'selectors'      => [
					'{{WRAPPER}} .aae-posts' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
			]
		);

		$this->add_responsive_control(
			'column_gap',
			[
				'label'      => esc_html__( 'Columns Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default'    => [
					'size' => 30,
				],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .aae-posts' => 'column-gap: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'row_gap',
			[
				'label'      => esc_html__( 'Rows Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default'    => [
					'size' => 35,
				],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .aae-posts' => 'row-gap: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'post_b_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aae-post, {{WRAPPER}} .style-2 .aae-posts-wrap, {{WRAPPER}} .style-2 .aae-view-all' => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .aae--posts-tab .aae-posts-wrap'                                                     => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .aae--posts-tab .aae-view-all'                                                       => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'post_overlay',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .posts-banner::after',
			]
		);

		$this->add_responsive_control(
			'post_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .aae-post' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'alignment',
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
				'selectors' => [
					'{{WRAPPER}} .aae-post' => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .aae-meta' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_query_controls() {
		$this->start_controls_section(
			'section_query',
			[
				'label' => esc_html__( 'Query', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

//		$this->add_control(
//			'include_term_ids',
//			[
//				'label'       => esc_html__( 'Category ID', 'animation-addons-for-elementor' ),
//				'description' => esc_html__( 'Add comma for separated.', 'animation-addons-for-elementor' ),
//				'placeholder' => esc_html__( '122, 125', 'animation-addons-for-elementor' ),
//				'label_block' => true,
//			]
//		);

		$this->add_control(
			'post_order_by',
			[
				'label'   => esc_html__( 'Order By', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'          => esc_html__( 'Date', 'animation-addons-for-elementor' ),
					'title'         => esc_html__( 'Title', 'animation-addons-for-elementor' ),
					'menu_order'    => esc_html__( 'Menu Order', 'animation-addons-for-elementor' ),
					'modified'      => esc_html__( 'Last Modified', 'animation-addons-for-elementor' ),
					'comment_count' => esc_html__( 'Comment Count', 'animation-addons-for-elementor' ),
					'rand'          => esc_html__( 'Random', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'post_order',
			[
				'label'   => esc_html__( 'Order', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'desc',
				'options' => [
					'asc'  => esc_html__( 'ASC', 'animation-addons-for-elementor' ),
					'desc' => esc_html__( 'DESC', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'post_sticky_ignore',
			[
				'label'        => esc_html__( 'Ignore Sticky Posts', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
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

		$query = array(
			'post_type'           => 'post',
			'posts_per_page'      => $this->get_settings( 'posts_per_page' ),
			'ignore_sticky_posts' => empty( $this->get_settings( 'post_sticky_ignore' ) ) ? false : true,
			'order'               => $this->get_settings( 'post_order' ),
			'orderby'             => $this->get_settings( 'post_order_by' ),
			'tax_query'           => array(
				array(
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'terms'    => array( 'post-format-video' ), // Only video posts
				),
			),
		);

		$query = new WP_Query( $query );

		// Wrapper Class
		$this->add_render_attribute( 'wrapper', 'class', [ 'aae--posts-tab' ] );
		?>

        <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
            <div class="posts-banner">
				<?php
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						?>
                        <div class="thumb" data-target="<?php echo esc_attr( get_the_ID() ); ?>">
							<?php the_post_thumbnail(); ?>
                            <a href="<?php the_permalink(); ?>">
                                <div class="play-icon">
		                            <?php Icons_Manager::render_icon( $settings['video_play_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                                </div>
                            </a>
                        </div>
						<?php
					}
				} else { ?>
                    <p style="text-align: center"><?php echo esc_html__( "No Video Post Found!", "animation-addons-for-elementor" ); ?></p>
				<?php } ?>
            </div>
            <div class="aae-posts-wrap">
                <div class="aae-posts">
					<?php
					while ( $query->have_posts() ) {
						$query->the_post();
						$this->render_post( $settings );
					}
					?>
                </div>
				<?php if ( $settings['show_view_more'] == 'yes' ):
					if ( ! empty( $settings['view_more_link']['url'] ) ) {
						$this->add_link_attributes( 'view_more_link', $settings['view_more_link'] );
					}
					?>
                    <div class="aae-view-all">
                        <a <?php $this->print_render_attribute_string( 'view_more_link' ); ?>>
							<?php echo wp_kses_post( $settings['show_view_more_text'] ); ?>
							<?php \Elementor\Icons_Manager::render_icon( $settings['view_more_icon'], [ 'aria-hidden' => 'true' ] ); ?>
                        </a>
                    </div>
				<?php endif; ?>
            </div>
        </div>
		<?php
		wp_reset_postdata();
	}

	protected function render_thumbnail( $settings ) {
		$settings['thumbnail_size'] = [
			'id' => get_post_thumbnail_id(),
		];
		?>
        <div class="thumb" data-target="<?php echo esc_attr( get_the_ID() ); ?>">
            <a href="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php the_title(); ?>">
				<?php Group_Control_Image_Size::print_attachment_image_html( $settings, 'thumbnail_size' ); ?>
            </a>
        </div>
		<?php
	}


	public static function wcf_wrap_first_n_words( $text, $n, $class = 'highlight' ) {
		// Split the text into an array of words
		$words = explode( ' ', $text );
		// Check if the text has enough words to wrap
		if ( count( $words ) >= $n ) {
			// Extract the first N words and wrap them in a span tag
			$wrapped_words   = array_slice( $words, 0, $n );
			$remaining_words = array_slice( $words, $n );
			// Create the wrapped portion
			$wrapped = '<span class="' . $class . '">' . implode( ' ', $wrapped_words ) . '</span>';

			// Combine the wrapped portion with the remaining words
			return $wrapped . ' ' . implode( ' ', $remaining_words );
		}

		// If there are fewer words than N, wrap the whole text
		return '<span class="' . $class . '">' . $text . '</span>';
	}

	protected function render_title() {
		if ( ! $this->get_settings( 'show_title' ) ) {
			return;
		}

		$tag = $this->get_settings( 'title_tag' );
		?>
        <<?php Utils::print_validated_html_tag( $tag ); ?> class="title">
        <a href="<?php echo esc_url( get_the_permalink() ); ?>">
			<?php
			global $post;
			// Force the manually-generated Excerpt length as well if the user chose to enable 'apply_to_custom_excerpt'.
			if ( ! empty( $post->post_title ) ) {
				$max_length = (int) $this->get_settings( 'title_length' );
				$title      = $this->trim_words( get_the_title(), $max_length );

				$highlight_title_length = (int) $this->get_settings( 'highlight_title_length' );

				echo wp_kses_post( $this->wcf_wrap_first_n_words( $title, $highlight_title_length ) ); // Wrap first 2 words

			} else {
				the_title();
			}
			?>
        </a>
        </<?php Utils::print_validated_html_tag( $tag ); ?>>
		<?php
	}

	public function filter_excerpt_length() {
		return (int) $this->get_settings( 'excerpt_length' );
	}

	public static function trim_words( $text, $length ) {
		if ( $length && str_word_count( $text ) > $length ) {
			$text = explode( ' ', $text, $length + 1 );
			unset( $text[ $length ] );
			$text = implode( ' ', $text );
		}

		return $text;
	}

	protected function render_excerpt() {
		if ( ! $this->get_settings( 'show_excerpt' ) ) {
			return;
		}
		add_filter( 'excerpt_length', [ $this, 'filter_excerpt_length' ], 20 );
		?>
        <div class="desc">
			<?php
			global $post;
			// Force the manually-generated Excerpt length as well if the user chose to enable 'apply_to_custom_excerpt'.
			if ( empty( $post->post_excerpt ) ) {
				$max_length = (int) $this->get_settings( 'excerpt_length' );
				$excerpt    = apply_filters( 'the_excerpt', get_the_excerpt() );
				$excerpt    = $this->trim_words( $excerpt, $max_length );
				echo wp_kses_post( $excerpt );
			} else {
				the_excerpt();
			}
			?>
        </div>
		<?php
		remove_filter( 'excerpt_length', [ $this, 'filter_excerpt_length' ], 20 );
	}

	protected function render_date_by_type( $type = 'publish' ) {
		if ( ! $this->get_settings( 'show_date' ) ) {
			return;
		}
		?>
        <li>
		<span class="post-date">
			<?php
			switch ( $type ) :
				case 'modified':
					$date = get_the_modified_date();
					break;
				default:
					$date = get_the_date();
			endswitch;
			/** This filter is documented in wp-includes/general-template.php */
			// PHPCS - The date is safe.
			echo apply_filters( 'the_date', $date, get_option( 'date_format' ), '', '' ); // phpcs:ignore
			?>
		</span>
        </li>
		<?php
	}

	protected function render_comment() {
		if ( ! $this->get_settings( 'show_comment' ) ) {
			return;
		}
		?>
        <li><span><?php comments_number(); ?></span></li>
		<?php
	}

	protected function render_author() {
		if ( ! $this->get_settings( 'show_author' ) ) {
			return;
		}
		?>
        <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
			<?php the_author(); ?>
        </a>
		<?php
	}

	protected function render_author_avatar() {
		if ( ! $this->get_settings( 'show_author' ) ) {
			return;
		}
		?>
        <div class="author">
            <div class="author-img">
				<?php echo wp_kses_post( get_avatar( get_the_author_meta( 'ID' ), 60 ) ); ?>
            </div>
            <div class="author-bio">
                <p>
					<?php
					esc_html_e( 'Written by ', 'animation-addons-for-elementor' );
					$this->render_author();
					?>
                </p>
            </div>
        </div>
		<?php
	}


	protected function render_post_taxonomy() {
		if ( ! $this->get_settings( 'show_taxonomy' ) ) {
			return;
		}

		$taxonomy = $this->get_settings( 'post_taxonomy' );

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$terms = get_the_terms( get_the_ID(), $taxonomy );

		if ( empty( $terms ) ) {
			return;
		}

		?>
        <ul class="aae-meta">
			<?php
			foreach ( $terms as $term ) {
				printf( '<li><a href="%1$s">%2$s</a></li>',
					esc_url( get_term_link( $term->slug, $taxonomy ) ),
					esc_html( $term->name )
				);
			}

			if ( $this->get_settings( 'show_author' ) ) {
				?>
                <li><?php $this->render_author(); ?></li>
				<?php
			}

			$this->render_date_by_type();
			$this->render_comment();
			?>
        </ul>
		<?php
	}

	protected function render_read_more() {
		if ( ! $this->get_settings( 'show_read_more' ) ) {
			return;
		}

		$read_more       = $this->get_settings( 'read_more_text' );
		$aria_label_text = sprintf(
		/* translators: %s: Post title. */
			esc_attr__( 'Read more about %s', 'animation-addons-for-elementor' ),
			get_the_title()
		);
		$migrated        = isset( $this->get_settings( '__fa4_migrated' )['selected_icon'] );
		$is_new          = empty( $this->get_settings( 'icon' ) ) && Icons_Manager::is_migration_allowed();
		?>

        <a class="link <?php echo esc_attr( $this->get_settings( 'icon_align' ) ); ?>"
           href="<?php echo esc_url( get_the_permalink() ); ?>"
           tabindex="-1">
            <span class="screen-reader-text"><?php echo esc_html( $aria_label_text ); ?></span>
			<?php if ( $is_new || $migrated ) :
				Icons_Manager::render_icon( $this->get_settings( 'selected_icon' ), [ 'aria-hidden' => 'true' ] );
			else : ?>
                <i class="<?php echo esc_attr( $this->get_settings( 'icon' ) ); ?>" aria-hidden="true"></i>
			<?php endif; ?>
			<?php echo wp_kses_post( $read_more ); ?>
        </a>
		<?php
	}

	protected function render_post( $settings ) {
		?>
        <article <?php post_class( 'aae-post' ); ?>>
			<?php
			if ( 'yes' === $settings['show_thumb'] ) {
				$this->render_thumbnail( $settings );
			}
			?>
            <div class="content-wrap">
                <div class="content" data-id="<?php echo esc_attr( get_the_ID() ); ?>">
					<?php
					$this->render_title();
					$this->render_excerpt();
					$this->render_date_by_type();
					$this->render_author_avatar();
					$this->render_post_taxonomy();
					?>
                </div>
                <div class="link-wrap">
					<?php $this->render_read_more(); ?>
                </div>
            </div>
        </article>
		<?php
	}
}
