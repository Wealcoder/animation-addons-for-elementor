<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Slides" element-control for the AAE Nested Slider.
 *
 * Mirrors Elementor's own Tabs_Control: an element-control carries NO stored
 * prop value. It serialises as { type: 'element-control', value: { type } } and
 * the editing panel routes that to the React component registered under the
 * same type id ('aae-slides') in the controls registry (see
 * src/modules/atomic/element-controls/). The component renders one repeater row
 * per real <e-aae-a-slide> child of the slider's track — there is no separate
 * repeater data to keep in sync; the list is a live projection of the element
 * tree.
 */
class AAE_A_Slides_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-slides';
	}

	public function get_props(): array {
		return [];
	}
}
