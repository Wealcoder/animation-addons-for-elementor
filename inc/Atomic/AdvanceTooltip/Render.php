<?php

namespace WCF_ADDONS\Atomic\AdvanceTooltip;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if (! defined('ABSPATH')) {
    exit;
}

final class Render
{
    use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

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
}
