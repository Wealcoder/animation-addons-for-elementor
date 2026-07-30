<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\StackCards;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Presets" element-control for the Stack Cards deck.
 *
 * Thin registration stub (mirrors every other AAE widget's copy). An
 * element-control carries NO stored prop value — it serialises as
 * { type: 'element-control', value: { type } } and the editing panel routes that
 * to the React component registered under the same type id ('aae-preset-picker')
 * in the shared controls registry (src/modules/atomic/element-controls/
 * PresetPickerControl.jsx). The component fetches presets for the selected
 * element's type from the REST route aae/v1/presets and, on pick, replaces the
 * selected element with the preset design via the shared preset-apply engine.
 * No props are passed from PHP — the preset list is fetched client-side.
 */
class AAE_A_Preset_Picker_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-preset-picker';
	}

	public function get_props(): array {
		return [];
	}
}
