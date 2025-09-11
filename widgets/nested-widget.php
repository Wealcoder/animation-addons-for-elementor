<?php
namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
use Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater;
use Elementor\Repeater;
use Elementor\Plugin;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use WCF_ADDONS\AAE_Nested_Slider_Trait;
class Nested_Widget extends Widget_Nested_Base {

    public function get_name() {
        return 'my_nested_widget';
    }

    public function get_title() {
        return __( 'My Nested Widget', 'my-plugin' );
    }

    public function show_in_panel() {
        // Only show this widget when nested elements experimental flag is active.
        return Plugin::$instance->experiments->is_feature_active( 'nested-elements' );
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_items',
            [
                'label' => __( 'Items', 'my-plugin' ),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // define repeater
        $repeater = new Repeater();
        $repeater->add_control(
            'item_title',
            [
                'label' => __( 'Title', 'my-plugin' ),
                'type' => Controls_Manager::TEXT,
                'default' => __( 'Item Title', 'my-plugin' ),
                'label_block' => true,
            ]
        );

        // Use nested repeater control
        $this->add_control(
            'items',
            [
                'label' => __( 'Items', 'my-plugin' ),
                'type' => Control_Nested_Repeater::CONTROL_TYPE,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [ 'item_title' => __( 'First Item', 'my-plugin' ) ],
                    [ 'item_title' => __( 'Second Item', 'my-plugin' ) ],
                ],
                'title_field' => '{{{ item_title }}}',
                'button_text' => __( 'Add Item', 'my-plugin' ),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        if ( ! Plugin::$instance->experiments->is_feature_active( 'nested-elements' ) ) {
            echo esc_html__( 'Nested Elements are not active. Please enable them via Elementor → Settings.', 'my-plugin' );
            return;
        }

        $items = $settings['items'] ?? [];

        echo '<div class="my-nested-widget">';
        foreach ( $items as $index => $item ) {
            // setup wrapper for editor to drop content children
            // something like this:
            echo '<div class="my-nested-item" data-repeater_index="' . esc_attr( $index ) . '">';
            
            // render just the title for now
            echo '<h3>' . esc_html( $item['item_title'] ) . '</h3>';

            // here you might output nested container so other widgets can be dropped
            // maybe using $this->get_item_data() etc (depending on version)

            echo '</div>';
        }
        echo '</div>';
    }
}
