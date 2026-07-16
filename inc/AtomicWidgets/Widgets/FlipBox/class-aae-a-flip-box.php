<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\FlipBox;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Heading\Atomic_Heading;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Flexbox\Flexbox;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AAE Flip Box — an open hover-flip card. No flip_type/show_back_face
 * controls: each design (direction, 3D depth, single- vs double-sided) is
 * baked into a preset (see Widgets/FlipBox/presets/) as a fixed hook class
 * on this element plus real, natively-styleable front/back containers.
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
					'width'    => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					'height'   => Size_Prop_Type::generate( [ 'size' => 300, 'unit' => 'px' ] ),
					'position' => String_Prop_Type::generate( 'relative' ),
					'overflow' => String_Prop_Type::generate( 'hidden' ),
				] ) ),
		];
	}

	/**
	 * Default drop-in content: two plain flexbox faces, no bespoke child
	 * widget — presets restyle/replace these natively, same as Btn's
	 * default paragraph+svg children.
	 */
	protected function define_default_children(): array {
		return [
			$this->make_face( 'flip-box-front', 'Front Title', 'This is front side content.', 'Front Face' ),
			$this->make_face( 'flip-box-back', 'Back Title', 'This is back side content.', 'Back Face' ),
		];
	}

	private function make_face( string $side_class, string $title, string $content, string $editor_title ) {
		return Flexbox::generate()
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ $side_class ] ),
			] )
			->editor_settings( [ 'title' => $editor_title ] )
			->children( [
				Atomic_Heading::generate()
					->settings( [
						'title' => Html_V3_Prop_Type::generate( [
							'content'  => String_Prop_Type::generate( $title ),
							'children' => [],
						] ),
						'tag' => String_Prop_Type::generate( 'h2' ),
					] )
					->build(),

				Atomic_Paragraph::generate()
					->settings( [
						'paragraph' => Html_V3_Prop_Type::generate( [
							'content'  => String_Prop_Type::generate( $content ),
							'children' => [],
						] ),
					] )
					->build(),
			] )
			->build();
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
