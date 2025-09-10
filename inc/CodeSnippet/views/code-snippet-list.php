<?php
/**
 * Admin View: List Code Snippets
 *
 * @package WCF_ADDONS\CodeSnippet
 * @since 1.0.0
 */

use WCF_ADDONS\CodeSnippet\Helpers;

defined( 'ABSPATH' ) || exit;

// Initialize the list table
$list_table = Helpers::aae_get_list_table( 'wcf-code-snippet' );

// Process any bulk actions
$action = $list_table->current_action();
if ( $action ) {
	$list_table->process_bulk_action( $action );
}

// Prepare items for display
$list_table->prepare_items();

// Handle messages
$message = '';
if ( isset( $_GET['message'] ) ) {
	$message_code = sanitize_key( wp_unslash( $_GET['message'] ) );
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
		// Display any additional admin notices
		if ( method_exists( $list_table, 'admin_notices' ) ) {
			$list_table->admin_notices();
		}
		?>

		<form id="code-snippet-list-table-form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<?php
			// Preserve essential URL parameters.
			$preserve_params = array( 'page', 'code_type', 'orderby', 'order' );
			foreach ( $preserve_params as $param ) {
				if ( isset( $_GET[ $param ] ) ) {
					printf(
						'<input type="hidden" name="%s" value="%s" />',
						esc_attr( $param ),
						esc_attr( sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) )
					);
				}
			}
			$list_table->views();
			$list_table->search_box( __( 'Search Snippets', 'animation-addons-for-elementor' ), 'snippet' );
			$list_table->display();
			?>
		</form>
	</div>
</div>

<script>
	jQuery(document).ready(function($) {
	$('.row-actions .delete a').on('click', function(e) {
		if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete this snippet?', 'animation-addons-for-elementor' ) ); ?>')) {
			e.preventDefault();
			return false;
		}
	});

	// Confirm bulk delete actions
	$('#doaction, #doaction2').on('click', function(e) {
		var action = $(this).siblings('select').val();
		if (action === 'delete') {
			var checkedBoxes = $('input[name="ids[]"]:checked');
			if (checkedBoxes.length > 0) {
				var confirmMessage = checkedBoxes.length === 1
					? '<?php echo esc_js( __( 'Are you sure you want to delete this snippet?', 'animation-addons-for-elementor' ) ); ?>'
					: '<?php echo esc_js( __( 'Are you sure you want to delete the selected snippets?', 'animation-addons-for-elementor' ) ); ?>';

				if (!confirm(confirmMessage)) {
					e.preventDefault();
					return false;
				}
			}
		}
	});

	// Auto-submit form when filter dropdown changes
	$('#filter-by-code-type').on('change', function() {
		// Clear search when changing filters
		$('input[name="s"]').val('');
		$(this).closest('form').submit();
	});

	// Handle search form submission
	$('#search-submit').on('click', function(e) {
		// Reset pagination when searching
		$('input[name="paged"]').remove();
		// Reset filters when searching (optional)
		// $('select[name="code_type"]').val('all');
	});

	// Handle views links (All, HTML, CSS, etc.)
	$('.subsubsub a').on('click', function(e) {
		e.preventDefault();
		var href = $(this).attr('href');
		window.location.href = href;
	});
	});
</script>