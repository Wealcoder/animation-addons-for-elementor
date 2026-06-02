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
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

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

		$cast_bool        = static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' );
		// Pre-compute which breakpoints have the effect disabled.
		$disabled_bps     = [];
		$enabled_resolved = [ 'desktop' => $cast_bool( $enabled_map['desktop'] ?? false ) ];
		foreach ( $extra_bps as $bp ) {
			$own            = $enabled_map[ $bp ] ?? null;
			$parent_enabled = $this->cascade_parent_enabled( $bp, $enabled_resolved, $enabled_resolved['desktop'] );
			$effective      = ( null === $own || '' === $own ) ? $parent_enabled : $cast_bool( $own );
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		// Pull the image URL from the JS-managed media field (aae_ih_image).
		// Shape stored by MediaInput: { id, url, size, sizes } as a cell of a Responsive_Json_Prop_Type envelope.
		$media_map = $this->envelope_to_map( $settings[ Schema::IH_IMAGE ] ?? null );
		$desktop_media = $media_map['desktop'] ?? null;
		$desktop_url   = $this->get_image_url_from_media( $desktop_media );

		$image_resolved = [ 'desktop' => $desktop_url ];
		$image_config   = [];
		if ( '' !== $desktop_url ) {
			$image_config['imageUrl'] = $desktop_url;
		}

		foreach ( $extra_bps as $bp ) {
			$own_media = $media_map[ $bp ] ?? null;
			$own_url   = $this->get_image_url_from_media( $own_media );
			$parent    = $this->cascade_parent( $bp, $image_resolved, $desktop_url );

			if ( '' === $own_url ) {
				$image_resolved[ $bp ] = $parent;
				continue;
			}

			$image_resolved[ $bp ] = $own_url;
			if ( $own_url === $parent ) {
				continue;
			}

			if ( isset( $disabled_bps[ $bp ] ) ) {
				continue;
			}

			$image_config['imageUrl_' . $bp] = $own_url;
		}

		// Make sure we have at least one resolved image URL.
		$has_any_image = false;
		foreach ( $image_resolved as $url ) {
			if ( '' !== $url && ! $this->is_placeholder_url( $url ) ) {
				$has_any_image = true;
				break;
			}
		}

		if ( ! $has_any_image ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$config = $image_config;

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

	private function get_image_url_from_media( $media ): string {
		if ( ! is_array( $media ) ) {
			return '';
		}
		if ( isset( $media['url'] ) && '' !== $media['url'] ) {
			return (string) $media['url'];
		}
		if ( isset( $media['id'] ) && is_numeric( $media['id'] ) ) {
			$url = wp_get_attachment_image_url( (int) $media['id'], $media['size'] ?? 'full' );
			return $url ? (string) $url : '';
		}
		return '';
	}

	private function is_placeholder_url( string $url ): bool {
		if ( '' === $url ) {
			return false;
		}
		$suffix = 'placeholder-v4.svg';
		return substr( $url, -strlen( $suffix ) ) === $suffix;
	}
}
