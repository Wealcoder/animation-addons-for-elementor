<?php
/**
 * AAE Forms — smart-tag parser (Milestone 7).
 *
 * Resolves the spec's MVP tag syntax inside email subjects/bodies:
 *
 *   {{field.<key>}}                            submitted value by field key
 *   {{site.title}} {{site.url}} {{site.admin_email}}
 *   {{page.title}} {{page.url}}                page the form was submitted from
 *   {{submission.id}} {{submission.date}} {{submission.time}}
 *   {{all_fields}}                             every field as label: value
 *
 * Escaping is context-aware per the spec: 'html' mode escapes every
 * substituted value (email bodies are sent text/html), 'text' mode inserts
 * raw text (subjects, plain-text bodies, webhook JSON builds its own).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Smart_Tags {

	/**
	 * @param string $template Text containing {{...}} tags.
	 * @param array  $context  fields / labels / types / submission_id /
	 *                         page_title / page_url / submitted_at (Y-m-d H:i:s).
	 * @param string $mode     'html' or 'text'.
	 */
	public static function resolve( string $template, array $context, string $mode = 'html' ): string {
		$template = str_replace(
			'{{all_fields}}',
			self::all_fields( $context, $mode ),
			$template
		);

		return (string) preg_replace_callback(
			'/\{\{\s*(field|site|page|submission)\.([A-Za-z0-9_\-]+)\s*\}\}/',
			static function ( $matches ) use ( $context, $mode ) {
				$value = self::lookup( $matches[1], $matches[2], $context );

				return 'html' === $mode ? esc_html( $value ) : $value;
			},
			$template
		);
	}

	private static function lookup( string $group, string $key, array $context ): string {
		switch ( $group ) {
			case 'field':
				return self::field_value( $context['fields'][ $key ] ?? '' );

			case 'site':
				if ( 'title' === $key ) {
					return (string) get_bloginfo( 'name' );
				}
				if ( 'url' === $key ) {
					return (string) home_url();
				}
				if ( 'admin_email' === $key ) {
					return (string) get_option( 'admin_email' );
				}
				return '';

			case 'page':
				if ( 'title' === $key ) {
					return (string) ( $context['page_title'] ?? '' );
				}
				if ( 'url' === $key ) {
					return (string) ( $context['page_url'] ?? '' );
				}
				return '';

			case 'submission':
				if ( 'id' === $key ) {
					return (string) ( $context['submission_id'] ?? '' );
				}
				$submitted_at = (string) ( $context['submitted_at'] ?? '' );
				$timestamp    = $submitted_at ? strtotime( $submitted_at ) : false;
				if ( false === $timestamp ) {
					return '';
				}
				if ( 'date' === $key ) {
					return date_i18n( (string) get_option( 'date_format', 'Y-m-d' ), $timestamp );
				}
				if ( 'time' === $key ) {
					return date_i18n( (string) get_option( 'time_format', 'H:i' ), $timestamp );
				}
				return '';
		}

		return '';
	}

	/** "Label: value" for every submitted field — table in html, lines in text. */
	private static function all_fields( array $context, string $mode ): string {
		$fields = $context['fields'] ?? [];
		$labels = $context['labels'] ?? [];

		if ( 'text' === $mode ) {
			$lines = [];
			foreach ( $fields as $key => $value ) {
				$lines[] = ( $labels[ $key ] ?? $key ) . ': ' . self::field_value( $value );
			}

			return implode( "\n", $lines );
		}

		$rows = '';
		foreach ( $fields as $key => $value ) {
			$rows .= '<tr>'
				. '<td style="padding:6px 12px 6px 0;vertical-align:top;"><strong>' . esc_html( $labels[ $key ] ?? $key ) . '</strong></td>'
				. '<td style="padding:6px 0;">' . nl2br( esc_html( self::field_value( $value ) ) ) . '</td>'
				. '</tr>';
		}

		return '<table cellpadding="0" cellspacing="0" border="0">' . $rows . '</table>';
	}

	private static function field_value( $value ): string {
		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}

		return (string) $value;
	}
}
