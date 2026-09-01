<?php
/**
 * Nav — responsive style controls for the DESKTOP DROPDOWN INDICATOR.
 *
 * WHAT THIS SOLVES
 * ----------------
 * The Nav injects four icons, and only ONE of them is unreachable from the
 * panel. The hamburger, close and back icons are real Atomic SVG children of
 * the mobile companion, so each already has its own Style tab — select the icon
 * in the Navigator and Width, Height and Color are all there.
 *
 * The desktop dropdown indicator is different: nav.js FETCHES the chosen SVG
 * and inlines it into every has-dropdown label at runtime (see
 * injectDropdownIcons), so it is not a model at all. It has no Style tab, and
 * before this layer nothing in the panel could size or colour it — the picker
 * only ever chose WHICH file to draw.
 *
 * This adds per-breakpoint Size / Gap / Color rows to the Nav's existing
 * "Dropdown Icon" section, built on the same AAE responsive framework the WP
 * Menu widget uses (see Widgets/Menu/class-aae-a-menu-responsive.php).
 *
 * HOW IT DIFFERS FROM THE WP MENU LAYER
 * -------------------------------------
 * The Menu widget's Twig bakes an inline `style="--aae-menu-*: …"` attribute,
 * so its responsive layer only ever has to re-set a VARIABLE the stylesheet
 * already reads. The injected indicator has no such variable plumbing, and
 * adding it would mean writing `inline-size: var(--x, <fallback>)` into
 * nav.scss — where the fallback would have to reproduce the current `0.7em`
 * while still letting a builder override it, which CSS cannot express.
 *
 * So this layer emits COMPLETE DECLARATIONS instead of variables:
 *
 *     .aae-a-nav[data-id="7f3a"] .aae-a-nav-dropdown-icon
 *         { inline-size:14px !important; block-size:14px !important }
 *
 * Nothing at all is emitted until a builder actually fills a row in, which is
 * what keeps this a no-op for every Nav that already exists — and what makes it
 * an ADD-ONLY change to a widget that is live on client sites.
 *
 * `!important` is required rather than decorative: nav.scss styles this icon
 * through `.aae-a-nav .aae-a-nav-dropdown-icon` (specificity 0-2-0), which an
 * ordinary rule of the same shape would only tie with — and a tie is decided by
 * document order, which this block cannot rely on.
 *
 * SELECTOR ROOTS
 * --------------
 * Every rule hangs off a root resolved from the Nav's own id. Only the 'nav'
 * root is used today; root_selector() also knows the 'companion' root — the
 * separate mobile-nav element, reachable through the `data-source-nav-id`
 * back-link it already renders — because that is what a future section for a
 * drawer icon would need, and the JS mirror carries the same two names.
 *
 * @package animation-addons-for-elementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sentinel props the panel binds placeholder Text_Controls to. The editor's
 * React dispatcher matches on `$$type` and swaps that one row for the whole
 * <ResponsiveSection> tree, so the key IS the match — one subclass per panel
 * section. Stored values are never read.
 */
class AAE_A_Nav_Dropdown_Icon_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-nav-dropdown-icon';
	}
}

/**
 * Collects per-element CSS during render and prints it once in the footer.
 *
 * Footer, not inline-before-the-element: the editor canvas re-renders an
 * element client-side from its Twig template, and anything printed into the
 * element's own region would be thrown away on the first edit. A footer block
 * keyed by element id survives, and the editor bridge rewrites the same node
 * in place (`aae-nav-rs-<id>`) as the builder types.
 */
final class AAE_A_Nav_Responsive {

	const ELEMENT_TYPE = 'e-aae-a-nav';

