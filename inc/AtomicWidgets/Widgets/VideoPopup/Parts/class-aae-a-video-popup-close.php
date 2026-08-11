<?php
/**
 * AAE Video Popup — Close. The X button inside the Panel that closes the
 * popup. A real, styleable leaf widget — mirrors AAE_A_Offcanvas_Close
 * exactly (own icon prop, inlined SVG, hook class hardcoded in the twig).
 * If the user deletes it, overlay-click + Esc still close the popup.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Video_Popup_Close extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Closes the Video Popup. Seeded inside the Panel; fully styleable via the Style tab.';

	public static function get_element_type(): string {
		return 'e-aae-a-video-popup-close';
	}

	public function get_title() {
		return esc_html__( 'Video Popup Close', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-close';
	}

	public function get_keywords() {
		return [ 'video', 'popup', 'close', 'icon', 'atomic' ];
	}

	public function show_in_panel() {
		return false;
	}

	public function hide_on_search() {
		return true;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Empty by default → Twig falls back to a built-in X glyph.
			'icon' => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/VideoPopup/Parts/assets/icons/close.svg' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items( [
					Svg_Control::bind_to( 'icon' )
						->set_label( __( 'Icon', 'animation-addons-for-elementor' ) ),
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
	 * Chromeless icon button pinned top-right of the Panel. Same
	 * block-flex + auto inline-start-margin trick as AAE_A_Offcanvas_Close
	 * (see that class's docblock for why `align-self` wouldn't work here).
	 */
	protected function define_base_styles(): array {
		$zero = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 'fit-content', 'unit' => 'custom' ] ) )
						->add_prop( 'cursor', String_Prop_Type::generate( 'pointer' ) )
						->add_prop( 'color', Color_Prop_Type::generate( '#ffffff' ) )
						->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ) )
						->add_prop(
							'background',
							Background_Prop_Type::generate( [
								'color' => Color_Prop_Type::generate( 'transparent' ),
							] )
						)
						->add_prop( 'border-width', $zero )
						->add_prop( 'z-index', Size_Prop_Type::generate( [ 'size' => 3, 'unit' => 'custom' ] ) )
						->add_prop(
							'padding',
							Dimensions_Prop_Type::generate( [
								'block-start'  => $zero,
								'block-end'    => $zero,
								'inline-start' => $zero,
								'inline-end'   => $zero,
							] )
						)
						->add_prop(
							'margin',
							Dimensions_Prop_Type::generate( [
								'block-start'  => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
								'block-end'    => $zero,
								'inline-start' => Size_Prop_Type::generate( [ 'size' => 'auto', 'unit' => 'auto' ] ),
								'inline-end'   => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
							] )
						)
				)
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::HOVER )
						->add_prop( 'color', Color_Prop_Type::generate( '#cccccc' ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-popup-close' => __DIR__ . '/aae-a-video-popup-close.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
