<?php
namespace WCF_ADDONS\Atomic\ImageAdvancedAnimation;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Advanced Animation section anchor — one prop registered in
 * Schema.php, bound to a placeholder Text_Control in Controls.php. The
 * unique $$type is what the JS-side registerResponsiveSection() dispatcher
 * matches to swap the placeholder row for the full <ResponsiveSection> tree.
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-image-advanced-animation';
	}
}
