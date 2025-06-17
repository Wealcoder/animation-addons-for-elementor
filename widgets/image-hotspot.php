<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Posts
 *
 * Elementor widget for Posts.
 *
 * @since 1.0.0
 */
class Image_Hotspot extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_name() {
		return 'aae--image-hotspot';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_title() {
		return esc_html__( 'Image Hotspot', 'animation-addons-for-elementor-pro' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_icon() {
		return 'wcf eicon-image-hotspot';
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
	 *
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
		return [ 'aae-image-hotspot' ];
	}

	public function get_script_depends() {
		return [ 'aae-image-hotspot' ];
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
		$this->register_hotspot_image();

		$this->register_hotspot_content();

		$this->register_hotspot_tooltip();

		$this->style_hotspot_image();

		$this->style_hotspot_content();

		$this->style_hotspot_tooltip();
	}

	protected function register_hotspot_image() {
		$this->start_controls_section(
			'section_image',
			[
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'hsp_image',
			[
				'label'   => esc_html__( 'Image', 'animation-addons-for-elementor-pro' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'hsp_img_size',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `thumbnail_size` and `thumbnail_custom_dimension`.
				'exclude' => [ 'custom' ],
				'include' => [],
				'default' => 'full',
			]
		);

		$this->end_controls_section();
	}

	protected function register_hotspot_content() {
		$this->start_controls_section(
			'section_hotspot',
			[
				'label' => esc_html__( 'Hotspot', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new Repeater();

		// Start Tabs
		$repeater->start_controls_tabs( 'hotspot_tabs' );

		// Tab 1: Content
		$repeater->start_controls_tab(
			'hsp_content_tab',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor-pro' ),
			]
		);

		$repeater->add_control(
			'hsp_layout',
			[
				'label'   => esc_html__( 'Hotspot Layout', 'animation-addons-for-elementor-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dot',
				'options' => [
					'dot'       => esc_html__( 'Default', 'animation-addons-for-elementor-pro' ),
					'icon'      => esc_html__( 'Icon', 'animation-addons-for-elementor-pro' ),
					'text'      => esc_html__( 'Text', 'animation-addons-for-elementor-pro' ),
					'icon-text' => esc_html__( 'Icon + Text', 'animation-addons-for-elementor-pro' ),
				],
			]
		);

		$repeater->add_control(
			'tooltip_type',
			[
				'label'   => esc_html__( 'Tooltip Type', 'animation-addons-for-elementor-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'tooltip',
				'options' => [
					'tooltip' => esc_html__( 'Content', 'animation-addons-for-elementor-pro' ),
					'link'    => esc_html__( 'Link', 'animation-addons-for-elementor-pro' ),
				],
			]
		);

		$repeater->add_control(
			'hsp_icon',
			[
				'label'       => esc_html__( 'Icon', 'animation-addons-for-elementor-pro' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-circle',
					'library' => 'fa-solid',
				],
				'condition'   => [ 'hsp_layout' => [ 'icon', 'icon-text' ] ],
			]
		);

		$repeater->add_control(
			'hsp_text',
			[
				'label'       => esc_html__( 'Text', 'animation-addons-for-elementor-pro' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Type your text here', 'animation-addons-for-elementor-pro' ),
				'condition'   => [ 'hsp_layout' => [ 'text', 'icon-text' ] ],
			]
		);

		$repeater->add_control(
			'tlp_content',
			[
				'label'       => esc_html__( 'Tooltip Content', 'animation-addons-for-elementor-pro' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => esc_html__( 'Tooltip content', 'animation-addons-for-elementor-pro' ),
				'placeholder' => esc_html__( 'Type your content here', 'animation-addons-for-elementor-pro' ),
				'condition'   => [ 'tooltip_type' => 'tooltip' ],
			]
		);

		$repeater->add_control(
			'tlp_link',
			[
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor-pro' ),
				'type'        => Controls_Manager::URL,
				'options'     => [ 'url', 'is_external', 'nofollow' ],
				'default'     => [
					'url'         => '',
					'is_external' => false,
					'nofollow'    => false,
				],
				'label_block' => true,
				'condition'   => [ 'tooltip_type' => 'link' ],
			]
		);

		$repeater->end_controls_tab();

		// Tab 2: Position
		$repeater->start_controls_tab(
			'hsp_position_tab',
			[
				'label' => esc_html__( 'Position', 'animation-addons-for-elementor-pro' ),
			]
		);

		$repeater->add_responsive_control(
			'position_left',
			[
				'label'      => esc_html__( 'Horizontal Offset', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'    => [
					'size' => 50,
					'unit' => '%',
				],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$repeater->add_responsive_control(
			'position_top',
			[
				'label'      => esc_html__( 'Vertical Offset', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'    => [
					'size' => 50,
					'unit' => '%',
				],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$repeater->end_controls_tab();

		// End Tabs
		$repeater->end_controls_tabs();

		$this->add_control(
			'hsp_list',
			[
				'label'       => esc_html__( 'Hotspot Lists', 'animation-addons-for-elementor-pro' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [ [] ],
				'title_field' => '{{{ hsp_text }}}',
			]
		);

		$this->add_control(
			'hsp_animation',
			[
				'label'   => esc_html__( 'Animation', 'animation-addons-for-elementor-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'pulse',
				'options' => [
					'beat'  => esc_html__( 'Soft Beat', 'animation-addons-for-elementor-pro' ),
					'pulse' => esc_html__( 'Pulse', 'animation-addons-for-elementor-pro' ),
					'none'  => esc_html__( 'None', 'animation-addons-for-elementor-pro' ),
				],
			]
		);

		$this->add_control(
			'animation_speed',
			[
				'label'     => esc_html__( 'Animation Speed', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0.1,
				'max'       => 10,
				'step'      => 0.1,
				'default'   => 3,
				'selectors' => [
					'{{WRAPPER}} .hotspot-icon' => '--anim-speed: {{SIZE}}s;',
				],
				'condition' => [ 'hsp_animation' => [ 'beat', 'pulse' ] ]
			]
		);

		$this->add_control(
			'pulse_color',
			[
				'label'     => esc_html__( 'Pulse Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hotspot-icon' => '--pulse-color: {{VALUE}}',
				],
				'condition' => [ 'hsp_animation' => 'pulse' ]
			]
		);

		$this->end_controls_section();
	}

	protected function register_hotspot_tooltip() {
		$this->start_controls_section(
			'section_tooltip',
			[
				'label' => esc_html__( 'Tooltip', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'trigger_type',
			[
				'label'   => esc_html__( 'Trigger Type', 'animation-addons-for-elementor-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'hover',
				'options' => [
					'hover' => esc_html__( 'Hover', 'animation-addons-for-elementor-pro' ),
					'click' => esc_html__( 'Click', 'animation-addons-for-elementor-pro' ),
					'none'  => esc_html__( 'None', 'animation-addons-for-elementor-pro' ),
				],
			]
		);

		$this->add_control(
			'tlp_position',
			[
				'label'        => esc_html__( 'Position', 'animation-addons-for-elementor-pro' ),
				'type'         => Controls_Manager::SELECT,
				'prefix_class' => 'aae--tlp-position-',
				'default'      => 'bottom',
				'options'      => [
					'top'    => esc_html__( 'Top', 'animation-addons-for-elementor-pro' ),
					'bottom' => esc_html__( 'Bottom', 'animation-addons-for-elementor-pro' ),
					'left'   => esc_html__( 'Left', 'animation-addons-for-elementor-pro' ),
					'right'  => esc_html__( 'Right', 'animation-addons-for-elementor-pro' ),
				],
			]
		);

		$this->add_responsive_control(
			'tlp_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .tooltip-content' => '--tlp-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tooltip_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor-pro' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor-pro' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor-pro' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .tooltip-content' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_hotspot_image() {
		$this->start_controls_section(
			'style_hsp_image',
			[
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'img_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .aae--image-hotspot' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'img_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .aae--image-hotspot' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'img_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .aae--image-hotspot img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_hotspot_content() {
		$this->start_controls_section(
			'style_hsp_content',
			[
				'label' => esc_html__( 'Hotspot', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'hsp_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .hotspot-icon',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'hsp_border',
				'selector' => '{{WRAPPER}} .hotspot-icon',
			]
		);

		$this->add_responsive_control(
			'hsp_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .hotspot-icon, {{WRAPPER}} .pulse .hotspot-icon::after, {{WRAPPER}} .pulse .hotspot-icon::before' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'hsp_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .hotspot-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Dot
		$this->add_control(
			'dot_heading',
			[
				'label'     => esc_html__( 'Dot', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'dot_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .hotspot-icon.dot' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'dot_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .hotspot-icon.dot' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'text_heading',
			[
				'label'     => esc_html__( 'Text', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .text, {{WRAPPER}} .icon-text' => 'fill: {{VALUE}}; color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'text_typo',
				'selector' => '{{WRAPPER}} .text, {{WRAPPER}} .icon-text',
			]
		);

		$this->add_control(
			'icon_heading',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .icon' => 'fill: {{VALUE}}; color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .hotspot-icon svg, {{WRAPPER}} .hotspot-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_gap',
			[
				'label'      => esc_html__( 'Icon Gap', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .hotspot-icon' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_hotspot_tooltip() {
		$this->start_controls_section(
			'style_hsp_tooltip',
			[
				'label' => esc_html__( 'Tooltip', 'animation-addons-for-elementor-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tlp_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .tooltip-content, {{WRAPPER}} .tooltip-content::after',
			]
		);

		$this->add_responsive_control(
			'tlp_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .tooltip-content' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tlp_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .tooltip-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tlp_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .tooltip-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

		if ( empty( $settings['hsp_list'] ) ) {
			return;
		}
		?>
        <div class="aae--image-hotspot <?php echo $settings['hsp_animation']; ?>">
			<?php
			echo Group_Control_Image_Size::get_attachment_image_html(
				$settings,
				'hsp_img_size',
				'hsp_image',
			);
			?>

			<?php
			foreach ( $settings['hsp_list'] as $index => $item ) {
				if ( 'link' === $item['tooltip_type'] ) {
					$link_id = 'link_id_' . $index;

					if ( ! empty( $item['tlp_link']['url'] ) ) {
						$this->add_link_attributes( $link_id, $item['tlp_link'] );
					}

					?>
                    <a class="aae--hotspot-item elementor-repeater-item-<?php echo esc_attr( $item['_id'] ) . ' ' . $settings['trigger_type']; ?>" <?php $this->print_render_attribute_string( $link_id ); ?>>
                        <div class="hotspot-icon <?php echo $item['hsp_layout']; ?>">
							<?php $this->render_hotspot_layout( $item ); ?>
                        </div>
                    </a>
					<?php
				} else {
					?>
                    <div class="aae--hotspot-item elementor-repeater-item-<?php echo esc_attr( $item['_id'] ) . ' ' . $settings['trigger_type']; ?>">
                        <div class="hotspot-icon <?php echo $item['hsp_layout']; ?>">
							<?php $this->render_hotspot_layout( $item ); ?>
                        </div>

                        <div class="tooltip-content">
							<?php echo wp_kses_post( $item['tlp_content'] ); ?>
                        </div>
                    </div>
					<?php
				}
			}
			?>
        </div>
		<?php
	}

	protected function render_hotspot_layout( $item ) {
		if ( 'text' === $item['hsp_layout'] ) {
			echo esc_html( $item['hsp_text'] );
		} elseif ( 'icon' === $item['hsp_layout'] ) {
			Icons_Manager::render_icon( $item['hsp_icon'], [ 'aria-hidden' => 'true' ] );
		} elseif ( 'icon-text' === $item['hsp_layout'] ) {
			echo esc_html( $item['hsp_text'] );
			Icons_Manager::render_icon( $item['hsp_icon'], [ 'aria-hidden' => 'true' ] );
		} else {
			echo '';
		}
	}

}
