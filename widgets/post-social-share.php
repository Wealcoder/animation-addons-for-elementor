<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Post_Social_Share extends Widget_Base {

	public function get_name() {
		return 'wcf--blog--post--social-share';
	}

	public function get_title() {
		return esc_html__( 'Social Share', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-share';
	}

	public function get_categories() {
		return [ 'wcf-single-addon' ];
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
		return [ 'wcf--socials-share' ];
	}
	public function get_keywords() {
		return [ 'social share', 'post share' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'social_icon_style',
			[
				'label'   => esc_html__( 'Social Icon Style', 'animation-addons-for-elementor' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''                         => esc_html__( 'One', 'animation-addons-for-elementor' ),
					'wcf-social-share-style-1' => esc_html__( 'Two', 'animation-addons-for-elementor' ),
					'wcf-social-share-style-2' => esc_html__( 'Three', 'animation-addons-for-elementor' ),

				]
			]
		);

		$this->add_control(
			'vendor_show',
			[
				'label'   => esc_html__( 'Show Title', 'animation-addons-for-elementor' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'inline' => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'none'   => esc_html__( 'None', 'animation-addons-for-elementor' ),

				],

				'selectors' => [
					'{{WRAPPER}} .default-details-social-media .info-s-title' => 'display: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'share_icon',
			[
				'label'   => esc_html__( 'Share Icon', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'yes' => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'no'  => esc_html__( 'No', 'animation-addons-for-elementor' ),

				]
			]
		);
		
		$this->add_control(
			'share_count',
			[
				'label'   => esc_html__( 'Share Count', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'yes' => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'no'  => esc_html__( 'No', 'animation-addons-for-elementor' ),

				]
			]
		);

		$this->add_control(
			'share_text',
			[
				'label'       => esc_html__( 'Share Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Share', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your title here', 'animation-addons-for-elementor' ),
				'condition'   => [ 'share_icon' => [ 'yes' ] ]
			]
		);

		$this->add_control(
			'share_icons',
			[
				'label'   => esc_html__( 'Share Icon', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fa-solid fa-share-nodes',
					'library' => 'fa-solid',
				]
			]
		);

		$this->add_control(
			'share_separator',
			[
				'label'   => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'no',
				'options' => [
					'yes' => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'no'  => esc_html__( 'No', 'animation-addons-for-elementor' ),
				]
			]
		);

		$this->add_control(
			'share_separator_icons',
			[
				'label'   => esc_html__( 'Separator Icon', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::ICONS,
				'condition'   => [ 'share_separator' => [ 'yes' ] ],
				'default' => [
					'value'   => 'fa-solid fa-share-nodes',
					'library' => 'fa-solid',
				]
			]
		);


		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'list_title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Vendor Title', 'animation-addons-for-elementor' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'list_vendor',
			[
				'label'       => esc_html__( 'Vendor', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''          => '---',
					'facebook'  => esc_html__( 'Facebook', 'animation-addons-for-elementor' ),
					'twitter'   => esc_html__( 'Twitter', 'animation-addons-for-elementor' ),
					'linkedin'  => esc_html__( 'Linkedin', 'animation-addons-for-elementor' ),
					'pinterest' => esc_html__( 'Pinterest ', 'animation-addons-for-elementor' ),
					'tumblr'    => esc_html__( 'Tumblr ', 'animation-addons-for-elementor' ),
					'blogger'   => esc_html__( 'Blogger ', 'animation-addons-for-elementor' ),
					'reddit'    => esc_html__( 'Reddit ', 'animation-addons-for-elementor' ),
				],
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'icon',
			[
				'label'   => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [

				]
			]
		);


		$this->add_control(
			'list',
			[
				'label'       => esc_html__( 'Social Share', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ list_title }}}',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$start = is_rtl() ? 'right' : 'left';
		$end   = is_rtl() ? 'left' : 'right';

		$this->add_responsive_control(
			'sflex_direction',
			[
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'row'            => [
						'title' => esc_html__( 'Row - horizontal', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-' . $end,
					],
					'column'         => [
						'title' => esc_html__( 'Column - vertical', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
					'row-reverse'    => [
						'title' => esc_html__( 'Row - reversed', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-' . $start,
					],
					'column-reverse' => [
						'title' => esc_html__( 'Column - reversed', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-up',
					],
				],
				'default'   => 'row',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'sflex_justify_content',
			[
				'label'     => esc_html__( 'Justify Content', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start'    => [
						'title' => esc_html_x( 'Start', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-start-h',
					],
					'center'        => [
						'title' => esc_html_x( 'Center', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-center-h',
					],
					'flex-end'      => [
						'title' => esc_html_x( 'End', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-end-h',
					],
					'space-between' => [
						'title' => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-space-between-h',
					],
					'space-around'  => [
						'title' => esc_html__( 'Space Around', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-space-around-h',
					],
					'space-evenly'  => [
						'title' => esc_html__( 'Space Evenly', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-space-evenly-h',
					],
				],
				'default'   => '',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'sflex_align_items',
			[
				'label'     => esc_html__( 'Align Item', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html_x( 'Start', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-start-v',
					],
					'center'     => [
						'title' => esc_html_x( 'Center', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-center-v',
					],
					'flex-end'   => [
						'title' => esc_html_x( 'End', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-end-v',
					],
					'stretch'    => [
						'title' => esc_html__( 'Stretch', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-stretch-v',
					],
				],
				'default'   => '',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			's_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],

				'selectors' => [
					'{{WRAPPER}} .default-details-social-media' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'sflex_awraps',
			[
				'label'     => esc_html__( 'Wrap', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'nowrap' => [
						'title' => esc_html_x( 'No Wrap', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-nowrap',
					],
					'wrap'   => [
						'title' => esc_html_x( 'Wrap', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-wrap',
					],
				],
				'default'   => '',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media' => 'flex-wrap: {{VALUE}};',
				],
			]
		);


		$this->add_responsive_control(
			's-align-content',
			[
				'label'     => esc_html__( 'Align Content', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''              => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'center'        => esc_html__( 'Center', 'animation-addons-for-elementor' ),
					'flex-start'    => esc_html__( 'Start', 'animation-addons-for-elementor' ),
					'flex-end'      => esc_html__( 'End', 'animation-addons-for-elementor' ),
					'space-between' => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
					'space-around'  => esc_html__( 'Space Around', 'animation-addons-for-elementor' ),
					'space-evenly'  => esc_html__( 'Space Evenly', 'animation-addons-for-elementor' ),
				],
				'condition' => [
					'sflex_awraps' => 'wrap',
				],
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media' => 'align-content: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Item', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs(
			'style_n_tabs'
		);

		$this->start_controls_tab(
			'style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a .info-s-title' => 'color: {{VALUE}};',
					'{{WRAPPER}} .default-details-social-media a .aae-share-count' => 'color: {{VALUE}};',
				],
			]
		);


		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .default-details-social-media a .info-s-title, {{WRAPPER}} .default-details-social-media a .aae-share-count',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'text_stroke',
				'selector' => '{{WRAPPER}} .default-details-social-media a',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .default-details-social-media a',
			]
		);


		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'     => 'item_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .default-details-social-media a',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'itemborder',
				'selector' => '{{WRAPPER}} .default-details-social-media a',
			]
		);

		$this->add_control(
			'item_border_rad',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],

				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'item_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .default-details-social-media a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'item_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],

				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'item_height',
			[
				'label'      => esc_html__( 'height', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],

				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'laymore_options',
			[
				'label'     => esc_html__( 'Additional Layout Options', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'itemflex_direction',
			[
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'row'            => [
						'title' => esc_html__( 'Row - horizontal', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-' . $end,
					],
					'column'         => [
						'title' => esc_html__( 'Column - vertical', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
					'row-reverse'    => [
						'title' => esc_html__( 'Row - reversed', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-' . $start,
					],
					'column-reverse' => [
						'title' => esc_html__( 'Column - reversed', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-up',
					],
				],
				'default'   => 'row',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'flex-direction: {{VALUE}}; display:flex;',
				],
			]
		);

		$this->add_responsive_control(
			'item_justify_content',
			[
				'label'     => esc_html__( 'Justify Content', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start'    => [
						'title' => esc_html_x( 'Start', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-start-h',
					],
					'center'        => [
						'title' => esc_html_x( 'Center', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-center-h',
					],
					'flex-end'      => [
						'title' => esc_html_x( 'End', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-end-h',
					],
					'space-between' => [
						'title' => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-space-between-h',
					],
					'space-around'  => [
						'title' => esc_html__( 'Space Around', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-space-around-h',
					],
					'space-evenly'  => [
						'title' => esc_html__( 'Space Evenly', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-justify-space-evenly-h',
					],
				],
				'default'   => '',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'item_align_items',
			[
				'label'     => esc_html__( 'Align Item', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => esc_html_x( 'Start', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-start-v',
					],
					'center'     => [
						'title' => esc_html_x( 'Center', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-center-v',
					],
					'flex-end'   => [
						'title' => esc_html_x( 'End', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-end-v',
					],
					'stretch'    => [
						'title' => esc_html__( 'Stretch', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-stretch-v',
					],
				],
				'default'   => '',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'align-items: {{VALUE}};display:flex;',
				],
			]
		);

		$this->add_control(
			'item_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],

				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'gap: {{SIZE}}{{UNIT}};display:flex;',
				],
			]
		);

		$this->add_responsive_control(
			'itemflex_awraps',
			[
				'label'     => esc_html__( 'Wrap', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => [
					'nowrap' => [
						'title' => esc_html_x( 'No Wrap', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-nowrap',
					],
					'wrap'   => [
						'title' => esc_html_x( 'Wrap', 'Flex Container Control', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-wrap',
					],
				],
				'default'   => '',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'flex-wrap: {{VALUE}};',
				],
			]
		);


		$this->add_responsive_control(
			'item-align-content',
			[
				'label'     => esc_html__( 'Align Content', 'animation-addons-for-elementor' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''              => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'center'        => esc_html__( 'Center', 'animation-addons-for-elementor' ),
					'flex-start'    => esc_html__( 'Start', 'animation-addons-for-elementor' ),
					'flex-end'      => esc_html__( 'End', 'animation-addons-for-elementor' ),
					'space-between' => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
					'space-around'  => esc_html__( 'Space Around', 'animation-addons-for-elementor' ),
					'space-evenly'  => esc_html__( 'Space Evenly', 'animation-addons-for-elementor' ),
				],
				'condition' => [
					'sflex_awraps' => 'wrap',
				],
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a' => 'align-content: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'style_nol_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a:hover .info-s-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'thover_icon_color',
			[
				'label'     => esc_html__( 'Hover Icon Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a:hover i'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .default-details-social-media a:hover svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'     => 'item_h_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .default-details-social-media a:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_icon_layout',
			[
				'label'     => esc_html__( 'Icon Style', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'social_icon_style!' => '' ]
			]
		);


		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'     => 'icon_s_widborder',
				'selector' => '{{WRAPPER}} .wcf-social-icn',
			]
		);

		$this->add_control(
			'ucn_border_rad',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
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

				'selectors' => [
					'{{WRAPPER}} .wcf-social-icn' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_s_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],

				'selectors' => [
					'{{WRAPPER}} .wcf-social-icn' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_s_height',
			[
				'label'      => esc_html__( 'height', 'animation-addons-for-elementor' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],

				'selectors' => [
					'{{WRAPPER}} .wcf-social-icn' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name'     => 'icon_h_background',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf-social-icn',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_svg_style',
			[
				'label' => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		

		$this->add_responsive_control(
			'icon_svg_width',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .default-details-social-media a i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .default-details-social-media a svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'default'    => [
					'unit' => 'px',
					'size' => 16,
				],
			]
		);

		$this->add_control(
			'title_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a i'   => 'background: {{VALUE}};',
					'{{WRAPPER}} .default-details-social-media a svg' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_icon_color',
			[
				'label'     => esc_html__( 'Icon Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a i'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .default-details-social-media a svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'icon_border',
				'selector' => '{{WRAPPER}} .default-details-social-media a svg',
			]
		);

		$this->add_control(
			'icon_border_rad',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media a i'   => 'border-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .default-details-social-media a svg' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'item_icon_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .default-details-social-media a i' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .default-details-social-media a svg' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_icon_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .default-details-social-media a i'   => 'width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .default-details-social-media a svg' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);


		$this->add_responsive_control(
			'social_icon_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .default-details-social-media a i'   => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .default-details-social-media a svg' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'title_bg_hover_divider',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->add_control(
			'title_bg_color_hover',
			[
				'label'     => esc_html__( 'Hover BG Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media:hover a i'   => 'background: {{VALUE}};',
					'{{WRAPPER}} .default-details-social-media:hover a svg' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_icon_color_hover',
			[
				'label'     => esc_html__( 'Hover Icon Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media:hover a i'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .default-details-social-media:hover a svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_icon_border_hover',
			[
				'label'     => esc_html__( 'Hover Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-social-media:hover a i'   => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .default-details-social-media:hover a svg' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
		$this->start_controls_section(
			'section_seperator_style',
			[
				'label' => esc_html__( 'Separator', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
                        'share_separator' => ['yes']
                ]
			]
		);

		$this->add_responsive_control(
			'seperator_size_info',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 5,
					]
				],
				'selectors'  => [
					'{{WRAPPER}} span.wcf_separator_icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'default'    => [
					'unit' => 'px',
					'size' => 16,
				],
			]
		);

		$this->add_control(
			'seperator_icon_color',
			[
				'label' => esc_html__( 'Icon Color', 'animation-addons-for-elementor' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} span.wcf_separator_icon' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();
	}

	public function get_generated_link( $vendor ) {
		$url = '#';

		if ( $vendor == 'facebook' ) {
			$url = add_query_arg( array(
				'u' => get_the_permalink(),
			), 'https://www.facebook.com/sharer/sharer.php' );
		}

		if ( $vendor == 'twitter' ) {
			$url = add_query_arg( array(
				'url'  => get_the_permalink(),
				'text' => get_the_title()
			), 'https://twitter.com/intent/tweet' );
		}

		if ( $vendor == 'linkedin' ) {

			$url = add_query_arg( array(
				'url'     => get_the_permalink(),
				'mini'    => true,
				'title'   => get_the_title(),
				'summary' => get_the_title(),
				'source'  => get_the_permalink(),
			), 'https://www.linkedin.com/shareArticle' );
		}

		if ( $vendor == 'pinterest' ) {
			$url = add_query_arg( array(
				'media'       => get_the_post_thumbnail_url( get_the_ID(), 'full' ),
				'url'         => get_the_permalink(),
				'description' => get_the_title(),
			), 'https://pinterest.com/pin/create/button/' );
		}

		if ( $vendor == 'reddit' ) {
			$url = add_query_arg( array(
				'url'   => get_the_permalink(),
				'title' => get_the_title(),
			), 'https://www.reddit.com/submit' );
		}

		if ( $vendor == 'tumblr' ) {
			$url = add_query_arg( array(
				'url'         => get_the_permalink(),
				'name' => get_the_title(),
			), 'https://www.tumblr.com/share/link' );
		}

		if ( $vendor == 'blogger' ) {
			$url = add_query_arg( array(
				'u'         => get_the_permalink(),
				'n' => get_the_title(),
			), 'https://www.blogger.com/blog-this.g' );
		}

		return $url;
	}

	protected function render() {

		$settings = $this->get_settings_for_display();
		$socials  = $settings['list'];
		$current_shares = get_post_meta( get_the_id() , 'aae_post_shares', true );
		$total_share = get_post_meta( get_the_id() , 'aae_post_shares_count', true );
		
		?>
        <style>
            .default-details-social-media {
                display: flex;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .default-details-social-media svg {
                width: 1em;
                height: 1em;
            }
        </style>

		<?php
			if($settings['social_icon_style'] =='wcf-social-share-style-2'){ ?>

			<ul class="default-details-social-media">
			<?php foreach ( $socials as $share ) { ?>
                <li class="wcf-social-share-style-2">
                    <a data-type="<?php echo esc_attr($share['list_vendor']); ?>" href="<?php echo esc_url( $this->get_generated_link( $share['list_vendor'] ) ); ?>"
                    <span class="wcf-social-icn <?php echo esc_attr( $settings['social_icon_style'] ); ?>"><?php \Elementor\Icons_Manager::render_icon( $share['icon'], [
							'aria-hidden' => 'true',
							'class'       => 'share-ico'
						] ); ?></span>
                    <span class="info-s-title"> <?php echo esc_html( $share['list_title'] ); ?> </span>
					<?php if ( $settings['share_icon'] == 'yes' ) { ?>
                        <span>
                                <?php \Elementor\Icons_Manager::render_icon( $settings['share_icons'], [
	                                'aria-hidden' => 'true',
	                                'class'       => 'share-ico'
                                ] ); ?>
                                
                            </span>
					<?php } ?>
					<span class="wcf_separator_icon">
					<?php \Elementor\Icons_Manager::render_icon( $settings['share_separator_icons'], [
	                                'aria-hidden' => 'true',
	                                'class'       => 'share-ico'
                                ] ); ?>
					</span>
					<?php if(isset($settings['share_count']) && $settings['share_count'] === 'yes' ) { ?>
						<span data-type="<?php echo esc_attr($share['list_vendor']); ?>" class="aae-share-count <?php echo esc_attr($share['list_vendor']); ?>">
							<?php
							echo esc_html( 
								aaeaddon_format_number_count(is_array($current_shares) && isset($current_shares[$share['list_vendor']]) ? $current_shares[$share['list_vendor']] : 0) 
							);
							?>
						</span>
					<?php } ?>
					<?php echo esc_html( $settings['share_text'] ); ?>
                    </a>
                </li>
			<?php } ?>
        </ul>

		<?php }else{?>

        <ul class="default-details-social-media">
			<?php foreach ( $socials as $share ) { ?>
                <li>
                    <a data-type="<?php echo esc_attr($share['list_vendor']); ?>" href="<?php echo esc_url( $this->get_generated_link( $share['list_vendor'] ) ); ?>"
                    <span class="wcf-social-icn <?php echo esc_attr( $settings['social_icon_style'] ); ?>"><?php \Elementor\Icons_Manager::render_icon( $share['icon'], [
							'aria-hidden' => 'true',
							'class'       => 'share-ico'
						] ); ?></span>
                    <span class="info-s-title"> <?php echo esc_html( $share['list_title'] ); ?> </span>
					<?php if ( $settings['share_icon'] == 'yes' ) { ?>
                        <span>
                                <?php \Elementor\Icons_Manager::render_icon( $settings['share_icons'], [
	                                'aria-hidden' => 'true',
	                                'class'       => 'share-ico'
                                ] ); ?>
                                <?php echo esc_html( $settings['share_text'] ); ?>
                            </span>
					<?php } ?>
					<?php if(isset($settings['share_count']) && $settings['share_count'] === 'yes' ) { ?>
						<span data-type="<?php echo esc_attr($share['list_vendor']); ?>" class="aae-share-count <?php echo esc_attr($share['list_vendor']); ?>">
							<?php
							echo esc_html( 
								aaeaddon_format_number_count(is_array($current_shares) && isset($current_shares[$share['list_vendor']]) ? $current_shares[$share['list_vendor']] : 0) 
							);
							?>
						</span>
					<?php } ?>
                    </a>
                </li>
			<?php } ?>
        </ul>
		<?php } ?>

		<?php
	}
}
