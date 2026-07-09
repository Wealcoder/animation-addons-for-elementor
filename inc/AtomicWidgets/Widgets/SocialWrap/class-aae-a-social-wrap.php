<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\SocialWrap;

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
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-social-wrap-item.php';

use WCF_ADDONS\AtomicWidgets\Widgets\SocialWrap\AAE_A_Social_Wrap_Item;

/**
 * AAE Social Wrap — an open atomic container for hand-built social share
 * designs.
 *
 * Where AAE_A_Social_Share is a locked composite (fixed vendor items,
 * preset CSS baked into the parent's Twig <style>), this widget is a
 * blank canvas: default children are three ordinary, unlocked
 * AAE_A_Social_Wrap_Item instances you can freely restyle, duplicate, or
 * delete — the same "minimal wrapper for Elementor-native templates"
 * pattern as AAE_A_Btn / AAE_A_Btn_Pro.
 */
class AAE_A_Social_Wrap extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'An open social-share row you build yourself: three unlocked icon+label items to duplicate, restyle, or delete. Pair with the ready-made templates for the minimal / outlined / solid looks.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-social-wrap';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-social-wrap';
	}

	public function get_title() {
		return esc_html__( 'AAE Social Wrap', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'social', 'share', 'post', 'aae', 'atomic', 'list', 'container', 'wrap' ];
	}

	public function get_icon() {
		return 'eicon-share';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items( [
					AAE_A_Preset_Picker_Control::make()
						->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
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
	 * Outer container — MINIMAL wrapper base styles only (Btn pattern).
	 * Everything else (colors, chips, hover states) belongs to the items
	 * or a template's own local/global classes.
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
				),
		];
	}

	/**
	 * Three ordinary, UNLOCKED items — a usable starting point, not a
	 * fixed composition. Delete, duplicate, or reorder them freely.
	 */
	protected function define_default_children() {
		$defaults = [
			'facebook' => 'Facebook',
			'twitter'  => 'Twitter',
			'linkedin' => 'LinkedIn',
		];

		$children = [];
		foreach ( $defaults as $vendor => $label ) {
			$children[] = AAE_A_Social_Wrap_Item::generate()
				->editor_settings( [ 'title' => $label ] )
				->children( AAE_A_Social_Wrap_Item::build_default_inner_children( $vendor, $label ) )
				->build();
		}

		return $children;
	}

	protected function define_allowed_child_types() {
		return [
			'e-aae-a-social-wrap-item',
			'widget',
			'e-heading',
			'e-paragraph',
			'e-svg',
			'e-image',
			'e-divider',
			'e-flexbox',
			'e-div-block',
		];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-social-wrap' => __DIR__ . '/aae-a-social-wrap.html.twig',
		];
	}

	public function get_style_depends(): array {
		return ['aae-a-social-wrap-css'];
	}
}
