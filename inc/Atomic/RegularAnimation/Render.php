<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Regular Animation onto atomic widgets.
 *
 * Migrated from per-element data-attrs to the InteractionsMap pattern:
 * one `data-aae-id` attr per element + a single inline JS map at the end
 * of <body> holding every animation config on the page. See
 * `inc/Atomic/InteractionsMap.php` for the rationale.
 */
final class Render {

	public function register(): void {
		add_filter( 'elementor/widget/render_content', [ $this, 'inject_into_html' ], 10, 2 );
	}

	public function inject_into_html( $html, $widget ): string {
		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_element_type' ) ) {
			return $html;
		}

		$type = $widget->get_element_type();
		if ( ! in_array( $type, Bootstrap::target_element_types(), true ) ) {
			return $html;
		}

		$settings = method_exists( $widget, 'get_atomic_settings' )
			? $widget->get_atomic_settings()
			: [];

		$config = $this->build_config( $settings );
		if ( empty( $config ) ) {
			return $html;
		}

		// Same id Elementor exposes as data-interaction-id on the rendered tag
		// (universal on atomic widgets, frontend + editor). JS looks up
		// window.AAE_INTERACTIONS_ANIM[interactionId] — no custom attr needed.
		$id = method_exists( $widget, 'get_id' ) ? (string) $widget->get_id() : '';
		if ( '' === $id ) {
			return $html;
		}

		InteractionsMap::register( 'anim', $id, $config );

