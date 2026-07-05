<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Accordion;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Types\Html_Tag_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Toggle_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Elements\Div_Block\Div_Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Accordion_Item extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-accordion-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-accordion-item';
	}

	public function get_title() {
		return esc_html__( 'Accordion Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_keywords() {
		return [ 'accordion', 'item', 'tab', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false; // Should only be inserted via Accordion container
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'item_title' => String_Prop_Type::make()->default( 'Accordion Title' ),
			'is_active' => Boolean_Prop_Type::make()->default( false ),
			'icon_position' => String_Prop_Type::make()->enum( [ 'left', 'right' ] )->default( 'right' ),
			'expand_icon' => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Accordion/assets/icons/open.svg' ),
			'collapse_icon' => Svg_Src_Prop_Type::make()->default_url( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Accordion/assets/icons/close.svg' ),
			'title_html_tag' => String_Prop_Type::make()->enum( [ 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] )->default( 'div' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Item Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					// Text_Control::bind_to( 'item_title' )
					// 	->set_label( __( 'Title', 'animation-addons-for-elementor' ) ),
					// Html_Tag_Control::bind_to( 'title_html_tag' )
					// 	->set_label( __( 'Title HTML Tag', 'animation-addons-for-elementor' ) )
					// 	->set_options( [
					// 		[ 'value' => 'div', 'label' => 'div' ],
					// 		[ 'value' => 'h1', 'label' => 'H1' ],
					// 		[ 'value' => 'h2', 'label' => 'H2' ],
					// 		[ 'value' => 'h3', 'label' => 'H3' ],
					// 		[ 'value' => 'h4', 'label' => 'H4' ],
					// 		[ 'value' => 'h5', 'label' => 'H5' ],
					// 		[ 'value' => 'h6', 'label' => 'H6' ],
					// 	] ),
					Switch_Control::bind_to( 'is_active' )
						->set_label( __( 'Active by Default', 'animation-addons-for-elementor' ) ),
				] ),
				
			// Section::make()
			// 	->set_id( 'icon_settings' )
			// 	->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
			// 	->set_items( [
			// 		Toggle_Control::bind_to( 'icon_position' )
			// 			->set_label( __( 'Position', 'animation-addons-for-elementor' ) )
			// 			->add_options( [
			// 				'left'  => [ 'title' => __( 'Left', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-h-align-left' ],
			// 				'right' => [ 'title' => __( 'Right', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-h-align-right' ],
			// 			] )
			// 			->set_exclusive( true )
			// 			->set_convert_options( true ),
			// 		Svg_Control::bind_to( 'expand_icon' )
			// 			->set_label( __( 'Expand', 'animation-addons-for-elementor' ) ),
			// 		Svg_Control::bind_to( 'collapse_icon' )
			// 			->set_label( __( 'Collapse', 'animation-addons-for-elementor' ) ),
			// 	] ),
		];
	}

	protected function define_default_children() {
		// Base-style classes for the Header div and its icons. define_base_styles()
		// emits static, cached CSS for these ({element_type}-{key} naming — see
		// Has_Base_Styles::generate_base_style_id()), so the default design costs
		// no per-element style data and no editor JS.
		$header_class = static::get_element_type() . '-header_element';
		$icon_class   = static::get_element_type() . '-header_icon';

		// Header children: Title (Paragraph) + Icon (SVG)
		$header_title = Atomic_Paragraph::generate()
			->editor_settings( [ 'title' => 'Header Title' ] )
			->settings( [
				'classes'   => Classes_Prop_Type::generate( [ 'aae-header-title-element' ] ),
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( 'Accordion Title' ),
					'children' => [],
				] ),
				'tag'       => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		$open_icon_url  = WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Accordion/assets/icons/open.svg';
		$close_icon_url = WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Accordion/assets/icons/close.svg';

		// Icon show/hide is owned entirely by accordion.scss (scoped + !important
		// so it always wins over the Atomic_Svg element's own base `display`):
		// collapsed shows only the open icon, the `.active` item shows only the
		// close icon. We deliberately do NOT attach per-element local display
		// styles here — two competing systems made the editor/frontend
		// occasionally render both icons on a collapsed header.

		// Open icon — shown while the item is collapsed (default resting state).
		$header_icon_open = Atomic_Svg::generate()
			->editor_settings( [ 'title' => 'Open Icon' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-header-icon-element', 'aae-header-icon-open', $icon_class ] ),
				'svg'     => Svg_Src_Prop_Type::generate( [
					'id'  => null,
					'url' => Url_Prop_Type::generate( $open_icon_url ),
				] ),
			] )
			->build();

		// Close icon — hidden by default; shown only while the item is active.
		$header_icon_close = Atomic_Svg::generate()
			->editor_settings( [ 'title' => 'Close Icon' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-header-icon-element', 'aae-header-icon-close', $icon_class ] ),
				'svg'     => Svg_Src_Prop_Type::generate( [
					'id'  => null,
					'url' => Url_Prop_Type::generate( $close_icon_url ),
				] ),
			] )
			->build();

		// Content children: Text (Paragraph) + Image
		$content_text = Atomic_Paragraph::generate()
			->editor_settings( [ 'title' => 'Content Text' ] )
			->settings( [
				'classes'   => Classes_Prop_Type::generate( [ 'aae-content-text-element' ] ),
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.' ),
					'children' => [],
				] ),
			] )
			->build();

		$content_image = Atomic_Image::generate()
			->editor_settings( [ 'title' => 'Content Image' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-content-image-element' ] ),
			] )
			->build();

		// Header wrapper div. Default layout (display:flex / row / space-between)
		// comes from the 'header_element' base-style class added below — static
		// cached CSS, no per-element style data, no editor JS.
		$header_div = Div_Block::generate()
			->editor_settings( [ 'title' => 'Header' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-header-element', $header_class ] ),
			] )
			->children( [ $header_title, $header_icon_open, $header_icon_close ] )
			->build();

		// Content wrapper div
		$content_div = Div_Block::generate()
			->editor_settings( [ 'title' => 'Content' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-content-element' ] ),
			] )
			->children( [ $content_text, $content_image ] )
			->build();

		return [ $header_div, $content_div ];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-paragraph', 'e-svg', 'e-image', 'e-heading', 'e-button' ];
	}

	protected function define_atomic_style_states(): array {
		return [
			Style_States::get_class_states_map()['selected']
		];
	}

	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display' => String_Prop_Type::generate( 'block' ),
			'width' => String_Prop_Type::generate( '100%' ),
			'background' => Background_Prop_Type::generate([]),
			'border-style' => String_Prop_Type::generate(''),
			'border-color' => Color_Prop_Type::generate(''),
			'border-width' => Size_Prop_Type::generate([]),
			'border-radius' => Dimensions_Prop_Type::generate([]),
			'padding' => Dimensions_Prop_Type::generate([]),
			'margin' => Dimensions_Prop_Type::generate([]),
		];

		$wrapper_hover_styles = [
			'background' => Background_Prop_Type::generate([]),
			'border-color' => Color_Prop_Type::generate(''),
		];

		$wrapper_selected_styles = [
			'background' => Background_Prop_Type::generate([]),
			'border-color' => Color_Prop_Type::generate(''),
		];

		$header_styles = [
			'background' => Background_Prop_Type::generate([]),
			'color' => Color_Prop_Type::generate(''),
			'border-style' => String_Prop_Type::generate(''),
			'border-color' => Color_Prop_Type::generate(''),
			'border-width' => Size_Prop_Type::generate([]),
			'border-radius' => Dimensions_Prop_Type::generate([]),
			'padding' => Dimensions_Prop_Type::generate([]),
			'margin' => Dimensions_Prop_Type::generate([]),
		];

		$header_hover_styles = [
			'background' => Background_Prop_Type::generate([]),
			'color' => Color_Prop_Type::generate(''),
			'border-color' => Color_Prop_Type::generate(''),
		];

		$header_selected_styles = [
			'background' => Background_Prop_Type::generate([]),
			'color' => Color_Prop_Type::generate(''),
			'border-color' => Color_Prop_Type::generate(''),
		];

		$icon_styles = [
			'color' => Color_Prop_Type::generate(''),
			'font-size' => Size_Prop_Type::generate([]), // For SVG size usually mapped via width/height or font-size depending on SVG setup
			'padding' => Dimensions_Prop_Type::generate([]),
			'background' => Background_Prop_Type::generate([]),
			'border-radius' => Dimensions_Prop_Type::generate([]),
		];

		$icon_hover_styles = [
			'color' => Color_Prop_Type::generate(''),
			'background' => Background_Prop_Type::generate([]),
		];

		$icon_selected_styles = [
			'color' => Color_Prop_Type::generate(''),
			'background' => Background_Prop_Type::generate([]),
		];

		$content_styles = [
			'background' => Background_Prop_Type::generate([]),
			'color' => Color_Prop_Type::generate(''),
			'border-style' => String_Prop_Type::generate(''),
			'border-color' => Color_Prop_Type::generate(''),
			'border-width' => Size_Prop_Type::generate([]),
			'border-radius' => Dimensions_Prop_Type::generate([]),
			'padding' => Dimensions_Prop_Type::generate([]),
			'margin' => Dimensions_Prop_Type::generate([]),
		];

		// Default design for the Header div block and its open/close icons.
		// These render as static, cached base-style CSS under the classes
		// e-aae-a-accordion-item-header_element / -header_icon, which
		// define_default_children() puts on those child elements. Being base
		// styles, per-element local styles (user edits) still override them.
		// Icon `display` stays scss-owned (state-driven show/hide), and
		// flex-shrink/svg-fill live in accordion.scss (no style-schema keys).
		$header_element_styles = [
			'display' => String_Prop_Type::generate( 'flex' ),
			'flex-direction' => String_Prop_Type::generate( 'row' ),
			'justify-content' => String_Prop_Type::generate( 'space-between' ),
		];

		$header_icon_styles = [

		//    'width' => String_Prop_Type::generate( '24px !important' ),
		// 	'height' => String_Prop_Type::generate( '24px !important' ),
			//'justify-content' => String_Prop_Type::generate( 'space-between' ),
			'width' => Size_Prop_Type::generate( [
				'size' => 24,
				'unit' => 'px',
			] ),
			'height' => Size_Prop_Type::generate( [
				'size' => 24,
				'unit' => 'px',
			] ),
			'align-items' => String_Prop_Type::generate( 'center' ),
			'justify-content' => String_Prop_Type::generate( 'center' ),
		];

		return [
			'base' => Style_Definition::make()
				->set_label( __( 'Item Box', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::HOVER )->add_props( $wrapper_hover_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::SELECTED )->add_props( $wrapper_selected_styles ) ),

			'header_element' => Style_Definition::make()
				->set_label( __( 'Header Layout', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( $header_element_styles ) ),

			'header_icon' => Style_Definition::make()
				->set_label( __( 'Header Icon', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( $header_icon_styles ) ),
			
			'header' => Style_Definition::make()
				->set_label( __( 'Header', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( $header_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::HOVER )->add_props( $header_hover_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::SELECTED )->add_props( $header_selected_styles ) ),
			
			'icon' => Style_Definition::make()
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( $icon_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::HOVER )->add_props( $icon_hover_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::SELECTED )->add_props( $icon_selected_styles ) ),
			
			'content' => Style_Definition::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( $content_styles ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-accordion-item' => __DIR__ . '/aae-a-accordion-item.html.twig',
		];
	}
}
