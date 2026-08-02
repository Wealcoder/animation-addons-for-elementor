<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Accordion;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Presets" element-control for the Accordion.
 *
 * An element-control carries NO stored prop value — it serialises as
 * { type: 'element-control', value: { type } } and the editing panel routes
 * that to the React component registered under the same type id
 * ('aae-preset-picker') in the controls registry
 * (see src/modules/atomic/element-controls/PresetPickerControl.jsx).
 *
 * The component reads window.AAE_WIDGET_PRESETS, lists the presets keyed to
 * 'e-aae-a-accordion', and on choosing one replaces the selected accordion
 * with the preset's design. No props are passed from PHP — the preset list is
 * global.
 */
class AAE_A_Preset_Picker_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-preset-picker';
	}

	public function get_props(): array {
		return [];
	}
}
