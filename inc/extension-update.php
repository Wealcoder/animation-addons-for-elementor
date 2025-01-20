<?php
namespace WCF_ADDONS;

/* Extension for Elementor */
Class WCfAddon_Extension_Free{
    public $cache_allowed;
    public $cache_key;
    public $plugin_slug;
    public $plugin_path;
    public $version;
    /**
	 * Constructor
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function __construct() {
	
        $this->plugin_slug   = 'extension-for-animation-addons/extension-for-animation-addons.php';
		$this->plugin_path   = 'extension-for-animation-addons/extension-for-animation-addons.php';
		
		$this->version       = 1.2;
		$this->cache_key     = 'wcf_addon_free_ma_upd';
		$this->cache_allowed = true;		
		if(defined('WCF_ADDONS_EX_VERSION')){
			$this->version = WCF_ADDONS_EX_VERSION; 
	    }
        add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
        add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
        add_action( 'upgrader_process_complete', [ $this, 'purge' ], 10, 2 );	
	}
    public function request(){	
    
		$remote = get_transient( $this->cache_key );	
		
		if( false === $remote || ! $this->cache_allowed ) {
			$remote = wp_remote_get(
				'https://themecrowdy.com/wp-json/wcf-plugin/update/info?slug=extension-for-animation-addons',
				array(
					'timeout' => 60,					
					'headers' => array(
						'Accept' => 'application/json'
					)
				)
			);
			if(
				is_wp_error( $remote )
				|| 200 !== wp_remote_retrieve_response_code( $remote )
				|| empty( wp_remote_retrieve_body( $remote ) )
			) {
				return false;
			}

			set_transient( $this->cache_key, $remote, 12 * HOUR_IN_SECONDS );
		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );  
	   
		return $remote;

	}

	function info( $res, $action, $args ) {
      
		// do nothing if you're not getting plugin information right now
		if( 'plugin_information' !== $action ) {
			return $res;
		}
       
		// do nothing if it is not our plugin
		if( $this->plugin_slug !== $args->slug ) {
			return $res;
		}

		// get updates
		$remote = $this->request();
	   
		if( ! $remote ) {
			return $res;
		}
		
		$res = new \stdClass();

		$res->name           = $remote->name;
		$res->slug           = $remote->slug;
		$res->version        = $remote->version;
		$res->tested         = $remote->tested;
		$res->requires       = $remote->requires;
		$res->author         = $remote->author;
		$res->author_profile = $remote->author_profile;
		$res->download_link  = $remote->download_url;
		$res->trunk          = $remote->download_url;
		$res->requires_php   = $remote->requires_php;
		$res->last_updated   = $remote->last_updated;

		$res->sections = array(
			'description' => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog' => $remote->sections->changelog
		);

		if( ! empty( $remote->banners ) ) {
			$res->banners = array(
				'low' => $remote->banners->low,
				'high' => $remote->banners->high
			);
		}

		return $res;

	}

	public function update( $transient ) {
			
	  
		if ( empty($transient->checked ) ) {
			return $transient;
		}
		
		$remote = $this->request();
       
		if(
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
			&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
			&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
		) {
			
			$res                                 = new \stdClass();
			$res->slug                           = $this->plugin_slug;
			$res->plugin                         = $this->plugin_path;  // -update-plugin/-update-plugin.php
			$res->new_version                    = $remote->version;
			$res->tested                         = $remote->tested;
			$res->package                        = $remote->download_url;
			$transient->response[ $res->plugin ] = $res;
          
		}
		
		return $transient;

	}

	public function purge( $upgrader, $options ){

		if (
			$this->cache_allowed
			&& 'update' === $options['action']
			&& 'plugin' === $options[ 'type' ]
		) {
			// just clean the cache when new plugin version is installed
			delete_transient( $this->cache_key );
		}

	}
}

Class WCfAddon_Extension_Pro{
    public $cache_allowed;
    public $cache_key;
    public $plugin_slug;
    public $plugin_path;
    public $version;
    /**
	 * Constructor
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function __construct() {
	
        $this->plugin_slug   = 'animation-addon-for-elementor-pro/animation-addon-for-elementor-pro.php';
		$this->plugin_path   = 'animation-addon-for-elementor-proanimation-addon-for-elementor-pro.php';
		
		$this->version       = 1.2;
		$this->cache_key     = 'wcf_addon_pro_ma_upd';
		$this->cache_allowed = true;		
	    
	    if(defined('WCF_ADDONS_PRO_VERSION')){
			$this->version = WCF_ADDONS_PRO_VERSION; 
	    }
        
        add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
        add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
        add_action( 'upgrader_process_complete', [ $this, 'purge' ], 10, 2 );	
	}
    public function request(){	
    
		$remote = get_transient( $this->cache_key );	
		
		if( false === $remote || ! $this->cache_allowed ) {
			$remote = wp_remote_get(
				'https://themecrowdy.com/wp-json/wcf-plugin/update/info?slug=wcf-addons-pro',
				array(
					'timeout' => 60,					
					'headers' => array(
						'Accept' => 'application/json'
					)
				)
			);
			if(
				is_wp_error( $remote )
				|| 200 !== wp_remote_retrieve_response_code( $remote )
				|| empty( wp_remote_retrieve_body( $remote ) )
			) {
				return false;
			}

			set_transient( $this->cache_key, $remote, 12 * HOUR_IN_SECONDS );
		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );  
	   
		return $remote;

	}

	function info( $res, $action, $args ) {
      
		// do nothing if you're not getting plugin information right now
		if( 'plugin_information' !== $action ) {
			return $res;
		}
       
		// do nothing if it is not our plugin
		if( $this->plugin_slug !== $args->slug ) {
			return $res;
		}

		// get updates
		$remote = $this->request();
	   
		if( ! $remote ) {
			return $res;
		}
		
		$res = new \stdClass();

		$res->name           = $remote->name;
		$res->slug           = $remote->slug;
		$res->version        = $remote->version;
		$res->tested         = $remote->tested;
		$res->requires       = $remote->requires;
		$res->author         = $remote->author;
		$res->author_profile = $remote->author_profile;
		$res->download_link  = $remote->download_url;
		$res->trunk          = $remote->download_url;
		$res->requires_php   = $remote->requires_php;
		$res->last_updated   = $remote->last_updated;

		$res->sections = array(
			'description' => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog' => $remote->sections->changelog
		);

		if( ! empty( $remote->banners ) ) {
			$res->banners = array(
				'low' => $remote->banners->low,
				'high' => $remote->banners->high
			);
		}

		return $res;

	}

	public function update( $transient ) {
			
	  
		if ( empty($transient->checked ) ) {
			return $transient;
		}
		
		$remote = $this->request();
       
		if(
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
			&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
			&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
		) {
			
			$res                                 = new \stdClass();
			$res->slug                           = $this->plugin_slug;
			$res->plugin                         = $this->plugin_path;  // -update-plugin/-update-plugin.php
			$res->new_version                    = $remote->version;
			$res->tested                         = $remote->tested;
			$res->package                        = $remote->download_url;
			$transient->response[ $res->plugin ] = $res;
          
		}
		
		return $transient;

	}

	public function purge( $upgrader, $options ){

		if (
			$this->cache_allowed
			&& 'update' === $options['action']
			&& 'plugin' === $options[ 'type' ]
		) {
			// just clean the cache when new plugin version is installed
			delete_transient( $this->cache_key );
		}

	}
} 

new WCfAddon_Extension_Free();
new WCfAddon_Extension_Pro();

