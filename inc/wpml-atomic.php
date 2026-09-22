<?php
/**
 * WPML × the ATOMIC (V4) widgets — the translatable fields, derived.
 *
 * `inc/wpml-manager.php` hand-lists every classic (V3) widget's text
 * controls for `wpml_elementor_widgets_to_translate`. The atomic line has
 * ~100 element types across the two plugins, every one declares its panel
 * through `get_atomic_controls()` and its props through a typed schema — so
 * the list is READ, not written:
 *
 *   a control a builder TYPES into (text / textarea / an HTML editor)
 *   + bound to a string / html prop
 *   + that prop has no enum (a select over string values is a choice, not
 *     prose)
 *   + whose name is not an identifier (`_cssid`, `name`, `*_key`, …)
 *   = one translatable field.
 *
 * Every element registered under `e-aae-a-*` is walked — the free widgets
 * and the Pro ones the free registry registers on Pro's behalf (Loop
 * Filters, the Woo family, Stack Cards …) — so a widget shipped later is
 * covered the day it ships, with no list to keep in step.
 *
 * Two entries per type, because an atomic LEAF saves as `{elType:'widget',
 * widgetType:'e-…'}` while an atomic CONTAINER saves as `{elType:'e-…'}`:
 * WPML's Elementor integration matches an entry's `conditions` against the
 * element's own keys, so a container needs the `elType` spelling. (WPML
 * 4.6.x walks only elType widget/container and never reaches a container's
 * own text; 4.9+ reads e-flexbox and friends, and that is the version the
 * twin is for.)
 *
 * Every field is a `>` PATH into the saved `{$$type, value}` envelope — see
 * TEXT_PROPS. Proven against the real WPML 4.6.8 + String Translation
 * (E:\Local Testing\verify-wpml-live.php): register → translate → the bn
 * page renders the translation.
 *
 * Loaded from WPML_Manager::add_widgets_to_translate() — WPML present, the
 * filter firing — and nowhere else.
 *
 * @package Wealcoder\AnimationAddons
 */

namespace Wealcoder\AnimationAddons\INC\WPML;

defined( 'ABSPATH' ) || die();

class Atomic_Widgets {

	/**
	 * Control types a person types prose into → WPML editor type.
	 *
	 * Never VISUAL for an inline text: WPML runs `wpautop()` over a one-line
	 * VISUAL translation when it writes it back (StringFormat::useWpAutoP),
	 * which wraps a heading's text in `<p>` — measured on WPML 4.6.8. WPML's
	 * own e-heading entry is LINE for that reason; the rich ones are AREA (a
	 * textarea in the translation editor, no wpautop).
	 */
	const CONTROL_EDITORS = array(
		'text'            => 'LINE',
		'textarea'        => 'AREA',
		'html'            => 'AREA',
		'inline-editing'  => 'LINE', // Elementor's canvas editor (a heading's text, a label)
		'aae-inline-text' => 'AREA', // ours, the Advanced Heading's rich text
	);

	/**
	 * Prop-type keys that carry text, and WHERE the text sits inside the saved
	 * envelope — the `>` path WPML's Elementor integration walks
	 * (`Obj::pathOr( …, explode( '>', $field ), $element['settings'] )`). The
	 * envelope itself is an array, so a bare prop name never resolves to a
	 * string and WPML would skip the field in silence; the path is what makes
	 * it read. Shapes read out of Elementor 4.2.4's prop types:
	 *
	 *   string / html   {$$type:'string', value:'…'}                       → value
	 *   html-v2         {$$type:'html-v2', value:{content:'…'}}            → value>content
	 *   html-v3         {$$type:'html-v3', value:{content:{value:'…'}}}    → value>content>value
	 *   escaped-html    Elementor 4.3 replaces html-v3 with a flat string   → value
	 *
	 * WPML's own config for e-heading declares the 4.2 and 4.3 paths side by
	 * side with one `field_id`, so a string keeps its name across the
	 * Elementor update; an html-v3 prop here does the same.
	 */
	const TEXT_PROPS = array(
		'string'       => array( 'value' ),
		'html'         => array( 'value' ),
		'escaped-html' => array( 'value' ),
		'html-v2'      => array( 'value>content' ),
		'html-v3'      => array( 'value>content>value', 'value' ),
	);

	/**
	 * Line lists: `value|Label` (form select / radio options), `value:Label`
	 * (choices), `min..max|Label` (price buckets). The LABEL half is prose, the
	 * value half is not, and one textarea holds both — so they are offered as
	 * an AREA with the rule in the field's own name, rather than skipped.
	 */
	const LINE_LIST_PROPS = array( 'options', 'choices', 'buckets' );

