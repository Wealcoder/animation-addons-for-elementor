<?php
/**
 * Loop Filters — the authoriser.
 *
 * Visitor-driven filtering is the first time visitor input touches the Loop
 * Grid's query. This class is the gate: it turns "what the visitor sent" into
 * "what the saved page allows", and nothing else ever reaches build_query_args().
 *
 * The rule, in one line: THE VISITOR SENDS CHOICES. THE SAVED PAGE SAYS WHICH
 * CHOICES EXIST. A filter key is honoured only when a filter widget in the saved
 * `_elementor_data` targets this grid and declares that key; the value is then
 * resolved inside the widget's own declaration (a slug inside its taxonomy, a
 * value from its authored list, a sort key from its option list). Anything the
 * page does not declare is dropped silently — no error, because a stale shared
 * link must still open the page.
 *
 * Two inputs, one parser. The URL (`?aae_tax_area=gulshan,banani`) on the first
 * server render and the AJAX body (`filters={"aae_tax_area":"gulshan,banani"}`)
 * on a page change carry the SAME shape — a map of url_key => string — so the
 * authoriser has one code path and a rule added once holds on both.
 *
 * The output is a normalised `$filters` array handed to
 * AAE_A_Loop_Grid::build_query_args() as its own argument. It is deliberately
 * NOT smuggled through the settings blob: ajax_loop_post_data() (the editor
 * preview) passes client-sent settings straight into the builder, so a
 * `_filters` key there would let any `edit_posts` user hand-write a meta_query.
 *
 * WIDGET CONTRACT — the props the M2 filter widgets must expose. The authoriser
 * reads them off the saved element settings by these names:
 *
 *   common     target_grid (grid element id), url_key (default per type)
 *   tax        taxonomy, match ('any'|'all'), offer ('all'|'chosen'), terms
 *              (aae-query-chips JSON strings), include_children (bool)
 *   meta       source ('custom'|'acf'|'woo'), meta_key, mode ('range'|'choice'|
 *              'toggle'), value_type ('numeric'|'decimal'|'char'|'date'|
 *              'datetime'), min, max, choices (String_Array — "value" or
 *              "value|Label"), multi_stored (bool — serialized array, LIKE),
 *              toggle_compare ('exists'|'equals'), toggle_value,
 *              acf_field (ACF field key), woo_field ('price'|'rating'|'stock'|
 *              'onsale'|'featured')
 *   search     title_only (bool)
 *   author     offer ('all'|'chosen'), authors (aae-query-chips JSON strings)
 *   date       source ('publish'|'modified'|'meta'|'acf'), meta_key, acf_field,
 *              value_type ('ymd'|'date'|'datetime')
 *   sort       options (String_Array of JSON {key,label,orderby,order,meta_key,
 *              value_type})
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loop_Filter_Auth {

	public const TYPE_TAX    = 'e-aae-a-loop-filter-tax';
	public const TYPE_META   = 'e-aae-a-loop-filter-meta';
	public const TYPE_SEARCH = 'e-aae-a-loop-search';
	public const TYPE_AUTHOR = 'e-aae-a-loop-filter-author';
	public const TYPE_DATE   = 'e-aae-a-loop-filter-date';
	public const TYPE_SORT   = 'e-aae-a-loop-sort';

	public const FILTER_TYPES = [
		self::TYPE_TAX,
		self::TYPE_META,
		self::TYPE_SEARCH,
		self::TYPE_AUTHOR,
		self::TYPE_DATE,
		self::TYPE_SORT,
	];

	/** Rule 6 — hard caps on what a visitor may send. */
	public const MAX_PAYLOAD_BYTES = 4096;
	public const MAX_KEYS          = 20;
	public const MAX_VALUES        = 50;
	public const MAX_VALUE_LENGTH  = 200;
	public const MAX_SEARCH_LENGTH = 200;

	/** Meta compare types the widget may declare (`value_type` => WP_Meta_Query type). */
	private const META_TYPES = [
		'numeric'  => 'NUMERIC',
		'decimal'  => 'DECIMAL(19,4)',
		'char'     => 'CHAR',
		'date'     => 'DATE',
		'datetime' => 'DATETIME',
	];

	/** Rule 4 — orderby values a Sort option may name. `rand` is deliberately absent. */
	private const SORT_ORDERBY = [
		'date',
		'title',
		'menu_order',
		'ID',
		'modified',
		'comment_count',
		'name',
		'author',
		'relevance',
		'meta_value',
		'meta_value_num',
	];

	/** Per-document declaration cache: post id => [ grid id => declarations ]. */
	private static array $memo = [];

	/** Cached dashboard settings for loop grid filters. */
	private static ?array $settings = null;

	/* ------------------------------------------------------------------ */
	/* Declarations — what the saved page offers                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Every filter widget in $elements whose target_grid is $grid_id, as a
	 * normalised declaration list. Walks the whole tree once: a filter may sit
	 * anywhere on the page (sidebar, offcanvas), not inside the grid.
	 *
	 * @param array  $elements Saved element tree (`_elementor_data` shape).
	 * @param string $grid_id  The Loop Grid element id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function declarations( array $elements, string $grid_id ): array {
		$out = [];
		if ( '' === $grid_id ) {
			return $out;
		}

		$walk = static function ( $els ) use ( &$walk, &$out, $grid_id ) {
			foreach ( (array) $els as $el ) {
				if ( ! is_array( $el ) ) {
					continue;
				}
				$type = (string) ( $el['widgetType'] ?? ( $el['elType'] ?? '' ) );
				if ( in_array( $type, self::FILTER_TYPES, true ) ) {
					$s = AAE_A_Loop_Grid::unwrap( (array) ( $el['settings'] ?? [] ) );
					if ( (string) ( $s['target_grid'] ?? '' ) === $grid_id ) {
						$decl = self::declare( $type, $s, (string) ( $el['id'] ?? '' ) );
						if ( $decl ) {
							$out[] = $decl;
						}
					}
				}
				if ( ! empty( $el['elements'] ) ) {
					$walk( $el['elements'] );
				}
			}
		};
		$walk( $elements );

		/**
		 * Lets a plugin add or veto declarations for a grid. Anything returned
		 * here is trusted exactly as a saved widget is — it is a declaration of
		 * what MAY be filtered, never a filter value.
		 *
		 * @param array  $out     Declarations.
		 * @param string $grid_id Grid element id.
		 */
		return (array) apply_filters( 'aae/loop_grid/filter_declarations', $out, $grid_id );
	}

	/**
	 * Declarations for a grid inside a saved document, memoised per request.
	 * The frontend render and the AJAX handler both come through here.
	 */
	public static function declarations_for_document( int $post_id, string $grid_id ): array {
		if ( ! $post_id || '' === $grid_id ) {
			return [];
		}
		if ( isset( self::$memo[ $post_id ][ $grid_id ] ) ) {
			return self::$memo[ $post_id ][ $grid_id ];
		}

		$decls = [];
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$doc = \Elementor\Plugin::$instance->documents->get( $post_id );
			if ( $doc ) {
				$decls = self::declarations( (array) $doc->get_elements_data(), $grid_id );
			}
		}

		self::$memo[ $post_id ][ $grid_id ] = $decls;
		return $decls;
	}

	/** Drop the per-request memo (tests, or after a document save inside one request). */
	public static function reset_memo(): void {
		self::$memo = [];
		self::$settings = null;
	}

	/**
	 * One widget's settings -> one declaration, or null when the widget is not
	 * usable (no taxonomy, no meta key, ACF field gone…). A declaration that
	 * cannot be resolved must vanish rather than degrade into something wider.
	 */
	private static function declare( string $type, array $s, string $element_id ): ?array {
		switch ( $type ) {
			case self::TYPE_TAX:
				$taxonomy = sanitize_key( (string) ( $s['taxonomy'] ?? '' ) );
				if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
					return null;
				}
				$legacy_key = 'aae_tax_' . $taxonomy;
				return [
					'type'             => 'tax',
					'element_id'       => $element_id,
					'url_key'          => self::url_key( $s, self::default_url_key( 'tax', $taxonomy, $legacy_key ) ),
					'legacy_key'       => $legacy_key,
					'taxonomy'         => $taxonomy,
					'all'              => 'all' === ( $s['match'] ?? 'any' ),
					'chosen'           => 'chosen' === ( $s['offer'] ?? 'all' ) ? AAE_A_Loop_Grid::extract_ids( $s['terms'] ?? null ) : [],
					'include_children' => ! isset( $s['include_children'] ) || ! empty( $s['include_children'] ),
				];

			case self::TYPE_META:
				return self::declare_meta( $s, $element_id );

			case self::TYPE_SEARCH:
				$legacy_key = 'aae_s';
				return [
					'type'       => 'search',
					'element_id' => $element_id,
					'url_key'    => self::url_key( $s, self::default_url_key( 'search', 'search', $legacy_key ) ),
					'legacy_key' => $legacy_key,
					'title_only' => ! empty( $s['title_only'] ),
				];

			case self::TYPE_AUTHOR:
				$legacy_key = 'aae_author';
				return [
					'type'       => 'author',
					'element_id' => $element_id,
					'url_key'    => self::url_key( $s, self::default_url_key( 'author', 'author', $legacy_key ) ),
					'legacy_key' => $legacy_key,
					'chosen'     => 'chosen' === ( $s['offer'] ?? 'all' ) ? AAE_A_Loop_Grid::extract_ids( $s['authors'] ?? null ) : [],
				];

			case self::TYPE_DATE:
				return self::declare_date( $s, $element_id );

			case self::TYPE_SORT:
				$options = [];
				foreach ( (array) ( $s['options'] ?? [] ) as $raw ) {
					$o = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
					if ( ! is_array( $o ) || empty( $o['key'] ) ) {
						continue;
					}
					$key = sanitize_key( (string) $o['key'] );
					if ( '' === $key ) {
						continue;
					}
					$meta_key = sanitize_text_field( (string) ( $o['meta_key'] ?? '' ) );
					if ( '' !== $meta_key && 0 === strpos( $meta_key, '_elementor' ) ) {
						continue;
					}
					$options[ $key ] = [
						'orderby'    => (string) ( $o['orderby'] ?? 'date' ),
						'order'      => 'asc' === strtolower( (string) ( $o['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC',
						'meta_key'   => $meta_key,
						'value_type' => (string) ( $o['value_type'] ?? 'char' ),
					];
				}
				if ( ! $options ) {
					return null;
				}
				$legacy_key = 'aae_sort';
				return [
					'type'       => 'sort',
					'element_id' => $element_id,
					'url_key'    => self::url_key( $s, self::default_url_key( 'sort', 'sort', $legacy_key ) ),
					'legacy_key' => $legacy_key,
					'options'    => $options,
				];
		}

		return null;
	}

	private static function declare_meta( array $s, string $element_id ): ?array {
		$source = (string) ( $s['source'] ?? 'custom' );

		if ( 'woo' === $source ) {
			$field = sanitize_key( (string) ( $s['woo_field'] ?? '' ) );
			if ( ! Loop_Query_Woo::active() || ! in_array( $field, Loop_Query_Woo::FIELDS, true ) ) {
				return null;
			}
			$legacy_key = 'aae_' . $field;
			return [
				'type'       => 'woo',
				'element_id' => $element_id,
				'url_key'    => self::url_key( $s, self::default_url_key( 'woo', $field, $legacy_key ) ),
				'legacy_key' => $legacy_key,
				'field'      => $field,
			];
		}

		$mode = (string) ( $s['mode'] ?? 'range' );
		$decl = [
			'type'           => 'meta',
			'element_id'     => $element_id,
			'key'            => '',
			'mode'           => $mode,
			// A range compares numbers; a choice / toggle compares the stored
			// STRING unless the widget says otherwise. Defaulting a choice to
			// NUMERIC would CAST 'red' to 0 and match every row — measured.
			'value_type'     => (string) ( $s['value_type'] ?? ( 'range' === $mode ? 'numeric' : 'char' ) ),
			'min'            => null,
			'max'            => null,
			'choices'        => [],
			'multi_stored'   => ! empty( $s['multi_stored'] ),
			'toggle_compare' => (string) ( $s['toggle_compare'] ?? 'exists' ),
			'toggle_value'   => (string) ( $s['toggle_value'] ?? '1' ),
		];

		if ( 'acf' === $source ) {
			$acf = self::resolve_acf( (string) ( $s['acf_field'] ?? '' ) );
			if ( ! $acf ) {
				return null; // ACF off or the field is gone: no fallback to a wider rule.
			}
			$decl = array_merge( $decl, $acf );
		} else {
			$decl['key'] = sanitize_text_field( (string) ( $s['meta_key'] ?? '' ) );
			if ( isset( $s['min'] ) && is_numeric( $s['min'] ) ) {
				$decl['min'] = (float) $s['min'];
			}
			if ( isset( $s['max'] ) && is_numeric( $s['max'] ) ) {
				$decl['max'] = (float) $s['max'];
			}
			foreach ( (array) ( $s['choices'] ?? [] ) as $line ) {
				if ( ! is_string( $line ) ) {
					continue;
				}
				$value = trim( (string) strtok( $line, '|' ) );
				if ( '' !== $value ) {
					$decl['choices'][] = $value;
				}
			}
		}

		if ( '' === $decl['key'] || 0 === strpos( $decl['key'], '_elementor' ) ) {
			return null;
		}
		if ( ! in_array( $decl['mode'], [ 'range', 'choice', 'toggle' ], true ) ) {
			return null;
		}
		if ( ! isset( self::META_TYPES[ $decl['value_type'] ] ) ) {
			$decl['value_type'] = 'char';
		}
		if ( 'choice' === $decl['mode'] && ! $decl['choices'] && empty( $decl['open_choice'] ) ) {
			return null; // Rule 3: a choice filter with nothing authored offers nothing.
		}

		$legacy_key         = 'aae_meta_' . sanitize_key( $decl['key'] );
		$decl['url_key']    = self::url_key( $s, self::default_url_key( 'meta', sanitize_key( $decl['key'] ), $legacy_key ) );
		$decl['legacy_key'] = $legacy_key;
		return $decl;
	}

	private static function declare_date( array $s, string $element_id ): ?array {
		$source     = (string) ( $s['source'] ?? 'publish' );
		$legacy_key = 'aae_date';
		$decl       = [
			'type'       => 'date',
			'element_id' => $element_id,
			'url_key'    => self::url_key( $s, self::default_url_key( 'date', 'date', $legacy_key ) ),
			'legacy_key' => $legacy_key,
			'source'     => $source,
			'key'        => '',
			'value_type' => (string) ( $s['value_type'] ?? 'date' ),
		];

		if ( 'meta' === $source ) {
			$decl['key'] = sanitize_text_field( (string) ( $s['meta_key'] ?? '' ) );
		} elseif ( 'acf' === $source ) {
			$acf = self::resolve_acf( (string) ( $s['acf_field'] ?? '' ) );
			if ( ! $acf || ! in_array( $acf['value_type'], [ 'ymd', 'datetime', 'date' ], true ) ) {
				return null;
			}
			$decl['source']     = 'meta';
			$decl['key']        = $acf['key'];
			$decl['value_type'] = $acf['value_type'];
		} elseif ( ! in_array( $source, [ 'publish', 'modified' ], true ) ) {
			return null;
		}

		if ( 'meta' === $decl['source'] && '' === $decl['key'] ) {
			return null;
		}
		if ( ! in_array( $decl['value_type'], [ 'ymd', 'date', 'datetime' ], true ) ) {
			$decl['value_type'] = 'date';
		}
		return $decl;
	}

	/**
	 * An ACF field, translated into the meta declaration its storage format
	 * needs (spec §2b). Resolved at request time through acf_get_field(), never
	 * from a snapshot, so editing the field in ACF changes the filter next load.
	 *
	 * @return array|null Partial declaration (key, mode, value_type, min, max, choices, multi_stored) or null.
	 */
	public static function resolve_acf( string $field_key ): ?array {
		if ( '' === $field_key || ! function_exists( 'acf_get_field' ) ) {
			return null;
		}
		$field = acf_get_field( $field_key );
		if ( ! is_array( $field ) || empty( $field['name'] ) ) {
			return null;
		}

		$out = [
			'key'          => (string) $field['name'],
			'mode'         => 'range',
			'value_type'   => 'numeric',
			'min'          => null,
			'max'          => null,
			'choices'      => [],
			'multi_stored' => false,
		];

		switch ( (string) ( $field['type'] ?? '' ) ) {
			case 'number':
			case 'range':
				$out['min'] = is_numeric( $field['min'] ?? null ) ? (float) $field['min'] : null;
				$out['max'] = is_numeric( $field['max'] ?? null ) ? (float) $field['max'] : null;
				return $out;

			case 'select':
			case 'radio':
			case 'button_group':
			case 'checkbox':
				$out['mode']         = 'choice';
				$out['value_type']   = 'char';
				$out['choices']      = array_map( 'strval', array_keys( (array) ( $field['choices'] ?? [] ) ) );
				$out['multi_stored'] = 'checkbox' === $field['type'] || ! empty( $field['multiple'] );
				return $out['choices'] ? $out : null;

			case 'true_false':
				$out['mode']           = 'toggle';
				$out['value_type']     = 'char';
				$out['toggle_compare'] = 'equals';
				$out['toggle_value']   = '1';
				return $out;

			case 'date_picker':
				$out['value_type'] = 'ymd'; // stored as 20260910
				return $out;

			case 'date_time_picker':
				$out['value_type'] = 'datetime'; // stored as Y-m-d H:i:s
				return $out;

			case 'post_object':
			case 'relationship':
			case 'user':
				// Ids of related objects. The visitor supplies an id; it is checked
				// for existence in authorize() (a missing object resolves to nothing).
				$out['mode']         = 'choice';
				$out['value_type']   = 'char';
				$out['open_choice']  = 'user' === $field['type'] ? 'user' : 'post';
				$out['multi_stored'] = 'relationship' === $field['type'] || ! empty( $field['multiple'] );
				return $out;
		}

		return null; // taxonomy (use the Taxonomy Filter), repeater, group, flexible, …
	}

	private static function url_key( array $s, string $default ): string {
		$key = isset( $s['url_key'] ) && is_string( $s['url_key'] ) ? sanitize_key( $s['url_key'] ) : '';
		return '' !== $key ? $key : $default;
	}

	/**
	 * Read the loop grid filter settings stored via the admin dashboard.
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		if ( null === self::$settings ) {
			$saved = get_option( 'aae_loop_grid_settings' );
			if ( is_string( $saved ) ) {
				$decoded = json_decode( $saved, true );
				self::$settings = is_array( $decoded ) ? $decoded : [];
			} elseif ( is_array( $saved ) ) {
				self::$settings = $saved;
			} else {
				self::$settings = [];
			}
		}
		return self::$settings;
	}

	/**
	 * Reset the cached settings (useful during tests or after updates).
	 */
	public static function reset_settings(): void {
		self::$settings = null;
	}

	/**
	 * Resolve the default URL parameter key based on dashboard settings.
	 *
	 * Supported modes:
	 * - 'clean' (default): natural, human-friendly parameter names without prefixes (e.g. price, rating, sort, search, taxonomy slug).
	 * - 'prefixed': plugin-prefixed parameters (aae_price, aae_sort, aae_tax_*, etc.).
	 * - 'custom': user-configured parameter names and/or custom prefix.
	 *
	 * @param string $type            Filter type ('woo', 'tax', 'search', 'sort', 'author', 'date', 'meta').
	 * @param string $subject         Specific field, taxonomy, or meta key name.
	 * @param string $legacy_fallback The original prefixed fallback ('aae_price', etc.).
	 * @return string Sanitized URL parameter key.
	 */
	public static function default_url_key( string $type, string $subject, string $legacy_fallback ): string {
		$settings = self::get_settings();
		$mode     = $settings['param_mode'] ?? 'clean';

		if ( 'prefixed' === $mode ) {
			return $legacy_fallback;
		}

		if ( 'custom' === $mode ) {
			$custom_key = '';
			if ( 'woo' === $type && ! empty( $settings[ 'custom_' . $subject ] ) ) {
				$custom_key = sanitize_key( (string) $settings[ 'custom_' . $subject ] );
			} elseif ( ! empty( $settings[ 'custom_' . $type ] ) ) {
				$custom_key = sanitize_key( (string) $settings[ 'custom_' . $type ] );
			}

			if ( '' !== $custom_key ) {
				return $custom_key;
			}

			$prefix = isset( $settings['custom_prefix'] ) && is_string( $settings['custom_prefix'] )
				? sanitize_key( $settings['custom_prefix'] )
				: '';

			if ( '' !== $prefix ) {
				return $prefix . '_' . sanitize_key( $subject );
			}
		}

		// 'clean' mode (default):
		switch ( $type ) {
			case 'woo':
				return sanitize_key( $subject ); // price, rating, stock, onsale, featured
			case 'search':
				return 'search';
			case 'sort':
				return 'sort';
			case 'author':
				return 'author';
			case 'date':
				return 'date';
			case 'tax':
				return sanitize_key( $subject ); // category, product_cat, etc.
			case 'meta':
				return sanitize_key( $subject );
			default:
				return $legacy_fallback;
		}
	}

	/* ------------------------------------------------------------------ */
	/* Raw input                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Pull the declared url_keys out of a request array ($_GET, or a decoded
	 * AJAX `filters` object). Anything not declared is not even read.
	 *
	 * Supports legacy fallback keys for backward compatibility with older URLs.
	 *
	 * @return array<string, string> url_key => raw string
	 */
	public static function raw_from_request( array $declarations, array $request ): array {
		$raw = [];
		foreach ( $declarations as $d ) {
			$k = $d['url_key'];
			if ( ! isset( $request[ $k ] ) ) {
				if ( ! empty( $d['legacy_key'] ) && isset( $request[ $d['legacy_key'] ] ) ) {
					$raw_key = $d['legacy_key'];
				} else {
					continue;
				}
			} else {
				$raw_key = $k;
			}
			$v = $request[ $raw_key ];
			if ( is_array( $v ) ) {
				$scalars = [];
				foreach ( array_slice( $v, 0, self::MAX_VALUES ) as $item ) {
					if ( is_scalar( $item ) ) {
						$scalars[] = (string) $item;
					}
				}
				$v = implode( ',', $scalars );
			}
			if ( ! is_scalar( $v ) ) {
				continue;
			}
			$v = (string) wp_unslash( $v );
			if ( '' === $v ) {
				continue;
			}
			$raw[ $k ] = $v;
		}
		return $raw;
	}

	/**
	 * Decode the AJAX `filters` body under the rule-6 caps. Oversized or
	 * malformed -> null, and the endpoint answers 400.
	 */
	public static function decode_payload( $json ): ?array {
		if ( ! is_string( $json ) ) {
			return null;
		}
		if ( strlen( $json ) > self::MAX_PAYLOAD_BYTES ) {
			return null;
		}
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}
		if ( count( $decoded ) > self::MAX_KEYS ) {
			return null;
		}
		return $decoded;
	}

	/* ------------------------------------------------------------------ */
	/* Authorisation                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * Turn declared widgets + raw visitor values into the `$filters` array
	 * build_query_args() accepts. Empty array when nothing survives.
	 *
	 * @param array  $declarations From declarations().
	 * @param array  $raw          url_key => string (from raw_from_request()).
	 * @param string $post_type    The grid's post type — authors must publish it.
	 */
	public static function authorize( array $declarations, array $raw, string $post_type = 'post' ): array {
		if ( ! $declarations || ! $raw || count( $raw ) > self::MAX_KEYS ) {
			return [];
		}

		$f = [
			'tax'        => [],
			'meta'       => [],
			's'          => '',
			'title_only' => false,
			'author'     => [],
			'date'       => [],
			'sort'       => [],
			'woo'        => [],
			'active'     => [],
		];

		foreach ( $declarations as $d ) {
			$key = $d['url_key'];
			if ( ! isset( $raw[ $key ] ) ) {
				continue;
			}
			$value = mb_substr( (string) $raw[ $key ], 0, self::MAX_VALUE_LENGTH * 4 );

			switch ( $d['type'] ) {
				case 'tax':
					$ids = self::resolve_terms( $d, $value );
					if ( $ids ) {
						$f['tax'][ $d['taxonomy'] ] = [
							'ids'              => $ids,
							'all'              => (bool) $d['all'],
							'include_children' => (bool) $d['include_children'],
						];
						$f['active'][ $key ] = implode( ',', self::slugs_of( $ids, $d['taxonomy'] ) );
					}
					break;

				case 'meta':
					$clause = self::meta_clause( $d, $value );
					if ( $clause ) {
						$f['meta'][]         = $clause['clause'];
						$f['active'][ $key ] = $clause['active'];
					}
					break;

				case 'woo':
					$woo = Loop_Query_Woo::authorize_field( $d['field'], $value );
					if ( null !== $woo ) {
						$f['woo'][ $d['field'] ] = $woo['value'];
						$f['active'][ $key ]     = $woo['active'];
					}
					break;

				case 'search':
					$s = sanitize_text_field( mb_substr( $value, 0, self::MAX_SEARCH_LENGTH ) );
					if ( '' !== $s ) {
						$f['s']              = $s;
						$f['title_only']     = (bool) $d['title_only'];
						$f['active'][ $key ] = $s;
					}
					break;

				case 'author':
					$ids = self::resolve_authors( $d, $value, $post_type );
					if ( $ids ) {
						$f['author']         = $ids;
						$f['active'][ $key ] = implode( ',', self::nicenames_of( $ids ) );
					}
					break;

				case 'date':
					$date = self::date_clause( $d, $value );
					if ( $date ) {
						if ( isset( $date['meta'] ) ) {
							$f['meta'][] = $date['meta'];
						} else {
							$f['date'][] = $date['date'];
						}
						$f['active'][ $key ] = $date['active'];
					}
					break;

				case 'sort':
					$sort = self::resolve_sort( $d, $value, $post_type );
					if ( $sort ) {
						if ( isset( $sort['woo'] ) ) {
							$f['woo']['sort'] = $sort['woo'];
						} else {
							$f['sort'] = $sort['sort'];
						}
						$f['active'][ $key ] = $sort['active'];
					}
					break;
			}
		}

		return $f['active'] ? $f : [];
	}

	/** The saved-page filters for a grid rendered on the frontend: URL -> authorised. */
	public static function current( int $post_id, string $grid_id, string $post_type, array $request ): array {
		$decls = self::declarations_for_document( $post_id, $grid_id );
		if ( ! $decls ) {
			return [];
		}
		return self::authorize( $decls, self::raw_from_request( $decls, $request ), $post_type );
	}

	/* ------------------------------------------------------------------ */
	/* Resolvers                                                           */
	/* ------------------------------------------------------------------ */

	/** Split a comma list into at most MAX_VALUES trimmed, non-empty parts. */
	private static function parts( string $value ): array {
		$parts = array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' );
		return array_values( array_unique( array_slice( $parts, 0, self::MAX_VALUES ) ) );
	}

	/** Rule 2 — slugs resolve inside the widget's taxonomy, and inside its Chosen list if any. */
	private static function resolve_terms( array $d, string $value ): array {
		$ids = [];
		foreach ( self::parts( $value ) as $slug ) {
			$slug = sanitize_title( mb_substr( $slug, 0, self::MAX_VALUE_LENGTH ) );
			if ( '' === $slug ) {
				continue;
			}
			$term = get_term_by( 'slug', $slug, $d['taxonomy'] );
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			if ( ! empty( $d['chosen'] ) ) {
				$term_id = (int) $term->term_id;
				$chosen  = (array) $d['chosen'];
				if ( ! in_array( $term_id, $chosen, true ) ) {
					// WPML / Polylang translation fallback: check if term translates to any chosen term ID
					$matched = false;
					if ( has_filter( 'wpml_object_id' ) ) {
						foreach ( $chosen as $cid ) {
							if ( (int) apply_filters( 'wpml_object_id', (int) $cid, $d['taxonomy'], false ) === $term_id ) {
								$matched = true;
								break;
							}
						}
					} elseif ( function_exists( 'pll_get_term' ) ) {
						foreach ( $chosen as $cid ) {
							if ( (int) pll_get_term( (int) $cid ) === $term_id ) {
								$matched = true;
								break;
							}
						}
					}
					if ( ! $matched ) {
						continue;
					}
				}
			}
			$ids[] = (int) $term->term_id;
		}
		return array_values( array_unique( $ids ) );
	}

	private static function slugs_of( array $ids, string $taxonomy ): array {
		$slugs = [];
		foreach ( $ids as $id ) {
			$t = get_term( $id, $taxonomy );
			if ( $t instanceof \WP_Term ) {
				$slugs[] = $t->slug;
			}
		}
		return $slugs;
	}

	/** Rule 5 — nicenames resolve to people who publish this post type (or are chosen). */
	private static function resolve_authors( array $d, string $value, string $post_type ): array {
		$ids = [];
		foreach ( self::parts( $value ) as $nice ) {
			$nice = sanitize_title( mb_substr( $nice, 0, 60 ) );
			if ( '' === $nice ) {
				continue;
			}
			$user = get_user_by( 'slug', $nice );
			if ( ! $user instanceof \WP_User ) {
				continue;
			}
			$id = (int) $user->ID;
			if ( $d['chosen'] ) {
				if ( ! in_array( $id, $d['chosen'], true ) ) {
					continue;
				}
			} elseif ( (int) count_user_posts( $id, $post_type, true ) < 1 ) {
				continue;
			}
			$ids[] = $id;
		}
		return array_values( array_unique( $ids ) );
	}

	private static function nicenames_of( array $ids ): array {
		$out = [];
		foreach ( $ids as $id ) {
			$u = get_user_by( 'id', $id );
			if ( $u instanceof \WP_User ) {
				$out[] = $u->user_nicename;
			}
		}
		return $out;
	}

	/**
	 * Rule 3 — the widget fixes key, compare and type; the visitor supplies
	 * only the value, and even that is clamped to the authored bounds / list.
	 *
	 * @return array{clause: array, active: string}|null
	 */
	private static function meta_clause( array $d, string $value ): ?array {
		$type = self::META_TYPES[ $d['value_type'] ] ?? 'CHAR';

		switch ( $d['mode'] ) {
			case 'range':
				$range = self::parse_range( $value );
				if ( ! $range ) {
					return null;
				}
				[ $min, $max ] = $range;
				if ( null !== $d['min'] && null !== $min ) {
					$min = max( $min, (float) $d['min'] );
				}
				if ( null !== $d['max'] && null !== $max ) {
					$max = min( $max, (float) $d['max'] );
				}
				if ( null !== $min && null !== $max && $min > $max ) {
					return null;
				}
				$num_type = in_array( $type, [ 'NUMERIC', 'DECIMAL(19,4)' ], true ) ? $type : 'NUMERIC';
				if ( null !== $min && null !== $max ) {
					$clause = [ 'key' => $d['key'], 'value' => [ $min, $max ], 'compare' => 'BETWEEN', 'type' => $num_type ];
				} elseif ( null !== $min ) {
					$clause = [ 'key' => $d['key'], 'value' => $min, 'compare' => '>=', 'type' => $num_type ];
				} else {
					$clause = [ 'key' => $d['key'], 'value' => $max, 'compare' => '<=', 'type' => $num_type ];
				}
				return [
					'clause' => $clause,
					'active' => self::fmt_num( $min ) . '..' . self::fmt_num( $max ),
				];

			case 'choice':
				$picked = [];
				foreach ( self::parts( $value ) as $v ) {
					$v = sanitize_text_field( mb_substr( $v, 0, self::MAX_VALUE_LENGTH ) );
					if ( '' === $v ) {
						continue;
					}
					if ( ! empty( $d['open_choice'] ) ) {
						if ( ! ctype_digit( $v ) ) {
							continue;
						}
						$exists = 'user' === $d['open_choice']
							? get_user_by( 'id', (int) $v ) instanceof \WP_User
							: ( ( $p = get_post( (int) $v ) ) && 'publish' === $p->post_status );
						if ( ! $exists ) {
							continue;
						}
					} elseif ( ! in_array( $v, $d['choices'], true ) ) {
						continue;
					}
					$picked[] = $v;
				}
				if ( ! $picked ) {
					return null;
				}
				if ( ! empty( $d['multi_stored'] ) ) {
					// Serialized array storage (ACF checkbox / multi-select): the
					// documented pattern is LIKE '"value"', quotes included. A plain
					// IN here matches nothing, silently.
					$clause = [ 'relation' => 'OR' ];
					foreach ( $picked as $v ) {
						$clause[] = [ 'key' => $d['key'], 'value' => '"' . $v . '"', 'compare' => 'LIKE' ];
					}
				} else {
					$clause = [ 'key' => $d['key'], 'value' => $picked, 'compare' => 'IN', 'type' => $type ];
				}
				return [ 'clause' => $clause, 'active' => implode( ',', $picked ) ];

			case 'toggle':
				if ( '1' !== $value && 'true' !== $value && 'on' !== $value ) {
					return null;
				}
				$clause = 'equals' === $d['toggle_compare']
					? [ 'key' => $d['key'], 'value' => (string) $d['toggle_value'], 'compare' => '=' ]
					: [ 'key' => $d['key'], 'compare' => 'EXISTS' ];
				return [ 'clause' => $clause, 'active' => '1' ];
		}

		return null;
	}

	/**
	 * "10..500", "10..", "..500" -> [min|null, max|null]; anything else null.
	 *
	 * @return array{0: ?float, 1: ?float}|null
	 */
	public static function parse_range( string $value ): ?array {
		if ( ! preg_match( '/^\s*(-?\d+(?:\.\d+)?)?\s*\.\.\s*(-?\d+(?:\.\d+)?)?\s*$/', $value, $m ) ) {
			return null;
		}
		$min = isset( $m[1] ) && '' !== $m[1] ? (float) $m[1] : null;
		$max = isset( $m[2] ) && '' !== $m[2] ? (float) $m[2] : null;
		if ( null === $min && null === $max ) {
			return null;
		}
		return [ $min, $max ];
	}

	private static function fmt_num( ?float $n ): string {
		if ( null === $n ) {
			return '';
		}
		return rtrim( rtrim( number_format( $n, 4, '.', '' ), '0' ), '.' );
	}

	/**
	 * "2026-01-01..2026-03-31" (either side optional) -> a date_query entry or a
	 * meta BETWEEN in the field's own storage format.
	 *
	 * @return array{active: string, date?: array, meta?: array}|null
	 */
	private static function date_clause( array $d, string $value ): ?array {
		if ( ! preg_match( '/^\s*(\d{4}-\d{2}-\d{2})?\s*\.\.\s*(\d{4}-\d{2}-\d{2})?\s*$/', $value, $m ) ) {
			return null;
		}
		$after  = ! empty( $m[1] ) ? AAE_A_Loop_Grid::valid_date( $m[1] ) : null;
		$before = ! empty( $m[2] ) ? AAE_A_Loop_Grid::valid_date( $m[2] ) : null;
		if ( ! $after && ! $before ) {
			return null;
		}
		if ( $after && $before && $after > $before ) {
			return null;
		}
		$active = (string) $after . '..' . (string) $before;

		if ( 'meta' !== $d['source'] ) {
			$q = [ 'inclusive' => true, 'column' => 'modified' === $d['source'] ? 'post_modified' : 'post_date' ];
			if ( $after ) {
				$q['after'] = $after;
			}
			if ( $before ) {
				$q['before'] = $before . ' 23:59:59';
			}
			return [ 'date' => $q, 'active' => $active ];
		}

		switch ( $d['value_type'] ) {
			case 'ymd': // ACF date_picker: 20260910
				$lo   = $after ? str_replace( '-', '', $after ) : null;
				$hi   = $before ? str_replace( '-', '', $before ) : null;
				$type = 'NUMERIC';
				break;
			case 'datetime': // Y-m-d H:i:s
				$lo   = $after ? $after . ' 00:00:00' : null;
				$hi   = $before ? $before . ' 23:59:59' : null;
				$type = 'DATETIME';
				break;
			default: // Y-m-d
				$lo   = $after;
				$hi   = $before;
				$type = 'DATE';
		}

		if ( null !== $lo && null !== $hi ) {
			$clause = [ 'key' => $d['key'], 'value' => [ $lo, $hi ], 'compare' => 'BETWEEN', 'type' => $type ];
		} elseif ( null !== $lo ) {
			$clause = [ 'key' => $d['key'], 'value' => $lo, 'compare' => '>=', 'type' => $type ];
		} else {
			$clause = [ 'key' => $d['key'], 'value' => $hi, 'compare' => '<=', 'type' => $type ];
		}
		return [ 'meta' => $clause, 'active' => $active ];
	}

	/**
	 * Rule 4 — the visitor sends a KEY into the Sort widget's own option list
	 * and gets that option's orderby/order/meta_key. Never a raw orderby.
	 *
	 * @return array{active: string, sort?: array, woo?: array}|null
	 */
	private static function resolve_sort( array $d, string $value, string $post_type ): ?array {
		$key = sanitize_key( $value );
		if ( '' === $key || ! isset( $d['options'][ $key ] ) ) {
			return null;
		}
		$o       = $d['options'][ $key ];
		$orderby = (string) $o['orderby'];

		if ( in_array( $orderby, Loop_Query_Woo::SORT_KEYS, true ) ) {
			if ( ! Loop_Query_Woo::active() || 'product' !== $post_type ) {
				return null;
			}
			return [ 'woo' => [ 'key' => $orderby, 'order' => $o['order'] ], 'active' => $key ];
		}

		if ( ! in_array( $orderby, self::SORT_ORDERBY, true ) ) {
			return null;
		}

		$sort = [ 'orderby' => $orderby, 'order' => $o['order'], 'meta_key' => '', 'meta_type' => '' ];
		if ( in_array( $orderby, [ 'meta_value', 'meta_value_num' ], true ) ) {
			if ( '' === $o['meta_key'] || 0 === strpos( $o['meta_key'], '_elementor' ) ) {
				return null;
			}
			$sort['meta_key'] = $o['meta_key'];
			if ( 'meta_value' === $orderby && isset( self::META_TYPES[ $o['value_type'] ] ) ) {
				$sort['meta_type'] = self::META_TYPES[ $o['value_type'] ];
			}
		}
		return [ 'sort' => $sort, 'active' => $key ];
	}
}
