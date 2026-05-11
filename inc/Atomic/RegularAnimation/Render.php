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
		$effect = $settings[ Schema::ANIM_EFFECT ] ?? 'none';

		if ( ! $effect || 'none' === $effect ) {
			return [];
		}

		return [
			'data-aae-anim'     => $effect,
			'data-aae-trigger'  => $settings[ Schema::ANIM_TRIGGER ]  ?? 'in-view',
			'data-aae-duration' => (string) ( $settings[ Schema::ANIM_DURATION ] ?? 600 ),
			'data-aae-delay'    => (string) ( $settings[ Schema::ANIM_DELAY ]    ?? 0 ),
			'data-aae-easing'   => $settings[ Schema::ANIM_EASING ]   ?? 'power2.out',
			'data-aae-repeat'   => (string) ( $settings[ Schema::ANIM_REPEAT ]   ?? 0 ),
		];
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
