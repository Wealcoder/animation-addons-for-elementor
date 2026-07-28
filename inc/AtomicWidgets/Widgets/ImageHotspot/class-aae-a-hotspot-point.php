<?php
/**
 * AAE Hotspot Point — atomic element (repeating child).
 *
 * One marker positioned over the parent Image Hotspot's canvas. Inserted only
 * via the "Hotspots" element-control (AAE_A_Hotspots_Control) on the parent —
 * never dragged from the panel directly (should_show_in_panel() => false),
 * same convention as AAE_A_Slide.
 *
 * Position is a plain X/Y percent CONTENT prop (pos_left/pos_top), not a Style
 * tab override — this keeps every default point trivially positionable at
 * generate() time (see AAE_A_Image_Hotspot::define_default_children()) without
 * needing per-instance base-style overrides, and leaves room for a future
 * click-to-place editor UX to just write these two numbers.
 *
 * Children are UNRESTRICTED (mirrors AAE_A_Flip_Box, not the Offcanvas Panel
 * whitelist) — they ARE the tooltip/lightbox content, and dropping another
 * e-aae-a-image-hotspot in here is exactly how drill-down hotspots work; no
 * special-case "nested hotspot" code exists anywhere, it's pure composition.
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
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Link_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

class AAE_A_Hotspot_Point extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-hotspot-point';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-hotspot-point';
	}

	public function get_title() {
		return esc_html__( 'Hotspot Point', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-point';
	}

	public function get_keywords() {
		return [ 'hotspot', 'point', 'marker', 'tooltip', 'atomic' ];
	}

	// Inserted only via the parent's "Hotspots" control, never dragged from panel.
	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		$show_if_icon = Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ 'hsp_layout' ],
				'value'    => [ 'icon', 'icon-text' ],
				'effect'   => 'hide',
			] )
			->get();

		$show_if_text = Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ 'hsp_layout' ],
				'value'    => [ 'text', 'icon-text' ],
				'effect'   => 'hide',
			] )
			->get();

		$show_if_link = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'tooltip_type' ],
				'value'    => 'link',
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// X/Y position as plain percentages of the parent canvas.
			'pos_left' => Number_Prop_Type::make()->default( 50 ),
			'pos_top'  => Number_Prop_Type::make()->default( 50 ),

			// Marker layout — 'number' is the auto-badge option (JS fills the
			// digit from DOM order; nothing is computed server-side, so it stays
			// correct across drag-reorders in the Hotspots control).
			'hsp_layout' => String_Prop_Type::make()
				->enum( [ 'dot', 'icon', 'text', 'icon-text', 'number' ] )
				->default( 'dot' ),

			'hsp_icon' => Svg_Src_Prop_Type::make()
				->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/ImageHotspot/assets/icons/dot.svg' )
				->set_dependencies( $show_if_icon ),

			'hsp_text' => String_Prop_Type::make()
				->default( __( 'Hotspot', 'animation-addons-for-elementor' ) )
				->set_dependencies( $show_if_text ),

			// tooltip = inline popover, lightbox = teleported modal (new),
			// link = marker becomes a plain <a>, no popup.
			'tooltip_type' => String_Prop_Type::make()
				->enum( [ 'tooltip', 'lightbox', 'link' ] )
				->default( 'tooltip' ),

			'tlp_link'          => Link_Prop_Type::make()->set_dependencies( $show_if_link ),
			'tlp_link_nofollow' => Boolean_Prop_Type::make()->default( false )->set_dependencies( $show_if_link ),

			// Per-point override of the container's global marker_anim.
			'marker_anim_override' => String_Prop_Type::make()
				->enum( [ 'inherit', 'none', 'beat', 'pulse', 'ripple', 'ring', 'glow', 'bounce' ] )
				->default( 'inherit' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'position' )
				->set_label( __( 'Position', 'animation-addons-for-elementor' ) )
				->set_items( [
					Number_Control::bind_to( 'pos_left' )
						->set_label( __( 'Horizontal (%)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'pos_top' )
						->set_label( __( 'Vertical (%)', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Marker', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'hsp_layout' )
						->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'dot',       'label' => __( 'Dot', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'icon',      'label' => __( 'Icon', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'text',      'label' => __( 'Text', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'icon-text', 'label' => __( 'Icon + Text', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'number',    'label' => __( 'Number', 'animation-addons-for-elementor' ) ],
						] ),
					Svg_Control::bind_to( 'hsp_icon' )
						->set_label( __( 'Icon', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'hsp_text' )
						->set_label( __( 'Text', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'marker_anim_override' )
						->set_label( __( 'Marker Animation', 'animation-addons-for-elementor' ) )
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
				->set_id( 'interaction' )
				->set_label( __( 'Interaction', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'tooltip_type' )
						->set_label( __( 'On Click/Hover', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'tooltip',  'label' => __( 'Tooltip', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'lightbox', 'label' => __( 'Lightbox', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'link',     'label' => __( 'Link', 'animation-addons-for-elementor' ) ],
						] ),
					Link_Control::bind_to( 'tlp_link' )
						->set_label( __( 'Link URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'tlp_link_nofollow' )
						->set_label( __( 'Add Nofollow', 'animation-addons-for-elementor' ) ),
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
	 * Structural only — visual defaults (marker look, tooltip placement, portal
	 * geometry) live in assets/scss/image-hotspot.scss, since they're shared
	 * across every point and driven by data-attrs, not per-element style props.
	 *
	 * width/height/display are set EXPLICITLY (fit-content via the custom-unit
	 * trick, inline-flex) rather than left unset — Elementor's own `.e-con`
	 * class (present in every atomic container's class list, see the twig)
	 * otherwise stretches this element to 100% of its containing block. Since
	 * the tooltip content is `position: absolute` relative to THIS element,
	 * an unset width silently made the point as wide as the whole image, which
	 * re-centered the tooltip under the image's middle instead of the marker
	 * (the `left: 50%` in image-hotspot.scss was 50% of the wrong box).
	 */
	protected function define_base_styles(): array {
		$fit_content = Size_Prop_Type::generate( [ 'size' => 'fit-content', 'unit' => 'custom' ] );

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position', String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
						->add_prop( 'width', $fit_content )
						->add_prop( 'height', $fit_content )
				),
		];
	}

	protected function define_default_children() {
		return [
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Tooltip Content' ] )
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( __( 'Tooltip content', 'animation-addons-for-elementor' ) ),
						'children' => [],
					] ),
				] )
				->build(),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-hotspot-point' => __DIR__ . '/aae-a-hotspot-point.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
