<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\SocialShare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * Single social share item inside AAE_A_Social_Share.
 *
 * Locked composite child tree per item:
 *   1. Atomic_Svg       (icon — class `aae-a-social-share-item-icon`)
 *   2. Atomic_Paragraph (title — class `aae-a-social-share-item-title`)
 *
 * Per-element visual defaults are NOT defined here — they live in the
 * PARENT's Twig <style> block scoped via `[data-id][data-preset]` and
 * wrapped in `:where()` so the user's Style panel rules at (0,1,0)
 * always beat them. The item's own `define_base_styles()` is limited
 * to minimal structural layout (display, position, gap, cursor) so
 * Style-panel overrides remain free.
 */
class AAE_A_Social_Share_Item extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A single social share item — icon + title — inside an AAE Social Share parent.';

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
		return esc_html__( 'Social Share Item', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'social', 'share', 'item', 'atomic', 'aae' ];
	}

	public function get_icon() {
		return 'eicon-share';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'vendor'     => String_Prop_Type::make()
				->enum( [ 'facebook', 'twitter', 'linkedin', 'instagram', 'pinterest', 'tumblr', 'blogger', 'reddit' ] )
				->default( 'facebook' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Social Item', 'animation-addons-for-elementor' ) )
				->set_id( 'item_settings' )
				->set_items( [
					Select_Control::bind_to( 'vendor' )
						->set_label( __( 'Vendor', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'facebook',  'label' => __( 'Facebook',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'twitter',   'label' => __( 'Twitter',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'linkedin',  'label' => __( 'LinkedIn',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'instagram', 'label' => __( 'Instagram', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'pinterest', 'label' => __( 'Pinterest', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'tumblr',    'label' => __( 'Tumblr',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'blogger',   'label' => __( 'Blogger',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'reddit',    'label' => __( 'Reddit',    'animation-addons-for-elementor' ) ],
						] ),
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
	 * Item-wrapper base style — STRUCTURAL ONLY.
	 *
	 * No colors, typography, sizing — all of that lives in the parent's
	 * Twig <style> block wrapped in `:where()` so Style-panel overrides
	 * always win. Compound child selectors are forbidden here too: each
	 * inner SVG/Paragraph has its own Style panel and any rule emitted
	 * at (0,2,0) here would silently block their (0,1,0) panel rules.
	 */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',    String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'display',    String_Prop_Type::generate( 'inline-flex' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',        Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
						->add_prop( 'cursor',     String_Prop_Type::generate( 'pointer' ) )
				),
		];
	}

	/**
	 * Static fallback children — used only when an item is instantiated
	 * WITHOUT the parent pre-supplying a `->children([...])` tree.
	 *
	 * Helper exposed publicly so the parent can call it with per-vendor
	 * defaults when composing each locked instance.
	 */
	public static function build_default_inner_children( string $vendor = 'facebook', string $label = 'Facebook' ): array {
		// 1. Icon (Atomic_Svg) — pre-filled with the brand SVG file shipped
		//    inside `assets/svg/<vendor>.svg`. User can swap from Style panel
		//    by uploading a custom SVG; without an override our default file
		//    is what renders.
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
			->is_locked( true )
			->editor_settings( [ 'title' => 'Icon' ] )
			->settings( $svg_settings )
			->build();

		// 2. Title (Atomic_Paragraph)
		$title = Atomic_Paragraph::generate()
			->is_locked( true )
			->editor_settings( [ 'title' => 'Title' ] )
			->settings( [
				'classes'   => Classes_Prop_Type::generate( [ 'aae-a-social-share-item-title' ] ),
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( $label ),
					'children' => [],
				] ),
				'tag'       => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		return [ $icon, $title ];
	}

	protected function define_default_children() {
		return self::build_default_inner_children();
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading' ];
	}

	protected function define_default_html_tag() {
		return 'li';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-social-share-item' => __DIR__ . '/aae-a-social-share-item.html.twig',
		];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$vendor = isset( $settings['vendor'] ) ? $settings['vendor'] : 'facebook';

		$settings['share_url']   = self::build_share_url( $vendor );
		$settings['share_count'] = self::get_share_count( $vendor );

		return $settings;
	}

	public static function get_vendor_svg_url( $vendor ) {
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

	private static function build_share_url( $vendor ) {
		$permalink = get_the_permalink();
		$title     = get_the_title();

		if ( ! $permalink ) {
			return '#';
		}

		switch ( $vendor ) {
			case 'facebook':
				return add_query_arg( [ 'u' => $permalink ], 'https://www.facebook.com/sharer/sharer.php' );
			case 'twitter':
				return add_query_arg( [ 'url' => $permalink, 'text' => $title ], 'https://twitter.com/intent/tweet' );
			case 'linkedin':
				return add_query_arg( [
					'url'     => $permalink,
					'mini'    => true,
					'title'   => $title,
					'summary' => $title,
					'source'  => $permalink,
				], 'https://www.linkedin.com/shareArticle' );
			case 'pinterest':
				return add_query_arg( [
					'media'       => get_the_post_thumbnail_url( get_the_ID(), 'full' ),
					'url'         => $permalink,
					'description' => $title,
				], 'https://pinterest.com/pin/create/button/' );
			case 'reddit':
				return add_query_arg( [ 'url' => $permalink, 'title' => $title ], 'https://www.reddit.com/submit' );
			case 'tumblr':
				return add_query_arg( [ 'url' => $permalink, 'name' => $title ], 'https://www.tumblr.com/share/link' );
			case 'blogger':
				return add_query_arg( [ 'u' => $permalink, 'n' => $title ], 'https://www.blogger.com/blog-this.g' );
			case 'instagram':
				return 'https://www.instagram.com/';
		}

		return '#';
	}

	private static function get_share_count( $vendor ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return 0;
		}

		$current_shares = get_post_meta( $post_id, 'aae_post_shares', true );

		if ( is_array( $current_shares ) && isset( $current_shares[ $vendor ] ) ) {
			$count = (int) $current_shares[ $vendor ];
		} else {
			$count = 0;
		}

		if ( function_exists( 'aaeaddon_format_number_count' ) ) {
			return aaeaddon_format_number_count( $count );
		}

		return $count;
	}
}
