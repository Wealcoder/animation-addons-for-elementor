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

		// This widget actually carries a text or regular animation, so pull
		// the matching effect bundle in. Its dependency chain (declared in
		// Assets.php) automatically also enqueues the core runtime. Safe to
		// call repeatedly; WordPress dedupes. On the editor preview every
		// bundle is already enqueued, so this is a no-op there.
		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-animation' );
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

		// Every responsive setting → [ data-attr base, default value ].
		// Desktop emits the bare attr; each active extra breakpoint emits "-{bp}"
		// suffixed variants. Empty / null per-breakpoint values fall back to desktop.
		// Defaults match Schema::RESPONSIVE_NUMBER_SETTINGS / RESPONSIVE_STRING_SETTINGS,
		// which is what the JS reader falls back to via `|| <default>`. When a value
		// equals its default we skip the attr — the frontend supplies the same value
		// in code. Effect-specific attrs are also gated to the active effect family.
		$responsive_map = [
			Schema::TEXT_EFFECT           => [ 'data-aae-text-anim',             'none',            null ],
			Schema::TEXT_TRIGGER          => [ 'data-aae-text-trigger',          'on_scroll',       null ],
			Schema::TEXT_TRIGGER_SELECTOR => [ 'data-aae-text-trigger-selector', '',                null ],
			Schema::TEXT_WRAPPER          => [ 'data-aae-text-wrapper',          'default',         null ],
			Schema::TEXT_WRAPPER_SELECTOR => [ 'data-aae-text-wrapper-selector', '',                null ],
			Schema::TEXT_DELAY            => [ 'data-aae-text-delay',            0.15,              null ],
			Schema::TEXT_DURATION         => [ 'data-aae-text-duration',         1,                 Schema::TEXT_DURATION_EFFECTS ],
			Schema::TEXT_STAGGER          => [ 'data-aae-text-stagger',          0.02,              Schema::TEXT_DURATION_EFFECTS ],
			Schema::TEXT_TRANSLATE_X      => [ 'data-aae-text-translate-x',      20,                Schema::TEXT_TRANSLATE_EFFECTS ],
			Schema::TEXT_TRANSLATE_Y      => [ 'data-aae-text-translate-y',      0,                 Schema::TEXT_TRANSLATE_EFFECTS ],
			Schema::TEXT_ROTATION_DIR     => [ 'data-aae-text-rotation-dir',     'x',               [ 'char', 'word' ] ],
			Schema::TEXT_ROTATION         => [ 'data-aae-text-rotation',         -80,               [ 'char', 'word' ] ],
			Schema::TEXT_TRANSFORM_ORIGIN => [ 'data-aae-text-transform-origin', 'top center -50',  [ 'char', 'word' ] ],

			/* scroll trigger settings — only when trigger=on_scroll */
			Schema::TEXT_START_TRIGGER    => [ 'data-aae-text-start-trigger',    '',          null ],
			Schema::TEXT_END_TRIGGER      => [ 'data-aae-text-end-trigger',      '',          null ],
			Schema::TEXT_START_POSITION   => [ 'data-aae-text-start',            'top top',   null ],
			Schema::TEXT_START_CUSTOM     => [ 'data-aae-text-start-custom',     'top top',   null ],
			Schema::TEXT_END_POSITION     => [ 'data-aae-text-end',              'bottom top', null ],
			Schema::TEXT_END_CUSTOM       => [ 'data-aae-text-end-custom',       'bottom top', null ],

			/* text-invert specific */
			Schema::TEXT_INVERT_START     => [ 'data-aae-text-invert-start',     'top 85%',        [ 'text_invert' ] ],
			Schema::TEXT_INVERT_END       => [ 'data-aae-text-invert-end',       'bottom center',  [ 'text_invert' ] ],

			/* text-spin specific */
			Schema::TEXT_SPIN_START       => [ 'data-aae-text-spin-start',       'top 50%',        [ 'text_spin' ] ],
			Schema::TEXT_SPIN_END         => [ 'data-aae-text-spin-end',         'bottom 30%',     [ 'text_spin' ] ],

			/* text-scale specific */
			Schema::TEXT_SCALE_EASE       => [ 'data-aae-text-scale-ease',       'back',           [ 'text_scale' ] ],
			Schema::TEXT_SCALE_NUM        => [ 'data-aae-text-scale-num',        1.5,              [ 'text_scale' ] ],
			Schema::TEXT_SCALE_BREAK      => [ 'data-aae-text-scale-break',      'lines',          [ 'text_scale' ] ],
		];

		// Non-responsive attrs (toggle + legacy alias + non-responsive v3 controls).
		// Defaults intentionally omitted from emission — only present when truthy
		// / non-default. The runtime falls back via `|| <default>` either way.
		$attrs = [
			'data-aae-anim' => $effect,
		];
		if ( ! empty( $settings[ Schema::TEXT_ENABLE_EDITOR ] ) ) {
			$attrs['data-aae-text-enable-editor'] = '1';
		}
		if ( ! empty( $settings[ Schema::TEXT_MARKERS ] ) ) {
			$attrs['data-aae-text-markers'] = 'true';
		}
		if ( ! empty( $settings[ Schema::TEXT_SCRUB ] ) ) {
			$attrs['data-aae-text-scrub'] = 'yes';
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

			// Emit desktop attr only when it actually overrides the JS default.
			// The reader uses `pickResponsive(...) || <default>`, so a missing
			// attr lands on the same value — but for the widget-effect attr
			// (data-aae-text-anim) we always emit, since it's the dispatch key.
			$emit_desktop = ( $base_key === Schema::TEXT_EFFECT )
				|| ( (string) $desktop_value !== (string) $default );

			if ( $emit_desktop ) {
				$attrs[ $base_attr ] = (string) $desktop_value;
			}

			// Emit a per-breakpoint attr only when its value actually overrides
			// the cascaded parent. The frontend walks BP_CASCADE on read, so a
			// missing attr inherits naturally — behavior unchanged, DOM smaller.
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
