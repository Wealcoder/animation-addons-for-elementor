<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancePortfolio;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Presets" element-control for the Advanced Portfolio.
 *
 * Identical in shape to every other widget's copy (see
 * Widgets/LoopGrid/class-aae-a-preset-picker-control.php for the full write-up):
 * an element-control stores NO prop of its own, serialises as
 * { type: 'element-control', value: { type } }, and the panel routes it to the
 * React component registered under 'aae-preset-picker'. The preset list is
 * global (window.AAE_WIDGET_PRESETS), keyed by element type, so nothing needs
 * passing from PHP.
 *
 * This is the seam the nine v3 skins arrive through: each becomes a preset for
 * `e-aae-a-advance-portfolio`, chosen here.
 */
class AAE_A_Portfolio_Preset_Picker_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-preset-picker';
	}

	public function get_props(): array {
		return [];
	}
}
