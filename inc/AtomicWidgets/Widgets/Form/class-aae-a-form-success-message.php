<?php
/**
 * AAE Form Success Message — locked container shown when the form state is
 * success. Colors match Elementor's native e-form-success-message.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-aae-a-form-message.php';

if ( ! class_exists( __NAMESPACE__ . '\AAE_A_Form_Message' ) ) {
	return;
}

class AAE_A_Form_Success_Message extends AAE_A_Form_Message {

	public static $widget_description = 'Shown when the form is submitted successfully. Hidden by default.';

	public static function get_type() {
		return 'e-aae-a-form-success-message';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-form-success-message';
	}

	public function get_title() {
		return esc_html__( 'Success message', 'animation-addons-for-elementor' );
	}

	protected static function get_background_color(): string {
		return '#D4E9D6';
	}

	protected static function get_text_color(): string {
		return '#2F532E';
	}

	protected static function get_default_status_paragraph_text(): string {
		return __( 'Great! We’ve received your information.', 'animation-addons-for-elementor' );
	}
}
