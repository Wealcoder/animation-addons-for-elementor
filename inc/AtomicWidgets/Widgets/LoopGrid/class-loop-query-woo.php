<?php
/**
 * Loop Grid — WooCommerce adapter.
 *
 * WooCommerce only shapes the MAIN query: `WC_Query::pre_get_posts()` returns
 * on `! $q->is_main_query()`, so a Loop Grid querying `product` sees what WC's
 * own shop loop never shows — catalog-hidden products, and out-of-stock ones
 * on a store that hides them. This class gives the grid's secondary WP_Query
 * the same rules WC applies to its own Product Collection block
 * (src/Blocks/BlockTypes/ProductCollection/QueryBuilder.php), read from
 * WooCommerce 10.0.4:
 *
 *   - product_visibility NOT IN exclude-from-catalog (exclude-from-search when
 *     the query is a search), plus `outofstock` when "Hide out of stock items"
 *     is on — always, filter or no filter;
 *   - price by the `wc_product_meta_lookup` table (min_price / max_price), not
 *     `_price` meta: a variable product carries one `_price` row per distinct
 *     variation price, and a store that displays prices including tax needs
 *     the filter amount converted before it meets the stored (excl.) price;
 *   - rating through the `rated-N` product_visibility terms, not meta;
 *   - sort by price / popularity / rating on the lookup table's columns,
 *     exactly the ORDER BY clauses WC_Query::order_by_*_post_clauses() emit;
 *   - on sale via wc_get_product_ids_on_sale(), featured via its term, stock
 *     via `_stock_status`.
 *
 * Everything here is inert without WooCommerce: active() is false, apply()
 * returns its input, and the authoriser never declares a `woo` field.
 *
 * The lookup-table work happens in `posts_clauses`, scoped to OUR query by
 * private query vars (`aae_woo_price`, `aae_woo_sort`) — the same trick WC's
 * block uses with `isProductCollection`, so the filter can be registered once
 * and touch no other query on the page.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Loop_Query_Woo {

	/** `woo_field` values a Meta Filter widget may declare. */
	public const FIELDS = [ 'price', 'rating', 'stock', 'onsale', 'featured' ];

	/** Sort orderby values that only the lookup table can answer. */
	public const SORT_KEYS = [ 'price', 'popularity', 'rating' ];

	/**
	 * The private query vars that carry a price range and a lookup-table sort
	 * into posts_clauses. PUBLIC because the gate that decides whether to load
	 * this class at all lives in Atomic::init_hooks(). That gate spells both
	 * names as literals — reading a constant would mean loading this class on
	 * every WP_Query on the site — so the two are a MIRROR: rename one and the
	 * gate goes silently dead, the JOIN is never added and the visitor's price
	 * range is simply ignored. verify-loop-filter-seam.php asserts they match.
	 */
	public const QV_PRICE = 'aae_woo_price';
	public const QV_SORT  = 'aae_woo_sort';

	public static function active(): bool {
		return class_exists( 'WooCommerce' )
			&& function_exists( 'wc_get_product_visibility_term_ids' )
			&& taxonomy_exists( 'product_visibility' );
	}

	/** Is this args array a product query (string or array post_type)? */
	public static function is_product_query( array $args ): bool {
		$type = $args['post_type'] ?? '';
		return 'product' === $type || ( is_array( $type ) && in_array( 'product', $type, true ) );
	}

	/**
	 * WooCommerce attribute taxonomies (`pa_color`, …) as registered objects.
	 * They are `public => false` unless "Enable archives" is ticked, so the
	 * grid's public-taxonomy discovery never sees them; this adds them back.
	 *
	 * @return array<string, \WP_Taxonomy>
	 */
	public static function attribute_taxonomies(): array {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) || ! function_exists( 'wc_attribute_taxonomy_name' ) ) {
			return [];
		}
		$out = [];
		foreach ( (array) wc_get_attribute_taxonomies() as $attr ) {
			if ( empty( $attr->attribute_name ) ) {
				continue;
			}
			$name = wc_attribute_taxonomy_name( $attr->attribute_name );
			$tax  = get_taxonomy( $name );
			if ( $tax instanceof \WP_Taxonomy ) {
				$out[ $name ] = $tax;
			}
		}
		return $out;
	}

	/** Memoised store-wide price bounds. @see price_bounds() */
	private static $price_bounds = null;

	/**
	 * The cheapest and dearest effective price in the catalogue.
	 *
	 * Only a range SLIDER needs this: a slider has to know where its track
	 * starts and ends before a visitor has chosen anything, and a builder who
	 * has not typed bounds should still get a usable one rather than two bare
	 * number boxes that read as the slider being broken.
	 *
	 * It is NOT the gate, and must never become it. `Loop_Filter_Auth` clamps an
	 * incoming range to the WIDGET's own Min/Max exactly as before, so a
	 * hand-typed `?price=0..999999` is bounded by the builder's decision and not
	 * by whatever happens to be in stock today. This answers a drawing question
	 * only.
	 *
	 * Deliberately store-wide rather than scoped to the grid's current filters:
	 * a track whose ends move every time a category is picked makes the handle
	 * positions mean something different on each render, and the visitor's own
	 * selection appears to jump. One aggregate over the price index, memoised
	 * per request, and only ever asked for by a page that draws a slider.
	 *
	 * @return array{0: float, 1: float}|null Null when unanswerable.
	 */
	public static function price_bounds(): ?array {
		if ( null !== self::$price_bounds ) {
			return self::$price_bounds ?: null;
		}
		self::$price_bounds = false;

		if ( ! self::active() ) {
			return null;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wc_product_meta_lookup';

		// The same index every price filter and price sort already reads, so a
		// store whose prices work at all can answer this.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( "SELECT MIN(min_price) AS lo, MAX(max_price) AS hi FROM {$table} WHERE min_price IS NOT NULL" );

		if ( ! $row || null === $row->lo || null === $row->hi ) {
			return null;
		}

		$lo = (float) $row->lo;
		$hi = (float) $row->hi;
		if ( $hi <= $lo ) {
			return null;
		}

		self::$price_bounds = [ $lo, $hi ];
		return self::$price_bounds;
	}

	/**
	 * Authorise one visitor value for a WC field (Loop_Filter_Auth rule 3: the
	 * widget fixes the field, the visitor supplies a value, and that value is
	 * shaped here).
	 *
	 * @return array{value: mixed, active: string}|null
	 */
	public static function authorize_field( string $field, string $value, array $decl = [] ): ?array {
		switch ( $field ) {
			case 'price':
				$range = Loop_Filter_Auth::parse_range( $value );
				if ( ! $range ) {
					return null;
				}
				[ $min, $max ] = $range;
				$min = null === $min ? null : max( 0.0, $min );
				$max = null === $max ? null : max( 0.0, $max );

				// The builder's own bounds, when they typed any. A price filter
				// is the one range that never passed through meta_clause(),
				// where every other range is clamped, so it accepted anything
				// until this — including a hand-typed span entirely outside the
				// track its own slider draws.
				//
				// An OPEN end is clamped too, and that is the half worth being
				// deliberate about: `?price=200..` on a shop capped at 200 means
				// "everything above the ceiling", which is not a narrower
				// question than the builder allowed — it is the one they
				// excluded. Closing it against the ceiling turns it into a
				// filter that matches the top of the allowed range instead.
				$lo = isset( $decl['min'] ) && is_numeric( $decl['min'] ) ? (float) $decl['min'] : null;
				$hi = isset( $decl['max'] ) && is_numeric( $decl['max'] ) ? (float) $decl['max'] : null;
				if ( null !== $lo ) {
					$min = null === $min ? null : max( $min, $lo );
					$max = null === $max ? null : max( $max, $lo );
				}
				if ( null !== $hi ) {
					$min = null === $min ? null : min( $min, $hi );
					$max = null === $max ? $hi : min( $max, $hi );
				}

				if ( null !== $min && null !== $max && $min > $max ) {
					return null;
				}
				return [
					'value'  => [ 'min' => $min, 'max' => $max ],
					'active' => ( null === $min ? '' : rtrim( rtrim( number_format( $min, 4, '.', '' ), '0' ), '.' ) )
						. '..' . ( null === $max ? '' : rtrim( rtrim( number_format( $max, 4, '.', '' ), '0' ), '.' ) ),
				];

			case 'rating':
				$stars = [];
				foreach ( explode( ',', $value ) as $v ) {
					$v = trim( $v );
					if ( ctype_digit( $v ) && (int) $v >= 1 && (int) $v <= 5 ) {
						$stars[] = (int) $v;
					}
				}
				$stars = array_values( array_unique( $stars ) );
				return $stars ? [ 'value' => $stars, 'active' => implode( ',', $stars ) ] : null;

			case 'stock':
			case 'onsale':
			case 'featured':
				return in_array( $value, [ '1', 'true', 'on' ], true ) ? [ 'value' => true, 'active' => '1' ] : null;
		}
		return null;
	}

	/**
	 * Apply WC rules to a built args array. Visibility is applied to EVERY
	 * product query; the rest only when the authoriser put something in $woo.
	 *
	 * @param array $args WP_Query args.
	 * @param array $woo  `$filters['woo']` from the authoriser (may be empty).
	 */
	public static function apply( array $args, array $woo = [] ): array {
		if ( ! self::active() || ! self::is_product_query( $args ) ) {
			return $args;
		}

		$terms     = wc_get_product_visibility_term_ids();
		$tax_query = [];

		// --- visibility, on every product query ------------------------------
		// WC_Query only shapes the MAIN query, so without this a Loop Grid shows
		// catalog-hidden and (where the store hides them) out-of-stock products
		// that no other listing on the site would show.
		//
		// It is applied unconditionally, which does change what an existing grid
		// renders — a strip deliberately pinning hidden products, say. The
		// filter is the way back for that site; the default matches every other
		// product listing WooCommerce draws.
		//
		// @param bool  $apply Whether to exclude hidden / out-of-stock products.
		// @param array $args  The query args being built.
		$not_in = [];
		if ( (bool) apply_filters( 'aae/loop_grid/woo_visibility', true, $args ) ) {
			$not_in = [ ! empty( $args['s'] ) ? ( $terms['exclude-from-search'] ?? 0 ) : ( $terms['exclude-from-catalog'] ?? 0 ) ];
			if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $terms['outofstock'] ) ) {
				$not_in[] = $terms['outofstock'];
			}
		}
		$not_in = array_values( array_filter( array_map( 'intval', $not_in ) ) );
		if ( $not_in ) {
			$tax_query[] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $not_in,
				'operator' => 'NOT IN',
			];
		}

		// --- featured -------------------------------------------------------
		if ( ! empty( $woo['featured'] ) && ! empty( $terms['featured'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => [ (int) $terms['featured'] ],
				'operator' => 'IN',
			];
		}

		// --- rating ---------------------------------------------------------
		if ( ! empty( $woo['rating'] ) && is_array( $woo['rating'] ) ) {
			$rated = [];
			foreach ( $woo['rating'] as $n ) {
				if ( ! empty( $terms[ 'rated-' . (int) $n ] ) ) {
					$rated[] = (int) $terms[ 'rated-' . (int) $n ];
				}
			}
			if ( $rated ) {
				$tax_query[] = [
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $rated,
					'operator' => 'IN',
				];
			}
		}

		if ( $tax_query ) {
			$args['tax_query'] = self::and_group( $args['tax_query'] ?? [], $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		// --- stock ----------------------------------------------------------
		if ( ! empty( $woo['stock'] ) ) {
			$clause = [ 'key' => '_stock_status', 'value' => 'instock', 'compare' => '=' ];
			$args['meta_query'] = self::and_group( $args['meta_query'] ?? [], [ $clause ] ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		// --- on sale --------------------------------------------------------
		if ( ! empty( $woo['onsale'] ) && function_exists( 'wc_get_product_ids_on_sale' ) ) {
			$sale = array_map( 'intval', (array) wc_get_product_ids_on_sale() );
			if ( ! empty( $args['post__in'] ) ) {
				$sale = array_values( array_intersect( array_map( 'intval', (array) $args['post__in'] ), $sale ) );
			}
			// An empty post__in means "no constraint" to WP_Query — force no rows.
			$args['post__in'] = $sale ? $sale : [ 0 ];
		}

		// --- price + sort: lookup table, in posts_clauses -------------------
		if ( ! empty( $woo['price'] ) && is_array( $woo['price'] ) ) {
			$args[ self::QV_PRICE ] = [
				'min' => isset( $woo['price']['min'] ) ? $woo['price']['min'] : null,
				'max' => isset( $woo['price']['max'] ) ? $woo['price']['max'] : null,
			];
		}
		if ( ! empty( $woo['sort']['key'] ) && in_array( $woo['sort']['key'], self::SORT_KEYS, true ) ) {
			$args[ self::QV_SORT ] = [
				'key'   => $woo['sort']['key'],
				'order' => 'ASC' === strtoupper( (string) ( $woo['sort']['order'] ?? 'DESC' ) ) ? 'ASC' : 'DESC',
			];
			// Neutral base so WP does not add its own ORDER BY before ours replaces it.
			$args['orderby'] = 'ID';
			unset( $args['order'] );
		}

		return $args;
	}

	/**
	 * Merge new clauses into an existing tax/meta query under AND. An existing
	 * OR group (the Related source) is nested, never flattened into the AND.
	 */
	public static function and_group( array $existing, array $new ): array {
		if ( ! $existing ) {
			$new['relation'] = 'AND';
			return $new;
		}
		$relation = strtoupper( (string) ( $existing['relation'] ?? 'AND' ) );
		if ( 'OR' === $relation ) {
			return array_merge( [ 'relation' => 'AND', $existing ], $new );
		}
		unset( $existing['relation'] );
		$merged             = array_merge( array_values( $existing ), $new );
		$merged['relation'] = 'AND';
		return $merged;
	}

	/**
	 * The lookup-table join, the price WHERE and the sort ORDER BY — only for a
	 * query carrying our private vars.
	 *
	 * @param array     $clauses
	 * @param \WP_Query $query
	 */
	public static function posts_clauses( $clauses, $query ) {
		if ( ! $query instanceof \WP_Query || ! self::active() ) {
			return $clauses;
		}
		$price = $query->get( self::QV_PRICE );
		$sort  = $query->get( self::QV_SORT );
		if ( ! $price && ! $sort ) {
			return $clauses;
		}

		global $wpdb;
		$lookup = $wpdb->prefix . 'wc_product_meta_lookup';

		if ( false === strpos( (string) $clauses['join'], 'wc_product_meta_lookup' ) ) {
			$clauses['join'] .= " LEFT JOIN {$lookup} wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id ";
		}

		if ( is_array( $price ) ) {
			$adjust = self::should_adjust_for_taxes();
			if ( isset( $price['min'] ) && null !== $price['min'] ) {
				$clauses['where'] .= $adjust
					? self::tax_adjusted_where( (float) $price['min'], 'max_price', '>=' )
					: $wpdb->prepare( ' AND wc_product_meta_lookup.max_price >= %f ', (float) $price['min'] );
			}
			if ( isset( $price['max'] ) && null !== $price['max'] ) {
				$clauses['where'] .= $adjust
					? self::tax_adjusted_where( (float) $price['max'], 'min_price', '<=' )
					: $wpdb->prepare( ' AND wc_product_meta_lookup.min_price <= %f ', (float) $price['max'] );
			}
		}

		if ( is_array( $sort ) && ! empty( $sort['key'] ) ) {
			$dir = 'ASC' === ( $sort['order'] ?? 'DESC' ) ? 'ASC' : 'DESC';
			switch ( $sort['key'] ) {
				case 'price':
					$clauses['orderby'] = 'ASC' === $dir
						? ' wc_product_meta_lookup.min_price ASC, wc_product_meta_lookup.product_id ASC '
						: ' wc_product_meta_lookup.max_price DESC, wc_product_meta_lookup.product_id DESC ';
					break;
				case 'popularity':
					$clauses['orderby'] = " wc_product_meta_lookup.total_sales {$dir}, wc_product_meta_lookup.product_id DESC ";
					break;
				case 'rating':
					$clauses['orderby'] = " wc_product_meta_lookup.average_rating {$dir}, wc_product_meta_lookup.rating_count DESC, wc_product_meta_lookup.product_id DESC ";
					break;
			}
		}

		return $clauses;
	}

	/** Prices are stored one way and displayed another -> the filter amount must be converted. */
	private static function should_adjust_for_taxes(): bool {
		if ( ! function_exists( 'wc_tax_enabled' ) || ! wc_tax_enabled() || ! class_exists( 'WC_Tax' ) ) {
			return false;
		}
		$display = (string) get_option( 'woocommerce_tax_display_shop' );
		$storage = function_exists( 'wc_prices_include_tax' ) && wc_prices_include_tax() ? 'incl' : 'excl';
		return $display !== $storage;
	}

	/** Cached tax classes for tax-adjusted price filtering. */
	private static ?array $tax_classes = null;

	/**
	 * Retrieve distinct tax classes from product lookup table, memoized per request.
	 *
	 * @return array
	 */
	private static function get_distinct_tax_classes(): array {
		if ( null === self::$tax_classes ) {
			global $wpdb;
			$lookup            = $wpdb->prefix . 'wc_product_meta_lookup';
			self::$tax_classes = (array) $wpdb->get_col( "SELECT DISTINCT tax_class FROM {$lookup};" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		return self::$tax_classes;
	}

	/**
	 * WC's own per-tax-class expansion (QueryBuilder::get_price_filter_query_for_displayed_taxes).
	 */
	private static function tax_adjusted_where( float $amount, string $column, string $operator ): string {
		global $wpdb;
		$classes = self::get_distinct_tax_classes();
		if ( empty( $classes ) ) {
			return '';
		}
		$column   = in_array( $column, [ 'min_price', 'max_price' ], true ) ? $column : 'min_price';
		$operator = in_array( $operator, [ '>=', '<=' ], true ) ? $operator : '>=';

		$ors = [];
		foreach ( $classes as $class ) {
			$ors[] = $wpdb->prepare(
				"( wc_product_meta_lookup.tax_class = %s AND wc_product_meta_lookup.`{$column}` {$operator} %f )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(string) $class,
				self::adjust_for_tax_class( $amount, (string) $class )
			);
		}

		$non_taxable = $wpdb->prepare(
			"wc_product_meta_lookup.`{$column}` {$operator} %f", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$amount
		);

		return " AND ( ( wc_product_meta_lookup.tax_status = 'taxable' AND ( 0=1 OR " . implode( ' OR ', $ors ) . " ) ) OR ( wc_product_meta_lookup.tax_status != 'taxable' AND {$non_taxable} ) ) ";
	}

	private static function adjust_for_tax_class( float $amount, string $tax_class ): float {
		$display    = (string) get_option( 'woocommerce_tax_display_shop' );
		$rates      = \WC_Tax::get_rates( $tax_class );
		$base_rates = \WC_Tax::get_base_tax_rates( $tax_class );

		if ( 'incl' === $display ) {
			// Shown incl. tax, stored excl.: strip the tax off the filter amount.
			$taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true )
				? \WC_Tax::calc_tax( $amount, $base_rates, true )
				: \WC_Tax::calc_tax( $amount, $rates, true );
			return $amount - array_sum( $taxes );
		}
		// Shown excl., stored incl.: add the tax to match the stored price.
		return $amount + array_sum( \WC_Tax::calc_tax( $amount, $rates, false ) );
	}
}
