<?php
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\Admin\Base;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

class AAEAddon_Importer {

	public $file_path = 'aaeaddon_tpl_file.xml';
	public $full_path = null;
	public $wishlist_key = 'aaeaddon_user_wishlists';
	/**
	 * [$_instance]
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 * @return [_Admin_Init]
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
	public function __construct() {
		
		add_action( 'wp_ajax_aaeaddon_template_installer', [ $this, 'template_installer' ] );
		add_action( 'wp_ajax_aaeaddon_heartbeat_data', [ $this, 'heartbeat_data' ] );  
		add_action( 'wp_ajax_aaeaddon_wishlist_option', [ $this, 'wishlist' ] ); 		
	
		add_filter('wcf_addons_dashboard_config', [ $this, 'include_user_wishlist']);		
	}	

	public function include_user_wishlist($config) {
		$user_id = get_current_user_id();    
    	// Fetch existing wishlist data
    	$config['wishlist'] = get_user_meta($user_id, $this->wishlist_key, true);
		return $config;
	}

	public function heartbeat_data(){
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		// Nonce alone only proves the request came from this site; this
		// reports import progress, so it needs an authority check too.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You are not allowed to do this action.', 'animation-addons-for-elementor' ) ], 403 );
		}
        $return_data = apply_filters('aaeaddon_heartbeat_data', [
			'import_state' => get_option('aaeaddon_template_import_state'),
			'import_porgress' => get_option('aaeaddon_template_import_progress')
		]);        
		wp_send_json($return_data);		
	}

	public function wishlist() {
    	check_ajax_referer('wcf_admin_nonce', 'nonce');

    	if (!current_user_can('install_plugins')) {
        	wp_send_json_error(__('You are not allowed to perform this action.', 'animation-addons-for-elementor'));
    	}

    	if (!isset($_POST['wishlist'])) {
        	wp_send_json_error(__('Provide wishlist data.', 'animation-addons-for-elementor'));
    	}

		$wishlist = sanitize_text_field(wp_unslash($_POST['wishlist'])); // Sanitize input
		$user_id = get_current_user_id();
		
		// Fetch existing wishlist data
		$wishlist_db = get_user_meta($user_id, $this->wishlist_key, true);
		
		// Ensure it's an array
		$wishlist_db = is_array($wishlist_db) ? $wishlist_db : [];

		if (in_array($wishlist, $wishlist_db, true)) {
			// Remove if exists
			$wishlist_db = array_values(array_filter($wishlist_db, fn($v) => $v !== $wishlist));
		} else {
			// Add new item
			$wishlist_db[] = $wishlist;
		}

		// Update user meta with modified wishlist
		update_user_meta($user_id, $this->wishlist_key, $wishlist_db);

		wp_send_json_success($wishlist_db);
	}


	public function template_installer(){
  
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}
		$progress = '25';	
		$msg = '';			
		$template_data = [];
		$theme_slug = $user_plugins = null;

		// No theme slug is accepted from the request any more. This plugin does
		// not install or switch themes, so there is nothing for the browser to
		// ask for; the recommended theme is read out of the template's own
		// dependency list further down, purely to name it in a notice.
		if (isset($_POST['user_plugins'])) {
			$user_plugins = sanitize_text_field(wp_unslash($_POST['user_plugins'])); // Remove slashes if added by WP	
			$user_plugins = explode(',',$user_plugins);	
		}
		
		if (isset($_POST['template_data'])) {
			$json_data = sanitize_text_field( wp_unslash($_POST['template_data']) ); // Remove slashes if added by WP		
			$template_data = json_decode($json_data, true);		
		
			if (json_last_error() === JSON_ERROR_NONE) {			
				array_walk_recursive($template_data, function (&$value) {
					if (is_string($value)) {
						$value = sanitize_text_field($value);
					}
				});			
			}

			if(isset($template_data['next_step']) && $template_data['next_step'] == 'plugins-importer' ){
				// Install required plugin
			    // Include the necessary plugin.php file
				$progress                   = '20';	
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				do_action('aaeaddon/starter-template/import/before/wp_options'); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Established plugin hook (slash-namespaced); kept for backward compatibility.
				if(is_array($user_plugins) && $user_plugins){
					if(isset($template_data['dependencies']['plugins']) && is_array($template_data['dependencies']['plugins'])){	
							if ( current_user_can( 'install_plugins' ) ) {				
								foreach($template_data['dependencies']['plugins'] as $item){

									// Only touch the plugins the user ticked on the import
									// screen. Anything else -- including a dependency that
									// happens to be installed already -- is left alone.
									if ( empty( $item['slug'] ) || ! in_array( $item['slug'], $user_plugins, true ) ) {
										continue;
									}

									/**
									 * Extension point for one dependency the user ticked on the
									 * import screen.
									 *
									 * This plugin ships no installer and activates nothing here;
									 * it only announces the user's selection. Our commercial
									 * add-on, which is not distributed on WordPress.org, is what
									 * attaches. With nothing attached the dependency is skipped
									 * and the import continues.
									 */
									if ( has_action( 'aae/starter_template/install_plugin' ) ) {

										update_option(
											'aaeaddon_template_import_state',
											/* translators: %s: name of the plugin being installed. */
											sprintf( esc_html__( 'Installing %s', 'animation-addons-for-elementor' ), $item['name'] )
										);

										do_action( 'aae/starter_template/install_plugin', $item, $user_plugins );
									}
								}
							}

							update_option(
								'aaeaddon_template_import_state',
								has_action( 'aae/starter_template/install_plugin' )
									? esc_html__( 'Plugin Installation Done', 'animation-addons-for-elementor' )
									: esc_html__( 'Required plugins were skipped -- install them manually.', 'animation-addons-for-elementor' )
							);
					}
				}
				$template_data['next_step'] = 'install-wp-options';					
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'check-template-status'){					
				$tpl = $this->validate_download_file($template_data);				
				if($tpl){
					update_option('aaeaddon_template_import_state', esc_html__( 'Content file Downloading' , 'animation-addons-for-elementor' ) );
					$template_data['next_step'] = 'download-xml-file';
					$template_data['file']      = json_decode($tpl);
					
				}else{
					update_option('aaeaddon_template_import_state', esc_html__( 'Invalid file', 'animation-addons-for-elementor'));
					$template_data['next_step'] = 'fail';
				}
				$progress                    = '37';
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'download-xml-file'){	
								
				if(isset($template_data['file']['content_url'])){						
					update_option('aaeaddon_template_import_state', esc_html__('Content installing', 'animation-addons-for-elementor'));
					$template_data['next_step']  = 'install-template';
					$template_data['local_path'] = $this->full_path;	
							
				}else{
					$template_data['next_step'] = 'fail';
					update_option('aaeaddon_template_import_state', esc_html__('Missing Content file, contact author', 'animation-addons-for-elementor'));
				}
				$progress                    = '40';			
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-template'){
				$template_data['next_step'] = 'check-theme';
				$progress                   = '50';
				$msg                        = esc_html__('Varifying Content Import', 'animation-addons-for-elementor');
				update_option('aaeaddon_template_import_state', 'Checking Theme');
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'check-theme'){
				/*
				 * A starter template records the theme it was designed against.
				 * This plugin does not install that theme and never changes the
				 * site's active theme: switching themes is the user's decision,
				 * taken in Appearance > Themes. The step only reports the
				 * recommendation so the import can carry on.
				 */
				$template_data['next_step'] = 'install-elementor-settings';
				$progress                   = '75';
				if ( isset( $template_data['dependencies']['themes'][0]['slug'] ) ) {
					$theme_slug = sanitize_key( $template_data['dependencies']['themes'][0]['slug'] );
				}
				if ( ! empty( $theme_slug ) ) {
					$msg = sprintf(
						/* translators: %s: slug of the theme this starter template was designed for. */
						esc_html__( 'This template was designed for the "%s" theme. Your active theme has not been changed -- install and activate it yourself from Appearance > Themes.', 'animation-addons-for-elementor' ),
						$theme_slug
					);
					update_option( 'aaeaddon_template_import_state', $msg );
				}
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-elementor-settings'){
				update_option( 'wcf_addons_setup_wizard', 'complete' );
				$template_data['next_step'] = 'done';
				$progress                   = '100';		
				// The template server labels EVERY elementor_settings file "json" --
				// its REST layer hardcodes the type for whatever file is attached to
				// the entry. A V4 demo attaches a kit ZIP there, so trusting the label
				// fed a zip to installElementorKit(), which json_decode()d it to null,
				// reported "Kit Settings Update", and never imported a single global
				// class or variable -- every imported page then rendered unstyled.
				// The file extension is the fact; the label is a guess.
				$kit_type = isset( $template_data['elementor_settings']['type'] ) ? (string) $template_data['elementor_settings']['type'] : '';
				if ( isset( $template_data['elementor_settings']['content_url'] ) ) {
					$kit_ext = strtolower( (string) pathinfo( (string) wp_parse_url( $template_data['elementor_settings']['content_url'], PHP_URL_PATH ), PATHINFO_EXTENSION ) );
					if ( 'zip' === $kit_ext ) {
						$kit_type = 'kit-zip';
					} elseif ( 'json' === $kit_ext ) {
						$kit_type = 'json';
					}
				}
				if ( isset( $template_data['elementor_settings']['content_url'] ) && 'json' === $kit_type ) {
					$response = wp_remote_get( $template_data['elementor_settings']['content_url']);
					if ( is_array( $response ) && ! is_wp_error( $response ) ) {
						$json_data = wp_remote_retrieve_body( $response );
						$msg = $this->installElementorKit($json_data);
						update_option('aaeaddon_template_import_state', $msg);
					}
				}
				if ( isset( $template_data['elementor_settings']['content_url'] ) && 'kit-zip' === $kit_type ) {
					// Elementor V4 demo: global classes + variables (and, on a first
					// import, site settings) from the kit zip. See Atomic_Kit_Import.
					$kit = \WCF_ADDONS\Admin\Base\Atomic_Kit_Import::import_from_url(
						$template_data['elementor_settings']['content_url'],
						! empty( $template_data['aae_site_has_atomic'] )
					);
					$msg = is_wp_error( $kit )
						? $kit->get_error_message()
						: sprintf(
							/* translators: 1: import mode, 2: classes, 3: variables, 4: posts */
							esc_html__( 'Design system imported (%1$s): %2$d classes, %3$d variables, %4$d posts updated', 'animation-addons-for-elementor' ),
							$kit['mode'], $kit['classes'], $kit['variables'], $kit['posts']
						);
					update_option('aaeaddon_template_import_state', $msg);
				}
				// A V4 starter TEMPLATE is a whole site: once it has landed, V3 goes
				// off everywhere it is switched on (widgets, extensions, the Kit's
				// preloader/cursor/to-top/indicator, v3 popup templates, the v3
				// Site Settings tabs). Never for a starter PAGE, which drops into
				// an existing site. See Atomic_V3_Switch_Off for the one
				// exception — widgets that pre-existing v3 pages still use.
				$import_type_now = isset( $_POST['import_type'] ) ? sanitize_text_field( wp_unslash( $_POST['import_type'] ) ) : 'full-demo'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- check_ajax_referer() ran at the top of this handler.
				$is_v4_template  = isset( $template_data['builder_version'] ) && 'v4' === (string) $template_data['builder_version']
					&& 'page' !== $import_type_now
					&& empty( $template_data['elementor_template'] );
				if ( $is_v4_template && class_exists( '\WCF_ADDONS\Admin\Base\Atomic_V3_Switch_Off' ) ) {
					$v3 = \WCF_ADDONS\Admin\Base\Atomic_V3_Switch_Off::run( ! empty( $template_data['aae_site_had_v3'] ) );
					$msg .= ( '' !== $msg ? ' — ' : '' ) . \WCF_ADDONS\Admin\Base\Atomic_V3_Switch_Off::describe( $v3 );
					update_option( 'aaeaddon_template_import_state', $msg );
				}
				if ( isset( $template_data['elementor_template']['content_url'] ) && $template_data['elementor_template']['type'] === 'template-json' ) {
					// Elementor V4 starter PAGE: only the classes/variables this page
					// uses, from its template export. keep_create unless the user
					// picked "match my site's design". See Atomic_Kit_Import.
					$page_mode = ( isset( $template_data['aae_page_mode'] ) && 'match_site' === $template_data['aae_page_mode'] ) ? 'match_site' : 'keep_create';
					$tpl = \WCF_ADDONS\Admin\Base\Atomic_Kit_Import::import_template_json_from_url(
						$template_data['elementor_template']['content_url'],
						$page_mode
					);
					$msg = is_wp_error( $tpl )
						? $tpl->get_error_message()
						: sprintf(
							/* translators: 1: import mode, 2: classes, 3: variables, 4: posts */
							esc_html__( 'Page design imported (%1$s): %2$d classes, %3$d variables, %4$d posts updated', 'animation-addons-for-elementor' ),
							$tpl['mode'], $tpl['classes'], $tpl['variables'], $tpl['posts']
						);
					update_option('aaeaddon_template_import_state', $msg);
				}
				$this->update_blog_and_homepage_options($template_data);
				// V4 only, and only when the user ticked it in the import dialog:
				// copy url-shaped atomic images into the media library. Its own
				// repeating step, because it is one download per image against
				// a remote host and does not fit in one request. See
				// Atomic_Image_Localize.
				$wants_images = ! empty( $template_data['aae_localize_images'] )
					&& isset( $template_data['builder_version'] ) && 'v4' === (string) $template_data['builder_version']
					&& class_exists( '\WCF_ADDONS\Admin\Base\Atomic_Image_Localize' );
				if ( $wants_images ) {
					// Keep this step's summary (design system, V3 switch-off): the
					// image step appends its own to it when it finishes.
					$template_data['aae_import_summary'] = $msg;
					$template_data['next_step']          = 'localize-images';
					$progress                            = '95';
					$msg                                 = esc_html__( 'Copying images to the media library', 'animation-addons-for-elementor' );
					update_option( 'aaeaddon_template_import_state', $msg );
				}
				do_action('aaeaddon/starter-template/import/step/metasettings'); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Established plugin hook (slash-namespaced); kept for backward compatibility.
				
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'localize-images'){
				// Repeats until done: each request does what fits in its time
				// budget and the client re-posts the same step. Progress climbs
				// 95 -> 99 with the image count so the bar never looks stuck.
				$images = \WCF_ADDONS\Admin\Base\Atomic_Image_Localize::run_batch();
				if ( $images['done'] ) {
					$template_data['next_step'] = 'done';
					$progress                   = '100';
					$summary                    = isset( $template_data['aae_import_summary'] ) ? (string) $template_data['aae_import_summary'] : '';
					$msg                        = ( '' !== $summary ? $summary . ' — ' : '' ) . \WCF_ADDONS\Admin\Base\Atomic_Image_Localize::describe( $images );
				} else {
					$template_data['next_step'] = 'localize-images';
					$fraction                   = $images['total'] > 0 ? min( 1, $images['processed'] / $images['total'] ) : 0;
					$progress                   = (string) ( 95 + (int) floor( 4 * $fraction ) );
					$msg                        = \WCF_ADDONS\Admin\Base\Atomic_Image_Localize::progress_message( $images );
				}
				update_option( 'aaeaddon_template_import_state', $msg );
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'install-wp-options'){

				$template_data['next_step'] = 'check-template-status';
				$progress                   = '30';
				$msg                        =  esc_html__('Downloading Template', 'animation-addons-for-elementor');

				if(isset($template_data['wp_options']) && is_array($template_data['wp_options'])){
					$this->install_options($template_data['wp_options']);
				}

				$import_type = isset($_POST['import_type']) ? sanitize_text_field(wp_unslash($_POST['import_type'])) : 'full-demo'; // Remove slashes if added by WP
				
				if( $import_type !='page' ){
					do_action('aaeaddon/starter-template/import/step/wp_options'); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Established plugin hook (slash-namespaced); kept for backward compatibility.
				}	
								
				update_option('aaeaddon_template_import_state', $msg);			
			}elseif(isset($template_data['next_step']) && $template_data['next_step'] == 'fail'){
				$msg = esc_html__('Template Demo Import fail', 'animation-addons-for-elementor');
			}else{
				$template_data['next_step'] = 'plugins-importer';	
				// Snapshot NOW whether this site already holds V4 content. Asked at
				// the kit step it would always be true -- this import's own pages
				// are in the DB by then. $template_data round-trips every step.
				$template_data['aae_site_has_atomic'] = ( class_exists( '\WCF_ADDONS\AtomicWidgets\Atomic' ) && \WCF_ADDONS\AtomicWidgets\Atomic::has_atomic_usage() ) ? 1 : 0;
				// And whether it holds V3 content, for the same reason: a V4
				// template import ends by switching V3 off, and the widgets
				// that pre-existing v3 pages use must survive that. Asked at the
				// end it would answer for the demo's pages too.
				$template_data['aae_site_had_v3'] = ( class_exists( '\WCF_ADDONS\Admin\Base\Atomic_V3_Switch_Off' ) && \WCF_ADDONS\Admin\Base\Atomic_V3_Switch_Off::site_has_v3_content() ) ? 1 : 0;
				$progress                   = '10';	
			
				update_option('aaeaddon_template_import_state', esc_html__('Checking Setup requirement', 'animation-addons-for-elementor'));
			}			
			
		}
	    
		wp_send_json( ['template' => wp_unslash( $template_data ),'msg' => $msg, 'progress' => $progress] );
	}

	public function update_blog_and_homepage_options($template_data){
	
		 if(isset($template_data['home_page']) && $template_data['home_page'] !=''){
			// Get the front page.
			$front_page = get_posts(
				[
				'post_type'              => 'page',
				'title'                  => $template_data['home_page'],
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				]
			);
			
			if ( ! empty( $front_page ) ) {		
				update_option( 'page_on_front', $front_page[0]->ID );
			}
		 }

		 if(isset($template_data['blog_page']) && $template_data['blog_page'] !=''){
			// Get the blog page.
			$blog_page = get_posts(
				[
				'post_type'              => 'page',
				'title'                  => $template_data['blog_page'],
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				]
			);
			
			if ( ! empty( $blog_page ) ) {
				update_option( 'page_for_posts', $blog_page[0]->ID );
			}
			
			if ( ! empty( $blog_page ) || ! empty( $front_page ) ) {
				update_option( 'show_on_front', 'page' );
			}
			
		}
	}

	public function install_options( $settings ) {
		global $wpdb;
		// clean cache
		delete_option('aae_cpts_032153');
		delete_option('aae_taxs_933153');
		foreach ( $settings as $item ) {
			$response = wp_remote_get( $item['xml_file'] );
	
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				$xml_data = wp_remote_retrieve_body( $response );
				$xml      = simplexml_load_string( $xml_data );
				if ( ! $xml ) {
					continue; // Skip if XML parsing fails.
				}
	
				if ( isset( $xml->option ) ) {
					foreach ( $xml->option as $opt ) {
						$option_name     = sanitize_text_field( (string) $opt->name );
						$serialized_data = sanitize_text_field( (string) $opt->value );
						// One-time import write to the options table; object caching does not apply.
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
						$wpdb->update(
							$wpdb->options,
							array( 'option_value' => $serialized_data ),
							array( 'option_name'  => $option_name )
						);
						do_action('aae/addons/options/import',$option_name, $serialized_data); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Established plugin hook using slash-namespaced form; kept for backward compatibility.
					}
				} else {
					$option_name     = sanitize_text_field( (string) $xml->name );
					$serialized_data = sanitize_text_field( (string) $xml->value );

					// One-time import write to the options table; object caching does not apply.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->update(
						$wpdb->options,
						array( 'option_value' => $serialized_data ),
						array( 'option_name'  => $option_name )
					);
					do_action('aae/addons/options/import',$option_name, $serialized_data); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Established plugin hook using slash-namespaced form; kept for backward compatibility.
				}
			}
		}
	}
	
	
	public function installElementorKit($elementor){
		$activeKitId = get_option( 'elementor_active_kit' );
		$kit_data    = json_decode( $elementor, true );
		if ( $activeKitId ) {
			$postMeta = get_post_meta( $activeKitId, '_elementor_page_settings', true );
			// Ensure $postMeta is an array
			$newPostMeta = is_array( $postMeta ) ? $postMeta : [];
			// Add or override custom colors
			if ( $kit_data && isset( $kit_data['settings'] ) ) {
				$newPostMeta = $kit_data['settings'];
				update_post_meta( $activeKitId, '_elementor_page_settings', $newPostMeta );
			}
			return esc_html__('Kit Settings Update', 'animation-addons-for-elementor');
		}
	}	

	function validate_download_file($template) {
	
		if (empty($template)) {
			update_option('aaeaddon_template_import_state', esc_html__('Template Required', 'animation-addons-for-elementor'));
			return false;
		}
		
	    $remote_url = WCF_TEMPLATE_STARTER_BASE_URL . 'wp-json/starter-templates/download';	
		
		if(isset($template['base_path']) && $template['base_path'] !=''){
			$remote_url = $template['base_path'] . 'wp-json/starter-templates/download';
		}

		$args = [
			'timeout'   => 90,
			'body' => [
				'template' => $template
			],
			'sslverify' => false // Disable SSL verification
		];	
	    
		// Fetch the remote file with POST request
		$response = wp_remote_get($remote_url, apply_filters('aaeaddon/starter_templates/download_args',$args)); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Established plugin hook (slash-namespaced); kept for backward compatibility.
		
		if (is_wp_error($response)) {
			update_option('aaeaddon_template_import_state', esc_html__('Failed to validate file from remote URL.', 'animation-addons-for-elementor'));
			return false;
		}
		
		$response_code = wp_remote_retrieve_response_code($response);		
		
		if ($response_code !== 200) {
			update_option('aaeaddon_template_import_state', esc_html__('Invalid file arguments. Please check the URL.', 'animation-addons-for-elementor'));
			return false;
		}
	     
		$body = wp_remote_retrieve_body($response);
		
		if (empty($body)) {
			update_option('aaeaddon_template_import_state', esc_html__('The downloadable file is empty.', 'animation-addons-for-elementor'));
			return false;
		}

		return $body;
	}
	
	
	function download_remote_wp_xml_file($remote_url) {
		
		if (empty($remote_url)) {
			
			return esc_html__('Remote URL is required.', 'animation-addons-for-elementor');
		}
	
		// Fetch the remote file
		$response = wp_safe_remote_get($remote_url);
	
		if (is_wp_error($response)) {
			return esc_html__('Failed to fetch XML from remote URL.', 'animation-addons-for-elementor');
		}
	
		$body = wp_remote_retrieve_body($response);
	
		if (empty($body)) {
			update_option('aaeaddon_template_import_state', esc_html__('The remote XML file is empty.', 'animation-addons-for-elementor'));
			return esc_html__('The remote XML file is empty.', 'animation-addons-for-elementor');
		}
	
		// Initialize the WordPress filesystem
		if (!function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
	
		global $wp_filesystem;
		WP_Filesystem();
	
		// Define file path in the uploads directory
		$upload_dir = wp_upload_dir();
		$this->full_path = trailingslashit($upload_dir['path']) . $this->file_path;
		
		// Write the file using the filesystem API
		if (!$wp_filesystem->put_contents($this->full_path, $body, FS_CHMOD_FILE)) {
			update_option('aaeaddon_template_import_state', esc_html__('Failed to save the XML file.', 'animation-addons-for-elementor'));
			return esc_html__('Failed to save the XML file.', 'animation-addons-for-elementor');
		}
		update_option('aaeaddon_template_import_state', esc_html__('File downloaded and saved successfully', 'animation-addons-for-elementor'));
		return esc_html__('File downloaded and saved successfully at ', 'animation-addons-for-elementor') . $this->full_path;
	}
	
	
}

AAEAddon_Importer::instance();