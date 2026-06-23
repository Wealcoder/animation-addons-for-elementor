<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\SiteLogo;

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Attachment_Id_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Site_Logo extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-site-logo';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-site-logo';
	}

	public function get_title(): string {
		return esc_html__( 'AAE Site Logo', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-site-logo';
	}

	public function get_keywords(): array {
		return [ 'site', 'logo', 'branding', 'atomic', 'header' ];
	}

	protected static function define_props_schema(): array {
		$is_custom_logo = Dependency_Manager::make()
			->where( [
				'operator' => 'nin',
				'path'     => [ 'sl_logo_to' ],
				'value'    => [ 'custom' ],
				'effect'   => 'hide',
			] )
			->get();

		$is_custom_link = Dependency_Manager::make()
			->where( [
				'operator' => 'nin',
				'path'     => [ 'sl_link_to' ],
				'value'    => [ 'custom' ],
				'effect'   => 'hide',
			] )
			->get();

		$has_link = Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ 'sl_link_to' ],
				'value'    => [ 'none' ],
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'           => Classes_Prop_Type::make()->default( [] ),
			'attributes'        => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Image source mode
			'sl_logo_to'        => String_Prop_Type::make()
				->enum( [ 'site_logo', 'custom' ] )
				->default( 'site_logo' ),

			// Custom image URL — only active in 'custom' mode; updated dynamically for 'site_logo' mode
			'sl_custom_img_url' => String_Prop_Type::make()
				->default( '' )
				->set_dependencies( $is_custom_logo ),

			// Computed at render time by get_atomic_settings(); no panel control.
			// In 'site_logo' mode: current WP custom logo URL.
			// In 'custom' mode: empty (children_placeholder handles rendering).
			'sl_site_logo_url'  => String_Prop_Type::make()->default( '' ),
			'sl_site_logo_alt'  => String_Prop_Type::make()->default( '' ),

			// Link controls
			'sl_link_to'        => String_Prop_Type::make()
				->enum( [ 'none', 'site_url', 'custom' ] )
				->default( 'site_url' ),
			'sl_link_url'       => String_Prop_Type::make()
				->default( '' )
				->set_dependencies( $is_custom_link ),
			'sl_link_target'    => String_Prop_Type::make()
				->enum( [ '_self', '_blank' ] )
				->default( '_self' )
				->set_dependencies( $has_link ),
			'sl_link_nofollow'  => Boolean_Prop_Type::make()
				->default( false )
				->set_dependencies( $has_link ),

			// Resolved href computed by get_atomic_settings(); no panel control.
			'sl_resolved_href'  => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Site Logo', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'sl_logo_to' )
						->set_label( __( 'Logo', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'site_logo', 'label' => __( 'Site Logo (WordPress)',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'custom',    'label' => __( 'Custom (Child Image)',   'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'sl_link_to' )
						->set_label( __( 'Link', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'none',     'label' => __( 'None',       'animation-addons-for-elementor' ) ],
							[ 'value' => 'site_url', 'label' => __( 'Site URL',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'custom',   'label' => __( 'Custom URL', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'sl_link_url' )
						->set_label( __( 'Custom URL', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'sl_link_target' )
						->set_label( __( 'Open In', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '_self',  'label' => __( 'Same Window', 'animation-addons-for-elementor' ) ],
							[ 'value' => '_blank', 'label' => __( 'New Window',  'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'sl_link_nofollow' )
						->set_label( __( 'Add Nofollow', 'animation-addons-for-elementor' ) ),
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

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'display'         => String_Prop_Type::generate( 'inline-flex' ),
					'align-items'     => String_Prop_Type::generate( 'center' ),
					'text-decoration' => String_Prop_Type::generate( 'none' ),
					'cursor'          => String_Prop_Type::generate( 'pointer' ),
				] ) ),
		];
	}

	/**
	 * Default child: native Atomic_Image used in 'custom' logo mode.
	 * In 'site_logo' mode this child is still present in the tree but the
	 * template bypasses children_placeholder and renders the WP logo directly.
	 */
	protected function define_default_children(): array {
		$custom_logo_id = (int) get_theme_mod( 'custom_logo' );

		if ( $custom_logo_id > 0 ) {
			$img_src = Image_Src_Prop_Type::generate( [
				'id'  => Image_Attachment_Id_Prop_Type::generate( $custom_logo_id ),
				'url' => null,
			] );
		} else {
			$img_src = Image_Src_Prop_Type::generate( [
				'id'  => null,
				'url' => Url_Prop_Type::generate( \Elementor\Utils::get_placeholder_image_src() ),
			] );
		}

		return [
			Atomic_Image::generate()
				->settings( [
					'image' => Image_Prop_Type::generate( [
						'src'  => $img_src,
						'size' => String_Prop_Type::generate( 'full' ),
					] ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'e-image', 'e-svg' ];
	}

	protected function define_default_html_tag(): string {
		return 'a';
	}

	/**
	 * Inject two sets of dynamic values before the template renders:
	 *
	 * 1. sl_site_logo_url / sl_site_logo_alt — always the current WP custom logo,
	 *    resolved fresh on every request so logo changes in the Customizer are
	 *    instantly reflected without re-saving the page.
	 *
	 * 2. sl_resolved_href — the actual href value derived from sl_link_to,
	 *    because Twig cannot call home_url() directly.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		// --- WP custom logo (always injected, template uses it in site_logo mode) ---
		$custom_logo_id = (int) get_theme_mod( 'custom_logo' );

		if ( $custom_logo_id > 0 ) {
			$logo_src = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			$settings['sl_site_logo_url'] = $logo_src ? esc_url( $logo_src[0] ) : '';
			$settings['sl_site_logo_alt'] = get_post_meta( $custom_logo_id, '_wp_attachment_image_alt', true )
				?: get_bloginfo( 'name' );
		} else {
			$settings['sl_site_logo_url'] = esc_url( \Elementor\Utils::get_placeholder_image_src() );
			$settings['sl_site_logo_alt'] = get_bloginfo( 'name' );
		}

		// --- Resolved link href ---
		switch ( $settings['sl_link_to'] ?? 'site_url' ) {
			case 'site_url':
				$settings['sl_resolved_href'] = esc_url( home_url( '/' ) );
				break;
			case 'custom':
				$settings['sl_resolved_href'] = esc_url( $settings['sl_link_url'] ?? '' );
				break;
			default:
				$settings['sl_resolved_href'] = '';
		}

		return $settings;
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-site-logo' => __DIR__ . '/aae-a-site-logo.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-site-logo-css' ];
	}
}
