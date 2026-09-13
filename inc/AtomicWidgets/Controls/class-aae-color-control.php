<?php
/**
 * AAE Color Control — a colour picker on the CONTENT tab.
 *
 * Elementor's atomic panel ships no colour control for the Content tab: every
 * picker it has lives in the Style tab, bound to a Style_Definition, and the
 * Style tab reaches only an element's own root class. A part whose look is
 * three things (a slider's rail, fill and thumb) therefore had no way to be
 * coloured at all — the Style tab can paint one box, and none of those three is
 * the box.
 *
 * This binds Elementor's OWN `ColorControl` component (from
 * `@elementor/editor-controls`, registered under `aae-color` by
 * `src/modules/atomic/element-controls/index.js`) to a `Color_Prop_Type`, so
 * the value is the same `{ $$type: 'color', value: '#hex' }` a style colour is
 * and the picker is the one builders already know. The twig then decides what
 * the colour paints — normally by emitting a CSS custom property the
 * stylesheet reads with a fallback, so an untouched control changes nothing.
 *
 *   Color_Prop_Type::make()->default( '' )            // schema
 *   AAE_Color_Control::bind_to( 'rail_color' )->set_label( … )   // panel
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Controls;

use Elementor\Modules\AtomicWidgets\Controls\Base\Atomic_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_Color_Control extends Atomic_Control_Base {

	private ?string $placeholder = null;

	public function get_type(): string {
		return 'aae-color';
	}

	public function set_placeholder( string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function get_props(): array {
		return array(
			'placeholder' => $this->placeholder,
		);
	}
}
