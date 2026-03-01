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
            [ 'name' => 'wcf--image',       'section' => 'section_content' ],
            [ 'name' => 'wcf--blog--post--title', 'section' => 'section_content' ],
            [ 'name' => 'wcf--animated-heading', 'section' => 'section_content' ],
            [ 'name' => 'wcf--title', 'section' => 'section_content' ],
            [ 'name' => 'wcf--blog--archive--title', 'section' => 'section_content' ],
            [ 'name' => 'wcf--text', 'section' => 'section_content' ],
            [ 'name' => 'wcf--theme-post-content', 'section' => 'wcf_starter_animations_section' ],
            [ 'name' => 'wcf--blog--post--excerpt', 'section' => 'section_content' ]
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

        // Add editor-only script to handle the replay button click
        add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'editor_play_button_js' ] );
      
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
                'default' => 'none',
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
                    'bottom' => esc_html__( 'Bottom -> Top', 'animation-addons-for-elementor' ),
                    'top'    => esc_html__( 'Top -> Bottom', 'animation-addons-for-elementor' ),
                    'left'   => esc_html__( 'Left -> Right', 'animation-addons-for-elementor' ),
                    'right'  => esc_html__( 'Right -> Left', 'animation-addons-for-elementor' ),
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
                'prefix_class' => 'wcf-reveal-',
                'frontend_available' => true,
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
            'wcf_char_preset',
            [
                'label' => esc_html__( 'Character Preset', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'default' => 'revolve',
                'options' => [
                    'revolve'       => 'Revolve Scale',
                    'ball'          => 'Ball Drop',
                    'slide'         => 'Side Slide',
                    'revolve_drop'  => 'Revolve Drop',
                    'drop_vanish'   => 'Drop Vanish',
                    'twister'       => 'Twister',
                ],
                'prefix_class' => 'wcf-char-preset-',
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                ],
            ]
        );

        $element->add_control(
            'wcf_char_revolve_x',
            [
                'label' => 'Translate X',
                'type'  => Controls_Manager::NUMBER,
                'default' => -150,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-x: {{VALUE}}px;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                    'wcf_char_preset'        => 'revolve',
                ],
            ]
        );

        $element->add_control(
            'wcf_char_revolve_y',
            [
                'label' => 'Translate Y',
                'type'  => Controls_Manager::NUMBER,
                'default' => -50,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-y: {{VALUE}}px;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                    'wcf_char_preset'        => 'revolve',
                ],
            ]
        );
        $element->add_control(
            'wcf_char_ball_y',
            [
                'label' => 'Drop Distance',
                'type'  => Controls_Manager::NUMBER,
                'default' => 200,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-y: {{VALUE}}px;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                    'wcf_char_preset'        => 'ball',
                ],
            ]
        );
        $element->add_control(
            'wcf_char_twister_rotate',
            [
                'label' => 'Rotate Degree',
                'type'  => Controls_Manager::NUMBER,
                'default' => -180,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-rotate: {{VALUE}}deg;',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'text-char-animate',
                    'wcf_char_preset'        => 'twister',
                ],
            ]
        );
        $element->add_control(
            'wcf_char_custom_x',
            [
                'label' => 'Translate X',
                'type'  => Controls_Manager::NUMBER,
                'default' => 0,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-x: {{VALUE}}px;',
                ],
                'condition' => [
                    'wcf_char_preset' => 'custom',
                ],
            ]
        );

        $element->add_control(
            'wcf_char_custom_y',
            [
                'label' => 'Translate Y',
                'type'  => Controls_Manager::NUMBER,
                'default' => 0,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-y: {{VALUE}}px;',
                ],
                'condition' => [
                    'wcf_char_preset' => 'custom',
                ],
            ]
        );

        $element->add_control(
            'wcf_char_custom_rotate',
            [
                'label' => 'Rotate',
                'type'  => Controls_Manager::NUMBER,
                'default' => 0,
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-char-rotate: {{VALUE}}deg;',
                ],
                'condition' => [
                    'wcf_char_preset' => 'custom',
                ],
            ]
        );


        $element->add_control(
            'wcf_scale_start',
            [
                'label' => esc_html__( 'Start Scale', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 0.6,
                'step' => 0.1,
                'min'  => 0,
                'max'  => 3,
                'condition' => [
                    'wcf_starter_animations' => 'scale-up',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-scale-start: {{VALUE}};',
                ],
            ]
        );

        $element->add_control(
            'wcf_scale_end',
            [
                'label' => esc_html__( 'End Scale', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 1,
                'step' => 0.1,
                'min'  => 0,
                'max'  => 3,
                'condition' => [
                    'wcf_starter_animations' => 'scale-up',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-scale-end: {{VALUE}};',
                ],
            ]
        );


        $element->add_control(
            'wcf_scale_origin',
            [
                'label' => esc_html__( 'Scale From', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'default' => 'center',
                'options' => [
                    'center' => 'Center',
                    'top'    => 'Top',
                    'bottom' => 'Bottom',
                    'left'   => 'Left',
                    'right'  => 'Right',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'scale-up',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-scale-origin: {{VALUE}};',
                ],
            ]
        );

        $element->add_control(
            'wcf_scale_opacity_toggle',
            [
                'label' => esc_html__( 'Animate Opacity', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => [
                    'wcf_starter_animations' => 'scale-up',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-scale-opacity: 0;',
                ],
            ]
        );

        $element->add_control(
            'wcf_slide_direction',
            [
                'label' => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'default' => 'bottom',
                'options' => [
                    'bottom' => 'Bottom → Top',
                    'top'    => 'Top → Bottom',
                    'left'   => 'Left → Right',
                    'right'  => 'Right → Left',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'slide',
                ],
                'prefix_class' => 'wcf-slide-',
                'render_type' => 'ui',
                'frontend_available' => true,
            ]
        );

        $element->add_control(
            'wcf_slide_distance',
            [
                'label' => esc_html__( 'Distance (px)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 60,
                'min' => 0,
                'max' => 500,
                'step' => 5,
                'condition' => [
                    'wcf_starter_animations' => 'slide',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-slide-distance: {{VALUE}}px;',
                ],
            ]
        );

        $element->add_control(
            'wcf_skew_angle',
            [
                'label' => 'Skew Angle',
                'type' => Controls_Manager::NUMBER,
                'default' => 18,
                'condition' => [
                    'wcf_starter_animations' => 'skew-reveal',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-skew-angle: {{VALUE}}deg;',
                ],
            ]
        );

        $element->add_control(
            'wcf_skew_distance',
            [
                'label' => 'Translate Distance',
                'type' => Controls_Manager::NUMBER,
                'default' => 40,
                'condition' => [
                    'wcf_starter_animations' => 'skew-reveal',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-skew-distance: {{VALUE}}px;',
                ],
            ]
        );

        $element->add_control(
            'wcf_flip_axis',
            [
                'label' => 'Flip Direction',
                'type' => Controls_Manager::SELECT,
                'default' => 'x',
                'options' => [
                    'x' => 'Flip X',
                    'y' => 'Flip Y',
                ],
                'condition' => [
                    'wcf_starter_animations' => 'flip',
                ],
                'prefix_class' => 'wcf-flip-axis-',
            ]
        );

        $element->add_control(
            'wcf_flip_angle',
            [
                'label' => 'Flip Angle',
                'type' => Controls_Manager::NUMBER,
                'default' => 90,
                'condition' => [
                    'wcf_starter_animations' => 'flip',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-flip-angle: {{VALUE}}deg;',
                ],
            ]
        );

        $element->add_control(
            'wcf_flip_perspective',
            [
                'label' => 'Perspective',
                'type' => Controls_Manager::NUMBER,
                'default' => 800,
                'condition' => [
                    'wcf_starter_animations' => 'flip',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-flip-perspective: {{VALUE}}px;',
                ],
            ]
        );

        $element->add_control(
            'wcf_repeat_on_enter',
            [
                'label' => esc_html__( 'Repeat Animation?', 'animation-addons-for-elementor' ),
                'description' => esc_html__( 'Choose whether the animation should play only once or replay every time the element enters the screen while scrolling.', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'default' => 'no',
                'options' => [
                    'no'  => esc_html__( 'Play Once', 'animation-addons-for-elementor' ),
                    'yes' => esc_html__( 'Every Time', 'animation-addons-for-elementor' ),
                ],
                'condition' => [
                    'wcf_starter_animations!' => 'none',
                ],
                'prefix_class' => 'wcf-repeat-',
                'frontend_available' => true,
                'render_type' => 'ui',
            ]
        );

        $element->add_control(
            'wcf_play_animation',
            [
                'label' => esc_html__( 'Replay Animation', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::BUTTON,
                'text'  => esc_html__( 'Play', 'animation-addons-for-elementor' ),
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

        /* =========================================
        Container Animation: Slide
        ========================================= */

        $element->add_control(
            'wcf_starter_container_animations_list',
            [
                'label' => esc_html__( 'Animation', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'label_block' => true,
                'options' => [
                    'none'  => esc_html__( 'None', 'animation-addons-for-elementor' ),
                    'slide' => esc_html__( 'Slide', 'animation-addons-for-elementor' ),
                    'flip' => esc_html__( 'Flip', 'animation-addons-for-elementor' ),
                ],
                'default' => 'none',
                'prefix_class' => 'wcf-starter-animations-',
            ]
        );

        /* Slide Direction */

        $element->add_control(
            'wcf_slide_direction_container',
            [
                'label' => esc_html__( 'Slide Direction', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'default' => 'bottom',
                'options' => [
                    'bottom' => 'Bottom → Top',
                    'top'    => 'Top → Bottom',
                    'left'   => 'Left → Right',
                    'right'  => 'Right → Left',
                ],
                'condition' => [
                    'wcf_starter_container_animations_list' => 'slide',
                ],
                'prefix_class' => 'wcf-slide-',
            ]
        );

        /* Distance */

        $element->add_control(
            'wcf_slide_distance_container',
            [
                'label' => esc_html__( 'Distance (px)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 40,
                'condition' => [
                    'wcf_starter_container_animations_list' => 'slide',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-slide-distance: {{VALUE}}px;',
                ],
            ]
        );

        /* Duration */

        $element->add_control(
            'wcf_slide_duration_container',
            [
                'label' => esc_html__( 'Duration (ms)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 600,
                'condition' => [
                    'wcf_starter_container_animations_list' => 'slide',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-slide-duration: {{VALUE}}ms;',
                ],
            ]
        );

        /* Delay */

        $element->add_control(
            'wcf_slide_delay_container',
            [
                'label' => esc_html__( 'Delay (ms)', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'wcf_starter_container_animations_list' => 'slide',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-slide-delay: {{VALUE}}ms;',
                ],
            ]
        );

        /* Easing */

        $element->add_control(
            'wcf_slide_ease_container',
            [
                'label' => esc_html__( 'Easing', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT,
                'default' => 'ease',
                'options' => [
                    'ease'        => 'Ease',
                    'ease-in'     => 'Ease In',
                    'ease-out'    => 'Ease Out',
                    'ease-in-out' => 'Ease In Out',
                    'linear'      => 'Linear',
                ],
                'condition' => [
                    'wcf_starter_container_animations_list' => 'slide',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-slide-ease: {{VALUE}};',
                ],
            ]
        );

        // flip animation controls
        
        $element->add_control(
            'wcf_flip_axis_container',
            [
                'label' => 'Flip Direction',
                'type' => Controls_Manager::SELECT,
                'default' => 'x',
                'options' => [
                    'x' => 'Flip X',
                    'y' => 'Flip Y',
                ],
                'condition' => [
                    'wcf_starter_container_animations_list' => 'flip',
                ],
                'prefix_class' => 'wcf-flip-axis-container-',
            ]
        );

        $element->add_control(
            'wcf_flip_angle_container',
            [
                'label' => 'Flip Angle',
                'type' => Controls_Manager::NUMBER,
                'default' => 90,
                'condition' => [
                    'wcf_starter_container_animations_list' => 'flip',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-flip-angle-container: {{VALUE}}deg;',
                ],
            ]
        );

        $element->add_control(
            'wcf_flip_perspective_container',
            [
                'label' => 'Perspective',
                'type' => Controls_Manager::NUMBER,
                'default' => 800,
                'condition' => [
                    'wcf_starter_container_animations_list' => 'flip',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-flip-perspective-container: {{VALUE}}px;',
                ],
            ]
        );

        $element->add_control(
            'wcf_play_animation_container',
            [
                'label' => esc_html__( 'Replay Animation', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::BUTTON,
                'text'  => esc_html__( 'Play', 'animation-addons-for-elementor' ),
                'classes' => 'wcf-play-animation-btn wcf-play-animation-container',
                'condition' => [
                    'wcf_starter_container_animations_list!' => '',
                ],
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Output a small inline script in the Elementor editor to handle
     * clicks on the replay button control.
     */
    public static function editor_play_button_js() {
        ?>
        <script>
        document.addEventListener('click', function(e){

        const btn = e.target.closest('.wcf-play-animation-btn');
        if (!btn) return;

        // Find current edited widget
        try {
            const panel = elementor.getPanelView();
            const page  = panel.getCurrentPageView();
            const view  = page.getOption('editedElementView');
            const model = view.model;
            const id    = model.get('id');

            const iframe = document.querySelector('iframe');
            const target = iframe.contentDocument.querySelector(
            '.elementor-element-' + id
            );

            if (target && iframe.contentWindow.wcfReplayAnimation) {
            iframe.contentWindow.wcfReplayAnimation(target);
            }

        } catch(err){
            console.log('Replay error', err);
        }

        });
        </script>
        <?php
    }

    private static function get_animation_options_by_widget( $widget_name ) {

        $options = [
            'none' => esc_html__( 'None', 'animation-addons-for-elementor' ),
            'reveal' => esc_html__( 'Reveal', 'animation-addons-for-elementor' ),
            'scale-up' => esc_html__( 'Scale', 'animation-addons-for-elementor' ),
            'slide' => esc_html__( 'Slide', 'animation-addons-for-elementor' ),
            'skew-reveal' => esc_html__( 'Skew Reveal', 'animation-addons-for-elementor' ),
            'flip' => esc_html__( 'Flip', 'animation-addons-for-elementor' ),
        ];

        if ( in_array( $widget_name, [ 'heading', 'e-heading', 'text-editor','wcf--animated-heading','wcf--blog--post--title','wcf--title','wcf--blog--archive--title','wcf--text','wcf--theme-post-content','wcf--blog--post--excerpt' ], true ) ) {
            $options['__text_effect'] = esc_html__( '-- Text Effects --', 'animation-addons-for-elementor' );

            $options['text-glow'] = esc_html__( 'Glow Pulse', 'animation-addons-for-elementor' );
            $options['text-typewriter'] = esc_html__( 'Typewriter', 'animation-addons-for-elementor' );
            $options['text-mask-wipe'] = esc_html__( 'Mask Wipe', 'animation-addons-for-elementor' );

            /* NEW */
            $options['text-wave'] = esc_html__( 'Water Wave', 'animation-addons-for-elementor' );
            $options['text-bg-clip'] = esc_html__( 'Background Clip Text', 'animation-addons-for-elementor' );
            $options['text-char-animate'] = esc_html__( 'Character Animation', 'animation-addons-for-elementor' );


        }

        return $options;
    }

}

WCF_Starter_Animations::init();
