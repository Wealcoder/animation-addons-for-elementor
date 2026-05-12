<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

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

		if ( ! in_array( $type, Schema::text_animation_widgets(), true ) ) {
			return $html;
		}

		$settings = method_exists( $widget, 'get_atomic_settings' )
			? $widget->get_atomic_settings()
			: [];

		$attrs = $this->build_text_attrs( $settings );
		if ( empty( $attrs ) ) {
			return $html;
		}

		// Enqueue the effect bundle on demand. Dependency chain (declared in
		// Assets.php) auto-pulls the core runtime. WordPress dedupes; no-op
		// on the editor preview (every bundle pre-enqueued there).
		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-animation' );
		}

		return $this->splice_attrs_into_first_tag( $html, $attrs );
	}

	private function build_text_attrs( array $settings ): array {
		$effect = $settings[ Schema::TEXT_EFFECT ] ?? 'none';

		if ( ! $effect || 'none' === $effect ) {
			return [];
		}

		// Per-attr table: [ data-attr base, default value, effect_family|null ].
		// When a value equals its default we skip the attr — the JS reader uses
		// `|| <default>` (or numOr in animation.js), so a missing attr lands on
		// the same value. Defaults mirror Schema::RESPONSIVE_NUMBER_SETTINGS
		// where they overlap; the dispatch attr (data-aae-text-anim) is emitted
		// unconditionally below and is intentionally absent from this table.
		$translate_family = Schema::TEXT_TRANSLATE_EFFECTS; // char + word
		$responsive_map = [
			Schema::TEXT_TRIGGER          => [ 'data-aae-text-trigger',          'on_scroll',       null ],
			Schema::TEXT_TRIGGER_SELECTOR => [ 'data-aae-text-trigger-selector', '',                null ],
			Schema::TEXT_WRAPPER          => [ 'data-aae-text-wrapper',          'default',         null ],
			Schema::TEXT_WRAPPER_SELECTOR => [ 'data-aae-text-wrapper-selector', '',                null ],
			Schema::TEXT_DELAY            => [ 'data-aae-text-delay',            Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_DELAY ],         null ],
			Schema::TEXT_DURATION         => [ 'data-aae-text-duration',         Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_DURATION ],      Schema::TEXT_DURATION_EFFECTS ],
			Schema::TEXT_STAGGER          => [ 'data-aae-text-stagger',          Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_STAGGER ],       Schema::TEXT_DURATION_EFFECTS ],
			Schema::TEXT_TRANSLATE_X      => [ 'data-aae-text-translate-x',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_TRANSLATE_X ],   $translate_family ],
			Schema::TEXT_TRANSLATE_Y      => [ 'data-aae-text-translate-y',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_TRANSLATE_Y ],   $translate_family ],
			Schema::TEXT_ROTATION_DIR     => [ 'data-aae-text-rotation-dir',     'x',                                                               $translate_family ],
			Schema::TEXT_ROTATION         => [ 'data-aae-text-rotation',         Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_ROTATION ],      $translate_family ],
			Schema::TEXT_TRANSFORM_ORIGIN => [ 'data-aae-text-transform-origin', 'top center -50',                                                  $translate_family ],

			/* scroll trigger settings — only when trigger=on_scroll */
			Schema::TEXT_START_TRIGGER    => [ 'data-aae-text-start-trigger',    '',          null ],
			Schema::TEXT_END_TRIGGER      => [ 'data-aae-text-end-trigger',      '',          null ],
			Schema::TEXT_START_POSITION   => [ 'data-aae-text-start',            'top top',   null ],
			Schema::TEXT_START_CUSTOM     => [ 'data-aae-text-start-custom',     'top top',   null ],
			Schema::TEXT_END_POSITION     => [ 'data-aae-text-end',              'bottom top', null ],
			Schema::TEXT_END_CUSTOM       => [ 'data-aae-text-end-custom',       'bottom top', null ],

			/* text-invert specific */
			Schema::TEXT_INVERT_START     => [ 'data-aae-text-invert-start',     'top 85%',        Schema::TEXT_INVERT_EFFECTS ],
			Schema::TEXT_INVERT_END       => [ 'data-aae-text-invert-end',       'bottom center',  Schema::TEXT_INVERT_EFFECTS ],

			/* text-spin specific */
			Schema::TEXT_SPIN_START       => [ 'data-aae-text-spin-start',       'top 50%',        Schema::TEXT_SPIN_EFFECTS ],
			Schema::TEXT_SPIN_END         => [ 'data-aae-text-spin-end',         'bottom 30%',     Schema::TEXT_SPIN_EFFECTS ],

			/* text-scale specific */
			Schema::TEXT_SCALE_EASE       => [ 'data-aae-text-scale-ease',       'back',                                                                Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SCALE_NUM        => [ 'data-aae-text-scale-num',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_SCALE_NUM ],          Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SCALE_BREAK      => [ 'data-aae-text-scale-break',      'lines',                                                               Schema::TEXT_SCALE_EFFECTS ],
		];

		// Dispatch key always emits; the rest are emitted only when explicitly
		// truthy. data-aae-anim belongs to RegularAnimation\Render and is NOT
		// written here — both modules can co-render on the same element and
		// the JS readers dispatch independently off their own dispatch attrs.
		$attrs = [
			'data-aae-text-anim' => $effect,
		];
		if ( ! empty( $settings[ Schema::TEXT_ENABLE_EDITOR ] ) ) {
			$attrs['data-aae-text-enable-editor'] = '1';
		}
		if ( ! empty( $settings[ Schema::TEXT_MARKERS ] ) ) {
			$attrs['data-aae-text-markers'] = 'true';
		}
		if ( 'text_spin' === $effect ) {
			$spin_color = is_string( $settings[ Schema::TEXT_SPIN_COLOR ] ?? '' ) ? (string) $settings[ Schema::TEXT_SPIN_COLOR ] : '';
			if ( '' !== $spin_color ) {
				$attrs['data-aae-text-spin-color'] = $spin_color;
			}
			$spin_toggle = is_string( $settings[ Schema::TEXT_SPIN_TOGGLE ] ?? '' ) ? (string) $settings[ Schema::TEXT_SPIN_TOGGLE ] : '';
			if ( '' !== $spin_toggle && 'play none none reverse' !== $spin_toggle ) {
				$attrs['data-aae-text-spin-toggle'] = $spin_toggle;
			}
		}

		// Skip scroll-trigger attrs entirely when the widget isn't on-scroll.
		$is_on_scroll = ( $settings[ Schema::TEXT_TRIGGER ] ?? 'on_scroll' ) === 'on_scroll';
		$scroll_only_attrs = [
			Schema::TEXT_START_TRIGGER,
			Schema::TEXT_END_TRIGGER,
			Schema::TEXT_START_POSITION,
			Schema::TEXT_START_CUSTOM,
			Schema::TEXT_END_POSITION,
			Schema::TEXT_END_CUSTOM,
		];

		$extra_bps = Schema::get_extra_breakpoints();

		foreach ( $responsive_map as $base_key => [ $base_attr, $default, $effect_family ] ) {
			// Skip effect-specific attrs when the chosen effect doesn't use them.
			if ( null !== $effect_family && ! in_array( $effect, $effect_family, true ) ) {
				continue;
			}

			// Skip scroll-trigger attrs entirely when not on-scroll.
			if ( ! $is_on_scroll && in_array( $base_key, $scroll_only_attrs, true ) ) {
				continue;
			}

			$desktop_value = $settings[ $base_key ] ?? $default;

			// Desktop: skip when value equals the JS-side default — the reader
			// supplies that value when the attr is missing.
			if ( (string) $desktop_value !== (string) $default ) {
				$attrs[ $base_attr ] = (string) $desktop_value;
			}

			// Per-breakpoint: emit only when the value actually overrides the
			// cascaded parent. JS walks BP_CASCADE on read, so missing attrs
			// inherit naturally — behavior unchanged, DOM smaller.
			$resolved_by_bp = [ 'desktop' => $desktop_value ];

			foreach ( $extra_bps as $bp ) {
				$prop = Schema::breakpoint_prop( $base_key, $bp );
				$own  = $settings[ $prop ] ?? null;

				$parent = $this->cascade_parent( $bp, $resolved_by_bp, $desktop_value );

				if ( null === $own || '' === $own ) {
					$resolved_by_bp[ $bp ] = $parent;
					continue; // pure inherit — skip the attr
				}

				$resolved_by_bp[ $bp ] = $own;

				if ( (string) $own === (string) $parent ) {
					continue; // identical to inherited value — skip the attr
				}

				$attrs[ $base_attr . '-' . $bp ] = (string) $own;
			}
		}

		return $attrs;
	}

	/**
	 * Mirror of common.js BP_CASCADE — for a given breakpoint, find the nearest
	 * already-resolved ancestor value. Used to decide whether emitting the
	 * per-bp data-attr is redundant.
	 */
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
