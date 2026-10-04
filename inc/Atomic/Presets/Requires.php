<?php
namespace WCF_ADDONS\Atomic\Presets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What a preset needs before it can work — and whether this site has it.
 *
 * A Loop Grid or Loop Filter preset is the first kind in this plugin whose
 * design is not self-contained. A property-directory filter set is wired to a
 * `property` post type and to ACF fields named `price` and `bedrooms`; a shop
 * filter is wired to WooCommerce's price index. Apply one of those on a site
 * that has none of it and every control renders, none of them filters, and
 * nothing anywhere says why. That is the failure this describes: not an error,
 * a design that silently does nothing.
 *
 * THIS CLASS ONLY ANSWERS. It installs nothing and writes nothing. Free can
 * honestly say what a preset needs and what is missing — `post_type_exists()`,
 * `is_plugin_active()` and ACF's own lookup are all free to ask — so the panel
 * can show the requirement whatever licence the site holds. Acting on it is a
 * separate question with a much higher bar, and it lives in Pro behind
 * `aae/preset/requires/install`; `installable()` reports whether anything is
 * listening, exactly as the starter template's dependency screen reports
 * `needs_pro` per row rather than offering a checkbox that would do nothing.
 *
 * DANGER — a `requires` block is UNTRUSTED INPUT. Most of them arrive from the
 * remote preset server, which means a compromised or mistaken server would
 * otherwise be naming plugins for this site to install. normalize() is the only
 * way one may be read: it whitelists the four keys, refuses a plugin slug that
 * is not a plain wordpress.org slug, refuses a plugin file that does not live
 * under that slug's own directory, and caps every list. The capability check
 * that decides whether an install may happen at all is Pro's, and is
 * deliberately NOT `edit_posts` — see that module.
 *
 * @package AnimationAddonsForElementor
 */
final class Requires {

	/** The only keys a requires block may carry. */
	public const KINDS = [ 'plugins', 'post_types', 'taxonomies', 'acf_groups' ];

	/** admin-ajax action. Free owns the door; Pro owns what is behind it. */
	public const ACTION = 'aae_preset_requires_install';

