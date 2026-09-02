<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Toggle Switcher — Tablist. A bare `role="tablist"` wrapper for the
 * Switch-style preset (Track + two Labels), which — unlike the default
 * Tabs/Tab pairing (see class-aae-a-toggle-switcher-tabs.php) — has no
 * dedicated container of its own and was being laid out in a plain
 * `e-flexbox`. That left the two `role="tab"` Labels (see
 * class-aae-a-toggle-switcher-label.php) with no `tablist` ancestor —
 * axe's aria-required-parent failure.
 *
 * Deliberately carries NO base styles (unlike Tabs, whose border-bottom +
 * bottom margin are baked in for the underlined-text look): this element
 * exists purely to supply the ARIA role. All layout (display:flex, gap,
 * etc.) is expected to keep living on this element's own per-instance
 * local style, exactly as it did when it was a plain e-flexbox — swapping
 * the element type preserves that style, only the semantics change.
 *
 * Not used by the root Switcher's role="tablist": that was tried first and
 * reverted — the outer Switcher wrapper also contains the Panes
 * (role="tabpanel"), and axe's aria-required-children rule does not allow
 * a tablist to have non-tab children, even indirectly. Scoping the role to
 * a wrapper around ONLY the tab-like Labels avoids that entirely.
 */
class AAE_A_Toggle_Switcher_Tablist extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-toggle-switcher-tablist';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher-tablist';
	}

	public function get_title() {
		return esc_html__( 'Toggle Switcher — Tablist', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'tablist', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function should_show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_allowed_child_types() {
		return [
			'widget',
			'e-aae-a-toggle-switcher-label',
			'e-aae-a-toggle-switcher-track',
			'e-flexbox',
			'e-div-block',
		];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher-tablist' => __DIR__ . '/aae-a-toggle-switcher-tablist.html.twig',
		];
	}
}
