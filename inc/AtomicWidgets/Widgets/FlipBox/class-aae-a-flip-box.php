<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\FlipBox;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
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
 * baked into a preset (see Widgets/FlipBox/presets/) as fixed flip_effect /
 * flip_back_hidden / flip_3d props on this element plus real,
 * natively-styleable front/back containers.
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
		return esc_html__( 'Flip Box', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-flip-box';
	}

	public function get_keywords(): array {
		return [ 'flip', 'box', 'card', 'hover', 'atomic', 'animation', 'preset' ];
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
			// Snapshot of this element's own full model (JSON), captured by the
			// JS preset-apply engine the first time a preset is applied — see
			// preset-apply.js's SNAPSHOT_REVERT_TYPES / "Reset to Default".
			'aae_preset_snapshot' => String_Prop_Type::make()->default( '' ),

			// The flip variant, as DATA. flip-box.scss keys its mechanics off
			// plain hook classes (flip-box-animate-*, flip-box--back-hidden,
			// flip-box--3d), and those used to ride in `classes` — but the
			// editor resolves every entry of `classes` to a local or global
			// style definition, so a raw CSS class made it warn "Some classes
			// are missing" on the element, and dismissing that alert calls
			// unapplyClasses() — silently stripping the hook and leaving a
			// dead, unflippable card on the published page. The twig turns
			// these props back into the same classes at render time, so the
			// markup is unchanged and the panel stays clean.
			//
			// 'left' keeps a freshly dropped box flipping out of the box,
			// matching the very-basic reference design — a preset swaps it
			// for right/up/down/zoom-in/zoom-out/fade-in.
			'flip_effect'      => String_Prop_Type::make()->default( 'left' ),
			'flip_back_hidden' => Boolean_Prop_Type::make()->default( false ),
			'flip_3d'          => Boolean_Prop_Type::make()->default( false ),

			'classes'    => Classes_Prop_Type::make()->default( [] ),
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

	public function get_style_depends(): array {
		return [ 'aae-a-flip-box-css' ];
	}
}
