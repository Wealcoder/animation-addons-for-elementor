<?php
namespace WCF_ADDONS\Atomic\PropTypes;

use Elementor\Modules\AtomicWidgets\PropTypes\Base\Plain_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permissive responsive JSON prop type.
 *
 * Stored value shape:
 *   {
 *     $$type: 'aae-rj',
 *     value: {
 *       desktop: <any JSON>,
 *       tablet:  <any JSON> | null,
 *       mobile:  <any JSON> | null,
 *       ...
 *     }
 *   }
 *
 * Used by JS-only controls (like the in-section repeater) where the row
 * shape semantics live entirely in JS config. PHP only guarantees:
 *   - the outer transformable envelope is intact
 *   - value is an object keyed by recognized breakpoint names
 *   - each breakpoint's payload is JSON-serializable (array | object | scalar | null)
 *
 * That's enough for Elementor to save/load round-trip; cell-level validation
 * is the JS layer's job since only JS knows the row contract.
 *
 * Why a Plain_Prop_Type, not Object_Prop_Type: Object_Prop_Type would
 * insist every cell be a recognized prop-type transformable, which forces
 * us back into the per-cell hierarchy we're trying to avoid. Plain lets
 * us own the validate/sanitize gates ourselves.
 */
class Responsive_Json_Prop_Type extends Plain_Prop_Type {

	public function __construct() {
		// Plain_Prop_Type has no parent constructor — set meta directly.
		// Opt out of extender wrapping (overridable / dynamic) so our $$type
		// stays a plain key the JS registry can match on without union
		// unwrapping.
		$this->meta( 'overridable', false );
		$this->meta( 'dynamic',     false );
	}

	public static function get_key(): string {
		return 'aae-rj';
	}

	/** Known breakpoint names. Used to filter the value map on validate/sanitize. */
	private const KNOWN_BPS = [
		'desktop', 'widescreen', 'laptop',
		'tablet_extra', 'tablet',
		'mobile_extra', 'mobile',
	];

	protected function validate_value( $value ): bool {
		if ( null === $value ) {
			return true;
		}
		if ( ! is_array( $value ) ) {
			return false;
		}
		// Every key must be a recognized breakpoint name. Each per-bp payload
		// is allowed to be null, scalar, array, or object — anything JSON-
		// serializable. We don't inspect deeper than the breakpoint level.
		foreach ( array_keys( $value ) as $key ) {
			if ( ! in_array( $key, self::KNOWN_BPS, true ) ) {
				return false;
			}
		}
		return true;
	}

	protected function sanitize_value( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}
		// Drop unknown breakpoint keys silently so a future site that disables
		// a breakpoint doesn't carry orphaned data.
		$clean = [];
		foreach ( self::KNOWN_BPS as $bp ) {
			if ( array_key_exists( $bp, $value ) ) {
				$clean[ $bp ] = $value[ $bp ];
			}
		}
		return $clean;
	}
}
