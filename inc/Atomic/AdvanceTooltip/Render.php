<?php

namespace WCF_ADDONS\Atomic\AdvanceTooltip;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if (! defined('ABSPATH')) {
    exit;
}

final class Render
{

    public function register(): void
    {

        add_action(
            'elementor/frontend/before_render',
            [$this, 'maybe_register']
        );
    }

    public function maybe_register(
        $element
    ): void {

        if (
            ! in_array(
                $element->get_element_type(),
                Bootstrap::target_element_types(),
                true
            )
        ) {
            return;
        }

        $settings =
            $element->get_settings();

        $config =
            $this->build_config(
                $settings
            );

        if (empty($config['enabled'])) {
            return;
        }

        InteractionsMap::register(

            'advance-tooltip',

            $element->get_id(),

            $config
        );
    }

    private function build_config(
        array $settings
    ): array {

        return [

            'enabled' =>
            $settings[Schema::TOOLTIP_ENABLE] ?? false,

            'text' =>
            $settings[Schema::TEXT] ?? [],

            'position' =>
            $settings[Schema::POSITION] ?? [],

            'trigger' =>
            $settings[Schema::TRIGGER] ?? [],

            'bg' =>
            $settings[Schema::BG] ?? [],

            'color' =>
            $settings[Schema::COLOR] ?? [],

            'width' =>
            $settings[Schema::WIDTH] ?? [],

            'offset' =>
            $settings[Schema::OFFSET] ?? [],

            'arrow' =>
            $settings[Schema::ARROW] ?? true,

            'animation' =>
            $settings[Schema::ANIMATION] ?? [],

            'duration' =>
            $settings[Schema::DURATION] ?? [],
        ];
    }
}
