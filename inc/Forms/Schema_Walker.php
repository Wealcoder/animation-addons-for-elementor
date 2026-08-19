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
		'e-aae-a-form-rating'   => 'rating',
		'e-aae-a-form-range'    => 'range',
		'e-aae-a-form-country'  => 'country',
		'e-aae-a-form-password' => 'password',
		'e-aae-a-form-calculation' => 'calculation',
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
		$steps   = []; // ordered list of { key, title } — empty = single-page form.

		self::collect( $form['elements'] ?? [], $fields, $submit, $labels, $message, $steps, null );

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
			// Present only when the form actually has e-aae-a-form-step
			// children — a plain single-page form's schema has NO 'steps'
			// key at all, so existing single-page forms need no migration
			// and the frontend/validator can treat "no steps" as the
			// single-page case without a separate flag.
			'steps'         => $steps,
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

	/**
	 * Depth-first, DOM-order walk. Skips nested e-aae-a-form subtrees.
	 *
	 * @param string|null $current_step The step's element_id while descending
	 *   inside an e-aae-a-form-step subtree, else null (single-page form, or
	 *   between/outside steps — e.g. a Submit button placed after all steps).
	 *   Threaded down exactly like the pro Conditional Display engine threads
	 *   an ancestor accumulator in its own walk (Engine.php's walk_containers).
	 * @param string $group_label The caption of the e-aae-a-form-range-group
	 *   we are descending inside, else ''. A Range Group's caption is a real
	 *   heading CHILD, not a Label widget pointing at the field's CSS id, so
	 *   build()'s $labels map can never resolve it — it has to be carried in
	 *   from the ancestor, the same way $current_step is.
	 */
	private static function collect( array $elements, array &$fields, &$submit, array &$labels, array &$message, array &$steps, ?string $current_step, string $group_label = '' ): void {
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

			$settings    = $element['settings'] ?? [];
			$step_scope  = $current_step;
			$label_scope = $group_label;

			// The caption is whatever heading/paragraph the builder left at the top
			// of the group — first_label_text() is the same recursive reader the
			// Submit button's label uses, so a caption nested in a flexbox resolves.
			if ( 'e-aae-a-form-range-group' === $type ) {
				$label_scope = self::first_label_text( $element );
			}

			if ( 'e-aae-a-form-step' === $type ) {
				$step_scope = (string) ( $element['id'] ?? '' );
				$steps[]    = [
					'key'   => $step_scope,
					'title' => (string) Prop::read( $settings, 'step_title', '' ),
				];
			} elseif ( isset( self::FIELD_TYPES[ $type ] ) ) {
				$field = self::build_field( self::FIELD_TYPES[ $type ], $element, $settings, $current_step );

				// Inside a Range Group the caption above the slider IS this field's
				// label, so submissions and notification emails read "Total area to be
				// cleaned" instead of a bare field key. An explicit Label widget still
				// wins: build() applies the resolved $labels map after this walk.
				if ( '' !== $group_label && '' === $field['label'] ) {
					$field['label'] = $group_label;
				}

				$fields[] = $field;
			} elseif ( 'e-aae-a-form-label' === $type ) {
				$for = (string) Prop::read( $settings, 'input-id', '' );
				if ( '' !== $for && ! isset( $labels[ $for ] ) ) {
					$labels[ $for ] = Prop::read_html_text( $settings, 'text', '' );
				}
			} elseif ( 'e-aae-a-form-submit' === $type && null === $submit ) {
				// The label is the Paragraph child, not a `text` prop — the Submit
				// button is a container (Paragraph + SVG) so the label and icon
				// can be real, styleable elements.
				$submit = [
					'text'          => self::first_label_text( $element ),
					'loading_label' => (string) Prop::read( $settings, 'loading_label', '' ),
				];
			} elseif ( 'e-aae-a-form-success-message' === $type ) {
				$message['success'] = self::first_paragraph_text( $element );
			} elseif ( 'e-aae-a-form-error-message' === $type ) {
				$message['error'] = self::first_paragraph_text( $element );
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] )
				&& ! in_array( $type, [ 'e-aae-a-form-success-message', 'e-aae-a-form-error-message' ], true ) ) {
				self::collect( $element['elements'], $fields, $submit, $labels, $message, $steps, $step_scope, $label_scope );
			}
		}
	}

	private static function build_field( string $field_type, array $element, $settings, ?string $step = null ): array {
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
			// Which e-aae-a-form-step this field lives in (that step's raw
			// element id), or '' for a single-page form / a field placed
			// outside any step. The Validator uses this for per-step gating.
			'step'          => (string) $step,
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
			case 'rating':
				// Star count — the Validator's range check reuses this as `max`
				// so a crafted request can never post above the rendered stars.
				$field['min'] = '0';
				$field['max'] = (string) Prop::read( $settings, 'max', '5' );
				break;
			case 'range':
				// Min/max the Validator re-checks server-side (never trusted from
				// client attributes) — same reasoning as the input family's own
				// min/max above. Step is display-only (native browser stepping),
				// not a submission-security concern, so it isn't in the schema.
				$field['min'] = (string) Prop::read( $settings, 'min', '0' );
				$field['max'] = (string) Prop::read( $settings, 'max', '100' );
				break;
			case 'password':
				// Rules the Validator re-checks server-side (never trusted
				// from client attributes), plus the storage policy that
				// decides what — if anything — is persisted. `store_mode` has
				// no 'plain' value by design: a password field's value is
				// never stored or sent readable.
				$field['min_length'] = (string) Prop::read( $settings, 'min_length', '' );
				$field['match_field'] = (string) Prop::read( $settings, 'match_field', '' );
				$field['store_mode'] = 'hash' === Prop::read( $settings, 'store_mode', 'never' ) ? 'hash' : 'never';
				$field['mismatch_message'] = (string) Prop::read( $settings, 'mismatch_message', '' );
				break;
			case 'country':
				// Same whitelist mechanics as select. The fallback mirrors the
				// widget's own `options` default (the built-in ISO list) so an
				// untouched field still snapshots the full server-side whitelist
				// instead of an empty one.
				$field['options'] = (string) Prop::read( $settings, 'options', Countries::options_string() );
				break;
			case 'calculation':
				// The formula is the ONLY thing that decides this field's value:
				// the Validator recomputes it from the other posted values and
				// ignores whatever the browser sent, so editing the hidden input
				// in DevTools can't change what gets stored.
				$field['formula']  = (string) Prop::read( $settings, 'formula', '' );
				$field['decimals'] = (int) Prop::read( $settings, 'decimals', 2 );
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

	/**
	 * Label text of a Submit button — the first heading/paragraph anywhere
	 * underneath it.
	 *
	 * RECURSIVE, unlike first_paragraph_text() below: the default children are
	 * Paragraph + SVG directly under the button, but a builder is free to nest
	 * the label inside a flexbox — and buttons saved before the wrapper row was
	 * dropped still have it one level down. Heading stores its text under
	 * `title`, Paragraph under `paragraph` — both are accepted so swapping one
	 * for the other on the canvas doesn't blank the schema's submit label.
	 */
	private static function first_label_text( array $element ): string {
		foreach ( $element['elements'] ?? [] as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			$type = ( 'widget' === ( $child['elType'] ?? '' ) ) ? ( $child['widgetType'] ?? '' ) : ( $child['elType'] ?? '' );
			$key  = [
				'e-heading'   => 'title',
				'e-paragraph' => 'paragraph',
			][ $type ] ?? null;

			if ( $key ) {
				$text = Prop::read_html_text( $child['settings'] ?? [], $key, '' );
				if ( '' !== $text ) {
					return $text;
				}
			}

			$nested = self::first_label_text( $child );
			if ( '' !== $nested ) {
				return $nested;
			}
		}

		return '';
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
