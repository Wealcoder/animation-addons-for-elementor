<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (! defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

/**
 * Current Date
 *
 * Elementor widget for toggle.
 *
 * @since 1.0.0
 */
class Current_Date extends Widget_Base
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
		return 'wcf--current-date';
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
		return esc_html__('Current Date', 'animation-addons-for-elementor');
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
		return 'wcf eicon-date';
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
			'section_current_date',
			[
				'label' => esc_html__('Current Date', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'date_format',
			[
				'label'   => esc_html__('Date Format', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'F j, Y',
				'options' => [
					'F j, Y' => esc_html__('F j, Y', 'animation-addons-for-elementor'),
					'Y-m-d'  => esc_html__('Y-m-d', 'animation-addons-for-elementor'),
					'm/d/Y'  => esc_html__('m/d/Y', 'animation-addons-for-elementor'),
					'd/m/Y'  => esc_html__('d/m/Y', 'animation-addons-for-elementor'),
					'custom' => esc_html__('Custom', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'custom_date_format',
			[
				'label'       => esc_html__('Custom Format', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('F j, Y', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('F j, Y', 'animation-addons-for-elementor'),
				'condition'   => ['date_format' => 'custom']
			]
		);

		$this->add_control(
			'show_day',
			[
				'label'        => esc_html__('Show Day', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'animation-addons-for-elementor'),
				'label_off'    => esc_html__('Hide', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'day_separator',
			[
				'label'       => esc_html__('Separator', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('/', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('/', 'animation-addons-for-elementor'),
				'condition'   => ['show_day' => 'yes']
			]
		);

		$this->add_responsive_control(
			'separator_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf__current_date' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['show_day' => 'yes']
			]
		);

		$this->add_responsive_control(
			'day_direction',
			[
				'label'     => esc_html__('Direction', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'row',
				'options'   => [
					'row'    => esc_html__('Default', 'animation-addons-for-elementor'),
					'column' => esc_html__('Column', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .wcf__current_date' => 'flex-direction: {{VALUE}};',
				],
				'condition' => ['show_day' => 'yes']
			]
		);

		$this->add_responsive_control(
			'text_align',
			[
				'label'     => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::CHOOSE,
				'separator' => 'before',
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
				'toggle'    => false,
				'selectors' => [
					'{{WRAPPER}} .wcf__current_date' => 'justify-content: {{VALUE}}; align-items: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		//style
		$this->start_controls_section(
			'section_style_current_date',
			[
				'label' => __('Current Date', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'date_text_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .today-date' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'date_text_typography',
				'selector' => '{{WRAPPER}} .today-date',
			]
		);

		$this->add_control(
			'day_options_heading',
			[
				'label'     => esc_html__('Day', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => ['show_day' => 'yes']
			]
		);

		$this->add_control(
			'day_text_color',
			[
				'label'     => esc_html__('Day Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .today' => 'color: {{VALUE}}',
				],
				'condition' => ['show_day' => 'yes']
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'day_text_typography',
				'selector'  => '{{WRAPPER}} .today',
				'condition' => ['show_day' => 'yes']
			]
		);

		$this->add_responsive_control(
			'day_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .today' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition'  => ['show_day' => 'yes']
			]
		);

		$this->add_control(
			'separator_heading',
			[
				'label'     => esc_html__('Separator', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => ['show_day' => 'yes'],
			]
		);

		$this->add_control(
			'separator_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .separator' => 'color: {{VALUE}}',
				],
				'condition' => ['show_day' => 'yes']
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'separator_typo',
				'selector'  => '{{WRAPPER}} .separator',
				'condition' => ['show_day' => 'yes']
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
		$this->add_render_attribute('wrapper', 'class', 'wcf__current_date');

?>
		<style>
			.wcf__current_date {
				display: flex;
			}
		</style>
		<div <?php $this->print_render_attribute_string('wrapper'); ?>>
			<?php if (! empty($settings['show_day'])) : ?>
				<div class="today">
					<?php echo esc_html(wp_date('l')); ?>
				</div>
				<?php
				if (! empty($settings['day_separator'])) {
				?><span class="separator"><?php echo esc_html($settings['day_separator']); ?></span><?php
																								}
																									?>
			<?php endif; ?>

			<div class="today-date">
				<?php
				if ('custom' === $settings['date_format'] && ! empty($settings['custom_date_format'])) {
					echo esc_html(wp_date($settings['custom_date_format']));
				} else {
					echo esc_html(wp_date($settings['date_format']));
				}
				?>
			</div>
		</div>
<?php
	}
}
