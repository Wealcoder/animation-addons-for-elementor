<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageCompare;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Presets" element-control for the Image Compare wrapper widget.
 *
 * Carries NO stored prop value — it's an action control that routes to the
 * shared React component registered under 'aae-preset-picker'
 * (src/modules/atomic/element-controls/PresetPickerControl.jsx). See
 * Widgets/Btn/class-aae-a-preset-picker-control.php for the full contract.
 */
class AAE_A_Preset_Picker_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-preset-picker';
	}

	public function get_props(): array {
		return [];
	}
}
