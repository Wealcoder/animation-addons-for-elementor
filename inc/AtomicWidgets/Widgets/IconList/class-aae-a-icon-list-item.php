<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\IconList;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Icon_List_Item extends Atomic_Element_Base {
	use Has_Element_Template;

	/**
	 * Single source of truth for the icon's fixed square size.
	 *
	 * Read by BOTH define_base_styles() (the real default) AND
	 * AAE_A_Icon_List::get_frontend_css_override() (the CSS that must load
	 * after Elementor's base-desktop.css to win the tie against e-svg-base's
	 * native 65px default — same pattern as AAE_A_Btn / AAE_A_Social_Share).
	 * The old icon-list.scss rule targeting this class had NO `.elementor`
	 * ancestor, so at (0,1,0) specificity it could never beat e-svg-base's
	 * (0,2,0) — width/height always lost regardless of stylesheet order. On
	 * top of that, neither this class nor the container declared
	 * get_style_depends(), so icon-list.css was registered but never actually
	 * enqueued on any page. Both are fixed now: the size lives here + the
	 * override, and the container declares the dependency.
	 */
	const ICON_SIZE_PX = 16;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true ); // Container for nested SVG and Paragraph
	}

	public static function get_type() {
		return 'e-aae-a-icon-list-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-icon-list-item';
	}

	public function get_title() {
		return esc_html__( 'Icon List Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	public function get_keywords() {
		return [ 'list', 'item', 'icon', 'bullet', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false; // Only added via parent Icon List
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
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

	/**
	 * Elementor-native Icon List look — plain inline icon + text row, no
	 * background/radius/padding/hover chip. Icon and label are unstyled
	 * children that inherit size/color from this base.
	 */
	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display'     => String_Prop_Type::generate( 'inline-flex' ),
			'width'       => Size_Prop_Type::generate( [ 'size' => 'fit-content', 'unit' => 'custom' ] ),
			'align-items' => String_Prop_Type::generate( 'center' ),
			// Icon and label had NO gap at all — Atomic_Paragraph's own base
			// style sets margin:0, so the two sat glued together edge to edge.
			// 10px matches native Icon List's default "Icon Spacing".
			'gap'         => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),

			'margin' => Dimensions_Prop_Type::generate( [
				'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			] ),

			'color' => Color_Prop_Type::generate( '#26282c' ),

			'font-size'   => Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
			'line-height' => Size_Prop_Type::generate( [ 'size' => 18, 'unit' => 'px' ] ),
		];

		$icon_styles = [
			'width'  => Size_Prop_Type::generate( [ 'size' => self::ICON_SIZE_PX, 'unit' => 'px' ] ),
			'height' => Size_Prop_Type::generate( [ 'size' => self::ICON_SIZE_PX, 'unit' => 'px' ] ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),

			'icon' => Style_Definition::make()
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( $icon_styles ) ),
		];
	}

	/**
	 * Bundled starter icons, one per default label below — same allow-list +
	 * "unknown key degrades to Elementor's own placeholder, never an error"
	 * contract as AAE_A_Social_Share_Item::get_vendor_svg_url().
	 */
	public static function get_icon_svg_url( string $icon ): string {
		$allowed = [ 'bolt', 'sliders', 'headset' ];
		if ( ! in_array( $icon, $allowed, true ) ) {
			return '';
		}

		$file = __DIR__ . '/assets/svg/' . $icon . '.svg';
		if ( ! file_exists( $file ) ) {
			return '';
		}

		if ( ! defined( 'WCF_ADDONS_URL' ) ) {
			return '';
		}

		return WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/IconList/assets/svg/' . $icon . '.svg';
	}

	/**
	 * Prefilled icon + label pair. Exposed publicly so the parent's
	 * define_default_children() can seed each fresh instance with its own
	 * real-world label + matching icon — same shape as
	 * AAE_A_Social_Share_Item::build_default_inner_children(). $icon is a
	 * bundled key (see get_icon_svg_url()); '' or unknown keeps Elementor's
	 * own default SVG placeholder, exactly like before this was added.
	 */
	public static function build_default_inner_children( string $label = 'List Item Text', string $icon = '' ): array {
		// Matches define_base_styles()'s "{element_type}-{key}" naming for the
		// 'icon' style key — same convention AAE_A_Btn / AAE_A_Social_Share_Item
		// use for their own icon class.
		$icon_class = static::get_element_type() . '-icon';

		$svg_settings = [
			'classes' => Classes_Prop_Type::generate( [ $icon_class ] ),
		];
		$svg_url = self::get_icon_svg_url( $icon );
		if ( $svg_url ) {
			$svg_settings['svg'] = Svg_Src_Prop_Type::generate( [
				'id'  => null,
				'url' => Url_Prop_Type::generate( $svg_url ),
			] );
		}

		$svg = Atomic_Svg::generate()
			->settings( $svg_settings )
			->build();

		$paragraph = Atomic_Paragraph::generate()
			->settings( [
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( $label ),
					'children' => [],
				] ),
				'tag'       => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		return [ $svg, $paragraph ];
	}

	protected function define_default_children() {
		return self::build_default_inner_children();
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading','e-flexbox' ];
	}

	protected function define_default_html_tag() {
		return 'li';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-icon-list-item' => __DIR__ . '/aae-a-icon-list-item.html.twig',
		];
	}
}
