<?php
/**
 * AAE Loop Numbers — atomic pagination number list (Pro replica).
 *
 * A structural atomic container that renders the numbered page links
 * (`1 2 3 … N`, smart-truncated) for the Loop Grid. Fully editable/styleable
 * like any atomic element; the individual number anchors are generated at render
 * from the grid's Render_Context (current page + total pages).
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Numbers extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		// NOT a drop container: the number links are generated at render, so the
		// editor must not show an empty "+" drop-zone. It stays a styleable leaf.
	}

	public static function get_type() {
		return 'e-aae-a-loop-numbers';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-numbers';
	}

	public function get_title() {
		return esc_html__( 'Page Numbers', 'animation-addons-for-elementor' );
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

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'gap', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-numbers' => __DIR__ . '/aae-a-loop-numbers.html.twig',
		];
	}

	/**
	 * Expose the current page + total pages + smart-truncated page list to twig.
	 */
	protected function build_template_context(): array {
		$ctx     = Render_Context::get( AAE_A_Loop_Grid::class );
		$current = isset( $ctx['paged'] ) ? (int) $ctx['paged'] : 1;
		$total   = isset( $ctx['max_num_pages'] ) ? (int) $ctx['max_num_pages'] : 1;

		// In the editor there's no real query — show a representative set so the
		// user can style it.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$current = 1;
			$total   = 5;
		}

		return array_merge( $this->build_base_template_context(), [
			'current_page' => $current,
			'total_pages'  => $total,
			'page_items'   => self::smart_pages( $current, $total ),
		] );
	}

	/**
	 * Smart-truncated page list: 1 … c-1 c c+1 … N.
	 * Returns an array of ints, or the string '...' for a gap.
	 */
	public static function smart_pages( int $current, int $total ): array {
		if ( $total <= 7 ) {
			return range( 1, max( 1, $total ) );
		}

		$pages   = [];
		$pages[] = 1;

		$start = max( 2, $current - 1 );
		$end   = min( $total - 1, $current + 1 );

		if ( $start > 2 ) {
			$pages[] = '...';
		}
		for ( $i = $start; $i <= $end; $i++ ) {
			$pages[] = $i;
		}
		if ( $end < $total - 1 ) {
			$pages[] = '...';
		}

		$pages[] = $total;

		return $pages;
	}
}
