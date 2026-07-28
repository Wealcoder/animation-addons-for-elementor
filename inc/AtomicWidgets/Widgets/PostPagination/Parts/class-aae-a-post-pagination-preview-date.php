<?php
/**
 * AAE Post Pagination Preview — Date. See class-aae-a-post-pagination-
 * preview-image.php's docblock for the shared `role`/Render_Context pattern.
 *
 * Renders `inline-block` (not `block`) so it naturally sits on the same
 * visual line as the Author piece right after it — see
 * AAE_A_Post_Pagination_Preview::build_default_inner_children() for the
 * default order (Date, then Author). The middle-dot separator between them
 * is a ::after pseudo-element in post-pagination.scss (can't be a base
 * style — no pseudo-element support there).
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
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Post_Pagination_Preview_Date extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-post-pagination-preview-date';
	}

	public function get_title() {
		return esc_html__( 'Post Pagination Preview Date', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	/** See the identical note in class-aae-a-post-pagination-preview-image.php. */
	public function should_show_in_panel() {
		return true;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'role'       => String_Prop_Type::make()->default( 'next' ),
			'text'       => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-block' ) )
					->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ) )
					->add_prop( 'color', Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.6)' ) )
					->add_prop( 'margin', Dimensions_Prop_Type::generate( [
						'block-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'block-end'   => Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ),
					] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-pagination-preview-date' => __DIR__ . '/aae-a-post-pagination-preview-date.html.twig',
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

		if ( $post && ! empty( $post['date'] ) ) {
			$settings['text'] = $post['date'];
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['text'] = date_i18n( get_option( 'date_format' ) );
		} else {
			$settings['text'] = '';
		}

		return $settings;
	}
}
