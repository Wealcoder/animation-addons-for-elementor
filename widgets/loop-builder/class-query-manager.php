<?php
namespace WCF_ADDONS\Widgets\Loop_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Query Manager Class.
 *
 * Handles post queries and filtering for loop widgets.
 */
class Query_Manager {

	/**
	 * Instance var
	 *
	 * @var object $_instance Class instance.
	 */
	private static $_instance = null;

	/**
	 * Instance function.
	 *
	 * @since 2.4.16
	 * @return object Class instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function __construct() {
		// Initialize query hooks.
		add_action( 'pre_get_posts', array( $this, 'modify_main_query' ) );
	}

	/**
	 * Build query arguments from widget settings.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @since 2.4.16
	 * @return array Query arguments.
	 */
	public function build_query_args( $settings ) {
		// Use paged from settings if provided, otherwise get the current page.
		$paged = isset( $settings['paged'] ) ? absint( $settings['paged'] ) : $this->get_current_page();

		$query_args = array(
			'post_type'           => $settings['post_type'] ?? 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $settings['posts_per_page'] ?? 6,
			'paged'               => $paged,
			'ignore_sticky_posts' => ! empty( $settings['post_sticky_ignore'] ) && 'yes' === $settings['post_sticky_ignore'],
		);

		// Handle Query Type: Related Posts.
		if ( 'related' === ( $settings['query_type'] ?? '' ) && is_singular() ) {
			return $this->build_related_query( $settings );
		}

		// Handle Query Type: Archive.
		if ( 'archive' === ( $settings['query_type'] ?? '' ) && ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			global $wp_query;
			if ( $wp_query->is_archive || $wp_query->is_search ) {
				return array(); // Use main query.
			}
		}

		// Handle ordering.
		if ( ! empty( $settings['post_order_by'] ) ) {
			$query_args['orderby'] = $settings['post_order_by'];
			$query_args['order']   = $settings['post_order'] ?? 'DESC';
		}

		// Handle date filters.
		if ( ! empty( $settings['post_date'] ) && 'anytime' !== $settings['post_date'] ) {
			$query_args['date_query'] = array(
				'after' => $settings['post_date'],
			);
		}

		// Initialize tax_query array.
		$tax_query = array();

		// Handle include filters.
		if ( ! empty( $settings['include'] ) && is_array( $settings['include'] ) ) {

			// Include terms.
			if ( in_array( 'terms', $settings['include'], true ) ) {

				// Handle include_term_ids (comma-separated IDs).
				if ( ! empty( $settings['include_term_ids'] ) ) {
					$term_ids      = array_map( 'intval', explode( ',', $settings['include_term_ids'] ) );
					$grouped_terms = $this->group_terms_by_taxonomy( $term_ids );

					foreach ( $grouped_terms as $taxonomy => $term_ids_array ) {
						$tax_query[] = array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_taxonomy_id',
							'terms'    => $term_ids_array,
							'operator' => 'IN',
						);
					}
				}

				// Handle post_categories (term names for category taxonomy).
				if ( ! empty( $settings['post_categories'] ) && is_array( $settings['post_categories'] ) ) {
					$tax_query[] = array(
						'taxonomy' => 'category',
						'field'    => 'name',
						'terms'    => $settings['post_categories'],
						'operator' => 'IN',
					);
				}

				// Handle post_tags (term names for post_tag taxonomy).
				if ( ! empty( $settings['post_tags'] ) && is_array( $settings['post_tags'] ) ) {
					$tax_query[] = array(
						'taxonomy' => 'post_tag',
						'field'    => 'name',
						'terms'    => $settings['post_tags'],
						'operator' => 'IN',
					);
				}
			}

			// Include authors.
			if ( in_array( 'authors', $settings['include'], true ) && ! empty( $settings['include_authors'] ) ) {
				$query_args['author__in'] = array_map( 'intval', explode( ',', $settings['include_authors'] ) );
			}
		}

