<?php
/**
 * WP Menu — responsive overrides for every CSS-variable-driven style section.
 *
 * WHY THIS EXISTS AS A SEPARATE LAYER
 * -----------------------------------
 * The widget's style props (padding_x, dropdown_bg, hamburger_size, …) are
 * plain Number/String props that the Twig template bakes into ONE inline
 * `style="--aae-menu-*: …"` attribute. That attribute cannot express
 * breakpoints, and the props themselves cannot be retyped to
 * Responsive_Json_Prop_Type without breaking every existing site:
 *
 *   - a stored `{$$type:'number'}` value fails Responsive_Json's
 *     `is_transformable()` check → `invalid_value` → Props_Parser makes
 *     `get_data_for_save()` throw, so the page can no longer be saved;
 *   - and `aae-rj` has no SETTINGS transformer, so Render_Props_Resolver
 *     resolves it to null → the Twig `|default(...)` fallback silently
 *     replaces the builder's saved value with the hardcoded default.
 *
 * So the legacy props stay exactly as they are — same keys, same types, same
 * Twig, same inline vars — and this layer adds a PARALLEL set of responsive
 * props that override the same CSS variables from a per-element <style>
 * block. Nothing is emitted until a builder actually sets a value, so an
 * untouched menu renders byte-identical markup to before.
 *
 * `!important` is required, not decorative: the legacy values live in the
 * element's inline `style` attribute, which outranks any stylesheet rule.
 *
 * Per-breakpoint values are emitted as OWN values only (no cascade
 * duplication) and ordered widest → narrowest, so CSS's own cascade produces
 * the inheritance the panel promises: a tablet value keeps applying at mobile
 * widths until mobile overrides it.
 *
 * WHAT IS DELIBERATELY NOT HERE
 * -----------------------------
 * Only properties the stylesheet reads as a CSS VARIABLE can work this way.
 * These stay single-value native controls, and each for a concrete reason:
 *
 *   - Layout (horizontal/vertical), Mobile Hamburger, Open On (hover/click),
 *     Drawer/Dropdown Effect — delivered as `data-*` attributes that CSS
 *     branches on and menu.js reads. An attribute cannot vary by media query.
 *   - Mobile Breakpoint — menu.js's own per-widget px switch, a DIFFERENT
 *     breakpoint system from Elementor's (see menu.js's own note: "a media
 *     query cannot read a PER-WIDGET breakpoint"). Making it responsive would
 *     put two breakpoint systems in a loop.
 *   - Select Menu, Label, Logo, the two toggle Icons — content and media, not
 *     style.
 *
 * @package animation-addons-for-elementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Menu;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sentinel prop the panel binds a placeholder Text_Control to. The editor's
 * React dispatcher matches this `$$type` and swaps that one row for the whole
 * <ResponsiveSection> tree. The stored value is never read.
 *
 * One subclass per panel section, because the key IS the match: a section is
 * found by its anchor's `$$type`, never by element type, which is what lets
 * this attach to a widget rather than to every atomic element.
 */
class AAE_A_Menu_Items_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-items';
	}
}

class AAE_A_Menu_Layout_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-layout';
	}
}

class AAE_A_Menu_Dropdown_Panel_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-dropdown-panel';
	}
}

class AAE_A_Menu_Dropdown_Items_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-dropdown-items';
	}
}

class AAE_A_Menu_Toggle_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-toggle';
	}
}

class AAE_A_Menu_Hamburger_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-hamburger';
	}
}

class AAE_A_Menu_Drawer_Header_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-drawer-header';
	}
}

class AAE_A_Menu_Drawer_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-drawer';
	}
}

class AAE_A_Menu_Motion_Anchor_Prop_Type extends Base_Section_Anchor {
	public static function get_key(): string {
		return 'aae-section-aae-menu-motion';
	}
}

/**
 * Collects per-element CSS during render and prints it once in the footer.
 *
 * Footer, not inline-before-the-element: the editor canvas re-renders a widget
 * client-side from its Twig template, and anything printed into the element's
 * own region would be thrown away on the first edit. A footer block keyed by
 * element id survives, and the editor bridge rewrites the same node in place
 * (`aae-mi-rs-<id>`) as the builder types.
 */
