<?php
namespace WCF_ADDONS\Atomic\ImageHover;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Hover section anchor — paired with a placeholder Text_Control in
 * Controls.php. React replacement swaps the placeholder row for the full
 * responsive tree (width / height / top / left fields).
 *
 * The native Image_Control + non-responsive z-index live as separate items
 * in the same Section — they render natively, not through ResponsiveSection.
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-image-hover';
	}
}
