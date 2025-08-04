<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WCF_ADDONS\WCF_Post_Query_Trait;
use WCF_ADDONS\WCF_Post_Handler_Trait;
use WP_Query;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Posts
 *
 * Elementor widget for Posts.
 *
 * @since 1.0.0
 */
class Feature_Posts extends Widget_Base
{
	use WCF_Post_Query_Trait;
	use WCF_Post_Handler_Trait;

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
	public function get_name()
	{
		return 'wcf--feature-posts';
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
	public function get_title()
	{
		return esc_html__('Featured Posts', 'animation-addons-for-elementor');
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
	public function get_icon()
	{
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
	public function get_categories()
	{
		return ['animation-addons-for-elementor'];
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
	public function get_script_depends()
	{
		return ['swiper', 'wcf--posts'];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['swiper', 'wcf--post-pro'];
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
	protected function register_controls()
	{
		//query
		$this->register_query_controls();

		//settings
		$this->register_settings_controls();

		//banner
		$this->register_banner_controls();

		$this->register_tabs_controls();

		$this->register_grid_controls();

		$this->register_thumbnail_controls();

		//title
		$this->register_title_controls();

		$this->register_taxonomy_controls();

		$this->register_meta_controls();

		$this->register_read_more_controls();

		$this->register_audio_video_play_controls();
	}

	protected function register_settings_controls()
	{
		$this->start_controls_section(
			'section_settings',
			[
				'label' => esc_html__('Settings', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'thumbnail_size',
				'exclude' => ['custom'],
				'default' => 'medium',
			]
		);

		$this->add_control(
			'show_taxonomy',
			[
				'label'     => esc_html__('Show Taxonomy', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'show_meta',
			[
				'label'     => esc_html__('Show Meta', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'show_read_more',
			[
				'label'     => esc_html__('Tabs Read More', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'show_read_more_grid',
			[
				'label'     => esc_html__('Grid Read More', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'no',
			]
		);

		//post format audio/ video
		$this->add_control(
			'post_format_a_v',
			[
				'label'        => esc_html__('Post Audio, Video & Gallery', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_off'    => esc_html__('Off', 'animation-addons-for-elementor'),
				'label_on'     => esc_html__('On', 'animation-addons-for-elementor'),
				'separator'    => 'before',
				'return_value' => 'yes',
			]
		);

		$this->end_controls_section();
	}

	protected function register_banner_controls()
	{

		$this->start_controls_section(
			'section_banner_style',
			[
				'label' => esc_html__('Banner', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'banner_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .post-banner' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		//offset
		$this->add_control(
			'banner_taxonomy_h',
			[
				'label'     => esc_html__('Taxonomy', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'b_taxonomy_offset_x',
			[
				'label'      => esc_html__('Offset X', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
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
				'default'    => [
					'size' => 10,
				],
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'body:not(.rtl) {{WRAPPER}} .post-banner .wcf-post-taxonomy' => 'right: {{SIZE}}{{UNIT}}; left:auto;',
					'body.rtl {{WRAPPER}} .post-banner .wcf-post-taxonomy'       => 'left: {{SIZE}}{{UNIT}}; right: auto;',
				],
			]
		);

		$this->add_responsive_control(
			'b_taxonomy_offset_y',
			[
				'label'      => esc_html__('Offset Y', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
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
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'default'    => [
					'size' => 10,
				],
				'selectors'  => [
					'{{WRAPPER}} .post-banner .wcf-post-taxonomy' => 'top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_tabs_controls()
	{

		$this->start_controls_section(
			'section_tab_style',
			[
				'label' => esc_html__('Tab Area', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'tab_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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
					'{{WRAPPER}} .post-tabs' => '--tabs-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tab_background',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .tabs-wrap',
			]
		);

		$this->add_control(
			'tab_border_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-tabs .wcf-post' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'tab_item_padding',
			[
				'label'      => esc_html__('Item Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .post-tabs .wcf-post' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_grid_controls()
	{

		$this->start_controls_section(
			'section_grid_style',
			[
				'label' => esc_html__('Grid Area', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'grid_border_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-grid .wcf-post:after' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_thumbnail_controls()
	{
		$this->start_controls_section(
			'section_style_post_image',
			[
				'label' => esc_html__('Thumbnail', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'thumb_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'custom'],
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
					'{{WRAPPER}} .post-wrapper .thumb' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'thumb_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .post-wrapper .thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'thumb_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .post-wrapper .thumb' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_title_controls()
	{
		$this->start_controls_section(
			'section_title',
			[
				'label' => esc_html__('Title', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'title_length',
			[
				'label' => esc_html__('Title Length', 'animation-addons-for-elementor'),
				'type'  => Controls_Manager::NUMBER,
				'min'   => 2,
				'max'   => 100,
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'     => esc_html__('Title HTML Tag', 'animation-addons-for-elementor'),
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
			'show_title_highlight',
			[
				'label'              => esc_html__('Show Highlight', 'animation-addons-for-elementor'),
				'type'               => Controls_Manager::SWITCHER,
				'separator'          => 'before',
				'label_on'           => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'          => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value'       => 'yes',
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'highlight_title_length',
			[
				'label'              => esc_html__('Highlight Length', 'animation-addons-for-elementor'),
				'type'               => Controls_Manager::NUMBER,
				'default'            => 5,
				'min'                => 2,
				'max'                => 100,
				'condition'          => [
					'show_title_highlight' => 'yes',
				],
				'frontend_available' => true,
			]
		);

		$this->end_controls_section();

		//style
		$this->start_controls_section(
			'section_title_style',
			[
				'label' => esc_html__('Title', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);


		$this->start_controls_tabs('tabs_title');

		$this->start_controls_tab(
			'tab_title_normal',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .wcf-post-title',
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tab_title_color',
			[
				'label'     => esc_html__('Tab Title Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-tabs .wcf-post-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tab_title_typography',
				'selector' => '{{WRAPPER}} .post-tabs .wcf-post-title',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_tile_hover',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'title_color_hover',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-title:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tab_title_color_hover',
			[
				'label'     => esc_html__('Tab Title Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-tabs .wcf-post-title:hover'            => 'color: {{VALUE}};',
					'{{WRAPPER}} .post-tabs .wcf-post.active .wcf-post-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tab_title_margin',
			[
				'label'      => esc_html__('Tab Title Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .post-tabs .wcf-post-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_highlight',
			[
				'label'     => esc_html__('Highlight', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_title_highlight' => 'yes',
				],
			]
		);

		$this->add_control(
			'title_h_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-title .highlight' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_title_highlight' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'title_h_typography',
				'selector'  => '{{WRAPPER}} .wcf-post-title .highlight',
				'condition' => [
					'show_title_highlight' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_taxonomy_controls()
	{

		$this->start_controls_section(
			'section_taxonomy',
			[
				'label'     => esc_html__('Taxonomy', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'show_taxonomy' => 'yes',
				],
			]
		);

		$this->add_control(
			'post_taxonomy',
			[
				'label'       => esc_html__('Taxonomy', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => 'category',
				'options'     => $this->get_taxonomies(),
			]
		);

		$this->add_control(
			'taxonomy_limit',
			[
				'label'   => esc_html__('Limit', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::NUMBER,
				'min'     => -1,
				'max'     => 5,
				'default' => 1,
			]
		);

		$this->end_controls_section();

		//style
		$this->start_controls_section(
			'section_taxonomy_style',
			[
				'label'     => esc_html__('Taxonomy', 'animation-addons-for-elementor'),
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
				'selector' => '{{WRAPPER}} .wcf-post-taxonomy a',
			]
		);

		$this->add_responsive_control(
			'taxonomy_spacing',
			[
				'label'      => esc_html__('Space Between', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-taxonomy' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs('tabs_taxonomy');

		$this->start_controls_tab(
			'tab_taxonomy_normal',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'tab_taxonomy_color',
			[
				'label'     => esc_html__('Tab Area Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .active .wcf-post-taxonomy a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tab_taxonomy_background',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .active .wcf-post-taxonomy a',
			]
		);

		$this->add_control(
			'taxonomy_color',
			[
				'label'     => esc_html__('Grid Area Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-taxonomy a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'taxonomy_background',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .wcf-post-taxonomy a',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_taxonomy_hover',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'taxonomy_hover_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-taxonomy a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'taxonomy_hover_background',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .wcf-post-taxonomy a:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'taxonomy_border',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .wcf-post-taxonomy a',
			]
		);

		$this->add_responsive_control(
			'taxonomy_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-taxonomy a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'taxonomy_box_shadow',
				'selector' => '{{WRAPPER}} .wcf-post-taxonomy a',
			]
		);

		$this->add_responsive_control(
			'taxonomy_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-taxonomy a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		//offset
		$this->add_responsive_control(
			'taxonomy_offset_x',
			[
				'label'      => esc_html__('Offset X', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
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
				'default'    => [
					'size' => 10,
				],
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'body:not(.rtl) {{WRAPPER}} .wcf-post-taxonomy' => 'left: {{SIZE}}{{UNIT}}',
					'body.rtl {{WRAPPER}} .wcf-post-taxonomy'       => 'right: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'taxonomy_offset_y',
			[
				'label'      => esc_html__('Offset Y', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
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
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'default'    => [
					'size' => 10,
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-taxonomy' => 'top: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_meta_controls()
	{

		$this->start_controls_section(
			'section_meta',
			[
				'label'     => esc_html__('Meta', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'show_meta' => 'yes',
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'post_meta',
			[
				'label'   => esc_html__('Meta', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'comments',
				'options' => [
					'author'   => esc_html__('Author', 'animation-addons-for-elementor'),
					'view'     => esc_html__('View', 'animation-addons-for-elementor'),
					'date'     => esc_html__('Date', 'animation-addons-for-elementor'),
					'time'     => esc_html__('Time', 'animation-addons-for-elementor'),
					'time-ago' => esc_html__('Time Ago', 'animation-addons-for-elementor'),
					'comments' => esc_html__('Comments', 'animation-addons-for-elementor'),
					'reviews'    => esc_html__('Reviews', 'animation-addons-for-elementor'),
					'read-later' => esc_html__('Read Later', 'animation-addons-for-elementor'),
				],
			]
		);

		$repeater->add_control(
			'meta_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'far fa-flag',
					'library' => 'fa-regular',
				],
			]
		);

		$this->add_control(
			'post_meta_data',
			[
				'label'       => esc_html__('Meta Data', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'post_meta' => 'view',
					],
					[
						'post_meta' => 'date',
					],
				],
				'title_field' => '{{{ post_meta }}}',
			]
		);

		$this->add_control(
			'meta_separator',
			[
				'label'     => esc_html__('Separator Between', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::TEXT,
				'default'   => '///',
				'ai'        => [
					'active' => false,
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-post-meta span + span:before' => 'content: "{{VALUE}}"',
				],
				'dynamic'   => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'post_by',
			[
				'label'   => esc_html__('Author By', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__('By', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'show_avatar',
			[
				'label'        => esc_html__('Author Avatar', 'animation-addons-for-elementor'),
				'description'  => esc_html__('If you want to use the author avatar, you must chose "Author" in the meta data.', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'separator'    => 'before',
				'return_value' => 'yes',
			]
		);

		$this->add_responsive_control(
			'avatar_size',
			[
				'label'      => esc_html__('Avatar Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .post-author img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['show_avatar' => 'yes']
			]
		);

		$this->end_controls_section();

		//style
		$this->start_controls_section(
			'section_meta_style',
			[
				'label'     => esc_html__('Meta', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_meta' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'meta_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-meta span + span:before' => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf-post-meta'                    => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'meta_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-meta' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'meta_typography',
				'selector' => '{{WRAPPER}} .wcf-post-meta',
			]
		);

		$this->add_control(
			'tab_meta_color',
			[
				'label'     => esc_html__('Tab Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-tabs .wcf-post-meta' => 'color: {{VALUE}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tabs_meta_typography',
				'selector' => '{{WRAPPER}} .post-tabs .wcf-post-meta',
			]
		);

		$this->add_responsive_control(
			'meta_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-meta' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tab_meta_margin',
			[
				'label'      => esc_html__('Tab Area Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .post-tabs .wcf-post-meta' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'meta_icon',
			[
				'label'      => esc_html__('Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'separator'  => 'before',
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-meta .meta-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'meta_icon_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-meta .meta-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'meta_icon_gap',
			[
				'label'      => esc_html__('Icon Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-meta .meta-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		//style admin
		$this->start_controls_section(
			'section_meta_admin_style',
			[
				'label'     => esc_html__('Meta Admin', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_meta' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'author_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .post-author' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'author_by_heading',
			[
				'label' => esc_html__('Author By', 'animation-addons-for-elementor'),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'admin_by_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-meta .post-by' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'admin_by_typography',
				'selector' => '{{WRAPPER}} .wcf-post-meta .post-by',
			]
		);

		$this->add_control(
			'author_heading',
			[
				'label'     => esc_html__('Author', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'admin_typography',
				'selector' => '{{WRAPPER}} .post-author a',
			]
		);

		$this->start_controls_tabs('tabs_author');

		$this->start_controls_tab(
			'tab_author_normal',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'author_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-author a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_author_hover',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'author_color_hover',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .post-author a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_read_more_controls()
	{

		$this->start_controls_section(
			'section_post_read_more',
			[
				'label'     => esc_html__('Read More', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'show_read_more' => 'yes',
				],
			]
		);

		$this->add_control(
			'read_more_text',
			[
				'label'   => esc_html__('Read More Text', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => esc_html__('Read More', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'selected_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
			]
		);

		$this->add_control(
			'icon_align',
			[
				'label'   => esc_html__('Icon Position', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left'  => esc_html__('Before', 'animation-addons-for-elementor'),
					'right' => esc_html__('After', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'icon_indend',
			[
				'label'     => esc_html__('Icon Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-post-link' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		//style
		$this->start_controls_section(
			'style_post_read_more',
			[
				'label'     => esc_html__('Read More', 'animation-addons-for-elementor'),
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
				'selector' => '{{WRAPPER}} .wcf-post-link',
			]
		);

		$this->add_responsive_control(
			'read_more_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
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
					'{{WRAPPER}} .wcf-post-link i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf-post-link svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'tabs_read_more',
		);

		$this->start_controls_tab(
			'tab_read_more_normal',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'read_more_color',
			[
				'label'     => esc_html__('Text Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .wcf-post-link' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'read_more_background',
				'types'    => ['classic', 'gradient'],
				'exclude'  => ['image'],
				'selector' => '{{WRAPPER}} .wcf-post-link',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_read-more_hover',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'read_more_text_hover_color',
			[
				'label'     => esc_html__('Text Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-link:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'read_more_hover_background',
				'types'    => ['classic', 'gradient'],
				'exclude'  => ['image'],
				'selector' => '{{WRAPPER}} .wcf-post-link:hover',
			]
		);

		$this->add_control(
			'read_more_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-link:hover' => 'border-color: {{VALUE}};',
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
				'selector'  => '{{WRAPPER}} .wcf-post-link',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'read_more_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'read_more_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'read_more_shadow',
				'selector' => '{{WRAPPER}} .wcf-post-link',
			]
		);

		$this->add_responsive_control(
			'read_more_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-link' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_audio_video_play_controls()
	{
		$this->start_controls_section(
			'section_audio_video_play',
			[
				'label'     => esc_html__('Post Popup', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['post_format_a_v' => 'yes']
			]
		);

		$this->add_control(
			'audio_video_play',
			[
				'label'       => esc_html__('Video Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-play-circle',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'audio_icon',
			[
				'label'       => esc_html__('Audio Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-music',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'gallery_icon',
			[
				'label'       => esc_html__('Gallery Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'far fa-images',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_responsive_control(
			'audio_video_play_size',
			[
				'label'      => esc_html__('Play Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'default'    => [
					'unit' => 'px',
					'size' => 50,
				],
				'selectors'  => [
					'{{WRAPPER}} .play' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'audio_video_play_color',
			[
				'label'     => esc_html__('Play Icon Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .play' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'audio_video_play_offset_x',
			[
				'label'      => esc_html__('Offset X', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
				],
				'default'    => [
					'unit' => '%',
					'size' => 50,
				],
				'selectors'  => [
					'{{WRAPPER}} .play' => '--offset-x: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'audio_video_play_offset_y',
			[
				'label'      => esc_html__('Offset Y', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
				],
				'default'    => [
					'unit' => '%',
					'size' => 50,
				],
				'selectors'  => [
					'{{WRAPPER}} .play' => '--offset-y: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_query_controls()
	{
		$this->start_controls_section(
			'section_query',
			[
				'label' => esc_html__('Query', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'post_type',
			[
				'label'   => esc_html__('Source', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => $this->get_public_post_types(),
			]
		);

		$this->add_control(
			'post_sticky_ignore',
			[
				'label'        => esc_html__('Ignore Sticky Posts', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('No', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		//tab query
		$this->add_control(
			'tab_query_options',
			[
				'label'     => esc_html__('Tab Query', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'tab_posts_per_page',
			[
				'label'   => esc_html__('Posts Per Page', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
			]
		);

		$this->start_controls_tabs(
			'post_in_ex_tabs'
		);

		$this->start_controls_tab(
			'query_include',
			[
				'label' => esc_html__('Include', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'include',
			[
				'label'       => esc_html__('Include By', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'terms'   => esc_html__('Term', 'animation-addons-for-elementor'),
					'authors' => esc_html__('Author', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'include_term_ids',
			[
				'label'       => esc_html__('Term', 'animation-addons-for-elementor'),
				'description' => esc_html__('Add coma separated, terms id', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('All', 'animation-addons-for-elementor'),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'include' => 'terms',
				],
			]
		);

		$this->add_control(
			'include_authors',
			[
				'label'       => esc_html__('Author', 'animation-addons-for-elementor'),
				'description' => esc_html__('Add separated, authors ID', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('All', 'animation-addons-for-elementor'),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'include' => 'authors',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'query_exclude',
			[
				'label' => esc_html__('Exclude', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'exclude',
			[
				'label'       => esc_html__('Exclude By', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'terms'   => esc_html__('Term', 'animation-addons-for-elementor'),
					'authors' => esc_html__('Author', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'exclude_term_ids',
			[
				'label'       => esc_html__('Term', 'animation-addons-for-elementor'),
				'description' => esc_html__('Add coma separated, terms id', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('All', 'animation-addons-for-elementor'),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'exclude' => 'terms',
				],
			]
		);

		$this->add_control(
			'exclude_authors',
			[
				'label'       => esc_html__('Author', 'animation-addons-for-elementor'),
				'description' => esc_html__('Add separated, authors ID', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('All', 'animation-addons-for-elementor'),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'exclude' => 'authors',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'post_date',
			[
				'label'   => esc_html__('Date', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'anytime',
				'options' => [
					'anytime'  => esc_html__('All', 'animation-addons-for-elementor'),
					'-1 day'   => esc_html__('Past Day', 'animation-addons-for-elementor'),
					'-1 week'  => esc_html__('Past Week', 'animation-addons-for-elementor'),
					'-1 month' => esc_html__('Past Month', 'animation-addons-for-elementor'),
					'-3 month' => esc_html__('Past Quarter', 'animation-addons-for-elementor'),
					'-1 year'  => esc_html__('Past Year', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'post_order_by',
			[
				'label'   => esc_html__('Order By', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'          => esc_html__('Date', 'animation-addons-for-elementor'),
					'title'         => esc_html__('Title', 'animation-addons-for-elementor'),
					'menu_order'    => esc_html__('Menu Order', 'animation-addons-for-elementor'),
					'modified'      => esc_html__('Last Modified', 'animation-addons-for-elementor'),
					'comment_count' => esc_html__('Comment Count', 'animation-addons-for-elementor'),
					'rand'          => esc_html__('Random', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'post_order',
			[
				'label'   => esc_html__('Order', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'desc',
				'options' => [
					'asc'  => esc_html__('ASC', 'animation-addons-for-elementor'),
					'desc' => esc_html__('DESC', 'animation-addons-for-elementor'),
				],
			]
		);

		//grid query
		$this->add_control(
			'grid_query_options',
			[
				'label'     => esc_html__('Grid Query', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'grid_posts_per_page',
			[
				'label'   => esc_html__('Posts Per Page', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
			]
		);

		$this->start_controls_tabs(
			'post_grid_in_ex_tabs'
		);

		$this->start_controls_tab(
			'grid_query_include',
			[
				'label' => esc_html__('Include', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'grid_include',
			[
				'label'       => esc_html__('Include By', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'terms'   => esc_html__('Term', 'animation-addons-for-elementor'),
					'authors' => esc_html__('Author', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'grid_include_term_ids',
			[
				'label'       => esc_html__('Term', 'animation-addons-for-elementor'),
				'description' => esc_html__('Add coma separated, terms id', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('All', 'animation-addons-for-elementor'),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'grid_include' => 'terms',
				],
			]
		);

		$this->add_control(
			'grid_include_authors',
			[
				'label'       => esc_html__('Author', 'animation-addons-for-elementor'),
				'description' => esc_html__('Add separated, authors ID', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('All', 'animation-addons-for-elementor'),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'grid_include' => 'authors',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'grid_query_exclude',
			[
				'label' => esc_html__('Exclude', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'grid_exclude',
			[
				'label'       => esc_html__('Exclude By', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'terms'   => esc_html__('Term', 'animation-addons-for-elementor'),
					'authors' => esc_html__('Author', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'grid_exclude_term_ids',
			[
				'label'       => esc_html__('Term', 'animation-addons-for-elementor'),
				'description' => esc_html__('Add coma separated, terms id', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('All', 'animation-addons-for-elementor'),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'grid_exclude' => 'terms',
				],
			]
		);

		$this->add_control(
			'grid_exclude_authors',
			[
				'label'       => esc_html__('Author', 'animation-addons-for-elementor'),
				'description' => esc_html__('Add separated, authors ID', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('All', 'animation-addons-for-elementor'),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'grid_exclude' => 'authors',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'grid_post_date',
			[
				'label'   => esc_html__('Date', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'anytime',
				'options' => [
					'anytime'  => esc_html__('All', 'animation-addons-for-elementor'),
					'-1 day'   => esc_html__('Past Day', 'animation-addons-for-elementor'),
					'-1 week'  => esc_html__('Past Week', 'animation-addons-for-elementor'),
					'-1 month' => esc_html__('Past Month', 'animation-addons-for-elementor'),
					'-3 month' => esc_html__('Past Quarter', 'animation-addons-for-elementor'),
					'-1 year'  => esc_html__('Past Year', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'grid_post_order_by',
			[
				'label'   => esc_html__('Order By', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'          => esc_html__('Date', 'animation-addons-for-elementor'),
					'title'         => esc_html__('Title', 'animation-addons-for-elementor'),
					'menu_order'    => esc_html__('Menu Order', 'animation-addons-for-elementor'),
					'modified'      => esc_html__('Last Modified', 'animation-addons-for-elementor'),
					'comment_count' => esc_html__('Comment Count', 'animation-addons-for-elementor'),
					'rand'          => esc_html__('Random', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'grid_post_order',
			[
				'label'   => esc_html__('Order', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'desc',
				'options' => [
					'asc'  => esc_html__('ASC', 'animation-addons-for-elementor'),
					'desc' => esc_html__('DESC', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->end_controls_section();
	}

	protected function query1_arg()
	{

		$query_args = [
			'post_type'           => $this->get_settings('post_type'),
			'posts_per_page'      => $this->get_settings('tab_posts_per_page'),
			'ignore_sticky_posts' => empty($this->get_settings('post_sticky_ignore')) ? false : true,
			'paged'               => $this->get_current_page(),
			'order'               => $this->get_settings('post_order'),
			'orderby'             => $this->get_settings('post_order_by'),
		];

		if ('anytime' !== $this->get_settings('post_date')) {
			$query_args['date_query'] = ['after' => $this->get_settings('post_date')];
		}

		if (! empty($this->get_settings('include'))) {
			if (in_array('terms', $this->get_settings('include'))) {
				$query_args['tax_query'] = [];

				if (! empty($this->get_settings('include_term_ids'))) {
					$terms = [];

					foreach (explode(',', $this->get_settings('include_term_ids')) as $id) {
						$term_data = get_term_by('term_taxonomy_id', $id);

						if (! $term_data) {
							continue;
						}

						$taxonomy             = $term_data->taxonomy;
						$terms[$taxonomy][] = $id;
					}
					foreach ($terms as $taxonomy => $ids) {
						$query = [
							'taxonomy' => $taxonomy,
							'field'    => 'term_taxonomy_id',
							'terms'    => $ids,
						];

						$query_args['tax_query'][] = $query;
					}
				}
			}

			if (! empty($this->get_settings('include_authors'))) {
				$query_args['author__in'] = explode(',', $this->get_settings('include_authors'));
			}
		}

		if (! empty($this->get_settings('exclude'))) {
			if (in_array('terms', $this->get_settings('exclude'))) {
				$query_args['tax_query']['relation'] = 'AND';

				if (! empty($this->get_settings('exclude_term_ids'))) {
					$terms = [];

					foreach (explode(',', $this->get_settings('exclude_term_ids')) as $id) {
						$term_data = get_term_by('term_taxonomy_id', $id);
						if (! $term_data) {
							continue;
						}

						$taxonomy             = $term_data->taxonomy;
						$terms[$taxonomy][] = $id;
					}
					foreach ($terms as $taxonomy => $ids) {
						$query = [
							'taxonomy' => $taxonomy,
							'field'    => 'term_taxonomy_id',
							'terms'    => $ids,
							'operator' => 'NOT IN',
						];

						$query_args['tax_query'][] = $query;
					}
				}
			}

			if (! empty($this->get_settings('exclude_authors'))) {
				$query_args['author__not_in'] = explode(',', $this->get_settings('exclude_authors'));
			}
		}

		return $query_args;
	}

	protected function query2_arg()
	{

		$query_args = [
			'post_type'           => $this->get_settings('post_type'),
			'posts_per_page'      => $this->get_settings('grid_posts_per_page'),
			'ignore_sticky_posts' => empty($this->get_settings('post_sticky_ignore')) ? false : true,
			'paged'               => $this->get_current_page(),
			'order'               => $this->get_settings('grid_post_order'),
			'orderby'             => $this->get_settings('grid_post_order_by'),
		];

		if ('anytime' !== $this->get_settings('grid_post_date')) {
			$query_args['date_query'] = ['after' => $this->get_settings('grid_post_date')];
		}

		if (! empty($this->get_settings('grid_include'))) {
			if (in_array('terms', $this->get_settings('grid_include'))) {
				$query_args['tax_query'] = [];

				if (! empty($this->get_settings('grid_include_term_ids'))) {
					$terms = [];

					foreach (explode(',', $this->get_settings('grid_include_term_ids')) as $id) {
						$term_data = get_term_by('term_taxonomy_id', $id);

						if (! $term_data) {
							continue;
						}

						$taxonomy             = $term_data->taxonomy;
						$terms[$taxonomy][] = $id;
					}
					foreach ($terms as $taxonomy => $ids) {
						$query = [
							'taxonomy' => $taxonomy,
							'field'    => 'term_taxonomy_id',
							'terms'    => $ids,
						];

						$query_args['tax_query'][] = $query;
					}
				}
			}

			if (! empty($this->get_settings('grid_include_authors'))) {
				$query_args['author__in'] = explode(',', $this->get_settings('grid_include_authors'));
			}
		}

		if (! empty($this->get_settings('grid_exclude'))) {
			if (in_array('terms', $this->get_settings('grid_exclude'))) {
				$query_args['tax_query']['relation'] = 'AND';

				if (! empty($this->get_settings('grid_exclude_term_ids'))) {
					$terms = [];

					foreach (explode(',', $this->get_settings('grid_exclude_term_ids')) as $id) {
						$term_data = get_term_by('term_taxonomy_id', $id);
						if (! $term_data) {
							continue;
						}

						$taxonomy             = $term_data->taxonomy;
						$terms[$taxonomy][] = $id;
					}
					foreach ($terms as $taxonomy => $ids) {
						$query = [
							'taxonomy' => $taxonomy,
							'field'    => 'term_taxonomy_id',
							'terms'    => $ids,
							'operator' => 'NOT IN',
						];

						$query_args['tax_query'][] = $query;
					}
				}
			}

			if (! empty($this->get_settings('grid_exclude_authors'))) {
				$query_args['author__not_in'] = explode(',', $this->get_settings('grid_exclude_authors'));
			}
		}

		return $query_args;
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
	protected function render()
	{
		$settings = $this->get_settings_for_display();
		//wrapper class
		$this->add_render_attribute('wrapper', 'class', ['wcf__feature-posts']);
?>
		<div <?php $this->print_render_attribute_string('wrapper'); ?>>
			<div class="post-banner"></div>
			<div class="post-wrapper">
				<?php $this->render_tab_posts(); ?>
				<?php $this->render_grid_posts(); ?>
			</div>
		</div>
	<?php
	}

	protected function render_tab_posts()
	{
		$settings = $this->get_settings_for_display();

		$query1 = new WP_Query($this->query1_arg());

		if (! $query1->found_posts) {
			return;
		}

	?>
		<div class="post-tabs">
			<div class="tabs-wrap">
				<?php
				while ($query1->have_posts()) {
					$query1->the_post();
					$this->render_tab_post($settings);
				} ?>
			</div>
		</div>
	<?php

		wp_reset_postdata();
	}

	protected function render_tab_post($settings)
	{
	?>
		<article class="wcf-post" data-id="<?php echo esc_attr(get_the_ID()); ?>">
			<?php
			$this->render_thumbnail($settings);
			$this->render_title();
			$this->render_meta_data();
			$this->render_read_more_feature();
			?>
		</article>
	<?php
	}

	protected function render_grid_posts()
	{
		$settings = $this->get_settings_for_display();

		$query2 = new WP_Query($this->query2_arg());

		if (! $query2->found_posts) {
			return;
		}

	?>
		<div class="post-grid">
			<?php
			while ($query2->have_posts()) {
				$query2->the_post();
				$this->render_grid_post($settings);
			}
			?>
		</div>
	<?php

		wp_reset_postdata();
	}

	protected function render_grid_post($settings)
	{
	?>
		<article class="wcf-post">
			<?php
			$this->render_thumbnail($settings);
			$this->render_title();
			$this->render_meta_data();
			if ($settings['show_read_more_grid'] == 'yes'):
				$this->render_read_more();
			endif;
			?>
		</article>
	<?php
	}

	protected function render_title()
	{
		$tag = $this->get_settings('title_tag');
	?>
		<<?php Utils::print_validated_html_tag($tag); ?> class="wcf-post-title">
			<a href="<?php echo esc_url(get_the_permalink()); ?>">
				<?php
				global $post;
				// Force the manually-generated Excerpt length as well if the user chose to enable 'apply_to_custom_excerpt'.
				if (! empty($post->post_title)) {
					$max_length = (int) $this->get_settings('title_length');
					$title      = $this->trim_words(get_the_title(), $max_length);
					echo esc_html($title);
				} else {
					the_title();
				}
				?>
			</a>
		</<?php Utils::print_validated_html_tag($tag); ?>>
	<?php
	}

	protected function render_thumbnail($settings)
	{
		$settings['thumbnail_size'] = [
			'id' => get_post_thumbnail_id(),
		];
	?>
		<div class="thumb" data-target="<?php echo esc_attr(get_the_ID()); ?>">
			<?php $this->render_post_taxonomy(); ?>
			<?php $this->render_audio_video_play_icon(); ?>
			<?php Group_Control_Image_Size::print_attachment_image_html($settings, 'thumbnail_size'); ?>
		</div>
<?php
	}
}
