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
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/Parts/class-aae-a-timeline-number.php';
require_once __DIR__ . '/Parts/class-aae-a-timeline-year.php';
require_once __DIR__ . '/Parts/class-aae-a-timeline-title.php';
require_once __DIR__ . '/Parts/class-aae-a-timeline-desc.php';

/**
 * AAE Timeline — Item (open template wrapper).
 *
 * Structural base styles only (position/flex/gap + spacing between items).
 * The marker/year/title/desc children are each a dedicated small widget type
 * (AAE_A_Timeline_Number/_Year/_Title/_Desc) carrying its own fixed
 * typography via its own define_base_styles() — see
 * class-aae-a-timeline-number.php for why plain e-paragraph/e-heading reuse
 * can't express that (base styles are owned by the widget TYPE, not a
 * per-instance override).
 */
class AAE_A_Timeline_Item extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A single event row inside an AAE Timeline — contains marker, date, title, and description as atomic children.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-timeline-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-timeline-item';
	}

	public function get_title() {
		return esc_html__( 'Timeline Item', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'timeline', 'item', 'event' ];
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	public function should_show_in_panel() {
		// Internal sub-element — never draggable from the widget panel.
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
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
						->add_prop( 'position',       String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'display',        String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
						->add_prop( 'gap',            Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ) )
						->add_prop( 'margin', Dimensions_Prop_Type::generate( [
							'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'    => Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ),
							'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ) )
				),
		];
	}

	public static function build_default_inner_children(
		string $date = '2024',
		string $number = '01',
		string $title = 'Event Title',
		string $desc = 'Describe what happened during this milestone.'
	): array {
		return [
			AAE_A_Timeline_Number::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Number' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-timeline-item-marker' ] ),
					'text'    => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $number ),
						'children' => [],
					] ),
					'tag'     => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			AAE_A_Timeline_Year::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Year' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-timeline-item-date' ] ),
					'text'    => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $date ),
						'children' => [],
					] ),
					'tag'     => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			AAE_A_Timeline_Title::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Title' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-timeline-item-title' ] ),
					'text'    => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $title ),
						'children' => [],
					] ),
					'tag'     => String_Prop_Type::generate( 'h3' ),
				] )
				->build(),

			AAE_A_Timeline_Desc::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Description' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-timeline-item-desc' ] ),
					'text'    => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $desc ),
						'children' => [],
					] ),
					'tag'     => String_Prop_Type::generate( 'p' ),
				] )
				->build(),
		];
	}

	protected function define_default_children() {
		return self::build_default_inner_children();
	}

	protected function define_allowed_child_types() {
		return [
			'widget',
			'e-heading',
			'e-paragraph',
			'e-svg',
			'e-button',
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
			'elementor/elements/aae-a-timeline-item' => __DIR__ . '/aae-a-timeline-item.html.twig',
		];
	}
}
