<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Btn;

use Elementor\Modules\AtomicWidgets\Controls\Base\Atomic_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Hover Style" control for the Btn widget — prop-bound to the plain String
 * prop `aae_btn_hover_style` (see class-aae-a-btn.php's props schema).
 *
 * Serialises as { type: 'aae-btn-hover-style', bind, props } and the editing
 * panel routes it to the React component registered under the same id in
 * the FREE plugin's shared registry
 * (src/modules/atomic/element-controls/BtnHoverStyleControl.jsx) — same
 * arrangement as AAE_Query_Chips_Control / AAE_A_Media_Url_Control.
 *
 * That component hides its own row unless the sibling `aae_btn_hover_effect`
 * marker is true on the selected button — this stub carries no props of its
 * own to support that; get_props() is intentionally empty.
 */
class AAE_A_Btn_Hover_Style_Control extends Atomic_Control_Base {

	public function get_type(): string {
		return 'aae-btn-hover-style';
	}

	public function get_props(): array {
		return [];
	}
}
