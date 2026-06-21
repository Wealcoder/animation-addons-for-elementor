<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-toggle-pane.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Toggle_Switcher extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-toggle-switcher';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-toggle-switcher';
	}

	public function get_title() {
		return esc_html__( 'AAE Toggle Switcher', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function get_keywords() {
		return [ 'toggle', 'switch', 'tabs', 'atomic', 'switcher' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'         => Classes_Prop_Type::make()->default( [] ),
			'attributes'      => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'ts_style'        => String_Prop_Type::make()->enum( [ '1', '2' ] )->default( '1' ),
			'ts_label_before' => String_Prop_Type::make()->default( 'Monthly' ),
			'ts_label_after'  => String_Prop_Type::make()->default( 'Yearly' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Toggle Switcher', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'ts_style' )
						->set_label( __( 'Style', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '1', 'label' => __( 'Style One (Switch)', 'animation-addons-for-elementor' ) ],
							[ 'value' => '2', 'label' => __( 'Style Two (Label Highlight)', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'ts_label_before' )
						->set_label( __( 'Label Before', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'ts_label_after' )
						->set_label( __( 'Label After', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'display'        => String_Prop_Type::generate( 'flex' ),
					'flex-direction' => String_Prop_Type::generate( 'column' ),
					'width'          => String_Prop_Type::generate( '100%' ),
				] ) ),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Toggle_Pane::generate()
				->editor_settings( [ 'title' => 'Pane 1' ] )
				->build(),
			AAE_A_Toggle_Pane::generate()
				->editor_settings( [ 'title' => 'Pane 2' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-toggle-pane' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toggle-switcher' => __DIR__ . '/aae-a-toggle-switcher.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-toggle-switcher-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-toggle-switcher-css' ];
	}
}
