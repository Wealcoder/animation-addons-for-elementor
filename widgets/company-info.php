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
class Company_Info extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'aae--company-info';
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
		return esc_html__( 'Company Profile', 'animation-addons-for-elementor' );
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
		return [ 'company-profile' ];
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
		$this->register_profile_content_controls();

		$this->register_website_content_controls();

		$this->start_controls_section(
			'style_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'layout_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .aae--company-profile',
			]
		);

		$this->add_responsive_control(
			'layout_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .aae--company-profile' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->register_gallery_controls();

		// Style
		$this->register_profile_logo_style();

		$this->register_profile_name_style();

		$this->register_profile_follow_style();

		$this->register_gallery_style();

		$this->register_website_style();
	}

	protected function register_profile_content_controls() {
		$this->start_controls_section(
			'section_profile_content',
			[
				'label' => esc_html__( 'Profile Content', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'profile_name',
			[
				'label'       => esc_html__( 'Profile Name', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'WealCoder', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your profile name here', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'badge_type',
			[
				'label'        => esc_html__( 'Badge Visibility', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'profile_badge',
			[
				'label'       => esc_html__( 'Profile Badge', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-circle',
					'library' => 'fa-solid',
				],
				'condition'   => [ 'badge_type' => 'yes' ],
			]
		);

		$this->add_control(
			'profile_link',
			[
				'label'       => esc_html__( 'Profile Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'options'     => [ 'url', 'is_external', 'nofollow' ],
				'default'     => [
					'url'         => '#',
					'is_external' => true,
					'nofollow'    => true,
				],
				'label_block' => true,
			]
		);

		$this->add_control(
			'follow_text',
			[
				'label'       => esc_html__( 'Follow Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Follow', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your button text here', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'following',
			[
				'label'       => esc_html__( 'Total Following', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '[1,081] Following', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your total following', 'animation-addons-for-elementor' ),
                'label_block' => true,
				'description' => 'For Highlight, keep text in [ ]. Ex. [ Text ]',
			]
		);

		$this->add_control(
			'follower',
			[
				'label'       => esc_html__( 'Total Followers', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__( '[32M] Followers', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your total followers here', 'animation-addons-for-elementor' ),
				'description' => 'For Highlight, keep text in [ ]. Ex. [ Text ]',
			]
		);

		$this->add_control(
			'profile_logo',
			[
				'label'   => esc_html__( 'Profile Logo', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_website_content_controls() {
		$this->start_controls_section(
			'section_web_content',
			[
				'label' => esc_html__( 'Website Content', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'website_name',
			[
				'label'       => esc_html__( 'Website Name', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'wealcoder.com', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your company website name here', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'website_icon',
			[
				'label'       => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-circle',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'website_link',
			[
				'label'       => esc_html__( 'Company Website Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'options'     => [ 'url', 'is_external', 'nofollow' ],
				'default'     => [
					'url'         => '#',
					'is_external' => true,
					'nofollow'    => true,
				],
				'label_block' => true,
			]
		);

		$this->add_control(
			'post_published',
			[
				'label'       => esc_html__( 'Total Posts Published', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '421.1K posts', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( '421.1K posts published', 'animation-addons-for-elementor' ),
				'label_block' => true,
			]
		);

		$this->end_controls_section();
	}

	protected function register_gallery_controls() {
		$this->start_controls_section(
			'section_gallery',
			[
				'label' => esc_html__( 'Social Posts', 'animation-addons-for-elementor' ),
			]
		);
		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'social_post_img',
			[
				'label'   => esc_html__( 'Social Post Image', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'social_post_link',
			[
				'label'       => esc_html__( 'Social Post Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'options'     => [ 'url', 'is_external', 'nofollow' ],
				'default'     => [
					'url'         => '#',
					'is_external' => true,
					'nofollow'    => true,
				],
				'label_block' => true,
			]
		);

		$this->add_control(
			'social_posts',
			[
				'label'   => esc_html__( 'Social Posts', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::REPEATER,
				'fields'  => $repeater->get_controls(),
				'default' => [ [], [] ],
			]
		);

		$this->end_controls_section();
	}

	protected function register_gallery_style() {
		$this->start_controls_section(
			'style_social_post',
			[
				'label' => esc_html__( 'Social Posts', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'gallery_cols',
			[
				'label'     => esc_html__( 'Columns', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '4',
				'options'   => [
					'1' => esc_html__( '1', 'animation-addons-for-elementor' ),
					'2' => esc_html__( '2', 'animation-addons-for-elementor' ),
					'3' => esc_html__( '3', 'animation-addons-for-elementor' ),
					'4' => esc_html__( '4', 'animation-addons-for-elementor' ),
					'5' => esc_html__( '5', 'animation-addons-for-elementor' ),
					'6' => esc_html__( '6', 'animation-addons-for-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .gallery' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				],
			]
		);

		$this->add_responsive_control(
			'g_col_gap',
			[
				'label'      => esc_html__( 'Column Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
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
					'{{WRAPPER}} .gallery' => 'column-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'g_row_gap',
			[
				'label'      => esc_html__( 'Row Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
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
					'{{WRAPPER}} .gallery' => 'row-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'g_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .gallery img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'g_height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .gallery img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'gp_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .gallery-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_profile_logo_style() {
		$this->start_controls_section(
			'style_profile_logo',
			[
				'label' => esc_html__( 'Profile Logo', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'profile_logo_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .logo',
			]
		);

		$this->add_responsive_control(
			'profile_logo_width',
			[
				'label'      => esc_html__( 'Logo Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .logo img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'profile_logo_circle',
			[
				'label'      => esc_html__( 'Circle Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .logo' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'pl_border',
				'selector' => '{{WRAPPER}} .logo',
			]
		);

		$this->add_responsive_control(
			'pl_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .logo' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pl_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .logo' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_profile_name_style() {
		$this->start_controls_section(
			'style_profile_name',
			[
				'label' => esc_html__( 'Profile Name', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'profile_name_color',
			[
				'label' => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .name' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'profile_name_typo',
				'selector' => '{{WRAPPER}} .name',
			]
		);

		$this->add_responsive_control(
			'profile_name_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .name' => 'gap: {{SIZE}}{{UNIT}};',
				],
                'separator' => 'after',
			]
		);

		$this->add_control(
			'profile_badge_color',
			[
				'label' => esc_html__( 'Badge Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .badge' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'profile_badge_size',
			[
				'label'      => esc_html__( 'Badge Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .badge' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'profile_name_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .name' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function register_profile_follow_style() {
		$this->start_controls_section(
			'style_profile_follow',
			[
				'label' => esc_html__( 'Profile Follow', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'follow_color',
			[
				'label' => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .follow-wrap p' => 'color: {{VALUE}}; ',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'follow_typo',
				'selector' => '{{WRAPPER}} .follow-wrap p',
			]
		);

		$this->add_responsive_control(
			'profile_follow_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .follow-wrap' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'after',
			]
		);

		$this->add_control(
			'follow_hl_color',
			[
				'label' => esc_html__( 'Highlight Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .follow-wrap .highlight' => 'color: {{VALUE}}; ',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'follow_hl_typo',
				'selector' => '{{WRAPPER}} .follow-wrap .highlight  ',
			]
		);

		$this->add_control(
			'follow_btn_heading',
			[
				'label' => esc_html__( 'Follow Button', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'follow_btn_color',
			[
				'label' => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .follow-btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'follow_btn_h_color',
			[
				'label' => esc_html__( 'Hover Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .follow-btn:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'follow_btn_typo',
				'selector' => '{{WRAPPER}} .follow-btn',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'f_btn_border',
				'selector' => '{{WRAPPER}} .follow-btn',
			]
		);

		$this->add_responsive_control(
			'follow_btn_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .site-link' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'after',
			]
		);

		$this->add_responsive_control(
			'f_btn_padding',
			[
				'label' => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .follow-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_website_style() {
		$this->start_controls_section(
			'style_website_content',
			[
				'label' => esc_html__( 'Profile Website', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'website_color',
			[
				'label' => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .website-btn' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'website_h_color',
			[
				'label' => esc_html__( 'Hover Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .website-btn:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'website_typo',
				'selector' => '{{WRAPPER}} .website-btn',
			]
		);

		$this->add_responsive_control(
			'website_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .website-btn' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'after',
			]
		);

		$this->add_control(
			'website_icon_color',
			[
				'label' => esc_html__( 'Icon Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'website_icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'web_post_color',
			[
				'label' => esc_html__( 'Post Text Color', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .total-posts' => 'color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'web_text_typo',
				'selector' => '{{WRAPPER}} .total-posts',
			]
		);

		$this->add_responsive_control(
			'website_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .site-link' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
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

		if ( ! empty( $settings['profile_link']['url'] ) ) {
			$this->add_link_attributes( 'profile_link', $settings['profile_link'] );
		}

		if ( ! empty( $settings['website_link']['url'] ) ) {
			$this->add_link_attributes( 'website_link', $settings['website_link'] );
		}

		$following = $settings['following'];
		preg_match_all( '/\[([^\]]*)\]/', $following, $matches );
		foreach ( $matches[0] as $key => $value ) {
			$following = str_replace( $value, '<span class="highlight">' . $matches[1][ $key ] . '</span>', $following, );
		}

		$follower = $settings['follower'];
		preg_match_all( '/\[([^\]]*)\]/', $follower, $matches );
		foreach ( $matches[0] as $key => $value ) {
			$follower = str_replace( $value, '<span class="highlight">' . $matches[1][ $key ] . '</span>', $follower );
		}

		?>
        <div class="aae--company-profile">
            <h2 class="name">
				<?php echo esc_html( $settings['profile_name'] ); ?>
                <span class="badge"><?php Icons_Manager::render_icon( $settings['profile_badge'], [ 'aria-hidden' => 'true' ] ); ?></span>
            </h2>
            <div class="total-posts"><?php echo esc_html( $settings['post_published'] ); ?></div>
            <div class="gallery-wrap">
                <div class="gallery">
					<?php
					if ( $settings['social_posts'] ) {
						foreach ( $settings['social_posts'] as $index => $item ) {
							$post_id = 'post_' . $index;
							$this->add_link_attributes( $post_id, $item['social_post_link'] );
							?>
                            <a <?php $this->print_render_attribute_string( $post_id ); ?> class="item">
                                <img src="<?php echo esc_url( $item['social_post_img']['url'] ); ?>"
                                     alt="<?php echo esc_attr( 'Post Image', 'animation-addons-for-elementor' ); ?>">
                            </a>
							<?php
						}
					}
					?>
                </div>
                <div class="logo">
                    <a <?php $this->print_render_attribute_string( 'profile_link' ); ?>>
                        <img src="<?php echo esc_url( $settings['profile_logo']['url'] ); ?>"
                             alt="<?php echo esc_html( $settings['profile_name'] ); ?>">
                    </a>
                </div>
            </div>
            <div class="site-link">
                <a class="website-btn" <?php $this->print_render_attribute_string( 'website_link' ); ?>>
                    <span class="icon"><?php Icons_Manager::render_icon( $settings['website_icon'], [ 'aria-hidden' => 'true' ] ); ?></span>
                    <?php echo esc_html( $settings['website_name'] ); ?>
                </a>
                <a class="follow-btn" <?php $this->print_render_attribute_string( 'profile_link' ); ?>><?php echo esc_html( $settings['follow_text'] ); ?></a>
            </div>
            <div class="follow-wrap">
                <p><?php echo wp_kses_post( $following ); ?></p>
                <p><?php echo wp_kses_post( $follower ); ?></p>
            </div>
        </div>
		<?php
	}
}
