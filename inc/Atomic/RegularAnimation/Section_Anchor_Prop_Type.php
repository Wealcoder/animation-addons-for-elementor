<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RegularAnimation's section anchor — one prop registered in Schema.php,
 * bound to a placeholder Text_Control in Controls.php inside the
 * Section::make() for "Animation". The unique $$type is what the JS
 * registerResponsiveSection() condition matches to swap the placeholder
 * row for the full <ResponsiveSection> render tree (config-driven label +
 * input + dot + per-bp visibility for every animation field).
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-animation';
	}
}
