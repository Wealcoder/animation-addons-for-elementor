<?php
/**
 * AAE Loop Slide Pagination — the Loop Grid Slider's post-paging bar.
 *
 * Subclass of the Loop Grid's AAE_A_Loop_Pagination. It reuses the entire paging
 * engine (load method, AJAX config, edge-state stamping) but drops the Nav Wrap
 * (Prev / Next) from the seeded tree: the slider already has its OWN Prev / Next
 * ARROWS for navigating slides, so the pagination bar's own Prev/Next would be a
 * confusing second, duplicate pair. Here the bar seeds only Page Numbers + Load
 * More — both of which advance the QUERY (fetch more posts), which the arrows do
 * not. Users who want the paging prev/next back can still add a Nav element.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider;

use WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Pagination;
use WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_LoadMore;

require_once __DIR__ . '/../LoopGrid/class-aae-a-loop-pagination.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Slide_Pagination extends AAE_A_Loop_Pagination {

	public static function get_type() {
		return 'e-aae-a-loop-slide-pagination';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-slide-pagination';
	}

	/**
	 * Seed ONLY Load More — no Nav Wrap (the slider's own arrows cover slide
	 * navigation) and no Page Numbers. In a slider, "more" means fetch the next
	 * page of posts and append them as new slides; discrete page links don't fit
	 * that model, so Load More is the single paging control. Numbers / Nav are
	 * still allowed as children if a user deliberately adds them back.
	 */
	protected function define_default_children() {
		return [
			AAE_A_Loop_LoadMore::generate()
				->editor_settings( [ 'title' => 'Load More' ] )
				->is_locked( true )
				->build(),
		];
	}

	/** Load More is the seeded control; Numbers / Nav Wrap stay available to add. */
	protected function define_allowed_child_types() {
		return [ 'e-aae-a-loop-loadmore', 'e-aae-a-loop-numbers', 'e-aae-a-loop-nav-wrap' ];
	}

	protected function get_templates(): array {
		// Reuse the parent's twig — the wrapper markup + data-attrs are identical;
		// only the seeded children differ.
		return [
			'elementor/elements/aae-a-loop-slide-pagination' => __DIR__ . '/../LoopGrid/aae-a-loop-pagination.html.twig',
		];
	}
}
