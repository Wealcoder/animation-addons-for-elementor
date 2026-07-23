<?php
/**
 * AAE Loop Arrow — atomic leaf widget rendering a chevron arrow icon.
 *
 * Used inside the Pagination Prev/Next. Renders an inline chevron SVG (no
 * external file, no e-svg local-style seeding — which Elementor strips from
 * default children). Its size + colour live in define_base_styles() so they are
 * a reliable, editable default (base styles are NOT stripped like seeded child
 * local styles) and the user can override everything from the Style panel.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Loop_Arrow extends Atomic_Widget_Base {
	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	public static function get_element_type(): string {
		return 'e-aae-a-loop-arrow';
	}

	public function get_title() {
		return esc_html__( 'Arrow', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-chevron-right';
	}

	public function show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			// 'prev' or 'next' — selects which chevron to draw.
			'direction'  => String_Prop_Type::make()->default( 'next' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	/** Reliable default size + colour, fully overridable from the Style panel. */
	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ) )
					->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ) )
					->add_prop( 'color', Color_Prop_Type::generate( '#1a1a1a' ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-arrow' => __DIR__ . '/aae-a-loop-arrow.html.twig',
		];
	}
}
