<?php

namespace WCF_ADDONS;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Plugin as ElementorPlugin;
use Elementor\Repeater;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Shows pro controls in the free plugin editor as visual placeholders.
 * None of the controls have `frontend_available => true` or functional JS,
 * so they render in the panel but do nothing.
 */
class WCFAddon_BlackList_Notice {

	const TD = 'animation-addons-for-elementor';

	public static function __( $text ) {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		return __( $text, 'animation-addons-for-elementor' );
	}

	private static function pro_notice( $element, $id ) {
		$element->add_control( $id, [
			'label'           => self::__( 'Pro Note'),
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => sprintf(
				/* translators: %1$s: opening <a> tag, %2$s: closing </a> tag */
				self::__( 'These settings are available in the Pro version. %1$sUpgrade to Animation Addons Pro%2$s to unlock all extensions and advanced features.'),
				'<a href="' . esc_url( 'https://animation-addons.com/pricing/' ) . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
		] );
	}

	public static function init() {

		add_action( 'elementor/element/common/_section_style/after_section_end', [ __CLASS__, 'tooltip_controls_section' ], -1 );
		add_action( 'elementor/element/container/section_layout/after_section_end', [ __CLASS__, 'tooltip_controls_section' ], -1 );

		add_action( 'elementor/element/container/section_layout/after_section_end', [ __CLASS__, 'register_cursor_hover_effect_controls' ] );
		add_action( 'elementor/element/wcf--a-portfolio/section_layout/after_section_end', [ __CLASS__, 'register_cursor_hover_effect_controls' ] );

		$image_elements = [
			[ 'name' => 'image', 'section' => 'section_image' ],
			[ 'name' => 'wcf--image', 'section' => 'section_content' ],
		];
		foreach ( $image_elements as $element ) {
			add_action(
				'elementor/element/' . $element['name'] . '/' . $element['section'] . '/after_section_end',
				[ __CLASS__, 'register_image_animation_controls' ],
				10,
				2
			);
		}

		$text_elements = [
			[ 'name' => 'heading', 'section' => 'section_title' ],
			[ 'name' => 'text-editor', 'section' => 'section_editor' ],
			[ 'name' => 'wcf--title', 'section' => 'section_content' ],
			[ 'name' => 'wcf--text', 'section' => 'section_content' ],
		];
		foreach ( $text_elements as $element ) {
			add_action(
				'elementor/element/' . $element['name'] . '/' . $element['section'] . '/after_section_end',
				[ __CLASS__, 'register_text_animation_controls' ],
				10,
				2
			);
		}
	}

	private static function pro_label( $text ) {
		return sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text aae-icon-lock"><span>', $text );
	}

	/* =====================================================================
	 * TEXT ANIMATION
	 * =================================================================== */
	public static function register_text_animation_controls( $element ) {
		$element->start_controls_section(
			'_section_wcf_text_animation',
			[ 'label' => self::pro_label( self::__( 'Text Animation') ) ]
		);

		self::pro_notice( $element, 'pro_notice_text_animation' );

		$animation = [
			'none'        => self::__( 'none'),
			'char'        => self::__( 'Character'),
			'word'        => self::__( 'Word'),
			'text_move'   => self::__( 'Text Move'),
			'text_reveal' => self::__( 'Text Reveal'),
			'text_scale'  => self::__( 'Text Scale'),
		];
		if ( in_array( $element->get_name(), [ 'heading', 'wcf--title' ], true ) ) {
			$animation['text_invert'] = self::__( 'Text Invert');
			$animation['text_spin']   = self::__( '3D Spin');
		}

		$animated_list = [ 'char', 'word', 'text_reveal', 'text_move', 'text_spin', 'text_scale' ];

		$element->add_responsive_control( 'wcf_text_animation', [
			'label'       => self::__( 'Animation'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => $animation,
			'render_type' => 'template',
		] );

		$element->add_responsive_control( 'aae_text_trigger', [
			'label'       => self::__( 'Trigger'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'on_scroll',
			'render_type' => 'none',
			'options'     => [
				'on_scroll'        => self::__( 'On Scroll'),
				'on_page_load'     => self::__( 'On Page Load'),
				'play_with_scroll' => self::__( 'Play With Scroll'),
				'mouseover'        => self::__( 'On Hover'),
				'click'            => self::__( 'On Click'),
			],
			'condition'   => [ 'wcf_text_animation' => $animated_list ],
		] );

		$element->add_responsive_control( 'aae_trigger_text_selector', [
			'label'       => self::__( 'Trigger Selector'),
			'description' => self::__( 'Selector for trigger element. Example: .my-class, #my-id'),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.my-class',
			'render_type' => 'none',
			'condition'   => [
				'wcf_text_animation' => $animated_list,
				'aae_text_trigger'   => [ 'mouseover', 'click' ],
			],
		] );

		$element->add_responsive_control( 'aae_anim_txt_wrapper', [
			'label'       => self::__( 'Text Wrapper'),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => self::__( 'Default'),
				'custom' => self::__( 'Custom'),
			],
			'condition'   => [
				'aae_text_trigger'   => [ 'on_scroll', 'play_with_scroll' ],
				'wcf_text_animation' => $animated_list,
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_delay', [
			'label'       => self::__( 'Delay'),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 0.15,
			'condition'   => [ 'wcf_text_animation' => $animated_list ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_duration', [
			'label'       => self::__( 'Duration'),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 1,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word', 'text_reveal', 'text_move', 'text_scale' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_stagger', [
			'label'       => self::__( 'Stagger'),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.01,
			'default'     => 0.02,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word', 'text_reveal', 'text_move', 'text_scale' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_translate_x', [
			'label'       => self::__( 'Transform-X'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 20,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_translate_y', [
			'label'       => self::__( 'Transform-Y'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_rotation_di', [
			'label'       => self::__( 'Rotation Direction'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'x',
			'separator'   => 'before',
			'options'     => [ 'x' => 'X', 'y' => 'Y' ],
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_rotation', [
			'label'       => self::__( 'Rotation Value'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => -80,
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_transform_origin', [
			'label'       => self::__( 'transformOrigin'),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'top center -50',
			'placeholder' => 'top center',
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_text_animation_editor', [
			'label'        => self::__( 'Enable On Editor'),
			'description'  => self::__( 'For better performance in editor mode, keep the setting turned off.'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_text_animation!' => 'none' ],
		] );

		$element->end_controls_section();
	}

	/* =====================================================================
	 * IMAGE ANIMATION
	 * =================================================================== */
	public static function register_image_animation_controls( $element ) {

		$element->start_controls_section(
			'_section_wcf_image_animation',
			[ 'label' => self::pro_label( self::__( 'Image Animation') ) ]
		);

		self::pro_notice( $element, 'pro_notice_image_animation' );

		$element->add_responsive_control( 'wcf-image-animation', [
			'label'       => self::__( 'Animation'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => [
				'none'    => self::__( 'none'),
				'reveal'  => self::__( 'Reveal'),
				'scale'   => self::__( 'Scale'),
				'stretch' => self::__( 'Stretch'),
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'aae_a_start_from', [
			'label'       => self::__( 'Animation To'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'right',
			'render_type' => 'none',
			'options'     => [
				'left'   => self::__( 'Left'),
				'right'  => self::__( 'Right'),
				'top'    => self::__( 'Top'),
				'bottom' => self::__( 'Bottom'),
			],
			'condition'   => [ 'wcf-image-animation' => 'reveal' ],
		] );

		$element->add_responsive_control( 'wcf-scale-start', [
			'label'       => self::__( 'Start Scale'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.5,
			'condition'   => [ 'wcf-image-animation' => 'scale' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf-scale-end', [
			'label'       => self::__( 'End Scale'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 1,
			'condition'   => [ 'wcf-image-animation' => 'scale' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'image-ease', [
			'label'       => self::__( 'Data ease'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'power2.out',
			'options'     => [
				'power2.out' => 'Power2.out',
				'bounce'     => 'Bounce',
				'back'       => 'Back',
				'elastic'    => 'Elastic',
				'slowmo'     => 'Slowmo',
				'stepped'    => 'Stepped',
				'sine'       => 'Sine',
				'expo'       => 'Expo',
			],
			'condition'   => [ 'wcf-image-animation' => 'reveal' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_img_animation_editor', [
			'label'        => self::__( 'Enable On Editor'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf-image-animation!' => 'none' ],
		] );

		$element->end_controls_section();
	}

	/* =====================================================================
	 * CURSOR HOVER + HOVER IMAGE + POPUP
	 * =================================================================== */
	public static function register_cursor_hover_effect_controls( $element ) {
		$tab = ( 'container' === $element->get_name() ) ? Controls_Manager::TAB_ADVANCED : Controls_Manager::TAB_CONTENT;

		// --- Cursor hover effect ---
		$element->start_controls_section(
			'_section_wcf_cursor_hover_area',
			[ 'label' => self::pro_label( self::__( 'Cursor hover effect') ), 'tab' => $tab ]
		);

		self::pro_notice( $element, 'pro_notice_cursor_hover' );

		$element->add_control( 'wcf_enable_cursor_hover_effect', [
			'label'        => self::__( 'Enable'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_cursor_hover_effect_editor', [
			'label'        => self::__( 'Enable On Editor'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_cursor_hover_effect!' => '' ],
		] );

		$element->add_control( 'wcf_enable_cursor_hover_effect_text', [
			'label'     => self::__( 'Text'),
			'type'      => Controls_Manager::TEXT,
			'separator' => 'after',
			'default'   => self::__( 'View'),
		] );

		$element->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'wcf_cursor_hover_cursor_typography',
			'selector' => '.wcf-hover-cursor-effect.active-{{ID}}',
		] );

		$element->add_control( 'wcf_cursor_hover_cursor_color', [
			'label'     => self::__( 'Text Color'),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'color: {{VALUE}}' ],
		] );

		$element->add_group_control( Group_Control_Background::get_type(), [
			'name'     => 'wcf_cursor_hover_cursor_background',
			'types'    => [ 'classic', 'gradient' ],
			'selector' => '.wcf-hover-cursor-effect.active-{{ID}}',
		] );

		$element->add_responsive_control( 'wcf_cursor_hover_cursor_width', [
			'label'      => self::__( 'Width'),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
			'selectors'  => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'width: {{SIZE}}{{UNIT}};' ],
		] );

		$element->add_responsive_control( 'wcf_cursor_hover_cursor_height', [
			'label'      => self::__( 'Height'),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'separator'  => 'after',
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
			'selectors'  => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'height: {{SIZE}}{{UNIT}};' ],
		] );

		$element->add_group_control( Group_Control_Border::get_type(), [
			'name'     => 'wcf_cursor_hover_cursor_border',
			'selector' => '.wcf-hover-cursor-effect.active-{{ID}}',
		] );

		$element->add_control( 'wcf_cursor_hover_cursor_border_radius', [
			'label'      => self::__( 'Border Radius'),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors'  => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$element->end_controls_section();

		// --- Image Reveal on Hover (containers only) ---
		if ( 'container' === $element->get_name() ) {
			$element->start_controls_section(
				'_section_wcf_hover_image_area',
				[ 'label' => self::pro_label( self::__( 'Image Reveal on Hover') ), 'tab' => Controls_Manager::TAB_ADVANCED ]
			);

			self::pro_notice( $element, 'pro_notice_hover_image' );

			$element->add_control( 'wcf_enable_hover_image', [
				'label'        => self::__( 'Enable'),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			] );

			$element->add_control( 'wcf_enable_hover_image_editor', [
				'label'        => self::__( 'Enable On Editor'),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'wcf_enable_hover_image!' => '' ],
			] );

			$element->add_control( 'wcf_hover_image', [
				'label'     => self::__( 'Choose Image'),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [ 'url' => Utils::get_placeholder_image_src() ],
				'selectors' => [ '{{WRAPPER}} .wcf-image-hover' => 'background-image: url( {{URL}} );' ],
				'condition' => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_width', [
				'label'      => self::__( 'Width'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'width: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_height', [
				'label'      => self::__( 'Height'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'separator'  => 'after',
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'height: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_position_top', [
				'label'      => self::__( 'Position Top'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => -1000, 'max' => 1000 ], '%' => [ 'min' => -100, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'top: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_position_left', [
				'label'      => self::__( 'Position Left'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => -1000, 'max' => 1000 ], '%' => [ 'min' => -100, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'left: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_control( 'wcf_hover_image_zindex', [
				'label'     => self::__( 'Z-index'),
				'type'      => Controls_Manager::NUMBER,
				'min'       => -9999,
				'max'       => 9999,
				'selectors' => [ '{{WRAPPER}} .wcf-image-hover' => 'z-index: {{VALUE}};' ],
				'condition' => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->end_controls_section();

			// --- Popup (containers only) ---
			$element->start_controls_section(
				'_section_wcf_popup_area',
				[ 'label' => self::pro_label( self::__( 'Popup') ), 'tab' => Controls_Manager::TAB_ADVANCED ]
			);

			self::pro_notice( $element, 'pro_notice_popup' );

			$element->add_control( 'wcf_enable_popup', [
				'label'        => self::__( 'Enable Popup'),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			] );

			$element->add_control( 'wcf_enable_popup_editor', [
				'label'        => self::__( 'Enable On Editor'),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'wcf_enable_popup!' => '' ],
			] );

			$element->add_control( 'popup_content_type', [
				'label'     => self::__( 'Content Type'),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'content'  => self::__( 'Content'),
					'template' => self::__( 'Saved Templates'),
				],
				'default'   => 'content',
				'condition' => [ 'wcf_enable_popup!' => '' ],
			] );

			$templates = function_exists( 'wcf_addons_get_saved_template_list' ) ? wcf_addons_get_saved_template_list() : [];
			$element->add_control( 'popup_elementor_templates', [
				'label'       => self::__( 'Save Template'),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => false,
				'multiple'    => false,
				'options'     => $templates,
				'condition'   => [
					'popup_content_type' => 'template',
					'wcf_enable_popup!'  => '',
				],
			] );

			$element->add_control( 'popup_content', [
				'label'     => self::__( 'Content'),
				'default'   => self::__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'),
				'type'      => Controls_Manager::WYSIWYG,
				'condition' => [
					'popup_content_type' => 'content',
					'wcf_enable_popup!'  => '',
				],
			] );

			$element->add_control( 'popup_condition', [
				'label'     => self::__( 'Open Condition'),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'click'      => self::__( 'Click'),
					'pageloaded' => self::__( 'Page Loaded'),
				],
				'default'   => 'click',
				'condition' => [ 'wcf_enable_popup!' => '' ],
			] );

			$element->add_control( 'wcf_enable_login_user', [
				'label'        => self::__( 'Enable On Login User'),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_load_after_xtime', [
				'label'     => self::__( 'Show After X time(milisecond)'),
				'type'      => Controls_Manager::NUMBER,
				'min'       => -1,
				'max'       => 80000,
				'step'      => 1000,
				'default'   => 2000,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_show_up_to_xtime', [
				'label'     => self::__( 'Show UpTo X time'),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 50,
				'default'   => 5,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_load_after_x_pageviews', [
				'label'     => self::__( 'Show After X Page Views'),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 50,
				'default'   => 0,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_show_x_devices', [
				'label'       => self::__( 'Show in X Devices'),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'mobile'  => self::__( 'Mobile'),
					'teblet'  => self::__( 'Teblet'),
					'desktop' => self::__( 'Desktop'),
				],
				'default'     => [],
				'condition'   => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'popup_trigger_cursor', [
				'label'     => self::__( 'Cursor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => [
					'default'  => self::__( 'Default'),
					'none'     => self::__( 'None'),
					'pointer'  => self::__( 'Pointer'),
					'grabbing' => self::__( 'Grabbing'),
					'move'     => self::__( 'Move'),
					'text'     => self::__( 'Text'),
				],
				'selectors' => [ '{{WRAPPER}}' => 'cursor: {{VALUE}};' ],
				'condition' => [ 'wcf_enable_popup!' => '' ],
			] );

			$element->end_controls_section();
		}
	}

	/* =====================================================================
	 * ADVANCED TAB: Tooltip, Tilt, Mouse Move, Horizontal Scroll, Animation, Pin
	 * =================================================================== */
	public static function tooltip_controls_section( $element ) {

		// --- Tooltip ---
		$element->start_controls_section(
			'_section_wcf_advanced_tooltip',
			[ 'label' => self::pro_label( self::__( 'Tooltip') ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_tooltip' );

		$element->add_control( 'wcf_advanced_tooltip_enable', [
			'label'        => self::__( 'Enable Tooltip?'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => self::__( 'On'),
			'label_off'    => self::__( 'Off'),
			'return_value' => 'enable',
			'default'      => '',
		] );

		$element->start_controls_tabs( 'wcf_tooltip_tabs' );

		$element->start_controls_tab( 'wcf_tooltip_settings', [
			'label'     => self::__( 'Settings'),
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_content', [
			'label'     => self::__( 'Content'),
			'type'      => Controls_Manager::TEXTAREA,
			'rows'      => 5,
			'default'   => self::__( 'I am a tooltip'),
			'dynamic'   => [ 'active' => true ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_position', [
			'label'     => self::__( 'Position'),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'top',
			'options'   => [
				'top'    => self::__( 'Top'),
				'bottom' => self::__( 'Bottom'),
				'left'   => self::__( 'Left'),
				'right'  => self::__( 'Right'),
			],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_animation', [
			'label'     => self::__( 'Animation'),
			'type'      => Controls_Manager::ANIMATION,
			'default'   => 'fadeIn',
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_duration', [
			'label'     => self::__( 'Animation Duration (ms)'),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 100,
			'max'       => 5000,
			'step'      => 50,
			'default'   => 1000,
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_arrow', [
			'label'        => self::__( 'Arrow'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => self::__( 'Show'),
			'label_off'    => self::__( 'Hide'),
			'return_value' => 'true',
			'default'      => 'true',
			'condition'    => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_trigger', [
			'label'     => self::__( 'Trigger'),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'hover',
			'options'   => [ 'click' => self::__( 'Click'), 'hover' => self::__( 'Hover') ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->end_controls_tab();

		$element->start_controls_tab( 'wcf_advanced_tooltip_styles', [
			'label'     => self::__( 'Styles'),
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_width', [
			'label'     => self::__( 'Width'),
			'type'      => Controls_Manager::SLIDER,
			'default'   => [ 'size' => 120 ],
			'range'     => [ 'px' => [ 'min' => 1, 'max' => 800 ] ],
			'selectors' => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'width: {{SIZE}}{{UNIT}};' ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_group_control( Group_Control_Typography::get_type(), [
			'name'      => 'wcf_advanced_tooltip_typography',
			'selector'  => '{{WRAPPER}} .wcf-advanced-tooltip',
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_background_color', [
			'label'     => self::__( 'Background Color'),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#000000',
			'selectors' => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'background: {{VALUE}};' ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_color', [
			'label'     => self::__( 'Text Color'),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'color: {{VALUE}};' ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_border_radius', [
			'label'      => self::__( 'Border Radius'),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			'condition'  => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_padding', [
			'label'      => self::__( 'Padding'),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em', '%' ],
			'selectors'  => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			'condition'  => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name'      => 'wcf_advanced_tooltip_box_shadow',
			'selector'  => '{{WRAPPER}} .wcf-advanced-tooltip',
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->end_controls_tab();
		$element->end_controls_tabs();
		$element->end_controls_section();

		// --- Tilt ---
		$element->start_controls_section(
			'notice_section_wcf_tilt_area',
			[ 'label' => self::pro_label( self::__( 'Tilt') ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_tilt' );

		$element->add_control( 'wcf_enable_tilt', [
			'label'        => self::__( 'Enable'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_tilt_editor', [
			'label'        => self::__( 'Enable On Editor'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_max_tilt', [
			'label'     => self::__( 'maxTilt'),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 5,
			'max'       => 50,
			'default'   => 20,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_perspective', [
			'label'     => self::__( 'Perspective'),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 1000,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_scale', [
			'label'     => self::__( 'Scale'),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 10,
			'default'   => 1,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_speed', [
			'label'     => self::__( 'Speed'),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 3000,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->end_controls_section();

		// --- Mouse Move Effect ---
		$element->start_controls_section(
			'_section_wcf_mouse_move_area',
			[ 'label' => self::pro_label( self::__( 'Mouse Move Effect') ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_mouse_move' );

		$element->add_control( 'wcf_enable_mouse_move_effect', [
			'label'        => self::__( 'Enable'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_mouse_movee_editor', [
			'label'        => self::__( 'Enable On Editor'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_mouse_move_effect!' => '' ],
		] );

		$element->add_control( 'wcf_mouse_move_area_trigger', [
			'label'       => self::__( 'Movement Wrapper'),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => self::__( 'Default'),
				'custom' => self::__( 'Custom'),
			],
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_custom_mouse_move_area', [
			'label'       => self::__( 'Custom Area'),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.movement_area',
			'render_type' => 'none',
			'condition'   => [
				'wcf_mouse_move_area_trigger'   => 'custom',
				'wcf_enable_mouse_move_effect!' => '',
			],
		] );

		$element->add_control( 'wcf_mouse_move_x', [
			'label'       => self::__( 'Move X'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 70,
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_mouse_move_y', [
			'label'       => self::__( 'Move Y'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 70,
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_mouse_move_duration', [
			'label'       => self::__( 'Duration'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.5,
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
		] );

		$element->add_control( 'wcf_mouse_move_custom', [
			'label'       => self::__( 'Customs'),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 5,
			'placeholder' => 'property:value, property2:value2',
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
		] );

		$element->end_controls_section();

		// --- Horizontal Scroll ---
		$element->start_controls_section(
			'_section_wcf_horizontal_scroll_area',
			[ 'label' => self::pro_label( self::__( 'Horizontal Scroll') ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_horizontal_scroll' );

		$element->add_control( 'important_note', [
			'label'           => self::__( 'Important Note'),
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => self::__( 'Please use full width Container to work properly.'),
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
		] );

		$element->add_responsive_control( 'wcf_enable_horizontal_scroll', [
			'label'       => self::__( 'Enable'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'no',
			'separator'   => 'before',
			'options'     => [
				'no'  => self::__( 'No'),
				'yes' => self::__( 'Yes'),
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'horizontal_scroll_width', [
			'label'       => self::__( 'Width'),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => [ 'px', '%', 'em', 'rem', 'custom' ],
			'range'       => [ 'px' => [ 'min' => 100, 'max' => 50000 ], '%' => [ 'min' => 10, 'max' => 1000 ] ],
			'default'     => [ 'unit' => '%', 'size' => 900 ],
			'description' => self::__( 'Set the total width of the horizontal scroll area in percentage (%).'),
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_horizontal_scroll' => 'yes' ],
		] );

		$element->add_responsive_control( 'horizontal_scroll_end', [
			'label'       => self::__( 'End'),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => [ 'px' ],
			'range'       => [ 'px' => [ 'min' => 100, 'max' => 10000 ] ],
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_horizontal_scroll' => 'yes' ],
		] );

		$element->end_controls_section();

		// --- Animation ---
		$element->start_controls_section(
			'_section_wcf_animation_area',
			[ 'label' => self::pro_label( self::__( 'Animation') ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_animation' );

		$anim_types = [ 'custom', 'fade', 'move' ];

		$element->add_responsive_control( 'wcf-animation', [
			'label'       => self::__( 'Animation'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => [
				'none'   => self::__( 'None'),
				'fade'   => self::__( 'Fade animation'),
				'move'   => self::__( '3D Move'),
				'custom' => self::__( 'Custom'),
			],
			'render_type' => 'template',
		] );

		$element->add_responsive_control( 'aae_method', [
			'label'       => self::__( 'Method'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'from',
			'render_type' => 'none',
			'options'     => [
				'from' => self::__( 'From'),
				'to'   => self::__( 'To'),
			],
			'condition'   => [ 'wcf-animation' => $anim_types ],
		] );

		$element->add_responsive_control( 'aae_trigger', [
			'label'       => self::__( 'Trigger'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'on_scroll',
			'render_type' => 'none',
			'options'     => [
				'on_scroll'        => self::__( 'On Scroll'),
				'on_page_load'     => self::__( 'On Page Load'),
				'play_with_scroll' => self::__( 'Play With Scroll'),
				'mouseover'        => self::__( 'On Hover'),
				'click'            => self::__( 'On Click'),
			],
			'condition'   => [ 'wcf-animation' => $anim_types ],
		] );

		$element->add_responsive_control( 'delay', [
			'label'       => self::__( 'Delay'),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 0.15,
			'render_type' => 'none',
			'condition'   => [ 'wcf-animation!' => [ 'custom', 'none' ] ],
		] );

		$element->add_responsive_control( 'fade-from', [
			'label'       => self::__( 'Fade from'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'bottom',
			'render_type' => 'none',
			'options'     => [
				'top'    => self::__( 'Top'),
				'bottom' => self::__( 'Bottom'),
				'left'   => self::__( 'Left'),
				'right'  => self::__( 'Right'),
				'in'     => self::__( 'In'),
				'scale'  => self::__( 'Zoom'),
			],
			'condition'   => [ 'wcf-animation' => 'fade' ],
		] );

		$element->add_responsive_control( 'data-duration', [
			'label'       => self::__( 'Duration'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 1.5,
			'render_type' => 'none',
			'condition'   => [ 'wcf-animation!' => [ 'custom', 'none' ] ],
		] );

		$element->add_responsive_control( 'ease', [
			'label'       => self::__( 'Ease'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'power2.out',
			'render_type' => 'none',
			'options'     => [
				'power2.out' => 'Power2.out',
				'bounce'     => 'Bounce',
				'back'       => 'Back',
				'elastic'    => 'Elastic',
				'slowmo'     => 'Slowmo',
				'stepped'    => 'Stepped',
				'sine'       => 'Sine',
				'expo'       => 'Expo',
				'none'       => self::__( 'None'),
			],
			'condition'   => [ 'wcf-animation!' => 'none' ],
		] );

		$element->add_responsive_control( 'fade-offset', [
			'label'       => self::__( 'Fade offset'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 50,
			'render_type' => 'none',
			'condition'   => [
				'fade-from!'    => [ 'in', 'scale' ],
				'wcf-animation' => 'fade',
			],
		] );

		$element->add_responsive_control( 'wcf-a-scale', [
			'label'       => self::__( 'Start Scale'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.7,
			'condition'   => [
				'fade-from'     => 'scale',
				'wcf-animation' => 'fade',
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_rotation_di', [
			'label'       => self::__( 'Rotation Direction'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'x',
			'separator'   => 'before',
			'options'     => [ 'x' => 'X', 'y' => 'Y' ],
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_rotation', [
			'label'       => self::__( 'Rotation Value'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => -80,
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_transform_origin', [
			'label'       => self::__( 'TransformOrigin'),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'top center -50',
			'placeholder' => 'top center',
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$repeater = new Repeater();
		$repeater->add_control( 'property', [
			'label'       => self::__( 'Property'),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => false,
			'options'     => [
				'none'            => self::__( 'None'),
				'opacity'         => self::__( 'Opacity'),
				'x'               => self::__( 'X'),
				'y'               => self::__( 'Y'),
				'width'           => self::__( 'Width'),
				'height'          => self::__( 'Height'),
				'scale'           => self::__( 'Scale'),
				'repeat'          => self::__( 'Repeat'),
				'rotate'          => self::__( 'Rotate'),
				'rotateX'         => self::__( 'RotateX'),
				'rotateY'         => self::__( 'RotateY'),
				'transformOrigin' => self::__( 'TransformOrigin'),
				'color'           => self::__( 'Color'),
				'background'      => self::__( 'Background'),
				'border'          => self::__( 'Border'),
				'boxShadow'       => self::__( 'BoxShadow'),
				'delay'           => self::__( 'Delay'),
				'duration'        => self::__( 'Duration'),
			],
			'render_type' => 'ui',
		] );
		$repeater->add_responsive_control( 'value', [
			'label'       => self::__( 'Value'),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'render_type' => 'ui',
		] );

		$element->add_control( 'aae_ani_custom_props', [
			'label'       => self::__( 'Custom Properties'),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'condition'   => [ 'wcf-animation' => 'custom' ],
			'label_block' => true,
			'title_field' => '{{{ property }}}',
			'separator'   => 'before',
			'render_type' => 'ui',
		] );

		$element->add_control( 'wcf_enable_animation_editor', [
			'label'        => self::__( 'Enable On Editor'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf-animation!' => 'none' ],
		] );

		$element->end_controls_section();

		// --- Sticky / Pin Element ---
		$element->start_controls_section(
			'_section_pin-area',
			[ 'label' => self::pro_label( self::__( 'Sticky/Pin Element') ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_pin' );

		$element->add_responsive_control( 'wcf_enable_pin_area', [
			'label'       => self::__( 'Enable'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'no',
			'separator'   => 'before',
			'options'     => [
				'no'  => self::__( 'No'),
				'yes' => self::__( 'Yes'),
			],
			'render_type' => 'ui',
		] );

		$element->add_responsive_control( 'wcf_pin_area_trigger', [
			'label'       => self::__( 'Pin Trigger'),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => self::__( 'Default'),
				'custom' => self::__( 'Custom'),
			],
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_custom_pin_area', [
			'label'       => self::__( 'Custom Pin Area'),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.pin_area',
			'render_type' => 'none',
			'condition'   => [
				'wcf_pin_area_trigger' => 'custom',
				'wcf_enable_pin_area'  => 'yes',
			],
		] );

		$element->add_responsive_control( 'wcf_pin_end_trigger_type', [
			'label'       => self::__( 'End Trigger'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'default',
			'separator'   => 'before',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
			'options'     => [
				'default' => self::__( 'Default'),
				'custom'  => self::__( 'Custom'),
			],
			'render_type' => 'ui',
		] );

		$element->add_responsive_control( 'wcf_pin_end_trigger', [
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.my-end-trigger',
			'render_type' => 'none',
			'default'     => '',
			'condition'   => [
				'wcf_enable_pin_area'      => 'yes',
				'wcf_pin_end_trigger_type' => 'custom',
			],
			'separator'   => 'after',
			'show_label'  => false,
		] );

		$element->add_responsive_control( 'wcf_pin_status', [
			'label'       => self::__( 'Pin'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'true',
			'options'     => [
				'true'   => self::__( 'True'),
				'false'  => self::__( 'False'),
				'custom' => self::__( 'Custom'),
			],
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
		] );

		$element->add_responsive_control( 'wcf_pin_spacing', [
			'label'       => self::__( 'PinSpacing'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'false',
			'options'     => [
				'true'   => self::__( 'True'),
				'false'  => self::__( 'False'),
				'custom' => self::__( 'Custom'),
			],
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
		] );

		$element->add_control( 'wcf_pin_markers', [
			'label'       => self::__( 'Pin Markers'),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'false',
			'options'     => [
				'true'  => self::__( 'True'),
				'false' => self::__( 'False'),
			],
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
		] );

		$element->end_controls_section();
	}
}

if ( ! defined( 'WCF_ADDONS_PRO_FILE' ) ) {
	WCFAddon_BlackList_Notice::init();
}
