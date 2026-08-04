<?php

namespace WCF_ADDONS\Atomic\BackgroundVideo;

use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anchor for the Background Video panel section.
 *
 * The $$type below is what the editor's control-replacement dispatcher matches
 * to swap the placeholder Text_Control row for the whole <ResponsiveSection>
 * built from extensions/background-video/config.js. Must stay in sync with that
 * file's `anchorKey`.
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-background-video';
	}
}
