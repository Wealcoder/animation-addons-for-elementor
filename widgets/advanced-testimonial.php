<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use WCF_ADDONS\WCF_Slider_Trait;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Testimonial
 *
 * Elementor widget for testimonial.
 *
 * @since 1.0.0
 */
class Advanced_Testimonial extends Widget_Base
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
		return 'wcf--a-testimonial';
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
		return esc_html__('Advanced Testimonial', 'animation-addons-for-elementor');
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
		return 'wcf eicon-testimonial';
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

	public function get_style_depends()
	{
		return ['swiper', 'aae-a-testimonial'];
	}

	public function get_script_depends()
	{
		return ['swiper', 'wcf--slider'];
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
		// Layout
		$this->register_testimonial_layout();

		// Content
		$this->register_testimonial_content();

		// Settings
		$this->register_testimonial_settings();

		// Slider Control
		$this->register_testimonial_slider_controls();

		// Slide Style
		$this->style_testimonial_slide();

		// Feedback Style
		$this->style_testimonial_feedback();

		// Quote Image Style.
		$this->style_testimonial_quote();

		// Rating Style
		$this->style_testimonial_rating();

		// Reason Style
		$this->style_testimonial_reason();

		// Client Content
		$this->style_testimonial_client_content();

		// Slider Navigation
		$this->style_slider_navigation();

		// Slider Pagination
		$this->style_slider_pagination();
	}

	protected function register_testimonial_layout()
	{
		$this->start_controls_section(
			'section_layout',
			[
				'label' => esc_html__('Layout', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'element_list',
			[
				'label'   => esc_html__('Testimonial Style', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__('One', 'animation-addons-for-elementor'),
					'2' => esc_html__('Two', 'animation-addons-for-elementor'),
					'3' => esc_html__('Three', 'animation-addons-for-elementor'),
					'4' => esc_html__('Four', 'animation-addons-for-elementor'),
					'5' => esc_html__('Five', 'animation-addons-for-elementor'),
					'6' => esc_html__('Six', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_responsive_control(
			'tsm_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
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
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .swiper-slide'       => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .swiper-slide .wrap' => 'justify-content: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function register_testimonial_content()
	{
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__('Content', 'animation-addons-for-elementor'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'tsm_content',
			[
				'label'   => esc_html__('Feedback', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::TEXTAREA,
				'dynamic' => [
					'active' => true,
				],
				'rows'    => '7',
				'default' => esc_html__('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'animation-addons-for-elementor'),
			]
		);

		$repeater->add_control(
			'tsm_quote',
			[
				'label'   => esc_html__('Quote/Image', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => [
					'active' => true,
				],
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'tsm_reason',
			[
				'label'   => esc_html__('Feedback Reason', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__('Flexibility', 'animation-addons-for-elementor'),
			]
		);

		$repeater->add_control(
			'tsm_rating',
			[
				'label'   => __('Rating', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 5,
				'step'    => 0.5,
				'default' => 4.5,
			]
		);

		$repeater->add_control(
			'tsm_name',
			[
				'label'       => esc_html__('Client Name', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
				'default'     => 'John Doe',
			]
		);

		$repeater->add_control(
			'tsm_role',
			[
				'label'       => esc_html__('Designation', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
				'default'     => 'Developer',
			]
		);

		$repeater->add_control(
			'tsm_image',
			[
				'label'   => esc_html__('Client Image', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => [
					'active' => true,
				],
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tsm_item_bg',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}}',
			]
		);

		$this->add_control(
			'testimonials',
			[
				'label'   => esc_html__('Testimonials', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::REPEATER,
				'fields'  => $repeater->get_controls(),
				'default' => [[], [], [], [], []],
			]
		);

		$this->end_controls_section();
	}

	protected function register_testimonial_settings()
	{
		$this->start_controls_section(
			'section_settings',
			[
				'label' => esc_html__('Settings', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'quote_show',
			[
				'label'        => esc_html__('Show Quote/Image', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'client_img_show',
			[
				'label'        => esc_html__('Show Client Image', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'rating_show',
			[
				'label'        => esc_html__('Show Rating Image', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->add_control(
			'reason_show',
			[
				'label'        => esc_html__('Show Feedback Reason', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->add_responsive_control(
			'client_content_direction',
			[
				'label'     => esc_html__('Client Content Direction', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'row'            => [
						'title' => esc_html__('Row - Horizontal', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-arrow-right',
					],
					'column'         => [
						'title' => esc_html__('Column - Vertical', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-arrow-down',
					],
					'row-reverse'    => [
						'title' => esc_html__('Row - Reversed', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-arrow-left',
					],
					'column-reverse' => [
						'title' => esc_html__('Column - Reversed', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-arrow-up',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .aae--a-testimonial .author' => 'flex-direction: {{VALUE}};',
				],
				'condition' => ['element_list!' => '2'],
			]
		);

		$this->end_controls_section();
	}

	protected function style_testimonial_slide()
	{
		$this->start_controls_section(
			'style_slide',
			[
				'label' => esc_html__('Slide', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'slide_background',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .slide',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'slide_border',
				'selector' => '{{WRAPPER}} .slide',
			]
		);

		$this->add_responsive_control(
			'slide_b_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .slide' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'slide_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .slide' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'slide_sep_color',
			[
				'label'     => esc_html__('Separator Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .swiper-slide::after' => 'background-color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'slide_sep_right',
			[
				'label'      => esc_html__('Separator Position', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => -100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .swiper-slide::after' => 'right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_testimonial_feedback()
	{
		$this->start_controls_section(
			'style_tsm_feedback',
			[
				'label' => esc_html__('Feedback', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'feedback_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .feedback' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'feedback_typo',
				'selector' => '{{WRAPPER}} .feedback',
			]
		);

		$this->add_responsive_control(
			'feedback_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .feedback' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'feedback_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .feedback' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'feedback_border',
				'selector' => '{{WRAPPER}} .feedback',
			]
		);

		$this->end_controls_section();
	}

	protected function style_testimonial_rating()
	{
		$this->start_controls_section(
			'style_tsm_rating',
			[
				'label'     => esc_html__('Rating', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['rating_show' => 'yes'],
			]
		);

		$this->add_control(
			'rating_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .stars-outer, {{WRAPPER}} .stars-inner' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'rating_size',
			[
				'label'      => esc_html__('Stars Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .stars-outer::before, {{WRAPPER}} .stars-inner::before' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'rating_gap',
			[
				'label'      => esc_html__('Stars Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 20,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .stars-outer::before, {{WRAPPER}} .stars-inner::before' => 'letter-spacing: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'rating_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_testimonial_quote()
	{
		$this->start_controls_section(
			'style_quote_img',
			[
				'label'     => esc_html__('Quote/Image', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['quote_show' => 'yes'],
			]
		);

		$this->add_responsive_control(
			'quote_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .quote img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'quote_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem'],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .quote img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'quote_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .quote' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_testimonial_reason()
	{
		$this->start_controls_section(
			'style_tsm_reason',
			[
				'label'     => esc_html__('Reason/Text', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['reason_show' => 'yes'],
			]
		);

		$this->add_control(
			'reason_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .reason' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'reason_typo',
				'selector' => '{{WRAPPER}} .reason',
			]
		);

		$this->add_responsive_control(
			'reason_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .reason' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_testimonial_client_content()
	{
		$this->start_controls_section(
			'style_client_content',
			[
				'label' => esc_html__('Client Content', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'separator_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .info' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'separator_padding',
			[
				'label'      => esc_html__('Border Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .info' => 'padding-left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'name_heading',
			[
				'label'     => esc_html__('Name', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'tsm_name_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
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
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .swiper-slide .wrap' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'name_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typo',
				'selector' => '{{WRAPPER}} .name',
			]
		);

		$this->add_responsive_control(
			'name_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .name' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'role_heading',
			[
				'label'     => esc_html__('Designation', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'role_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .designation' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'role_typo',
				'selector' => '{{WRAPPER}} .designation',
			]
		);

		$this->add_responsive_control(
			'role_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .designation' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Client Image
		$this->add_control(
			'client_img_heading',
			[
				'label'     => esc_html__('Image', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => ['client_img_show' => 'yes'],
			]
		);

		$this->add_responsive_control(
			'client_img_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem'],
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
					'{{WRAPPER}} .image img'      => 'width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .style-2 .image' => 'flex: {{SIZE}}{{UNIT}} 0 0;',
				],
				'condition'  => ['client_img_show' => 'yes'],
			]
		);

		$this->add_responsive_control(
			'client_img_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem'],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .image img' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['client_img_show' => 'yes'],
			]
		);

		$this->add_responsive_control(
			'client_img_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => ['client_img_show' => 'yes'],
			]
		);

		$this->add_responsive_control(
			'client_img_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => ['client_img_show' => 'yes'],
			]
		);

		$this->end_controls_section();
	}

	private function register_testimonial_slider_controls()
	{
		$this->start_controls_section(
			'sec_slider_options',
			[
				'label' => esc_html__('Slider Options', 'animation-addons-for-elementor'),
			]
		);

		$this->register_slider_controls();

		$this->end_controls_section();
	}

	private function style_slider_navigation()
	{
		$this->start_controls_section(
			'style_navigation',
			[
				'label'     => esc_html__('Navigation', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['navigation' => 'yes'],
			]
		);

		$this->register_slider_navigation_style_controls();

		$this->end_controls_section();
	}

	private function style_slider_pagination()
	{
		$this->start_controls_section(
			'style_pagination',
			[
				'label'     => esc_html__('Pagination', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['pagination' => 'yes'],
			]
		);

		$this->register_slider_pagination_style_controls();

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

		if (empty($settings['testimonials'])) {
			return;
		}

		// slider settings
		$slider_settings = $this->get_slider_attributes();

		$this->add_render_attribute(
			'wrapper',
			[
				'class'         => ['wcf__t_slider-wrapper aae--a-testimonial', 'style-' . $settings['element_list']],
				'data-settings' => json_encode($slider_settings), //phpcs:ignore
			]
		);


?>
		<div <?php $this->print_render_attribute_string('wrapper'); ?>>
			<div <?php $this->print_render_attribute_string('carousel-wrapper'); ?>>
				<div class="swiper-wrapper">
					<?php foreach ($settings['testimonials'] as $index => $item) { ?>
						<div class="swiper-slide">
							<?php
							if ('2' === $settings['element_list']) {
								$this->render_testimonial_two($settings, $item, $index);
							} elseif ('3' === $settings['element_list']) {
								$this->render_testimonial_three($settings, $item, $index);
							} elseif ('4' === $settings['element_list']) {
								$this->render_testimonial_four($settings, $item, $index);
							} elseif ('5' === $settings['element_list']) {
								$this->render_testimonial_five($settings, $item, $index);
							} elseif ('6' === $settings['element_list']) {
								$this->render_testimonial_six($settings, $item, $index);
							} else {
								$this->render_testimonial_one($settings, $item, $index);
							}
							?>
						</div>
					<?php } ?>
				</div>
			</div>

			<!-- Navigation and Pagination -->
			<?php if (1 < count($settings['testimonials'])) : ?>
				<?php $this->render_slider_navigation(); ?>
				<?php $this->render_slider_pagination(); ?>
			<?php endif; ?>
		</div>
	<?php
	}

	protected function render_testimonial_one($settings, $item, $index)
	{
	?>
		<div class="slide elementor-repeater-item-<?php echo esc_attr($item['_id']) ?>">
			<?php if ($item['tsm_quote']['url'] && 'yes' === $settings['quote_show']) { ?>
				<div class="quote">
					<img src="<?php echo esc_url($item['tsm_quote']['url']); ?>" alt="Quote">
				</div>
			<?php } ?>
			<?php if ('yes' === $settings['rating_show']) {
				$rating = $item['tsm_rating'];
			?>
				<div class="rating" data-rating="<?php echo esc_attr($rating); ?>">
					<div class="stars-outer">
						<div class="stars-inner" style="width: <?php echo esc_attr(($rating / 5) * 100); ?>%;"></div>
					</div>
				</div>
			<?php } ?>
			<?php if ('yes' === $settings['reason_show']) { ?>
				<div class="reason">
					<?php $this->print_unescaped_setting('tsm_reason', 'testimonials', $index); ?>
				</div>
			<?php } ?>
			<p class="feedback">
				<?php $this->print_unescaped_setting('tsm_content', 'testimonials', $index); ?>
			</p>
			<div class="wrap">
				<div class="author">
					<?php if ($item['tsm_image']['url'] && 'yes' === $settings['client_img_show']) { ?>
						<div class="image">
							<img src="<?php echo esc_url($item['tsm_image']['url']); ?>" alt="Image">
						</div>
					<?php } ?>
					<div class="info">
						<div class="name"><?php $this->print_unescaped_setting('tsm_name', 'testimonials', $index); ?></div>
						<div class="designation"><?php $this->print_unescaped_setting('tsm_role', 'testimonials', $index); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	protected function render_testimonial_two($settings, $item, $index)
	{ ?>
		<div class="slide elementor-repeater-item-<?php echo esc_attr($item['_id']) ?>">
			<?php if ($item['tsm_image']['url'] && 'yes' === $settings['client_img_show']) { ?>
				<div class="image">
					<img src="<?php echo esc_url($item['tsm_image']['url']); ?>" alt="Image">
				</div>
			<?php } ?>
			<div class="content">
				<?php if ($item['tsm_quote']['url'] && 'yes' === $settings['quote_show']) { ?>
					<div class="quote">
						<img src="<?php echo esc_url($item['tsm_quote']['url']); ?>" alt="Quote">
					</div>
				<?php } ?>
				<?php if ('yes' === $settings['rating_show']) {
					$rating = $item['tsm_rating'];
				?>
					<div class="rating" data-rating="<?php echo esc_attr($rating); ?>">
						<div class="stars-outer">
							<div class="stars-inner" style="width: <?php echo esc_attr(($rating / 5) * 100); ?>%;"></div>
						</div>
					</div>
				<?php } ?>
				<?php if ('yes' === $settings['reason_show']) { ?>
					<div class="reason">
						<?php $this->print_unescaped_setting('tsm_reason', 'testimonials', $index); ?>
					</div>
				<?php } ?>
				<p class="feedback">
					<?php $this->print_unescaped_setting('tsm_content', 'testimonials', $index); ?>
				</p>
				<div class="wrap">
					<div class="author">
						<div class="info">
							<div class="name"><?php $this->print_unescaped_setting('tsm_name', 'testimonials', $index); ?>
							</div>
							<div class="designation">
								<?php $this->print_unescaped_setting('tsm_role', 'testimonials', $index); ?></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	protected function render_testimonial_three($settings, $item, $index)
	{
	?>
		<div class="slide elementor-repeater-item-<?php echo esc_attr($item['_id']) ?>">
			<div class="wrap">
				<div class="author">
					<?php if ($item['tsm_image']['url'] && 'yes' === $settings['client_img_show']) { ?>
						<div class="image">
							<img src="<?php echo esc_url($item['tsm_image']['url']); ?>" alt="Image">
						</div>
					<?php } ?>
					<div class="info">
						<div class="name"><?php $this->print_unescaped_setting('tsm_name', 'testimonials', $index); ?></div>
						<div class="designation"><?php $this->print_unescaped_setting('tsm_role', 'testimonials', $index); ?>
						</div>
					</div>
				</div>
			</div>
			<?php if ($item['tsm_quote']['url'] && 'yes' === $settings['quote_show']) { ?>
				<div class="quote">
					<img src="<?php echo esc_url($item['tsm_quote']['url']); ?>" alt="Quote">
				</div>
			<?php } ?>
			<p class="feedback">
				<?php $this->print_unescaped_setting('tsm_content', 'testimonials', $index); ?>
			</p>
			<?php if ('yes' === $settings['rating_show']) {
				$rating = $item['tsm_rating'];
			?>
				<div class="rating" data-rating="<?php echo esc_attr($rating); ?>">
					<div class="stars-outer">
						<div class="stars-inner" style="width: <?php echo esc_attr(($rating / 5) * 100); ?>%;"></div>
					</div>
				</div>
			<?php } ?>
			<?php if ('yes' === $settings['reason_show']) { ?>
				<div class="reason">
					<?php $this->print_unescaped_setting('tsm_reason', 'testimonials', $index); ?>
				</div>
			<?php } ?>
		</div>
	<?php
	}

	protected function render_testimonial_four($settings, $item, $index)
	{
	?>
		<div class="slide elementor-repeater-item-<?php echo esc_attr($item['_id']) ?>">
			<?php if ($item['tsm_quote']['url'] && 'yes' === $settings['quote_show']) { ?>
				<div class="quote">
					<img src="<?php echo esc_url($item['tsm_quote']['url']); ?>" alt="Quote">
				</div>
			<?php } ?>
			<p class="feedback">
				<?php $this->print_unescaped_setting('tsm_content', 'testimonials', $index); ?>
			</p>
			<?php if ('yes' === $settings['rating_show']) {
				$rating = $item['tsm_rating'];
			?>
				<div class="rating" data-rating="<?php echo esc_attr($rating); ?>">
					<div class="stars-outer">
						<div class="stars-inner" style="width: <?php echo esc_attr(($rating / 5) * 100); ?>%;"></div>
					</div>
				</div>
			<?php } ?>
			<?php if ('yes' === $settings['reason_show']) { ?>
				<div class="reason">
					<?php $this->print_unescaped_setting('tsm_reason', 'testimonials', $index); ?>
				</div>
			<?php } ?>
			<div class="wrap">
				<div class="author">
					<?php if ($item['tsm_image']['url'] && 'yes' === $settings['client_img_show']) { ?>
						<div class="image">
							<img src="<?php echo esc_url($item['tsm_image']['url']); ?>" alt="Image">
						</div>
					<?php } ?>
					<div class="info">
						<div class="name"><?php $this->print_unescaped_setting('tsm_name', 'testimonials', $index); ?></div>
						<div class="designation"><?php $this->print_unescaped_setting('tsm_role', 'testimonials', $index); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	protected function render_testimonial_five($settings, $item, $index)
	{
	?>
		<div class="slide elementor-repeater-item-<?php echo esc_attr($item['_id']) ?>">
			<div class="top">
				<?php if ('yes' === $settings['reason_show']) { ?>
					<div class="reason">
						<?php $this->print_unescaped_setting('tsm_reason', 'testimonials', $index); ?>
					</div>
				<?php } ?>
				<?php if ('yes' === $settings['rating_show']) {
					$rating = $item['tsm_rating'];
				?>
					<div class="rating" data-rating="<?php echo esc_attr($rating); ?>">
						<div class="stars-outer">
							<div class="stars-inner" style="width: <?php echo esc_attr(($rating / 5) * 100); ?>%;"></div>
						</div>
					</div>
				<?php } ?>
			</div>
			<p class="feedback">
				<?php $this->print_unescaped_setting('tsm_content', 'testimonials', $index); ?>
			</p>
			<div class="wrap">
				<div class="author">
					<?php if ($item['tsm_image']['url'] && 'yes' === $settings['client_img_show']) { ?>
						<div class="image">
							<img src="<?php echo esc_url($item['tsm_image']['url']); ?>" alt="Image">
						</div>
					<?php } ?>
					<div class="info">
						<div class="name"><?php $this->print_unescaped_setting('tsm_name', 'testimonials', $index); ?></div>
						<div class="designation"><?php $this->print_unescaped_setting('tsm_role', 'testimonials', $index); ?>
						</div>
					</div>
				</div>
				<?php if ($item['tsm_quote']['url'] && 'yes' === $settings['quote_show']) { ?>
					<div class="quote">
						<img src="<?php echo esc_url($item['tsm_quote']['url']); ?>" alt="Quote">
					</div>
				<?php } ?>
			</div>
		</div>
	<?php
	}

	protected function render_testimonial_six($settings, $item, $index)
	{
	?>
		<div class="slide six elementor-repeater-item-<?php echo esc_attr($item['_id']) ?>">
			<?php if ($item['tsm_quote']['url'] && 'yes' === $settings['quote_show']) { ?>
				<div class="quote">
					<img src="<?php echo esc_url($item['tsm_quote']['url']); ?>" alt="Quote">
				</div>
			<?php } ?>
			<p class="feedback">
				<?php $this->print_unescaped_setting('tsm_content', 'testimonials', $index); ?>
			</p>
			<div class="wrap">
				<div class="author">
					<?php if ($item['tsm_image']['url'] && 'yes' === $settings['client_img_show']) { ?>
						<div class="image">
							<img src="<?php echo esc_url($item['tsm_image']['url']); ?>" alt="Image">
						</div>
					<?php } ?>
					<div class="info">
						<div class="name"><?php $this->print_unescaped_setting('tsm_name', 'testimonials', $index); ?></div>
						<div class="designation"><?php $this->print_unescaped_setting('tsm_role', 'testimonials', $index); ?>
						</div>
					</div>
				</div>
				<div class="rating-reason">
					<?php if ('yes' === $settings['reason_show']) { ?>
						<div class="reason">
							<?php $this->print_unescaped_setting('tsm_reason', 'testimonials', $index); ?>
						</div>
					<?php } ?>
					<?php if ('yes' === $settings['rating_show']) {
						$rating = $item['tsm_rating'];
					?>
						<div class="rating" data-rating="<?php echo esc_attr($rating); ?>">
							<div class="stars-outer">
								<div class="stars-inner" style="width: <?php echo esc_attr(($rating / 5) * 100); ?>%;"></div>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
<?php
	}
}
