<?php

/**
 * AAE Loop Item Document
 *
 * A custom Elementor document type that hosts the "loop item template" — the
 * recurring layout the Loop Grid widget repeats once per queried post.
 *
 * Why a V3 document (not an atomic element): atomic widgets have no document
 * type of their own; they live INSIDE a V3 document. So the loop-item template
 * is a standard Elementor library document whose canvas can contain AAE atomic
 * widgets. This mirrors Elementor Pro's loop-builder Loop document.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Documents;

use Elementor\Core\Base\Document;
use Elementor\TemplateLibrary\Source_Local;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Core\Base\Document' ) ) {
	return;
}

class AAE_Loop_Item_Document extends Document {

	const DOCUMENT_TYPE = 'aae-loop-item';

	public static function get_type() {
		return self::DOCUMENT_TYPE;
	}

	public static function get_title() {
		return esc_html__( 'AAE Loop Item', 'animation-addons-for-elementor' );
	}

	public static function get_plural_title() {
		return esc_html__( 'AAE Loop Items', 'animation-addons-for-elementor' );
	}

	/**
	 * Document properties.
	 *
	 * register_type + cpt make loop-items live in Elementor's template-library
	 * CPT (saved posts), exactly like Library_Document. show_in_library lets
	 * them appear in the library; admin_tab_group keeps them grouped.
	 */
	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['admin_tab_group']     = 'library';
		$properties['show_in_library']     = true;
		$properties['register_type']       = true;
		$properties['cpt']                 = [ Source_Local::CPT ];
		$properties['support_kit']         = true;
		$properties['support_conditions']  = false;

		return $properties;
	}

	/**
	 * CSS wrapper selector — scopes this document's styles to its own wrapper.
	 * Each rendered loop item gets a `.elementor-<id>` class, so per-item CSS
	 * targets only that instance.
	 */
	public function get_css_wrapper_selector() {
		return '.elementor-' . $this->get_main_id();
	}

	/**
	 * Print this document's content.
	 *
	 * The core Document base has no print_content() (it lives on Pro's
	 * Theme_Document). The Loop Grid widget calls this once per queried post —
	 * inside a WP loop where setup_postdata() has set the current post — so the
	 * atomic "current post" widgets inside (post title/image) resolve to the
	 * right post automatically.
	 */
	public function print_content() {
		// get_content() runs the elements through the frontend renderer and
		// returns the HTML (CSS handled separately by the loop grid widget).
		echo $this->get_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
