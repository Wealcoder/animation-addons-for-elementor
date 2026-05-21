<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Regular Animation onto atomic widgets.
 *
 * Migrated from per-element data-attrs to the InteractionsMap pattern:
 * one `data-aae-id` attr per element + a single inline JS map at the end
 * of <body> holding every animation config on the page. See
 * `inc/Atomic/InteractionsMap.php` for the rationale.
 */
final class Render {

	public function register(): void {
		// `elementor/frontend/before_render` fires for EVERY element (widgets
		// AND containers like e-flexbox / e-div-block). The widget-only
		// `elementor/widget/render_content` filter would skip containers — we
		// support both, so we use the universal hook and just call into the
		// InteractionsMap (no HTML transformation needed).
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		$type = $element->get_element_type();
		if ( ! in_array( $type, Bootstrap::target_element_types(), true ) ) {
			return;
		}

		// Use get_settings() (raw saved props), NOT get_atomic_settings().
		// get_atomic_settings() runs every prop through Render_Props_Resolver
		// which strips any transformable whose $$type has no registered
		// transformer — and aae-rj intentionally doesn't register
		// one. Reading raw lets normalize_responsive_settings walk the
		// envelope ourselves.
		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$config = $this->build_config( $settings );
		if ( empty( $config ) ) {
			return;
		}

		// Same id Elementor exposes as data-interaction-id on the rendered tag
		// (universal on atomic widgets, frontend + editor). JS looks up
		// window.AAE_INTERACTIONS_ANIM[interactionId] — no custom attr needed.
		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		InteractionsMap::register( 'anim', $id, $config );

		// Enqueue effect bundle on demand. Assets.php declares the dep chain
		// so the core runtime is pulled in automatically.
		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-animation' );
		}
	}

	/**
	 * Build the JS-side config object. Mirrors the structure animation.js
	 * expects after the data-attr → JS-map migration. Keys are camelCase
	 * (JS-side prefers it; no more `el.dataset.aaeFooBar` kebab→camel hops).
	 */
	private function build_config( array $settings ): array {
		$effect = $this->read_primitive( $settings, Schema::ANIM_EFFECT, 'none' );

		$config = [];

		if ( $effect && 'none' !== $effect ) {
			$config['effect'] = $effect;

			// Per-attr table: [ config key, default, effect_family|null ].
			// Mirrors the Schema's responsive registrations. Per-bp variants
			// are emitted by `emit_responsive()` below; values equal to the
			// default are skipped (the JS reader supplies the default when
			// the key is missing).
			$responsive_map = [
				// Effect is responsive — user can pick a different preset per
				// breakpoint or 'none' to disable on that bp. Default 'none'
				// keeps the desktop value (already set above) as-is when no
				// per-bp override exists.
				Schema::ANIM_EFFECT           => [ 'effect',          'none',         null ],
				Schema::ANIM_METHOD           => [ 'method',          'from',         null ],
				Schema::ANIM_TRIGGER          => [ 'trigger',         'on_scroll',    null ],
				Schema::ANIM_TRIGGER_SELECTOR => [ 'triggerSelector', '',             null ],
				Schema::ANIM_WRAPPER          => [ 'wrapper',         'default',      null ],
				Schema::ANIM_START_TRIGGER    => [ 'startTrigger',    '',             null ],
				Schema::ANIM_END_TRIGGER      => [ 'endTrigger',      '',             null ],
				Schema::ANIM_START_POSITION   => [ 'startPosition',   'top top',      null ],
				Schema::ANIM_START_CUSTOM     => [ 'startCustom',     '',             null ],
				Schema::ANIM_END_POSITION     => [ 'endPosition',     'bottom top',   null ],
				Schema::ANIM_END_CUSTOM       => [ 'endCustom',       '',             null ],
				Schema::ANIM_DELAY            => [ 'delay',           Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_DELAY ]    ?? 0.15, null ],
				Schema::ANIM_DURATION         => [ 'duration',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_DURATION ] ?? 1.5,  null ],
				Schema::ANIM_EASING           => [ 'easing',          'power2.out',   null ],
				Schema::ANIM_FADE_FROM        => [ 'fadeFrom',        'bottom',       [ 'fade' ] ],
				Schema::ANIM_FADE_OFFSET      => [ 'fadeOffset',      Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_FADE_OFFSET ] ?? 50, [ 'fade' ] ],
				Schema::ANIM_SCALE            => [ 'scale',           Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_SCALE ]       ?? 0.7, [ 'fade' ] ],
				Schema::ANIM_ROTATION_DIR     => [ 'rotationDir',     'x',            [ 'move' ] ],
				Schema::ANIM_ROTATION         => [ 'rotation',        Schema::RESPONSIVE_NUMBER_SETTINGS[ Schema::ANIM_ROTATION ]    ?? -80, [ 'move' ] ],
				Schema::ANIM_TRANSFORM_ORIGIN => [ 'transformOrigin', 'top center -50', [ 'move' ] ],
			];

			// Gate: scroll-trigger custom block requires wrapper=custom.
			$wrapper_is_custom = $this->read_primitive( $settings, Schema::ANIM_WRAPPER, 'default' ) === 'custom';
			$scroll_custom_only = [
				Schema::ANIM_START_TRIGGER,
				Schema::ANIM_END_TRIGGER,
				Schema::ANIM_START_POSITION,
				Schema::ANIM_END_POSITION,
			];
			$start_is_custom = $this->read_primitive( $settings, Schema::ANIM_START_POSITION, '' ) === 'custom';
			$end_is_custom   = $this->read_primitive( $settings, Schema::ANIM_END_POSITION,   '' ) === 'custom';

			$extra_bps = $this->get_extra_breakpoints();

			// Expand the effect envelope into flat <base>_<bp> keys so the
			// cascade below can read it the legacy way.
			$expanded = $this->normalize_responsive_settings( $settings, Schema::ANIM_EFFECT, $extra_bps );

			// Pre-compute which breakpoints have the animation disabled
			// (effect=none after cascade). emit_responsive uses this to
			// skip emitting other per-bp keys for those breakpoints.
			$disabled_bps    = [];
			$effect_resolved = [ 'desktop' => $effect ];
			foreach ( $extra_bps as $bp ) {
				$prop = Schema::ANIM_EFFECT . '_' . $bp;
				$own  = $expanded[ $prop ] ?? null;
				$parent_eff = $this->cascade_parent( $bp, $effect_resolved, $effect );
				$effective  = ( null === $own || '' === $own ) ? $parent_eff : $own;
				$effect_resolved[ $bp ] = $effective;
				if ( ! $effective || 'none' === $effective ) {
					$disabled_bps[ $bp ] = true;
				}
			}

			foreach ( $responsive_map as $base_key => [ $cfg_key, $default, $family ] ) {
				if ( null !== $family && ! in_array( $effect, $family, true ) ) {
					continue;
				}
				if ( in_array( $base_key, $scroll_custom_only, true ) && ! $wrapper_is_custom ) {
					continue;
				}
				if ( Schema::ANIM_START_CUSTOM === $base_key && ! ( $wrapper_is_custom && $start_is_custom ) ) {
					continue;
				}
				if ( Schema::ANIM_END_CUSTOM === $base_key && ! ( $wrapper_is_custom && $end_is_custom ) ) {
					continue;
				}

				$this->emit_responsive( $config, $settings, $base_key, $cfg_key, $default, $extra_bps, $disabled_bps );
			}

			// Markers — single-value flag, ships independently of wrapper.
			// Effect runtime only honors it for scroll-tied animations, but
			// emitting it always keeps the editor toggle authoritative.
			if ( (bool) $this->read_primitive( $settings, Schema::ANIM_MARKERS, false ) ) {
				$config['markers'] = true;
			}

			// Custom-effect repeater. ANIM_CUSTOM_PROPS is now a
			// Responsive_Json_Prop_Type whose value is a breakpoint map:
			//   { $$type: 'aae-rj',
			//     value: { desktop: [ {enabled,property,value}, ... ],
			//              tablet:  [ ... ] | null, ... } }
			//
			// JS owns the row shape entirely — rows are plain objects, no
			// transformable nesting per cell. Render emits desktop rows as
			// the base `customProps` and any per-bp override rows as
			// `customProps_<bp>` (the same convention used for scalar
			// responsives, so the JS reader's cascade handles it).
			if ( 'custom' === $effect ) {
				$custom_envelope = $settings[ Schema::ANIM_CUSTOM_PROPS ] ?? null;
				$custom_map      = $this->responsive_json_to_map( $custom_envelope );

				$desktop_pairs = $this->custom_rows_to_pairs( $custom_map['desktop'] ?? [] );
				if ( ! empty( $desktop_pairs ) ) {
					$config['customProps'] = $desktop_pairs;
				}

				// Per-bp overrides: emit `customProps_<bp>` when the bp cell
				// has its own non-empty row array AND those rows differ from
				// the desktop pairs. Empty/null bp cells mean "inherit from
				// the cascaded parent" — the JS reader walks BP_CASCADE.
				foreach ( $extra_bps as $bp ) {
					if ( ! array_key_exists( $bp, $custom_map ) || null === $custom_map[ $bp ] ) {
						continue;
					}
					$bp_pairs = $this->custom_rows_to_pairs( $custom_map[ $bp ] );
					if ( $bp_pairs === $desktop_pairs ) {
						continue;
					}
					$config[ 'customProps_' . $bp ] = $bp_pairs;
				}
			}

			if ( (bool) $this->read_primitive( $settings, Schema::ANIM_ENABLE_EDITOR, false ) ) {
				$config['enableEditor'] = true;
			}
		}

		return $config;
	}

	/**
	 * Emit a desktop value + per-breakpoint variants for a single setting.
	 * Desktop is skipped when it equals `$default`; per-bp is skipped when
	 * it equals the cascaded parent. JS rehydrates the default at read time.
	 *
	 * Supports both prop-storage shapes during the responsive-prop migration:
	 *
	 *   NEW shape (Responsive_*_Prop_Type):
	 *     $settings[$base_key] === [ 'desktop' => ..., 'tablet' => ..., ... ]
	 *
	 *   LEGACY shape (fanned-out scalar props):
	 *     $settings[$base_key]          === <scalar>          // desktop
	 *     $settings[$base_key.'_<bp>']  === <scalar> | null   // per-bp
	 *
	 * normalize_responsive_settings() converts the new shape into the
	 * legacy lookup shape so the rest of this method stays untouched. After
	 * every responsive prop is migrated and confirmed, the legacy branch
	 * can be deleted.
	 */
	private function emit_responsive( array &$config, array $settings, string $base_key, string $cfg_key, $default, array $extra_bps, array $disabled_bps = [] ): void {
		$settings = $this->normalize_responsive_settings( $settings, $base_key, $extra_bps );

		$desktop_value = $settings[ $base_key ] ?? $default;

		if ( (string) $desktop_value !== (string) $default ) {
			$config[ $cfg_key ] = $this->cast_value( $desktop_value );
		}

		$resolved = [ 'desktop' => $desktop_value ];
		foreach ( $extra_bps as $bp ) {
			$prop = $base_key . '_' . $bp;
			$own  = $settings[ $prop ] ?? null;

			$parent = $this->cascade_parent( $bp, $resolved, $desktop_value );

			if ( null === $own || '' === $own ) {
				$resolved[ $bp ] = $parent;
				continue;
			}
			$resolved[ $bp ] = $own;
			if ( (string) $own === (string) $parent ) {
				continue;
			}
			// Skip per-bp emission when the user disabled the animation
			// on this breakpoint. The effect key itself is always emitted
			// so the runtime can see effect_<bp>=none and short-circuit.
			if ( isset( $disabled_bps[ $bp ] ) && 'effect' !== $cfg_key ) {
				continue;
			}
			$config[ $cfg_key . '_' . $bp ] = $this->cast_value( $own );
		}
	}

	/**
	 * Expand a Responsive_Json_Prop_Type envelope into flat `<base>` +
	 * `<base>_<bp>` lookup keys so emit_responsive can read it the
	 * legacy-flat way without changes. Pass-through when absent.
	 *
	 * Storage shape:
	 *   { $$type: 'aae-rj',
	 *     value: { desktop: <scalar>, tablet: <scalar>|null, … } }
	 *
	 * Cells are bare scalars — no per-cell transformable nesting.
	 */
	private function normalize_responsive_settings( array $settings, string $base_key, array $extra_bps ): array {
		$value = $settings[ $base_key ] ?? null;

		if ( ! is_array( $value ) || ! isset( $value['value'] ) || ! is_array( $value['value'] ) ) {
			return $settings;
		}

		$map = $value['value'];

		$settings[ $base_key ] = $map['desktop'] ?? null;
		foreach ( $extra_bps as $bp ) {
			if ( array_key_exists( $bp, $map ) ) {
				$settings[ $base_key . '_' . $bp ] = $map[ $bp ];
			}
		}
		return $settings;
	}

	/**
	 * Read a scalar out of $settings[$key] for non-responsive primitives.
	 *
	 * get_settings() returns raw saved props: non-responsive primitives arrive
	 * as { $$type: 'boolean'|'string'|'number', value: <scalar> }; our
	 * responsive props arrive as { $$type: 'aae-rj', value:
	 * { desktop: <scalar>, … } } — in which case the desktop scalar is
	 * returned as the "single-value" read for dep-style gates.
	 *
	 * Falls back to $default when the key is absent or empty.
	 */
	private function read_primitive( array $settings, string $key, $default ) {
		$value = $settings[ $key ] ?? null;
		if ( null === $value ) {
			return $default;
		}
		if ( ! is_array( $value ) || ! array_key_exists( 'value', $value ) ) {
			return ( null === $value || '' === $value ) ? $default : $value;
		}
		$inner = $value['value'];
		// Responsive envelope: pull the desktop scalar.
		if ( is_array( $inner ) && array_key_exists( 'desktop', $inner ) ) {
			$d = $inner['desktop'];
			return ( null === $d || '' === $d ) ? $default : $d;
		}
		return ( null === $inner || '' === $inner ) ? $default : $inner;
	}

	/**
	 * Pull the breakpoint→rows map out of a Responsive_Json_Prop_Type
	 * envelope. Returns [] for unset / malformed. Each per-bp value is an
	 * array of plain row objects (or null = inherit).
	 */
	private function responsive_json_to_map( $envelope ): array {
		if ( ! is_array( $envelope ) || ! isset( $envelope['value'] ) || ! is_array( $envelope['value'] ) ) {
			return [];
		}
		return $envelope['value'];
	}

	/**
	 * Filter a per-bp rows array into the emitted runtime contract: an array
	 * of { k, v } pairs. Rows are skipped when enabled=false, property is
	 * empty, or property === 'none'. The JS effect runtime accepts strings
	 * for both — numbers are coerced from strings on the JS side.
	 */
	private function custom_rows_to_pairs( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return [];
		}
		$pairs = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$enabled = $row['enabled'] ?? true;
			if ( false === $enabled ) {
				continue;
			}
			$k = is_string( $row['property'] ?? null ) ? trim( $row['property'] ) : '';
			if ( '' === $k || 'none' === $k ) {
				continue;
			}
			$v = is_string( $row['value'] ?? null ) ? trim( $row['value'] ) : '';
			$pairs[] = [ 'k' => $k, 'v' => $v ];
		}
		return $pairs;
	}

	/** Numeric strings round-trip as numbers; others stay strings. */
	private function cast_value( $v ) {
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) return $v;
		if ( is_string( $v ) && is_numeric( $v ) ) {
			return ( false !== strpos( $v, '.' ) ) ? (float) $v : (int) $v;
		}
		return $v;
	}

	/** Mirror of common.js BP_CASCADE for dedup decisions. */
	private function cascade_parent( string $bp, array $resolved, $desktop_value ) {
		static $cascade = [
			'mobile'       => [ 'mobile_extra', 'tablet', 'tablet_extra', 'laptop' ],
			'mobile_extra' => [ 'tablet', 'tablet_extra', 'laptop' ],
			'tablet'       => [ 'tablet_extra', 'laptop' ],
			'tablet_extra' => [ 'laptop' ],
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

}
