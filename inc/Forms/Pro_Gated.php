<?php
/**
 * Makes a form widget's PANEL CARD locked while the site has no Pro licence.
 *
 * One method, because Elementor already does the rest. `is_editable()` is read
 * by Element_Base::get_initial_config() (includes/base/element-base.php:1516),
 * which is what Atomic_Element_Base's own get_initial_config() calls up to, so
 * returning false puts `editable: false` in the widget config and then:
 *
 *   - views/element.js `onRender()` returns BEFORE it wires html5Draggable and
 *     click-to-add, so the card cannot be dragged into the canvas;
 *   - the card template prints a corner lock icon, so it reads as Pro at a
 *     glance without hiding what the widget is;
 *   - mousedown dispatches `widget-promotion:open`, and Elementor's promotions
 *     app opens an upgrade card carrying the copy Pro_Gate supplies.
 *
 * A TRAIT, not a base class: these eight widgets already extend two different
 * Elementor bases (Atomic_Widget_Base for the leaf fields, Atomic_Element_Base
 * for Step), so there is no single parent to put this on.
 *
 * WHAT THIS DOES NOT DO, and it is the reason it is safe: `editable` is read
 * ONLY under assets/dev/js/editor/regions/panel/pages/elements/ — the widget
 * PANEL LIST. Nothing in an element's own view or editing panel consults it.
 * An instance already on the canvas, dropped by a preset import or built while
 * the site was licensed, stays selectable and editable exactly as before. That
 * is deliberate: it must carry a Pro notice, not become unreachable, and its
 * settings must keep saving so a lapse never costs the customer their work.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Pro_Gated {

	public function is_editable() {
		return ! Pro_Gate::is_locked();
	}
}
