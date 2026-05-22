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

        $extra_bps   = $this->get_extra_breakpoints();
        $enabled_map = $this->envelope_to_map($settings[Schema::TOOLTIP_ENABLE] ?? null);
       
        // Only register when at least one breakpoint has the effect enabled.
        if ( ! $this->any_breakpoint_enabled($enabled_map, $extra_bps) ) {
            return;
        }

        $cast_bool        = static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' );
        // Pre-compute which breakpoints have the effect disabled.
        $disabled_bps     = [];
        $enabled_resolved = [ 'desktop' => $cast_bool($enabled_map['desktop'] ?? false) ];
        foreach ( $extra_bps as $bp ) {
            $own            = $enabled_map[ $bp ] ?? null;
            $parent_enabled = $this->cascade_parent_enabled($bp, $enabled_resolved, $enabled_resolved['desktop']);
            $effective      = ( null === $own || '' === $own ) ? $parent_enabled : $cast_bool($own);
            $enabled_resolved[ $bp ] = $effective;
            if ( ! $effective ) {
                $disabled_bps[ $bp ] = true;
            }
        }

        $config = [];

        // Emit responsive enabled flag.
        $this->emit_responsive(
            $config, $settings, Schema::TOOLTIP_ENABLE, 'enabled', false, $extra_bps,
            $cast_bool,
            $disabled_bps
        );
        if ( ! isset($config['enabled']) ) {
            $config['enabled'] = $enabled_resolved['desktop'];
        }

        // Primitive Responsive Fields
        $this->emit_responsive(
            $config, $settings, Schema::TEXT, 'text', '', $extra_bps,
            static fn( $v ) => (string) $v,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::POSITION, 'position', 'top', $extra_bps,
            static fn( $v ) => (string) $v,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::TRIGGER, 'trigger', 'hover', $extra_bps,
            static fn( $v ) => (string) $v,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::BG, 'bg', '#000000', $extra_bps,
            static fn( $v ) => (string) $v,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::COLOR, 'color', '#ffffff', $extra_bps,
            static fn( $v ) => (string) $v,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::WIDTH, 'width', '200px', $extra_bps,
            static fn( $v ) => (string) $v,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::OFFSET, 'offset', 10, $extra_bps,
            static fn( $v ) => is_numeric($v) ? (float) $v : null,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::ARROW_ENABLE, 'arrowEnable', true, $extra_bps,
            $cast_bool,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::ANIMATION, 'animation', 'fade', $extra_bps,
            static fn( $v ) => (string) $v,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::DURATION, 'duration', 0.3, $extra_bps,
            static fn( $v ) => is_numeric($v) ? (float) $v : null,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::ARROW_SIZE, 'arrowSize', 10, $extra_bps,
            static fn( $v ) => is_numeric($v) ? (int) $v : null,
            $disabled_bps
        );

        $this->emit_responsive(
            $config, $settings, Schema::ALIGNMENT, 'alignment', 'center', $extra_bps,
            static fn( $v ) => (string) $v,
            $disabled_bps
        );

        // Border Radius object
        $this->emit_responsive_object(
            $config, $settings, Schema::BORDER_RADIUS, 'borderRadius',
            [
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
                'unit' => 'px',
                'isLinked' => true,
            ],
            $extra_bps,
            $disabled_bps
        );

        if ( (bool) $this->unwrap_primitive( $settings[ Schema::TOOLTIP_ENABLE_EDITOR ] ?? null, false ) ) {
            $config['enableEditor'] = true;
        }

        $id = method_exists($element, 'get_id') ? (string) $element->get_id() : '';
        if ( '' === $id ) {
            return;
        }

        InteractionsMap::register('advance_tooltip', $id, $config);

        if (! is_admin()) {
            wp_enqueue_script('aae-effect-advance-tooltip');
        }
    }

    private function emit_responsive(
        array &$config,
        array $settings,
        string $base_key,
        string $cfg_key,
        $default,
        array $extra_bps,
        callable $cast,
        array $disabled_bps = []
    ): void {
        $map = $this->envelope_to_map($settings[$base_key] ?? null);

        $desktop_raw   = $map['desktop'] ?? $default;
        $desktop_value = $cast($desktop_raw);

        if ( $desktop_value !== $cast($default) ) {
            $config[$cfg_key] = $desktop_value;
        }

        $resolved = [ 'desktop' => $desktop_value ];

        $is_enable_key = ('enabled' === $cfg_key || 'enable' === $cfg_key);

        foreach ( $extra_bps as $bp ) {
            $own_raw = $map[$bp] ?? null;
            $parent  = $is_enable_key
                ? $this->cascade_parent_enabled($bp, $resolved, $desktop_value)
                : $this->cascade_parent($bp, $resolved, $desktop_value);

            if ( null === $own_raw || '' === $own_raw ) {
                $resolved[$bp] = $parent;
                continue;
            }

            $own_value = $cast($own_raw);
            $resolved[$bp] = $own_value;

            if ( $own_value === $parent ) {
                continue;
            }

            if ( isset($disabled_bps[$bp]) && 'enabled' !== $cfg_key ) {
                continue;
            }

            $config[$cfg_key . '_' . $bp] = $own_value;
        }
    }

    private function emit_responsive_object(
        array &$config,
        array $settings,
        string $base_key,
        string $cfg_key,
        array $default,
        array $extra_bps,
        array $disabled_bps = []
    ): void {
        $map = $this->envelope_to_map($settings[$base_key] ?? null);

        $desktop_val = $map['desktop'] ?? null;
        if ( $desktop_val && is_array($desktop_val) ) {
            $config[$cfg_key] = $desktop_val;
        }

        foreach ( $extra_bps as $bp ) {
            if ( isset($disabled_bps[$bp]) ) {
                continue;
            }
            $bp_val = $map[$bp] ?? null;
            if ( $bp_val && is_array($bp_val) ) {
                $config[$cfg_key . '_' . $bp] = $bp_val;
            }
        }
    }

    private function envelope_to_map($envelope): array
    {
        if (! is_array($envelope) || ! isset($envelope['value']) || ! is_array($envelope['value'])) {
            return [];
        }
        return $envelope['value'];
    }

    private function any_breakpoint_enabled(array $enabled_map, array $extra_bps): bool
    {
        $cast = static fn($v) => is_bool($v) ? $v : ($v === 'yes' || $v === 'true' || $v === 1 || $v === '1');
        if ($cast($enabled_map['desktop'] ?? false)) {
            return true;
        }
        foreach ($extra_bps as $bp) {
            if ($cast($enabled_map[$bp] ?? null)) {
                return true;
            }
        }
        return false;
    }

    private function cascade_parent_enabled(string $bp, array $resolved, $desktop_value): bool
    {
        static $cascade = [
            'mobile'       => [ 'mobile_extra', 'tablet', 'tablet_extra', 'laptop' ],
            'mobile_extra' => [ 'tablet', 'tablet_extra', 'laptop' ],
            'tablet'       => [ 'tablet_extra', 'laptop' ],
            'tablet_extra' => [ 'laptop' ],
            'laptop'       => [],
            'widescreen'   => [],
        ];
        foreach ( $cascade[$bp] ?? [] as $step ) {
            if ( array_key_exists($step, $resolved) ) {
                $v = $resolved[$step];
                $v_bool = is_bool($v) ? $v : ($v === 'yes' || $v === 'true' || $v === 1 || $v === '1');
                if ( $v_bool ) {
                    return true;
                }
            }
        }
        $d_bool = is_bool($desktop_value) ? $desktop_value : ($desktop_value === 'yes' || $desktop_value === 'true' || $desktop_value === 1 || $desktop_value === '1');
        return $d_bool;
    }

    private function cascade_parent(string $bp, array $resolved, $desktop_value)
    {
        static $cascade = [
            'mobile'       => [ 'mobile_extra', 'tablet', 'tablet_extra', 'laptop' ],
            'mobile_extra' => [ 'tablet', 'tablet_extra', 'laptop' ],
            'tablet'       => [ 'tablet_extra', 'laptop' ],
            'tablet_extra' => [ 'laptop' ],
            'laptop'       => [],
            'widescreen'   => [],
        ];
        foreach ( $cascade[$bp] ?? [] as $step ) {
            if ( array_key_exists($step, $resolved) ) {
                return $resolved[$step];
            }
        }
        return $desktop_value;
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

    /**
     * Read a scalar out of a transformable envelope (plain primitive or
     * responsive). Plain primitives arrive as { $$type, value: <scalar> };
     * responsive props arrive as { $$type: 'aae-rj', value:
     * { desktop: <scalar>, … } } — desktop scalar is the dep-style read.
     */
    private function unwrap_primitive( $value, $fallback ) {
        if ( ! is_array( $value ) ) {
            return $value;
        }
        if ( ! array_key_exists( 'value', $value ) ) {
            return $fallback;
        }
        $inner = $value['value'];
        if ( is_array( $inner ) && array_key_exists( 'desktop', $inner ) ) {
            return $inner['desktop'];
        }
        return $inner;
    }
}
