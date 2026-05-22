<?php

namespace WCF_ADDONS\Atomic\WrapperLink;

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
            ! is_object($element) ||
            ! method_exists(
                $element,
                'get_element_type'
            )
        ) {
            return;
        }

        if (
            ! in_array(
                $element->get_element_type(),
                Bootstrap::target_element_types(),
                true
            )
        ) {
            return;
        }

        $settings = method_exists(
            $element,
            'get_settings'
        )
            ? $element->get_settings()
            : [];

        $config =
            $this->build_config(
                $settings
            );

        if (
            empty($config['url'])
        ) {
            return;
        }

        $id = method_exists(
            $element,
            'get_id'
        )
            ? $element->get_id()
            : '';

        if (empty($id)) {
            return;
        }

        InteractionsMap::register(

            'wrapper-link',

            $id,

            $config
        );
    }

    private function build_config(
        array $settings
    ): array {

        $link =
            $settings[Schema::LINK]['value'] ?? [];

        $url =
            $link['destination']['value']
            ?? '';

        return [

            'url' => $url,

            'isExternal' =>
            ! empty($link['isTargetBlank']),

            'nofollow' => false,
        ];
    }
}
