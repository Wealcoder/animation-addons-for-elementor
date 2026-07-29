<?php
/**
 * AAE Hotspot Marker — atomic element (container).
 *
 * The clickable marker inside a Hotspot Point. Split out as its own real,
 * selectable element (mirrors AAE_A_Progressbar_Fill / AAE_A_Toggle_
 * Switcher_Tab) specifically so its look — background, color, border-radius,
 * padding, font-size — is editable from Elementor's generic Style tab instead
 * of being hardcoded in image-hotspot.scss.
 *
 * Out of the box it's an EMPTY container — its own base style (background
 * circle, 32x32, border-radius:999px) already reads as a plain "dot" marker
 * with nothing inside, matching the old default before this was rewritten.
 * A builder who wants an icon and/or label drops a real `e-svg`/`e-image`
 * (icon) and/or `e-paragraph` (text) child in directly — no more `hsp_layout`
 * Select control choosing between 5 preset looks. This mirrors
 * AAE_A_Hotspot_Content's own "unrestricted children, builder manages them"
 * philosophy rather than a closed enum, and means icon/text sizing is a
 * normal per-instance Style-tab edit on that REAL child element instead of
 * plugin CSS — there's no API in this codebase (or Elementor core) for
 * seeding a child with a per-instance style override at generate() time, so
 * a builder-added icon starts at that widget's own generic default size and
 * gets resized like any other element, same as everywhere else in this
 * plugin.
 *
 * Auto-numbering (post-pagination-style badges) is now opt-in by class
 * presence rather than a "Number" layout mode: image-hotspot.js's
 * renumber() fills any descendant carrying the `hotspot-number` CSS class
 * (added via Elementor's generic "CSS Classes" field on a builder-added
 * `e-paragraph` child) with its 1-based position among sibling Points.
 *
 * Seeded as the Hotspot Point's first default child; never dragged from the
 * panel directly (should_show_in_panel() => false).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Hotspot_Marker extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'The clickable marker inside a Hotspot Point. Seeded automatically (empty by default); drop in an Icon/Image and/or Paragraph child, fully styleable via the Style tab.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-hotspot-marker';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-hotspot-marker';
	}

	public function get_title() {
		return esc_html__( 'Hotspot Marker', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-point';
	}

	public function get_keywords() {
		return [ 'hotspot', 'marker', 'icon', 'atomic' ];
	}

	protected function should_show_in_panel() {
		return false;
	}

	public function hide_on_search() {
		return true;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// 'inherit' = use the parent Image Hotspot's global marker_anim.
			'marker_anim' => String_Prop_Type::make()
				->enum( [ 'inherit', 'none', 'beat', 'pulse', 'ripple', 'ring', 'glow', 'bounce' ] )
				->default( 'inherit' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'marker_anim' )
						->set_label( __( 'Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'inherit', 'label' => __( 'Inherit', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'none',    'label' => __( 'None', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'beat',    'label' => __( 'Beat', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'pulse',   'label' => __( 'Pulse', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ripple',  'label' => __( 'Ripple', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ring',    'label' => __( 'Ring', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'glow',    'label' => __( 'Glow', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'bounce',  'label' => __( 'Bounce', 'animation-addons-for-elementor' ) ],
						] ),
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

	/**
	 * Universal resting look, fully Style-tab overridable, including
	 * `width`/`height` (default 32px, a square that reads as a plain dot by
	 * default with no children) — a builder who drops in a text child can
	 * widen it from the same Style-tab fields if the default is too narrow
	 * for their label. Elementor's own global reset
	 * (`.elementor * { box-sizing: border-box }`) means width/height already
	 * include padding, so 32px really renders as 32px regardless of the
	 * padding value below.
	 *
	 * `position: relative` also moved here (was plain CSS) — it's the anchor
	 * every marker animation's ::before/::after pseudo-element in
	 * image-hotspot.scss attaches to; nothing about it depends on which
	 * children (if any) are present.
	 */
	protected function define_base_styles(): array {
		$pad = Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] );
		$size = Size_Prop_Type::generate( [ 'size' => 32, 'unit' => 'px' ] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position', String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 5, 'unit' => 'px' ] ) )
						->add_prop( 'cursor', String_Prop_Type::generate( 'pointer' ) )
						->add_prop( 'color', Color_Prop_Type::generate( '#ffffff' ) )
						->add_prop(
							'background',
							Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#000000' ) ] )
						)
						->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 999, 'unit' => 'px' ] ) )
						->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ) )
						->add_prop( 'width', $size )
						->add_prop( 'height', $size )
						->add_prop( 'min-height', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
						->add_prop(
							'padding',
							Dimensions_Prop_Type::generate( [
								'block-start'  => $pad,
								'block-end'    => $pad,
								'inline-start' => $pad,
								'inline-end'   => $pad,
							] )
						)
				),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-svg', 'e-image', 'e-paragraph' ];
	}

	// Empty by default — matches the old 'dot' default (which rendered none
	// of the icon/text/number spans), see class docblock.
	protected function define_default_children() {
		return [];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-hotspot-marker' => __DIR__ . '/aae-a-hotspot-marker.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
