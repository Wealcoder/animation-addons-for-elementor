<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\Admin\Notices;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

defined( 'ABSPATH' ) || exit();
/**
 * Notices class.
 *
 * @since 2.4.16
 */
class Notices {
	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * [instance] Initializes a singleton instance
	 *
	 * @return [_Admin_Init]
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Id of the seasonal upgrade banner registered for this request, if any.
	 * Dismissing a banner is stored under its id, so every campaign has its own.
	 *
	 * @var string
	 */
	protected $promo_notice_id = '';

	/**
	 * The plugin prefix.
	 *
	 * @var array|string|string[]
	 */
	protected $plugin_prefix;

	/**
	 * The notices.
	 *
	 * @since 2.4.16
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Notices constructor.
	 *
	 * @since 2.4.16
	 */
	public function __construct() {
		
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'add_admin_notices' ) );
		

		$this->plugin_prefix = 'aae_notice_';
		add_action( 'wp_ajax_' . $this->plugin_prefix . '_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_style( 'aae-notice', WCF_ADDONS_URL . 'assets/css/css/notice.css', array( \WCF_ADDONS\AAE_Fonts::ensure() ), WCF_ADDONS_VERSION );
		wp_register_script( 'aae-notice', WCF_ADDONS_URL . 'assets/js/js/notice.js', array( 'jquery' ), WCF_ADDONS_VERSION, true );
		wp_register_style( 'aae-notice-promo', WCF_ADDONS_URL . 'assets/css/css/promo-banner.css', array(), WCF_ADDONS_VERSION );

		// Only load the banner stylesheet on the screen that will actually show it.
		if ( $this->promo_notice_id && isset( $this->notices[ $this->promo_notice_id ] ) && $this->should_display( $this->notices[ $this->promo_notice_id ] ) ) {
			wp_enqueue_style( 'aae-notice-promo' );
		}
	}

	/**
	 * Add Admin notices.
	 *
	 * @since 2.4.16
	 */
	public function add_admin_notices() {
		if ( $this->has_pro() ) {
			return;
		}

		// One banner at a time: the first campaign whose date range covers today.
		foreach ( $this->get_campaigns() as $id => $campaign ) {
			if ( ! $this->is_campaign_live( $campaign ) ) {
				continue;
			}

			$this->promo_notice_id = 'aae_promo_' . $id;
			$this->add(
				array(
					'message'     => $this->get_campaign_message( $campaign ),
					'notice_id'   => $this->promo_notice_id,
					'class'       => 'aae-promo-notice',
					'dismissible' => false,
				)
			);
			break;
		}
	}

	/**
	 * Seasonal upgrade banners for free users.
	 *
	 * 'start' and 'end' are inclusive dates (Y-m-d) in the site's timezone; an
	 * empty 'start' means "from the release onwards". 'image' is a 1100x150
	 * file in assets/images/notice/ with the close button drawn at its top
	 * right. A new campaign needs a new key: dismissing is stored under it.
	 *
	 * @since 2.6.0
	 * @return array
	 */
	protected function get_campaigns() {
		return array(
			'halloween_2026' => array(
				'start'        => '2026-10-20',
				'end'          => '2026-11-05',
				'image'        => 'halloween-2026.png',
				'alt'          => __( 'Upgrade to Pro and save 40% this Halloween', 'animation-addons-for-elementor' ),
				'label'        => __( 'Upgrade to Pro - 40% off', 'animation-addons-for-elementor' ),
				'utm_campaign' => 'halloween2026',
			),
			// The artwork is not in the plugin yet: until assets/images/notice/bfcm-2026.png
			// exists, is_campaign_live() keeps this one hidden. Alt/label are placeholders
			// to match the final image text.
			'bfcm_2026'      => array(
				'start'        => '2026-11-10',
				'end'          => '2026-12-10',
				'image'        => 'bfcm-2026.png',
				'alt'          => __( 'Upgrade to Pro and save big this Black Friday and Cyber Monday', 'animation-addons-for-elementor' ),
				'label'        => __( 'Upgrade to Pro - Black Friday offer', 'animation-addons-for-elementor' ),
				'utm_campaign' => 'bfcm2026',
			),
		);
	}

	/**
	 * Does the Pro plugin exist on this site, active or not?
	 *
	 * Anyone who has it has nothing to upgrade to.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	protected function has_pro() {
		return defined( 'WCF_ADDONS_PRO_VERSION' )
			|| defined( 'AAEADDON_PRO_VERSION' )
			|| file_exists( WP_PLUGIN_DIR . '/animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php' );
	}

	/**
	 * Is today inside the campaign's date range (and its artwork shipped)?
	 *
	 * @param array $campaign A get_campaigns() entry.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	protected function is_campaign_live( $campaign ) {
		if ( ! file_exists( WCF_ADDONS_PATH . 'assets/images/notice/' . $campaign['image'] ) ) {
			return false;
		}

		$today = wp_date( 'Y-m-d' );

		return ( empty( $campaign['start'] ) || $today >= $campaign['start'] ) && $today <= $campaign['end'];
	}

	/**
	 * Markup of a campaign banner: the artwork, a click-through over the whole
	 * image and an invisible close button over the one drawn into it.
	 *
	 * @param array $campaign A get_campaigns() entry.
	 *
	 * @since 2.6.0
	 * @return string
	 */
	protected function get_campaign_message( $campaign ) {
		$url = add_query_arg(
			array(
				'utm_source'   => 'wp',
				'utm_medium'   => 'noticebanner',
				'utm_campaign' => $campaign['utm_campaign'],
			),
			'https://animation-addons.com/pricing/'
		);

		return sprintf(
			'<div class="aae-promo-banner"><img src="%1$s" alt="%2$s" width="1100" height="150" /><a class="aae-promo-link" href="%3$s" target="_blank" rel="noopener noreferrer"><span class="screen-reader-text">%4$s</span></a><button type="button" class="aae-promo-close" data-dismiss="1"><span class="screen-reader-text">%5$s</span></button></div>',
			esc_url( WCF_ADDONS_URL . 'assets/images/notice/' . $campaign['image'] ),
			esc_attr( $campaign['alt'] ),
			esc_url( $url ),
			esc_html( $campaign['label'] ),
			esc_html__( 'Dismiss this notice', 'animation-addons-for-elementor' )
		);
	}

	/**
	 * Dismisses the notice.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function ajax_dismiss_notice() {
		if ( ! check_ajax_referer( $this->plugin_prefix . '_dismiss_notice', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
			exit;
		}
		$notice_id   = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : '';
		$snooze      = isset( $_POST['snooze'] ) ? filter_var( wp_unslash( $_POST['snooze'] ), FILTER_VALIDATE_BOOLEAN ) : false;
		$snooze_time = isset( $_POST['snooze_time'] ) ? absint( wp_unslash( $_POST['snooze_time'] ) ) : 7 * DAY_IN_SECONDS;
		$notice      = array_key_exists( $notice_id, $this->notices ) ? $this->notices[ $notice_id ] : null;
		if ( ! is_null( $notice ) ) {
			if ( $snooze ) {
				$this->snooze( $notice_id, $snooze_time );
			} else {
				$this->dismiss( $notice_id );
			}
			/*
			 * No wp_cache_flush() here.
			 *
			 * It used to sit on this line, almost certainly to fight the
			 * "dismissed notice comes back" symptom that is_dismissed() above
			 * was actually causing. It could never have fixed that — under a
			 * persistent object cache the row it was looking for does not exist,
			 * so emptying the cache just makes the next read miss and find
			 * nothing again.
			 *
			 * What it DID do was throw away the entire site's object cache —
			 * every option, query and term — because someone closed a notice.
			 * update_option() and set_transient() both maintain their own cache
			 * entries, so there was nothing to invalidate by hand.
			 */
			wp_send_json_success();
			exit;
		}
		wp_send_json_error();
		exit;
	}

	/**
	 * Display the admin notices.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function admin_notices() {
		if ( empty( $this->notices ) ) {
			return;
		}

		// Enqueue the notices script.
		wp_enqueue_script( 'aae-notice' );

		foreach ( $this->notices as $notice ) {
			if ( $this->should_display( $notice ) ) {
				$classes = array_unique( array_filter( wp_parse_list( $notice['class'] ) ) );
				$style   = ! empty( $notice['style'] ) ? $notice['style'] : '';
				$message = $notice['message'];
				if ( str_ends_with( $message, '.php' ) ) {
					wp_enqueue_style( 'aae-notice' );
					$path = wp_normalize_path( $message );
					if ( file_exists( $path ) ) {
						$ob_level = ob_get_level();
						ob_start();
						try {
							include $path;
						} finally {
							while ( ob_get_level() > $ob_level ) {
								$message = (string) ob_get_clean();
							}
						}
					}
				}

				// if dismissible then add is-dismissible class.
				if ( $notice['dismissible'] ) {
					$classes[] = 'is-dismissible';
				}

				// if empty message then skip.
				if ( empty( $message ) ) {
					continue;
				}

				// if message does not contain html tags then wrap it with a paragraph.
				if ( ! preg_match( '/<[^>]+>/', $message ) ) {
					$message = wpautop( $message );
				}

				printf(
					'<div class="notice aae-notice notice-%1$s %2$s" data-notice_id="%3$s" data-nonce="%4$s" data-action="%5$s" style="%6$s">%7$s%8$s</div>',
					esc_attr( $notice['type'] ),
					esc_attr( implode( ' ', $classes ) ),
					esc_attr( $notice['notice_id'] ),
					esc_attr( wp_create_nonce( $this->plugin_prefix . '_dismiss_notice' ) ),
					esc_attr( $this->plugin_prefix . '_dismiss_notice' ),
					esc_attr( $style ),
					wp_kses_post( wptexturize( $message ) ),
					$notice['dismissible'] ? '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice', 'animation-addons-for-elementor' ) . '</span></button>' : ''
				);
			}
		}
	}

	/**
	 * Add a notice.
	 *
	 * @param string|array $args The notice arguments or message.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function add( $args ) {
		if ( is_string( $args ) ) {
			$args = array( 'message' => $args );
		}
		$args = wp_parse_args(
			$args,
			array(
				'message'     => '',
				'type'        => 'info',
				'dismissible' => true,
				'capability'  => 'manage_options',
				'notice_id'   => '',
				'class'       => '',
				'style'       => '',
			)
		);

		// if message is empty then skip.
		if ( empty( $args['message'] ) ) {
			return;
		}

		// if we do not have a notice id then generate one.
		if ( empty( $args['notice_id'] ) ) {
			$args['notice_id'] = $this->plugin_prefix . '_' . md5( $args['message'] . $args['type'] );
		}

		// if dismissible and already dismissed, don't add the notice.
		if ( true === filter_var( $args['dismissible'], FILTER_VALIDATE_BOOLEAN ) && $this->is_dismissed( $args['notice_id'] ) ) {
			return;
		}

		$this->notices[ $args['notice_id'] ] = $args;
	}

	/**
	 * Get the notices.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_notices() {
		return $this->notices;
	}

	/**
	 * Is the notice dismissed?
	 *
	 * @param string $id The notice id.
	 *
	 * @since 2.4.16
	 * @return bool
	 */
	public function is_dismissed( $id ) {
		/*
		 * get_transient(), NOT get_option( '_transient_' . $id ).
		 *
		 * snooze() stores this with set_transient(). Reading the transient's
		 * underlying option row only works while transients happen to live in
		 * wp_options — which stops being true the moment the site has a
		 * persistent object cache: set_transient() then writes to Redis or
		 * Memcached and creates NO row at all. The read returned false forever,
		 * so snoozing silently did nothing and the notice reappeared on the very
		 * next page load.
		 *
		 * get_transient() is correct in both storage modes, and it also honours
		 * the expiry, which the raw option read did not.
		 */
		if ( 'yes' === get_option( $id ) || 'yes' === get_transient( $id ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Should the notice be displayed?
	 *
	 * @param array $notice The notice options.
	 *
	 * @since 2.4.16
	 * @return bool
	 */
	public function should_display( $notice ) {
		// WordPress.org Guideline 11: Display notices only on plugin-related screens unless it is an error.
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen ) {
				$is_plugin_screen = ( false !== strpos( $screen->id, 'animation-addons' ) || false !== strpos( $screen->id, 'wcf' ) );
				if ( ! $is_plugin_screen && ( empty( $notice['type'] ) || 'error' !== $notice['type'] ) ) {
					return false;
				}
			}
		}

		if ( ( $notice['notice_id'] && $this->is_dismissed( $notice['notice_id'] ) ) || ( $notice['capability'] && ! current_user_can( $notice['capability'] ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Dismiss a notice.
	 *
	 * @param string $id The notice id.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function dismiss( $id ) {
		update_option( $id, 'yes' );
		delete_transient( $id );
	}

	/**
	 * Snooze a notice.
	 *
	 * @param string $id The notice id.
	 * @param int    $time The time to snooze the notice for.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function snooze( $id, $time = null ) {
		set_transient( $id, 'yes', absint( $time ) );
	}
}

// Initialize the class.
Notices::instance();
