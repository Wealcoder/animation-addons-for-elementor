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
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;

require_once __DIR__ . '/Parts/class-aae-a-toggle-pane-title.php';
require_once __DIR__ . '/Parts/class-aae-a-toggle-pane-desc.php';

/**
 * AAE Toggle Pane — an open, unlocked content pane meant to live inside
 * AAE_A_Toggle_Switcher. Shown/hidden by toggle-switcher.js purely via the
 * .aae-ts-pane marker class baked into its own template (position in the
 * DOM decides before/after pane, same as ToggleSwitcherMain's pane pair) —
 * no locked props, restyle from this pane's own Style panel exactly like
 * the AAE Btn wrapper pattern. Its default title/description children are
 * each a dedicated small widget type (AAE_A_Toggle_Pane_Title/_Desc)
 * carrying real typography via their own define_base_styles() — see
 * class-aae-a-toggle-pane-title.php for why plain e-heading/e-paragraph
 * reuse can't express that.
 */
class AAE_A_Toggle_Pane extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-toggle-pane';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-pane';
	}

	public function get_title() {
		return esc_html__( 'Toggle Pane', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-inner-section';
	}

	public function get_keywords() {
		return [ 'toggle', 'pane', 'content', 'atomic' ];
	}

	public function should_show_in_panel() {
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
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [] ),
		];
	}

	/**
	 * No layout props needed here — the Switcher's own flex-column layout
	 * stacks Tabs/Panes directly; unlike the old flat labels+switch+panes
	 * structure this pane no longer sits inside a flex-wrap row of siblings.
	 */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-aae-a-toggle-pane-title', 'e-aae-a-toggle-pane-desc', 'e-heading', 'e-paragraph', 'e-svg' ];
	}

	/**
	 * Exposed publicly so the parent Switcher's define_default_children() can
	 * seed each fresh pane's title/description directly (mirrors
	 * AAE_A_Timeline_Item::build_default_inner_children()).
	 */
	public static function build_default_inner_children(
		string $title = 'Pane Title',
		string $desc = 'Add your content here.'
	): array {
		return [
			AAE_A_Toggle_Pane_Title::generate()
				->editor_settings( [ 'title' => 'Title' ] )
				->settings( [
					'text' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $title ),
						'children' => [],
					] ),
				] )
				->build(),

			AAE_A_Toggle_Pane_Desc::generate()
				->editor_settings( [ 'title' => 'Description' ] )
				->settings( [
					'text' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $desc ),
						'children' => [],
					] ),
				] )
				->build(),
		];
	}

	protected function define_default_children(): array {
		return self::build_default_inner_children();
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-pane' => __DIR__ . '/aae-a-toggle-pane.html.twig',
		];
	}
}
