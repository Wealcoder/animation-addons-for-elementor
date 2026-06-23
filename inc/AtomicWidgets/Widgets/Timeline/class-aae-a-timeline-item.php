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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Heading\Atomic_Heading;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

/**
 * Shared sub-element used by AAE_A_Timeline for each event row.
 *
 * Each item hosts four locked atomic children (in DOM order):
 *   1. Marker  (Atomic_Paragraph) — circular dot anchored to the spine
 *   2. Date    (Atomic_Paragraph) — small uppercase label
 *   3. Title   (Atomic_Heading)   — bold h3
 *   4. Desc    (Atomic_Paragraph) — body copy
 *
 * The spine itself is the item's own `border-inline-start`; the marker
 * is absolutely positioned to overlay the spine. Both rules live in
 * the Twig <style> block because they need values the v4 style schema
 * cannot carry cleanly (shorthand `border` declaration + negative
 * `inset-inline-start`). Everything else is in `define_base_styles()`.
 *
 * Hidden from the widget panel — only spawnable inside an AAE_A_Timeline
 * parent via `define_default_children()`.
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

	/**
	 * Per-element base styles, keyed off the child class names. Compound
	 * selectors (`base .child-class`) emit nested CSS scoped to the item's
	 * auto-generated base class — no external SCSS/CSS file required.
	 */
	protected function define_base_styles(): array {
		$px_0     = Size_Prop_Type::generate( [ 'size' => 0,   'unit' => 'px' ] );
		$px_4     = Size_Prop_Type::generate( [ 'size' => 4,   'unit' => 'px' ] );
		$px_8     = Size_Prop_Type::generate( [ 'size' => 8,   'unit' => 'px' ] );
		$px_12    = Size_Prop_Type::generate( [ 'size' => 12,  'unit' => 'px' ] );
		$px_22    = Size_Prop_Type::generate( [ 'size' => 22,  'unit' => 'px' ] );
		$px_32    = Size_Prop_Type::generate( [ 'size' => 32,  'unit' => 'px' ] );
		$pct_50   = Size_Prop_Type::generate( [ 'size' => 50,  'unit' => '%' ] );

		return [
			// Item wrapper — flex column, relative for the absolute marker,
			// padded so the spine (border-inline-start, set in Twig) clears
			// the content. border + negative-inset-positioned marker live in
			// the Twig <style>.
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'position',             String_Prop_Type::generate( 'relative' ) )
						->add_prop( 'display',              String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction',       String_Prop_Type::generate( 'column' ) )
						->add_prop( 'gap',                  $px_4 )
						->add_prop( 'padding-inline-start', $px_32 )
						->add_prop( 'padding-block-end',    $px_32 )
				),

			// Marker — circular dot. Size + appearance live here; the
			// absolute positioning (negative inset onto the spine) is in
			// the Twig <style> because Size_Prop_Type → CSS for negative
			// values is not part of the v4 style schema's safe surface.
			self::BASE_STYLE_KEY . ' .aae-a-timeline-item-marker' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'width',           $px_22 )
						->add_prop( 'height',          $px_22 )
						->add_prop( 'margin',          $px_0 )
						->add_prop( 'display',         String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'align-items',     String_Prop_Type::generate( 'center' ) )
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'background',      String_Prop_Type::generate( '#3b82f6' ) )
						->add_prop( 'color',           String_Prop_Type::generate( '#ffffff' ) )
						->add_prop( 'border-radius',   $pct_50 )
						->add_prop( 'font-size',       Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ) )
						->add_prop( 'font-weight',     Number_Prop_Type::generate( 800 ) )
						->add_prop( 'line-height',     Number_Prop_Type::generate( 1 ) )
						->add_prop( 'z-index',         Number_Prop_Type::generate( 2 ) )
				),

			// Date — small uppercase label above the title.
			self::BASE_STYLE_KEY . ' .aae-a-timeline-item-date' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'margin',         $px_0 )
						->add_prop( 'color',          String_Prop_Type::generate( '#64748b' ) )
						->add_prop( 'font-size',      Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ) )
						->add_prop( 'font-weight',    Number_Prop_Type::generate( 700 ) )
						->add_prop( 'letter-spacing', Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ) )
						->add_prop( 'text-transform', String_Prop_Type::generate( 'uppercase' ) )
						->add_prop( 'line-height',    Number_Prop_Type::generate( 1 ) )
				),

			// Title — bold heading.
			self::BASE_STYLE_KEY . ' .aae-a-timeline-item-title' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'margin',      $px_0 )
						->add_prop( 'font-size',   Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ) )
						->add_prop( 'font-weight', Number_Prop_Type::generate( 700 ) )
						->add_prop( 'color',       String_Prop_Type::generate( '#0f172a' ) )
						->add_prop( 'line-height', Number_Prop_Type::generate( 1.2 ) )
				),

			// Description — body copy.
			self::BASE_STYLE_KEY . ' .aae-a-timeline-item-desc' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'margin',      $px_0 )
						->add_prop( 'color',       String_Prop_Type::generate( '#475569' ) )
						->add_prop( 'font-size',   Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ) )
						->add_prop( 'line-height', Number_Prop_Type::generate( 1.6 ) )
				),
		];
	}

	/**
	 * Static fallback children — used only when an item is instantiated
	 * WITHOUT the parent pre-supplying a `->children([...])` tree.
	 *
	 * IMPORTANT: do NOT call `$this->get_settings()` here. This method
	 * fires while the instance is still being constructed, so settings
	 * is `null` and the chain bottoms out fatal. Mirror the IconList /
	 * Countdown pattern: emit static literal defaults only. The parent
	 * passes correctly-prefilled children via `Element_Builder::children()`
	 * at spawn time.
	 *
	 * Helper exposed publicly so the parent (AAE_A_Timeline) can call it
	 * with per-item data when composing each locked instance.
	 */
	public static function build_default_inner_children(
		string $date = '2024',
		string $number = '01',
		string $title = 'Event Title',
		string $desc = 'Describe what happened during this milestone.'
	): array {
		return [
			// 1. Marker — circular dot anchored to the spine.
			//    Atomic_Paragraph (not Heading) because the marker is a
			//    span of styled text, and Atomic_Heading's `tag` enum is
			//    restricted to h1-h6 only.
			Atomic_Paragraph::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Marker' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-timeline-item-marker' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $number ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			// 2. Date — small uppercase label above the title.
			Atomic_Paragraph::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Date' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-timeline-item-date' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $date ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),

			// 3. Title — bold heading (h3). Atomic_Heading's `title` prop is
			//    Html_V3_Prop_Type (same shape as Atomic_Paragraph's
			//    `paragraph`), NOT a plain string — wrap accordingly or the
			//    v4 settings validator throws `title: invalid_value`.
			Atomic_Heading::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Title' ] )
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-timeline-item-title' ] ),
					'title'   => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $title ),
						'children' => [],
					] ),
					'tag'     => String_Prop_Type::generate( 'h3' ),
				] )
				->build(),

			// 4. Description — body copy paragraph.
			Atomic_Paragraph::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Description' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-timeline-item-desc' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $desc ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'p' ),
				] )
				->build(),
		];
	}

	protected function define_default_children() {
		return self::build_default_inner_children();
	}

	protected function define_allowed_child_types() {
		// Users can still drop arbitrary heading / paragraph / svg widgets
		// inside an item if they want richer content alongside the locked
		// marker / date / title / description.
		return [ 'widget', 'e-heading', 'e-paragraph', 'e-svg' ];
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
