<?php
/**
 * AAE Form Error Message — locked container shown when the form state is
 * error. Colors match Elementor's native e-form-error-message.
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

class AAE_A_Form_Error_Message extends AAE_A_Form_Message {

	public static $widget_description = 'Shown when the form submission fails. Hidden by default.';

	public static function get_type() {
		return 'e-aae-a-form-error-message';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-form-error-message';
	}

	public function get_title() {
		return esc_html__( 'Error message', 'animation-addons-for-elementor' );
	}

	protected static function get_background_color(): string {
		return '#FFDEDE';
	}

	protected static function get_text_color(): string {
		return '#870000';
	}

	protected static function get_default_status_paragraph_text(): string {
		return __( 'We couldn’t process your submission. Please retry.', 'animation-addons-for-elementor' );
	}
}
