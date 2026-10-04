<?php
namespace WCF_ADDONS\Admin\Base;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor V4 starter template: bring the demo's design system (global
 * classes + global variables) and site settings in from a kit zip.
 *
 * The kit zip is what `wp elementor kit export x.zip --include=settings`
 * produces on the demo site. It holds `site-settings.json`, `manifest.json`,
 * `global-variables.json` and `global-classes/<id>.json` + `order.json`.
 * Those are the two things a WXR content import cannot carry: every V4 page
 * references its classes by `g-…` id and its variables by `e-gv-…` id, and both
 * live in the kit post / `e_global_class` posts, outside `_elementor_data`.
 *
 * TWO DIFFERENT PATHS FOR THE TWO HALVES — deliberately:
 *
 *  - Site settings (global colours/fonts, theme style, general settings) go
 *    through Elementor's own `import_kit()`, FIRST IMPORT ONLY, with the
 *    class/variable runners and the theme installer switched off. A second
 *    kit import would `create_new_kit()`, and there is only one Theme Style
 *    per site — later demos inherit the first one's (the second-import dialog
 *    says so).
 *
 *  - Classes and variables NEVER go through the kit runners. Verified against
 *    Elementor 4.2.4: the kit path does not rewrite element class ids, does
 *    not rewrite the variable ids referenced INSIDE classes, and its variables
 *    runner drops the id map for an id-only collision. Every one of those bites
 *    on demos cloned from one base site, where the ids are identical. The
 *    template-library import chain (`elementor/template_library/import/
 *    process_content`) does all three, and it accepts exactly the snapshots the
 *    kit files contain — so the kit files are fed to THAT chain, once, over
 *    every page that arrived with the content import.
 *
 *    First import → `match_site`: an empty site keeps the demo's ids and labels
 *    verbatim; a site with its own classes maps same-label ones onto its own.
 *    Later imports → `keep_create`: every incoming class/variable gets a new id
 *    and, on a label clash, a `DUP_` label — the previous demo's pages are
 *    untouched, the new demo renders exactly as previewed.
 */
class Atomic_Kit_Import {

	const MODE_MATCH_SITE  = 'match_site';
	const MODE_KEEP_CREATE = 'keep_create';

	/**
	 * Post types the content (WXR) import must NOT create, because this class
	 * is the only correct source for them.
	 *
	 * A V4 demo's WXR carries every `e_global_class` post the demo site owns —
	 * measured: 61 of them in the Design Studio export. Elementor never reads a
	 * class post directly; it reaches them ONLY through the active kit's
	 * `_elementor_global_classes_post_ids` map, and that map is written by the
	 * template-library chain this class drives, which creates its OWN class
	 * posts. The WXR copies therefore land as orphans: 61 dead rows per import,
	 * invisible to the editor, never cleaned up, and doubling on every re-import.
	 */
	const WXR_SKIP_TYPES = [ 'e_global_class' ];

	/**
	 * Hook the content-import side. Called once, from the same place
	 * Atomic_Attachment_Remap::init() is.
	 */
	public static function init(): void {
		add_filter( 'wxr_importer.pre_process.post', [ __CLASS__, 'skip_class_posts' ] );
	}

	/**
	 * `wxr_importer.pre_process.post` — returning an empty value skips the item.
	 *
	 * @param mixed $data Post data from the WXR.
	 * @return mixed The data, or an empty array to skip it.
	 */
	public static function skip_class_posts( $data ) {
		if ( is_array( $data ) && isset( $data['post_type'] ) && in_array( $data['post_type'], self::WXR_SKIP_TYPES, true ) ) {
			return [];
		}

		return $data;
	}

	/**
	 * Download a kit zip and import it.
	 *
	 * @param string     $url             Kit zip URL (from the template server).
	 * @param bool       $site_has_atomic Was there V4 content on this site BEFORE this import started?
	 *                                    Must be the value snapshotted at step 1 — by the time this
	 *                                    runs the import's own content is already in the DB.
	 * @param int[]|null $post_ids        Posts whose `_elementor_data` should be rewritten; null =
	 *                                    the posts tracked by the content import that just ran.
	 * @return array|WP_Error Summary on success.
	 */
	public static function import_from_url( string $url, bool $site_has_atomic, ?array $post_ids = null ) {
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$zip = wp_tempnam( 'aae-kit.zip' );

		$url = esc_url_raw( $url );
		if ( ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'aae_kit_invalid_url', 'Invalid kit URL provided.' );
		}

