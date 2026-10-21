<?php
namespace Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Shortcut buttons (Hamburger / Overlay / Drawer) that select those parts of the
 * mobile menu in the editor. Stores no prop; editor-only, see
 * MobileNavPartsControl in NavItemsControl.jsx.
 */
class Aaeaddon_A_Mobile_Nav_Parts_Control extends Element_Control_Base {
	public function get_type(): string { return 'aae-mobile-nav-parts'; }
	public function get_props(): array { return []; }
}
