<?php
/**
 * REFERENCE STUB — copy into inc/AtomicWidgets/Widgets/<Name>/ and change ONLY
 * the namespace to the target widget's namespace. Do not edit get_type():
 * it must stay 'aae-preset-picker' so every copy resolves to the same React
 * component registered in src/modules/atomic/element-controls/index.js.
 *
 * "Presets" element-control for AAE atomic widgets. An element-control carries
 * NO stored prop value — it serialises as an element-control the editing panel
 * routes to the component registered under the matching type id. The React side
 * reads window.AAE_WIDGET_PRESETS (localised by class-atomic.php), lists the
 * presets for the selected element's type, and on pick replaces the selected
 * element with the preset design (flex wrapper unwrapped, in place).
 */
namespace WCF_ADDONS\AtomicWidgets\Widgets\REPLACE_WITH_WIDGET_NAMESPACE;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Preset_Picker_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-preset-picker';
	}

	public function get_props(): array {
		return [];
	}
}
