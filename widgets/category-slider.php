<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WCF_ADDONS\WCF_Slider_Trait;
use WP_Query;

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
class Category_Slider extends Widget_Base
{

	use WCF_Slider_Trait;

	public $query = null;

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
		return 'aae--category-slider';
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
		return esc_html__('Category Slider', 'animation-addons-for-elementor');
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
		return ['swiper', 'wcf--category-slider'];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['swiper', 'wcf--category-showcase'];
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

		// Layout
		$this->register_category_layout();

		// Query
		$this->register_categories_query();

		// slide controls
		$this->start_controls_section(
			'section_slider_options',
			[
				'label' => esc_html__('Slider Settings', 'animation-addons-for-elementor'),
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

		// Layout Style
		$this->style_category_layout();

		// Icon Style
		$this->style_category_icon();

		// Thumb Style
		$this->style_category_img();

		// Title Style
		$this->style_category_name();

		// Count Style
		$this->style_category_count();

		// Slogan Style
		$this->style_category_slogan();


		// Description Style
		$this->style_category_desc();

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

		// Navigation Position Options

		$this->add_responsive_control(
			'arrows_type',
			[
				'label'     => esc_html__('Arrows Position Type', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'static',
				'options'   => [
					'static'   => esc_html__('Default', 'animation-addons-for-elementor'),
					'absolute' => esc_html__('Absolute', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf-arrow' => 'position: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrows_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start'    => [
						'title' => esc_html__('Start', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-justify-start-h',
					],
					'center'        => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-justify-center-h',
					],
					'flex-end'      => [
						'title' => esc_html__('End', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-justify-end-h',
					],
					'space-between' => [
						'title' => esc_html__('Space Between', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-justify-space-between-h',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .ts-navigation' => 'justify-content: {{VALUE}};',
				],
				'condition' => [
					'arrows_type' => 'static',
				],
			]
		);

		$this->add_control(
			'prev_pos_toggle',
			[
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label'        => esc_html__('Arrow Prev', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Default', 'animation-addons-for-elementor'),
				'label_on'     => esc_html__('Custom', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'condition'    => [
					'arrows_type' => 'absolute',
				],
			]
		);

		$this->start_popover();

		$this->add_responsive_control(
			'prev_pos_left',
			[
				'label'      => esc_html__('Left', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => -1200,
						'max' => 1200,
					],
					'%'  => [
						'min' => -100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-arrow.wcf-arrow-prev' => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'prev_pos_btm',
			[
				'label'      => esc_html__('Top', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => -500,
						'max' => 500,
					],
					'%'  => [
						'min' => -100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-arrow.wcf-arrow-prev' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_popover();

		$this->add_control(
			'next_pos_toggle',
			[
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label'        => esc_html__('Arrow Next', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Default', 'animation-addons-for-elementor'),
				'label_on'     => esc_html__('Custom', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'condition'    => [
					'arrows_type' => 'absolute',
				],
			]
		);

		$this->start_popover();

		$this->add_responsive_control(
			'next_pos_right',
			[
				'label'      => esc_html__('Right', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => -1200,
						'max' => 1200,
					],
					'%'  => [
						'min' => -100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-arrow.wcf-arrow-next' => 'right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'next_pos_btm',
			[
				'label'      => esc_html__('Top', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => -500,
						'max' => 500,
					],
					'%'  => [
						'min' => -100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-arrow.wcf-arrow-next' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_popover();


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

		$this->add_responsive_control(
			'slider-pagination_gaps',
			[
				'label'      => esc_html__('Spacing', 'animation-addons-for-elementor'),
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
					'{{WRAPPER}} .ts-pagination' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->register_slider_pagination_style_controls();

		$this->add_responsive_control(
			'pagination_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .swiper-pagination-bullet' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

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

	protected function register_categories_query()
	{
		$this->start_controls_section(
			'sec_cat_query',
			[
				'label' => esc_html__('Query', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'cat_show',
			[
				'label' => esc_html__('Show Category', 'animation-addons-for-elementor'),
				'type'  => Controls_Manager::NUMBER,
				'min'   => 1,
			]
		);

		$this->add_control(
			'cat_order_by',
			[
				'label'   => __('Order By', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'  => __('Date', 'animation-addons-for-elementor'),
					'title' => __('Title', 'animation-addons-for-elementor'),
					'rand'  => __('Random', 'animation-addons-for-elementor'),
				],
			]
		);
		$this->add_control(
			'cat_order',
			[
				'label'   => __('Order', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'ASC'  => __('ASC', 'animation-addons-for-elementor'),
					'DESC' => __('DESC', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'cat_empty',
			[
				'label'   => esc_html__('Show Empty Category', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'false',
				'options' => [
					'false' => esc_html__('No', 'animation-addons-for-elementor'),
					'true'  => esc_html__('Yes', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'show_child_cat',
			[
				'label'        => esc_html__('Show Child Categories?', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);


		$this->end_controls_section();
	}

	protected function register_category_layout()
	{
		$this->start_controls_section(
			'sec_category_layout',
			[
				'label' => esc_html__('Layout', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'category_layout',
			[
				'label'   => esc_html__('Layout', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__('One', 'animation-addons-for-elementor'),
					'2' => esc_html__('Two', 'animation-addons-for-elementor'),
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'content_type',
			[
				'label'   => __('Content Type', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'icon'   => __('Icon', 'animation-addons-for-elementor'),
					'title'  => __('Title', 'animation-addons-for-elementor'),
					'total'  => __('Total', 'animation-addons-for-elementor'),
					'desc'   => __('Description', 'animation-addons-for-elementor'),
					'slogan' => __('Slogan', 'animation-addons-for-elementor'),
				],
				'default' => 'title',
			]
		);

		$this->add_control(
			'cat_content_layout',
			[
				'label'        => __('Category Content', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::REPEATER,
				'fields'       => $repeater->get_controls(),
				'item_actions' => [
					'add'       => false,
					'duplicate' => false,
					'remove'    => false,
					'sort'      => true,
				],
				'default'      => [
					['content_type' => 'icon'],
					['content_type' => 'title'],
					['content_type' => 'total'],
					['content_type' => 'desc'],
					['content_type' => 'slogan'],
				],
				'title_field'  => '{{{ content_type }}}',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'count_text',
			[
				'label'       => esc_html__('Article Text', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Articles', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Type your text here', 'animation-addons-for-elementor'),
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'show_icon',
			[
				'label'        => esc_html__('Show Icon', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'show_thumb',
			[
				'label'        => esc_html__('Show Thumb', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => ['category_layout' => '2'],
			]
		);

		$this->add_control(
			'show_counter',
			[
				'label'        => esc_html__('Show Counter', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_slogan',
			[
				'label'        => esc_html__('Show Slogan', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_desc',
			[
				'label'        => esc_html__('Show Description', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}

	protected function style_category_layout()
	{
		$this->start_controls_section(
			'style_cat_layout',
			[
				'label' => esc_html__('Content', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'layout_bg',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .category-item::after',
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .category-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_align',
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
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .category-item' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_category_icon()
	{
		$this->start_controls_section(
			'style_cat_icon',
			[
				'label' => esc_html__('Icon', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'icon_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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
					'{{WRAPPER}} .icon img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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
					'{{WRAPPER}} .icon img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_category_img()
	{
		$this->start_controls_section(
			'style_cat_img',
			[
				'label' => esc_html__('Thumb', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'thumb_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
					'%'  => [
						'min' => 0,
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
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .thumb' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'thumb_b_radius',
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

	protected function style_category_name()
	{
		$this->start_controls_section(
			'style_cat_name',
			[
				'label' => esc_html__('Name', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typo',
				'selector' => '{{WRAPPER}} .name',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'name_border',
				'selector' => '{{WRAPPER}} .name',
			]
		);

		// Tabs
		$this->start_controls_tabs(
			'style_name_tabs'
		);

		// Normal
		$this->start_controls_tab(
			'name_normal_tab',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'name_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .name' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		// Hover
		$this->start_controls_tab(
			'name_hover_tab',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'name_h_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .name:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'name_b_h_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .name:hover' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();


		$this->add_responsive_control(
			'name_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .name' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function style_category_count()
	{
		$this->start_controls_section(
			'style_cat_count',
			[
				'label' => esc_html__('Count', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'count_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cat-count' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'count_typo',
				'selector' => '{{WRAPPER}} .cat-count',
			]
		);

		$this->add_responsive_control(
			'count_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .cat-count' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function style_category_slogan()
	{
		$this->start_controls_section(
			'style_cat_slogan',
			[
				'label' => esc_html__('Slogan', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'slogan_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .slogan' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'slogan_typo',
				'selector' => '{{WRAPPER}} .slogan',
			]
		);

		$this->add_responsive_control(
			'slogan_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .slogan' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function style_category_desc()
	{
		$this->start_controls_section(
			'style_cat_desc',
			[
				'label' => esc_html__('Description', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desc' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typo',
				'selector' => '{{WRAPPER}} .desc',
			]
		);

		$this->add_responsive_control(
			'desc_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .desc' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
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

		$categories = get_categories([
			'number'     => $settings['cat_show'],
			'orderby'    => $settings['cat_order_by'],
			'order'      => $settings['cat_order'],
			'hide_empty' => 'false' === $settings['cat_empty'],
			'parent'     => $settings['show_child_cat'] === 'yes' ? '' : 0,
		]);

		if (count($categories) < 0) {
			return;
		}

		// Slider Settings
		$slider_settings = $this->get_slider_attributes();

		// Wrapper Class
		$this->add_render_attribute(
			'slider-wrapper',
			[
				'class'         => ['aae--category-slider-wrapper style-' . $settings['category_layout']],
				'data-settings' => json_encode($slider_settings), //phpcs:ignore
			]
		);

		// Swiper Class
		$this->add_render_attribute('carousel-wrapper', 'class', ['aae--category-slider',]);
?>

		<div <?php $this->print_render_attribute_string('slider-wrapper'); ?>>
			<div <?php $this->print_render_attribute_string('carousel-wrapper'); ?>>
				<div class="swiper-wrapper">
					<?php
					foreach ($categories as $cat) {
						if ('2' === $settings['category_layout']) {
							$this->render_category_layout_two($cat, $settings);
						} else {
							$this->render_category_layout_one($cat, $settings);
						}
					}
					?>
				</div>
			</div>

			<!-- Navigation and Pagination -->
			<?php if (1 < count($categories)) : ?>
				<?php $this->render_slider_navigation(); ?>
				<?php $this->render_slider_pagination(); ?>
			<?php endif; ?>
		</div>
	<?php
	}

	protected function render_category_layout_one($cat, $settings)
	{
		$image_url = esc_url(get_term_meta($cat->term_id, 'aae_category_image', true));
		$icon_url  = esc_url(get_term_meta($cat->term_id, 'aae_category_icon', true));
		$slogan    = esc_html(get_term_meta($cat->term_id, 'aae_cate_additional_text', true));
	?>
		<div class="swiper-slide">
			<div class="category-item" style="background-image: url('<?php echo esc_url($image_url); ?>')">
				<?php
				foreach ($settings['cat_content_layout'] as $item) {
					if ('icon' === $item['content_type'] && 'yes' === $settings['show_icon']) {
				?>
						<div class="icon">
							<img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($cat->name); ?>">
						</div>
					<?php
					}

					if ('title' === $item['content_type']) {
					?>
						<a class="name" href="<?php echo esc_url(get_term_link($cat->slug, 'category')); ?>">
							<?php echo esc_html($cat->name); ?>
						</a>
					<?php
					}

					if ('total' === $item['content_type'] && 'yes' === $settings['show_counter']) {
					?>
						<div class="cat-count">
							<?php echo esc_html($cat->count . ' ' . $settings['count_text']); ?>
						</div>
					<?php
					}

					if ('slogan' === $item['content_type'] && 'yes' === $settings['show_slogan']) {
					?>
						<p class="slogan"><?php echo esc_html($slogan); ?></p>
					<?php
					}

					if ('desc' === $item['content_type'] && 'yes' === $settings['show_desc']) {
					?>
						<p class="desc"><?php echo esc_html($cat->description); ?></p>
				<?php
					}
				}
				?>
			</div>
		</div>
	<?php
	}

	protected function render_category_layout_two($cat, $settings)
	{
		$image_url = esc_url(get_term_meta($cat->term_id, 'aae_category_image', true));
		$icon_url  = esc_url(get_term_meta($cat->term_id, 'aae_category_icon', true));
		$slogan    = esc_html(get_term_meta($cat->term_id, 'aae_cate_additional_text', true));
	?>
		<div class="swiper-slide">
			<div class="category-item">
				<?php
				foreach ($settings['cat_content_layout'] as $item) {
					if ('icon' === $item['content_type']) {
				?>
						<div class="thumb-wrap">
							<?php if ('yes' === $settings['show_thumb']) { ?>
								<div class="thumb">
									<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($cat->name); ?>">
								</div>
							<?php } ?>
							<?php if ('yes' === $settings['show_icon']) { ?>
								<div class="icon">
									<img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($cat->name); ?>">
								</div>
							<?php } ?>
						</div>
					<?php
					}

					if ('title' === $item['content_type']) {
					?>
						<a class="name" href="<?php echo esc_url(get_term_link($cat->slug, 'category')); ?>">
							<?php echo esc_html($cat->name); ?>
						</a>
					<?php
					}

					if ('total' === $item['content_type'] && 'yes' === $settings['show_counter']) {
					?>
						<div class="cat-count">
							<?php echo esc_html($cat->count . ' ' . $settings['count_text']); ?>
						</div>
					<?php
					}

					if ('slogan' === $item['content_type'] && 'yes' === $settings['show_slogan']) {
					?>
						<p class="slogan"><?php echo esc_html($slogan); ?></p>
					<?php
					}

					if ('desc' === $item['content_type'] && 'yes' === $settings['show_desc']) {
					?>
						<p class="desc"><?php echo esc_html($cat->description); ?></p>
				<?php
					}
				}
				?>
			</div>
		</div>
<?php
	}
}
