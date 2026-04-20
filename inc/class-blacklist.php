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

	private static function pro_notice( $element, $id ) {
		$element->add_control( $id, [
			'label'           => esc_html__( 'Pro Note', self::TD ),
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => sprintf(
				/* translators: %1$s: opening <a> tag, %2$s: closing </a> tag */
				esc_html__( 'These settings are available in the Pro version. %1$sUpgrade to Animation Addons Pro%2$s to unlock all extensions and advanced features.', self::TD ),
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
			[ 'label' => self::pro_label( __( 'Text Animation', self::TD ) ) ]
		);

		self::pro_notice( $element, 'pro_notice_text_animation' );

		$animation = [
			'none'        => esc_html__( 'none', self::TD ),
			'char'        => esc_html__( 'Character', self::TD ),
			'word'        => esc_html__( 'Word', self::TD ),
			'text_move'   => esc_html__( 'Text Move', self::TD ),
			'text_reveal' => esc_html__( 'Text Reveal', self::TD ),
			'text_scale'  => esc_html__( 'Text Scale', self::TD ),
		];
		if ( in_array( $element->get_name(), [ 'heading', 'wcf--title' ], true ) ) {
			$animation['text_invert'] = esc_html__( 'Text Invert', self::TD );
			$animation['text_spin']   = esc_html__( '3D Spin', self::TD );
		}

		$animated_list = [ 'char', 'word', 'text_reveal', 'text_move', 'text_spin', 'text_scale' ];

		$element->add_responsive_control( 'wcf_text_animation', [
			'label'       => esc_html__( 'Animation', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => $animation,
			'render_type' => 'template',
		] );

		$element->add_responsive_control( 'aae_text_trigger', [
			'label'       => esc_html__( 'Trigger', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'on_scroll',
			'render_type' => 'none',
			'options'     => [
				'on_scroll'        => esc_html__( 'On Scroll', self::TD ),
				'on_page_load'     => esc_html__( 'On Page Load', self::TD ),
				'play_with_scroll' => esc_html__( 'Play With Scroll', self::TD ),
				'mouseover'        => esc_html__( 'On Hover', self::TD ),
				'click'            => esc_html__( 'On Click', self::TD ),
			],
			'condition'   => [ 'wcf_text_animation' => $animated_list ],
		] );

		$element->add_responsive_control( 'aae_trigger_text_selector', [
			'label'       => esc_html__( 'Trigger Selector', self::TD ),
			'description' => esc_html__( 'Selector for trigger element. Example: .my-class, #my-id', self::TD ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.my-class',
			'render_type' => 'none',
			'condition'   => [
				'wcf_text_animation' => $animated_list,
				'aae_text_trigger'   => [ 'mouseover', 'click' ],
			],
		] );

		$element->add_responsive_control( 'aae_anim_txt_wrapper', [
			'label'       => esc_html__( 'Text Wrapper', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => esc_html__( 'Default', self::TD ),
				'custom' => esc_html__( 'Custom', self::TD ),
			],
			'condition'   => [
				'aae_text_trigger'   => [ 'on_scroll', 'play_with_scroll' ],
				'wcf_text_animation' => $animated_list,
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_delay', [
			'label'       => esc_html__( 'Delay', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 0.15,
			'condition'   => [ 'wcf_text_animation' => $animated_list ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_duration', [
			'label'       => esc_html__( 'Duration', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 1,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word', 'text_reveal', 'text_move', 'text_scale' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_stagger', [
			'label'       => esc_html__( 'Stagger', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.01,
			'default'     => 0.02,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word', 'text_reveal', 'text_move', 'text_scale' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_translate_x', [
			'label'       => esc_html__( 'Transform-X', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 20,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_translate_y', [
			'label'       => esc_html__( 'Transform-Y', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_rotation_di', [
			'label'       => esc_html__( 'Rotation Direction', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'x',
			'separator'   => 'before',
			'options'     => [ 'x' => 'X', 'y' => 'Y' ],
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_rotation', [
			'label'       => esc_html__( 'Rotation Value', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => -80,
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_transform_origin', [
			'label'       => esc_html__( 'transformOrigin', self::TD ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'top center -50',
			'placeholder' => 'top center',
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_text_animation_editor', [
			'label'        => esc_html__( 'Enable On Editor', self::TD ),
			'description'  => esc_html__( 'For better performance in editor mode, keep the setting turned off.', self::TD ),
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
			[ 'label' => self::pro_label( __( 'Image Animation', self::TD ) ) ]
		);

		self::pro_notice( $element, 'pro_notice_image_animation' );

		$element->add_responsive_control( 'wcf-image-animation', [
			'label'       => esc_html__( 'Animation', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => [
				'none'    => esc_html__( 'none', self::TD ),
				'reveal'  => esc_html__( 'Reveal', self::TD ),
				'scale'   => esc_html__( 'Scale', self::TD ),
				'stretch' => esc_html__( 'Stretch', self::TD ),
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'aae_a_start_from', [
			'label'       => esc_html__( 'Animation To', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'right',
			'render_type' => 'none',
			'options'     => [
				'left'   => esc_html__( 'Left', self::TD ),
				'right'  => esc_html__( 'Right', self::TD ),
				'top'    => esc_html__( 'Top', self::TD ),
				'bottom' => esc_html__( 'Bottom', self::TD ),
			],
			'condition'   => [ 'wcf-image-animation' => 'reveal' ],
		] );

		$element->add_responsive_control( 'wcf-scale-start', [
			'label'       => esc_html__( 'Start Scale', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.5,
			'condition'   => [ 'wcf-image-animation' => 'scale' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf-scale-end', [
			'label'       => esc_html__( 'End Scale', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 1,
			'condition'   => [ 'wcf-image-animation' => 'scale' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'image-ease', [
			'label'       => esc_html__( 'Data ease', self::TD ),
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
			'label'        => esc_html__( 'Enable On Editor', self::TD ),
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
			[ 'label' => self::pro_label( __( 'Cursor hover effect', self::TD ) ), 'tab' => $tab ]
		);

		self::pro_notice( $element, 'pro_notice_cursor_hover' );

		$element->add_control( 'wcf_enable_cursor_hover_effect', [
			'label'        => esc_html__( 'Enable', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_cursor_hover_effect_editor', [
			'label'        => esc_html__( 'Enable On Editor', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_cursor_hover_effect!' => '' ],
		] );

		$element->add_control( 'wcf_enable_cursor_hover_effect_text', [
			'label'     => esc_html__( 'Text', self::TD ),
			'type'      => Controls_Manager::TEXT,
			'separator' => 'after',
			'default'   => esc_html__( 'View', self::TD ),
		] );

		$element->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'wcf_cursor_hover_cursor_typography',
			'selector' => '.wcf-hover-cursor-effect.active-{{ID}}',
		] );

		$element->add_control( 'wcf_cursor_hover_cursor_color', [
			'label'     => esc_html__( 'Text Color', self::TD ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'color: {{VALUE}}' ],
		] );

		$element->add_group_control( Group_Control_Background::get_type(), [
			'name'     => 'wcf_cursor_hover_cursor_background',
			'types'    => [ 'classic', 'gradient' ],
			'selector' => '.wcf-hover-cursor-effect.active-{{ID}}',
		] );

		$element->add_responsive_control( 'wcf_cursor_hover_cursor_width', [
			'label'      => esc_html__( 'Width', self::TD ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
			'selectors'  => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'width: {{SIZE}}{{UNIT}};' ],
		] );

		$element->add_responsive_control( 'wcf_cursor_hover_cursor_height', [
			'label'      => esc_html__( 'Height', self::TD ),
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
			'label'      => esc_html__( 'Border Radius', self::TD ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors'  => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$element->end_controls_section();

		// --- Image Reveal on Hover (containers only) ---
		if ( 'container' === $element->get_name() ) {
			$element->start_controls_section(
				'_section_wcf_hover_image_area',
				[ 'label' => self::pro_label( __( 'Image Reveal on Hover', self::TD ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
			);

			self::pro_notice( $element, 'pro_notice_hover_image' );

			$element->add_control( 'wcf_enable_hover_image', [
				'label'        => esc_html__( 'Enable', self::TD ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			] );

			$element->add_control( 'wcf_enable_hover_image_editor', [
				'label'        => esc_html__( 'Enable On Editor', self::TD ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'wcf_enable_hover_image!' => '' ],
			] );

			$element->add_control( 'wcf_hover_image', [
				'label'     => esc_html__( 'Choose Image', self::TD ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [ 'url' => Utils::get_placeholder_image_src() ],
				'selectors' => [ '{{WRAPPER}} .wcf-image-hover' => 'background-image: url( {{URL}} );' ],
				'condition' => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_width', [
				'label'      => esc_html__( 'Width', self::TD ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'width: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_height', [
				'label'      => esc_html__( 'Height', self::TD ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'separator'  => 'after',
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'height: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_position_top', [
				'label'      => esc_html__( 'Position Top', self::TD ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => -1000, 'max' => 1000 ], '%' => [ 'min' => -100, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'top: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_position_left', [
				'label'      => esc_html__( 'Position Left', self::TD ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => -1000, 'max' => 1000 ], '%' => [ 'min' => -100, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'left: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_control( 'wcf_hover_image_zindex', [
				'label'     => esc_html__( 'Z-index', self::TD ),
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
				[ 'label' => self::pro_label( __( 'Popup', self::TD ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
			);

			self::pro_notice( $element, 'pro_notice_popup' );

			$element->add_control( 'wcf_enable_popup', [
				'label'        => esc_html__( 'Enable Popup', self::TD ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			] );

			$element->add_control( 'wcf_enable_popup_editor', [
				'label'        => esc_html__( 'Enable On Editor', self::TD ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'wcf_enable_popup!' => '' ],
			] );

			$element->add_control( 'popup_content_type', [
				'label'     => esc_html__( 'Content Type', self::TD ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'content'  => esc_html__( 'Content', self::TD ),
					'template' => esc_html__( 'Saved Templates', self::TD ),
				],
				'default'   => 'content',
				'condition' => [ 'wcf_enable_popup!' => '' ],
			] );

			$templates = function_exists( 'wcf_addons_get_saved_template_list' ) ? wcf_addons_get_saved_template_list() : [];
			$element->add_control( 'popup_elementor_templates', [
				'label'       => esc_html__( 'Save Template', self::TD ),
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
				'label'     => esc_html__( 'Content', self::TD ),
				'default'   => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', self::TD ),
				'type'      => Controls_Manager::WYSIWYG,
				'condition' => [
					'popup_content_type' => 'content',
					'wcf_enable_popup!'  => '',
				],
			] );

			$element->add_control( 'popup_condition', [
				'label'     => esc_html__( 'Open Condition', self::TD ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'click'      => esc_html__( 'Click', self::TD ),
					'pageloaded' => esc_html__( 'Page Loaded', self::TD ),
				],
				'default'   => 'click',
				'condition' => [ 'wcf_enable_popup!' => '' ],
			] );

			$element->add_control( 'wcf_enable_login_user', [
				'label'        => esc_html__( 'Enable On Login User', self::TD ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_load_after_xtime', [
				'label'     => esc_html__( 'Show After X time(milisecond)', self::TD ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => -1,
				'max'       => 80000,
				'step'      => 1000,
				'default'   => 2000,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_show_up_to_xtime', [
				'label'     => esc_html__( 'Show UpTo X time', self::TD ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 50,
				'default'   => 5,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_load_after_x_pageviews', [
				'label'     => esc_html__( 'Show After X Page Views', self::TD ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 50,
				'default'   => 0,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_show_x_devices', [
				'label'       => esc_html__( 'Show in X Devices', self::TD ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'mobile'  => esc_html__( 'Mobile', self::TD ),
					'teblet'  => esc_html__( 'Teblet', self::TD ),
					'desktop' => esc_html__( 'Desktop', self::TD ),
				],
				'default'     => [],
				'condition'   => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'popup_trigger_cursor', [
				'label'     => esc_html__( 'Cursor', self::TD ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => [
					'default'  => esc_html__( 'Default', self::TD ),
					'none'     => esc_html__( 'None', self::TD ),
					'pointer'  => esc_html__( 'Pointer', self::TD ),
					'grabbing' => esc_html__( 'Grabbing', self::TD ),
					'move'     => esc_html__( 'Move', self::TD ),
					'text'     => esc_html__( 'Text', self::TD ),
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
			[ 'label' => self::pro_label( __( 'Tooltip', self::TD ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_tooltip' );

		$element->add_control( 'wcf_advanced_tooltip_enable', [
			'label'        => __( 'Enable Tooltip?', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'On', self::TD ),
			'label_off'    => __( 'Off', self::TD ),
			'return_value' => 'enable',
			'default'      => '',
		] );

		$element->start_controls_tabs( 'wcf_tooltip_tabs' );

		$element->start_controls_tab( 'wcf_tooltip_settings', [
			'label'     => __( 'Settings', self::TD ),
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_content', [
			'label'     => __( 'Content', self::TD ),
			'type'      => Controls_Manager::TEXTAREA,
			'rows'      => 5,
			'default'   => __( 'I am a tooltip', self::TD ),
			'dynamic'   => [ 'active' => true ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_position', [
			'label'     => __( 'Position', self::TD ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'top',
			'options'   => [
				'top'    => __( 'Top', self::TD ),
				'bottom' => __( 'Bottom', self::TD ),
				'left'   => __( 'Left', self::TD ),
				'right'  => __( 'Right', self::TD ),
			],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_animation', [
			'label'     => esc_html__( 'Animation', self::TD ),
			'type'      => Controls_Manager::ANIMATION,
			'default'   => 'fadeIn',
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_duration', [
			'label'     => __( 'Animation Duration (ms)', self::TD ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 100,
			'max'       => 5000,
			'step'      => 50,
			'default'   => 1000,
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_arrow', [
			'label'        => __( 'Arrow', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', self::TD ),
			'label_off'    => __( 'Hide', self::TD ),
			'return_value' => 'true',
			'default'      => 'true',
			'condition'    => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_trigger', [
			'label'     => __( 'Trigger', self::TD ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'hover',
			'options'   => [ 'click' => __( 'Click', self::TD ), 'hover' => __( 'Hover', self::TD ) ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->end_controls_tab();

		$element->start_controls_tab( 'wcf_advanced_tooltip_styles', [
			'label'     => __( 'Styles', self::TD ),
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_width', [
			'label'     => __( 'Width', self::TD ),
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
			'label'     => __( 'Background Color', self::TD ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#000000',
			'selectors' => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'background: {{VALUE}};' ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_color', [
			'label'     => __( 'Text Color', self::TD ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'color: {{VALUE}};' ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_border_radius', [
			'label'      => __( 'Border Radius', self::TD ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			'condition'  => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_padding', [
			'label'      => __( 'Padding', self::TD ),
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
			[ 'label' => self::pro_label( __( 'Tilt', self::TD ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_tilt' );

		$element->add_control( 'wcf_enable_tilt', [
			'label'        => esc_html__( 'Enable', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_tilt_editor', [
			'label'        => esc_html__( 'Enable On Editor', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_max_tilt', [
			'label'     => esc_html__( 'maxTilt', self::TD ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 5,
			'max'       => 50,
			'default'   => 20,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_perspective', [
			'label'     => esc_html__( 'Perspective', self::TD ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 1000,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_scale', [
			'label'     => esc_html__( 'Scale', self::TD ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 10,
			'default'   => 1,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_speed', [
			'label'     => esc_html__( 'Speed', self::TD ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 3000,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->end_controls_section();

		// --- Mouse Move Effect ---
		$element->start_controls_section(
			'_section_wcf_mouse_move_area',
			[ 'label' => self::pro_label( __( 'Mouse Move Effect', self::TD ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_mouse_move' );

		$element->add_control( 'wcf_enable_mouse_move_effect', [
			'label'        => esc_html__( 'Enable', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_mouse_movee_editor', [
			'label'        => esc_html__( 'Enable On Editor', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_mouse_move_effect!' => '' ],
		] );

		$element->add_control( 'wcf_mouse_move_area_trigger', [
			'label'       => esc_html__( 'Movement Wrapper', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => esc_html__( 'Default', self::TD ),
				'custom' => esc_html__( 'Custom', self::TD ),
			],
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_custom_mouse_move_area', [
			'label'       => esc_html__( 'Custom Area', self::TD ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.movement_area',
			'render_type' => 'none',
			'condition'   => [
				'wcf_mouse_move_area_trigger'   => 'custom',
				'wcf_enable_mouse_move_effect!' => '',
			],
		] );

		$element->add_control( 'wcf_mouse_move_x', [
			'label'       => esc_html__( 'Move X', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 70,
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_mouse_move_y', [
			'label'       => esc_html__( 'Move Y', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 70,
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_mouse_move_duration', [
			'label'       => esc_html__( 'Duration', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.5,
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
		] );

		$element->add_control( 'wcf_mouse_move_custom', [
			'label'       => esc_html__( 'Customs', self::TD ),
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
			[ 'label' => self::pro_label( __( 'Horizontal Scroll', self::TD ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_horizontal_scroll' );

		$element->add_control( 'important_note', [
			'label'           => esc_html__( 'Important Note', self::TD ),
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Please use full width Container to work properly.', self::TD ),
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
		] );

		$element->add_responsive_control( 'wcf_enable_horizontal_scroll', [
			'label'       => esc_html__( 'Enable', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'no',
			'separator'   => 'before',
			'options'     => [
				'no'  => esc_html__( 'No', self::TD ),
				'yes' => esc_html__( 'Yes', self::TD ),
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'horizontal_scroll_width', [
			'label'       => esc_html__( 'Width', self::TD ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => [ 'px', '%', 'em', 'rem', 'custom' ],
			'range'       => [ 'px' => [ 'min' => 100, 'max' => 50000 ], '%' => [ 'min' => 10, 'max' => 1000 ] ],
			'default'     => [ 'unit' => '%', 'size' => 900 ],
			'description' => esc_html__( 'Set the total width of the horizontal scroll area in percentage (%).', self::TD ),
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_horizontal_scroll' => 'yes' ],
		] );

		$element->add_responsive_control( 'horizontal_scroll_end', [
			'label'       => esc_html__( 'End', self::TD ),
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
			[ 'label' => self::pro_label( __( 'Animation', self::TD ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_animation' );

		$anim_types = [ 'custom', 'fade', 'move' ];

		$element->add_responsive_control( 'wcf-animation', [
			'label'       => esc_html__( 'Animation', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => [
				'none'   => esc_html__( 'None', self::TD ),
				'fade'   => esc_html__( 'Fade animation', self::TD ),
				'move'   => esc_html__( '3D Move', self::TD ),
				'custom' => esc_html__( 'Custom', self::TD ),
			],
			'render_type' => 'template',
		] );

		$element->add_responsive_control( 'aae_method', [
			'label'       => esc_html__( 'Method', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'from',
			'render_type' => 'none',
			'options'     => [
				'from' => esc_html__( 'From', self::TD ),
				'to'   => esc_html__( 'To', self::TD ),
			],
			'condition'   => [ 'wcf-animation' => $anim_types ],
		] );

		$element->add_responsive_control( 'aae_trigger', [
			'label'       => esc_html__( 'Trigger', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'on_scroll',
			'render_type' => 'none',
			'options'     => [
				'on_scroll'        => esc_html__( 'On Scroll', self::TD ),
				'on_page_load'     => esc_html__( 'On Page Load', self::TD ),
				'play_with_scroll' => esc_html__( 'Play With Scroll', self::TD ),
				'mouseover'        => esc_html__( 'On Hover', self::TD ),
				'click'            => esc_html__( 'On Click', self::TD ),
			],
			'condition'   => [ 'wcf-animation' => $anim_types ],
		] );

		$element->add_responsive_control( 'delay', [
			'label'       => esc_html__( 'Delay', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 0.15,
			'render_type' => 'none',
			'condition'   => [ 'wcf-animation!' => [ 'custom', 'none' ] ],
		] );

		$element->add_responsive_control( 'fade-from', [
			'label'       => esc_html__( 'Fade from', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'bottom',
			'render_type' => 'none',
			'options'     => [
				'top'    => esc_html__( 'Top', self::TD ),
				'bottom' => esc_html__( 'Bottom', self::TD ),
				'left'   => esc_html__( 'Left', self::TD ),
				'right'  => esc_html__( 'Right', self::TD ),
				'in'     => esc_html__( 'In', self::TD ),
				'scale'  => esc_html__( 'Zoom', self::TD ),
			],
			'condition'   => [ 'wcf-animation' => 'fade' ],
		] );

		$element->add_responsive_control( 'data-duration', [
			'label'       => esc_html__( 'Duration', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 1.5,
			'render_type' => 'none',
			'condition'   => [ 'wcf-animation!' => [ 'custom', 'none' ] ],
		] );

		$element->add_responsive_control( 'ease', [
			'label'       => esc_html__( 'Ease', self::TD ),
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
				'none'       => esc_html__( 'None', self::TD ),
			],
			'condition'   => [ 'wcf-animation!' => 'none' ],
		] );

		$element->add_responsive_control( 'fade-offset', [
			'label'       => esc_html__( 'Fade offset', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 50,
			'render_type' => 'none',
			'condition'   => [
				'fade-from!'    => [ 'in', 'scale' ],
				'wcf-animation' => 'fade',
			],
		] );

		$element->add_responsive_control( 'wcf-a-scale', [
			'label'       => esc_html__( 'Start Scale', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.7,
			'condition'   => [
				'fade-from'     => 'scale',
				'wcf-animation' => 'fade',
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_rotation_di', [
			'label'       => esc_html__( 'Rotation Direction', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'x',
			'separator'   => 'before',
			'options'     => [ 'x' => 'X', 'y' => 'Y' ],
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_rotation', [
			'label'       => esc_html__( 'Rotation Value', self::TD ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => -80,
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_transform_origin', [
			'label'       => esc_html__( 'TransformOrigin', self::TD ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'top center -50',
			'placeholder' => 'top center',
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$repeater = new Repeater();
		$repeater->add_control( 'property', [
			'label'       => __( 'Property', self::TD ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => false,
			'options'     => [
				'none'            => __( 'None', self::TD ),
				'opacity'         => __( 'Opacity', self::TD ),
				'x'               => __( 'X', self::TD ),
				'y'               => __( 'Y', self::TD ),
				'width'           => __( 'Width', self::TD ),
				'height'          => __( 'Height', self::TD ),
				'scale'           => __( 'Scale', self::TD ),
				'repeat'          => __( 'Repeat', self::TD ),
				'rotate'          => __( 'Rotate', self::TD ),
				'rotateX'         => __( 'RotateX', self::TD ),
				'rotateY'         => __( 'RotateY', self::TD ),
				'transformOrigin' => __( 'TransformOrigin', self::TD ),
				'color'           => __( 'Color', self::TD ),
				'background'      => __( 'Background', self::TD ),
				'border'          => __( 'Border', self::TD ),
				'boxShadow'       => __( 'BoxShadow', self::TD ),
				'delay'           => __( 'Delay', self::TD ),
				'duration'        => __( 'Duration', self::TD ),
			],
			'render_type' => 'ui',
		] );
		$repeater->add_responsive_control( 'value', [
			'label'       => __( 'Value', self::TD ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'render_type' => 'ui',
		] );

		$element->add_control( 'aae_ani_custom_props', [
			'label'       => __( 'Custom Properties', self::TD ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'condition'   => [ 'wcf-animation' => 'custom' ],
			'label_block' => true,
			'title_field' => '{{{ property }}}',
			'separator'   => 'before',
			'render_type' => 'ui',
		] );

		$element->add_control( 'wcf_enable_animation_editor', [
			'label'        => esc_html__( 'Enable On Editor', self::TD ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf-animation!' => 'none' ],
		] );

		$element->end_controls_section();

		// --- Sticky / Pin Element ---
		$element->start_controls_section(
			'_section_pin-area',
			[ 'label' => self::pro_label( __( 'Sticky/Pin Element', self::TD ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_pin' );

		$element->add_responsive_control( 'wcf_enable_pin_area', [
			'label'       => esc_html__( 'Enable', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'no',
			'separator'   => 'before',
			'options'     => [
				'no'  => esc_html__( 'No', self::TD ),
				'yes' => esc_html__( 'Yes', self::TD ),
			],
			'render_type' => 'ui',
		] );

		$element->add_responsive_control( 'wcf_pin_area_trigger', [
			'label'       => esc_html__( 'Pin Trigger', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => esc_html__( 'Default', self::TD ),
				'custom' => esc_html__( 'Custom', self::TD ),
			],
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_custom_pin_area', [
			'label'       => esc_html__( 'Custom Pin Area', self::TD ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.pin_area',
			'render_type' => 'none',
			'condition'   => [
				'wcf_pin_area_trigger' => 'custom',
				'wcf_enable_pin_area'  => 'yes',
			],
		] );

		$element->add_responsive_control( 'wcf_pin_end_trigger_type', [
			'label'       => esc_html__( 'End Trigger', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'default',
			'separator'   => 'before',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
			'options'     => [
				'default' => esc_html__( 'Default', self::TD ),
				'custom'  => esc_html__( 'Custom', self::TD ),
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
			'label'       => esc_html__( 'Pin', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'true',
			'options'     => [
				'true'   => esc_html__( 'True', self::TD ),
				'false'  => esc_html__( 'False', self::TD ),
				'custom' => esc_html__( 'Custom', self::TD ),
			],
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
		] );

		$element->add_responsive_control( 'wcf_pin_spacing', [
			'label'       => esc_html__( 'PinSpacing', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'false',
			'options'     => [
				'true'   => esc_html__( 'True', self::TD ),
				'false'  => esc_html__( 'False', self::TD ),
				'custom' => esc_html__( 'Custom', self::TD ),
			],
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
		] );

		$element->add_control( 'wcf_pin_markers', [
			'label'       => esc_html__( 'Pin Markers', self::TD ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'false',
			'options'     => [
				'true'  => esc_html__( 'True', self::TD ),
				'false' => esc_html__( 'False', self::TD ),
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