final class AAE_A_Menu_Responsive {

	const ELEMENT_TYPE = 'e-aae-a-menu';

	/** Shared enum value lists. Mirrored in the JS table — change both. */
	const BORDER_STYLES = [ 'solid', 'dashed', 'dotted', 'double', 'none' ];
	const ALIGNMENTS    = [ 'flex-start', 'center', 'flex-end', 'space-between' ];
	const FONT_WEIGHTS  = [ '400', '500', '600', '700' ];

	/**
	 * Panel section => responsive props it owns.
	 *
	 * `prefix . <suffix>` is the prop key; `var` is the CSS variable the Twig
	 * already emits for the legacy prop, so an override lands on exactly the
	 * same declaration the stylesheet reads.
	 *
	 * MIRRORED in src/modules/atomic/extensions/menu-sections/fields.js —
	 * the editor writes the same CSS into the canvas. Change one, change both;
	 * scratchpad/test-menu-css.php + parity.mjs diff the two tables.
	 */
	const SECTIONS = [
		'items' => [
			'anchor' => 'aae_mi_anchor',
			'prefix' => 'aae_mi_',
			'fields' => [
				'text_color'        => [ 'var' => '--aae-menu-color',             'kind' => 'color' ],
				'hover_color'       => [ 'var' => '--aae-menu-hover-color',       'kind' => 'color' ],
				'item_hover_bg'     => [ 'var' => '--aae-menu-item-hover-bg',     'kind' => 'color' ],
				'active_color'      => [ 'var' => '--aae-menu-active-color',      'kind' => 'color' ],
				'active_weight'     => [ 'var' => '--aae-menu-active-weight',     'kind' => 'enum', 'allowed' => self::FONT_WEIGHTS ],
				'padding_x'         => [ 'var' => '--aae-menu-item-padding-x',    'kind' => 'px' ],
				'padding_y'         => [ 'var' => '--aae-menu-item-padding-y',    'kind' => 'px' ],
				'item_gap'          => [ 'var' => '--aae-menu-item-gap',          'kind' => 'px' ],
				'link_radius'       => [ 'var' => '--aae-menu-link-radius',       'kind' => 'px' ],
				'item_border_width' => [ 'var' => '--aae-menu-item-border-width', 'kind' => 'px' ],
				'item_border_style' => [ 'var' => '--aae-menu-item-border-style', 'kind' => 'enum', 'allowed' => self::BORDER_STYLES ],
				'item_border_color' => [ 'var' => '--aae-menu-item-border-color', 'kind' => 'color' ],
			],
		],

		// Only Alignment is a variable. Layout / Mobile Hamburger / Mobile
		// Breakpoint are attribute- and JS-driven — see the class docblock.
		'layout' => [
			'anchor' => 'aae_ml_anchor',
			'prefix' => 'aae_ml_',
			'fields' => [
				'align' => [ 'var' => '--aae-menu-align', 'kind' => 'enum', 'allowed' => self::ALIGNMENTS ],
			],
		],

		'dropdown_panel' => [
			'anchor' => 'aae_mdp_anchor',
			'prefix' => 'aae_mdp_',
			'fields' => [
				'bg'            => [ 'var' => '--aae-menu-dropdown-bg',             'kind' => 'color' ],
				'panel_padding' => [ 'var' => '--aae-menu-dropdown-panel-padding',  'kind' => 'px' ],
				'min_width'     => [ 'var' => '--aae-menu-dropdown-min-width',      'kind' => 'px' ],
				'radius'        => [ 'var' => '--aae-menu-dropdown-radius',         'kind' => 'px' ],
				'border_width'  => [ 'var' => '--aae-menu-dropdown-border-width',   'kind' => 'px' ],
				'border_style'  => [ 'var' => '--aae-menu-dropdown-border-style',   'kind' => 'enum', 'allowed' => self::BORDER_STYLES ],
				'border_color'  => [ 'var' => '--aae-menu-dropdown-border-color',   'kind' => 'color' ],
			],
		],

		'dropdown_items' => [
			'anchor' => 'aae_mdi_anchor',
			'prefix' => 'aae_mdi_',
			'fields' => [
				'bg'               => [ 'var' => '--aae-menu-dropdown-item-bg',          'kind' => 'color' ],
				'text_color'       => [ 'var' => '--aae-menu-dropdown-text-color',       'kind' => 'color' ],
				'hover_bg'         => [ 'var' => '--aae-menu-dropdown-hover-bg',         'kind' => 'color' ],
				'hover_text_color' => [ 'var' => '--aae-menu-dropdown-hover-text-color', 'kind' => 'color' ],
				'padding_x'        => [ 'var' => '--aae-menu-dropdown-padding-x',        'kind' => 'px' ],
				'padding_y'        => [ 'var' => '--aae-menu-dropdown-padding-y',        'kind' => 'px' ],
				'gap'              => [ 'var' => '--aae-menu-dropdown-item-gap',         'kind' => 'px' ],
				'radius'           => [ 'var' => '--aae-menu-dropdown-item-radius',      'kind' => 'px' ],
			],
		],

		'toggle' => [
			'anchor' => 'aae_mtg_anchor',
			'prefix' => 'aae_mtg_',
			'fields' => [
				'color'     => [ 'var' => '--aae-menu-toggle-color',     'kind' => 'color' ],
				'bg'        => [ 'var' => '--aae-menu-toggle-bg',        'kind' => 'color' ],
				'hover_bg'  => [ 'var' => '--aae-menu-toggle-hover-bg',  'kind' => 'color' ],
				'size'      => [ 'var' => '--aae-menu-toggle-size',      'kind' => 'px' ],
				'padding'   => [ 'var' => '--aae-menu-toggle-padding',   'kind' => 'px' ],
				'icon_size' => [ 'var' => '--aae-menu-toggle-icon-size', 'kind' => 'px' ],
				'radius'    => [ 'var' => '--aae-menu-toggle-radius',    'kind' => 'px' ],
			],
		],

		'hamburger' => [
			'anchor' => 'aae_mhb_anchor',
			'prefix' => 'aae_mhb_',
			'fields' => [
				'color'         => [ 'var' => '--aae-menu-hamburger-color',         'kind' => 'color' ],
				'bg'            => [ 'var' => '--aae-menu-hamburger-bg',            'kind' => 'color' ],
				'hover_bg'      => [ 'var' => '--aae-menu-hamburger-hover-bg',      'kind' => 'color' ],
				'size'          => [ 'var' => '--aae-menu-hamburger-size',          'kind' => 'px' ],
				'radius'        => [ 'var' => '--aae-menu-hamburger-radius',        'kind' => 'px' ],
				'border_width'  => [ 'var' => '--aae-menu-hamburger-border-width',  'kind' => 'px' ],
				'border_color'  => [ 'var' => '--aae-menu-hamburger-border-color',  'kind' => 'color' ],
				'bar_width'     => [ 'var' => '--aae-menu-hamburger-bar-width',     'kind' => 'px' ],
				'bar_thickness' => [ 'var' => '--aae-menu-hamburger-bar-thickness', 'kind' => 'px' ],
				'bar_gap'       => [ 'var' => '--aae-menu-hamburger-bar-gap',       'kind' => 'px' ],
			],
		],

		'drawer_header' => [
			'anchor' => 'aae_mdh_anchor',
			'prefix' => 'aae_mdh_',
			'fields' => [
				'logo_width'   => [ 'var' => '--aae-menu-drawer-logo-width',          'kind' => 'px' ],
				'label_color'  => [ 'var' => '--aae-menu-drawer-label-color',         'kind' => 'color' ],
				'label_size'   => [ 'var' => '--aae-menu-drawer-label-size',          'kind' => 'px' ],
				'label_weight' => [ 'var' => '--aae-menu-drawer-label-weight',        'kind' => 'enum', 'allowed' => self::FONT_WEIGHTS ],
				'border_width' => [ 'var' => '--aae-menu-drawer-header-border-width', 'kind' => 'px' ],
				'border_style' => [ 'var' => '--aae-menu-drawer-header-border-style', 'kind' => 'enum', 'allowed' => self::BORDER_STYLES ],
				'border_color' => [ 'var' => '--aae-menu-drawer-header-border-color', 'kind' => 'color' ],
			],
		],

		'drawer' => [
			'anchor' => 'aae_mdr_anchor',
			'prefix' => 'aae_mdr_',
			'fields' => [
				'width'        => [ 'var' => '--aae-menu-drawer-width',        'kind' => 'px' ],
				'bg'           => [ 'var' => '--aae-menu-drawer-bg',           'kind' => 'color' ],
				'overlay'      => [ 'var' => '--aae-menu-drawer-overlay',      'kind' => 'color' ],
				'border_width' => [ 'var' => '--aae-menu-drawer-border-width', 'kind' => 'px' ],
				'border_style' => [ 'var' => '--aae-menu-drawer-border-style', 'kind' => 'enum', 'allowed' => self::BORDER_STYLES ],
				'border_color' => [ 'var' => '--aae-menu-drawer-border-color', 'kind' => 'color' ],
			],
		],

		// Only the duration is a variable; the two Effect selects are
		// data-attributes menu.js branches on.
		'motion' => [
			'anchor' => 'aae_mmo_anchor',
			'prefix' => 'aae_mmo_',
			'fields' => [
				'transition_ms' => [ 'var' => '--aae-menu-transition', 'kind' => 'transition' ],
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
			'aae_mi_anchor'  => AAE_A_Menu_Items_Anchor_Prop_Type::class,
			'aae_ml_anchor'  => AAE_A_Menu_Layout_Anchor_Prop_Type::class,
			'aae_mdp_anchor' => AAE_A_Menu_Dropdown_Panel_Anchor_Prop_Type::class,
			'aae_mdi_anchor' => AAE_A_Menu_Dropdown_Items_Anchor_Prop_Type::class,
			'aae_mtg_anchor' => AAE_A_Menu_Toggle_Anchor_Prop_Type::class,
			'aae_mhb_anchor' => AAE_A_Menu_Hamburger_Anchor_Prop_Type::class,
			'aae_mdh_anchor' => AAE_A_Menu_Drawer_Header_Anchor_Prop_Type::class,
			'aae_mdr_anchor' => AAE_A_Menu_Drawer_Anchor_Prop_Type::class,
			'aae_mmo_anchor' => AAE_A_Menu_Motion_Anchor_Prop_Type::class,
		];
	}

	/** Every prop this layer owns, ready to merge into the widget's schema. */
	public static function props_schema(): array {
		$schema  = [];
		$anchors = self::anchor_prop_types();

		foreach ( self::SECTIONS as $section ) {
			$anchor_class = $anchors[ $section['anchor'] ] ?? null;
			if ( $anchor_class ) {
				$schema[ $section['anchor'] ] = $anchor_class::make()->default( '' );
			}

			foreach ( array_keys( $section['fields'] ) as $field ) {
				// Empty map, NOT a seeded desktop value: an unset responsive prop
				// must emit nothing so the legacy inline var keeps winning. The
				// panel shows the legacy value as its display default instead
				// (see menu-sections/fields.js).
				$schema[ $section['prefix'] . $field ] = \WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type::make()
					->default( [ 'desktop' => null ] );
			}
		}

		return $schema;
	}

	/** Flat `prop key => field meta` across every section. */
	private static function all_fields(): array {
		static $flat = null;
		if ( null !== $flat ) {
			return $flat;
		}

		$flat = [];
		foreach ( self::SECTIONS as $section ) {
			foreach ( $section['fields'] as $field => $meta ) {
				$flat[ $section['prefix'] . $field ] = $meta;
			}
		}

		return $flat;
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
		// transformer registered) — the envelopes have to be read unresolved,
		// exactly like the Atomic extension Render classes do.
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

		// Priority 5: ahead of anything that might want to override it, and the
		// same slot InteractionsMap uses for its own footer payload.
		add_action( 'wp_footer', [ self::class, 'print_blocks' ], 5 );
	}

	public static function print_blocks(): void {
		foreach ( self::$blocks as $id => $css ) {
			printf(
				'<style id="aae-mi-rs-%s">%s</style>',
				esc_attr( $id ),
				$css // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from sanitize_value(); see build_css().
			);
		}

		self::$blocks = [];
	}

	/**
	 * Build the whole <style> body for one element.
	 *
	 * Returns '' when the builder has not set a single responsive value, which
	 * is the case for every menu that existed before this feature shipped —
	 * that is what keeps the upgrade a no-op on live sites.
	 */
	private static function build_css( string $id, array $settings ): string {
		$selector = '.aae-a-menu[data-id="' . $id . '"]';
		$groups   = [];

		// Desktop — no media query.
		$desktop = self::declarations_for( $settings, 'desktop' );
		if ( '' !== $desktop ) {
			$groups[] = $selector . '{' . $desktop . '}';
		}

		foreach ( self::breakpoints() as $bp => $bp_data ) {
			$decls = self::declarations_for( $settings, $bp );
			if ( '' === $decls ) {
				continue;
			}

			$groups[] = sprintf(
				'@media(%s-width:%dpx){%s{%s}}',
				'min' === $bp_data['direction'] ? 'min' : 'max',
				$bp_data['value'],
				$selector,
				$decls
			);
		}

		return implode( '', $groups );
	}

	/** All declarations one breakpoint owns, as a CSS body string. */
	private static function declarations_for( array $settings, string $bp ): string {
		$out = '';

		foreach ( self::all_fields() as $prop => $meta ) {
			$map = self::envelope_to_map( $settings[ $prop ] ?? null );

			// OWN value only. A missing cell inherits through the CSS cascade
			// rather than being duplicated into every narrower query.
			if ( ! array_key_exists( $bp, $map ) ) {
				continue;
			}

			$value = self::sanitize_value( $map[ $bp ], $meta );
			if ( null === $value ) {
				continue;
			}

			// !important: the legacy value sits in the element's inline style
			// attribute, which no ordinary rule can outrank.
			$out .= $meta['var'] . ':' . $value . ' !important;';
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
	 * deliberately narrow: numbers become numbers, enums must be in the
	 * field's own allow-list, and colours are limited to the characters CSS
	 * colour notations actually use. `;`, `{`, `}` and `:` can never survive.
	 */
	private static function sanitize_value( $raw, array $meta ) {
		if ( null === $raw || '' === $raw ) {
			return null;
		}

		$kind = $meta['kind'];

		if ( 'px' === $kind ) {
			return is_numeric( $raw ) ? ( (float) $raw ) . 'px' : null;
		}

		// Duration only — the easing curve is fixed, matching the Twig, so a
		// builder can never inject an arbitrary transition value.
		if ( 'transition' === $kind ) {
			return is_numeric( $raw ) ? ( (float) $raw ) . 'ms cubic-bezier(.4,0,.2,1)' : null;
		}

		if ( 'enum' === $kind ) {
			$allowed = $meta['allowed'] ?? [];
			return in_array( (string) $raw, $allowed, true ) ? (string) $raw : null;
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
	 * Active non-desktop breakpoints as `key => [value, direction]`, ordered
	 * so narrower queries come last and therefore win.
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
				'mobile' => [ 'value' => 767,  'direction' => 'max' ],
			];
		}

		$min = array_filter( $active, static fn( $bp ) => 'min' === $bp['direction'] );
		$max = array_filter( $active, static fn( $bp ) => 'min' !== $bp['direction'] );

		// Widest first so the narrowest query is the last one to match.
		uasort( $max, static fn( $a, $b ) => $b['value'] <=> $a['value'] );

		return $min + $max;
	}
}
