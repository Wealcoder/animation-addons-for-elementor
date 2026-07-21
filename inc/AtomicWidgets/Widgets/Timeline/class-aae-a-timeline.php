<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Timeline;

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
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-timeline-item.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Item;

class AAE_A_Timeline extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A composite vertical timeline with four locked event items. Each item, marker, date, title, and description is an independent atomic child styleable from its own Style panel.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-timeline';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-timeline';
	}

	public function get_title() {
		return esc_html__( 'AAE Timeline', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'timeline', 'history', 'roadmap', 'composite', 'aae' ];
	}

	public function get_icon() {
		return 'eicon-time-line';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'preset'     => String_Prop_Type::make()
				->enum( [
					'editorial-rail',
					'heritage-split',
					'roadmap-track',
					'case-study',
					'social',
				] )
				->default( 'editorial-rail' ),
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
				->set_label( __( 'Timeline', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'preset' )
						->set_label( __( 'Preset', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'editorial-rail', 'label' => __( 'Editorial Rail — green vertical',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'heritage-split', 'label' => __( 'Heritage Split — warm alternating', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'roadmap-track',  'label' => __( 'Roadmap Track — purple horizontal', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'case-study',     'label' => __( 'Case Study Steps — bold navy',      'animation-addons-for-elementor' ) ],
							[ 'value' => 'social',         'label' => __( 'Social — purple dotted split',      'animation-addons-for-elementor' ) ],
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

	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'width',         Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
						->add_prop( 'margin-inline', String_Prop_Type::generate( 'auto' ) )
				),
		];
	}

	protected function define_default_children() {
		$items = [
			[
				'date'   => '2022',
				'number' => '01',
				'title'  => __( 'Foundation System', 'animation-addons-for-elementor' ),
				'desc'   => __( 'Define the timeline item structure, repeatable content slots, and clean visual hierarchy.', 'animation-addons-for-elementor' ),
			],
			[
				'date'   => '2023',
				'number' => '02',
				'title'  => __( 'Visual Presets', 'animation-addons-for-elementor' ),
				'desc'   => __( 'Create a stronger set of timeline styles that feel like selectable design presets.', 'animation-addons-for-elementor' ),
			],
			[
				'date'   => '2024',
				'number' => '03',
				'title'  => __( 'Responsive Layouts', 'animation-addons-for-elementor' ),
				'desc'   => __( 'Support vertical, split, horizontal, tile, archive, and case-study timeline shapes.', 'animation-addons-for-elementor' ),
			],
			[
				'date'   => '2025',
				'number' => '04',
				'title'  => __( 'Widget Ready', 'animation-addons-for-elementor' ),
				'desc'   => __( 'Map each visual layer into editable Elementor child elements without generated decoration.', 'animation-addons-for-elementor' ),
			],
		];

		$children = [];
		foreach ( $items as $index => $item ) {
			$children[] = AAE_A_Timeline_Item::generate()
				->editor_settings( [ 'title' => 'Item ' . ( $index + 1 ) ] )
				->children(
					AAE_A_Timeline_Item::build_default_inner_children(
						$item['date'],
						$item['number'],
						$item['title'],
						$item['desc']
					)
				)
				->build();
		}

		$children[] = Atomic_Paragraph::generate()
			->is_locked( true )
			->editor_settings( [ 'title' => 'Spine' ] )
			->settings( [
				'classes'   => Classes_Prop_Type::generate( [ 'aae-a-timeline-spine' ] ),
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( "\u{00A0}" ),
					'children' => [],
				] ),
				'tag'       => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		return $children;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-timeline-item', 'e-paragraph', 'e-divider' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-timeline' => __DIR__ . '/aae-a-timeline.html.twig',
		];
	}
}
