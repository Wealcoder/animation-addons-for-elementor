<?php
/**
 * AAE Loop Slide Item — one post = one slide.
 *
 * Subclass of the Loop Grid's AAE_A_Loop_Item: it inherits the entire per-post
 * repeat engine (print_content() reads the Render_Context published by the Loop
 * Grid root — keyed by AAE_A_Loop_Grid::class, which the Loop Grid Slider root
 * extends, so the same context is found — runs the WP_Query and renders this
 * element's Twig once per post).
 *
 * The only differences from the grid item:
 *   - it renders with class `aae-a-slide` (its own Twig) so the shared
 *     nested-slider runtime counts each card as a real slide;
 *   - it drops the grid `flex: 1 1 32%` base style — slide width is owned by the
 *     slider runtime (slidesPerView / peek / gap), not flex-wrap.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider;

use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Item;

require_once __DIR__ . '/../LoopGrid/class-aae-a-loop-item.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Slide_Item extends AAE_A_Loop_Item {

	public static function get_type() {
		return 'e-aae-a-loop-slide-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-slide-item';
	}

	public function get_title() {
		return esc_html__( 'Loop Slide Item', 'animation-addons-for-elementor' );
	}

	/**
	 * Column-flex card, full height, but NO `flex: 1 1 32%` — the slider runtime
	 * sizes each slide (slidesPerView / peek / gap). A flex-basis here would fight
	 * the runtime's width math.
	 */
	protected function define_base_styles(): array {
		// Zero padding by default — the slide card carries `e-con`, whose default
		// 10px padding is an unwanted inherited default here. The user can still add
		// padding from the panel to inset the card content.
		$zero = Dimensions_Prop_Type::generate( [
			'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
		] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
						->add_prop( 'padding', $zero )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-slide-item' => __DIR__ . '/aae-a-loop-slide-item.html.twig',
		];
	}

	// NOTE: the per-post repeat is inherited from AAE_A_Loop_Item::print_content()
	// (frontend WP_Query). The EDITOR shows only one authored slide because atomic
	// elements render client-side there (no server print_content, no WP_Query) — the
	// multi-up editor preview is instead synthesized in JS by the editor-bridge
	// module `slider-editor-preview.js`, which clones the single slide client-side.
	// A server-side clone here would have no effect in the editor canvas.
}
