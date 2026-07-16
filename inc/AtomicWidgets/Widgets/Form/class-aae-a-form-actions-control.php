<?php
/**
 * AAE Form — "Actions After Submit" element-control stub.
 *
 * Serialises as { type: 'element-control', value: { type: 'aae-form-actions' } };
 * the editing panel resolves it to the FormActionsControl React component
 * (src/modules/atomic/element-controls/FormActionsControl.jsx), which opens
 * the actions dialog and reads/writes the form's hidden actions_json prop.
 * Action control — no stored value of its own.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Form_Actions_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-form-actions'; // MUST match the JS registry key.
	}

	public function get_props(): array {
		return [];
	}
}
