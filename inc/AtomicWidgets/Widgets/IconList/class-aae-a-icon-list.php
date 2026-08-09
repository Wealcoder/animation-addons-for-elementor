<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\IconList;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-icon-list-item.php';
use WCF_ADDONS\AtomicWidgets\Widgets\IconList\AAE_A_Icon_List_Item;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Icon_List extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-icon-list';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-icon-list';
	}

	public function get_title() {
		return esc_html__( 'Icon List', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	public function get_keywords() {
		return [ 'list', 'icon', 'bullet', 'atomic', 'nested' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	/**
	 * Panel category for the Elements panel.
	 *
	 * Atomic_Element_Base reads the panel category from HERE — get_categories()
	 * is Widget_Base's hook and is never called for an element type, so a
	 * category declared only there silently falls back to Elementor's own
	 * 'v4-elements' ("Atomic Elements") bucket. Delegate so both stay in sync.
	 */
	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'list_tag' => String_Prop_Type::make()->enum( [ 'ul', 'ol' ] )->default( 'ul' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'layout_settings' )
				->set_label( __( 'List Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'list_tag' )
						->set_label( __( 'List Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'ul', 'label' => __( 'Unordered List (ul)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ol', 'label' => __( 'Ordered List (ol)', 'animation-addons-for-elementor' ) ],
						] ),
				] ),
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

	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display' => String_Prop_Type::generate( 'flex' ),
			'flex-direction' => String_Prop_Type::generate( 'column' ),
			'align-items' => String_Prop_Type::generate( 'flex-start' ),
			// Each item is now a chip with its own background — without a gap
			// here, stacked chips would touch edge to edge with no separation.
			'gap' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 15, 'unit' => 'px' ] ),
			'list-style' => String_Prop_Type::generate( 'none' ),
			'width' => String_Prop_Type::generate( '100%' ),
			'margin' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
				'block-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'block-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
			]),
			'padding' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
				'block-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'block-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
			]),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	/**
	 * Three ordinary, real-world-labeled items — a usable starting point,
	 * not three copies of the same placeholder text (was "List Item Text"
	 * ×3) or the same generic Elementor icon. Same idea as
	 * AAE_A_Social_Share::define_default_children() seeding
	 * "Facebook"/"Twitter"/"LinkedIn" instead of one repeated label.
	 */
	protected function define_default_children() {
		$defaults = [
			'Fast & Reliable Performance' => 'bolt',
			'Easy to Customize'           => 'sliders',
			'24/7 Customer Support'       => 'headset',
		];

		$children = [];
		foreach ( $defaults as $label => $icon ) {
			$children[] = AAE_A_Icon_List_Item::generate()
				->editor_settings( [ 'title' => $label ] )
				->children( AAE_A_Icon_List_Item::build_default_inner_children( $label, $icon ) )
				->build();
		}

		return $children;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-icon-list-item' ];
	}

	protected function define_default_html_tag() {
		return 'ul';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-icon-list' => __DIR__ . '/aae-a-icon-list.html.twig',
		];
	}

	/**
	 * Was `[]` — icon-list.css was registered (class-atomic.php's registry
	 * has a style_handle/style_path for both this class and the item) but
	 * never actually enqueued on any page, since this is the method Elementor
	 * calls to know what to auto-load for a used element. Declaring it here
	 * is enough for the item too: they share the same handle, and an item
	 * only ever renders as this element's child.
	 */
	public function get_style_depends(): array {
		return [ 'aae-a-icon-list-css' ];
	}

	/**
	 * Inline CSS that Atomic::fix_frontend_atomic_css_order() injects right
	 * after this widget's own stylesheet, once that stylesheet is guaranteed
	 * to load after Elementor's base-desktop.css.
	 *
	 * Same pattern as AAE_A_Btn::get_frontend_css_override() /
	 * AAE_A_Social_Share::get_frontend_css_override(): `.e-svg-base` compiles
	 * as `.elementor .e-svg-base` (specificity 0,2,0) with a native 65px
	 * default, so a plain `.e-aae-a-icon-list-item-icon{width:...}` rule
	 * (0,1,0) can never win regardless of load order — it needs the matching
	 * `.elementor` ancestor to even tie, and this mechanism is what then
	 * guarantees the tie is broken in our favor.
	 */
	public static function get_frontend_css_override(): string {
		return sprintf(
			'.elementor .e-aae-a-icon-list-item-icon{width:%1$dpx;height:%1$dpx}',
			AAE_A_Icon_List_Item::ICON_SIZE_PX
		);
	}
}