	/**
	 * Hook the install door.
	 *
	 * The ENDPOINT is free and the INSTALLER is Pro, which is the opposite of
	 * where each half usually sits and is deliberate: the nonce, the capability
	 * and — most importantly — the decision about WHAT a given preset requires
	 * all live in one auditable place, and Pro is handed an already-validated
	 * list with nothing left to re-check. It also means a site without Pro gets
	 * an honest "nothing can install this" rather than a 400 from an action
	 * nobody registered.
	 */
	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, [ $this, 'ajax_install' ] );
	}

	/**
	 * Install what one preset needs.
	 *
	 * THE CLIENT NAMES A PRESET; THE SERVER DECIDES WHAT THAT PRESET REQUIRES.
	 * The browser does not send the requirement list, and that is the whole
	 * shape of this handler — the same rule the Loop Grid's filter authoriser
	 * holds for a visitor's URL. A posted list would mean an admin's browser,
	 * or anything that could reach it with a valid nonce, naming plugins to
	 * install; re-resolving the preset server-side means the only installable
	 * things are the ones a preset actually asked for.
	 *
	 * `install_plugins` is checked by Pro per plugin on top of this, because on
	 * multisite `manage_options` is true for a site admin who may not install
	 * anything.
	 */
	public function ajax_install(): void {
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		// NOT edit_posts. The preset picker opens at edit_posts, and a
		// contributor who could make this site install a plugin the remote
		// preset server named would be remote code execution by proxy.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to install anything.', 'animation-addons-for-elementor' ) ], 403 );
		}

		if ( ! self::installable() ) {
			wp_send_json_error( [ 'message' => __( 'Nothing on this site can install a preset’s requirements.', 'animation-addons-for-elementor' ) ], 501 );
		}

		$type = isset( $_POST['element_type'] ) ? sanitize_text_field( wp_unslash( $_POST['element_type'] ) ) : '';
		$id   = isset( $_POST['preset_id'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_id'] ) ) : '';

		if ( '' === $type || '' === $id ) {
			wp_send_json_error( [ 'message' => __( 'Missing preset.', 'animation-addons-for-elementor' ) ], 400 );
		}

		$requires = self::requires_of( $type, $id );
		if ( ! $requires ) {
			wp_send_json_error( [ 'message' => __( 'That preset needs nothing, or no longer exists.', 'animation-addons-for-elementor' ) ], 404 );
		}

		/**
		 * Install one preset's requirements. Pro answers this.
		 *
		 * @param array $results  Per-item outcome, keyed "<kind>:<slug>".
		 * @param array $requires An ALREADY-NORMALISED requires block.
		 */
		$results = (array) apply_filters( 'aae/preset/requires/install', [], $requires );

		// The status is re-read rather than inferred from $results: an install
		// can report success and still leave the requirement unmet (a plugin
		// that downloads but will not activate), and the panel must show what
		// is TRUE now, not what was attempted.
		wp_send_json_success(
			[
				'results' => $results,
				'status'  => self::status( $requires ),
			]
		);
	}

	/**
	 * The requires block of one preset, read from the server's own copy.
	 *
	 * @return array Normalised, or [] when the preset is unknown or needs nothing.
	 */
	private static function requires_of( string $element_type, string $preset_id ): array {
		$cache  = new Cache();
		$result = $cache->get_presets_for_type( $element_type, '', false );

		foreach ( (array) ( $result['presets'] ?? [] ) as $entry ) {
			if ( (string) ( $entry['id'] ?? '' ) !== $preset_id ) {
				continue;
			}
			return self::normalize( $entry['requires'] ?? null );
		}

		return [];
	}

	/**
	 * Per-kind caps. A preset that genuinely needs nine plugins is not a
	 * preset, so these are generous enough never to bite an honest author and
	 * tight enough that a malformed or hostile block cannot turn one panel open
	 * into a hundred `plugins_api()` round trips.
	 */
	private const MAX = [
		'plugins'    => 5,
		'post_types' => 5,
		'taxonomies' => 10,
		'acf_groups' => 10,
	];

	/**
	 * Validate a raw `requires` block into the only shape anything else may
	 * read. Anything unrecognised is dropped rather than passed along: a value
	 * this method does not understand is a value the install engine must never
	 * see.
	 *
	 * @param mixed $raw The `requires` value off a preset entry.
	 * @return array<string, array<int, array>> Empty when there is nothing valid.
	 */
	public static function normalize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$out = [];

		foreach ( self::KINDS as $kind ) {
			if ( empty( $raw[ $kind ] ) || ! is_array( $raw[ $kind ] ) ) {
				continue;
			}

			$items = [];
			foreach ( array_slice( array_values( $raw[ $kind ] ), 0, self::MAX[ $kind ] ) as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$clean = self::normalize_item( $kind, $item );
				if ( $clean ) {
					$items[] = $clean;
				}
			}

			if ( $items ) {
				$out[ $kind ] = $items;
			}
		}

		return $out;
	}

	/** @return array|null One validated entry, or null when it cannot be trusted. */
	private static function normalize_item( string $kind, array $item ): ?array {
		switch ( $kind ) {
			case 'plugins':
				// A wordpress.org slug and nothing else. This value reaches
				// `plugins_api()` and decides what gets downloaded and run, so
				// anything that is not plainly a slug — a URL, a path, a zip —
				// is refused outright rather than sanitised into something that
				// merely looks like one.
				$slug = strtolower( trim( (string) ( $item['slug'] ?? '' ) ) );
				if ( '' === $slug || ! preg_match( '/^[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/', $slug ) ) {
					return null;
				}

				// The entry file is how "is it installed" is asked. It must sit
				// inside that slug's own directory: `acf/../../evil.php` would
				// otherwise let a file_exists() probe walk the filesystem, and a
				// mismatched pair would report a DIFFERENT plugin as satisfying
				// this requirement.
				$file = trim( (string) ( $item['file'] ?? '' ) );
				if ( '' === $file ) {
					$file = $slug . '/' . $slug . '.php';
				}
				if (
					false !== strpos( $file, '..' )
					|| 0 !== strpos( $file, $slug . '/' )
					|| '.php' !== substr( $file, -4 )
					|| ! preg_match( '#^[A-Za-z0-9_\-/]+\.php$#', $file )
				) {
					return null;
				}

				return [
					'kind' => 'plugins',
					'slug' => $slug,
					'file' => $file,
					'name' => self::label( $item, $slug ),
				];

			case 'post_types':
			case 'taxonomies':
				$slug = sanitize_key( (string) ( $item['slug'] ?? '' ) );
				// WordPress refuses a post type or taxonomy name over 20
				// characters, so one that long is a broken requirement, not a
				// long one — better to drop it than to offer an install that
				// register_post_type() will reject.
				if ( '' === $slug || strlen( $slug ) > 20 ) {
					return null;
				}

				$clean = [
					'kind' => $kind,
					'slug' => $slug,
					'name' => self::label( $item, $slug ),
				];

				// The builder needs a singular as well as a plural and warns
				// without one, so it is carried rather than left to the
				// installer to invent. Falling back to the plural is wrong
				// English and right behaviour: a visible label a builder can
				// correct beats a PHP notice on every admin page load.
				$clean['singular'] = ! empty( $item['singular'] ) && is_string( $item['singular'] )
					? sanitize_text_field( $item['singular'] )
					: $clean['name'];

				// The definition the CPT builder needs to create it. Carried
				// verbatim minus its shape check — the builder is what decides
				// which of its own fields it honours, and duplicating that list
				// here is how the two would drift.
				if ( ! empty( $item['args'] ) && is_array( $item['args'] ) ) {
					$clean['args'] = $item['args'];
				}
				if ( 'taxonomies' === $kind ) {
					$clean['object_type'] = array_values( array_filter( array_map(
						'sanitize_key',
						(array) ( $item['object_type'] ?? [] )
					) ) );
				}

				return $clean;

			case 'acf_groups':
				// ACF keys its own records by `key`, and that is what makes an
				// import idempotent — without one there is no way to ask
				// whether this group is already here, so every apply would add
				// another copy.
				$key = (string) ( $item['key'] ?? '' );
				if ( '' === $key || ! preg_match( '/^group_[A-Za-z0-9_]{1,60}$/', $key ) ) {
					return null;
				}
				if ( empty( $item['fields'] ) || ! is_array( $item['fields'] ) ) {
					return null;
				}

				return [
					'kind'   => 'acf_groups',
					'slug'   => $key,
					'name'   => self::label( $item, $key ),
					// The group body goes to acf_import_field_group() whole.
					// ACF owns this schema and validates it itself; a partial
					// copy of its rules here would reject valid groups the day
					// ACF adds a field type.
					'group'  => $item,
				];
		}

		return null;
	}

	/** A human label for a requirement row, falling back to its own slug. */
	private static function label( array $item, string $fallback ): string {
		foreach ( [ 'name', 'title', 'label' ] as $k ) {
			if ( ! empty( $item[ $k ] ) && is_string( $item[ $k ] ) ) {
				return sanitize_text_field( $item[ $k ] );
			}
		}
		return $fallback;
	}

	/**
	 * What this site is missing, per requirement.
	 *
	 * `missing` is the only status that gates anything. `inactive` is reported
	 * separately from `absent` because they need different words in front of a
	 * builder — one is a switch, the other is a download — and lumping them
	 * together is how "install ACF" appears on a site that already has it.
	 *
	 * @param mixed $raw The raw `requires` block; normalised here so callers
	 *                   cannot forget to.
	 * @return array{items: array<int, array>, missing: int, installable: bool}
	 */
	public static function status( $raw ): array {
		$requires = self::normalize( $raw );
		$items    = [];
		$missing  = 0;

		foreach ( $requires as $kind => $list ) {
			foreach ( $list as $item ) {
				$state = self::state_of( $kind, $item );
				if ( 'ok' !== $state ) {
					++$missing;
				}
				$items[] = [
					'kind'   => $kind,
					'slug'   => $item['slug'],
					'name'   => $item['name'],
					'status' => $state,
				];
			}
		}

		return [
			'items'       => $items,
			'missing'     => $missing,
			'installable' => self::installable(),
		];
	}

	/** @return string 'ok' | 'inactive' | 'missing' */
	private static function state_of( string $kind, array $item ): string {
		switch ( $kind ) {
			case 'plugins':
				if ( ! function_exists( 'is_plugin_active' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				if ( ! file_exists( WP_PLUGIN_DIR . '/' . $item['file'] ) ) {
					return 'missing';
				}
				return is_plugin_active( $item['file'] ) ? 'ok' : 'inactive';

			case 'post_types':
				return post_type_exists( $item['slug'] ) ? 'ok' : 'missing';

			case 'taxonomies':
				return taxonomy_exists( $item['slug'] ) ? 'ok' : 'missing';

			case 'acf_groups':
				// Ask ACF, never the posts table: a field group can legitimately
				// live in a JSON file under the theme (acf-json local sync) with
				// no post behind it at all, and importing a second copy over one
				// of those is how a site ends up with duplicate fields.
				if ( ! function_exists( 'acf_get_field_group' ) ) {
					return 'missing';
				}
				return acf_get_field_group( $item['slug'] ) ? 'ok' : 'missing';
		}

		return 'missing';
	}

	/**
	 * Is anything listening that could actually satisfy a requirement?
	 *
	 * Free ships the panel and the answer; Pro ships the installer. With
	 * nothing hooked the panel must say so per row rather than offer a button
	 * that would quietly do nothing — the same rule, and the same shape, as the
	 * starter template's `needs_pro` per dependency.
	 */
	public static function installable(): bool {
		return (bool) has_filter( 'aae/preset/requires/install' );
	}
}
