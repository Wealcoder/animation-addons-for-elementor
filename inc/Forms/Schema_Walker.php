<?php
/**
 * AAE Forms — schema walker (Milestone 4).
 *
 * Walks one e-aae-a-form element subtree (the SAVED document data, not
 * rendered HTML) and builds the canonical schema JSON the SSR spec
 * describes: form-level settings, fields in DOM order, submit settings,
 * status-message texts. Every submission later stores the schema version
 * it was made against, so old submissions keep rendering after edits.
 *
 * Field "keys" mirror the twig name-attribute fallbacks EXACTLY (name →
 * _cssid → element id, with the checkbox_/radio_ prefixes) so the schema
 * matches what the browser will actually POST in Milestone 5.
 *
 * Nested e-aae-a-form subtrees are skipped entirely — nested forms are
 * invalid per spec (reject/warn belongs to a later UI pass; the walker
 * just refuses to blend their fields into the outer schema).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema_Walker {

	const SCHEMA_FORMAT = 1;

	/** Map of widgetType → schema field type. */
	const FIELD_TYPES = [
		'e-aae-a-form-input'    => 'input',
		'e-aae-a-form-textarea' => 'textarea',
		'e-aae-a-form-checkbox' => 'checkbox',
		'e-aae-a-form-radio'    => 'radio',
		'e-aae-a-form-select'   => 'select',
		'e-aae-a-form-file'     => 'file',
	];

	/** Build the canonical schema array for one form element. */
	public static function build( array $form ): array {
		$settings = $form['settings'] ?? [];

		$fields  = [];
		$submit  = null;
		$labels  = []; // input-id => label text, resolved once for the whole form.
		$message = [
			'success' => '',
			'error'   => '',
		];

		self::collect( $form['elements'] ?? [], $fields, $submit, $labels, $message );

		// Attach resolved label text to each field by its _cssid.
		foreach ( $fields as &$field ) {
			if ( '' !== $field['css_id'] && isset( $labels[ $field['css_id'] ] ) ) {
				$field['label'] = $labels[ $field['css_id'] ];
			}
		}
		unset( $field );

		$schema = [
			'schema_format' => self::SCHEMA_FORMAT,
			'source'        => 'elements',
			'form_key'      => (string) Prop::read( $settings, 'form_key', '' ),
			'settings'      => [
				'behavior'         => (string) Prop::read( $settings, 'behavior', 'store_email' ),
				'spam_honeypot'    => (bool) Prop::read( $settings, 'spam_honeypot', true ),
				'spam_min_seconds' => (int) Prop::read( $settings, 'spam_min_seconds', 3 ),
				'captcha_provider' => (string) Prop::read( $settings, 'captcha_provider', 'none' ),
				'actions_json'     => (string) Prop::read( $settings, 'actions_json', '' ),
			],
			'fields'        => $fields,
			'submit'        => null !== $submit ? $submit : [
				'text'          => 'Submit',
				'loading_label' => '',
			],
			'messages'      => $message,
		];

		/**
		 * Post-process the whole canonical schema with the raw form subtree
		 * in hand. The pro Conditional Display engine uses this to attach
		 * ancestor (row/container) conditions to the fields inside them.
		 *
		 * @param array $schema Canonical schema (shape above).
		 * @param array $form   Raw e-aae-a-form element node.
		 */
		return apply_filters( 'aae_form/schema_walker/schema', $schema, $form );
	}

	/** Stable hash of a canonical schema (key order is construction order). */
	public static function hash( array $schema ): string {
		return md5( wp_json_encode( $schema ) );
	}

	/** Depth-first, DOM-order walk. Skips nested e-aae-a-form subtrees. */
	private static function collect( array $elements, array &$fields, &$submit, array &$labels, array &$message ): void {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$el_type = $element['elType'] ?? '';
			$type    = ( 'widget' === $el_type ) ? ( $element['widgetType'] ?? '' ) : $el_type;

			// Nested form — refuse to blend its fields into this schema.
			if ( Identity::FORM_TYPE === $type ) {
				continue;
			}

			$settings = $element['settings'] ?? [];

			if ( isset( self::FIELD_TYPES[ $type ] ) ) {
				$fields[] = self::build_field( self::FIELD_TYPES[ $type ], $element, $settings );
			} elseif ( 'e-aae-a-form-label' === $type ) {
				$for = (string) Prop::read( $settings, 'input-id', '' );
				if ( '' !== $for && ! isset( $labels[ $for ] ) ) {
					$labels[ $for ] = Prop::read_html_text( $settings, 'text', '' );
				}
			} elseif ( 'e-aae-a-form-submit' === $type && null === $submit ) {
				$submit = [
					'text'          => Prop::read_html_text( $settings, 'text', 'Submit' ),
					'loading_label' => (string) Prop::read( $settings, 'loading_label', '' ),
				];
			} elseif ( 'e-aae-a-form-success-message' === $type ) {
				$message['success'] = self::first_paragraph_text( $element );
			} elseif ( 'e-aae-a-form-error-message' === $type ) {
				$message['error'] = self::first_paragraph_text( $element );
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] )
				&& ! in_array( $type, [ 'e-aae-a-form-success-message', 'e-aae-a-form-error-message' ], true ) ) {
				self::collect( $element['elements'], $fields, $submit, $labels, $message );
			}
		}
	}

	private static function build_field( string $field_type, array $element, $settings ): array {
		$element_id = (string) ( $element['id'] ?? '' );
		$css_id     = (string) Prop::read( $settings, '_cssid', '' );
		$name       = (string) Prop::read( $settings, 'name', '' );

		$field = [
			'type'       => 'input' === $field_type
				? (string) Prop::read( $settings, 'type', 'text' )
				: $field_type,
			'key'        => self::resolve_key( $field_type, $name, $css_id, $element_id ),
			'element_id' => $element_id,
			'css_id'     => $css_id,
			'label'      => '',
			'required'   => (bool) Prop::read( $settings, 'required', false ),
			// Per-field custom error message — the server echoes it on a 422
			// so backend errors read the same as the frontend's.
			'error_message' => (string) Prop::read( $settings, 'error_message', '' ),
		];

		switch ( $field_type ) {
			case 'input':
			case 'textarea':
				$field['placeholder'] = (string) Prop::read( $settings, 'placeholder', '' );
				// Range rules for number inputs — enforced server-side by the
				// Validator, never trusted from client attributes.
				$field['min'] = (string) Prop::read( $settings, 'min', '' );
				$field['max'] = (string) Prop::read( $settings, 'max', '' );
				break;
			case 'checkbox':
			case 'radio':
				$field['value'] = (string) Prop::read( $settings, 'value', '' );
				break;
			case 'select':
				$field['options']  = (string) Prop::read( $settings, 'options', '' );
				$field['multiple'] = (bool) Prop::read( $settings, 'multiple', false );
				break;
			case 'file':
				// Upload rules the server enforces (accept/max_size come from
				// the SCHEMA at upload time, never from client attributes).
				$field['accept']    = (string) Prop::read( $settings, 'accept', '' );
				$field['max_size']  = (float) Prop::read( $settings, 'max_size', 0 );
				$field['multiple']  = (bool) Prop::read( $settings, 'multiple', false );
				$field['max_files'] = (int) Prop::read( $settings, 'max_files', 0 );
				break;
		}

		/**
		 * Extend a schema field entry with extension-owned data. The pro
		 * Conditional Display engine appends its `conditions` config here so
		 * the Validator can re-evaluate visibility server-side.
		 *
		 * @param array $field    Canonical field entry (built above).
		 * @param mixed $settings Raw element settings.
		 * @param array $element  Raw element node.
		 */
		return apply_filters( 'aae_form/schema_walker/field', $field, $settings, $element );
	}

	/**
	 * The submission key — MUST mirror the twig name fallbacks:
	 * input/textarea/select: name → _cssid → element id;
	 * checkbox/radio: name → '<type>_' + _cssid → '<type>_' + element id.
	 * (Radio twig uses the 'radio_' prefix; checkbox uses 'checkbox_'.)
	 */
	private static function resolve_key( string $field_type, string $name, string $css_id, string $element_id ): string {
		if ( '' !== $name ) {
			return $name;
		}

		$prefix = '';
		if ( 'checkbox' === $field_type ) {
			$prefix = 'checkbox_';
		} elseif ( 'radio' === $field_type ) {
			$prefix = 'radio_';
		}

		return $prefix . ( '' !== $css_id ? $css_id : $element_id );
	}

	/** Text of the first e-paragraph child (status-message body). */
	private static function first_paragraph_text( array $element ): string {
		foreach ( $element['elements'] ?? [] as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}
			$type = ( 'widget' === ( $child['elType'] ?? '' ) ) ? ( $child['widgetType'] ?? '' ) : ( $child['elType'] ?? '' );
			if ( 'e-paragraph' === $type ) {
				return Prop::read_html_text( $child['settings'] ?? [], 'paragraph', '' );
			}
		}

		return '';
	}
}
