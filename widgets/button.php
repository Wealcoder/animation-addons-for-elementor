<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use WCF_ADDONS\WCF_Button_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Button
 *
 * Elementor widget for buttons.
 *
 * @since 1.0.0
 */
class Button extends Widget_Base {
	use  WCF_Button_Trait;

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--button';
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
		return esc_html__( 'Button', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-button';
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
			'section_content',
			[
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor' ),
			]
		);

		$this->register_button_content_controls();

		$this->add_responsive_control(
			'btn_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		//style controls
		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_button_style_controls();

		$this->add_control(
			'btn_stretch',
			[
				'label'     => esc_html__( 'Button Stretch', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''        => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'stretch' => esc_html__( 'Enable', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf__btn a' => 'width: 100%;',
				],
				'condition' => [
					'btn_element_list' => [ 'default', 'underline' ],
				],
			]
		);

		$this->add_control(
			'btn_stretch_align',
			[
				'label'     => esc_html__( 'Stretch Align', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'start',
				'options'   => [
					'start'         => esc_html__( 'Start', 'animation-addons-for-elementor' ),
					'center'        => esc_html__( 'Center', 'animation-addons-for-elementor' ),
					'end'           => esc_html__( 'End', 'animation-addons-for-elementor' ),
					'space-between' => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf__btn a' => 'justify-content: {{VALUE}}',
				],
				'condition' => [
					'btn_stretch' => 'stretch',
					'btn_element_list' => [ 'default', 'underline' ],
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
	protected function render() {
		$settings = $this->get_settings_for_display();
		$this->render_button( $settings );
	}
}
