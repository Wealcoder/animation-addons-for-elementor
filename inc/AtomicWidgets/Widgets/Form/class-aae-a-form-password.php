<?php
/**
 * AAE Form Password — atomic leaf WIDGET. Renders <input type="password">.
 *
 * A SEPARATE element type rather than the Input widget's existing
 * `password` type value, because storage behavior must be schema-driven and
 * unambiguous: a field of type `password` is NEVER stored in plain text and
 * never reaches emails/webhooks/CSV as a real value (see the `store_mode`
 * prop and Validator/Submissions redaction). Deriving that from an Input's
 * mutable `type` prop would mean a builder flipping Text→Password later
 * leaves already-stored plaintext behind with no signal.
 *
 * The Input widget keeps its `password` type for the legacy/simple case
 * (masked entry, plainly stored) — this widget is the privacy-correct one.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
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
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Form_Password extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Password field — masked entry, never stored in plain text. Supports a confirm-match partner field.';

	/** store_mode values: what reaches the database / actions. */
	const STORE_MODES = [ 'never', 'hash' ];

	public static function get_element_type(): string {
		return 'e-aae-a-form-password';
	}

	public function get_title() {
		return esc_html__( 'Password (AAE)', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-lock-user';
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
		return [ 'atomic', 'form', 'password', 'secret', 'confirm' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'placeholder' => String_Prop_Type::make()->default( '' ),
			'required'    => Boolean_Prop_Type::make()->default( false ),

			// Autofill hint: new-password on a signup field stops browsers
			// pasting the saved password and offers a generated one.
			'autocomplete' => String_Prop_Type::make()
				->enum( [ 'new-password', 'current-password', 'off' ] )
				->default( 'new-password' ),

			// Minimum length, enforced BOTH client- and server-side. '' = none.
			'min_length' => String_Prop_Type::make()->default( '' ),

			// Confirm-match: the _cssid of the OTHER password field this one
			// must equal. Set it on the "Confirm password" field, pointing at
			// the first one. Empty = no match check.
			'match_field' => String_Prop_Type::make()->default( '' ),

			// Reveal (eye) toggle button next to the field.
			'show_toggle' => Boolean_Prop_Type::make()->default( true ),

			/*
			 * What reaches storage/actions:
			 *   never — the value is validated then DISCARDED (default). The
			 *           submission records the field as "********".
			 *   hash  — a one-way wp_hash_password() digest is stored instead
			 *           of the value (for handoff to an account-creation
			 *           integration). Still never emailed/webhooked raw.
			 * There is deliberately NO "plain" option.
			 */
			'store_mode' => String_Prop_Type::make()->enum( self::STORE_MODES )->default( 'never' ),

			'error_message'   => String_Prop_Type::make()->default( '' ),
			'mismatch_message' => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Text_Control::bind_to( 'placeholder' )
							->set_label( __( 'Placeholder', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'required' )
							->set_label( __( 'Required', 'animation-addons-for-elementor' ) ),
						Switch_Control::bind_to( 'show_toggle' )
							->set_label( __( 'Show/hide button', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Adds an eye button that reveals the typed password.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'min_length' )
							->set_label( __( 'Minimum length', 'animation-addons-for-elementor' ) )
							->set_placeholder( '8' )
							->set_description(
								__( 'Shortest accepted password, checked on the server too. Leave blank for no minimum.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'match_field' )
							->set_label( __( 'Must match field ID', 'animation-addons-for-elementor' ) )
							->set_placeholder( 'aae-password' )
							->set_description(
								__( 'For a “Confirm password” field: enter the ID of the first password field. Leave blank on the first field itself.', 'animation-addons-for-elementor' )
							),
						Select_Control::bind_to( 'store_mode' )
							->set_label( __( 'Storage', 'animation-addons-for-elementor' ) )
							->set_options(
								[
									[
										'value' => 'never',
										'label' => __( 'Never store (recommended)', 'animation-addons-for-elementor' ),
									],
									[
										'value' => 'hash',
										'label' => __( 'Store one-way hash', 'animation-addons-for-elementor' ),
									],
								]
							)
							->set_description(
								__( 'Passwords are never stored, emailed or sent to webhooks in readable form. “Never store” records the field as ******** ; “Store one-way hash” keeps an irreversible digest for account-creation integrations.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'error_message' )
							->set_label( __( 'Error message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'This field is required.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown when this field is empty or too short. Leave blank to use the form-wide message.', 'animation-addons-for-elementor' )
							),
						Text_Control::bind_to( 'mismatch_message' )
							->set_label( __( 'Mismatch message', 'animation-addons-for-elementor' ) )
							->set_placeholder( __( 'Passwords do not match.', 'animation-addons-for-elementor' ) )
							->set_description(
								__( 'Shown when this field does not match the field above. Only used when “Must match field ID” is set.', 'animation-addons-for-elementor' )
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

	protected function define_base_styles(): array {
		// Mirrors AAE_A_Form_Input so a password field sits flush with the
		// text fields around it.
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
							'height'        => Size_Prop_Type::generate(
								[
									'size' => 40,
									'unit' => 'px',
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
							'border-style'  => String_Prop_Type::generate( 'solid' ),
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
				)
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::FOCUS )
						->add_props(
							[
								'border-color'  => Color_Prop_Type::generate( '#706F6F' ),
								'outline-style' => String_Prop_Type::generate( 'none' ),
							]
						)
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form-password' => __DIR__ . '/aae-a-form-password.html.twig',
		];
	}
}
