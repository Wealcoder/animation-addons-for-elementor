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

        $element->add_responsive_control(
            'wcf_starter_container_animations_list',
            [
                'label' => esc_html__( 'Animation', 'animation-addons-for-elementor' ),
                'type'  => Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple' => false,
                'render_type' => 'ui',
                'frontend_available' => true,
                'classes' => 'wcf-select-scroll',
                'options' => [
                    'none' => esc_html__( 'None', 'animation-addons-for-elementor' ),
                    'reveal' => esc_html__( 'Reveal', 'animation-addons-for-elementor' ),
                    'slide-up' => esc_html__( 'Slide Up', 'animation-addons-for-elementor' ),
                    'skew-reveal' => esc_html__( 'Skew Reveal', 'animation-addons-for-elementor' ),
                    'flip-x' => esc_html__( 'Flip X', 'animation-addons-for-elementor' ),
                ],
                'default' => '',
                'prefix_class' => 'wcf-starter-animations-',
            ]
        );

        // Duration / Delay / Easing for container
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
                    'wcf_starter_container_animations_list!' => '',
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
                    'wcf_starter_container_animations_list!' => '',
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
                    'wcf_starter_container_animations_list!' => '',
                ],

                'selectors' => [
                    '{{WRAPPER}}' => '--wcf-ease: {{VALUE}};',
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
                    'wcf_starter_container_animations_list' => 'reveal',
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
                    'wcf_starter_container_animations_list' => 'reveal',
                ],
                'render_type' => 'ui',
                'frontend_available' => true,
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
        (function(){
            document.addEventListener('click', function(e){
                var btn = e.target.closest && e.target.closest('.wcf-play-animation-btn');
                if (!btn) return;

                // Try to determine the widget id from the editor control DOM and panel APIs.
                function findWidgetId(node) {
                    var el = node;
                    while (el && el !== document.body) {
                        if (el.getAttribute) {
                            if (el.getAttribute('data-element-id')) return el.getAttribute('data-element-id');
                            if (el.getAttribute('data-element_id')) return el.getAttribute('data-element_id');
                            if (el.getAttribute('data-id')) return el.getAttribute('data-id');
                            if (el.getAttribute('data-widget-id')) return el.getAttribute('data-widget-id');
                            if (el.getAttribute('data-widget_id')) return el.getAttribute('data-widget_id');
                        }
                        el = el.parentNode;
                    }

                    // Try to read from elementor panel model / current page view
                    try {
                        var panel = window.elementor && window.elementor.getPanelView && window.elementor.getPanelView();
                        var currentPage = panel && panel.getCurrentPageView && panel.getCurrentPageView();
                        var editedView = currentPage && currentPage.getOption && currentPage.getOption('editedElementView');
                        if (!editedView && panel && panel.getOption) {
                            editedView = panel.getOption && panel.getOption('editedElementView');
                        }
                        if (editedView && editedView.model) {
                            var m = editedView.model;
                            var id = (m.get && (m.get('id') || m.get('element_id') || m.get('elementId'))) || m.id || null;
                            if (id) return id;
                        }
                        if (currentPage && currentPage.getOption) {
                            var ev = currentPage.getOption('editedElementView');
                            if (ev && ev.model) {
                                var mm = ev.model;
                                var iid = (mm.get && (mm.get('id') || mm.get('element_id'))) || mm.id || null;
                                if (iid) return iid;
                            }
                        }
                    } catch(e){}

                    return null;
                }

                var widgetId = findWidgetId(btn) || null;

                try { console.log('wcf-play detected widgetId:', widgetId); } catch(e){}

                var widgetText = null;
                try {
                    var panel = window.elementor && window.elementor.getPanelView && window.elementor.getPanelView();
                    var currentPage = panel && panel.getCurrentPageView && panel.getCurrentPageView();
                    var editedView = currentPage && currentPage.getOption && currentPage.getOption('editedElementView');
                    if (!editedView && panel && panel.getOption) editedView = panel.getOption && panel.getOption('editedElementView');
                    var model = editedView && editedView.model ? editedView.model : (editedView && editedView.options && editedView.options.model ? editedView.options.model : null);
                    if (model) {
                        var settings = null;
                        try { settings = (model.get && model.get('settings')) || model.settings || model.attributes && model.attributes.settings; } catch(e) { settings = null; }
                        if (settings) {
                            var keys = ['title','text','editor','content','heading','html'];
                            for (var i=0;i<keys.length;i++) {
                                var k = keys[i];
                                if (settings[k]) { widgetText = settings[k]; break; }
                            }
                        }
                    }
                } catch(e) {}

                var payload = { wcfReplay: true, widgetId: widgetId, widgetText: widgetText, __debug: { foundOnButton: !!widgetId, modelText: !!widgetText } };
                var iframes = document.querySelectorAll('iframe');
                iframes.forEach(function(iframe){
                    try {
                        iframe.contentWindow.postMessage(JSON.stringify(payload), '*');
                    } catch(err) {
                        try { iframe.contentWindow.postMessage(payload, '*'); } catch(e){}
                    }
                });

            });

            function editorInitCharSplits(){
                try{
                    var all = document.querySelectorAll('[class*="wcf-starter-animations-text-char"]');
                    all.forEach(function(wrapper){
                        try{
                            if (wrapper.dataset && wrapper.dataset.wcfEditorCharInit) return;

                            var target = wrapper.querySelector('.elementor-widget-container > *') || wrapper.querySelector('.elementor-heading-title') || Array.from(wrapper.children).find(function(c){ return c.nodeType === 1; });
                            if (target && target.classList && target.classList.contains('elementor-inline-editing')) return;
                            if (!target) return;

                            if (target.querySelector && target.querySelector('span')) { if (wrapper.dataset) wrapper.dataset.wcfEditorCharInit = '1'; return; }

                            var text = (target.textContent || target.innerText || '').trim();
                            if (!text) { if (wrapper.dataset) wrapper.dataset.wcfEditorCharInit = '1'; return; }

                            var frag = document.createDocumentFragment();
                            text.split('').forEach(function(ch,i){
                                var span = document.createElement('span');
                                span.textContent = ch;
                                span.style.setProperty('--i', i);
                                frag.appendChild(span);
                            });

                            target.innerHTML = '';
                            target.appendChild(frag);
                            if (wrapper.dataset) wrapper.dataset.wcfEditorCharInit = '1';
                            try{ target.setAttribute('data-char-init','true'); } catch(e){}
                        }catch(e){}
                    });
                }catch(e){}
            }

            try{ editorInitCharSplits(); } catch(e){}
            try{
                var _wcf_editor_interval_count = 0;
                var _wcf_editor_interval = setInterval(function(){
                    try{ editorInitCharSplits(); } catch(e){}
                    _wcf_editor_interval_count++;
                    if (_wcf_editor_interval_count > 12) { clearInterval(_wcf_editor_interval); }
                }, 500);
            } catch(e){}
        })();
        </script>
        <?php
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
