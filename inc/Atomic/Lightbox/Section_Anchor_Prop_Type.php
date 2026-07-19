<?php
namespace WCF_ADDONS\Atomic\Lightbox;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anchor for the "Lightbox Style" responsive React section. Schema binds one
 * prop of this type; Controls.php places a placeholder Text_Control on it; the
 * editor-bridge config (extensions/lightbox-style/config.js) matches this key
 * and swaps the row for the full <ResponsiveSection> of style controls.
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-lightbox-style';
	}
}
 