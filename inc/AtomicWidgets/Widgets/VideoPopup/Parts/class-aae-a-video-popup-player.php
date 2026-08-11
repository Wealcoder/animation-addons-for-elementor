<?php

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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Video Popup — Player. The actual video engine mounted inside the
 * popup Panel: a single mount point plus the custom controls bar — own,
 * self-contained copy of AAE_A_Video_Player (see that class's docblock for
 * the full engine reasoning). Deliberately its own class/type rather than
 * reusing AAE_A_Video_Player directly: see
 * class-aae-a-video-popup-panel.php's docblock for why the two widget
 * families never share a Part.
 *
 * Owns NO source/playback settings — those live on the parent Panel, which
 * emits them as data-aae-video-* attributes on the shared `.aae-a-video`
 * wrapper. video-popup.js binds its own frontend handler to that wrapper
 * and reads this part's mount div + controls bar as descendants.
 */
class AAE_A_Video_Popup_Player extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Internal video engine used by the Video Popup widget — mounts YouTube/Vimeo/hosted playback and the custom controls bar.';

	public static function get_element_type(): string {
		return 'e-aae-a-video-popup-player';
	}

	public function get_title() {
		return esc_html__( 'Video Popup Player', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'video', 'popup', 'player', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	public function show_in_panel() {
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
					Style_Variant::make()->add_props( [
						'display'  => String_Prop_Type::generate( 'block' ),
						'position' => String_Prop_Type::generate( 'relative' ),
						'width'    => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
						'height'   => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-popup-player' => __DIR__ . '/aae-a-video-popup-player.html.twig',
		];
	}
}
