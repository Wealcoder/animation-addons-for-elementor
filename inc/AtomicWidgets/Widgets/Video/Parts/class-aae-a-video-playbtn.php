<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Video;

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
 * AAE Video — Play Button. The circular overlay play/pause trigger centred
 * over the video.
 *
 * Its own element type — NOT a reused native e-button — for the same reason
 * Progress Bar's Fill/Track parts exist (see class-aae-a-progressbar-track.
 * php's docblock): a reused native widget has no twig of its own to hardcode
 * a hook class into, so video.js's `.aae-a-video-playbtn` lookup class had
 * to be seeded through the `classes` prop instead — which the panel reports
 * as a "Some classes are missing" alert, whose ✕ silently unapplies the
 * class (see CLAUDE.md's "Never put a functional hook class in the classes
 * prop"). Owning this element type means the hook class is hardcoded
 * directly in THIS class's own twig, so it can never be flagged or dismissed
 * away, and every visual default below is real, editable Style-tab CSS
 * instead of the plugin's own global stylesheet — a user can restyle the
 * circle (or anything else about it) with no custom CSS at all.
 *
 * The icon is a genuine nested Atomic_Svg child (same icon+label pattern
 * AAE_A_Btn's own default children use) so swapping it for any other SVG is
 * just Elementor's native "Choose SVG" control on that child — no bespoke
 * icon-upload plumbing needed here.
 */
class AAE_A_Video_PlayBtn extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'Internal play-button trigger used by the AAE Video widget.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-video-playbtn';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-video-playbtn';
	}

	public function get_title() {
		return esc_html__( 'Video Play Button', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'video', 'play', 'button', 'atomic' ];
	}

	public function get_icon() {
		return 'eicon-play';
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

	/**
	 * Every key here is verified against
	 * atomic-style-schema-reference.md — one invalid key silently voids this
	 * whole method (see that file's "Golden Rule"). `pointer-events` isn't in
	 * the schema at all, so the is-playing fade-out still lives in video.scss
	 * (it also depends on an ANCESTOR's `.is-playing` class, which a
	 * per-element base style can never reach anyway).
	 */
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

			// Matches define_default_children()'s "{element_type}-{key}" naming
			// convention — same one AAE_A_Btn's own icon style key uses.
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
						'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Video/Parts/assets/icons/play.svg' ),
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
			'elementor/elements/aae-a-video-playbtn' => __DIR__ . '/aae-a-video-playbtn.html.twig',
		];
	}
}
