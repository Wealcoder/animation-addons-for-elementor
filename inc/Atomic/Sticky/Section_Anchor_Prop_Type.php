<?php

namespace WCF_ADDONS\Atomic\Sticky;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sticky section anchor.
 *
 * One prop registered in Schema.php and bound to a placeholder
 * Text_Control inside Controls.php.
 *
 * The unique $$type returned from get_key() is used by the
 * JS-side registerControlReplacement() dispatcher to replace
 * the placeholder row with the full custom Sticky UI component.
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-sticky';
	}
}