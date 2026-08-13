<?php
/**
 * AAE Form Submit — atomic CONTAINER. Renders <button type="submit"> and
 * drops its children inside it.
 *
 * Composite, not a leaf: the label and the icon are real atomic elements
 * (Flexbox › SVG + Heading), so both show in the Structure panel and get the
 * full Style tab — same shape AAE_A_Btn uses. `define_default_children()` and
 * `define_allowed_child_types()` exist ONLY on Atomic_Element_Base
 * (atomic-element-base.php:113), so holding children is exactly why this is
 * not Atomic_Widget_Base any more.
 *
 * BREAKING (see the migration note in the class docblock of AAE_A_Form): the
 * base class decides how Elementor SAVES the element. As a widget it stored
 * `{"elType":"widget","widgetType":"e-aae-a-form-submit"}`; as an element it
 * stores `{"elType":"e-aae-a-form-submit"}`. Data written before this change
 * resolves through widgets_manager, finds nothing, and create_element_instance()
 * returns null (elements.php:82) — so a submit button saved by an older build
 * is dropped from _elementor_data the next time that page is saved. Forms have
 * to be re-dropped, not repaired.
 *
 * Registration needs no change: resolve_registerable_classes() buckets by
 * `is_subclass_of( …, Widget_Base )`, so swapping the base class moves this
 * from the widgets registry to the elements registry on its own.
 *
 * NOT locked: locking would also block MOVING it — e.g. into a flexbox row
 * inside the form — which is a legit layout. The "form must stay submittable"
 * guarantee lives in editor-bridge/form-guards.js's save-time audit instead.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Element_Builder;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Heading\Atomic_Heading;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Submit extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'Form submit button. A container — its label and icon are real child elements.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-form-submit';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-form-submit';
	}

	public function get_title() {
		return esc_html__( 'Submit Button', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-button';
	}

	/**
	 * Seeded as the Form's default child, not dragged from the panel.
	 *
	 * `should_show_in_panel()` — NOT `show_in_panel()`. That pair belongs to
	 * Widget_Base and is silently never consulted for an Atomic_Element_Base;
	 * see the inverse warning in class-aae-a-form-next.php, which is a leaf and
	 * therefore needs the classic pair. Getting this wrong doesn't error, it
	 * just leaves the element listed in the panel.
	 */
	public function should_show_in_panel() {
		return false;
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'submit', 'button' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'       => Classes_Prop_Type::make()->default( [] ),
			'attributes'    => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// No `text`, `icon` or `icon_position` prop: the label is the Heading
			// child and the icon is the SVG child, both edited on the canvas and
			// reordered by dragging in the Structure panel. A prop the schema
			// does not declare is stripped from saved data by Props_Parser, which
			// is correct here — nothing reads them any more.

			// submit = sends the form; reset = clears every field (native
			// <button type="reset">, plus the runtime resyncs custom UI).
			'button_type'   => String_Prop_Type::make()->enum( [ 'submit', 'reset' ] )->default( 'submit' ),

			// Swapped into the label child while the request is in flight — see
			// setLoading() in form.js.
			'loading_label' => String_Prop_Type::make()->default( __( 'Sending...', 'animation-addons-for-elementor' ) ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						// No Button text / Icon / Icon position controls: select the
						// Heading or SVG child in the Structure panel to edit them,
						// and drag to reorder. That is the point of the composite.
						Select_Control::bind_to( 'button_type' )
							->set_label( __( 'Button type', 'animation-addons-for-elementor' ) )
							->set_options(
								[
									[
										'value' => 'submit',
										'label' => __( 'Submit', 'animation-addons-for-elementor' ),
									],
									[
										'value' => 'reset',
										'label' => __( 'Reset / Clear', 'animation-addons-for-elementor' ),
									],
								]
							),
						Text_Control::bind_to( 'loading_label' )
							->set_label( __( 'Loading text', 'animation-addons-for-elementor' ) ),
					]
				),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( '_cssid' )
							->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
							->set_meta( $this->get_css_id_control_meta() ),
					]
				),
		];
	}

	/**
	 * Copied from Elementor's native e-form-submit-button base styles.
	 * CAUTION: `background` must be a Background_Prop_Type (a bare
	 * Color_Prop_Type is invalid) and `cursor` is not a style-schema key —
	 * either one silently voids the ENTIRE definition (that bug shipped as
	 * an unstyled, colorless button).
	 */
	protected function define_base_styles(): array {
		$zero = Size_Prop_Type::generate(
			[
				'size' => 0,
				'unit' => 'px',
			]
		);

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop(
							'background',
							Background_Prop_Type::generate(
								[
									'color' => Color_Prop_Type::generate( '#000' ),
								]
							)
						)
						->add_prop( 'color', Color_Prop_Type::generate( '#fff' ) )
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop(
							'padding',
							Dimensions_Prop_Type::generate(
								[
									'block-start'  => Size_Prop_Type::generate(
										[
											'size' => 10,
											'unit' => 'px',
										]
									),
									'inline-end'   => Size_Prop_Type::generate(
										[
											'size' => 30,
											'unit' => 'px',
										]
									),
									'block-end'    => Size_Prop_Type::generate(
										[
											'size' => 10,
											'unit' => 'px',
										]
									),
									'inline-start' => Size_Prop_Type::generate(
										[
											'size' => 28,
											'unit' => 'px',
										]
									),
								]
							)
						)
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'border-radius', $zero )
						->add_prop( 'border-width', $zero )
				)
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::HOVER )
						->add_prop(
							'background',
							Background_Prop_Type::generate(
								[
									'color' => Color_Prop_Type::generate( '#323232' ),
								]
							)
						)
				),

			// One style key per child. These compile to real registered base
			// styles named `{element_type}-{key}` (Has_Base_Styles::
			// generate_base_style_id()), which is why seeding the matching class
			// into a child's `classes` prop is safe here: the panel resolves it,
			// so it never shows up under "Some classes are missing" and its ✕
			// cannot strip it. A bare hook class would.
			'row'  => Style_Definition::make()
				->set_label( __( 'Content row', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'row' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop(
							'gap',
							Size_Prop_Type::generate(
								[
									'size' => 8,
									'unit' => 'px',
								]
							)
						)
					// NO `padding` here, deliberately. e-flexbox ships
					// padding:10px, which would sit inside the button's own
					// padding and inflate it — but a `padding` prop on this key
					// cannot win: both compile to (0,2,0) selectors
					// (`.elementor .e-flexbox-base` vs
					// `.elementor .e-aae-a-form-submit-row`), so the tie is
					// decided by source order, and Elementor's atomic module
					// registers its elements AFTER ours on the shared
					// `elementor/elements/elements_registered` hook. Measured in
					// the generated base-style sheet: ours at offset ~11.9k,
					// e-flexbox-base at ~17.4k. A prop here would emit a rule
					// that silently never applies. The reset lives in form.scss
					// at (0,3,0) instead — see the note there.
				),

			'icon' => Style_Definition::make()
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()
						->add_prop(
							'width',
							Size_Prop_Type::generate(
								[
									'size' => 16,
									'unit' => 'px',
								]
							)
						)
						->add_prop(
							'height',
							Size_Prop_Type::generate(
								[
									'size' => 16,
									'unit' => 'px',
								]
							)
						)
				),

			// Atomic_Heading's own base style is `margin: 0` and nothing else, so
			// without this the label arrives at the theme's h2 size — a 32px
			// "Submit" that dwarfs the button. `inherit` (custom unit ⇒ the value
			// is emitted verbatim) hands sizing back to the button's own Style
			// tab, which is how the label behaved when it was a plain span.
			// font-weight is deliberately left alone: it is an ENUM in the style
			// schema, so 'inherit' there would be invalid and would silently void
			// this whole definition.
			'label' => Style_Definition::make()
				->set_label( __( 'Label', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()
						->add_prop(
							'font-size',
							Size_Prop_Type::generate(
								[
									'size' => 'inherit',
									'unit' => 'custom',
								]
							)
						)
						->add_prop(
							'line-height',
							Size_Prop_Type::generate(
								[
									'size' => 'inherit',
									'unit' => 'custom',
								]
							)
						)
				),
		];
	}

	/**
	 * Flexbox › SVG + Heading.
	 *
	 * The row is a real e-flexbox rather than letting the button's own flex do
	 * the job, so the builder gets a element they can set direction, gap and
	 * alignment on without touching the button's padding/background.
	 */
	protected function define_default_children() {
		$type = static::get_element_type();

		$icon = Atomic_Svg::generate()
			->settings(
				[
					'classes' => Classes_Prop_Type::generate( [ $type . '-icon' ] ),
					'svg'     => Svg_Src_Prop_Type::generate(
						[
							'id'  => null,
							'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Form/assets/icons/arrow-right.svg' ),
						]
					),
				]
			)
			->editor_settings( [ 'title' => __( 'Icon', 'animation-addons-for-elementor' ) ] )
			->build();

		$label = Atomic_Heading::generate()
			->settings(
				[
					'classes' => Classes_Prop_Type::generate( [ $type . '-label' ] ),
					'tag'     => String_Prop_Type::generate( 'h6' ),
					'title'   => Html_V3_Prop_Type::generate(
						[
							'content'  => String_Prop_Type::generate( __( 'Submit', 'animation-addons-for-elementor' ) ),
							'children' => [],
						]
					),
				]
			)
			->editor_settings( [ 'title' => __( 'Label', 'animation-addons-for-elementor' ) ] )
			->build();

		return [
			Element_Builder::make( 'e-flexbox' )
				->children( [ $label, $icon ] )
				->settings(
					[
						'classes' => Classes_Prop_Type::generate( [ $type . '-row' ] ),
					]
				)
				->editor_settings( [ 'title' => __( 'Content', 'animation-addons-for-elementor' ) ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-flexbox', 'e-div-block', 'e-svg', 'e-heading', 'e-paragraph', 'e-image' ];
	}

	protected function define_default_html_tag() {
		return 'button';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-submit' => __DIR__ . '/aae-a-form-submit.html.twig',
		];
	}
}
