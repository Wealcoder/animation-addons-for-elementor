<?php
namespace WCF_ADDONS\Atomic\Lightbox;

use WCF_ADDONS\Atomic\InteractionsMap;
use Elementor\Modules\AtomicWidgets\Controls\Section;

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Public developer façade for the global Lightbox system.
 *
 * A widget developer needs exactly three things:
 *   1. register_lightbox_controls()  — add the shared "Lightbox" panel section
 *   2. is_enabled( $settings )        — cheap gate before doing any work
 *   3. get_attributes( $settings, $item [, $iid ] )
 *          — normalizes the item, publishes its config to the interactions
 *            map, enqueues the assets on demand, and returns the trigger
 *            attributes to print on the clickable element.
 *
 * No widget contains Lightbox JavaScript. Every widget uses this same API;
 * only the $item descriptor differs per widget.
 *
 * Example (inside a widget render):
 *
 *   $attrs = Lightbox_Manager::get_attributes( $settings, [
 *       'src'     => $image_url,
 *       'title'   => $title,
 *       'caption' => $caption,
 *       'gallery' => 'gallery-' . $this->get_id(),
 *       'type'    => 'image',
 *   ] );
 *   echo '<img ' . Lightbox_Manager::attrs_string( $attrs ) . ' src="…">';
 */
final class Lightbox_Manager {

	const SCRIPT_HANDLE = 'aae-effect-lightbox';
	const STYLE_HANDLE  = 'aae-lightbox-css';

	/**
	 * Shared "Lightbox" panel section. Call from a custom atomic widget's
	 * define_atomic_controls() and spread it into the returned array.
	 *
	 * @param array $args Optional overrides (e.g. [ 'label' => 'Media Lightbox' ]).
	 */
	public static function register_lightbox_controls( array $args = [] ): Section {
		return ( new Controls() )->build_section( $args );
	}

	/**
	 * Whether the Lightbox is enabled for the given settings. LB_ENABLE is a
	 * Boolean prop; unwrap a possible { $$type, value } envelope, then coerce.
	 */
	public static function is_enabled( array $settings ): bool {
		$raw = $settings[ Schema::LB_ENABLE ] ?? null;

		if ( is_array( $raw ) && array_key_exists( 'value', $raw ) ) {
			$raw = $raw['value'];
		}

		return self::truthy( $raw );
	}

	/**
	 * Normalize a descriptor, publish its config to the 'lb' interactions map,
	 * enqueue assets, and return the trigger attributes.
	 *
	 * @param array  $settings Widget settings (used for enable gate + defaults).
	 * @param array  $item     { src, title, caption, gallery, type, thumb, alt }.
	 * @param string $iid      Interaction id. Defaults to a unique generated id;
	 *                         pass the element id to key off data-interaction-id.
	 * @return array Attribute map (empty when Lightbox is disabled → renders nothing).
	 */
	public static function get_attributes( array $settings, array $item, string $iid = '' ): array {
		if ( ! self::is_enabled( $settings ) ) {
			return [];
		}

		$src = (string) ( $item['src'] ?? '' );
		if ( '' === $src ) {
			return [];
		}

		$iid = '' !== $iid ? $iid : wp_unique_id( 'aae-lb-' );

		$config = Content_Type_Registry::normalize( $item, $settings );

		InteractionsMap::register( 'lb', $iid, $config );

		self::enqueue();

		$attrs = [
			'data-interaction-id' => $iid,
			'data-aae-lb'         => $config['type'],
			'role'                => 'button',
			'tabindex'            => '0',
			'aria-haspopup'       => 'dialog',
		];

		$group = $item['gallery'] ?? '';
		if ( '' !== $group ) {
			$attrs['data-aae-lb-group'] = (string) $group;
		}

		return $attrs;
	}

	/**
	 * Publish container-level options into the 'lbc' interactions map, keyed by
	 * the container's interaction id, and enqueue the runtime. The JS reads
	 * window.AAE_INTERACTIONS_LBC[<interactionId>] when a click lands inside a
	 * container carrying that id, then discovers + groups the child images.
	 *
	 * @param string $iid     Container interaction id (= data-interaction-id).
	 * @param array  $options Normalized option bag (see Container_Render).
	 */
	public static function register_container( string $iid, array $options ): void {
		if ( '' === $iid ) {
			return;
		}
		InteractionsMap::register( 'lbc', $iid, $options );
		self::enqueue();
	}

	/** Read a Boolean prop from raw settings (unwraps the atomic envelope). */
	public static function bool( array $settings, string $key, bool $default = false ): bool {
		$raw = $settings[ $key ] ?? null;
		if ( null === $raw ) {
			return $default;
		}
		if ( is_array( $raw ) && array_key_exists( 'value', $raw ) ) {
			$raw = $raw['value'];
		}
		return self::truthy( $raw );
	}

	/**
	 * Read a Responsive_Json prop as a plain { desktop, tablet, … } map.
	 * Unwraps the { $$type:'aae-rj', value:{…} } envelope. Returns [] when the
	 * prop is unset/empty. Per-bp payloads are returned as-is (scalar/array).
	 */
	public static function responsive( array $settings, string $key ): array {
		$raw = $settings[ $key ] ?? null;
		if ( is_array( $raw ) && array_key_exists( 'value', $raw ) ) {
			$raw = $raw['value'];
		}
		return is_array( $raw ) ? $raw : [];
	}

	/** Read a String prop from raw settings (unwraps the atomic envelope). */
	public static function string( array $settings, string $key, string $default = '' ): string {
		$raw = $settings[ $key ] ?? null;
		if ( is_array( $raw ) && array_key_exists( 'value', $raw ) ) {
			$raw = $raw['value'];
		}
		return is_string( $raw ) && '' !== $raw ? $raw : $default;
	}

	/** Register (once) then enqueue the runtime + stylesheet. Frontend only. */
	public static function enqueue(): void {
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_script( self::SCRIPT_HANDLE );
		wp_enqueue_style( self::STYLE_HANDLE );
	}

	/**
	 * Convenience: turn an attribute map into an escaped attribute string for
	 * plain PHP templates. (Twig callers loop the map directly instead.)
	 */
	public static function attrs_string( array $attrs ): string {
		$out = '';
		foreach ( $attrs as $k => $v ) {
			$out .= ' ' . esc_attr( $k ) . '="' . esc_attr( (string) $v ) . '"';
		}
		return $out;
	}

	private static function truthy( $v ): bool {
		return true === $v || 'yes' === $v || 'true' === $v || 1 === $v || '1' === $v;
	}
}
