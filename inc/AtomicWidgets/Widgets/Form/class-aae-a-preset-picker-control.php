<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Presets" element-control for the AAE Form widget.
 *
 * An element-control carries NO stored prop value. It serialises as
 * { type: 'element-control', value: { type } } and the editing panel routes
 * that to the React component registered under the same type id
 * ('aae-preset-picker') in the controls registry
 * (see src/modules/atomic/element-controls/PresetPickerControl.jsx).
 *
 * The component reads window.AAE_WIDGET_PRESETS (localised by
 * class-atomic.php), lists the presets keyed to 'e-aae-a-form', and on
 * choosing one replaces the selected form with the preset's design.
 */
class AAE_A_Preset_Picker_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-preset-picker';
	}

	public function get_props(): array {
		return [];
	}
}
