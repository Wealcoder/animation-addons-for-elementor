<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Utils;
use Elementor\Widget_Base;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Post_Rating_Form extends Widget_Base
{

	public function get_name()
	{
		return 'aae--post-rating-form';
	}

	public function get_title()
	{
		return esc_html__('Post Rating Form', 'animation-addons-for-elementor-pro');
	}

	public function get_icon()
	{
		return 'wcf eicon-rating';
	}

	public function get_categories()
	{
		return ['animation-addons-for-elementor-pro'];
	}

	public function get_keywords()
	{
		return ['rating', 'review', 'feedback', 'form'];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['aae-post-rating'];
	}

	public function get_script_depends()
	{
		return ['aae-post-rating'];
	}

	protected function register_controls()
	{
		$this->register_rating_settings();

		$this->register_rating_content_controls();

		$this->register_rating_form_controls();

		$this->style_rating_title();

		$this->style_rating_text();

		$this->style_rating_rating();

		$this->style_rating_input();

		$this->style_rating_button();

		$this->style_error_success_messages();
	}

	protected function register_rating_settings()
	{
		$this->start_controls_section(
			'section_rating_settings',
			[
				'label' => esc_html__('Settings', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->add_control(
			'only_logged_in',
			[
				'label'        => esc_html__('Only Logged-in Users?', 'animation-addons-for-elementor-pro'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'animation-addons-for-elementor-pro'),
				'label_off'    => esc_html__('No', 'animation-addons-for-elementor-pro'),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->add_control(
			'require_approval',
			[
				'label'        => esc_html__('Require Manual Approval?', 'animation-addons-for-elementor-pro'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'animation-addons-for-elementor-pro'),
				'label_off'    => esc_html__('No', 'animation-addons-for-elementor-pro'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_responsive_control(
			'alignment',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'default'   => 'center',
				'options'   => [
					'left'   => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor-pro'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor-pro'),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor-pro'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .aae--post-rating-form, {{WRAPPER}} .rating' => 'text-align: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function register_rating_content_controls()
	{
		$this->start_controls_section(
			'section_rating_content',
			[
				'label' => esc_html__('Content', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__('Title', 'animation-addons-for-elementor-pro'),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'label_block' => false,
				'default'     => esc_html__('How useful was this post?', 'animation-addons-for-elementor-pro'),
				'placeholder' => esc_html__('Type your title here', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'   => esc_html__('Title HTML Tag', 'animation-addons-for-elementor-pro'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
				'default' => 'h3',
			]
		);

		$this->add_control(
			'text',
			[
				'label'       => esc_html__('Text', 'animation-addons-for-elementor-pro'),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'label_block' => false,
				'default'     => esc_html__('click on the star to rate it.', 'animation-addons-for-elementor-pro'),
				'placeholder' => esc_html__('Type your text here', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->end_controls_section();
	}

	protected function register_rating_form_controls()
	{
		$this->start_controls_section(
			'section_rating_form',
			[
				'label' => esc_html__('Form', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->add_control(
			'rating_icon',
			[
				'label'       => esc_html__('Rating Icon', 'animation-addons-for-elementor-pro'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'name_plh_text',
			[
				'label'       => esc_html__('Name Placeholder Text', 'animation-addons-for-elementor-pro'),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'label_block' => true,
				'default'     => esc_html__('Name', 'animation-addons-for-elementor-pro'),
				'placeholder' => esc_html__('Type your placeholder here', 'animation-addons-for-elementor-pro'),
				'condition'   => ['only_logged_in!' => 'yes'],
			]
		);

		$this->add_control(
			'email_plh_text',
			[
				'label'       => esc_html__('Email Placeholder Text', 'animation-addons-for-elementor-pro'),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'label_block' => true,
				'default'     => esc_html__('Email', 'animation-addons-for-elementor-pro'),
				'placeholder' => esc_html__('Type your placeholder here', 'animation-addons-for-elementor-pro'),
				'condition'   => ['only_logged_in!' => 'yes'],
			]
		);

		$this->add_control(
			'review_placeholder',
			[
				'label'       => esc_html__('Review Placeholder Text', 'animation-addons-for-elementor-pro'),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'label_block' => true,
				'default'     => esc_html__('Write your review...', 'animation-addons-for-elementor-pro'),
				'placeholder' => esc_html__('Type your placeholder here', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->add_control(
			'btn_text',
			[
				'label'       => esc_html__('Button Text', 'animation-addons-for-elementor-pro'),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'label_block' => false,
				'default'     => esc_html__('Submit', 'animation-addons-for-elementor-pro'),
				'placeholder' => esc_html__('Type your button text here', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->end_controls_section();
	}

	// Title
	protected function style_rating_title()
	{
		$this->start_controls_section(
			'style_title',
			[
				'label' => esc_html__('Title', 'animation-addons-for-elementor-pro'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typo',
				'selector' => '{{WRAPPER}} .title',
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// Text
	protected function style_rating_text()
	{
		$this->start_controls_section(
			'style_text',
			[
				'label' => esc_html__('Text', 'animation-addons-for-elementor-pro'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .text' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'text_typo',
				'selector' => '{{WRAPPER}} .text',
			]
		);

		$this->add_responsive_control(
			'text_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .text' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// Rating
	protected function style_rating_rating()
	{
		$this->start_controls_section(
			'style_rating',
			[
				'label' => esc_html__('Rating', 'animation-addons-for-elementor-pro'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'rating_color',
			[
				'label'     => esc_html__('Normal Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .rating' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'rating_fill_color',
			[
				'label'     => esc_html__('Fill Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .rating label:hover, {{WRAPPER}} .rating label:hover ~ label, {{WRAPPER}} .rating input:checked + label, {{WRAPPER}} .rating input:checked + label ~ label ' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'rating_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'rating_icon_gap',
			[
				'label'      => esc_html__('Icon Gap', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => -5,
						'max' => 50,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating label' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'rating_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// Input
	protected function style_rating_input()
	{
		// Style Review
		$this->start_controls_section(
			'style_input_fields',
			[
				'label' => esc_html__('Input', 'animation-addons-for-elementor-pro'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'input_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'input_plh_color',
			[
				'label'     => esc_html__('Placeholder Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .review::placeholder, {{WRAPPER}} .anon-fields input::placeholder' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'input_typo',
				'selector' => '{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'input_border',
				'selector' => '{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input',
			]
		);

		$this->add_responsive_control(
			'input_b_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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
					'{{WRAPPER}} .anon-fields input' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Name and Email
		$this->add_control(
			'name_email_heading',
			[
				'label'     => esc_html__('Name & Email', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'name_email_dir',
			[
				'label'     => esc_html__('Direction', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'row',
				'options'   => [
					'row'    => esc_html__('Row', 'animation-addons-for-elementor-pro'),
					'column' => esc_html__('Column', 'animation-addons-for-elementor-pro'),
				],
				'selectors' => [
					'{{WRAPPER}} .anon-fields' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'name_email_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .anon-fields' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Textarea
		$this->add_control(
			'textarea_heading',
			[
				'label'     => esc_html__('Textarea', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'review_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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
					'{{WRAPPER}} .review' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'review_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .review' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'review_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .review-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// Button
	protected function style_rating_button()
	{
		$this->start_controls_section(
			'style_button',
			[
				'label' => esc_html__('Button', 'animation-addons-for-elementor-pro'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'btn_typo',
				'selector' => '{{WRAPPER}} .submit-btn',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'btn_bg',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .submit-btn',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'btn_border',
				'selector' => '{{WRAPPER}} .submit-btn',
			]
		);

		$this->add_responsive_control(
			'btn_b_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .submit-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'btn_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .submit-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Hover Tabs
		$this->start_controls_tabs(
			'style_btn_tabs'
		);

		// Normal
		$this->start_controls_tab(
			'btn_normal_tab',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->add_control(
			'btn_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .submit-btn' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		// Hover
		$this->start_controls_tab(
			'btn_hover_tab',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor-pro'),
			]
		);

		$this->add_control(
			'btn_h_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .submit-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'btn_h_bg',
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .submit-btn:hover',
			]
		);

		$this->add_control(
			'btn_h_b_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .submit-btn:hover' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'btn_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .submit-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();
	}

	// Messages
	protected function style_error_success_messages()
	{
		$this->start_controls_section(
			'style_success_message',
			[
				'label' => esc_html__('Success Message', 'animation-addons-for-elementor-pro'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'success_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #aae-review-success-message p' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'success_typo',
				'selector' => '{{WRAPPER}} #aae-review-success-message p',
			]
		);

		$this->add_responsive_control(
			'success_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} #aae-review-success-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_error_message',
			[
				'label' => esc_html__('Error Message', 'animation-addons-for-elementor-pro'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'error_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor-pro'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #aae-review-error-message p' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'error_typo',
				'selector' => '{{WRAPPER}} #aae-review-error-message p',
			]
		);

		$this->add_responsive_control(
			'error_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor-pro'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} #aae-review-error-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();
	}

	// Render
	protected function render()
	{
		$settings = $this->get_settings_for_display();
		$post_id  = get_the_ID();

		// Only logged-in users can submit
		$only_logged_in = isset($settings['only_logged_in']) && $settings['only_logged_in'] === 'yes';

		if ($only_logged_in && ! is_user_logged_in()) {
?>
			<div class="aae--post-rating-form">
				<p class="login-required-message">Only logged-in users can submit a review.</p>
			</div>
		<?php
			return;
		}
		?>

		<div class="aae--post-rating-form" data-require-approval=<?php echo $settings['require_approval']; ?>>
			<<?php Utils::print_validated_html_tag($settings['title_tag']); ?> class="title">
				<?php echo esc_html($settings['title']); ?>
			</<?php Utils::print_validated_html_tag($settings['title_tag']); ?>>

			<p class="text"><?php echo esc_html($settings['text']); ?></p>

			<div class="rating-form">
				<input type="hidden" id="post_id" value="<?php echo esc_attr($post_id); ?>">

				<div class="rating">
					<?php for ($i = 5; $i >= 1; $i--) : ?>
						<input id="rating-<?php echo $i; ?>" type="radio" name="rating" value="<?php echo $i; ?>">
						<label for="rating-<?php echo $i; ?>">
							<?php Icons_Manager::render_icon($settings['rating_icon'], ['aria-hidden' => 'true']); ?>
						</label>
					<?php endfor; ?>
				</div>

				<?php
				if ('yes' !== $settings['only_logged_in']) {
					if (Plugin::$instance->editor->is_edit_mode() || ! is_user_logged_in()) {
				?>
						<div class="anon-fields">
							<input type="text" id="reviewer_name" placeholder="<?php echo esc_attr($settings['name_plh_text']); ?>"
								required>
							<input type="email" id="reviewer_email" placeholder="<?php echo esc_attr($settings['email_plh_text']); ?>"
								required>
						</div>
				<?php
					}
				}
				?>

				<div class="review-message">
					<input type="hidden" id="selected_rating" value="">
					<textarea name="review" id="review_text" class="review"
						placeholder="<?php echo esc_attr($settings['review_placeholder']); ?>"></textarea>
				</div>

				<button type="submit" id="aae-post-rating-btn" class="submit-btn">
					<?php echo esc_html($settings['btn_text']); ?>
				</button>
			</div>

			<div id="aae-review-success-message"></div>
			<div id="aae-review-error-message"></div>
		</div>
<?php
	}
}
