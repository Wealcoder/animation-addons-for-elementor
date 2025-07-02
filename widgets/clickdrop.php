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
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Heading', 'animation-addons-for-elementor'),
            ]
        );

        $this->add_control(
            'menus_url',
            [
                'label' => esc_html__('Add Menu', 'animation-addons-for-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'menu_title',
                        'label' => esc_html__('Menu Title', 'animation-addons-for-elementor'),
                        'type' => Controls_Manager::TEXT,
                        'default' => esc_html__('Menu Title', 'animation-addons-for-elementor'),
                        'label_block' => true,
                    ],                    [
                        'name' => 'menu_link',
                        'label' => esc_html__('Menu URL', 'animation-addons-for-elementor'),
                        'type' => Controls_Manager::TEXT,
                        'default' => esc_html__('List Title', 'animation-addons-for-elementor'),
                        'label_block' => true,
                    ],
                    [
                        'name' => 'menu_icon', // ✅ Added name key here
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
    }


    protected function render()
    {

        $settings = $this->get_settings_for_display();

        ?>
        <div class="aae-clickdrop-wrapper">
            <div class="aae-clickdrop-inner">
                <button class="aae-clickdrop-btn">login</button>
                <div class="aae-clickdrop-modal">
                    <ul>
                        <?php

                        foreach ($settings['menus_url'] as $item) {
                            ?>
                            <li>
                                <a href="<?php echo esc_url( $item['menu_link'] ); ?>">
                                    <?php
                                    if ( ! empty( $item['menu_icon']['value'] ) ) {
                                        \Elementor\Icons_Manager::render_icon( $item['menu_icon'], [ 'aria-hidden' => 'true' ] );
                                    }
                                    ?>
                                    <span><?php echo esc_html( $item['menu_title'] ); ?></span>
                                </a>
                            </li>

                            <?php
                        }

                        ?>
                    </ul>
                </div>
            </div>
        </div>
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
