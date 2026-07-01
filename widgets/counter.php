<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Counter
 *
 * Elementor widget for counter.
 *
 * @since 1.0.0
 */
class Counter extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--counter';
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
		return esc_html__( 'Counter', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-counter';
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
	public function get_style_depends() {
		return [ 'wcf--counter' ];
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
		return [ 'wcf--counter' ];
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
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'starting_number',
			[
				'label'   => esc_html__( 'Starting Number', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'ending_number',
			[
				'label'   => esc_html__( 'Ending Number', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 100,
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'prefix',
			[
				'label'       => esc_html__( 'Number Prefix', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'default'     => '',
				'placeholder' => 1,
			]
		);

		$this->add_control(
			'suffix',
			[
				'label'       => esc_html__( 'Number Suffix', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => [
					'active' => true,
				],
				'default'     => '',
				'placeholder' => esc_html__( 'Plus', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'duration',
			[
				'label'   => esc_html__( 'Animation Duration', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 2000,
				'min'     => 100,
				'step'    => 100,
			]
		);

		$this->add_control(
			'thousand_separator',
			[
				'label'     => esc_html__( 'Thousand Separator', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off' => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'thousand_separator_char',
			[
				'label'     => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'condition' => [
					'thousand_separator' => 'yes',
				],
				'options'   => [
					''  => 'Default',
					'.' => 'Dot',
					' ' => 'Space',
					'_' => 'Underline',
					"'" => 'Apostrophe',
				],
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
				'default'     => esc_html__( 'Cool Number', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Cool Number', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_responsive_control(
			'direction',
			[
				'label'        => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => [
					'row'    => [
						'title' => esc_html__( 'Row', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-right',
					],
					'column' => [
						'title' => esc_html__( 'Column', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
				],
				'default'      => 'column',
				'separator'    => 'before',
				'render_type'  => 'template',
				'prefix_class' => 'wcf%s-position-',
				'selectors'    => [
					'{{WRAPPER}} .wcf--counter' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 300,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--counter' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator',
			[
				'label'        => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'separator_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .separator' => 'background-color: {{VALUE}}',
				],
				'condition' => [
					'separator' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'flex-start',
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .wcf--counter' => 'justify-content: {{VALUE}}; text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		//style control
		$this->start_controls_section(
			'section_number',
			[
				'label' => esc_html__( 'Number', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'number_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--counter-number' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography_number',
				'selector' => '{{WRAPPER}} .wcf--counter-number',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'number_stroke',
				'selector' => '{{WRAPPER}} .wcf--counter-number',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'number_shadow',
				'selector' => '{{WRAPPER}} .wcf--counter-number',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'suffix_prefix',
			[
				'label' => esc_html__( 'Suffix / Prefix', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'suffix_prefix_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--counter-number-prefix' => 'color: {{VALUE}};',
					'{{WRAPPER}} .wcf--counter-number-suffix' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'suffix_prefix_typography',
				'selector' => '{{WRAPPER}} .wcf--counter-number-prefix, {{WRAPPER}} .wcf--counter-number-suffix',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'suffix_prefix_stroke',
				'selector' => '{{WRAPPER}} .wcf--counter-number-prefix, {{WRAPPER}} .wcf--counter-number-suffix',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'suffix_prefix_shadow',
				'selector' => '{{WRAPPER}} .wcf--counter-number-prefix, {{WRAPPER}} .wcf--counter-number-suffix',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_title',
			[
				'label' => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--counter .title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography_title',
				'selector' => '{{WRAPPER}} .wcf--counter .title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'title_stroke',
				'selector' => '{{WRAPPER}} .wcf--counter .title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'title_shadow',
				'selector' => '{{WRAPPER}} .wcf--counter .title',
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--counter .title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render counter widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		//wrapper
		$this->add_render_attribute( 'wrapper', 'class', 'wcf--counter' );

		//counter attributes
		$this->add_render_attribute( 'counter', [
			'class'           => 'wcf--counter-number',
			'data-duration'   => $settings['duration'],
			'data-to-value'   => $settings['ending_number'],
			'data-from-value' => $settings['starting_number'],
		] );

		if ( ! empty( $settings['thousand_separator'] ) ) {
			$delimiter = empty( $settings['thousand_separator_char'] ) ? ',' : $settings['thousand_separator_char'];
			$this->add_render_attribute( 'counter', 'data-delimiter', $delimiter );
		}
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<div class="count">
				<span class="wcf--counter-number-prefix"><?php $this->print_unescaped_setting( 'prefix' ); ?></span>
				<span <?php $this->print_render_attribute_string( 'counter' ); ?>><?php $this->print_unescaped_setting( 'starting_number' ); ?></span>
				<span class="wcf--counter-number-suffix"><?php $this->print_unescaped_setting( 'suffix' ); ?></span>
			</div>

			<?php if ( 'yes' === $settings['separator'] ) : ?>
				<div class="separator"></div>
			<?php endif; ?>

			<?php if ( $settings['title'] ) : ?>
				<div class="title">
					<?php $this->print_unescaped_setting( 'title' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
