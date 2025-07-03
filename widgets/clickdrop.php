<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Utils;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;   // Exit if accessed directly.
}

class ClickDrop extends Widget_Base
{

    public function get_name()
    {
        return 'wcf--clickdrop';
    }

    public function get_title()
    {
        return esc_html__('ClickDrop', 'animation-addons-for-elementor');
    }

    public function get_icon()
    {
        return 'wcf eicon-click';
    }

    public function show_in_panel()
    {
        // By default don't show.
        return true;
    }

    public function get_categories()
    {
        return ['wcf-single-addon'];
    }

    public function get_keywords()
    {
        return ['clickdrop', 'popup', 'content popup'];
    }

    public function get_style_depends()
    {
        return ['aae-clickdrop'];
    }

    public function get_script_depends()
    {
        return ['aae-clickdrop'];
    }

    protected function register_controls()
    {
        // content control
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Add Menu', 'animation-addons-for-elementor'),
            ]
        );
        $this->add_control(
            'login_label',
            [
                'label' => esc_html__('Login label', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Login', 'animation-addons-for-elementor'),
                'placeholder' => esc_html__('Login', 'animation-addons-for-elementor'),
            ]
        );
        $this->add_control(
            'logged_label',
            [
                'label' => esc_html__('Logged label', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Your Account', 'animation-addons-for-elementor'),
                'placeholder' => esc_html__('Your Account', 'animation-addons-for-elementor'),
            ]
        );
        $this->add_control(
            'menus_url',
            [
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'menu_title',
                        'label' => esc_html__('Menu title', 'animation-addons-for-elementor'),
                        'type' => Controls_Manager::TEXT,
                        'default' => esc_html__('Saved', 'animation-addons-for-elementor'),
                        'label_block' => true,
                    ],
                    [
                        'name' => 'menu_link',
                        'label' => esc_html__('Menu URL', 'animation-addons-for-elementor'),
                        'type' => Controls_Manager::URL,
                        'default' => [
                            'url' => 'https://crowdytheme.com',
                            'is_external' => true,
                            'nofollow' => true,
                        ],
                        'label_block' => true,
                    ],
                    [
                        'name' => 'menu_icon',
                        'label' => esc_html__('Menu Icon', 'animation-addons-for-elementor'),
                        'type' => Controls_Manager::ICONS,
                        'default' => [
                            'value' => 'fas fa-circle',
                            'library' => 'fa-solid',
                        ],
                        'recommended' => [
                            'fa-solid' => [
                                'circle',
                                'dot-circle',
                                'square-full',
                            ],
                            'fa-regular' => [
                                'circle',
                                'dot-circle',
                                'square-full',
                            ],
                        ],
                    ],
                ],
                'default' => [
                    [
                        'list_title' => esc_html__('https://crowdytheme.com', 'animation-addons-for-elementor'),
                        'list_icon' => [
                            'value' => 'fas fa-circle',
                            'library' => 'fa-solid',
                        ],
                    ],
                ],
                'title_field' => '{{{ list_title }}}',
            ]
        );
        $this->end_controls_section();
        // style control
        $this->start_controls_section(
            'clickdrop_style',
            [
                'label' => esc_html__('Style', 'animation-addons-for-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'wrapper_bg',
                'types' => ['classic', 'gradient', 'video'],
                'selector' => '{{WRAPPER}} .aae-clickdrop-modal',
            ]
        );
        $this->add_control(
            'padding',
            [
                'label' => esc_html__('Padding', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                'default' => [
                    'top' => 2,
                    'right' => 0,
                    'bottom' => 2,
                    'left' => 0,
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .aae-clickdrop-modal' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->end_controls_section();
        // icon style
        $this->start_controls_section(
            'icon',
            [
                'label' => esc_html__('Icon', 'animation-addons-for-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'icon_width',
            [
                'label' => esc_html__('Size', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 5,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 16,
                ],
                'selectors' => [
                    '{{WRAPPER}} .aae-clickdrop-modal ul li a svg' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_control(
            'icon_color',
            [
                'label' => esc_html__('Color', 'textdomain'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .aae-clickdrop-modal ul li a svg' => 'fill: {{VALUE}}',
                ],
            ]
        );
        $this->add_control(
            'icon_padding',
            [
                'label' => esc_html__('Padding', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                'default' => [
                    'top' => 2,
                    'right' => 0,
                    'bottom' => 2,
                    'left' => 0,
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .aae-clickdrop-modal ul li a svg' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->end_controls_section();
        // text label style
        $this->start_controls_section(
            'text_control',
            [
                'label' => esc_html('Text', 'animation-addons-for-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'label_text',
            [
                'label' => esc_html__('Label style', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'default',
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .aae-clickdrop-btn',
            ]
        );
        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Color', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .aae-clickdrop-btn' => 'color: {{VALUE}}',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'label_bg',
                'types' => ['classic', 'gradient', 'video'],
                'selector' => '{{WRAPPER}} .aae-clickdrop-btn',
            ]
        );
        // text menu style
        $this->add_control(
            'menu_text',
            [
                'label' => esc_html__('Menu text', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'menu_typography',
                'selector' => '{{WRAPPER}} .aae-clickdrop-modal ul li a span',
            ]
        );
        $this->add_control(
            'menu_color',
            [
                'label' => esc_html__('Color', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .aae-clickdrop-modal ul li a span' => 'color: {{VALUE}}',
                ],
            ]
        );
        $this->add_control(
            'menu_padding',
            [
                'label' => esc_html__('Padding', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                'default' => [
                    'top' => 2,
                    'right' => 0,
                    'bottom' => 2,
                    'left' => 0,
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .aae-clickdrop-modal ul li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        if (!is_user_logged_in()) {
            ?>
            <a href="<?php echo esc_url(wp_login_url()); ?>"
               class="aae-clickdrop-btn"><?php echo esc_html($settings['login_label']); ?></a>
            <?php
        } else {
            ?>
            <div class="aae-clickdrop-wrapper">
                <div class="aae-clickdrop-inner">
                    <button class="aae-clickdrop-btn"><?php echo $settings['logged_label']; ?></button>
                    <div class="aae-clickdrop-modal">
                        <ul>
                            <?php
                            foreach ($settings['menus_url'] as $item) {
                                ?>
                                <li>
                                    <?php
                                    $url = !empty($item['menu_link']['url']) ? $item['menu_link']['url'] : '#';
                                    $is_external = !empty($item['menu_link']['is_external']) ? ' target="_blank"' : '';
                                    $nofollow = !empty($item['menu_link']['nofollow']) ? ' rel="nofollow"' : '';
                                    ?>
                                    <a href="<?php echo esc_url($url); ?>"<?php echo $is_external . $nofollow; ?>>
                                        <?php
                                        if (!empty($item['menu_icon']['value'])) {
                                            \Elementor\Icons_Manager::render_icon($item['menu_icon'], ['aria-hidden' => 'true']);
                                        }
                                        ?>
                                        <span><?php echo esc_html($item['menu_title']); ?></span>
                                    </a>
                                </li>

                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <script>
            (function ($) {
                $(".aae-clickdrop-btn").click(function () {
                    $(".aae-clickdrop-modal").slideToggle();
                });
            })(jQuery);
        </script>
        <?php
    }
}
