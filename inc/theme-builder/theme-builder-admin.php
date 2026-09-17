<?php

namespace Wealcoder\AnimationAddons;

use Wealcoder\AnimationAddons\Nonce;

if (! defined('ABSPATH')) {
	exit();
} // Exit if accessed directly

/**
 * The AAE Builder's wp-admin half.
 *
 * Split out of theme-builder.php 2026-09-16. That file is 86 KB and every
 * front-end request parsed all of it, although a third of it exists only to
 * draw the Builder list table, its edit modal and the admin menu entry, and to
 * answer three wp_ajax_ writers. Every hook registered below can only fire in
 * wp-admin, so Aaeaddon_Theme_Builder requires this file under is_admin() and a
 * visitor's request never parses it. admin-ajax IS is_admin(), so the three
 * writers still answer.
 *
 * What did NOT move: the template CPT registration, the header/footer/single/
 * archive overrides, the popup render, the asset deferral, and the six public
 * static helpers (get_template_type(), get_offered_template_type(), the four
 * *_location_selections()). Those are front-end work, or are read from outside
 * -- Pro's global-elements.php and the Code Snippet edit screen both call into
 * them -- so the class's public API is unchanged.
 *
 * Deliberately NOT PSR-4 named: it sits beside the class it was cut from and the
 * one require_once in that constructor is the gate. Nothing else may require it.
 */
class Aaeaddon_Theme_Builder_Admin
{
	/**
	 * @var Aaeaddon_Theme_Builder_Admin|null
	 */
	public static $_instance = null;

	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct()
	{
		$cpt = Aaeaddon_Theme_Builder::CPTTYPE;

		// Add Menu
		add_action('admin_menu', array($this, 'admin_menu'), 225);

		// Print template tabs.
		add_filter('views_edit-' . $cpt, array($this, 'print_tabs'));

		// Template type column.
		add_action('manage_' . $cpt . '_posts_columns', array($this, 'manage_columns'));
		add_action('manage_' . $cpt . '_posts_custom_column', array($this, 'columns_content'), 10, 2);

		// Print template edit popup.
		add_action('admin_footer', array($this, 'print_popup'));

		// Template store ajax action
		// 'wcf_save_template' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'wcf_save_template', 'aaeaddon_save_template', array($this, 'save_template_request') );

		// Get template data Ajax action
		// 'wcf_get_template' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'wcf_get_template', 'aaeaddon_get_template', array($this, 'get_post_By_id') );

		// 'wcf_get_posts_by_query' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'wcf_get_posts_by_query', 'aaeaddon_get_posts_by_query', array($this, 'get_posts_by_query') );

		// Load Scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
	}



