<?php
/**
 * Elementor v4 Atomic Widget - Alert Box Example.
 *
 * A complete example showing how to build an Atomic Widget from scratch.
 */

namespace MyPlugin\Widgets\Alert_Box;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Inline_Editing_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Link_Control;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alert Box Atomic Widget.
 *
 * Step 1: Unique element type (slug) - identity of the widget.
 * Step 2: define_props_schema() - declare all data fields.
 * Step 3: define_atomic_controls() - editor panel form.
 * Step 4: define_base_styles() - default styles (auto CSS class).
 * Step 5: get_templates() - path to Twig template file.
 */
class Alert_Box extends Atomic_Widget_Base {
	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	// Step 1: Unique element type (slug).
	public static function get_element_type(): string {
		return 'e-alert-box';
	}

	public function get_title() {
		return esc_html__( 'Alert Box', 'my-plugin' );
	}

	public function get_keywords() {
		return [ 'ato', 'atom', 'atomic', 'alert', 'box', 'message', 'notice' ];
	}

	public function get_icon() {
		return 'eicon-alert';
	}

	// Step 2: Props Schema - declare all data fields here.
	// Every control's bind_to('field') MUST exist in this schema.
	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()
				->default( [] ),

			'type' => String_Prop_Type::make()
				->enum( [ 'success', 'error', 'warning', 'info' ] )
				->default( 'info' )
				->description( 'Select the alert type' ),

			'title' => String_Prop_Type::make()
				->default( 'Important Message' )
				->description( 'The alert title' ),

			'content' => Html_V3_Prop_Type::make()
				->default( [
					'content'  => String_Prop_Type::generate( 'Type your message here...' ),
					'children' => [],
				] )
				->description( 'The detailed alert text' ),

			'link' => Link_Prop_Type::make(),

			'attributes' => Attributes_Prop_Type::make()
				->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	// Step 3: Atomic Controls - editor panel sections and controls.
	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'my-plugin' ) )
				->set_items( [
					Select_Control::bind_to( 'type' )
						->set_label( __( 'Alert Type', 'my-plugin' ) )
						->set_options( [
							[
								'value' => 'success',
								'label' => 'Success',
							],
							[
								'value' => 'error',
								'label' => 'Error',
							],
							[
								'value' => 'warning',
								'label' => 'Warning',
							],
							[
								'value' => 'info',
								'label' => 'Info',
							],
						] ),

					Text_Control::bind_to( 'title' )
						->set_label( __( 'Title', 'my-plugin' ) )
						->set_placeholder( 'Type your title' ),

					Inline_Editing_Control::bind_to( 'content' )
						->set_label( __( 'Message', 'my-plugin' ) )
						->set_placeholder( 'Type your detailed message...' ),
				] ),

			Section::make()
				->set_label( __( 'Settings', 'my-plugin' ) )
				->set_id( 'settings' )
				->set_items( $this->get_settings_controls() ),
		];
	}

	protected function get_settings_controls(): array {
		return [
			Link_Control::bind_to( 'link' )
				->set_label( __( 'Link (optional)', 'my-plugin' ) )
				->set_placeholder( 'https://...' )
				->set_meta( [ 'topDivider' => true ] ),

			Text_Control::bind_to( '_cssid' )
				->set_label( __( 'CSS ID', 'my-plugin' ) )
				->set_meta( $this->get_css_id_control_meta() ),
		];
	}

	// Step 4: Base Styles - default styles that auto-generate a CSS class.
	protected function define_base_styles(): array {
		$padding_value = Dimensions_Prop_Type::generate( [
			'block-start'  => Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
			'inline-end'   => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
			'block-end'    => Size_Prop_Type::generate( [ 'size' => 16, 'unit' => 'px' ] ),
			'inline-start' => Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] ),
		] );

		$border_radius = Size_Prop_Type::generate( [
			'size' => 8,
			'unit' => 'px',
		] );

		$border_width = Size_Prop_Type::generate( [
			'size' => 1,
			'unit' => 'px',
		] );

		$background = Background_Prop_Type::generate( [
			'color' => Color_Prop_Type::generate( '#e7f0fd' ),
		] );

		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'background', $background )
						->add_prop( 'padding', $padding_value )
						->add_prop( 'border-radius', $border_radius )
						->add_prop( 'border-width', $border_width )
						->add_prop( 'border-style', 'solid' )
						->add_prop( 'border-color', Color_Prop_Type::generate( '#bcd6fb' ) )
						->add_prop( 'color', Color_Prop_Type::generate( '#1a3a6c' ) )
						->add_prop( 'display', 'flex' )
						->add_prop( 'align-items', 'flex-start' )
						->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ) )
				),
		];
	}

	// Step 5: Twig template path. key => path format.
	protected function get_templates(): array {
		return [
			'elementor/elements/alert-box' => __DIR__ . '/alert-box.html.twig',
		];
	}
}