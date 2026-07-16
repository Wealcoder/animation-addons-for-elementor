<?php
/**
 * AAE Forms — submission storage (Milestone 6).
 *
 * "Never lose a lead": the submit endpoint calls save() synchronously and
 * only answers 200 AFTER the rows exist (spec pipeline steps 7 and 9).
 * One row in aae_submissions + one aae_submission_values row per clean
 * field. Every submission pins the schema_version it was made against, so
 * old submissions keep rendering after the form is edited.
 *
 * Values arrive pre-sanitized from the Validator (`clean` output only —
 * unknown keys were already dropped). Multi-value fields (multi-select)
 * are stored JSON-encoded; the dashboard (M9) decodes by field_type.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Submissions {

	/**
	 * Persist one validated submission. Returns the submission id, or 0 on
	 * failure (the endpoint then answers 500 — success is never faked).
	 *
	 * @param int    $form_id        aae_forms row id.
	 * @param int    $schema_version Active schema version submitted against.
	 * @param string $form_key       Public form key.
	 * @param array  $clean          Validator 'clean' output: key => string|string[].
	 * @param array  $schema         Active canonical schema (for label/type lookups).
	 * @param array  $meta           source_url / referrer_url / user_agent / ip_hash.
	 */
	public static function save( int $form_id, int $schema_version, string $form_key, array $clean, array $schema, array $meta ): int {
		global $wpdb;
		$now = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			Database::submissions_table(),
			[
				'form_id'        => $form_id,
				'form_key'       => $form_key,
				'schema_version' => $schema_version,
				'status'         => 'new',
				'source_url'     => (string) ( $meta['source_url'] ?? '' ),
				'referrer_url'   => (string) ( $meta['referrer_url'] ?? '' ),
				'utm_json'       => self::utm_json( (string) ( $meta['source_url'] ?? '' ) ),
				'ip_hash'        => (string) ( $meta['ip_hash'] ?? '' ),
				'user_agent'     => (string) ( $meta['user_agent'] ?? '' ),
				'created_at'     => $now,
			],
			[ '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $inserted || $wpdb->insert_id <= 0 ) {
			return 0;
		}

		$submission_id = (int) $wpdb->insert_id;
		$field_info    = self::field_info_map( $schema );

		foreach ( $clean as $key => $value ) {
			$info = $field_info[ $key ] ?? [
				'label' => '',
				'type'  => '',
			];

			$wpdb->insert(
				Database::submission_values_table(),
				[
					'submission_id' => $submission_id,
					'field_key'     => (string) $key,
					'field_label'   => $info['label'],
					'field_type'    => $info['type'],
					'field_value'   => is_array( $value ) ? wp_json_encode( $value ) : (string) $value,
					'created_at'    => $now,
				],
				[ '%d', '%s', '%s', '%s', '%s', '%s' ]
			);
		}

		return $submission_id;
	}

	/**
	 * Map of key => [label, type] from the schema's field list. First entry wins on
	 * duplicate keys (radio groups share one key — same label anyway).
	 */
	private static function field_info_map( array $schema ): array {
		$map = [];

		foreach ( $schema['fields'] ?? [] as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$key = (string) ( $field['key'] ?? '' );
			if ( '' === $key || isset( $map[ $key ] ) ) {
				continue;
			}
			$map[ $key ] = [
				'label' => (string) ( $field['label'] ?? '' ),
				'type'  => (string) ( $field['type'] ?? '' ),
			];
		}

		return $map;
	}

	/** UTM (utm_*) query params of the source URL as JSON, or '' when none. */
	private static function utm_json( string $source_url ): string {
		$query = (string) wp_parse_url( $source_url, PHP_URL_QUERY );
		if ( '' === $query ) {
			return '';
		}

		parse_str( $query, $params );

		$utm = [];
		foreach ( $params as $key => $value ) {
			if ( 0 === strpos( (string) $key, 'utm_' ) && is_scalar( $value ) ) {
				$utm[ (string) $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $utm ? (string) wp_json_encode( $utm ) : '';
	}
}
