<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\DrawSvg;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Play Animation" element-control for the AAE DrawSVG widget.
 *
 * An element-control carries NO stored prop value — it's an action button. It
 * serialises as { type: 'element-control', value: { type } } and the editing
 * panel routes that to the React component registered under the same type id
 * ('aae-draw-play') in the controls registry
 * (see src/modules/atomic/element-controls/DrawPlayControl.jsx).
 *
 * On click the component calls window.AAEDrawSvg.replay( elementId ) inside the
 * preview iframe (exposed by the widget's frontend runtime, draw-svg.js), which
 * restarts the draw animation for the selected element.
 */
class AAE_A_Draw_Play_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-draw-play';
	}

	public function get_props(): array {
		return [];
	}
}
