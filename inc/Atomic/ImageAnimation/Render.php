<?php
namespace WCF_ADDONS\Atomic\ImageAnimation;

use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Image Animation onto e-image / e-svg widgets.
 *
 * Emits into its own `img` interactions namespace — frontend reads
 * `window.AAE_INTERACTIONS_IMG[<id>]`. The runtime composes a tween from
 * `(effect, startFrom, scaleStart, scaleEnd, startPos, customStart, ease)`,
 * same pattern as RegularAnimation's fade/move composition.
 *
 * Trigger model is hardcoded per-effect on the JS side:
 *   - reveal  → scroll-tied (fires once on enter)
 *   - scale   → scrub (progress follows scroll position)
 *   - stretch → pinned scrub (top top → bottom bottom+=100)
 *
 * Also carries the 8 cinematic presets merged in from the sibling
 * ImageAdvancedAnimation extension (cinematicMask, scaleAnimation,
 * sliceShutter, mosaicDepth, liquidClip, orbitTilt, zoomTunnel,
 * scrollParallax) — FIELD_MAP below is an independent copy of that
 * extension's field map; ImageAdvancedAnimation itself is untouched.
 */
final class Render {
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	/**
	 * Field map for the cinematic-preset-specific portion of a row: snake_case
	 * bind key => [ camelCase runtime key, cast ('num'|'str'|'bool'), default ].
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
		if ( ! in_array( $type, Schema::image_animation_widgets(), true ) ) {
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

		InteractionsMap::register( 'img', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-image-animation' );
		}
	}

	/**
	 * Build the JS-side image config — REPEATER. Output:
	 *   { rows: [ <interaction>, ... ], rows_<bp>: [...], enableEditor: true }
	 * Exclusive-trigger dedupe applied per breakpoint (page_load + scroll +
	 * play_with_scroll share one slot; click + hover unlimited).
	 */
	private function build_config( array $settings ): array {
		$map = $this->envelope_to_map( $settings[ Schema::IMG_INTERACTIONS ] ?? null );

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

		if ( (bool) $this->unwrap_primitive( $settings[ Schema::IMG_ENABLE_EDITOR ] ?? null, false ) ) {
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

	/** One editor row → one runtime image interaction config (camelCase). */
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
			'duration'        => $num( 'duration', 1.5 ),
			'ease'            => $str( 'ease', 'power2.out' ),
			'startFrom'       => $str( 'start_from', 'right' ),
			'scaleStart'      => $num( 'scale_start', 0.5 ),
			'scaleEnd'        => $num( 'scale_end', 1 ),
			'method'          => $str( 'method', 'from' ),
			'customProps'     => $this->custom_rows_to_pairs( $row['custom_props'] ?? [] ),
			'customPropsTo'   => $this->custom_rows_to_pairs( $row['custom_props_to'] ?? [] ),
		];

		// Cinematic-preset fields (8 built-in presets merged in from
		// ImageAdvancedAnimation) — copied through generically via FIELD_MAP;
		// unused-by-this-effect keys are simply absent from $row.
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

	/** Repeater rows → [{k,v}] pairs (custom effect props). */
	private function custom_rows_to_pairs( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return [];
		}
		$pairs = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( isset( $row['enabled'] ) && false === $row['enabled'] ) {
				continue;
			}
			$k = isset( $row['property'] ) && is_scalar( $row['property'] ) ? trim( (string) $row['property'] ) : '';
			if ( '' === $k || 'none' === $k ) {
				continue;
			}
			$v = isset( $row['value'] ) && is_scalar( $row['value'] ) ? trim( (string) $row['value'] ) : '';
			$pairs[] = [ 'k' => $k, 'v' => $v ];
		}
		return $pairs;
	}

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

	/** Numeric strings round-trip as numbers; others stay strings. */
	public function cast_value( $v ) {
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) return $v;
		if ( is_string( $v ) && is_numeric( $v ) ) {
			return ( false !== strpos( $v, '.' ) ) ? (float) $v : (int) $v;
		}
		return $v;
	}
}
