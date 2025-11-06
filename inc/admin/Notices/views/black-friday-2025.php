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
	<div class="falling-element">👻</div>
	<div class="falling-element">🎃</div>
	<div class="falling-element">🎃</div>
	<div class="falling-element">🕷️</div>
	<div class="falling-element">🦇</div>

	<img src="<?php echo esc_url( WCF_ADDONS_URL . 'assets/images/notice/halloween-animation-addons.gif' ); ?>" class="aae-halloween-end-shape" alt="Animation Addons" />
	<div class="aae-halloween-container">
		<div class="aae-halloween-item">
			<div class="aae-halloween-logo">
				<img src="<?php echo esc_url( WCF_ADDONS_URL . 'assets/images/notice/halloween-offer-text.png' ); ?>" alt="Animation Addons" />
			</div>
			<div class="aae-halloween-content">
				<h3 class="aae-halloween-title">🎃
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Halloween Deal for You */
							__( 'Halloween Deal for You — Save up to <span>$1050!</span>', 'animation-addons-for-elementor' )
						)
					);
					?>
				</h3>
				<p class="aae-halloween-text">
					<?php
					echo wp_kses_post(
						sprintf(
							// translators: %1$s: Animation Addons for Elementor Pro link, %2$s: Discount Pricing page link.
							__( 'Upgrade to %1$s and unlock advanced GSAP animations, templates, and features — all with a flat %2$s this <strong>Halloween!</strong>', 'animation-addons-for-elementor' ),
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

