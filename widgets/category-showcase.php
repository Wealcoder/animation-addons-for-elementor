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


if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class Category_Showcase extends Widget_Base
{

	// Widget Name
	public function get_name()
	{
		return 'category-showcase';
	}

	// Widget Title
	public function get_title()
	{
		return __('Category Showcase', 'animation-addons-for-elementor');
	}

	// Widget Icon
	public function get_icon()
	{
		return 'wcf eicon-archive-posts';
	}

	// Widget Category
	public function get_categories()
	{
		return ['weal-coder-addon', 'wcf-archive-addon'];
	}

	/**git s
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['wcf--category-showcase'];
	}

	// main Controls
	protected function register_controls()
	{
		// Layout
		$this->register_layout_controls();

		// Content
		$this->register_category_content();

		// Image
		$this->register_image_setting();

		// Settings
		$this->register_settings_controls();

		// Style
		$this->register_layout_style_controls();
		$this->register_thumbnail_controls();
		$this->register_content_style();
		$this->register_title_controls();
		$this->register_sub_title_controls();
		$this->register_desc_controls();
		$this->register_slogan_controls();
		$this->register_thumb_icon_controls();
		$this->register_border_style_controls();

		// Style Two
		$this->category_showcase_style_two();
	}

	// Image Controls
	protected function register_image_setting()
	{
		$this->start_controls_section(
			'image_settings',
			[
				'label'     => esc_html__('Image', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => ['cat_layout_style' => '1'],
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
				'name'    => 'thumbnail_size',
				'exclude' => ['custom'],
				'default' => 'medium',
			]
		);
		$this->add_control(
			'choose_render',
			[
				'label'     => esc_html__('Image Display Type', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'normal',
				'options'   => [
					'normal'     => esc_html__('Inline Image', 'animation-addons-for-elementor'),
					'background' => esc_html__('Background Image', 'animation-addons-for-elementor'),
				],
				'condition' => [
					'show_thumb' => 'yes',
				],
			]
		);
		$this->add_control(
			'show_thumb_icon',
			[
				'label'     => esc_html__('Show Icon', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'no',
			]
		);
		$this->end_controls_section();
	}

	// Category Content
	protected function register_category_content()
	{
		$this->start_controls_section(
			'category_content',
			[
				'label'     => esc_html__('Content', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => ['cat_layout_style' => '1'],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'content_type',
			[
				'label'   => __('Content Type', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'title'       => __('Title', 'animation-addons-for-elementor'),
					'total'       => __('Total', 'animation-addons-for-elementor'),
					'description' => __('Description', 'animation-addons-for-elementor'),
					'slogan'      => __('Slogan', 'animation-addons-for-elementor'),
				],
				'default' => 'title',
			]
		);

		$this->add_control(
			'category_content_repeater',
			[
				'label'       => __('Category Content', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					['content_type' => 'title'],
					['content_type' => 'total'],
					['content_type' => 'description'],
					['content_type' => 'slogan'],
				],
				'title_field' => '{{{ content_type }}}',
			]
		);

		$this->add_control(
			'show_content_title',
			[
				'label'     => esc_html__('Show Title', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);
		$this->add_control(
			'title_tag',
			[
				'label'   => esc_html__('Title HTML Tag', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
				'default' => 'h2',
			]
		);
		$this->add_control(
			'show_sub_title',
			[
				'label'     => esc_html__('Show Sub Title', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);
		$this->add_control(
			'subtitle_tag',
			[
				'label'   => esc_html__('Sub Title HTML Tag', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
				'default' => 'h2',
			]
		);
		$this->add_control(
			'show_description',
			[
				'label'     => esc_html__('Show Discription', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);
		$this->add_control(
			'description_tag',
			[
				'label'   => esc_html__('Description HTML Tag', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
				'default' => 'h2',
			]
		);
		$this->add_control(
			'slogan_show',
			[
				'label'     => esc_html__('Show Slogan', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);

		$this->add_control(
			'border_show',
			[
				'label'     => esc_html__('Show Border', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('Hide', 'animation-addons-for-elementor'),
				'default'   => 'yes',
			]
		);

		$this->end_controls_section();
	}

	// Layout Category
	protected function register_layout_controls()
	{

		$this->start_controls_section(
			'layout_settings',
			[
				'label' => esc_html__('Layout', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'cat_layout_style',
			[
				'label'   => esc_html__('Style', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__('One', 'animation-addons-for-elementor'),
					'2' => esc_html__('Two', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_responsive_control(
			'gap_categories',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'custom'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-category-showcase-wrapper, {{WRAPPER}} .aae--category-list' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	// Settings
	protected function register_settings_controls()
	{
		$this->start_controls_section(
			'section_settings',
			[
				'label' => esc_html__('Query', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'number_of_categories',
			[
				'label'   => esc_html__('Number of Categories', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
			]
		);

		$this->add_control(
			'show_child_categories',
			[
				'label'        => esc_html__('Show Child Categories?', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->add_responsive_control(
			'number_of_columns',
			[
				'label'     => esc_html__('Number of Columns', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4,
				'min'       => 1,
				'selectors' => [
					'{{WRAPPER}} .wcf-category-showcase-wrapper' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
				'condition' => ['cat_layout_style' => '1'],
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => __('Order By', 'animation-addons-for-elementor'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'  => __('Date', 'animation-addons-for-elementor'),
					'title' => __('Title', 'animation-addons-for-elementor'),
					'rand'  => __('Random', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => __('Order', 'animation-addons-for-elementor'),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'ASC'  => __('Ascending (ASC)', 'animation-addons-for-elementor'),
					'DESC' => __('Descending (DESC)', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->end_controls_section();
	}

	// Style controls
	protected function register_layout_style_controls()
	{
		$this->start_controls_section(
			'section_design_layout',
			[
				'label'     => esc_html__('Item', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['cat_layout_style' => '1'],
			]
		);

		$this->add_responsive_control(
			'item_thumb_width',
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
					'{{WRAPPER}} .wcf-category-showcase-item' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'post_border_1',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .wcf-category-showcase-item',
			]
		);

		$this->add_responsive_control(
			'post_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-category-showcase-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
				'label'     => esc_html__('Thumbnail', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_thumb'       => 'yes',
					'cat_layout_style' => '1',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'item_overlay',
				'types'     => ['classic', 'gradient', 'video'],
				'condition' => [
					'show_thumb'     => 'yes',
					'choose_render!' => 'normal'
				],
				'selector'  => '{{WRAPPER}} .wcf-category-showcase-item::before',
			]

		);
		$this->add_responsive_control(
			'item_overlay_opacity',
			[
				'label'      => esc_html__('Opacity', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [''],
				'range'      => [
					'px' => [
						'min'  => 0.1,
						'max'  => 1,
						'step' => 0.1,
					],
				],
				'condition'  => [
					'show_thumb'     => 'yes',
					'choose_render!' => 'normal'
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-category-showcase-item::before' => 'opacity: {{SIZE}};',
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
					'{{WRAPPER}} .wcf-cs-main-image' => 'width: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .wcf-cs-main-image' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'thumb_fit',
			[
				'label'     => esc_html__('Object Fit', 'animation-addons-for-elementor'),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => [
					''        => esc_html__('Default', 'animation-addons-for-elementor'),
					'contain' => esc_html__('contain', 'animation-addons-for-elementor'),
					'cover'   => esc_html__('cover', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-main-image' => 'object-fit: {{VALUE}};',
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
					'{{WRAPPER}} .wcf-cs-main-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .wcf-cs-main-image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_content_style()
	{
		$this->start_controls_section(
			'content_style',
			[
				'label'     => esc_html__('Content', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['cat_layout_style' => '1'],
			]
		);
		$this->add_control(
			'content_element_type',
			[
				'label'     => esc_html__('Display', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'block',
				'options'   => [
					'block'        => esc_html__('Default', 'animation-addons-for-elementor'),
					'inline'       => esc_html__('Inline', 'animation-addons-for-elementor'),
					'inline-block' => esc_html__('Inline-block', 'animation-addons-for-elementor'),
					'flex'         => esc_html__('Flex', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-content' => 'display: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'justify_content',
			[
				'label'     => __('Justify Content', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start'    => [
						'title' => __('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center'        => [
						'title' => __('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'      => [
						'title' => __('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
					'space-between' => [
						'title' => __('Space Between', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-ellipsis-h',
					],
					'space-around'  => [
						'title' => __('Space Around', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-stretch',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-content' => 'justify-content: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'align_content',
			[
				'label'     => __('Align Items', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => __('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => __('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'   => [
						'title' => __('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-content' => 'align-items: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'content_gap',
			[
				'label'      => __('Content Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px'  => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
					'em'  => [
						'min'  => 0,
						'max'  => 10,
						'step' => 0.1,
					],
					'rem' => [
						'min'  => 0,
						'max'  => 10,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-content' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'content_pos',
			[
				'label'     => esc_html__('Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'relative',
				'options'   => [
					'unset'    => esc_html__('Default', 'animation-addons-for-elementor'),
					'relative' => esc_html__('Relative', 'animation-addons-for-elementor'),
					'absolute' => esc_html__('Absolute', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-content' => 'position: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'content_background',
				'types'    => ['classic', 'gradient', 'video'],
				'selector' => '{{WRAPPER}} .wcf-cs-content',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'content_border',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .wcf-cs-content',
			]
		);
		$this->add_responsive_control(
			'content_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'content_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'content_text_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-content' => 'text-align: {{VALUE}};',
				],
			]
		);
		$this->end_controls_section();
	}

	protected function register_title_controls()
	{

		//style
		$this->start_controls_section(
			'section_title_style',
			[
				'label'     => esc_html__('Title', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_content_title' => 'yes',
					'cat_layout_style'   => '1',
				],
			]
		);
		$this->add_control(
			'title_element_type',
			[
				'label'     => esc_html__('Display', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'Inline-block',
				'options'   => [
					'block'        => esc_html__('Default', 'animation-addons-for-elementor'),
					'inline'       => esc_html__('Inline', 'animation-addons-for-elementor'),
					'inline-block' => esc_html__('Inline-block', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-title' => 'display: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .wcf-cs-cat-title',
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
			Group_Control_Background::get_type(),
			[
				'name'     => 'title_background',
				'types'    => ['classic', 'gradient', 'video'],
				'selector' => '{{WRAPPER}} .wcf-cs-cat-title',
			]
		);
		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-title' => 'color: {{VALUE}};',
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
			'title_color_hover',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-title:hover' => 'color: {{VALUE}};',
				],
			]
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_before',
			[
				'label' => esc_html__('Before', 'animation-addons-for-elementor'),

			]
		);
		$this->add_responsive_control(
			'title_before_width',
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
					'{{WRAPPER}} .wcf-cs-cat-title::before' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_before_height',
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
					'{{WRAPPER}} .wcf-cs-cat-title::before' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tab_before_bg_2',
				'types'    => ['classic', 'gradient', 'video'],
				'selector' => '{{WRAPPER}} .wcf-cs-cat-title::before',
			]
		);

		$this->end_controls_tab();
		$this->start_controls_tab(
			'tab_after',
			[
				'label' => esc_html__('After', 'animation-addons-for-elementor'),
			]
		);
		$this->add_responsive_control(
			'title_after_width',
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
					'{{WRAPPER}} .wcf-cs-cat-title::after' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_after_height',
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
					'{{WRAPPER}} .wcf-cs-cat-title::after' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tab_after_background',
				'types'    => ['classic', 'gradient', 'video'],
				'selector' => '{{WRAPPER}} .wcf-cs-cat-title::after',
			]
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'title_text_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-title' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_before_after_control',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->end_controls_section();
	}

	protected function register_sub_title_controls()
	{

		//style
		$this->start_controls_section(
			'section_sub_title_style',
			[
				'label'     => esc_html__('Sub Title', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_sub_title'   => 'yes',
					'cat_layout_style' => '1',
				],
			]
		);
		$this->add_control(
			'subtitle_element_type',
			[
				'label'     => esc_html__('Display', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'Inline-block',
				'options'   => [
					'block'        => esc_html__('Default', 'animation-addons-for-elementor'),
					'inline'       => esc_html__('Inline', 'animation-addons-for-elementor'),
					'inline-block' => esc_html__('Inline-block', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'display: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sub_title_typography',
				'selector' => '{{WRAPPER}} .wcf-cs-cat-total',
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'subtitle_background',
				'types'    => ['classic', 'gradient', 'video'],
				'selector' => '{{WRAPPER}} .wcf-cs-cat-total',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'subtitle_style_w_1',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .wcf-cs-cat-total',
			]
		);
		$this->add_responsive_control(
			'subtitle_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'sub_title_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'sub_color_hover',
			[
				'label'     => esc_html__('Hover Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-total:hover' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'subtitle_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'sub_title_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'hr_divider',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);
		$this->add_control(
			'highlight_title_color',
			[
				'label'     => esc_html__('Highlight Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-highlight' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'highlight_color_hover',
			[
				'label'     => esc_html__('Highligt Hover', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-highlight:hover' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'subtitle_text_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'text-align: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'subtitle_pos',
			[
				'label'     => esc_html__('Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'relative',
				'options'   => [
					'unset'    => esc_html__('Default', 'animation-addons-for-elementor'),
					'relative' => esc_html__('Relative', 'animation-addons-for-elementor'),
					'absolute' => esc_html__('Absolute', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'position: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'subtitle_top',
			[
				'label'      => esc_html__('Top', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'custom'],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'subtitle_left',
			[
				'label'      => esc_html__('Right', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'custom'],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-total' => 'right: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();
	}

	protected function register_desc_controls()
	{

		//style
		$this->start_controls_section(
			'section_desc_style',
			[
				'label'     => esc_html__('Description', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_description' => 'yes',
					'cat_layout_style' => '1',
				],
			]
		);
		$this->add_control(
			'desc_element_type',
			[
				'label'     => esc_html__('Display', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'Inline-block',
				'options'   => [
					'block'        => esc_html__('Default', 'animation-addons-for-elementor'),
					'inline'       => esc_html__('Inline', 'animation-addons-for-elementor'),
					'inline-block' => esc_html__('Inline-block', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-description' => 'display: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .wcf-cs-cat-description',
			]
		);
		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-description' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'desc_color_hover',
			[
				'label'     => esc_html__('Hover Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-description:hover' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'desc_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'hr',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);
		$this->add_control(
			'highlight_desc_color',
			[
				'label'     => esc_html__('Highlight Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-description' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'highlight_desc_color_hover',
			[
				'label'     => esc_html__('Highligt Hover', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-description:hover' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'desc_text_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-description' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_slogan_controls()
	{

		//style
		$this->start_controls_section(
			'section_slogan_style',
			[
				'label'     => esc_html__('Slogan', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'slogan_show'      => 'yes',
					'cat_layout_style' => '1',
				],
			]
		);
		$this->add_control(
			'slogan_element_type',
			[
				'label'     => esc_html__('Display', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'Inline-block',
				'options'   => [
					'block'        => esc_html__('Default', 'animation-addons-for-elementor'),
					'inline'       => esc_html__('Inline', 'animation-addons-for-elementor'),
					'inline-block' => esc_html__('Inline-block', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-slogan' => 'display: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'slogan_typography',
				'selector' => '{{WRAPPER}} .wcf-cs-cat-slogan',
			]
		);
		$this->add_control(
			'slogan_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-slogan' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'slogan_color_hover',
			[
				'label'     => esc_html__('Hover Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-slogan:hover' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'slogan_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-slogan' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'slogan_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-cat-slogan' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'slogan_text_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-cat-slogan' => 'text-align: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'slogan_before_width',
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
					'{{WRAPPER}} .wcf-cs-cat-slogan::after' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'slogan_before_height',
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
					'{{WRAPPER}} .wcf-cs-cat-slogan::after' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tab_before_bg',
				'types'    => ['classic', 'gradient', 'video'],
				'selector' => '{{WRAPPER}} .wcf-cs-cat-slogan::after',
			]
		);
		$this->end_controls_section();
	}

	protected function register_thumb_icon_controls()
	{
		$this->start_controls_section(
			'thumb_icon_style',
			[
				'label'     => esc_html__('Icon', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_thumb_icon'  => 'yes',
					'cat_layout_style' => '1',
				],
			]
		);

		$this->add_responsive_control(
			'icon_thumb_width',
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
					'{{WRAPPER}} .wcf-cs-icon' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_thumb_height',
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
					'{{WRAPPER}} .wcf-cs-icon' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'icon_pos',
			[
				'label'     => esc_html__('Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'relative',
				'options'   => [
					'unset'    => esc_html__('Default', 'animation-addons-for-elementor'),
					'relative' => esc_html__('Relative', 'animation-addons-for-elementor'),
					'absolute' => esc_html__('Absolute', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-cs-icon' => 'position: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'pos_top',
			[
				'label'      => esc_html__('Top', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'custom'],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-icon' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'pos_left',
			[
				'label'      => esc_html__('Left', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'custom'],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-icon' => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'background',
				'types'    => ['classic', 'gradient', 'video'],
				'selector' => '{{WRAPPER}} .wcf-cs-icon',
			]
		);
		$this->add_responsive_control(
			'icon_thumb_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_thumb_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'post_border',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .wcf-cs-icon',
			]
		);

		$this->add_responsive_control(
			'icon_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-cs-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();
	}

	protected function register_border_style_controls()
	{
		$this->start_controls_section(
			'border_style',
			[
				'label'     => esc_html__('Border', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'border_show'      => 'yes',
					'cat_layout_style' => '1',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'border_style_w_1',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .wcf-cs-border-style',
			]
		);

		$this->end_controls_section();
	}

	protected function category_showcase_style_two()
	{
		$this->start_controls_section(
			'category_style_two',
			[
				'label'     => esc_html__('Category', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['cat_layout_style' => '2',],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'scat_typo',
				'selector' => '{{WRAPPER}} .cat-name',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'scat_bg',
				'types'    => ['classic', 'gradient', 'video'],
				'selector' => '{{WRAPPER}} .cat-name',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'scat_border',
				'selector' => '{{WRAPPER}} .cat-name',
			]
		);

		$this->add_responsive_control(
			'scat_b_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .cat-name' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'scat_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .cat-name' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Tabs
		$this->start_controls_tabs(
			'scat_style_tabs'
		);

		// Normal
		$this->start_controls_tab(
			'scat_normal_tab',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'scat_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cat-name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover
		$this->start_controls_tab(
			'scat_hover_tab',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'scat_h_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cat-name:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'scat_h_bg',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .cat-name:hover',
			]
		);

		$this->add_control(
			'scat_h_b_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cat-name:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	// Widget Render Output
	protected function render()
	{
		$settings             = $this->get_settings_for_display();
		$number_of_categories = ! empty($settings['number_of_categories']) ? (int) $settings['number_of_categories'] : 4;
		$show_content_title   = $settings['show_content_title'];
		$show_sub_title       = $settings['show_sub_title'];
		$show_description     = $settings['show_description'];
		$slogan_show          = $settings['slogan_show'];
		$show_thumb           = $settings['show_thumb'];
		$show_thumb_icon      = $settings['show_thumb_icon'];
		$border_show          = $settings['border_show'];
		$image_display_type   = $settings['choose_render'];

		// Get the orderby and order settings
		$orderby = ! empty($settings['orderby']) ? $settings['orderby'] : 'date';
		$order   = ! empty($settings['order']) ? $settings['order'] : 'DESC';

		// Dynamic tags
		$title_tag       = ! empty($settings['title_tag']) ? $settings['title_tag'] : 'h2';
		$subtitle_tag    = ! empty($settings['subtitle_tag']) ? $settings['subtitle_tag'] : 'p';
		$description_tag = ! empty($settings['description_tag']) ? $settings['description_tag'] : 'p';
		$show_child      = $settings['show_child_categories'] === 'yes' ? '' : 0;

		// Get categories with orderby and order
		$categories = get_categories([
			'number'  => $number_of_categories,
			'orderby' => $orderby,
			'order'   => $order,
			'parent'  => $show_child,
		]);

		if (! empty($categories)) {
			if ('2' === $settings['cat_layout_style']) {
?>
				<ul class="aae--category-list">
					<?php
					foreach ($categories as $category) {
					?>
						<li><a class="cat-name" href="<?php echo esc_url(get_category_link($category->term_id)); ?>">
								<?php echo esc_html($category->name); ?>
							</a></li>
					<?php
					}
					?>
				</ul>
<?php
			} else {
				echo '<div class="wcf-category-showcase-wrapper">';
				foreach ($categories as $category) {
					// Get the category image and icon URLs from term meta
					$image_url = esc_url(get_term_meta($category->term_id, 'aae_category_image', true));
					$icon_url  = esc_url(get_term_meta($category->term_id, 'aae_category_icon', true));
					$slogan    = esc_html(get_term_meta($category->term_id, 'aae_cate_additional_text', true));

					// Apply the background image conditionally
					$background_style = ('yes' === $show_thumb && 'normal' !== $image_display_type && $image_url) ? 'style="background-image: url(' . $image_url . ');"' : '';

					// Open the category item div with the optional background style
					echo '<div class="wcf-category-showcase-item" ' . esc_attr($background_style) . '>';

					// Thumbnail and icon
					echo '<div class="wcf-cs-thumb">';
					if ('yes' === $show_thumb && 'normal' === $image_display_type && $image_url) {
						echo '<img class="wcf-cs-main-image" src="' . esc_attr($image_url) . '" alt="' . esc_html($category->name) . '" >';
					}
					if ('yes' === $show_thumb_icon && $icon_url) {
						echo '<div class="wcf-cs-icon"><img src="' . esc_attr($icon_url) . '" alt="' . esc_html($category->name) . '"></div>';
					}
					echo '</div>'; // End .wcf-cs-thumb

					// Category content
					echo '<div class="wcf-cs-content">';
					foreach ($settings['category_content_repeater'] as $content_item) {
						if ($content_item['content_type'] === 'title' && 'yes' === $show_content_title) {
							echo sprintf(
								'<%1$s class="wcf-cs-cat-title"><a href="%2$s">%3$s</a></%1$s>',
								esc_html($title_tag),
								esc_url(get_category_link($category->term_id)),
								esc_html($category->name)
							);
							if ('yes' === $border_show) {
								echo "<div class='wcf-cs-border-style'></div>";
							}
						} elseif ($content_item['content_type'] === 'total' && 'yes' === $show_sub_title) {
							echo sprintf(
								'<%1$s class="wcf-cs-cat-total"><a href="%2$s">%3$d %4$s</a></%1$s>',
								esc_html($subtitle_tag),
								esc_url(get_category_link($category->term_id)),
								esc_html($category->count),
								('yes' === $border_show) ? '' : 'Articles'
							);
						} elseif ($content_item['content_type'] === 'description' && 'yes' === $show_description) {
							echo sprintf(
								'<%1$s class="wcf-cs-cat-description">%2$s</%1$s>',
								esc_html($description_tag),
								esc_html($category->description)
							);
						} elseif ($content_item['content_type'] === 'slogan' && 'yes' === $slogan_show && ! empty($slogan)) {
							echo '<p class="wcf-cs-cat-slogan">' . esc_html($slogan) . '</p>';
						}
					}
					echo '</div>'; // End .wcf-cs-content

					echo '</div>'; // End .wcf-category-showcase-item
				}
				echo '</div>';
			}
		} else {
			echo esc_html__('No categories found.', 'animation-addons-for-elementor');
		}
	}
}
