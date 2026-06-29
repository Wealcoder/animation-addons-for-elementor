<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Menu Items" element-control for the AAE Nav.
 *
 * Same pattern as NestedSlider's AAE_A_Slides_Control: carries NO stored prop
 * value, serialises as { type: 'element-control', value: { type: 'aae-nav-items' } }.
 * The editing panel routes it to the React component registered under that
 * type id (src/modules/atomic/element-controls/NavItemsControl.jsx).
 *
 * The list is a live projection of the nav's <e-aae-a-nav-item> children —
 * add / duplicate / remove / drag-reorder all operate on the real element
 * tree via Elementor's editor-elements helpers.
 */
class AAE_A_Nav_Items_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-nav-items';
	}

	public function get_props(): array {
		return [];
	}
}
