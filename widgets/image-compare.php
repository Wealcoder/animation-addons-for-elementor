<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Image_Size;

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
class Image_Compare extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--image-compare';
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
		return esc_html__( 'Image Compare', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-image-before-after';
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
		return [ 'wcf--image-compare' ];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array(
			'wcf--image-compare',
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
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'before_image',
			[
				'label'   => esc_html__( 'Before Image', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'after_image',
			[
				'label'   => esc_html__( 'After Image', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'show_caption',
			[
				'label'        => esc_html__( 'Show Caption', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'before_caption',
			[
				'label'       => esc_html__( 'Before Caption', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Before Caption', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your caption here', 'animation-addons-for-elementor' ),
				'condition'   => [ 'show_caption' => 'yes' ],
			]
		);

		$this->add_control(
			'after_caption',
			[
				'label'       => esc_html__( 'After Caption', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'After Caption', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your caption here', 'animation-addons-for-elementor' ),
				'condition'   => [ 'show_caption' => 'yes' ],
			]
		);

		$this->add_control(
			'show_btn',
			[
				'label'        => esc_html__( 'Show Button', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'prefix_class' => 'cmp--align-',
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
				'default'   => 'left',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// Image Style
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'width',
			[
				'label'          => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'size_units'     => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range'          => [
					'%'  => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'vw' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'      => [
					'{{WRAPPER}} .wcf--image-compare, {{WRAPPER}} .slider-img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'max-width',
			[
				'label'          => esc_html__( 'Max Width', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'size_units'     => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range'          => [
					'%'  => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'vw' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'      => [
					'{{WRAPPER}} .wcf--image-compare, {{WRAPPER}} .slider-img' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vh', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--image-compare' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'image_border',
				'selector'  => '{{WRAPPER}} .wcf--image-compare',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--image-compare' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'image_box_shadow',
				'exclude'  => [
					'box_shadow_position',
				],
				'selector' => '{{WRAPPER}} .wcf--image-compare',
			]
		);

		$this->end_controls_section();


		// Handle Style
		$this->start_controls_section(
			'style_handle',
			[
				'label' => esc_html__( 'Handler', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'line_color',
			[
				'label' => esc_html__( 'Line Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--image-compare-handle' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'circle_heading',
			[
				'label' => esc_html__( 'Circle', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'circle_bg',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => ['image'],
				'selector' => '{{WRAPPER}} .wcf--image-compare-handle:after',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'circle_border',
				'selector' => '{{WRAPPER}} .wcf--image-compare-handle:after',
			]
		);

		$this->add_responsive_control(
			'circle_b_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--image-compare-handle:after' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();


		// Caption Style
		$this->start_controls_section(
			'style_caption',
			[
				'label' => esc_html__( 'Caption', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => ['show_caption' => 'yes'],
			]
		);

		$this->add_control(
			'caption_color',
			[
				'label' => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .slider-caption' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'content_typography',
				'selector' => '{{WRAPPER}} .slider-caption',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'caption_bg',
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .slider-caption',
			]
		);

		$this->add_responsive_control(
			'caption_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .slider-caption' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'caption_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .slider-caption' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();


		// Button Style
		$this->start_controls_section(
			'style_button',
			[
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => ['show_btn' => 'yes'],
			]
		);

		$this->add_control(
			'btn_color',
			[
				'label' => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cmp-btn' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'btn_typo',
				'selector' => '{{WRAPPER}} .cmp-btn',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'btn_bg',
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .cmp-btn',
			]
		);

		$this->add_responsive_control(
			'btn_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .cmp-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'btn_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .cmp-btn-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

		$this->add_render_attribute( 'wrapper', 'class', 'wcf--image-compare' );
		?>

        <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>

            <!-- After Image -->
            <div class="slider-right">
                <div class="slider-img img-right"
                     style="background-image: url('<?php echo $settings['after_image']['url']; ?>')">
					<?php if ( 'yes' === $settings['show_caption'] ) { ?>
                        <p class="slider-caption slider-caption-right"><?php echo esc_html($settings['after_caption']); ?></p>
					<?php } ?>
                </div>
            </div>

            <!-- Before Image -->
            <div class="slider-left">
                <div class="slider-img img-left"
                     style="background-image: url('<?php echo $settings['before_image']['url']; ?>')">
					<?php if ( 'yes' === $settings['show_caption'] ) { ?>
                        <p class="slider-caption slider-caption-left"><?php echo esc_html($settings['before_caption']); ?></p>
					<?php } ?>
                </div>
            </div>

            <!-- DRAG HANDLE -->
            <div class="wcf--image-compare-handle"></div>

            <!-- Control Buttons -->
			<?php if ( 'yes' === $settings['show_btn'] ) { ?>
                <div class="cmp-btn-wrapper">
                    <button class="cmp-btn btn-expand-left"><?php echo esc_html__('Expand left', 'animation-addons-for-elementor'); ?></button>
                    <button class="cmp-btn btn-expand-center"><?php echo esc_html__('50/50', 'animation-addons-for-elementor'); ?></button>
                    <button class="cmp-btn btn-expand-right"><?php echo esc_html__('Expand right', 'animation-addons-for-elementor'); ?></button>
                </div>
			<?php } ?>
        </div>


		<?php
	}
}
