<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\BtnPro;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Preset_Picker_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-preset-picker';
	}

	public function get_props(): array {
		return [];
	}
}
