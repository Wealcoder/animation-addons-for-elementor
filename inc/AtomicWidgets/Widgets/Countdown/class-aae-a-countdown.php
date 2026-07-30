<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Countdown;

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
use Elementor\Modules\AtomicWidgets\Controls\Types\Textarea_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Divider\Atomic_Divider;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

// Sub-element file — loaded eagerly so define_default_children() can call ::generate().
require_once __DIR__ . '/class-aae-a-countdown-unit.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Countdown\AAE_A_Countdown_Unit;

/**
 * AAE Countdown — composite atomic widget.
 *
 * Structure:
 *   AAE_A_Countdown (this class — the parent users drop in)
 *     ├─ AAE_A_Countdown_Unit  (locked — unit_type=days)
 *     ├─ AAE_A_Countdown_Unit  (locked — unit_type=hours)
 *     ├─ AAE_A_Countdown_Unit  (locked — unit_type=minutes)
 *     └─ AAE_A_Countdown_Unit  (locked — unit_type=seconds)
 *
 * Each unit internally hosts an Atomic_Heading (digit) + Atomic_Paragraph
 * (label) — see class-aae-a-countdown-unit.php. The JS handler updates
 * each unit's `.aae-a-countdown-unit-count` text every second based on
 * the `due_date` set here.
 */
class AAE_A_Countdown extends Atomic_Element_Base {

	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public static $widget_description = 'A composite countdown timer with four locked time units (days, hours, minutes, seconds). Each unit, its digit, and its label are independent atomic children — style each from its own Style panel.';

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-countdown';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-countdown';
	}

	public function get_title() {
		return esc_html__( 'AAE Countdown', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'countdown', 'timer', 'composite' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	public function get_icon() {
		return 'eicon-countdown';
	}

	protected static function define_props_schema(): array {
		return [
			'classes'        => Classes_Prop_Type::make()->default( [] ),
			'attributes'     => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Due-date string — passed verbatim into `new Date(...)` on the
			// client. Default = 1 day from now so the editor shows a live
			// counter on first drop without the user having to set it.
			'due_date'       => String_Prop_Type::make()->default( gmdate( 'Y-m-d H:i:s', strtotime( '+1 day' ) ) ),

			// Expire message — surfaced via data-attributes so the JS handler
			// can swap the markup when the timer hits zero. Kept as widget-
			// level text props for v1; can be promoted to atomic children
			// later if users need independent styling of the expire block.
			'expire_title'   => String_Prop_Type::make()->default( esc_html__( 'Countdown is finished!', 'animation-addons-for-elementor' ) ),
			'expire_desc'    => String_Prop_Type::make()->default( esc_html__( 'Default description', 'animation-addons-for-elementor' ) ),

			// Show / hide colon separator between units (visual only).
			'show_separator' => Boolean_Prop_Type::make()->default( true ),

			// Flex direction of the units row.
			'layout'         => String_Prop_Type::make()->enum( [ 'horizontal', 'vertical' ] )->default( 'horizontal' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Timer', 'animation-addons-for-elementor' ) )
				->set_id( 'timer' )
				->set_items( [
					Text_Control::bind_to( 'due_date' )
						->set_label( __( 'Due Date', 'animation-addons-for-elementor' ) )
						->set_placeholder( 'YYYY-MM-DD HH:MM:SS' ),
					Select_Control::bind_to( 'layout' )
						->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'horizontal', 'label' => __( 'Horizontal', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'vertical',   'label' => __( 'Vertical',   'animation-addons-for-elementor' ) ],
						] ),
					Switch_Control::bind_to( 'show_separator' )
						->set_label( __( 'Show Separator', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Expire Message', 'animation-addons-for-elementor' ) )
				->set_id( 'expire' )
				->set_items( [
					Text_Control::bind_to( 'expire_title' )
						->set_label( __( 'Title', 'animation-addons-for-elementor' ) ),
					Textarea_Control::bind_to( 'expire_desc' )
						->set_label( __( 'Description', 'animation-addons-for-elementor' ) ),
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
						->add_prop( 'display',         String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction',  String_Prop_Type::generate( 'row' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'align-items',     String_Prop_Type::generate( 'center' ) )
						->add_prop( 'gap',             Size_Prop_Type::generate( [ 'size' => 40, 'unit' => 'px' ] ) )
				),
		];
	}

	/**
	 * Locked composition. Four units, one per time fragment. `is_locked(true)`
	 * so users can't reorder / delete them — the JS expects all four to
	 * exist to keep counting. Users can still delete a unit (Elementor
	 * allows deletion of locked children via the structure panel); the JS
	 * gracefully skips missing units.
	 *
	 * Each unit's grandchild tree (digit Atomic_Heading + label
	 * Atomic_Paragraph) is composed HERE via `->children([...])` so the
	 * correct localized label lands on the first render. The unit's own
	 * `define_default_children()` only kicks in if a unit is spawned
	 * without a pre-supplied children tree (e.g. user hand-adds one),
	 * because reading `$this->get_settings()` inside that method runs
	 * during construction when settings is still null → fatal.
	 */
	protected function define_default_children() {
		$units = [
			'days'    => __( 'Days',    'animation-addons-for-elementor' ),
			'hours'   => __( 'Hours',   'animation-addons-for-elementor' ),
			'minutes' => __( 'Minutes', 'animation-addons-for-elementor' ),
			'seconds' => __( 'Seconds', 'animation-addons-for-elementor' ),
		];

		$children   = [];
		$unit_keys  = array_keys( $units );
		$last_unit  = end( $unit_keys );

		foreach ( $units as $unit_type => $label ) {
			$children[] = AAE_A_Countdown_Unit::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => ucfirst( $unit_type ) ] )
				->settings( [
					'unit_type' => String_Prop_Type::generate( $unit_type ),
				] )
				->children( AAE_A_Countdown_Unit::build_default_inner_children( $label ) )
				->build();

			if ( $unit_type !== $last_unit ) {
				$children[] = Atomic_Divider::generate()
					->is_locked( true )
					->editor_settings( [ 'title' => 'Separator' ] )
					->settings( [
						'classes' => Classes_Prop_Type::generate( [ 'aae-a-countdown-separator' ] ),
					] )
					->build();
			}
		}

		return $children;
	}

	/**
	 * Restrict drag-drop so users can't drop arbitrary widgets inside the
	 * Countdown — only our own unit type is allowed. (Atomic_Heading /
	 * Atomic_Paragraph etc. still nest inside the unit itself.)
	 */
	protected function define_allowed_child_types() {
		return [ 'e-aae-a-countdown-unit', 'e-divider' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-countdown' => __DIR__ . '/aae-a-countdown.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-countdown-js' ];
	}
}