	/**
	 * Prop names that are identifiers, keys or selectors, whatever their type.
	 * Matched as a whole name or a suffix.
	 */
	const SKIP_NAMES = array(
		'_cssid', 'name', 'id', 'key', 'slug', 'class', 'classes', 'attributes', 'tag', 'type', 'mode', 'source', 'taxonomy',
		'url_key', 'meta_key', 'acf_field', 'aae_field', 'target_grid', 'post_type', 'field', 'format', 'style', 'layout',
		'orderby', 'order', 'position', 'icon', 'selector', 'url', 'link', 'src', 'video', 'action', 'regex', 'pattern', 'match_field', 'wrapper', 'trigger', 'anchor', 'target', 'unit', 'currency',
		// numbers, dates, code and machine values that happen to be typed into a text box
		'min', 'max', 'step', 'max_size', 'max_files', 'min_length', 'accept', 'due_date', 'formula', 'svg_code',
		'svg_stroke', 'container', 'language', 'region', 'address', 'value', 'meta_key_exists', 'share_link',
	);

	/** Suffixes that mark an identifier-shaped prop (`_key`, `_id`, `_slug`, `_class`, `_type` …). */
	const SKIP_SUFFIXES = array( '_key', '_id', '_slug', '_class', '_classes', '_type', '_mode', '_source', '_format', '_url', '_selector', '_pattern', '_regex', '_field', '_name', '_color', '_link', '_code', '_start', '_end', '_from', '_to', '_exists' );

