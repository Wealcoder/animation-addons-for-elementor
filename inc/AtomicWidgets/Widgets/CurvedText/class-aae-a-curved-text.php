<?php
/**
 * AAE Curved Text — atomic WIDGET.
 *
 * A spinning circular badge: a rotating curved text (SVG `<textPath>`) or a
 * rotating image, with a static icon layered on top. Purely decorative — no
 * click behavior, no JS runtime at all, driven entirely by a CSS `@keyframes`
 * animation.
 *
 * This widget is what remains of the old "Video Popup" family after the
 * video engine and the popup mechanics (Overlay/Panel/Close/PlayBtn/Player)
 * were removed entirely — only the spinning trigger badge survived, promoted
 * from an internal child to its own standalone top-level widget (briefly
 * named "Rotating Badge" before this name). See git history for the removed
 * video/popup code if it's ever needed for reference.
 *
 * Structure:
 *   AAE_A_Curved_Text (this class, a container)
 *     ├─ (twig-rendered SVG) — curved text, when rotator_type='text'
 *     ├─ e-image (locked)    — rotator image, shown when rotator_type='image'
 *     └─ e-svg   (locked)    — the static icon on top
 *
 * Rotator Image and Icon are real, native Elementor elements (Image/Svg)
 * instead of markup this class renders itself; text mode is this class's own
 * inline curved SVG `<textPath>` — a native Paragraph can't bend text along a
 * circle (tried twice on the original Video Popup Trigger — see git history),
 * so text mode trades away native Style-tab text editing for the curved
 * look. DO NOT re-introduce a Paragraph child for text mode without an
 * explicit, repeated ask.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\CurvedText;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Curved_Text extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'A spinning circular badge with curved rotating text or a rotating image, plus a static icon on top. Fully styleable via the Style tab. Its Rotator Image and Icon children are real Image and Svg elements — edit them directly.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-curved-text';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-curved-text';
	}

	public function get_title() {
		return esc_html__( 'Curved Text', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-dot-circle-o';
	}

	public function get_keywords() {
		return [ 'curved', 'text', 'rotate', 'rotating', 'spin', 'spinner', 'circle', 'circular', 'badge', 'image', 'atomic' ];
	}

	public function get_categories(): array {
		return [ 'aae-atomic-general' ];
	}

	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	protected static function define_props_schema(): array {
		$is_text = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'rotator_type' ],
				'value'    => 'text',
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Text mode has no child element (see the twig) — image mode's
			// Rotator Image is a real, locked e-image child instead; only
			// the CHOICE between them lives here.
			'rotator_type' => String_Prop_Type::make()
				->enum( [ 'image', 'text' ] )
				->default( 'image' ),

			'rotator_text' => String_Prop_Type::make()
				->default( 'EXPLORE MORE • EXPLORE MORE •' )
				->set_dependencies( $is_text ),

			// The curved text lives inside an SVG <textPath> (see the twig),
			// so it's outside the atomic Style tab's normal CSS vocabulary —
			// there's no "SVG path radius" style key, and a generic Style-tab
			// font-size would only ever reach it by CSS inheritance, which an
			// explicit rule on the <text> element always wins over regardless
			// of specificity. Both need their own dedicated controls instead.
			'rotator_text_font_size' => Number_Prop_Type::make()
				->default( 10 )
				->set_dependencies( $is_text ),

			// How far the text sits IN from the circle's outer edge. The
			// path radius used in the twig is `50 - this value` (the SVG
			// viewBox is a fixed 0..100 box), so a bigger number pulls the
			// text closer to the center — "distance from the border" in the
			// same sense as padding, just expressed as a radius offset
			// rather than a box inset.
			'rotator_text_padding' => Number_Prop_Type::make()
				->default( 8 )
				->set_dependencies( $is_text ),

			'rotation_duration'  => Number_Prop_Type::make()->default( 8 ),
			'rotation_direction' => String_Prop_Type::make()
				->enum( [ 'cw', 'ccw' ] )
				->default( 'cw' ),

			// The Icon child (see define_default_children()) always stays in
			// the tree — locked, non-deletable — so this only toggles its
			// visibility (a scoped CSS rule in the twig), the same way
			// `rotator_type` hides the Rotator Image without ever removing
			// it.
			'show_icon' => Boolean_Prop_Type::make()->default( true ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Rotator', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'rotator_type' )
						->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'image', 'label' => __( 'Image', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'text',  'label' => __( 'Text', 'animation-addons-for-elementor' ) ],
						] ),
					Text_Control::bind_to( 'rotator_text' )
						->set_label( __( 'Rotator Text', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'rotator_text_font_size' )
						->set_label( __( 'Text Font Size', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'rotator_text_padding' )
						->set_label( __( 'Text Distance from Edge', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'rotation_duration' )
						->set_label( __( 'Rotation Duration (s)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'rotation_direction' )
						->set_label( __( 'Direction', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'cw',  'label' => __( 'Clockwise', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ccw', 'label' => __( 'Counter-clockwise', 'animation-addons-for-elementor' ) ],
						] ),
					Switch_Control::bind_to( 'show_icon' )
						->set_label( __( 'Show Icon', 'animation-addons-for-elementor' ) ),
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
	 * Neutral circle default — every value below is fully Style-tab
	 * overridable. `overflow: hidden` clips a non-square uploaded rotator
	 * image to the circle.
	 */
	protected function define_base_styles(): array {
		$zero = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'         => String_Prop_Type::generate( 'inline-flex' ),
						'position'        => String_Prop_Type::generate( 'relative' ),
						'align-items'     => String_Prop_Type::generate( 'center' ),
						'justify-content' => String_Prop_Type::generate( 'center' ),
						'width'           => Size_Prop_Type::generate( [ 'size' => 120, 'unit' => 'px' ] ),
						'height'          => Size_Prop_Type::generate( [ 'size' => 120, 'unit' => 'px' ] ),
						'border-radius'   => Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ),
						'overflow'        => String_Prop_Type::generate( 'hidden' ),
						'color'           => Color_Prop_Type::generate( '#ffffff' ),
						'background'      => Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.4)' ),
						] ),
					] )
				),

			// Shared by both rotator variants — the curved-text `<svg>`
			// (twig-rendered, class set literally there) and the Rotator
			// Image child (class set via its `classes` prop below). Must
			// fill the circle exactly, or whichever is visible renders at
			// its own natural size instead. `object-fit: cover` is a no-op
			// on the SVG and crops the Image nicely by default.
			// curved-text.scss layers the spin `@keyframes`/animation-*
			// properties on top of this same class (`get_element_type() .
			// '-rotator'`, i.e. carries the `e-` prefix) — no atomic prop
			// for keyframes, so that part has to stay there.
			'rotator' => Style_Definition::make()
				->set_label( __( 'Rotator', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'position'           => String_Prop_Type::generate( 'absolute' ),
					'inset-block-start'  => $zero,
					'inset-inline-end'   => $zero,
					'inset-block-end'    => $zero,
					'inset-inline-start' => $zero,
					'width'              => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					'height'             => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					'display'            => String_Prop_Type::generate( 'block' ),
					'object-fit'         => String_Prop_Type::generate( 'cover' ),
				] ) ),

			// The static icon layer — `1em` on both axes rather than a fixed
			// px size so the Icon child's own Typography > Font Size control
			// (the native e-svg widget inherits/accepts font-size like any
			// other atomic element) is what resizes it — set the font-size
			// on the Icon child itself to scale the icon.
			//
			// `position: relative` + `z-index: 1` are load-bearing, not just
			// "look": the rotator layer is `position: absolute` (see the
			// 'rotator' key above), and CSS always paints a positioned
			// element above a non-positioned sibling regardless of DOM
			// order — so without these two the icon renders BEHIND the
			// rotator and is invisible, even though it comes later in the
			// tree. `display: block` is likewise required for width/height
			// to apply at all on the Svg child's own wrapper.
			'icon' => Style_Definition::make()
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'position' => String_Prop_Type::generate( 'relative' ),
					'z-index'  => Number_Prop_Type::generate( 1 ),
					'display'  => String_Prop_Type::generate( 'block' ),
					'width'    => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'em' ] ),
					'height'   => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'em' ] ),
				] ) ),
		];
	}

	/**
	 * The Rotator Image is always present (locked, non-deletable) even in
	 * text mode — the twig hides it by class rather than this class ever
	 * removing/re-adding it, so switching `rotator_type` back and forth
	 * never loses the uploaded image. The Icon is likewise locked: there is
	 * no fallback glyph any more, so losing it would leave the badge with
	 * nothing on top of the rotator.
	 */
	protected function define_default_children() {
		$rotator_class = static::get_element_type() . '-rotator';
		$icon_class    = static::get_element_type() . '-icon';

		return [
			Atomic_Image::generate()
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ $rotator_class, $rotator_class . '--image' ] ),
					'image'   => Image_Prop_Type::generate( [
						'src'  => Image_Src_Prop_Type::generate( [
							'id'  => null,
							'url' => Url_Prop_Type::generate( \Elementor\Utils::get_placeholder_image_src() ),
						] ),
						'size' => String_Prop_Type::generate( 'full' ),
					] ),
				] )
				->is_locked( true )
				->editor_settings( [ 'title' => 'Rotator Image' ] )
				->build(),

			Atomic_Svg::generate()
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ $icon_class ] ),
					'svg'     => Svg_Src_Prop_Type::generate( [
						'id'  => null,
						'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/CurvedText/assets/icons/icon.svg' ),
					] ),
				] )
				->is_locked( true )
				->editor_settings( [ 'title' => 'Icon' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-image', 'e-svg' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-curved-text' => __DIR__ . '/aae-a-curved-text.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-curved-text-css' ];
	}
}
