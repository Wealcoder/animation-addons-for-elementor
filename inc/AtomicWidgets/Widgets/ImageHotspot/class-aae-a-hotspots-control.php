<?php
namespace Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ImageHotspot;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Hotspots" element-control for the AAE Image Hotspot.
 *
 * Mirrors Aaeaddon_A_Slides_Control / Aaeaddon_A_Items_Control: an element-control
 * carries NO stored prop value. It serialises as
 * { type: 'element-control', value: { type: 'aae-hotspots' } } and the editing
 * panel routes that to the React component registered under the same type id
 * ('aae-hotspots') in src/modules/atomic/element-controls/index.js. The
 * component renders one repeater row per real <e-aae-a-hotspot-point> DIRECT
 * child of the image-hotspot container — no intermediate "track" element
 * (unlike NestedSlider), so there is no separate repeater data to keep in
 * sync; the list is a live projection of the element tree.
 */
class Aaeaddon_A_Hotspots_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-hotspots';
	}

	public function get_props(): array {
		return [];
	}
}
