<?php
/**
 * Admin View: List Code Snippets
 *
 * @package WCF_ADDONS\CodeSnippet
 * @since 1.0.0
 */

use WCF_ADDONS\CodeSnippet\Helpers;

defined( 'ABSPATH' ) || exit;

$list_table = Helpers::aae_get_list_table( 'wcf-code-snippet' );
$action     = $list_table->current_action();
if ( $action ) {
	$list_table->process_bulk_action( $action );
}
$list_table->prepare_items();

// Handle messages.
$message = '';
if ( isset( $_GET['message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$message_code = sanitize_key( wp_unslash( $_GET['message'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$messages     = array(
		'deleted'     => __( 'Snippet(s) deleted successfully.', 'animation-addons-for-elementor' ),
		'activated'   => __( 'Snippet(s) activated successfully.', 'animation-addons-for-elementor' ),
		'deactivated' => __( 'Snippet(s) deactivated successfully.', 'animation-addons-for-elementor' ),
		'error'       => __( 'An error occurred. Please try again.', 'animation-addons-for-elementor' ),
	);

	if ( isset( $messages[ $message_code ] ) ) {
		$message      = $messages[ $message_code ];
		$message_type = 'error' === $message_code ? 'error' : 'success';
	}
}
?>

<div class="wrap wcf-code-snippet-admin">
	<div class="wcf-admin-page-header">
		<div class="wcf-header-content">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Code Snippets', 'animation-addons-for-elementor' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet&new=1' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' ); ?>
				</a>
			</h1>

			<?php if ( ! empty( $message ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( $message_type ?? 'success' ); ?> is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="wcf-admin-page-content">
		<?php
		// Display any additional admin notices.
		if ( method_exists( $list_table, 'admin_notices' ) ) {
			$list_table->admin_notices();
		}
		?>

		<form id="code-snippet-list-table-form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" onsubmit="return false;">
			<?php
			$list_table->views();
			$list_table->search_box( __( 'Search Snippets', 'animation-addons-for-elementor' ), 'snippet' );
			$list_table->display();
			?>
		</form>
	</div>
</div>