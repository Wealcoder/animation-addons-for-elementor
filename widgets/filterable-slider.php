<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Control_Media;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WCF_ADDONS\WCF_Slider_Trait;

if (! defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

/**
 * Elementor Hello World
 *
 * Elementor widget for hello world.
 *
 * @since 1.0.0
 */
class Filterable_Slider extends Widget_Base
{

	use WCF_Slider_Trait;

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
		return 'wcf--filterable-slider';
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
		return esc_html__('Filterable Slider', 'animation-addons-for-elementor');
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
		return 'wcf eicon-image-box';
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
		return ['swiper', 'wcf--filterable-slider'];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['swiper', 'wcf--filterable-slider'];
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
		$start = is_rtl() ? 'right' : 'left';
		$end   = is_rtl() ? 'left' : 'right';

		//filter
		$this->register_filter_controls();

		//slide
		$this->register_slides_controls();

		//slide controls
		$this->start_controls_section(
			'section_slider_options',
			[
				'label' => esc_html__('Slider Options', 'animation-addons-for-elementor'),
			]
		);

		$this->register_slider_controls(['slides_to_show' => 3]);

		$pagination_type['options'] = [
			'bullets'     => esc_html__('Bullets', 'animation-addons-for-elementor'),
			'fraction'    => esc_html__('Fraction', 'animation-addons-for-elementor'),
			'progressbar' => esc_html__('Progressbar', 'animation-addons-for-elementor'),
			'custom'      => esc_html__('Fraction Progress', 'animation-addons-for-elementor'),
		];

		$this->update_control('pagination_type', $pagination_type);

		$this->end_controls_section();

		//Settings
		$this->start_controls_section(
			'section_settings',
			[
				'label' => __('Settings', 'animation-addons-for-elementor'),
			]
		);

		$this->add_responsive_control(
			'filter_direction',
			[
				'label'        => esc_html__('Direction', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => [
					'column'         => [
						'title' => esc_html__('Above', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-v-align-top',
					],
					'column-reverse' => [
						'title' => esc_html__('Below', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-v-align-bottom',
					],
					'row-reverse'    => [
						'title' => esc_html__('After', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-' . $end,
					],
					'row'            => [
						'title' => esc_html__('Before', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-' . $start,
					],
				],
				'separator'    => 'before',
				'selectors'    => [
					'{{WRAPPER}} .wcf--filterable-slider' => 'flex-direction: {{VALUE}}',
				],
				'prefix_class' => 'filter-direction-',
			]
		);

		$this->add_responsive_control(
			'filter_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''       => esc_html__('Start', 'animation-addons-for-elementor'),
					'center' => esc_html__('Center', 'animation-addons-for-elementor'),
					'end'    => esc_html__('End', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .slide-filter' => 'justify-content: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'filter_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .wcf--filterable-slider' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'slider_width',
			[
				'label'      => esc_html__('Slider Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
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
				'default'    => [
					'unit' => '%',
					'size' => 80,
				],
				'selectors'  => [
					'{{WRAPPER}} .slider-wrapper' => 'max-width: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'filter_direction' => ['row', 'row-reverse'],
				],
			]
		);

		$this->end_controls_section();

		//slider navigation style controls
		$this->start_controls_section(
			'section_slider_navigation_style',
			[
				'label'     => esc_html__('Slider Navigation', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['navigation' => 'yes'],
			]
		);

		$this->register_slider_navigation_style_controls();

		$this->end_controls_section();

		//slider pagination style controls
		$this->start_controls_section(
			'section_slider_pagination_style',
			[
				'label'     => esc_html__('Slider Pagination', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['pagination' => 'yes'],
			]
		);

		$this->register_slider_pagination_style_controls();

		$this->add_responsive_control(
			'pagination_spacing',
			[
				'label'     => esc_html__('Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}} .swiper-pagination' => 'bottom: {{SIZE}}{{UNIT}};',
				],
				'condition' => ['pagination_type' => 'custom']
			]
		);

		$this->add_responsive_control(
			'pagination_width',
			[
				'label'     => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}} .swiper-pagination .paginate-fill' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition' => ['pagination_type' => 'custom']
			]
		);

		$this->add_responsive_control(
			'pagination_height',
			[
				'label'     => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 1,
						'max' => 20,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .swiper-pagination .paginate-fill' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition' => ['pagination_type' => 'custom']
			]
		);

		$this->add_control(
			'paginate_color',
			[
				'label'     => esc_html__('Paginate Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .swiper-pagination .paginate-fill' => 'background: {{VALUE}};',
				],
				'condition' => ['pagination_type' => 'custom']
			]
		);

		$this->add_control(
			'paginate_active_color',
			[
				'label'     => esc_html__('Paginate Active Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .swiper-pagination .paginate-fill:after' => 'background: {{VALUE}};',
				],
				'condition' => ['pagination_type' => 'custom']
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'paginate_typography',
				'selector'  => '{{WRAPPER}} .swiper-pagination',
				'condition' => ['pagination_type' => 'custom']
			]
		);

		$this->add_control(
			'paginate_count_color',
			[
				'label'     => esc_html__('Count Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .swiper-pagination' => 'color: {{VALUE}}',
				],
				'condition' => ['pagination_type' => 'custom']
			]
		);

		$this->end_controls_section();
	}

	protected function register_filter_controls()
	{
		$this->start_controls_section(
			'section_filter',
			[
				'label' => __('Filter', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'enable_filter',
			[
				'label'        => esc_html__('Enable Filter', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'filter_all_label',
			[
				'label'     => esc_html__('Filter All Labels', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__('All', 'animation-addons-for-elementor'),
				'condition' => ['enable_filter' => 'yes'],
			]
		);

		$this->add_control(
			'filter_all_count',
			[
				'label'   => esc_html__('All Count', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'default' => 10,
			]
		);

		$filter_repeater = new Repeater();

		$filter_repeater->add_control(
			'filter_title',
			[
				'label'       => esc_html__('Title', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Filter Item', 'animation-addons-for-elementor'),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$filter_repeater->add_control(
			'filter_count',
			[
				'label'   => esc_html__('Count', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'default' => 2,
			]
		);

		$this->add_control(
			'filter_items',
			[
				'label'       => esc_html__('Filter Items', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $filter_repeater->get_controls(),
				'condition'   => ['enable_filter' => 'yes'],
				'default'     => [
					[
						'filter_title' => esc_html__('Filter Item 1', 'animation-addons-for-elementor'),
					],
				],
				'title_field' => '{{{ filter_title }}}',
			]
		);

		$this->end_controls_section();

		//style
		$this->start_controls_section(
			'section_filter_style',
			[
				'label' => __('Filter', 'animation-addons-for-elementor'),
				'tab'        => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'filter_item_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .slide-filter li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filter_item_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .slide-filter' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'filter_typography',
				'selector' => '{{WRAPPER}} .slide-filter li',
			]
		);

		$this->start_controls_tabs(
			'filter_style_tabs'
		);

		$this->start_controls_tab(
			'style_normal_tab',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'filter_text_color',
			[
				'label' => esc_html__('Text Color', 'animation-addons-for-elementor'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .slide-filter li' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'style_hover_tab',
			[
				'label' => esc_html__('Active', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'filter_hover_text_color',
			[
				'label'     => esc_html__('Text Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .slide-filter li.active' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		//count
		$this->add_control(
			'filter_count_heading',
			[
				'label'     => esc_html__('Count', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'filter_count_size',
			[
				'label'      => esc_html__('Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .slide-filter .count' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'filter_count_align',
			[
				'label'     => esc_html__('Vertical Align', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'inherit',
				'options'   => [
					'inherit' => esc_html__('Default', 'animation-addons-for-elementor'),
					'sub'     => esc_html__('Sub', 'animation-addons-for-elementor'),
					'super'   => esc_html__('Super', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .slide-filter .count' => 'vertical-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'filter_count_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .slide-filter .count' => 'margin-inline-start: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_slides_controls()
	{
		$this->start_controls_section(
			'section_slides',
			[
				'label' => __('Slides', 'animation-addons-for-elementor'),
			]
		);

		$project_repeater = new Repeater();

		$project_repeater->add_control(
			'project_item_filter_name',
			[
				'label'       => esc_html__('Filter name', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Filter Item 1', 'animation-addons-for-elementor'),
				'description' => __('Use the filter name. Separate multiple items with comma (e.g. <strong>Project Item, Project Item 2</strong>)', 'animation-addons-for-elementor'),
				'separator'   => 'after',
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$project_repeater->add_control(
			'project_image',
			[
				'label'   => esc_html__('Choose Image', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		// Title
		$project_repeater->add_control(
			'title',
			[
				'label'       => esc_html__('Title', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Alexa Complex', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Type your title', 'animation-addons-for-elementor'),
			]
		);

		// Sub Title
		$project_repeater->add_control(
			'subtitle',
			[
				'label'       => esc_html__('Sub Title', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Construction', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Type your sub title', 'animation-addons-for-elementor'),
			]
		);

		// Description
		$project_repeater->add_control(
			'description',
			[
				'label'       => esc_html__('Description', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::WYSIWYG,
				'rows'        => 10,
				'default'     => esc_html__('Default description', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Type your description', 'animation-addons-for-elementor'),
			]
		);

		$project_repeater->add_control(
			'link',
			[
				'label'       => esc_html__('Link', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [
					'active' => true,
				],
				'placeholder' => 'https://your-link.com',
			]
		);

		$this->add_control(
			'project_items',
			[
				'label'       => esc_html__('Slides', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $project_repeater->get_controls(),
				'default'     => [
					[],
					[],
					[],
					[],
				],
				'title_field' => '{{{ project_item_filter_name }}}',
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'   => esc_html__('Title HTML Tag', 'animation-addons-for-elementor'),
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
				'default' => 'h4',
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'image_size',
				'exclude' => ['custom'],
				'include' => [],
				'default' => 'full',
			]
		);

		$this->end_controls_section();

		//style
		$this->register_image_style_controls();

		$this->register_content_style_controls();
	}

	protected function register_content_style_controls()
	{

		$this->start_controls_section(
			'section_content_style',
			[
				'label' => esc_html__('Content', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'content_background',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .content',
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Title
		$this->add_control(
			'title_heading',
			[
				'label'     => esc_html__('Title', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'title_space',
			[
				'label'     => esc_html__('Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
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

		// Sub Title
		$this->add_control(
			'subtitle_heading',
			[
				'label'     => esc_html__('Sub Title', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'subtitle_space',
			[
				'label'     => esc_html__('Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .subtitle' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'subtitle_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
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

		// Description
		$this->add_control(
			'desc_heading',
			[
				'label'     => esc_html__('Description', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'desc_space',
			[
				'label'     => esc_html__('Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
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
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .description',
			]
		);

		$this->end_controls_section();
	}

	protected function register_image_style_controls()
	{

		$this->start_controls_section(
			'section_image_style',
			[
				'label' => esc_html__('Image', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'img_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem'],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .thumb img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'img_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem'],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 700,
						'step' => 1,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'object_fit',
			[
				'label'     => esc_html__('Object Fit', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'condition' => [
					'img_height[size]!' => '',
				],
				'options'   => [
					''        => esc_html__('Default', 'animation-addons-for-elementor'),
					'fill'    => esc_html__('Fill', 'animation-addons-for-elementor'),
					'cover'   => esc_html__('Cover', 'animation-addons-for-elementor'),
					'contain' => esc_html__('Contain', 'animation-addons-for-elementor'),
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .thumb img' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'object_position',
			[
				'label'     => esc_html__('Object Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'center center' => esc_html__('Center Center', 'animation-addons-for-elementor'),
					'center left'   => esc_html__('Center Left', 'animation-addons-for-elementor'),
					'center right'  => esc_html__('Center Right', 'animation-addons-for-elementor'),
					'top center'    => esc_html__('Top Center', 'animation-addons-for-elementor'),
					'top left'      => esc_html__('Top Left', 'animation-addons-for-elementor'),
					'top right'     => esc_html__('Top Right', 'animation-addons-for-elementor'),
					'bottom center' => esc_html__('Bottom Center', 'animation-addons-for-elementor'),
					'bottom left'   => esc_html__('Bottom Left', 'animation-addons-for-elementor'),
					'bottom right'  => esc_html__('Bottom Right', 'animation-addons-for-elementor'),
				],
				'default'   => 'center center',
				'selectors' => [
					'{{WRAPPER}} .thumb img' => 'object-position: {{VALUE}};',
				],
				'condition' => [
					'object_fit' => 'cover',
				],
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
		$this->add_render_attribute('wrapper', ['class' => 'wcf--filterable-slider']);
?>
		<div <?php $this->print_render_attribute_string('wrapper'); ?>>
			<?php
			$this->render_filter($settings);
			$this->render_projects($settings)
			?>
		</div>
	<?php
	}

	protected function render_filter($settings)
	{
		if (empty($settings['enable_filter'])) {
			return;
		}
	?>
		<ul class="slide-filter">
			<?php if (! empty($settings['filter_all_label'])): ?>
				<li data-filter="all">
					<?php echo esc_html($settings['filter_all_label']); ?>

					<?php if (! empty($settings['filter_all_count'])) { ?>
						<span class="count"><?php echo esc_html($settings['filter_all_count']); ?></span>
					<?php } ?>
				</li>
			<?php endif; ?>

			<?php foreach ($settings['filter_items'] as $item): ?>
				<li data-filter=".<?php echo esc_attr(str_replace(' ', '', $item['filter_title'])); ?>">
					<?php echo esc_html($item['filter_title']) ?>

					<?php if (! empty($item['filter_count'])) { ?>
						<span class="count"><?php echo esc_html($item['filter_count']); ?></span>
					<?php } ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php
	}

	protected function render_projects($settings)
	{
		if (empty($settings['project_items'])) {
			return;
		}

		//slider settings
		$slider_settings = $this->get_slider_attributes();
		$this->add_render_attribute(
			'slider-wrapper',
			[
				'class'         => ['slider-wrapper'],
				'data-settings' => json_encode($slider_settings), //phpcs:ignore
			]
		);

	?>
		<div <?php $this->print_render_attribute_string('slider-wrapper'); ?>>

			<div <?php $this->print_render_attribute_string('carousel-wrapper'); ?>>
				<div class="swiper-wrapper">
					<?php foreach ($settings['project_items'] as $index => $item) { ?>
						<?php $this->render_project_slide($settings, $item, $index); ?>
					<?php } ?>
				</div>
			</div>

			<!-- navigation and pagination -->
			<?php if (1 < count($settings['project_items'])) : ?>
				<?php $this->render_slider_navigation(); ?>
				<?php $this->render_slider_pagination(); ?>
			<?php endif; ?>
		</div>
	<?php
	}

	protected function render_project_slide($settings, $item, $index)
	{
		$filter_class = '';
		$filter_items = explode(',', $item['project_item_filter_name']);
		if (count($filter_items)) {
			foreach ($filter_items as $filter_item) {
				$filter_class = $filter_class . ' ' . str_replace(' ', '', $filter_item);
			}
		}
	?>
		<div class="swiper-slide <?php echo esc_attr($filter_class); ?>">
			<div class="thumb">
				<?php
				$image_url = Group_Control_Image_Size::get_attachment_image_src($item['project_image']['id'], 'image_size', $settings);

				if (! $image_url && isset($item['project_image']['url'])) {
					$image_url = $item['project_image']['url'];
				}
				$image_html = '<img class="swiper-slide-image" src="' . esc_url($image_url) . '" alt="' . esc_attr(Control_Media::get_image_alt($item['project_image'])) . '" />';

				echo wp_kses_post($image_html);
				?>
			</div>
			<div class="content">
				<<?php Utils::print_validated_html_tag($settings['title_tag']); ?> class="title">
					<?php $this->print_unescaped_setting('title', 'project_items', $index); ?>
				</<?php Utils::print_validated_html_tag($settings['title_tag']); ?>>

				<?php if (! empty($item['subtitle'])) : ?>
					<div class="subtitle"><?php echo esc_html($item['subtitle']); ?></div>
				<?php endif; ?>

				<?php if (! empty($item['description'])) : ?>
					<div class="description"><?php echo wp_kses_post($this->parse_text_editor($item['description'])); ?></div>
				<?php endif; ?>
			</div>
		</div>
<?php
	}
}
