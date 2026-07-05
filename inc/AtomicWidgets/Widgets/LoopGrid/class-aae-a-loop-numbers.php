<?php
/**
 * AAE Loop Numbers — atomic pagination number list (Pro replica).
 *
 * A structural atomic container holding seven persistent, styleable Atomic
 * anchor slots. Runtime query state updates each slot without replacing it.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-loop-number.php';

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
		$this->meta( 'is_container', true );
		// Only the seven internal number slots are valid children.
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

	protected function define_default_children() {
		return self::build_number_slots();
	}

	/**
	 * Smart pagination never renders more than seven items, so seven persistent
	 * atomic slots cover every runtime state without rebuilding their DOM.
	 */
	public static function build_number_slots(): array {
		$children = [];

		for ( $slot = 1; $slot <= 7; $slot++ ) {
			$children[] = AAE_A_Loop_Number::generate()
				->settings( [ 'slot' => Number_Prop_Type::generate( $slot ) ] )
				->editor_settings( [
					'title' => sprintf(
						/* translators: %d is the pagination slot number. */
						__( 'Page Number %d', 'animation-addons-for-elementor' ),
						$slot
					),
				] )
				->build();
		}

		return $children;
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

	public static function page_url( int $page ): string {
		if ( 1 === $page ) {
			return remove_query_arg( 'aae_page' );
		}

		return add_query_arg( 'aae_page', $page );
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
