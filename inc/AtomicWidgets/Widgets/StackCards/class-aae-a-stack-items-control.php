<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\StackCards;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Cards" element-control — add / duplicate / remove / reorder / rename the
 * deck's cards from the panel instead of hunting in the Structure tree.
 *
 * Thin registration stub. An element-control carries NO stored prop value — it
 * serialises as { type: 'element-control', value: { type } } and the editing
 * panel routes that to the React component registered under the same type id
 * ('aae-stack-cards') in the shared controls registry, which lives in the FREE
 * plugin at src/modules/atomic/element-controls/StackCardsControl.jsx.
 */
class AAE_A_Stack_Items_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-stack-cards';
	}

	public function get_props(): array {
		return [];
	}
}
