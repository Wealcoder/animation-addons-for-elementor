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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
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
 *
 * The controls bar/progress/time/button "look" (background, spacing, size,
 * colour) is exposed here as extra named Style_Definition keys — Style tab
 * editable, same `{element_type}-{key}` convention as the Trigger/PlayBtn
 * "icon" key — so builders no longer need video-popup.scss for it. What
 * CANNOT move here stays in the parent widget's inline stylesheet (see
 * class-aae-a-video-popup.php's docblock): the rotator's `@keyframes`, any
 * rule that reveals one element off a DIFFERENT element's `:hover`/state
 * (atomic Style_Variant states only ever target the widget's own root), and
 * styling for `.aae-a-video-popup-native`/`-embed` — those two don't exist in
 * any twig, video-popup.js creates them at runtime.
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
		$zero = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		return [
			// The mount point itself — `position: absolute` + full inset, NOT
			// `relative`: video-popup.scss's `.aae-a-video-popup-source
			// .aae-a-video-popup-mount { position: absolute; inset: 0; }`
			// carried specificity (0,2,0) against this class's own (0,1,0),
			// so it always won and `relative` never actually took effect —
			// Mount has to fill the Panel from behind Close/PlayBtn (both
			// themselves absolutely positioned), or it renders as a normal-
			// flow box pushed below Close and overflowing the Panel's fixed
			// height. Written honestly now instead of relying on a higher-
			// specificity override to correct it. Also adds the dark fill
			// video-popup.scss set on `.aae-a-video-popup-mount`.
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'            => String_Prop_Type::generate( 'block' ),
						'position'           => String_Prop_Type::generate( 'absolute' ),
						'inset-block-start'  => $zero,
						'inset-inline-end'   => $zero,
						'inset-block-end'    => $zero,
						'inset-inline-start' => $zero,
						'width'              => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
						'height'             => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
						'background'         => Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( '#000000' ),
						] ),
					] )
				),

			// Controls bar. Opacity (hover/is-playing reveal) stays in the
			// parent widget's inline stylesheet — it depends on the Panel's
			// own state classes, which no atomic Style_Variant can target.
			'controls' => Style_Definition::make()
				->set_label( __( 'Controls Bar', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'position'           => String_Prop_Type::generate( 'absolute' ),
					'inset-inline-start' => $zero,
					'inset-inline-end'   => $zero,
					'inset-block-end'    => $zero,
					'z-index'            => Number_Prop_Type::generate( 3 ),
					'display'            => String_Prop_Type::generate( 'flex' ),
					'align-items'        => String_Prop_Type::generate( 'center' ),
					'gap'                => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
					'padding'            => Dimensions_Prop_Type::generate( [
						'block-start'  => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'block-end'    => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'inline-start' => Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
						'inline-end'   => Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
					] ),
					'background' => Background_Prop_Type::generate( [
						'color' => Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.75)' ),
					] ),
				] ) ),

			// Progress/seek track. The 6px hover-grow is a plain self-hover,
			// so — unlike the reveal states above — it fits an atomic
			// Style_Variant directly; no residual CSS needed for it.
			'progress' => Style_Definition::make()
				->set_label( __( 'Progress Track', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'position'      => String_Prop_Type::generate( 'relative' ),
					'flex'          => Flex_Prop_Type::generate( [
						'flexGrow'   => Number_Prop_Type::generate( 1 ),
						'flexShrink' => Number_Prop_Type::generate( 1 ),
						'flexBasis'  => Size_Prop_Type::generate( [ 'size' => 'auto', 'unit' => 'auto' ] ),
					] ),
					'height'        => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
					'border-radius' => Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ),
					'background'    => Background_Prop_Type::generate( [
						'color' => Color_Prop_Type::generate( 'rgba(255, 255, 255, 0.35)' ),
					] ),
					'cursor' => String_Prop_Type::generate( 'pointer' ),
				] ) )
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::HOVER )
						->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ) )
				),

			'progress-fill' => Style_Definition::make()
				->set_label( __( 'Progress Fill', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'position'           => String_Prop_Type::generate( 'absolute' ),
					'inset-block-start'  => $zero,
					'inset-block-end'    => $zero,
					'inset-inline-start' => $zero,
					'width'              => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => '%' ] ),
					'border-radius'      => Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ),
					'background'         => Background_Prop_Type::generate( [
						'color' => Color_Prop_Type::generate( '#ffffff' ),
					] ),
				] ) ),

			'time' => Style_Definition::make()
				->set_label( __( 'Time', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'flex'      => Flex_Prop_Type::generate( [
						'flexGrow'   => Number_Prop_Type::generate( 0 ),
						'flexShrink' => Number_Prop_Type::generate( 0 ),
						'flexBasis'  => Size_Prop_Type::generate( [ 'size' => 'auto', 'unit' => 'auto' ] ),
					] ),
					'font-size' => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
					'color'     => Color_Prop_Type::generate( '#ffffff' ),
				] ) ),

			// Shared by the playpause/mute/fullscreen buttons — they've
			// always rendered as one visually-unified group under a single
			// class, so one Style_Definition key styling all three matches
			// existing behaviour rather than changing it.
			'btn' => Style_Definition::make()
				->set_label( __( 'Button', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'flex'            => Flex_Prop_Type::generate( [
						'flexGrow'   => Number_Prop_Type::generate( 0 ),
						'flexShrink' => Number_Prop_Type::generate( 0 ),
						'flexBasis'  => Size_Prop_Type::generate( [ 'size' => 'auto', 'unit' => 'auto' ] ),
					] ),
					'display'         => String_Prop_Type::generate( 'flex' ),
					'align-items'     => String_Prop_Type::generate( 'center' ),
					'justify-content' => String_Prop_Type::generate( 'center' ),
					'width'           => Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ),
					'height'          => Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ),
					'border-width'    => $zero,
					'padding'         => Dimensions_Prop_Type::generate( [
						'block-start'  => $zero,
						'block-end'    => $zero,
						'inline-start' => $zero,
						'inline-end'   => $zero,
					] ),
					'background' => Background_Prop_Type::generate( [
						'color' => Color_Prop_Type::generate( 'transparent' ),
					] ),
					'color'  => Color_Prop_Type::generate( '#ffffff' ),
					'cursor' => String_Prop_Type::generate( 'pointer' ),
				] ) )
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::HOVER )
						->add_prop( 'opacity', Size_Prop_Type::generate( [ 'size' => 80, 'unit' => '%' ] ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-popup-player' => __DIR__ . '/aae-a-video-popup-player.html.twig',
		];
	}
}
