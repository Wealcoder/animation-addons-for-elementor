<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageGallery;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * A single gallery item — a container holding one native Atomic_Image child.
 *
 * The item wrapper carries the hover/overlay classes and (when the parent's
 * lightbox is on) the `.aae-a-gallery-trigger` behaviour via the frontend JS,
 * which reads the rendered <img> src. Reusing the native Atomic_Image element
 * gives the user Elementor's standard image control (pick / size / alt) for
 * every item, with drag / duplicate / remove handled by the parent's
 * AAE_A_Image_Gallery_Items_Control (registry id 'aae-gallery-items').
 */
class AAE_A_Image_Gallery_Item extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-image-gallery-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-image-gallery-item';
	}

	public function get_title() {
		return esc_html__( 'Gallery Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-image';
	}

	public function get_keywords() {
		return [ 'gallery', 'image', 'item', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false; // Only inserted via the Image Gallery container.
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
	 * Default child: a native Atomic_Image. The frontend lightbox reads this
	 * rendered <img>'s src (full-size, resolved from the attachment id when
	 * available) so no separate image data lives on the item element.
	 */
	protected function define_default_children() {
		$image = Atomic_Image::generate()
			->editor_settings( [ 'title' => 'Gallery Image' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-a-gallery-image' ] ),
			] )
			->build();

		return [ $image ];
	}

	protected function define_allowed_child_types() {
		// Image is primary; allow the common atomic content elements too so a
		// user can add an overlay caption/button per item if they wish.
		return [ 'e-image', 'e-paragraph', 'e-heading', 'e-button', 'e-svg', 'e-div-block' ];
	}

	/**
	 * Item wrapper defaults: clip hover-zoom, relative for the overlay, and a
	 * default radius. All overridable per item in the style panel.
	 */
	protected function define_base_styles(): array {
		$styles = [
			'position'      => String_Prop_Type::generate( 'relative' ),
			'overflow'      => String_Prop_Type::generate( 'hidden' ),
			'border-radius' => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
			'display'       => String_Prop_Type::generate( 'block' ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $styles ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-image-gallery-item' => __DIR__ . '/aae-a-image-gallery-item.html.twig',
		];
	}
}
