<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\SocialShare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;

/**
 * AAE Social Share Item — Title. A plain e-paragraph with its own element
 * type, existing ONLY so it has a stable identity of its own instead of a
 * hook class (`aae-a-social-share-item-label`) stuffed into `classes` — same
 * reasoning as class-aae-a-social-share-item-icon.php. That class carried no
 * base CSS of its own (nothing in social-share.scss ever targeted it); it
 * only ever existed as a selector handle, which this element's own type name
 * now provides for free.
 *
 * define_props_schema(), define_atomic_controls() and define_base_styles()
 * are all inherited from Atomic_Paragraph unchanged — this is still a plain
 * paragraph in every way except its type name and panel visibility.
 */
class AAE_A_Social_Share_Item_Title extends Atomic_Paragraph {

	public static $widget_description = 'Internal label used by the AAE Social Share Item.';

	public static function get_element_type(): string {
		return 'e-aae-a-social-share-item-title';
	}

	public function get_title() {
		return esc_html__( 'Title', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'social', 'share', 'title', 'label', 'atomic' ];
	}

	public function show_in_panel() {
		// Internal sub-element — managed only via its parent Social Share
		// Item, never dragged in independently from the widget panel.
		return false;
	}
}
