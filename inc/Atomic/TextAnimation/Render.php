<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

use WCF_ADDONS\Atomic\InteractionsMap;
use WCF_ADDONS\Atomic\TextAnimation\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Text Animation onto heading-class atomic widgets. Uses the
 * InteractionsMap pattern (one `data-aae-id` per element + a single inline
 * JS map at end of <body>).
 *
 * After the responsive-section migration each responsive prop is stored as
 * a transformable envelope ({ $$type, value: { desktop, tablet, … } })
 * instead of fanned-out flat keys. `normalize_responsive_settings()`
 * expands the envelope into flat `<base>` + `<base>_<bp>` lookup keys
 * before reads, so emit_responsive's existing cascade/emit logic stays
 * untouched.
 */
final class Render {
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	public function register(): void {
		// `elementor/frontend/before_render` is universal (widgets + containers).
		// Text animation's target set is heading-class only (no containers), but
		// we use the same hook for symmetry with the other extensions.
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		$type = $element->get_element_type();
		if ( ! in_array( $type, Schema::text_animation_widgets(), true ) ) {
			return;
		}

		// Use get_settings() (raw saved props), NOT get_atomic_settings().
		// get_atomic_settings() runs every prop through Render_Props_Resolver
		// which strips any transformable whose $$type has no registered
		// transformer — and aae-rj intentionally doesn't register
		// one. Reading raw lets envelope_to_map walk the structure ourselves.
		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$config = $this->build_config( $settings );
		if ( null === $config ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		InteractionsMap::register( 'text', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-animation' );
		}
	}

	/**
	 * Build the JS-side text config. Returns null when no effect is selected
	 * at the desktop level (after cascade).
	 */
	private function build_config( array $settings ): ?array {
		$extra_bps = $this->get_extra_breakpoints();

		$effect_map      = $this->envelope_to_map( $settings[ Schema::TEXT_EFFECT ] ?? null );
		$effect          = $effect_map['desktop'] ?? 'none';
		if ( ! $effect || 'none' === $effect ) {
			return null;
		}

		$config = [];

		// Non-responsive single-value flags. With raw settings these arrive as
		// { $$type: 'boolean', value: true|false } so `! empty()` would always
		// be truthy. Unwrap the inner primitive first.
		if ( (bool) $this->unwrap_primitive( $settings[ Schema::TEXT_ENABLE_EDITOR ] ?? null, false ) ) {
			$config['enableEditor'] = true;
		}
		if ( (bool) $this->unwrap_primitive( $settings[ Schema::TEXT_MARKERS ] ?? null, false ) ) {
			$config['markers'] = true;
		}

		// Resolve trigger at desktop for the scroll-only-keys gate.
		$trigger_map      = $this->envelope_to_map( $settings[ Schema::TEXT_TRIGGER ] ?? null );
		$trigger_desktop  = $trigger_map['desktop'] ?? 'on_scroll';

		$is_on_scroll = 'on_scroll' === $trigger_desktop || 'play_with_scroll' === $trigger_desktop;
		$scroll_only_keys = [
			Schema::TEXT_START_TRIGGER,
			Schema::TEXT_END_TRIGGER,
			Schema::TEXT_START_POSITION,
			Schema::TEXT_START_CUSTOM,
			Schema::TEXT_END_POSITION,
			Schema::TEXT_END_CUSTOM,
		];

		// Per-attr table: [ config key, default value, effect_family|null ].
		// Defaults mirror RESPONSIVE_NUMBER_SETTINGS where they overlap.
		$translate_family = Schema::TEXT_TRANSLATE_EFFECTS;

		$responsive_map = [
			Schema::TEXT_EFFECT           => [ 'effect',          'none',            null ],
			Schema::TEXT_TRIGGER          => [ 'trigger',         'on_scroll',       null ],
			Schema::TEXT_TRIGGER_SELECTOR => [ 'triggerSelector', '',                null ],
			Schema::TEXT_WRAPPER          => [ 'wrapper',         'default',         null ],
			Schema::TEXT_WRAPPER_SELECTOR => [ 'wrapperSelector', '',                null ],
			Schema::TEXT_DELAY            => [ 'delay',           Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_DELAY ],       null ],
			Schema::TEXT_DURATION         => [ 'duration',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_DURATION ],    'duration_family' ],
			Schema::TEXT_EASE             => [ 'ease',            '',                                                             'duration_family' ],
			Schema::TEXT_TRANSLATE_X      => [ 'translateX',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_TRANSLATE_X ], $translate_family ],
			Schema::TEXT_TRANSLATE_Y      => [ 'translateY',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_TRANSLATE_Y ], $translate_family ],
			Schema::TEXT_ROTATION_DIR     => [ 'rotationDir',     'x',                                                            Schema::TEXT_MOVE_EFFECTS ],
			Schema::TEXT_ROTATION         => [ 'rotation',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_ROTATION ],    Schema::TEXT_MOVE_EFFECTS ],
			Schema::TEXT_TRANSFORM_ORIGIN => [ 'transformOrigin', 'top center -50',                                               ['text_move', 'origami_fold', 'shutter_cascade'] ],
			Schema::TEXT_TEXT_SHADOW      => [ 'textShadow',      '',                                                             ['cyber_phantom'] ],

			Schema::TEXT_START_TRIGGER    => [ 'startTrigger',  '',           null ],
			Schema::TEXT_END_TRIGGER      => [ 'endTrigger',    '',           null ],
			Schema::TEXT_START_POSITION   => [ 'startPosition', 'top 85%',    null ],
			Schema::TEXT_START_CUSTOM     => [ 'startCustom',   'top top',    null ],
			Schema::TEXT_END_POSITION     => [ 'endPosition',   'bottom 30%', null ],
			Schema::TEXT_END_CUSTOM       => [ 'endCustom',     'bottom top', null ],

			Schema::TEXT_INVERT_START     => [ 'invertStart',   'top 85%',       Schema::TEXT_INVERT_EFFECTS ],
			Schema::TEXT_INVERT_END       => [ 'invertEnd',     'bottom center', Schema::TEXT_INVERT_EFFECTS ],

			Schema::TEXT_SPIN_START       => [ 'spinStart',     'top 50%',                Schema::TEXT_SPIN_EFFECTS ],
			Schema::TEXT_SPIN_END         => [ 'spinEnd',       'bottom 30%',             Schema::TEXT_SPIN_EFFECTS ],
			Schema::TEXT_SPIN_TOGGLE      => [ 'spinToggle',    'play none none reverse', Schema::TEXT_SPIN_EFFECTS ],

			Schema::TEXT_SCALE_EASE       => [ 'scaleEase',     'back',                                                            Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SCALE_NUM        => [ 'scaleNum',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_SCALE_NUM ],      Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SCALE_BREAK      => [ 'scaleBreak',    'lines',                                                           Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SPIN_COLOR       => [ 'spinColor',     '#000000',                                                Schema::TEXT_SPIN_EFFECTS ],
		];

		// Pre-compute breakpoints where the animation is disabled (effect=none
		// after cascade) — used to skip emitting other per-bp keys for those bps.
		$disabled_bps   = [];
		$resolved_effect = [ 'desktop' => $effect ];
		foreach ( $extra_bps as $bp ) {
			$own        = $effect_map[ $bp ] ?? null;
			$parent_eff = $this->cascade_parent( $bp, $resolved_effect, $effect );
			$effective  = ( null === $own || '' === $own ) ? $parent_eff : $own;
			$resolved_effect[ $bp ] = $effective;
			if ( ! $effective || 'none' === $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		foreach ( $responsive_map as $base_key => [ $config_key, $default, $effect_family ] ) {
			// Skip effect-specific keys when the chosen effect doesn't use them.
			if ( null !== $effect_family ) {
				$is_premium = Schema::is_premium_effect( $effect );
				if ( 'duration_family' === $effect_family ) {
					if ( ! $is_premium && ! in_array( $effect, Schema::TEXT_DURATION_EFFECTS, true ) ) {
						continue;
					}
				} else if ( is_array( $effect_family ) && ! in_array( $effect, $effect_family, true ) ) {
					continue;
				}
			}

			// Skip scroll-trigger keys entirely when not on a scroll-style trigger.
			if ( ! $is_on_scroll && in_array( $base_key, $scroll_only_keys, true ) ) {
				continue;
			}

			$this->emit_responsive(
				$config,
				$settings,
				$base_key,
				$config_key,
				$default,
				$extra_bps,
				[ $this, 'cast_value' ],
				$disabled_bps
			);
		}

		$responsive_object_map = [
			Schema::TEXT_STAGGER => [ 'stagger', [], 'duration_family' ],
		];

		foreach ( $responsive_object_map as $base_key => [ $config_key, $default, $effect_family ] ) {
			if ( null !== $effect_family ) {
				$is_premium = Schema::is_premium_effect( $effect );
				if ( 'duration_family' === $effect_family ) {
					if ( ! $is_premium && ! in_array( $effect, Schema::TEXT_DURATION_EFFECTS, true ) ) {
						continue;
					}
				} else if ( is_array( $effect_family ) && ! in_array( $effect, $effect_family, true ) ) {
					continue;
				}
			}

			if ( ! $is_on_scroll && in_array( $base_key, $scroll_only_keys, true ) ) {
				continue;
			}

			if ( Schema::TEXT_STAGGER === $base_key && isset( $settings[ $base_key ] ) ) {
				if ( is_array( $settings[ $base_key ] ) && isset( $settings[ $base_key ]['value'] ) && is_array( $settings[ $base_key ]['value'] ) ) {
					foreach ( $settings[ $base_key ]['value'] as $bp => $val ) {
						$settings[ $base_key ]['value'][ $bp ] = $this->parse_stagger_data( $val );
					}
				} else {
					$settings[ $base_key ] = $this->parse_stagger_data( $settings[ $base_key ] );
				}
			}

			// We use emit_responsive_object for arrays/JSON props
			$this->emit_responsive_object(
				$config,
				$settings,
				$base_key,
				$config_key,
				$default,
				$extra_bps,
				$disabled_bps
			);
		}
		
		if ( ! isset( $config['effect'] ) ) {
			$config['effect'] = $effect;
		}

		return $config;
	}

	/**
	 * Read a scalar out of a transformable envelope (plain primitive or
	 * responsive). Plain primitives arrive as { $$type, value: <scalar> };
	 * responsive props arrive as { $$type: 'aae-rj', value:
	 * { desktop: <scalar>, … } } — desktop scalar is the dep-style read.
	 */
	private function unwrap_primitive( $value, $fallback ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( ! array_key_exists( 'value', $value ) ) {
			return $fallback;
		}
		$inner = $value['value'];
		if ( is_array( $inner ) && array_key_exists( 'desktop', $inner ) ) {
			return $inner['desktop'];
		}
		return $inner;
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
	 * Parses the raw stagger data from Elementor into a GSAP-compatible array.
	 * Moves logic previously handled by buildStaggerConfig in text.js.
	 */
	private function parse_stagger_data( $staggerData ) {
		$userStagger = [];
		if ( is_array( $staggerData ) && null !== $staggerData ) {
			if ( isset( $staggerData['type'] ) && 'amount' === $staggerData['type'] ) {
				$userStagger['amount'] = isset($staggerData['val']) ? (float) $staggerData['val'] : 0.02;
			} else {
				$userStagger['each'] = isset($staggerData['val']) ? (float) $staggerData['val'] : 0.02;
			}
			if ( ! empty( $staggerData['from'] ) ) $userStagger['from'] = $staggerData['from'];
			if ( ! empty( $staggerData['ease'] ) ) $userStagger['ease'] = $staggerData['ease'];
			if ( ! empty( $staggerData['repeat'] ) ) $userStagger['repeat'] = (int) $staggerData['repeat'];
			if ( ! empty( $staggerData['yoyo'] ) ) $userStagger['yoyo'] = (bool) $staggerData['yoyo'];
			if ( ! empty( $staggerData['grid'] ) ) {
				$g = $staggerData['grid'];
				if ( is_string( $g ) && strpos( trim($g), '[' ) === 0 ) {
					$parsed = json_decode( $g, true );
					if ( is_array( $parsed ) ) {
						$g = $parsed;
					}
				}
				$userStagger['grid'] = $g;
			}
			if ( ! empty( $staggerData['axis'] ) ) $userStagger['axis'] = $staggerData['axis'];
		} else {
			$userStagger['each'] = is_numeric( $staggerData ) ? (float) $staggerData : 0.02;
		}
		return $userStagger;
	}
}
