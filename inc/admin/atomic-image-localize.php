<?php
/**
 * Copies url-shaped atomic images into the media library after a V4 import.
 *
 * An atomic image is `id XOR url`. One picked from the demo's media library
 * stores only an attachment id, arrives in the WXR as an attachment, and
 * Atomic_Attachment_Remap points it at the new id. One that was LINKED —
 * pasted as a URL, which is how the demo builders work — stores only the url:
 *
 *   {"$$type":"image-src","value":{"id":null,"url":{"$$type":"url","value":"https://…/image-8.webp"},"alt":null}}
 *
 * Nothing on the import path can touch that: there is no id to remap, and the
 * WXR importer's url_remap only ever sees post_content. The page renders the
 * hot-linked file, and keeps doing so exactly as long as that host serves it.
 * That is what the V3 importer has done for years, so it is not a defect; but
 * a customer may reasonably want the images in THEIR library — to edit, to
 * serve from their own CDN, or simply not to depend on someone else's host.
 *
 * So this is an OPTION the user ticks in the V4 import dialog, off by default.
 * It runs as its own repeating importer step (`localize-images`) after the
 * design system has landed, because it is the one part of the import whose
 * cost is not ours: one download per distinct url, ~100 on the sample demo,
 * against a remote host. Each request does what fits in a time budget, saves
 * its cursor, and hands the same step back to the client, which re-posts
 * until the step reports done. Elementor's own Import_Images does the file
 * handling (sanitised SVG, attachment metadata, the `_elementor_source_image_hash`
 * meta), so a localised image is indistinguishable from one Elementor's
 * template-library import would have created.
 *
 * Two things it is careful about:
 *
 *  - Dedupe BEFORE downloading. Import_Images only consults its hash meta when
 *    the incoming value carries an id, and ours never do; left to it, the same
 *    hero image used on eight pages would be downloaded eight times into eight
 *    attachments. The hash lookup is done here first, plus a per-import cache.
 *  - A url that fails is remembered and never retried within this import. It
 *    stays url-shaped — the page keeps rendering the hot-link, which is the
 *    state the user started from — and is counted in the summary. Without the
 *    memory a single dead url would make the step spin forever, since the
 *    re-scan on every request would find it "pending" again.
 *
 * @package WCF_ADDONS
 */

namespace WCF_ADDONS\Admin\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atomic_Image_Localize {

	const STATE_OPTION = 'aae_import_image_localize';

	/** Seconds of work per request. admin-ajax on a shared host is often capped at 30. */
	const DEFAULT_BUDGET = 18.0;

	/** Seconds allowed for ONE download. wp_safe_remote_get's default is 5, too short for a 2 MB hero on a slow link. */
	const DOWNLOAD_TIMEOUT = 40;

