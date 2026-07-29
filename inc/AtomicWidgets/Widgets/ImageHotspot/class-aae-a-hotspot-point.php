<?php
/**
 * AAE Hotspot Point — atomic element (repeating child).
 *
 * One marker+content pair positioned over the parent Image Hotspot's canvas.
 * Inserted only via the "Hotspots" element-control (AAE_A_Hotspots_Control)
 * on the parent — never dragged from the panel directly
 * (should_show_in_panel() => false), same convention as AAE_A_Slide.
 *
 * Position is a plain X/Y percent CONTENT prop (pos_left/pos_top), not a
 * Style tab override — this keeps every default point trivially positionable
 * at generate() time (see AAE_A_Image_Hotspot::define_default_children())
 * without needing per-instance base-style overrides, and leaves room for a
 * future click-to-place editor UX to just write these two numbers.
 *
 * The MARKER's own look (background/color/radius/padding/size) and the
 * CONTENT box's own look (background/padding/width/radius) each live on
 * their OWN real child element (AAE_A_Hotspot_Marker / AAE_A_Hotspot_Content)
 * — split out specifically so a builder can select either part in the
 * Navigator and restyle it from Elementor's generic Style tab, instead of
 * those looks being hardcoded in image-hotspot.scss. This element itself is
 * ALWAYS a plain <div> (role="button"/"link" + tabindex, never a real
 * <button>/<a>) — Content, its other child, always contains a real <button>
 * (Close) and may contain arbitrary user content including links/buttons;
 * nesting that inside a real interactive tag is invalid HTML and real
 * browsers auto-close the outer tag on parse, which silently reparented
 * Close + the tooltip body onto the page as trailing siblings on the
 * frontend (invisible in the editor, which builds the DOM via direct JS
 * calls rather than parsing an HTML string). image-hotspot.js wires click +
 * Enter/Space keyboard activation onto this div instead.
 *
 * Content's children are UNRESTRICTED (mirrors AAE_A_Flip_Box) — they ARE
 * the tooltip/lightbox content, and dropping another e-aae-a-image-hotspot
 * in there is exactly how drill-down hotspots work; no special-case "nested
 * hotspot" code exists anywhere, it's pure composition.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Link_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

require_once __DIR__ . '/Parts/class-aae-a-hotspot-marker.php';
require_once __DIR__ . '/class-aae-a-hotspot-content.php';

use WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Marker;
use WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Content;

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

			// tooltip = inline popover, lightbox = teleported modal, link =
			// this element itself renders as a real <a>, no popup at all.
			'tooltip_type' => String_Prop_Type::make()
				->enum( [ 'tooltip', 'lightbox', 'link' ] )
				->default( 'tooltip' ),

			'tlp_link'          => Link_Prop_Type::make()->set_dependencies( $show_if_link ),
			'tlp_link_nofollow' => Boolean_Prop_Type::make()->default( false )->set_dependencies( $show_if_link ),
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
	 * Structural only. width/height/display are set EXPLICITLY (fit-content
	 * via the custom-unit trick, inline-flex) rather than left unset —
	 * Elementor's own `.e-con` class otherwise stretches this element to
	 * 100% of its containing block, which re-centers the Content box (its
	 * `left: 50%` in image-hotspot.scss is 50% of THIS box) under the whole
	 * image instead of under the marker.
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
						->add_prop( 'cursor', String_Prop_Type::generate( 'pointer' ) )
				),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Hotspot_Marker::generate()
				->editor_settings( [ 'title' => 'Marker' ] )
				->build(),

			AAE_A_Hotspot_Content::generate()
				->editor_settings( [ 'title' => 'Content' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-hotspot-marker', 'e-aae-a-hotspot-content' ];
	}

	/**
	 * Publishes this Point's OWN `tooltip_type` down the Render_Context stack
	 * so Content — which can't read an ancestor's props in its own Twig —
	 * can render `data-aae-hotspot-mode` on ITSELF, server-side, at first
	 * paint. Same mechanism AAE_A_Post_Pagination already uses to hand
	 * prev/next post data down to its Prev/Next children.
	 *
	 * This replaces relying on image-hotspot.js to copy the mode onto
	 * Content after the fact: that copy only exists once the script has run,
	 * so on a fresh page load lightbox Content briefly had NO hiding rule
	 * matching at all (its own `data-aae-hotspot-mode` attribute didn't
	 * exist yet) and flashed visible in its normal in-flow position for a
	 * frame before JS caught up. Server-rendering it removes that window
	 * entirely — Content carries the attribute from the very first byte of
	 * HTML, same guarantee Point's own (already server-rendered) attribute
	 * always had.
	 *
	 * @see \Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context
	 */
	protected function define_render_context(): array {
		$settings = $this->get_atomic_settings();

		return [
			[
				'context_key' => self::class,
				'context'     => [
					'tooltip_type' => isset( $settings['tooltip_type'] ) ? $settings['tooltip_type'] : 'tooltip',
				],
			],
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
