<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Contact Form 7
 *
 * Elementor widget for contact-form-7.
 *
 * @since 1.0.0
 */
class Contact_Form_7 extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--contact-form-7';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_title() {
		return esc_html__( 'Contact Form 7', 'animation-addons-for-elementor' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_icon() {
		return 'wcf eicon-mail';
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
	public function get_categories() {
		return [ 'weal-coder-addon' ];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array(
			'wcf--button',
		);
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
	public function get_script_depends() {
		return [ 'wcf-addons-core' ];
	}
	public function contactform_forms() {
		$formlist   = array();
		$forms_args = array(
			'posts_per_page' => - 1,
			'post_type'      => 'wpcf7_contact_form',
		);
		$forms      = get_posts( $forms_args );
		if ( $forms ) {
			foreach ( $forms as $form ) {
				$formlist[ $form->ID ] = $form->post_title;
			}
		} else {
			$formlist['0'] = esc_html__( 'Form not found', 'animation-addons-for-elementor' );
		}

		return $formlist;
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
	protected function register_controls() {
		$this->start_controls_section(
			'contactform_content',
			[
				'label' => esc_html__( 'Contact Form', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'contact_form_id',
			[
				'label'       => esc_html__( 'Select Form', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'options'     => $this->contactform_forms(),
				'default'     => '0',
			]
		);

		$this->end_controls_section();

		// Style tab section
		$this->start_controls_section(
			'contactform_form_section_style',
			[
				'label' => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'contactform_form_section_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--form-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_form_section_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--form-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'contactform_form_section_background',
				'label'    => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf--form-wrapper',
			]
		);

		$this->add_responsive_control(
			'contactform_form_section_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => esc_html__( 'Justified', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--form-wrapper' => 'text-align: {{VALUE}};',
				],
				'default'   => 'left',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// Input Field style tab start
		$this->start_controls_section(
			'contactform_contactform_input_style',
			[
				'label' => esc_html__( 'Input', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_height',
			[
				'label'   => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => [
					'px' => [
						'max' => 150,
					],
				],
				'default' => [
					'size' => 55,
				],

				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]'   => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]'  => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]'    => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]'    => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]'   => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select'         => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'contactform_input_box_background',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]'   => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]'  => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]'    => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]'    => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]'   => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select'         => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'contactform_input_box_typography',
				'selector' => '{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select',
			]
		);

		$this->add_control(
			'contactform_input_box_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]' => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select'         => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contactform_input_box_placeholder_color',
			[
				'label'     => esc_html__( 'Placeholder Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]::-webkit-input-placeholder'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]::-moz-placeholder'            => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]:-ms-input-placeholder'        => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]::-webkit-input-placeholder'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]::-moz-placeholder'           => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]:-ms-input-placeholder'       => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]::-webkit-input-placeholder'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]::-moz-placeholder'             => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]:-ms-input-placeholder'         => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]::-webkit-input-placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]::-moz-placeholder'          => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]:-ms-input-placeholder'      => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]::-webkit-input-placeholder'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]::-moz-placeholder'             => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]:-ms-input-placeholder'         => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]::-webkit-input-placeholder'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]::-moz-placeholder'            => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]:-ms-input-placeholder'        => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select'                                    => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'contactform_input_box_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"], {{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select',
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]'   => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]'  => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]'    => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]'    => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]'   => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select'         => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]'  => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]'    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]'    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select'         => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_input_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="text"]'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="email"]'  => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="url"]'    => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="number"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="tel"]'    => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap input[type*="date"]'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap .wpcf7-select'         => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section(); // Input Field style tab end

		// Textarea style tab start
		$this->start_controls_section(
			'contactform_textarea_style',
			[
				'label' => esc_html__( 'Textarea', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'contactform_textarea_box_height',
			[
				'label'   => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => [
					'px' => [
						'max' => 500,
					],
				],
				'default' => [
					'size' => 175,
				],

				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'contactform_textarea_box_background',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'contactform_textarea_box_typography',
				'selector' => '{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea',
			]
		);

		$this->add_control(
			'contactform_textarea_box_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contactform_textarea_box_placeholder_color',
			[
				'label'     => esc_html__( 'Placeholder Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea::-webkit-input-placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea::-moz-placeholder'          => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea:-ms-input-placeholder'      => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'contactform_textarea_box_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea',
			]
		);

		$this->add_responsive_control(
			'contactform_textarea_box_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_textarea_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_textarea_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-form-control-wrap textarea' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section(); // Textarea style tab end

		// Label style tab start
		$this->start_controls_section(
			'contactform_contactform_label_style',
			[
				'label' => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'contactform_label_background',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--form-wrapper form.wpcf7-form label' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contactform_label_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--form-wrapper form.wpcf7-form label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'contactform_label_typography',
				'selector' => '{{WRAPPER}} .wcf--form-wrapper form.wpcf7-form label',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'contactform_label_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .wcf--form-wrapper form.wpcf7-form label',
			]
		);

		$this->add_responsive_control(
			'contactform_label_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .wcf--form-wrapper form.wpcf7-form label' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_label_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--form-wrapper form.wpcf7-form label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'contactform_label_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--form-wrapper form.wpcf7-form label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section(); // // Label style tab end

		// Input submit button style tab start
		$this->start_controls_section(
			'contactform_inputsubmit_style',
			[
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'btn_hover_list',
			[
				'label'   => esc_html__( 'Hover Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'hover-none',
				'options' => [
					'hover-none'      => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'hover-divide'    => esc_html__( 'Divided', 'animation-addons-for-elementor' ),
					'hover-cross'     => esc_html__( 'Cross', 'animation-addons-for-elementor' ),
					'hover-cropping'  => esc_html__( 'Cropping', 'animation-addons-for-elementor' ),
					'rollover-top'    => esc_html__( 'Rollover Top', 'animation-addons-for-elementor' ),
					'rollover-left'   => esc_html__( 'Rollover Left', 'animation-addons-for-elementor' ),
					'parallal-border' => esc_html__( 'Parallel Border', 'animation-addons-for-elementor' ),
					'rollover-cross'  => esc_html__( 'Rollover Cross', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'button_icon',
			[
				'label'            => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
			]
		);

		$this->add_control(
			'button_icon_align',
			[
				'label'        => esc_html__( 'Icon Position', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'before',
				'options'      => [
					'before' => esc_html__( 'Before', 'animation-addons-for-elementor' ),
					'after'  => esc_html__( 'After', 'animation-addons-for-elementor' ),
				],
				'prefix_class' => 'icon-position-',
			]
		);

		$this->add_control(
			'button_icon_indend',
			[
				'label'     => esc_html__( 'Icon Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpcf7-submit' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
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
					'{{WRAPPER}} .wpcf7-submit i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wpcf7-submit svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'input_submit_typography',
				'selector' => '{{WRAPPER}} .wpcf7-form .wpcf7-submit',
			]
		);

		$this->add_control(
			'input_submit_height',
			[
				'label'     => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 150,
					],
				],
				'default'   => [
					'size' => 55,
				],
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-submit' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'submit_style_tabs' );

		// Button Normal tab start
		$this->start_controls_tab(
			'submit_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'input_submit_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-submit' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_submit_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-submit' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab(); // Button Normal tab end

		// Button Hover tab start
		$this->start_controls_tab(
			'submit_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'input_submithover_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-submit:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_submithover_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-submit:not(.btn-parallal-border, .btn-rollover-cross):after, {{WRAPPER}} .btn-rollover-cross:hover, {{WRAPPER}} .btn-parallal-border:hover, {{WRAPPER}} .wpcf7-submit.btn-hover-none:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-submit:hover, {{WRAPPER}} .wpcf7-submit:focus, {{WRAPPER}} .wpcf7-submit:hover.btn-parallal-border:before, {{WRAPPER}} .wpcf7-submit:hover.btn-parallal-border:after, {{WRAPPER}} .wpcf7-submit:hover.btn-rollover-cross:before, {{WRAPPER}} .wpcf7-submit:hover.btn-rollover-cross:after, {{WRAPPER}} .wpcf7-submit.btn-hover-none:hover' => 'border-color: {{VALUE}};',
				],
				'condition' => [ 'input_submit_border_border!' => '' ],
			]
		);

		$this->end_controls_tab(); // Button Hover tab end

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'input_submit_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'input_submit_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-submit' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'input_submit_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .wpcf7-submit',
			]
		);

		$this->add_responsive_control(
			'input_submit_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-form .wpcf7-submit' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section(); // Input submit button style tab end

		// Success/Error Message
		$this->start_controls_section(
			'style_message',
			[
				'label' => esc_html__( 'Message', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'empty_color',
			[
				'label'     => esc_html__( 'Error Field Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpcf7-not-valid-tip' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'empty_typo',
				'selector' => '{{WRAPPER}} .wpcf7-not-valid-tip',
			]
		);

		$this->add_control(
			'error_msg_color',
			[
				'label'     => esc_html__( 'Error Message Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .invalid .wpcf7-response-output' => 'color: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'success_msg_color',
			[
				'label'     => esc_html__( 'Success Message Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sent .wpcf7-response-output' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'message_typo',
				'selector' => '{{WRAPPER}} .wpcf7-response-output',
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
	protected function render() {
		$settings = $this->get_settings_for_display();
		$id       = $this->get_id();

		$this->add_render_attribute(
			'wrapper', [
				'class'     => 'wcf--form-wrapper',
				'btn-hover' => 'btn-' . $settings['btn_hover_list'],
			]
		);

		$this->add_render_attribute( 'shortcode', 'id', $settings['contact_form_id'] );
		$shortcode = sprintf( '[contact-form-7 %s]', $this->get_render_attribute_string( 'shortcode' ) );
		?>
        <style>
            .wcf--form-wrapper form label {
                display: block;
            }
        </style>
        <div <?php $this->print_render_attribute_string( 'wrapper' ); ?> >
			<?php
			if ( ! empty( $settings['contact_form_id'] ) ) {
				echo do_shortcode( $shortcode );
			} else {
				?>
                <div class="form_no_select"><?php echo esc_html__( 'Please Select contact form.', 'animation-addons-for-elementor' ); ?></div>
				<?php
			}
			$migrated = isset( $settings['__fa4_migrated']['button_icon'] );
			$is_new   = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();
			?>
            <div class="btn-icon elementor-screen-only">
				<?php if ( $is_new || $migrated ) :
					Icons_Manager::render_icon( $settings['button_icon'], [ 'aria-hidden' => 'true' ] );
				else : ?>
                    <i class="<?php echo esc_attr( $settings['icon'] ); ?>" aria-hidden="true"></i>
				<?php endif; ?>
            </div>
        </div>

		<?php
	}
}