	/**
	 * Print Admin Tab
	 *
	 * @param [array] $views
	 *
	 * @return array
	 */
	public function print_tabs($views)
	{
		$active_class = 'nav-tab-active';
		$current_type = '';
		// Read-only admin list-table tab filter; no nonce applies.
		if (isset($_GET['template_type'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active_class = '';
			$current_type = sanitize_key(wp_unslash($_GET['template_type'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
?>
		<div id="wcf-template-tabs-wrapper" class="nav-tab-wrapper">
			<div class="wcf-menu-area">
				<a class="nav-tab <?php echo esc_attr($active_class); ?>"
					href="edit.php?post_type=<?php echo esc_attr(Aaeaddon_Theme_Builder::CPTTYPE); ?>">
					<?php echo esc_html__('All', 'animation-addons-for-elementor'); ?>
				</a>
				<?php
				foreach (Aaeaddon_Theme_Builder::get_offered_template_type() as $tabkey => $tab) {
					// Legacy v3 type on a site that has switched v3 off and owns
					// none of them. query_filter() still honours the GET param,
					// so an old ?template_type=popup bookmark keeps working.
					if (! empty($tab['hidden'])) {
						continue;
					}

					$active_class = ($current_type == $tabkey ? 'nav-tab-active' : '');
					$url          = 'edit.php?post_type=' . Aaeaddon_Theme_Builder::CPTTYPE . '&template_type=' . $tabkey;

					printf(
						'<a class="nav-tab %s" href="%s">%s</a>',
						esc_attr($active_class),
						esc_url($url),
						esc_html($tab['label'])
					);
				}
				?>
			</div>
		</div>
		<?php
		return $views;
	}


	/**
	 * Manage Post Table columns
	 *
	 * @param [array] $columns
	 *
	 * @return array
	 */
	public function manage_columns($columns)
	{

		$column_date = $columns['date'];
		unset($columns['date']);

		$columns['type']   = esc_html__('Type', 'animation-addons-for-elementor');
		$columns['status'] = esc_html__('Display', 'animation-addons-for-elementor');
		$columns['date']   = esc_html($column_date);

		return $columns;
	}


	/**
	 * Manage Custom column content
	 *
	 * @param [string] $column_name
	 * @param [int]    $post_id
	 *
	 * @return void
	 */
	public function columns_content($column_name, $post_id)
	{
		$tmpType = get_post_meta($post_id, Aaeaddon_Theme_Builder::CPT_META . '_type', true);

		if (! array_key_exists($tmpType, Aaeaddon_Theme_Builder::get_template_type())) {
			return;
		}

		if ($column_name === 'type') {
			echo isset( Aaeaddon_Theme_Builder::get_template_type()[ $tmpType ] ) ? '<div class="column-tmptype">' . esc_html( Aaeaddon_Theme_Builder::get_template_type()[ $tmpType ]['label'] ) . '</div>' : '-';
		}

		if ($column_name === 'status') {
			$tmpDisplay = get_post_meta($post_id, Aaeaddon_Theme_Builder::CPT_META . '_location', true);
		?>
			<div class="post-status">
				<strong>Display: </strong>
				<?php echo esc_html($tmpDisplay); ?>
			</div>
		<?php
		}
	}


	/**
	 * Print Template edit popup
	 *
	 * @return void
	 */
	public function print_popup()
	{
		// Read-only admin screen check; no nonce applies.
		if (isset($_GET['post_type']) && Aaeaddon_Theme_Builder::CPTTYPE === sanitize_key(wp_unslash($_GET['post_type']))) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
	<script type="text/template" id="tmpl-wcf-addons-ctppopup">
		<div class="wcf-addons-template-edit-popup-area">
			<div class="wcf-addons-body-overlay"></div>
			<div class="wcf-addons-template-edit-popup">

				<div class="wcf-addons-template-edit-header">
					<h3 class="wcf-addons-template-edit-setting-title">
						{{{data.heading.head}}}
					</h3>
					<span class="wcf-addons-template-edit-cross">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
							class="bi bi-x-lg" viewBox="0 0 16 16"><path
									d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/></svg>
					</span>
				</div>

				<div class="wcf-addons-template-edit-body">

					<div class="wcf-addons-template-edit-field">
						<label class="wcf-addons-template-edit-label">{{{ data.heading.fields.name.title}}}</label>
						<input class="wcf-addons-template-edit-input" id="wcf-addons-template-title" type="text" name="wcf-addons-template-title" placeholder="{{ data.heading.fields.name.placeholder }}"/>
					</div>

					<div class="wcf-addons-template-edit-field">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.type}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-template-type"
								id="wcf-addons-template-type">
							<#
							_.each( data.templatetype, function( item, key ) {

							#>
							<option value="{{ key }}" <# if ( item.hidden ) { #>hidden<# } #>>{{{ item.label }}}</option>
							<#

							} );
							#>
						</select>
					</div>

					<div class="wcf-addons-template-edit-field hf-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-hf-display-type"
								id="wcf-addons-hf-display-type">
							<#
							_.each( data.hflocation, function( items, keys ) {
							#>
							<optgroup label="{{{ items.label }}}">
								<#
								_.each( items.value, function( item, key ) {
								#>
								<option value="{{ key }}">{{{ item }}}</option>
								<#
								} );
								#>
							</optgroup>
							<#
							} );
							#>
						</select>
					</div>

					<div class="wcf-addons-template-edit-field hf-s-location hidden">
						<label class="wcf-addons-template-edit-label"></label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-hf-s-display-type[]"
								id="wcf-addons-hf-s-display-type" multiple="multiple">
						</select>
					</div>

					<div class="wcf-addons-template-edit-field archive-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-archive-display-type"
								id="wcf-addons-archive-display-type">
							<#
							_.each( data.archivelocation, function( items, keys ) {
							#>
							<optgroup label="{{{ items.label }}}">
								<#
								_.each( items.value, function( item, key ) {
								#>
								<option value="{{ key }}">{{{ item }}}</option>
								<#
								} );
								#>
							</optgroup>
							<#
							} );
							#>
						</select>
					</div>

					<div class="wcf-addons-template-edit-field single-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-single-display-type"
								id="wcf-addons-single-display-type">
							<#
							_.each( data.singlelocation, function( items, keys ) {
							#>
							<optgroup label="{{{ items.label }}}">
								<#
								_.each( items.value, function( item, key ) {
								#>
								<option value="{{ key }}">{{{ item }}}</option>
								<#
								} );
								#>
							</optgroup>
							<#
							} );
							#>
						</select>
					</div>

					<div class="wcf-addons-template-edit-field single-category-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.category}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-single-category-display-type"
								id="wcf-addons-single-category-display-type">
							<#								
							_.each( data.postcategory, function( items, keys ) {
							#>                                   
								<#
								_.each( items.value, function( item, key ) {
								#>
								<option value="{{ key }}">{{{ item }}}</option>
								<#
								} );
								#>                                  
							<#
							} );
							#>
						</select>
					</div>
					
					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.trigger}}}</label>
							<select class="wcf-addons-template-edit-input" name="wcf-addons--popup--builder-trigger"
								id="wcf-addons--popup--builder-trigger">
								<option value="click"><?php echo esc_html__('Click', 'animation-addons-for-elementor'); ?></option>
								<option value="pageloaded"><?php echo esc_html__('Page Loaded', 'animation-addons-for-elementor'); ?></option>
								<option value="pageexit"><?php echo esc_html__('Page Body Exist', 'animation-addons-for-elementor'); ?></option>
								<option value="user_inactivity"><?php echo esc_html__('User Inactivity', 'animation-addons-for-elementor'); ?></option>
								<option value="page_scroll"><?php echo esc_html__('Page Scroll', 'animation-addons-for-elementor'); ?></option>
								<option value="page_scroll_up"><?php echo esc_html__('Page Scroll Up', 'animation-addons-for-elementor'); ?></option>
							</select>
					</div>
				
					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.delay}}}</label>
						<input class="wcf-addons-template-edit-input" id="aae-popup-builder-delay" type="number"
								name="aae-popup-builder-delay"
								placeholder="{{ data.heading.fields.delay.placeholder }}">
					</div>

					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.selector}}}</label>
						<input class="wcf-addons-template-edit-input" id="aae-popup-builder-selector" type="text"
								name="aae-popup-builder-selector"
								placeholder=".body">
					</div>
					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label"><?php echo esc_html__('Scroll Postion','animation-addons-for-elementor') ?></label>
						<input class="wcf-addons-template-edit-input" id="aae-popup-builder-scrollPostion" type="text"
								name="aae-popup-builder-scrollPostion"
								placeholder="1500">
					</div>
					
					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label">Effects</label>
							<select class="wcf-addons-template-edit-input" name="wcf-addons--popup--builder-effect"
								id="wcf-addons--popup--builder-effect">
								<option value="flip"><?php echo esc_html__('Flip', 'animation-addons-for-elementor'); ?></option>
								<option value="shakeEffect"><?php echo esc_html__('Scale + Shake Effect', 'animation-addons-for-elementor'); ?></option>
								<option value="slideFromTo"><?php echo esc_html__('Slide From Top', 'animation-addons-for-elementor'); ?></option>
								<option value="zoomBounce"><?php echo esc_html__('Zoom + Bounce', 'animation-addons-for-elementor'); ?></option>
								<option value="fadeSlideup"><?php echo esc_html__('Fade + Slide Up', 'animation-addons-for-elementor'); ?></option>
							</select>
					</div>
							<!-- Header Smoother -->
					<div class="wcf-addons-template-edit-field aae-header-smoother-location hidden">
						<label class="wcf-addons-template-edit-label"><?php echo esc_html__('Smoother?', 'animation-addons-for-elementor'); ?></label>
							<select class="wcf-addons-template-edit-input" name="aae-header-smoother-location"
								id="aae-header-smoother-location">
								<option value=""><?php echo esc_html__('Default', 'animation-addons-for-elementor'); ?></option>
								<option value="yes"><?php echo esc_html__('Yes', 'animation-addons-for-elementor'); ?></option>	
								<option value="no"><?php echo esc_html__('No', 'animation-addons-for-elementor'); ?></option>								
							</select>
					</div>

					<div class="wcf-addons-template-edit-field aae-header-smoother-location yoffset hidden">
						<label class="wcf-addons-template-edit-label"><?php echo esc_html__('OffsetY(px)', 'animation-addons-for-elementor'); ?></label>
							<input class="wcf-addons-template-edit-input" id="aae-header-smoother-yoffset" type="text"
								name="aae-header-smoother-yoffset"
								placeholder="120">
					</div>
					
				</div>
				
				<div class="wcf-addons-template-edit-footer">
					<div class="wcf-addons-template-button-group">
						<div class="wcf-addons-template-button-item wcf-addons-editor-elementor {{ data.haselementor === 'yes' ? 'button-show' : '' }}">
							<button class="wcf-addons-tmp-elementor button">{{{
								data.heading.buttons.elementor.label
								}}}
							</button>
						</div>
						<div class="wcf-addons-template-button-item">
							<button class="wcf-addons-tmp-save button button-primary">{{{
								data.heading.buttons.save.label }}}
							</button>
						</div>
					</div>
				</div>
				
			</div>
		</div>
	</script>
