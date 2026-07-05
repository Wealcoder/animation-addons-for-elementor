<?php
/**
 * AAE Loop Layout — the CSS-grid wrapper inside the Loop Grid.
 *
 * Structural container (Pro replica). Holds exactly one Loop Item, which repeats
 * per post at render. This element renders the `.aae-a-loop-grid` grid; the
 * column layout is flexbox-driven (each Loop Item's base style: flex 1 1 32%).
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
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'row' ) )
						->add_prop( 'flex-wrap', String_Prop_Type::generate( 'wrap' ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-layout' => __DIR__ . '/aae-a-loop-layout.html.twig',
		];
	}

	// No custom template context: the column layout is flexbox-driven (the Loop
	// Item's base style `flex: 1 1 32%`), so there is no columns CSS var to pass.
}
