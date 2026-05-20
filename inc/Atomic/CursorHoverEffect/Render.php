<?php

namespace WCF_ADDONS\Atomic\CursorHoverEffect;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Render {

	public function register(): void {

		add_action(
			'elementor/frontend/before_render',
			[ $this, 'maybe_register' ]
		);
	}

	public function maybe_register(
		$element
	): void {

		if (
			! is_object( $element ) ||
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

		if ( empty( $config['enabled'] ) ) {
			return;
		}

		$id = method_exists(
			$element,
			'get_id'
		)
			? $element->get_id()
			: '';

		if ( empty( $id ) ) {
			return;
		}

		InteractionsMap::register(

			/*
			|--------------------------------------------------------------------------
			| CHANGE THIS
			|--------------------------------------------------------------------------
			*/

			'cursor-hover-effect',

			$id,

			$config
		);
	}

	private function build_config(
		array $settings
	): array {
		$enabled = $settings[Schema::ENABLE_EDITOR] ?? false;
		if ( is_array( $enabled ) && isset( $enabled['value'] ) ) {
			$enabled = (bool) $enabled['value'];
		}
		if ( ! $enabled ) {
			return [];
		}

		$extra_bps = $this->get_extra_breakpoints();
		$config    = [
			'enabled' => true,
		];

		// Strings/presets (responsive)
		$this->emit_responsive( $config, $settings, Schema::TEXT, 'text', '', $extra_bps, static fn( $v ) => (string) $v );	
		$this->emit_responsive( $config, $settings, Schema::COLOR, 'color', '#ffffff', $extra_bps, static fn( $v ) => (string) $v );
		$this->emit_responsive( $config, $settings, Schema::BACKGROUND, 'background', '#000000', $extra_bps, static fn( $v ) => (string) $v );

		$dimension_cast = static function( $v ) {
			if ( is_array( $v ) ) {
				$size = $v['size'] ?? ( $v['value'] ?? '' );
				$unit = $v['unit'] ?? 'px';
				if ( '' === $size || null === $size ) {
					return '';
				}
				return $size . $unit;
			}
			return (string) $v;
		};

		$this->emit_responsive( $config, $settings, Schema::WIDTH, 'width', '', $extra_bps, $dimension_cast );
		$this->emit_responsive( $config, $settings, Schema::HEIGHT, 'height', '', $extra_bps, $dimension_cast );
		$this->emit_responsive( $config, $settings, Schema::BORDER, 'border', '1px solid #ffffff', $extra_bps, static fn( $v ) => (string) $v );

		// objects (responsive)
	
		$this->emit_responsive_object( $config, $settings, Schema::BORDER_RADIUS, 'borderRadius', $extra_bps );

		return $config;
	}

	private function emit_responsive(
		array &$config,
		array $settings,
		string $base_key,
		string $cfg_key,
		$default,
		array $extra_bps,
		callable $cast
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
			$config[ $cfg_key . '_' . $bp ] = $own_value;
		}
	}

	private function emit_responsive_object(
		array &$config,
		array $settings,
		string $base_key,
		string $cfg_key,
		array $extra_bps
	): void {
		$map = $this->envelope_to_map( $settings[ $base_key ] ?? null );
		$desktop_raw = $map['desktop'] ?? null;

		if ( ! empty( $desktop_raw ) ) {
			$config[ $cfg_key ] = $desktop_raw;
		}

		$resolved = [ 'desktop' => $desktop_raw ];

		foreach ( $extra_bps as $bp ) {
			$own_raw = $map[ $bp ] ?? null;
			$parent  = $this->cascade_parent( $bp, $resolved, $desktop_raw );

			if ( null === $own_raw || '' === $own_raw ) {
				$resolved[ $bp ] = $parent;
				continue;
			}

			$resolved[ $bp ] = $own_raw;

			if ( $own_raw === $parent ) {
				continue;
			}
			$config[ $cfg_key . '_' . $bp ] = $own_raw;
		}
	}

	private function envelope_to_map( $envelope ): array {
		if ( ! is_array( $envelope ) || ! isset( $envelope['value'] ) || ! is_array( $envelope['value'] ) ) {
			return [];
		}
		return $envelope['value'];
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
}