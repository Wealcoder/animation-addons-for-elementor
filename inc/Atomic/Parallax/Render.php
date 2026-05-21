<?php
namespace WCF_ADDONS\Atomic\Parallax;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Parallax (ScrollSmoother) config onto atomic widgets.
 *
 * Emits into its own `plx` interactions namespace — frontend reads
 * `window.AAE_INTERACTIONS_PLX[<id>] = { enabled, speed, lag,
 * enabled_tablet?, speed_tablet?, lag_tablet?, ... }`. Independent from the
 * `anim` namespace, so the parallax runtime can boot without loading the
 * animation runtime and vice versa.
 *
 * Per-breakpoint variants are emitted only when the cell has its own value
 * that differs from the cascaded parent — same wire shape as
 * RegularAnimation / TextAnimation, so the JS runtime can use the same
 * BP_CASCADE read.
 */
final class Render {

	public function register(): void {
		// `elementor/frontend/before_render` fires for widgets AND containers,
		// so e-flexbox / e-div-block get parallax too.
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		$type = $element->get_element_type();
		if ( ! in_array( $type, Bootstrap::target_element_types(), true ) ) {
			return;
		}

		// get_settings() (raw saved props); never get_atomic_settings() —
		// see Render notes in the other extensions for why.
		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$extra_bps    = $this->get_extra_breakpoints();
		$enabled_map  = $this->envelope_to_map( $settings[ Schema::PARALLAX_ENABLE ] ?? null );

		// Register only if at least one breakpoint enables parallax (after
		// cascade). Without this we'd emit empty `plx` entries for every
		// widget on the page.
		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$config = [
			'lag' => 0,
			'speed' => 0.9,
		];

		// Pre-compute which breakpoints have parallax disabled.
		$disabled_bps = [];
		$enabled_resolved = [ 'desktop' => $enabled_map['desktop'] ?? false ];
		foreach ( $extra_bps as $bp ) {
			$own = $enabled_map[ $bp ] ?? null;
			$parent_enabled = $this->cascade_parent( $bp, $enabled_resolved, $enabled_map['desktop'] ?? false );
			$effective = ( null === $own || '' === $own ) ? $parent_enabled : (bool) $own;
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		$this->emit_responsive(
			$config, $settings, Schema::PARALLAX_ENABLE, 'enabled', false, $extra_bps,
			static fn( $v ) => (bool) $v,
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::PARALLAX_SPEED, 'speed', 0.9, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null,
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::PARALLAX_LAG, 'lag', 0, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null,
			$disabled_bps
		);

		if ( empty( $config ) ) {
			return;
		}

		InteractionsMap::register( 'plx', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-animation' );
		}
	}

	/**
	 * Emit a desktop value + per-bp variants for one responsive Parallax
	 * field. Desktop is skipped when it equals $default (JS reader supplies
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

			// Skip per-bp emission when the user disabled parallax on this breakpoint.
			// The enabled key itself must still be emitted so the runtime knows it's disabled.
			if ( isset( $disabled_bps[ $bp ] ) && 'enabled' !== $cfg_key ) {
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

	/**
	 * True when desktop has parallax enabled OR any extra-bp has its own
	 * enabled=true override. Skip the cascade chain — a child bp inheriting
	 * `enabled=true` from a parent counts too because we walk the parents.
	 */
	private function any_breakpoint_enabled( array $enabled_map, array $extra_bps ): bool {
		if ( ( $enabled_map['desktop'] ?? false ) === true ) {
			return true;
		}
		foreach ( $extra_bps as $bp ) {
			if ( ( $enabled_map[ $bp ] ?? null ) === true ) {
				return true;
			}
		}
		return false;
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
