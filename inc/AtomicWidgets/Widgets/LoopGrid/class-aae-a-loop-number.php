<?php
/**
 * AAE Loop Number — the styleable page-number TEMPLATE.
 *
 * One authored atomic element (like the Loop Item): the user styles it once —
 * Normal / Hover / Current-page (Selected) states via the native Style panel —
 * and it REPEATS at render, once per page link: 1 2 3 … N (smart-truncated).
 *
 * Render strategy mirrors AAE_A_Loop_Item: print_content() reads the grid's
 * render context (current page + total pages) off the Render_Context stack,
 * builds the smart-truncated page list, and renders this element's own Twig
 * once per item — setting a per-iteration payload the template reads. Each
 * render emits the same authored atomic element (same base-style class, same
 * local styles), so styling the one template themes every number.
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

	/**
	 * Per-iteration payload set by print_content() right before each render(), so
	 * build_template_context() knows which page link it is currently emitting.
	 * Shape: [ 'item' => int|'...'|null, 'current' => int ].
	 *
	 * @var array|null
	 */
	private $render_item = null;

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
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	/**
	 * Expose the "selected" class-state so the Style panel offers a Current-page
	 * state (the runtime adds `e--selected` to whichever number is the current
	 * page). Hover is a pseudo-state and is always available.
	 */
	protected function define_atomic_style_states(): array {
		return [ Style_States::get_class_states_map()['selected'] ];
	}

	/**
	 * The number look. One authored element -> one base-style class -> every
	 * repeated number inherits it, and the user's own Style-panel edits on this
	 * template apply to all of them. Normal / Hover / Selected (current page).
	 *
	 * Props are strictly typed (the base-style renderer resolves each key against
	 * Elementor's Style_Schema and silently drops a mistyped value), so keep
	 * min-width/height/border as Size, color as Color, background as Background.
	 */
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
			'border-width'    => Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ),
			'border-style'    => String_Prop_Type::generate( 'solid' ),
			'border-color'    => Color_Prop_Type::generate( '#d5d8dc' ),
			'border-radius'   => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
			'color'           => Color_Prop_Type::generate( '#1a1a1a' ),
			'text-decoration' => String_Prop_Type::generate( 'none' ),
			'cursor'          => String_Prop_Type::generate( 'pointer' ),
		];		

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $normal ) )							
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-number' => __DIR__ . '/aae-a-loop-number.html.twig',
		];
	}

	/**
	 * Per-render template payload. Reads the current iteration set by
	 * print_content(); falls back to a single "page 1 of 1" (or a sample in the
	 * editor) when rendered in isolation.
	 */
	protected function build_template_context(): array {
		$context = Render_Context::get( AAE_A_Loop_Grid::class );
		$current = isset( $context['paged'] ) ? (int) $context['paged'] : 1;

		// Which page link this iteration is.
		if ( is_array( $this->render_item ) ) {
			$item    = $this->render_item['item'];
			$current = (int) $this->render_item['current'];
		} else {
			// Rendered in isolation (editor authoring / no repeat context): show a
			// representative "current" number so the template is visible + styleable.
			$item = $current;
		}

		$is_gap = '...' === $item;
		$page   = is_int( $item ) ? $item : null;
		$url    = $page ? AAE_A_Loop_Numbers::page_url( $page ) : '';

		return array_merge( $this->build_base_template_context(), [
			'page_number' => $page,
			'page_url'    => $url,
			'is_gap'      => $is_gap,
			'is_current'  => null !== $page && $page === $current,
		] );
	}

	/**
	 * Repeat this template once per page link.
	 *
	 * Reads the grid's render context (current + total pages) off the
	 * Render_Context stack, builds the smart-truncated page list
	 * (1 … c-1 c c+1 … N), and renders this element's Twig once per item — setting
	 * $this->render_item before each render() so build_template_context() knows
	 * the number/href/selected/gap for that iteration.
	 *
	 * In the editor / with no context, render once so the template is authorable.
	 */
	public function print_content() {
		$context = Render_Context::get( AAE_A_Loop_Grid::class );

		// No context (edited in isolation) — single render via the Twig pipeline.
		if ( empty( $context ) || ! isset( $context['max_num_pages'] ) ) {
			$this->render();
			return;
		}

		$current = isset( $context['paged'] ) ? (int) $context['paged'] : 1;
		$total   = (int) $context['max_num_pages'];
		$items   = AAE_A_Loop_Numbers::smart_pages( $current, $total );

		foreach ( $items as $item ) {
			$this->render_item = [
				'item'    => $item,
				'current' => $current,
			];
			$this->render();
		}
		$this->render_item = null;
	}
}
