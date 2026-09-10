<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\CurvedText;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AAE Curved Text — atomic WIDGET.
 *
 * A spinning circular badge: a rotating curved text (SVG `<textPath>`) or a
 * rotating image, with a static center overlay on top. Purely decorative —
 * no click behavior, no JS runtime at all, driven entirely by a CSS
 * `@keyframes` animation.
 *
 * @package AnimationAddonsForElementor
 */

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Div_Block\Div_Block;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Curved_Text extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'A spinning circular badge with curved rotating text or a rotating image, plus an open "Center" box on top that you fill yourself. It starts with a single Icon you can restyle or delete outright, and accepts anything else you drop in — text, headings, several elements stacked and centered. Fully styleable via the Style tab. Its Rotator Image and Icon are real Image and Svg elements — edit them directly.';

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

			// How far the text sits IN from the circle's outer edge. The
			// path radius used in the twig is `50 - this value` (the SVG
			// viewBox is a fixed 0..100 box), so a bigger number pulls the
			// text closer to the center — "distance from the border" in the
			// same sense as padding, just expressed as a radius offset
			// rather than a box inset. Kept as its own Number control,
			// because it's geometry, not a CSS style property — there's no
			// "SVG path radius" style key. Typography (color/font-family/
			// font-weight/font-size) is NOT a prop at all — see the class
			// docblock: it rides the widget's own root Style tab via CSS
			// inheritance instead.
			'rotator_text_padding' => Number_Prop_Type::make()
				->default( 8 )
				->set_dependencies( $is_text ),

			'rotation_duration'  => Number_Prop_Type::make()->default( 8 ),
			'rotation_direction' => String_Prop_Type::make()
				->enum( [ 'cw', 'ccw' ] )
				->default( 'cw' ),
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
						// Rotator Text's only sizing/coloring hook — see the
						// class docblock's "explicit, repeated decision" note.
						// The curved SVG `<text>` sets nothing of its own but
						// `fill: currentColor`, so both of these reach it by
						// ordinary CSS inheritance from THIS real, per-instance
						// editable root style. Also inherited by the Icon
						// child's own `1em` sizing — a known, accepted
						// trade-off, not a bug.
						'font-size'       => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
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

			// The Rotator Image's hide-in-text-mode hook (toggled by the
			// twig's scoped `<style>` rule) — no CSS of its own beyond a
			// no-op `display: block` (matches what the 'rotator' key above
			// already sets on the same node). See the class-level docblock
			// above: this key existing here, not just as a per-instance
			// override, is what keeps the panel from flagging it.
			'rotator--image' => Style_Definition::make()
				->set_label( __( 'Rotator (Image)', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'display' => String_Prop_Type::generate( 'block' ),
				] ) ),

			// The static "Center" box — holds the Icon and Text children
			// (see define_default_children()), never spins. Same
			// full-fill-the-circle shape as 'rotator' above (position:
			// absolute + inset 0 on every side + 100%/100%) rather than a
			// centered flex COLUMN laying out the Icon above the Text.
			// `z-index: 1` is load-bearing, not just "look": the rotator
			// layer is `position: absolute` too, and CSS always paints a
			// positioned element above a non-positioned sibling regardless
			// of DOM order — so without it the box renders BEHIND the
			// rotator and is invisible, even though it comes later in the
			// tree.

			'center' => Style_Definition::make()
				->set_label( __( 'Center', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'position'           => String_Prop_Type::generate( 'absolute' ),
					'inset-block-start'  => $zero,
					'inset-inline-end'   => $zero,
					'inset-block-end'    => $zero,
					'inset-inline-start' => $zero,
					'width'              => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					'height'             => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					'z-index'            => Number_Prop_Type::generate( 1 ),
					'display'            => String_Prop_Type::generate( 'flex' ),
					'flex-direction'     => String_Prop_Type::generate( 'column' ),
					'align-items'        => String_Prop_Type::generate( 'center' ),
					'justify-content'    => String_Prop_Type::generate( 'center' ),
				] ) ),

			// The icon — a plain flex item of the Center column now,
			// carrying no positioning of its own (it used to self-centre
			// with `absolute + 50%/50% + translate(-50%,-50%)`; see the
			// 'center' key for why that's gone). `1em` on both axes rather
			// than a fixed px size so the Icon child's own Typography >
			// Font Size control (the native e-svg widget inherits/accepts
			// font-size like any other atomic element) is what resizes it —
			// set the font-size on the Icon child itself to scale the icon.
			// `display: block` is required for width/height to apply at all
			// on the Svg child's own wrapper; it is ALSO the value the user
			// flips to `none` (Style tab > Layout > Display) to hide the
			// icon, now that this widget no longer hides it for them.
			'icon' => Style_Definition::make()
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'display' => String_Prop_Type::generate( 'block' ),
					'width'   => Size_Prop_Type::generate( [ 'size' => 40, 'unit' => 'px' ] ),
					'height'  => Size_Prop_Type::generate( [ 'size' => 40, 'unit' => 'px' ] ),
				] ) ),

			// The Center box's Text — a plain flex item, no positioning of
			// its own. `text-align: center` keeps a wrapped, multi-line
			// label centred; the flex column above does the rest.
			'text' => Style_Definition::make()
				->set_label( __( 'Text', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( [
					'text-align' => String_Prop_Type::generate( 'center' ),
				] ) ),
		];
	}

	/**
	 * The Rotator Image is always present (locked, non-deletable) even in
	 * text mode — the twig hides it by class rather than this class ever
	 * removing/re-adding it, so switching `rotator_type` back and forth
	 * never loses the uploaded image.
	 *
	 * The Center box is likewise always present and locked, but it is an
	 * OPEN drop target: its only seeded child is a single Icon, and that
	 * Icon is deliberately NOT locked, so the user can delete it and put
	 * whatever they like in the middle of the badge. There is no Text child
	 * any more — a user who wants text drops a real Paragraph or Heading.
	 * See the class docblock for the three steps that led here, and why
	 * neither a `center_content` select nor a hidden-by-default child is
	 * coming back.
	 */
	protected function define_default_children() {
		$rotator_class = static::get_element_type() . '-rotator';
		$icon_class    = static::get_element_type() . '-icon';
		$zero          = Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] );

		$rotator_image = Atomic_Image::generate()
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
			->build();

		/**
		 * Backs `$rotator_class` with a REAL local style entry on THIS
		 * element too, so Elementor's panel recognizes it as a known style
		 * instead of flagging "Some classes are missing"
		 */
		$rotator_image['styles'] = [
			$rotator_class => Style_Definition::make()
				->set_label( 'local' )
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
				] ) )
				->build( $rotator_class ),
		];

		$center_class = static::get_element_type() . '-center';

		$icon = Atomic_Svg::generate()
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ $icon_class ] ),
				'svg'     => Svg_Src_Prop_Type::generate( [
					'id'  => null,
					'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/CurvedText/assets/icons/icon.svg' ),
				] ),
			] )
			// NOT locked, unlike every other default child here. The Icon is a
			// starting suggestion, not part of the widget's structure: the user
			// is meant to delete it and drop whatever they want into Center.
			->is_locked( false )
			->editor_settings( [ 'title' => 'Icon' ] )
			->build();

		// Same "real local style" duplication as the Rotator Image above —
		// `icon_class`'s props mirror `define_base_styles()`'s 'icon' key
		// so the panel recognizes it as a known style of THIS element
		// instead of flagging "Some classes are missing".
		$icon['styles'] = [
			$icon_class => Style_Definition::make()
				->set_label( 'local' )
				->add_variant( Style_Variant::make()->add_props( [
					'display' => String_Prop_Type::generate( 'block' ),
					'width'   => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'em' ] ),
					'height'  => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'em' ] ),
				] ) )
				->build( $icon_class ),
		];

		/**
		 * No child gets a `styles` entry here, and none should — assigning
		 * one does nothing at all.
		 */

		$center_box = Div_Block::generate()
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ $center_class ] ),
			] )
			->children( [ $icon ] )
			->is_locked( true )
			->editor_settings( [ 'title' => 'Center' ] )
			->build();

		// `center_class`'s props mirror `define_base_styles()`'s 'center'
		// key, for the same "missing classes" reason as above.
		$center_box['styles'] = [
			$center_class => Style_Definition::make()
				->set_label( 'local' )
				->add_variant( Style_Variant::make()->add_props( [
					'position'           => String_Prop_Type::generate( 'absolute' ),
					'inset-block-start'  => $zero,
					'inset-inline-end'   => $zero,
					'inset-block-end'    => $zero,
					'inset-inline-start' => $zero,
					'width'              => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					'height'             => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					'z-index'            => Number_Prop_Type::generate( 1 ),
					'display'            => String_Prop_Type::generate( 'flex' ),
					'flex-direction'     => String_Prop_Type::generate( 'column' ),
					'align-items'        => String_Prop_Type::generate( 'center' ),
					'justify-content'    => String_Prop_Type::generate( 'center' ),
				] ) )
				->build( $center_class ),
		];

		return [
			$rotator_image,
			$center_box,
		];
	}

	/**
	 * Only the Rotator Image and the Center box are allowed as DIRECT
	 * children of the root. This restriction stops at the root: the Center
	 * box is a plain `e-div-block` and accepts whatever it natively allows,
	 * which is what makes it a usable drop target for the badge's contents.
	 */
	protected function define_allowed_child_types() {
		return [ 'e-image', 'e-div-block' ];
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
