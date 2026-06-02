<?php

namespace WCF_ADDONS\Atomic\Traits;

if (! defined('ABSPATH')) {
	exit;
}

trait Responsive_Config
{
	/**
	 * Emit a desktop value + per-bp variants for one responsive field. 
	 * Desktop is skipped when it equals $default (JS reader supplies
	 * the default); per-bp is skipped when it equals the cascaded parent.
	 * $cast converts the stored cell to its emitted type (bool / float).
	 */
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
		$map = $this->envelope_to_map( $settings[ $base_key ] ?? null );

		$desktop_raw   = $map['desktop'] ?? $default;
		$desktop_value = $cast( $desktop_raw );

		if ( $desktop_value !== $cast( $default ) ) {
			$config[ $cfg_key ] = $desktop_value;
		}

		$resolved = [ 'desktop' => $desktop_value ];
		$is_enable_key = ( 'enabled' === $cfg_key || 'enable' === $cfg_key );

		foreach ( $extra_bps as $bp ) {
			$own_raw = $map[ $bp ] ?? null;
			$parent  = $is_enable_key
				? $this->cascade_parent_enabled($bp, $resolved, $desktop_value)
				: $this->cascade_parent($bp, $resolved, $desktop_value);

			if ( null === $own_raw || '' === $own_raw ) {
				$resolved[ $bp ] = $parent;
				continue;
			}

			$own_value = $cast( $own_raw );
			$resolved[ $bp ] = $own_value;

			if ( $own_value === $parent ) {
				continue;
			}

			if ( isset( $disabled_bps[ $bp ] ) && ! $is_enable_key ) {
				continue;
			}

			$config[ $cfg_key . '_' . $bp ] = $own_value;
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

	/** Pull the breakpoint→primitive map out of a Responsive_Json envelope. */
	private function envelope_to_map( $envelope ): array {
		if ( ! is_array( $envelope ) || ! isset( $envelope['value'] ) || ! is_array( $envelope['value'] ) ) {
			return [];
		}
		return $envelope['value'];
	}

	/**
	 * True when desktop has effect enabled OR any extra-bp has its own
	 * enabled=true override. Handles Elementor's string booleans 'yes'/'true'/'1'.
	 */
	private function any_breakpoint_enabled( array $enabled_map, array $extra_bps ): bool {
		$cast = static fn($v) => is_bool($v) ? $v : ($v === 'yes' || $v === 'true' || $v === 1 || $v === '1');
		if ( $cast( $enabled_map['desktop'] ?? false ) ) {
			return true;
		}
		foreach ( $extra_bps as $bp ) {
			if ( $cast( $enabled_map[ $bp ] ?? null ) ) {
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

	/** Mirror of common.js BP_CASCADE for dedup decisions. */
	private function cascade_parent( string $bp, array $resolved, $desktop_value ) {
		static $cascade = [
			'mobile'       => [ 'mobile_extra', 'tablet', 'tablet_extra', 'laptop' ],
			'mobile_extra' => [ 'tablet', 'tablet_extra', 'laptop' ],
			'tablet'       => [ 'tablet_extra', 'laptop' ],
			'tablet_extra' => [ 'laptop' ],
			'laptop'       => [],
			'widescreen'   => [],
		];
		foreach ( $cascade[ $bp ] ?? [] as $step ) {
			if ( array_key_exists( $step, $resolved ) ) {
				return $resolved[ $step ];
			}
		}
		return $desktop_value;
	}

	/**
	 * Active extra-breakpoint keys (non-desktop), largest→smallest. Falls
	 * back to tablet+mobile when Elementor's Breakpoints manager isn't loaded.
	 */
	private function get_extra_breakpoints(): array {
		$active_keys = [];

		if ( class_exists( \Elementor\Plugin::class )
			&& isset( \Elementor\Plugin::$instance->breakpoints )
			&& method_exists( \Elementor\Plugin::$instance->breakpoints, 'get_active_breakpoints' ) ) {
			$active_keys = array_keys( \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints() );
		}

		if ( empty( $active_keys ) ) {
			$active_keys = [ 'tablet', 'mobile' ];
		}

		static $order = [ 'widescreen', 'laptop', 'tablet_extra', 'tablet', 'mobile_extra', 'mobile' ];
		$ordered = [];
		foreach ( $order as $bp ) {
			if ( in_array( $bp, $active_keys, true ) ) {
				$ordered[] = $bp;
			}
		}
		return $ordered;
	}

	private function read_primitive( array $settings, string $key, $default ) {
		$value = $settings[ $key ] ?? null;
		if ( null === $value ) {
			return $default;
		}
		if ( ! is_array( $value ) || ! array_key_exists( 'value', $value ) ) {
			return ( null === $value || '' === $value ) ? $default : $value;
		}
		return ( null === $value['value'] || '' === $value['value'] ) ? $default : $value['value'];
	}

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
