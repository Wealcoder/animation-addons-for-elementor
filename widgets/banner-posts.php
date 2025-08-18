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
 * Banner Posts
 *
 * Elementor widget for Posts.
 *
 * @since 1.0.0
 */
class Banner_Posts extends Widget_Base
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
	 */
	public function get_name()
	{
		return 'wcf--banner-posts';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_title()
	{
		return esc_html__('Banner Posts', 'animation-addons-for-elementor');
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_icon()
	{
		return 'wcf eicon-post';
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
	public function get_categories()
	{
		return array('weal-coder-addon');
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

		// query
		$this->register_query_controls();

		// settings
		$this->register_settings_controls();

		// title
		$this->register_title_controls();

		// meta
		$this->register_meta_controls();

		// read more
		$this->register_read_more_controls();

		// Play Icon
		$this->register_audio_video_play_controls();
	}

	protected function register_query_controls()
	{
		$this->start_controls_section(
			'section_query',
			array(
				'label' => __('Query', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'query_type',
			array(
				'label'    => __('Query Type', 'animation-addons-for-elementor'),
				'type'     => Controls_Manager::SELECT,
				'multiple' => true,
				'options'  => array(
					'recent'      => __('Recent Post', 'animation-addons-for-elementor'),
					'select_post' => __('Selected Post', 'animation-addons-for-elementor'),
				),
			)
		);

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		// we get an array of posts objects
		$posts = get_posts($args);

		$blog_post = array();

		foreach ((array) $posts as $single_post) {
			$blog_post[$single_post->ID] = $single_post->post_title;
		}

		$this->add_control(
			'select_post',
			array(
				'label'     => __('Select Post', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT2,
				'multiple'  => true,
				'options'   => $blog_post,
				'condition' => array(
					'query_type' => array('select_post'),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function register_settings_controls()
	{
		$this->start_controls_section(
			'section_settings',
			array(
				'label' => __('Settings', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'thumbnail_size',
				'exclude'   => array('custom'),
				'default'   => 'medium',
				'condition' => array(
					'show_thumb' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_taxonomy',
			array(
				'label'     => __('Show Taxonomy', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => __('Show', 'animation-addons-for-elementor'),
				'label_off' => __('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			)
		);

		$this->add_control(
			'show_meta',
			array(
				'label'     => __('Show Meta', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => __('Show', 'animation-addons-for-elementor'),
				'label_off' => __('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			)
		);

		$this->add_control(
			'show_read_more',
			array(
				'label'     => __('Read More', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => __('Show', 'animation-addons-for-elementor'),
				'label_off' => __('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			)
		);

		// post format audio/ video
		$this->add_control(
			'post_format_a_v',
			array(
				'label'        => __('Post Audio, Video & Gallery', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_off'    => __('Off', 'animation-addons-for-elementor'),
				'label_on'     => __('On', 'animation-addons-for-elementor'),
				'separator'    => 'before',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function register_title_controls()
	{
		$this->start_controls_section(
			'section_title',
			array(
				'label' => __('Title', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'title_length',
			array(
				'label' => __('Title Length', 'animation-addons-for-elementor'),
				'type'  => Controls_Manager::NUMBER,
				'min'   => 2,
				'max'   => 100,
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => __('Title HTML Tag', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				),
				'default'   => 'h3',
				'condition' => array(
					'show_title' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_title_highlight',
			array(
				'label'              => __('Show Highlight', 'animation-addons-for-elementor'),
				'type'               => Controls_Manager::SWITCHER,
				'separator'          => 'before',
				'label_on'           => __('Show', 'animation-addons-for-elementor'),
				'label_off'          => __('Hide', 'animation-addons-for-elementor'),
				'return_value'       => 'yes',
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'highlight_title_length',
			array(
				'label'              => __('Highlight Length', 'animation-addons-for-elementor'),
				'type'               => Controls_Manager::NUMBER,
				'default'            => 5,
				'min'                => 2,
				'max'                => 100,
				'condition'          => array(
					'show_title_highlight' => 'yes',
				),
				'frontend_available' => true,
			)
		);

		$this->end_controls_section();

		// style
		$this->start_controls_section(
			'section_title_style',
			array(
				'label' => __('Title', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .wcf-post-title',
			)
		);

		$this->start_controls_tabs('tabs_title');

		$this->start_controls_tab(
			'tab_title_normal',
			array(
				'label' => __('Normal', 'animation-addons-for-elementor'),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_tile_hover',
			array(
				'label' => __('Hover', 'animation-addons-for-elementor'),
			)
		);

		$this->add_control(
			'title_color_hover',
			array(
				'label'     => __('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-title:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'title_margin',
			array(
				'label'      => __('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array('px', 'em', 'rem', 'custom'),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_highlight',
			array(
				'label'     => __('Highlight', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'show_title_highlight' => 'yes',
				),
			)
		);

		$this->add_control(
			'title_h_color',
			array(
				'label'     => __('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-title .highlight' => 'color: {{VALUE}};',
				),
				'condition' => array(
					'show_title_highlight' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'title_h_typography',
				'selector'  => '{{WRAPPER}} .wcf-post-title .highlight',
				'condition' => array(
					'show_title_highlight' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function register_meta_controls()
	{

		$this->start_controls_section(
			'section_meta',
			array(
				'label'     => __('Meta', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'show_meta' => 'yes',
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'post_meta',
			array(
				'label'   => __('Meta', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'comments',
				'options' => array(
					'author'     => __('Author', 'animation-addons-for-elementor'),
					'view'       => __('View', 'animation-addons-for-elementor'),
					'date'       => __('Date', 'animation-addons-for-elementor'),
					'time'       => __('Time', 'animation-addons-for-elementor'),
					'time-ago'   => __('Time Ago', 'animation-addons-for-elementor'),
					'comments'   => __('Comments', 'animation-addons-for-elementor'),
					'reviews'    => __('Reviews', 'animation-addons-for-elementor'),
					'read-later' => __('Read Later', 'animation-addons-for-elementor'),
				),
			)
		);

		$repeater->add_control(
			'meta_icon',
			array(
				'label'       => __('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => array(
					'value'   => 'far fa-flag',
					'library' => 'fa-regular',
				),
			)
		);

		$this->add_control(
			'post_meta_data',
			array(
				'label'       => __('Meta Data', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'post_meta' => 'view',
					),
					array(
						'post_meta' => 'date',
					),
				),
				'title_field' => '{{{ post_meta }}}',
			)
		);

		$this->add_control(
			'meta_separator',
			array(
				'label'     => __('Separator Between', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::TEXT,
				'default'   => '///',
				'ai'        => array(
					'active' => false,
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-meta span + span:before' => 'content: "{{VALUE}}"',
				),
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'post_by',
			array(
				'label'   => __('Author By', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('By', 'animation-addons-for-elementor'),
			)
		);

		$this->add_control(
			'show_avatar',
			array(
				'label'        => __('Author Avatar', 'animation-addons-for-elementor'),
				'description'  => __('If you want to use the author avatar, you must chose "Author" in the meta data.', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Show', 'animation-addons-for-elementor'),
				'label_off'    => __('Hide', 'animation-addons-for-elementor'),
				'separator'    => 'before',
				'return_value' => 'yes',
			)
		);

		$this->add_responsive_control(
			'avatar_size',
			array(
				'label'      => __('Avatar Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array('px'),
				'selectors'  => array(
					'{{WRAPPER}} .post-author img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array('show_avatar' => 'yes'),
			)
		);

		$this->end_controls_section();

		// style
		$this->start_controls_section(
			'section_meta_style',
			array(
				'label'     => __('Meta', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_meta' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'meta_gap',
			array(
				'label'      => __('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array('px', '%', 'em', 'rem', 'custom'),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-meta span + span:before' => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf-post-meta' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'meta_color',
			array(
				'label'     => __('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-meta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'meta_typography',
				'selector' => '{{WRAPPER}} .wcf-post-meta',
			)
		);

		$this->add_responsive_control(
			'meta_margin',
			array(
				'label'      => __('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array('px', 'em', 'rem', 'custom'),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-meta' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'meta_icon',
			array(
				'label'      => __('Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'separator'  => 'before',
				'size_units' => array('px'),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-meta .meta-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'meta_icon_color',
			array(
				'label'     => __('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-meta .meta-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'meta_icon_gap',
			array(
				'label'      => __('Icon Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array('px'),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-meta .meta-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// style admin
		$this->start_controls_section(
			'section_meta_admin_style',
			array(
				'label'     => __('Meta Admin', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_meta' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'author_gap',
			array(
				'label'      => __('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array('px', '%', 'em', 'rem', 'custom'),
				'selectors'  => array(
					'{{WRAPPER}} .post-author' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'author_by_heading',
			array(
				'label' => __('Author By', 'animation-addons-for-elementor'),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'admin_by_color',
			array(
				'label'     => __('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-meta .post-by' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'admin_by_typography',
				'selector' => '{{WRAPPER}} .wcf-post-meta .post-by',
			)
		);

		$this->add_control(
			'author_heading',
			array(
				'label'     => __('Author', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'admin_typography',
				'selector' => '{{WRAPPER}} .post-author a',
			)
		);

		$this->start_controls_tabs('tabs_author');

		$this->start_controls_tab(
			'tab_author_normal',
			array(
				'label' => __('Normal', 'animation-addons-for-elementor'),
			)
		);

		$this->add_control(
			'author_color',
			array(
				'label'     => __('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .post-author a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_author_hover',
			array(
				'label' => __('Hover', 'animation-addons-for-elementor'),
			)
		);

		$this->add_control(
			'author_color_hover',
			array(
				'label'     => __('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .post-author a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_read_more_controls()
	{

		$this->start_controls_section(
			'section_post_read_more',
			array(
				'label'     => __('Read More', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'show_read_more' => 'yes',
				),
			)
		);

		$this->add_control(
			'read_more_text',
			array(
				'label'   => __('Read More Text', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => array(
					'active' => true,
				),
				'default' => __('Read More', 'animation-addons-for-elementor'),
			)
		);

		$this->add_control(
			'selected_icon',
			array(
				'label'       => __('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
			)
		);

		$this->add_control(
			'icon_align',
			array(
				'label'   => __('Icon Position', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => array(
					'left'  => __('Before', 'animation-addons-for-elementor'),
					'right' => __('After', 'animation-addons-for-elementor'),
				),
			)
		);

		$this->add_control(
			'icon_indend',
			array(
				'label'     => __('Icon Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max' => 50,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-link' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// style
		$this->start_controls_section(
			'style_post_read_more',
			array(
				'label'     => __('Read More', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_read_more' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'read_more_typography',
				'selector' => '{{WRAPPER}} .wcf-post-link',
			)
		);

		$this->add_responsive_control(
			'read_more_icon_size',
			array(
				'label'      => __('Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array('px', '%'),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					),
					'%'  => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-link i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf-post-link svg' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs(
			'tabs_read_more',
		);

		$this->start_controls_tab(
			'tab_read_more_normal',
			array(
				'label' => __('Normal', 'animation-addons-for-elementor'),
			)
		);

		$this->add_control(
			'read_more_color',
			array(
				'label'     => __('Text Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-link' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'read_more_background',
				'types'    => array('classic', 'gradient'),
				'exclude'  => array('image'),
				'selector' => '{{WRAPPER}} .wcf-post-link',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_read-more_hover',
			array(
				'label' => __('Hover', 'animation-addons-for-elementor'),
			)
		);

		$this->add_control(
			'read_more_text_hover_color',
			array(
				'label'     => __('Text Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-link:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'read_more_hover_background',
				'types'    => array('classic', 'gradient'),
				'exclude'  => array('image'),
				'selector' => '{{WRAPPER}} .wcf-post-link:hover',
			)
		);

		$this->add_control(
			'read_more_hover_border_color',
			array(
				'label'     => __('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-link:hover' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'read_more_border_border!' => '',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'read_more_border',
				'selector'  => '{{WRAPPER}} .wcf-post-link',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'read_more_border_radius',
			array(
				'label'      => __('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array('px', '%', 'em', 'rem', 'custom'),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'read_more_padding',
			array(
				'label'      => __('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array('px', '%', 'em', 'rem', 'vw', 'custom'),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'read_more_shadow',
				'selector' => '{{WRAPPER}} .wcf-post-link',
			)
		);

		$this->add_responsive_control(
			'read_more_margin',
			array(
				'label'      => __('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array('px', 'em', 'rem', 'custom'),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-link' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function register_audio_video_play_controls()
	{
		$this->start_controls_section(
			'section_audio_video_play',
			array(
				'label'     => __('Post Popup', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array('post_format_a_v' => 'yes'),
			)
		);

		$this->add_control(
			'audio_video_play',
			array(
				'label'       => __('Video Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => array(
					'value'   => 'fas fa-play-circle',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_control(
			'audio_icon',
			array(
				'label'       => __('Audio Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => array(
					'value'   => 'fas fa-music',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_control(
			'gallery_icon',
			array(
				'label'       => __('Gallery Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => array(
					'value'   => 'far fa-images',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_responsive_control(
			'audio_video_play_size',
			array(
				'label'      => __('Play Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array('px'),
				'default'    => array(
					'unit' => 'px',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .play' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'audio_video_play_color',
			array(
				'label'     => __('Play Icon Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .play' => 'color: {{VALUE}}; fill: {{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'audio_video_play_offset_x',
			array(
				'label'      => __('Offset X', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array('px', '%', 'em', 'rem', 'custom'),
				'range'      => array(
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
					'px' => array(
						'min' => 0,
						'max' => 1000,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .play' => '--offset-x: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'audio_video_play_offset_y',
			array(
				'label'      => __('Offset Y', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array('px', '%', 'em', 'rem', 'custom'),
				'range'      => array(
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
					'px' => array(
						'min' => 0,
						'max' => 1000,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'selectors'  => array(
					'{{WRAPPER}} .play' => '--offset-y: {{SIZE}}{{UNIT}};',
				),
			)
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

	protected function query_arg()
	{

		$query_args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
		);

		if ('select_post' == $this->get_settings('query_type') && ! empty($this->get_settings('select_post'))) {
			$query_args['post__in'] = array_map('absint', $this->get_settings('select_post'));
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

		$query = new WP_Query($this->query_arg());

		if (! $query->found_posts) {
			return;
		}

		// wrapper class
		$this->add_render_attribute('wrapper', 'class', array('wcf__banner-posts'));
?>
		<div <?php $this->print_render_attribute_string('wrapper'); ?>>
			<div class="post-wrapper">
				<?php
				while ($query->have_posts()) {
					$query->the_post();
					$this->render_post($settings);
				}
				?>
			</div>
		</div>
	<?php
		wp_reset_postdata();
	}

	protected function render_post($settings)
	{
	?>
		<article class="wcf-post" data-id="<?php echo esc_attr(get_the_ID()); ?>">
			<?php
			$this->render_thumbnail($settings);
			$this->render_title();
			$this->render_meta_data();
			$this->render_read_more();
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
		$settings['thumbnail_size'] = array(
			'id' => get_post_thumbnail_id(),
		);
	?>
		<div class="thumb" data-target="<?php echo esc_attr(get_the_ID()); ?>">
			<?php $this->render_post_taxonomy(); ?>
			<?php $this->render_audio_video_play_icon(); ?>
			<?php Group_Control_Image_Size::print_attachment_image_html($settings, 'thumbnail_size'); ?>
		</div>
<?php
	}
}
