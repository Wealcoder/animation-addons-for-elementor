<?php
namespace WCF_ADDONS\Atomic\TextAnimation;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TextAnimation's section anchor — one prop registered in Schema.php, bound
 * to a placeholder Text_Control in Controls.php inside the Section::make()
 * for "Text Animation". The unique $$type is what the JS-side
 * registerResponsiveSection() dispatcher matches to swap the placeholder
 * row for the full <ResponsiveSection> tree.
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-text-animation';
	}
}
