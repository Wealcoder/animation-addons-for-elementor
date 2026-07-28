<?php
/**
 * AAE Post Pagination Preview — Excerpt. See class-aae-a-post-pagination-
 * preview-image.php's docblock for the shared `role`/Render_Context pattern.
 *
 * Owns its OWN `excerpt_length` control (mirrors AAE_A_Post_Title's own
 * `title_limit`) — AAE_A_Post_Pagination::post_summary() resolves a
 * generously-capped excerpt (55 words, WP's own core default) once per
 * adjacent post; this widget re-trims THAT string down to its own setting
 * at render time, so the length is a per-instance Content-tab control on
 * the piece that actually displays it, not a root-level setting reaching
 * into a child it can't see.
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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Post_Pagination_Preview_Excerpt extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-post-pagination-preview-excerpt';
	}

	public function get_title() {
		return esc_html__( 'Post Pagination Preview Excerpt', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-text-align-left';
	}

	/** See the identical note in class-aae-a-post-pagination-preview-image.php. */
	public function should_show_in_panel() {
		return true;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'        => Classes_Prop_Type::make()->default( [] ),
			'attributes'     => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'role'           => String_Prop_Type::make()->default( 'next' ),
			'excerpt_length' => Number_Prop_Type::make()->default( 20 ),
			'text'           => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Excerpt Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Number_Control::bind_to( 'excerpt_length' )
						->set_label( __( 'Length (words)', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 100 ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
					->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 13, 'unit' => 'px' ] ) )
					->add_prop( 'line-height', Size_Prop_Type::generate( [ 'size' => 1.45, 'unit' => 'em' ] ) )
					->add_prop( 'color', Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.85)' ) )
					->add_prop( 'margin', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-pagination-preview-excerpt' => __DIR__ . '/aae-a-post-pagination-preview-excerpt.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-post-pagination-css' ];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();
		$role     = ! empty( $settings['role'] ) ? $settings['role'] : 'next';
		$length   = isset( $settings['excerpt_length'] ) ? max( 1, (int) $settings['excerpt_length'] ) : 20;

		$ctx  = Render_Context::get( AAE_A_Post_Pagination::class );
		$post = isset( $ctx[ $role ] ) ? $ctx[ $role ] : null;

		if ( $post && ! empty( $post['excerpt'] ) ) {
			$settings['text'] = wp_trim_words( $post['excerpt'], $length );
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['text'] = __( 'Post excerpt goes here…', 'animation-addons-for-elementor' );
		} else {
			$settings['text'] = '';
		}

		return $settings;
	}
}
