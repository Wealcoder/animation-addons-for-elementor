<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Video Popup — Play Button. The circular overlay play/pause trigger
 * centred over the video inside the Panel. Own, self-contained copy of
 * AAE_A_Video_PlayBtn (see that class's docblock for the full reasoning on
 * why this is a real element type rather than a reused native e-button).
 * Deliberately its own class/type — see class-aae-a-video-popup-panel.php's
 * docblock for why this widget family never shares a Part with AAE Video.
 */
class AAE_A_Video_Popup_PlayBtn extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'Internal play-button trigger used by the Video Popup widget.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-video-popup-playbtn';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-video-popup-playbtn';
	}

	public function get_title() {
		return esc_html__( 'Video Popup Play Button', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'video', 'popup', 'play', 'button', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-play';
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
		$auto = Size_Prop_Type::generate( [ 'size' => 'auto', 'unit' => 'auto' ] );
		$zero = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'position'           => String_Prop_Type::generate( 'absolute' ),
					'inset-block-start'  => $zero,
					'inset-inline-end'   => $zero,
					'inset-block-end'    => $zero,
					'inset-inline-start' => $zero,
					'margin'             => Dimensions_Prop_Type::generate( [
						'block-start'  => $auto,
						'inline-end'   => $auto,
						'block-end'    => $auto,
						'inline-start' => $auto,
					] ),
					'width'           => Size_Prop_Type::generate( [ 'size' => 64, 'unit' => 'px' ] ),
					'height'          => Size_Prop_Type::generate( [ 'size' => 64, 'unit' => 'px' ] ),
					'z-index'         => Number_Prop_Type::generate( 2 ),
					'border-radius'   => Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ),
					'background'      => Background_Prop_Type::generate( [
						'color' => Color_Prop_Type::generate( 'rgba(255, 255, 255, 0.85)' ),
					] ),
					'display'         => String_Prop_Type::generate( 'flex' ),
					'align-items'     => String_Prop_Type::generate( 'center' ),
					'justify-content' => String_Prop_Type::generate( 'center' ),
					'cursor'          => String_Prop_Type::generate( 'pointer' ),
				] ) ),

			'icon' => Style_Definition::make()
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'width'  => Size_Prop_Type::generate( [ 'size' => 22, 'unit' => 'px' ] ),
					'height' => Size_Prop_Type::generate( [ 'size' => 22, 'unit' => 'px' ] ),
				] ) ),
		];
	}

	protected function define_default_children() {
		$icon_class = static::get_element_type() . '-icon';

		return [
			Atomic_Svg::generate()
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ $icon_class ] ),
					'svg'     => Svg_Src_Prop_Type::generate( [
						'id'  => null,
						'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/VideoPopup/Parts/assets/icons/play.svg' ),
					] ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-svg' ];
	}

	protected function define_default_html_tag() {
		return 'button';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-popup-playbtn' => __DIR__ . '/aae-a-video-popup-playbtn.html.twig',
		];
	}
}
