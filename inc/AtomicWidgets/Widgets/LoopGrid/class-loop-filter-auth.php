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

	/**
	 * Widgets that READ the filter state instead of contributing to it.
	 *
	 * They declare nothing, so they are deliberately absent from FILTER_TYPES —
	 * the authoriser must never read a value for a widget that offers no
	 * choices. But they still describe the filtered result set, so a filter
	 * change makes them stale exactly as a filter widget goes stale, and the
	 * AJAX response has to send them back too.
	 */
	public const TYPE_RESULT_COUNT   = 'e-aae-a-loop-result-count';
	public const TYPE_ACTIVE_FILTERS = 'e-aae-a-loop-active-filters';

	public const READOUT_TYPES = [
		self::TYPE_RESULT_COUNT,
		self::TYPE_ACTIVE_FILTERS,
	];

	/**
	 * Everything a filter change invalidates: the controls AND the readouts.
	 *
	 * One list, because "which elements does the endpoint re-render" has one
	 * answer and two callers — free's AJAX handler and Pro's decision about
	 * whether a page gets the instant runtime at all.
	 */
	public const RERENDER_TYPES = [
		self::TYPE_TAX,
		self::TYPE_META,
		self::TYPE_SEARCH,
		self::TYPE_AUTHOR,
		self::TYPE_DATE,
		self::TYPE_SORT,
		self::TYPE_RESULT_COUNT,
		self::TYPE_ACTIVE_FILTERS,
	];

	/**
	 * The ids of every element in $elements that this grid's filter change
	 * invalidates — the declared filter widgets plus the readouts pointed at it.
	 *
	 * A readout has no declaration (it offers no choices), so it cannot be found
	 * the way a filter widget is. It is matched here the same way `declarations()`
	 * matches a filter's target: an explicit `target_grid`, or an empty one
	 * meaning "the grid on this page".
	 *
	 * @param array  $elements Saved element tree.
	 * @param string $grid_id  The Loop Grid element id.
	 * @return array<string, true> element id => true
	 */
	public static function rerender_ids( array $elements, string $grid_id ): array {
		$ids = [];
		foreach ( self::declarations( $elements, $grid_id ) as $decl ) {
			$id = (string) ( $decl['element_id'] ?? '' );
			if ( '' !== $id ) {
				$ids[ $id ] = true;
			}
		}

		$walk = static function ( $els ) use ( &$walk, &$ids, $grid_id ) {
			foreach ( (array) $els as $el ) {
				if ( ! is_array( $el ) ) {
					continue;
				}
				$type = (string) ( $el['widgetType'] ?? ( $el['elType'] ?? '' ) );
				if ( in_array( $type, self::READOUT_TYPES, true ) ) {
					$s      = AAE_A_Loop_Grid::unwrap( (array) ( $el['settings'] ?? [] ) );
					$target = trim( (string) ( $s['target_grid'] ?? '' ) );
					$id     = (string) ( $el['id'] ?? '' );
					if ( '' !== $id && ( '' === $target || $target === $grid_id ) ) {
						$ids[ $id ] = true;
					}
				}
				if ( ! empty( $el['elements'] ) ) {
					$walk( $el['elements'] );
				}
			}
		};
		$walk( $elements );

		return $ids;
	}

	/** Rule 6 — hard caps on what a visitor may send. */
	public const MAX_PAYLOAD_BYTES = 4096;
	public const MAX_KEYS          = 20;
	public const MAX_VALUES        = 50;
	public const MAX_VALUE_LENGTH  = 200;
	public const MAX_SEARCH_LENGTH = 200;

	/**
	 * The value an unresolvable open-choice id is replaced by, so that it
	 * matches nothing instead of dropping its filter.
	 *
	 * An "open choice" filter (an ACF relationship or user field) cannot carry
	 * a whitelist — its legitimate values are every post or user id — so the
	 * only check available is whether the id resolves. Refusing the VALUE the
	 * way every other resolver does would then drop the whole clause, and a
	 * filter that contributes nothing returns the UNFILTERED grid, which is
	 * visibly different from the empty one a real-but-unused id produces. Those
	 * two responses together answer "does user id N exist" for anyone who can
	 * load the page, which is precisely the user-enumeration oracle the author
	 * resolver refuses ids to avoid.
	 *
	 * So both cases answer with the same empty grid. `-1` is the sentinel
	 * because it is never a post or user id and is safe in every shape this
	 * clause takes: `CAST('-1' AS SIGNED)` is `-1` under a numeric compare, it
	 * is itself under CHAR, and `"-1"` appears in no serialized id array.
	 */
	private const NO_MATCH_ID = '-1';

	/** Meta compare types the widget may declare (`value_type` => WP_Meta_Query type). */
	private const META_TYPES = [
		'numeric'  => 'NUMERIC',
		'decimal'  => 'DECIMAL(19,4)',
		'char'     => 'CHAR',
		'date'     => 'DATE',
		'datetime' => 'DATETIME',
	];

	/**
	 * The sort options a Sort widget can offer by NAME instead of by writing the
	 * orderby out itself.
	 *
	 * It lives here, beside the whitelist it draws from, because "which sorts
	 * exist" is an authorisation question before it is a panel question — a key
	 * the visitor sends has to mean the same thing on the server as it did in
	 * the builder's switch. The `options` JSON contract is still read and still
	 * wins, for the sorts nobody can enumerate in advance (a meta key).
	 *
	 * price / popularity / rating are `Loop_Query_Woo::SORT_KEYS`, refused by
	 * resolve_sort() unless WooCommerce is running AND the grid queries products
	 * — so offering them on a blog grid costs nothing but an option that never
	 * resolves.
	 */
	public const SORT_CATALOGUE = [
		'newest'     => [ 'orderby' => 'date', 'order' => 'DESC' ],
		'oldest'     => [ 'orderby' => 'date', 'order' => 'ASC' ],
		'title_asc'  => [ 'orderby' => 'title', 'order' => 'ASC' ],
		'title_desc' => [ 'orderby' => 'title', 'order' => 'DESC' ],
		'commented'  => [ 'orderby' => 'comment_count', 'order' => 'DESC' ],
		'menu_order' => [ 'orderby' => 'menu_order', 'order' => 'ASC' ],
		'price_asc'  => [ 'orderby' => 'price', 'order' => 'ASC' ],
		'price_desc' => [ 'orderby' => 'price', 'order' => 'DESC' ],
		'popularity' => [ 'orderby' => 'popularity', 'order' => 'DESC' ],
		'rating'     => [ 'orderby' => 'rating', 'order' => 'DESC' ],
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

	/** Per-document element tree cache: post id => elements. */
	private static array $elements = [];

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
					// An EMPTY target means "whichever grid is on this page",
					// which is the common case and what lets a filter widget
					// work the moment it is dropped. It is not a loosening of
					// the gate: the declaration still offers exactly what this
					// widget declares, and a page with two grids is where the
					// builder fills the field in.
					$target = trim( (string) ( $s['target_grid'] ?? '' ) );
					if ( '' === $target || $target === $grid_id ) {
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

		$decls = self::declarations( self::elements_for( $post_id ), $grid_id );

		self::$memo[ $post_id ][ $grid_id ] = $decls;
		return $decls;
	}

	/**
	 * The decoded element tree for a document, once per request. Document::
	 * get_elements_data() is a get_post_meta plus a json_decode every time it is
	 * called and Elementor memoises neither, so a page holding several grids
	 * would otherwise decode the whole blob once more per grid.
	 */
	private static function elements_for( int $post_id ): array {
		if ( isset( self::$elements[ $post_id ] ) ) {
			return self::$elements[ $post_id ];
		}

		$elements = [];
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$doc = \Elementor\Plugin::$instance->documents->get( $post_id );
			if ( $doc ) {
				$elements = (array) $doc->get_elements_data();
			}
		}

		self::$elements[ $post_id ] = $elements;
		return $elements;
	}

	/**
	 * Hand over a tree the caller has already decoded, so current() does not
	 * decode it a second time. Purely an optimisation: the declarations are read
	 * from it exactly as they would be from the document.
	 */
	public static function prime_document( int $post_id, array $elements ): void {
		if ( $post_id > 0 && ! isset( self::$elements[ $post_id ] ) ) {
			self::$elements[ $post_id ] = $elements;
		}
	}

	/** Drop the per-request memo (tests, or after a document save inside one request). */
	public static function reset_memo(): void {
		self::$memo        = [];
		self::$elements    = [];
		self::$settings    = null;
		self::$request_url = '';
		// Same per-request family: a summary was computed FROM these
		// declarations, so keeping it after they are dropped is how a test
		// asserts against the state it thought it had cleared.
		AAE_A_Loop_Grid::reset_summaries();
	}

	/* ------------------------------------------------------------------ */
	/* The page this render is FOR                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * The page whose filter state we are rendering, as `path?query`.
	 *
	 * Empty means "the live request", which is every ordinary page view. It is
	 * set only by the AJAX endpoint, where `$_SERVER['REQUEST_URI']` is
	 * `/wp-admin/admin-ajax.php` and `$_GET` is empty — so a filter widget
	 * re-rendered there would read no active terms and build every link
	 * pointing at admin-ajax. One value, read by both halves of that question.
	 */
	private static string $request_url = '';

	/**
	 * Render as though the visitor were on $url.
	 *
	 * The value arrives from the browser, so the only thing it is ever allowed
	 * to decide is WHICH PAGE's links we draw — never which host they point at.
	 * An absolute URL is accepted only for this site; anything else, and
	 * anything with a traversal in it, resets to the live request rather than
	 * being half-honoured.
	 *
	 * @param string $url Absolute same-site URL, or a site-relative path.
	 * @return bool Whether it was accepted. A caller that was handed a URL and
	 *              gets false back should refuse the request rather than render
	 *              the live one — silently rendering a different page than the
	 *              one asked for is the confusing failure.
	 */
	public static function set_request_url( string $url ): bool {
		self::$request_url = '';

		$url = trim( $url );
		if ( '' === $url || strlen( $url ) > 2000 ) {
			return false;
		}

		$parts = wp_parse_url( $url );
		if ( false === $parts || ! is_array( $parts ) ) {
			return false;
		}

		if ( ! empty( $parts['host'] ) ) {
			$home = wp_parse_url( home_url( '/' ) );
			if ( strtolower( (string) $parts['host'] ) !== strtolower( (string) ( $home['host'] ?? '' ) ) ) {
				return false;
			}
		}

		$path = isset( $parts['path'] ) ? '/' . ltrim( (string) $parts['path'], '/' ) : '/';
		if ( false !== strpos( $path, '..' ) ) {
			return false;
		}

		$query = isset( $parts['query'] ) ? (string) $parts['query'] : '';

		self::$request_url = '' !== $query ? $path . '?' . $query : $path;
		return true;
	}

	/**
	 * The URL a filter widget builds its links against. Defaults to the live
	 * request, which is exactly what `remove_query_arg()` would have used on
	 * its own — so an ordinary page view is unchanged.
	 */
	public static function request_url(): string {
		if ( '' !== self::$request_url ) {
			return self::$request_url;
		}
		return isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '/';
	}

	/**
	 * The query args of that URL, ALWAYS UNSLASHED — including the `$_GET`
	 * case, which WordPress leaves slashed. Callers therefore never unslash,
	 * and cannot get it right for one source and wrong for the other.
	 *
	 * @return array<string, mixed>
	 */
	public static function request_args(): array {
		if ( '' === self::$request_url ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter state, same footing as aae_page.
			return (array) wp_unslash( $_GET );
		}

		$query = (string) ( wp_parse_url( self::$request_url, PHP_URL_QUERY ) ?? '' );
		if ( '' === $query ) {
			return [];
		}

		$args = [];
		wp_parse_str( $query, $args );
		return (array) $args;
	}

	/**
	 * One widget's settings -> one declaration, or null when the widget is not
	 * usable (no taxonomy, no meta key, ACF field gone…). A declaration that
	 * cannot be resolved must vanish rather than degrade into something wider.
	 */
	private static function declare( string $type, array $s, string $element_id ): ?array {
		$decl = self::declare_type( $type, $s, $element_id );
		if ( null === $decl ) {
			return null;
		}

		// The builder's own heading for this filter, carried so a readout can
		// say "Category: Hoodies" instead of "product_cat: hoodies". Display
		// text only — nothing in the authorisation path reads it, and a label
		// can never widen what a filter allows.
		$decl['label'] = sanitize_text_field( (string) ( $s['title'] ?? '' ) );

		return $decl;
	}

	/** The per-type half of declare(). @see declare() */
	private static function declare_type( string $type, array $s, string $element_id ): ?array {
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
					'url_key'          => self::url_key( $s, self::default_url_key( 'tax', $taxonomy, $legacy_key ), $legacy_key ),
					'legacy_key'       => $legacy_key,
					'taxonomy'         => $taxonomy,
					'all'              => 'all' === ( $s['match'] ?? 'any' ),
					// The Chosen list is stored per taxonomy (`terms_<slug>`),
					// because the chips control resolves its taxonomy when
					// controls are built — once per widget TYPE, not per
					// instance — so one shared `terms` control could never know
					// which taxonomy this instance picked. A plain `terms` is
					// still read, for anything that sets the contract directly.
					'chosen'           => 'chosen' === ( $s['offer'] ?? 'all' )
						? AAE_A_Loop_Grid::extract_ids( $s[ 'terms_' . $taxonomy ] ?? ( $s['terms'] ?? null ) )
						: [],
					'include_children' => ! isset( $s['include_children'] ) || ! empty( $s['include_children'] ),
				];

			case self::TYPE_META:
				return self::declare_meta( $s, $element_id );

			case self::TYPE_SEARCH:
				$legacy_key = 'aae_s';
				return [
					'type'       => 'search',
					'element_id' => $element_id,
					'url_key'    => self::url_key( $s, self::default_url_key( 'search', 'search', $legacy_key ), $legacy_key ),
					'legacy_key' => $legacy_key,
					'title_only' => ! empty( $s['title_only'] ),
				];

			case self::TYPE_AUTHOR:
				$legacy_key = 'aae_author';
				return [
					'type'       => 'author',
					'element_id' => $element_id,
					'url_key'    => self::url_key( $s, self::default_url_key( 'author', 'author', $legacy_key ), $legacy_key ),
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
						// Carried only so a readout can NAME the active sort.
						// Nothing in the authorisation path reads it — an option
						// is chosen by key, and the label is display text the
						// builder typed.
						'label'      => sanitize_text_field( (string) ( $o['label'] ?? '' ) ),
						'orderby'    => (string) ( $o['orderby'] ?? 'date' ),
						'order'      => 'asc' === strtolower( (string) ( $o['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC',
						'meta_key'   => $meta_key,
						'value_type' => (string) ( $o['value_type'] ?? 'char' ),
					];
				}
				// The catalogue half of the contract: `sort_<key> => true` plus an
				// optional `label_<key>`. An explicit JSON option of the same key
				// wins — it is the more specific statement, and silently
				// overwriting it with the generic one is how a builder's custom
				// meta sort turns back into "Newest" without a word.
				foreach ( self::SORT_CATALOGUE as $cat_key => $spec ) {
					if ( empty( $s[ 'sort_' . $cat_key ] ) || isset( $options[ $cat_key ] ) ) {
						continue;
					}
					$options[ $cat_key ] = [
						'label'      => sanitize_text_field( (string) ( $s[ 'label_' . $cat_key ] ?? '' ) ),
						'orderby'    => $spec['orderby'],
						'order'      => $spec['order'],
						'meta_key'   => '',
						'value_type' => 'char',
					];
				}

				if ( ! $options ) {
					return null;
				}
				$legacy_key = 'aae_sort';
				return [
					'type'       => 'sort',
					'element_id' => $element_id,
					'url_key'    => self::url_key( $s, self::default_url_key( 'sort', 'sort', $legacy_key ), $legacy_key ),
					'legacy_key' => $legacy_key,
					'options'    => $options,
				];
		}

		return null;
	}

	/**
	 * The Min / Max a builder typed, or nothing.
	 *
	 * Both props default to 0, so `is_numeric()` alone reads an untouched pair
	 * as a real ceiling of zero — which clamps every range filter to "at most
	 * nothing" and matches no row on the site. The widget already settled the
	 * convention when it decided whether to draw a slider track
	 * (`$hi <= $lo` means "not typed"), and this is that same test on the
	 * authorising side so the track and the gate cannot disagree about whether
	 * bounds exist.
	 *
	 * @return array{0: ?float, 1: ?float}
	 */
	private static function authored_bounds( array $s ): array {
		$min = isset( $s['min'] ) && is_numeric( $s['min'] ) ? (float) $s['min'] : null;
		$max = isset( $s['max'] ) && is_numeric( $s['max'] ) ? (float) $s['max'] : null;

		if ( null !== $max && null !== $min && $max <= $min ) {
			return [ null, null ];
		}
		if ( null !== $max && null === $min && $max <= 0.0 ) {
			return [ null, null ];
		}

		return [ $min, $max ];
	}

	private static function declare_meta( array $s, string $element_id ): ?array {
		$source = (string) ( $s['source'] ?? 'custom' );

		if ( 'woo' === $source ) {
			$field = sanitize_key( (string) ( $s['woo_field'] ?? '' ) );
			if ( ! Loop_Query_Woo::active() || ! in_array( $field, Loop_Query_Woo::FIELDS, true ) ) {
				return null;
			}
			$legacy_key = 'aae_' . $field;
			$bounds     = self::authored_bounds( $s );
			return [
				'type'       => 'woo',
				'element_id' => $element_id,
				'url_key'    => self::url_key( $s, self::default_url_key( 'woo', $field, $legacy_key ), $legacy_key ),
				'legacy_key' => $legacy_key,
				'field'      => $field,
				// A WooCommerce price is a RANGE, so it has the same gate every
				// other range has — and until this it had none: the woo branch
				// returned before the bounds were read, and a price filter never
				// reaches meta_clause(), where the clamp lives. The panel calls
				// these fields the gate and the slider draws its track from
				// them, so a shop capped at 200 answered a hand-typed
				// ?price=300..400 with the products above it. Measured on the
				// shop demo.
				'min'        => $bounds[0],
				'max'        => $bounds[1],
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
			'choice_labels'  => [],
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
			[ $decl['min'], $decl['max'] ] = self::authored_bounds( $s );
			// One value per line, or an array of them. The panel's textarea
			// gives a string; a preset JSON and the documented contract give an
			// array. Casting a newline string with (array) would make the whole
			// block ONE choice, which matches nothing and says nothing.
			$choice_lines = $s['choices'] ?? [];
			if ( is_string( $choice_lines ) ) {
				$choice_lines = preg_split( '/
|
|
/', $choice_lines );
			}

			foreach ( (array) $choice_lines as $line ) {
				if ( ! is_string( $line ) ) {
					continue;
				}
				$value = trim( (string) strtok( $line, '|' ) );
				if ( '' === $value ) {
					continue;
				}
				$decl['choices'][] = $value;
				// "value|Label" — the label half, kept for the readouts only.
				// Authorisation still matches on the VALUE; a label can never
				// widen what is allowed.
				$label = trim( (string) substr( (string) $line, strlen( $value ) + 1 ) );
				if ( '' !== $label ) {
					$decl['choice_labels'][ $value ] = sanitize_text_field( $label );
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
		$decl['url_key']    = self::url_key( $s, self::default_url_key( 'meta', sanitize_key( $decl['key'] ), $legacy_key ), $legacy_key );
		$decl['legacy_key'] = $legacy_key;
		return $decl;
	}

	private static function declare_date( array $s, string $element_id ): ?array {
		$source     = (string) ( $s['source'] ?? 'publish' );
		$legacy_key = 'aae_date';
		$decl       = [
			'type'       => 'date',
			'element_id' => $element_id,
			'url_key'    => self::url_key( $s, self::default_url_key( 'date', 'date', $legacy_key ), $legacy_key ),
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

	private static function url_key( array $s, string $default, string $legacy = '' ): string {
		$key = isset( $s['url_key'] ) && is_string( $s['url_key'] ) ? sanitize_key( $s['url_key'] ) : '';
		return self::reserve_key( '' !== $key ? $key : $default, $legacy );
	}

	/**
	 * WordPress owns a great many query-string names, and a filter that borrows
	 * one does not merely fail to work — it hijacks the page it is standing on.
	 * `?author=2` adds post_author to the MAIN query, which a page cannot
	 * satisfy, so the request 404s before the grid renders at all (measured on
	 * a plain page: /sample-page/ answers 200, /sample-page/?author=2 answers
	 * 404). `search` is on that list too, and register_taxonomy() defaults
	 * `query_var` to the taxonomy NAME — which is exactly the spelling a clean
	 * mode taxonomy filter wants (`?product_cat=hoodies`).
	 *
	 * So every key comes through here: the clean defaults, a custom mode key and
	 * a builder's own `url_key` field alike. A reserved name falls back to the
	 * prefixed `aae_*` spelling, which is namespaced by construction and cannot
	 * collide. The fallback is deterministic per request, and both the URL and
	 * the AJAX body resolve through this same function, so the two never
	 * disagree about which key a filter answers to.
	 */
	private static function reserve_key( string $key, string $legacy ): string {
		$key = sanitize_key( $key );
		if ( '' === $key ) {
			return $legacy;
		}
		if ( ! self::is_reserved_key( $key ) ) {
			return $key;
		}
		return '' !== $legacy ? $legacy : 'aae_' . $key;
	}

	/** Is this query-string name one WordPress (or this plugin) already answers to? */
	public static function is_reserved_key( string $key ): bool {
		$key = sanitize_key( $key );
		return '' === $key || isset( self::reserved_keys()[ $key ] );
	}

	/**
	 * The reserved set, as a lookup map. Read from WordPress itself wherever
	 * possible so a core addition is covered without a release here; the
	 * hardcoded floor keeps an early or CLI call from being LESS safe than a
	 * real request, which is the direction that fails silently.
	 *
	 * @return array<string, true>
	 */
	private static function reserved_keys(): array {
		static $map = null;
		if ( null !== $map ) {
			return $map;
		}

		$names = [
			// WP::$public_query_vars, as of 7.1 — the floor, not the answer.
			'm', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's',
			'search', 'exact', 'sentence', 'calendar', 'page', 'paged', 'more',
			'tb', 'pb', 'author', 'order', 'orderby', 'year', 'monthnum', 'day',
			'hour', 'minute', 'second', 'name', 'category_name', 'tag', 'feed',
			'author_name', 'pagename', 'page_id', 'error', 'attachment',
			'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots',
			'favicon', 'taxonomy', 'term', 'cpage', 'post_type', 'embed',
			// Ours: the Loop Grid's own pagination key.
			'aae_page',
		];

		if ( isset( $GLOBALS['wp'] ) && $GLOBALS['wp'] instanceof \WP ) {
			$names = array_merge(
				$names,
				(array) $GLOBALS['wp']->public_query_vars,
				(array) $GLOBALS['wp']->private_query_vars
			);
		}

		// A taxonomy's or post type's own query var. Only the REGISTERED
		// query_var, never the object name: `category` registers as
		// `category_name`, so `?category=x` is free and clean mode may use it —
		// reserving the name instead would take the readable spelling away from
		// the taxonomies that can safely have it.
		if ( function_exists( 'get_taxonomies' ) ) {
			foreach ( get_taxonomies( [], 'objects' ) as $tax ) {
				if ( ! empty( $tax->query_var ) && is_string( $tax->query_var ) ) {
					$names[] = $tax->query_var;
				}
			}
		}
		if ( function_exists( 'get_post_types' ) ) {
			foreach ( get_post_types( [], 'objects' ) as $type ) {
				if ( ! empty( $type->query_var ) && is_string( $type->query_var ) ) {
					$names[] = $type->query_var;
				}
			}
		}

		/**
		 * Query-string names a Loop Filter may never claim.
		 *
		 * @param string[] $names Reserved names.
		 */
		$names = (array) apply_filters( 'aae/loop_grid/reserved_url_keys', $names );

		$map = [];
		foreach ( $names as $name ) {
			$name = sanitize_key( (string) $name );
			if ( '' !== $name ) {
				$map[ $name ] = true;
			}
		}
		return $map;
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
	 * The URL parameter key a filter answers to, before the reservation check.
	 *
	 * Three modes, set once for the site on the Loop Grid dashboard card:
	 * - 'clean' (default): the subject's own readable name — `price`, `sort`,
	 *   `product_cat`. Reserved names still fall back; see reserve_key().
	 * - 'prefixed': the plugin-namespaced spelling, `aae_price` / `aae_tax_*`.
	 * - 'custom': a per-subject key the user typed, else a shared prefix.
	 *
	 * @param string $type            Filter type ('woo', 'tax', 'search', 'sort', 'author', 'date', 'meta').
	 * @param string $subject         Field, taxonomy or meta key. Equal to $type
	 *                                for the filters that have only one subject.
	 * @param string $legacy_fallback The prefixed spelling ('aae_price', …).
	 * @return string Sanitized URL parameter key.
	 */
	public static function default_url_key( string $type, string $subject, string $legacy_fallback ): string {
		$settings = self::get_settings();
		$mode     = $settings['param_mode'] ?? 'clean';

		if ( 'prefixed' === $mode ) {
			return $legacy_fallback;
		}

		if ( 'custom' === $mode ) {
			// Keyed by SUBJECT, never by type. A `custom_tax` would hand every
			// taxonomy on the site the same URL key and silently merge Colour
			// and Size into one filter; the same for `custom_meta`. Only the
			// single-subject filters (search, sort, author, date) can be named
			// this way, and for those the subject IS the type.
			$custom_key = ! empty( $settings[ 'custom_' . $subject ] ) && is_string( $settings[ 'custom_' . $subject ] )
				? sanitize_key( $settings[ 'custom_' . $subject ] )
				: '';

			if ( '' !== $custom_key ) {
				return $custom_key;
			}

			// A blank per-subject key is the normal case, and it is what lets
			// the prefix apply: a pre-filled one would always win and the
			// prefix would appear to do nothing.
			$prefix = isset( $settings['custom_prefix'] ) && is_string( $settings['custom_prefix'] )
				? trim( sanitize_key( $settings['custom_prefix'] ), '_' )
				: '';

			if ( '' !== $prefix ) {
				return $prefix . '_' . sanitize_key( $subject );
			}
		}

		// 'clean', and the fallback for a custom mode with nothing filled in:
		// price, rating, sort, search, author, date, the taxonomy slug, the
		// meta key. All seven types resolve the same way — the subject IS the
		// readable name — so there is nothing to branch on.
		return sanitize_key( $subject );
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
	 * $slashed says which of the two shapes this is, and it matters: WordPress
	 * slash-escapes the superglobals, so $_GET needs one wp_unslash() — but the
	 * AJAX body was already unslashed whole before being JSON-decoded, and
	 * stripping it a second time eats a real backslash. Measured: a search for
	 * `a` arrived as `ab` on a page change, so page 2 queried a different set
	 * than page 1.
	 *
	 * @param array $declarations From declarations().
	 * @param array $request      url_key => value.
	 * @param bool  $slashed      True for a raw superglobal, false for a value
	 *                            the caller already unslashed.
	 * @return array<string, string> url_key => raw string
	 */
	public static function raw_from_request( array $declarations, array $request, bool $slashed = true ): array {
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
				$v = implode( self::join_separator( $d ), $scalars );
			}
			if ( ! is_scalar( $v ) ) {
				continue;
			}
			$v = $slashed ? (string) wp_unslash( $v ) : (string) $v;
			if ( '' === $v ) {
				continue;
			}
			$raw[ $k ] = $v;
		}
		return $raw;
	}

	/**
	 * How several values sent under one key are joined into this declaration's
	 * own wire format.
	 *
	 * This exists so a plain HTML FORM can express a range. A range is one key
	 * holding `min..max`, but a form has two boxes, and two boxes named
	 * `price[]` arrive here as an array — which joined with a comma becomes
	 * `10,500`, a value `parse_range()` refuses. So the widget would submit,
	 * the page would reload, and the filter would be dropped in total silence:
	 * exactly the failure mode a link-shaped control was chosen to avoid.
	 *
	 * Joining a RANGE declaration's array with `..` instead makes the form's
	 * natural output the value the authoriser already understands, with no
	 * second URL spelling to reserve, explain, or keep in step. It is also why
	 * the range slider needs nothing of its own here: it writes the same two
	 * boxes.
	 *
	 * Anything else still joins with a comma — a taxonomy or choice filter's
	 * `?cat[]=a&cat[]=b` is a LIST, and it always has been.
	 */
	private static function join_separator( array $d ): string {
		switch ( (string) ( $d['type'] ?? '' ) ) {
			case 'date':
				return '..';
			case 'meta':
				return 'range' === (string) ( $d['mode'] ?? '' ) ? '..' : ',';
			case 'woo':
				return 'price' === (string) ( $d['field'] ?? '' ) ? '..' : ',';
		}
		return ',';
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
					$woo = Loop_Query_Woo::authorize_field( $d['field'], $value, $d );
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

	/**
	 * The saved-page filters for one grid: request in, authorised filters out.
	 * The ONE pipeline — the first server render (URL) and the AJAX page change
	 * (decoded body) both come through here, so a rule added to it holds on both
	 * and page 2 can never be authorised on different terms than page 1.
	 *
	 * @param bool $slashed See raw_from_request(). $_GET is slashed; a decoded
	 *                      AJAX payload is not.
	 */
	public static function current( int $post_id, string $grid_id, string $post_type, array $request, bool $slashed = true ): array {
		$decls = self::declarations_for_document( $post_id, $grid_id );
		if ( ! $decls ) {
			return [];
		}
		return self::authorize( $decls, self::raw_from_request( $decls, $request, $slashed ), $post_type );
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
				$active = [];
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
						// NOT `continue` — see NO_MATCH_ID. Dropping the only
						// value sent drops the whole filter, and an unfiltered
						// grid looks nothing like the empty one a valid-but-unused
						// id produces, so the two answers together tell an
						// anonymous visitor whether user id N exists.
						$active[] = $v;
						$picked[] = $exists ? $v : self::NO_MATCH_ID;
						continue;
					}
					if ( ! in_array( $v, $d['choices'], true ) ) {
						continue;
					}
					$active[] = $v;
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
				// `$active`, not `$picked`: for an open-choice filter the two
				// differ exactly when an id did not resolve, and the chip should
				// name what the visitor asked for rather than the sentinel that
				// replaced it.
				return [ 'clause' => $clause, 'active' => implode( ',', $active ) ];

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
