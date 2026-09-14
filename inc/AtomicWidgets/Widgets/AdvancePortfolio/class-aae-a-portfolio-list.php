<?php
/**
 * AAE Advanced Portfolio — Posts List.
 *
 * The v3 skin's `.posts-list` div: the grid the repeating item sits in. It is a
 * container of its own rather than the root's own display, for the reason the
 * Loop Grid splits Loop Layout out from Loop Grid — the root also holds the
 * section title, which must NOT be a grid cell.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancePortfolio;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Portfolio_List extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-portfolio-list';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-portfolio-list';
	}

	public function get_title() {
		return esc_html__( 'Posts List', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function show_in_panel() {
		return false;
	}

	/**
	 * The one that actually hides an ELEMENT type from the panel.
	 *
	 * show_in_panel() alone is not enough here: it is the Widget_Base hook, so
	 * it works for the leaf parts (Section Title, Date — both Atomic_Widget_Base)
	 * but is never consulted for an Atomic_Element_Base. Verified in the live
	 * editor: with only show_in_panel(), this part still arrived in
	 * elementor.widgetsCache with show_in_panel: true, while Loop Layout and
	 * Loop Item — which declare should_show_in_panel() — arrived false.
	 */
	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	/**
	 * Three across with a 30px gutter, matching the v3 Portfolio Three grid.
	 *
	 * `grid-template-columns` is a plain String in the atomic style schema (the
	 * Loop Grid's own presets set it the same way); there is no dedicated
	 * columns prop. Column count is then a Style-panel edit per breakpoint
	 * rather than a widget control.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'grid' ) )
					// Two across, not three. v3's skin-three is a two-column
					// grid (`grid-template-columns: 1fr 1fr` from 768px up in
					// advance-portfolio.css) whose even items are pushed down
					// half a row — the offset only reads as intentional in two
					// columns, and it is the skin's whole signature. Three
					// across was my own invention and it flattened the design.
					->add_prop( 'grid-template-columns', String_Prop_Type::generate( 'repeat(2, 1fr)' ) )
					->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 30, 'unit' => 'px' ] ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
			),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-portfolio-item' ];
	}

	protected function define_default_children() {
		// The root seeds the item, so this stays empty — seeding here too would
		// give a doubled item on drop.
		return [];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-portfolio-list' => __DIR__ . '/aae-a-portfolio-list.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-advance-portfolio-css' ];
	}
}
