<?php
/**
 * AAE Loop Layout — the CSS-grid wrapper inside the Loop Grid.
 *
 * Structural container (Pro replica). Holds exactly one Loop Item, which repeats
 * per post at render. This element renders the `.aae-a-loop-grid` grid using
 * real CSS grid (display: grid; grid-template-columns), editable from the
 * Style panel.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Layout extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-loop-layout';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-layout';
	}

	public function get_title() {
		return esc_html__( 'Loop Layout', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-loop-builder';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-loop-item' ];
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

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'grid' ) )
						->add_prop( 'grid-template-columns', String_Prop_Type::generate( 'repeat(3, 1fr)' ) )
						->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ) )
						 ->add_prop( 'padding', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-layout' => __DIR__ . '/aae-a-loop-layout.html.twig',
		];
	}

	/**
	 * The grid's identity, on the element whose contents actually get replaced.
	 *
	 * The Pagination child carries the same block, but a grid does not have to
	 * have one — a six-item portfolio with a category filter and no second page
	 * is an ordinary shape, and until this existed nothing in the DOM said which
	 * grid that was, so a filter had no way to ask for it. Built by
	 * `AAE_A_Loop_Grid::endpoint_config()`, the one builder both use.
	 *
	 * Editor canvas only ever gets an empty attribute: there is no render
	 * context there, so `grid` would be '' and a runtime keying on it would bind
	 * to a grid that does not exist.
	 */
	protected function build_template_context(): array {
		$ctx = Render_Context::get( AAE_A_Loop_Grid::class );

		$config = '';
		if ( is_array( $ctx ) && ! empty( $ctx['grid_id'] ) && class_exists( AAE_A_Loop_Grid::class ) ) {
			$config = (string) wp_json_encode( AAE_A_Loop_Grid::endpoint_config( $ctx ) );
		}

		return array_merge( $this->build_base_template_context(), [
			'grid_config' => $config,
		] );
	}

	// The column layout itself is flexbox-driven (the Loop Item's base style
	// `flex: 1 1 32%`), so there is no columns CSS var to pass.
}
