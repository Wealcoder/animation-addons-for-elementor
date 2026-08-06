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
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transition_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Selection_Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
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

	/**
	 * Single source of truth for the icon's fixed square size.
	 *
	 * Read by BOTH define_base_styles() (the real default) AND
	 * AAE_A_Social_Share::get_frontend_css_override() (the CSS that must load
	 * after Elementor's base-desktop.css to win the tie against e-svg-base's
	 * native 65px default). The override lives on the CONTAINER class, not
	 * here, because only the container has a registered style_handle for
	 * Atomic::fix_frontend_atomic_css_order() to hook — see AAE_A_Btn for the
	 * single-widget version of this same pattern.
	 */
	const ICON_SIZE_PX = 30;

	/**
	 * Starter share links, one per supported vendor (see get_vendor_svg_url()
	 * for the same allow-list). Bare network endpoints, deliberately with NO
	 * `?u=`/`?url=` query string — a default child's settings are baked once
	 * into `_elementor_data` at drop time and would otherwise hardcode
	 * whichever post happened to be open in the editor at that moment,
	 * silently wrong the instant the layout is reused as a template/on
	 * another post. The V3 widget's own get_generated_link()
	 * (widgets/post-social-share.php) can do this correctly because it runs
	 * at RENDER time via get_the_permalink() — a builder who wants that here
	 * fills in the real permalink themselves in the Link section.
	 */
	const DEFAULT_SHARE_URLS = [
		'facebook'  => 'https://www.facebook.com/sharer/sharer.php',
		'twitter'   => 'https://twitter.com/intent/tweet',
		'linkedin'  => 'https://www.linkedin.com/shareArticle',
		'pinterest' => 'https://pinterest.com/pin/create/button/',
		'reddit'    => 'https://www.reddit.com/submit',
		'tumblr'    => 'https://www.tumblr.com/share/link',
		'blogger'   => 'https://www.blogger.com/blog-this.g',
		'instagram' => 'https://www.instagram.com/',
	];

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

	public function should_show_in_panel() {
		// Internal sub-element — managed via the parent's "Items" repeater
		// control, never dragged in independently from the widget panel.
		return false;
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
	 * A plain neutral CHIP by default — no theme colors assumed, so it reads
	 * fine before any preset/per-item Style panel edit is ever made. Icon and
	 * label are unstyled children (see build_default_inner_children()) that
	 * pick up size/weight/color entirely by inheriting from this base, so
	 * there is nothing to keep in sync if this palette changes later.
	 */
	protected function define_base_styles(): array {
		$item_styles = [
			'display'        => String_Prop_Type::generate( 'inline-flex' ),
			'align-items'    => String_Prop_Type::generate( 'center' ),
			'gap'            => Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ),

			'padding' => Dimensions_Prop_Type::generate( [
				'block-start'  => Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
				'inline-end'   => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
				'block-end'    => Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
				'inline-start' => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
			] ),

			'background' => Background_Prop_Type::generate( [
				'color' => Color_Prop_Type::generate( '#f2f3f5' ),
			] ),
			'color'         => Color_Prop_Type::generate( '#26282c' ),
			'border-radius' => Size_Prop_Type::generate( [ 'size' => 999, 'unit' => 'px' ] ),

			'font-size'      => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
			'line-height'    => Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
			'font-weight'    => String_Prop_Type::generate( '600' ),
			'text-decoration' => String_Prop_Type::generate( 'none' ),
			'cursor'         => String_Prop_Type::generate( 'pointer' ),

			'transition' => Transition_Prop_Type::generate( [
				Selection_Size_Prop_Type::generate( [
					'selection' => Key_Value_Prop_Type::generate( [
						'key'   => String_Prop_Type::generate( 'Background color' ),
						'value' => String_Prop_Type::generate( 'background-color' ),
					] ),
					'size' => Size_Prop_Type::generate( [ 'size' => 200, 'unit' => 'ms' ] ),
				] ),
			] ),
		];

		// Hover — darken the chip a touch, no motion (kept deliberately plain).
		$item_hover_styles = [
			'background' => Background_Prop_Type::generate( [
				'color' => Color_Prop_Type::generate( '#e4e6ea' ),
			] ),
		];

		// Pressed / keyboard-focus — same quiet feedback the Btn wrapper uses.
		$item_pressed_styles = [
			'opacity' => Size_Prop_Type::generate( [ 'size' => 85, 'unit' => '%' ] ),
		];

		$icon_styles = [
			'width'  => Size_Prop_Type::generate( [ 'size' => self::ICON_SIZE_PX, 'unit' => 'px' ] ),
			'height' => Size_Prop_Type::generate( [ 'size' => self::ICON_SIZE_PX, 'unit' => 'px' ] ),
		];

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $item_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::HOVER )->add_props( $item_hover_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::ACTIVE )->add_props( $item_pressed_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::FOCUS )->add_props( $item_pressed_styles ) ),

			'icon' => Style_Definition::make()
				->set_label( __( 'Icon', 'animation-addons-for-elementor' ) )
				->add_variant( Style_Variant::make()->add_props( $icon_styles ) ),
		];
	}

	/**
	 * Prefilled icon + label pair. Exposed publicly so the parent's
	 * define_default_children() can seed each fresh (unlocked) instance.
	 */
	public static function build_default_inner_children( string $vendor = 'facebook', string $label = 'Share' ): array {
		// Matches define_base_styles()'s "{element_type}-{key}" naming for the
		// 'icon' style key — same convention AAE_A_Btn uses for its own icon
		// class. Required, not cosmetic: this is the exact class the generated
		// "icon" style rule targets.
		$icon_class = static::get_element_type() . '-icon';

		$svg_settings = [
			'classes' => Classes_Prop_Type::generate( [ $icon_class ] ),
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

	/**
	 * The starter share URL for a vendor, or '' for one not in the map — same
	 * "unknown input degrades to empty, never an error" contract as
	 * get_vendor_svg_url().
	 */
	public static function get_default_share_url( string $vendor ): string {
		return self::DEFAULT_SHARE_URLS[ $vendor ] ?? '';
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
