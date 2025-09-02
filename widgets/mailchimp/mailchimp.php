<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (! defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

/**
 * Mailchimp
 *
 * Elementor widget for mailchimp.
 *
 * @since 1.0.0
 */
class Mailchimp extends Widget_Base
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
		return 'wcf--mailchimp';
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
		return esc_html__('Mailchimp', 'animation-addons-for-elementor');
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
		return 'wcf eicon-mailchimp';
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
	public function get_style_depends()
	{
		return ['wcf--mailchimp'];
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
		return ['wcf--mailchimp'];
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
		$this->register_mailchimp_controls();

		$this->register_form_content_controls();

		$this->register_label_style_controls();

		$this->register_input_style_controls();

		$this->register_input_icon_style_controls();

		$this->register_button_style_controls();
	}

	protected function register_mailchimp_controls()
	{
		$this->start_controls_section(
			'_section_mailchimp',
			[
				'label' => __('MailChimp', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'mailchimp_api',
			[
				'label'       => __('MailChimp API', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => __('Enter your mailchimp api here', 'animation-addons-for-elementor'),
				'dynamic'     => ['active' => true],
				'default' => get_option('aae_mailchimp_api')
			]
		);

		$this->add_control(
			'mailchimp_lists',
			[
				'label'       => __('Audience', 'animation-addons-for-elementor'),
				'label_block' => true,
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => false,
				'placeholder' => 'Choose your created audience ',
				'options'     => [],
				'description' => esc_html__('Create a audience/ list in mailchimp account ', 'animation-addons-for-elementor') . '<a href="https://mailchimp.com/help/create-audience/" target="_blank"> ' . esc_html__('Create Audience', 'animation-addons-for-elementor') . '</a>',
			]
		);

		$this->add_control(
			'mailchimp_list_tags',
			[
				'label'       => __('Tags', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => __('Tag-1, Tag-2', 'animation-addons-for-elementor'),
				'description' => __('Enter tag here to separate your subscribers. Use comma separator to use multiple tags. Example: Tag-1, Tag-2, Tag-3', 'animation-addons-for-elementor'),
				'condition'   => [
					'mailchimp_lists!' => '',
				],
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'enable_double_opt_in',
			[
				'label'        => __('Enable Double Opt In?', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'animation-addons-for-elementor'),
				'label_off'    => __('No', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->end_controls_section();
	}

	protected function register_form_content_controls()
	{

		$this->start_controls_section(
			'_section_mailchimp_form_message',
			[
				'label' => esc_html__('Message', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'confirmation_message',
			[
				'label'       => esc_html__('Confirmation Message', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__('Please confirm your subscription via the link sent to your email.', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Please confirm your subscription via the link sent to your email.', 'animation-addons-for-elementor'),
				'dynamic'     => ['active' => true],
				'frontend_available' => true,
				'render_type' => 'none',
			]			
		);
		
		$this->add_control(
			'success_message',
			[
				'label'       => esc_html__('Success Message', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__('Thank you for subscribing!', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Thank you for subscribing!', 'animation-addons-for-elementor'),
				'dynamic'     => ['active' => true],
				'frontend_available' => true,	
				'render_type' => 'none',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'_section_mailchimp_form',
			[
				'label' => esc_html__('Form', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'form_align',
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
					'{{WRAPPER}} .wcf-mailchimp-form' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'item_gap',
			[
				'label'      => esc_html__('Item Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem'],
				'separator'  => 'after',
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-mailchimp-form' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		//name
		$this->add_control(
			'enable_name',
			[
				'label'        => esc_html__('Enable Name?', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('No', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
			]
		);

		//first name
		$this->add_control(
			'_fname_heading',
			[
				'label'     => esc_html__('First Name:', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'enable_name'     => 'yes',
				],
			]
		);

		$this->add_control(
			'fname_label',
			[
				'label'       => esc_html__('Label', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('First Name', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('First Name', 'animation-addons-for-elementor'),
				'condition'   => [
					'enable_name'     => 'yes',
				],
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'fname_placeholder',
			[
				'label'       => esc_html__('Placeholder', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('First Name', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('First Name', 'animation-addons-for-elementor'),
				'condition'   => [
					'enable_name'     => 'yes',
				],
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'fname_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'far fa-user',
					'library' => 'regular',
				],
				'condition'   => ['enable_name' => 'yes'],
			]
		);

		$this->add_control(
			'fname_icon_position',
			[
				'label'     => esc_html__('Icon Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'before',
				'options'   => [
					'before' => esc_html__('Before', 'animation-addons-for-elementor'),
					'after'  => esc_html__('After', 'animation-addons-for-elementor'),
				],
				'condition' => ['enable_name' => 'yes'],
			]
		);

		//last name
		$this->add_control(
			'_lname_heading',
			[
				'label'     => esc_html__('Last Name:', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'enable_name'     => 'yes',
				],
			]
		);

		$this->add_control(
			'lname_label',
			[
				'label'       => esc_html__('Label', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Last Name', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Last Name', 'animation-addons-for-elementor'),
				'condition'   => [
					'enable_name'     => 'yes',
				],
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'lname_placeholder',
			[
				'label'       => esc_html__('Placeholder', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Last Name', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Last Name', 'animation-addons-for-elementor'),
				'condition'   => [
					'enable_name'     => 'yes',
				],
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'lname_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'far fa-user',
					'library' => 'regular',
				],
				'condition'   => [
					'enable_name'     => 'yes',
				],
			]
		);

		$this->add_control(
			'lname_icon_position',
			[
				'label'     => esc_html__('Icon Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'before',
				'options'   => [
					'before' => esc_html__('Before', 'animation-addons-for-elementor'),
					'after'  => esc_html__('After', 'animation-addons-for-elementor'),
				],
				'condition' => [
					'enable_name'     => 'yes',
				],
			]
		);

		//phone
		$this->add_control(
			'enable_phone',
			[
				'label'        => esc_html__('Enable Phone?', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'separator'    => 'before',
				'label_on'     => esc_html__('Yes', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('No', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'_phone_heading',
			[
				'label'     => esc_html__('Phone:', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'enable_phone' => 'yes',
				],
			]
		);

		$this->add_control(
			'phone_label',
			[
				'label'       => esc_html__('Label', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Phone', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Phone', 'animation-addons-for-elementor'),
				'condition'   => [
					'enable_phone' => 'yes',
				],
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'phone_placeholder',
			[
				'label'       => esc_html__('Placeholder', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Phone', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Phone', 'animation-addons-for-elementor'),
				'condition'   => [
					'enable_phone' => 'yes',
				],
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'phone_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-phone-alt',
					'library' => 'solid',
				],
				'condition'   => ['enable_phone' => 'yes'],
			]
		);

		$this->add_control(
			'phone_icon_position',
			[
				'label'     => esc_html__('Icon Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'before',
				'options'   => [
					'before' => __('Before', 'animation-addons-for-elementor'),
					'after'  => __('After', 'animation-addons-for-elementor'),
				],
				'condition' => ['enable_phone' => 'yes'],
			]
		);

		//email
		$this->add_control(
			'_email_heading',
			[
				'label'     => esc_html__('Email:', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'email_label',
			[
				'label'       => esc_html__('Label', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Email', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Email', 'animation-addons-for-elementor'),
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'email_placeholder',
			[
				'label'       => esc_html__('Placeholder', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Email', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Email input placeholder', 'animation-addons-for-elementor'),
				'dynamic'     => ['active' => true],
			]
		);

		$this->add_control(
			'email_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'far fa-envelope',
					'library' => 'regular',
				],
			]
		);

		$this->add_control(
			'email_icon_position',
			[
				'label'   => esc_html__('Icon Position', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'before',
				'options' => [
					'before' => __('Before', 'animation-addons-for-elementor'),
					'after'  => __('After', 'animation-addons-for-elementor'),
				],
			]
		);


		//button
		$this->add_control(
			'_button_heading',
			[
				'label'     => esc_html__('Button:', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'   => esc_html__('Text', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__('Subscribe', 'animation-addons-for-elementor'),
				'dynamic' => ['active' => true],
			]
		);

		$this->add_control(
			'button_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-check',
					'library' => 'solid',
				],
			]
		);

		$this->add_control(
			'button_icon_position',
			[
				'label'   => esc_html__('Icon Position', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'before',
				'options' => [
					'before' => esc_html__('Before', 'animation-addons-for-elementor'),
					'after'  => esc_html__('After', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_responsive_control(
			'button_icon_spacing',
			[
				'label'      => esc_html__('Icon Spacing', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-mc-button' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_label_style_controls()
	{
		$this->start_controls_section(
			'_section_style_input_label',
			[
				'label' => esc_html__('Label', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'input_label_typography',
				'label'    => esc_html__('Typography', 'animation-addons-for-elementor'),
				'selector' => '{{WRAPPER}} label',
			]
		);

		$this->add_control(
			'input_label_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} label' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'input_label_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_input_style_controls()
	{
		$this->start_controls_section(
			'_section_input_style',
			[
				'label' => esc_html__('Input', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'input_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .input input' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'input_typography',
				'label'    => esc_html__('Typography', 'animation-addons-for-elementor'),
				'selector' => '{{WRAPPER}} .input input',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'input_background',
				'label'    => esc_html__('Background', 'animation-addons-for-elementor'),
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .input input',
				'exclude'  => [
					'image',
				],
			]
		);

		$this->add_responsive_control(
			'input_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .input input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'default'    => [
					'unit' => '%',
					'size' => 100,
				],
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
				'selectors'  => [
					'{{WRAPPER}} .input-wrapper' => 'flex: 0 0 {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'input_border',
				'label'     => esc_html__('Border', 'animation-addons-for-elementor'),
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .input input',
			]
		);

		$this->add_responsive_control(
			'input_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .input input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'input_box_shadow',
				'label'    => esc_html__('Box Shadow', 'animation-addons-for-elementor'),
				'selector' => '{{WRAPPER}} .input input, {{WRAPPER}} .input input:focus',
			]
		);

		//placeholder
		$this->add_control(
			'input_placeholder_heading',
			[
				'label'     => esc_html__('Placeholder', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'input_placeholder_color',
			[
				'label'     => esc_html__('Placeholder Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .input input::-webkit-input-placeholder' => 'color: {{VALUE}}',
					'{{WRAPPER}} .input input::-moz-placeholder'          => 'color: {{VALUE}}',
					'{{WRAPPER}} .input input:-ms-input-placeholder'      => 'color: {{VALUE}}',
					'{{WRAPPER}} .input input:-moz-placeholder'           => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'input_placeholder_font_size',
			[
				'label'      => esc_html__('Font Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .input input::-webkit-input-placeholder' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .input input::-moz-placeholder'          => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .input input:-ms-input-placeholder'      => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .input input:-moz-placeholder'           => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_input_icon_style_controls()
	{
		$this->start_controls_section(
			'_section_input_icon_style',
			[
				'label' => esc_html__('Input Icon', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'input_icon_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [
					'{{WRAPPER}} .input .icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_icon_font_size',
			[
				'label'      => esc_html__('Font Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .input .icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'input_icon_background',
				'label'    => esc_html__('Background', 'animation-addons-for-elementor'),
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .input .icon',
				'exclude'  => [
					'image',
				],
			]
		);

		$this->add_responsive_control(
			'input_icon_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .input .icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'input_icon_border',
				'separator' => 'before',
				'label'     => esc_html__('Border', 'animation-addons-for-elementor'),
				'selector'  => '{{WRAPPER}} .input .icon',
			]
		);

		$this->add_responsive_control(
			'input_icon_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .input .icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;;',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_button_style_controls()
	{
		$this->start_controls_section(
			'_section_button_style',
			[
				'label' => esc_html__('Button', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'label'    => esc_html__('Typography', 'animation-addons-for-elementor'),
				'selector' => '{{WRAPPER}} .wcf-mc-button',
			]
		);

		$this->add_responsive_control(
			'button_icon_size',
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
					'{{WRAPPER}} .wcf-mc-button i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf-mc-button svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'button_normal_and_hover_tabs'
		);

		$this->start_controls_tab(
			'button_normal_tab',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-mc-button' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'button_background',
				'label'    => esc_html__('Background', 'animation-addons-for-elementor'),
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .wcf-mc-button',
				'exclude'  => [
					'image',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_hover_tab',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'button_color_hover',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-mc-button:hover, {{WRAPPER}} .wcf-mc-button:focus' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'button_background_hover',
				'label'    => esc_html__('Background', 'animation-addons-for-elementor'),
				'types'    => ['classic', 'gradient'],
				'selector' => '{{WRAPPER}} .wcf-mc-button:hover, {{WRAPPER}} .wcf-mc-button:focus',
				'exclude'  => [
					'image',
				],
			]
		);

		$this->add_control(
			'button_border_color_hover',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-mc-button:hover, {{WRAPPER}} .wcf-mc-button:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'button_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'button_border',
				'separator' => 'before',
				'label'     => esc_html__('Border', 'animation-addons-for-elementor'),
				'selector'  => '{{WRAPPER}} .wcf-mc-button',
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-mc-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'button_box_shadow',
				'label'    => esc_html__('Box Shadow', 'animation-addons-for-elementor'),
				'selector' => '{{WRAPPER}} .wcf-mc-button',
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-mc-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .wcf-mc-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'range'      => [
					'px' => [
						'min'  => 50,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => 10,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf-mc-button' => 'width: {{SIZE}}{{UNIT}};',
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

		$this->add_render_attribute('wrapper', 'class', 'wcf--mailchimp');

		$this->add_render_attribute('wcf-mailchimp-form', [
			'class'           => 'wcf-mailchimp-form wcf--form-wrapper',
			'data-key'        => ! empty($settings['mailchimp_api']) ? base64_encode('w1c2f' . $settings['mailchimp_api'] . 'w1c2f') : '',
			'data-list-id'    => ! empty($settings['mailchimp_lists']) ? ltrim($settings['mailchimp_lists']) : '',
			'data-double-opt' => ! empty($settings['enable_double_opt_in']) ? $settings['enable_double_opt_in'] : '',
			'data-list-tags'  => ! empty($settings['mailchimp_list_tags']) ? $settings['mailchimp_list_tags'] : '',
		]);

		$input_fields = [
			'email' => [
				'label'         => 'email_label',
				'placeholder'   => 'email_placeholder',
				'icon'          => 'email_icon',
				'icon_position' => 'email_icon_position',
			],
		];

		if (! empty($settings['enable_phone'])) {
			$input_fields['phone'] = [
				'label'         => 'phone_label',
				'placeholder'   => 'phone_placeholder',
				'icon'          => 'phone_icon',
				'icon_position' => 'phone_icon_position',
			];
		}

		if (! empty($settings['enable_name'])) {
			$input_fields['lname'] = [
				'label'         => 'lname_label',
				'placeholder'   => 'lname_placeholder',
				'icon'          => 'lname_icon',
				'icon_position' => 'lname_icon_position',
			];

			$input_fields['fname'] = [
				'label'         => 'fname_label',
				'placeholder'   => 'fname_placeholder',
				'icon'          => 'fname_icon',
				'icon_position' => 'fname_icon_position',
			];
		}

?>
<div <?php $this->print_render_attribute_string('wrapper'); ?>>
    <div class="mailchimp-response-message"></div>
    <form <?php $this->print_render_attribute_string('wcf-mailchimp-form'); ?>>
        <?php
				foreach (array_reverse($input_fields) as $key => $value) {
					$this->render_input($key, $value, $settings);
				}
				$this->render_submit_button($settings);
				?>
    </form>
</div>
<?php
	}

	protected function render_input($key, $value, $settings)
	{
		$type = 'text';

		if ('phone' === $key) {
			$type = 'tel';
		}

		if ('email' === $key) {
			$type = 'email';
			$this->add_render_attribute($key, [
				'required' => '',
			]);
		}

		$this->add_render_attribute($key, [
			'type'        => $type,
			'id'          => $key,
			'name'        => $key,
			'placeholder' => $settings[$value['placeholder']],
		]);

		$icon_position = '';
		if (! empty($settings[$value['icon']]['value'])) {
			$icon_position = $settings[$value['icon_position']];
		}
	?>
<div class="input-wrapper">
    <?php $this->render_label($key, $value['label'], $settings); ?>

    <div class="input <?php echo esc_attr($icon_position); ?>">
        <?php $this->render_icon($value['icon'], $settings); ?>
        <input <?php $this->print_render_attribute_string($key); ?>>
    </div>
</div>
<?php
	}

	protected function render_icon($icon, $settings)
	{
		if (empty($settings[$icon]['value'])) {
			return;
		}
	?>
<div class="icon">
    <?php Icons_Manager::render_icon($settings[$icon], ['aria-hidden' => 'true']); ?>
</div>
<?php
	}

	protected function render_label($key, $label, $settings)
	{
		$screen_reader_label = [
			'email' => 'Email',
			'phone' => 'Phone',
			'fname' => 'First Name',
			'lname' => 'Last Name',
		];
		if (! empty($settings[$label])) : ?>
<label for="<?php echo esc_attr($key); ?>">
    <?php echo esc_html($settings[$label]); ?>
</label>
<?php else : ?>
<span class="elementor-screen-only"><?php echo esc_html(ucwords($screen_reader_label[$key])); ?></span>
<?php
		endif;
	}

	protected function render_submit_button($settings)
	{
		$this->add_render_attribute(
			'button_wrapper',
			[
				'class' => 'wcf-mc-button',
				'type'  => 'submit',
				'name'  => 'wcf-mailchimp',
			]
		);

		if ('after' === $settings['button_icon_position']) {
			$this->add_render_attribute('button_wrapper', 'class', 'icon-position-after');
		}
		?>
<button <?php $this->print_render_attribute_string('button_wrapper'); ?>
    aria-label="<?php echo esc_html__('Mailchimp Button', 'animation-addons-for-elementor'); ?>">
    <?php
			Icons_Manager::render_icon($settings['button_icon'], ['aria-hidden' => 'true']);
			$this->print_unescaped_setting('button_text');
			?>
</button>
<?php
	}
}
