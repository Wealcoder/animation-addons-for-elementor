<?php

namespace WCF_ADDONS\Atomic\MouseMoveEffect;

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

	public function maybe_register( $element ): void {

		if (! is_object($element) ||! method_exists($element,'get_element_type'	)) {
			return;
		}

		if (! in_array(	$element->get_element_type(),Bootstrap::target_element_types(),	true)	) {
			return;
		}

		$settings = method_exists(	$element,'get_settings'	)? $element->get_settings()	: [];

		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map( $settings[ Schema::ENABLE ] ?? null );
		
		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$id = method_exists($element,'get_id')	? $element->get_id() : '';

		if (empty($id)) {
			return;
		}

		$config = $this->build_config($settings,	$extra_bps,	$enabled_map );

		if (empty($config)) {
			return;
		}

		InteractionsMap::register(
			'mouse_move_effect',
			$id,
			$config
		);

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-mouse-move' );
		}
	}

	private function build_config(
		array $settings,
		array $extra_bps,
		array $enabled_map
	): array {
		$config = [];

		$cast_bool = static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' );
		$cast_string = static fn( $v ) => is_string( $v ) ? $v : (null === $v ? '' : (string) $v);

		// Pre-compute which breakpoints have mouse-move disabled.
		$disabled_bps = [];
		$enabled_resolved = [ 'desktop' => $cast_bool( $enabled_map['desktop'] ?? false ) ];
		foreach ( $extra_bps as $bp ) {
			$own = $enabled_map[ $bp ] ?? null;
			$parent_enabled = $this->cascade_parent( $bp, $enabled_resolved, $enabled_resolved['desktop'] );
			$effective = ( null === $own || '' === $own ) ? $parent_enabled : $cast_bool( $own );
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		$config['enable_editor'] = (bool) $this->unwrap_primitive( $settings[ Schema::ENABLE_EDITOR ] ?? null, false );

		$this->emit_responsive(
			$config, $settings, Schema::ENABLE, 'enable', false, $extra_bps,
			$cast_bool,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::MOVEMENT_WRAPPER, 'movement_wrapper', 'default', $extra_bps,
			$cast_string,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::MOVE_X, 'move_x', '100', $extra_bps,
			$cast_string,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::MOVE_Y, 'move_y', '100', $extra_bps,
			$cast_string,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::DURATION, 'duration', '1', $extra_bps,
			$cast_string,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::CUSTOMS, 'customs', '', $extra_bps,
			$cast_string,
			$disabled_bps
		);

		// Custom Properties repeater
		$custom_envelope = $settings[ Schema::CUSTOM_PROPS ] ?? null;
		$custom_map      = $this->envelope_to_map( $custom_envelope );

		$desktop_pairs = $this->custom_rows_to_pairs( $custom_map['desktop'] ?? [] );
		if ( ! empty( $desktop_pairs ) ) {
			$config['customProps'] = $desktop_pairs;
		}

		foreach ( $extra_bps as $bp ) {
			if ( ! array_key_exists( $bp, $custom_map ) || null === $custom_map[ $bp ] ) {
				continue;
			}
			$bp_pairs = $this->custom_rows_to_pairs( $custom_map[ $bp ] );
			if ( $bp_pairs === $desktop_pairs ) {
				continue;
			}
			$config[ 'customProps_' . $bp ] = $bp_pairs;
		}

		// Ensure the enable flag is always present for runtime if it wasn't written
		if ( ! isset( $config['enable'] ) ) {
			$config['enable'] = $enabled_resolved['desktop'];
		}

		return $config;
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
		$map = $this->envelope_to_map( $settings[ $base_key ] ?? null );

		$desktop_raw   = $map['desktop'] ?? $default;
		$desktop_value = $cast( $desktop_raw );

		if ( $desktop_value !== $cast( $default ) ) {
			$config[ $cfg_key ] = $desktop_value;
		}

		$resolved = [ 'desktop' => $desktop_value ];

		foreach ( $extra_bps as $bp ) {
			$own_raw = $map[ $bp ] ?? null;
			$parent  = $this->cascade_parent( $bp, $resolved, $desktop_value );

			if ( null === $own_raw || '' === $own_raw ) {
				$resolved[ $bp ] = $parent;
				continue;
			}

			$own_value = $cast( $own_raw );
			$resolved[ $bp ] = $own_value;

			if ( $own_value === $parent ) {
				continue;
			}

			// Skip per-bp emission when the user disabled mouse move on this breakpoint.
			// The enable key itself must still be emitted so the runtime knows it's disabled.
			if ( isset( $disabled_bps[ $bp ] ) && 'enable' !== $cfg_key ) {
				continue;
			}

			$config[ $cfg_key . '_' . $bp ] = $own_value;
		}
	}

	private function any_breakpoint_enabled( array $enabled_map, array $extra_bps ): bool {
		$cast = static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' );
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

	private function envelope_to_map( $envelope ): array {
		if ( ! is_array( $envelope ) || ! isset( $envelope['value'] ) || ! is_array( $envelope['value'] ) ) {
			return [];
		}
		return $envelope['value'];
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

	private function cascade_parent( string $bp, array $resolved, $desktop_value ) {
		static $cascade = [
			'mobile_extra' => [ 'mobile', 'tablet' ],
			'mobile'       => [ 'tablet' ],
			'tablet_extra' => [ 'tablet' ],
			'tablet'       => [],
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

	/**
	 * Filter a per-bp rows array into the emitted runtime contract: an array
	 * of { k, v } pairs. Rows are skipped when enabled=false, property is
	 * empty, or property === 'none'.
	 */
	private function custom_rows_to_pairs( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return [];
		}
		$pairs = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$enabled = $row['enabled'] ?? true;
			if ( false === $enabled ) {
				continue;
			}
			$k = is_string( $row['property'] ?? null ) ? trim( $row['property'] ) : '';
			if ( '' === $k || 'none' === $k ) {
				continue;
			}
			$v = is_string( $row['value'] ?? null ) ? trim( $row['value'] ) : '';
			$pairs[] = [ 'k' => $k, 'v' => $v ];
		}
		return $pairs;
	}
}
