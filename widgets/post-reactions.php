<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

if (! defined('ABSPATH')) exit;

class Post_Reactions extends Widget_Base
{

    public function get_name()
    {
        return 'aaeaddon-post-reactions';
    }

    public function get_title()
    {
        return __('Post Reactions', 'animation-addons-for-elementor');
    }

    public function get_icon()
    {
        return 'wcf eicon-facebook-like-box';
    }
    public function get_script_depends()
    {
        return ['wcf--post-reactions'];
    }

    /**
     * Requires css files.
     *
     * @return array
     */
    public function get_style_depends()
    {
        return ['wcf--post-reactions'];
    }

    public function get_categories()
    {
        return ['weal-coder-addon'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Reactions', 'animation-addons-for-elementor'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'reaction_label',
            [
                'label'       => __('Reaction Label', 'animation-addons-for-elementor'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('👍 Like', 'animation-addons-for-elementor'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'reaction_icon_type',
            [
                'label'   => __('Icon Type', 'animation-addons-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'emoji',
                'options' => [
                    'emoji'  => __('Emoji', 'animation-addons-for-elementor'),
                    'custom' => __('Custom Icon', 'animation-addons-for-elementor'),
                    'icon'   => __('Font Icon', 'animation-addons-for-elementor'),
                ],
            ]
        );

        $repeater->add_control(
            'reaction_type',
            [
                'label'   => __('Type', 'animation-addons-for-elementor'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'emoji',

                'options' => [
                    'like'  => __('Like', 'animation-addons-for-elementor'),
                    'dislike' => __('Dislike', 'animation-addons-for-elementor'),
                    'funny'   => __('Funny', 'animation-addons-for-elementor'),
                    'wow'   => __('Wow', 'animation-addons-for-elementor'),
                    'love'   => __('Love', 'animation-addons-for-elementor'),
                    'sad'   => __('Sad', 'animation-addons-for-elementor'),
                    'angry'   => __('Angry', 'animation-addons-for-elementor'),
                ],
            ]
        );



        // Icon control for Font Icon
        $repeater->add_control(
            'reaction_icon',
            [
                'label'       => __('Reaction Icon', 'animation-addons-for-elementor'),
                'type'        => \Elementor\Controls_Manager::ICONS,
                'default'     => [
                    'value' => 'fa fa-thumbs-up', // Default icon value (Font Awesome)
                    'library' => 'fa', // Font Awesome library
                ],
                'label_block' => true,
                'condition'   => [
                    'reaction_icon_type' => 'icon', // Only show if icon type is selected
                ],
            ]
        );

        $repeater->add_control(
            'reaction_custom_icon',
            [
                'label'       => __('Custom Icon', 'animation-addons-for-elementor'),
                'type'        => Controls_Manager::MEDIA,
                'default'     => [
                    'url' => '',
                ],
                'label_block' => true,
                'condition'   => [
                    'reaction_icon_type' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'reactions_list',
            [
                'label'       => __('Reactions', 'animation-addons-for-elementor'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    ['reaction_label' => '👍 Like', 'reaction_icon' => '👍', 'reaction_type' => 'like'],
                    ['reaction_label' => '❤️ Love', 'reaction_icon' => '❤️', 'reaction_type' => 'love'],
                    ['reaction_label' => '😂 Funny', 'reaction_icon' => '😂', 'reaction_type' => 'funny'],
                    ['reaction_label' => '😮 Wow', 'reaction_icon' => '😮', 'reaction_type' => 'wow'],
                    ['reaction_label' => '😢 Sad', 'reaction_icon' => '😢', 'reaction_type' => 'sad'],
                    ['reaction_label' => '😡 Angry', 'reaction_icon' => '😡', 'reaction_type' => 'angry'],
                    ['reaction_label' => '👎 Dislike', 'reaction_icon' => '👎', 'reaction_type' => 'dislike'], // Add Dislike
                ],
                'title_field' => '{{{ reaction_label }}}',
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => esc_html__('Show Level ?', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'animation-addons-for-elementor'),
                'label_off' => esc_html__('No', 'animation-addons-for-elementor'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );


        $this->add_control(
            'reaction_count',
            [
                'label' => esc_html__('Reaction Count?', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'animation-addons-for-elementor'),
                'label_off' => esc_html__('No', 'animation-addons-for-elementor'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'reaction_separator',
            [
                'label' => esc_html__('Separator ?', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'animation-addons-for-elementor'),
                'label_off' => esc_html__('No', 'animation-addons-for-elementor'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'separator_icon',
            [
                'label' => esc_html__('Separator Icon', 'animation-addons-for-elementor'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'condition'   => [
                    'reaction_separator' => 'yes', // Only show if icon type is selected
                ],
            ]
        );

        $this->end_controls_section();

        // Style Controls for Buttons

        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Reactions Button', 'animation-addons-for-elementor'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'reaction_filter_typography',
                'selector' => '{{WRAPPER}} .aaeaddon-reaction-btn',
            ]
        );

        $this->add_responsive_control(
            'flex_dir',
            [
                'label' => esc_html__('Display Direction', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'row' => [
                        'title' => esc_html__('row', 'animation-addons-for-elementor'),
                        'icon' => 'eicon-ellipsis-h',
                    ],
                    'column' => [
                        'title' => esc_html__('column', 'animation-addons-for-elementor'),
                        'icon' => 'eicon-ellipsis-v',
                    ],
                ],
                'default' => 'row',
                'toggle' => true,
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn' => 'flex-direction: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'reaction_gap',
            [
                'label'     => __('Gap', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'     => [
                    'px' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 1,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn'  => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'reaction_btn_bg_color',
            [
                'label'     => __('Background', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f1f1f1',
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'reaction_btn_padding',
            [
                'label'     => __('Padding', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );


        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'selector' => '{{WRAPPER}} .aaeaddon-reaction-btn',
            ]
        );

        $this->add_responsive_control(
            'reaction_btn_border_radius',
            [
                'label'     => __('Border Radius', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );


        $this->start_controls_tabs(
            'tabs_react_more',
        );

        $this->start_controls_tab(
            'tab_react_more_normal',
            [
                'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
            ]
        );

        $this->add_control(
            'reaction_btn_color',
            [
                'label'     => __('Text Color', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000',
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_load_more_hover',
            [
                'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
            ]
        );

        $this->add_control(
            'reaction_btn_hover_bg_color',
            [
                'label'     => __('Background Color', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e1e1e1',
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'reaction_btn_hover_color',
            [
                'label'     => __('Text Color', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000',
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'load_more_hover_border_color',
            [
                'label'     => esc_html__('Border Color', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // Style Controls for Reaction Icon

        $this->start_controls_section(
            'reaction_icon_style',
            [
                'label' => __('Reaction Icon', 'animation-addons-for-elementor'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'label'     => __('Label Size', 'animation-addons-for-elementor'),
                'selector' => '{{WRAPPER}} .aaeaddon-reaction-btn span.aae-reaction-label',
            ]
        );
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'counter_typography',
                'label'     => __('Counter Size', 'animation-addons-for-elementor'),
                'selector' => '{{WRAPPER}} .aaeaddon-reaction-btn span.aae-reaction-count',
            ]
        );
        $this->add_responsive_control(
            'reaction_icon_size',
            [
                'label'     => __('Icon Size', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'     => [
                    'px' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 1,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn img'  => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .aaeaddon-reaction-btn span' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'reaction_icon_margin',
            [
                'label'     => __('Margin', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .aaeaddon-reaction-btn img' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Controls for Separator Icon

        $this->start_controls_section(
            'separator_icon_style',
            [
                'label' => __('Separator Icon', 'animation-addons-for-elementor'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'reaction_separator' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'separator_icon_size',
            [
                'label'     => __('Icon Size', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'     => [
                    'px' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 1,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .aae-reaction-separator' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'separator_icon_color',
            [
                'label'     => __('Color', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000',
                'selectors' => [
                    '{{WRAPPER}} .aae-reaction-separator' => 'color: {{VALUE}};fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'separator_icon_margin',
            [
                'label'     => __('Margin', 'animation-addons-for-elementor'),
                'type'      => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .aae-reaction-separator' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $reactions = $settings['reactions_list'];
        $reaction_count = $settings['reaction_count'];
        $reactions_count = get_post_meta(get_the_id(), 'aaeaddon_post_reactions', true);
        $reaction_separator = $settings['reaction_separator'];
        if (! empty($reactions)) {
            echo '<div class="aaeaddon-post-reactions">';
            foreach ($reactions as $reaction) {
                // Check if the reaction icon is set to use an icon
                if ('icon' === $reaction['reaction_icon_type']) {
                    // Render the selected icon (Font Awesome or custom)
                    ob_start();
                    // Render the icon and capture the output
                    \Elementor\Icons_Manager::render_icon($reaction['reaction_icon'], ['aria-hidden' => 'true', 'class' => 'aaeaddon-reaction-icon']);
                    // Get the buffered content and store it in a variable
                    $icon = ob_get_clean();
                } elseif ('custom' === $reaction['reaction_icon_type'] && ! empty($reaction['reaction_custom_icon']['url'])) {
                    // Handle custom icon (if available)
                    $icon = '<img src="' . esc_url($reaction['reaction_custom_icon']['url']) . '" alt="' . esc_attr($reaction['reaction_label']) . '">';
                } else {
                    // Fallback for text-based reactions
                    $icon = $reaction['reaction_icon'] ?? '';
                }
                $count = is_array($reactions_count) && isset($reactions_count[$reaction['reaction_type']]) ? $reactions_count[$reaction['reaction_type']] : 0;
                if (function_exists('aaeaddon_format_number_count')) {
                    $count = aaeaddon_format_number_count($count);
                }

                $levelshow = $settings['show_title'];
                if ($levelshow == 'yes') {
                    $level_name = $reaction['reaction_label'];
                } else {
                    $level_name = '';
                }
                ob_start();
                \Elementor\Icons_Manager::render_icon($settings['separator_icon'], ['aria-hidden' => 'true']);
                $sep = ob_get_clean();

                // Display the reaction button
                if ($reaction_count == 'yes') {
                    echo '<button class="aaeaddon-reaction-btn" data-reaction="' . esc_attr($reaction['reaction_label']) . '" data-rtype="' . esc_attr($reaction['reaction_type']) . '">';
                    // echo $icon;
                    echo "<span>" . wp_kses_post($icon) . "</span>";

                    echo '<span class="aae-reaction-label">' . esc_html($level_name) . '</span>';

                    if ($reaction_separator) {
                        echo '<span class="aae-reaction-separator">' . esc_html($sep) . '</span>';
                    }

                    echo '<span class="aae-reaction-count">' . esc_html($count) . '</span>';
                    echo '</button>';
                } else {
                    echo '<button class="aaeaddon-reaction-btn" data-reaction="' . esc_attr($reaction['reaction_label']) . '" data-rtype="' . esc_attr($reaction['reaction_type']) . '">';
                    echo wp_kses_post($icon) . esc_html($reaction['reaction_label']);
                    echo '</button>';
                }
            }
            echo '</div>';
        }
    }
}
