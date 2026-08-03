<?php
/**
 * AAE Post Pagination Preview — Thumbnail.
 *
 * Dynamic content leaf, same mechanism as AAE_A_Post_Title/AAE_A_Post_Image:
 * `get_atomic_settings()` overrides its own `image_url` prop at render time
 * by reading the resolved adjacent-post data off
 * Render_Context::get(AAE_A_Post_Pagination::class) — keyed by `role`
 * ('prev'/'next', baked in by AAE_A_Post_Pagination_Preview::build_default_inner_children()) —
 * rather than the visitor typing/uploading an image, since this always has
 * to be THAT post's own featured image.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\PostPagination;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Border_Radius_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Post_Pagination_Preview_Image extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-post-pagination-preview-image';
	}

	public function get_title() {
		return esc_html__( 'Thumbnail', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-image';
	}

	public function show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'role'       => String_Prop_Type::make()->default( 'next' ),
			'image_url'  => String_Prop_Type::make()->default( '' ),
			'image_alt'  => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
					->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 130, 'unit' => 'px' ] ) )
					->add_prop( 'object-fit', String_Prop_Type::generate( 'cover' ) )
					// Bleeds to the card's own edges (card padding is 12px —
					// see AAE_A_Post_Pagination_Preview::define_base_styles())
					// with only the top corners rounded to match the card.
					->add_prop( 'width', String_Prop_Type::generate( 'calc(100% + 24px)' ) )
					->add_prop( 'margin', Dimensions_Prop_Type::generate( [
						'block-start'  => Size_Prop_Type::generate( [ 'size' => -12, 'unit' => 'px' ] ),
						'inline-end'   => Size_Prop_Type::generate( [ 'size' => -12, 'unit' => 'px' ] ),
						'block-end'    => Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
						'inline-start' => Size_Prop_Type::generate( [ 'size' => -12, 'unit' => 'px' ] ),
					] ) )
					->add_prop( 'border-radius', Border_Radius_Prop_Type::generate( [
						'start-start' => Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
						'start-end'   => Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
						'end-start'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'end-end'     => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
					] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-pagination-preview-image' => __DIR__ . '/aae-a-post-pagination-preview-image.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-post-pagination-css' ];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();
		$role     = ! empty( $settings['role'] ) ? $settings['role'] : 'next';

		$ctx  = Render_Context::get( AAE_A_Post_Pagination::class );
		$post = isset( $ctx[ $role ] ) ? $ctx[ $role ] : null;

		if ( $post && ! empty( $post['thumbnail'] ) ) {
			$settings['image_url'] = $post['thumbnail'];
			$settings['image_alt'] = isset( $post['title'] ) ? $post['title'] : '';
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			// Editor fallback so the piece always shows something to style,
			// same reasoning as AAE_A_Post_Image's own sample-post fallback.
			$sample = class_exists( '\WCF_ADDONS\AtomicWidgets\Atomic' ) ? \WCF_ADDONS\AtomicWidgets\Atomic::get_sample_post() : null;
			$settings['image_url'] = $sample ? get_the_post_thumbnail_url( $sample, 'medium' ) : '';
			if ( empty( $settings['image_url'] ) ) {
				$settings['image_url'] = \Elementor\Utils::get_placeholder_image_src();
			}
			$settings['image_alt'] = 'Placeholder Image';
		} else {
			$settings['image_url'] = '';
		}

		return $settings;
	}
}
