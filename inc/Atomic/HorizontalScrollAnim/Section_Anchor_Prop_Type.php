<?php
namespace WCF_ADDONS\Atomic\HorizontalScrollAnim;
use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {

		/*
		|--------------------------------------------------------------------------
		| CHANGE THIS
		|--------------------------------------------------------------------------
		*/

		return 'aae-section-aae-horizontal-scroll-anim';
	}
}