<?php

namespace WCF_ADDONS;

use Elementor\Controls_Manager;
use WP_Query;

trait WCF_Post_Query_Trait {

	public static function get_public_post_types( $args = [] ) {
		$post_type_args = [
			// Default is the value $public.
			'show_in_nav_menus' => true,
		];

		// Keep for backwards compatibility
		if ( ! empty( $args['post_type'] ) ) {
			$post_type_args['name'] = $args['post_type'];
			unset( $args['post_type'] );
		}

		$post_type_args = wp_parse_args( $post_type_args, $args );

		$_post_types = get_post_types( $post_type_args, 'objects' );

		$post_types = [];

		foreach ( $_post_types as $post_type => $object ) {
			$post_types[ $post_type ] = $object->label;
		}

		return $post_types;
	}

	protected function register_query_controls() {
		$this->start_controls_section(
			'section_query',
			[
				'label' => esc_html__( 'Query', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'query_type',
			[
				'label'   => esc_html__( 'Query Type', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'custom',
				'options' => [
					'custom'  => esc_html__( 'Custom', 'animation-addons-for-elementor' ),
					'archive' => esc_html__( 'Archive', 'animation-addons-for-elementor' ),
					'related' => esc_html__( 'related', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'post_type',
			[
				'label'     => esc_html__( 'Source', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'post',
				'options'   => $this->get_public_post_types(),
				'condition' => [ 'query_type' => 'custom' ],
			]
		);

		$this->start_controls_tabs(
			'post_in_ex_tabs'
		);

		$this->start_controls_tab(
			'query_include',
			[
				'label' => esc_html__( 'Include', 'animation-addons-for-elementor' ),
				'condition' => [ 'query_type' => 'custom' ],
			]
		);

		$this->add_control(
			'include',
			[
				'label'       => esc_html__( 'Include By', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'terms'   => esc_html__( 'Term', 'animation-addons-for-elementor' ),
					'authors' => esc_html__( 'Author', 'animation-addons-for-elementor' ),
				],
				'condition' => [ 'query_type' => 'custom' ],
			]
		);

		$this->add_control(
			'include_term_ids',
			[
				'label'       => esc_html__( 'Term', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Add coma separated, terms id', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'All', 'animation-addons-for-elementor' ),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'include'    => 'terms',
					'query_type' => 'custom',
				],
			]
		);

		$this->add_control(
			'include_authors',
			[
				'label'       => esc_html__( 'Author', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Add separated, authors ID', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'All', 'animation-addons-for-elementor' ),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'include'    => 'authors',
					'query_type' => 'custom',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'query_exclude',
			[
				'label' => esc_html__( 'Exclude', 'animation-addons-for-elementor' ),
				'condition' => [ 'query_type' => 'custom' ],
			]
		);

		$this->add_control(
			'exclude',
			[
				'label'       => esc_html__( 'Exclude By', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => [
					'terms'   => esc_html__( 'Term', 'animation-addons-for-elementor' ),
					'authors' => esc_html__( 'Author', 'animation-addons-for-elementor' ),
				],
				'condition' => [ 'query_type' => 'custom' ],
			]
		);

		$this->add_control(
			'exclude_term_ids',
			[
				'label'       => esc_html__( 'Term', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Add coma separated, terms id', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'All', 'animation-addons-for-elementor' ),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'exclude'    => 'terms',
					'query_type' => 'custom',
				],
			]
		);

		$this->add_control(
			'exclude_authors',
			[
				'label'       => esc_html__( 'Author', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Add separated, authors ID', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'All', 'animation-addons-for-elementor' ),
				'label_block' => true,
				'ai'          => [
					'active' => false,
				],
				'condition'   => [
					'exclude'    => 'authors',
					'query_type' => 'custom',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();


		$this->add_control(
			'post_date',
			[
				'label'     => esc_html__( 'Date', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'anytime',
				'options'   => [
					'anytime'  => esc_html__( 'All', 'animation-addons-for-elementor' ),
					'-1 day'   => esc_html__( 'Past Day', 'animation-addons-for-elementor' ),
					'-1 week'  => esc_html__( 'Past Week', 'animation-addons-for-elementor' ),
					'-1 month' => esc_html__( 'Past Month', 'animation-addons-for-elementor' ),
					'-3 month' => esc_html__( 'Past Quarter', 'animation-addons-for-elementor' ),
					'-1 year'  => esc_html__( 'Past Year', 'animation-addons-for-elementor' ),
				],
				'condition' => [ 'query_type' => 'custom' ],
			]
		);

		$this->add_control(
			'post_order_by',
			[
				'label'     => esc_html__( 'Order By', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'date',
				'options'   => [
					'date'          => esc_html__( 'Date', 'animation-addons-for-elementor' ),
					'title'         => esc_html__( 'Title', 'animation-addons-for-elementor' ),
					'menu_order'    => esc_html__( 'Menu Order', 'animation-addons-for-elementor' ),
					'modified'      => esc_html__( 'Last Modified', 'animation-addons-for-elementor' ),
					'comment_count' => esc_html__( 'Comment Count', 'animation-addons-for-elementor' ),
					'rand'          => esc_html__( 'Random', 'animation-addons-for-elementor' ),
				],
				'condition' => [ 'query_type' => 'custom' ],
			]
		);

		$this->add_control(
			'post_order',
			[
				'label'     => esc_html__( 'Order', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'desc',
				'options'   => [
					'asc'  => esc_html__( 'ASC', 'animation-addons-for-elementor' ),
					'desc' => esc_html__( 'DESC', 'animation-addons-for-elementor' ),
				],
				'condition' => [ 'query_type' => 'custom' ],
			]
		);

		$this->add_control(
			'post_sticky_ignore',
			[
				'label'        => esc_html__( 'Ignore Sticky Posts', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'query_type' => 'custom' ],
			]
		);


		$this->end_controls_section();
	}

	public function get_current_page() {
		if ( '' === $this->get_settings_for_display( 'pagination_type' ) ) {
			return 1;
		}

		return max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
	}

	protected function query_arg() {
		$query_args = [];

		//related post
		if ( 'related' === $this->get_settings( 'query_type' ) && is_singular() ) {
			$post_id         = get_queried_object_id();
			$related_post_id = is_singular() && ( 0 !== $post_id ) ? $post_id : null;

			$taxonomies    = get_object_taxonomies( get_post_type( $related_post_id ) );
			$tax_query_arg = [];

			foreach ( $taxonomies as $taxonomy ) {

				$terms = get_the_terms( $post_id, $taxonomy );

				if ( empty( $terms ) ) {
					continue;
				}

				$term_list = wp_list_pluck( $terms, 'slug' );


				if ( ! empty( $tax_query_arg ) && empty( $tax_query_arg['relation'] ) ) {
					$tax_query_arg['relation'] = 'OR';
				}

				$tax_query_arg[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $term_list
				];
			}

			$query_args['post_type']      = get_post_type( $related_post_id );
			$query_args['posts_per_page'] = $this->get_settings( 'posts_per_page' );
			$query_args['post__not_in']   = [ $related_post_id ];
			$query_args['orderby']        = 'rand';

			if ( ! empty( $tax_query_arg ) ) { //backward compatibility if post has no taxonomies
				$query_args['tax_query'] = $tax_query_arg;
			}

			return $query_args;
		}

		$query_args = [
			'post_type'           => $this->get_settings( 'post_type' ),
			'posts_per_page'      => $this->get_settings( 'posts_per_page' ),
			'ignore_sticky_posts' => empty( $this->get_settings( 'post_sticky_ignore' ) ) ? false : true,
			'paged'               => $this->get_current_page(),
			'order'               => $this->get_settings( 'post_order' ),
			'orderby'             => $this->get_settings( 'post_order_by' ),
		];

		if ( 'anytime' !== $this->get_settings( 'post_date' ) ) {
			$query_args['date_query'] = [ 'after' => $this->get_settings( 'post_date' ) ];
		}

		if ( ! empty( $this->get_settings( 'include' ) ) ) {
			if ( in_array( 'terms', $this->get_settings( 'include' ) ) ) {
				$query_args['tax_query'] = [];

				if ( ! empty( $this->get_settings( 'include_term_ids' ) ) ) {
					$terms = [];

					foreach ( explode( ',', $this->get_settings( 'include_term_ids' ) ) as $id ) {
						$term_data = get_term_by( 'term_taxonomy_id', $id );

						if ( ! $term_data ) {
							continue;
						}

						$taxonomy             = $term_data->taxonomy;
						$terms[ $taxonomy ][] = $id;
					}
					foreach ( $terms as $taxonomy => $ids ) {
						$query = [
							'taxonomy' => $taxonomy,
							'field'    => 'term_taxonomy_id',
							'terms'    => $ids,
						];

						$query_args['tax_query'][] = $query;
					}
				}
			}

			if ( ! empty( $this->get_settings( 'include_authors' ) ) ) {
				$query_args['author__in'] = explode( ',', $this->get_settings( 'include_authors' ) );
			}
		}

		if ( ! empty( $this->get_settings( 'exclude' ) ) ) {
			if ( in_array( 'terms', $this->get_settings( 'exclude' ) ) ) {
				$query_args['tax_query']['relation'] = 'AND';

				if ( ! empty( $this->get_settings( 'exclude_term_ids' ) ) ) {
					$terms = [];

					foreach ( explode( ',', $this->get_settings( 'exclude_term_ids' ) ) as $id ) {
						$term_data = get_term_by( 'term_taxonomy_id', $id );
						if ( ! $term_data ) {
							continue;
						}

						$taxonomy             = $term_data->taxonomy;
						$terms[ $taxonomy ][] = $id;
					}
					foreach ( $terms as $taxonomy => $ids ) {
						$query = [
							'taxonomy' => $taxonomy,
							'field'    => 'term_taxonomy_id',
							'terms'    => $ids,
							'operator' => 'NOT IN',
						];

						$query_args['tax_query'][] = $query;
					}
				}
			}

			if ( ! empty( $this->get_settings( 'exclude_authors' ) ) ) {
				$query_args['author__not_in'] = explode( ',', $this->get_settings( 'exclude_authors' ) );
			}
		}

		return $query_args;
	}

	public function get_query() {
		global $wp_query;

		if ( 'archive' === $this->get_settings( 'query_type' ) && ! \Elementor\Plugin::$instance->editor->is_edit_mode() && ( $wp_query->is_archive || $wp_query->is_search ) ) {

			return $this->query = $wp_query;
		} else {
			return $this->query = new WP_Query( $this->query_arg() );
		}
	}

	protected function next_page_link( $next_page ) {
		return get_pagenum_link( $next_page );
	}

	protected function get_taxonomies() {
		$taxonomies = get_taxonomies( [ 'show_in_nav_menus' => true ], 'objects' );

		$options = [ '' => '' ];

		foreach ( $taxonomies as $taxonomy ) {
			$options[ $taxonomy->name ] = $taxonomy->label;
		}

		return $options;
	}

}
