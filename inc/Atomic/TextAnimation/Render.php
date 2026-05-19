<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

use WCF_ADDONS\Atomic\InteractionsMap;

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

		$effect_envelope = $settings[ Schema::TEXT_EFFECT ] ?? null;
		$effect_map      = $this->envelope_to_map( $effect_envelope );
		$effect          = $effect_map['desktop'] ?? 'none';
		if ( ! $effect || 'none' === $effect ) {
			return null;
		}

		$config = [ 'effect' => $effect ];

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
		$trigger_envelope = $settings[ Schema::TEXT_TRIGGER ] ?? null;
		$trigger_map      = $this->envelope_to_map( $trigger_envelope );
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
			Schema::TEXT_DURATION         => [ 'duration',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_DURATION ],    Schema::TEXT_DURATION_EFFECTS ],
			Schema::TEXT_STAGGER          => [ 'stagger',         Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_STAGGER ],     Schema::TEXT_DURATION_EFFECTS ],
			Schema::TEXT_TRANSLATE_X      => [ 'translateX',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_TRANSLATE_X ], $translate_family ],
			Schema::TEXT_TRANSLATE_Y      => [ 'translateY',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_TRANSLATE_Y ], $translate_family ],
			Schema::TEXT_ROTATION_DIR     => [ 'rotationDir',     'x',                                                            $translate_family ],
			Schema::TEXT_ROTATION         => [ 'rotation',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_ROTATION ],    $translate_family ],
			Schema::TEXT_TRANSFORM_ORIGIN => [ 'transformOrigin', 'top center -50',                                               $translate_family ],

			Schema::TEXT_START_TRIGGER    => [ 'startTrigger',  '',           null ],
			Schema::TEXT_END_TRIGGER      => [ 'endTrigger',    '',           null ],
			Schema::TEXT_START_POSITION   => [ 'startPosition', 'top top',    null ],
			Schema::TEXT_START_CUSTOM     => [ 'startCustom',   'top top',    null ],
			Schema::TEXT_END_POSITION     => [ 'endPosition',   'bottom top', null ],
			Schema::TEXT_END_CUSTOM       => [ 'endCustom',     'bottom top', null ],

			Schema::TEXT_INVERT_START     => [ 'invertStart',   'top 85%',       Schema::TEXT_INVERT_EFFECTS ],
			Schema::TEXT_INVERT_END       => [ 'invertEnd',     'bottom center', Schema::TEXT_INVERT_EFFECTS ],

			Schema::TEXT_SPIN_START       => [ 'spinStart',     'top 50%',                Schema::TEXT_SPIN_EFFECTS ],
			Schema::TEXT_SPIN_END         => [ 'spinEnd',       'bottom 30%',             Schema::TEXT_SPIN_EFFECTS ],
			Schema::TEXT_SPIN_TOGGLE      => [ 'spinToggle',    'play none none reverse', Schema::TEXT_SPIN_EFFECTS ],

			Schema::TEXT_SCALE_EASE       => [ 'scaleEase',     'back',                                                            Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SCALE_NUM        => [ 'scaleNum',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::TEXT_SCALE_NUM ],      Schema::TEXT_SCALE_EFFECTS ],
			Schema::TEXT_SCALE_BREAK      => [ 'scaleBreak',    'lines',                                                           Schema::TEXT_SCALE_EFFECTS ],
			'aae_text_spin_color'                 => [ 'spinColor',     '#000000',                                                Schema::TEXT_SPIN_EFFECTS ],
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
			if ( null !== $effect_family && ! in_array( $effect, $effect_family, true ) ) {
				continue;
			}

			// Skip scroll-trigger keys entirely when not on a scroll-style trigger.
			if ( ! $is_on_scroll && in_array( $base_key, $scroll_only_keys, true ) ) {
				continue;
			}

			$expanded = $this->expand_responsive_settings( $settings, $base_key, $extra_bps );

			$desktop_value = $expanded[ $base_key ] ?? $default;

			// Desktop: skip when value equals JS-side default — the reader
			// supplies that value when the key is missing.
			if ( ! $this->values_equal( $desktop_value, $default ) ) {
				$config[ $config_key ] = $this->cast_value( $desktop_value );
			}

			// Per-breakpoint: emit only when the value actually overrides the
			// cascaded parent. JS walks BP_CASCADE on read.
			$resolved_by_bp = [ 'desktop' => $desktop_value ];

			foreach ( $extra_bps as $bp ) {
				$own = $expanded[ $base_key . '_' . $bp ] ?? null;

				$parent = $this->cascade_parent( $bp, $resolved_by_bp, $desktop_value );

				if ( null === $own || '' === $own ) {
					$resolved_by_bp[ $bp ] = $parent;
					continue;
				}

				$resolved_by_bp[ $bp ] = $own;

				if ( $this->values_equal( $own, $parent ) ) {
					continue;
				}

				// Skip per-bp emission when the animation is disabled on this
				// breakpoint. The effect key itself is always emitted so the
				// runtime can see `effect_<bp>=none` and short-circuit.
				if ( isset( $disabled_bps[ $bp ] ) && 'effect' !== $config_key ) {
					continue;
				}

				$config[ $config_key . '_' . $bp ] = $this->cast_value( $own );
			}
		}

		return $config;
	}

	/**
	 * Expand a Responsive_Json_Prop_Type envelope into flat `<base>` +
	 * `<base>_<bp>` lookup keys so callers can read it the flat way.
	 * Pass-through when absent.
	 *
	 * Storage shape:
	 *   { $$type: 'aae-rj',
	 *     value: { desktop: <scalar>, tablet: <scalar>|null, … } }
	 */
	private function expand_responsive_settings( array $settings, string $base_key, array $extra_bps ): array {
		$envelope = $settings[ $base_key ] ?? null;
		$map      = $this->envelope_to_map( $envelope );

		$expanded = $settings;
		$expanded[ $base_key ] = $map['desktop'] ?? null;
		foreach ( $extra_bps as $bp ) {
			if ( array_key_exists( $bp, $map ) ) {
				$expanded[ $base_key . '_' . $bp ] = $map[ $bp ];
			}
		}
		return $expanded;
	}

	/** Pull the breakpoint→primitive map out of a Responsive_Json envelope. */
	private function envelope_to_map( $envelope ): array {
		if ( ! is_array( $envelope ) || ! isset( $envelope['value'] ) || ! is_array( $envelope['value'] ) ) {
			return [];
		}
		return $envelope['value'];
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

	private function values_equal( $a, $b ): bool {
		if ( is_array( $a ) && is_array( $b ) ) {
			return $a === $b;
		}
		return (string) $a === (string) $b;
	}
}
