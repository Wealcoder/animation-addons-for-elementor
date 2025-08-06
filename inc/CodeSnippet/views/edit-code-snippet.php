<?php
/**
 * Admin views: Add/Edit Code Snippet
 *
 * @since 2.3.10
 * @package WCF_ADDONS
 */

defined( 'ABSPATH' ) || exit;

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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet&new=1' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' ); ?>
				</a>
			<?php } ?>
		</h1>
		<p>Create and manage custom code snippets for your WordPress site</p>
	</div>

	<div class="form-content">
		<form class="form-grid" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<div class="main-form">
				<div class="form-group">
					<label for="snippet-title">Snippet Title *</label>
					<input type="text" id="snippet-title" name="snippet_title" placeholder="Enter a descriptive title for your snippet"  value="">
					<div class="help-text">Choose a clear, descriptive name to easily identify this snippet</div>
				</div>

				<div class="inline-fields">
					<div class="form-group">
						<label for="code-type">Code Type</label>
						<select id="code-type" name="code_type">
							<option value="html">HTML</option>
							<option value="css">CSS</option>
							<option value="javascript">JavaScript</option>
							<option value="php">PHP</option>
						</select>
					</div>

					<div class="form-group">
						<label for="load-location">Load Location</label>
						<select id="load-location" name="load_location">
							<option value="head">Head Section</option>
							<option value="footer">Footer</option>
							<option value="body_start">After Body Open</option>
							<option value="content_before">Before Content</option>
							<option value="content_after">After Content</option>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label for="code-content">Code Content *</label>
					<div class="code-editor">
						<div class="code-editor-header">
							<span class="dot-red"></span>
							<span class="dot-yellow"></span>
							<span class="dot-green"></span>
							<div class="language">HTML/CSS/JS</div>
						</div>
						<textarea
								class="code-textarea"
								id="code-content"
								name="code_content"
								placeholder=""
						></textarea>
					</div>
					<div class="help-text">Write your HTML, CSS, or JavaScript code. Use proper syntax for best results.</div>
				</div>
			</div>

			<div class="sidebar">
				<div class="sidebar-section">
					<h3>Status</h3>
					<div class="form-group">
						<label for="active-toggle" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
							<span>Active Status</span>
							<label class="toggle-switch">
								<input type="checkbox" id="active-toggle" name="is_active" checked>
								<span class="slider"></span>
							</label>
						</label>
						<div class="help-text">Enable or disable this snippet</div>
					</div>
				</div>

				<div class="sidebar-section">
					<h3>Priority</h3>
					<div class="form-group">
						<label for="priority-slider">Execution Priority</label>
						<input type="range" id="priority-slider" name="priority" min="1" max="100" value="50" class="priority-slider" oninput="updatePriorityValue(this.value)">
						<div class="priority-value" id="priority-value">50</div>
						<div class="help-text">Higher numbers = higher priority</div>
					</div>
				</div>

				<div class="sidebar-section">
					<h3>Page Visibility</h3>
					<div class="form-group">
						<label>Where should this snippet appear?</label>
						<div class="checkbox-group">
							<div class="checkbox-item">
								<input type="checkbox" id="front-page" name="visibility[]" value="front_page" checked>
								<label for="front-page">Front Page</label>
							</div>
							<div class="checkbox-item">
								<input type="checkbox" id="all-posts" name="visibility[]" value="posts">
								<label for="all-posts">All Posts</label>
							</div>
							<div class="checkbox-item">
								<input type="checkbox" id="all-pages" name="visibility[]" value="pages">
								<label for="all-pages">All Pages</label>
							</div>
							<div class="checkbox-item">
								<input type="checkbox" id="archives" name="visibility[]" value="archives">
								<label for="archives">Archive Pages</label>
							</div>
							<div class="checkbox-item">
								<input type="checkbox" id="search" name="visibility[]" value="search">
								<label for="search">Search Results</label>
							</div>
							<div class="checkbox-item">
								<input type="checkbox" id="404" name="visibility[]" value="404">
								<label for="404">404 Pages</label>
							</div>
							<div class="checkbox-item">
								<input type="checkbox" id="admin" name="visibility[]" value="admin">
								<label for="admin">Admin Area</label>
							</div>
						</div>
						<div class="help-text">Select where this code snippet should be loaded</div>
					</div>
				</div>
			</div>
		</form>

		<div class="action-buttons">
			<input type="hidden" name="action" value="wcf_code_snippet"/>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet' ) ); ?>" class="btn btn-secondary">
				<?php esc_html_e( 'Back', 'animation-addons-for-elementor' ); ?>
			</a>
			<?php wp_nonce_field( 'wcf_code_snippet' ); ?>
			<?php if ( ! isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<button class="button button-primary"><?php esc_html_e( 'Save Code Snippet', 'animation-addons-for-elementor' ); ?></button>
			<?php } else { ?>
				<input type="hidden" name="snippet_id" >
				<a class="del" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', admin_url( 'admin.php?page=wcf-code-snippet&id=10' ) ), 'bulk-snippets' ) ); ?>"><?php esc_html_e( 'Delete', 'animation-addons-for-elementor' ); ?></a>
				<button class="btn btn-primary"><?php esc_html_e( 'Update Code Snippet', 'animation-addons-for-elementor' ); ?></button>
			<?php } ?>
		</div>
	</div>
</div>