<?php

namespace Wealcoder\AnimationAddons\Atomic\CursorHoverEffect;

use Wealcoder\AnimationAddons\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

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

		return 'aae-section-aae-cursor-hover-effect-anchor';
	}
}