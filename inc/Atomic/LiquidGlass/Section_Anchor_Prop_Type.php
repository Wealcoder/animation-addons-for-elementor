<?php
namespace Wealcoder\AnimationAddons\Atomic\LiquidGlass;

use Wealcoder\AnimationAddons\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liquid Glass section anchor. The $$type is what the JS-side
 * registerResponsiveSection() dispatcher matches to swap the placeholder
 * Text_Control for the full <ResponsiveSection> tree.
 */
class Section_Anchor_Prop_Type extends Base_Section_Anchor {

	public static function get_key(): string {
		return 'aae-section-aae-liquid-glass';
	}
}
