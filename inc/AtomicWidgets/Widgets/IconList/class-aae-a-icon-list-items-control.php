<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\IconList;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Items" element-control for the AAE Icon List (mirrors the Social Share's
 * AAE_A_Social_Share_Items_Control). Carries no stored prop value — it
 * serialises as { type: 'element-control', value: { type: 'aae-icon-list-items' } }
 * and the editing panel routes that to the React component registered under
 * the same type id in src/modules/atomic/element-controls/. That component
 * renders one repeater row per real <e-aae-a-icon-list-item> child — the list
 * is a live projection of the element tree, not separate repeater data.
 */
class AAE_A_Icon_List_Items_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-icon-list-items';
	}

	public function get_props(): array {
		return [];
	}
}
