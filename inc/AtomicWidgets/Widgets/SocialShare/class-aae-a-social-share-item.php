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
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

require_once __DIR__ . '/Parts/class-aae-a-social-share-item-icon.php';
require_once __DIR__ . '/Parts/class-aae-a-social-share-item-title.php';

use WCF_ADDONS\AtomicWidgets\Widgets\SocialShare\AAE_A_Social_Share_Item_Icon;
use WCF_ADDONS\AtomicWidgets\Widgets\SocialShare\AAE_A_Social_Share_Item_Title;

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
	 * Starter share links, one per supported vendor (see get_vendor_svg_url()
	 * for the same allow-list). Bare network endpoints, deliberately with NO
	 * `?u=`/`?url=` query string baked into `_elementor_data` at drop time —
	 * see resolve_share_href() below, which fills the real, CURRENT-page URL
	 * in at RENDER time instead (same idea as the V3 widget's own
	 * get_generated_link() in widgets/post-social-share.php, but keyed off
	 * the request itself rather than the loop's global $post, so it is
	 * correct on an archive/template too, not just a singular post).
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

	/**
	 * The query arg each vendor's sharer reads as "the page to share".
	 * Instagram is deliberately absent — it has no URL-based share endpoint,
	 * so a link to it is always left exactly as the builder set it.
	 */
	const SHARE_URL_PARAMS = [
		'facebook'  => 'u',
		'twitter'   => 'url',
		'linkedin'  => 'url',
		'pinterest' => 'url',
		'reddit'    => 'url',
		'tumblr'    => 'url',
		'blogger'   => 'u',
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
		// btn_url ("URL") and share_link ("Shared Link") are mutually
		// exclusive views of the same idea — which one is on screen follows
		// share_vendor, not the other way round, so only one is ever visible
		// or writable at a time.
		//
		// NOTE: a dependency term describes when the field is SHOWN, not
		// hidden — the editor's own gate is `isHidden = !isMet` regardless of
		// the 'effect' key (see @elementor/editor-editing-panel's
		// ConditionalField / DynamicSelectionControl, both `!isDependencyMet(...).isMet`).
		// So 'in' here means "show on a match", 'nin' means "show on anything else".
		$is_custom_network = Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ 'share_vendor' ],
				'value'    => [ '' ],
				'effect'   => 'hide',
			] )
			->get();

		$has_known_vendor = Dependency_Manager::make()
			->where( [
				'operator' => 'nin',
				'path'     => [ 'share_vendor' ],
				'value'    => [ '' ],
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'share_vendor' => String_Prop_Type::make()->default( '' ),

			// Custom / None mode only — a hand-written link, optionally using
			// ${u} / ${t} tokens (see apply_url_tokens()) to reach a network
			// that has no entry in the Social Network dropdown.
			'btn_url'      => String_Prop_Type::make()->default( '' )->set_dependencies( $is_custom_network ),

			// A known-network mode only — optional override of the
			// auto-generated share link for that specific network.
			'share_link'   => String_Prop_Type::make()->default( '' )->set_dependencies( $has_known_vendor ),

			'btn_target'   => String_Prop_Type::make()->default( '_blank' ),
			'btn_nofollow' => Boolean_Prop_Type::make()->default( false ),

			// Rendered unconditionally from this element's own twig as the
			// `aae-social-pulse` class (styled in social-share.scss) — never
			// via `classes`, which is exactly the "functional hook class"
			// anti-pattern the icon/title split above also exists to avoid.
			'hover_pulse'  => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Link', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'share_vendor' )
						->set_label( __( 'Social Network', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '',          'label' => __( 'Custom / None', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'facebook',  'label' => __( 'Facebook', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'twitter',   'label' => __( 'Twitter / X', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'linkedin',  'label' => __( 'LinkedIn', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'pinterest', 'label' => __( 'Pinterest', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'reddit',    'label' => __( 'Reddit', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'tumblr',    'label' => __( 'Tumblr', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'blogger',   'label' => __( 'Blogger', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'instagram', 'label' => __( 'Instagram', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'btn_url' )
						->set_label( __( 'URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'https://t.me/share/url?url=${u}&text=${t}' )
						->set_description( __( 'Leave empty to auto-share the current page. Or write your own link for a network not listed above — ${u} becomes the page URL, ${t} becomes its title.', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'share_link' )
						->set_label( __( 'Shared Link', 'animation-addons-for-elementor' ) )
						->set_placeholder( self::editor_preview_page_url() )
						->set_description( __( 'Optional. Leave empty to auto-share the current page on this network — fill in only to override it with a specific link.', 'animation-addons-for-elementor' ) ),

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
					Switch_Control::bind_to( 'hover_pulse' )
						->set_label( __( 'Pulse on Hover', 'animation-addons-for-elementor' ) ),

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

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $item_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::HOVER )->add_props( $item_hover_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::ACTIVE )->add_props( $item_pressed_styles ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::FOCUS )->add_props( $item_pressed_styles ) ),
		];
	}

	/**
	 * Prefilled icon + label pair. Exposed publicly so the parent's
	 * define_default_children() can seed each fresh (unlocked) instance.
	 *
	 * Both children are AAE's own element types (AAE_A_Social_Share_Item_Icon
	 * / _Title), not native e-svg/e-paragraph — see those classes' docblocks.
	 * Neither needs a `classes` entry: the old `aae-a-social-share-item-icon`
	 * / `-label` hook classes existed only so something could select these
	 * children from outside, and each child's own element type now serves
	 * that purpose without a class the panel would flag as missing.
	 */
	public static function build_default_inner_children( string $vendor = 'facebook', string $label = 'Share' ): array {
		$svg_settings = [];
		$svg_url = self::get_vendor_svg_url( $vendor );
		if ( $svg_url ) {
			$svg_settings['svg'] = Svg_Src_Prop_Type::generate( [
				'id'  => null,
				'url' => Url_Prop_Type::generate( $svg_url ),
			] );
		}

		$icon = AAE_A_Social_Share_Item_Icon::generate()
			->editor_settings( [ 'title' => 'Icon' ] )
			->settings( $svg_settings )
			->build();

		$title = AAE_A_Social_Share_Item_Title::generate()
			->editor_settings( [ 'title' => 'Title' ] )
			->settings( [
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
		return [
			'widget',
			'e-aae-a-social-share-item-icon',
			'e-aae-a-social-share-item-title',
			'e-svg',
			'e-paragraph',
			'e-heading',
			'e-image',
		];
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
	 * Adds the render-time-resolved href alongside the raw settings. Only
	 * runs on the real PHP render — the editor canvas re-renders this same
	 * twig CLIENT-SIDE from settings alone (see Has_Element_Template), where
	 * there is no request to resolve a "current page" from, so the twig falls
	 * back to whichever of `settings.btn_url` / `settings.share_link` is
	 * active there. See resolve_share_href().
	 */
	protected function build_template_context(): array {
		$settings = $this->get_atomic_settings();

		return array_merge( $this->build_base_template_context(), [
			'resolved_href' => self::resolve_share_href(
				(string) ( $settings['share_vendor'] ?? '' ),
				(string) ( $settings['btn_url'] ?? '' ),
				(string) ( $settings['share_link'] ?? '' )
			),
		] );
	}

	/**
	 * The href to actually render.
	 *
	 * Which raw field is "active" follows share_vendor: a known network reads
	 * share_link (the "Shared Link" override), anything else reads btn_url
	 * (the plain "URL" field, ${u}/${t} tokens and all — see
	 * apply_url_tokens()). Whichever it is, ${u}/${t} are substituted FIRST,
	 * so a hand-written link for an unlisted network (Telegram, WhatsApp,
	 * mailto:, …) already has its own URL/title by the time the vendor logic
	 * even looks at it. Only then, for a recognised vendor still missing the
	 * param its sharer needs, is the rest auto-filled from the current page
	 * (and title, and for Pinterest the featured image). A configured value
	 * that already has everything it needs — or points somewhere else
	 * entirely — is never touched beyond that token substitution.
	 */
	public static function resolve_share_href( string $vendor, string $btn_url, string $share_link ): string {
		$detected  = self::detect_share_vendor( $vendor, $btn_url );
		$has_field = '' !== $detected && isset( self::SHARE_URL_PARAMS[ $detected ] );

		$configured = self::apply_url_tokens( $has_field && '' !== $share_link ? $share_link : $btn_url );

		if ( ! $has_field ) {
			return $configured;
		}

		if ( ! self::share_link_needs_autofill( $detected, $configured ) ) {
			return $configured;
		}

		return self::build_share_url( $detected, $configured );
	}

	/**
	 * Substitutes ${u} (current page URL) / ${t} (current page title) inside
	 * a hand-written link, so a network with no Social Network dropdown entry
	 * can still auto-share the current page — e.g. Telegram:
	 * `https://t.me/share/url?url=${u}&text=${t}`. A no-op when neither token
	 * is present, so an ordinary custom URL is never touched.
	 */
	protected static function apply_url_tokens( string $url ): string {
		if ( '' === $url || ( false === strpos( $url, '${u}' ) && false === strpos( $url, '${t}' ) ) ) {
			return $url;
		}

		return strtr( $url, [
			'${u}' => rawurlencode( self::current_page_url() ),
			'${t}' => rawurlencode( self::current_page_title() ),
		] );
	}

	/**
	 * The explicit "Social Network" pick wins. Failing that, infer from the
	 * URL's own host, so items saved before this control existed (their URL
	 * already points at a bare sharer endpoint) keep working with no edit.
	 */
	protected static function detect_share_vendor( string $vendor, string $url ): string {
		if ( '' !== $vendor && isset( self::DEFAULT_SHARE_URLS[ $vendor ] ) ) {
			return $vendor;
		}

		$host = '' !== $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';
		if ( ! $host ) {
			return '';
		}

		foreach ( self::DEFAULT_SHARE_URLS as $candidate => $base_url ) {
			if ( 0 === strcasecmp( $host, (string) wp_parse_url( $base_url, PHP_URL_HOST ) ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * True when the URL is empty, OR points at the vendor's own sharer domain
	 * but is missing (or has blanked out) the param that sharer reads as the
	 * page to share. False for a URL pointing at any other domain — that's a
	 * deliberate custom link and is never rewritten.
	 */
	protected static function share_link_needs_autofill( string $vendor, string $url ): bool {
		if ( '' === $url || '#' === $url ) {
			return true;
		}

		$vendor_host = wp_parse_url( self::DEFAULT_SHARE_URLS[ $vendor ] ?? '', PHP_URL_HOST );
		$url_host    = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $vendor_host || ! $url_host || 0 !== strcasecmp( $url_host, $vendor_host ) ) {
			return false;
		}

		$param = self::SHARE_URL_PARAMS[ $vendor ] ?? '';
		if ( '' === $param ) {
			return false;
		}

		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_args );

		return empty( $query_args[ $param ] );
	}

	/**
	 * Fills in the missing share params from the CURRENT request, keeping
	 * anything the builder already put in the URL's query string (e.g. a
	 * hand-added `hashtags=` for Twitter). Existing values win over the
	 * auto-filled ones on the rare key that overlaps.
	 */
	protected static function build_share_url( string $vendor, string $existing_url ): string {
		$base_url = self::DEFAULT_SHARE_URLS[ $vendor ] ?? '';
		if ( '' === $base_url ) {
			return $existing_url;
		}

		parse_str( (string) wp_parse_url( $existing_url, PHP_URL_QUERY ), $existing_args );
		$existing_args = array_filter( $existing_args, static fn( $value ) => '' !== $value );

		$page_url = self::current_page_url();
		$title    = self::current_page_title();

		switch ( $vendor ) {
			case 'facebook':
				$auto_args = [ 'u' => $page_url ];
				break;
			case 'twitter':
				$auto_args = [ 'url' => $page_url, 'text' => $title ];
				break;
			case 'linkedin':
				$auto_args = [ 'url' => $page_url, 'title' => $title ];
				break;
			case 'pinterest':
				$auto_args = array_filter( [
					'url'         => $page_url,
					'description' => $title,
					'media'       => self::current_page_thumbnail(),
				] );
				break;
			case 'reddit':
				$auto_args = [ 'url' => $page_url, 'title' => $title ];
				break;
			case 'tumblr':
				$auto_args = [ 'url' => $page_url, 'name' => $title ];
				break;
			case 'blogger':
				$auto_args = [ 'u' => $page_url, 'n' => $title ];
				break;
			default:
				return $existing_url;
		}

		return add_query_arg( array_merge( $auto_args, $existing_args ), $base_url );
	}

	/**
	 * The URL shown as the "Shared Link" placeholder — a preview, nothing is
	 * ever read from it at render time. define_atomic_controls() runs once
	 * per editor session (this builds the shared widget-type config, not a
	 * per-instance one — see Has_Element_Template::get_initial_config()), and
	 * that session is always scoped to one post being edited, so
	 * Plugin::$instance->editor->get_post_id() names it reliably. Unlike
	 * current_page_url(), REQUEST_URI is no use here — in the editor it's the
	 * `post.php?...&action=elementor` admin URL, not the post's front-end one.
	 */
	protected static function editor_preview_page_url(): string {
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) ) {
			$post_id = (int) \Elementor\Plugin::$instance->editor->get_post_id();
			$permalink = $post_id ? get_permalink( $post_id ) : false;

			if ( $permalink ) {
				return $permalink;
			}
		}

		return self::current_page_url();
	}

	/**
	 * The page this element is rendering on right now — built from the
	 * REQUEST URI against the site's own configured home_url(), never from
	 * the Host header, so a spoofed Host can't land in the generated link.
	 * Deliberately NOT get_the_permalink(): that follows the loop's global
	 * $post, which is wrong the moment this sits in a template/header or
	 * inside someone else's loop (see "switch_to_post() nulls the global
	 * post" in this plugin's CLAUDE.md) — the request IS the current page.
	 */
	protected static function current_page_url(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

		return esc_url_raw( home_url( $request_uri ) );
	}

	/**
	 * Prefers the in-loop post title (right on a singular post/page), falls
	 * back to the resolved document title everywhere else (archives, search,
	 * a theme-builder template) so it's still meaningful off a single post.
	 */
	protected static function current_page_title(): string {
		$title = is_singular() ? get_the_title() : '';

		if ( '' === $title ) {
			$title = wp_get_document_title();
		}

		return html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES );
	}

	protected static function current_page_thumbnail(): string {
		if ( ! is_singular() ) {
			return '';
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id || ! has_post_thumbnail( $post_id ) ) {
			return '';
		}

		$thumbnail_url = get_the_post_thumbnail_url( $post_id, 'full' );

		return $thumbnail_url ? $thumbnail_url : '';
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
