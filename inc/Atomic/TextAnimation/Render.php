<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

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

		$is_text = in_array( $type, Schema::text_animation_widgets(), true );
		$is_anim = in_array( $type, Bootstrap::target_element_types(), true );

		if ( ! $is_text && ! $is_anim ) {
			return $html;
		}

		$settings = method_exists( $widget, 'get_atomic_settings' )
			? $widget->get_atomic_settings()
			: [];

		// Text-animation widgets may also carry regular animation settings —
		// merge both attribute sets. Text attrs win on `data-aae-anim` since
		// the text builder treats that as the legacy alias for its effect.
		$attrs = [];
		if ( $is_anim ) {
			$attrs = array_merge( $attrs, $this->build_anim_attrs( $settings ) );
		}
		if ( $is_text ) {
			$attrs = array_merge( $attrs, $this->build_text_attrs( $settings ) );
		}

		if ( empty( $attrs ) ) {
			return $html;
		}

		return $this->splice_attrs_into_first_tag( $html, $attrs );
	}

	private function build_anim_attrs( array $settings ): array {
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

	private function build_text_attrs( array $settings ): array {
		$effect = $settings[ Schema::TEXT_EFFECT ] ?? 'none';

		if ( ! $effect || 'none' === $effect ) {
			return [];
		}

		// Every prop → [ data-attr name, default value ]. Single emission, no per-breakpoint variants.
		$attr_map = [
			Schema::TEXT_TRIGGER          => [ 'data-aae-text-trigger',          'on_scroll' ],
			Schema::TEXT_TRIGGER_SELECTOR => [ 'data-aae-text-trigger-selector', '' ],
			Schema::TEXT_WRAPPER          => [ 'data-aae-text-wrapper',          'default' ],
			Schema::TEXT_WRAPPER_SELECTOR => [ 'data-aae-text-wrapper-selector', '' ],
			Schema::TEXT_DELAY            => [ 'data-aae-text-delay',            0.15 ],
			Schema::TEXT_DURATION         => [ 'data-aae-text-duration',         1 ],
			Schema::TEXT_STAGGER          => [ 'data-aae-text-stagger',          0.02 ],
			Schema::TEXT_TRANSLATE_X      => [ 'data-aae-text-translate-x',      20 ],
			Schema::TEXT_TRANSLATE_Y      => [ 'data-aae-text-translate-y',      0 ],
			Schema::TEXT_ROTATION_DIR     => [ 'data-aae-text-rotation-dir',     'x' ],
			Schema::TEXT_ROTATION         => [ 'data-aae-text-rotation',         -80 ],
			Schema::TEXT_TRANSFORM_ORIGIN => [ 'data-aae-text-transform-origin', 'top center -50' ],

			/* scroll trigger settings */
			Schema::TEXT_START_TRIGGER    => [ 'data-aae-text-start-trigger',    '' ],
			Schema::TEXT_END_TRIGGER      => [ 'data-aae-text-end-trigger',      '' ],
			Schema::TEXT_START_POSITION   => [ 'data-aae-text-start',            'top top' ],
			Schema::TEXT_START_CUSTOM     => [ 'data-aae-text-start-custom',     'top top' ],
			Schema::TEXT_END_POSITION     => [ 'data-aae-text-end',              'bottom top' ],
			Schema::TEXT_END_CUSTOM       => [ 'data-aae-text-end-custom',       'bottom top' ],

			/* text-invert specific */
			Schema::TEXT_INVERT_START     => [ 'data-aae-text-invert-start',     'top 85%' ],
			Schema::TEXT_INVERT_END       => [ 'data-aae-text-invert-end',       'bottom center' ],

			/* text-spin specific */
			Schema::TEXT_SPIN_START       => [ 'data-aae-text-spin-start',       'top 50%' ],
			Schema::TEXT_SPIN_END         => [ 'data-aae-text-spin-end',         'bottom 30%' ],

			/* text-scale specific */
			Schema::TEXT_SCALE_EASE       => [ 'data-aae-text-scale-ease',       'back' ],
			Schema::TEXT_SCALE_NUM        => [ 'data-aae-text-scale-num',        1.5 ],
			Schema::TEXT_SCALE_BREAK      => [ 'data-aae-text-scale-break',      'lines' ],
		];

		// Booleans + non-mapped attrs.
		$attrs = [
			'data-aae-anim'               => $effect,
			'data-aae-text-anim'          => $effect,
			'data-aae-text-enable-editor' => ! empty( $settings[ Schema::TEXT_ENABLE_EDITOR ] ) ? '1' : '0',
			'data-aae-text-markers'       => ! empty( $settings[ Schema::TEXT_MARKERS ] )        ? 'true' : 'false',
			'data-aae-text-scrub'         => ! empty( $settings[ Schema::TEXT_SCRUB ] )          ? 'yes'  : '',
			'data-aae-text-spin-color'    => is_string( $settings[ Schema::TEXT_SPIN_COLOR ]  ?? '' ) ? (string) $settings[ Schema::TEXT_SPIN_COLOR ]  : '',
			'data-aae-text-spin-toggle'   => is_string( $settings[ Schema::TEXT_SPIN_TOGGLE ] ?? '' ) ? (string) $settings[ Schema::TEXT_SPIN_TOGGLE ] : 'play none none reverse',
		];

		foreach ( $attr_map as $prop_key => [ $attr_name, $default ] ) {
			$attrs[ $attr_name ] = (string) ( $settings[ $prop_key ] ?? $default );
		}

		return $attrs;
	}

	/**
	 * Splice key="value" pairs into the first opening tag of $html.
	 * Uses a single regex with a callback so we never touch later tags.
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
			1, // only replace the first opening tag
			$count
		);

		return $count > 0 && is_string( $out ) ? $out : $html;
	}
}
