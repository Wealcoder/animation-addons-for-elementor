<?php
/**
 * AAE Post Pagination Preview — Title. See class-aae-a-post-pagination-
 * preview-image.php's docblock for the shared `role`/Render_Context pattern.
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
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Post_Pagination_Preview_Title extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-post-pagination-preview-title';
	}

	public function get_title() {
		return esc_html__( 'Post Pagination Preview Title', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-t-letter';
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
			'tag'        => String_Prop_Type::make()->default( 'div' ),
			'text'       => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Title Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'div', 'label' => 'div' ],
							[ 'value' => 'h3', 'label' => 'H3' ],
							[ 'value' => 'h4', 'label' => 'H4' ],
							[ 'value' => 'p', 'label' => 'p' ],
							[ 'value' => 'span', 'label' => 'span' ],
						] ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
					->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ) )
					->add_prop( 'font-weight', String_Prop_Type::generate( '600' ) )
					->add_prop( 'line-height', Size_Prop_Type::generate( [ 'size' => 1.35, 'unit' => 'em' ] ) )
					->add_prop( 'color', Color_Prop_Type::generate( '#1a1a1a' ) )
					->add_prop( 'margin', Dimensions_Prop_Type::generate( [
						'block-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'block-end'   => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
					] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-pagination-preview-title' => __DIR__ . '/aae-a-post-pagination-preview-title.html.twig',
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

		if ( $post && ! empty( $post['title'] ) ) {
			$settings['text'] = $post['title'];
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['text'] = __( 'Post Title', 'animation-addons-for-elementor' );
		} else {
			$settings['text'] = '';
		}

		return $settings;
	}
}