	/**
	 * The map, in the shape `wpml_elementor_widgets_to_translate` takes.
	 *
	 * @return array<string,array>
	 */
	public static function map(): array {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return array();
		}
		$out   = array();
		$types = self::atomic_types();
		foreach ( $types as $name => $instance ) {
			$fields = self::fields_of( $instance );
			if ( ! $fields ) {
				continue;
			}
			$label = method_exists( $instance, 'get_title' ) ? (string) $instance->get_title() : $name;
			foreach ( $fields as &$f ) {
				$f['type'] = $label . ': ' . $f['type'];
			}
			unset( $f );
			$out[ $name ] = array(
				'conditions' => array( 'widgetType' => $name ),
				'fields'     => $fields,
			);
			// The container spelling (see the header).
			if ( ! $instance instanceof \Elementor\Widget_Base ) {
				$out[ $name . '--element' ] = array(
					'conditions' => array( 'elType' => $name ),
					'fields'     => $fields,
				);
			}
		}
		return $out;
	}

	/**
	 * Every registered `e-aae-a-*` element, name => instance.
	 *
	 * @return array<string,object>
	 */
	public static function atomic_types(): array {
		$out = array();
		$p   = \Elementor\Plugin::$instance;
		if ( ! $p ) {
			return $out;
		}
		foreach ( (array) $p->widgets_manager->get_widget_types() as $name => $w ) {
			if ( 0 === strpos( (string) $name, 'e-aae-a-' ) ) {
				$out[ $name ] = $w;
			}
		}
		foreach ( (array) $p->elements_manager->get_element_types() as $name => $e ) {
			if ( 0 === strpos( (string) $name, 'e-aae-a-' ) ) {
				$out[ $name ] = $e;
			}
		}
		return $out;
	}

	/**
	 * The translatable fields of one element, from its panel + schema.
	 *
	 * @param object $instance an atomic widget or element
	 * @return array<int,array{field:string,type:string,editor_type:string}>
	 */
	public static function fields_of( $instance ): array {
		if ( ! method_exists( $instance, 'get_atomic_controls' ) || ! method_exists( $instance, 'get_props_schema' ) ) {
			return array();
		}
		try {
			$controls = (array) $instance->get_atomic_controls();
			$schema   = (array) $instance::get_props_schema();
		} catch ( \Throwable $e ) {
			return array();
		}
		$out  = array();
		$seen = array();
		self::walk( $controls, $schema, $out, $seen );
		self::canvas_edited( $schema, $out, $seen );
		return $out;
	}

	/**
	 * Text a builder edits ON THE CANVAS, with no panel control at all.
	 *
	 * A Nav item's label, a Loop Filter's heading label, a button's caption:
	 * their prop is the rich-text envelope (`html-v3` / `html-v2`), which
	 * exists for exactly one reason — Elementor's inline canvas editing — so
	 * a panel walk never sees it and the whole Nav was invisible to WPML
	 * (measured 2026-09-22). Every such prop is offered as AREA (it may carry
	 * inline markup; AREA is never wpautop'd, VISUAL is), unless it is
	 * already listed from a control or identifier-shaped.
	 *
	 * @param array<string,object>                        $schema
	 * @param array<int,array<string,string>>            $out
	 * @param array<string,bool>                          $seen
	 */
	private static function canvas_edited( array $schema, array &$out, array &$seen ): void {
		foreach ( $schema as $bind => $prop ) {
			$bind = (string) $bind;
			if ( '' === $bind || isset( $seen[ $bind ] ) || self::is_identifier( $bind ) ) {
				continue;
			}
			$tkey = self::text_key( $prop );
			if ( ! in_array( $tkey, array( 'html-v3', 'html-v2' ), true ) ) {
				continue;
			}
			// A DERIVED twin is not a second string. The Advanced Heading keeps
			// `content_html` — the interpreted form of `content`, recomputed from
			// it on every render — so offering it would list the same heading
			// twice and the translation typed into it would be overwritten at
			// render time. A prop named `<base>_html` beside a `<base>` that is
			// itself offered is that shape.
			$base = preg_replace( '/_html$/', '', $bind );
			if ( $base !== $bind && isset( $schema[ $base ] ) && null !== self::text_key( $schema[ $base ] ) ) {
				continue;
			}
			$seen[ $bind ] = true;
			$label = ucfirst( str_replace( '_', ' ', $bind ) );
			foreach ( self::TEXT_PROPS[ $tkey ] as $path ) {
				$out[] = array(
					'field'       => $bind . '>' . $path,
					'field_id'    => $bind,
					'type'        => $label,
					'editor_type' => 'AREA',
				);
			}
		}
	}

	private static function walk( array $items, array $schema, array &$out, array &$seen ): void {
		foreach ( $items as $item ) {
			if ( $item instanceof \Elementor\Modules\AtomicWidgets\Controls\Section ) {
				self::walk( (array) $item->get_items(), $schema, $out, $seen );
				continue;
			}
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_type' ) || ! method_exists( $item, 'get_bind' ) ) {
				continue;
			}
			$ctype = (string) $item->get_type();
			if ( ! isset( self::CONTROL_EDITORS[ $ctype ] ) ) {
				continue;
			}
			$bind = (string) $item->get_bind();
			if ( '' === $bind || isset( $seen[ $bind ] ) || ! isset( $schema[ $bind ] ) ) {
				continue;
			}
			$prop = $schema[ $bind ];
			$tkey = self::text_key( $prop );
			if ( null === $tkey || self::is_identifier( $bind ) ) {
				continue;
			}
			$seen[ $bind ] = true;
			$json  = $item->jsonSerialize();
			$label = isset( $json['value']['label'] ) && is_string( $json['value']['label'] ) && '' !== $json['value']['label'] ? $json['value']['label'] : $bind;
			$editor = self::CONTROL_EDITORS[ $ctype ];
			if ( in_array( strtolower( $bind ), self::LINE_LIST_PROPS, true ) ) {
				$editor = 'AREA';
				$label .= ' ' . __( '(one entry per line — translate the label after the separator, keep the value before it)', 'animation-addons-for-elementor' );
			}
			// One entry per envelope shape; `field_id` (the prop name) keeps the
			// string's NAME the same whichever shape the saved page carries.
			foreach ( self::TEXT_PROPS[ $tkey ] as $path ) {
				$out[] = array(
					'field'       => $bind . '>' . $path,
					'field_id'    => $bind,
					'type'        => $label,
					'editor_type' => $editor,
				);
			}
		}
	}

	/**
	 * The TEXT_PROPS key of a string-family prop with no enum, or null. A prop
	 * that accepts a dynamic tag is a UNION (string | dynamic) — the string
	 * member is the one that matters; when the saved value IS a dynamic tag
	 * the path resolves to an array and WPML skips it, which is right.
	 */
	public static function text_key( $prop ): ?string {
		if ( ! is_object( $prop ) || ! method_exists( $prop, 'get_key' ) ) {
			return null;
		}
		$key = (string) $prop::get_key();
		if ( 'union' === $key && method_exists( $prop, 'get_prop_types' ) ) {
			foreach ( (array) $prop->get_prop_types() as $member ) {
				$k = self::text_key( $member );
				if ( null !== $k ) {
					return $k;
				}
			}
			return null;
		}
		if ( ! isset( self::TEXT_PROPS[ $key ] ) ) {
			return null;
		}
		if ( method_exists( $prop, 'get_settings' ) ) {
			$settings = (array) $prop->get_settings();
			if ( ! empty( $settings['enum'] ) ) {
				return null;
			}
		}
		return $key;
	}

	/** Identifier-shaped prop names are never prose. */
	public static function is_identifier( string $name ): bool {
		$n = strtolower( $name );
		if ( in_array( $n, self::SKIP_NAMES, true ) ) {
			return true;
		}
		foreach ( self::SKIP_SUFFIXES as $suffix ) {
			if ( strlen( $n ) > strlen( $suffix ) && substr( $n, -strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}
		return false;
	}
}
