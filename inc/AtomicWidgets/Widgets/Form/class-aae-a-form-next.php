<?php
/**
 * AAE Form Next — atomic leaf WIDGET. Renders <button type="button"
 * data-aae-form-step-nav="next">. Advances the parent multi-step form to
 * the next step; the click handler lives in multi-step.js, which binds to
 * ANY element carrying data-aae-form-step-nav="next" inside the active
 * step (author-placed widget preferred; multi-step.js only injects its own
 * fallback button when none is found — see buildNav()'s widget-detection).
 *
 * A real styleable widget (not just a DOM button multi-step.js injects) so
 * builders get the same Style-tab control as every other form part —
 * mirrors class-aae-a-form-submit.php almost verbatim.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Inline_Editing_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Next extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Advances a multi-step form to the next step. Validates the current step first — see Multi-Step Forms.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-next';
	}

	public function get_title() {
		return esc_html__( 'Next Step Button', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-arrow-right';
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'step', 'multi-step', 'next', 'button' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'text'       => Html_V3_Prop_Type::make()->default(
				[
					'content'  => String_Prop_Type::generate( __( 'Next', 'animation-addons-for-elementor' ) ),
					'children' => [],
				]
			),

			// Empty by default → Twig falls back to a built-in chevron (→),
			// rendered AFTER the text. Swap in any uploaded SVG via the Icon
			// control, or clear it to fall back to the chevron again — same
			// pattern as class-aae-a-offcanvas-trigger.php's icon prop.
			'icon'       => Svg_Src_Prop_Type::make(),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Inline_Editing_Control::bind_to( 'text' )
							->set_label( __( 'Button text', 'animation-addons-for-elementor' ) ),
						Svg_Control::bind_to( 'icon' )
							->set_label( __( 'Icon', 'animation-addons-for-elementor' ) ),
					]
				),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( '_cssid' )
							->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
							->set_meta( $this->get_css_id_control_meta() ),
					]
				),
		];
	}

	/** Same visual defaults as the Submit button (copied, not shared — each stays independently styleable). */
	protected function define_base_styles(): array {
		$zero = Size_Prop_Type::generate(
			[
				'size' => 0,
				'unit' => 'px',
			]
		);

		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop(
							'background',
							Background_Prop_Type::generate(
								[
									'color' => Color_Prop_Type::generate( '#000' ),
								]
							)
						)
						->add_prop( 'color', Color_Prop_Type::generate( '#fff' ) )
						->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
						->add_prop(
							'padding',
							Dimensions_Prop_Type::generate(
								[
									'block-start'  => Size_Prop_Type::generate(
										[
											'size' => 10,
											'unit' => 'px',
										]
									),
									'inline-end'   => Size_Prop_Type::generate(
										[
											'size' => 30,
											'unit' => 'px',
										]
									),
									'block-end'    => Size_Prop_Type::generate(
										[
											'size' => 10,
											'unit' => 'px',
										]
									),
									'inline-start' => Size_Prop_Type::generate(
										[
											'size' => 28,
											'unit' => 'px',
										]
									),
								]
							)
						)
						->add_prop( 'justify-content', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
						->add_prop( 'border-radius', $zero )
						->add_prop( 'border-width', $zero )
				)
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::HOVER )
						->add_prop(
							'background',
							Background_Prop_Type::generate(
								[
									'color' => Color_Prop_Type::generate( '#323232' ),
								]
							)
						)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-next' => __DIR__ . '/aae-a-form-next.html.twig',
		];
	}
}
