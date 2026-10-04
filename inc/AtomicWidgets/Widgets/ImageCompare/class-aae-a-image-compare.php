<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageCompare;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Divider\Atomic_Divider;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Div_Block\Div_Block;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Image Compare — a draggable before/after slider whose before image,
 * after image, divider, handle and labels are independent atomic children,
 * each styleable from its own Style panel.
 *
 * Preset-driven by design: this class owns only the root's base style, and a
 * "Presets" picker supplies the whole layout (see define_base_styles() and
 * define_default_children() below for what that costs and how it's handled).
 *
 * It once had a sibling, Image Compare Main, that carried per-child compound
 * selectors; that widget is gone (only a historical note in class-atomic.php
 * remains). Don't reintroduce references to it.
 */
class AAE_A_Image_Compare extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A draggable before/after image comparison slider with ready-made horizontal/vertical presets. Before image, after image, divider, handle, and labels are independent atomic children — each styleable from its own Style panel.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-image-compare';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-image-compare';
	}

	public function get_title() {
		return esc_html__( 'Image Compare', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'image', 'compare', 'before', 'after', 'slider', 'template' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	/**
	 * Panel category for the Elements panel.
	 *
	 * Atomic_Element_Base reads the panel category from HERE — get_categories()
	 * is Widget_Base's hook and is never called for an element type, so a
	 * category declared only there silently falls back to Elementor's own
	 * 'v4-elements' ("Atomic Elements") bucket. Delegate so both stay in sync.
	 */
	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	public function get_icon() {
		return 'eicon-image-before-after';
	}

	protected static function define_props_schema(): array {
		return [
			// Snapshot of this element's own full model (JSON), captured by the
			// JS preset-apply engine the first time a preset is applied — see
			// preset-apply.js's SNAPSHOT_REVERT_TYPES / "Reset to Default".
			'aae_preset_snapshot' => String_Prop_Type::make()->default( '' ),

			'classes'           => Classes_Prop_Type::make()->default( [] ),
			'attributes'        => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'direction'         => String_Prop_Type::make()->default( 'horizontal' ),
			'default_position'  => Number_Prop_Type::make()->default( 50 ),
			'enable_click_move' => Boolean_Prop_Type::make()->default( true ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items( [
					AAE_A_Preset_Picker_Control::make()
						->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_label( __( 'Image Compare', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'direction' )
						->set_label( __( 'Direction', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'label' => __( 'Horizontal', 'animation-addons-for-elementor' ), 'value' => 'horizontal' ],
							[ 'label' => __( 'Vertical',   'animation-addons-for-elementor' ), 'value' => 'vertical' ],
						] ),
					Number_Control::bind_to( 'default_position' )
						->set_label( __( 'Initial Position (%)', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 0, 'max' => 100, 'step' => 1 ] ),
					Switch_Control::bind_to( 'enable_click_move' )
						->set_label( __( 'Enable Click to Move', 'animation-addons-for-elementor' ) ),
				] ),

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
	 * Root wrapper only. Unlike Image Compare Main, this wrapper does NOT
	 * carry per-child compound selectors — before/after image, captions,
	 * divider, and handle positioning all live as each preset's own local
	 * element styles instead (see Widgets/ImageCompare/presets/*.json),
	 * exactly like Btn/SocialShare. Only the CSS-var-driven live position,
	 * the sibling-combinator hover effect, and the vendor pseudo-elements —
	 * things the v4 style schema genuinely can't express — live in
	 * assets/scss/image-compare.scss.
	 */
	protected function define_base_styles(): array {
		$size_100_pct = Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] );

		$zero_px = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',   String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'overflow',   String_Prop_Type::generate( 'hidden' ) )
						->add_prop( 'width',      $size_100_pct )
						->add_prop( 'max-width',  $size_100_pct )
						->add_prop( 'display',    String_Prop_Type::generate( 'grid' ) )
						// The active Kit's default container padding (`.e-con`,
						// commonly 10px a side) otherwise leaks onto this widget's
						// root. That only shrinks the in-flow After image's
						// content box (605px of a 625px box, verified live) —
						// the absolutely positioned Before/divider/thumb/captions
						// use `inset:0`, whose containing block is the PADDING
						// box, so they ignore it and span the full 625px. Same
						// mismatch in both directions; explicit padding:0 makes
						// every child agree on the same box.
						->add_prop( 'padding',    $zero_px )
						->add_prop( 'min-height', Size_Prop_Type::generate( [ 'size' => 200, 'unit' => 'px' ] ) )
				),
		];
	}

	/**
	 * The seed tree a preset replaces. It is NOT a usable fallback on its own:
	 * these are plain e-image/e-divider/e-paragraph children with no layout of
	 * their own (define_base_styles() above styles only the root), so rendered
	 * bare they stack vertically. The design lives entirely in presets/*.json,
	 * and the editor auto-applies one on drop — see AUTO_PRESETS in
	 * src/modules/atomic/editor-bridge/auto-preset.js.
	 *
	 * Every child carries the `aae-ic-default` marker class, which that
	 * watcher reads as its `defaultMarker`. The preset JSONs seed the exact
	 * same 6 widget types (image/image/divider/div-block/paragraph/paragraph),
	 * so the watcher cannot tell "untouched default" from "just got the preset
	 * applied" by shape alone. Without the marker it would re-apply the preset
	 * to its own output forever, since the newly-created element gets a new id
	 * and is never in `handled`.
	 */
	protected function define_default_children() {
		return [
			Atomic_Image::generate()
				->editor_settings( [ 'title' => 'Before Image' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-before', 'aae-ic-default' ] ),
					'image'   => Image_Prop_Type::generate( [
						'src'  => Image_Src_Prop_Type::generate( [
							'id'  => null,
							'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/ImageCompare/assets/image/before.jpg' ),
						] ),
						'size' => String_Prop_Type::generate( 'large' ),
					] ),
				] )
				->build(),

			Atomic_Image::generate()
				->editor_settings( [ 'title' => 'After Image' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-after', 'aae-ic-default' ] ),
					'image'   => Image_Prop_Type::generate( [
						'src'  => Image_Src_Prop_Type::generate( [
							'id'  => null,
							'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/ImageCompare/assets/image/after.jpg' ),
						] ),
						'size' => String_Prop_Type::generate( 'large' ),
					] ),
				] )
				->build(),

			Atomic_Divider::generate()
				->editor_settings( [ 'title' => 'Divider' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-divider', 'aae-ic-default' ] ),
				] )
				->build(),

			Div_Block::generate()
				->editor_settings( [ 'title' => 'Handle' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-thumb', 'aae-ic-default' ] ),
				] )
				->children( [
					Atomic_Svg::generate()
						->editor_settings( [ 'title' => 'Handle Icon' ] )
						->settings( [
							'classes' => Classes_Prop_Type::generate( [ 'aae-a-svg-30' ] ),
							'svg'     => Svg_Src_Prop_Type::generate( [
								'id'  => null,
								'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/ImageCompare/assets/icon/handle.svg' ),
							] ),
						] )
						->build(),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Before Label' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-image-compare-caption-before', 'aae-ic-default' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Before' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'After Label' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-image-compare-caption-after', 'aae-ic-default' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'After' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-image', 'e-paragraph', 'e-button', 'e-divider', 'e-div-block', 'e-svg' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-image-compare' => __DIR__ . '/aae-a-image-compare.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-image-compare-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-image-compare-css' ];
	}
}
