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
<form class="form-grid" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<div class="aae-csp">
		<div class="aae-csp__container">
		<div class="aae-csp-top">
			<div class="aae-csp-top__start">
				<div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet' ) ); ?>" class="aae-csp-top__backward-btn">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none" class="w-[14px] h-[14px] flex-shrink-0">
						<path d="M3.34988 6.1001H14V7.90001H3.34988L8.04335 12.7273L6.80593 14L0 7.00005L6.80593 0L8.04335 1.27273L3.34988 6.1001Z" fill="#717784"></path>
					</svg>
				</a>
				</div>
				<div class="aae-csp-top__content">
					<h2 class="aae-csp-top__title">
						<?php
						if ( ! isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' );
						} else {
							esc_html_e( 'Edit Snippet', 'animation-addons-for-elementor' );
						}
						?>
					</h2>
					<p class="aae-csp-top__text"><?php esc_html_e( 'Create and manage custom code snippets for your WordPress site', 'animation-addons-for-elementor' ); ?></p>
				</div>
			</div>
			<div class="aae-csp-top__end">
				<input type="hidden" name="action" value="add_wcf_code_snippet"/>
				<?php wp_nonce_field( 'wcf_code_snippet' ); ?>
				<?php if ( isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcf-code-snippet&new=1' ) ); ?>" class="aae-csp-top__tools-btn">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M7.99992 14.6667C11.6666 14.6667 14.6666 11.6667 14.6666 8.00004C14.6666 4.33337 11.6666 1.33337 7.99992 1.33337C4.33325 1.33337 1.33325 4.33337 1.33325 8.00004C1.33325 11.6667 4.33325 14.6667 7.99992 14.6667Z" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M5.33325 8H10.6666" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M8 10.6667V5.33337" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg> <?php esc_html_e( 'Add New Snippet', 'animation-addons-for-elementor' ); ?>
					</a>
				<?php } ?>
				<button class="aae-csp-top__tools-btn">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_49_377)">
							<path d="M3.2001 1.59998H11.2001L14.4001 4.79998V12.8C14.4001 13.2243 14.2315 13.6313 13.9315 13.9313C13.6314 14.2314 13.2244 14.4 12.8001 14.4H3.2001C2.77575 14.4 2.36878 14.2314 2.06873 13.9313C1.76867 13.6313 1.6001 13.2243 1.6001 12.8V3.19998C1.6001 2.77563 1.76867 2.36866 2.06873 2.0686C2.36878 1.76855 2.77575 1.59998 3.2001 1.59998Z" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M6.3999 9.6C6.3999 10.0243 6.56847 10.4313 6.86853 10.7314C7.16859 11.0314 7.57556 11.2 7.9999 11.2C8.42425 11.2 8.83121 11.0314 9.13127 10.7314C9.43133 10.4313 9.5999 10.0243 9.5999 9.6C9.5999 9.17565 9.43133 8.76869 9.13127 8.46863C8.83121 8.16857 8.42425 8 7.9999 8C7.57556 8 7.16859 8.16857 6.86853 8.46863C6.56847 8.76869 6.3999 9.17565 6.3999 9.6Z" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M9.60005 1.59998V4.79998H4.80005V1.59998" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</g>
					</svg> <?php esc_html_e( 'Save Snippet', 'animation-addons-for-elementor' ); ?> 
				</button>
				<?php if ( isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<input type="hidden" name="snippet_id" value="<?php echo absint( $code_snippet_id ); ?>">
					<span class="aae-csp-top__tools-btn" id="ajax-delete-snippet" data-id="<?php echo absint( $code_snippet_id ); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M14 3.98665C11.78 3.76665 9.54667 3.65332 7.32 3.65332C6 3.65332 4.68 3.71999 3.36 3.85332L2 3.98665" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M5.66675 3.31337L5.81341 2.44004C5.92008 1.80671 6.00008 1.33337 7.12675 1.33337H8.87341C10.0001 1.33337 10.0867 1.83337 10.1867 2.44671L10.3334 3.31337" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M12.5667 6.09338L12.1334 12.8067C12.06 13.8534 12 14.6667 10.14 14.6667H5.86002C4.00002 14.6667 3.94002 13.8534 3.86668 12.8067L3.43335 6.09338" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M6.88672 11H9.10672" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M6.33325 8.33337H9.66659" stroke="#525866" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				<?php } ?>
			</div>
		</div>
		<div class="aae-csp-main">
			<div class="aae-csp-aside">
			<div class="aae-csp-active aae-csp-widget">
				<span class="aae-csp-active__status <?php echo 'yes' === $snippet_details['is_active'] ? '' : 'inactive'; ?>">
					<span>
						<?php echo 'yes' === $snippet_details['is_active'] ? 'Active' : 'Inactive'; ?>
					</span>
				</span>
				<div class="aae-csp-active__input-wrapper">
					<p class="aae-csp-active__label"><?php esc_html_e( 'Enable or disable this snippet', 'animation-addons-for-elementor' ); ?></p>
					<div class="checkbox-wrapper-6">
						<input class="tgl tgl-light" id="active-toggle" type="checkbox" name="is_active" value="yes" <?php checked( $snippet_details['is_active'], 'yes' ); ?>/>
						<label class="tgl-btn" for="active-toggle">
					</div>
				</div>
				<div class="aae-csp-active__title-group">
					<label for="snippet-title" class="aae-csp-aside__label"><?php esc_html_e( 'Snippet Title', 'animation-addons-for-elementor' ); ?> *</label>
					<input class="aae-csp-aside__input" autocomplete="off" type="text" id="snippet-title" name="snippet_title" placeholder="<?php esc_html_e( 'Enter a descriptive title for your snippet', 'animation-addons-for-elementor' ); ?>"  value="<?php echo isset( $snippet_details['snippet_title'] ) ? esc_attr( $snippet_details['snippet_title'] ) : ''; ?>">
					<span class="aae-csp-aside__help-text"><?php esc_html_e( 'Choose a clear, descriptive name to easily identify this snippet', 'animation-addons-for-elementor' ); ?></span>
				</div>
			</div>
			<div class="aae-csp-visibility aae-csp-widget">
				<div class="aae-csp-visibility__input-group">
					<div>
						<label for="visibility-page" class="aae-csp-aside__label"><?php esc_html_e( 'Where should this snippet appear?', 'animation-addons-for-elementor' ); ?></label>
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
						<span class="aae-csp-aside__help-text"><?php esc_html_e( 'Select where this code snippet should be loaded', 'animation-addons-for-elementor' ); ?></span>
					</div>
					<div class="form-subgroup aae-csp-visibility__visibility-pages">
						<label for="visibility-page-list" class="visibility-page-list aae-csp-aside__label"><?php esc_html_e( 'Add Specific Pages', 'animation-addons-for-elementor' ); ?></label>
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
						<span class="aae-csp-aside__help-text"><?php esc_html_e( 'Search and select pages to execute your snippet.', 'animation-addons-for-elementor' ); ?></span>
					</div>
				</div>
				<div class="aae-csp-visibility__input-group" id="load-location-group">
					<label for="load-location" class="aae-csp-aside__label"><?php esc_html_e( 'Load Location', 'animation-addons-for-elementor' ); ?></label>
					<select id="load-location" name="load_location">
						<option value=""><?php echo esc_html__( 'Select Location', 'animation-addons-for-elementor' ); ?></option>
						<?php foreach ( Helpers::get_load_location_list() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $snippet_details['load_location'], $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="aae-csp-aside__help-text"><?php esc_html_e( 'Select the page location where that will appear.', 'animation-addons-for-elementor' ); ?></span>
				</div>
				<div id="php-version-notice" class="form-group aae-csp-visibility__input-group">

				</div>
				<div class="aae-csp-visibility__input-group">
					<label for="priority-slider" class="aae-csp-aside__label"><?php esc_html_e( 'Execution Priority', 'animation-addons-for-elementor' ); ?></label>
					<div class="aae-csp-priority-slider-wrapper">
						<input type="range" id="priority-slider" name="priority" min="1" max="999" value="<?php echo esc_attr( $snippet_details['priority'] ); ?>" class="priority-slider" oninput="updatePriorityValue(this.value)">
						<label for="priority-value"><input type="number" min="1" max="999"  class="priority-value" id="priority-value" name="priority-value" value="<?php echo esc_attr( $snippet_details['priority'] ); ?>"></label>
					</div>
					<span class="aae-csp-aside__help-text"><?php esc_html_e( 'Higher numbers = higher priority', 'animation-addons-for-elementor' ); ?></span>
				</div>
			</div>
			</div>
			<div class="aae-csp-content">
			<div class="aae-csp-editor">
				<div class="aae-csp-editor__field-group">
					<label for="code-type" class="aae-csp-aside__label"><?php esc_html_e( 'Code Type', 'animation-addons-for-elementor' ); ?></label>
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
				<div class="aae-csp-editor-top">
					<div class="aae-csp-editor-top-start">
						<p class="aae-csp-editor-top__text"><?php esc_html_e( 'Code Content', 'animation-addons-for-elementor' ); ?>*<span>(<?php esc_html_e( 'Write your HTML, CSS, JavaScript, or PHP code. Use proper syntax for best results.', 'animation-addons-for-elementor' ); ?>)</span></p>
					</div>
					<div class="aae-csp-editor-top__end">
						<div class="aae-csp-editor__mode">
							<p class="aae-csp-active__label"><?php esc_html_e( 'Dark Mode', 'animation-addons-for-elementor' ); ?></p>
							<div class="checkbox-wrapper-6 mode">
								<input class="tgl tgl-light" id="theme-toggle-btn" type="checkbox"/>
								<label class="tgl-btn" for="theme-toggle-btn">
							</div>
						</div>
						<button class="aae-csp-top__tools-btn" type="button" id="fullscreen-btn">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M5.99992 14.6667H9.99992C13.3333 14.6667 14.6666 13.3334 14.6666 10V6.00004C14.6666 2.66671 13.3333 1.33337 9.99992 1.33337H5.99992C2.66659 1.33337 1.33325 2.66671 1.33325 6.00004V10C1.33325 13.3334 2.66659 14.6667 5.99992 14.6667Z" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M12 4L4 12" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M11.9999 6.66667V4H9.33325" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M4 9.33337V12H6.66667" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							</svg> 
						</button>
						<button class="aae-csp-top__tools-btn" type="button" id="copy-code-btn">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M11.3132 8.16663V11.1666C11.3132 13.6666 10.3132 14.6666 7.81323 14.6666H4.81323C2.31323 14.6666 1.31323 13.6666 1.31323 11.1666V8.16663C1.31323 5.66663 2.31323 4.66663 4.81323 4.66663H7.81323C10.3132 4.66663 11.3132 5.66663 11.3132 8.16663Z" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M14.6466 3.90004V6.10004C14.6466 7.93337 13.9132 8.66671 12.0799 8.66671H11.3132V8.16671C11.3132 5.66671 10.3132 4.66671 7.81323 4.66671H7.31323V3.90004C7.31323 2.06671 8.04657 1.33337 9.8799 1.33337H12.0799C13.9132 1.33337 14.6466 2.06671 14.6466 3.90004Z" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							</svg> 
						</button>
						<button class="aae-csp-top__tools-btn" type="button" id="download-code-btn">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M10.96 5.93335C13.36 6.14002 14.34 7.37335 14.34 10.0733V10.16C14.34 13.14 13.1467 14.3333 10.1667 14.3333H5.82665C2.84665 14.3333 1.65332 13.14 1.65332 10.16V10.0733C1.65332 7.39335 2.61999 6.16002 4.97999 5.94002" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M8 1.33337V9.92004" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M10.2333 8.43335L7.99994 10.6667L5.7666 8.43335" stroke="#717784" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
					</div>
				</div>
				<div id="wp-code-editor-container" class="code-editor-wrapper">
					<!-- CodeMirror will be initialized here -->
				</div>
				<input type="hidden" id="code-content-hidden" name="code_content" value="<?php echo $snippet_details['code_content'] ? esc_textarea( $snippet_details['code_content'] ) : ''; ?>">
				<div class="aae-csp-editor__footer">
					<div id="editor-stats" class="editor-stats"></div>
					<button class="aae-csp-editor__insertBtn" type="button" id="insert-example-btn">
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12.8334 1.16675L8.05005 5.95008" stroke="#717784" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M7.58325 3.59912V6.41662H10.4008" stroke="#717784" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M6.41675 1.16675H5.25008C2.33341 1.16675 1.16675 2.33341 1.16675 5.25008V8.75008C1.16675 11.6667 2.33341 12.8334 5.25008 12.8334H8.75008C11.6667 12.8334 12.8334 11.6667 12.8334 8.75008V7.58341" stroke="#717784" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<?php esc_html_e( 'Insert Example', 'animation-addons-for-elementor' ); ?>
					</button>
				</div>
			</div>
			</div>
		</div>
		</div>
	</div>
</form>

<div id="wcf-code-loading" class="wcf-code-loading" style="display: none;">
	<div class="spinner"></div>
</div>
