<?php
namespace WCF_ADDONS\Atomic\Lightbox;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightbox section anchor — paired with a placeholder Text_Control in
 * Controls.php. A React replacement (registered editor-side) may later swap
 * the placeholder row for the full responsive control tree. Until then the
 * anchor keeps the section shape identical to the plugin's other effects.
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-lightbox';
	}
}
