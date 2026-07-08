<?php
/**
 * Concrete library-document types for the AAE atomic top-level widgets.
 *
 * One tiny subclass per savable root element type, mirroring Elementor's
 * Flexbox / Div_Block library documents. Each just declares its type name so
 * Plugin::$instance->documents->get_document_type( <elType> ) resolves.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Library;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Loop_Grid_Document extends AAE_A_Library_Document {
	public function get_name() {
		return 'e-aae-a-loop-grid';
	}
	public static function get_title() {
		return esc_html__( 'AAE Loop Grid', 'animation-addons-for-elementor' );
	}
	public static function get_type() {
		return 'e-aae-a-loop-grid';
	}
}

class AAE_A_Loop_Grid_Slider_Document extends AAE_A_Library_Document {
	public function get_name() {
		return 'e-aae-a-loop-grid-slider';
	}
	public static function get_title() {
		return esc_html__( 'AAE Loop Grid Slider', 'animation-addons-for-elementor' );
	}
	public static function get_type() {
		return 'e-aae-a-loop-grid-slider';
	}
}

class AAE_A_Slider_Document extends AAE_A_Library_Document {
	public function get_name() {
		return 'e-aae-a-slider';
	}
	public static function get_title() {
		return esc_html__( 'AAE Slider', 'animation-addons-for-elementor' );
	}
	public static function get_type() {
		return 'e-aae-a-slider';
	}
}
