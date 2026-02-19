<?php

namespace WCF_ADDONS;

use Elementor\Controls_Manager;
use Elementor\Element_Base;

defined( 'ABSPATH' ) || die();

class WCF_Starter_Animations {

    private static function get_text_widgets() {
        return [
            [ 'name' => 'heading',     'section' => 'section_title' ],
            [ 'name' => 'e-heading',   'section' => 'section_title' ],
            [ 'name' => 'text-editor', 'section' => 'section_editor' ],
            [ 'name' => 'image',       'section' => 'section_image' ],
        ];
    }

    public static function init() {

        // Inject Controls
        foreach ( self::get_text_widgets() as $widget ) {

            add_action(
                'elementor/element/' . $widget['name'] . '/' . $widget['section'] . '/after_section_end',
                [ __CLASS__, 'register_controls' ],
                10,
                2
            );
        }

        add_action('elementor/element/container/section_layout/after_section_end', [
			__CLASS__,
			'register_controls_container'
		], 1);
      
    }

    /**
     * Register Control Section
     */
    public static function register_controls( Element_Base $element ) {

        $widget_name = $element->get_name();


        $element->start_controls_section(
            'wcf_starter_animations_section',
            [
                'label' => sprintf(
                    '<i class="wcf-logo"></i> %s',
                    esc_html__( 'Starter Animations', 'animation-addons-for-elementor' )
                ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $element->add_responsive_control(
            'wcf_starter_animations',
            [
                'label' => esc_html__( 'Animation', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'render_type' => 'ui',
                'frontend_available' => true,
                'classes' => 'wcf-select-scroll',
                'options' => self::get_animation_options_by_widget( $widget_name ),
                'default' => '',
                'prefix_class' => 'wcf-starter-animations-',
            ]
        );

        $element->add_responsive_control(
            'wcf_anim_duration',
            [
                'label' => esc_html__( 'Duration (ms)', 'animation-addons-for-elementor' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 1000,
                'min' => 100,
                'max' => 10000,
                'step' => 50,
                'frontend_available' => true,
                'render_type' => 'ui',
                'condition' => [
                    'wcf_starter_animations!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-duration: {{VALUE}}ms;',
                ],
            ]
        );

        $element->add_responsive_control(
            'wcf_anim_delay',
            [
                'label' => esc_html__( 'Delay (ms)', 'animation-addons-for-elementor' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'max' => 10000,
                'step' => 50,
                'frontend_available' => true,
                'render_type' => 'ui',
                'condition' => [
                    'wcf_starter_animations!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-delay: {{VALUE}}ms;',
                ],
            ]
        );

        $element->add_responsive_control(
            'wcf_anim_ease',
            [
                'label' => esc_html__( 'Easing', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'default' => 'ease',
                'frontend_available' => true,
                'render_type' => 'ui',

                'options' => [
                    'ease'            => esc_html__( 'Ease (Default)', 'animation-addons-for-elementor' ),
                    'linear'          => esc_html__( 'Linear', 'animation-addons-for-elementor' ),
                    'ease-in'         => esc_html__( 'Ease In', 'animation-addons-for-elementor' ),
                    'ease-out'        => esc_html__( 'Ease Out', 'animation-addons-for-elementor' ),
                    'ease-in-out'     => esc_html__( 'Ease In Out', 'animation-addons-for-elementor' ),
                    'cubic-bezier(.25,.8,.25,1)' => esc_html__( 'Smooth Cubic', 'animation-addons-for-elementor' ),
                    'cubic-bezier(.17,.67,.83,.67)' => esc_html__( 'Elastic Feel', 'animation-addons-for-elementor' ),
                ],

                'condition' => [
                    'wcf_starter_animations!' => '',
                ],

                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-ease: {{VALUE}};',
                ],
            ]
        );

        $element->add_responsive_control(
            'wcf_glow_color',
            [
                'label' => esc_html__( 'Glow Color', 'animation-addons-for-elementor' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#00f',
                'condition' => [
                    'wcf_starter_animations' => 'text-glow',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-glow-color: {{VALUE}};',
                ],
            ]
        );

        $element->add_responsive_control(
            'wcf_glow_size',
            [
                'label' => esc_html__( 'Glow Size', 'animation-addons-for-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'size' => 20,
                ],
                'range' => [
                    'px' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-glow',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-glow-size: {{SIZE}}px;',
                ],
            ]
        );

        $element->add_responsive_control(
            'wcf_glow_iteration',
            [
                'label' => esc_html__( 'Animation Loop', 'animation-addons-for-elementor' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'infinite',
                'options' => [
                    '1' => 'Play Once',
                    'infinite' => 'Infinite',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-glow',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-iteration: {{VALUE}};',
                ],
            ]
        );

        $element->add_responsive_control(
            'wcf_mask_wipe_bg',
            [
                'label' => esc_html__( 'Mask Color', 'animation-addons-for-elementor' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#000000',
                'condition' => [
                    'wcf_starter_animations' => 'text-mask-wipe',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-mask-bg: {{VALUE}};',
                ],
            ]
        );

        $element->add_control(
            'wcf_reveal_direction',
            [
                'label' => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'default' => 'bottom',
                'options' => [
                    'bottom' => esc_html__( 'Bottom → Top', 'animation-addons-for-elementor' ),
                    'top'    => esc_html__( 'Top → Bottom', 'animation-addons-for-elementor' ),
                    'left'   => esc_html__( 'Left → Right', 'animation-addons-for-elementor' ),
                    'right'  => esc_html__( 'Right → Left', 'animation-addons-for-elementor' ),
                    'center' => esc_html__( 'Center Expand', 'animation-addons-for-elementor' ),
                ],
                'condition' => [
                    'wcf_starter_animations' => 'reveal',
                ],
                'prefix_class' => 'wcf-reveal-',
                'render_type' => 'ui',
                'frontend_available' => true,
            ]
        );

        $element->add_control(
            'wcf_reveal_fade',
            [
                'label' => esc_html__( 'Enable Fade', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SWITCHER,
                'default' => '',
                'condition' => [
                    'wcf_starter_animations' => 'reveal',
                ],
                'render_type' => 'ui',
                'frontend_available' => true,
            ]
        );

        $element->add_control(
            'wcf_wave_stroke_color',
            [
                'label' => esc_html__( 'Wave Stroke Color', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-wave-stroke: {{VALUE}};',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-wave',
                ],
            ]
        );

        $element->add_control(
            'wcf_wave_fill_color',
            [
                'label' => esc_html__( 'Wave Fill Color', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-wave-fill: {{VALUE}};',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-wave',
                ],
            ]
        );

        $element->add_control(
            'wcf_bg_text_image',
            [
                'label' => esc_html__( 'Background Image', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::MEDIA,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-bg-text-image: url({{URL}});',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-bg-clip',
                ],
            ]
        );

        $element->add_control(
            'wcf_bg_text_speed',
            [
                'label' => esc_html__( 'Animation Speed (seconds)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 15,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-bg-speed: {{VALUE}}s;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-bg-clip',
                ],
            ]
        );

        $element->add_control(
            'wcf_char_translate_x',
            [
                'label' => esc_html__( 'Translate X (px)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => -150,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-x: {{VALUE}}px;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                ],
            ]
        );

        $element->add_control(
            'wcf_char_translate_y',
            [
                'label' => esc_html__( 'Translate Y (px)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 0,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-y: {{VALUE}}px;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                ],
            ]
        );

        $element->add_control(
            'wcf_char_rotate',
            [
                'label' => esc_html__( 'Rotate (deg)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => -180,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-rotate: {{VALUE}}deg;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                ],
            ]
        );

        $element->add_control(
            'wcf_char_scale',
            [
                'label' => esc_html__( 'Scale', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 2,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-scale: {{VALUE}};',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                ],
            ]
        );


        $element->add_control(
            'wcf_char_stagger',
            [
                'label' => esc_html__( 'Stagger Delay (s)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 0.05,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-stagger: {{VALUE}}s;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                ],
            ]
        );


        $element->add_control(
            'wcf_play_animation',
            [
                'label' => esc_html__( 'Replay Animation', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::BUTTON,
                'text'  => esc_html__( '▶ Play', 'animation-addons-for-elementor' ),
                'classes' => 'wcf-play-animation-btn',
                'condition' => [
                    'wcf_starter_animations!' => '',
                ],
            ]
        );







        $element->end_controls_section();
    }
    public static function register_controls_container( Element_Base $element ) {

        $widget_name = $element->get_name();


        $element->start_controls_section(
            'wcf_starter_animations_container',
            [
                'label' => sprintf(
                    '<i class="wcf-logo"></i> %s',
                    esc_html__( 'Starter Animations', 'animation-addons-for-elementor' )
                ),
                'tab'   => Controls_Manager::TAB_LAYOUT,
            ]
        );


        $element->end_controls_section();
    }
    private static function get_animation_options_by_widget( $widget_name ) {

        $options = [
            'none' => esc_html__( 'None', 'animation-addons-for-elementor' ),
            'reveal' => esc_html__( 'Reveal', 'animation-addons-for-elementor' ),
            'slide-up' => esc_html__( 'Slide Up', 'animation-addons-for-elementor' ),
            'skew-reveal' => esc_html__( 'Skew Reveal', 'animation-addons-for-elementor' ),
            'flip-x' => esc_html__( 'Flip X', 'animation-addons-for-elementor' ),
        ];

        if ( in_array( $widget_name, [ 'heading', 'e-heading', 'text-editor' ], true ) ) {

            $options['__text_effect'] = esc_html__( '── Text Effects ──', 'animation-addons-for-elementor' );

            $options['text-glow'] = esc_html__( 'Glow Pulse', 'animation-addons-for-elementor' );
            $options['text-typewriter'] = esc_html__( 'Typewriter', 'animation-addons-for-elementor' );
            $options['text-mask-wipe'] = esc_html__( 'Mask Wipe', 'animation-addons-for-elementor' );

            /* 🔥 NEW */
            $options['text-wave'] = esc_html__( 'Water Wave', 'animation-addons-for-elementor' );
            $options['text-bg-clip'] = esc_html__( 'Background Clip Text', 'animation-addons-for-elementor' );
            $options['text-char-animate'] = esc_html__( 'Character Animation', 'animation-addons-for-elementor' );


        }

        return $options;
    }



    
}

WCF_Starter_Animations::init();
