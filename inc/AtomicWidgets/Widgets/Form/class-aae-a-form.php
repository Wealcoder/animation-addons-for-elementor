<?php
/**
 * AAE Form — atomic container element (parent), renders <form> with a
 * flex-wrap base style (mirrors Elementor's native e-form).
 *
 * Per-part widget architecture: every field part is its OWN child widget —
 * a label and its input are loose siblings linked only by ID
 * (label.input-id → input._cssid → for=/id=). No repeater, no combined
 * "field" element.
 *
 * No submit logic, no REST, no DB, no Bot Shield yet — see CLAUDE.md's
 * "AAE Atomic Form Builder" section for the full milestone plan.
 *
 * Default structure on drop:
 *   AAE_A_Form (this class, <form>)
 *     ├─ Label "First name"  + Input(text)
 *     ├─ Label "Last name"   + Input(text)
 *     ├─ Label "Email"       + Input(email)
 *     ├─ Label "Message"     + Textarea
 *     └─ AAE_A_Form_Submit (locked)
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
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Textarea_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Toggle_Control;
use Elementor\Modules\AtomicWidgets\Elements\Base\Element_Builder;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

// Per-part field widgets — each renders one HTML element (input/label/…),
// composed as loose siblings by define_default_children(). This replaces the
// old combined e-aae-a-form-field + aae-form-fields repeater approach.
require_once __DIR__ . '/class-aae-a-form-label.php';
require_once __DIR__ . '/class-aae-a-form-input.php';
require_once __DIR__ . '/class-aae-a-form-textarea.php';
require_once __DIR__ . '/class-aae-a-form-checkbox.php';
require_once __DIR__ . '/class-aae-a-form-radio.php';
require_once __DIR__ . '/class-aae-a-form-select.php';
require_once __DIR__ . '/class-aae-a-form-submit.php';
require_once __DIR__ . '/class-aae-a-form-success-message.php';
require_once __DIR__ . '/class-aae-a-form-error-message.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Label;
use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Input;
use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Textarea;
use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Checkbox;
use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Submit;
use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Success_Message;
use WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Error_Message;

class AAE_A_Form extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-form';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-form';
	}

	public function get_title() {
		return esc_html__( 'AAE Form', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_keywords() {
		return [ 'form', 'contact', 'lead', 'atomic', 'submit' ];
	}

	/**
	 * Prop names and defaults follow the SSR spec (see CLAUDE.md → "AAE
	 * Atomic Form Builder" → "Atomic element requirements"). `form_key` and
	 * `actions_json` stay empty/unused until the Identity (Milestone 4) and
	 * Actions (Milestone 7) work lands — they only need to exist on the
	 * schema now so later milestones don't have to migrate old elements.
	 */
	protected static function define_props_schema(): array {
		return [
			'classes'          => Classes_Prop_Type::make()->default( [] ),
			'attributes'       => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Identity — populated by the Schema Sync milestone. Hidden from
			// the panel; not user-facing per CLAUDE.md's Form identity rules.
			'form_key'         => String_Prop_Type::make()->default( '' ),

			// Actions After Submit — populated by the Actions milestone.
			'actions_json'     => String_Prop_Type::make()->default( '' ),

			'behavior'         => String_Prop_Type::make()
				->enum( [ 'store_email', 'store', 'email' ] )
				->default( 'store_email' ),

			// Editor preview state — the form twig emits form-state-{value} as
			// a class, which reveals the matching (display:none) message
			// container via a rule in form.scss. Message TEXT lives in the
			// message containers' paragraph children, not in props (native
			// e-form pattern; replaces the old msg_success/msg_error props).
			'form-state'       => String_Prop_Type::make()
				->enum( [ 'default', 'success', 'error' ] )
				->default( 'default' )
				->meta( 'generates_class', 'form-state-{value}' ),

			'spam_honeypot'    => Boolean_Prop_Type::make()->default( true ),
			'spam_min_seconds' => Number_Prop_Type::make()->default( 3 ),

			'captcha_provider' => String_Prop_Type::make()
				->enum( [ 'none', 'recaptcha_v3', 'turnstile' ] )
				->default( 'none' ),
		];
	}

	protected function define_atomic_controls(): array {
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';
		require_once __DIR__ . '/class-aae-a-form-actions-control.php';

		// No "Fields" repeater section anymore — fields are real child widgets
		// (label/input/textarea/…) edited by selecting them in the canvas /
		// Structure panel, exactly like Elementor's native atomic form.
		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items(
					[
						AAE_A_Preset_Picker_Control::make()
							->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
							->set_meta( [ 'layout' => 'custom' ] ),
					]
				),

			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						// Preview the success/error message containers in the
						// editor without submitting (native e-form "States").
						$this->build_state_toggle(),
						Select_Control::bind_to( 'behavior' )
						->set_label( __( 'Submission Behavior', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'topDivider' => true ] )
						->set_options(
							[
								[
									'value' => 'store_email',
									'label' => __( 'Store + Admin Email', 'animation-addons-for-elementor' ),
								],
								[
									'value' => 'store',
									'label' => __( 'Store Only', 'animation-addons-for-elementor' ),
								],
								[
									'value' => 'email',
									'label' => __( 'Email Only', 'animation-addons-for-elementor' ),
								],
							]
						),
					]
				),

			Section::make()
				->set_id( 'aae_form_actions' )
				->set_label( __( 'Actions After Submit', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						// Opens the actions dialog (admin email / visitor auto
						// reply / webhook) — reads/writes the hidden actions_json.
						AAE_A_Form_Actions_Control::make()
							->set_label( __( 'Manage Actions', 'animation-addons-for-elementor' ) )
							->set_meta( [ 'layout' => 'custom' ] ),
					]
				),

			Section::make()
				->set_id( 'bot-shield' )
				->set_label( __( 'Bot Shield', 'animation-addons-for-elementor' ) )
				->set_items(
					[
						Switch_Control::bind_to( 'spam_honeypot' )
							->set_label( __( 'Honeypot', 'animation-addons-for-elementor' ) ),
						Number_Control::bind_to( 'spam_min_seconds' )
							->set_label( __( 'Minimum Submit Time (seconds)', 'animation-addons-for-elementor' ) )
							->set_min( 0 ),
						// Providers stay listed (schema enum already ships them) but
						// are honestly labeled until the integration lands (Pro).
						Select_Control::bind_to( 'captcha_provider' )
							->set_label( __( 'CAPTCHA Provider', 'animation-addons-for-elementor' ) )
							->set_options(
								[
									[
										'value' => 'none',
										'label' => __( 'None', 'animation-addons-for-elementor' ),
									],
									[
										'value' => 'recaptcha_v3',
										'label' => __( 'reCAPTCHA v3 (coming soon)', 'animation-addons-for-elementor' ),
									],
									[
										'value' => 'turnstile',
										'label' => __( 'Turnstile (coming soon)', 'animation-addons-for-elementor' ),
									],
								]
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

	/**
	 * Normal/Success/Error preview toggle (native e-form "States" control).
	 * Guarded the way native does — falls back gracefully if the runtime's
	 * Toggle_Control API changes.
	 */
	private function build_state_toggle() {
		$state_control = Toggle_Control::bind_to( 'form-state' )
			->set_label( __( 'States', 'animation-addons-for-elementor' ) );

		if ( $state_control instanceof Toggle_Control ) {
			$state_control
				->add_options(
					[
						'default' => [ 'title' => __( 'Normal', 'animation-addons-for-elementor' ) ],
						'success' => [ 'title' => __( 'Success', 'animation-addons-for-elementor' ) ],
						'error'   => [ 'title' => __( 'Error', 'animation-addons-for-elementor' ) ],
					]
				)
				->set_exclusive( true )
				->set_convert_options( true )
				->set_size( 'tiny' )
				->set_full_width( true );
		}

		return $state_control;
	}

	/**
	 * Default drop-in form — mirrors Elementor's native e-form starter tree:
	 * each field is a LABEL + INPUT pair of loose sibling widgets, linked by
	 * ID (label.input-id → input._cssid → for=/id=), then a checkbox row
	 * (the ONLY wrapped pair — e-flexbox with checkbox + label inline),
	 * Submit, and the two locked status-message containers.
	 */
	protected function define_default_children(): array {
		$prefix = 'aae-form-';

		return [
			$this->build_label( __( 'First name', 'animation-addons-for-elementor' ), $prefix . 'first-name' ),
			$this->build_input( __( 'First name', 'animation-addons-for-elementor' ), 'text', $prefix . 'first-name' ),

			$this->build_label( __( 'Last name', 'animation-addons-for-elementor' ), $prefix . 'last-name' ),
			$this->build_input( __( 'Last name', 'animation-addons-for-elementor' ), 'text', $prefix . 'last-name' ),

			$this->build_label( __( 'Email', 'animation-addons-for-elementor' ), $prefix . 'email' ),
			$this->build_input( __( 'your@mail.com', 'animation-addons-for-elementor' ), 'email', $prefix . 'email' ),

			$this->build_label( __( 'Message', 'animation-addons-for-elementor' ), $prefix . 'message' ),
			$this->build_input( __( 'Your message', 'animation-addons-for-elementor' ), 'textarea', $prefix . 'message' ),

			$this->build_checkbox_row( __( 'Checkbox', 'animation-addons-for-elementor' ), $prefix . 'checkbox' ),

			// Text set explicitly (like native e-form does) — relying on the
			// schema default alone left the button empty in the canvas. Not
			// locked: locking would also block moving it (e.g. into a flexbox
			// row inside the form).
			AAE_A_Form_Submit::generate()
				->settings(
					[
						'text' => Html_V3_Prop_Type::generate(
							[
								'content'  => String_Prop_Type::generate( __( 'Submit', 'animation-addons-for-elementor' ) ),
								'children' => [],
							]
						),
					]
				)
				->editor_settings( [ 'title' => __( 'Submit', 'animation-addons-for-elementor' ) ] )
				->build(),

			// Locked status-message containers — hidden (display:none base
			// style) until the form gets a form-state-success/error class.
			AAE_A_Form_Success_Message::generate()
				->editor_settings( [ 'title' => __( 'Success message', 'animation-addons-for-elementor' ) ] )
				->build(),
			AAE_A_Form_Error_Message::generate()
				->editor_settings( [ 'title' => __( 'Error message', 'animation-addons-for-elementor' ) ] )
				->build(),
		];
	}

	/**
	 * Checkbox + its label wrapped in an e-flexbox row (the only field pair
	 * that gets a wrapper — mirrors native build_checkbox_row()).
	 */
	private function build_checkbox_row( string $label_text, string $checkbox_id ): array {
		$checkbox = AAE_A_Form_Checkbox::generate()
			->settings(
				[
					'_cssid' => String_Prop_Type::generate( $checkbox_id ),
				]
			)
			->editor_settings( [ 'title' => __( 'Checkbox', 'animation-addons-for-elementor' ) ] )
			->build();

		$label = $this->build_label( $label_text, $checkbox_id );

		return Element_Builder::make( 'e-flexbox' )
			->children( [ $checkbox, $label ] )
			->settings(
				[
					'classes' => Classes_Prop_Type::generate( [ 'aae-form-checkbox-row' ] ),
				]
			)
			->editor_settings( [ 'title' => __( 'Checkbox row', 'animation-addons-for-elementor' ) ] )
			->build();
	}

	/** One <label> widget pointing at an input's _cssid (renders for=). */
	private function build_label( string $text, string $input_id ): array {
		return AAE_A_Form_Label::generate()
			->settings(
				[
					'text'     => Html_V3_Prop_Type::generate(
						[
							'content'  => String_Prop_Type::generate( $text ),
							'children' => [],
						]
					),
					'input-id' => String_Prop_Type::generate( $input_id ),
				]
			)
			->editor_settings( [ 'title' => __( 'Label', 'animation-addons-for-elementor' ) ] )
			->build();
	}

	/** One <input> (or <textarea> when $type is 'textarea') widget with the given _cssid. */
	private function build_input( string $placeholder, string $type, string $input_id ): array {
		if ( 'textarea' === $type ) {
			return AAE_A_Form_Textarea::generate()
				->settings(
					[
						'placeholder' => String_Prop_Type::generate( $placeholder ),
						'rows'        => Number_Prop_Type::generate( 4 ),
						'_cssid'      => String_Prop_Type::generate( $input_id ),
					]
				)
				->editor_settings( [ 'title' => __( 'Text area', 'animation-addons-for-elementor' ) ] )
				->build();
		}

		return AAE_A_Form_Input::generate()
			->settings(
				[
					'placeholder' => String_Prop_Type::generate( $placeholder ),
					'type'        => String_Prop_Type::generate( $type ),
					'_cssid'      => String_Prop_Type::generate( $input_id ),
				]
			)
			->editor_settings( [ 'title' => __( 'Input', 'animation-addons-for-elementor' ) ] )
			->build();
	}

	/**
	 * Unrestricted (like Elementor's native e-form): empty array = no
	 * restriction, any atomic element can be dropped inside the form.
	 */
	protected function define_allowed_child_types(): array {
		return [];
	}

	/**
	 * Flex-wrap layout, matching the native e-form base styles: fields flow
	 * as wrapping rows; each input's own 100%-width base style makes a
	 * label+input pair read as one row until the user resizes fields.
	 */
	protected function define_base_styles(): array {
		return [
			'base'                        => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'display'        => String_Prop_Type::generate( 'flex' ),
							'flex-direction' => String_Prop_Type::generate( 'row' ),
							'flex-wrap'      => String_Prop_Type::generate( 'wrap' ),
							'align-items'    => String_Prop_Type::generate( 'flex-start' ),
							'align-content'  => String_Prop_Type::generate( 'start' ),
							'gap'            => Size_Prop_Type::generate(
								[
									'size' => 10,
									'unit' => 'px',
								]
							),
							'padding'        => Size_Prop_Type::generate(
								[
									'size' => 20,
									'unit' => 'px',
								]
							),
						]
					)
				),

			// Checkbox row: checkbox + label inline (mirrors native
			// .e-form-checkbox-row scoped styles).
			'base .aae-form-checkbox-row' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props(
						[
							'align-items' => String_Prop_Type::generate( 'center' ),
							'gap'         => Size_Prop_Type::generate(
								[
									'size' => 8,
									'unit' => 'px',
								]
							),
							'padding'     => Size_Prop_Type::generate(
								[
									'size' => 0,
									'unit' => 'px',
								]
							),
						]
					)
				),
		];
	}

	protected function define_default_html_tag() {
		return 'form';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-form' => __DIR__ . '/aae-a-form.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