	public static function init(): void {
		add_action( 'aaeaddon/content_import/fresh_start', [ __CLASS__, 'reset' ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	public static function reset(): void {
		delete_option( self::STATE_OPTION );
	}

	/**
	 * Do as much as fits in the budget and report where things stand.
	 *
	 * @return array{done:bool,total:int,processed:int,downloaded:int,reused:int,failed:int,posts:int}
	 */
	public static function run_batch( ?float $budget = null ): array {
		$budget = null === $budget ? (float) apply_filters( 'aae/import/localize_images/budget', self::DEFAULT_BUDGET ) : $budget; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$posts  = class_exists( __NAMESPACE__ . '\Atomic_Attachment_Remap' ) ? Atomic_Attachment_Remap::imported_posts() : [];
		$state  = self::state();

		if ( null === $state['total'] ) {
			// First request of this import: count what there is, so the client
			// can say "12 of 99" instead of a bare spinner. Distinct urls, since
			// that is how many downloads there will be.
			$state['total'] = count( self::pending_urls( $posts ) );
		}

		$deadline = microtime( true ) + $budget;

		while ( $state['cursor'] < count( $posts ) ) {
			$post_id  = (int) $posts[ $state['cursor'] ];
			$finished = self::localize_post( $post_id, $deadline, $state );

			if ( ! $finished ) {
				// Out of time mid-post. The rewrite so far is saved; the re-scan
				// on the next request only sees what is still url-shaped.
				self::save( $state );
				return self::summary( $state, false );
			}

			$state['cursor']++;
			self::save( $state );

			if ( microtime( true ) >= $deadline ) {
				break;
			}
		}

		$done = $state['cursor'] >= count( $posts );

		if ( $done && $state['posts'] && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			// Same reason as the attachment remap: anything rendered between the
			// WXR insert and this rewrite cached markup pointing at the old urls.
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return self::summary( $state, $done );
	}

	/**
	 * Rewrite one post. Returns false when the deadline cut it short.
	 */
	public static function localize_post( int $post_id, float $deadline, array &$state ): bool {
		$raw = get_post_meta( $post_id, '_elementor_data', true );

		if ( ! is_string( $raw ) || '' === $raw || false === strpos( $raw, '"image-src"' ) ) {
			return true;
		}

		$elements = json_decode( $raw, true );

		if ( ! is_array( $elements ) ) {
			return true;
		}

		$stopped = false;
		$changed = 0;
		$tree    = self::walk( $elements, $deadline, $state, $stopped, $changed );

		if ( $changed ) {
			// wp_slash: update_post_meta unslashes, and _elementor_data is JSON.
			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $tree ) ) );
			$state['posts_changed'][ $post_id ] = true;
			$state['posts']                     = count( $state['posts_changed'] );
		}

		return ! $stopped;
	}

	/**
	 * Every image-src node whose value is url-only becomes id-only, in place.
	 */
	private static function walk( array $node, float $deadline, array &$state, bool &$stopped, int &$changed ): array {
		if ( $stopped ) {
			return $node;
		}

		if ( self::is_url_only_image( $node ) ) {
			$url = (string) $node['value']['url']['value'];

			if ( isset( $state['failed'][ $url ] ) ) {
				return $node;
			}

			if ( microtime( true ) >= $deadline ) {
				$stopped = true;
				return $node;
			}

			$id = self::resolve( $url, $state );

			if ( $id > 0 ) {
				$node['value']['id']  = [ '$$type' => 'image-attachment-id', 'value' => $id ];
				$node['value']['url'] = null;
				$changed++;
			}

			return $node;
		}

		foreach ( $node as $key => $value ) {
			if ( is_array( $value ) ) {
				$node[ $key ] = self::walk( $value, $deadline, $state, $stopped, $changed );
			}
		}

		return $node;
	}

	public static function is_url_only_image( $node ): bool {
		return is_array( $node )
			&& isset( $node['$$type'], $node['value'] )
			&& 'image-src' === $node['$$type']
			&& is_array( $node['value'] )
			&& empty( $node['value']['id'] )
			&& isset( $node['value']['url']['value'] )
			&& is_string( $node['value']['url']['value'] )
			&& '' !== $node['value']['url']['value'];
	}

	/**
	 * Attachment id for a url: this import's cache, then the hash meta
	 * Elementor writes on every image it has ever imported, then a download.
	 * Returns 0 on failure and records the url so it is not tried again.
	 */
	private static function resolve( string $url, array &$state ): int {
		if ( isset( $state['cache'][ $url ] ) ) {
			return (int) $state['cache'][ $url ];
		}

		$existing = self::find_by_hash( $url );

		if ( $existing > 0 ) {
			$state['cache'][ $url ] = $existing;
			$state['reused']++;
			$state['processed']++;
			return $existing;
		}

		$id = self::download( $url );

		if ( $id > 0 ) {
			$state['cache'][ $url ] = $id;
			$state['downloaded']++;
		} else {
			$state['failed'][ $url ] = true;
		}

		$state['processed']++;

		return $id;
	}

	private static function find_by_hash( string $url ): int {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT p.ID FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
			 WHERE m.meta_key = '_elementor_source_image_hash' AND m.meta_value = %s AND p.post_type = 'attachment' AND p.post_status = 'inherit'
			 LIMIT 1",
			sha1( $url )
		) );

