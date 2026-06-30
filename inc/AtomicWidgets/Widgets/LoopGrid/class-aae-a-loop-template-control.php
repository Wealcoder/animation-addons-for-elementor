<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Loop Item Template" element-control for the Loop Grid widget.
 *
 * Mirrors AAE_A_Preset_Picker_Control: an element-control with no stored prop.
 * It serialises as { type: 'element-control', value: { type } } and the editing
 * panel routes that to the React component registered under the same type id
 * ('aae-loop-template') in the controls registry
 * (src/modules/atomic/element-controls/LoopTemplateControl.jsx).
 *
 * The component lets the user create a new loop-item template (ajax) or pick an
 * existing one, writes the chosen id into the widget's `template_id` prop, and
 * triggers the in-place document switch to edit it.
 */
class AAE_A_Loop_Template_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-loop-template';
	}

	public function get_props(): array {
		return [];
	}
}
