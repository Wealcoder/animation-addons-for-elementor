<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
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
 * Title
 *
 * Elementor widget for title.
 *
 * @since 1.0.0
 */
class Animated_Title extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--title';
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
		return esc_html__( 'Animated Title', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-t-letter';
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
			'title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXTAREA,
				'ai'          => [
					'type' => 'text',
				],
				'dynamic'     => [
					'active' => true,
				],
				'placeholder' => esc_html__( 'Enter your title', 'animation-addons-for-elementor' ),
				'default'     => esc_html__( 'Add Your Heading [Text] Here', 'animation-addons-for-elementor' ),
				'description' => 'For Highlight, keep text in [ ]. Ex. [ Text ]',
			]
		);

		$this->add_control(
			'link',
			[
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'options'     => [ 'url', 'is_external', 'nofollow' ],
				'default'     => [
					'url'         => '',
					'is_external' => false,
					'nofollow'    => true,
				],
				'label_block' => true,
			]
		);

		$this->add_control(
			'show_title_prefix',
			[
				'label'        => esc_html__( 'Show Prefix', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'title_prefix_use',
			[
				'label'     => esc_html__( 'Prefix Use On', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'prefix_on_normal',
				'options'   => [
					'prefix_on_normal' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
					'prefix_on_hover'  => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
				],
				'condition' => [ 'show_title_prefix' => 'yes' ]
			]
		);

		$this->add_control(
			'header_size',
			[
				'label'   => esc_html__( 'HTML Tag', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
				'default' => 'h2',
			]
		);

		$this->add_responsive_control(
			'align',
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
				'default'   => '',
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
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
					'{{WRAPPER}} .wcf--title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .wcf--title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'text_stroke',
				'selector' => '{{WRAPPER}} .wcf--title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .wcf--title',
			]
		);

		$this->add_control(
			'blend_mode',
			[
				'label'     => esc_html__( 'Blend Mode', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''            => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
					'multiply'    => 'Multiply',
					'screen'      => 'Screen',
					'overlay'     => 'Overlay',
					'darken'      => 'Darken',
					'lighten'     => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'saturation'  => 'Saturation',
					'color'       => 'Color',
					'difference'  => 'Difference',
					'exclusion'   => 'Exclusion',
					'hue'         => 'Hue',
					'luminosity'  => 'Luminosity',
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--title' => 'mix-blend-mode: {{VALUE}}',
				],
				'separator' => 'none',
			]
		);

		$this->add_control(
			'highlight',
			[
				'label'     => esc_html__( 'Highlight', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'highlight_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f00000',
				'selectors' => [
					'{{WRAPPER}} .wcf--title .highlight' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'aaebackground',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf--title .highlight',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'highlight_typography',
				'selector' => '{{WRAPPER}} .wcf--title .highlight',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'highlight_stroke',
				'selector' => '{{WRAPPER}} .wcf--title .highlight',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'highlight_shadow',
				'selector' => '{{WRAPPER}} .wcf--title .highlight',
			]
		);

		$this->add_control(
			'highlight_blend_mode',
			[
				'label'     => esc_html__( 'Blend Mode', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''            => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
					'multiply'    => 'Multiply',
					'screen'      => 'Screen',
					'overlay'     => 'Overlay',
					'darken'      => 'Darken',
					'lighten'     => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'saturation'  => 'Saturation',
					'color'       => 'Color',
					'difference'  => 'Difference',
					'exclusion'   => 'Exclusion',
					'hue'         => 'Hue',
					'luminosity'  => 'Luminosity',
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--title .highlight' => 'mix-blend-mode: {{VALUE}}',
				],
				'separator' => 'none',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_title_prefix_style',
			[
				'label'     => esc_html__( 'Prefix', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_title_prefix' => 'yes' ]
			]
		);

		$this->add_control(
			'title_prefix_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--title.prefix_on_normal:before, {{WRAPPER}} .wcf--title.prefix_on_hover:before' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_prefix_w',
			[
				'label'     => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--title.prefix_on_normal:before, {{WRAPPER}} .wcf--title.prefix_on_hover:before' => '--prefix-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_prefix_h',
			[
				'label'     => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--title.prefix_on_normal:before, {{WRAPPER}} .wcf--title.prefix_on_hover:before' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_prefix_gap',
			[
				'label'     => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--title.prefix_on_normal:before, {{WRAPPER}} .wcf--title.prefix_on_hover:before' => ' --prefix-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control( 'title_prefix_v_alignment',
			[
				'label'     => esc_html__( 'Vertical Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'top'    => [
						'title' => esc_html__( 'Top', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-top',
					],
					'middle' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'bottom' => [
						'title' => esc_html__( 'Bottom', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .wcf--title.prefix_on_normal:before, {{WRAPPER}} .wcf--title.prefix_on_hover:before' => 'vertical-align: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();

		// Hover
		$this->start_controls_section(
			'style_title_hover',
			[
				'label'     => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_h_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--title a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'highlight_h_color',
			[
				'label'     => esc_html__( 'Highlight Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--title a:hover .highlight' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'prefix_h_color',
			[
				'label'     => esc_html__( 'Prefix Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .has-link.prefix_on_normal:hover:before, {{WRAPPER}} .has-link.prefix_on_hover:hover:before' => 'background-color: {{VALUE}};',
				],
				'condition' => [ 'show_title_prefix' => 'yes' ],
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

		if ( '' === $settings['title'] ) {
			return;
		}

		$title = $settings['title'];
		preg_match_all( '/\[([^\]]*)\]/', $title, $matches );
		foreach ( $matches[0] as $key => $value ) {
			$title = str_replace( $value, '<span class="highlight">' . $matches[1][ $key ] . '</span>', $title, );
		}

		if ( ! empty( $settings['show_title_prefix'] ) ) {
			$this->add_render_attribute( 'title', 'class', $settings['title_prefix_use'] );
		}

		$this->add_render_attribute( 'title', 'class', 'wcf--title' );

		$title_html = sprintf( '<%1$s %2$s>%3$s</%1$s>', Utils::validate_html_tag( $settings['header_size'] ), $this->get_render_attribute_string( 'title' ), $title );
		
		if ( empty( $settings['link']['url'] ) ) {
			echo wp_kses_post( $title_html );
		} else {
			
			$this->add_link_attributes( 'link', $settings['link'] );
			$this->add_render_attribute( 'title', 'class', 'has-link' );
			?>
            <<?php echo esc_attr( Utils::validate_html_tag( $settings['header_size'] ) ); ?> <?php echo wp_kses_post($this->get_render_attribute_string( 'title' )); ?>>
            <a <?php $this->print_render_attribute_string( 'link' ); ?>>
				<?php echo wp_kses_post( $title ); ?>
            </a>
            </<?php echo esc_attr(Utils::validate_html_tag( $settings['header_size'] )); ?>>
			<?php
		}
	}
}