		// Use wp_safe_remote_get to prevent Server-Side Request Forgery (SSRF).
		$response = wp_safe_remote_get( $url, [
			'timeout'  => 120,
			'stream'   => true,
			'filename' => $zip,
		] );

		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || 200 !== $code || ! is_file( $zip ) || 0 === filesize( $zip ) ) {
			wp_delete_file( $zip );

			return is_wp_error( $response )
				? $response
				: new WP_Error( 'aae_kit_download', sprintf( 'Kit zip download failed (HTTP %d): %s', $code, $url ) );
		}

		$result = self::import_from_file( $zip, $site_has_atomic, $post_ids );

		wp_delete_file( $zip );

		return $result;
	}

	/**
	 * @see import_from_url()
	 */
	public static function import_from_file( string $zip, bool $site_has_atomic, ?array $post_ids = null ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance ) {
			return new WP_Error( 'aae_kit_no_elementor', 'Elementor is not loaded.' );
		}

		if ( ! is_readable( $zip ) ) {
			return new WP_Error( 'aae_kit_missing', 'Kit zip is not readable: ' . $zip );
		}

		// Extract OUR copy first: Elementor's Import extracts and then deletes
		// its own working directory, and we need the files after it is done.
		$dir = self::extract( $zip );

		if ( is_wp_error( $dir ) ) {
			return $dir;
		}

		$summary = [
			'mode'          => $site_has_atomic ? self::MODE_KEEP_CREATE : self::MODE_MATCH_SITE,
			'site_settings' => null,
			'classes'       => 0,
			'variables'     => 0,
			'posts'         => 0,
			'errors'        => [],
		];

		if ( ! $site_has_atomic ) {
			$settings = self::import_site_settings( $zip );

			if ( is_wp_error( $settings ) ) {
				// Not fatal: classes are what make the pages render. Record and carry on.
				$summary['errors'][]     = $settings->get_error_message();
				$summary['site_settings'] = false;
			} else {
				$summary['site_settings'] = true;
			}
		}

		$snapshots = self::build_snapshots( $dir );
		self::remove_dir( $dir );

		if ( empty( $snapshots['global_classes'] ) && empty( $snapshots['global_variables'] ) ) {
			$summary['errors'][] = 'Kit contains no global classes or variables.';

			return $summary;
		}

		if ( null === $post_ids ) {
			$post_ids = self::target_posts();
		}

		$applied = self::apply_design_system( $snapshots, $summary['mode'], $post_ids );

		return array_merge( $summary, $applied );
	}

	/**
	 * Starter PAGE: the design system from a per-page Elementor template export.
	 *
	 * A page's template JSON (editor → Save as Template → Export) carries
	 * `global_classes` / `global_variables` snapshots holding ONLY what that page
	 * uses (`extract_used_class_ids_from_elements()`), already in the shape the
	 * chain reads. The page's CONTENT still arrives through the WXR file like
	 * every other starter page — its images are re-pointed by
	 * Atomic_Attachment_Remap — so the JSON's `content` is deliberately ignored
	 * here; only its two snapshots are applied to the posts the WXR import
	 * created.
	 *
	 * Default mode is `keep_create`: the page renders exactly as previewed and
	 * cannot disturb anything already on the site. `match_site` is the
	 * "match my site's design" option.
	 *
	 * @return array|WP_Error Summary on success.
	 */
	public static function import_template_json_from_url( string $url, string $mode = self::MODE_KEEP_CREATE, ?array $post_ids = null ) {
		$url = esc_url_raw( $url );
		if ( ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'aae_tpl_invalid_url', 'Invalid template JSON URL provided.' );
		}

		// Use wp_safe_remote_get to prevent Server-Side Request Forgery (SSRF).
		$response = wp_safe_remote_get( $url, [ 'timeout' => 120 ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'aae_tpl_download', sprintf( 'Template JSON download failed (HTTP %d): %s', (int) wp_remote_retrieve_response_code( $response ), $url ) );
		}

		return self::import_template_json( (string) wp_remote_retrieve_body( $response ), $mode, $post_ids );
	}

	/**
	 * @see import_template_json_from_url()
	 */
	public static function import_template_json( string $json, string $mode = self::MODE_KEEP_CREATE, ?array $post_ids = null ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance ) {
			return new WP_Error( 'aae_kit_no_elementor', 'Elementor is not loaded.' );
		}

		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'aae_tpl_invalid', 'Template JSON is not valid JSON.' );
		}

		$snapshots = [
			'global_classes'   => ( ! empty( $decoded['global_classes']['items'] ) && is_array( $decoded['global_classes'] ) ) ? $decoded['global_classes'] : null,
			'global_variables' => ( ! empty( $decoded['global_variables']['data'] ) && is_array( $decoded['global_variables'] ) ) ? $decoded['global_variables'] : null,
		];

		$mode = in_array( $mode, [ self::MODE_MATCH_SITE, self::MODE_KEEP_CREATE ], true ) ? $mode : self::MODE_KEEP_CREATE;

		$summary = [
			'mode'          => $mode,
			'site_settings' => null,
			'classes'       => 0,
			'variables'     => 0,
			'posts'         => 0,
			'errors'        => [],
		];

		if ( empty( $snapshots['global_classes'] ) && empty( $snapshots['global_variables'] ) ) {
			// A V3 page, or a page that uses no global class: nothing to do, not an error.
			return $summary;
		}

		if ( null === $post_ids ) {
			$post_ids = self::target_posts();
		}

		return array_merge( $summary, self::apply_design_system( $snapshots, $mode, $post_ids ) );
	}

	/**
	 * First import only: site settings through Elementor's own kit importer,
	 * with everything this class handles itself switched off.
	 *
	 * Settings shape verified in `Design_System_Import_Context` /
	 * `Site_Settings::import_with_customization()`: `customization.settings.theme`
	 * empty → no theme install (AAE's own check-theme step owns that);
	 * `classes` / `variables` false → the two design-system runners bail.
	 */
	private static function import_site_settings( string $zip ) {
		$app = \Elementor\Plugin::$instance->app ?? null;

		if ( ! $app || ! method_exists( $app, 'get_component' ) ) {
			return new WP_Error( 'aae_kit_no_app', 'Elementor app is not available in this context.' );
		}

		$module = $app->get_component( 'import-export-customization' );

		if ( ! $module || ! method_exists( $module, 'import_kit' ) ) {
			return new WP_Error( 'aae_kit_no_module', 'Elementor import-export-customization module is not available (manage_options required).' );
		}

		try {
			$result = $module->import_kit( $zip, [
				'include'       => [ 'settings' ],
				'customization' => [
					'settings' => [
						'theme'     => false,
						'classes'   => false,
						'variables' => false,
					],
				],
			] );
		} catch ( \Throwable $e ) {
			return new WP_Error( 'aae_kit_settings_failed', $e->getMessage() );
		}

		return is_array( $result ) ? $result : [];
	}

	/**
	 * Kit files → the two snapshots the template-library chain reads.
	 *
	 *  - `global-variables.json` is `Variables_Collection::serialize()`, i.e.
	 *    `{data, watermark, version}` — the chain wants `{data}`; passed as-is.
	 *  - `global-classes/order.json` is `[{id,label},…]` and each
	 *    `global-classes/<id>.json` is the class item — the chain wants
	 *    `{items: {id => item}, order: [id,…]}`.
	 */
	public static function build_snapshots( string $dir ): array {
		$dir       = rtrim( $dir, '/\\' );
		$snapshots = [ 'global_classes' => null, 'global_variables' => null ];

		$vars_file = $dir . '/global-variables.json';
		if ( is_readable( $vars_file ) ) {
			$vars = json_decode( (string) file_get_contents( $vars_file ), true );
			if ( is_array( $vars ) && ! empty( $vars['data'] ) ) {
				$snapshots['global_variables'] = $vars;
			}
		}

		$order_file = $dir . '/global-classes/order.json';
		if ( is_readable( $order_file ) ) {
			$order = json_decode( (string) file_get_contents( $order_file ), true );
			$items = [];
			$ids   = [];

			foreach ( is_array( $order ) ? $order : [] as $entry ) {
				$id = is_array( $entry ) ? ( $entry['id'] ?? null ) : $entry;
				if ( ! is_string( $id ) || '' === $id || ! preg_match( '/^[a-z0-9-]+$/', $id ) ) {
					continue;
				}
				$file = $dir . '/global-classes/' . $id . '.json';
				if ( ! is_readable( $file ) ) {
					continue;
				}
				$item = json_decode( (string) file_get_contents( $file ), true );
				if ( ! is_array( $item ) || ( $item['id'] ?? null ) !== $id ) {
					continue;
				}
				$items[ $id ] = $item;
				$ids[]        = $id;
			}

			if ( $items ) {
				$snapshots['global_classes'] = [ 'items' => $items, 'order' => $ids ];
			}
		}

		return $snapshots;
	}

	/**
	 * Run Elementor's template-library import chain ONCE over every target
	 * post, then write each post's rewritten elements back.
	 *
	 * Once, not per post: `keep_create` creates a fresh copy of every incoming
	 * class each time it runs, so a per-post loop would create N copies. The
	 * chain walks a plain elements array with `Template_Library_Element_Iterator`
	 * — structure and order are preserved — so the posts' element lists are
	 * concatenated going in and sliced back by count coming out.
	 *
	 * @return array{classes:int, variables:int, posts:int, errors:string[]}
	 */
	public static function apply_design_system( array $snapshots, string $mode, array $post_ids ): array {
		$out = [ 'classes' => 0, 'variables' => 0, 'posts' => 0, 'errors' => [] ];

		$data = [];
		if ( ! empty( $snapshots['global_classes'] ) ) {
			$data['global_classes'] = $snapshots['global_classes'];
		}
		if ( ! empty( $snapshots['global_variables'] ) ) {
			$data['global_variables'] = $snapshots['global_variables'];
		}

		$all    = [];
		$counts = [];

		foreach ( array_values( array_unique( array_map( 'intval', $post_ids ) ) ) as $pid ) {
			if ( $pid <= 0 ) {
				continue;
			}
			$raw = get_post_meta( $pid, '_elementor_data', true );
			if ( ! is_string( $raw ) || '' === $raw ) {
				continue;
			}
			$els = json_decode( $raw, true );
			if ( ! is_array( $els ) ) {
				continue;
			}
			$counts[ $pid ] = count( $els );
			foreach ( $els as $el ) {
				$all[] = $el;
			}
		}

		$source = \Elementor\Plugin::$instance->templates_manager->get_source( 'local' );

		// Counts by before/after diff on the repositories — the two handlers
		// report their work in different shapes, and "how many are on the site
		// now that were not before" is the number a support ticket needs.
		$classes_before   = self::count_classes();
		$variables_before = self::count_variables();

		// Variables (prio 10) → transform_snapshot rewrites the variable ids
		// inside the class snapshot → classes (prio 20) → element ids rewritten.
		$result = apply_filters(
			'elementor/template_library/import/process_content',
			[ 'content' => $all ],
			$mode,
			$data,
			$source
		);

		if ( ! is_array( $result ) || ! isset( $result['content'] ) || ! is_array( $result['content'] ) ) {
			$out['errors'][] = 'process_content returned no content.';

			return $out;
		}

		$out['classes']   = max( 0, self::count_classes() - $classes_before );
		$out['variables'] = max( 0, self::count_variables() - $variables_before );

		if ( count( $result['content'] ) !== count( $all ) ) {
			// Should be impossible — the iterator never drops elements — but a
			// mis-sliced write would put one page's content on another.
			$out['errors'][] = 'process_content changed the element count; posts left untouched.';

			return $out;
		}

		$offset = 0;
		foreach ( $counts as $pid => $n ) {
			$slice = array_slice( $result['content'], $offset, $n );
			$offset += $n;

			$json = wp_json_encode( $slice );
			if ( $json !== wp_json_encode( array_slice( $all, $offset - $n, $n ) ) ) {
				update_post_meta( $pid, '_elementor_data', wp_slash( $json ) );
				$out['posts']++;
			}
		}

		if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return $out;
	}

	/**
	 * The posts the content import just created, as tracked by
	 * Atomic_Attachment_Remap (every inserted post, not only pages — header /
	 * footer templates carry classes too). Falls back to the pages the importer
	 * marked `aae_imported` in its last batch.
	 *
	 * @return int[]
	 */
	public static function target_posts(): array {
		if ( class_exists( Atomic_Attachment_Remap::class ) ) {
			$tracked = Atomic_Attachment_Remap::imported_posts();
			if ( $tracked ) {
				return $tracked;
			}
		}

		$batch = get_option( 'aae_last_import_batch' );
		if ( ! $batch ) {
			return [];
		}

		return array_map( 'intval', get_posts( [
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => 'aae_import_batch',
			'meta_value'     => $batch,
		] ) );
	}

	private static function count_classes(): int {
		if ( ! class_exists( '\Elementor\Modules\GlobalClasses\Global_Classes_Repository' ) ) {
			return 0;
		}

		try {
			return count( \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->set_preview( false )->all_labels() );
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	private static function count_variables(): int {
		if ( ! class_exists( '\Elementor\Modules\Variables\Storage\Variables_Repository' ) ) {
			return 0;
		}

		try {
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
			$all = ( new \Elementor\Modules\Variables\Storage\Variables_Repository( $kit ) )->load()->serialize()['data'] ?? [];
			$n   = 0;
			foreach ( $all as $v ) {
				if ( empty( $v['deleted'] ) ) {
					$n++;
				}
			}

			return $n;
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	private static function extract( string $zip ) {
		if ( ! function_exists( 'unzip_file' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}

		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . 'aae-kit-' . wp_generate_password( 10, false );

		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'aae_kit_mkdir', 'Cannot create temp directory: ' . $dir );
		}

		$unzipped = unzip_file( $zip, $dir );

		if ( is_wp_error( $unzipped ) ) {
			self::remove_dir( $dir );

			return $unzipped;
		}

		return $dir;
	}

	private static function remove_dir( string $dir ): void {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			WP_Filesystem();
		}

		// extract() initialised this same filesystem before unzip_file(); if
		// that failed nothing was extracted, so there is nothing to remove.
		if ( ! $wp_filesystem || ! $wp_filesystem->is_dir( $dir ) ) {
			return;
		}

		$wp_filesystem->rmdir( $dir, true );
	}
}
