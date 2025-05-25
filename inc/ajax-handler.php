<?php

namespace WCF_ADDONS;

use Elementor\Plugin;
use WP_Query;


defined( 'ABSPATH' ) || die();

class Ajax_Handler {

	public static function init() {
		add_action( 'wp_ajax_live_search', [ __CLASS__, 'handle_live_search' ] );
		add_action( 'wp_ajax_nopriv_live_search', [ __CLASS__, 'handle_live_search' ] );
	}


	public static function handle_live_search() {
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';

		$args = [
			'post_type'      => 'post',
			's'              => $keyword,
			'posts_per_page' => 5,
		];

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$title = get_the_title();
				$thumb = get_the_post_thumbnail_url( get_the_ID(), 'full' );
				$date  = get_the_date();
				?>
					<div class="search-item">
						<div class="thumb">
							<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_html( $title ); ?>">
						</div>
						<div class="content">
							<a class="title" href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( $title ); ?></a>
							<div class="date"><?php echo esc_html( $date ); ?></div>
						</div>
					</div>
				<?php
			}
			wp_reset_postdata();
		} else {
			echo '<div class="search-no-result">No results found.</div>';
		}

		wp_die();
	}


}

Ajax_Handler::init();