	/**
	 * Panel section => the responsive props it owns.
	 *
	 * `prefix . <key>` is the prop key. `roots` names the selector root(s) the
	 * rule hangs off ('nav' or 'companion', resolved by root_selector()), `sel`
	 * the suffix(es) appended to each root, and `props` the CSS properties the
	 * sanitized value is written to — a size row writes two, which is why this
	 * is a list rather than a single property name.
	 *
	 * MIRRORED in src/modules/atomic/extensions/nav-sections/fields.js, which
	 * builds the identical CSS for the editor canvas. `prefix` + `sel` +
	 * `props` + `kind` must match that table exactly. Change one, change both.
	 */
	const SECTIONS = [
		/*
		 * The desktop indicator nav.js inlines into every has-dropdown label.
		 * Sized in `inline-size`/`block-size` to match the rule it overrides in
		 * nav.scss — an !important physical `width` would win too, but keeping
		 * the two spellings aligned means the override reads as the same
		 * declaration when someone diffs the computed styles.
		 */
		'dropdown_icon' => [
			'anchor' => 'aae_ndi_anchor',
			'prefix' => 'aae_ndi_',
			'roots'  => [ 'nav' ],
			'fields' => [
				'icon_size'        => [ 'sel' => [ ' .aae-a-nav-dropdown-icon' ], 'props' => [ 'inline-size', 'block-size' ], 'kind' => 'px' ],
				'gap'              => [ 'sel' => [ ' .aae-a-nav-dropdown-icon' ], 'props' => [ 'margin-inline-start' ], 'kind' => 'px' ],
				'icon_color'       => [ 'sel' => [ ' .aae-a-nav-dropdown-icon' ], 'props' => [ 'color' ], 'kind' => 'color' ],
				'hover_icon_color' => [ 'sel' => [ ' .aae-a-nav-item:hover > .aae-a-nav-item-label .aae-a-nav-dropdown-icon' ], 'props' => [ 'color' ], 'kind' => 'color' ],
				// Open state covers the frontend class AND the editor's own
				// preview class — the same pair nav.scss rotates.
				'open_icon_color'  => [
					'sel'   => [
						' .aae-a-nav-item.is-open > .aae-a-nav-item-label .aae-a-nav-dropdown-icon',
						' .aae-a-nav-item.aae-editor-dropdown-open > .aae-a-nav-item-label .aae-a-nav-dropdown-icon',
					],
					'props' => [ 'color' ],
					'kind'  => 'color',
				],
				'open_rotate'      => [
					'sel'   => [
						' .aae-a-nav-item.is-open > .aae-a-nav-item-label .aae-a-nav-dropdown-icon',
						' .aae-a-nav-item.aae-editor-dropdown-open > .aae-a-nav-item-label .aae-a-nav-dropdown-icon',
					],
					'props' => [ 'transform' ],
					'kind'  => 'deg',
				],
			],
		],

	];

	/** element id => css text. */
	private static array $blocks = [];

	private static bool $hooked = false;

	public static function register(): void {
		if ( self::$hooked ) {
			return;
		}
		self::$hooked = true;

		add_action( 'elementor/frontend/before_render', [ self::class, 'collect' ], 10, 1 );
	}

	/** The anchor prop key a panel section binds its placeholder control to. */
	public static function anchor( string $section ): string {
		return self::SECTIONS[ $section ]['anchor'] ?? '';
	}

	/** Anchor prop key => prop-type class. */
	private static function anchor_prop_types(): array {
		return [
			'aae_ndi_anchor' => AAE_A_Nav_Dropdown_Icon_Anchor_Prop_Type::class,
		];
	}

	/** Every prop this layer owns, ready to merge into the Nav's schema. */
	public static function props_schema(): array {
		$schema  = [];
		$anchors = self::anchor_prop_types();

		foreach ( self::SECTIONS as $section ) {
			$anchor_class = $anchors[ $section['anchor'] ] ?? null;
			if ( $anchor_class ) {
				$schema[ $section['anchor'] ] = $anchor_class::make()->default( '' );
			}

			foreach ( array_keys( $section['fields'] ) as $field ) {
				// Empty map, NOT a seeded desktop value: an unset row must emit
				// nothing so the icon keeps rendering exactly as it does today.
				$schema[ $section['prefix'] . $field ] = Responsive_Json_Prop_Type::make()
					->default( [ 'desktop' => null ] );
			}
		}

		return $schema;
	}

