<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\ImageGallery;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Div_Block\Div_Block;

require_once __DIR__ . '/class-aae-a-image-gallery-item.php';
require_once __DIR__ . '/class-aae-a-gallery-items-control.php';

use WCF_ADDONS\AtomicWidgets\Widgets\ImageGallery\AAE_A_Image_Gallery_Item;
use WCF_ADDONS\AtomicWidgets\Widgets\ImageGallery\AAE_A_Gallery_Items_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AAE Image Gallery — atomic (Elementor V4) port of the legacy
 * wcf--image-gallery widget.
 *
 * A CSS-grid gallery whose children are draggable <e-aae-a-image-gallery-item>
 * elements (managed by the 'aae-gallery-items' element-control). Layout,
 * hover effect, icon overlay and lightbox are configured on this parent; the
 * grid metrics are exposed as CSS variables (--aae-gallery-*) set inline in the
 * twig from settings, so the base-style class stays token-driven with no
 * duplicated declarations.
 */
class AAE_A_Image_Gallery extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-image-gallery';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-image-gallery';
	}

	public function get_title() {
		return esc_html__( 'AAE Image Gallery', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_keywords() {
		return [ 'gallery', 'image', 'grid', 'lightbox', 'atomic' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-image-gallery-css' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Layout.
			'columns'    => Number_Prop_Type::make()->default( 5 ),
			'column_gap' => Number_Prop_Type::make()->default( 30 ),
			'row_gap'    => Number_Prop_Type::make()->default( 30 ),

			// Hover effect — appended as a modifier class on the wrapper.
			'hover_effect' => String_Prop_Type::make()
				->enum( [ 'none', 'effect-zoom-in', 'effect-zoom-out', 'left-move', 'right-move' ] )
				->default( 'effect-zoom-in' ),

			// Lightbox.
			'enable_lightbox'    => Boolean_Prop_Type::make()->default( true ),
			'lightbox_animation' => String_Prop_Type::make()
				->enum( [ 'fade-scale', 'slide' ] )
				->default( 'fade-scale' ),
			'lightbox_counter'   => Boolean_Prop_Type::make()->default( true ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			// Live projection of the gallery's real <e-aae-a-image-gallery-item>
			// children — add / drag / duplicate / remove. Rendered by the React
			// component registered under 'aae-gallery-items'.
			Section::make()
				->set_id( 'images' )
				->set_label( __( 'Images', 'animation-addons-for-elementor' ) )
				->set_items( [
					AAE_A_Gallery_Items_Control::make()
						->set_label( __( 'Images', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_id( 'layout' )
				->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
				->set_items( [
					Number_Control::bind_to( 'columns' )
						->set_label( __( 'Columns', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'column_gap' )
						->set_label( __( 'Columns Gap (px)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'row_gap' )
						->set_label( __( 'Rows Gap (px)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'hover_effect' )
						->set_label( __( 'Hover Effect', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'none',            'label' => __( 'None', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'effect-zoom-in',  'label' => __( 'Zoom In', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'effect-zoom-out', 'label' => __( 'Zoom Out', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'left-move',       'label' => __( 'Left Move', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'right-move',      'label' => __( 'Right Move', 'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_id( 'lightbox' )
				->set_label( __( 'Lightbox', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'enable_lightbox' )
						->set_label( __( 'Enable Lightbox', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'lightbox_animation' )
						->set_label( __( 'Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'fade-scale', 'label' => __( 'Fade & Scale', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'slide',      'label' => __( 'Slide', 'animation-addons-for-elementor' ) ],
						] ),
					Switch_Control::bind_to( 'lightbox_counter' )
						->set_label( __( 'Show Counter', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	/**
	 * Grid base styles. Concrete metrics (columns/gap) come from CSS variables
	 * the twig sets inline from settings, so this stays token-driven with no
	 * per-instance duplicated CSS.
	 */
	protected function define_base_styles(): array {
		$styles = [
			// Only style-schema-valid keys belong here. `display` and
			// `grid-template-columns` are String props; `gap` (Union of
			// Layout_Direction/Size) and `width` (Size) can't carry the row/col
			// CSS-var expression we need, so those two are emitted as inline CSS
			// in the twig (where var() is valid) instead — keeping this
			// definition from silently failing.
			'display'               => String_Prop_Type::generate( 'grid' ),
			'grid-template-columns' => String_Prop_Type::generate( 'repeat(var(--aae-gallery-columns, 5), 1fr)' ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $styles ) ),
		];
	}

	/**
	 * Seed five gallery items (each a container with a default Atomic_Image),
	 * followed by a single LOCKED "All Icons" wrapper holding the four native
	 * Atomic_Svg lightbox icons (close / prev / next / fullscreen).
	 *
	 * Only the wrapper is locked (is_locked true): Elementor hides
	 * delete / duplicate / save-as on it, so the whole icon group can't be
	 * removed, duplicated or detached, stays pinned as the last child, and
	 * persists after save. The four icons INSIDE are ordinary Atomic Icons —
	 * the user selects each in the Structure tree to swap it from the Icon
	 * Library and style it with the standard Base Style panel (size, color,
	 * background, border, radius, box-shadow, opacity, hover/active states,
	 * responsive, motion, Global Style tokens). The Images panel
	 * (AAE_A_Gallery_Items_Control) excludes the wrapper from the item list +
	 * count and always inserts new items before it. The lightbox JS clones the
	 * icon nodes into the overlay at runtime.
	 */
	protected function define_default_children() {
		$items = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$items[] = AAE_A_Image_Gallery_Item::generate()
				->editor_settings( [ 'title' => 'Gallery Item ' . $i ] )
				->build();
		}

		$items[] = $this->build_lightbox_icons();

		return $items;
	}

	/**
	 * Build the locked "All Icons" wrapper holding the four Atomic_Svg lightbox
	 * icons. Each icon carries a role class (aae-lb-icon-{role}) the runtime
	 * matches on, plus a shared class (aae-lb-icon) so users can style all four
	 * together via that Global class while still overriding any single icon.
	 *
	 * @return array Element data for the locked wrapper (with its 4 icons).
	 */
	private function build_lightbox_icons(): array {
		$icon_url = function ( $name ) {
			return WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/ImageGallery/assets/icons/' . $name . '.svg';
		};

		$make_icon = function ( $role, $title, $file ) use ( $icon_url ) {
			// Per-icon local style id (v4 local-style convention). Seeds a
			// professional default size (24x24) so the lightbox controls look
			// right out of the box — Atomic_Svg's own base is 65px, too large
			// for a control. It's a real atomic style, so the user can override
			// size (and everything else) per icon in the Base Style panel.
			$size_style_id = 'e-aae-lb-icon-' . $role . '-size';

			$icon = Atomic_Svg::generate()
				->editor_settings( [ 'title' => $title ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-lb-icon', 'aae-lb-icon-' . $role, $size_style_id ] ),
					'svg'     => Svg_Src_Prop_Type::generate( [
						'id'  => null,
						'url' => Url_Prop_Type::generate( $icon_url( $file ) ),
					] ),
				] )
				->build();

			$icon['styles'] = [
				$size_style_id => [
					'id'       => $size_style_id,
					'label'    => 'local',
					'type'     => 'class',
					'variants' => [
						[
							'meta'       => [
								'breakpoint' => 'desktop',
								'state'      => null,
							],
							'props'      => [
								'width'  => [
									'$$type' => 'size',
									'value'  => [ 'size' => 24, 'unit' => 'px' ],
								],
								'height' => [
									'$$type' => 'size',
									'value'  => [ 'size' => 24, 'unit' => 'px' ],
								],
							],
							'custom_css' => null,
						],
					],
				],
			];

			return $icon;
		};

		// Local style id doubling as the wrapper class (v4 local-style
		// convention — same shape the widget presets use). display:none is
		// carried here as a real atomic style so the group never renders inline
		// through the style pipeline, rather than a rule in the widget's SCSS.
		// The editor-only "show when Lightbox is on" override lives in SCSS and
		// out-specifies this instance style, so it still surfaces for styling.
		$hidden_style_id = 'e-aae-lightbox-icons-hidden';

		$wrapper = Div_Block::generate()
			->editor_settings( [ 'title' => 'All Icons' ] )
			->is_locked( true )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-lightbox-icons', $hidden_style_id ] ),
			] )
			->children( [
				$make_icon( 'close', 'Close Icon', 'close' ),
				$make_icon( 'prev', 'Previous Icon', 'prev' ),
				$make_icon( 'next', 'Next Icon', 'next' ),
				$make_icon( 'fullscreen', 'Fullscreen Icon', 'fullscreen' ),
			] )
			->build();

		$wrapper['styles'] = [
			$hidden_style_id => [
				'id'       => $hidden_style_id,
				'label'    => 'local',
				'type'     => 'class',
				'variants' => [
					[
						'meta'       => [
							'breakpoint' => 'desktop',
							'state'      => null,
						],
						'props'      => [
							'display' => [
								'$$type' => 'string',
								'value'  => 'none',
							],
						],
						'custom_css' => null,
					],
				],
			],
		];

		return $wrapper;
	}

	protected function define_allowed_child_types() {
		// Gallery items (user content) + the div-block the locked "All Icons"
		// wrapper is an instance of.
		return [ 'e-aae-a-image-gallery-item', 'e-div-block' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-image-gallery' => __DIR__ . '/aae-a-image-gallery.html.twig',
		];
	}
}
