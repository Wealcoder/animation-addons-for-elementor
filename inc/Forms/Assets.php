<?php
/**
 * AAE Forms — runtime configuration for the frontend bundle (Milestone 5).
 *
 * The form widget's JS handle (`aae-a-form-js`) is registered/enqueued by
 * the AtomicWidgets on-demand pipeline (class-atomic.php) — this class only
 * attaches the REST config + translated UI copy to it. Localizing onto a
 * registered-but-not-enqueued handle is safe: the data prints only if the
 * script actually loads, so pages without a form ship zero extra bytes.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	const SCRIPT_HANDLE = 'aae-a-form-js';

	const SCRIPT_PATH  = '/assets/atomic/js/form.js';
	const STYLE_HANDLE = 'aae-a-form-css';
	const STYLE_PATH   = '/assets/atomic/css/form.css';

	public static function init(): void {
		// Force-load form assets on a page that has no form at render time but
		// injects one later (custom AJAX / popup). Runs BEFORE localize so the
		// handle exists to attach config to. Off by default — opt in with:
		// add_filter( 'aae_form/force_assets', '__return_true' );
		// (or return true only for specific pages/conditions).
		add_action( 'wp_enqueue_scripts', [ self::class, 'maybe_force_assets' ], 15 );

		// After class-atomic.php's registration (default prio 10).
		add_action( 'wp_enqueue_scripts', [ self::class, 'localize' ], 20 );
		// The editor preview blanket-enqueues widget scripts — config must
		// exist there too (the runtime itself stays inert in edit mode).
		add_action( 'elementor/preview/enqueue_scripts', [ self::class, 'localize' ], 20 );
	}

	/**
	 * Ensure form.js + form.css are present on pages that will inject a form
	 * dynamically. Without this the on-demand pipeline never enqueues them
	 * (it keys off a form rendering in the initial page), so an AJAX-injected
	 * form has no runtime — the MutationObserver/aaeInitForms hook that would
	 * wire it up lives inside form.js itself.
	 */
	public static function maybe_force_assets(): void {
		/**
		 * Whether to force-load AAE form assets on this request.
		 *
		 * @param bool $force Default false.
		 */
		if ( ! apply_filters( 'aae_form/force_assets', false ) ) {
			return;
		}

		if ( ! wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			$path    = self::SCRIPT_PATH;
			$file    = WCF_ADDONS_PATH . $path;
			$version = file_exists( $file ) ? filemtime( $file ) : WCF_ADDONS_VERSION;
			wp_register_script(
				self::SCRIPT_HANDLE,
				WCF_ADDONS_URL . $path,
				[ 'elementor-v2-frontend-handlers' ],
				$version,
				true
			);
		}
		wp_enqueue_script( self::SCRIPT_HANDLE );

		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			$style_file    = WCF_ADDONS_PATH . self::STYLE_PATH;
			$style_version = file_exists( $style_file ) ? filemtime( $style_file ) : WCF_ADDONS_VERSION;
			wp_register_style( self::STYLE_HANDLE, WCF_ADDONS_URL . self::STYLE_PATH, [], $style_version );
		}
		wp_enqueue_style( self::STYLE_HANDLE );
	}

	public static function localize(): void {
		if ( ! wp_script_is( self::SCRIPT_HANDLE, 'registered' ) && ! wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'AAEFormConfig',
			[
				'restUrl'          => esc_url_raw( rest_url( Rest::REST_NAMESPACE . '/' ) ),
				// Public site key only — never the secret key. The runtime
				// loads Google's script and calls grecaptcha.execute() itself,
				// only for forms actually marked captcha_provider=recaptcha_v3
				// (data-aae-form-recaptcha on the <form>), so a page with no
				// such form never touches Google's script at all.
				'recaptchaSiteKey' => Captcha::site_key(),
				'i18n'             => [
					// Network/state copy — exact wording from the spec's UX table.
					'slow'          => __( 'Your connection seems slow. Please wait a moment.', 'animation-addons-for-elementor' ),
					'offline'       => __( 'You appear to be offline. Please reconnect and try again.', 'animation-addons-for-elementor' ),
					'timeout'       => __( 'We could not submit the form. Your information is still here. Please try again.', 'animation-addons-for-elementor' ),
					'duplicate'     => __( 'This form was already submitted.', 'animation-addons-for-elementor' ),
					'rateLimit'     => __( 'Too many attempts. Please wait a moment and try again.', 'animation-addons-for-elementor' ),
					'genericError'  => __( 'We could not submit the form. Please try again.', 'animation-addons-for-elementor' ),
					'sending'       => __( 'Sending…', 'animation-addons-for-elementor' ),
					// Frontend validation copy (backend repeats these checks).
					'required'      => __( 'This field is required.', 'animation-addons-for-elementor' ),
					'invalidEmail'  => __( 'Please enter a valid email address.', 'animation-addons-for-elementor' ),
					'invalidUrl'    => __( 'Please enter a valid URL.', 'animation-addons-for-elementor' ),
					'invalidNumber' => __( 'Please enter a number.', 'animation-addons-for-elementor' ),
					'invalidTel'    => __( 'Please enter a valid phone number.', 'animation-addons-for-elementor' ),
				],
			]
		);
	}
}
