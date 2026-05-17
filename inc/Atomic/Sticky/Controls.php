<?php

namespace WCF_ADDONS\Atomic\Sticky;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Controls {

    const TD = 'animation-addons-for-elementor';

    public function register(): void {

        add_filter(
            'elementor/atomic-widgets/controls',
            [ $this, 'inject_controls' ],
            10,
            2
        );
    }

    public function inject_controls( array $controls, $element ) {

        if (
            ! is_object( $element ) ||
            ! method_exists( $element, 'get_element_type' )
        ) {
            return $controls;
        }

        $type = $element->get_element_type();

        if ( in_array( $type, Bootstrap::target_element_types(), true ) ) {
            $controls[] = $this->build_sticky_section();
        }

        return $controls;
    }

    private function build_sticky_section(): Section {

        return Section::make()
            ->set_label( __( 'Sticky', self::TD ) )
            ->set_items([

                // Enable Sticky
                Switch_Control::bind_to( Schema::STICKY_ENABLE )
                    ->set_label( __( 'Enable Sticky', self::TD ) ),

                // Pin Trigger
                Select_Control::bind_to( Schema::STICKY_PIN_TRIGGER )
                    ->set_label( __( 'Pin Trigger', self::TD ) )
                    ->set_options([
                        [
                            'value' => 'default',
                            'label' => __( 'Default', self::TD ),
                        ],
                        [
                            'value' => 'custom',
                            'label' => __( 'Custom', self::TD ),
                        ],
                    ]),

                // Custom Pin Area
                Text_Control::bind_to( Schema::STICKY_CUSTOM_PIN_AREA )
                    ->set_label( __( 'Custom Pin Area', self::TD ) ),

                // Pin End Trigger
                Select_Control::bind_to( Schema::STICKY_PIN_END_TRIGGER )
                    ->set_label( __( 'Pin End Trigger', self::TD ) )
                    ->set_options([
                        [
                            'value' => 'default',
                            'label' => __( 'Default', self::TD ),
                        ],
                        [
                            'value' => 'custom',
                            'label' => __( 'Custom', self::TD ),
                        ],
                    ]),

                // Custom Pin End Area
                Text_Control::bind_to( Schema::STICKY_CUSTOM_PIN_END_AREA )
                    ->set_label( __( 'Custom Pin End Area', self::TD ) ),

            ]);
    }
}