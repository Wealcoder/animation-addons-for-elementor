<?php
namespace WCF_ADDONS\Atomic\ImageAdvancedAnimation;

use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Image Advanced Animation onto e-image / e-svg / e-aae-a-post-image
 * widgets.
 *
 * Emits into its own `imgadv` interactions namespace — frontend reads
 * `window.AAE_INTERACTIONS_IMGADV[<id>]`. Same REPEATER shape as
 * ImageAnimation/Render.php: `{ rows: [...], rows_<bp>: [...] }`, one row per
 * independent interaction (preset + trigger + preset-specific fields).
 *
 * Trigger model reuses the shared vocabulary (on_page_load / on_scroll /
 * play_with_scroll / mouseover / click / on_slide_change) — the frontend
 * runtime dispatches every row through the same wireTrigger() helper the
 * rest of the plugin uses, so no bespoke scroll/hover wiring lives here.
 */
final class Render {
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	/**
	 * Field map for the preset-specific portion of a row: snake_case bind
	 * key => [ camelCase runtime key, cast ('num'|'str'|'bool'), default ].
	 * Every preset only uses a subset of these — unused keys are simply
	 * absent from a row, the frontend reader falls back to its own default.
	 */
	private const FIELD_MAP = [
		'direction'          => [ 'direction', 'str', 'bottomToTop' ],
		'move_direction'     => [ 'moveDirection', 'str', 'none' ],
		'orbit_direction'    => [ 'orbitDirection', 'str', 'left' ],
		'parallax_direction' => [ 'parallaxDirection', 'str', 'up' ],
		'slice_axis'         => [ 'sliceAxis', 'str', 'vertical' ],
		'slice_direction'    => [ 'sliceDirection', 'str', 'alternate' ],
		'origin'             => [ 'origin', 'str', 'center' ],
		'tile_order'         => [ 'tileOrder', 'str', 'random' ],

		'start_scale'        => [ 'startScale', 'num', 1 ],
		'end_scale'          => [ 'endScale', 'num', 1 ],
		'image_shift'        => [ 'imageShift', 'num', 0 ],
		'travel'             => [ 'travel', 'num', 0 ],
		'tilt'               => [ 'tilt', 'num', 0 ],
		'rotation'           => [ 'rotation', 'num', 0 ],
		'rotation_x'         => [ 'rotationX', 'num', 0 ],
		'rotation_y'         => [ 'rotationY', 'num', 0 ],
		'rotation_z'         => [ 'rotationZ', 'num', 0 ],
		'blur'               => [ 'blur', 'num', 0 ],
		'brightness'         => [ 'brightness', 'num', 1 ],
		'saturation'         => [ 'saturation', 'num', 1 ],
		'radius'             => [ 'radius', 'num', 0 ],
		'shade_opacity'      => [ 'shadeOpacity', 'num', 0 ],
		'slice_count'        => [ 'sliceCount', 'num', 12 ],
		'slice_skew'         => [ 'sliceSkew', 'num', 0 ],
		'depth'              => [ 'depth', 'num', 0 ],
		'stagger'            => [ 'stagger', 'num', 0 ],
		'tile_columns'       => [ 'tileColumns', 'num', 6 ],
		'tile_rows'          => [ 'tileRows', 'num', 5 ],
		'tile_scatter'       => [ 'tileScatter', 'num', 0 ],
		'tile_start_scale'   => [ 'tileStartScale', 'num', 1 ],
		'tile_rotation'      => [ 'tileRotation', 'num', 0 ],
		'wave_size'          => [ 'waveSize', 'num', 12 ],
		'circle_start'       => [ 'circleStart', 'num', 0 ],
		'circle_end'         => [ 'circleEnd', 'num', 100 ],
		'frame_distance'     => [ 'frameDistance', 'num', 0 ],
		'image_distance'     => [ 'imageDistance', 'num', 0 ],

		'sweep'              => [ 'sweep', 'bool', false ],
		'fade'               => [ 'fade', 'bool', false ],
	];

	public function register(): void {
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		$type = $element->get_element_type();
		if ( ! in_array( $type, Schema::image_advanced_animation_widgets(), true ) ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$config = $this->build_config( $settings );
		if ( empty( $config ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		InteractionsMap::register( 'imgadv', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-image-advanced-animation' );
		}
	}

	/**
	 * Build the JS-side config — REPEATER. Output:
	 *   { rows: [ <interaction>, ... ], rows_<bp>: [...], enableEditor: true }
	 * Exclusive-trigger dedupe applied per breakpoint (page_load + scroll +
	 * play_with_scroll + slide_change share one slot; click + hover unlimited).
	 */
	private function build_config( array $settings ): array {
		$map = $this->envelope_to_map( $settings[ Schema::IMGADV_INTERACTIONS ] ?? null );

		$desktop_rows = $this->rows_to_runtime( $map['desktop'] ?? [] );
		if ( empty( $desktop_rows ) ) {
			$any = false;
			foreach ( $map as $rows ) {
				if ( is_array( $rows ) && ! empty( $rows ) ) { $any = true; break; }
			}
			if ( ! $any ) {
				return [];
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

		if ( (bool) $this->unwrap_primitive( $settings[ Schema::IMGADV_ENABLE_EDITOR ] ?? null, false ) ) {
			$config['enableEditor'] = true;
		}

		return $config;
	}

	/** Per-bp rows → runtime configs, with exclusive-trigger dedupe. */
	private function rows_to_runtime( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$out = [];
		// Two independent exclusive groups, each capped at one row (first-wins):
		//   A) page-load   B) scroll / play-scroll / slide-change.
		$exclusive_groups = [
			[ 'on_page_load' ],
			[ 'on_scroll', 'play_with_scroll', 'on_slide_change' ],
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

	/** One editor row → one runtime image-advanced interaction config
	 *  (camelCase). Shared trigger/timing fields are explicit; the
	 *  preset-specific fields are copied through FIELD_MAP generically. */
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
			'startPosition'   => $str( 'start_position', 'top center' ),
			'endPosition'     => $str( 'end_position', 'bottom bottom' ),
			'wrapper'         => $str( 'wrapper', 'default' ),
			'startTrigger'    => $str( 'start_trigger', '' ),
			'endTrigger'      => $str( 'end_trigger', '' ),
			'delay'           => $num( 'delay', 0 ),
			'duration'        => $num( 'duration', 1.2 ),
			'ease'            => $str( 'ease', 'expo.out' ),
		];

		foreach ( self::FIELD_MAP as $bind => list( $out_key, $cast, $default ) ) {
			if ( ! array_key_exists( $bind, $row ) ) {
				continue;
			}
			$raw = $row[ $bind ];
			if ( null === $raw || '' === $raw ) {
				continue;
			}
			switch ( $cast ) {
				case 'num':
					$cfg[ $out_key ] = is_numeric( $raw ) ? $this->cast_value( $raw ) : $default;
					break;
				case 'bool':
					$cfg[ $out_key ] = filter_var( $raw, FILTER_VALIDATE_BOOLEAN );
					break;
				default:
					$cfg[ $out_key ] = is_scalar( $raw ) ? (string) $raw : $default;
			}
		}

		if ( ! empty( $row['markers'] ) ) {
			$cfg['markers'] = true;
		}

		return $cfg;
	}

	/** Numeric strings round-trip as numbers; others stay strings. */
	public function cast_value( $v ) {
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) return $v;
		if ( is_string( $v ) && is_numeric( $v ) ) {
			return ( false !== strpos( $v, '.' ) ) ? (float) $v : (int) $v;
		}
		return $v;
	}
}
