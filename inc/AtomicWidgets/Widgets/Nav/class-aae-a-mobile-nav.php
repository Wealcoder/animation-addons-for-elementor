<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Flexbox\Flexbox;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAE_A_Mobile_Nav extends Atomic_Element_Base {
	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() { return 'e-aae-a-mobile-nav'; }
	public static function get_element_type(): string { return 'e-aae-a-mobile-nav'; }
	public function get_title() { return esc_html__( 'Mobile Nav', 'animation-addons-for-elementor' ); }
	public function get_icon() { return 'eicon-menu-bar'; }
	public function should_show_in_panel() { return false; }

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'source_nav_id' => String_Prop_Type::make()->default( '' ),
			'enabled' => Boolean_Prop_Type::make()->default( true ),
			'breakpoint' => String_Prop_Type::make()->default( '767' ),
			'position' => String_Prop_Type::make()->default( 'right' ),
			'close_on_link' => Boolean_Prop_Type::make()->default( true ),
			'lock_scroll' => Boolean_Prop_Type::make()->default( true ),
		];
	}

	protected function define_atomic_controls(): array { return []; }

	/**
	 * Compound-selector keys (Image Compare pattern): the key becomes the id
	 * `e-aae-a-mobile-nav-base .aae-mobile-nav-hamburger`, rendered as the
	 * two-class selector `.e-aae-a-mobile-nav-base .aae-mobile-nav-hamburger`
	 * — enough specificity to beat e-svg's own 65px single-class base style.
	 * The bare 'base' key must exist so the Twig root picks up the scope
	 * class via `base_styles.base`.
	 */
	protected function define_base_styles(): array {
		$icon_size = Size_Prop_Type::generate( [ 'size' => 15, 'unit' => 'px' ] );

		$icon_style = fn() => Style_Definition::make()
			->add_variant(
				Style_Variant::make()
					->add_prop( 'width', $icon_size )
					->add_prop( 'height', $icon_size )
			);

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
				),
			self::BASE_STYLE_KEY . ' .aae-mobile-nav-hamburger'      => $icon_style(),
			self::BASE_STYLE_KEY . ' .aae-mobile-nav-close-icon'     => $icon_style(),
			self::BASE_STYLE_KEY . ' .aae-mobile-nav-arrow-template' => $icon_style(),
			self::BASE_STYLE_KEY . ' .aae-mobile-submenu-icon'       => $icon_style(),
		];
	}

	private function svg( string $file, string $title, string $class ): array {
		return Atomic_Svg::generate()
			->editor_settings( [ 'title' => $title ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ $class ] ),
				'svg' => Svg_Src_Prop_Type::generate( [
					'id' => null,
					'url' => Url_Prop_Type::generate( WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Nav/assets/icons/' . $file ),
				] ),
			] )->build();
	}

	protected function define_default_children(): array {
		$toggle = Flexbox::generate()
			->editor_settings( [ 'title' => 'Mobile Menu Toggle' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-mobile-nav-toggle' ] ),
				'tag' => String_Prop_Type::generate( 'button' ),
			] )
			->children( [ $this->svg( 'hamburger.svg', 'Hamburger Icon', 'aae-mobile-nav-hamburger' ) ] )
			->build();

		$overlay = Flexbox::generate()
			->editor_settings( [ 'title' => 'Mobile Menu Overlay' ] )
			->settings( [ 'classes' => Classes_Prop_Type::generate( [ 'aae-mobile-nav-overlay' ] ) ] )
			->build();

		$close = Flexbox::generate()
			->editor_settings( [ 'title' => 'Mobile Menu Close' ] )
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'aae-mobile-nav-close' ] ),
				'tag' => String_Prop_Type::generate( 'button' ),
			] )
			->children( [ $this->svg( 'close.svg', 'Close Icon', 'aae-mobile-nav-close-icon' ) ] )
			->build();

		$arrow = $this->svg( 'chevron-down.svg', 'Submenu Arrow Template', 'aae-mobile-nav-arrow-template' );
		$header = Flexbox::generate()
			->editor_settings( [ 'title' => 'Offcanvas Header' ] )
			->settings( [ 'classes' => Classes_Prop_Type::generate( [ 'aae-mobile-nav-header' ] ) ] )
			->children( [ $close ] )
			->build();
		$menu_area = Flexbox::generate()
			->editor_settings( [ 'title' => 'Menu Area' ] )
			->settings( [ 'classes' => Classes_Prop_Type::generate( [ 'aae-mobile-nav-menu-area' ] ) ] )
			->children( [ $arrow ] )
			->build();
		$footer = Flexbox::generate()
			->editor_settings( [ 'title' => 'Footer Custom Container' ] )
			->settings( [ 'classes' => Classes_Prop_Type::generate( [ 'aae-mobile-nav-footer' ] ) ] )
			->build();
		$drawer = Flexbox::generate()
			->editor_settings( [ 'title' => 'Mobile Menu Drawer' ] )
			->settings( [ 'classes' => Classes_Prop_Type::generate( [ 'aae-mobile-nav-drawer' ] ) ] )
			->children( [ $header, $menu_area, $footer ] )
			->build();

		/* Keep every structural control at root level. Elementor v4 can freeze
		 * while switching devices when an atomic tree reaches four content
		 * levels (companion → drawer → button → SVG). The empty drawer is the
		 * frontend mount surface; close and arrow are positioned siblings. */
		return [ $toggle, $overlay, $drawer ];
	}

	protected function define_allowed_child_types(): array { return [ 'e-flexbox', 'e-svg' ]; }
	protected function define_default_html_tag() { return 'div'; }
	protected function get_templates(): array {
		return [ 'elementor/elements/aae-a-mobile-nav' => __DIR__ . '/aae-a-mobile-nav.html.twig' ];
	}
	public function get_script_depends(): array { return [ 'aae-a-nav-js' ]; }
	public function get_style_depends(): array { return [ 'aae-a-nav-css' ]; }
}
