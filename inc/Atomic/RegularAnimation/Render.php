<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use WCF_ADDONS\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Atomic widgets don't honour `_wrapper` render attributes (their Twig
 * templates render their own root tag) and `Attributes_Transformer` returns
 * null at resolve time, so adding to `settings.attributes` is also a dead end.
 *
 * The reliable path is `elementor/widget/render_content`, which receives the
 * already-rendered HTML and the widget instance — we splice our data-attrs
 * into the first opening tag.
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

		$attrs = $this->build_attrs( $settings );
		if ( empty( $attrs ) ) {
			return $html;
		}

		// Enqueue the effect bundle on demand. Its dependency chain (declared
		// in Assets.php) automatically pulls in the core runtime too. Safe to
		// call repeatedly; WordPress dedupes. No-op on the editor preview
		// since every bundle is pre-enqueued there.
		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-animation' );
		}

		return $this->splice_attrs_into_first_tag( $html, $attrs );
	}

	private function build_attrs( array $settings ): array {
		$effect           = $settings[ Schema::ANIM_EFFECT ] ?? 'none';
		$parallax_enabled = ! empty( $settings[ Schema::PARALLAX_ENABLE ] );

		$attrs = [];

		// --- Animation attrs (only when an effect is selected) ---
		if ( $effect && 'none' !== $effect ) {
			$attrs['data-aae-anim']     = $effect;
			$attrs['data-aae-method']   = $settings[ Schema::ANIM_METHOD ]  ?? 'from';
			$attrs['data-aae-trigger']  = $settings[ Schema::ANIM_TRIGGER ] ?? 'on_scroll';
			$attrs['data-aae-easing']   = $settings[ Schema::ANIM_EASING ]  ?? 'power2.out';
			$attrs['data-aae-duration'] = (string) ( $settings[ Schema::ANIM_DURATION ] ?? 1.5 );
			$attrs['data-aae-delay']    = (string) ( $settings[ Schema::ANIM_DELAY ]    ?? 0.15 );

			$trigger_selector = $settings[ Schema::ANIM_TRIGGER_SELECTOR ] ?? '';
			if ( '' !== $trigger_selector ) {
				$attrs['data-aae-trigger-selector'] = (string) $trigger_selector;
			}

			// Scroll-trigger block — only emit when wrapper=custom.
			$wrapper = $settings[ Schema::ANIM_WRAPPER ] ?? 'default';
			if ( 'custom' === $wrapper ) {
				$attrs['data-aae-wrapper']        = 'custom';
				$attrs['data-aae-start-trigger']  = (string) ( $settings[ Schema::ANIM_START_TRIGGER ]  ?? '' );
				$attrs['data-aae-end-trigger']    = (string) ( $settings[ Schema::ANIM_END_TRIGGER ]    ?? '' );
				$attrs['data-aae-start-position'] = (string) ( $settings[ Schema::ANIM_START_POSITION ] ?? 'top top' );
				$attrs['data-aae-end-position']   = (string) ( $settings[ Schema::ANIM_END_POSITION ]   ?? 'bottom top' );

				if ( 'custom' === ( $settings[ Schema::ANIM_START_POSITION ] ?? '' ) ) {
					$attrs['data-aae-start-custom'] = (string) ( $settings[ Schema::ANIM_START_CUSTOM ] ?? '' );
				}
				if ( 'custom' === ( $settings[ Schema::ANIM_END_POSITION ] ?? '' ) ) {
					$attrs['data-aae-end-custom'] = (string) ( $settings[ Schema::ANIM_END_CUSTOM ] ?? '' );
				}

				if ( ! empty( $settings[ Schema::ANIM_MARKERS ] ) ) {
					$attrs['data-aae-markers'] = 'true';
				}
			}

			// Fade-specific
			if ( 'fade' === $effect ) {
				$attrs['data-aae-fade-from']   = $settings[ Schema::ANIM_FADE_FROM ] ?? 'bottom';
				$fade_offset = $settings[ Schema::ANIM_FADE_OFFSET ] ?? null;
				if ( null !== $fade_offset && '' !== $fade_offset ) {
					$attrs['data-aae-fade-offset'] = (string) $fade_offset;
				}
				if ( 'scale' === ( $settings[ Schema::ANIM_FADE_FROM ] ?? '' ) ) {
					$attrs['data-aae-scale'] = (string) ( $settings[ Schema::ANIM_SCALE ] ?? 0.7 );
				}
			}

			// 3D Move-specific
			if ( 'move' === $effect ) {
				$attrs['data-aae-rotation-dir']     = $settings[ Schema::ANIM_ROTATION_DIR ]     ?? 'x';
				$attrs['data-aae-rotation']         = (string) ( $settings[ Schema::ANIM_ROTATION ] ?? -80 );
				$attrs['data-aae-transform-origin'] = $settings[ Schema::ANIM_TRANSFORM_ORIGIN ] ?? 'top center -50';
			}

			// Custom-specific: zip the two parallel arrays (keys + values) by index
			// into "property=value;property=value;..." for JS to split on ';' then '='.
			// A row is emitted only when its property name is a non-empty, non-"none" string.
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
						$pairs[] = $k . '=' . $v;
					}
					if ( ! empty( $pairs ) ) {
						$attrs['data-aae-custom-props'] = implode( ';', $pairs );
					}
				}
			}

			if ( ! empty( $settings[ Schema::ANIM_ENABLE_EDITOR ] ) ) {
				$attrs['data-aae-enable-editor'] = '1';
			}
		}

		// --- Parallax attrs (independent of animation effect) ---
		if ( $parallax_enabled ) {
			$attrs['data-aae-parallax'] = '1';
			$speed = $settings[ Schema::PARALLAX_SPEED ] ?? null;
			$lag   = $settings[ Schema::PARALLAX_LAG ]   ?? null;
			if ( null !== $speed && '' !== $speed ) {
				$attrs['data-speed'] = (string) $speed;
			}
			if ( null !== $lag && '' !== $lag ) {
				$attrs['data-lag'] = (string) $lag;
			}
		}

		return $attrs;
	}

	/**
	 * Splice key="value" pairs into the first opening tag of $html. Single
	 * regex with a callback so we never touch later tags.
	 */
	private function splice_attrs_into_first_tag( string $html, array $attrs ): string {
		$serialized = '';
		foreach ( $attrs as $key => $value ) {
			$serialized .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		$count = 0;
		$out   = preg_replace_callback(
			'/<([a-zA-Z][a-zA-Z0-9]*)\b/',
			static function ( $matches ) use ( $serialized ) {
				return $matches[0] . $serialized;
			},
			$html,
			1,
			$count
		);

		return $count > 0 && is_string( $out ) ? $out : $html;
	}
}
