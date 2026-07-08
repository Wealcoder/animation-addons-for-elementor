<?php
/**
 * Base library-document for AAE atomic top-level widgets.
 *
 * Elementor only registers atomic document types for e-div-block / e-flexbox /
 * e-form (see modules/atomic-widgets/library/atomic-widgets-library.php). Any
 * other atomic top-level element (our Loop Grid, Loop Grid Slider, Nested
 * Slider) therefore has NO registered document type, so "Save as a template"
 * fails validation in Local::is_valid_template_type() with "Invalid template
 * type." — the editor sends the element's own elType as the template type
 * (save-template.js getSaveType(): `type = model.get('elType')`), and that
 * elType has no document class.
 *
 * Registering a Library_Document subclass per atomic root type fixes it exactly
 * the way Elementor fixes it for Flexbox: get_document_type() now resolves, the
 * cpt check passes, and the element saves as a normal library template. No UI or
 * settings are added — this is purely the missing type registration.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Library;

use Elementor\Modules\Library\Documents\Library_Document;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

abstract class AAE_A_Library_Document extends Library_Document {

	public static function get_properties() {
		$properties = parent::get_properties();

		// Match Elementor's atomic Flexbox document — lets the saved template
		// participate in the kit/global-styles pipeline like a native one.
		$properties['support_kit'] = true;

		return $properties;
	}
}
