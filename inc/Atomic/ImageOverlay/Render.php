<?php
namespace WCF_ADDONS\Atomic\ImageOverlay;

use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Image Overlay config onto e-image / e-svg.
 *
 * Emits into its own `img_ovl` interactions namespace — frontend reads
 * `window.AAE_INTERACTIONS_IMG_OVL[<id>] = { enabled, type, color,
 * gradientColor1, gradientColor2, gradientAngle, opacity, blendMode,
 * ...<key>_<bp> variants, enableEditor? }`.
 *
 * The actual CSS (box-shadow for a solid tint, background-image +
 * mix-blend-mode for a gradient) is applied by the frontend runtime
 * (effects/image-overlay/index.js), not printed here — see that
 * file's header comment for why a real atomic STYLE prop can't do this for
 * a bare <img>.
 */
final class Render {
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	public function register(): void {
		// `elementor/frontend/before_render` fires for widgets AND containers;
		// e-svg without a Link renders as a <div>, so this covers both shapes.
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		$type = $element->get_element_type();
		if ( ! in_array( $type, Schema::target_element_types(), true ) ) {
			return;
		}

		// get_settings() (raw saved props); never get_atomic_settings() — see
		// Render notes in the other extensions for why.
		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map( $settings[ Schema::ENABLE ] ?? null );

		// Register only if at least one breakpoint enables the overlay.
		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$config = [
			'type'           => 'color',
			'color'          => '#000000',
			'gradientColor1' => '#000000',
			'gradientColor2' => '#ffffff',
			'gradientAngle'  => 180,
			'opacity'        => 50,
			'blendMode'      => 'multiply',
		];

		// Pre-compute which breakpoints have the overlay disabled, same
		// dedup pattern as Parallax/Tilt.
		$disabled_bps      = [];
		$enabled_resolved  = [ 'desktop' => $enabled_map['desktop'] ?? false ];
		foreach ( $extra_bps as $bp ) {
			$own            = $enabled_map[ $bp ] ?? null;
			$parent_enabled = $this->cascade_parent( $bp, $enabled_resolved, $enabled_map['desktop'] ?? false );
			$effective      = ( null === $own || '' === $own ) ? $parent_enabled : (bool) $own;
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		$cast_string = static fn( $v ) => is_string( $v ) ? $v : ( null === $v ? '' : (string) $v );
		$cast_float  = static fn( $v ) => is_numeric( $v ) ? (float) $v : null;

		$this->emit_responsive(
			$config, $settings, Schema::ENABLE, 'enabled', false, $extra_bps,
			static fn( $v ) => (bool) $v,
			$disabled_bps
		);
		$this->emit_responsive( $config, $settings, Schema::TYPE, 'type', 'color', $extra_bps, $cast_string, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::COLOR, 'color', '#000000', $extra_bps, $cast_string, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::GRADIENT_COLOR_1, 'gradientColor1', '#000000', $extra_bps, $cast_string, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::GRADIENT_COLOR_2, 'gradientColor2', '#ffffff', $extra_bps, $cast_string, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::GRADIENT_ANGLE, 'gradientAngle', 180, $extra_bps, $cast_float, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::OPACITY, 'opacity', 50, $extra_bps, $cast_float, $disabled_bps );
		$this->emit_responsive( $config, $settings, Schema::BLEND_MODE, 'blendMode', 'multiply', $extra_bps, $cast_string, $disabled_bps );

		// Non-responsive editor-replay flag.
		$editor = $settings[ Schema::ENABLE_EDITOR ] ?? null;
		if ( is_array( $editor ) && ! empty( $editor['value'] ) ) {
			$config['enableEditor'] = true;
		}

		InteractionsMap::register( 'img_ovl', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-image-overlay' );
		}
	}
}
