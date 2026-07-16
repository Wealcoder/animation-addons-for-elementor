<?php
/**
 * AAE Forms — identity + schema persistence (Milestone 4).
 *
 * Table aae_forms:  one row per form_key (post_id + element_id ownership).
 * aae_form_schemas: append-only version history; exactly one active row
 *                   per form. A new version is written ONLY when the
 *                   canonical schema hash changes.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- queries interpolate only the internal aae_forms/aae_form_schemas table names; every value goes through $wpdb->prepare().

final class Schema_Store {

	/** Is this key owned by a different post/element? (Identity collision oracle.) */
	public static function key_taken_elsewhere( string $key, int $post_id, string $element_id ): bool {
		global $wpdb;
		$table = Database::forms_table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT post_id, element_id FROM {$table} WHERE form_key = %s LIMIT 1",
				$key
			)
		);

		if ( ! $row ) {
			return false;
		}

		return (int) $row->post_id !== $post_id || (string) $row->element_id !== $element_id;
	}

	/** Insert-or-update the identity row; returns the form's row id. */
	public static function upsert_form( string $form_key, int $post_id, string $element_id ): int {
		global $wpdb;
		$table = Database::forms_table();
		$now   = current_time( 'mysql' );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE form_key = %s LIMIT 1",
				$form_key
			)
		);

		if ( $id ) {
			$wpdb->update(
				$table,
				[
					'post_id'    => $post_id,
					'element_id' => $element_id,
					'status'     => 'active',
					'updated_at' => $now,
				],
				[ 'id' => (int) $id ],
				[ '%d', '%s', '%s', '%s' ],
				[ '%d' ]
			);

			return (int) $id;
		}

		$wpdb->insert(
			$table,
			[
				'form_key'   => $form_key,
				'post_id'    => $post_id,
				'element_id' => $element_id,
				'status'     => 'active',
				'created_at' => $now,
				'updated_at' => $now,
			],
			[ '%s', '%d', '%s', '%s', '%s', '%s' ]
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Persist a schema for a form: if its hash differs from the latest
	 * version, deactivate old rows and insert version+1 as active.
	 *
	 * @return int The now-active schema version.
	 */
	public static function sync_schema( int $form_id, array $schema ): int {
		global $wpdb;
		$table = Database::schemas_table();
		$hash  = Schema_Walker::hash( $schema );

		$latest = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT version, schema_hash FROM {$table} WHERE form_id = %d ORDER BY version DESC LIMIT 1",
				$form_id
			)
		);

		if ( $latest && $latest->schema_hash === $hash ) {
			return (int) $latest->version; // unchanged — no new version.
		}

		$next = $latest ? (int) $latest->version + 1 : 1;

		$wpdb->update(
			$table,
			[ 'active' => 0 ],
			[ 'form_id' => $form_id ],
			[ '%d' ],
			[ '%d' ]
		);

		$wpdb->insert(
			$table,
			[
				'form_id'     => $form_id,
				'version'     => $next,
				'schema_hash' => $hash,
				'schema_json' => wp_json_encode( $schema ),
				'source'      => 'elements',
				'active'      => 1,
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%d', '%s' ]
		);

		return $next;
	}

	/**
	 * The active schema row for a form key, or null.
	 *
	 * @return array|null [ 'form_id' => int, 'version' => int, 'schema' => array ]
	 */
	public static function get_active( string $form_key ): ?array {
		global $wpdb;
		$forms   = Database::forms_table();
		$schemas = Database::schemas_table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT f.id AS form_id, s.version, s.schema_json FROM {$schemas} s
			 INNER JOIN {$forms} f ON f.id = s.form_id
			 WHERE f.form_key = %s AND s.active = 1
			 ORDER BY s.version DESC LIMIT 1",
				$form_key
			)
		);

		if ( ! $row ) {
			return null;
		}

		$decoded = json_decode( (string) $row->schema_json, true );

		if ( ! is_array( $decoded ) ) {
			return null;
		}

		return [
			'form_id' => (int) $row->form_id,
			'version' => (int) $row->version,
			'schema'  => $decoded,
		];
	}

	/** The active schema (decoded) for a form key, or null. */
	public static function get_active_schema( string $form_key ): ?array {
		$active = self::get_active( $form_key );

		return $active ? $active['schema'] : null;
	}
}
