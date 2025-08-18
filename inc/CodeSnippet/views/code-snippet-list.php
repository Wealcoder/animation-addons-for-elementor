<?php
/**
 * Admin View: List Code Snippets
 *
 * @package WCF_ADDONS
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$list_table = aae_get_list_table( 'wcf-code-snippet' );
$action     = $list_table->current_action();
$list_table->process_bulk_action( $action );
$list_table->prepare_items();
?>

<div class="wrap bk-wrap">
	<div class="bk-admin-page__header">
		<div>
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Code Snippets', 'animation-addons-for-elementor' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet&new=1' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' ); ?>
				</a>
			</h1>
		</div>
	</div>
	<form id="code-snippet-list-table" method="get">
		<?php
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$list_table->views();
		$list_table->search_box( __( 'Search', 'animation-addons-for-elementor' ), 'key' );
		$list_table->display();
		?>
		<input type="hidden" name="page" value="wcf-code-snippet">
	</form>
</div>

