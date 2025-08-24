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
	exit; // Exit if accessed directly
}

/**
 * Button
 *
 * Elementor widget for testimonial.
 *
 * @since 1.0.0
 */
class Button_Pro extends Widget_Base
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
		return 'aae--advanced-button';
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
		return esc_html__('Advanced Button', 'animation-addons-for-elementor');
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
		return 'wcf eicon-post-slider';
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
		return ['aae--button-pro'];
	}

	public function get_script_depends()
	{
		return ['aae--button-pro'];
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
			'section_content',
			[
				'label' => esc_html__('Button', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'btn_style',
			[
				'label'     => esc_html__('Style', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => '1',
				'options'   => [
					'1' => esc_html__('1', 'animation-addons-for-elementor'),
					'2' => esc_html__('2', 'animation-addons-for-elementor'),
					'3' => esc_html__('3', 'animation-addons-for-elementor'),
					'4' => esc_html__('4', 'animation-addons-for-elementor'),
					'5' => esc_html__('5', 'animation-addons-for-elementor'),
					'6' => esc_html__('6', 'animation-addons-for-elementor'),
					'7' => esc_html__('7', 'animation-addons-for-elementor'),
					'8' => esc_html__('8', 'animation-addons-for-elementor'),
				],
				'separator' => 'after',
			]
		);

		$this->add_control(
			'btn_text',
			[
				'label'   => esc_html__('Text', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__('Discover More', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'btn_icon',
			[
				'label'       => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-arrow-right',
					'library' => 'fa-solid',
				],
				'condition'   => ['btn_style!' => '4'],
			]
		);

		$this->add_control(
			'btn_icon_position',
			[
				'label'     => esc_html__('Icon Position', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'row',
				'options'   => [
					'row'         => esc_html__('After', 'animation-addons-for-elementor'),
					'row-reverse' => esc_html__('Before', 'animation-addons-for-elementor'),
				],
				'selectors' => [
					'{{WRAPPER}} .aae--btn-pro' => 'flex-direction: {{VALUE}};',
				],
				'condition' => [
					'btn_style!' => ['5', '6'],
				],
			]
		);

		$this->add_control(
			'btn_link',
			[
				'label'   => esc_html__('Link', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::URL,
				'options' => ['url', 'is_external', 'nofollow'],
				'default' => [
					'url'         => '#',
					'is_external' => false,
					'nofollow'    => true,
				],
			]
		);

		$this->add_responsive_control(
			'btn_outline_gap',
			[
				'label'      => esc_html__('Outline Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 20,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .style-7 .aae--btn-pro' => '--outline-gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['btn_style' => '7'],
			]
		);

		$this->add_responsive_control(
			'btn_align',
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
					'{{WRAPPER}} .aae--btn-pro-wrapper' => 'text-align: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();


		// Button Style
		$this->button_style_controls();
	}

	// Button Style
	public function button_style_controls()
	{

		$this->start_controls_section(
			'style_button',
			[
				'label' => esc_html__('Button', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'btn_typo',
				'selector' => '{{WRAPPER}} .aae--btn-pro, {{WRAPPER}} .g-btn-text',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'btn_bg',
				'types'     => ['classic', 'gradient'],
				'exclude'   => ['image'],
				'selector'  => '{{WRAPPER}} .aae--btn-pro, {{WRAPPER}} .g-btn-text, {{WRAPPER}} .g-btn-icon',
				'condition' => ['btn_style!' => '7'],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'btn_bg_2',
				'types'     => ['classic', 'gradient'],
				'exclude'   => ['image'],
				'selector'  => '{{WRAPPER}} .aae--btn-pro::after',
				'condition' => ['btn_style' => ['7', '8']],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'btn_border',
				'selector'  => '{{WRAPPER}} .aae--btn-pro, {{WRAPPER}} .g-btn-text, {{WRAPPER}} .g-btn-icon',
				'condition' => [
					'btn_style!' => '1',
				],
			]
		);

		$this->add_responsive_control(
			'btn_border_height',
			[
				'label'      => esc_html__('Border Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .btn-border-divide .text, {{WRAPPER}} .btn-border-divide .icon' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['btn_style' => '1'],
			]
		);

		$this->add_responsive_control(
			'btn_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .aae--btn-pro, {{WRAPPER}} .g-btn-text, {{WRAPPER}} .g-btn-icon, {{WRAPPER}} .aae--btn-pro::after' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'btn_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .aae--btn-pro' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .g-btn-text'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Icon Style
		$this->add_control(
			'btn_icon_heading',
			[
				'label'     => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'btn_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .aae--btn-pro .icon, {{WRAPPER}} .g-btn-icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .style-4 .aae--btn-pro strong'                => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'btn_icon_size_width',
			[
				'label'      => esc_html__('Icon Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .g-btn-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; --icon-width: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['btn_style' => ['5', '6']],
			]
		);

		$this->add_responsive_control(
			'btn_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .aae--btn-pro, {{WRAPPER}} .g-btn-text' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['btn_style!' => ['5', '6']],
			]
		);

		// Tabs
		$this->start_controls_tabs(
			'btn_style_tabs'
		);

		$this->start_controls_tab(
			'btn_normal_tab',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'btn_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aae--btn-pro, {{WRAPPER}} .btn-text-flip span, {{WRAPPER}} .g-btn-text' => 'color: {{VALUE}}; fill: {{VALUE}}',
					'{{WRAPPER}} .g-btn-icon' => 'color: {{VALUE}}; fill: {{VALUE}}',
					'{{WRAPPER}} .style-4 .aae--btn-pro strong' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'btn_br_color',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .btn-border-divide .text, {{WRAPPER}} .btn-border-divide .icon' => 'border-color: {{VALUE}}',
				],
				'condition' => ['btn_style' => '1'],
			]
		);

		$this->end_controls_tab();

		// Hover
		$this->start_controls_tab(
			'btn_hover_tab',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'btn_h_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aae--btn-pro:hover, {{WRAPPER}} .aae--btn-pro:hover .icon'      => 'color: {{VALUE}}; fill: {{VALUE}}',
					'{{WRAPPER}} .btn-text-flip:hover span, {{WRAPPER}} .btn-text-flip:hover svg' => 'color: {{VALUE}}; fill: {{VALUE}}',
					'{{WRAPPER}} .aae-btn-pro-group:hover span, {{WRAPPER}} .g-btn-text:hover'    => 'color: {{VALUE}}',
					'{{WRAPPER}} .aae-btn-pro-group:hover .g-btn-icon svg'                        => 'fill: {{VALUE}}',
					'{{WRAPPER}} .style-4 .aae--btn-pro:hover strong'                             => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .style-4 .aae--btn-pro:hover strong::after'                      => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'btn_h_border',
			[
				'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .aae--btn-pro:hover, {{WRAPPER}} .g-btn-text:hover, {{WRAPPER}} .aae-btn-pro-group:hover span' => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .btn-border-divide:hover .text, {{WRAPPER}} .btn-border-divide:hover .icon'                    => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'btn_h_bg',
				'types'    => ['classic', 'gradient'],
				'exclude'  => ['image'],
				'selector' => '{{WRAPPER}} .aae--btn-pro:hover, {{WRAPPER}} .aae-btn-pro-group:hover span, {{WRAPPER}} .style-4 .aae--btn-pro span',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'btn_box_shadow',
				'selector'  => '{{WRAPPER}} .style-2 .aae--btn-pro:hover',
				'condition' => ['btn_style' => '2'],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

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

		if (! empty($settings['btn_link']['url'])) {
			$this->add_link_attributes('btn_link', $settings['btn_link']);
		}
?>

		<div class="aae--btn-pro-wrapper style-<?php echo esc_html($settings['btn_style']); ?>">
			<?php if ('1' === $settings['btn_style']) { ?>
				<a <?php $this->print_render_attribute_string('btn_link'); ?> class="aae--btn-pro btn-border-divide">
					<span class="text"><?php echo esc_html($settings['btn_text']); ?></span>
					<span class="icon">
						<?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
						<?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
					</span>
				</a>
			<?php } elseif ('2' === $settings['btn_style']) { ?>
				<a <?php $this->print_render_attribute_string('btn_link'); ?> class="aae--btn-pro">
					<?php echo esc_html($settings['btn_text']); ?>
					<span class="icon"><?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
					</span>
				</a>
			<?php } elseif ('3' === $settings['btn_style']) { ?>
				<a <?php $this->print_render_attribute_string('btn_link'); ?> class="aae--btn-pro btn-text-flip">
					<span data-text="<?php echo esc_html($settings['btn_text']); ?>">
						<?php echo esc_html($settings['btn_text']); ?>
					</span>
					<?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
				</a>
			<?php } elseif ('4' === $settings['btn_style']) { ?>
				<a <?php $this->print_render_attribute_string('btn_link'); ?> class="btn-hover aae--btn-pro">
					<span></span>
					<?php echo esc_html($settings['btn_text']); ?>
					<strong></strong>
				</a>
			<?php } elseif ('5' === $settings['btn_style']) { ?>
				<a <?php $this->print_render_attribute_string('btn_link'); ?> class="aae-btn-pro-group">
					<span class="g-btn-icon">
						<?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
					</span>
					<span class="g-btn-text">
						<?php echo esc_html($settings['btn_text']); ?>
					</span>
					<span class="g-btn-icon">
						<?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
					</span>
				</a>
			<?php } elseif ('6' === $settings['btn_style']) { ?>
				<a <?php $this->print_render_attribute_string('btn_link'); ?> class="aae-btn-pro-group">
					<span class="g-btn-icon">
						<?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
					</span>
					<span class="g-btn-text">
						<?php echo esc_html($settings['btn_text']); ?>
					</span>
					<span class="g-btn-icon">
						<?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
					</span>
				</a>
			<?php } elseif ('7' === $settings['btn_style']) { ?>
				<a <?php $this->print_render_attribute_string('btn_link'); ?> class="aae--btn-pro">
					<?php echo esc_html($settings['btn_text']); ?>
					<span class="icon"><?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
					</span>
				</a>
			<?php } elseif ('8' === $settings['btn_style']) { ?>
				<a <?php $this->print_render_attribute_string('btn_link'); ?> class="aae--btn-pro">
					<?php echo esc_html($settings['btn_text']); ?>
					<span class="icon"><?php Icons_Manager::render_icon($settings['btn_icon'], ['aria-hidden' => 'true']); ?>
					</span>
				</a>
			<?php } ?>
		</div>
<?php
	}
}
