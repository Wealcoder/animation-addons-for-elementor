<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Control_Media;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

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
class Advance_Accordion extends Widget_Base
{

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
		return 'wcf--a-accordion';
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
		return esc_html__('Advanced Accordion', 'animation-addons-for-elementor');
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
		return 'wcf eicon-accordion';
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
		return ['wcf--a-accordion'];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['wcf--a-accordion'];
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
		$this->start_controls_section(
			'section_title',
			[
				'label' => esc_html__('Accordion', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'accordion_style',
			[
				'label' => esc_html__('Accordion Style', 'animation-addons-for-elementor'),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => esc_html__('Default', 'animation-addons-for-elementor'),
					'2' => esc_html__('One', 'animation-addons-for-elementor'),
				],
				'separator' => 'after',
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'tab_count',
			[
				'label'       => esc_html__('Number', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('O1', 'animation-addons-for-elementor'),
				'dynamic'     => [
					'active' => true,
				],
				'label_block' => false,
			]
		);

		$repeater->add_control(
			'tab_title',
			[
				'label'       => esc_html__('Title', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Accordion Title', 'animation-addons-for-elementor'),
				'dynamic'     => [
					'active' => true,
				],
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'tab_content',
			[
				'label'   => esc_html__('Content', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => esc_html__('Accordion Content', 'animation-addons-for-elementor'),
			]
		);

		$repeater->add_control(
			'tab_image',
			[
				'label'   => esc_html__('Image', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'tab_btn_text',
			[
				'label'       => esc_html__('Button Text', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Read more', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Type Button Text', 'animation-addons-for-elementor'),
			]
		);

		$repeater->add_control(
			'tab_btn_link',
			[
				'label'       => esc_html__('Link', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::URL,
				'options'     => ['url', 'is_external', 'nofollow'],
				'default'     => [
					'url'         => '',
					'is_external' => true,
					'nofollow'    => true,
				],
				'label_block' => true,
			]
		);

		$this->add_control(
			'tabs',
			[
				'label'       => esc_html__('Accordion Items', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'tab_title'   => esc_html__('Accordion #1', 'animation-addons-for-elementor'),
						'tab_content' => esc_html__('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'animation-addons-for-elementor'),
					],
					[
						'tab_title'   => esc_html__('Accordion #2', 'animation-addons-for-elementor'),
						'tab_content' => esc_html__('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'animation-addons-for-elementor'),
					],
				],
				'title_field' => '{{{ tab_title }}}',
			]
		);

		$this->add_control(
			'selected_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'separator'   => 'before',
				'default'     => [
					'value'   => 'fas fa-plus',
					'library' => 'fa-solid',
				],
				'recommended' => [
					'fa-solid'   => [
						'chevron-down',
						'angle-down',
						'angle-double-down',
						'caret-down',
						'caret-square-down',
					],
					'fa-regular' => [
						'caret-square-down',
					],
				],
				'skin'        => 'inline',
				'label_block' => false,
			]
		);

		$this->add_control(
			'selected_active_icon',
			[
				'label'       => esc_html__('Active Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-minus',
					'library' => 'fa-solid',
				],
				'recommended' => [
					'fa-solid'   => [
						'chevron-up',
						'angle-up',
						'angle-double-up',
						'caret-up',
						'caret-square-up',
					],
					'fa-regular' => [
						'caret-square-up',
					],
				],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'selected_icon[value]!' => '',
				],
			]
		);

		$this->add_control(
			'title_html_tag',
			[
				'label'     => esc_html__('Title HTML Tag', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'div',
				],
				'default'   => 'div',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'first_item_open',
			[
				'label'        => esc_html__('First Item Opened', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'render_type'  => 'template',
				'prefix_class' => 'accordion-first-item-',
			]
		);

		$this->add_control(
			'faq_schema',
			[
				'label'     => esc_html__('FAQ Schema', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SWITCHER,
				'separator' => 'before',
			]
		);

		$this->end_controls_section();


		//style
		$this->start_controls_section(
			'section_title_style',
			[
				'label' => esc_html__('Accordion', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'border_width',
			[
				'label'      => esc_html__('Border Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'range'      => [
					'px' => [
						'max' => 20,
					],
					'em' => [
						'max' => 2,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .accordion-item'                           => 'border-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .accordion-item .tab-content'              => 'border-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .accordion-item .tab-title.element-active' => 'border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'border_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .accordion-item'                           => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .accordion-item .tab-content'              => 'border-top-color: {{VALUE}};',
					'{{WRAPPER}} .accordion-item .tab-title.element-active' => 'border-bottom-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .accordion-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'item_gap',
			[
				'label'      => esc_html__('Item Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .wcf--a-accordion' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_count',
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
					'{{WRAPPER}} .count' => 'color: {{VALUE}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'count_typography',
				'selector' => '{{WRAPPER}} .count',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
			]
		);

		$this->add_responsive_control(
			'count_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .count' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'section_toggle_style_title',
			[
				'label' => esc_html__('Title', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'title_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'  => [
						'title' => esc_html__('Start', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-left',
					],
					'right' => [
						'title' => esc_html__('End', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'default'   => is_rtl() ? 'right' : 'left',
				'toggle'    => false,
				'selectors' => [
					'{{WRAPPER}} .tab-title' => 'text-align: {{VALUE}};',
				]
			]
		);

		$this->add_control(
			'title_background',
			[
				'label'     => esc_html__('Background', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tab-title' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .accordion-icon, {{WRAPPER}} .accordion-title' => 'color: {{VALUE}};',
					'{{WRAPPER}} .accordion-icon svg'                           => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tab_active_color',
			[
				'label'     => esc_html__('Active Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .element-active .accordion-icon, {{WRAPPER}} .element-active .accordion-title' => 'color: {{VALUE}};',
					'{{WRAPPER}} .element-active .accordion-icon svg'                                           => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .accordion-title',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'text_stroke',
				'selector' => '{{WRAPPER}} .accordion-title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'title_shadow',
				'selector' => '{{WRAPPER}} .accordion-title',
			]
		);

		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .tab-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_toggle_style_icon',
			[
				'label'     => esc_html__('Icon', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'selected_icon[value]!' => '',
				],
			]
		);


		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => esc_html__('Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'range'      => [
					'px'  => [
						'max' => 100,
					],
					'em'  => [
						'max' => 1,
					],
					'rem' => [
						'max' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .accordion-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_align',
			[
				'label'   => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'  => [
						'title' => esc_html__('Start', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-left',
					],
					'right' => [
						'title' => esc_html__('End', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'default' => is_rtl() ? 'right' : 'left',
				'toggle'  => false,
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .accordion-icon i:before' => 'color: {{VALUE}};',
					'{{WRAPPER}} .accordion-icon svg'      => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_active_color',
			[
				'label'     => esc_html__('Active Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .element-active .accordion-icon i:before' => 'color: {{VALUE}};',
					'{{WRAPPER}} .element-active .accordion-icon svg'      => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_space',
			[
				'label'      => esc_html__('Spacing', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem', 'custom'],
				'range'      => [
					'px'  => [
						'max' => 100,
					],
					'em'  => [
						'max' => 1,
					],
					'rem' => [
						'max' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .accordion-icon.accordion-icon-left'  => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .accordion-icon.accordion-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_toggle_style_content',
			[
				'label' => esc_html__('Content', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'content_background_color',
			[
				'label'     => esc_html__('Background', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tab-content' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'content_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tab-content' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_typography',
				'selector' => '{{WRAPPER}} .tab-content',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'content_shadow',
				'selector' => '{{WRAPPER}} .tab-content',
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .tab-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Image
		$this->start_controls_section(
			'style_tab_image',
			[
				'label' => esc_html__('Image', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => ['accordion_style' => '2'],
			]
		);

		$this->add_responsive_control(
			'tab_img_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
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
					'{{WRAPPER}} .acc-image img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tab_img_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
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
					'{{WRAPPER}} .acc-image img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tab_img_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .acc-image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();


		// Button
		$this->start_controls_section(
			'acc_btn_style',
			[
				'label' => esc_html__('Button', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => ['accordion_style' => '2'],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'acc_btn_typo',
				'selector' => '{{WRAPPER}} .acc-btn',
			]
		);

		$this->add_responsive_control(
			'acc_btn_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .acc-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'style_acc_btn_tabs'
		);

		// Normal
		$this->start_controls_tab(
			'acc_btn_normal_tab',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'acc_btn_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .acc-btn' => 'color: {{VALUE}}',
					'{{WRAPPER}} .acc-btn::after' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		// Hover
		$this->start_controls_tab(
			'acc_btn_hover_tab',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'acc_btn_h_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .acc-btn:hover' => 'color: {{VALUE}}',
					'{{WRAPPER}} .acc-btn:hover::after' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

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
		$this->add_render_attribute('wrapper', ['class' => 'wcf--a-accordion style-' . $settings['accordion_style']]);
		$id_int = substr($this->get_id_int(), 0, 3);
?>
		<div <?php $this->print_render_attribute_string('wrapper'); ?>>
			<?php
			foreach ($settings['tabs'] as $index => $item) :
				$tab_count = $index + 1;

				$tab_title_setting_key = $this->get_repeater_setting_key('tab_title', 'tabs', $index);

				$tab_content_setting_key = $this->get_repeater_setting_key('tab_content', 'tabs', $index);

				$this->add_render_attribute($tab_title_setting_key, [
					'id'            => 'tab-title-' . $id_int . $tab_count,
					'class'         => ['tab-title'],
					'data-tab'      => $tab_count,
					'role'          => 'button',
					'aria-controls' => 'tab-content-' . $id_int . $tab_count,
					'aria-expanded' => 'false',
				]);

				$this->add_render_attribute($tab_content_setting_key, [
					'id'              => 'tab-content-' . $id_int . $tab_count,
					'class'           => ['tab-content'],
					'data-tab'        => $tab_count,
					'role'            => 'region',
					'aria-labelledby' => 'tab-title-' . $id_int . $tab_count,
				]);
			?>
				<div class="accordion-item">
					<?php
					if ('2' === $settings['accordion_style']) {
						$this->render_advanced_accordion_two($settings, $tab_content_setting_key, $index, $item, $tab_title_setting_key);
					} else {
						$this->render_advanced_accordion_one($settings, $tab_content_setting_key, $index, $item, $tab_title_setting_key);
					}
					?>
				</div>
			<?php endforeach; ?>
			<?php

			if (isset($settings['faq_schema']) && 'yes' === $settings['faq_schema']) {
				$json = [
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'mainEntity' => [],
				];

				foreach ($settings['tabs'] as $index => $item) {
					$json['mainEntity'][] = [
						'@type'          => 'Question',
						'name'           => wp_kses_post($item['tab_title']),
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text'  => $this->parse_text_editor($item['tab_content']),
						],
					];
				}
			?>
				<script type="application/ld+json">
					<?php echo wp_json_encode($json); ?>
				</script>
			<?php } ?>
		</div>
	<?php
	}


	protected function render_advanced_accordion_one($settings, $tab_content_setting_key, $index, $item, $tab_title_setting_key)
	{
	?>
		<<?php Utils::print_validated_html_tag($settings['title_html_tag']); ?>
			<?php $this->print_render_attribute_string($tab_title_setting_key); ?>>
			<span class="accordion-icon accordion-icon-<?php echo esc_attr($settings['icon_align']); ?>" aria-hidden="true">
				<span class="icon-closed"><?php Icons_Manager::render_icon($settings['selected_icon']); ?></span>
				<span class="icon-opened"><?php Icons_Manager::render_icon($settings['selected_active_icon']); ?></span>
			</span>
			<span class="accordion-title" tabindex="0">
				<?php
				if (! empty($item['tab_count'])):
				?> <span class="count"> <?php $this->print_unescaped_setting('tab_count', 'tabs', $index); ?>
					</span><?php
						endif;
							?>

				<?php $this->print_unescaped_setting('tab_title', 'tabs', $index); ?>
			</span>
		</<?php Utils::print_validated_html_tag($settings['title_html_tag']); ?>>
		<div <?php $this->print_render_attribute_string($tab_content_setting_key); ?>>
			<?php $this->print_text_editor($item['tab_content']); ?>
		</div>
	<?php
	}

	protected function render_advanced_accordion_two($settings, $tab_content_setting_key, $index, $item, $tab_title_setting_key)
	{
		$tab_btn_id = 'tab_btn_' . $index;
		$this->add_link_attributes($tab_btn_id, $item['tab_btn_link']);
	?>
		<<?php Utils::print_validated_html_tag($settings['title_html_tag']); ?>
			<?php $this->print_render_attribute_string($tab_title_setting_key); ?>>
			<?php
			if (! empty($item['tab_count'])):
			?> <span class="count"> <?php $this->print_unescaped_setting('tab_count', 'tabs', $index); ?> </span><?php
																												endif;
																													?>
			<span class="accordion-title" tabindex="0">
				<?php $this->print_unescaped_setting('tab_title', 'tabs', $index); ?>
			</span>

			<span class="accordion-icon accordion-icon-<?php echo esc_attr($settings['icon_align']); ?>" aria-hidden="true">
				<span class="icon-closed"><?php Icons_Manager::render_icon($settings['selected_icon']); ?></span>
				<span class="icon-opened"><?php Icons_Manager::render_icon($settings['selected_active_icon']); ?></span>
			</span>
		</<?php Utils::print_validated_html_tag($settings['title_html_tag']); ?>>

		<!-- Accordion Content -->
		<div <?php $this->print_render_attribute_string($tab_content_setting_key); ?>>
			<div class="content-wrap">
				<div class="acc-image">
					<img src="<?php echo esc_url($item['tab_image']['url']); ?>"
						alt="<?php echo esc_attr($item['tab_title']); ?>">
				</div>
				<div class="acc-content">
					<?php $this->print_text_editor($item['tab_content']); ?>
					<a <?php $this->print_render_attribute_string($tab_btn_id); ?> class="acc-btn">
						<?php echo esc_html($item['tab_btn_text']); ?>
					</a>
				</div>
			</div>
		</div>
<?php
	}
}
