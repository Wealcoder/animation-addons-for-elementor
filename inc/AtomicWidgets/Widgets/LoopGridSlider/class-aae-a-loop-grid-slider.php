<?php
/**
 * AAE Loop Grid Slider — atomic NESTED element.
 *
 * A Loop Grid whose per-post cards are presented as SLIDES instead of a static
 * flex grid. It reuses two existing engines wholesale:
 *
 *   1. QUERY ENGINE — inherited from AAE_A_Loop_Grid (build_query_args(),
 *      compute_max_pages(), define_render_context(), and the entire Query /
 *      Query-Filters control panel). The per-post repeat lives on the child
 *      Loop Slide Item (e-aae-a-loop-slide-item), exactly like the grid's Loop
 *      Item, so every card is a real, styleable atomic subtree.
 *
 *   2. SLIDER RUNTIME — the single shared `aae-effect-nested-slider` bundle
 *      (src/modules/atomic/effects/nested-slider/index.js). That runtime binds
 *      by CSS structure — `.aae-a-slider` root -> `.aae-slider-track` -> real
 *      `.aae-a-slide` children — and reads its config from
 *      window.AAE_INTERACTIONS_NS[<id>]. So this element:
 *        - renders its wrapper with class `aae-a-slider`
 *        - contains a Slider Track (`.aae-slider-track`) whose repeating child
 *          cards carry class `aae-a-slide`
 *        - binds the SAME slider panel via the NestedSlider SLIDER_SECTION_ANCHOR
 *          prop (reusing the NS_* schema + React ResponsiveSection panel)
 *      The config is published by inc/Atomic/LoopGridSlider/Render.php via
 *      InteractionsMap::register('ns', ...) — identical namespace, identical JS.
 *
 * There is deliberately NO second slider runtime: one slider JS drives both the
 * Nested Slider and this widget.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid;
use WCF_ADDONS\AtomicWidgets\Widgets\PostImage\AAE_A_Post_Image;
use WCF_ADDONS\AtomicWidgets\Widgets\PostTitle\AAE_A_Post_Title;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Prev;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Next;
use WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Pagination;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The Loop Grid parent pulls in the query engine + the loop children it seeds.
require_once __DIR__ . '/../LoopGrid/class-aae-a-loop-grid.php';
require_once __DIR__ . '/class-aae-a-loop-slide-track.php';
require_once __DIR__ . '/class-aae-a-loop-slide-item.php';
// Reuse the Nested Slider's Prev / Next arrows + dot pagination verbatim — the
// same shared runtime that drives our slides also builds/handles these dots
// (.js-aae-dots container + .js-aae-dot template), so no extra JS is needed.
require_once __DIR__ . '/../NestedSlider/class-aae-a-slider-nav-prev.php';
require_once __DIR__ . '/../NestedSlider/class-aae-a-slider-nav-next.php';
require_once __DIR__ . '/../NestedSlider/class-aae-a-slider-dot.php';
require_once __DIR__ . '/../NestedSlider/class-aae-a-slider-pagination.php';
// Slider-specific post-paging bar (Numbers + Load More, NO duplicate Prev/Next).
require_once __DIR__ . '/class-aae-a-loop-slide-pagination.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Grid_Slider extends AAE_A_Loop_Grid {

	public static function get_type() {
		return 'e-aae-a-loop-grid-slider';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-grid-slider';
	}

	public function get_title() {
		return esc_html__( 'AAE Loop Grid Slider', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_keywords() {
		return [ 'loop', 'grid', 'slider', 'carousel', 'posts', 'query', 'atomic', 'dynamic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-post'];
	}

	/**
	 * Query / Query-Filters sections are inherited from the parent unchanged; we
	 * append the slider-settings anchor. The React ResponsiveSection replaces the
	 * anchor control (bound to NestedSlider\Schema::SLIDER_SECTION_ANCHOR) with the
	 * full slider panel — the SAME panel the Nested Slider shows — because the
	 * panel attaches by anchor key, not by widget type.
	 */
	protected function define_atomic_controls(): array {
		$controls = parent::define_atomic_controls();

		$controls[] = Section::make()
			->set_label( __( 'Slider Settings', 'animation-addons-for-elementor' ) )
			->set_id( 'aae_loop_slider_settings' )
			->set_items( [
				Text_Control::bind_to( \WCF_ADDONS\Atomic\NestedSlider\Schema::SLIDER_SECTION_ANCHOR ),
			] );

		return $controls;
	}

	/**
	 * Slider wrapper base styles. The nested-slider runtime handles the track
	 * transform / overflow; here we just make the wrapper a positioned,
	 * full-width, overflow-hidden block so absolutely-positioned nav arrows
	 * anchor to it (mirrors AAE_A_Slider::define_base_styles()).
	 */
	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display'  => String_Prop_Type::generate( 'block' ),
			'overflow' => String_Prop_Type::generate( 'hidden' ),
			'position' => String_Prop_Type::generate( 'relative' ),
			'width'    => String_Prop_Type::generate( '100%' ),
			// Default the slider wrapper to zero padding. It carries `e-con`, whose
			// default 10px padding offsets the slide-width reference box away from
			// the runtime's positioning box (see AAE_A_Loop_Slide_Track), clipping
			// the first slide. The user can still add padding deliberately from the
			// panel; this just removes the surprising inherited default.
			'padding'  => Dimensions_Prop_Type::generate( [
				'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			] ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	/**
	 * Default tree:
	 *   Slider Track (.aae-slider-track)
	 *     └─ Loop Slide Item (.aae-a-slide, repeats per post)
	 *          ├─ Post Image  (if registered)
	 *          └─ Post Title  (if registered)
	 *   Slider Prev Nav
	 *   Slider Next Nav
	 *   Pagination (self-seeds Prev / Numbers / Next / Load More)
	 *
	 * Only registered card children are seeded — an unknown child type makes the
	 * editor throw ElementTypeNotFound on drop (see parent::type_registered()).
	 */
	protected function define_default_children() {
		$card_children = [];

		if ( self::type_registered( 'e-aae-a-post-image' ) ) {
			$card_children[] = AAE_A_Post_Image::generate()
				->editor_settings( [ 'title' => 'Post Image' ] )
				->build();
		}

		if ( self::type_registered( 'e-aae-a-post-title' ) ) {
			$card_children[] = AAE_A_Post_Title::generate()
				->editor_settings( [ 'title' => 'Post Title' ] )
				->build();
		}

		return [
			AAE_A_Loop_Slide_Track::generate()
				->editor_settings( [ 'title' => 'Slider Track' ] )
				->is_locked( true )
				->children( [
					AAE_A_Loop_Slide_Item::generate()
						->editor_settings( [ 'title' => 'Loop Slide Item' ] )
						->is_locked( true )
						->children( $card_children )
						->build(),
				] )
				->build(),
			AAE_A_Slider_Nav_Prev::generate()
				->editor_settings( [ 'title' => 'Prev Nav' ] )
				->build(),
			AAE_A_Slider_Nav_Next::generate()
				->editor_settings( [ 'title' => 'Next Nav' ] )
				->build(),
			// Dot pagination (slide indicators). Reuses the Nested Slider's
			// `.js-aae-dots` container + `.js-aae-dot` template; the shared runtime
			// clones one dot per slide and wires click-to-slide + active state.
			AAE_A_Slider_Pagination::generate()
				->editor_settings( [ 'title' => 'Dot Pagination' ] )
				->build(),
			// Post PAGING — Numbers + Load More only (NO Nav Wrap Prev/Next; the
			// slider arrows above already handle navigation). Advancing here fetches
			// MORE posts into the slider. See AAE_A_Loop_Slide_Pagination.
			AAE_A_Loop_Slide_Pagination::generate()
				->editor_settings( [ 'title' => 'Pagination' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [
			'e-aae-a-loop-slide-track',
			'e-aae-a-slider-nav-prev',
			'e-aae-a-slider-nav-next',
			'e-aae-a-slider-pagination',
			'e-aae-a-loop-slide-pagination',
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-grid-slider' => __DIR__ . '/aae-a-loop-grid-slider.html.twig',
		];
	}

	/**
	 * Only our own stylesheet — NOT the Nested Slider's `aae-a-slider-css`. That
	 * handle is registered only when the Nested Slider widget is enabled; hard-
	 * depending on it would break styling if a site enables Loop Grid Slider but
	 * disables Nested Slider. The slider motion itself needs no shared CSS (the
	 * track's layout is inline + runtime-driven).
	 */
	public function get_style_depends(): array {
		return [ 'aae-a-loop-grid-slider-css' ];
	}
}
