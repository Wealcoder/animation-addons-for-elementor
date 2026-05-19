<?php

namespace WCF_ADDONS\Atomic\Sticky;

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

	public function maybe_register( $element ): void {

		if (
			! is_object( $element ) ||
			! method_exists( $element, 'get_element_type' )
		) {
			return;
		}

		if (
			! in_array(
				$element->get_element_type(),
				Schema::targeted_elements(),
				true
			)
		) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$config = $this->build_config( $settings );

		if ( empty( $config['enable'] ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' )
			? $element->get_id()
			: '';

		if ( empty( $id ) ) {
			return;
		}

		InteractionsMap::register(
			'sticky',
			$id,
			$config
		);

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-sticky' );
		}
	}

	private function build_config( array $settings ): array {
		$config = [];
		$extra_bps  = $this->get_extra_breakpoints();

		$this->emit_responsive(
			$config, $settings, Schema::STICKY_ENABLE, 'enable', false, $extra_bps,
			static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' )
		);

		if ( empty( $config['enable'] ) ) {
			return [];
		}

		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_TRIGGER, 'pinTrigger', 'default', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'default'
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_CUSTOM_PIN_AREA, 'customPinArea', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : ''
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_END_TRIGGER, 'pinEndTrigger', 'default', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'default'
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_CUSTOM_PIN_END_AREA, 'customPinEndArea', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : ''
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN, 'pin', true, $extra_bps,
			static fn( $v ) => $v === 'custom' ? 'custom' : ( is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' ) )
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_CUSTOM_PIN, 'customPin', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : ''
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_START, 'pinStart', 'top top', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'top top'
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_CUSTOM_PIN_START, 'customPinStart', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : ''
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_END, 'pinEnd', 'bottom bottom', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'bottom bottom'
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_CUSTOM_PIN_END, 'customPinEnd', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : ''
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_SPACING, 'pinSpacing', true, $extra_bps,
			static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' )
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_MARKERS, 'pinMarkers', false, $extra_bps,
			static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' )
		);

		// Emit border object
		$border_envelope = $settings[ Schema::STICKY_BORDER ] ?? null;
		$border_map      = $this->envelope_to_map( $border_envelope );

		$border_desktop = $border_map['desktop'] ?? null;
		if ( $border_desktop && is_array( $border_desktop ) && ! empty( $border_desktop['style'] ) && $border_desktop['style'] !== 'none' ) {
			$config['border'] = $border_desktop;
		}

		foreach ( $extra_bps as $bp ) {
			$bp_border = $border_map[ $bp ] ?? null;
			if ( $bp_border && is_array( $bp_border ) && ! empty( $bp_border['style'] ) && $bp_border['style'] !== 'none' ) {
				$config[ 'border_' . $bp ] = $bp_border;
			}
		}

		// Emit custom CSS (plain string)
		$custom_css = $this->read_primitive( $settings, Schema::STICKY_CUSTOM_CSS, '' );
		if ( ! empty( $custom_css ) ) {
			$config['customCSS'] = $custom_css;
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
}