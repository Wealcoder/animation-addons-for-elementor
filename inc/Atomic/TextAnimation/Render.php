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
	 * Build the JS-side text config — REPEATER. Output:
	 *   { rows: [ <interaction>, ... ], rows_<bp>: [...], enableEditor: true }
	 * Each <interaction> is one row's runtime config. Exclusive-trigger dedupe
	 * (page-load max 1, scroll/scrub share one slot) is applied per breakpoint.
	 */
	private function build_config( array $settings ): ?array {
		$map = $this->envelope_to_map( $settings[ Schema::TEXT_INTERACTIONS ] ?? null );

		$desktop_rows = $this->rows_to_runtime( $map['desktop'] ?? [] );
		if ( empty( $desktop_rows ) ) {
			$any = false;
			foreach ( $map as $rows ) {
				if ( is_array( $rows ) && ! empty( $rows ) ) { $any = true; break; }
			}
			if ( ! $any ) {
				return null;
			}
		}

		$config = [];
		if ( ! empty( $desktop_rows ) ) {
			$config['rows'] = $desktop_rows;
		}

		$extra_bps = $this->get_extra_breakpoints();
		foreach ( $extra_bps as $bp ) {
			if ( ! array_key_exists( $bp, $map ) || null === $map[ $bp ] ) {
				continue;
			}
			$bp_rows = $this->rows_to_runtime( $map[ $bp ] );
			if ( $bp_rows === $desktop_rows ) {
				continue;
			}
			$config[ 'rows_' . $bp ] = $bp_rows;
		}

		if ( (bool) $this->unwrap_primitive( $settings[ Schema::TEXT_ENABLE_EDITOR ] ?? null, false ) ) {
			$config['enableEditor'] = true;
		}

		return $config;
	}

	/** Per-bp rows array → runtime interaction configs, with exclusive-trigger
	 *  dedupe (first-row-wins). Rows with effect=none/empty are dropped. */
	private function rows_to_runtime( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$out = [];
		// Two independent exclusive groups, each capped at one row (first-wins):
		//   A) page-load   B) scroll / play-scroll / slide-change.
		$exclusive_groups = [
			['on_page_load'],
			['on_scroll', 'play_with_scroll', 'on_slide_change'],
		];
		$used_groups = [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$effect = isset( $row['effect'] ) ? (string) $row['effect'] : 'none';
			if ( '' === $effect || 'none' === $effect ) {
				continue;
			}

			$trigger = isset( $row['trigger'] ) ? (string) $row['trigger'] : 'on_scroll';

			$gi = null;
			foreach ( $exclusive_groups as $idx => $group ) {
				if ( in_array( $trigger, $group, true ) ) { $gi = $idx; break; }
			}
			if ( null !== $gi ) {
				if ( isset( $used_groups[ $gi ] ) ) { continue; }
				$used_groups[ $gi ] = true;
			}

			$out[] = $this->row_to_config( $row, $effect, $trigger );
		}

		return $out;
	}

	/** One editor row → one runtime text interaction config (camelCase keys). */
	private function row_to_config( array $row, string $effect, string $trigger ): array {
		$str = function ( $key, $default = '' ) use ( $row ) {
			$v = $row[ $key ] ?? null;
			return ( is_scalar( $v ) && '' !== $v ) ? $v : $default;
		};
		$num = function ( $key, $default ) use ( $row ) {
			$v = $row[ $key ] ?? null;
			return ( is_numeric( $v ) ) ? $this->cast_value( $v ) : $default;
		};

		$cfg = [
			'effect'          => $effect,
			'trigger'         => $trigger,
			'triggerSelector' => $str( 'trigger_selector', '' ),
			'startPosition'   => $str( 'start_position', 'top 85%' ),
			'endPosition'     => $str( 'end_position', 'bottom 30%' ),
			'invertStart'     => $str( 'invert_start', 'top 85%' ),
			'invertEnd'       => $str( 'invert_end', 'bottom center' ),
			'delay'           => $num( 'delay', 0.15 ),
			'duration'        => $num( 'duration', 1 ),
			'stagger'         => $num( 'stagger', 0.02 ),
			'ease'            => $str( 'ease', '' ),
			'translateX'      => $num( 'translate_x', 20 ),
			'translateY'      => $num( 'translate_y', 0 ),
			'rotationDir'     => $str( 'rotation_dir', 'x' ),
			'rotation'        => $num( 'rotation', -80 ),
			'transformOrigin' => $str( 'transform_origin', '' ),
			'scaleEase'       => $str( 'scale_ease', 'back' ),
			'scaleNum'        => $num( 'scale_num', 1.5 ),
			'scaleBreak'      => $str( 'scale_break', 'lines' ),
		];

		if ( ! empty( $row['markers'] ) ) {
			$cfg['markers'] = true;
		}

		return $cfg;
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
