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
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

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
			wp_enqueue_script( 'aae-effect-parallax' );
		}
	}
}
