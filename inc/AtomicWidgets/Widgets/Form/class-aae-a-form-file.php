<?php
/**
 * AAE Form File — atomic leaf WIDGET. Renders <input type="file">.
 *
 * Local private storage only (no cloud adapters): the frontend runtime
 * pre-uploads each chosen file to POST /aae/v1/forms/{form_key}/uploads
 * during submit, then the submit payload carries the returned refs. The
 * server re-validates everything against the ACTIVE schema (accept/max
 * size live in the schema, never trusted from the browser).
 *
 * Atomic_Widget_Base (not Atomic_Element_Base) so the editor overlay is
 * handled outside the rendered markup — see class-aae-a-form-input.php.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_File extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'File upload field — files are stored privately on this server and validated server-side (type, size).';

	public static function get_element_type(): string {
		return 'e-aae-a-form-file';
	}

	public function get_title() {
		return esc_html__( 'File Upload (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-upload';
	}

	/**
	 * Listed in the AAE Form panel category so a builder can drag this field
	 * into a form instead of only getting what a preset seeded.
	 *
	 * Leaf form widgets extend Atomic_Widget_Base (→ classic Widget_Base), so the
	 * panel reads THIS pair — show_in_panel() + get_categories(). The
	 * Atomic_Element_Base pair (should_show_in_panel() + define_panel_categories())
	 * is silently never called here; see class-atomic.php::register_atomic_categories().
	 */
	public function show_in_panel() {
		return true;
	}

	public function get_categories(): array {
		return [ 'aae-atomic-form' ];
	}

	public function get_keywords() {
		return [ 'atomic', 'form', 'file', 'upload', 'attachment' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Comma-separated extensions ('.pdf,.jpg' or 'pdf,jpg'). The server
			// intersects this with its own whitelist and always blocks
			// executables/scripts/svg/html whatever is listed here.
			'accept'     => String_Prop_Type::make()->default( 'pdf,jpg,jpeg,png' ),

			// Max size per file, in MB (string so empty = plugin default).
			'max_size'   => String_Prop_Type::make()->default( '10' ),

			'multiple'   => Boolean_Prop_Type::make()->default( false ),

			// Max number of files when multiple is on.
			'max_files'  => String_Prop_Type::make()->default( '3' ),

			'required'   => Boolean_Prop_Type::make()->default( false ),

			// Per-field validation message — overrides the form-wide default.
			'error_message' => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( 'accept' )
							->set_label( __( 'Allowed types', 'animation-addons-for-elementor' ) )
							->set_placeholder( 'pdf,jpg,jpeg,png' )
							->set_description(
								__( 'Comma-separated extensions. Executables, scripts, SVG and HTML are always blocked server-side.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'max_size' )
							->set_label( __( 'Max size (MB)', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'multiple' )
							->set_label( __( 'Multiple files', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'max_files' )
							->set_label( __( 'Max files', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Only used when Multiple files is on.', 'animation-addons-for-elementor' )
							),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Text_Control::bind_to( 'error_message' )
							->set_label( __( 'Error message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'This field is required.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown when this field fails validation (missing, too large, wrong type). Leave blank to use the form-wide message.', 'animation-addons-for-elementor' )
							),
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

	/** Dashed drop-target look; matches the input family's metrics. */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'width'         => Size_Prop_Type::generate(
								[
									'size' => 100,
									'unit' => '%',
								]
							),
							'padding'       => Size_Prop_Type::generate(
								[
									'size' => 10,
									'unit' => 'px',
								]
							),
							'border-width'  => Size_Prop_Type::generate(
								[
									'size' => 1,
									'unit' => 'px',
								]
							),
							'border-style'  => String_Prop_Type::generate( 'dashed' ),
							'border-color'  => Color_Prop_Type::generate( '#D6D5D5' ),
							'border-radius' => Size_Prop_Type::generate(
								[
									'size' => 3,
									'unit' => 'px',
								]
							),
							'font-size'     => Size_Prop_Type::generate(
								[
									'size' => 14,
									'unit' => 'px',
								]
							),
							'color'         => Color_Prop_Type::generate( '#0c0d0e' ),
						]
					)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-file' => __DIR__ . '/aae-a-form-file.html.twig',
		];
	}
}
