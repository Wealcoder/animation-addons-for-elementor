<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Accordion;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Items" element-control for the AAE Accordion.
 *
 * Mirrors the Nested Slider's Slides element-control (and Elementor's own
 * Tabs_Control): an element-control carries NO stored prop value. It serialises
 * as { type: 'element-control', value: { type } } and the editing panel routes
 * that to the React component registered under the same type id ('aae-items')
 * in the controls registry (see src/modules/atomic/element-controls/). The
 * component renders one repeater row per real <e-aae-a-accordion-item> child of
 * the accordion — there is no separate repeater data to keep in sync; the list
 * is a live projection of the element tree (drag/duplicate/remove/rename all act
 * directly on the children).
 */
class AAE_A_Items_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-items';
	}

	public function get_props(): array {
		return [];
	}
}