		// Handle exclude filters.
		if ( ! empty( $settings['exclude'] ) && is_array( $settings['exclude'] ) ) {

			if ( empty( $tax_query ) ) {
				$tax_query['relation'] = 'AND';
			}

			// Exclude terms.
			if ( in_array( 'terms', $settings['exclude'], true ) ) {

				if ( ! empty( $settings['exclude_term_ids'] ) ) {
					$term_ids      = array_map( 'intval', explode( ',', $settings['exclude_term_ids'] ) );
					$grouped_terms = $this->group_terms_by_taxonomy( $term_ids );

					foreach ( $grouped_terms as $taxonomy => $term_ids_array ) {
						$tax_query[] = array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_taxonomy_id',
							'terms'    => $term_ids_array,
							'operator' => 'NOT IN',
						);
					}
				}
			}

			// Exclude authors.
			if ( in_array( 'authors', $settings['exclude'], true ) && ! empty( $settings['exclude_authors'] ) ) {
				$query_args['author__not_in'] = array_map( 'intval', explode( ',', $settings['exclude_authors'] ) );
			}
		}

		// Handle post format filter.
		if ( ! empty( $settings['post_format'] ) && is_array( $settings['post_format'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'post_format',
				'field'    => 'slug',
				'terms'    => $settings['post_format'],
				'operator' => 'IN',
			);
		}

		// Handle special query types.
		$this->handle_special_query_types( $query_args, $settings );

		// Add tax_query to query_args if not empty.
		if ( ! empty( $tax_query ) ) {
			// Add relation if multiple taxonomies.
			if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
				$tax_query['relation'] = 'AND';
			}
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		// Handle AJAX filter parameters.
		$this->handle_ajax_filters( $query_args );

		return apply_filters( 'custom_loop_builder_query_args', $query_args, $settings );
	}

	/**
	 * Group term IDs by their taxonomy.
	 *
	 * @param array $term_ids Array of term IDs.
	 *
	 * @since 2.4.16
	 * @return array Grouped terms by taxonomy.
	 */
	private function group_terms_by_taxonomy( $term_ids ) {
		$grouped_terms = array();

		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id );
			if ( $term && ! is_wp_error( $term ) ) {
				$grouped_terms[ $term->taxonomy ][] = $term_id;
			}
		}

		return $grouped_terms;
	}

	/**
	 * Build related posts query.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @since 2.4.16
	 * @return array Query arguments.
	 */
	private function build_related_query( $settings ) {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return array( 'post__in' => array( 0 ) );
		}

		$taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
		$tax_query  = array();

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			$term_list = wp_list_pluck( $terms, 'slug' );

			if ( ! empty( $tax_query ) && empty( $tax_query['relation'] ) ) {
				$tax_query['relation'] = 'OR';
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $term_list,
			);
		}

		$query_args = array(
			'post_type'      => get_post_type( $post_id ),
			'posts_per_page' => $settings['posts_per_page'] ?? 6,
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'rand',
		);

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return $query_args;
	}

	/**
	 * Handle special query types.
	 *
	 * @param array $query_args Query arguments (passed by reference).
	 * @param array $settings Widget settings.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	private function handle_special_query_types( &$query_args, $settings ) {
		$query_type = $settings['query_type'] ?? 'custom';

		switch ( $query_type ) {
			case 'most_views':
				$query_args['meta_key']   = 'wcf_post_views_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']    = 'meta_value_num';
				$query_args['order']      = 'DESC';
				$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'wcf_post_views_count',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				);
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'top_post_week':
				$query_args['meta_key']   = 'wcf_post_views_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']    = 'meta_value_num';
				$query_args['order']      = 'DESC';
				$query_args['date_query'] = array(
					array(
						'after'     => '1 week ago',
						'inclusive' => true,
					),
				);
				$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'wcf_post_views_count',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				);
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'most_popular':
				$query_args['orderby']    = array(
					'meta_value_num' => 'DESC',
					'comment_count'  => 'DESC',
				);
				$query_args['order']      = 'DESC';
				$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'  => 'wcf_post_views_count',
						'type' => 'NUMERIC',
					),
					array(
						'key'  => 'aae_post_shares_count',
						'type' => 'NUMERIC',
					),
				);
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'trending_score':
				$query_args['meta_key'] = 'aae_trending_score'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'most_share_count':
				$query_args['meta_key'] = 'aae_post_shares_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'most_comments':
				$query_args['orderby'] = 'comment_count';
				$query_args['order']   = 'DESC';
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'most_reactions':
				$query_args['meta_key'] = 'aaeaddon_post_total_reactions'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'most_reactions_love':
				$query_args['meta_key'] = 'aaeaddon_post_reactions_love'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'most_reactions_like':
				$query_args['meta_key'] = 'aaeaddon_post_reactions_like'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'most_reviews':
				$query_args['meta_key']   = 'review_count'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']    = 'meta_value_num';
				$query_args['order']      = 'DESC';
				$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'review_count',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				);
				break;

			case 'last_12_hours':
				$query_args['order']      = 'DESC';
				$query_args['date_query'] = array(
					array(
						'after'     => '-12 hours',
						'inclusive' => true,
					),
				);
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'last_24_hours':
				$query_args['order']      = 'DESC';
				$query_args['date_query'] = array(
					array(
						'after'     => '-24 hours',
						'inclusive' => true,
					),
				);
				unset( $query_args['ignore_sticky_posts'] );
				break;

			case 'read_later':
				if ( isset( $_COOKIE['readLater'] ) ) {
					$ids = json_decode( sanitize_text_field( wp_unslash( $_COOKIE['readLater'] ) ), true );
					if ( is_array( $ids ) && ! empty( $ids ) ) {
						$query_args['post__in'] = array_map( 'absint', $ids );
						$query_args['orderby']  = 'post__in';
					} else {
						$query_args['post__in'] = array( 0 );
					}
				} else {
					$query_args['post__in'] = array( 0 );
				}
				break;

			case 'recent_visited':
				$visited_posts = isset( $_COOKIE['aae_visited_posts'] ) ? json_decode( sanitize_text_field( wp_unslash( $_COOKIE['aae_visited_posts'] ) ), true ) : array();

				if ( is_array( $visited_posts ) ) {
					$post_type = $settings['post_type'] ?? 'post';

					if ( isset( $visited_posts[ $post_type ] ) && is_array( $visited_posts[ $post_type ] ) ) {
						$post_ids = array_map( 'absint', $visited_posts[ $post_type ] );

						if ( ! empty( $post_ids ) ) {
							$query_args['post__in'] = $post_ids;
							$query_args['orderby']  = 'post__in';
						}
					}
				}
				break;
		}
	}

	/**
	 * Handle AJAX filter parameters.
	 *
	 * @param array $query_args Query arguments (passed by reference).
	 *
	 * @since 2.4.16
	 * @return void
	 */
	private function handle_ajax_filters( &$query_args ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['aae-ajax-filter'] ) ) {
			return;
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['tax'] ) && isset( $_GET['term'] ) && 'all' !== $_GET['term'] ) {
			if ( ! isset( $query_args['tax_query'] ) ) {
				$query_args['tax_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}

			$query_args['tax_query'][] = array(
				'taxonomy' => sanitize_text_field( wp_unslash( $_GET['tax'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'field'    => 'term_id',
				'terms'    => absint( wp_unslash( $_GET['term'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['cpaged'] ) ) {
			$query_args['paged'] = absint( sanitize_text_field( wp_unslash( $_GET['cpaged'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	/**
	 * Get the current page for pagination.
	 *
	 * @since 2.4.16
	 * @return int Current page number.
	 */
	private function get_current_page() {
		$paged = 1;

		if ( is_front_page() ) {
			$paged = get_query_var( 'page', 1 );
		} else {
			$paged = get_query_var( 'paged', 1 );
		}

		return max( 1, $paged );
	}

	/**
	 * Execute query.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @since 2.4.16
	 * @return \WP_Query Query object.
	 */
	public function get_query( $settings ) {
		$query_args = $this->build_query_args( $settings );

		if ( empty( $query_args ) ) {
			global $wp_query;
			return $wp_query;
		}

		return new \WP_Query( $query_args );
	}

	/**
	 * Modify main query for loop pages.
	 *
	 * @param \WP_Query $query Main query object.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function modify_main_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// TODO::Add any main query modifications here if needed.
	}
}

Query_Manager::instance();
