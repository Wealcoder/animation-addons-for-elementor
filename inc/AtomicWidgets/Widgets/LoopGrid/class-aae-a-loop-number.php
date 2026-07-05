<?php
/**
 * AAE Loop Number — one styleable atomic page-number slot.
 *
 * Seven of these slots are seeded inside the Page Numbers container. The
 * frontend runtime updates their page, label and selected state without
 * replacing the atomic elements in the DOM.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Number extends Atomic_Element_Base {
	use Has_Element_Template;

	public static function get_type() {
		return 'e-aae-a-loop-number';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-number';
	}

	public function get_title() {
		return esc_html__( 'Page Number', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		return [];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'slot'       => Number_Prop_Type::make()
				->default( 1 )
				->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	protected function define_atomic_style_states(): array {
		return [ Style_States::get_class_states_map()['selected'] ];
	}

	protected function define_base_styles(): array {
		$normal = [
			'display'         => String_Prop_Type::generate( 'inline-flex' ),
			'align-items'     => String_Prop_Type::generate( 'center' ),
			'justify-content' => String_Prop_Type::generate( 'center' ),
			'min-width'       => Size_Prop_Type::generate( [ 'size' => 36, 'unit' => 'px' ] ),
			'height'          => Size_Prop_Type::generate( [ 'size' => 36, 'unit' => 'px' ] ),
			'padding'         => Dimensions_Prop_Type::generate( [
				'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'inline-start' => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
				'inline-end'   => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
			] ),
			'border-width'   => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ),
			'border-style'   => String_Prop_Type::generate( 'solid' ),
			'border-color'   => Color_Prop_Type::generate( '#d5d8dc' ),
			'border-radius'  => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
			'color'          => Color_Prop_Type::generate( '#1a1a1a' ),
			'text-decoration' => String_Prop_Type::generate( 'none' ),
			'cursor'          => String_Prop_Type::generate( 'pointer' ),
		];

		$selected = [
			'background'       => Background_Prop_Type::generate( [
				'color' => Color_Prop_Type::generate( '#515962' ),
			] ),
			'border-color'     => Color_Prop_Type::generate( '#515962' ),
			'color'            => Color_Prop_Type::generate( '#ffffff' ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $normal ) )
				->add_variant( Style_Variant::make()->set_state( Style_States::SELECTED )->add_props( $selected ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-number' => __DIR__ . '/aae-a-loop-number.html.twig',
		];
	}

	protected function build_template_context(): array {
		$context = Render_Context::get( AAE_A_Loop_Grid::class );
		$settings = $this->get_atomic_settings();
		$slot = max( 1, min( 7, (int) ( $settings['slot'] ?? 1 ) ) );
		$current = isset( $context['paged'] ) ? (int) $context['paged'] : 1;
		$total = isset( $context['max_num_pages'] ) ? (int) $context['max_num_pages'] : 1;

		// Keep every slot visible in the editor so each real atomic child can be
		// selected and styled, including the two gap slots.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$current = 5;
			$total = 10;
		}

		$items = AAE_A_Loop_Numbers::smart_pages( $current, $total );
		$item = $items[ $slot - 1 ] ?? null;
		$is_gap = '...' === $item;
		$page = is_int( $item ) ? $item : null;
		$url = $page ? AAE_A_Loop_Numbers::page_url( $page ) : '';

		return array_merge( $this->build_base_template_context(), [
			'slot'       => $slot,
			'page_item'  => $item,
			'page_number' => $page,
			'page_url'   => $url,
			'is_gap'     => $is_gap,
			'is_current' => $page === $current,
			'is_hidden'  => null === $item,
		] );
	}

}
