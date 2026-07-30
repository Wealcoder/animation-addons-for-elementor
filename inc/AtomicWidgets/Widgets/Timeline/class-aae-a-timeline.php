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
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-timeline-item.php';
require_once __DIR__ . '/class-aae-a-timeline-items-control.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Item;
use WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Items_Control;

/**
 * AAE Timeline — an open composite timeline container (Btn pattern).
 *
 * Minimal counterpart of TimelineMain: no `preset` prop, no preset-driven
 * <style> block. Out of the box it renders a plain vertical list; the
 * "Presets" section (Apply Preset picker, presets/*.json) swaps it for a
 * fully designed look — editorial-rail, heritage-split, roadmap-track,
 * case-study, social — each carrying its own local per-element styles on
 * top of this widget's generic base styles, styled with Elementor's native
 * builder wherever possible.
 */
class AAE_A_Timeline extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'An open vertical timeline container — four editable event items to duplicate, restyle, or delete. Pair with the ready-made editorial-rail/heritage-split/roadmap-track/case-study/social templates.';

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
		return esc_html__( 'Timeline', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'timeline', 'history', 'roadmap', 'aae' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	public function get_icon() {
		return 'eicon-time-line';
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

			// "Items": a live projection of the timeline's real
			// <e-aae-a-timeline-item> children — one repeater row each, with
			// drag-reorder, duplicate, remove and rename. Mirrors the
			// Accordion's "Items" element-control. Rendered by the React
			// component registered under 'aae-timeline-items'
			// (src/modules/atomic/element-controls).
			Section::make()
				->set_label( __( 'Items', 'animation-addons-for-elementor' ) )
				->set_id( 'items' )
				->set_items( [
					AAE_A_Timeline_Items_Control::make()
						->set_label( __( 'Items', 'animation-addons-for-elementor' ) )
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

	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'width',         Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
						->add_prop( 'max-width',     Size_Prop_Type::generate( [ 'size' => 640, 'unit' => 'px' ] ) )
						->add_prop( 'display',        String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
						->add_prop( 'margin', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 48, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => null, 'unit' => 'auto' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 48, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => null, 'unit' => 'auto' ] ),
						] ) )
						->add_prop( 'padding', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
						] ) )
				),
		];
	}

	protected function define_default_children() {
		$items = [
			[
				'date'   => '2022',
				'number' => '01',
				'title'  => __( 'Getting Started', 'animation-addons-for-elementor' ),
				'desc'   => __( 'Describe the first milestone of your timeline here.', 'animation-addons-for-elementor' ),
			],
			[
				'date'   => '2023',
				'number' => '02',
				'title'  => __( 'Building Momentum', 'animation-addons-for-elementor' ),
				'desc'   => __( 'Describe the second milestone of your timeline here.', 'animation-addons-for-elementor' ),
			],
			[
				'date'   => '2024',
				'number' => '03',
				'title'  => __( 'Major Expansion', 'animation-addons-for-elementor' ),
				'desc'   => __( 'Describe the third milestone of your timeline here.', 'animation-addons-for-elementor' ),
			],
			[
				'date'   => '2025',
				'number' => '04',
				'title'  => __( 'Where We Are Now', 'animation-addons-for-elementor' ),
				'desc'   => __( 'Describe the fourth milestone of your timeline here.', 'animation-addons-for-elementor' ),
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