		// Enqueue effect bundle on demand. Assets.php declares the dep chain
		// so the core runtime is pulled in automatically.
		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-animation' );
		}

		return $html;
	}

	/**
	 * Build the JS-side config object. Mirrors the structure animation.js
	 * expects after the data-attr → JS-map migration. Keys are camelCase
	 * (JS-side prefers it; no more `el.dataset.aaeFooBar` kebab→camel hops).
	 */
	private function build_config( array $settings ): array {
		$effect           = $settings[ Schema::ANIM_EFFECT ] ?? 'none';
		$parallax_enabled = ! empty( $settings[ Schema::PARALLAX_ENABLE ] );

		$config = [];

		if ( $effect && 'none' !== $effect ) {
			$config['effect'] = $effect;

			// Per-attr table: [ config key, default, effect_family|null ].
			// Mirrors the Schema's responsive registrations. Per-bp variants
			// are emitted by `emit_responsive()` below; values equal to the
			// default are skipped (the JS reader supplies the default when
			// the key is missing).
			$responsive_map = [
				Schema::ANIM_METHOD           => [ 'method',          'from',         null ],
				Schema::ANIM_TRIGGER          => [ 'trigger',         'on_scroll',    null ],
				Schema::ANIM_TRIGGER_SELECTOR => [ 'triggerSelector', '',             null ],
				Schema::ANIM_WRAPPER          => [ 'wrapper',         'default',      null ],
				Schema::ANIM_START_TRIGGER    => [ 'startTrigger',    '',             null ],
				Schema::ANIM_END_TRIGGER      => [ 'endTrigger',      '',             null ],
				Schema::ANIM_START_POSITION   => [ 'startPosition',   'top top',      null ],
				Schema::ANIM_START_CUSTOM     => [ 'startCustom',     '',             null ],
				Schema::ANIM_END_POSITION     => [ 'endPosition',     'bottom top',   null ],
				Schema::ANIM_END_CUSTOM       => [ 'endCustom',       '',             null ],
				Schema::ANIM_DELAY            => [ 'delay',           Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_DELAY ]    ?? 0.15, null ],
				Schema::ANIM_DURATION         => [ 'duration',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_DURATION ] ?? 1.5,  null ],
				Schema::ANIM_EASING           => [ 'easing',          'power2.out',   null ],
				Schema::ANIM_FADE_FROM        => [ 'fadeFrom',        'bottom',       [ 'fade' ] ],
				Schema::ANIM_FADE_OFFSET      => [ 'fadeOffset',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_FADE_OFFSET ] ?? 50, [ 'fade' ] ],
				Schema::ANIM_SCALE            => [ 'scale',           Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_SCALE ]       ?? 0.7, [ 'fade' ] ],
				Schema::ANIM_ROTATION_DIR     => [ 'rotationDir',     'x',            [ 'move' ] ],
				Schema::ANIM_ROTATION         => [ 'rotation',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_ROTATION ]    ?? -80, [ 'move' ] ],
				Schema::ANIM_TRANSFORM_ORIGIN => [ 'transformOrigin', 'top center -50', [ 'move' ] ],
			];

			// Gate: scroll-trigger custom block requires wrapper=custom.
			$wrapper_is_custom = ( $settings[ Schema::ANIM_WRAPPER ] ?? 'default' ) === 'custom';
			$scroll_custom_only = [
				Schema::ANIM_START_TRIGGER,
				Schema::ANIM_END_TRIGGER,
				Schema::ANIM_START_POSITION,
				Schema::ANIM_END_POSITION,
			];
			$start_is_custom = ( $settings[ Schema::ANIM_START_POSITION ] ?? '' ) === 'custom';
			$end_is_custom   = ( $settings[ Schema::ANIM_END_POSITION ]   ?? '' ) === 'custom';

			$extra_bps = $this->get_extra_breakpoints();

			foreach ( $responsive_map as $base_key => [ $cfg_key, $default, $family ] ) {
				if ( null !== $family && ! in_array( $effect, $family, true ) ) {
					continue;
				}
				if ( in_array( $base_key, $scroll_custom_only, true ) && ! $wrapper_is_custom ) {
					continue;
				}
				if ( Schema::ANIM_START_CUSTOM === $base_key && ! ( $wrapper_is_custom && $start_is_custom ) ) {
					continue;
				}
				if ( Schema::ANIM_END_CUSTOM === $base_key && ! ( $wrapper_is_custom && $end_is_custom ) ) {
					continue;
				}

				$this->emit_responsive( $config, $settings, $base_key, $cfg_key, $default, $extra_bps );
			}

			// wrapper=custom + markers — single-value flag.
			if ( $wrapper_is_custom && ! empty( $settings[ Schema::ANIM_MARKERS ] ) ) {
				$config['markers'] = true;
			}

			// Custom-effect repeater (non-responsive).
			if ( 'custom' === $effect ) {
				$keys   = $settings[ Schema::ANIM_CUSTOM_PROP_KEYS ]   ?? [];
				$values = $settings[ Schema::ANIM_CUSTOM_PROP_VALUES ] ?? [];

				if ( is_array( $keys ) && ! empty( $keys ) ) {
					$pairs = [];
					$count = count( $keys );
					for ( $i = 0; $i < $count; $i++ ) {
						$k = is_string( $keys[ $i ] ?? null ) ? trim( $keys[ $i ] ) : '';
						if ( '' === $k || 'none' === $k ) {
							continue;
						}
						$v = is_string( $values[ $i ] ?? null ) ? trim( $values[ $i ] ) : '';
						$pairs[] = [ 'k' => $k, 'v' => $v ];
					}
					if ( ! empty( $pairs ) ) {
						$config['customProps'] = $pairs;
					}
				}
			}

			if ( ! empty( $settings[ Schema::ANIM_ENABLE_EDITOR ] ) ) {
				$config['enableEditor'] = true;
			}
		}

		// Parallax block — independent of animation effect, non-responsive.
		if ( $parallax_enabled ) {
			$parallax = [];
			$speed = $settings[ Schema::PARALLAX_SPEED ] ?? null;
			$lag   = $settings[ Schema::PARALLAX_LAG ]   ?? null;
			if ( null !== $speed && '' !== $speed ) {
				$parallax['speed'] = (float) $speed;
			}
			if ( null !== $lag && '' !== $lag ) {
				$parallax['lag'] = (float) $lag;
			}
			$config['parallax'] = $parallax;
		}

		return $config;
	}

	/**
	 * Emit a desktop value + per-breakpoint variants for a single setting.
	 * Desktop is skipped when it equals `$default`; per-bp is skipped when
	 * it equals the cascaded parent (matches the dedup pattern used by
	 * TextAnimation\Render). JS rehydrates the default at read time.
	 */
	private function emit_responsive( array &$config, array $settings, string $base_key, string $cfg_key, $default, array $extra_bps ): void {
		$desktop_value = $settings[ $base_key ] ?? $default;

		if ( (string) $desktop_value !== (string) $default ) {
			$config[ $cfg_key ] = $this->cast_value( $desktop_value );
		}

		$resolved = [ 'desktop' => $desktop_value ];
		foreach ( $extra_bps as $bp ) {
			$prop = $base_key . '_' . $bp;
			$own  = $settings[ $prop ] ?? null;

			$parent = $this->cascade_parent( $bp, $resolved, $desktop_value );

			if ( null === $own || '' === $own ) {
				$resolved[ $bp ] = $parent;
				continue;
			}
			$resolved[ $bp ] = $own;
			if ( (string) $own === (string) $parent ) {
				continue;
			}
			$config[ $cfg_key . '_' . $bp ] = $this->cast_value( $own );
		}
	}

	/** Numeric strings round-trip as numbers; others stay strings. */
	private function cast_value( $v ) {
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) return $v;
		if ( is_string( $v ) && is_numeric( $v ) ) {
			return ( false !== strpos( $v, '.' ) ) ? (float) $v : (int) $v;
		}
		return $v;
	}

	/** Mirror of common.js BP_CASCADE for dedup decisions. */
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

	/**
	 * Active extra-breakpoint keys (non-desktop), largest→smallest. Falls
	 * back to tablet+mobile if Elementor's Breakpoints manager isn't loaded.
	 */
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
