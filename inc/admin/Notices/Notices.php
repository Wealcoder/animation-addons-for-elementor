<?php

namespace Wealcoder\AnimationAddons\Admin\Notices;

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
		// The migration and import notices instantiate this class from inside
		// admin_init (priorities 11 and 12), after priority 10 has already run,
		// so a hook added here would never fire on that request.
		if ( did_action( 'admin_init' ) ) {
			$this->add_admin_notices();
		} else {
			add_action( 'admin_init', array( $this, 'add_admin_notices' ) );
		}


		$this->plugin_prefix = 'aaeaddon_notice';
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
		// `Aaeaddon_Fonts` lives in inc/admin/class-fonts.php, which is NOT
		// PSR-4 loadable and is required from Plugin::include_files() — a path
		// that never runs without Elementor. This class, however, is booted by
		// the storage-name Migration on every admin request whether Elementor
		// is active or not (the bridge serves options either way), so on a site
		// with Elementor deactivated every wp-admin screen fataled here. Measured
		// over real HTTP (verify-plugin-cases.mjs, case elementor-off).
		if ( ! class_exists( '\Wealcoder\AnimationAddons\Aaeaddon_Fonts', false ) ) {
			require_once AAEADDON_PATH . 'inc/admin/class-fonts.php';
		}
		wp_register_style( 'aae-notice', AAEADDON_URL . 'assets/css/css/notice.css', array( \Wealcoder\AnimationAddons\Aaeaddon_Fonts::ensure() ), AAEADDON_VERSION );
		wp_register_script( 'aae-notice', AAEADDON_URL . 'assets/js/js/notice.js', array( 'jquery' ), AAEADDON_VERSION, true );
		wp_register_style( 'aaeaddon-notice-promo', AAEADDON_URL . 'assets/css/css/promo-banner.css', array(), AAEADDON_VERSION );

		// Only load the banner stylesheet on the screen that will actually show it.
		if ( $this->promo_notice_id && isset( $this->notices[ $this->promo_notice_id ] ) && $this->should_display( $this->notices[ $this->promo_notice_id ] ) ) {
			wp_enqueue_style( 'aaeaddon-notice-promo' );
		}
	}

	/**
	 * Add Admin notices.
	 *
	 * @since 2.4.16
	 */
	public function add_admin_notices() {
		$forced = $this->is_campaign_forced();

		if ( ! $forced && $this->has_pro() ) {
			return;
		}

		// One banner at a time: the first campaign whose date range covers today.
		foreach ( $this->get_campaigns() as $id => $campaign ) {
			if ( ! $this->is_campaign_live( $campaign, $forced ) ) {
				continue;
			}

			// `aaeaddon_notice_` is the prefix the dismissal row is migrated,
			// kept and uninstalled under (see Compat/key-map.php).
			$this->promo_notice_id = 'aaeaddon_notice_promo_' . $id;
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
	 * Testing switch: `define( 'AAEADDON_CAMPAIGN_SHOW', true )` shows the first
	 * campaign whose artwork exists whatever the date, whether Pro is installed
	 * or not, and even after it was dismissed. Never define it in a release.
	 *
	 * @since 4.2.8
	 * @return bool
	 */
	protected function is_campaign_forced() {
		return defined( 'AAEADDON_CAMPAIGN_SHOW' ) && true === AAEADDON_CAMPAIGN_SHOW;
	}

	/**
	 * Seasonal upgrade banners for free users.
	 *
	 * 'start' and 'end' are inclusive dates (Y-m-d) in the site's timezone; an
	 * empty 'start' means "from the release onwards". 'image' is a 1100x150
	 * file in assets/images/notice/ with the close button drawn at its top
	 * right. A new campaign needs a new key: dismissing is stored under it.
	 *
	 * @since 4.2.8
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
				'utm_campaign' => 'halloween_2026',
			),
			'bfcm_2026'      => array(
				'start'        => '2026-11-10',
				'end'          => '2026-12-10',
				'image'        => 'bfcm-2026.png',
				'alt'          => __( 'Black Friday deal on Animation Addons Pro. Upgrade now', 'animation-addons-for-elementor' ),
				'label'        => __( 'Upgrade to Pro - Black Friday deal', 'animation-addons-for-elementor' ),
				'utm_campaign' => 'bfcm_2026',
			),
		);
	}

	/**
	 * Does the Pro plugin exist on this site, active or not?
	 *
	 * Anyone who has it has nothing to upgrade to.
	 *
	 * @since 4.2.8
	 * @return bool
	 */
	protected function has_pro() {
		return defined( 'AAEADDON_PRO_VERSION' )
			|| defined( 'WCF_ADDONS_PRO_VERSION' )
			|| file_exists( WP_PLUGIN_DIR . '/animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php' );
	}

	/**
	 * Is today inside the campaign's date range (and its artwork shipped)?
	 *
	 * @param array $campaign A get_campaigns() entry.
	 * @param bool  $forced   Ignore the date range (testing switch).
	 *
	 * @since 4.2.8
	 * @return bool
	 */
	protected function is_campaign_live( $campaign, $forced = false ) {
		if ( ! file_exists( AAEADDON_PATH . 'assets/images/notice/' . $campaign['image'] ) ) {
			return false;
		}

		if ( $forced ) {
			return true;
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
	 * @since 4.2.8
	 * @return string
	 */
	protected function get_campaign_message( $campaign ) {
		$url = add_query_arg(
			array(
				'utm_source'   => 'plugin_dashboard',
				'utm_medium'   => 'free_user_notice',
				'utm_campaign' => $campaign['utm_campaign'],
				'utm_content'  => 'upgrade_button',
			),
			'https://animation-addons.com/pricing/'
		);

		// The end date is a plain calendar day, so format it in UTC: going through
		// the site's timezone could land on the neighbouring day.
		$ends = wp_date( 'M j', strtotime( $campaign['end'] . ' 00:00:00 UTC' ), new \DateTimeZone( 'UTC' ) );

		// The artwork's own X snoozes for a day (notice.js reads data-snooze);
		// the row under it offers the other two answers.
		return sprintf(
			'<div class="aae-promo-banner"><img src="%1$s" alt="%2$s" width="1100" height="150" /><a class="aae-promo-link" href="%3$s" target="_blank" rel="noopener noreferrer"><span class="screen-reader-text">%4$s</span></a><button type="button" class="aae-promo-close" data-snooze="%5$d"><span class="screen-reader-text">%6$s</span></button></div><div class="aae-promo-actions"><a href="#" data-snooze="%5$d">%7$s</a><span aria-hidden="true">&middot;</span><a href="#" data-dismiss="1">%8$s</a><span class="aae-promo-ends">%9$s</span></div>',
			esc_url( AAEADDON_URL . 'assets/images/notice/' . $campaign['image'] ),
			esc_attr( $campaign['alt'] ),
			esc_url( $url ),
			esc_html( $campaign['label'] ),
			DAY_IN_SECONDS,
			esc_html__( 'Remind me tomorrow', 'animation-addons-for-elementor' ),
			esc_html__( 'Remind me tomorrow', 'animation-addons-for-elementor' ),
			esc_html__( "Don't show again", 'animation-addons-for-elementor' ),
			/* translators: %s: last day of the offer, e.g. "Nov 5". */
			esc_html( sprintf( __( 'Offer ends %s', 'animation-addons-for-elementor' ), $ends ) )
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
				// 'aaeaddon' covers the dashboard, whose screen id is
				// animation-addon_page_aaeaddon_settings.
				$is_plugin_screen = ( false !== strpos( $screen->id, 'animation-addons' ) || false !== strpos( $screen->id, 'aaeaddon' ) || false !== strpos( $screen->id, 'wcf' ) );
				if ( ! $is_plugin_screen && ( empty( $notice['type'] ) || 'error' !== $notice['type'] ) ) {
					return false;
				}
			}
		}

		$dismissed = $notice['notice_id'] && $this->is_dismissed( $notice['notice_id'] ) && ! ( $this->is_campaign_forced() && $notice['notice_id'] === $this->promo_notice_id );

		if ( $dismissed || ( $notice['capability'] && ! current_user_can( $notice['capability'] ) ) ) {
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
