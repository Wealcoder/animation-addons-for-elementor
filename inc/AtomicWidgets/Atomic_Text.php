<?php
/**
 * The text envelope a core atomic widget accepts — ASKED, never assumed.
 *
 * Elementor 4.3 retyped the three core text props. `e-heading.title`,
 * `e-paragraph.paragraph` and `e-button.text` are now a union whose only
 * value member is `Escaped_Html_Prop_Type` — a FLAT string:
 *
 *     { $$type: 'escaped-html', value: 'Hello' }
 *
 * Up to 4.2 they took `html-v3`, the nested shape:
 *
 *     { $$type: 'html-v3', value: { content: { $$type: 'string', value: 'Hello' }, children: [] } }
 *
 * Measured on 4.3.0: the union refuses html-v3, a bare `string` envelope and a
 * plain PHP string. So every widget of ours that seeds a core child with the
 * old shape produces a child whose settings FAIL `Props_Parser::validate()` —
 * 47 children across 24 of our element types — and the symptom is silent: the
 * child renders Elementor's own default text, and a page written through the
 * abilities API is refused with `title: invalid_value`.
 *
 * DANGER — a blanket "html-v3 becomes escaped-html" is WRONG, and the site
 * measures it: OUR OWN text props were not retyped. `e-aae-a-nav-item.text`,
 * `e-aae-a-toggle-switcher-tab.text` and `e-aae-a-progressbar-label.text` are
 * unions that accept html-v3 and REFUSE escaped-html, while
 * `e-aae-a-social-share-item-title.paragraph` (it extends Atomic_Paragraph)
 * is the other way round. One rule cannot cover both, and a rule keyed on the
 * Elementor VERSION would be a second place to keep in step.
 *
 * So nothing here is hardcoded. It builds the candidate envelopes and asks the
 * prop type's own `validate()` which one it takes, which is correct on 4.2 and
 * on 4.3, correct for our props and Elementor's, and correct for whatever the
 * next retyping does — as long as the text is plain. A value that already
 * validates is returned UNTOUCHED.
 *
 * @package Wealcoder\AnimationAddons
 * @since   4.2.4
 */

namespace Wealcoder\AnimationAddons\AtomicWidgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Plugin as Elementor;

final class Atomic_Text {

	/**
	 * The envelope keys we know how to read and write, widest first. The order
	 * is the preference when a prop takes more than one of them: `escaped-html`
	 * leads because it is what Elementor's own default children now use.
	 */
	const SHAPES = array( 'escaped-html', 'html-v3', 'html-v2', 'html', 'string' );

	/** type.prop => the shape key that validates, '' = none, null = unknown type. */
	private static $resolved = array();

	/** type => props schema, memoised (Elementor does not cache it). */
	private static $schemas = array();

	/**
	 * The envelope `$type.$prop` wants for a run of plain text.
	 *
	 * Falls back to `html-v3` when the type is not registered yet (a widget
	 * asking during its own registration) — the shape every release before
	 * 4.3 took, so an unresolvable answer is the OLD answer, never a guess at
	 * a new one.
	 *
	 * @param string $type Element type, e.g. `e-paragraph`.
	 * @param string $prop Prop name, e.g. `paragraph`.
	 * @param string $text Plain text.
	 * @return array The `{ $$type, value }` envelope.
	 */
	public static function prop( string $type, string $prop, string $text ): array {
		$shape = self::shape_for( $type, $prop );

		return self::build( '' === $shape ? 'html-v3' : $shape, $text );
	}

	/** `e-heading.title`. */
	public static function heading( string $text ): array {
		return self::prop( 'e-heading', 'title', $text );
	}

	/** `e-paragraph.paragraph`. */
	public static function paragraph( string $text ): array {
		return self::prop( 'e-paragraph', 'paragraph', $text );
	}

	/** `e-button.text`. */
	public static function button( string $text ): array {
		return self::prop( 'e-button', 'text', $text );
	}

