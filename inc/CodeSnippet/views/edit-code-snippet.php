<?php
/**
 * Admin views: Add/Edit Code Snippet
 *
 * @since 2.3.10
 * @package WCF_ADDONS\CodeSnippet
 */

use WCF_ADDONS\CodeSnippet\Helpers;
use WCF_ADDONS\WCF_Theme_Builder;

defined( 'ABSPATH' ) || exit;

$locations = WCF_Theme_Builder::get_hf_location_selections();

if ( 'php' === $snippet_details['code_type'] ) {
	$locations['basic']['value'] = array_merge(
		$locations['basic']['value'],
		array(
			'frontend' => 'Frontend',
			'admin'    => 'Frontend',
		)
	);
}

?>
<div class="container">
	<div class="header">
		<h1>
			<div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet' ) ); ?>" class="">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none" class="w-[14px] h-[14px] flex-shrink-0"><path d="M3.34988 6.1001H14V7.90001H3.34988L8.04335 12.7273L6.80593 14L0 7.00005L6.80593 0L8.04335 1.27273L3.34988 6.1001Z" fill="#ffffff"></path></svg>
				</a>
				<div>
					<?php
					if ( ! isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' );
					} else {
						esc_html_e( 'Edit Snippet', 'animation-addons-for-elementor' );
					}
					?>
				</div>•
				<p><?php esc_html_e( 'Create and manage custom code snippets for your WordPress site', 'animation-addons-for-elementor' ); ?></p>
			</div>
			<div>
				<?php if ( isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet&new=1' ) ); ?>" class="btn btn-secondary-white" id="ajax-delete-snippet">
						<?php esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' ); ?>
					</a>
				<?php } ?>
			</div>
		</h1>
	</div>

	<div class="form-content">
		<form class="form-grid" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<div class="main-form">
				<div class="form-group">
					<label for="snippet-title"><?php esc_html_e( 'Snippet Title', 'animation-addons-for-elementor' ); ?> *</label>
					<input type="text" id="snippet-title" name="snippet_title" placeholder="<?php esc_html_e( 'Enter a descriptive title for your snippet', 'animation-addons-for-elementor' ); ?>"  value="<?php echo isset( $snippet_details['snippet_title'] ) ? esc_attr( $snippet_details['snippet_title'] ) : ''; ?>">
					<div class="help-text"><?php esc_html_e( 'Choose a clear, descriptive name to easily identify this snippet', 'animation-addons-for-elementor' ); ?></div>
				</div>

				<div class="inline-fields">
					<div class="form-group">
						<label for="code-type"><?php esc_html_e( 'Code Type', 'animation-addons-for-elementor' ); ?></label>
						<select id="code-type" name="code_type">
							<?php
							foreach ( Helpers::get_code_type_list() as $key => $code_type ) {
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $snippet_details['code_type'], $key ); ?>><?php echo esc_html( $code_type ); ?></option>
								<?php
							}
							?>
						</select>
					</div>

					<div class="form-group">
						<label for="load-location"><?php esc_html_e( 'Load Location', 'animation-addons-for-elementor' ); ?></label>
						<select id="load-location" name="load_location">
							<option value=""><?php echo esc_html__( 'Select Location', 'animation-addons-for-elementor' ); ?></option>
							<?php foreach ( Helpers::get_load_location_list() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $snippet_details['load_location'], $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div id="php-version-notice" class="form-group">
						
					</div>
				</div>

				<div class="form-group">
					<label for="code-content-hidden"><?php esc_html_e( 'Code Content', 'animation-addons-for-elementor' ); ?> *</label>
					<div class="help-text"><?php esc_html_e( 'Write your HTML, CSS, JavaScript, or PHP code. Use proper syntax for best results.', 'animation-addons-for-elementor' ); ?></div>

					<!-- Replace the textarea with this div -->
					<div id="wp-code-editor-container" class="code-editor-wrapper">
						<!-- CodeMirror will be initialized here -->
					</div>

					<!-- Hidden field for form submission -->
					<input type="hidden" id="code-content-hidden" name="code_content" value="<?php echo $snippet_details['code_content'] ? esc_textarea( $snippet_details['code_content'] ) : ''; ?>">

					<!-- Add toolbar buttons -->
					<div class="editor-toolbar">
						<button type="button" id="theme-toggle-btn" class="button"><?php esc_html_e( 'Toggle Dark / Light', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" id="fullscreen-btn" class="button"><?php esc_html_e( 'Fullscreen', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" id="copy-code-btn" class="button"><?php esc_html_e( 'Copy', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" id="download-code-btn" class="button"><?php esc_html_e( 'Download', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" id="insert-example-btn" class="button"><?php esc_html_e( 'Insert Example', 'animation-addons-for-elementor' ); ?></button>
					</div>

					<!-- Stats display -->
					<div id="editor-stats" class="editor-stats"></div>
				</div>
			</div>

			<div class="sidebar">
				<div class="sidebar-section">
					<h3><?php esc_html_e( 'Status', 'animation-addons-for-elementor' ); ?></h3>
					<div class="form-group">
						<label for="active-toggle" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
							<span><?php esc_html_e( 'Active Status', 'animation-addons-for-elementor' ); ?></span>
							<label class="toggle-switch">
								<input type="checkbox" id="active-toggle" name="is_active" value="yes" <?php checked( $snippet_details['is_active'], 'yes' ); ?>>
								<span class="slider"></span>
							</label>
						</label>
						<div class="help-text"><?php esc_html_e( 'Enable or disable this snippet', 'animation-addons-for-elementor' ); ?></div>
					</div>
				</div>

				<div class="sidebar-section">
					<h3><?php esc_html_e( 'Priority', 'animation-addons-for-elementor' ); ?></h3>
					<div class="form-group">
						<label for="priority-slider"><?php esc_html_e( 'Execution Priority', 'animation-addons-for-elementor' ); ?></label>
						<input type="range" id="priority-slider" name="priority" min="1" max="999" value="<?php echo esc_attr( $snippet_details['priority'] ); ?>" class="priority-slider" oninput="updatePriorityValue(this.value)">
						<div class="priority-value" id="priority-value"><?php echo absint( $snippet_details['priority'] ); ?></div>
						<div class="help-text"><?php esc_html_e( 'Higher numbers = higher priority', 'animation-addons-for-elementor' ); ?></div>
					</div>
				</div>

				<div class="sidebar-section">
					<h3><?php esc_html_e( 'Page Visibility', 'animation-addons-for-elementor' ); ?></h3>
					<div class="form-group">
						<label for="visibility-page"><?php esc_html_e( 'Where should this snippet appear?', 'animation-addons-for-elementor' ); ?></label>
						<select id="visibility-page" name="visibility_page">
							<?php foreach ( $locations as $group_key => $group ) : ?>
								<optgroup label="<?php echo esc_attr( $group['label'] ); ?>">
									<?php foreach ( $group['value'] as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $snippet_details['visibility_page'], $key ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
							<?php endforeach; ?>
						</select>

						<div class="form-subgroup">
							<label for="visibility-page-list" class="visibility-page-list"><?php esc_html_e( 'Add Specific Pages', 'animation-addons-for-elementor' ); ?></label>
							<select class="visibility-page-list" name="visibility_page_list[]" id="visibility-page-list" multiple="multiple">
								<?php
								if ( ! empty( $snippet_details['visibility_page_list'] ) && is_array( $snippet_details['visibility_page_list'] ) ) {
									foreach ( $snippet_details['visibility_page_list'] as $page ) :
										?>
									<option value="<?php echo esc_attr( $page ); ?>" selected="selected">
										<?php echo esc_html( get_the_title( $page ) ); ?>
									</option>
										<?php
								endforeach;
								}
								?>
							</select>
						</div>

						<div class="help-text"><?php esc_html_e( 'Select where this code snippet should be loaded', 'animation-addons-for-elementor' ); ?></div>
					</div>
				</div>
			</div>
			<div class="action-buttons">
				<input type="hidden" name="action" value="add_wcf_code_snippet"/>
				<?php wp_nonce_field( 'wcf_code_snippet' ); ?>
				<?php if ( ! isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<button class="btn btn-primary"><?php esc_html_e( 'Add Code Snippet', 'animation-addons-for-elementor' ); ?></button>
				<?php } else { ?>
					<input type="hidden" name="snippet_id" value="<?php echo absint( $code_snippet_id ); ?>">
					<span class="btn btn-secondary" id="ajax-delete-snippet" data-id="<?php echo absint( $code_snippet_id ); ?>"><?php esc_html_e( 'Delete', 'animation-addons-for-elementor' ); ?></span>
					<button class="btn btn-primary"><?php esc_html_e( 'Update Code Snippet', 'animation-addons-for-elementor' ); ?></button>
				<?php } ?>
			</div>
		</form>
	</div>
</div>