		return (int) $id;
	}

	private static function download( string $url ): int {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance->templates_manager ) ) {
			return 0;
		}

		$timeout = static function ( $args ) {
			$args['timeout'] = self::DOWNLOAD_TIMEOUT;
			return $args;
		};

		add_filter( 'http_request_args', $timeout, 20 );
		$result = \Elementor\Plugin::$instance->templates_manager->get_import_images_instance()->import( [ 'id' => 0, 'url' => $url ] );
		remove_filter( 'http_request_args', $timeout, 20 );

		// import() hands the ORIGINAL array back for a file whose type WordPress
		// does not recognise — that carries our id 0, so it reads as failure here.
		return ( is_array( $result ) && ! empty( $result['id'] ) ) ? (int) $result['id'] : 0;
	}

	/**
	 * Distinct url-only image urls across the given posts. Used once, for the
	 * total; the batch itself re-scans lazily.
	 */
	public static function pending_urls( array $posts ): array {
		$urls = [];

		foreach ( $posts as $post_id ) {
			$raw = get_post_meta( (int) $post_id, '_elementor_data', true );

			if ( ! is_string( $raw ) || false === strpos( $raw, '"image-src"' ) ) {
				continue;
			}

			$tree = json_decode( $raw, true );

			if ( is_array( $tree ) ) {
				self::collect_urls( $tree, $urls );
			}
		}

		return array_keys( $urls );
	}

	private static function collect_urls( array $node, array &$urls ): void {
		if ( self::is_url_only_image( $node ) ) {
			$urls[ (string) $node['value']['url']['value'] ] = true;
			return;
		}

		foreach ( $node as $value ) {
			if ( is_array( $value ) ) {
				self::collect_urls( $value, $urls );
			}
		}
	}

	public static function describe( array $summary ): string {
		$text = sprintf(
			/* translators: 1: downloaded count, 2: posts updated */
			esc_html__( 'Images copied to the media library: %1$d downloaded, %2$d pages updated', 'animation-addons-for-elementor' ),
			$summary['downloaded'],
			$summary['posts']
		);

		if ( $summary['reused'] ) {
			/* translators: %d: number of images already in the library */
			$text .= sprintf( esc_html__( ', %d already there', 'animation-addons-for-elementor' ), $summary['reused'] );
		}

		if ( $summary['failed'] ) {
			/* translators: %d: number of images that could not be downloaded */
			$text .= sprintf( esc_html__( ', %d could not be downloaded and stay linked', 'animation-addons-for-elementor' ), $summary['failed'] );
		}

		return $text;
	}

	public static function progress_message( array $summary ): string {
		return sprintf(
			/* translators: 1: images done, 2: images total */
			esc_html__( 'Copying images to the media library (%1$d of %2$d)', 'animation-addons-for-elementor' ),
			$summary['processed'],
			$summary['total']
		);
	}

	public static function state(): array {
		$state = get_option( self::STATE_OPTION, [] );
		$state = is_array( $state ) ? $state : [];

		return array_merge(
			[
				'cursor'        => 0,
				'total'         => null,
				'processed'     => 0,
				'downloaded'    => 0,
				'reused'        => 0,
				'failed'        => [],
				'cache'         => [],
				'posts_changed' => [],
				'posts'         => 0,
			],
			$state
		);
	}

	private static function save( array $state ): void {
		update_option( self::STATE_OPTION, $state, false );
	}

	private static function summary( array $state, bool $done ): array {
		return [
			'done'       => $done,
			'total'      => (int) $state['total'],
			'processed'  => (int) $state['processed'],
			'downloaded' => (int) $state['downloaded'],
			'reused'     => (int) $state['reused'],
			'failed'     => count( $state['failed'] ),
			'posts'      => (int) $state['posts'],
		];
	}
}
