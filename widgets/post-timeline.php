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
class Post_Timeline extends Widget_Base
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
		return 'wcf--posts-timeline';
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
		return esc_html__('Post Timeline', 'animation-addons-for-elementor');
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
		return ['weal-coder-addon'];
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
		return ['wcf--posts'];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['aae-post-timeline', 'wcf--post-pro'];
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

		//layout
		$this->register_layout_controls();

		// Timeline Style
		$this->style_post_timeline();

		//settings
		$this->register_settings_controls();

		//Thumbnail style
		$this->register_thumbnail_controls();

		// Content style
		$this->register_content_wrap_controls();

		//title
		$this->register_title_controls();

		//excerpt
		$this->register_excerpt_controls();

		$this->register_taxonomy_controls();

		$this->register_meta_controls();

		//read more
		$this->register_read_more_controls();

		// Rating
		$this->register_post_rating_controls();


		//pagination
		$this->register_pagination_controls();

		//audio video play
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

		$this->add_control(
			'posts_per_page',
			[
				'label'   => esc_html__('Posts Per Page', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6,
			]
		);

		$this->add_control(
			'show_thumb',
			[
				'label'     => esc_html__('Show Thumb', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_off' => esc_html__('Off', 'animation-addons-for-elementor'),
				'label_on'  => esc_html__('On', 'animation-addons-for-elementor'),
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'thumbnail_size',
				'exclude'   => ['custom'],
				'default'   => 'medium',
				'condition' => [
					'show_thumb' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_title',
			[
				'label'     => esc_html__('Show Title', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_off' => esc_html__('Off', 'animation-addons-for-elementor'),
				'label_on'  => esc_html__('On', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'show_excerpt',
			[
				'label'     => esc_html__('Show Excerpt', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
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
			'show_rating',
			[
				'label'     => esc_html__('Show Rating', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'no',
			]
		);

		$this->add_control(
			'show_read_more',
			[
				'label'     => esc_html__('Read More', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);

		// Post format audio/video
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

	protected function style_post_timeline()
	{
		$this->start_controls_section(
			'style_post_tl',
			[
				'label' => esc_html__('Timeline', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'tl_dot_color',
			[
				'label'     => esc_html__('Dot Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tl-date::before' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'tl_dot_size',
			[
				'label'      => esc_html__('Dot Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .tl-date::before' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tl_dot_otl_color',
			[
				'label'     => esc_html__('Outline Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tl-date::before' => 'outline-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'tl_dot_otl_width',
			[
				'label'      => esc_html__('Outline Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .tl-date::before' => 'outline-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tl_dot_position',
			[
				'label'        => esc_html__('Position', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label_off'    => esc_html__('Default', 'animation-addons-for-elementor'),
				'label_on'     => esc_html__('Custom', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->start_popover();

		$this->add_responsive_control(
			'tl_dot_left',
			[
				'label'      => esc_html__('Left', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .tl-date::before' => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_popover();

		$this->add_control(
			'tl_month_line',
			[
				'label'     => esc_html__('Line Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-posts::before' => 'background-color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'tl_month_line_width',
			[
				'label'      => esc_html__('Line Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 20,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-posts::before' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tl_line_position',
			[
				'label'        => esc_html__('Position', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label_off'    => esc_html__('Default', 'animation-addons-for-elementor'),
				'label_on'     => esc_html__('Custom', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->start_popover();

		$this->add_responsive_control(
			'tl_line_left',
			[
				'label'      => esc_html__('Left', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-posts::before' => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_popover();

		// Timeline Month
		$this->add_control(
			'tl_month_heading',
			[
				'label'     => esc_html__('Month', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'tl_month_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tl-month' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tl_month_typo',
				'selector' => '{{WRAPPER}} .tl-month',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tl_month_bg',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .tl-month, {{WRAPPER}} .tl-month::after',
			]
		);

		$this->add_control(
			'tl_month_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .tl-month' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tl_month_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .tl-month' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Timeline Day
		$this->add_control(
			'tl_date_heading',
			[
				'label'     => esc_html__('Day', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'tl_date_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tl-date' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tl_date_typo',
				'selector' => '{{WRAPPER}} .tl-date',
			]
		);

		$this->add_control(
			'tl_date_b_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tl-date span' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'tl_day_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .tl-date' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tl_day_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .tl-date' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

	protected function register_layout_controls()
	{
		$this->start_controls_section(
			'section_layout',
			[
				'label' => esc_html__('Layout', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		// Image selector
		$this->add_control(
			'post_layout',
			[
				'label'   => esc_html__('Layout', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'layout-normal'  => [
						'title' => esc_html__('Normal', 'animation-addons-for-elementor'),
						'url'   => WCF_ADDONS_URL . '/assets/image/post-layout-2.jpg',
					],
					'layout-aside'   => [
						'title' => esc_html__('Aside', 'animation-addons-for-elementor'),
						'url'   => WCF_ADDONS_URL . '/assets/image/post-layout-1.jpg',
					],
					'layout-overlay' => [
						'title' => esc_html__('Overlay', 'animation-addons-for-elementor'),
						'url'   => WCF_ADDONS_URL . '/assets/image/post-layout-3.jpg',
					],
				],
				'default' => 'layout-normal',
			]
		);

		$this->add_responsive_control(
			'layout-aside-position',
			[
				'label'          => esc_html__('Aside Position', 'animation-addons-for-elementor'),
				'type'           => Controls_Manager::CHOOSE,
				'toggle'         => false,
				'default'        => 'row',
				'mobile_default' => 'column',
				'options'        => [
					'row'         => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-left',
					],
					'column'      => [
						'title' => esc_html__('Top', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-v-align-top',
					],
					'row-reverse' => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'condition'      => [
					'post_layout' => 'layout-aside',
				],
				'selectors'      => [
					'{{WRAPPER}} .wcf-post' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		//layout overlay/aside
		$layout_one = new Repeater();

		$layout_one->add_control(
			'post_item',
			[
				'label'   => esc_html__('Item', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'title',
				'options' => [
					'title'     => esc_html__('Title', 'animation-addons-for-elementor'),
					'taxonomy'  => esc_html__('Taxonomy', 'animation-addons-for-elementor'),
					'excerpt'   => esc_html__('Excerpt', 'animation-addons-for-elementor'),
					'meta'      => esc_html__('Meta', 'animation-addons-for-elementor'),
					'rating'    => esc_html__('Rating', 'animation-addons-for-elementor'),
					'read_more' => esc_html__('Read More', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'post_layout_one',
			[
				'label'        => esc_html__('Layout Aside/Overlay', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::REPEATER,
				'fields'       => $layout_one->get_controls(),
				'item_actions' => [
					'add'       => false,
					'duplicate' => false,
					'remove'    => false,
					'sort'      => true,
				],
				'default'      => [
					[
						'post_item' => 'rating',
					],
					[
						'post_item' => 'taxonomy',
					],
					[
						'post_item' => 'title',
					],
					[
						'post_item' => 'excerpt',
					],
					[
						'post_item' => 'meta',
					],
					[
						'post_item' => 'read_more',
					],
				],
				'title_field'  => '{{{ post_item }}}',
				'condition'    => ['post_layout' => ['layout-aside', 'layout-overlay']]
			]
		);

		//layout normal
		$layout_two = new Repeater();

		$layout_two->add_control(
			'post_item',
			[
				'label'   => esc_html__('Item', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'title',
				'options' => [
					'thumb'     => esc_html__('Thumbnail', 'animation-addons-for-elementor'),
					'title'     => esc_html__('Title', 'animation-addons-for-elementor'),
					'taxonomy'  => esc_html__('Taxonomy', 'animation-addons-for-elementor'),
					'excerpt'   => esc_html__('Excerpt', 'animation-addons-for-elementor'),
					'meta'      => esc_html__('Meta', 'animation-addons-for-elementor'),
					'rating'    => esc_html__('Rating', 'animation-addons-for-elementor'),
					'read_more' => esc_html__('Read More', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'post_layout_two',
			[
				'label'        => esc_html__('Layout Normal', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::REPEATER,
				'fields'       => $layout_two->get_controls(),
				'item_actions' => [
					'add'       => false,
					'duplicate' => false,
					'remove'    => false,
					'sort'      => true,
				],
				'default'      => [
					[
						'post_item' => 'rating',
					],
					[
						'post_item' => 'taxonomy',
					],
					[
						'post_item' => 'thumb',
					],
					[
						'post_item' => 'title',
					],
					[
						'post_item' => 'excerpt',
					],
					[
						'post_item' => 'meta',
					],
					[
						'post_item' => 'read_more',
					],
				],
				'title_field'  => '{{{ post_item }}}',
				'condition'    => ['post_layout' => ['layout-normal']]
			]
		);


		$this->end_controls_section();

		//layout style
		$this->register_layout_style_controls();
	}

	protected function register_layout_style_controls()
	{
		$this->start_controls_section(
			'section_design_layout',
			[
				'label' => esc_html__('Layout', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'row_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem', 'custom'],
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

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'post_box_shadow',
				'selector' => '{{WRAPPER}} .wcf-post',
			]
		);

		$this->add_responsive_control(
			'post_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'post_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tl_post_bg',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .wcf-post',
			]
		);

		//hover effect
		$this->add_control(
			'el_hover_effects',
			[
				'label'        => esc_html__('Hover Effect', 'animation-addons-for-elementor'),
				'description'  => esc_html__('This effect will work only on image tags.', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'effect-zoom-in',
				'options'      => [
					''                => esc_html__('None', 'animation-addons-for-elementor'),
					'effect-zoom-in'  => esc_html__('Zoom In', 'animation-addons-for-elementor'),
					'effect-zoom-out' => esc_html__('Zoom Out', 'animation-addons-for-elementor'),
					'left-move'       => esc_html__('Left Move', 'animation-addons-for-elementor'),
					'right-move'      => esc_html__('Right Move', 'animation-addons-for-elementor'),
				],
				'prefix_class' => 'wcf--image-',
			]
		);

		$this->add_responsive_control(
			'alignment',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'default'   => 'start',
				'options'   => [
					'start'  => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-post'          => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .wcf-post-taxonomy' => 'align-self: {{VALUE}};',
					'{{WRAPPER}} .wcf-post-meta'     => 'align-self: {{VALUE}};',
					'{{WRAPPER}} .wcf-post-link'     => 'align-self: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tl_layout_shadow',
			[
				'label' => esc_html__('Layout Shadow', 'animation-addons-for-elementor'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tl_posts_bg',
				'types'    => ['gradient'],
				'selector' => '{{WRAPPER}} .aae--post-time::after',
			]
		);

		$this->end_controls_section();
	}

	protected function register_thumbnail_controls()
	{
		$this->start_controls_section(
			'section_style_post_image',
			[
				'label'     => esc_html__('Thumbnail', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_thumb'   => 'yes',
					'post_layout!' => 'layout-overlay',
				],
			]
		);

		$this->add_responsive_control(
			'thumb_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'custom'],
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
					'{{WRAPPER}} .thumb' => 'width: {{SIZE}}{{UNIT}};',
				],
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
					'{{WRAPPER}} .thumb' => 'height: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .thumb' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_content_wrap_controls()
	{
		$this->start_controls_section(
			'section_style_post_content',
			[
				'label'     => esc_html__('Content', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['post_layout!' => 'layout-normal']
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'content_background',
				'types'     => ['classic', 'gradient'],
				'exclude'   => ['image'],
				'selector'  => '{{WRAPPER}} .content',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'label'     => esc_html__('Title', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'show_title' => 'yes',
				],
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
				'label'     => esc_html__('Title', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_title' => 'yes',
				],
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

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_tile_hover',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'title_hover_style',
			[
				'label'        => esc_html__('Hover Type', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SELECT,
				'prefix_class' => 'wcf--title-',
				'default'      => '',
				'options'      => [
					''          => esc_html__('Default', 'animation-addons-for-elementor'),
					'underline' => esc_html__('Underline', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'title_typography_hover',
				'selector'  => '{{WRAPPER}} .wcf-post-title:hover',
				'condition' => ['title_hover_style' => ''],
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
			'title_under_color',
			[
				'label'     => esc_html__('Underline Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-title a' => '--underline-color: {{VALUE}};',
				],
				'condition' => ['title_hover_style' => 'underline'],
			]
		);

		$this->add_responsive_control(
			'title_under_thickness',
			[
				'label'      => esc_html__('Thickness', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-title a' => '--underline-thickness: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['title_hover_style' => 'underline'],
			]
		);

		$this->add_control(
			'title_under_transition',
			[
				'label'     => esc_html__('Transition', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3,
				'step'      => 0.1,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-title a' => '--underline-transition:  {{VALUE}}s;',
				],
				'condition' => ['title_hover_style' => 'underline'],
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

	protected function register_excerpt_controls()
	{
		$this->start_controls_section(
			'section_post_excerpt',
			[
				'label'     => esc_html__('Excerpt', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'show_excerpt' => 'yes',
				],
			]
		);

		$this->add_control(
			'excerpt_length',
			[
				'label'     => esc_html__('Excerpt Length', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 5,
				'max'       => 100,
				'default'   => 30,
				'condition' => [
					'show_excerpt' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		//style
		$this->start_controls_section(
			'section_style_post_excerpt',
			[
				'label'     => esc_html__('Excerpt', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_excerpt' => 'yes',
				],
			]
		);

		$this->add_control(
			'excerpt_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-excerpt' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'excerpt_typography',
				'selector' => '{{WRAPPER}} .wcf-post-excerpt',
			]
		);

		$this->add_responsive_control(
			'excerpt_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-excerpt' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
			'taxonomy_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
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
			'tax_hover_style',
			[
				'label'        => esc_html__('Hover Type', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SELECT,
				'prefix_class' => 'wcf--taxonomy-',
				'default'      => '',
				'options'      => [
					''          => esc_html__('Default', 'animation-addons-for-elementor'),
					'underline' => esc_html__('Underline', 'animation-addons-for-elementor'),
				],
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
				'condition' => ['tax_hover_style' => ''],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'taxonomy_hover_background',
				'types'     => ['classic', 'gradient'],
				'selector'  => '{{WRAPPER}} .wcf-post-taxonomy a:hover',
				'condition' => ['tax_hover_style' => ''],
			]
		);

		$this->add_control(
			'tax_under_color',
			[
				'label'     => esc_html__('Underline Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-taxonomy a' => '--tax-ul-color: {{VALUE}};',
				],
				'condition' => ['tax_hover_style' => 'underline'],
			]
		);

		$this->add_responsive_control(
			'tax_under_thickness',
			[
				'label'      => esc_html__('Thickness', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-taxonomy a' => '--tax-ul-thickness: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['tax_hover_style' => 'underline'],
			]
		);

		$this->add_responsive_control(
			'tax_under_btm',
			[
				'label'      => esc_html__('Position', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => -10,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-taxonomy a' => '--tax-btm-position: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['tax_hover_style' => 'underline'],
			]
		);

		$this->add_control(
			'tax_under_transition',
			[
				'label'     => esc_html__('Transition', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3,
				'step'      => 0.1,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-taxonomy a' => '--tax-ul-transition:  {{VALUE}}s;',
				],
				'condition' => ['tax_hover_style' => 'underline'],
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
		$this->add_control(
			'taxonomy_position',
			[
				'label'     => esc_html__('Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''         => esc_html__('Default', 'animation-addons-for-elementor'),
					'absolute' => esc_html__('Absolute', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-post-taxonomy' => 'position: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'taxonomy_offset_x',
			[
				'label'      => esc_html__('Offset X', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => -1000,
						'max' => 1000,
					],
					'%'  => [
						'min' => -200,
						'max' => 200,
					],
					'vw' => [
						'min' => -200,
						'max' => 200,
					],
					'vh' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'default'    => [
					'size' => 0,
				],
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'vh', 'custom'],
				'selectors'  => [
					'body:not(.rtl) {{WRAPPER}} .wcf-post-taxonomy' => 'left: {{SIZE}}{{UNIT}}',
					'body.rtl {{WRAPPER}} .wcf-post-taxonomy'       => 'right: {{SIZE}}{{UNIT}}',
				],
				'condition'  => ['taxonomy_position!' => ''],
			]
		);

		$this->add_responsive_control(
			'taxonomy_offset_y',
			[
				'label'      => esc_html__('Offset Y', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => -1000,
						'max' => 1000,
					],
					'%'  => [
						'min' => -200,
						'max' => 200,
					],
					'vh' => [
						'min' => -200,
						'max' => 200,
					],
					'vw' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'vw', 'custom'],
				'default'    => [
					'size' => 0,
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-taxonomy' => 'top: {{SIZE}}{{UNIT}}',
				],
				'condition'  => ['taxonomy_position!' => '',],
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
					'author'     => esc_html__('Author', 'animation-addons-for-elementor'),
					'view'       => esc_html__('View', 'animation-addons-for-elementor'),
					'date'       => esc_html__('Date', 'animation-addons-for-elementor'),
					'time'       => esc_html__('Time', 'animation-addons-for-elementor'),
					'time-ago'   => esc_html__('Time Ago', 'animation-addons-for-elementor'),
					'comments'   => esc_html__('Comments', 'animation-addons-for-elementor'),
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

	protected function register_pagination_controls()
	{
		$this->start_controls_section(
			'section_pagination',
			[
				'label' => esc_html__('Pagination', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'pagination_type',
			[
				'label'   => esc_html__('Pagination', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''                      => esc_html__('None', 'animation-addons-for-elementor'),
					'numbers'               => esc_html__('Numbers', 'animation-addons-for-elementor'),
					'prev_next'             => esc_html__('Previous/Next', 'animation-addons-for-elementor'),
					'numbers_and_prev_next' => esc_html__('Numbers', 'animation-addons-for-elementor') . ' + ' . esc_html__('Previous/Next', 'animation-addons-for-elementor'),
					'load_on_click'         => esc_html__('Load On Click', 'animation-addons-for-elementor'),
					'infinite_scroll'       => esc_html__('Infinite Scroll', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'pagination_page_limit',
			[
				'label'     => esc_html__('Page Limit', 'animation-addons-for-elementor'),
				'default'   => '5',
				'condition' => [
					'pagination_type' => ['numbers_and_prev_next', 'numbers', 'prev_next'],
				],
			]
		);

		$this->add_control(
			'pagination_numbers_shorten',
			[
				'label'     => esc_html__('Shorten', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => [
					'pagination_type' => ['numbers_and_prev_next', 'numbers'],
				],
			]
		);

		$this->add_control(
			'navigation_prev_icon',
			[
				'label'         => esc_html__('Previous', 'animation-addons-for-elementor'),
				'type'          => Controls_Manager::ICONS,
				'skin'          => 'inline',
				'label_block'   => false,
				'skin_settings' => [
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
				'recommended'   => [
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
				'condition'     => [
					'pagination_type' => ['prev_next', 'numbers_and_prev_next'],
				],
			]
		);

		$this->add_control(
			'navigation_next_icon',
			[
				'label'         => esc_html__('Next', 'animation-addons-for-elementor'),
				'type'          => Controls_Manager::ICONS,
				'skin'          => 'inline',
				'label_block'   => false,
				'skin_settings' => [
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
				'recommended'   => [
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
				'condition'     => [
					'pagination_type' => ['prev_next', 'numbers_and_prev_next'],
				],
			]
		);

		$this->add_responsive_control(
			'pagination_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'options'   => [
					'start'  => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .wcf-post-pagination' => 'justify-content: {{VALUE}};',
					'{{WRAPPER}} .wcf-post-load-more'  => 'align-self: {{VALUE}};',
				],
				'condition' => [
					'pagination_type!' => ['', 'infinite_scroll'],
				],
			]
		);

		$this->add_responsive_control(
			'pagination_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-pagination' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf-post-load-more'  => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => [
					'pagination_type!' => ['', 'infinite_scroll'],
				],
			]
		);

		$this->end_controls_section();

		// Pagination style controls for prev/next and numbers pagination.
		$this->start_controls_section(
			'section_pagination_style',
			[
				'label'     => esc_html__('Pagination', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'pagination_type' => ['numbers_and_prev_next', 'numbers', 'prev_next'],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pagination_typography',
				'selector' => '{{WRAPPER}} .wcf-post-pagination .page-numbers',
			]
		);

		$this->add_responsive_control(
			'pagination_spacing',
			[
				'label'      => esc_html__('Space Between', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem', 'custom'],
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
					'{{WRAPPER}} .wcf-post-pagination' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs('tab_pagination');

		$this->start_controls_tab(
			'tab_pagination_normal',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'pagination_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-pagination .page-numbers' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'     => 'nabackground',
				'types'    => ['classic'],
				'selector' => '{{WRAPPER}} .wcf-post-pagination .page-numbers',
			]
		);

		$this->add_responsive_control(
			'pagination_number_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-pagination .page-numbers' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'border',
				'selector' => '{{WRAPPER}} .wcf-post-pagination .page-numbers',
			]
		);

		$this->add_control(
			'pagination_number_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-pagination .page-numbers' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_pagination_color_hover',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'pagination_hover_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-pagination .page-numbers:not(.dots):hover, {{WRAPPER}} .wcf-post-pagination .page-numbers.current' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_hover_bg_color',
			[
				'label'     => esc_html__('Background', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-pagination .page-numbers:not(.dots):hover, {{WRAPPER}} .wcf-post-pagination .page-numbers.current' => 'background: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->register_load_more_controls();
	}

	protected function register_load_more_controls()
	{
		$this->start_controls_section(
			'section_load_more',
			[
				'label'     => esc_html__('Load More', 'animation-addons-for-elementor'),
				'condition' => [
					'pagination_type' => [
						'load_on_click',
						'infinite_scroll',
					],
				],
			]
		);

		$this->add_control(
			'load_more_spinner',
			[
				'label'                  => esc_html__('Spinner', 'animation-addons-for-elementor'),
				'type'                   => Controls_Manager::ICONS,
				'default'                => [
					'value'   => 'fas fa-spinner',
					'library' => 'fa-solid',
				],
				'exclude_inline_options' => ['svg'],
				'recommended'            => [
					'fa-solid' => [
						'spinner',
						'cog',
						'sync',
						'sync-alt',
						'asterisk',
						'circle-notch',
					],
				],
				'skin'                   => 'inline',
				'label_block'            => false,
			]
		);

		$this->add_responsive_control(
			'load_more_spinner_size',
			[
				'label'      => esc_html__('Spinner Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .load-more-spinner' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_load_more_button',
			[
				'label'     => esc_html__('Button', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'pagination_type' => 'load_on_click',
				],
			]
		);

		$this->add_control(
			'load_more_btn_text',
			[
				'label'       => esc_html__('Text', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Load More', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Load More', 'animation-addons-for-elementor'),
				'condition'   => [
					'pagination_type' => 'load_on_click',
				],
			]
		);

		$this->add_control(
			'load_more_btn_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'pagination_type' => 'load_on_click',
				],
			]
		);

		$this->add_control(
			'btn_icon_position',
			[
				'label'     => esc_html__('Icon Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'row',
				'options'   => [
					'row'         => esc_html__('After', 'animation-addons-for-elementor'),
					'row-reverse' => esc_html__('Before', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .aae--post-time .wcf-post-load-more .load-more-text' => 'flex-direction: {{VALUE}};',
				],
				'condition' => [
					'pagination_type' => 'load_on_click',
				],
			]
		);

		$this->end_controls_section();

		//style
		$this->start_controls_section(
			'section_load_more_style',
			[
				'label'     => esc_html__('Load More', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'pagination_type' => ['load_on_click'],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'load_more_typography',
				'selector' => '{{WRAPPER}} .wcf-post-load-more',
			]
		);

		$this->add_responsive_control(
			'load_more_icon_size',
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
					'{{WRAPPER}} .load-more-text i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .load-more-text svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'tabs_load_more',
		);

		$this->start_controls_tab(
			'tab_load_more_normal',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'load_more_color',
			[
				'label'     => esc_html__('Text Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .wcf-post-load-more' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'load_more_background',
				'types'    => ['classic', 'gradient'],
				'exclude'  => ['image'],
				'selector' => '{{WRAPPER}} .wcf-post-load-more',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_load_more_hover',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'load_more_text_hover_color',
			[
				'label'     => esc_html__('Text Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-load-more:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'load_more_hover_background',
				'types'    => ['classic', 'gradient'],
				'exclude'  => ['image'],
				'selector' => '{{WRAPPER}} .wcf-post-load-more:hover',
			]
		);

		$this->add_control(
			'load_more_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-post-load-more:hover' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'load_more_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'load_more_border',
				'selector'  => '{{WRAPPER}} .wcf-post-load-more',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'load_more_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-load-more' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'load_more_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-load-more' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'load_more_shadow',
				'selector' => '{{WRAPPER}} .wcf-post-load-more',
			]
		);

		$this->add_responsive_control(
			'load_more_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-post-load-more' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	public function get_current_page()
	{
		if ('' === $this->get_settings_for_display('pagination_type')) {
			return 1;
		}

		return max(1, get_query_var('paged'), get_query_var('page'));
	}

	protected function register_post_rating_controls()
	{
		$this->start_controls_section(
			'section_post_rating',
			[
				'label'     => esc_html__('Rating', 'animation-addons-for-elementor'),
				'condition' => ['show_rating' => 'yes'],
			]
		);

		$this->add_control(
			'post_rating_style',
			[
				'label'   => esc_html__('Style', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default' => esc_html__('Default', 'animation-addons-for-elementor'),
					'circle'  => esc_html__('Circle', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'rating_icon',
			[
				'label'       => esc_html__('Rating Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				],
			]
		);

		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'style_post_rating',
			[
				'label'     => esc_html__('Rating', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['show_rating' => 'yes'],
			]
		);

		$this->add_control(
			'rating_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .rating, {{WRAPPER}} .no-rating' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'rating_typo',
				'selector' => '{{WRAPPER}} .rating, {{WRAPPER}} .no-rating',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'rating_border',
				'selector'  => '{{WRAPPER}} .rating',
				'condition' => ['post_rating_style' => 'circle']
			]
		);

		$this->add_responsive_control(
			'rating_b_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => ['post_rating_style' => 'circle']
			]
		);

		$this->add_responsive_control(
			'rating_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'circle_size',
			[
				'label'      => esc_html__('Circle Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['post_rating_style' => 'circle']
			]
		);

		$this->add_responsive_control(
			'rating_icon_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['post_rating_style' => 'circle']
			]
		);

		$this->add_control(
			'Rating_icon_heading',
			[
				'label'     => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'rating_icon_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .rating .icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'rating_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating .icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'rating_icon_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating .icon' => 'margin: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['post_rating_style' => 'default']
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
	protected function render()
	{
		$settings = $this->get_settings_for_display();

		$query = $this->get_query();

		if (! $query->found_posts) {
			return;
		}

		//wrapper class
		$this->add_render_attribute('wrapper', 'class', [
			'aae--post-time',
			$settings['post_layout'],
		]);

?>
		<div <?php $this->print_render_attribute_string('wrapper'); ?>><?php

																		$this->render_loop_header();
																		$prev_date  = '';
																		$prev_month = '';

																		while ($query->have_posts()) {
																			$query->the_post();

																			if ('layout-normal' === $settings['post_layout']) {
																				$this->render_post_layout_normal($settings, $prev_date, $prev_month);
																			} else {
																				$this->render_post_layout_side_overlay($settings, $prev_date, $prev_month);
																			}
																		}

																		$this->render_loop_footer();

																		?></div><?php

																				wp_reset_postdata();
																			}

																			protected function render_loop_header()
																			{
																				?>
		<div class="wcf-posts">
		<?php
																			}

																			protected function render_loop_footer()
																			{
		?></div><?php
																				$this->render_pagination();
																			}

																			protected function render_post_layout_normal($settings, &$prev_date, &$prev_month)
																			{
																				$post_classes  = ['wcf-post'];
																				$current_date  = get_the_date('Y-m-d');
																				$display_date  = get_the_date('M j, Y');
																				$current_month = get_the_date('Y-m');
																				$display_month = get_the_date('M y');
				?>
		<article <?php post_class($post_classes); ?>>
			<?php
																				if ($current_month !== $prev_month) {
																					echo '<div class="tl-month">' . esc_html($display_month) . '</div>';
																				}

																				if ($current_date !== $prev_date) {
																					echo '<div class="tl-date">' . esc_html($display_date) . ' <span></span></div>';
																				}

																				$prev_date  = $current_date;
																				$prev_month = $current_month;

																				foreach ($settings['post_layout_two'] as $item) {
																					if ('thumb' === $item['post_item']) {
																						$this->render_thumbnail($settings);
																					}

																					if ('rating' === $item['post_item']) {
																						$this->render_post_rating($settings);
																					}

																					if ('taxonomy' === $item['post_item']) {
																						$this->render_post_taxonomy();
																					}

																					if ('title' === $item['post_item']) {
																						$this->render_title();
																					}

																					if ('excerpt' === $item['post_item']) {
																						$this->render_excerpt();
																					}

																					if ('meta' === $item['post_item']) {
																						$this->render_meta_data();
																					}

																					if ('read_more' === $item['post_item']) {
																						$this->render_read_more();
																					}
																				}
			?>
		</article>
	<?php
																			}

																			protected function render_post_layout_side_overlay($settings, &$prev_date, &$prev_month)
																			{
																				$post_classes  = ['wcf-post'];
																				$current_date  = get_the_date('Y-m-d');
																				$display_date  = get_the_date('M j, Y');
																				$current_month = get_the_date('Y-m');
																				$display_month = get_the_date('M y');

																				if ($current_month !== $prev_month) {
																					echo '<div class="tl-month">' . esc_html($display_month) . '</div>';
																				}

																				if ($current_date !== $prev_date) {
																					echo '<div class="tl-date">' . esc_html($display_date) . ' <span></span></div>';
																				}
	?>
		<article <?php post_class($post_classes); ?>>
			<?php

																				$prev_date  = $current_date;
																				$prev_month = $current_month;

																				$this->render_thumbnail($settings);
			?>
			<div class="content">
				<?php
																				foreach ($settings['post_layout_one'] as $item) {
																					if ('rating' === $item['post_item']) {
																						$this->render_post_rating($settings);
																					}

																					if ('taxonomy' === $item['post_item']) {
																						$this->render_post_taxonomy();
																					}

																					if ('title' === $item['post_item']) {
																						$this->render_title();
																					}

																					if ('excerpt' === $item['post_item']) {
																						$this->render_excerpt();
																					}

																					if ('meta' === $item['post_item']) {
																						$this->render_meta_data();
																					}

																					if ('read_more' === $item['post_item']) {
																						$this->render_read_more();
																					}
																				}
				?>
			</div>
		</article>
	<?php
																			}

																			protected function render_post_rating($settings)
																			{
																				if (! $this->get_settings('show_rating')) {
																					return;
																				}

																				$post_id = get_the_ID();

																				$ratings = get_posts([
																					'post_type'  => 'aaeaddon_post_rating',
																					'meta_query' => [
																						[
																							'key'   => 'post_id',
																							'value' => $post_id,
																						]
																					]
																				]);

																				$total_ratings = count($ratings);
																				$total_stars   = 0;

																				foreach ($ratings as $rating) {
																					$total_stars += get_post_meta($rating->ID, 'rating', true);
																				}
	?>
		<div class="post-rating <?php echo esc_attr($settings['post_rating_style']); ?>">
			<?php
																				if ($total_ratings > 0) {
																					$average_rating = round($total_stars / $total_ratings, 1);
			?>
				<div class="rating">
					<?php
																					if ('circle' === $settings['post_rating_style']) {
					?>
						<div class="icon">
							<?php Icons_Manager::render_icon($settings['rating_icon'], ['aria-hidden' => 'true']); ?>
						</div>
						<p>
							<span><?php echo esc_html($average_rating); ?></span>/<?php echo esc_html__('5', 'animation-addons-for-elementor'); ?>
						</p>
					<?php
																					} else {
					?>
						<?php echo esc_html($average_rating); ?>
						<span class="icon">
							<?php Icons_Manager::render_icon($settings['rating_icon'], ['aria-hidden' => 'true']); ?>
						</span>
						(<?php echo esc_html($total_ratings); ?>)
					<?php
																					}
					?>
				</div>
			<?php
																				} else {
			?>
				<p class="no-rating"><?php echo esc_html__('No ratings yet.', 'animation-addons-for-elementor'); ?></p>
			<?php
																				}
			?>
		</div>
<?php
																			}
																		}
