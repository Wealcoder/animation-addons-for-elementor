<?php
/**
 * View: Admin Page
 *
 * @since 2.3.10
 * @subpackage CodeSnippet/views
 * @package WCF_ADDONS\CodeSnippet
 * @var string $page_hook Page hook.
 */

defined( 'ABSPATH' ) || exit;

$current_tab  = filter_input( INPUT_GET, 'tab' );
$current_page = filter_input( INPUT_GET, 'page' );
$tabs         = isset( $tabs ) ? $tabs : array();
$tabs         = apply_filters( 'wcf_code_snippet_' . $page_hook . '_tabs', $tabs );
$current_tab  = ! empty( $current_tab ) && array_key_exists( $current_tab, $tabs ) ? $current_tab : key( $tabs );
?>
	<div class="" id="wcptm-top-bar">
		<div class="">
			<h1	 class=""><?php echo esc_attr( 'AAE Code Snippet' ); ?></h1>
			<p class=""><?php esc_html_e( 'Manage your reusable code snippets here. You can add custom HTML, CSS, JavaScript, or PHP snippets to use across your site.', 'animation-addons-for-elementor' ); ?></p>
		</div>
	</div>
	<div class="wrap bk-wrap ">
		<?php if ( ! empty( $tabs ) && count( $tabs ) > 1 ) : ?>
			<nav class="nav-tab-wrapper bk-navbar">
				<?php
				foreach ( $tabs as $name => $label ) {
					printf(
						'<a href="%s" class="nav-tab %s">%s</a>',
						esc_url( admin_url( 'admin.php?page=' . $current_page . '&tab=' . $name ) ),
						esc_attr( $current_tab === $name ? 'nav-tab-active' : '' ),
						esc_html( $label )
					);
				}
				?>
				<?php
				/**
				 * Fires after the tabs on the settings page.
				 *
				 * @param string $current_tab Current tab..
				 * @param array $tabs Tabs.
				 *
				 * @since 2.3.10
				 */
				do_action( 'wcf_code_snippet_' . $page_hook . '_nav_items', $current_tab, $tabs );
				?>
			</nav>
		<?php endif; ?>

		<?php
		if ( ! empty( $tabs ) && ! empty( $current_tab ) ) {
			/**
			 * Action: AAE Code Snippet Admin Page Tab
			 *
			 * @param string $current_tab Current tab.
			 *
			 * @since 2.3.10
			 */
			do_action( "wcf_code_snippet_{$page_hook}_{$current_tab}_content", $current_tab );
		}

		/**
		 * Action: AAE Code Snippet Admin Page
		 *
		 * @param string $current_tab Current tab.
		 *
		 * @since 2.3.10
		 */
		do_action( "wcf_code_snippet_{$page_hook}_content", $current_tab );
		?>
	</div>
<?php
