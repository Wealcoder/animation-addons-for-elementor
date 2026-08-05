<?php
/**
 * PHP stub for the `aae-inline-text` panel control.
 *
 * Carries no behaviour — it only names a control type and ships its props. The
 * component is registered client-side in
 * src/modules/atomic/element-controls/index.js and implemented in
 * InlineTextControl.jsx; read that file's docblock for WHY this exists rather
 * than core's Inline_Editing_Control (short version: core's panel control
 * renders no toolbar, and the toolbar it does ship is bolted to a hardcoded
 * list of five core element types we can never join).
 *
 * Bind it to an Html_V3_Prop_Type prop — the control reads and writes the
 * html-v3 envelope, so binding it to a plain string prop yields an empty box.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Controls\Base\Atomic_Control_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Controls\Base\Atomic_Control_Base;

class AAE_Inline_Text_Control extends Atomic_Control_Base {

	private ?string $placeholder = null;

	public function get_type(): string {
		return 'aae-inline-text';
	}

	public function set_placeholder( string $placeholder ): self {
		$this->placeholder = $placeholder;

		return $this;
	}

	public function get_props(): array {
		return [
			'placeholder' => $this->placeholder,
		];
	}
}
