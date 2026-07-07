<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Element-control shown on EVERY nav-item's panel. The React component
 * (registered under 'aae-nav-sub-items' in element-controls/index.js) manages
 * the item's nested sub-items: it finds/creates the item's dropdown flexbox and
 * adds/removes nested `e-aae-a-nav-item` elements inside it. Because it appears
 * on every nav-item, selecting a nested item shows the same manager — that's
 * how 2nd/3rd-level menus are authored without touching the Structure tree.
 */
class AAE_A_Nav_Sub_Items_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-nav-sub-items';
	}

	public function get_props(): array {
		return [];
	}
}
