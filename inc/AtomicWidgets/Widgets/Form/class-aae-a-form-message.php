<?php
/**
 * AAE Form Message — abstract base for the Success/Error message containers.
 *
 * Mirrors Elementor's native Form_Message: an atomic CONTAINER element that
 * is permanently locked (cannot be deleted/moved), hidden from the widget
 * panel (only auto-seeded inside a form), accepts exactly one kind of child
 * (e-paragraph) and seeds one with the status text. Base style is
 * display:none — revealed by the form's `form-state-{value}` class via a
 * rule in form.scss:
 *
 *   form[data-element_type="e-aae-a-form"].form-state-success
 *     [data-element_type="e-aae-a-form-success-message"] { display: block; }
 *
 * Per CLAUDE.md's "Validation message ownership": these are never separate
 * removable widgets a user can drag away.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

abstract class AAE_A_Form_Message extends Atomic_Element_Base {

	use Has_Element_Template;

	public static $widget_description = 'A container for form status messages. Hidden by default, shown based on the form state.';

	abstract protected static function get_background_color(): string;

	abstract protected static function get_text_color(): string;

	abstract protected static function get_default_status_paragraph_text(): string;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
		$this->meta( 'permanently_locked', true );
	}

	public function get_icon() {
		return 'eicon-div-block';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ Atomic_Paragraph::get_element_type() ];
	}

	protected function define_default_children(): array {
		return [
			Atomic_Paragraph::generate()
				->settings(
					[
						'paragraph' => Html_V3_Prop_Type::generate(
							[
								'content'  => String_Prop_Type::generate( static::get_default_status_paragraph_text() ),
								'children' => [],
							]
						),
					]
				)
				->build(),
		];
	}

	protected function define_atomic_controls(): array {
		return [
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

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'display'    => String_Prop_Type::generate( 'none' ),
							'background' => Background_Prop_Type::generate(
								[
									'color' => Color_Prop_Type::generate( static::get_background_color() ),
								]
							),
							'color'      => Color_Prop_Type::generate( static::get_text_color() ),
							'padding'    => Size_Prop_Type::generate(
								[
									'size' => 12,
									'unit' => 'px',
								]
							),
							'text-align' => String_Prop_Type::generate( 'center' ),
							'font-size'  => Size_Prop_Type::generate(
								[
									'size' => 12,
									'unit' => 'px',
								]
							),
							'width'      => Size_Prop_Type::generate(
								[
									'size' => 100,
									'unit' => '%',
								]
							),
						]
					)
				),
		];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-message' => __DIR__ . '/aae-a-form-message.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
