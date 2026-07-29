<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\SocialShare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * AAE Social Share Item — a single OPEN link container (icon + label) meant
 * to live inside AAE_A_Social_Share, or stand on its own.
 *
 * Unlike AAE_A_Social_Share_Main_Item (locked, vendor-enum driven, styled from
 * the parent's baked-in preset CSS), nothing here is locked and there is
 * no `vendor` prop — swap the icon, edit the label, or restyle from this
 * item's own Style panel exactly like the AAE Btn wrapper pattern.
 */
class AAE_A_Social_Share_Item extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'An open, freely editable share-link item (icon + label). Duplicate it inside an AAE Social Share to build a custom social-share row, or use it standalone as any icon+label link.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-social-share-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-social-share-item';
	}

	public function get_title() {
		return esc_html__( 'AAE Social Share Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-share';
	}

	public function get_keywords() {
		return [ 'social', 'share', 'item', 'aae', 'atomic', 'link', 'container' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'btn_url'      => String_Prop_Type::make()->default( '' ),
			'btn_target'   => String_Prop_Type::make()->default( '_blank' ),
			'btn_nofollow' => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Link', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Text_Control::bind_to( 'btn_url' )
						->set_label( __( 'URL', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'btn_target' )
						->set_label( __( 'Open In', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '_self',  'label' => __( 'Same Window', 'animation-addons-for-elementor' ) ],
							[ 'value' => '_blank', 'label' => __( 'New Window',  'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'btn_nofollow' )
						->set_label( __( 'Add Nofollow', 'animation-addons-for-elementor' ) ),
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
	 * Structural-only base style — no colors/typography beyond a sane
	 * neutral default, so preset templates and per-item Style panel edits
	 * both start from a clean, low-opinion baseline.
	 */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',         String_Prop_Type::generate( 'inline-flex' ) )
						->add_prop( 'align-items',      String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',              Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
						->add_prop( 'cursor',           String_Prop_Type::generate( 'pointer' ) )
						->add_prop( 'text-decoration',  String_Prop_Type::generate( 'none' ) )
						->add_prop( 'color',            Color_Prop_Type::generate( '#1a1a1a' ) )
				),
		];
	}

	/**
	 * Prefilled icon + label pair. Exposed publicly so the parent's
	 * define_default_children() can seed each fresh (unlocked) instance.
	 */
	public static function build_default_inner_children( string $vendor = 'facebook', string $label = 'Share' ): array {
		$svg_settings = [
			'classes' => Classes_Prop_Type::generate( [ 'aae-a-social-share-item-icon' ] ),
		];
		$svg_url = self::get_vendor_svg_url( $vendor );
		if ( $svg_url ) {
			$svg_settings['svg'] = Svg_Src_Prop_Type::generate( [
				'id'  => null,
				'url' => Url_Prop_Type::generate( $svg_url ),
			] );
		}

		$icon = Atomic_Svg::generate()
			->editor_settings( [ 'title' => 'Icon' ] )
			->settings( $svg_settings )
			->build();

		$title = Atomic_Paragraph::generate()
			->editor_settings( [ 'title' => 'Label' ] )
			->settings( [
				'classes'   => Classes_Prop_Type::generate( [ 'aae-a-social-share-item-label' ] ),
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( $label ),
					'children' => [],
				] ),
				'tag' => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		return [ $icon, $title ];
	}

	protected function define_default_children() {
		return self::build_default_inner_children();
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading', 'e-image' ];
	}

	protected function define_default_html_tag() {
		return 'a';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-social-share-item' => __DIR__ . '/aae-a-social-share-item.html.twig',
		];
	}

	public static function get_vendor_svg_url( string $vendor ): string {
		$allowed = [ 'facebook', 'twitter', 'linkedin', 'instagram', 'pinterest', 'tumblr', 'blogger', 'reddit' ];
		if ( ! in_array( $vendor, $allowed, true ) ) {
			return '';
		}

		$file = __DIR__ . '/assets/svg/' . $vendor . '.svg';
		if ( ! file_exists( $file ) ) {
			return '';
		}

		if ( ! defined( 'WCF_ADDONS_URL' ) ) {
			return '';
		}

		return WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/SocialShare/assets/svg/' . $vendor . '.svg';
	}
}
