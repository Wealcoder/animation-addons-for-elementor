<?php
/**
 * "This is a Pro field" notice, shown at the top of a locked widget's panel.
 *
 * An element-control carries no stored value; it serialises as
 * { type: 'element-control', value: { type: 'aae-pro-notice' } } and the editing
 * panel routes that to the React component registered under the same id in
 * src/modules/atomic/element-controls/.
 *
 * It exists for ONE case, and it is the case the panel lock cannot cover: an
 * instance that is already on the canvas. Pro_Gated stops a locked field being
 * dragged in, but a preset can seed one, and a page built while the site was
 * licensed keeps its fields after a lapse. Those elements stay fully editable
 * on purpose — losing that would cost the customer their work — so the notice
 * is what tells the builder the field will not render for visitors, and offers
 * the way to fix it.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\Forms;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pro_Notice_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-pro-notice';
	}

	public function get_props(): array {
		return [];
	}
}
