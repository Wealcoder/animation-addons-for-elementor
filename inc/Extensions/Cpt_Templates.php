<?php
/**
 * Post Type Builder presets — ready-made "Portfolio", "Team Member", ... post
 * type definitions a user starts from instead of filling every tab by hand.
 *
 * One JSON file per preset under `inc/cpt-templates/`. A file carries the SAME
 * shape `CustomCpt_Cpt_Admin::add_or_update_post_type()` already accepts as
 * `post_meta`, plus a few picker-only fields (id/title/description/icon/
 * category/plural_label). No taxonomy is ever created automatically — a
 * preset may only SUGGEST one by name (`suggested_taxonomy`), left for the
 * user to make in the Taxonomy Builder if they want it.
 *
 * Extra presets: `aaeaddon/cpt_builder/template_dirs` (more folders) or
 * `aaeaddon/cpt_builder/templates` (the decoded list).
 *
 * @package Wealcoder\AnimationAddons\Extensions
 */

namespace Wealcoder\AnimationAddons\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Cpt_Templates {

	/** The checkboxes `AdvanceGeneralPostType.jsx` offers — whitelist, never trust a JSON file blindly. */
	const SUPPORTS = array( 'title', 'author', 'comments', 'trackbacks', 'editor', 'excerpt', 'revisions', 'page-attributes', 'thumbnail', 'custom-fields', 'post-formats' );

	/** @var array|null per-request memo */
	private static $all = null;

	/** Every preset, keyed by id. */
	public static function all(): array {
		if ( null !== self::$all ) {
			return self::$all;
		}
		$dirs = apply_filters( 'aaeaddon/cpt_builder/template_dirs', array( dirname( __DIR__ ) . '/cpt-templates' ) );
		$out  = array();
		foreach ( (array) $dirs as $dir ) {
			foreach ( glob( trailingslashit( $dir ) . '*.json' ) ?: array() as $file ) {
				$raw = json_decode( (string) file_get_contents( $file ), true );
				if ( ! is_array( $raw ) || empty( $raw['meta'] ) ) {
					continue;
				}
				$id         = sanitize_key( $raw['id'] ?? basename( $file, '.json' ) );
				$out[ $id ] = self::shape( $id, $raw );
			}
		}
		self::$all = (array) apply_filters( 'aaeaddon/cpt_builder/templates', $out );
		return self::$all;
	}

	public static function get( string $id ): ?array {
		return self::all()[ sanitize_key( $id ) ] ?? null;
	}

	/** The list the builder's picker shows — the catalogue is small, nothing to page. */
	public static function summaries(): array {
		return array_values( self::all() );
	}

	private static function shape( string $id, array $raw ): array {
		$meta = (array) $raw['meta'];
		return array(
			'id'                 => $id,
			'title'              => sanitize_text_field( (string) ( $raw['title'] ?? $id ) ),
			'description'        => sanitize_text_field( (string) ( $raw['description'] ?? '' ) ),
			'icon'               => sanitize_key( (string) ( $raw['icon'] ?? 'layers' ) ),
			'category'           => sanitize_key( (string) ( $raw['category'] ?? 'content' ) ),
			'plural_label'       => sanitize_text_field( (string) ( $raw['plural_label'] ?? $raw['title'] ?? $id ) ),
			'suggested_taxonomy' => sanitize_text_field( (string) ( $raw['suggested_taxonomy'] ?? '' ) ),
			'meta'               => array(
				'singular_name' => sanitize_text_field( (string) ( $meta['singular_name'] ?? '' ) ),
				'post_type_key' => sanitize_key( (string) ( $meta['post_type_key'] ?? $id ) ),
				'taxonomies'    => array_values( array_map( 'sanitize_key', (array) ( $meta['taxonomies'] ?? array() ) ) ),
				'public'        => ! empty( $meta['public'] ),
				'hierarchical'  => ! empty( $meta['hierarchical'] ),
				'active'        => ! empty( $meta['active'] ),
				'advance'       => ! empty( $meta['advance'] ),
				'supports'      => array_values( array_intersect( (array) ( $meta['supports'] ?? array() ), self::SUPPORTS ) ),
				'description'   => sanitize_text_field( (string) ( $meta['description'] ?? '' ) ),
			),
		);
	}
}