	/**
	 * The prop type for one of OUR text props edited by core's
	 * `Inline_Editing_Control`.
	 *
	 * From 4.3 that control binds `escapedHtmlPropTypeUtil` only, so on an
	 * `html-v3` prop the panel renders NO input at all — the label text box
	 * simply vanishes.
	 *
	 * So: escaped-html first, html-v3 KEPT in a union (the atomic-svg
	 * pattern). Do NOT drop html-v3 and lean on core's prop migration — it is
	 * cached per post by manifest hash, so a page already marked migrated is
	 * never revisited and every saved label renders EMPTY (measured on
	 * atomic4). The control reads an old html-v3 value through its
	 * `extractInlineHtmlContent()` fallback and writes escaped-html back on
	 * the first edit. Older Elementor without Escaped_Html: plain html-v3.
	 *
	 * @param string $default Plain default text.
	 * @return \Elementor\Modules\AtomicWidgets\PropTypes\Base\Prop_Type
	 */
	public static function inline_prop_type( string $default ) {
		$escaped = '\Elementor\Modules\AtomicWidgets\PropTypes\Escaped_Html_Prop_Type';
		if ( class_exists( $escaped ) ) {
			return \Elementor\Modules\AtomicWidgets\PropTypes\Union_Prop_Type::create_from(
				$escaped::make()->default( $default )
			)->add_prop_type( \Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type::make() );
		}

		return \Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type::make()->default(
			array(
				'content'  => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::generate( $default ),
				'children' => array(),
			)
		);
	}

	/**
	 * Re-shape every text envelope in a default-children tree.
	 *
	 * This is what a widget's `define_default_children()` wraps its return in.
	 * It walks each node, reads that node's OWN type, and converts only the
	 * settings that are a text envelope the prop does not accept. Anything
	 * else — a Classes prop, a Size, an already-valid envelope, a prop the
	 * schema does not declare — is passed through untouched.
	 *
	 * @param array $nodes Elementor node arrays.
	 * @return array The same tree.
	 */
	public static function children( array $nodes ): array {
		foreach ( $nodes as $i => $node ) {
			if ( is_array( $node ) ) {
				$nodes[ $i ] = self::node( $node );
			}
		}

		return $nodes;
	}

	/**
	 * Re-shape ONE node (and its descendants).
	 *
	 * For a builder that returns a single element rather than a list.
	 *
	 * @param array $node Elementor node array.
	 * @return array
	 */
	public static function one( array $node ): array {
		return self::node( $node );
	}

	/**
	 * Re-shape one settings array against a known element type.
	 *
	 * For a caller that holds the settings without a node around them — the
	 * Post Image caption writer, for instance.
	 *
	 * @param string $type     Element type.
	 * @param array  $settings Settings array.
	 * @return array
	 */
	public static function settings( string $type, array $settings ): array {
		$schema = self::schema( $type );
		if ( null === $schema ) {
			return $settings;
		}

		foreach ( $settings as $prop => $value ) {
			if ( ! isset( $schema[ $prop ] ) || ! self::is_text_envelope( $value ) ) {
				continue;
			}

			$prop_type = $schema[ $prop ];
			if ( ! is_object( $prop_type ) || ! method_exists( $prop_type, 'validate' ) ) {
				continue;
			}

			// Already acceptable — leave it exactly as the caller wrote it.
			if ( $prop_type->validate( $value ) ) {
				continue;
			}

			$shape = self::shape_for( $type, $prop );
			if ( '' === $shape ) {
				continue;
			}

			$settings[ $prop ] = self::build( $shape, self::text_of( $value ) );
		}

		return $settings;
	}

	/* ------------------------------------------------------------------ inner */

