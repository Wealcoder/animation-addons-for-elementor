<?php
/**
 * Admin Halloween 2025 Offer Promotions.
 *
 * @since 2.4.16
 * @return void
 *
 * @package WCF_ADDONS
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="aae-halloween-wrapper">
	<img src="<?php echo esc_url( WCF_ADDONS_URL . 'assets/images/notice/black-friday-shape-1.png' ); ?>" class="aae-halloween-end-shape" alt="Animation Addons" />
	<div class="aae-halloween-container">
		<div class="aae-halloween-item">
			<div class="aae-halloween-logo">
				<img src="<?php echo esc_url( WCF_ADDONS_URL . 'assets/images/notice/black-friday-offer.png' ); ?>" alt="Animation Addons" />
			</div>
			<div class="aae-halloween-content">
				<h3 class="aae-halloween-title">
					<?php
					echo wp_kses_post(
						sprintf(
							__( 'Black Friday Deal for You <span>$1050!</span>', 'animation-addons-for-elementor' )
						)
					);
					?>
				</h3>
				<p class="aae-halloween-text">
					<?php
					echo wp_kses_post(
						sprintf(
							__( 'Upgrade to %1$s and unlock advanced GSAP animations, templates, and features — all with a flat %2$s this <strong>Black Friday!</strong>', 'animation-addons-for-elementor' ),
							'<a href="https://animation-addons.com/" target="_blank"><strong>Animation Addons for Elementor Pro</strong></a>',
							'<a href="https://animation-addons.com/pricing/" target="_blank"><strong>70% discount</strong></a>'
						)
					);
					?>

				</p>
				<div class="aae-halloween-btns">
					<a href="<?php echo esc_url( 'https://animation-addons.com/pricing/?utm_source=wp&utm_medium=noticebanner&utm_campaign=halloween' ); ?>" class="aae-halloween-btn" target="_blank">
						<span class="dashicons dashicons-cart"></span>
						<?php esc_html_e( 'Save $1050 Now', 'animation-addons-for-elementor' ); ?>
					</a>
					<a href="#" class="aae-halloween-btn outline" data-snooze="<?php echo esc_attr( DAY_IN_SECONDS ); ?>">
						<span class="dashicons dashicons-clock"></span>
						<?php esc_html_e( 'Skip for Now', 'animation-addons-for-elementor' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
	<a href="#" data-dismiss class="aae-halloween-close">
		<span class="dashicons dashicons-no-alt"></span>
	</a>
</div>

