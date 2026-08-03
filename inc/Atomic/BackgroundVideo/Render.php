<?php

namespace WCF_ADDONS\Atomic\BackgroundVideo;

use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background Video — publishes each container's config for the runtime.
 *
 * Emits into its own `bgv` namespace: the frontend reads
 * `window.AAE_INTERACTIONS_BGV[<id>] = { enabled, source, url, poster, … }`.
 *
 * No markup is produced here, and that is not a shortcut. e-flexbox /
 * e-div-block / e-grid are rendered from Elementor's OWN Twig templates, so
 * there is no seam to print a child <div> into — the alternative would be
 * buffering every container's output and splicing the layer in with a regex
 * (the Skip_Lazy pattern in Pro), which costs an ob_start() per container on
 * every page for a feature almost none of them use. The runtime injects the
 * layer instead; the video is decoration, so nothing is lost by it arriving a
 * tick after paint, and the poster covers that tick.
 *
 * Nothing is registered unless the switch is on AND a source resolves, so a
 * page with no background video ships no config and no script.
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

		if ( ! in_array( $element->get_element_type(), Schema::TARGET_TYPES, true ) ) {
			return;
		}

		// Raw saved props, never get_atomic_settings() — the resolver returns
		// values already transformed for CSS/Twig, which is the wrong shape for
		// the responsive envelopes read below.
		$settings = method_exists( $element, 'get_raw_data' )
			? ( $element->get_raw_data()['settings'] ?? [] )
			: [];

		if ( ! is_array( $settings ) || ! $this->unwrap_primitive( $settings[ Schema::ENABLE ] ?? null, false ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$config = $this->build_config( $settings );

		// Switched on but nothing to play at any breakpoint — registering would
		// make the runtime inject an empty layer over the container.
		if ( empty( $config['sources'] ) ) {
			return;
		}

		InteractionsMap::register( 'bgv', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-background-video' );
		}
	}

	/**
	 * @param array $settings Raw element settings.
	 * @return array Wire config for the runtime.
	 */
	private function build_config( array $settings ): array {
		$extra_bps = $this->get_extra_breakpoints();

		$cast_bool   = static fn( $v ) => is_bool( $v ) ? $v : ( 'yes' === $v || 'true' === $v || 1 === $v || '1' === $v );
		$cast_string = static fn( $v ) => is_string( $v ) ? $v : ( null === $v ? '' : (string) $v );

		$config = [ 'enabled' => true ];

		$this->emit_responsive( $config, $settings, Schema::SOURCE, 'source', Schema::SOURCE_FILE, $extra_bps, $cast_string );
		$this->emit_responsive( $config, $settings, Schema::LINK, 'link', '', $extra_bps, $cast_string );
		$this->emit_responsive( $config, $settings, Schema::PLAY_ONCE, 'playOnce', false, $extra_bps, $cast_bool );
		$this->emit_responsive( $config, $settings, Schema::PLAY_ON_MOBILE, 'playOnMobile', false, $extra_bps, $cast_bool );

		// Resolve each breakpoint's source down to ONE url here rather than
		// shipping the file object, the link and the source enum and making the
		// runtime re-derive the same answer three ways.
		$config['sources'] = $this->resolve_urls( $settings, $extra_bps, $cast_string );
		$config['posters'] = $this->resolve_media_urls( $settings, Schema::POSTER, $extra_bps );

		return $config;
	}

	/**
	 * breakpoint => playable url, for every breakpoint that resolves to one.
	 *
	 * @return array<string,string>
	 */
	private function resolve_urls( array $settings, array $extra_bps, callable $cast_string ): array {
		$source_map = $this->envelope_to_map( $settings[ Schema::SOURCE ] ?? null );
		$link_map   = $this->envelope_to_map( $settings[ Schema::LINK ] ?? null );
		$file_urls  = $this->resolve_media_urls( $settings, Schema::FILE, $extra_bps );

		$urls = [];

		foreach ( array_merge( [ 'desktop' ], $extra_bps ) as $bp ) {
			$source = $cast_string( $source_map[ $bp ] ?? '' );

			// An unset breakpoint inherits desktop's choice, matching how every
			// other responsive field cascades.
			if ( '' === $source ) {
				$source = 'desktop' === $bp
					? Schema::SOURCE_FILE
					: ( $cast_string( $source_map['desktop'] ?? '' ) ?: Schema::SOURCE_FILE );
			}

			$url = Schema::SOURCE_URL === $source
				? esc_url_raw( trim( $cast_string( $link_map[ $bp ] ?? ( 'desktop' === $bp ? '' : ( $link_map['desktop'] ?? '' ) ) ) ) )
				: ( $file_urls[ $bp ] ?? ( 'desktop' === $bp ? '' : ( $file_urls['desktop'] ?? '' ) ) );

			if ( '' !== $url ) {
				$urls[ $bp ] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Pull `url` out of a responsive media prop, per breakpoint.
	 *
	 * @return array<string,string>
	 */
	private function resolve_media_urls( array $settings, string $key, array $extra_bps ): array {
		$map  = $this->envelope_to_map( $settings[ $key ] ?? null );
		$urls = [];

		foreach ( array_merge( [ 'desktop' ], $extra_bps ) as $bp ) {
			$value = $map[ $bp ] ?? null;

			if ( is_array( $value ) && ! empty( $value['url'] ) && is_string( $value['url'] ) ) {
				$urls[ $bp ] = esc_url_raw( $value['url'] );
			} elseif ( is_string( $value ) && '' !== $value ) {
				// Older saves / a hand-edited value that is already a bare URL.
				$urls[ $bp ] = esc_url_raw( $value );
			}
		}

		return $urls;
	}
}
