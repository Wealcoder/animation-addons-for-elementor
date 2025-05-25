<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Countdown
 *
 * Elementor widget for countdown.
 *
 * @since 1.0.0
 */
class Countdown extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--countdown';
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
		return esc_html__( 'Countdown', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-countdown';
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
		return [ 'wcf--countdown' ];
	}

	public function get_script_depends() {
		return [ 'wcf-addons-core' ];
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

		// Layout Controls
		$this->start_controls_section(
			'section_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'countdown_style',
			[
				'label'   => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__( 'Style One', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'show_separator',
			[
				'label'        => esc_html__( 'Show Separator', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		//countdown timer
		$this->start_controls_section(
			'section_countdown_timer',
			[
				'label' => esc_html__( 'Timer', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'countdown_timer_due_date',
			[
				'label' => esc_html__( 'Due Date', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::DATE_TIME,
				'default' => date("Y-m-d h:i:s", strtotime("+ 1 day")), // PHPCS:Ignore WordPress.DateTime.RestrictedFunctions.date_date
				'description' => esc_html__( 'Set the due date and time', 'animation-addons-for-elementor' ),
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'countdown_timer_label_heading',
			[
				'label' => esc_html__( 'Labels', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'countdown_timer_days_label',
			[
				'label' => esc_html__( 'Days', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Days', 'animation-addons-for-elementor' ),
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'countdown_timer_hours_label',
			[
				'label' => esc_html__( 'Hours', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Hours', 'animation-addons-for-elementor' ),
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'countdown_timer_minutes_label',
			[
				'label' => esc_html__( 'Minutes', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Minutes', 'animation-addons-for-elementor' ),
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'countdown_timer_seconds_label',
			[
				'label' => esc_html__( 'Seconds', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Seconds', 'animation-addons-for-elementor' ),
				'frontend_available' => true,
			]
		);

		$this->add_responsive_control(
			'label_direction',
			[
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'row'    => [
						'title' => esc_html__( 'Row - horizontal', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-right',
					],
					'column' => [
						'title' => esc_html__( 'Column - vertical', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .timer-content' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'label_align_items',
			[
				'label'     => esc_html__( 'Align Items', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__( 'Start', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-start-v',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-center-v',
					],
					'flex-end'   => [
						'title' => esc_html__( 'End', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-end-v',
					],
				],
				'toggle'    => true,
				'condition' => [ 'label_direction' => 'row' ],
				'selectors' => [
					'{{WRAPPER}} .timer-content' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'label_gap',
			[
				'label'      => esc_html__( 'Label gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}}  .timer-content' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_countdown_time_expire',
			[
				'label' => esc_html__( 'Time Expire', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'time_expire_title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'frontend_available' => true,
				'default'     => esc_html__( 'Countdown is finished!', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'time_expire_desc',
			[
				'label'   => esc_html__( 'Description', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 10,
				'frontend_available' => true,
				'default' => esc_html__( 'Default description', 'animation-addons-for-elementor' ),
			]
		);

		$this->end_controls_section();

		//style layout
		$this->start_controls_section(
			'section_layout_style',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'timer_item_gap',
			[
				'label'      => esc_html__( 'Item gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--countdown' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		//Timer items style
		$items = [
			'days',
			'hours',
			'minutes',
			'seconds'
		];

		foreach ( $items as $item ) {
			$this->timer_items_style( $item );
		}

		//style separator

		$this->start_controls_section(
			'section_time_separator_style',
			[
				'label'     => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_separator!' => '' ]
			]
		);

		$this->add_control(
			'separator_content',
			[
				'label' => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => '\003A',
				'options' => [
					'\003A' => esc_html__( 'Colon', 'animation-addons-for-elementor' ),
					'\002F'  => esc_html__( 'slash ', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .timer-content::after' => 'content: "{{VALUE}}";',
				],
			]
		);

		$this->add_control(
			'separator_size',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .timer-content::after' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator_offset_popover_toggle',
			[
				'label' => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::POPOVER_TOGGLE,
			]
		);

		$this->start_popover();

		$this->add_responsive_control(
			'separator_offset_x',
			[
				'label'      => esc_html__( 'Offset Right', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => - 1000,
						'max' => 1000,
					],
					'%'  => [
						'min' => - 100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .timer-content::after' => 'right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'separator_offset_y',
			[
				'label'      => esc_html__( 'Offset Top', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => - 1000,
						'max' => 1000,
					],
					'%'  => [
						'min' => - 100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .timer-content::after' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_popover();

		$this->end_controls_section();
	}

	protected function timer_items_style( $item = '' ) {

		$this->start_controls_section(
			"section_timer_{$item}_style",
			[
				'label' => esc_html( ucfirst($item) ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			"timer_{$item}_general_style_heading",
			[
				'label'     => esc_html__( 'General', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			"timer_{$item}_digit_label_gap",
			[
				'label' => esc_html__( 'Digit & label gap', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					"{{WRAPPER}} .timer-item-{$item}" => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			"timer_{$item}_padding",
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					"{{WRAPPER}} .timer-item-{$item}" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => "timer_{$item}_background",
				'types' => [ 'classic', 'gradient' ],
				'selector' => "{{WRAPPER}} .timer-item-{$item}",
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => "timer_{$item}_border",
				'selector' => "{{WRAPPER}} .timer-item-{$item}",
			]
		);

		$this->add_responsive_control(
			"timer_{$item}_border_radius",
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					"{{WRAPPER}} .timer-item-{$item}" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => "timer_{$item}_box_shadow",
				'selector' => "{{WRAPPER}} .timer-item-{$item}",
			]
		);

		$this->add_control(
			"timer_{$item}_digit_style_heading",
			[
				'label'     => esc_html__( 'Digit', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => "timer_{$item}_digit_typography",
				'selector' => "{{WRAPPER}} .{$item}-count",
			]
		);

		$this->add_control(
			"timer_{$item}_digit_color",
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					"{{WRAPPER}} .{$item}-count" => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			"timer_{$item}_label_style_heading",
			[
				'label'     => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => "timer_{$item}_label_typography",
				'selector' => "{{WRAPPER}} .{$item}-title",
			]
		);

		$this->add_control(
			"timer_{$item}_label_color",
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					"{{WRAPPER}} .{$item}-title" => 'color: {{VALUE}}',
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

		$this->add_render_attribute( 'wrapper', 'class', 'wcf--countdown style-' . $settings['countdown_style'] );
		?>
        <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>></div>
		<?php
	}
}
