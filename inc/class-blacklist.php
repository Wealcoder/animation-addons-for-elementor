<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

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

	private static function pro_notice( $element, $id ) {
		$element->add_control( $id, [
			'label'           => __( 'Pro Note', 'animation-addons-for-elementor' ),
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => sprintf(
				/* translators: %1$s: opening <a> tag, %2$s: closing </a> tag */
				__( 'These settings are available in the Pro version. %1$sUpgrade to Animation Addons Pro%2$s to unlock all extensions and advanced features.', 'animation-addons-for-elementor' ),
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
		return sprintf( '<i class="wcf-logo"></i> %s <a href="https://try.animation-addons.com" target="_blank" class="wcfpro_text aae-icon-lock" style="font-size: 9px; font-weight: normal; text-decoration: none; display: inline-flex; align-items: center; gap: 3px;">Try</a>', $text );
	}

	/* =====================================================================
	 * TEXT ANIMATION
	 * =================================================================== */
	public static function register_text_animation_controls( $element ) {
		$element->start_controls_section(
			'_section_wcf_text_animation',
			[ 'label' => self::pro_label( __( 'Text Animation', 'animation-addons-for-elementor' ) ) ]
		);

		self::pro_notice( $element, 'pro_notice_text_animation' );

		$animation = [
			'none'        => __( 'none', 'animation-addons-for-elementor' ),
			'char'        => __( 'Character', 'animation-addons-for-elementor' ),
			'word'        => __( 'Word', 'animation-addons-for-elementor' ),
			'text_move'   => __( 'Text Move', 'animation-addons-for-elementor' ),
			'text_reveal' => __( 'Text Reveal', 'animation-addons-for-elementor' ),
			'text_scale'  => __( 'Text Scale', 'animation-addons-for-elementor' ),
		];
		if ( in_array( $element->get_name(), [ 'heading', 'wcf--title' ], true ) ) {
			$animation['text_invert'] = __( 'Text Invert', 'animation-addons-for-elementor' );
			$animation['text_spin']   = __( '3D Spin', 'animation-addons-for-elementor' );
		}

		$animated_list = [ 'char', 'word', 'text_reveal', 'text_move', 'text_spin', 'text_scale' ];

		$element->add_responsive_control( 'wcf_text_animation', [
			'label'       => __( 'Animation', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => $animation,
			'render_type' => 'template',
		] );

		$element->add_responsive_control( 'aae_text_trigger', [
			'label'       => __( 'Trigger', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'on_scroll',
			'render_type' => 'none',
			'options'     => [
				'on_scroll'        => __( 'On Scroll', 'animation-addons-for-elementor' ),
				'on_page_load'     => __( 'On Page Load', 'animation-addons-for-elementor' ),
				'play_with_scroll' => __( 'Play With Scroll', 'animation-addons-for-elementor' ),
				'mouseover'        => __( 'On Hover', 'animation-addons-for-elementor' ),
				'click'            => __( 'On Click', 'animation-addons-for-elementor' ),
			],
			'condition'   => [ 'wcf_text_animation' => $animated_list ],
		] );

		$element->add_responsive_control( 'aae_trigger_text_selector', [
			'label'       => __( 'Trigger Selector', 'animation-addons-for-elementor' ),
			'description' => __( 'Selector for trigger element. Example: .my-class, #my-id', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.my-class',
			'render_type' => 'none',
			'condition'   => [
				'wcf_text_animation' => $animated_list,
				'aae_text_trigger'   => [ 'mouseover', 'click' ],
			],
		] );

		$element->add_responsive_control( 'aae_anim_txt_wrapper', [
			'label'       => __( 'Text Wrapper', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => __( 'Default', 'animation-addons-for-elementor' ),
				'custom' => __( 'Custom', 'animation-addons-for-elementor' ),
			],
			'condition'   => [
				'aae_text_trigger'   => [ 'on_scroll', 'play_with_scroll' ],
				'wcf_text_animation' => $animated_list,
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_delay', [
			'label'       => __( 'Delay', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 0.15,
			'condition'   => [ 'wcf_text_animation' => $animated_list ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_duration', [
			'label'       => __( 'Duration', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 1,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word', 'text_reveal', 'text_move', 'text_scale' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_stagger', [
			'label'       => __( 'Stagger', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.01,
			'default'     => 0.02,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word', 'text_reveal', 'text_move', 'text_scale' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_translate_x', [
			'label'       => __( 'Transform-X', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 20,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_translate_y', [
			'label'       => __( 'Transform-Y', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'condition'   => [ 'wcf_text_animation' => [ 'char', 'word' ] ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_rotation_di', [
			'label'       => __( 'Rotation Direction', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'x',
			'separator'   => 'before',
			'options'     => [ 'x' => 'X', 'y' => 'Y' ],
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_rotation', [
			'label'       => __( 'Rotation Value', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => -80,
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'text_transform_origin', [
			'label'       => __( 'transformOrigin', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'top center -50',
			'placeholder' => 'top center',
			'condition'   => [ 'wcf_text_animation' => 'text_move' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_text_animation_editor', [
			'label'        => __( 'Enable On Editor', 'animation-addons-for-elementor' ),
			'description'  => __( 'For better performance in editor mode, keep the setting turned off.', 'animation-addons-for-elementor' ),
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
			[ 'label' => self::pro_label( __( 'Image Animation', 'animation-addons-for-elementor' ) ) ]
		);

		self::pro_notice( $element, 'pro_notice_image_animation' );

		$element->add_responsive_control( 'wcf-image-animation', [
			'label'       => __( 'Animation', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => [
				'none'    => __( 'none', 'animation-addons-for-elementor' ),
				'reveal'  => __( 'Reveal', 'animation-addons-for-elementor' ),
				'scale'   => __( 'Scale', 'animation-addons-for-elementor' ),
				'stretch' => __( 'Stretch', 'animation-addons-for-elementor' ),
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'aae_a_start_from', [
			'label'       => __( 'Animation To', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'right',
			'render_type' => 'none',
			'options'     => [
				'left'   => __( 'Left', 'animation-addons-for-elementor' ),
				'right'  => __( 'Right', 'animation-addons-for-elementor' ),
				'top'    => __( 'Top', 'animation-addons-for-elementor' ),
				'bottom' => __( 'Bottom', 'animation-addons-for-elementor' ),
			],
			'condition'   => [ 'wcf-image-animation' => 'reveal' ],
		] );

		$element->add_responsive_control( 'wcf-scale-start', [
			'label'       => __( 'Start Scale', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.5,
			'condition'   => [ 'wcf-image-animation' => 'scale' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf-scale-end', [
			'label'       => __( 'End Scale', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 1,
			'condition'   => [ 'wcf-image-animation' => 'scale' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'image-ease', [
			'label'       => __( 'Data ease', 'animation-addons-for-elementor' ),
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
			'label'        => __( 'Enable On Editor', 'animation-addons-for-elementor' ),
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
			[ 'label' => self::pro_label( __( 'Cursor hover effect', 'animation-addons-for-elementor' ) ), 'tab' => $tab ]
		);

		self::pro_notice( $element, 'pro_notice_cursor_hover' );

		$element->add_control( 'wcf_enable_cursor_hover_effect', [
			'label'        => __( 'Enable', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_cursor_hover_effect_editor', [
			'label'        => __( 'Enable On Editor', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_cursor_hover_effect!' => '' ],
		] );

		$element->add_control( 'wcf_enable_cursor_hover_effect_text', [
			'label'     => __( 'Text', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::TEXT,
			'separator' => 'after',
			'default'   => __( 'View', 'animation-addons-for-elementor' ),
		] );

		$element->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'wcf_cursor_hover_cursor_typography',
			'selector' => '.wcf-hover-cursor-effect.active-{{ID}}',
		] );

		$element->add_control( 'wcf_cursor_hover_cursor_color', [
			'label'     => __( 'Text Color', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'color: {{VALUE}}' ],
		] );

		$element->add_group_control( Group_Control_Background::get_type(), [
			'name'     => 'wcf_cursor_hover_cursor_background',
			'types'    => [ 'classic', 'gradient' ],
			'selector' => '.wcf-hover-cursor-effect.active-{{ID}}',
		] );

		$element->add_responsive_control( 'wcf_cursor_hover_cursor_width', [
			'label'      => __( 'Width', 'animation-addons-for-elementor' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
			'selectors'  => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'width: {{SIZE}}{{UNIT}};' ],
		] );

		$element->add_responsive_control( 'wcf_cursor_hover_cursor_height', [
			'label'      => __( 'Height', 'animation-addons-for-elementor' ),
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
			'label'      => __( 'Border Radius', 'animation-addons-for-elementor' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors'  => [ '.wcf-hover-cursor-effect.active-{{ID}}' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$element->end_controls_section();

		// --- Image Reveal on Hover (containers only) ---
		if ( 'container' === $element->get_name() ) {
			$element->start_controls_section(
				'_section_wcf_hover_image_area',
				[ 'label' => self::pro_label( __( 'Image Reveal on Hover', 'animation-addons-for-elementor' ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
			);

			self::pro_notice( $element, 'pro_notice_hover_image' );

			$element->add_control( 'wcf_enable_hover_image', [
				'label'        => __( 'Enable', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			] );

			$element->add_control( 'wcf_enable_hover_image_editor', [
				'label'        => __( 'Enable On Editor', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'wcf_enable_hover_image!' => '' ],
			] );

			$element->add_control( 'wcf_hover_image', [
				'label'     => __( 'Choose Image', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [ 'url' => Utils::get_placeholder_image_src() ],
				'selectors' => [ '{{WRAPPER}} .wcf-image-hover' => 'background-image: url( {{URL}} );' ],
				'condition' => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_width', [
				'label'      => __( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'width: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_height', [
				'label'      => __( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'separator'  => 'after',
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 1000 ], '%' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'height: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_position_top', [
				'label'      => __( 'Position Top', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => -1000, 'max' => 1000 ], '%' => [ 'min' => -100, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'top: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_responsive_control( 'wcf_hover_image_position_left', [
				'label'      => __( 'Position Left', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [ 'px' => [ 'min' => -1000, 'max' => 1000 ], '%' => [ 'min' => -100, 'max' => 100 ] ],
				'selectors'  => [ '{{WRAPPER}} .wcf-image-hover' => 'left: {{SIZE}}{{UNIT}};' ],
				'condition'  => [ 'wcf_enable_hover_image' => 'yes' ],
			] );

			$element->add_control( 'wcf_hover_image_zindex', [
				'label'     => __( 'Z-index', 'animation-addons-for-elementor' ),
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
				[ 'label' => self::pro_label( __( 'Popup', 'animation-addons-for-elementor' ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
			);

			self::pro_notice( $element, 'pro_notice_popup' );

			$element->add_control( 'wcf_enable_popup', [
				'label'        => __( 'Enable Popup', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			] );

			$element->add_control( 'wcf_enable_popup_editor', [
				'label'        => __( 'Enable On Editor', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'wcf_enable_popup!' => '' ],
			] );

			$element->add_control( 'popup_content_type', [
				'label'     => __( 'Content Type', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'content'  => __( 'Content', 'animation-addons-for-elementor' ),
					'template' => __( 'Saved Templates', 'animation-addons-for-elementor' ),
				],
				'default'   => 'content',
				'condition' => [ 'wcf_enable_popup!' => '' ],
			] );

			$templates = function_exists( 'wcf_addons_get_saved_template_list' ) ? wcf_addons_get_saved_template_list() : [];
			$element->add_control( 'popup_elementor_templates', [
				'label'       => __( 'Save Template', 'animation-addons-for-elementor' ),
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
				'label'     => __( 'Content', 'animation-addons-for-elementor' ),
				'default'   => __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::WYSIWYG,
				'condition' => [
					'popup_content_type' => 'content',
					'wcf_enable_popup!'  => '',
				],
			] );

			$element->add_control( 'popup_condition', [
				'label'     => __( 'Open Condition', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'click'      => __( 'Click', 'animation-addons-for-elementor' ),
					'pageloaded' => __( 'Page Loaded', 'animation-addons-for-elementor' ),
				],
				'default'   => 'click',
				'condition' => [ 'wcf_enable_popup!' => '' ],
			] );

			$element->add_control( 'wcf_enable_login_user', [
				'label'        => __( 'Enable On Login User', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_load_after_xtime', [
				'label'     => __( 'Show After X time(milisecond)', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => -1,
				'max'       => 80000,
				'step'      => 1000,
				'default'   => 2000,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_show_up_to_xtime', [
				'label'     => __( 'Show UpTo X time', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 50,
				'default'   => 5,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_load_after_x_pageviews', [
				'label'     => __( 'Show After X Page Views', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 50,
				'default'   => 0,
				'condition' => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'wcf_show_x_devices', [
				'label'       => __( 'Show in X Devices', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'mobile'  => __( 'Mobile', 'animation-addons-for-elementor' ),
					'teblet'  => __( 'Teblet', 'animation-addons-for-elementor' ),
					'desktop' => __( 'Desktop', 'animation-addons-for-elementor' ),
				],
				'default'     => [],
				'condition'   => [ 'popup_condition' => 'pageloaded' ],
			] );

			$element->add_control( 'popup_trigger_cursor', [
				'label'     => __( 'Cursor', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => [
					'default'  => __( 'Default', 'animation-addons-for-elementor' ),
					'none'     => __( 'None', 'animation-addons-for-elementor' ),
					'pointer'  => __( 'Pointer', 'animation-addons-for-elementor' ),
					'grabbing' => __( 'Grabbing', 'animation-addons-for-elementor' ),
					'move'     => __( 'Move', 'animation-addons-for-elementor' ),
					'text'     => __( 'Text', 'animation-addons-for-elementor' ),
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
			[ 'label' => self::pro_label( __( 'Tooltip', 'animation-addons-for-elementor' ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_tooltip' );

		$element->add_control( 'wcf_advanced_tooltip_enable', [
			'label'        => __( 'Enable Tooltip?', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'On', 'animation-addons-for-elementor' ),
			'label_off'    => __( 'Off', 'animation-addons-for-elementor' ),
			'return_value' => 'enable',
			'default'      => '',
		] );

		$element->start_controls_tabs( 'wcf_tooltip_tabs' );

		$element->start_controls_tab( 'wcf_tooltip_settings', [
			'label'     => __( 'Settings', 'animation-addons-for-elementor' ),
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_content', [
			'label'     => __( 'Content', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::TEXTAREA,
			'rows'      => 5,
			'default'   => __( 'I am a tooltip', 'animation-addons-for-elementor' ),
			'dynamic'   => [ 'active' => true ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_position', [
			'label'     => __( 'Position', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'top',
			'options'   => [
				'top'    => __( 'Top', 'animation-addons-for-elementor' ),
				'bottom' => __( 'Bottom', 'animation-addons-for-elementor' ),
				'left'   => __( 'Left', 'animation-addons-for-elementor' ),
				'right'  => __( 'Right', 'animation-addons-for-elementor' ),
			],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_animation', [
			'label'     => __( 'Animation', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::ANIMATION,
			'default'   => 'fadeIn',
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_duration', [
			'label'     => __( 'Animation Duration (ms)', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 100,
			'max'       => 5000,
			'step'      => 50,
			'default'   => 1000,
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_arrow', [
			'label'        => __( 'Arrow', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'animation-addons-for-elementor' ),
			'label_off'    => __( 'Hide', 'animation-addons-for-elementor' ),
			'return_value' => 'true',
			'default'      => 'true',
			'condition'    => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_trigger', [
			'label'     => __( 'Trigger', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'hover',
			'options'   => [ 'click' => __( 'Click', 'animation-addons-for-elementor' ), 'hover' => __( 'Hover', 'animation-addons-for-elementor' ) ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->end_controls_tab();

		$element->start_controls_tab( 'wcf_advanced_tooltip_styles', [
			'label'     => __( 'Styles', 'animation-addons-for-elementor' ),
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_width', [
			'label'     => __( 'Width', 'animation-addons-for-elementor' ),
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
			'label'     => __( 'Background Color', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#000000',
			'selectors' => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'background: {{VALUE}};' ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_control( 'wcf_advanced_tooltip_color', [
			'label'     => __( 'Text Color', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'color: {{VALUE}};' ],
			'condition' => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_border_radius', [
			'label'      => __( 'Border Radius', 'animation-addons-for-elementor' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .wcf-advanced-tooltip' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			'condition'  => [ 'wcf_advanced_tooltip_enable!' => '' ],
		] );

		$element->add_responsive_control( 'wcf_advanced_tooltip_padding', [
			'label'      => __( 'Padding', 'animation-addons-for-elementor' ),
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
			[ 'label' => self::pro_label( __( 'Tilt', 'animation-addons-for-elementor' ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_tilt' );

		$element->add_control( 'wcf_enable_tilt', [
			'label'        => __( 'Enable', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_tilt_editor', [
			'label'        => __( 'Enable On Editor', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_max_tilt', [
			'label'     => __( 'maxTilt', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 5,
			'max'       => 50,
			'default'   => 20,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_perspective', [
			'label'     => __( 'Perspective', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 1000,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_scale', [
			'label'     => __( 'Scale', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 10,
			'default'   => 1,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->add_control( 'wcf_tilt_speed', [
			'label'     => __( 'Speed', 'animation-addons-for-elementor' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 3000,
			'condition' => [ 'wcf_enable_tilt!' => '' ],
		] );

		$element->end_controls_section();

		// --- Mouse Move Effect ---
		$element->start_controls_section(
			'_section_wcf_mouse_move_area',
			[ 'label' => self::pro_label( __( 'Mouse Move Effect', 'animation-addons-for-elementor' ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_mouse_move' );

		$element->add_control( 'wcf_enable_mouse_move_effect', [
			'label'        => __( 'Enable', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		] );

		$element->add_control( 'wcf_enable_mouse_movee_editor', [
			'label'        => __( 'Enable On Editor', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf_enable_mouse_move_effect!' => '' ],
		] );

		$element->add_control( 'wcf_mouse_move_area_trigger', [
			'label'       => __( 'Movement Wrapper', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => __( 'Default', 'animation-addons-for-elementor' ),
				'custom' => __( 'Custom', 'animation-addons-for-elementor' ),
			],
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_custom_mouse_move_area', [
			'label'       => __( 'Custom Area', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.movement_area',
			'render_type' => 'none',
			'condition'   => [
				'wcf_mouse_move_area_trigger'   => 'custom',
				'wcf_enable_mouse_move_effect!' => '',
			],
		] );

		$element->add_control( 'wcf_mouse_move_x', [
			'label'       => __( 'Move X', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 70,
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_mouse_move_y', [
			'label'       => __( 'Move Y', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 70,
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
			'render_type' => 'none',
		] );

		$element->add_control( 'wcf_mouse_move_duration', [
			'label'       => __( 'Duration', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.5,
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_mouse_move_effect!' => '' ],
		] );

		$element->add_control( 'wcf_mouse_move_custom', [
			'label'       => __( 'Customs', 'animation-addons-for-elementor' ),
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
			[ 'label' => self::pro_label( __( 'Horizontal Scroll', 'animation-addons-for-elementor' ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_horizontal_scroll' );

		$element->add_control( 'important_note', [
			'label'           => __( 'Important Note', 'animation-addons-for-elementor' ),
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => __( 'Please use full width Container to work properly.', 'animation-addons-for-elementor' ),
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
		] );

		$element->add_responsive_control( 'wcf_enable_horizontal_scroll', [
			'label'       => __( 'Enable', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'no',
			'separator'   => 'before',
			'options'     => [
				'no'  => __( 'No', 'animation-addons-for-elementor' ),
				'yes' => __( 'Yes', 'animation-addons-for-elementor' ),
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'horizontal_scroll_width', [
			'label'       => __( 'Width', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => [ 'px', '%', 'em', 'rem', 'custom' ],
			'range'       => [ 'px' => [ 'min' => 100, 'max' => 50000 ], '%' => [ 'min' => 10, 'max' => 1000 ] ],
			'default'     => [ 'unit' => '%', 'size' => 900 ],
			'description' => __( 'Set the total width of the horizontal scroll area in percentage (%).', 'animation-addons-for-elementor' ),
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_horizontal_scroll' => 'yes' ],
		] );

		$element->add_responsive_control( 'horizontal_scroll_end', [
			'label'       => __( 'End', 'animation-addons-for-elementor' ),
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
			[ 'label' => self::pro_label( __( 'Animation', 'animation-addons-for-elementor' ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_animation' );

		$anim_types = [ 'custom', 'fade', 'move' ];

		$element->add_responsive_control( 'wcf-animation', [
			'label'       => __( 'Animation', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'none',
			'separator'   => 'before',
			'options'     => [
				'none'   => __( 'None', 'animation-addons-for-elementor' ),
				'fade'   => __( 'Fade animation', 'animation-addons-for-elementor' ),
				'move'   => __( '3D Move', 'animation-addons-for-elementor' ),
				'custom' => __( 'Custom', 'animation-addons-for-elementor' ),
			],
			'render_type' => 'template',
		] );

		$element->add_responsive_control( 'aae_method', [
			'label'       => __( 'Method', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'from',
			'render_type' => 'none',
			'options'     => [
				'from' => __( 'From', 'animation-addons-for-elementor' ),
				'to'   => __( 'To', 'animation-addons-for-elementor' ),
			],
			'condition'   => [ 'wcf-animation' => $anim_types ],
		] );

		$element->add_responsive_control( 'aae_trigger', [
			'label'       => __( 'Trigger', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'on_scroll',
			'render_type' => 'none',
			'options'     => [
				'on_scroll'        => __( 'On Scroll', 'animation-addons-for-elementor' ),
				'on_page_load'     => __( 'On Page Load', 'animation-addons-for-elementor' ),
				'play_with_scroll' => __( 'Play With Scroll', 'animation-addons-for-elementor' ),
				'mouseover'        => __( 'On Hover', 'animation-addons-for-elementor' ),
				'click'            => __( 'On Click', 'animation-addons-for-elementor' ),
			],
			'condition'   => [ 'wcf-animation' => $anim_types ],
		] );

		$element->add_responsive_control( 'delay', [
			'label'       => __( 'Delay', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 0,
			'max'         => 10,
			'step'        => 0.1,
			'default'     => 0.15,
			'render_type' => 'none',
			'condition'   => [ 'wcf-animation!' => [ 'custom', 'none' ] ],
		] );

		$element->add_responsive_control( 'fade-from', [
			'label'       => __( 'Fade from', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'bottom',
			'render_type' => 'none',
			'options'     => [
				'top'    => __( 'Top', 'animation-addons-for-elementor' ),
				'bottom' => __( 'Bottom', 'animation-addons-for-elementor' ),
				'left'   => __( 'Left', 'animation-addons-for-elementor' ),
				'right'  => __( 'Right', 'animation-addons-for-elementor' ),
				'in'     => __( 'In', 'animation-addons-for-elementor' ),
				'scale'  => __( 'Zoom', 'animation-addons-for-elementor' ),
			],
			'condition'   => [ 'wcf-animation' => 'fade' ],
		] );

		$element->add_responsive_control( 'data-duration', [
			'label'       => __( 'Duration', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 1.5,
			'render_type' => 'none',
			'condition'   => [ 'wcf-animation!' => [ 'custom', 'none' ] ],
		] );

		$element->add_responsive_control( 'ease', [
			'label'       => __( 'Ease', 'animation-addons-for-elementor' ),
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
				'none'       => __( 'None', 'animation-addons-for-elementor' ),
			],
			'condition'   => [ 'wcf-animation!' => 'none' ],
		] );

		$element->add_responsive_control( 'fade-offset', [
			'label'       => __( 'Fade offset', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 50,
			'render_type' => 'none',
			'condition'   => [
				'fade-from!'    => [ 'in', 'scale' ],
				'wcf-animation' => 'fade',
			],
		] );

		$element->add_responsive_control( 'wcf-a-scale', [
			'label'       => __( 'Start Scale', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0.7,
			'condition'   => [
				'fade-from'     => 'scale',
				'wcf-animation' => 'fade',
			],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_rotation_di', [
			'label'       => __( 'Rotation Direction', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'x',
			'separator'   => 'before',
			'options'     => [ 'x' => 'X', 'y' => 'Y' ],
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_rotation', [
			'label'       => __( 'Rotation Value', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => -80,
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_a_transform_origin', [
			'label'       => __( 'TransformOrigin', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'top center -50',
			'placeholder' => 'top center',
			'condition'   => [ 'wcf-animation' => 'move' ],
			'render_type' => 'none',
		] );

		$repeater = new Repeater();
		$repeater->add_control( 'property', [
			'label'       => __( 'Property', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => false,
			'options'     => [
				'none'            => __( 'None', 'animation-addons-for-elementor' ),
				'opacity'         => __( 'Opacity', 'animation-addons-for-elementor' ),
				'x'               => __( 'X', 'animation-addons-for-elementor' ),
				'y'               => __( 'Y', 'animation-addons-for-elementor' ),
				'width'           => __( 'Width', 'animation-addons-for-elementor' ),
				'height'          => __( 'Height', 'animation-addons-for-elementor' ),
				'scale'           => __( 'Scale', 'animation-addons-for-elementor' ),
				'repeat'          => __( 'Repeat', 'animation-addons-for-elementor' ),
				'rotate'          => __( 'Rotate', 'animation-addons-for-elementor' ),
				'rotateX'         => __( 'RotateX', 'animation-addons-for-elementor' ),
				'rotateY'         => __( 'RotateY', 'animation-addons-for-elementor' ),
				'transformOrigin' => __( 'TransformOrigin', 'animation-addons-for-elementor' ),
				'color'           => __( 'Color', 'animation-addons-for-elementor' ),
				'background'      => __( 'Background', 'animation-addons-for-elementor' ),
				'border'          => __( 'Border', 'animation-addons-for-elementor' ),
				'boxShadow'       => __( 'BoxShadow', 'animation-addons-for-elementor' ),
				'delay'           => __( 'Delay', 'animation-addons-for-elementor' ),
				'duration'        => __( 'Duration', 'animation-addons-for-elementor' ),
			],
			'render_type' => 'ui',
		] );
		$repeater->add_responsive_control( 'value', [
			'label'       => __( 'Value', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'render_type' => 'ui',
		] );

		$element->add_control( 'aae_ani_custom_props', [
			'label'       => __( 'Custom Properties', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'condition'   => [ 'wcf-animation' => 'custom' ],
			'label_block' => true,
			'title_field' => '{{{ property }}}',
			'separator'   => 'before',
			'render_type' => 'ui',
		] );

		$element->add_control( 'wcf_enable_animation_editor', [
			'label'        => __( 'Enable On Editor', 'animation-addons-for-elementor' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'condition'    => [ 'wcf-animation!' => 'none' ],
		] );

		$element->end_controls_section();

		// --- Sticky / Pin Element ---
		$element->start_controls_section(
			'_section_pin-area',
			[ 'label' => self::pro_label( __( 'Sticky/Pin Element', 'animation-addons-for-elementor' ) ), 'tab' => Controls_Manager::TAB_ADVANCED ]
		);

		self::pro_notice( $element, 'pro_notice_pin' );

		$element->add_responsive_control( 'wcf_enable_pin_area', [
			'label'       => __( 'Enable', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'no',
			'separator'   => 'before',
			'options'     => [
				'no'  => __( 'No', 'animation-addons-for-elementor' ),
				'yes' => __( 'Yes', 'animation-addons-for-elementor' ),
			],
			'render_type' => 'ui',
		] );

		$element->add_responsive_control( 'wcf_pin_area_trigger', [
			'label'       => __( 'Pin Trigger', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => '',
			'options'     => [
				''       => __( 'Default', 'animation-addons-for-elementor' ),
				'custom' => __( 'Custom', 'animation-addons-for-elementor' ),
			],
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
			'render_type' => 'none',
		] );

		$element->add_responsive_control( 'wcf_custom_pin_area', [
			'label'       => __( 'Custom Pin Area', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => '.pin_area',
			'render_type' => 'none',
			'condition'   => [
				'wcf_pin_area_trigger' => 'custom',
				'wcf_enable_pin_area'  => 'yes',
			],
		] );

		$element->add_responsive_control( 'wcf_pin_end_trigger_type', [
			'label'       => __( 'End Trigger', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'default',
			'separator'   => 'before',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
			'options'     => [
				'default' => __( 'Default', 'animation-addons-for-elementor' ),
				'custom'  => __( 'Custom', 'animation-addons-for-elementor' ),
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
			'label'       => __( 'Pin', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'true',
			'options'     => [
				'true'   => __( 'True', 'animation-addons-for-elementor' ),
				'false'  => __( 'False', 'animation-addons-for-elementor' ),
				'custom' => __( 'Custom', 'animation-addons-for-elementor' ),
			],
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
		] );

		$element->add_responsive_control( 'wcf_pin_spacing', [
			'label'       => __( 'PinSpacing', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'false',
			'options'     => [
				'true'   => __( 'True', 'animation-addons-for-elementor' ),
				'false'  => __( 'False', 'animation-addons-for-elementor' ),
				'custom' => __( 'Custom', 'animation-addons-for-elementor' ),
			],
			'render_type' => 'none',
			'condition'   => [ 'wcf_enable_pin_area' => 'yes' ],
		] );

		$element->add_control( 'wcf_pin_markers', [
			'label'       => __( 'Pin Markers', 'animation-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'false',
			'options'     => [
				'true'  => __( 'True', 'animation-addons-for-elementor' ),
				'false' => __( 'False', 'animation-addons-for-elementor' ),
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
