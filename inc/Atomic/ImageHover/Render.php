<?php
namespace WCF_ADDONS\Atomic\ImageHover;

use Elementor\Modules\AtomicWidgets\Utils\Image\Placeholder_Image;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Image Hover (Reveal-on-Hover) onto atomic widgets.
 *
 * Emits into its own `imghover` interactions namespace — frontend reads
 * `window.AAE_INTERACTIONS_IMGHOVER[<id>]`. Storage shape:
 *   {
 *     enabled:     true,
 *     enableEditor:true,
 *     imageUrl:    'https://.../img.jpg',
 *     zindex:      1,
 *     width:       300,
 *     height:      300,
 *     top:         0,
 *     left:        0,
 *     // per-bp variants when they differ from cascade:
 *     width_mobile:  200,
 *     ...
 *   }
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
		if ( ! in_array( $type, Schema::image_hover_widgets(), true ) ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		// Pull the image URL out of the Image_Prop_Type envelope. Either
		// `url` is set (external image) OR `id` is set (media library —
		// resolved via wp_get_attachment_image_url). Both populated is
		// rejected by the Image_Src_Prop_Type validator upstream.
		//
		// The effect activates as soon as the user picks a real image —
		// there's no separate Enable toggle. The schema defaults the URL
		// to Elementor's placeholder.svg (mandatory for validator); we
		// treat that exact URL as "not picked" and bail.
		$image_url = $this->extract_image_url( $settings[ Schema::IH_IMAGE ] ?? null );
		if ( '' === $image_url || $image_url === Placeholder_Image::get_placeholder_image() ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$extra_bps = $this->get_extra_breakpoints();
		$config    = [
			'enabled'  => true,
			'imageUrl' => $image_url,
		];

		// Non-responsive number — z-index.
		$zindex = $settings[ Schema::IH_ZINDEX ] ?? null;
		if ( is_array( $zindex ) && isset( $zindex['value'] ) && is_numeric( $zindex['value'] ) ) {
			$config['zindex'] = (int) $zindex['value'];
		}

		// Responsive numerics — px implicit.
		$this->emit_responsive(
			$config, $settings, Schema::IH_WIDTH, 'width', 300, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null
		);
		$this->emit_responsive(
			$config, $settings, Schema::IH_HEIGHT, 'height', 300, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null
		);
		$this->emit_responsive(
			$config, $settings, Schema::IH_TOP, 'top', 0, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null
		);
		$this->emit_responsive(
			$config, $settings, Schema::IH_LEFT, 'left', 0, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null
		);

		InteractionsMap::register( 'imghover', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-image-hover' );
		}
	}

	/**
	 * Unwrap the Image_Prop_Type envelope to a plain URL string. Elementor's
	 * Image_Src_Prop_Type validator forces EXACTLY ONE truthy key in {id, url}:
	 *   - internal media library image → id set, url empty (resolve via wp_get_attachment_image_url)
	 *   - external URL                  → url set, id empty (use as-is)
	 * Returns '' when neither is present.
	 */
	private function extract_image_url( $envelope ): string {
		if ( ! is_array( $envelope ) || ! isset( $envelope['value'] ) ) {
			return '';
		}
		$src = $envelope['value']['src'] ?? null;
		if ( ! is_array( $src ) || ! isset( $src['value'] ) ) {
			return '';
		}

		// Prefer explicit URL (external image).
		$url = $src['value']['url'] ?? null;
		if ( is_array( $url ) && isset( $url['value'] ) && is_string( $url['value'] ) && '' !== $url['value'] ) {
			return $url['value'];
		}

		// Fallback: internal media library image — id present, resolve to URL.
		$id_env = $src['value']['id'] ?? null;
		if ( is_array( $id_env ) && isset( $id_env['value'] ) && is_numeric( $id_env['value'] ) ) {
			$size = $envelope['value']['size']['value'] ?? 'full';
			$resolved = wp_get_attachment_image_url( (int) $id_env['value'], is_string( $size ) ? $size : 'full' );
			if ( is_string( $resolved ) && '' !== $resolved ) {
				return $resolved;
			}
		}

		return '';
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
