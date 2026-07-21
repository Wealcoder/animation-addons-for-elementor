<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\SocialShareMain;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-social-share-main-item.php';

use WCF_ADDONS\AtomicWidgets\Widgets\SocialShareMain\AAE_A_Social_Share_Main_Item;

/**
 * AAE Social Share Main — composite atomic widget.
 *
 * Structure:
 *   AAE_A_Social_Share_Main (parent — <ul>)
 *     ├─ AAE_A_Social_Share_Main_Item (locked — Facebook)
 *     │    ├─ Atomic_Svg       (icon)
 *     │    └─ Atomic_Paragraph (title)
 *     ├─ AAE_A_Social_Share_Main_Item (locked — Twitter)
 *     ├─ AAE_A_Social_Share_Main_Item (locked — LinkedIn)
 *     └─ AAE_A_Social_Share_Main_Item (locked — Instagram)
 *
 * Every visual layer is an independent atomic child styleable from its
 * own Style panel. NO external SCSS/CSS file — per-element default
 * design lives in the Twig <style> block scoped via [data-id][data-preset]
 * and wrapped in `:where()` so the user's Style panel rules at (0,1,0)
 * always beat the defaults at (0,0,0).
 */
class AAE_A_Social_Share_Main extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A composite social share widget with locked vendor items (Facebook, Twitter, LinkedIn, Instagram). Each item, icon, and title is an independent atomic child styleable from its own Style panel.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-social-share-main';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-social-share-main';
	}

	public function get_title() {
		return esc_html__( 'AAE Social Share Main', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'social', 'share', 'post', 'atomic', 'composite', 'aae' ];
	}

	public function get_icon() {
		return 'eicon-share';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'         => Classes_Prop_Type::make()->default( [] ),
			'attributes'      => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Visual preset — drives a `data-preset` attribute on the wrapper
			// that scopes a bank of override CSS in the Twig <style>. Default
			// matches the per-element defaults emitted in <style>, so the
			// default selection requires NO overrides.
			'preset'          => String_Prop_Type::make()
				->enum( [ 'minimal', 'outlined', 'solid' ] )
				->default( 'outlined' ),

			'show_title'      => Boolean_Prop_Type::make()->default( true ),
			'show_count'      => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items(
					[
						AAE_A_Preset_Picker_Control::make()
							->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
							->set_meta( [ 'layout' => 'custom' ] ),
					]
				),

			Section::make()
				->set_label( __( 'Social Share Main', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'preset' )
						->set_label( __( 'Preset', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'minimal',  'label' => __( 'Minimal — flat icons',           'animation-addons-for-elementor' ) ],
							[ 'value' => 'outlined', 'label' => __( 'Outlined — bordered chips',      'animation-addons-for-elementor' ) ],
							[ 'value' => 'solid',    'label' => __( 'Solid — brand-coloured buttons', 'animation-addons-for-elementor' ) ],
						] ),
					Switch_Control::bind_to( 'show_title' )
						->set_label( __( 'Show Title', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'show_count' )
						->set_label( __( 'Show Share Count', 'animation-addons-for-elementor' ) ),
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
	 * Outer container — MINIMAL wrapper base styles only (Timeline pattern).
	 *
	 * Layout-defining props (display, flex-direction, gap) belong here only
	 * as the bare-minimum row layout; per-preset overrides live in the Twig
	 * <style> block at [data-id][data-preset] specificity so the user's
	 * Style panel rules at (0,1,0) keep priority.
	 */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display',        String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'row' ) )
						->add_prop( 'flex-wrap',      String_Prop_Type::generate( 'wrap' ) )
						->add_prop( 'align-items',    String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',            Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ) )
						->add_prop( 'list-style',     String_Prop_Type::generate( 'none' ) )
				),
		];
	}

	/**
	 * Locked composition. Four vendor items spawned with pre-filled
	 * vendor + icon SVG + title text via `->children([...])`.
	 */
	protected function define_default_children() {
		$vendors = [
			'facebook'  => 'Facebook',
			'twitter'   => 'Twitter',
			'linkedin'  => 'LinkedIn',
			'instagram' => 'Instagram',
		];

		$children = [];
		foreach ( $vendors as $vendor => $label ) {
			$children[] = AAE_A_Social_Share_Main_Item::generate()
				->editor_settings( [ 'title' => $label ] )
				->settings( [
					'vendor' => String_Prop_Type::generate( $vendor ),
				] )
				->children( AAE_A_Social_Share_Main_Item::build_default_inner_children( $vendor, $label ) )
				->build();
		}

		return $children;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-social-share-main-item' ];
	}

	protected function define_default_html_tag() {
		return 'ul';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-social-share-main' => __DIR__ . '/aae-a-social-share-main.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-social-share-main-js' ];
	}
}