	/**
	 * A selector root, resolved from the NAV's id.
	 *
	 * 'companion' reaches the separate mobile-nav element through the
	 * `data-source-nav-id` back-link it already renders, which is what lets one
	 * settings source style icons that live in two different elements.
	 */
	private static function root_selector( string $root, string $id ): string {
		if ( 'companion' === $root ) {
			return '.aae-a-mobile-nav[data-source-nav-id="' . $id . '"]';
		}

		return '.aae-a-nav[data-id="' . $id . '"]';
	}

	public static function collect( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		if ( self::ELEMENT_TYPE !== $element->get_element_type() ) {
			return;
		}

		// RAW settings on purpose. get_atomic_settings() runs props through
		// Render_Props_Resolver, which returns null for `aae-rj` (no settings
		// transformer registered) — the envelopes have to be read unresolved.
		$settings = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];
		$id       = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';

		if ( '' === $id || empty( $settings ) ) {
			return;
		}

		$css = self::build_css( $id, $settings );
		if ( '' === $css ) {
			return;
		}

		self::$blocks[ $id ] = $css;
		self::ensure_print_hook();
	}

	private static function ensure_print_hook(): void {
		static $added = false;
		if ( $added ) {
			return;
		}
		$added = true;

		// Priority 5: the same slot the WP Menu layer and InteractionsMap use.
		add_action( 'wp_footer', [ self::class, 'print_blocks' ], 5 );
	}

	public static function print_blocks(): void {
		foreach ( self::$blocks as $id => $css ) {
			printf(
				'<style id="aae-nav-rs-%s">%s</style>',
				esc_attr( $id ),
				$css // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from sanitize_value(); see rules_for().
			);
		}

		self::$blocks = [];
	}

	/**
	 * Build the whole <style> body for one Nav.
	 *
	 * Returns '' when the builder has not filled in a single row, which is the
	 * case for every Nav that existed before this shipped — that is what keeps
	 * the upgrade a no-op on live sites.
	 *
	 * Per-breakpoint values are emitted as OWN values only (no cascade
	 * duplication) and ordered widest -> narrowest, so CSS's own cascade
	 * produces the inheritance the panel promises: a tablet value keeps
	 * applying at mobile widths until mobile overrides it.
	 */
	private static function build_css( string $id, array $settings ): string {
		$groups = [];

		// Desktop — no media query.
		$desktop = self::rules_for( $id, $settings, 'desktop' );
		if ( '' !== $desktop ) {
			$groups[] = $desktop;
		}

		foreach ( self::breakpoints() as $bp => $bp_data ) {
			$rules = self::rules_for( $id, $settings, $bp );
			if ( '' === $rules ) {
				continue;
			}

			$groups[] = sprintf(
				'@media(%s-width:%dpx){%s}',
				'min' === $bp_data['direction'] ? 'min' : 'max',
				$bp_data['value'],
				$rules
			);
		}

		return implode( '', $groups );
	}

	/**
	 * Every rule one breakpoint OWNS, as CSS text.
	 *
	 * Declarations are bucketed by selector first, so two rows aimed at the
	 * same element (Icon Color and Icon Size both land on
	 * `.aae-mobile-nav-hamburger`) merge into one rule instead of repeating the
	 * selector.
	 */
	private static function rules_for( string $id, array $settings, string $bp ): string {
		$buckets = [];

		foreach ( self::SECTIONS as $section ) {
			foreach ( $section['fields'] as $field => $meta ) {
				$map = self::envelope_to_map( $settings[ $section['prefix'] . $field ] ?? null );

				// OWN value only. A missing cell inherits through the CSS
				// cascade rather than being duplicated into narrower queries.
				if ( ! array_key_exists( $bp, $map ) ) {
					continue;
				}

				$value = self::sanitize_value( $map[ $bp ], $meta );
				if ( null === $value ) {
					continue;
				}

				$declaration = '';
				foreach ( $meta['props'] as $css_prop ) {
					// !important: the mobile icons carry a compound base style
					// of the same specificity as these rules, so an ordinary
					// declaration would only tie with it.
					$declaration .= $css_prop . ':' . $value . ' !important;';
				}

				foreach ( $section['roots'] as $root ) {
					$base = self::root_selector( $root, $id );

					foreach ( $meta['sel'] as $suffix ) {
						$selector             = $base . $suffix;
						$buckets[ $selector ] = ( $buckets[ $selector ] ?? '' ) . $declaration;
					}
				}
			}
		}

		$out = '';
		foreach ( $buckets as $selector => $declarations ) {
			$out .= $selector . '{' . $declarations . '}';
		}

		return $out;
	}

	/** Pull the breakpoint→primitive map out of a Responsive_Json envelope. */
	private static function envelope_to_map( $envelope ): array {
		if ( ! is_array( $envelope ) || ! isset( $envelope['value'] ) || ! is_array( $envelope['value'] ) ) {
			return [];
		}
		return $envelope['value'];
	}

	/**
	 * Coerce a stored cell to a safe CSS token, or null to skip it.
	 *
	 * Everything here lands inside a <style> block, so the gates are
	 * deliberately narrow: numbers become numbers, and colours are limited to
	 * the characters CSS colour notations actually use. `;`, `{`, `}` and `:`
	 * can never survive.
	 */
	private static function sanitize_value( $raw, array $meta ) {
		if ( null === $raw || '' === $raw ) {
			return null;
		}

		$kind = $meta['kind'];

		if ( 'px' === $kind ) {
			return is_numeric( $raw ) ? ( (float) $raw ) . 'px' : null;
		}

		// Rotation only — the function is fixed here, so a builder can never
		// inject an arbitrary transform.
		if ( 'deg' === $kind ) {
			return is_numeric( $raw ) ? 'rotate(' . ( (float) $raw ) . 'deg)' : null;
		}

		// Colour: hex, rgb()/rgba()/hsl()/hsla(), var(--x), or a named colour.
		if ( ! is_string( $raw ) ) {
			return null;
		}
		$clean = trim( $raw );
		if ( ! preg_match( '/^[A-Za-z0-9#(),.%\/ _-]+$/', $clean ) ) {
			return null;
		}
		return $clean;
	}

	/**
	 * Active non-desktop breakpoints as `key => [value, direction]`, ordered so
	 * narrower queries come last and therefore win.
	 *
	 * min-width breakpoints (widescreen) are emitted first: they never compete
	 * with the max-width chain, and keeping them ahead of it means a mobile
	 * override still lands last in source order.
	 */
	private static function breakpoints(): array {
		$active = [];

		if ( class_exists( \Elementor\Plugin::class )
			&& isset( \Elementor\Plugin::$instance->breakpoints )
			&& method_exists( \Elementor\Plugin::$instance->breakpoints, 'get_active_breakpoints' ) ) {

			foreach ( \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints() as $key => $breakpoint ) {
				$active[ $key ] = [
					'value'     => (int) $breakpoint->get_value(),
					'direction' => $breakpoint->get_direction(),
				];
			}
		}

		if ( empty( $active ) ) {
			$active = [
				'tablet' => [ 'value' => 1024, 'direction' => 'max' ],
				'mobile' => [ 'value' => 767, 'direction' => 'max' ],
			];
		}

		$min = array_filter( $active, static fn( $bp ) => 'min' === $bp['direction'] );
		$max = array_filter( $active, static fn( $bp ) => 'min' !== $bp['direction'] );

		// Widest first so the narrowest query is the last one to match.
		uasort( $max, static fn( $a, $b ) => $b['value'] <=> $a['value'] );

		return $min + $max;
	}
}
