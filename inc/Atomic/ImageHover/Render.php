<?php
namespace WCF_ADDONS\Atomic\ImageHover;

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

		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map( $settings[ Schema::IH_ENABLE ] ?? null );

		// Only register when at least one breakpoint has the effect enabled.
		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		// Pull the image URL from the JS-managed media field (aae_ih_image).
		// Shape stored by MediaInput: { id, url, size, sizes } as the desktop
		// cell of a Responsive_Json_Prop_Type envelope.
		$media_map = $this->envelope_to_map( $settings[ Schema::IH_IMAGE ] ?? null );
		$media     = $media_map['desktop'] ?? null;
		$image_url = ( is_array( $media ) && isset( $media['url'] ) && '' !== $media['url'] )
			? (string) $media['url']
			: ( ( is_array( $media ) && isset( $media['id'] ) && is_numeric( $media['id'] ) )
				? (string) wp_get_attachment_image_url( (int) $media['id'], $media['size'] ?? 'full' )
				: '' );

		if ( '' === $image_url ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$cast_bool        = static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' );
		// Pre-compute which breakpoints have the effect disabled.
		$disabled_bps     = [];
		$enabled_resolved = [ 'desktop' => $cast_bool( $enabled_map['desktop'] ?? false ) ];
		foreach ( $extra_bps as $bp ) {
			$own            = $enabled_map[ $bp ] ?? null;
			$parent_enabled = $this->cascade_parent( $bp, $enabled_resolved, $enabled_resolved['desktop'] );
			$effective      = ( null === $own || '' === $own ) ? $parent_enabled : $cast_bool( $own );
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		$config = [
			'imageUrl' => $image_url,
		];

		// Emit responsive enabled flag (desktop baseline + per-bp variants).
		$this->emit_responsive(
			$config, $settings, Schema::IH_ENABLE, 'enabled', false, $extra_bps,
			$cast_bool,
			$disabled_bps
		);
		if ( ! isset( $config['enabled'] ) ) {
			$config['enabled'] = $enabled_resolved['desktop'];
		}

		// Responsive numerics.
		$this->emit_responsive(
			$config, $settings, Schema::IH_ZINDEX, 'zindex', 1, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (int) $v : null,
			$disabled_bps
		);

		// Responsive numerics — px implicit.
		$this->emit_responsive(
			$config, $settings, Schema::IH_WIDTH, 'width', 300, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null,
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::IH_HEIGHT, 'height', 300, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null,
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::IH_TOP, 'top', 0, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null,
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::IH_LEFT, 'left', 0, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null,
			$disabled_bps
		);

		InteractionsMap::register( 'imghover', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-image-hover' );
		}
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

			if ( isset( $disabled_bps[ $bp ] ) && 'enabled' !== $cfg_key ) {
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

	/** True when desktop is enabled OR any extra-bp has its own enabled=true override. */
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

	private function cascade_parent( string $bp, array $resolved, $desktop_value ) {
		static $cascade = [
			'mobile'       => [ 'mobile_extra', 'tablet', 'tablet_extra', 'laptop' ],
			'mobile_extra' => [ 'tablet', 'tablet_extra', 'laptop' ],
			'tablet'       => [ 'tablet_extra', 'laptop' ],
			'tablet_extra' => [ 'laptop' ],
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
