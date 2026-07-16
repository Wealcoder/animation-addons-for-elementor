<?php
namespace WCF_ADDONS\Atomic\Lightbox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} 

/**
 * Normalizes a caller-supplied trigger descriptor into the flat config object
 * the JS runtime consumes from `window.AAE_INTERACTIONS_LB[<id>]`.
 *
 * Phase 1 ships the `image` type. The registry shape is future-proof: adding
 * video/iframe/html/ajax means registering a new normalizer via the
 * `aae/lightbox/content_types` filter — no change to callers or the runtime
 * dispatch.
 *
 * A normalizer is a callable: fn( array $item, array $settings ): array
 * returning the runtime slide config (must include at least `type` + `src`).
 */
final class Content_Type_Registry {

	/** @var array<string, callable>|null */
	private static $normalizers = null;

	private static function normalizers(): array {
		if ( null !== self::$normalizers ) {
			return self::$normalizers;
		}

		$defaults = [
			'image' => [ __CLASS__, 'normalize_image' ],
		];

		/**
		 * Register or override Lightbox content-type normalizers.
		 *
		 * @param array<string, callable> $normalizers type => fn($item,$settings):array
		 */
		self::$normalizers = apply_filters( 'aae/lightbox/content_types', $defaults );

		return self::$normalizers;
	}

	/**
	 * Turn a descriptor into a runtime slide config.
	 *
	 * @param array $item     { src, title, caption, gallery, type, ... }
	 * @param array $settings Raw widget settings (for defaults like anim).
	 */
	public static function normalize( array $item, array $settings = [] ): array {
		$type = $item['type'] ?? 'auto';
		if ( 'auto' === $type ) {
			$type = self::detect_type( $item );
		}

		$normalizers = self::normalizers();
		$fn          = $normalizers[ $type ] ?? $normalizers['image'];

		$config = call_user_func( $fn, $item, $settings );

		$config['type'] = $config['type'] ?? $type;
		if ( ! empty( $item['gallery'] ) ) {
			$config['group'] = (string) $item['gallery'];
		}
		if ( isset( $settings[ Schema::LB_ANIM ] ) && '' !== $settings[ Schema::LB_ANIM ] ) {
			$config['anim'] = (string) $settings[ Schema::LB_ANIM ];
		}

		/**
		 * Final mutation of a per-element Lightbox config before it is
		 * published to the interactions map.
		 *
		 * @param array $config Runtime slide config.
		 * @param array $item   Original descriptor.
		 */
		return apply_filters( 'aae/lightbox/config', $config, $item );
	}

	private static function detect_type( array $item ): string {
		$src = (string) ( $item['src'] ?? '' );
		if ( preg_match( '#(youtube\.com|youtu\.be|vimeo\.com)#i', $src ) ) {
			return 'video';
		}
		if ( preg_match( '#\.(mp4|webm|ogv)(\?.*)?$#i', $src ) ) {
			return 'video';
		}
		return 'image';
	}

	/** Default image normalizer. */
	public static function normalize_image( array $item, array $settings ): array {
		$config = [
			'type' => 'image',
			'src'  => (string) ( $item['src'] ?? '' ),
		];
		if ( ! empty( $item['title'] ) ) {
			$config['title'] = (string) $item['title'];
		}
		if ( ! empty( $item['caption'] ) ) {
			$config['caption'] = (string) $item['caption'];
		}
		if ( ! empty( $item['alt'] ) ) {
			$config['alt'] = (string) $item['alt'];
		}
		if ( ! empty( $item['thumb'] ) ) {
			$config['thumb'] = (string) $item['thumb'];
		}
		return $config;
	}
}
