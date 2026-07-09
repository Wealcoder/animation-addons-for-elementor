<?php
namespace WCF_ADDONS\Atomic\Lightbox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auto-attaches the Lightbox to core `e-image` elements.
 *
 * Uses the universal `elementor/frontend/before_render` hook (same as
 * RegularAnimation / ImageHover) and keys the config off the element id — the
 * same id Elementor exposes as `data-interaction-id` on the rendered tag. No
 * HTML transformation is needed: the JS runtime looks up
 * `window.AAE_INTERACTIONS_LB[<interactionId>]` and treats any element that
 * carries a matching entry as a lightbox trigger.
 *
 * Custom AAE widgets do NOT go through here — they call
 * {@see Lightbox_Manager::get_attributes()} directly.
 */
final class Render {

	public function register(): void {
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		if ( 'e-image' !== $element->get_element_type() ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];

		// TEMP DEBUG — remove after diagnosis.
		if ( defined( 'AAE_LB_DEBUG' ) && AAE_LB_DEBUG ) {
			error_log( '[AAE-LB] e-image seen. enable_raw=' . wp_json_encode( $settings[ Schema::LB_ENABLE ] ?? '(unset)' ) );
			error_log( '[AAE-LB] image_raw=' . wp_json_encode( $settings['image'] ?? '(unset)' ) );
		}

		if ( ! Lightbox_Manager::is_enabled( $settings ) ) {
			if ( defined( 'AAE_LB_DEBUG' ) && AAE_LB_DEBUG ) {
				error_log( '[AAE-LB] BAILED: is_enabled() false' );
			}
			return;
		}

		$img = $this->resolve_image( $settings );
		if ( '' === $img['url'] ) {
			if ( defined( 'AAE_LB_DEBUG' ) && AAE_LB_DEBUG ) {
				error_log( '[AAE-LB] BAILED: no image url resolved' );
			}
			return;
		}

		if ( defined( 'AAE_LB_DEBUG' ) && AAE_LB_DEBUG ) {
			error_log( '[AAE-LB] OK publishing. url=' . $img['url'] . ' id=' . ( method_exists( $element, 'get_id' ) ? $element->get_id() : '?' ) );
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		// get_attributes() publishes the config to InteractionsMap('lb', $id, …)
		// and enqueues the runtime; keyed by element id so the rendered tag's
		// own data-interaction-id becomes the trigger. We discard the returned
		// attribute array here because the element already carries the id — the
		// runtime binds via the map, not a spliced attribute.
		Lightbox_Manager::get_attributes(
			$settings,
			[
				'src'     => $img['url'],
				'title'   => $this->str( $settings, Schema::LB_TITLE, $img['title'] ),
				'caption' => $this->str( $settings, Schema::LB_CAPTION, $img['caption'] ),
				'alt'     => $img['alt'],
				'thumb'   => $img['thumb'],
				'gallery' => $this->str( $settings, Schema::LB_GROUP, '' ),
				'type'    => 'image',
			],
			$id
		);
	}

	/**
	 * Resolve the full-size image for a core e-image element. The element's
	 * image prop is an Image_Prop_Type ({ id, url, ... }); we prefer the
	 * full-size URL (lightbox shows the large image, not the thumbnail).
	 *
	 * @return array{url:string,thumb:string,title:string,caption:string,alt:string}
	 */
	private function resolve_image( array $settings ): array {
		$out = [ 'url' => '', 'thumb' => '', 'title' => '', 'caption' => '', 'alt' => '' ];

		// The core atomic image stores its media under the `image` prop as an
		// Image_Prop_Type: { src: { id, url }, size }. The raw get_settings()
		// value may be wrapped in a { $$type, value } envelope; peel it, then
		// descend into the nested `src` shape (also possibly enveloped).
		$image = $this->unwrap( $settings['image'] ?? ( $settings['src'] ?? null ) );
		if ( ! is_array( $image ) ) {
			return $out;
		}

		// `src` holds the media reference; older/looser shapes may put id/url
		// directly on `image`, so fall back to $image itself.
		$src_shape = $this->unwrap( $image['src'] ?? null );
		if ( ! is_array( $src_shape ) ) {
			$src_shape = $image;
		}

		$attach_id = null;
		if ( isset( $src_shape['id'] ) && is_numeric( $src_shape['id'] ) ) {
			$attach_id = (int) $src_shape['id'];
		}

		// Thumbnail / inline URL = whatever the element renders.
		$thumb = '';
		$url_field = $this->unwrap( $src_shape['url'] ?? null );
		if ( is_string( $url_field ) ) {
			$thumb = $url_field;
		} elseif ( is_array( $url_field ) && isset( $url_field['url'] ) ) {
			$thumb = (string) $url_field['url'];
		} elseif ( isset( $src_shape['url'] ) && is_string( $src_shape['url'] ) ) {
			$thumb = (string) $src_shape['url'];
		}

		$full = '';
		if ( $attach_id ) {
			$full           = (string) ( wp_get_attachment_image_url( $attach_id, 'full' ) ?: '' );
			$out['caption'] = (string) wp_get_attachment_caption( $attach_id );
			$out['title']   = (string) get_the_title( $attach_id );
			$alt            = get_post_meta( $attach_id, '_wp_attachment_image_alt', true );
			$out['alt']     = is_string( $alt ) ? $alt : '';
		}

		$out['url']   = '' !== $full ? $full : $thumb;
		$out['thumb'] = '' !== $thumb ? $thumb : $out['url'];

		return $out;
	}

	/** Peel a { $$type, value } atomic envelope down to its inner value. */
	private function unwrap( $v ) {
		if ( is_array( $v ) && array_key_exists( 'value', $v ) && array_key_exists( '$$type', $v ) ) {
			return $v['value'];
		}
		return $v;
	}

	private function str( array $settings, string $key, string $fallback ): string {
		$v = $settings[ $key ] ?? '';
		if ( is_array( $v ) ) {
			$v = $v['value'] ?? '';
		}
		$v = is_string( $v ) ? trim( $v ) : '';
		return '' !== $v ? $v : $fallback;
	}
}
