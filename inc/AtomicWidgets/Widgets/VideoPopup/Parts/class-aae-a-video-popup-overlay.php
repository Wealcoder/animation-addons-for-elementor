<?php
/**
 * AAE Video Popup — Overlay. The dimming backdrop behind the popup. A real,
 * styleable leaf widget (not plain markup) so builders get a full Style-tab
 * Background → Color picker — mirrors AAE_A_Offcanvas_Overlay exactly.
 * video-popup.js finds it by the `.aae-video-popup-overlay` hook class,
 * teleports it to the body portal, and drives its fade in/out.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Video_Popup_Overlay extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'The Video Popup backdrop. Set its colour from the Style tab (Background → Color).';

	public static function get_element_type(): string {
		return 'e-aae-a-video-popup-overlay';
	}

	public function get_title() {
		return esc_html__( 'Video Popup Overlay', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-square';
	}

	public function get_keywords() {
		return [ 'video', 'popup', 'overlay', 'backdrop', 'scrim', 'atomic' ];
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
	 * The only default: a dimming scrim colour, matching the V3 reference's
	 * own `rgba(11, 11, 11, 0.9)`. Position/size/visibility are applied at
	 * runtime by video-popup.js after it teleports the overlay to the
	 * viewport portal, so they are deliberately NOT base styles.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop(
							'background',
							Background_Prop_Type::generate( [
								'color' => Color_Prop_Type::generate( 'rgba(11, 11, 11, 0.9)' ),
							] )
						)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-popup-overlay' => __DIR__ . '/aae-a-video-popup-overlay.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
