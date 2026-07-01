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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Post_Rating_Form extends Widget_Base {


	public function get_name() {
		return 'aae--post-rating-form';
	}

	public function get_title() {
		return esc_html__( 'Post Rating Form', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-rating';
	}

	public function get_categories() {
		return array( 'weal-coder-addon' );
	}

	public function get_keywords() {
		return array( 'rating', 'review', 'feedback', 'form' );
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'aae-post-rating' );
	}

	public function get_script_depends() {
		return array( 'aae-post-rating' );
	}

	protected function register_controls() {
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

	protected function register_rating_settings() {
		$this->start_controls_section(
			'section_rating_settings',
			array(
				'label' => esc_html__( 'Settings', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'only_logged_in',
			array(
				'label'        => esc_html__( 'Only Logged-in Users?', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'require_approval',
			array(
				'label'        => esc_html__( 'Require Manual Approval?', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_responsive_control(
			'alignment',
			array(
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'default'   => 'center',
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .aae--post-rating-form, {{WRAPPER}} .rating' => 'text-align: {{VALUE}};',
				),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	protected function register_rating_content_controls() {
		$this->start_controls_section(
			'section_rating_content',
			array(
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'label_block' => false,
				'default'     => esc_html__( 'How useful was this post?', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your title here', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Title HTML Tag', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				),
				'default' => 'h3',
			)
		);

		$this->add_control(
			'text',
			array(
				'label'       => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'label_block' => false,
				'default'     => esc_html__( 'click on the star to rate it.', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your text here', 'animation-addons-for-elementor' ),
			)
		);

		$this->end_controls_section();
	}

	protected function register_rating_form_controls() {
		$this->start_controls_section(
			'section_rating_form',
			array(
				'label' => esc_html__( 'Form', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'rating_icon',
			array(
				'label'       => esc_html__( 'Rating Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => array(
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_control(
			'name_plh_text',
			array(
				'label'       => esc_html__( 'Name Placeholder Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'label_block' => true,
				'default'     => esc_html__( 'Name', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your placeholder here', 'animation-addons-for-elementor' ),
				'condition'   => array( 'only_logged_in!' => 'yes' ),
			)
		);

		$this->add_control(
			'email_plh_text',
			array(
				'label'       => esc_html__( 'Email Placeholder Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'label_block' => true,
				'default'     => esc_html__( 'Email', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your placeholder here', 'animation-addons-for-elementor' ),
				'condition'   => array( 'only_logged_in!' => 'yes' ),
			)
		);

		$this->add_control(
			'review_placeholder',
			array(
				'label'       => esc_html__( 'Review Placeholder Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'label_block' => true,
				'default'     => esc_html__( 'Write your review...', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your placeholder here', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'btn_text',
			array(
				'label'       => esc_html__( 'Button Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'label_block' => false,
				'default'     => esc_html__( 'Submit', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your button text here', 'animation-addons-for-elementor' ),
			)
		);

		$this->end_controls_section();
	}

	// Title
	protected function style_rating_title() {
		$this->start_controls_section(
			'style_title',
			array(
				'label' => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .title' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typo',
				'selector' => '{{WRAPPER}} .title',
			)
		);

		$this->add_responsive_control(
			'title_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// Text
	protected function style_rating_text() {
		$this->start_controls_section(
			'style_text',
			array(
				'label' => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .text' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typo',
				'selector' => '{{WRAPPER}} .text',
			)
		);

		$this->add_responsive_control(
			'text_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .text' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// Rating
	protected function style_rating_rating() {
		$this->start_controls_section(
			'style_rating',
			array(
				'label' => esc_html__( 'Rating', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'rating_color',
			array(
				'label'     => esc_html__( 'Normal Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .rating' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'rating_fill_color',
			array(
				'label'     => esc_html__( 'Fill Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .rating label:hover, {{WRAPPER}} .rating label:hover ~ label, {{WRAPPER}} .rating input:checked + label, {{WRAPPER}} .rating input:checked + label ~ label ' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'rating_icon_size',
			array(
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .rating' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'rating_icon_gap',
			array(
				'label'      => esc_html__( 'Icon Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -5,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .rating label' => 'margin-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'rating_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .rating' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// Input
	protected function style_rating_input() {
		// Style Review
		$this->start_controls_section(
			'style_input_fields',
			array(
				'label' => esc_html__( 'Input', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'input_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'input_plh_color',
			array(
				'label'     => esc_html__( 'Placeholder Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .review::placeholder, {{WRAPPER}} .anon-fields input::placeholder' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'input_typo',
				'selector' => '{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'input_border',
				'selector' => '{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input',
			)
		);

		$this->add_responsive_control(
			'input_b_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'input_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .review, {{WRAPPER}} .anon-fields input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'input_width',
			array(
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 1000,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .anon-fields input' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		// Name and Email
		$this->add_control(
			'name_email_heading',
			array(
				'label'     => esc_html__( 'Name & Email', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'name_email_dir',
			array(
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'row',
				'options'   => array(
					'row'    => esc_html__( 'Row', 'animation-addons-for-elementor' ),
					'column' => esc_html__( 'Column', 'animation-addons-for-elementor' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .anon-fields' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'name_email_gap',
			array(
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .anon-fields' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		// Textarea
		$this->add_control(
			'textarea_heading',
			array(
				'label'     => esc_html__( 'Textarea', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'review_width',
			array(
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 1000,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .review' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'review_height',
			array(
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 500,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .review' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'review_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .review-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// Button
	protected function style_rating_button() {
		$this->start_controls_section(
			'style_button',
			array(
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typo',
				'selector' => '{{WRAPPER}} .submit-btn',
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'btn_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .submit-btn',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'btn_border',
				'selector' => '{{WRAPPER}} .submit-btn',
			)
		);

		$this->add_responsive_control(
			'btn_b_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .submit-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .submit-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Hover Tabs
		$this->start_controls_tabs(
			'style_btn_tabs'
		);

		// Normal
		$this->start_controls_tab(
			'btn_normal_tab',
			array(
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'btn_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .submit-btn' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		// Hover
		$this->start_controls_tab(
			'btn_hover_tab',
			array(
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'btn_h_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .submit-btn:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'btn_h_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .submit-btn:hover',
			)
		);

		$this->add_control(
			'btn_h_b_color',
			array(
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .submit-btn:hover' => 'border-color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'btn_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .submit-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();
	}

	// Messages
	protected function style_error_success_messages() {
		$this->start_controls_section(
			'style_success_message',
			array(
				'label' => esc_html__( 'Success Message', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'success_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #aae-review-success-message p' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'success_typo',
				'selector' => '{{WRAPPER}} #aae-review-success-message p',
			)
		);

		$this->add_responsive_control(
			'success_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} #aae-review-success-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_error_message',
			array(
				'label' => esc_html__( 'Error Message', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'error_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #aae-review-error-message p' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'error_typo',
				'selector' => '{{WRAPPER}} #aae-review-error-message p',
			)
		);

		$this->add_responsive_control(
			'error_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} #aae-review-error-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();
	}

	// Render
	protected function render() {
		$settings = $this->get_settings_for_display();
		$post_id  = get_the_ID();

		// Only logged-in users can submit
		$only_logged_in = isset( $settings['only_logged_in'] ) && $settings['only_logged_in'] === 'yes';

		if ( $only_logged_in && ! is_user_logged_in() ) {
			?>
			<div class="aae--post-rating-form">
				<p class="login-required-message">Only logged-in users can submit a review.</p>
			</div>
			<?php
			return;
		}
		?>

		<div class="aae--post-rating-form" data-require-approval=<?php echo esc_attr( $settings['require_approval'] ); ?>>
			<<?php Utils::print_validated_html_tag( $settings['title_tag'] ); ?> class="title">
				<?php echo esc_html( $settings['title'] ); ?>
			</<?php Utils::print_validated_html_tag( $settings['title_tag'] ); ?>>

			<p class="text"><?php echo esc_html( $settings['text'] ); ?></p>

			<div class="rating-form">
				<input type="hidden" id="post_id" value="<?php echo esc_attr( $post_id ); ?>">

				<div class="rating">
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<input id="rating-<?php echo esc_attr( $i ); ?>" type="radio" name="rating"
							value="<?php echo esc_attr( $i ); ?>">
						<label for="rating-<?php echo esc_attr( $i ); ?>">
							<?php Icons_Manager::render_icon( $settings['rating_icon'], array( 'aria-hidden' => 'true' ) ); ?>
						</label>
					<?php endfor; ?>
				</div>

				<?php
				if ( 'yes' !== $settings['only_logged_in'] ) {
					if ( Plugin::$instance->editor->is_edit_mode() || ! is_user_logged_in() ) {
						?>
						<div class="anon-fields">
							<label for="reviewer_name"><input type="text" id="reviewer_name" placeholder="<?php echo esc_attr( $settings['name_plh_text'] ); ?>" required></label>
							<label for="reviewer_email"><input type="email" id="reviewer_email" placeholder="<?php echo esc_attr( $settings['email_plh_text'] ); ?>" required></label>
						</div>
						<?php
					}
				}
				?>

				<div class="review-message">
					<input type="hidden" id="selected_rating" value="">
					<label for="review_text"><textarea name="review" id="review_text" class="review" placeholder="<?php echo esc_attr( $settings['review_placeholder'] ); ?>"></textarea></label>
				</div>

				<button type="submit" id="aae-post-rating-btn" class="submit-btn">
					<?php echo esc_html( $settings['btn_text'] ); ?>
				</button>
			</div>

			<div id="aae-review-success-message"></div>
			<div id="aae-review-error-message"></div>
		</div>
		<?php
	}
}
