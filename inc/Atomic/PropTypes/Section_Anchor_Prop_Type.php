<?php
namespace WCF_ADDONS\Atomic\PropTypes;

use Elementor\Modules\AtomicWidgets\PropTypes\Base\Plain_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sentinel prop type whose only job is to give a section's React anchor a
 * unique transformable $$type the editor's control replacement registry can
 * match against.
 *
 * Each AAE extension (regular-animation, text-animation, image-animation, …)
 * subclasses this with its own get_key() (e.g. 'aae-section-aae-animation').
 * Schema.php registers ONE prop of that subtype, Controls.php binds a
 * placeholder Text_Control to it inside Section::make(), and the JS
 * registerResponsiveSection() condition matches the $$type and replaces the
 * placeholder row with an entire <ResponsiveSection> tree (every extension
 * field rendered with its own label, input, dot, and active-bp-aware
 * visibility predicate).
 *
 * The stored value is irrelevant — the editor never reads it because the
 * React replacement intercepts the row before the control would render.
 * Default is an empty string for round-trip safety.
 *
 * Concrete subclasses MUST override get_key() to return a stable unique key.
 * Extends Plain_Prop_Type so dynamic / overridable extenders get applied like
 * any normal primitive — but we opt out of both in the constructor since the
 * anchor never produces a real user-facing control.
 */
abstract class Section_Anchor_Prop_Type extends Plain_Prop_Type {

	public function __construct() {
		// Plain_Prop_Type uses traits and has no parent constructor — calling
		// parent::__construct() crashes with "Cannot call constructor". Just
		// set our meta directly.
		$this->meta( 'overridable', false );
		$this->meta( 'dynamic',     false );
	}

	protected function validate_value( $value ): bool {
		return is_string( $value );
	}

	protected function sanitize_value( $value ) {
		return is_string( $value ) ? $value : '';
	}
}
