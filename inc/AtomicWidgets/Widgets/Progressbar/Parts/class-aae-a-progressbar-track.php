<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Progressbar;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-progressbar-fill.php';

/**
 * AAE Progress Bar — Track. The rounded gray rail the Fill bar sits inside.
 * Its own widget type exists ONLY so it can carry its own fixed look via
 * define_base_styles() — a reused core Div_Block would mean styling every
 * div-block on the site, since base styles are owned by the widget TYPE, not
 * a per-instance override (same reasoning as the AAE Timeline sub-parts).
 */
class AAE_A_Progressbar_Track extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'Internal progress-bar track used by the AAE Progress Bar.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-progressbar-track';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-progressbar-track';
	}

	public function get_title() {
		return esc_html__( 'Progress Bar Track', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'progressbar', 'track' ];
	}

	public function get_icon() {
		return 'eicon-skill-bar';
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

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',       String_Prop_Type::generate( 'block' ) )
						->add_prop( 'width',         Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
						->add_prop( 'height',        Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ) )
						->add_prop( 'background',    Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#e6e4dd' ) ] ) )
						->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 999, 'unit' => 'px' ] ) )
						->add_prop( 'overflow',      String_Prop_Type::generate( 'hidden' ) )
						// Elementor's `.e-con` container framework applies a default
						// inline padding via CSS vars — without an explicit override
						// here it shrinks Fill's 100%-width child inside the track,
						// leaving a gap before the track's rounded edge.
						->add_prop( 'padding', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ) )
				),
		];
	}

	/**
	 * Exposed publicly so the parent Progressbar's define_default_children()
	 * can seed a fresh Track's child directly (mirrors
	 * AAE_A_Timeline_Item::build_default_inner_children()).
	 */
	public static function build_default_inner_children(): array {
		return [
			AAE_A_Progressbar_Fill::generate()
				->editor_settings( [ 'title' => 'Fill' ] )
				// No `classes`: the JS hook `aae-progressbar-fill` comes from the
				// fill's own twig. A hook class in `classes` is reported by the
				// panel as a missing class and can be dismissed away.
				->build(),
		];
	}

	protected function define_default_children() {
		return self::build_default_inner_children();
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-aae-a-progressbar-fill', 'e-aae-a-progressbar-dot', 'e-flexbox', 'e-div-block' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-progressbar-track' => __DIR__ . '/aae-a-progressbar-track.html.twig',
		];
	}
}
