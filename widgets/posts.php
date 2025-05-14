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
use WCF_ADDONS\WCF_Post_Query_Trait;

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
class Posts extends Widget_Base {

	use WCF_Post_Query_Trait;

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
		return 'wcf--posts';
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
		return esc_html__( 'Posts', 'animation-addons-for-elementor' );
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
		return [ 'weal-coder-addon', 'wcf-archive-addon' ];
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
		return [ 'wcf--posts' ];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array(
			'wcf--posts',
		);
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
					'3' => esc_html__( 'Three', 'animation-addons-for-elementor' ),
					'4' => esc_html__( 'Four', 'animation-addons-for-elementor' ),
					'5' => esc_html__( 'Five', 'animation-addons-for-elementor' ),
					'6' => esc_html__( 'Six', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_responsive_control(
			'columns',
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
					'{{WRAPPER}} .wcf-posts' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
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
				'label'     => esc_html__( 'Title Length (words)', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 5,
				'max'       => 100,
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'title_length_line',
			[
				'label'     => esc_html__( 'Title Length (lines)', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 5,
				'condition' => [
					'show_title' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-posts .title a' => '-webkit-line-clamp: {{VALUE}}; white-space: normal; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;',
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
			'post_thumb_icon',
			[
				'label'            => esc_html__( 'Default Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'condition'        => [
					'element_list' => '6',
				],
			]
		);

		$this->add_control(
			'video_selected_icon',
			[
				'label'            => esc_html__( 'Video Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'condition'        => [
					'element_list' => '6',
				],
			]
		);
		$this->add_control(
			'post_audio_icon',
			[
				'label'            => esc_html__( 'Audio Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'condition'        => [
					'element_list' => '6',
				],
			]
		);
		$this->add_control(
			'post_gallery_icon',
			[
				'label'            => esc_html__( 'Gallery Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'condition'        => [
					'element_list' => '6',
				],
			]
		);

		$this->add_control(
			'post_taxonomy',
			[
				'label'       => esc_html__( 'Taxonomy', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => 'category',
				'options'     => $this->get_taxonomies(),
				'condition'   => [
					'show_taxonomy' => 'yes',
				],
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

		$this->end_controls_section();

		//query
		$this->register_query_controls();

		//layout style
		$this->register_design_layout_controls();

		//Thumbnail style
		$this->start_controls_section(
			'section_style_post_image',
			[
				'label' => esc_html__( 'Thumbnail', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
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
						'max' => 1000,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .posts_video_thumb' => 'flex: 0 0 {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'thumb_icon_color',
			[
				'label'     => esc_html__( 'Icon Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .thumb_icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
				'condition' => [
					'element_list' => '6',
				],
			]
		);

		$this->add_responsive_control(
			'thumb_icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 6,
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .thumb_icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'element_list' => '6',
				],
			]
		);

		$this->end_controls_section();

		// Content style
		$this->start_controls_section(
			'section_style_post_content',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'content_background',
				'types'     => [ 'classic', 'gradient' ],
				'exclude'   => [ 'image' ],
				'selector'  => '{{WRAPPER}} .content',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'content_bg_even_heading',
			[
				'label'     => esc_html__( 'Background for Even Items', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'content_bg_even',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .wcf-post:nth-child(even) .content',
				'label'    => ''
			]
		);

		$this->end_controls_section();

		//Tile
		$this->start_controls_section(
			'section_style_post_title',
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
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title Typography', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .title, {{WRAPPER}} .title a',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'highlited_title_typography',
				'label'    => esc_html__( 'Highlight Title', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .title span.highlight',
			]
		);

		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'title_border',
				'selector' => '{{WRAPPER}} .title',
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
				'label'     => esc_html__( 'Title Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title, {{WRAPPER}} .title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'highlited_title_color',
			[
				'label'     => esc_html__( 'Highlited Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title span.highlight' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_tile_hover',
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
			'title_spacing',
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
					'{{WRAPPER}} .title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
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
				'name'     => 'excerpt_typography',
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


		//Taxonomy
		$this->start_controls_section(
			'section_style_post_taxonomy',
			[
				'label'     => esc_html__( 'Taxonomy', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_taxonomy' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'taxonomy_typography',
				'selector' => '{{WRAPPER}} .wcf-taxonomy a',
			]
		);

		$this->add_responsive_control(
			'taxonomy_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-taxonomy a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'taxonomy_style_tabs'
		);

		$this->start_controls_tab(
			'taxonomy_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'taxonomy_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-taxonomy a' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'taxonomy_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf-taxonomy a',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'taxonomy_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'taxonomy_h_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-taxonomy a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'taxonomy_h_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf-taxonomy a:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'taxonomy_border',
			[
				'label'     => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
					'0' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-taxonomy::before' => 'width: {{VALUE}};',
				],
				'condition' => [
					'element_list' => [ '2', '5' ],
				],
			]
		);

		$this->add_control(
			'tax_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-taxonomy::before' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'element_list'     => [ '2', '5' ],
					'taxonomy_border!' => '0',
				],
			]
		);

		$this->add_responsive_control(
			'taxonomy_spacing',
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
					'{{WRAPPER}} .wcf-taxonomy' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		//Meta
		$this->start_controls_section(
			'section_style_post_meta',
			[
				'label' => esc_html__( 'Meta', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'view_meta_icon',
			[
				'label'       => esc_html__( 'View Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'far fa-flag',
					'library' => 'fa-regular',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'meta_typography',
				'selector' => '{{WRAPPER}} .wcf-meta a, {{WRAPPER}} .wcf-meta span, {{WRAPPER}} .author_views a, {{WRAPPER}} .author_views span.posts_views',
			]
		);

		$this->add_responsive_control(
			'meta_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-meta a, {{WRAPPER}} .wcf-meta span, {{WRAPPER}} .author_views a, {{WRAPPER}} .author_views span.posts_views' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .wcf-meta a, {{WRAPPER}} .wcf-meta span, {{WRAPPER}} .author_views a, {{WRAPPER}} .author_views span.posts_views' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'meta_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf-meta a, {{WRAPPER}} .wcf-meta span, {{WRAPPER}} .author_views a, {{WRAPPER}} .author_views span.posts_views',
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
					'{{WRAPPER}} .wcf-meta a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'meta_h_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf-meta a:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'meta_border',
			[
				'label'     => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
					'0' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-meta::before' => 'width: {{VALUE}};',
				],
				'condition' => [
					'element_list' => [ '2', '5' ],
				],
			]
		);

		$this->add_control(
			'meta_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-meta::before' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'element_list' => [ '2', '5' ],
					'meta_border!' => '0',
				],
			]
		);

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
					'{{WRAPPER}} .wcf-meta' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Date style
		$this->start_controls_section(
			'section_style_post_dates',
			[
				'label'     => esc_html__( 'Date Style', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'element_list' => '6',
				],
			]
		);

		$this->add_responsive_control(
			'date_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
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
					'{{WRAPPER}} .post_video_date' => 'flex: 0 0 {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'content_date_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .post_video_date .wcf-meta_video' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'day_color',
			[
				'label'     => esc_html__( 'Day Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} span.meta_day' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'day_typography',
				'selector' => '{{WRAPPER}} span.meta_day',
			]
		);

		$this->add_responsive_control(
			'name_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'default'    => [
					'size' => 5,
				],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-meta_video span.meta_day' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);


		$this->add_control(
			'day_number_color',
			[
				'label'     => esc_html__( 'Day Color ( Number )', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} span.meta_year' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'day_number_typography',
				'selector' => '{{WRAPPER}} span.meta_year',
			]
		);

		$this->add_responsive_control(
			'number_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'default'    => [
					'size' => 5,
				],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-meta_video span.meta_year' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'day_month_color',
			[
				'label'     => esc_html__( 'Month Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} span.meta_month' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'day_month_typography',
				'selector' => '{{WRAPPER}} span.meta_month',
			]
		);

		$this->end_controls_section();

		//Author
		$this->start_controls_section(
			'section_style_post_author',
			[
				'label'     => esc_html__( 'Author', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'element_list' => '4' ]
			]
		);

		$this->add_control(
			'author_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .author .author-bio p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'author_typography',
				'selector' => '{{WRAPPER}} .author .author-bio p',
			]
		);

		$this->add_responsive_control(
			'author_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .author' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'author_name_color',
			[
				'label'     => esc_html__( 'Name Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .author .author-bio a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'author_name_typography',
				'label'     => esc_html__( 'Name Typography', 'animation-addons-for-elementor' ),
				'selector'  => '{{WRAPPER}} .author .author-bio a',
				'condition' => [ 'element_list' => '4' ]
			]
		);

		$this->end_controls_section();


		//Read More
		$this->start_controls_section(
			'style_post_read_more',
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
				'name'     => 'read_more_typography',
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

		//pagination
		$this->register_pagination_section_controls();

		//hover color
		$this->start_controls_section(
			'section_style_hover_post_content',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		//tile
		$this->add_control(
			'heading_title_hover_style',
			[
				'label'     => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post:hover .title, {{WRAPPER}} .wcf-post:hover .title a' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		//excerpt
		$this->add_control(
			'heading_excerpt_hover_style',
			[
				'label'     => esc_html__( 'Excerpt', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_excerpt' => 'yes',
				],
			]
		);

		$this->add_control(
			'excerpt_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post:hover .desc, {{WRAPPER}} .wcf-post:hover .desc p' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_excerpt' => 'yes',
				],
			]
		);

		//taxonomy
		$this->add_control(
			'heading_taxonomy_hover_style',
			[
				'label'     => esc_html__( 'Taxonomy', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_taxonomy' => 'yes',
				],
			]
		);

		$this->add_control(
			'taxonomy_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post:hover .wcf-taxonomy a' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_taxonomy' => 'yes',
				],
			]
		);

		//meta
		$this->add_control(
			'heading_meta_hover_style',
			[
				'label'     => esc_html__( 'Meta', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'meta_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post:hover .wcf-meta a, {{WRAPPER}} .wcf-post:hover .wcf-meta span' => 'color: {{VALUE}};',
				],
			]
		);

		//author
		$this->add_control(
			'heading_author_hover_style',
			[
				'label'     => esc_html__( 'Author', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [ 'element_list' => '4' ]
			]
		);

		$this->add_control(
			'author_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post:hover .author .author-bio p' => 'color: {{VALUE}};',
				],
				'condition' => [ 'element_list' => '4' ]
			]
		);

		$this->add_control(
			'author_name_hover_color',
			[
				'label'     => esc_html__( 'Name Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .wcf-post:hover .author .author-bio a' => 'color: {{VALUE}};',
				],
				'condition' => [ 'element_list' => '4' ]
			]
		);

		//read more
		$this->add_control(
			'heading_read_more_hover_style',
			[
				'label'     => esc_html__( 'Read More', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_read_more' => 'yes',
				],
			]
		);

		$this->add_control(
			'read_more_hover_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post:hover .link'       => 'color: {{VALUE}};',
					'{{WRAPPER}} .wcf-post:hover .link svg'   => 'fill: {{VALUE}};',
					'{{WRAPPER}} .wcf-post:hover .link:after' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'show_read_more' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'read_more_hover_link_background',
				'types'     => [ 'classic', 'gradient' ],
				'exclude'   => [ 'image' ],
				'selector'  => '{{WRAPPER}} .wcf-post:hover .link',
				'condition' => [
					'show_read_more' => 'yes',
				],
			]
		);

		$this->add_control(
			'read_more_hover_link_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post:hover .link' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'read_more_border_border!' => '',
					'show_read_more'           => 'yes',
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
					'{{WRAPPER}} .wcf-posts'                    => 'column-gap: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .wcf__posts.style-6 .wcf-post' => 'column-gap: {{SIZE}}{{UNIT}}',
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
					'{{WRAPPER}} .wcf-posts' => 'row-gap: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'post_border',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .wcf-post',
			]
		);

		$this->add_responsive_control(
			'post_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		//hover effect
		$this->add_control(
			'el_hover_effects',
			[
				'label'        => esc_html__( 'Hover Effect', 'animation-addons-for-elementor' ),
				'description'  => esc_html__( 'This effect will work only on image tags.', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'effect-zoom-in',
				'options'      => [
					''                => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'effect-zoom-in'  => esc_html__( 'Zoom In', 'animation-addons-for-elementor' ),
					'effect-zoom-out' => esc_html__( 'Zoom Out', 'animation-addons-for-elementor' ),
					'left-move'       => esc_html__( 'Left Move', 'animation-addons-for-elementor' ),
					'right-move'      => esc_html__( 'Right Move', 'animation-addons-for-elementor' ),
				],
				'prefix_class' => 'wcf--image-',
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
					'{{WRAPPER}} .wcf-post'                            => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .content'                             => 'align-items: {{VALUE}};',
					'{{WRAPPER}} .wcf-taxonomy, {{WRAPPER}} .wcf-meta' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	public function register_pagination_section_controls() {
		$this->start_controls_section(
			'section_pagination',
			[
				'label' => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'pagination_type',
			[
				'label'   => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''                      => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'numbers_and_prev_next' => esc_html__( 'Numbers', 'animation-addons-for-elementor' ) . ' + ' . esc_html__( 'Previous/Next', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'pagination_list',
			[
				'label'     => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '1',
				'options'   => [
					'1' => esc_html__( 'One', 'animation-addons-for-elementor' ),
					'2' => esc_html__( 'Two', 'animation-addons-for-elementor' ),
				],
				'condition' => [
					'pagination_type' => 'numbers_and_prev_next',
				],
			]
		);

		$this->add_control(
			'pagination_page_limit',
			[
				'label'     => esc_html__( 'Page Limit', 'animation-addons-for-elementor' ),
				'default'   => '5',
				'condition' => [
					'pagination_type' => 'numbers_and_prev_next',
				],
			]
		);

		$this->add_control(
			'pagination_numbers_shorten',
			[
				'label'     => esc_html__( 'Shorten', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => [
					'pagination_type' => 'numbers_and_prev_next',
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
				'condition'        => [
					'pagination_type' => [
						'numbers',
						'numbers_and_prev_next',
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
				'condition'        => [
					'pagination_type' => [
						'numbers',
						'numbers_and_prev_next',
					],
				],
			]
		);

		$this->add_control(
			'pagination_align',
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
				'selectors' => [
					'{{WRAPPER}} .post-pagination' => 'justify-content: {{VALUE}};',
					'{{WRAPPER}} .pf-load-more'    => 'justify-content: {{VALUE}};',
				],
				'condition' => [
					'pagination_type!' => '',
				],
			]
		);

		$this->add_responsive_control(
			'pagination_spacing_top',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default'    => [
					'size' => 70,
				],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .post-pagination' => 'margin-top: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .pf-load-more'    => 'margin-top: {{SIZE}}{{UNIT}}',
				],
				'condition'  => [
					'pagination_type!' => '',
				],
			]
		);

		//load more btn
		$this->add_control(
			'heading_load_more_button',
			[
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'pagination_type' => 'load_more',
				],
			]
		);

		$this->add_control(
			'load_more_btn_text',
			[
				'label'       => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Load More Works', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Load More', 'animation-addons-for-elementor' ),
				'condition'   => [
					'pagination_type' => 'load_more',
				],
			]
		);

		$this->add_control(
			'load_more_btn_icon',
			[
				'label'            => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'default'          => [
					'value'   => 'fas fa-arrow-right',
					'library' => 'fa-solid',
				],
				'condition'        => [
					'pagination_type' => 'load_more',
				],
			]
		);
		//load more

		$this->end_controls_section();

		// Pagination style controls for prev/next and numbers pagination.
		$this->start_controls_section(
			'section_pagination_style',
			[
				'label'     => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'pagination_type' => 'numbers_and_prev_next',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pagination_typography',
				'selector' => '{{WRAPPER}} .post-pagination .page-numbers',
			]
		);

		$this->add_control(
			'pagination_color_heading',
			[
				'label'     => esc_html__( 'Colors', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->start_controls_tabs( 'pagination_colors' );

		$this->start_controls_tab(
			'pagination_color_normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'pagination_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-pagination .page-numbers' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'pagination_color_hover',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'pagination_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-pagination .page-numbers:not(.dots):hover, {{WRAPPER}} .post-pagination .page-numbers.current' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_hover_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-pagination .page-numbers.current, {{WRAPPER}} .post-pagination .page-numbers:not(.prev, .next, .dots):hover' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'pagination_list' => '2',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pagination_spacing',
			[
				'label'      => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'separator'  => 'before',
				'default'    => [
					'size' => 10,
				],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .post-pagination' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}


	public function get_current_page() {
		if ( '' === $this->get_settings_for_display( 'pagination_type' ) ) {
			return 1;
		}

		return max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
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

		$query = $this->get_query();

		if ( ! $query->found_posts ) {
			return;
		}

		//wrapper class
		$this->add_render_attribute( 'wrapper', 'class', [
			'wcf__posts',
			'style-' . $settings['element_list'],
		] );

		?>
        <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>><?php

		$this->render_loop_header();

		while ( $query->have_posts() ) {
			$query->the_post();
			if ( $settings['element_list'] == '6' ) {
				$this->render_post_video( $settings );
			} else {
				$this->render_post( $settings );
			}
		}

		$this->render_loop_footer();

		?></div><?php

		wp_reset_postdata();
	}

	protected function render_loop_header() {
		?>
        <div class="wcf-posts">
		<?php
	}

	protected function render_loop_footer() {
		?></div><?php

		$settings = $this->get_settings_for_display();

		// If the skin has no pagination, there's nothing to render in the loop footer.
		if ( ! isset( $settings['pagination_type'] ) ) {
			return;
		}

		if ( '' === $settings['pagination_type'] ) {
			return;
		}

		//load more
		if ( 'load_more' === $settings['pagination_type'] ) {
			$current_page = $this->get_current_page();
			$next_page    = intval( $current_page ) + 1;

			$this->add_render_attribute( 'load_more_anchor', [
				'data-e-id'      => $this->get_id(),
				'data-page'      => $current_page,
				'data-max-page'  => $this->get_query()->max_num_pages,
				'data-next-page' => $this->next_page_link( $next_page ),
			] );

			//icon
			if ( empty( $settings['icon'] ) && ! Icons_Manager::is_migration_allowed() ) {
				// add old default
				$settings['icon'] = 'fa fa-arrow-right';
			}

			if ( ! empty( $settings['icon'] ) ) {
				$this->add_render_attribute( 'icon', 'class', $settings['icon'] );
				$this->add_render_attribute( 'icon', 'aria-hidden', 'true' );
			}

			$migrated = isset( $settings['__fa4_migrated']['load_more_btn_icon'] );
			$is_new   = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();
			?>
            <div class="load-more-anchor" <?php $this->print_render_attribute_string( 'load_more_anchor' ); ?>></div>

            <div class="pf-load-more">
                <button class="load-more">
					<?php $this->print_unescaped_setting( 'load_more_btn_text' ); ?>
					<?php if ( $is_new || $migrated ) :
						Icons_Manager::render_icon( $settings['load_more_btn_icon'], [ 'aria-hidden' => 'true' ] );
					else : ?>
                        <i <?php $this->print_render_attribute_string( 'icon' ); ?>></i>
					<?php endif; ?>
                </button>
            </div>
			<?php
		}

		$page_limit = $this->get_query()->max_num_pages;

		// Page limit control should not effect in load more mode.
		if ( '' !== $settings['pagination_page_limit'] && 'load_more' !== $settings['pagination_type'] ) {
			$page_limit = min( $settings['pagination_page_limit'], $page_limit );
		}

		if ( 2 > $page_limit ) {
			return;
		}

		//number and prev next
		if ( 'numbers_and_prev_next' === $settings['pagination_type'] ) {
			$paginate_args = [
				'current'            => $this->get_current_page(),
				'total'              => $page_limit,
				'prev_next'          => true,
				'prev_text'          => sprintf( '%1$s', $this->render_next_prev_button( 'prev' ) ),
				'next_text'          => sprintf( '%1$s', $this->render_next_prev_button( 'next' ) ),
				'show_all'           => 'yes' !== $settings['pagination_numbers_shorten'],
				'before_page_number' => '<span class="elementor-screen-only">' . esc_html__( 'Page', 'animation-addons-for-elementor' ) . '</span>',
			];

			//pagination class
			$this->add_render_attribute( 'pagination', 'class', [
				'post-pagination',
				'style-' . $settings['pagination_list'],
			] );
			?>
            <nav <?php $this->print_render_attribute_string( 'pagination' ); ?>
                    aria-label="<?php esc_attr_e( 'Pagination', 'animation-addons-for-elementor' ); ?>">
				<?php echo wp_kses_post( paginate_links( $paginate_args ) ); ?>
            </nav>
			<?php
		}
	}

	private function render_next_prev_button( $type ) {
		$direction     = 'next' === $type ? 'right' : 'left';
		$icon_settings = $this->get_settings( 'navigation_' . $type . '_icon' );

		if ( empty( $icon_settings['value'] ) ) {
			$icon_settings = [
				'library' => 'eicons',
				'value'   => 'eicon-chevron-' . $direction,
			];
		}

		$text = '';
		if ( '1' === $this->get_settings( 'pagination_list' ) ) {
			$text = $type;
		}

		if ( 'next' === $type ) {
			return esc_html( $text ) . ' ' . Icons_Manager::try_get_icon_html( $icon_settings, [ 'aria-hidden' => 'true' ] );
		} else {
			return Icons_Manager::try_get_icon_html( $icon_settings, [ 'aria-hidden' => 'true' ] ) . ' ' . esc_html( $text );
		}
	}

	protected function render_thumbnail( $settings ) {
		$settings['thumbnail_size'] = [
			'id' => get_post_thumbnail_id(),
		];
		// PHPCS - `get_permalink` is safe.
		?>
        <div class="thumb">
            <a href="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php the_title(); ?>">
				<?php Group_Control_Image_Size::print_attachment_image_html( $settings, 'thumbnail_size' ); ?>
            </a>
        </div>
		<?php
	}

	protected function render_thumbnail_video( $settings ) {
		$settings['thumbnail_size'] = [
			'id' => get_post_thumbnail_id(),
		];

		$format = get_post_format();
		?>

        <div class="thumb">
            <a href="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php the_title(); ?>">
				<?php Group_Control_Image_Size::print_attachment_image_html( $settings, 'thumbnail_size' ); ?>
            </a>
        </div>
        <div class="thumb_icon">
			<?php
			if ( 'audio' === $format ) {
				Icons_Manager::render_icon( $settings['post_audio_icon'], [ 'aria-hidden' => 'true' ] );
			} elseif ( 'video' === $format ) {
				Icons_Manager::render_icon( $settings['video_selected_icon'], [ 'aria-hidden' => 'true' ] );
			} elseif ( 'gallery' === $format ) {
				Icons_Manager::render_icon( $settings['post_gallery_icon'], [ 'aria-hidden' => 'true' ] );
			} else {
				Icons_Manager::render_icon( $settings['post_thumb_icon'], [ 'aria-hidden' => 'true' ] );
			}
			?>
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
		<span>
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

	protected function render_author_video() {
		if ( ! $this->get_settings( 'show_author' ) ) {
			return;
		}
		?>
        <div class="author_views">
            <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
                By <?php the_author(); ?>
            </a>
            <span class="posts_views">
				<?php \Elementor\Icons_Manager::render_icon( $this->get_settings( 'view_meta_icon' ) ); ?>
				<?php echo esc_html( get_post_meta( get_the_id(), 'wcf_post_views_count', true ) ); ?>
				<?php echo esc_html__( 'Views', 'animation-addons-for-elementor' ); ?>
			</span>
        </div>
		<?php
	}

	protected function render_author_avatar() {
		if ( ! $this->get_settings( 'show_author' ) ) {
			return;
		}

		if ( '4' !== $this->get_settings( 'element_list' ) ) {
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
        <ul class="wcf-taxonomy">
			<?php
			foreach ( $terms as $term ) {
				printf( '<li><a href="%1$s">%2$s</a></li>',
					esc_url( get_term_link( $term->slug, $taxonomy ) ),
					esc_html( $term->name )
				);
			}
			?>
        </ul>
		<?php
	}

	protected function render_post_meta() {
		?>
        <ul class="wcf-meta">
			<?php
			if ( '4' !== $this->get_settings( 'element_list' ) && $this->get_settings( 'show_author' ) ) {
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

	protected function render_post_meta_video() {
		?>
        <div class="wcf-meta_video">
            <span class="meta_day"><?php echo get_the_date( 'D' ); ?></span>
            <span class="meta_year"><?php echo get_the_date( 'd' ); ?></span>
            <span class="meta_month"><?php echo get_the_date( 'F' ); ?></span>
        </div>
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
        <article <?php post_class( 'wcf-post' ); ?>>
			<?php $this->render_thumbnail( $settings ); ?>

            <div class="content">
				<?php
				$this->render_author_avatar();
				$this->render_post_taxonomy();
				$this->render_post_meta();
				$this->render_title();
				$this->render_excerpt();
				$this->render_read_more();
				?>
            </div>
        </article>
		<?php
	}

	protected function render_post_video( $settings ) {
		?>
        <article <?php post_class( 'wcf-post' ); ?>>
            <div class="post_video_date">
				<?php $this->render_post_meta_video(); ?>
            </div>
            <div class="posts_video_thumb">
				<?php $this->render_thumbnail_video( $settings ); ?>
            </div>
            <div class="content">
				<?php
				$this->render_author_avatar();
				$this->render_post_taxonomy();
				$this->render_title();
				$this->render_author_video();
				$this->render_excerpt();
				$this->render_read_more();
				?>
            </div>
        </article>
		<?php
	}

}
