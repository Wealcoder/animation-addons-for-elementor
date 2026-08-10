<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Video;

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
 * AAE Video — Player. The actual video engine: a single mount point plus the
 * custom controls bar, working identically for YouTube, Vimeo, a hosted URL,
 * or a Media Library upload — and easy to extend with another provider later
 * (see assets/js/video.js's adapter pattern). Its own widget type exists so
 * it renders as a real, independent element (a plain Div_Block/e-image reuse
 * would give it no seam of its own — see class-aae-a-progressbar-track.php
 * for the same reasoning applied to Progress Bar's parts).
 *
 * It owns NO source/playback settings itself — those all live on the parent
 * AAE_A_Video, which emits them as data-aae-video-* attributes on the shared
 * wrapper. assets/js/video.js binds ONE frontend handler to the PARENT
 * element type and reads this part's mount div + controls bar as descendants
 * (exactly how VideoMask's JS reaches its internal button child), so this
 * class stays a "dumb" rendering surface: mount div + controls markup only.
 */
class AAE_A_Video_Player extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Internal video engine used by the AAE Video widget — mounts YouTube/Vimeo/hosted playback and the custom controls bar.';

	public static function get_element_type(): string {
		return 'e-aae-a-video-player';
	}

	public function get_title() {
		return esc_html__( 'Video Player', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'video', 'player' ];
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	public function show_in_panel() {
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
			'elementor/elements/aae-a-video-player' => __DIR__ . '/aae-a-video-player.html.twig',
		];
	}
}
