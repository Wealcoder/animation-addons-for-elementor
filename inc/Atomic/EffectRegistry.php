<?php
namespace WCF_ADDONS\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registry for atomic animation effects.
 *
 * Adding a new effect = one register() call. The registry's API drives
 * Schema, Controls, Render, and Assets automatically.
 *
 *   Bootstrap::init() {
 *     EffectRegistry::register( new Effects\Tilt() );
 *     EffectRegistry::register( new Effects\Pin() );
 *     // ...
 *   }
 *
 * Renderer-side loop:
 *
 *   foreach ( EffectRegistry::for_element( $type ) as $effect ) {
 *     $attrs = $effect->build_attrs( $settings );
 *     if ( $attrs && $effect->should_enqueue( $settings ) ) {
 *       wp_enqueue_script( $effect->bundle() );
 *     }
 *   }
 */
final class EffectRegistry {

	/** @var Effects\Effect_Interface[] */
	private static array $effects = [];

	public static function register( Effects\Effect_Interface $effect ): void {
		self::$effects[ $effect->name() ] = $effect;
	}

	/** @return Effects\Effect_Interface[] */
	public static function all(): array {
		return self::$effects;
	}

	public static function get( string $name ): ?Effects\Effect_Interface {
		return self::$effects[ $name ] ?? null;
	}

	/**
	 * Every effect whose targets() include the given atomic widget type, or
	 * whose targets() is empty (interpreted as "applies everywhere").
	 *
	 * @return Effects\Effect_Interface[]
	 */
	public static function for_element( string $type ): array {
		$matches = [];
		foreach ( self::$effects as $effect ) {
			$targets = $effect->targets();
			if ( empty( $targets ) || in_array( $type, $targets, true ) ) {
				$matches[] = $effect;
			}
		}
		return $matches;
	}
}
