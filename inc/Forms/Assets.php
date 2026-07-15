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

	public static function init(): void {
		// After class-atomic.php's registration (default prio 10).
		add_action( 'wp_enqueue_scripts', [ self::class, 'localize' ], 20 );
		// The editor preview blanket-enqueues widget scripts — config must
		// exist there too (the runtime itself stays inert in edit mode).
		add_action( 'elementor/preview/enqueue_scripts', [ self::class, 'localize' ], 20 );
	}

	public static function localize(): void {
		if ( ! wp_script_is( self::SCRIPT_HANDLE, 'registered' ) && ! wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'AAEFormConfig',
			[
				'restUrl' => esc_url_raw( rest_url( Rest::REST_NAMESPACE . '/' ) ),
				'i18n'    => [
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
