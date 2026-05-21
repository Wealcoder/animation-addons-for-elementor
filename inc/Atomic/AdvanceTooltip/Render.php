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
            empty($config['enabled']['desktop'])
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

            'advance-tooltip',

            $id,

            $config
        );
    }

    private function build_config(
        array $settings
    ): array {
    echo "<pre>";
    var_dump($settings);
    echo "</pre>";
        return [
            'enabled' =>
            $this->emit_responsive(
                $settings[Schema::TOOLTIP_ENABLE] ?? [],
                false
            ),

            'text' =>
            $this->emit_responsive(
                $settings[Schema::TEXT] ?? [],
                ''
            ),

            'position' =>
            $this->emit_responsive(
                $settings[Schema::POSITION] ?? [],
                'top'
            ),

            'trigger' =>
            $this->emit_responsive(
                $settings[Schema::TRIGGER] ?? [],
                'hover'
            ),

            'bg' =>
            $this->emit_responsive(
                $settings[Schema::BG] ?? [],
                '#000000'
            ),

            'color' =>
            $this->emit_responsive(
                $settings[Schema::COLOR] ?? [],
                '#ffffff'
            ),

            'width' =>
            $this->emit_responsive(
                $settings[Schema::WIDTH] ?? [],
                '200px'
            ),

            'offset' =>
            $this->emit_responsive(
                $settings[Schema::OFFSET] ?? [],
                10
            ),

            'arrowEnable' =>
            $this->emit_responsive(
                $settings[Schema::ARROW_ENABLE] ?? [],
                true
            ),

            'animation' =>
            $this->emit_responsive(
                $settings[Schema::ANIMATION] ?? [],
                'fade'
            ),

            'duration' =>
            $this->emit_responsive(
                $settings[Schema::DURATION] ?? [],
                0.3
            ),

            'arrowSize' =>
            $this->emit_responsive(
                $settings[Schema::ARROW_SIZE] ?? [],
                10
            ),

            'alignment' =>
            $this->emit_responsive(
                $settings[Schema::ALIGNMENT] ?? [],
                'center'
            ),

            'borderRadius' =>
            $this->emit_responsive_object(
                $settings[Schema::BORDER_RADIUS] ?? [],
                [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px',
                    'isLinked' => true,
                ]
            ),
        ];
    }

    private function emit_responsive(
        $value,
        $fallback = null
    ): array {

        $map =
            $this->envelope_to_map(
                $value
            );

        $bps = array_merge(
            ['desktop'],
            $this->get_extra_breakpoints()
        );

        $out = [];

        foreach ($bps as $bp) {

            $current =
                $map[$bp] ?? null;

            if (
                null === $current ||
                '' === $current
            ) {
                $current =
                    $this->cascade_parent(
                        $map,
                        $bp
                    );
            }

            if (
                null === $current ||
                '' === $current
            ) {
                $current = $fallback;
            }

            $out[$bp] = $current;
        }

        return $out;
    }

    private function emit_responsive_object(
        $value,
        array $fallback = []
    ): array {

        $map =
            $this->envelope_to_map(
                $value
            );

        $bps = array_merge(
            ['desktop'],
            $this->get_extra_breakpoints()
        );

        $out = [];

        foreach ($bps as $bp) {

            $current =
                $map[$bp] ?? null;

            if (
                empty($current)
            ) {
                $current =
                    $this->cascade_parent(
                        $map,
                        $bp
                    );
            }

            if (
                empty($current)
            ) {
                $current = $fallback;
            }

            $out[$bp] = $current;
        }

        return $out;
    }

    private function envelope_to_map(
        $value
    ): array {

        if (
            is_array($value) &&
            isset($value['value']) &&
            is_array($value['value'])
        ) {
            return $value['value'];
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    private function cascade_parent(
        array $map,
        string $bp
    ) {

        $order = array_merge(
            ['desktop'],
            $this->get_extra_breakpoints()
        );

        $index =
            array_search(
                $bp,
                $order,
                true
            );

        if (false === $index) {
            return null;
        }

        while ($index > 0) {

            $index--;

            $parent =
                $order[$index];

            if (
                isset($map[$parent]) &&
                '' !== $map[$parent] &&
                null !== $map[$parent]
            ) {
                return $map[$parent];
            }
        }

        return null;
    }

    private function get_extra_breakpoints(): array
    {

        $bps = [];

        if (
            ! class_exists(
                '\Elementor\Plugin'
            )
        ) {
            return [
                'tablet',
                'mobile',
            ];
        }

        $manager =
            \Elementor\Plugin::$instance
            ->breakpoints;

        if (
            ! $manager ||
            ! method_exists(
                $manager,
                'get_active_breakpoints'
            )
        ) {
            return [
                'tablet',
                'mobile',
            ];
        }

        $active =
            $manager->get_active_breakpoints();

        foreach ($active as $bp) {

            $key =
                method_exists($bp, 'get_name')
                ? $bp->get_name()
                : null;

            if (
                $key &&
                'desktop' !== $key
            ) {
                $bps[] = $key;
            }
        }

        return $bps;
    }
}
