<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class AAE_A_Nav_Items_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-nav-items';
	}

	public function get_props(): array {
		return [];
	}
}
