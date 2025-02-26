<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Plugin;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Post_Comment extends Widget_Base {

	public function get_name() {
		return 'wcf--blog--post--comment';
	}

	public function get_title() {
		return esc_html__( 'Post Comment', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-comments';
	}

	public function get_categories() {
		return [ 'wcf-single-addon' ];
	}

	public function get_keywords() {
		return [ 'comment', 'post comment' ];
	}

	public function get_style_depends() {
		return [ 'wcf--button' ];
	}

	protected function register_controls() {

		$author = [ 'Crowdyflow', 'CrowdyTheme' ];
		if ( ! in_array( wp_get_theme()->get( 'Author' ), $author ) ) {
			return;
		}

		$this->start_controls_section(
			'element_ready_form_con_section',
			[
				'label' => esc_html__( 'Container', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'background_box',
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .joya--blog-post-comment',
			]
		);

		$this->add_responsive_control(
			'comment__padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'comment_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'element_ready_comment_area_section',
			[
				'label' => esc_html__( 'Comment Area', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'comment_ct_color',
			[
				'label'     => esc_html__( 'Comment Count', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--comment .comment-num' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'comment_count_typography',
				'selector' => '{{WRAPPER}} .joya--comment .comment-num',
			]
		);

		$this->add_responsive_control(
			'comment_counter_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--comment .comment-num' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Author Name
		$this->add_control(
			'author_ct_color',
			[
				'label'     => esc_html__( 'Author Name', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-comment-name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'cathor_count_typography',
				'selector' => '{{WRAPPER}} .default-details-comment-name',
			]
		);

		$this->add_responsive_control(
			'cathor_count_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .default-details-comment-name' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Date Name
		$this->add_control(
			'meta_ct_color',
			[
				'label'     => esc_html__( 'Date Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .default-details-comment-date'      => 'color: {{VALUE}};',
					'{{WRAPPER}} .default-details-comment-date span' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'cmeta_count_typography',
				'selector' => '{{WRAPPER}} .default-details-comment-date',
				'label'    => esc_html__( 'Date', 'animation-addons-for-elementor' ),
			]
		);


		$this->add_responsive_control(
			'cathor_count_date_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .default-details-comment-date' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		// Comment TExt
		$this->add_control(
			'c_text_color',
			[
				'label'     => esc_html__( 'Comment Text', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .axtra-comment-text p'      => 'color: {{VALUE}};',
					'{{WRAPPER}} .logged-in-as'              => 'color: {{VALUE}};',
					'{{WRAPPER}} .comment-reply-link'        => 'color: {{VALUE}};',
					'{{WRAPPER}} .comment-notes'             => 'color: {{VALUE}};',
					'{{WRAPPER}} .newsprint-comment-text p'  => 'color: {{VALUE}};',
				],
			]
		);
		
		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'cmeta_ytext_typography',
				'label'    => esc_html__( 'Comment Text', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .axtra-comment-text p,{{WRAPPER}} .logged-in-as, {{WRAPPER}} .newsprint-comment-text p',
			]
		);

		$this->add_responsive_control(
			'cathor_body_text_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .newsprint-comment-text p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// link

		$this->add_control(
			'c_linkt_color',
			[
				'label'     => esc_html__( 'Reply Link', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--comment .comment-reply-link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'cmeta_linkt_typography',
				'label'    => esc_html__( 'Typography', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .joya--comment .comment-reply-link',
			]
		);

		$this->add_control(
			'c_replbtn_color',
			[
				'label'     => esc_html__( 'Reply Icon Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .comment-reply-link' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'element_ready_form_avatar_section',
			[
				'label' => esc_html__( 'Avator Images', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'avator_height',
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
					'{{WRAPPER}} .default-details-comment-thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'avator_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
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
					'{{WRAPPER}} .default-details-comment-thumb img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'border',
				'selector' => '{{WRAPPER}} .default-details-comment-thumb img',
			]
		);

		$this->add_responsive_control(
			'avator_image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .default-details-comment-thumb img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'element_ready_form_general_section',
			[
				'label' => esc_html__( 'Comment Form', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'aaebackground',
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .joya--comment .comment-respond',
			]
		);

		$this->add_responsive_control(
			'comment_form_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--comment .comment-respond' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'comment_form_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--comment .comment-respond' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'element_ready_form_heading_section',
			[
				'label' => esc_html__( 'Comment Box Title', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'c_repluiuy_color',
			[
				'label'     => esc_html__( 'Reply Heading', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--comment #reply-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'cmetaiui_reply_typography',
				'label'    => esc_html__( 'Reply Heading', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .joya--comment #reply-title',
			]
		);

		$this->end_controls_section();


		$this->start_controls_section(
			'element_ready_note_section',
			[
				'label' => esc_html__( 'Comment Notes', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'c_note_color',
			[
				'label'     => esc_html__( 'Note Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--comment .comment-notes' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'cmetaiui_note_typography',
				'label'    => esc_html__( 'Note Typography', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .joya--comment .comment-notes',
			]
		);

		$this->add_responsive_control(
			'comment_note_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--comment .comment-notes' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'comment_note_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--comment .comment-notes' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'comment_note_gap',
			[
				'label'          => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
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
					'{{WRAPPER}} img' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		

		/*----------------------------
		LABEL STYLE
	------------------------------*/
		$this->start_controls_section(
			'element_ready_form_label_style_section',
			[
				'label' => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'label_show',
			[
				'label' => esc_html__( 'Show/Hide', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'none' => esc_html__( 'None', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .comment-form label' => 'display: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .joya--blog-post-comment label',
			]
		);
		$this->add_control(
			'label_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment label' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'label_background',
				'label'    => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .joya--blog-post-comment label',
			]
		);
		$this->add_responsive_control(
			'label_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
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
				'default'    => [
					'unit' => '%',
					'size' => 100
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment label' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border:: get_type(),
			[
				'name'     => 'label_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .joya--blog-post-comment label',
			]
		);
		$this->add_responsive_control(
			'label_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment label' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'label_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'label_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->end_controls_section();
		/*---------------------------
			LABEL STYLE END
		-----------------------------*/

		/*---------------------------
			INPUT STYLE START
		----------------------------*/
		$this->start_controls_section(
			'element_ready_form_input_style_section',
			[
				'label' => esc_html__( 'Input', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->start_controls_tabs( 'input_box_tabs' );
		$this->start_controls_tab(
			'input_box_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);
		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'input_box_typography',
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment input[type*="text"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="email"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="url"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="number"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="tel"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="date"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="file"]
                            ',
			]
		);
		$this->add_control(
			'input_box_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]'   => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]'  => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]'    => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]' => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]'    => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]'   => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]'   => 'color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'input_box_background',
				'label'    => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment input[type*="text"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="email"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="url"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="number"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="tel"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="date"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="file"]
                            ',
			]
		);
		$this->add_control(
			'input_box_placeholder_color',
			[
				'label'     => esc_html__( 'Placeholder Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]::-webkit-input-placeholder'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]::-moz-placeholder'            => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]:-ms-input-placeholder'        => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]::-webkit-input-placeholder'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]::-moz-placeholder'           => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]:-ms-input-placeholder'       => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]::-webkit-input-placeholder'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]::-moz-placeholder'             => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]:-ms-input-placeholder'         => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]::-webkit-input-placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]::-moz-placeholder'          => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]:-ms-input-placeholder'      => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]::-webkit-input-placeholder'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]::-moz-placeholder'             => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]:-ms-input-placeholder'         => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]::-webkit-input-placeholder'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]::-moz-placeholder'            => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]:-ms-input-placeholder'        => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]'                              => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'input_box_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'max' => 150,
					],
				],
				'default'    => [
					'size' => 55,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]'   => 'height:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]'  => 'height:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]'    => 'height:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]' => 'height:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]'    => 'height:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]'   => 'height:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]'   => 'height:{{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'input_box_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
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
				'default'    => [
					'unit' => '%',
					'size' => 100,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]'   => 'width:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]'  => 'width:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]'    => 'width:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]' => 'width:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]'    => 'width:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]'   => 'width:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]'   => 'width:{{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border:: get_type(),
			[
				'name'      => 'input_box_border',
				'label'     => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector'  => '
                                {{WRAPPER}} .joya--blog-post-comment input[type*="text"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="email"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="url"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="number"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="tel"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="date"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="file"]
                            ',
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'input_box_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]'   => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]'  => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]'    => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]'    => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]'   => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]'   => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'input_box_shadow',
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment input[type*="text"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="email"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="url"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="number"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="tel"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="date"],
                                {{WRAPPER}} .joya--blog-post-comment input[type*="file"]
                            ',
			]
		);
		$this->add_responsive_control(
			'input_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]'  => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]'    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]'    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'input_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]'  => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]'    => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]'    => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_control(
			'input_box_transition',
			[
				'label'      => esc_html__( 'Transition', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0.1,
						'max'  => 3,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 0.3,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]'   => 'transition: {{SIZE}}s;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]'  => 'transition: {{SIZE}}s;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]'    => 'transition: {{SIZE}}s;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]' => 'transition: {{SIZE}}s;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]'    => 'transition: {{SIZE}}s;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]'   => 'transition: {{SIZE}}s;',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]'   => 'transition: {{SIZE}}s;',
				],
			]
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'input_box_hover_tabs',
			[
				'label' => esc_html__( 'Focus', 'animation-addons-for-elementor' ),
			]
		);
		$this->add_control(
			'input_box_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]:focus'   => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]:focus'  => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]:focus'    => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]:focus' => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]:focus'    => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]:focus'   => 'color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]:focus'   => 'color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'input_box_hover_backkground',
				'label'    => esc_html__( 'Focus Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '
                            {{WRAPPER}} .joya--blog-post-comment input[type*="text"]  : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="email"] : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="url"]   : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="number"]: focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="tel"]   : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="date"]  : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="file"]  : focus
                        ',
			]
		);
		$this->add_control(
			'input_box_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="text"]:focus'   => 'border-color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="email"]:focus'  => 'border-color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="url"]:focus'    => 'border-color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="number"]:focus' => 'border-color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="tel"]:focus'    => 'border-color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="date"]:focus'   => 'border-color:{{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment input[type*="file"]:focus'   => 'border-color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'input_box_hover_shadow',
				'selector' => '
                            {{WRAPPER}} .joya--blog-post-comment input[type*="text"]  : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="email"] : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="url"]   : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="number"]: focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="tel"]   : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="date"]  : focus,
                            {{WRAPPER}} .joya--blog-post-comment input[type*="file"]  : focus
                        ',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*-----------------------------
			INPUT STYLE END
		-------------------------------*/

		/*---------------------------
			INPUT CHECKBOX / RADIO STYLE
		----------------------------*/
		$this->start_controls_section(
			'element_ready_form_input_readio_style_section',
			[
				'label' => esc_html__( 'Input Radio / Checkbox', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->start_controls_tabs( 'input_radio_checkbox_tabs' );
		$this->start_controls_tab(
			'input_radio_checkbox_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_responsive_control(
			'input_radio_checkbox__display',
			[
				'label'   => esc_html__( 'Display', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'inline-block',

				'options'   => [
					'initial'      => esc_html__( 'Initial', 'animation-addons-for-elementor' ),
					'block'        => esc_html__( 'Block', 'animation-addons-for-elementor' ),
					'inline-block' => esc_html__( 'Inline Block', 'animation-addons-for-elementor' ),
					'flex'         => esc_html__( 'Flex', 'animation-addons-for-elementor' ),
					'inline-flex'  => esc_html__( 'Inline Flex', 'animation-addons-for-elementor' ),
					'none'         => esc_html__( 'none', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item' => 'display: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'input_radio_checkbox_typography',
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item,
                                {{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item label
                            ',
			]
		);
		$this->add_control(
			'input_radio_checkbox_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item' => 'color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'input_radio_checkbox_background',
				'label'    => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item
                            ',
			]
		);
		$this->add_responsive_control(
			'input_radio_checkbox_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'max' => 150,
					],
				],
				'default'    => [
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item' => 'height:{{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'input_radio_checkbox_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
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
				'default'    => [
					'unit' => '%',
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item' => 'width:{{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border:: get_type(),
			[
				'name'      => 'input_radio_checkbox_border',
				'label'     => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector'  => '
                                {{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item
                            ',
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'input_radio_checkbox_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'input_radio_checkbox_shadow',
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item
                            ',
			]
		);
		$this->add_responsive_control(
			'input_radio_checkbox_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'input_radio_checkbox_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_control(
			'input_radio_checkbox_transition',
			[
				'label'      => esc_html__( 'Transition', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0.1,
						'max'  => 3,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 0.3,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item' => 'transition: {{SIZE}}s;',
				],
			]
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'input_radio_checkbox_hover_tabs',
			[
				'label' => esc_html__( 'Focus', 'animation-addons-for-elementor' ),
			]
		);
		$this->add_control(
			'input_radio_checkbox_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item:focus' => 'color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'input_radio_checkbox_hover_backkground',
				'label'    => esc_html__( 'Focus Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '
                            {{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item: focus
                        ',
			]
		);
		$this->add_control(
			'input_radio_checkbox_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item:focus' => 'border-color:{{VALUE}};'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'input_radio_checkbox_hover_shadow',
				'selector' => '
                            {{WRAPPER}} .joya--blog-post-comment span.wpcf7-list-item: focus
                        ',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*-----------------------------
			INPUT CHECKBOX / RADIO STYLE  END
		-------------------------------*/

		/*---------------------------
			SELECT BOX STYLE START
		----------------------------*/
		$this->start_controls_section(
			'element_ready_form_select_style_section',
			[
				'label' => esc_html__( 'Select', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->start_controls_tabs( 'select_box_tabs' );
		$this->start_controls_tab(
			'select_box_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);
		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'select_box_typography',
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment select
                            ',
			]
		);
		$this->add_control(
			'select_box_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment select' => 'color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'select_box_background',
				'label'    => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment select
                            ',
			]
		);
		$this->add_control(
			'select_box_placeholder_color',
			[
				'label'     => esc_html__( 'Placeholder Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment select' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'select_box_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'max' => 150,
					],
				],
				'default'    => [
					'size' => 55,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment select' => 'height:{{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'select_box_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
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
				'default'    => [
					'unit' => '%',
					'size' => 100,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment select' => 'width:{{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border:: get_type(),
			[
				'name'      => 'select_box_border',
				'label'     => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector'  => '
                                {{WRAPPER}} .joya--blog-post-comment select
                            ',
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'select_box_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment select' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'select_box_shadow',
				'selector' => '
                                {{WRAPPER}} .joya--blog-post-comment select
                            ',
			]
		);
		$this->add_responsive_control(
			'select_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'select_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment select' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_control(
			'select_box_transition',
			[
				'label'      => esc_html__( 'Transition', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0.1,
						'max'  => 3,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 0.3,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment select' => 'transition: {{SIZE}}s;',
				],
			]
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'select_box_hover_tabs',
			[
				'label' => esc_html__( 'Focus', 'animation-addons-for-elementor' ),
			]
		);
		$this->add_control(
			'select_box_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment select:focus' => 'color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'select_box_hover_backkground',
				'label'    => esc_html__( 'Focus Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '
                            {{WRAPPER}} .joya--blog-post-comment select: focus
                        ',
			]
		);
		$this->add_control(
			'select_box_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment select:focus' => 'border-color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'select_box_hover_shadow',
				'selector' => '
                            {{WRAPPER}} .joya--blog-post-comment select: focus
                        ',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*-----------------------------
			SELECT BOX STYLE END
		-------------------------------*/

		/*-----------------------------
			TEXTAREA STYLE
		-----------------------------*/
		$this->start_controls_section(
			'element_ready_form_textarea_style_section',
			[
				'label' => esc_html__( 'Textarea', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->start_controls_tabs( 'textarea_box_tabs' );
		$this->start_controls_tab(
			'textarea_box_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);
		$this->add_responsive_control(
			'textarea_box_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max' => 500,
					],
				],
				'default'    => [
					'size' => 150,
				],

				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment textarea' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'textarea_expand_height',
			[
				'label'      => esc_html__( 'Expanded Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max' => 500,
					],
				],
				'default'    => [
					'size' => 150,
				],
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment textarea:focus' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'textarea_box_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'max' => 500,
					],
					'%'  => [
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment textarea' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'textarea_box_typography',
				'selector' => '{{WRAPPER}} .joya--blog-post-comment textarea',
			]
		);
		$this->add_control(
			'textarea_box_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment textarea' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'textarea_box_placeholder_color',
			[
				'label'     => esc_html__( 'Placeholder Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment textarea::-webkit-input-placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment textarea::-moz-placeholder'          => 'color: {{VALUE}};',
					'{{WRAPPER}} .joya--blog-post-comment textarea:-ms-input-placeholder'      => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'textarea_box_background',
				'label'    => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .joya--blog-post-comment textarea',
			]
		);
		$this->add_group_control(
			Group_Control_Border:: get_type(),
			[
				'name'     => 'textarea_box_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} .joya--blog-post-comment textarea',
			]
		);
		$this->add_responsive_control(
			'textarea_box_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment textarea' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'textarea_box_shadow',
				'selector' => '{{WRAPPER}} .joya--blog-post-comment textarea',
			]
		);
		$this->add_responsive_control(
			'textarea_box_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'textarea_box_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment textarea' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_control(
			'textarea_box_transition',
			[
				'label'      => esc_html__( 'Transition', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0.1,
						'max'  => 3,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 0.3,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment textarea' => 'transition: {{SIZE}}s;',
				],
			]
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'textarea_box_hover_tabs',
			[
				'label' => esc_html__( 'Focus', 'animation-addons-for-elementor' ),
			]
		);
		$this->add_control(
			'textarea_box_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment textarea:focus' => 'color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'textarea_box_hover_backkground',
				'label'    => esc_html__( 'Focus Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .joya--blog-post-comment textarea:focus',
			]
		);
		$this->add_control(
			'textarea_box_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment textarea:focus' => 'border-color:{{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'textarea_box_hover_shadow',
				'selector' => '{{WRAPPER}} .joya--blog-post-comment textarea:focus',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*----------------------------
			TEXTAREA STYLE END
		-----------------------------*/

		/*----------------------------
			BUTTONS TYLE
		------------------------------*/
		$this->start_controls_section(
			'element_ready_input_submit_style_section',
			[
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->start_controls_tabs( 'submit_style_tabs' );
		$this->start_controls_tab(
			'submit_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_group_control(
			Group_Control_Typography:: get_type(),
			[
				'name'     => 'input_submit_typography',
				'selector' => '{{WRAPPER}} .joya--blog-post-comment input[type*="submit"],{{WRAPPER}} .joya--blog-post-comment button[type="submit"]',
			]
		);

		$this->add_control(
			'input_submit_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="submit"],{{WRAPPER}} .joya--blog-post-comment button[type="submit"]' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'input_submit_background_color',
				'label'    => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .joya--blog-post-comment input[type*="submit"],{{WRAPPER}} .joya--blog-post-comment button[type="submit"]',
			]
		);

		$this->add_responsive_control(
			'input_submit_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
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
				'default'    => [
					'unit' => 'px',
					'size' => 200,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="submit"],{{WRAPPER}} .joya--blog-post-comment button[type="submit"]' => 'width:{{SIZE}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'input_submit_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'max' => 150,
					],
				],
				'default'    => [
					'size' => 55,
				],
				'selectors'  => [
					'{{WRAPPER}} .joya--blog-post-comment input[type*="submit"],{{WRAPPER}} .joya--blog-post-comment button[type="submit"]' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border:: get_type(),
			[
				'name'      => 'input_submit_border',
				'label'     => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector'  => '{{WRAPPER}} .joya--blog-post-comment input[type*="submit"],{{WRAPPER}} .joya--blog-post-comment button[type="submit"]',
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'input_submit_border_radius',
			[
				'label'     => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} button[type="submit"]' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
				'separator' => 'before',
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'input_submit_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} button[type="submit"]',
			]
		);
		$this->add_responsive_control(
			'input_submit_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} button[type="submit"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);
		$this->add_responsive_control(
			'input_submit_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} button[type="submit"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .default-details__cmtbtn' => 'margin: 0;',
				],
				'separator'  => 'before',
			]
		);
		$this->add_control(
			'input_submit_transition',
			[
				'label'      => esc_html__( 'Transition', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0.1,
						'max'  => 3,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 0.3,
				],
				'selectors'  => [
					'{{WRAPPER}} button[type="submit"]' => 'transition: {{SIZE}}s;',
				],
			]
		);
		$this->add_responsive_control(
			'input_submit_floting',
			[
				'label'     => esc_html__( 'Button Floating', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'float:left'                                       => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'display:block;margin-left:auto;margin-right:auto' => [
						'title' => esc_html__( 'None', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-top',
					],
					'float:right'                                      => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} button[type="submit"]' => '{{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'input_submit_text_align',
			[
				'label'     => esc_html__( 'Text Align', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-start-h',
					],
					'center' => [
						'title' => esc_html__( 'None', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-center-h',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-end-h',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .default-details__cmtbtn' => 'text-align: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_tab();
		$this->start_controls_tab(
			'submit_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);
		$this->add_control(
			'input_submithover_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} button[type="submit"]:hover' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Background:: get_type(),
			[
				'name'     => 'input_submithover_background_color',
				'label'    => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} button[type="submit"]:hover',
			]
		);
		$this->add_group_control(
			Group_Control_Border:: get_type(),
			[
				'name'     => 'input_submithover_border',
				'label'    => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} button[type="submit"]:hover',
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow:: get_type(),
			[
				'name'     => 'input_submithover_shadow',
				'label'    => esc_html__( 'Box Shadow', 'animation-addons-for-elementor' ),
				'selector' => '{{WRAPPER}} button[type="submit"]:hover',
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		/*----------------------------
			BUTTONS TYLE END
		------------------------------*/
	}

	protected function switch_post() {
		if ( 'wcf-addons-template' === get_post_type() ) {

			$recent_posts = wp_get_recent_posts( array(
				'numberposts' => 1,
				'post_status' => 'publish'
			) );

			$post_id = get_the_id();

			if ( isset( $recent_posts[0] ) ) {
				$post_id = $recent_posts[0]['ID'];
			}

			Plugin::$instance->db->switch_to_post( $post_id );
		}
	}

	protected function render() {

		$this->switch_post();

		if ( ! comments_open() && ( Plugin::$instance->preview->is_preview_mode() || Plugin::$instance->editor->is_edit_mode() ) ) :
			?>
            <div class="elementor-alert elementor-alert-danger" role="alert">
				<span class="elementor-alert-title">
					<?php esc_html_e( 'Comments are closed.', 'animation-addons-for-elementor' ); ?>
				</span>
                <span class="elementor-alert-description">
					<?php esc_html_e( 'Switch on comments from either the discussion box on the WordPress post edit screen or from the WordPress discussion settings.', 'animation-addons-for-elementor' ); ?>
				</span>
            </div>
		<?php
		else :
			comments_template();
		endif;

		Plugin::$instance->db->restore_current_post();
	}
}
