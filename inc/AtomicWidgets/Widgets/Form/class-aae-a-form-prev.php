<?php
/**
 * AAE Form Previous — atomic leaf WIDGET. Renders <button type="button"
 * data-aae-form-step-nav="prev">. Steps the parent multi-step form back
 * one step (never validated — going back is always allowed). See
 * class-aae-a-form-next.php for the full rationale; identical shape.
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

class AAE_A_Form_Prev extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Steps a multi-step form back one step. Never validated. See Multi-Step Forms.';

	public static function get_element_type(): string {
		return 'e-aae-a-form-prev';
	}

	public function get_title() {
		return esc_html__( 'Previous Step Button', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-arrow-left';
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'step', 'multi-step', 'previous', 'back', 'button' ];
	}

	/**
	 * Seeded automatically as a Step's default child, AND listed in the AAE Form
	 * panel category so a builder can rebuild a nav row they deleted or lay one
	 * out by hand.
	 *
	 * AAE_A_Form_Prev extends Atomic_Widget_Base (→ classic Widget_Base), NOT
	 * Atomic_Element_Base — the hook is the classic show_in_panel()/
	 * get_categories()/hide_on_search() trio (Widget_Base::get_initial_config()),
	 * not Atomic_Element_Base's should_show_in_panel()/define_panel_categories().
	 * See class-aae-a-form-next.php for the confirmed-live diagnosis.
	 */
	public function show_in_panel() {
		return true;
	}

	public function get_categories(): array {
		return [ 'aae-atomic-form' ];
	}

	public function hide_on_search() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'text'       => Html_V3_Prop_Type::make()->default(
				[
					'content'  => String_Prop_Type::generate( __( 'Previous', 'animation-addons-for-elementor' ) ),
					'children' => [],
				]
			),

			// Empty by default → Twig falls back to a built-in chevron (←),
			// rendered BEFORE the text. See class-aae-a-form-next.php.
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

	/** Muted/outline defaults (secondary action) — distinct from Next/Submit's solid black. */
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
									'color' => Color_Prop_Type::generate( 'transparent' ),
								]
							)
						)
						->add_prop( 'color', Color_Prop_Type::generate( '#000' ) )
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
									'color' => Color_Prop_Type::generate( '#f1f1f1' ),
								]
							)
						)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-prev' => __DIR__ . '/aae-a-form-prev.html.twig',
		];
	}
}
