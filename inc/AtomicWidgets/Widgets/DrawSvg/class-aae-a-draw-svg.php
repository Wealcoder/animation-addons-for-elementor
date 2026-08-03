<?php
/**
 * AAE DrawSVG — atomic (v4) leaf widget.
 *
 * v4 rewrite of the v3 `wcf-gsap-drawsvg` widget: draws an SVG's paths with GSAP
 * DrawSVGPlugin, optionally on scroll (ScrollTrigger), per-path, with the same
 * from/to/method/ease/duration/delay/yoyo/repeat/scrub options and an optional
 * wrapper link. Runtime config travels on data-attributes the handler reads.
 * Styling (size) is done from the Style panel; stroke colour has its own control.
 *
 * @package AnimationAddonsForElementor
 * @since   1.5.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\DrawSvg;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Textarea_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Link_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Draw_Svg extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-draw-svg';
	}

	public function get_title() {
		return esc_html__( 'DrawSVG', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-animation';
	}

	public function get_keywords() {
		return [ 'draw', 'svg', 'gsap', 'animation', 'scroll', 'atomic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-draw-svg-js' ];
	}

	protected static function define_props_schema(): array {
		// Show the SVG Code field only for the "SVG Code" type, and the Choose SVG
		// field only for the "SVG Image" type. In this Elementor version a
		// dependency's `where` is the SHOW condition (the `hide` effect applies when
		// it does NOT match), so each field's condition points at its own type value.
		$show_for_code = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'svg_type' ],
				'value'    => 'svg_code',
				'effect'   => 'hide',
			] )
			->get();
		$show_for_image = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'svg_type' ],
				'value'    => 'svg_image',
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'          => Classes_Prop_Type::make()->default( [] ),
			'attributes'       => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Content.
			'svg_type'         => String_Prop_Type::make()->enum( [ 'svg_code', 'svg_image' ] )->default( 'svg_code' ),
			'svg_image'        => Svg_Src_Prop_Type::make()->set_dependencies( $show_for_image ),
			'svg_code'         => String_Prop_Type::make()->default( self::default_svg() )->set_dependencies( $show_for_code ),

			// Animation.
			'animation_method' => String_Prop_Type::make()->enum( [ 'fromTo', 'from', 'to' ] )->default( 'from' ),
			'animation_from'   => String_Prop_Type::make()->default( '0%' ),
			'animation_to'     => String_Prop_Type::make()->default( '100%' ),
			'aae_duration'     => Number_Prop_Type::make()->default( 2 ),
			'aae_delay'        => Number_Prop_Type::make()->default( 0.5 ),
			'aae_ease'         => String_Prop_Type::make()->default( 'sine.inOut' ),
			'enable_yoyo'      => Boolean_Prop_Type::make()->default( false ),
			'aae_repeat_count' => Number_Prop_Type::make()->default( -1 ),
			'repeatDelay'      => Number_Prop_Type::make()->default( 0.5 ),

			// ScrollTrigger.
			'scroll_trigger'   => Boolean_Prop_Type::make()->default( false ),
			'trigger_start'    => String_Prop_Type::make()->default( 'top 75%' ),
			'trigger_end'      => String_Prop_Type::make()->default( 'bottom 0%' ),
			'scrub'            => String_Prop_Type::make()->enum( [ 'false', 'true', 'number' ] )->default( 'false' ),
			'scrub_number'     => Number_Prop_Type::make()->default( 1 ),

			// Linkable.
			'aae_svg_linkable' => Boolean_Prop_Type::make()->default( false ),
			'aae_website_link' => Link_Prop_Type::make(),

			// Style.
			'svg_stroke'       => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-draw-play-control.php';

		return [
			Section::make()
				->set_id( 'aae_drawsvg_content' )
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'svg_type' )
						->set_label( __( 'SVG Type', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'svg_code',  'label' => __( 'SVG Code', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'svg_image', 'label' => __( 'SVG Image', 'animation-addons-for-elementor' ) ],
						] ),
					Svg_Control::bind_to( 'svg_image' )
						->set_label( __( 'Choose SVG', 'animation-addons-for-elementor' ) ),
					Textarea_Control::bind_to( 'svg_code' )
						->set_label( __( 'SVG Code', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'aae_drawsvg_animation' )
				->set_label( __( 'Animation', 'animation-addons-for-elementor' ) )
				->set_items( [
					AAE_A_Draw_Play_Control::make()
						->set_label( __( 'Play Animation', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
					Select_Control::bind_to( 'animation_method' )
						->set_label( __( 'Animation Method', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'fromTo', 'label' => 'FromTo' ],
							[ 'value' => 'from',   'label' => 'From' ],
							[ 'value' => 'to',     'label' => 'To' ],
						] ),
					Text_Control::bind_to( 'animation_from' )
						->set_label( __( 'From', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'animation_to' )
						->set_label( __( 'To (End)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'aae_duration' )
						->set_label( __( 'Duration (s)', 'animation-addons-for-elementor' ) )
						->set_min( 0 )
						->set_max( 10 ),
					Number_Control::bind_to( 'aae_delay' )
						->set_label( __( 'Delay (s)', 'animation-addons-for-elementor' ) )
						->set_min( 0 )
						->set_max( 5 ),
					Select_Control::bind_to( 'aae_ease' )
						->set_label( __( 'Easing', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'none',         'label' => 'None' ],
							[ 'value' => 'linear',       'label' => 'Linear' ],
							[ 'value' => 'power1.inOut', 'label' => 'Power1 InOut' ],
							[ 'value' => 'power2.inOut', 'label' => 'Power2 InOut' ],
							[ 'value' => 'elastic.out',  'label' => 'Elastic Out' ],
							[ 'value' => 'sine.inOut',   'label' => 'Sine InOut' ],
							[ 'value' => 'sine.in',      'label' => 'Sine In' ],
							[ 'value' => 'expo.inOut',   'label' => 'Expo InOut' ],
							[ 'value' => 'quad.inOut',   'label' => 'Quad InOut' ],
							[ 'value' => 'circ.inOut',   'label' => 'Circ InOut' ],
						] ),
					Switch_Control::bind_to( 'enable_yoyo' )
						->set_label( __( 'Enable Yoyo', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'aae_repeat_count' )
						->set_label( __( 'Repeat (-1 = infinite)', 'animation-addons-for-elementor' ) )
						->set_min( -1 )
						->set_max( 5 ),
					Number_Control::bind_to( 'repeatDelay' )
						->set_label( __( 'Repeat Delay (s)', 'animation-addons-for-elementor' ) )
						->set_min( 0 )
						->set_max( 5 ),
				] ),

			Section::make()
				->set_id( 'aae_drawsvg_scroll' )
				->set_label( __( 'ScrollTrigger', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'scroll_trigger' )
						->set_label( __( 'Enable ScrollTrigger', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'trigger_start' )
						->set_label( __( 'Trigger Start', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'trigger_end' )
						->set_label( __( 'Trigger End', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'scrub' )
						->set_label( __( 'Scrub', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'false',  'label' => __( 'False', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'true',   'label' => __( 'True', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'number', 'label' => __( 'Custom', 'animation-addons-for-elementor' ) ],
						] ),
					Number_Control::bind_to( 'scrub_number' )
						->set_label( __( 'Scrub Number', 'animation-addons-for-elementor' ) )
						->set_min( 0 )
						->set_max( 10 ),
				] ),

			Section::make()
				->set_id( 'aae_drawsvg_link' )
				->set_label( __( 'Linkable', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'aae_svg_linkable' )
						->set_label( __( 'Enable Link', 'animation-addons-for-elementor' ) ),
					Link_Control::bind_to( 'aae_website_link' )
						->set_label( __( 'Link', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'aae_drawsvg_style' )
				->set_label( __( 'Stroke', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'svg_stroke' )
						->set_label( __( 'Stroke Color (e.g. #EB5E66)', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-draw-svg' => __DIR__ . '/aae-a-draw-svg.html.twig',
		];
	}

	/**
	 * Resolve the SVG markup (code or fetched image), the link data and the stroke
	 * colour, and expose them to the Twig template. Runs server-side only.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		// SVG markup.
		$svg_markup = '';
		if ( ( $settings['svg_type'] ?? 'svg_code' ) === 'svg_code' ) {
			$svg_markup = isset( $settings['svg_code'] ) ? $settings['svg_code'] : '';
		} else {
			$url = '';
			$img = $settings['svg_image'] ?? null;
			if ( is_array( $img ) ) {
				if ( ! empty( $img['url'] ) ) {
					$url = $img['url'];
				} elseif ( ! empty( $img['src'] ) ) {
					$url = is_array( $img['src'] ) ? ( $img['src']['url'] ?? '' ) : $img['src'];
				}
			}
			if ( $url ) {
				$fetched = @file_get_contents( $url ); // phpcs:ignore
				if ( is_string( $fetched ) && false !== strpos( $fetched, '<svg' ) ) {
					$svg_markup = $fetched;
				}
			}
		}
		$settings['svg_markup'] = $svg_markup;

		// Link data (shape-tolerant extraction).
		$link_url = '';
		$link_ext = false;
		$link_nf  = false;
		$link     = $settings['aae_website_link'] ?? null;
		if ( is_array( $link ) ) {
			$link_url = $link['url'] ?? ( is_array( $link['destination'] ?? null ) ? ( $link['destination']['url'] ?? '' ) : ( $link['destination'] ?? '' ) );
			$link_ext = ! empty( $link['is_external'] );
			$link_nf  = ! empty( $link['nofollow'] );
		} elseif ( is_string( $link ) ) {
			$link_url = $link;
		}
		$settings['link_url']      = is_string( $link_url ) ? $link_url : '';
		$settings['link_external'] = $link_ext;
		$settings['link_nofollow'] = $link_nf;

		// Stroke colour (may be an array/string depending on prop resolution).
		$stroke = $settings['svg_stroke'] ?? '';
		if ( is_array( $stroke ) ) {
			$stroke = $stroke['value'] ?? '';
		}
		$settings['stroke_color'] = is_string( $stroke ) ? $stroke : '';

		return $settings;
	}

	/** A small default SVG (single stroked path) so the widget draws + is visible on drop. */
	private static function default_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 120" width="400" height="120" fill="none" style="max-width:100%;height:auto;">'
			. '<path d="M10,90 C60,10 120,10 170,90 C220,170 280,170 330,90 C355,50 380,50 390,80" '
			. 'stroke="#EB5E66" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>'
			. '</svg>';
	}
}
