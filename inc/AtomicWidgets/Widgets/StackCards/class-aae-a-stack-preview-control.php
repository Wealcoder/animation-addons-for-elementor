<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\StackCards;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Preview Animation" element-control — a play button plus a scrub slider that
 * replays the deck's real animation on the canvas, so a user can audition all
 * ten animations without publishing.
 *
 * Thin registration stub, carrying no stored prop. The React component is
 * registered under 'aae-stack-preview' in the FREE plugin at
 * src/modules/atomic/element-controls/StackPreviewControl.jsx and talks to the
 * preview iframe's window.AAEStackCards — the same cross-frame pattern
 * DrawPlayControl uses for window.AAEDrawSvg.
 */
class AAE_A_Stack_Preview_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-stack-preview';
	}

	public function get_props(): array {
		return [];
	}
}
