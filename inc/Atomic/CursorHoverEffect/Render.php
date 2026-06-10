<?php

namespace WCF_ADDONS\Atomic\CursorHoverEffect;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Render {
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

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

		$settings = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];

		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map( $settings[ Schema::ENABLE ] ?? null );
		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? $element->get_id() : '';

		if ( empty( $id ) ) {
			return;
		}

		$config = $this->build_config( $settings, $extra_bps, $enabled_map );

		if ( empty( $config ) ) {
			return;
		}

		InteractionsMap::register(
			'cursor_hover_effect',
			$id,
			$config
		);

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-cursor-hover' );
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

		// Pre-compute which breakpoints have cursor-hover disabled.
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
			$config, $settings, Schema::ENABLE, 'enabled', false, $extra_bps,
			$cast_bool,
			$disabled_bps
		);

		// Strings/presets (responsive)
		$this->emit_responsive( $config, $settings, Schema::TEXT, 'text', '', $extra_bps, $cast_string, $disabled_bps );	
		$this->emit_responsive( $config, $settings, Schema::COLOR, 'color', '#ffffff', $extra_bps, $cast_string, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::BACKGROUND, 'background', '#000000', $extra_bps, $cast_string, $disabled_bps );

		$dimension_cast = static function( $v ) {
			if ( is_array( $v ) ) {
				$size = $v['size'] ?? ( $v['value'] ?? '' );
				$unit = $v['unit'] ?? 'px';
				if ( '' === $size || null === $size ) {
					return '';
				}
				return $size . $unit;
			}
			// Plain number from slider (no units) — append 'px'
			if ( is_numeric( $v ) ) {
				return $v . 'px';
			}
			return (string) $v;
		};

		$this->emit_responsive( $config, $settings, Schema::WIDTH, 'width', '', $extra_bps, $dimension_cast, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::HEIGHT, 'height', '', $extra_bps, $dimension_cast, $disabled_bps );

		// Font size — slider may save {size, unit} object or plain number
		$this->emit_responsive( $config, $settings, Schema::FONT_SIZE, 'fontSize', '', $extra_bps, $dimension_cast, $disabled_bps );

		// Padding — dimension control saves {size, unit} object
		$this->emit_responsive( $config, $settings, Schema::PADDING, 'padding', '', $extra_bps, $dimension_cast, $disabled_bps );

		// Border object (responsive) — contains style, width, color, radius
		$this->emit_responsive_object( $config, $settings, Schema::BORDER, 'border', $extra_bps, $disabled_bps );

		// Border Radius object (responsive)
		$this->emit_responsive_object( $config, $settings, Schema::BORDER_RADIUS, 'borderRadius', $extra_bps, $disabled_bps );

		// Ensure the enabled flag is always present for runtime if it wasn't written
		if ( ! isset( $config['enabled'] ) ) {
			$config['enabled'] = $enabled_resolved['desktop'];
		}

		return $config;
	}
}