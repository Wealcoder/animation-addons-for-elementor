<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Sub;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Nav_Item extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-nav-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-nav-item';
	}

	public function get_title() {
		return esc_html__( 'Nav Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'      => Classes_Prop_Type::make()->default( [] ),
			'attributes'   => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'has_dropdown'        => Boolean_Prop_Type::make()->default( false ),
			'trigger'             => String_Prop_Type::make()->default( 'click' ),
			'dropdown_animation'  => String_Prop_Type::make()->default( 'gsap' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
				->set_items( [
					Switch_Control::bind_to( 'has_dropdown' )
						->set_label( __( 'Enable Dropdown', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'trigger' )
						->set_label( __( 'Trigger', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'click', 'label' => __( 'Click', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'hover', 'label' => __( 'Hover', 'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'dropdown_animation' )
						->set_label( __( 'Dropdown Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'gsap',         'label' => __( 'Default (GSAP)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'grow-down',    'label' => __( 'Grow Down', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'rotate-3d',    'label' => __( 'Rotate 3D', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'grow-out',     'label' => __( 'Grow Out', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide-items',  'label' => __( 'Slide Items', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'rotate-items', 'label' => __( 'Rotate Items', 'animation-addons-for-elementor' ) ],
						] ),
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [];
	}

	protected function define_default_children() {
		$label = Atomic_Paragraph::generate()
			->settings( [
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( 'Menu Item' ),
					'children' => [],
				] ),
				'tag' => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		/* Only the label ships by default. The nav-sub is attached via the
		 * parent (AAE_A_Nav) for the one example nav-item that demonstrates
		 * dropdowns. Auto-shipping a nav-sub on every nav-item doubled the
		 * child count (25 elements/widget) which made device-mode switches
		 * hang the editor as every child re-rendered + the validator walked
		 * the (then-cyclic) allowed-child-types tree. */
		return [ $label ];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-heading', 'e-paragraph', 'e-svg', 'e-aae-a-nav-sub' ];
		// nav-sub IS allowed here (one-way), but nav-sub does NOT allow nav-item
		// back — that's the cycle-break that fixes the device-switch hang.
	}

	protected function define_default_html_tag() {
		return 'li';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-nav-item' => __DIR__ . '/aae-a-nav-item.html.twig',
		];
	}
}