<?php
		}
	}


	/**
	 * Save Template
	 *
	 * @return void
	 */
	public function save_template_request()
	{
		if (isset($_POST)) {

			if (! (current_user_can('manage_options') || current_user_can('edit_others_posts'))) {
				$errormessage = array(
					'message' => esc_html__('You are unauthorize to adding template!', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

			if (! wp_verify_nonce( $nonce, Nonce::action_for( $nonce, Nonce::THEME_BUILDER ) )) {
				$errormessage = array(
					'message' => esc_html__('Nonce Varification Faild !', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$title            = ! empty($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
			$tmpid            = ! empty($_POST['tmpId']) ? sanitize_text_field(wp_unslash($_POST['tmpId'])) : '';
			$tmpType          = ! empty($_POST['tmpType']) ? sanitize_text_field(wp_unslash($_POST['tmpType'])) : 'single';
			$tmplocation      = ! empty($_POST['tmpDisplay']) ? sanitize_text_field(wp_unslash($_POST['tmpDisplay'])) : '';
			$specificsDisplay = ! empty($_POST['specificsDisplay']) ? sanitize_text_field(wp_unslash($_POST['specificsDisplay'])) : '';
			$popupDelay       = ! empty($_POST['tmpDelay']) ? sanitize_text_field(wp_unslash($_POST['tmpDelay'])) : 0;
			$popuptrigger     = ! empty($_POST['tmpTrigger']) ? sanitize_text_field(wp_unslash($_POST['tmpTrigger'])) : 'pageloaded';
			$popupEffect     = ! empty($_POST['tmpEffect']) ? sanitize_text_field(wp_unslash($_POST['tmpEffect'])) : 'flip';
			$selector     = ! empty($_POST['tmpSelector']) ? sanitize_text_field(wp_unslash($_POST['tmpSelector'])) : '';
			$scrollPostion     = ! empty($_POST['tmpScrollPostion']) ? sanitize_text_field(wp_unslash($_POST['tmpScrollPostion'])) : 0;
			$headerSmoother     = ! empty($_POST['tmpHeaderSmoother']) ? sanitize_text_field(wp_unslash($_POST['tmpHeaderSmoother'])) : '';
			$headerSmootheroffset     = ! empty($_POST['tmpHeaderSmootherOffsetY']) ? sanitize_text_field(wp_unslash($_POST['tmpHeaderSmootherOffsetY'])) : '';

			$data = array(
				'title'         => $title,
				'id'            => $tmpid,
				'tmptype'       => $tmpType,
				'tmplocation'   => $tmplocation,
				'tmpSpLocation' => $specificsDisplay,
				'tmpDelay'      => $popupDelay,
				'tmpTrigger'    => $popuptrigger,
				'tmpSelector'    => $selector,
				'tmpScrollPostion'    => $scrollPostion,
				'tmpEffect' => $popupEffect,
				'tmpHeaderSmoother' => $headerSmoother,
				'tmpHeaderSmootherOffsetY' => $headerSmootheroffset
			);

			if ($tmpid) {
				$this->update($data);
			} else {
				$this->insert($data);
			}
		} else {
			$errormessage = array(
				'message' => esc_html__('Post request dose not found', 'animation-addons-for-elementor'),
			);
			wp_send_json_error($errormessage);
		}
	}


	/**
	 * Get Template data by id
	 *
	 * @return void
	 */
	public function get_post_By_id()
	{
		if (isset($_POST)) {

			if (! (current_user_can('manage_options') || current_user_can('edit_others_posts'))) {
				$errormessage = array(
					'message' => esc_html__('You are unauthorize to adding template!', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

			if (! wp_verify_nonce( $nonce, Nonce::action_for( $nonce, Nonce::THEME_BUILDER ) )) {
				$errormessage = array(
					'message' => esc_html__('Nonce Varification Failed !', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$tmpid            = ! empty($_POST['tmpId']) ? sanitize_text_field(wp_unslash($_POST['tmpId'])) : '';
			$postdata         = get_post($tmpid);
			$tmpType          = ! empty(get_post_meta($tmpid, Aaeaddon_Theme_Builder::CPT_META . '_type', true)) ? get_post_meta($tmpid, Aaeaddon_Theme_Builder::CPT_META . '_type', true) : 'single';
			$tmpLocation      = ! empty(get_post_meta($tmpid, Aaeaddon_Theme_Builder::CPT_META . '_location', true)) ? get_post_meta($tmpid, Aaeaddon_Theme_Builder::CPT_META . '_location', true) : '';
			$specificsDisplay = ! empty(get_post_meta($tmpid, Aaeaddon_Theme_Builder::CPT_META . '_splocation', true)) ? get_post_meta($tmpid, Aaeaddon_Theme_Builder::CPT_META . '_splocation', true) : '';
			$tmpDelay         = ! empty(get_post_meta($tmpid, 'delayTime', true)) ? get_post_meta($tmpid, 'delayTime', true) : 0;
			$popupTrigger     = ! empty(get_post_meta($tmpid, 'popup_trigger', true)) ? get_post_meta($tmpid, 'popup_trigger', true) : 'pageloaded';
			$popupEffect     = ! empty(get_post_meta($tmpid, 'effect', true)) ? get_post_meta($tmpid, 'effect', true) : 'flip';
			$popup_selector     = ! empty(get_post_meta($tmpid, 'popup_selector', true)) ? get_post_meta($tmpid, 'popup_selector', true) : '';
			$scrollPostion     = ! empty(get_post_meta($tmpid, 'scrollPostion', true)) ? get_post_meta($tmpid, 'scrollPostion', true) : '';
			$aae_header_smoother     = ! empty(get_post_meta($tmpid, 'aae_header_smoother', true)) ? get_post_meta($tmpid, 'aae_header_smoother', true) : '';
			$header_smootheroffsety     = ! empty(get_post_meta($tmpid, 'aae_header_smoother_offsety', true)) ? get_post_meta($tmpid, 'aae_header_smoother_offsety', true) : '';
			$spLocations      = array();

			if (! empty($specificsDisplay)) {

				foreach (json_decode($specificsDisplay) as $item) {

					// If it's an ID
					if (is_numeric($item)) {

						$post = get_post(intval($item));

						$spLocations[$item] = $post ? $post->post_title : '';

					}

					// If it's a slug or string
					elseif (is_string($item) && ! is_numeric($item)) {

						$slug  = sanitize_text_field($item);
						$post  = get_page_by_path($slug, OBJECT);

						if ($post) {
							$spLocations[$item] = $post->post_title; // Real title
						} else {
							// fallback title if page not found
							$spLocations[$item] = ucwords(str_replace('-', ' ', $slug));
						}
					}
				}
			}

			$data = array(
				'tmpTitle'      => $postdata->post_title,
				'tmpType'       => $tmpType,
				'tmpLocation'   => $tmpLocation,
				'tmpSpLocation' => $spLocations,
				'tmpDelay'      => $tmpDelay,
				'tmpTrigger'    => $popupTrigger,
				'tmpSelector' => $popup_selector,
				'tmpEffect' => $popupEffect,
				'tmpScrollPostion' => $scrollPostion,
				'tmpHeaderSmoother' => $aae_header_smoother,
				'tmpHeaderSmootherOffsetY' => $header_smootheroffsety,
			);
			wp_send_json_success($data);
		} else {
			$errormessage = array(
				'message' => esc_html__('Some thing is worng !', 'animation-addons-for-elementor'),
			);
			wp_send_json_error($errormessage);
		}
	}


	/**
	 * Ajax handeler to return the posts based on the search query.
	 * When searching for the post/pages only titles are searched for.
	 *
	 * @since  1.0.0
	 */
	function get_posts_by_query()
	{

		if (isset($_POST)) {

			if (! (current_user_can('manage_options') || current_user_can('edit_others_posts') || current_user_can('edit_posts'))) {
				$errormessage = array(
					'message' => esc_html__('You are unauthorized to perform this action!', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

			if (! wp_verify_nonce( $nonce, Nonce::action_for( $nonce, Nonce::THEME_BUILDER ) )) {
				$errormessage = array(
					'message' => esc_html__('Nonce Verification Failed!', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$search_string = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
			$data          = array();
			$result        = array();

			$args = array(
				'public'   => true,
				'_builtin' => false,
			);

			$output     = 'names'; // names or objects, note names is the default.
			$operator   = 'and'; // also supports 'or'.
			$post_types = get_post_types($args, $output, $operator);

			unset($post_types[Aaeaddon_Theme_Builder::CPTTYPE]); // Exclude wcf post type templates.
			unset($post_types['elementor_library']); // Exclude wcf elementor_library templates.

			$post_types['Posts'] = 'post';
			$post_types['Pages'] = 'page';

			foreach ($post_types as $key => $post_type) {
				$data = array();

				add_filter('posts_search', array($this, 'search_only_titles'), 10, 2);

				$query = new \WP_Query(
					array(
						's'              => $search_string,
						'post_type'      => $post_type,
						'posts_per_page' => -1,
					)
				);

				if ($query->have_posts()) {
					while ($query->have_posts()) {
						$query->the_post();
						$title  = get_the_title();
						$title .= (0 != $query->post->post_parent) ? ' (' . get_the_title($query->post->post_parent) . ')' : '';
						$id     = get_the_id();
						
						$data[] = array(
							'id' => get_post_field('post_name', $id),
							//'id'   => $id,
							'text' => $title,
						);
					}
				}

				if (is_array($data) && ! empty($data)) {
					$result[] = array(
						'text'     => $key,
						'children' => $data,
					);
				}
			}

			$data = array();

			wp_reset_postdata();

			// return the result in json.
			wp_send_json($result);
		} else {
			$errormessage = array(
				'message' => esc_html__('Some thing is worng !', 'animation-addons-for-elementor'),
			);
			wp_send_json_error($errormessage);
		}
	}


	/**
	 * Return search results only by post title.
	 * This is only run from hfe_get_posts_by_query()
	 *
	 * @param  (string)   $search   Search SQL for WHERE clause.
	 * @param  (WP_Query) $wp_query The current WP_Query object.
	 *
	 * @return (string) The Modified Search SQL for WHERE clause.
	 */
	function search_only_titles($search, $wp_query)
	{
		if (! empty($search) && ! empty($wp_query->query_vars['search_terms'])) {
			global $wpdb;

			$q = $wp_query->query_vars;
			$n = ! empty($q['exact']) ? '' : '%';

			$search = array();

			foreach ((array) $q['search_terms'] as $term) {
				$search[] = $wpdb->prepare("$wpdb->posts.post_title LIKE %s", $n . $wpdb->esc_like($term) . $n);
			}

			if (! is_user_logged_in()) {
				$search[] = "$wpdb->posts.post_password = ''";
			}

			$search = ' AND ' . implode(' AND ', $search);
		}

		return $search;
	}


	/**
	 * Template Insert
	 *
	 * @param [array] $data
	 *
	 * @return void
	 */
	public function insert($data)
	{

		$args        = array(
			'post_type'   => Aaeaddon_Theme_Builder::CPTTYPE,
			'post_status' => $data['tmptype'] == 'popup' ? 'draft' : 'publish',
			'post_title'  => $data['title'],
		);
		$new_post_id = wp_insert_post($args);

		if ($new_post_id) {
			$return = array(
				'message' => esc_html__('Template has been inserted', 'animation-addons-for-elementor'),
				'id'      => $new_post_id,
			);

			// Meta data
			update_post_meta($new_post_id, Aaeaddon_Theme_Builder::CPT_META . '_type', $data['tmptype']);
			update_post_meta($new_post_id, Aaeaddon_Theme_Builder::CPT_META . '_location', $data['tmplocation']);
			update_post_meta($new_post_id, '_elementor_edit_mode', 'builder');
			update_post_meta($new_post_id, '_wp_page_template', 'elementor_canvas');

			// specific page and post template header footer
			if ('header' === $data['tmptype'] || 'footer' === $data['tmptype']) {
				update_post_meta($new_post_id, Aaeaddon_Theme_Builder::CPT_META . '_splocation', $data['tmpSpLocation']);
				update_post_meta($new_post_id, 'aae_header_smoother', $data['tmpHeaderSmoother']);
				update_post_meta($new_post_id, 'aae_header_smoother_offsety', $data['tmpHeaderSmootherOffsetY']);

			}

			if ('archive' === $data['tmptype'] && 'specifics_cat' === $data['tmplocation']) {
				update_post_meta($new_post_id, Aaeaddon_Theme_Builder::CPT_META . '_splocation', $data['tmpSpLocation']);
			}

			if ('post-singular' === $data['tmplocation'] && 'single' === $data['tmptype']) {
				update_post_meta($new_post_id, Aaeaddon_Theme_Builder::CPT_META . '_splocation', $data['tmpSpLocation']);
			}

			if ('popup' === $data['tmptype']) {
				update_post_meta($new_post_id, 'delayTime', $data['tmpDelay']);
				update_post_meta($new_post_id, 'popup_trigger', $data['tmpTrigger']);
				update_post_meta($new_post_id, 'popup_selector', $data['tmpEffect']);
				update_post_meta($new_post_id, 'effect', $data['tmpEffect']);
				update_post_meta($new_post_id, 'scrollPostion', $data['tmpScrollPostion']);

				update_post_meta($new_post_id, Aaeaddon_Theme_Builder::CPT_META . '_splocation', $data['tmpSpLocation']);
			}

			wp_send_json_success($return);
		} else {
			$errormessage = array(
				'message' => esc_html__('Some thing is worng !', 'animation-addons-for-elementor'),
			);
			wp_send_json_error($errormessage);
		}
	}


	/**
	 * Template Update
	 *
	 * @param [array] $data
	 *
	 * @return void
	 */
	public function update($data)
	{

		$update_post_args = array(
			'ID'         => $data['id'],
			'post_title' => $data['title'],
		);
		wp_update_post($update_post_args);

		// Update Meta data
		update_post_meta($data['id'], Aaeaddon_Theme_Builder::CPT_META . '_type', $data['tmptype']);
		update_post_meta($data['id'], Aaeaddon_Theme_Builder::CPT_META . '_location', $data['tmplocation']);

		// specific page and post template header footer
		if ('header' === $data['tmptype'] || 'footer' === $data['tmptype']) {
			update_post_meta($data['id'], Aaeaddon_Theme_Builder::CPT_META . '_splocation', $data['tmpSpLocation']);
			update_post_meta($data['id'], 'aae_header_smoother', $data['tmpHeaderSmoother']);
			update_post_meta($data['id'], 'aae_header_smoother_offsety', $data['tmpHeaderSmootherOffsetY']);
		} else {
			delete_post_meta($data['id'], Aaeaddon_Theme_Builder::CPT_META . '_splocation');
		}

		if ('archive' === $data['tmptype'] && 'specifics_cat' === $data['tmplocation']) {
			update_post_meta($data['id'], Aaeaddon_Theme_Builder::CPT_META . '_splocation', $data['tmpSpLocation']);
		}

		if ('post-singular' === $data['tmplocation'] && 'single' === $data['tmptype']) {
			update_post_meta($data['id'], Aaeaddon_Theme_Builder::CPT_META . '_splocation', $data['tmpSpLocation']);
		}

		if ('popup' === $data['tmptype']) {
			update_post_meta($data['id'], 'delayTime', $data['tmpDelay']);
			update_post_meta($data['id'], 'popup_trigger', $data['tmpTrigger']);
			update_post_meta($data['id'], 'popup_selector', $data['tmpSelector']);
			update_post_meta($data['id'], 'effect', $data['tmpEffect']);
			update_post_meta($data['id'], 'scrollPostion', $data['tmpScrollPostion']);

			update_post_meta($data['id'], Aaeaddon_Theme_Builder::CPT_META . '_splocation', $data['tmpSpLocation']);
		}

		$return = array(
			'message' => esc_html__('Template has been updated', 'animation-addons-for-elementor'),
		);
		wp_send_json_success($return);
	}


	/**
	 * Manage Scripts
	 *
	 * @param [string] $hook
	 *
	 * @return void
	 */
	public function enqueue_scripts($hook)
	{

		// Read-only admin screen check; no nonce applies.
		if (isset($_GET['post_type']) && Aaeaddon_Theme_Builder::CPTTYPE === sanitize_key(wp_unslash($_GET['post_type']))) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// CSS
			wp_enqueue_style('select2', AAEADDON_URL . '/assets/css/select2.min.css', array(), AAEADDON_VERSION);
			wp_enqueue_style('wcf-theme-builder', AAEADDON_URL . '/assets/css/theme-builder.min.css', array(), AAEADDON_VERSION);

			// JS
			wp_enqueue_script('select2', AAEADDON_URL . 'assets/js/select2.min.js', array('jquery'), AAEADDON_VERSION, true);
			wp_enqueue_script(
				'wcf-theme-builder',
				AAEADDON_URL . 'assets/js/theme-builder.js',
				array(
					'jquery',
					'wp-util',
				),
				AAEADDON_VERSION,
				true
			);

			$localize_data = array(
				'ajaxurl'         => admin_url('admin-ajax.php'),
				'nonce'           => Nonce::create( Nonce::THEME_BUILDER ),
				'adminURL'        => admin_url(),
				'hflocation'      => Aaeaddon_Theme_Builder::get_hf_location_selections(),
				'archivelocation' => Aaeaddon_Theme_Builder::get_archive_location_selections(),
				'singlelocation'  => Aaeaddon_Theme_Builder::get_single_location_selections(),
				'postcategory'    => Aaeaddon_Theme_Builder::get_category_location_selections(),
				'templatetype'    => Aaeaddon_Theme_Builder::get_offered_template_type(),
				'labels'          => array(
					'fields'  => array(
						'name'     => array(
							'title'       => esc_html__('Name', 'animation-addons-for-elementor'),
							'placeholder' => esc_html__('Enter a template name', 'animation-addons-for-elementor'),
						),
						'type'     => esc_html__('Type', 'animation-addons-for-elementor'),
						'display'  => esc_html__('Display', 'animation-addons-for-elementor'),
						'category' => esc_html__('Category', 'animation-addons-for-elementor'),
						'delay'    => esc_html__('Delay', 'animation-addons-for-elementor'),
						'trigger'  => esc_html__('Trigger', 'animation-addons-for-elementor'),
						'selector' => esc_html__('Selector', 'animation-addons-for-elementor'),
					),
					'head'    => esc_html__('Template Settings', 'animation-addons-for-elementor'),
					'buttons' => array(
						'elementor' => array(
							'label' => esc_html__('Edit With Elementor', 'animation-addons-for-elementor'),
							'link'  => '#',
						),
						'save'      => array(
							'label'  => esc_html__('Save Settings', 'animation-addons-for-elementor'),
							'saving' => esc_html__('Saving...', 'animation-addons-for-elementor'),
							'saved'  => esc_html__('All Data Saved', 'animation-addons-for-elementor'),
							'link'   => '#',
						),
					),
				),
			);
			wp_localize_script('wcf-theme-builder', 'Aaeaddon_Theme_Builder', $localize_data);
		}
	}

	/**
	 * [admin_menu] Add Post type Submenu
	 *
	 * @return void
	 */
	public function admin_menu()
	{
		$link_custom_post = 'edit.php?post_type=' . Aaeaddon_Theme_Builder::CPTTYPE;
		add_submenu_page(
			'aaeaddon_page',
			esc_html__('Theme Builder', 'animation-addons-for-elementor'),
			esc_html__('Theme Builder', 'animation-addons-for-elementor'),
			'manage_options',
			$link_custom_post,
			null
		);
	}
}
