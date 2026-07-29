<?php
/**
 * AAE Hotspot Content — atomic element (container).
 *
 * The tooltip/lightbox BOX for a Hotspot Point — a real, styleable container
 * (background, color, padding, width, border-radius, position, z-index all
 * live in define_base_styles() below, editable via the Style tab) instead of
 * a plain hardcoded <div> in image-hotspot.scss.
 *
 * Unrestricted children (mirrors AAE_A_Flip_Box) — this is exactly where a
 * builder drops the tooltip/lightbox body, including another
 * e-aae-a-image-hotspot for drill-down hotspots.
 *
 * Deliberately has NO prop of its own describing whether it's acting as an
 * inline tooltip or a teleported lightbox — that's the PARENT Hotspot Point's
 * `tooltip_type` prop, which a child element's own twig can't read. The base
 * style below defaults to the TOOLTIP geometry (position: absolute); lightbox
 * mode overrides position/z-index/etc. via a higher-specificity selector in
 * image-hotspot.scss keyed off the ANCESTOR Point's own
 * `data-aae-hotspot-mode="lightbox"` attribute — the override selector
 * (`.aae-hotspot-point[data-aae-hotspot-mode="lightbox"] .aae-hotspot-content`)
 * is 0,3,0 vs. the base style's own 0,2,0, so it reliably wins regardless of
 * stylesheet order. Actual VISIBILITY (opacity/visibility/pointer-events,
 * toggled by the `.active` class on open/close) can't move here at all —
 * no Style_Variant/Style_States mechanism expresses "look when a runtime-
 * toggled custom class is present", same reasoning as ToggleSwitcher's
 * `.show`/`.active` staying in toggle-switcher.scss.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/Parts/class-aae-a-hotspot-close.php';

use WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Close;

class AAE_A_Hotspot_Content extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-hotspot-content';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-hotspot-content';
	}

	public function get_title() {
		return esc_html__( 'Hotspot Content', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-content';
	}

	public function get_keywords() {
		return [ 'hotspot', 'tooltip', 'lightbox', 'content', 'atomic' ];
	}

	public function should_show_in_panel() {
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
	 * The shared, resting DECORATIVE look — used whether this box ends up an
	 * inline tooltip or a teleported lightbox. See the class docblock for the
	 * exact position/z-index override mechanics and why opacity/visibility
	 * can't move here at all.
	 */
	protected function define_base_styles(): array {
		$pad = Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] );

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position', String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'z-index', Number_Prop_Type::generate( 20 ) )
						->add_prop(
							'background',
							Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#e8e8e8' ) ] )
						)
						->add_prop( 'color', Color_Prop_Type::generate( '#222222' ) )
						->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 280, 'unit' => 'px' ] ) )
						->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
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

	protected function define_default_children() {
		return [
			AAE_A_Hotspot_Close::generate()
				->editor_settings( [ 'title' => 'Close' ] )
				->build(),

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
			'elementor/elements/aae-a-hotspot-content' => __DIR__ . '/aae-a-hotspot-content.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
