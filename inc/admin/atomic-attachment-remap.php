<?php
namespace WCF_ADDONS\Admin\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Re-point atomic (Elementor V4) media references after a WXR content import.
 *
 * WHY THIS EXISTS. An atomic image stores ONLY the attachment id —
 * `Image_Src_Prop_Type::validate_value()` is `id XOR url`, so a media-library
 * image has no url in `_elementor_data` at all. At render time
 * `Image_Transformer` calls `wp_get_attachment_image_src( id )` and THROWS when
 * that fails; the props resolver turns the exception into `null` and the widget
 * renders nothing. If the demo's id happens to exist on the importing site it
 * renders the WRONG image instead. There is no url fallback once an id is set.
 *
 * The WXR importer copies `_elementor_data` verbatim (`process_post_meta()`),
 * and its `url_remap` only touches `post_content`, so every V4 page arrives
 * carrying the demo site's attachment ids. V3 never surfaced this because a
 * classic widget falls back to the (hot-linked) url when the id fails.
 *
 * URL re-download — the page-import lane's route — is impossible here: there is
 * no url to download from, and `Image_Src_Import_Transformer` returns null for
 * a url-less value, which would ERASE the image. The only correct fix is the
 * importer's own id map: WXRImporter fires `wp_import_insert_post` for every
 * post it creates, attachments included, with the original id alongside the new
 * one. This class records that pair per import and, when `import_end` fires,
 * rewrites every `image-attachment-id` / `video-attachment-id` prop in the
 * posts that arrived — settings, local styles (background images) and
 * interactions alike, since the walk is over the whole element array.
 *
 * Unknown ids are left untouched: a wrong image is a visible, fixable problem;
 * an erased one is not.
 *
 * State lives in one option rather than the importer's memory because AAE's
 * importer is CHUNKED — it hands off to a new AJAX request every ~25 s
 * (Importer.php), and `import_end` only fires from the last one.
 */
class Atomic_Attachment_Remap {

	const MAP_OPTION   = 'aae_import_attachment_map';
	const POSTS_OPTION = 'aae_import_atomic_posts';

	/**
	 * Every `$$type` whose `value` is an attachment id.
	 */
	const ID_PROP_TYPES = [ 'image-attachment-id', 'video-attachment-id' ];

	public static function init(): void {
		add_action( 'aaeaddon/content_import/fresh_start', [ __CLASS__, 'reset' ] );
		add_action( 'wp_import_insert_post', [ __CLASS__, 'track' ], 10, 4 );
		// Priority 9: run before Atomic::enable_used_atomic_after_import() on the
		// same hook. Not required for correctness, but keeps "remap, then enable"
		// the order a reader expects.
		add_action( 'import_end', [ __CLASS__, 'apply' ], 9 );
	}

	/**
	 * A fresh import starts: forget the previous run's map. Fired by
	 * OneClickImport when `use_existing_importer_data()` is false, i.e. NOT on
	 * the continuation requests of a chunked import — `import_start` would be
	 * the obvious hook, and it fires on every chunk.
	 */
	public static function reset(): void {
		delete_option( self::MAP_OPTION );
		delete_option( self::POSTS_OPTION );
	}

	/**
	 * `wp_import_insert_post` — record attachment id pairs and every post that
	 * may carry `_elementor_data` (meta is processed AFTER this action fires, so
	 * the post is only remembered here and read at the end).
	 */
	public static function track( $post_id, $original_id, $postdata, $data ): void {
		$post_id     = (int) $post_id;
		$original_id = (int) $original_id;

		if ( $post_id <= 0 || $original_id <= 0 ) {
			return;
		}

		if ( isset( $postdata['post_type'] ) && 'attachment' === $postdata['post_type'] ) {
			$map                 = self::map();
			$map[ $original_id ] = $post_id;
			update_option( self::MAP_OPTION, $map, false );
			return;
		}

		$posts = get_option( self::POSTS_OPTION, [] );
		$posts = is_array( $posts ) ? $posts : [];
		$posts[ $post_id ] = true;
		update_option( self::POSTS_OPTION, $posts, false );
	}

	/**
	 * `import_end` — rewrite the tracked posts, then clear the batch state.
	 *
	 * @return int Posts whose `_elementor_data` changed.
	 */
	public static function apply(): int {
		$map   = self::map();
		$posts = get_option( self::POSTS_OPTION, [] );
		$changed = 0;

		if ( ! empty( $map ) && is_array( $posts ) ) {
			foreach ( array_keys( $posts ) as $post_id ) {
				if ( self::remap_post( (int) $post_id, $map ) ) {
					$changed++;
				}
			}
		}

		if ( $changed && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			// A page rendered between the WXR insert and this rewrite would have
			// cached CSS/HTML built from the old data.
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		// Only the id map is spent. The post list stays until the next fresh
		// start: Atomic_Kit_Import reads it two steps later to know which posts
		// the design-system rewrite has to cover.
		delete_option( self::MAP_OPTION );

		return $changed;
	}

	/**
	 * Rewrite one post's `_elementor_data` in place.
	 *
	 * @return bool True when something changed and was saved.
	 */
	public static function remap_post( int $post_id, array $map ): bool {
		$raw = get_post_meta( $post_id, '_elementor_data', true );

		if ( ! is_string( $raw ) || '' === $raw ) {
			return false;
		}

		// Cheap gate: nothing atomic references an attachment without one of these.
		if ( false === strpos( $raw, '"image-attachment-id"' ) && false === strpos( $raw, '"video-attachment-id"' ) ) {
			return false;
		}

		$elements = json_decode( $raw, true );

		if ( ! is_array( $elements ) ) {
			return false;
		}

		$rewritten = self::remap_elements( $elements, $map, $count );

		if ( 0 === $count ) {
			return false;
		}

		// wp_slash: update_post_meta unslashes, and _elementor_data is JSON.
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $rewritten ) ) );

		return true;
	}

	/**
	 * Pure walk over decoded element data: every `{ $$type: <ID_PROP_TYPES>,
	 * value: N }` whose N is in the map gets the mapped id. Everything else,
	 * including classic (V3) control values and unmapped ids, is untouched.
	 *
	 * @param int      $count Out: number of ids rewritten.
	 */
	public static function remap_elements( array $node, array $map, ?int &$count = null ): array {
		if ( null === $count ) {
			$count = 0;
		}

		if ( isset( $node['$$type'], $node['value'] )
			&& is_string( $node['$$type'] )
			&& in_array( $node['$$type'], self::ID_PROP_TYPES, true )
			&& is_numeric( $node['value'] )
		) {
			$old = (int) $node['value'];

			if ( isset( $map[ $old ] ) && (int) $map[ $old ] !== $old ) {
				$node['value'] = (int) $map[ $old ];
				$count++;
			}

			return $node;
		}

		foreach ( $node as $key => $value ) {
			if ( is_array( $value ) ) {
				$node[ $key ] = self::remap_elements( $value, $map, $count );
			}
		}

		return $node;
	}

	/**
	 * Every post the last content import inserted (attachments excluded).
	 *
	 * @return int[]
	 */
	public static function imported_posts(): array {
		$posts = get_option( self::POSTS_OPTION, [] );

		return is_array( $posts ) ? array_map( 'intval', array_keys( $posts ) ) : [];
	}

	private static function map(): array {
		$map = get_option( self::MAP_OPTION, [] );

		return is_array( $map ) ? $map : [];
	}
}
