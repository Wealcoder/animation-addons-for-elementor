<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageGallery;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * "Images" element-control for the AAE Image Gallery.
 *
 * Mirrors the Nested Slider's Slides control and the Accordion's Items control:
 * an element-control carries NO stored prop value. It serialises as
 * { type: 'element-control', value: { type } } and the editing panel routes
 * that to the React component registered under the same type id
 * ('aae-gallery-items') in the controls registry
 * (see src/modules/atomic/element-controls/). The component renders one
 * repeater row per real <e-aae-a-image-gallery-item> child of the gallery —
 * the list is a live projection of the element tree (add / drag-reorder /
 * duplicate / remove all act directly on the children).
 */
class AAE_A_Gallery_Items_Control extends Element_Control_Base {

	public function get_type(): string {
		return 'aae-gallery-items';
	}

	public function get_props(): array {
		return [];
	}
}
