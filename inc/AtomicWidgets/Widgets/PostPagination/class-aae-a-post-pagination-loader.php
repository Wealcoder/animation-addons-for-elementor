<?php
/**
 * AAE Post Pagination Loader — the infinite-scroll loading indicator.
 *
 * A real, customizable atomic element (unlike the old JS-only spinner div
 * post-pagination.js used to build with document.createElement) seeded as a
 * default child of the root so a user can restyle it (size, border, colors,
 * or even drop in their own e-svg/e-image) from the Style/Content tabs like
 * any other element.
 *
 * Hidden on the frontend by default, visible in the editor for styling (see
 * post-pagination.scss's .aae-a-post-pagination-loader rule);
 * post-pagination.js clones this exact node and toggles the clone's
 * .aae-pp-loader-active class on while an infinite-scroll fetch is in
 * flight, then discards the clone. The template itself is never mutated, so
 * it's reusable for every subsequent fetch.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\PostPagination;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Post_Pagination_Loader extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-post-pagination-loader';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-post-pagination-loader';
	}

	public function get_title() {
		return esc_html__( 'Infinite Scroll Loader', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-loading';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		// Optional — a user can drop their own icon/GIF in place of the
		// default spinning ring (see post-pagination.scss's ::after ring,
		// which overlays regardless of whether a child is present).
		return [ 'e-svg', 'e-image' ];
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
		return [];
	}

	protected function define_base_styles(): array {
		// Size/shape/background/position here (all static, per-instance-
		// editable via the Style tab) — only the actual spinning ring stays
		// as a ::after pseudo-element in post-pagination.scss, since
		// Style_Variant has no way to express pseudo-elements or the
		// @keyframes it animates with. Decoupled on purpose besides: a user
		// picking a custom border/background here never has to fight the
		// spin animation's own border-top-color for cascade priority.
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'position', String_Prop_Type::generate( 'relative' ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 32, 'unit' => 'px' ] ) )
					->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 32, 'unit' => 'px' ] ) )
					->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ) )
					->add_prop( 'margin', Dimensions_Prop_Type::generate( [
						'block-start'  => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
						'inline-end'   => Size_Prop_Type::generate( [ 'size' => null, 'unit' => 'auto' ] ),
						'block-end'    => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
						'inline-start' => Size_Prop_Type::generate( [ 'size' => null, 'unit' => 'auto' ] ),
					] ) )
					->add_prop( 'background-color', Color_Prop_Type::generate( 'transparent' ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-pagination-loader' => __DIR__ . '/aae-a-post-pagination-loader.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-post-pagination-css' ];
	}

	protected function build_template_context(): array {
		return $this->build_base_template_context();
	}
}
