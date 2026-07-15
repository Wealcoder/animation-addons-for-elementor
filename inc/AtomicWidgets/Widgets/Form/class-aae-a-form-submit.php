<?php
/**
 * AAE Form Submit — atomic leaf WIDGET. Renders <button type="submit">
 * directly — mirrors Elementor's native e-form-submit-button.
 *
 * Atomic_Widget_Base (not Atomic_Element_Base) so the editor overlay is
 * handled outside the rendered markup. See class-aae-a-form-input.php.
 *
 * Locked by default (generate() pre-locks the builder, slider-dot pattern)
 * so the form cannot become silently unsubmittable via accidental deletion.
 *
 * No submit/REST logic yet (Milestone 5 "REST submit runtime") — the
 * loading_label prop is stored now as a data attribute so the runtime
 * milestone doesn't need a schema migration.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Inline_Editing_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Submit extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Form submit button.';

	/**
	 * NOT locked and visible in the panel (native e-form-submit-button
	 * behavior): locking would also block MOVING it — e.g. into a flexbox
	 * row inside the form — which is a legit layout. The "form must stay
	 * submittable" guarantee moves to a save-time warning in the Schema
	 * Sync milestone instead of a hard lock.
	 */
	public static function get_element_type(): string {
		return 'e-aae-a-form-submit';
	}

	public function get_title() {
		return esc_html__( 'Submit Button', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-button';
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'submit', 'button' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'       => Classes_Prop_Type::make()->default( [] ),
			'attributes'    => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'text'          => Html_V3_Prop_Type::make()->default(
				[
					'content'  => String_Prop_Type::generate( __( 'Submit', 'animation-addons-for-elementor' ) ),
					'children' => [],
				]
			),

			// Stored now, used by the Milestone-5 submit runtime (button text
			// swap while the request is in flight).
			'loading_label' => String_Prop_Type::make()->default( __( 'Sending...', 'animation-addons-for-elementor' ) ),
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
						Text_Control::bind_to( 'loading_label' )
							->set_label( __( 'Loading text', 'animation-addons-for-elementor' ) ),
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

	/**
	 * Copied from Elementor's native e-form-submit-button base styles.
	 * CAUTION: `background` must be a Background_Prop_Type (a bare
	 * Color_Prop_Type is invalid) and `cursor` is not a style-schema key —
	 * either one silently voids the ENTIRE definition (that bug shipped as
	 * an unstyled, colorless button).
	 */
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
			'elementor/elements/aae-a-form-submit' => __DIR__ . '/aae-a-form-submit.html.twig',
		];
	}
}
