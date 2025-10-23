<?php
/**
 * Admin notice for review.
 *
 * @since 2.4.16
 * @return void
 *
 * @package WCF_ADDONS
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="aae-review-wrapper">
	<img src="<?php echo esc_url( WCF_ADDONS_URL . 'assets/images/notice/review-bg-icon.svg' ); ?>" class="aae-review-end-shape" alt="Animation Addons" />
	<div class="aae-review-container">
		<div class="aae-review-item">
			<div class="aae-review-logo">
				<img src="<?php echo esc_url( WCF_ADDONS_URL . 'assets/images/notice/review-image.svg' ); ?>" alt="Animation Addons" />
			</div>
			<div class="aae-review-content">
				<h3 class="aae-review-title">👌
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Review Deal for You */
							__( 'Enjoying Animation Addons for <span>Elementor</span>?', 'animation-addons-for-elementor' )
						)
					);
					?>
				</h3>
				<p class="aae-review-text">
					<?php
					echo wp_kses_post(
						sprintf(
							// translators: %1$s: Animation Addons Pro link, %2$s: Review link.
							__( 'We hope you had a wonderful experience using %1$s. Please take a moment to show us your support by leaving a 5-star review on <a href="%2$s" target="_blank"><strong>WordPress.org</strong></a>.', 'animation-addons-for-elementor' ),
							'<a href="https://wordpress.org/plugins/animation-addons-for-elementor/" target="_blank"><strong>Animation Addons for Elementor</strong></a>',
							'https://wordpress.org/support/plugin/animation-addons-for-elementor/reviews/#new-post'
						)
					);
					?>
				</p>
				<div class="aae-review-btns">
					<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/animation-addons-for-elementor/reviews/#new-post' ); ?>" class="aae-review-btn" target="_blank">
						<span class="dashicons dashicons-heart"></span>
						<?php esc_html_e( 'Sure, I\'d love to help!', 'animation-addons-for-elementor' ); ?>
					</a>
					<a href="" class="aae-review-btn outline" data-snooze="<?php echo esc_attr( WEEK_IN_SECONDS ); ?>">
						<span class="dashicons dashicons-clock"></span>
						<?php esc_html_e( 'Maybe later', 'animation-addons-for-elementor' ); ?>
					</a>
					<a href="#" class="aae-review-btn outline" data-dismiss>
						<span class="dashicons dashicons-smiley"></span>
						<?php esc_html_e( 'I\'ve already left a review', 'animation-addons-for-elementor' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
	<a href="#" data-dismiss class="aae-review-close">
		<span class="dashicons dashicons-no-alt"></span>
	</a>
</div>