	/** One node plus its descendants. */
	private static function node( array $node ): array {
		$type = (string) ( $node['widgetType'] ?? '' );
		if ( '' === $type ) {
			$type = (string) ( $node['elType'] ?? '' );
		}

		if ( '' !== $type && 'widget' !== $type && ! empty( $node['settings'] ) && is_array( $node['settings'] ) ) {
			$node['settings'] = self::settings( $type, $node['settings'] );
		}

		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$node['elements'] = self::children( $node['elements'] );
		}

		return $node;
	}

	/**
	 * Which of SHAPES this prop validates, or '' when none does (or the type
	 * is unknown). Memoised per request — a Loop Grid of twelve cards must not
	 * rebuild `e-paragraph`'s schema twelve times.
	 */
	private static function shape_for( string $type, string $prop ): string {
		$key = $type . '.' . $prop;
		if ( isset( self::$resolved[ $key ] ) ) {
			return self::$resolved[ $key ];
		}

		self::$resolved[ $key ] = '';

		$schema = self::schema( $type );
		if ( null === $schema || ! isset( $schema[ $prop ] ) ) {
			return '';
		}

		$prop_type = $schema[ $prop ];
		if ( ! is_object( $prop_type ) || ! method_exists( $prop_type, 'validate' ) ) {
			return '';
		}

		foreach ( self::SHAPES as $shape ) {
			if ( $prop_type->validate( self::build( $shape, 'x' ) ) ) {
				self::$resolved[ $key ] = $shape;
				break;
			}
		}

		return self::$resolved[ $key ];
	}

	/** The props schema of an element type, or null when it is not registered. */
	private static function schema( string $type ) {
		if ( array_key_exists( $type, self::$schemas ) ) {
			return self::$schemas[ $type ];
		}

		self::$schemas[ $type ] = null;

		if ( ! class_exists( '\Elementor\Plugin' ) || ! Elementor::$instance ) {
			return null;
		}

		$element = null;
		if ( Elementor::$instance->widgets_manager ) {
			$element = Elementor::$instance->widgets_manager->get_widget_types( $type );
		}
		if ( ! $element && Elementor::$instance->elements_manager ) {
			$element = Elementor::$instance->elements_manager->get_element_types( $type );
		}
		if ( ! $element ) {
			return null;
		}

		$class = get_class( $element );
		if ( ! method_exists( $class, 'get_props_schema' ) ) {
			return null;
		}

		self::$schemas[ $type ] = $class::get_props_schema();

		return self::$schemas[ $type ];
	}

	/** Is this value one of the text envelopes we know how to read? */
	private static function is_text_envelope( $value ): bool {
		return is_array( $value )
			&& isset( $value['$$type'] )
			&& in_array( $value['$$type'], self::SHAPES, true );
	}

	/** The plain text inside any of the envelopes. */
	private static function text_of( $value ): string {
		$key   = (string) ( $value['$$type'] ?? '' );
		$inner = $value['value'] ?? '';

		if ( 'html-v3' === $key ) {
			$content = is_array( $inner ) ? ( $inner['content'] ?? '' ) : '';
			return is_array( $content ) ? (string) ( $content['value'] ?? '' ) : (string) $content;
		}

		if ( 'html-v2' === $key ) {
			return is_array( $inner ) ? (string) ( $inner['content'] ?? '' ) : '';
		}

		return is_string( $inner ) ? $inner : '';
	}

	/** One envelope of the named shape around plain text. */
	private static function build( string $shape, string $text ): array {
		if ( 'html-v3' === $shape ) {
			return array(
				'$$type' => 'html-v3',
				'value'  => array(
					'content'  => array(
						'$$type' => 'string',
						'value'  => $text,
					),
					'children' => array(),
				),
			);
		}

		if ( 'html-v2' === $shape ) {
			return array(
				'$$type' => 'html-v2',
				'value'  => array(
					'content'  => $text,
					'children' => array(),
				),
			);
		}

		return array(
			'$$type' => $shape,
			'value'  => $text,
		);
	}
}
