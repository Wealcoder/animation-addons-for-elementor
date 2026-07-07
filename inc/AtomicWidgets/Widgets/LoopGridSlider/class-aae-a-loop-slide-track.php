<?php
/**
 * AAE Loop Slide Track — the `.aae-slider-track` inside the Loop Grid Slider.
 *
 * Structural container (mirrors NestedSlider's AAE_A_Slider_Track). Holds exactly
 * one Loop Slide Item, which repeats per queried post at render time. The shared
 * nested-slider runtime finds this element by its `.aae-slider-track` class and
 * drives the transform / autoplay / effect on its `.aae-a-slide` children.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Slide_Track extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
		$this->meta( 'permanently_locked', true );
	}

	public static function generate() {
		return parent::generate()->is_locked( true );
	}

	public static function get_type() {
		return 'e-aae-a-loop-slide-track';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-slide-track';
	}

	public function get_title() {
		return esc_html__( 'Slider Track', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-loop-slide-item' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
				->set_items( [] ),
		];
	}

	/**
	 * Zero the track's default padding via the ATOMIC base-style system.
	 *
	 * The track carries Elementor's `.e-con` class. With no explicit padding set,
	 * Elementor's atomic base-styles cache emits a DEFAULT `.elementor .e-{id}
	 * { padding: 10px }` rule (higher specificity than `.e-con` / any plain class,
	 * so a raw CSS override can't beat it without `!important`). That 10px each side
	 * shrinks the track CONTENT box the slides size against (100% / slidesPerView),
	 * so N slides span (viewport − 20px) and leave a ~20px empty strip on the right.
	 *
	 * Defining the base style HERE makes atomic emit `padding: 0` for this element's
	 * own `.e-{id}` class instead of the 10px default — the native, correct fix. And
	 * because it flows through the same atomic style pipeline, a user setting padding
	 * on the track via the Style panel regenerates this class with THEIR value and
	 * wins cleanly (no `!important` fight). `padding` is a valid style-schema key
	 * (Union of Dimensions/Size), so the definition applies rather than silently
	 * failing.
	 *
	 * NOTE: atomic caches per-element CSS — after changing this, the cache may need a
	 * regen (resave the document / Elementor → Tools → Regenerate CSS) for the new
	 * `padding: 0` to appear.
	 */
	protected function define_base_styles(): array {
		$zero = Dimensions_Prop_Type::generate( [
			'block-start'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'block-end'    => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
			'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
		] );

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_prop( 'padding', $zero )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-slide-track' => __DIR__ . '/aae-a-loop-slide-track.html.twig',
		];
	}
}
