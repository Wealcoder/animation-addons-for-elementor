<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\FlipBox;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

use WCF_ADDONS\AtomicWidgets\Widgets\FlipBox\AAE_A_Flip_Box_Front;
use WCF_ADDONS\AtomicWidgets\Widgets\FlipBox\AAE_A_Flip_Box_Back;

require_once __DIR__ . '/Parts/class-aae-a-flip-box-front.php';
require_once __DIR__ . '/Parts/class-aae-a-flip-box-back.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AAE Flip Box — an open hover-flip card. No flip_type/show_back_face
 * controls: each design (direction, 3D depth, single- vs double-sided) is
 * baked into a preset (see Widgets/FlipBox/presets/) as a fixed hook class
 * on this element plus real, natively-styleable front/back containers.
 *
 * The default front/back faces are each a dedicated sub-widget
 * (AAE_A_Flip_Box_Front/_Back, Widgets/FlipBox/Parts/) carrying real
 * background/color/radius/padding via their own define_base_styles() — a
 * reused e-flexbox can't express that (base styles are owned by the widget
 * TYPE, not a per-instance override; see the AAE Timeline sub-parts for the
 * same reasoning). The flip's 3D mechanics (position, backface-visibility,
 * hover-driven rotate) still live in flip-box.scss, since the atomic style
 * schema has no backface-visibility key and can't express a parent-hover
 * affecting a descendant's transform.
 */
class AAE_A_Flip_Box extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-flip-box';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-flip-box';
	}

	public function get_title(): string {
		return esc_html__( 'AAE Flip Box', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-flip-box';
	}

	public function get_keywords(): array {
		return [ 'flip', 'box', 'card', 'hover', 'atomic', 'animation', 'preset' ];
	}

	protected static function define_props_schema(): array {
		return [
			// flip-box-animate-left makes a freshly dropped box actually flip
			// out of the box, matching the very-basic reference design — a
			// preset can still swap this for -right/-up/-down/etc.
			'classes'    => Classes_Prop_Type::make()->default( [ 'flip-box-animate-left' ] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items( [
					AAE_A_Preset_Picker_Control::make()
						->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
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
					'display'  => String_Prop_Type::generate( 'block' ),
					'width'    => Size_Prop_Type::generate( [ 'size' => 300, 'unit' => 'px' ] ),
					'height'   => Size_Prop_Type::generate( [ 'size' => 200, 'unit' => 'px' ] ),
					'position' => String_Prop_Type::generate( 'relative' ),
					'overflow' => String_Prop_Type::generate( 'hidden' ),
				] ) ),
		];
	}

	/**
	 * Default drop-in content: a dedicated Front/Back face pair, each
	 * seeded with its own Title/Text children — see AAE_A_Flip_Box_Front /
	 * AAE_A_Flip_Box_Back for the styling. Presets can still replace this
	 * subtree wholesale with plain e-flexbox faces.
	 */
	protected function define_default_children(): array {
		return [
			AAE_A_Flip_Box_Front::generate()
				->editor_settings( [ 'title' => 'Front Face' ] )
				->children( AAE_A_Flip_Box_Front::build_default_inner_children() )
				->build(),

			AAE_A_Flip_Box_Back::generate()
				->editor_settings( [ 'title' => 'Back Face' ] )
				->children( AAE_A_Flip_Box_Back::build_default_inner_children() )
				->build(),
		];
	}

	// No allowed-child-types whitelist — a non-empty list makes the
	// editor's drag-drop gate strict and can silently block AAE atomic
	// widgets not in it. Returning the base default (allow all) matches
	// Advanced Heading's open-container convention.

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-flip-box' => __DIR__ . '/aae-a-flip-box.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-flip-box-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-flip-box-css' ];
	}
}
