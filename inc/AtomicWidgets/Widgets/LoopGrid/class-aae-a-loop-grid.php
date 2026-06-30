<?php
/**
 * AAE Loop Grid — atomic widget.
 *
 * Queries WP posts and repeats a reusable "loop item" template (an
 * aae-loop-item document) once per post, like Elementor Pro's Loop Grid, but
 * the loop item is built from AAE atomic widgets.
 *
 * Render strategy (proven by spikes):
 *   WP_Query -> per post setup_postdata() -> loop-item document print_content()
 * Because the AAE "current post" widgets (post title/image) read the global WP
 * loop context, each rendered item automatically shows the right post — no
 * dynamic-tag system needed.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Grid extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-loop-grid';
	}

	protected function define_allowed_child_types(): array {
		return [];
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-grid';
	}

	public function get_title() {
		return esc_html__( 'AAE Loop Grid', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-loop-builder';
	}

	public function get_keywords() {
		return [ 'loop', 'grid', 'posts', 'query', 'template', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'        => Classes_Prop_Type::make()->default( [] ),
			'attributes'     => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// The loop-item template document id to repeat per post.
			// NOTE: prop name must NOT be `template_id` — Elementor Pro's loop
			// builder scans every element's `settings.template_id` and runs
			// Number() on it, which yields NaN for an atomic { $$type, value }
			// object and fires a bogus global-classes/post?post_id=NaN (HTTP 400).
			'loop_template_id' => Number_Prop_Type::make()->default( 0 ),

			// Layout + query.
			'columns'        => Number_Prop_Type::make()->default( 3 ),
			'posts_per_page' => Number_Prop_Type::make()->default( 6 ),
			'post_type'      => String_Prop_Type::make()->default( 'post' ),
			'order_by'       => String_Prop_Type::make()->default( 'date' ),
			'order'          => String_Prop_Type::make()->default( 'desc' ),

			// Internal: per-item rendered HTML, filled server-side at render time.
			'rendered_items' => Rendered_Items_Prop_Type::make()->default( [] ),

			// Internal: true only when PHP rendered (frontend / ajax). In the
			// client-side editor preview this stays the default false, so the
			// Twig template knows to emit the editor container the JS bridge
			// fills via ajax. Declared so it survives into the Twig context.
			'is_frontend'    => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-loop-template-control.php';

		return [
			Section::make()
				->set_label( __( 'Template', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_loop_template' )
				->set_items( [
					AAE_A_Loop_Template_Control::make()
						->set_label( __( 'Loop Item Template', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_label( __( 'Query', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_loop_query' )
				->set_items( [
					Select_Control::bind_to( 'post_type' )
						->set_label( __( 'Source', 'animation-addons-for-elementor' ) )
						->set_options( $this->get_post_type_options() ),

					Number_Control::bind_to( 'posts_per_page' )
						->set_label( __( 'Posts Per Page', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 50 ),

					Select_Control::bind_to( 'order_by' )
						->set_label( __( 'Order By', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'date',     'label' => __( 'Date', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'title',    'label' => __( 'Title', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'menu_order', 'label' => __( 'Menu Order', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'rand',     'label' => __( 'Random', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'order' )
						->set_label( __( 'Order', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'desc', 'label' => __( 'Descending', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'asc',  'label' => __( 'Ascending', 'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_loop_layout' )
				->set_items( [
					Number_Control::bind_to( 'columns' )
						->set_label( __( 'Columns', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 6 ),
				] ),
		];
	}

	private function get_post_type_options(): array {
		$types   = get_post_types( [ 'public' => true ], 'objects' );
		$options = [];
		foreach ( $types as $slug => $obj ) {
			if ( 'attachment' === $slug ) {
				continue;
			}
			$options[] = [ 'value' => $slug, 'label' => $obj->label ];
		}
		return $options;
	}

	protected static function define_styles(): array {
		return [
			Style_Definition::make( 'columns' )
				->add_variant(
					Style_Variant::make( 'grid-template-columns' )
						->set_selector( '& .aae-a-loop-grid' )
						->set_css_property( 'grid-template-columns' )
						->set_css_value( 'repeat({{VALUE}}, 1fr)' )
				),
		];
	}

	protected function set_initial_state(): void {
		parent::set_initial_state();
		$this->add_style_dependencies( [ $this->get_style_handle( 'columns' ) ] );
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-grid' => __DIR__ . '/aae-a-loop-grid.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-loop-grid-css' ];
	}

	/**
	 * Inject the per-item rendered HTML at render time.
	 *
	 * This only runs server-side (frontend, or the ajax render pass for the
	 * editor preview). The atomic editor preview is rendered CLIENT-side from
	 * raw saved settings and never executes this method — so `is_frontend`
	 * defaults to false there, and the editor-bridge JS fetches the real grid
	 * via the ajax endpoint and injects it (see assets/js/atomic-editor).
	 */
	public function get_atomic_settings(): array {
		$settings    = parent::get_atomic_settings();
		$template_id = isset( $settings['loop_template_id'] ) ? (int) $settings['loop_template_id'] : 0;

		$settings['loop_template_id'] = $template_id;
		// PHP only runs server-side; mark so the Twig template renders the grid.
		$settings['is_frontend']    = true;
		$settings['rendered_items'] = self::render_items( [
			'template_id'    => $template_id,
			'post_type'      => isset( $settings['post_type'] ) ? $settings['post_type'] : 'post',
			'posts_per_page' => isset( $settings['posts_per_page'] ) ? (int) $settings['posts_per_page'] : 6,
			'order_by'       => isset( $settings['order_by'] ) ? $settings['order_by'] : 'date',
			'order'          => isset( $settings['order'] ) ? $settings['order'] : 'desc',
		] );

		return $settings;
	}

	/**
	 * Render the loop-item template once per queried post.
	 *
	 * Shared by the frontend render (get_atomic_settings) and the editor
	 * preview ajax (Atomic::ajax_render_loop_grid). Each item is the loop-item
	 * document's content, printed inside the post's WP loop context so the
	 * atomic "current post" widgets resolve to the right post.
	 *
	 * @param array $args template_id, post_type, posts_per_page, order_by, order.
	 * @return string[] Per-item HTML.
	 */
	public static function render_items( array $args ): array {
		$template_id = isset( $args['template_id'] ) ? (int) $args['template_id'] : 0;
		if ( ! $template_id ) {
			return [];
		}

		$document = \Elementor\Plugin::$instance->documents->get( $template_id );
		if ( ! $document || ! method_exists( $document, 'print_content' ) ) {
			return [];
		}

		$query = new \WP_Query( [
			'post_type'           => isset( $args['post_type'] ) ? $args['post_type'] : 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 6,
			'orderby'             => isset( $args['order_by'] ) ? $args['order_by'] : 'date',
			'order'               => isset( $args['order'] ) ? strtoupper( $args['order'] ) : 'DESC',
			'ignore_sticky_posts' => true,
		] );

		$items = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				ob_start();
				$document->print_elements( $document->get_elements_data() );
				$items[] = ob_get_clean();
			}
			wp_reset_postdata();
		}

		return $items;
	}

	/**
	 * Build the full grid markup (the `.aae-a-loop-grid` div with its items).
	 *
	 * Used by the editor preview ajax so the injected HTML matches the Twig
	 * output exactly.
	 *
	 * @param array $args template_id + query + columns.
	 * @return string
	 */
	public static function render_grid_html( array $args ): string {
		$items   = self::render_items( $args );
		$columns = isset( $args['columns'] ) ? (int) $args['columns'] : 3;

		if ( empty( $items ) ) {
			return '<div class="aae-a-loop-grid-empty">' . esc_html__( 'No posts found.', 'animation-addons-for-elementor' ) . '</div>';
		}

		$html = '<div class="aae-a-loop-grid" data-columns="' . esc_attr( $columns ) . '">';
		foreach ( $items as $item ) {
			$html .= $item;
		}
		$html .= '</div>';

		return $html;
	}
}

/**
 * Internal prop type for the per-item rendered HTML array. Validation is
 * bypassed because this is server-computed data, not user input.
 */
class Rendered_Items_Prop_Type extends \Elementor\Modules\AtomicWidgets\PropTypes\Base\Array_Prop_Type {
	public static function get_key(): string {
		return 'aae_loop_rendered_items';
	}
	protected function define_item_type(): \Elementor\Modules\AtomicWidgets\PropTypes\Contracts\Prop_Type {
		return String_Prop_Type::make();
	}
	public function validate( $value ): bool {
		return true;
	}
}
