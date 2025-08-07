<?php
/**
 * Admin views: Add/Edit Code Snippet
 *
 * @since 2.3.10
 * @package WCF_ADDONS
 */

use WCF_ADDONS\WCF_Theme_Builder;

defined( 'ABSPATH' ) || exit;

?>
<?php
echo '<pre>';
// var_dump(WCF_Theme_Builder::get_hf_location_selections());

$locations = WCF_Theme_Builder::get_hf_location_selections();
echo '</pre>';
?>
<div class="container">
	<div class="header">
		<h1>
			<?php
			if ( ! isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' );
			} else {
				esc_html_e( 'Edit Snippet', 'animation-addons-for-elementor' );
			}
			?>
			<?php if ( isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet&new=1' ) ); ?>" class="btn btn-secondary">
					<?php esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' ); ?>
				</a>
			<?php } ?>
		</h1>
		<p><?php esc_html_e( 'Create and manage custom code snippets for your WordPress site', 'animation-addons-for-elementor' ); ?></p>
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
							<option value="html" <?php selected( $snippet_details['code_type'], 'html' ); ?>>HTML</option>
							<option value="css" <?php selected( $snippet_details['code_type'], 'css' ); ?>>CSS</option>
							<option value="javascript" <?php selected( $snippet_details['code_type'], 'javascript' ); ?>>JavaScript</option>
							<option value="php" <?php selected( $snippet_details['code_type'], 'php' ); ?>>PHP</option>
						</select>
					</div>

					<div class="form-group">
						<label for="load-location"><?php esc_html_e( 'Load Location', 'animation-addons-for-elementor' ); ?></label>
						<select id="load-location" name="load_location">
							<option value="head" <?php selected( $snippet_details['code_type'], 'head' ); ?>>Head Section</option>
							<option value="footer" <?php selected( $snippet_details['code_type'], 'footer' ); ?>>Footer</option>
							<option value="body_start" <?php selected( $snippet_details['code_type'], 'body_start' ); ?>>After Body Open</option>
							<option value="content_before" <?php selected( $snippet_details['code_type'], 'content_before' ); ?>>Before Content</option>
							<option value="content_after" <?php selected( $snippet_details['code_type'], 'content_after' ); ?>>After Content</option>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label for="code-content-hidden"><?php esc_html_e( 'Code Content', 'animation-addons-for-elementor' ); ?> *</label>
					<!-- Replace the textarea with this div -->
					<div id="wp-code-editor-container" class="code-editor-wrapper">
						<!-- CodeMirror will be initialized here -->
					</div>

					<!-- Hidden field for form submission -->
					<input type="hidden" id="code-content-hidden" name="code_content" value="<?php echo $snippet_details['code_content'] ? esc_textarea( $snippet_details['code_content'] ) : ''; ?>">

					<!-- Add toolbar buttons -->
					<div class="editor-toolbar">
						<button type="button" id="theme-toggle-btn" class="button"><?php esc_html_e( 'Toggle Theme', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" id="fullscreen-btn" class="button"><?php esc_html_e( 'Fullscreen', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" id="copy-code-btn" class="button"><?php esc_html_e( 'Copy', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" id="download-code-btn" class="button"><?php esc_html_e( 'Download', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" id="insert-example-btn" class="button"><?php esc_html_e( 'Insert Example', 'animation-addons-for-elementor' ); ?></button>
					</div>

					<!-- Stats display -->
					<div id="editor-stats" class="editor-stats"></div>
					<div class="help-text"><?php esc_html_e( 'Write your HTML, CSS, JavaScript, or PHP code. Use proper syntax for best results.', 'animation-addons-for-elementor' ); ?></div>
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
						<input type="range" id="priority-slider" name="priority" min="1" max="100" value="<?php echo esc_attr( $snippet_details['priority'] ); ?>" class="priority-slider" oninput="updatePriorityValue(this.value)">
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
							<label for="visibility-page-list" class="visibility-page-list">Add Specific Pages</label>
							<select class="visibility-page-list" name="visibility_page_list[]"
									id="visibility-page-list" multiple="multiple">
							</select>
						</div>

						<div class="help-text"><?php esc_html_e( 'Select where this code snippet should be loaded', 'animation-addons-for-elementor' ); ?></div>
					</div>
				</div>
			</div>
			<div class="action-buttons">
				<input type="hidden" name="action" value="add_wcf_code_snippet"/>
				<?php wp_nonce_field( 'wcf_code_snippet' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet' ) ); ?>" class="btn btn-secondary">
					<?php esc_html_e( 'Back', 'animation-addons-for-elementor' ); ?>
				</a>
				<?php if ( ! isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<button class="button button-primary"><?php esc_html_e( 'Add Code Snippet', 'animation-addons-for-elementor' ); ?></button>
				<?php } else { ?>
					<input type="hidden" name="snippet_id" value="<?php echo absint( $code_snippet_id ); ?>">
					<a class="del btn btn-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', admin_url( 'admin.php?page=wcf-code-snippet&id=' . absint( $code_snippet_id ) ) ), 'bulk-snippets' ) ); ?>"><?php esc_html_e( 'Delete', 'animation-addons-for-elementor' ); ?></a>
					<button class="btn btn-primary"><?php esc_html_e( 'Update Code Snippet', 'animation-addons-for-elementor' ); ?></button>
				<?php } ?>
			</div>
		</form>
	</div>
</div>