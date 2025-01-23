<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WCF_ADDONS\WCF_Button_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Elementor Hello World
 *
 * Elementor widget for hello world.
 *
 * @since 1.0.0
 */
class Image_Box extends Widget_Base {
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
		return 'wcf--image-box';
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
		return esc_html__( 'Image Box', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-image-box';
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
		return [ 'wcf--image-box', 'wcf--button', ];
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
			'image_box_style',
			[
				'label'   => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__( 'Style One', 'animation-addons-for-elementor' ),
					'2' => esc_html__( 'Style Two', 'animation-addons-for-elementor' ),
					'3' => esc_html__( 'Style Three', 'animation-addons-for-elementor' ),
					'4' => esc_html__( 'Style Four', 'animation-addons-for-elementor' ),
					'5' => esc_html__( 'Style Five', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_responsive_control(
			'img_content_direction',
			[
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'column'         => [
						'title' => esc_html__( 'Row', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-section',
					],
					'column-reverse' => [
						'title' => esc_html__( 'Row Reverse', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-exchange',
					],
					'row'            => [
						'title' => esc_html__( 'Column', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-column',
					],
					'row-reverse'    => [
						'title' => esc_html__( 'Column Reverse', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-wrap',
					],
				],
				'toggle'    => true,
				'default'   => 'column',
				'selectors' => [
					'{{WRAPPER}} .wcf--image-box' => 'flex-direction: {{VALUE}};',
				],
				'condition' => [
					'image_box_style' => [ '1', '2' ],
				],
			]
		);

		$this->add_control(
			'content_align',
			[
				'label'     => esc_html__( 'Vertically Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__( 'Top', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-up',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'flex-end'   => [
						'title' => esc_html__( 'Bottom', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .wcf--image-box' => 'align-items: {{VALUE}};',
				],
				'condition' => [
					'img_content_direction' => [ 'row', 'row-reverse' ],
				],
			]
		);

		$this->add_responsive_control(
			'img_content_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
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
					'{{WRAPPER}} .wcf--image-box' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'image_box_style' => [ '1', '2' ],
				],
			]
		);

		$this->add_control(
			'link_type',
			[
				'label'     => esc_html__( 'Link Type', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'button',
				'separator' => 'before',
				'options'   => [
					'none'    => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'button'  => esc_html__( 'Button', 'animation-addons-for-elementor' ),
					'wrapper' => esc_html__( 'Wrapper', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'details_link',
			[
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [
					'active' => true,
				],
				'placeholder' => esc_html__( 'https://your-link.com', 'animation-addons-for-elementor' ),
				'condition'   => [
					'link_type' => 'wrapper',
				],
			]
		);

		$this->add_control(
			'image_box_align',
			[
				'label'        => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => [
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
				'default'      => 'left',
				'toggle'       => true,
				'prefix_class' => 'img-box-wrap-',
				'selectors'    => [
					'{{WRAPPER}} .wcf--image-box' => 'text-align: {{VALUE}};',
				],
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'content_position',
			[
				'label'     => esc_html__( 'Vertically Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html__( 'Top', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-up',
					],
					'center'     => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'flex-end'   => [
						'title' => esc_html__( 'Bottom', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
				],
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .content' => 'justify-content: {{VALUE}};',
				],
				'condition' => [
					'image_box_style' => '4',
				],
			]
		);

		$this->add_control(
			'image_box_icon',
			[
				'label'            => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
				'recommended'      => [
					'fa-solid' => [
						'arrow-up',
						'arrow-down',
						'arrow-left',
						'arrow-right',
					],
				],
			]
		);

		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'section_box_style',
			[
				'label' => esc_html__( 'Image Box', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'box_border',
				'selector' => '{{WRAPPER}} .wcf--image-box',
			]
		);

		$this->add_control(
			'box_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--image-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		//hover effect
		$this->add_control(
			'el_hover_effects',
			[
				'label'        => esc_html__( 'Hover Effect', 'animation-addons-for-elementor' ),
				'description'  => esc_html__( 'This effect will work only on image tags.', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'effect-zoom-in',
				'options'      => [
					''            => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'effect-zoom-in' => esc_html__( 'Zoom In', 'animation-addons-for-elementor' ),
					'effect-zoom-out'  => esc_html__( 'Zoom Out', 'animation-addons-for-elementor' ),
					'left-move'   => esc_html__( 'Left Move', 'animation-addons-for-elementor' ),
					'right-move'  => esc_html__( 'Right Move', 'animation-addons-for-elementor' ),
				],
				'prefix_class' => 'wcf--image-',
			]
		);

		$this->end_controls_section();

		// Image Controls
		$this->register_image_controls();

		// Content Controls
		$this->register_content_controls();

		//button
		$this->start_controls_section(
			'section_button_content',
			[
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'condition' => [
					'link_type' => 'button',
				],
			]
		);

		//button content
		$this->register_button_content_controls();

		$this->end_controls_section();

		//button style
		$this->start_controls_section(
			'section_btn_style',
			[
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'link_type' => 'button',
				],
			]
		);

		$this->register_button_style_controls();

		$this->end_controls_section();

	}

	protected function register_image_controls() {
		$this->start_controls_section(
			'section_image',
			[
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'image',
			[
				'label'   => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'image_size',
				'exclude' => [ 'custom' ],
				'include' => [],
				'default' => 'full',
			]
		);

		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'section_image_style',
			[
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'img_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .thumb img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'img_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 700,
						'step' => 1,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'object_fit',
			[
				'label'     => esc_html__( 'Object Fit', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'condition' => [
					'img_height[size]!' => '',
				],
				'options'   => [
					''        => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'fill'    => esc_html__( 'Fill', 'animation-addons-for-elementor' ),
					'cover'   => esc_html__( 'Cover', 'animation-addons-for-elementor' ),
					'contain' => esc_html__( 'Contain', 'animation-addons-for-elementor' ),
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .thumb img' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'object_position',
			[
				'label'     => esc_html__( 'Object Position', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'center center' => esc_html__( 'Center Center', 'animation-addons-for-elementor' ),
					'center left'   => esc_html__( 'Center Left', 'animation-addons-for-elementor' ),
					'center right'  => esc_html__( 'Center Right', 'animation-addons-for-elementor' ),
					'top center'    => esc_html__( 'Top Center', 'animation-addons-for-elementor' ),
					'top left'      => esc_html__( 'Top Left', 'animation-addons-for-elementor' ),
					'top right'     => esc_html__( 'Top Right', 'animation-addons-for-elementor' ),
					'bottom center' => esc_html__( 'Bottom Center', 'animation-addons-for-elementor' ),
					'bottom left'   => esc_html__( 'Bottom Left', 'animation-addons-for-elementor' ),
					'bottom right'  => esc_html__( 'Bottom Right', 'animation-addons-for-elementor' ),
				],
				'default'   => 'center center',
				'selectors' => [
					'{{WRAPPER}} .thumb img' => 'object-position: {{VALUE}};',
				],
				'condition' => [
					'object_fit' => 'cover',
				],
			]
		);

		$this->add_responsive_control(
			'img_b_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .thumb img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			]
		);

		// Title
		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Siyantika Glory', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your title', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'   => esc_html__( 'Title HTML Tag', 'animation-addons-for-elementor' ),
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
				'default' => 'h4',
			]
		);

		// Sub Title
		$this->add_control(
			'subtitle',
			[
				'label'       => esc_html__( 'Sub Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Modelling - 2012', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your sub title', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'subtitle_position',
			[
				'label'     => esc_html__( 'Sub Title Position', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'column'         => [
						'title' => esc_html__( 'Before Title', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-up',
					],
					'column-reverse' => [
						'title' => esc_html__( 'After Title', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
				],
				'default'   => 'column',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .title-wrap' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		// Description
		$this->add_control(
			'description',
			[
				'label'       => esc_html__( 'Description', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 10,
				'default'     => esc_html__( 'Hatha yoga built on a harmonious balance between body strength and softness', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your description', 'animation-addons-for-elementor' ),
			]
		);

		$this->end_controls_section();


		// Style
		$this->start_controls_section(
			'section_content_style',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		// Title
		$this->add_control(
			'title_heading',
			[
				'label'     => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'title_space',
			[
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_hover_space',
			[
				'label'     => esc_html__( 'Hover Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--image-box.style-3:hover .title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'image_box_style' => '3',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .title',
			]
		);

		// Sub Title
		$this->add_control(
			'subtitle_heading',
			[
				'label'     => esc_html__( 'Sub Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'subtitle_space',
			[
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .subtitle' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'subtitle_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .subtitle' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'subtitle_typography',
				'selector' => '{{WRAPPER}} .subtitle',
			]
		);

		// Description
		$this->add_control(
			'desc_heading',
			[
				'label'     => esc_html__( 'Description', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'desc_space',
			[
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .description',
			]
		);

		// Icon
		$this->add_control(
			'icon_heading',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'icon_space',
			[
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .icon' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .icon' => 'color: {{VALUE}}; fill: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 6,
						'max' => 300,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_responsive_control(
			'icon_rotate',
			[
				'label'      => esc_html__( 'Rotate', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'deg' ],
				'range'      => [
					'deg' => [
						'min' => -360,
						'max' => 360,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .icon i, {{WRAPPER}} .icon svg' => 'transform: rotate({{SIZE}}{{UNIT}});',
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

		//Wrapper
		$this->add_render_attribute( 'wrapper', 'class', 'wcf--image-box style-' . $settings['image_box_style'] );

		// Wrapper Tag
		$link_tag = 'div';
		if ( ! empty( $settings['details_link']['url'] ) && 'wrapper' === $settings['link_type'] ) {
			$link_tag = 'a';
			$this->add_link_attributes( 'wrapper', $settings['details_link'] );
		}

		// Font Awesome
		$migrated = isset( $settings['__fa4_migrated']['image_box_icon'] );
		$is_new   = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();
		?>

		<<?php Utils::print_validated_html_tag($link_tag); ?> <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<div class="thumb">
				<?php				
                Group_Control_Image_Size::print_attachment_image_html($settings, 'image_size', 'image');
				if ( '2' === $settings['image_box_style'] && 'button' === $settings['link_type'] ) {
					$this->render_button( $settings );
				}
				?>
			</div>
			<div class="content">
				<div class="wrap">
					<?php
						if ( '' != $settings['image_box_icon']['value'] ) : ?>
							<div class="icon">
								<?php if ( $is_new || $migrated ) :
									Icons_Manager::render_icon( $settings['image_box_icon'], [ 'aria-hidden' => 'true' ] );
								else : ?>
									<i class="<?php echo esc_attr( $settings['icon'] ); ?>" aria-hidden="true"></i>
								<?php endif; ?>
							</div>
							<?php
						endif;
					?>

					<?php if ( '3' === $settings['image_box_style'] ) : ?>
						<?php if ( ! empty($settings['subtitle']) ) : ?>
							<div class="subtitle"><?php echo esc_html($settings['subtitle']); ?></div>
						<?php endif; ?>

						<<?php Utils::print_validated_html_tag($settings['title_tag']); ?> class="title">
							<?php $this->print_unescaped_setting('title'); ?>
						</<?php Utils::print_validated_html_tag($settings['title_tag']); ?>>
					<?php endif; ?>

					<?php if ( '3' != $settings['image_box_style'] ) : ?>
						<div class="title-wrap">
							<?php if ( ! empty($settings['subtitle']) ) : ?>
								<div class="subtitle"><?php echo esc_html($settings['subtitle']); ?></div>
							<?php endif; ?>

							<<?php Utils::print_validated_html_tag($settings['title_tag']); ?> class="title">
								<?php $this->print_unescaped_setting('title'); ?>
							</<?php Utils::print_validated_html_tag($settings['title_tag']); ?>>
						</div>
					<?php endif; ?>

					<?php if ( ! empty($settings['description']) ) : ?>
						<div class="description"><?php echo esc_html($settings['description']); ?></div>
					<?php endif; ?>

					<?php
					if ( '2' != $settings['image_box_style'] && 'button' === $settings['link_type'] ) {
						$this->render_button( $settings );
					}
					?>
				</div>
			</div>
		</<?php Utils::print_validated_html_tag($link_tag); ?>>
		<?php
	}
}
