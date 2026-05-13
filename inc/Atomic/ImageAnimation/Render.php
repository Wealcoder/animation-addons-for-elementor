<?php
namespace WCF_ADDONS\Atomic\ImageAnimation;

use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Image Animation onto e-image / e-svg widgets.
 *
 * Emits into its own `img` interactions namespace — frontend reads
 * `window.AAE_INTERACTIONS_IMG[<id>]`. The runtime composes a tween from
 * `(effect, startFrom, scaleStart, scaleEnd, startPos, customStart, ease)`,
 * same pattern as RegularAnimation's fade/move composition.
 *
 * Trigger model is hardcoded per-effect on the JS side:
 *   - reveal  → scroll-tied (fires once on enter)
 *   - scale   → scrub (progress follows scroll position)
 *   - stretch → pinned scrub (top top → bottom bottom+=100)
 */
final class Render {

	public function register(): void {
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		$type = $element->get_element_type();
		if ( ! in_array( $type, Schema::image_animation_widgets(), true ) ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$config = $this->build_config( $settings );
		if ( empty( $config ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		InteractionsMap::register( 'img', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-image-animation' );
		}
	}

	private function build_config( array $settings ): array {
		$effect_map = $this->envelope_to_map( $settings[ Schema::IMG_EFFECT ] ?? null );
		$extra_bps  = $this->get_extra_breakpoints();

		// Bail early if no breakpoint has an effect other than 'none'.
		if ( ! $this->any_breakpoint_active( $effect_map, $extra_bps ) ) {
			return [];
		}

		$config = [];

		// Effect — always emit so the runtime sees it. Other fields are
		// gated by effect family below.
		$this->emit_responsive(
			$config, $settings, Schema::IMG_EFFECT, 'effect', 'none', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'none'
		);
		if ( ! isset( $config['effect'] ) ) {
			$config['effect'] = $effect_map['desktop'] ?? 'none';
		}

		$desktop_effect = $effect_map['desktop'] ?? 'none';

		// reveal family — start_from + ease
		if ( $this->family_used( $effect_map, $extra_bps, [ 'reveal' ] ) ) {
			$this->emit_responsive(
				$config, $settings, Schema::IMG_START_FROM, 'startFrom', 'right', $extra_bps,
				static fn( $v ) => is_string( $v ) ? $v : 'right'
			);
			$this->emit_responsive(
				$config, $settings, Schema::IMG_EASE, 'ease', 'power2.out', $extra_bps,
				static fn( $v ) => is_string( $v ) ? $v : 'power2.out'
			);
		}

		// scale family — scaleStart + scaleEnd
		if ( $this->family_used( $effect_map, $extra_bps, [ 'scale' ] ) ) {
			$this->emit_responsive(
				$config, $settings, Schema::IMG_SCALE_START, 'scaleStart', 0.5, $extra_bps,
				static fn( $v ) => is_numeric( $v ) ? (float) $v : null
			);
			$this->emit_responsive(
				$config, $settings, Schema::IMG_SCALE_END, 'scaleEnd', 1.0, $extra_bps,
				static fn( $v ) => is_numeric( $v ) ? (float) $v : null
			);
		}

		// scale + reveal share the start position picker + custom override.
		if ( $this->family_used( $effect_map, $extra_bps, [ 'scale', 'reveal' ] ) ) {
			$this->emit_responsive(
				$config, $settings, Schema::IMG_START_POS, 'startPos', 'top center', $extra_bps,
				static fn( $v ) => is_string( $v ) ? $v : 'top center'
			);

			// Emit customStart only when desktop start_pos === 'custom'. The
			// runtime falls back to startPos otherwise.
			$start_pos_map = $this->envelope_to_map( $settings[ Schema::IMG_START_POS ] ?? null );
			if ( ( $start_pos_map['desktop'] ?? '' ) === 'custom' ) {
				$this->emit_responsive(
					$config, $settings, Schema::IMG_CUSTOM_START, 'customStart', 'top 90%', $extra_bps,
					static fn( $v ) => is_string( $v ) ? $v : 'top 90%'
				);
			}
		}

		// Non-responsive editor flag.
		$editor = $settings[ Schema::IMG_ENABLE_EDITOR ] ?? null;
		if ( is_array( $editor ) && ! empty( $editor['value'] ) ) {
			$config['enableEditor'] = true;
		}

		return $config;
	}

	/** True when at least one breakpoint has effect ≠ 'none' / empty. */
	private function any_breakpoint_active( array $effect_map, array $extra_bps ): bool {
		$desktop = $effect_map['desktop'] ?? 'none';
		if ( $desktop && 'none' !== $desktop ) {
			return true;
		}
		foreach ( $extra_bps as $bp ) {
			$v = $effect_map[ $bp ] ?? null;
			if ( $v && 'none' !== $v ) {
				return true;
			}
		}
		return false;
	}

	/** True when any breakpoint's effect falls in $family (after cascade). */
	private function family_used( array $effect_map, array $extra_bps, array $family ): bool {
		$resolved = [ 'desktop' => $effect_map['desktop'] ?? 'none' ];
		if ( in_array( $resolved['desktop'], $family, true ) ) {
			return true;
		}
		foreach ( $extra_bps as $bp ) {
			$own = $effect_map[ $bp ] ?? null;
			$parent = $this->cascade_parent( $bp, $resolved, $resolved['desktop'] );
			$resolved[ $bp ] = ( null === $own || '' === $own ) ? $parent : $own;
			if ( in_array( $resolved[ $bp ], $family, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Emit a desktop value + per-bp variants for one responsive field.
	 * Mirrors Parallax/Render.php (feature-agnostic helper).
	 */
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

	/** Pull the breakpoint→primitive map out of a Responsive_Json envelope. */
	private function envelope_to_map( $envelope ): array {
		if ( ! is_array( $envelope ) || ! isset( $envelope['value'] ) || ! is_array( $envelope['value'] ) ) {
			return [];
		}
		return $envelope['value'];
	}

	/** Mirror of common.js BP_CASCADE for dedup decisions. */
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
}
