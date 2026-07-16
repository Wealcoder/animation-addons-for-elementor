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
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Button\Atomic_Button;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Divider\Atomic_Divider;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Image_Compare extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A draggable before/after image comparison slider. Before image, after image, divider, handle, and labels are independent atomic children — each styleable from its own Style panel.';

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
		return esc_html__( 'AAE Image Compare', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'image', 'compare', 'before', 'after', 'slider' ];
	}

	public function get_icon() {
		return 'eicon-image-before-after';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'           => Classes_Prop_Type::make()->default( [] ),
			'attributes'        => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'direction'         => String_Prop_Type::make()->default( 'horizontal' ),
			'default_position'  => Number_Prop_Type::make()->default( 50 ),
			'enable_click_move' => Boolean_Prop_Type::make()->default( true ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
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
	 * Per-element base styles, keyed off the child class names. Compound
	 * selectors (`base .child-class`) emit nested CSS scoped to the widget's
	 * auto-generated base class, so each rule travels with the element
	 * definition — no external SCSS/CSS file required.
	 *
	 * Properties not in the v4 style schema (pointer-events, user-select,
	 * grid-area, ::-webkit-* pseudo-elements, sibling combinators like
	 * `.range:hover ~ .thumb`, and CSS-variable-driven `left`) are silently
	 * dropped by Render_Props_Resolver, so those rules stay in the inline
	 * <style> block of the Twig template alongside the markup that needs
	 * them. Everything that fits the schema lives here.
	 */
	protected function define_base_styles(): array {
		$size_100_pct  = Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] );
		$size_50_pct   = Size_Prop_Type::generate( [ 'size' => 50,  'unit' => '%' ] );
		$size_0_px     = Size_Prop_Type::generate( [ 'size' => 0,   'unit' => 'px' ] );
		$size_16_px    = Size_Prop_Type::generate( [ 'size' => 16,  'unit' => 'px' ] );
		$size_1_em     = Size_Prop_Type::generate( [ 'size' => 1,   'unit' => 'em' ] );
		$caption_clip  = String_Prop_Type::generate( 'inset(0 calc(100% - var(--aae-image-compare-position, 50%)) 0 0)' );

		return [
			// Parent container.
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',   String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'overflow',   String_Prop_Type::generate( 'hidden' ) )
						->add_prop( 'width',      $size_100_pct )
						->add_prop( 'max-width',  $size_100_pct )
						->add_prop( 'display',    String_Prop_Type::generate( 'grid' ) )
						->add_prop( 'min-height', Size_Prop_Type::generate( [ 'size' => 200, 'unit' => 'px' ] ) )
				),

			// AFTER image — natural flow, defines the widget's height.
			// `height: auto` can't go through Size_Prop_Type (no keyword
			// support) so it lives in the Twig <style> block instead.
			self::BASE_STYLE_KEY . ' .aae-a-image-compare-after' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',        String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'width',           $size_100_pct )
						->add_prop( 'max-width',       $size_100_pct )
						->add_prop( 'display',         String_Prop_Type::generate( 'block' ) )
						->add_prop( 'object-fit',      String_Prop_Type::generate( 'cover' ) )
						->add_prop( 'object-position', String_Prop_Type::generate( '0 50%' ) )
						->add_prop( 'margin',          $size_0_px )
						->add_prop( 'z-index',         Number_Prop_Type::generate( 1 ) )
				),

			// BEFORE image — absolutely positioned overlay, clipped by handle %.
			self::BASE_STYLE_KEY . ' .aae-a-image-compare-before' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',           String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'inset-block-start',  $size_0_px )
						->add_prop( 'inset-inline-start', $size_0_px )
						->add_prop( 'width',              $size_100_pct )
						->add_prop( 'max-width',          $size_100_pct )
						->add_prop( 'height',             $size_100_pct )
						->add_prop( 'display',            String_Prop_Type::generate( 'block' ) )
						->add_prop( 'object-fit',         String_Prop_Type::generate( 'cover' ) )
						->add_prop( 'object-position',    String_Prop_Type::generate( '0 50%' ) )
						->add_prop( 'margin',             $size_0_px )
						->add_prop( 'z-index',            Number_Prop_Type::generate( 2 ) )
						->add_prop( 'clip-path',          $caption_clip )
				),

			// BEFORE caption.
			self::BASE_STYLE_KEY . ' .aae-a-image-compare-caption-before' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',           String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'inset-block-start',  $size_16_px )
						->add_prop( 'inset-inline-start', $size_16_px )
						->add_prop( 'z-index',            Number_Prop_Type::generate( 12 ) )
						->add_prop( 'margin',             $size_0_px )
						->add_prop( 'line-height',        $size_1_em )
						->add_prop( 'clip-path',          $caption_clip )
				),

			// AFTER caption.
			self::BASE_STYLE_KEY . ' .aae-a-image-compare-caption-after' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',          String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'inset-block-start', $size_16_px )
						->add_prop( 'inset-inline-end',  $size_16_px )
						->add_prop( 'z-index',           Number_Prop_Type::generate( 12 ) )
						->add_prop( 'margin',            $size_0_px )
						->add_prop( 'line-height',       $size_1_em )
						->add_prop( 'text-align',        String_Prop_Type::generate( 'end' ) )
				),

			// Slider line (Atomic_Divider). `left: var(...)` lives in the
			// Twig <style> because Size_Prop_Type can't carry a CSS variable.
			// Default geometry: 2px wide × full height (horizontal slider).
			// Twig flips this to full width × 2px tall when direction is
			// vertical via `[data-direction="vertical"]`.
			self::BASE_STYLE_KEY . ' .aae-a-image-compare-divider' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',          String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'inset-block-start', $size_0_px )
						->add_prop( 'width',             Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ) )
						->add_prop( 'height',            $size_100_pct )
						->add_prop( 'margin',            $size_0_px )
						->add_prop( 'z-index',           Number_Prop_Type::generate( 10 ) )
				),

			// Slider thumb (Atomic_Button). The `left: var(...)` and
			// `transform: translate(-50%, -50%)` live in the Twig <style>
			// (CSS-var value + sibling combinator hover effects).
			// z-index needs to clear the editor element-overlay (30) and
			// the invisible range input (20), so the thumb is visible by
			// default without users hand-bumping it from the Style panel.
			self::BASE_STYLE_KEY . ' .aae-a-image-compare-thumb' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',          String_Prop_Type::generate( 'absolute' ) )
						->add_prop( 'inset-block-start', $size_50_pct )
						->add_prop( 'margin',            $size_0_px )
						->add_prop( 'z-index',           Number_Prop_Type::generate( 999 ) )
				),
		];
	}

	/**
	 * Default child structure, ordered to match the v1 spec:
	 *   1. Before Image  (Atomic_Image — clipped overlay)
	 *   2. After Image   (Atomic_Image — natural-flow baseline)
	 *   3. Divider       (Atomic_Divider — slider line)
	 *   4. Handle        (Atomic_Button — draggable thumb)
	 *   5. Before Label  (Atomic_Paragraph — optional caption)
	 *   6. After Label   (Atomic_Paragraph — optional caption)
	 *
	 * Each child is a core atomic element so the user can edit text /
	 * media and style appearance through the element's own Style panel.
	 * Image, label text, and label visibility are managed per-child — no
	 * widget-level duplicates.
	 */
	protected function define_default_children() {
		return [
			// 1. Before Image — absolutely positioned overlay, clipped by the
			//    handle position. Atomic_Image supplies its own placeholder.
			Atomic_Image::generate()
				->editor_settings( [ 'title' => 'Before Image' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-before' ] ),
				] )
				->build(),

			// 2. After Image — natural flow, defines the widget's height.
			Atomic_Image::generate()
				->editor_settings( [ 'title' => 'After Image' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-after' ] ),
				] )
				->build(),

			// 3. Divider — vertical line that tracks the handle position.
			//    User styles colour / width / opacity via Style panel.
			Atomic_Divider::generate()
				->editor_settings( [ 'title' => 'Divider' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-divider' ] ),
				] )
				->build(),

			// 4. Handle — the draggable thumb at the divider's midpoint.
			//    User styles background, border, padding, typography, arrow
			//    colour (= button text colour) via Style panel.
			Atomic_Button::generate()
				->editor_settings( [ 'title' => 'Handle' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-image-compare-thumb' ] ),
					'text'    => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '‹ ›' ),
						'children' => [],
					] ),
				] )
				->build(),

			// 5. Before Label — optional caption on the clipped (before) side.
			//    Hide by deleting the child or styling `display: none`.
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Before Label' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-image-compare-caption-before' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Before' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			// 6. After Label — optional caption on the after side.
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'After Label' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-image-compare-caption-after' ] ),
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
		return [ 'widget', 'e-image', 'e-paragraph', 'e-button', 'e-divider' ];
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
}
