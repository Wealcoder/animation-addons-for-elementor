<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Text Animation onto heading-class atomic widgets.
 *
 * Migrated from per-element data-attrs to the InteractionsMap pattern:
 * one `data-aae-id` attr per element + a single inline JS map at the end
 * of <body> holding every animation config on the page.
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

		$config = $this->build_config( $settings );
		if ( null === $config ) {
			return $html;
		}

		$id = method_exists( $widget, 'get_id' ) ? (string) $widget->get_id() : '';
		if ( '' === $id ) {
			return $html;
		}

		InteractionsMap::register( 'text', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-animation' );
		}

		// No DOM attr to inject — we piggyback on Elementor's own
		// `data-interaction-id` (universal on atomic widgets, frontend +
		// editor). JS looks up window.AAE_INTERACTIONS_TEXT[interactionId].
		return $html;
	}

	/**
	 * Build the JS-side text config. Returns null when no effect is selected.
	 *
	 * Responsive values are stored as flat per-breakpoint keys
	 * (e.g. `duration`, `duration_tablet`, `duration_mobile`) — only emitted
	 * when the breakpoint value actually overrides the cascaded parent. JS
	 * walks BP_CASCADE at read time to pick the right value.
	 */
	private function build_config( array $settings ): ?array {
		$effect = $settings[ Schema::TEXT_EFFECT ] ?? 'none';
		if ( ! $effect || 'none' === $effect ) {
			return null;
		}

		// Per-attr table: [ config key, default value, effect_family|null ].
		// effect_family null = always emit (subject to scroll-only gating).
		// Defaults mirror RESPONSIVE_NUMBER_SETTINGS where they overlap.
		$translate_family = Schema::TEXT_TRANSLATE_EFFECTS;

		$responsive_map = [
			Schema::TEXT_TRIGGER          => [ 'trigger',         'on_scroll',                                                       null ],
			Schema::TEXT_TRIGGER_SELECTOR => [ 'triggerSelector', '',                                                                null ],
			Schema::TEXT_WRAPPER          => [ 'wrapper',         'default',                                                         null ],
			Schema::TEXT_WRAPPER_SELECTOR => [ 'wrapperSelector', '',                                                                null ],
			Schema::TEXT_DELAY            => [ 'delay',           Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_DELAY ],          null ],
			Schema::TEXT_DURATION         => [ 'duration',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_DURATION ],       Schema::TEXT_DURATION_EFFECTS ],
			Schema::TEXT_STAGGER          => [ 'stagger',         Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_STAGGER ],        Schema::TEXT_DURATION_EFFECTS ],
			Schema::TEXT_TRANSLATE_X      => [ 'translateX',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_TRANSLATE_X ],    $translate_family ],
			Schema::TEXT_TRANSLATE_Y      => [ 'translateY',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_TRANSLATE_Y ],    $translate_family ],
			Schema::TEXT_ROTATION_DIR     => [ 'rotationDir',     'x',                                                               $translate_family ],
			Schema::TEXT_ROTATION         => [ 'rotation',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_ROTATION ],       $translate_family ],
			Schema::TEXT_TRANSFORM_ORIGIN => [ 'transformOrigin', 'top center -50',                                                  $translate_family ],

			/* scroll trigger settings — only when trigger=on_scroll/play_with_scroll */
			Schema::TEXT_START_TRIGGER    => [ 'startTrigger',  '',           null ],
			Schema::TEXT_END_TRIGGER      => [ 'endTrigger',    '',           null ],
			Schema::TEXT_START_POSITION   => [ 'startPosition', 'top top',    null ],
			Schema::TEXT_START_CUSTOM     => [ 'startCustom',   'top top',    null ],
			Schema::TEXT_END_POSITION     => [ 'endPosition',   'bottom top', null ],
			Schema::TEXT_END_CUSTOM       => [ 'endCustom',     'bottom top', null ],

			Schema::TEXT_INVERT_START     => [ 'invertStart',   'top 85%',       Schema::TEXT_INVERT_EFFECTS ],
			Schema::TEXT_INVERT_END       => [ 'invertEnd',     'bottom center', Schema::TEXT_INVERT_EFFECTS ],

			Schema::TEXT_SPIN_START       => [ 'spinStart',     'top 50%',    Schema::TEXT_SPIN_EFFECTS ],
			Schema::TEXT_SPIN_END         => [ 'spinEnd',       'bottom 30%', Schema::TEXT_SPIN_EFFECTS ],

			Schema::TEXT_SCALE_EASE       => [ 'scaleEase',     'back',                                                                Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SCALE_NUM        => [ 'scaleNum',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_SCALE_NUM ],          Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SCALE_BREAK      => [ 'scaleBreak',    'lines',                                                               Schema::TEXT_SCALE_EFFECTS ],
		];

		$config = [
			'effect' => $effect,
		];

		// Non-responsive single-value flags.
		if ( ! empty( $settings[ Schema::TEXT_ENABLE_EDITOR ] ) ) {
			$config['enableEditor'] = true;
		}
		if ( ! empty( $settings[ Schema::TEXT_MARKERS ] ) ) {
			$config['markers'] = true;
		}
		if ( 'text_spin' === $effect ) {
			$spin_color = is_string( $settings[ Schema::TEXT_SPIN_COLOR ] ?? '' ) ? (string) $settings[ Schema::TEXT_SPIN_COLOR ] : '';
			if ( '' !== $spin_color ) {
				$config['spinColor'] = $spin_color;
			}
			$spin_toggle = is_string( $settings[ Schema::TEXT_SPIN_TOGGLE ] ?? '' ) ? (string) $settings[ Schema::TEXT_SPIN_TOGGLE ] : '';
			if ( '' !== $spin_toggle && 'play none none reverse' !== $spin_toggle ) {
				$config['spinToggle'] = $spin_toggle;
			}
		}

		$is_on_scroll = ( $settings[ Schema::TEXT_TRIGGER ] ?? 'on_scroll' ) === 'on_scroll'
			|| ( $settings[ Schema::TEXT_TRIGGER ] ?? '' ) === 'play_with_scroll';
		$scroll_only_keys = [
			Schema::TEXT_START_TRIGGER,
			Schema::TEXT_END_TRIGGER,
			Schema::TEXT_START_POSITION,
			Schema::TEXT_START_CUSTOM,
			Schema::TEXT_END_POSITION,
			Schema::TEXT_END_CUSTOM,
		];

		$extra_bps = Schema::get_extra_breakpoints();

		foreach ( $responsive_map as $base_key => [ $config_key, $default, $effect_family ] ) {
			// Skip effect-specific keys when the chosen effect doesn't use them.
			if ( null !== $effect_family && ! in_array( $effect, $effect_family, true ) ) {
				continue;
			}

			// Skip scroll-trigger keys entirely when not on a scroll-style trigger.
			if ( ! $is_on_scroll && in_array( $base_key, $scroll_only_keys, true ) ) {
				continue;
			}

			$desktop_value = $settings[ $base_key ] ?? $default;

			// Desktop: skip when value equals JS-side default — the reader
			// supplies that value when the key is missing.
			if ( (string) $desktop_value !== (string) $default ) {
				$config[ $config_key ] = $this->cast_value( $desktop_value );
			}

			// Per-breakpoint: emit only when the value actually overrides
			// the cascaded parent. JS walks BP_CASCADE on read.
			$resolved_by_bp = [ 'desktop' => $desktop_value ];

			foreach ( $extra_bps as $bp ) {
				$prop = Schema::breakpoint_prop( $base_key, $bp );
				$own  = $settings[ $prop ] ?? null;

				$parent = $this->cascade_parent( $bp, $resolved_by_bp, $desktop_value );

				if ( null === $own || '' === $own ) {
					$resolved_by_bp[ $bp ] = $parent;
					continue;
				}

				$resolved_by_bp[ $bp ] = $own;

				if ( (string) $own === (string) $parent ) {
					continue;
				}

				$config[ $config_key . '_' . $bp ] = $this->cast_value( $own );
			}
		}

		return $config;
	}

	/** Numeric strings round-trip as numbers; other strings stay as strings. */
	private function cast_value( $v ) {
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) return $v;
		if ( is_string( $v ) && is_numeric( $v ) ) {
			return ( false !== strpos( $v, '.' ) ) ? (float) $v : (int) $v;
		}
		return $v;
	}

	/**
	 * Mirror of common.js BP_CASCADE — for a given breakpoint, find the
	 * nearest already-resolved ancestor value.
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

}
