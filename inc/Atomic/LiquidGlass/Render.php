<?php
namespace Wealcoder\AnimationAddons\Atomic\LiquidGlass;

use Wealcoder\AnimationAddons\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Liquid Glass config of a container into the `lg` interactions
 * namespace: window.AAE_INTERACTIONS_LG[<id>] = { enabled, preset, blur,
 * distortion, shadow, borderWidth, borderColor, radius, ...<key>_<bp> }.
 *
 * Every value is whitelisted or clamped here; the runtime
 * (effects/liquid-glass/index.js) turns it into CSS.
 */
final class Render {
	use \Wealcoder\AnimationAddons\Atomic\Traits\Responsive_Config;

	public function register(): void {
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		if ( ! in_array( $element->get_element_type(), Schema::target_element_types(), true ) ) {
			return;
		}

		$settings    = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];
		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map( $settings[ Schema::ENABLE ] ?? null );

		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$disabled_bps     = [];
		$enabled_resolved = [ 'desktop' => $enabled_map['desktop'] ?? false ];
		foreach ( $extra_bps as $bp ) {
			$own                     = $enabled_map[ $bp ] ?? null;
			$parent_enabled          = $this->cascade_parent( $bp, $enabled_resolved, $enabled_map['desktop'] ?? false );
			$effective               = ( null === $own || '' === $own ) ? $parent_enabled : (bool) $own;
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		$presets = Schema::presets();
		$shadows = Schema::shadows();

		$cast_preset = static fn( $v ) => in_array( $v, $presets, true ) ? $v : 'frosted';
		$cast_shadow = static fn( $v ) => in_array( $v, $shadows, true ) ? $v : 'soft-glow';
		$clamp       = static fn( float $min, float $max ) => static fn( $v ) => is_numeric( $v ) ? max( $min, min( $max, (float) $v ) ) : null;
		$cast_color  = static fn( $v ) => ( is_string( $v ) && preg_match( '/^(#[0-9a-f]{3,8}|(rgb|hsl)a?\(\s*[0-9.%,\s\/-]+\)|[a-z]+)$/i', trim( $v ) ) ) ? trim( $v ) : '';

		$config = [];

		$this->emit_responsive( $config, $settings, Schema::ENABLE, 'enabled', false, $extra_bps, static fn( $v ) => (bool) $v, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::PRESET, 'preset', 'frosted', $extra_bps, $cast_preset, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::BLUR, 'blur', 0, $extra_bps, $clamp( 0, 50 ), $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::DISTORTION, 'distortion', 100, $extra_bps, $clamp( 0, 300 ), $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::SHADOW, 'shadow', 'soft-glow', $extra_bps, $cast_shadow, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::BORDER_WIDTH, 'borderWidth', 1, $extra_bps, $clamp( 0, 20 ), $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::BORDER_COLOR, 'borderColor', 'rgba(255,255,255,0.35)', $extra_bps, $cast_color, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::RADIUS, 'radius', 0, $extra_bps, $clamp( 0, 500 ), $disabled_bps );

		$editor = $settings[ Schema::ENABLE_EDITOR ] ?? null;
		if ( is_array( $editor ) && ! empty( $editor['value'] ) ) {
			$config['enableEditor'] = true;
		}

		InteractionsMap::register( 'lg', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-liquid-glass' );
		}
	}
}
