<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Progressbar
 *
 * Elementor widget for progressbar.
 *
 * @since 1.0.0
 */
class Progressbar extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--progressbar';
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
		return esc_html__( 'Progress Bar', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-skill-bar';
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
		return [ 'progressbar', 'wcf--progressbar' ];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'wcf--progressbar' ];
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
			'section_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'element_list',
			[
				'label'   => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__( 'One', 'animation-addons-for-elementor' ),
					'2' => esc_html__( 'Two', 'animation-addons-for-elementor' ),
					'3' => esc_html__( 'Three', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->end_controls_section();

		// Progressbar Content
		$this->start_controls_section(
			'section_progressbar',
			[
				'label' => esc_html__( 'Progress Bar', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'percentage',
			[
				'label'      => esc_html__( 'Percentage', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'default'    => [
					'unit' => '%',
					'size' => 50,
				],
				'range'      => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
			]
		);

		$this->add_control(
			'display_percentage',
			[
				'label'        => esc_html__( 'Display Percentage', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'show',
				'default'      => 'show',
				'condition'    => [ 'element_list!' => '3' ],
			]
		);

		$this->end_controls_section();

		// Progressbar Style
		$this->start_controls_section(
			'section_progressbar_style',
			[
				'label' => esc_html__( 'Progress Bar', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'color',
			[
				'label'       => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::COLOR,
				'render_type' => 'template',
				'default'     => '#7DDED8',
				'selectors'   => [
					'{{WRAPPER}} .wcf__progressbar.style-3 .dot' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'bg-color',
			[
				'label'       => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::COLOR,
				'render_type' => 'template',
				'selectors'   => [
					'{{WRAPPER}} .wcf__progressbar.style-3 .dot.active' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'border-width',
			[
				'label'       => esc_html__( 'Border Width', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'template',
				'size_units'  => [ 'px' ],
				'default'     => [
					'unit' => 'px',
					'size' => 1,
				],
				'selectors'   => [
					'{{WRAPPER}} .wcf__progressbar.style-3 .dot' => 'border-width: {{SIZE}}{{UNIT}};',
				],
				'condition'   => [ 'element_list' => '3' ],
			]
		);

		$this->add_control(
			'stroke-width',
			[
				'label'       => esc_html__( 'Stroke Width', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'template',
				'size_units'  => [ 'em' ],
				'default'     => [
					'unit' => 'em',
					'size' => 2,
				],
				'condition'   => [ 'element_list!' => '3' ],
			]
		);

		$this->add_control(
			'trail-width',
			[
				'label'       => esc_html__( 'Trail Width', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'template',
				'size_units'  => [ 'em' ],
				'default'     => [
					'unit' => 'em',
					'size' => 1,
				],
				'condition'   => [ 'element_list!' => '3' ],
			]
		);

		$this->add_responsive_control(
			'progress-size',
			[
				'label'       => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SLIDER,
				'render_type' => 'template',
				'size_units'  => [ 'px' ],
				'selectors'   => [
					'{{WRAPPER}} .wcf__progressbar.style-2, {{WRAPPER}} .wcf__progressbar.style-3 .dot ' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'   => [ 'element_list!' => '1' ],
			]
		);

		$this->end_controls_section();

		// Percentage Style
		$this->start_controls_section(
			'percentage_style',
			[
				'label'     => esc_html__( 'Percentage', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'display_percentage' => 'show',
					'element_list!'      => '3',
				],
			]
		);

		$this->add_control(
			'percentage_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .progressbar-text' => 'color: {{VALUE}} !important',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'percentage_typography',
				'selector' => '{{WRAPPER}} .progressbar-text',
			]
		);

		$this->add_control(
			'percentage_position',
			[
				'label'      => esc_html__( 'Position Y', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => 'px',
					'size' => - 30,
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf__progressbar.style-1 .progressbar-text' => 'top: {{SIZE}}{{UNIT}} !important;',
				],
				'condition'  => [ 'element_list' => '1' ],
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

		$progressbar_settings = [
			'percentage'  => $settings['percentage']['size'],
			'color'       => $settings['color'],
			'trail-color' => $settings['bg-color'],
		];

		if ( '1' === $settings['element_list'] ) {
			$progressbar_settings['progress-type'] = 'line';
		}

		if ( '2' === $settings['element_list'] ) {
			$progressbar_settings['progress-type'] = 'circle';
		}

		if ( '3' === $settings['element_list'] ) {
			$progressbar_settings['progress-type'] = 'dot';
		}

		if ( in_array( $settings['element_list'], [ 1, 2 ] ) ) {
			$progressbar_settings['stroke-width'] = $settings['stroke-width']['size'];
			$progressbar_settings['trail-width']  = $settings['trail-width']['size'];
		}

		if ( ! empty( $settings['display_percentage'] ) ) {
			$progressbar_settings['display-percentage'] = $settings['display_percentage'];
		}

		$this->add_render_attribute(
			'wrapper',
			[
				'class'         => 'wcf__progressbar style-' . $settings['element_list'],
				'data-settings' => json_encode( $progressbar_settings ), //phpcs:ignore
			]
		);
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>><?php
		if ( '3' === $settings['element_list'] ) {
			?>
			<div class="progressbar dots">
				<span class="dot"></span>
				<span class="dot"></span>
				<span class="dot"></span>
				<span class="dot"></span>
				<span class="dot"></span>
			</div>
			<?php
		} else {
			?><div class="progressbar"></div><?php
		}
		?></div><?php
	}
}
