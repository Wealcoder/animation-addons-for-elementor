<?php
/**
 * Atomic_Admin -- the wp-admin, editor, admin-ajax and import half of Atomic.
 *
 * Split out of class-atomic.php, which was 241.9 KB parsed on EVERY request --
 * a visitor page view, a REST call, a cron tick -- to hold code that only ever
 * runs behind wp-admin, inside the Elementor editor, in an admin-ajax handler
 * or during a content import. None of it is reachable from a front-end render.
 *
 * Nothing here is loaded by a gate. Every hook stays registered from
 * Atomic::init_hooks() at exactly the priority and in exactly the order it
 * always was, but as a class-STRING callable -- [ Atomic_Admin::class, 'x' ]
 * -- which PHP does not resolve, and therefore does not autoload, until that
 * hook actually fires. That is why the methods here are static, and it is what
 * makes the split safe for the paths an is_admin() gate would have broken:
 * `import_end` fires under WP-CLI, where is_admin() is false, and the
 * import-time data-loss guard has to run there.
 *
 * The class is PSR-4 named on purpose, so a class-string callable resolves
 * with no require anywhere.
 *
 * Shared state stays on Atomic and is reached through its public API:
 * Atomic::instance()->... for the instance helpers, Atomic::... for the static
 * ones, and Atomic::instance()->flush_active_caches() for the four memo caches
 * this code used to reset by touching the properties directly.
 *
 * @package Wealcoder\AnimationAddons
 * @since   4.2.0
 */

namespace Wealcoder\AnimationAddons\AtomicWidgets;

use Wealcoder\AnimationAddons\Nonce;

defined( 'ABSPATH' ) || exit;

final class Atomic_Admin
{

	/**
	 * Get the full config array to pass to the React dashboard.
	 *
	 * Structure mirrors the existing `wcf_addons_dashboard_config` format
	 * so the same React component tree can render it.
	 *
	 * @return array
	 */
	/**
	 * Group every internal child under the widget it belongs to.
	 *
	 * WIDGET_PARENT_MAP already records the relationship — this inverts it into
	 * the shape the dashboard renders: parent slug => list of its parts, each
	 * with the label/icon needed to display it. Children are read-only here;
	 * they have no toggle of their own and follow the parent's state (see
	 * is_widget_active()).
	 *
	 * Chains are followed to the top so a grandchild is listed under the
	 * widget the user can actually switch off, not under an intermediate part
	 * that never appears in the dashboard.
	 *
	 * @return array<string, array<int, array{slug:string,label:string,icon:string}>>
	 */
	public static function get_widget_parts(): array
	{
		$parts = [];

		$parent_map = Atomic::instance()->widget_parent_map();

		foreach ($parent_map as $child => $parent) {
			// Walk up to the toggleable ancestor. Guarded against a cycle so a
			// bad map entry can never hang the dashboard request.
			$seen = [];
			while (isset($parent_map[$parent]) && ! isset($seen[$parent])) {
				$seen[$parent] = true;
				$parent        = $parent_map[$parent];
			}

			$def = Atomic::instance()->get_widgets_registry()[$child] ?? null;

			if (null === $def) {
				continue;
			}

			$parent_label = Atomic::instance()->get_widgets_registry()[$parent]['label'] ?? '';

			$parts[$parent][] = [
				'slug'  => $child,
				'label' => self::part_label($def['label'] ?? $child, $parent_label),
				'icon'  => $def['icon'] ?? '',
			];
		}

		foreach ($parts as &$list) {
			usort($list, static function ($a, $b) {
				return strcasecmp($a['label'], $b['label']);
			});
		}
		unset($list);

		return $parts;
	}

	/**
	 * Tidy an internal widget's label for display inside its parent's group.
	 *
	 * These labels were never user-facing before — they exist so developers can
	 * identify a slug — so they carry bookkeeping the dashboard should not show:
	 * an "(Internal)" marker, and the parent's own name repeated as a prefix
	 * ("Flip Box Back", "Countdown — Unit"). The group is already titled with the
	 * parent, so the prefix is noise. Falls back to the original whenever
	 * stripping would leave nothing.
	 *
	 * @param string $label        Raw registry label.
	 * @param string $parent_label Label of the widget this part belongs to.
	 *
	 * @return string
	 */
	public static function part_label(string $label, string $parent_label): string
	{
		$clean = trim(preg_replace('/\s*\((?:internal)\)\s*$/i', '', $label));

		if ('' !== $parent_label) {
			// "Countdown — Unit" / "Flip Box Back" -> "Unit" / "Back".
			$pattern = '/^' . preg_quote($parent_label, '/') . '\s*(?:—|-|–|:)?\s+/i';
			$trimmed = trim(preg_replace($pattern, '', $clean));

			if ('' !== $trimmed) {
				$clean = $trimmed;
			}
		}

		return '' !== $clean ? $clean : $label;
	}

	public static function get_dashboard_config(): array
	{
		$saved   = Atomic::instance()->get_saved_options();
		$widgets = [];
		$parts   = self::get_widget_parts();

		foreach (Atomic::instance()->get_widgets_registry() as $slug => $def) {
			// Sub-elements of a composite widget (e.g. Flip Box's own
			// Front/Back/Title/Text) are never individually toggleable —
			// keep them out of the dashboard list entirely. They are not
			// dropped from the payload though: each one is attached to its
			// parent below as a read-only `parts` entry, so the dashboard can
			// show what a widget contains without offering a switch for it.
			if (! empty($def['is_internal'])) {
				continue;
			}

			$widgets[$slug] = array_merge($def, [
				'is_active'   => isset($saved[$slug]),
				'parts'       => $parts[$slug] ?? [],
				'parts_count' => isset($parts[$slug]) ? count($parts[$slug]) : 0,
			]);
		}

		$ext_saved    = Atomic::instance()->get_saved_extension_options();
		$extensions   = [];

		foreach (Atomic::instance()->extensions_registry() as $slug => $def) {
			$extensions[$slug] = array_merge($def, [
				'is_active' => isset($ext_saved[$slug]),
			]);
		}

		return [
			'atomic_widgets' => [
				'title'    => 'Atomic Widgets',
				'elements' => $widgets,
			],
			'atomic_extensions' => [
				'title'    => 'Atomic Extensions',
				'elements' => $extensions,
			],
		];
	}

	/**
	 * Report drift between the two widget registries. WP_DEBUG only.
	 *
	 * Widget data is split across two hand-maintained arrays that must agree:
	 * get_available_widgets() (class / file / asset handles — what registers with
	 * Elementor) and widgets_registry (dashboard metadata — what can be toggled).
	 * Nothing enforced that agreement, and three separate defects had accumulated
	 * silently:
	 *
	 *   - 'aae-a-button', 'aae-a-button-pro', 'aae-a-image-compare-main' had
	 *     metadata but no class, so the dashboard rendered duplicate cards whose
	 *     toggles controlled nothing.
	 *   - 'aae-a-menu' had a class but no metadata, so it could never be enabled.
	 *
	 * None of that surfaces at runtime — a missing entry just makes a widget
	 * quietly unreachable — so it can persist for releases. This turns it into an
	 * immediate, visible failure while developing.
	 *
	 * Children routed through WIDGET_PARENT_MAP are expected to have no metadata:
	 * they are grouped under their parent rather than listed, which is why they
	 * are excluded here.
	 */
	public static function assert_registry_integrity(): void
	{
		if (! defined('WP_DEBUG') || ! WP_DEBUG) {
			return;
		}

		$available = array_keys(Atomic::instance()->get_available_widgets());
		$metadata  = array_keys(Atomic::instance()->get_widgets_registry());

		// Registered, but nothing in the dashboard can ever switch it on.
		$missing_metadata = array_diff(
			$available,
			$metadata,
			array_keys(Atomic::instance()->widget_parent_map()),
			Atomic::PARKED_WIDGETS
		);

		// A dashboard toggle for a widget that cannot load.
		$missing_class = array_diff($metadata, $available);

		// An internal child with no parent. Once the forced-active list is gone
		// these fall through to the saved-option lookup, which they can never
		// satisfy (they have no toggle), so they would never register and the
		// editor would throw ElementTypeNotFound on any page already using one.
		$internal = [];
		foreach (Atomic::instance()->get_widgets_registry() as $slug => $def) {
			if (! empty($def['is_internal'])) {
				$internal[] = $slug;
			}
		}

		$orphan_children = array_diff(
			$internal,
			array_keys(Atomic::instance()->widget_parent_map()),
			Atomic::instance()->always_active_widgets()
		);

		// A parent that no longer exists — the children would inherit from a
		// slug that is never active, silently disabling the whole family.
		$dangling_parents = array_diff(
			array_unique(array_values(Atomic::instance()->widget_parent_map())),
			$metadata
		);

		if ($missing_metadata) {
			wp_trigger_error(
				__METHOD__,
				'AAE atomic registry: registered widget(s) with no dashboard metadata — unreachable: '
				. implode(', ', $missing_metadata)
			);
		}

		if ($orphan_children) {
			wp_trigger_error(
				__METHOD__,
				'AAE atomic registry: internal widget(s) with no WIDGET_PARENT_MAP parent — cannot inherit: '
				. implode(', ', $orphan_children)
			);
		}

		if ($dangling_parents) {
			wp_trigger_error(
				__METHOD__,
				'AAE atomic registry: WIDGET_PARENT_MAP points at unknown parent(s): '
				. implode(', ', $dangling_parents)
			);
		}

		if ($missing_class) {
			wp_trigger_error(
				__METHOD__,
				'AAE atomic registry: dashboard metadata with no class/file — toggle does nothing: '
				. implode(', ', $missing_class)
			);
		}
	}

	/* =====================================================================
	 *  Dashboard integration
	 * =================================================================== */

	/**
	 * Inject atomic widgets config into the dashboard localize data.
	 *
	 * @param array $configs Existing dashboard config.
	 *
	 * @return array
	 */
	public static function inject_dashboard_config(array $configs): array
	{
		$dashboard = self::get_dashboard_config();

		$configs['atomic_widgets']    = $dashboard['atomic_widgets'];
		$configs['atomic_extensions'] = $dashboard['atomic_extensions'];

		// Whether this site has moved to Elementor V4, and what the user has
		// already said about it. Read by lib/systemVisibility.js — which is the
		// ONLY place allowed to turn any of it into a visibility rule.
		$configs['atomic_optin'] = self::atomic_optin_signal();

		// The return path out of an accidental "Enable" — see the block above
		// capture_atomic_undo(). `available => false` most of the time.
		$configs['atomic_undo'] = self::atomic_undo_offer();

		// Can a V4 (atomic) starter template or page be imported here at all?
		// Read by the starter-template grids in BOTH modules (dashboard and
		// page-import share this filter) to badge V4 cards and to disable their
		// Import button. `in_use` is deliberately NOT shipped here -- it is asked
		// fresh at click time (ajax_atomic_import_status), because a payload
		// snapshot goes stale the moment the first V4 import lands.
		$configs['atomic_import'] = [ 'available' => Atomic::is_elementor_atomic_active() ];

		return $configs;
	}

	/**
	 * AJAX handler — save atomic widget toggle states.
	 */
	public static function ajax_save_settings(): void
	{
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['fields'])) {
			wp_send_json_error(esc_html__('Missing fields.', 'animation-addons-for-elementor'));
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decode_slug_map() runs sanitize_text_field() on the raw value before json_decode(), and write_widget_option()/write_extension_option() sanitise every key and value again before storing. The handler has already checked the nonce and the capability.
		$settings = self::decode_slug_map(wp_unslash($_POST['fields']));

		if (null === $settings) {
			wp_send_json_error(esc_html__('Invalid data.', 'animation-addons-for-elementor'));
		}

		$result = self::write_widget_option($settings);

		// A hand save is the user's own choice, and it ENDS the undo offer:
		// restoring a snapshot over a list somebody has since curated would
		// destroy real work, which is the one thing an undo must never do.
		self::forget_atomic_undo();

		wp_send_json($result);
	}

	/**
	 * Decode a `{ slug: bool }` map posted by the dashboard.
	 *
	 * @param string $raw Raw, already-unslashed request value.
	 *
	 * @return array|null Null when the payload is not a map.
	 */
	public static function decode_slug_map($raw): ?array
	{
		$decoded = json_decode(sanitize_text_field((string) $raw), true);

		return is_array($decoded) ? $decoded : null;
	}

	/**
	 * Write the atomic WIDGET option, sanitised, and drop the derived caches.
	 *
	 * Extracted from ajax_save_settings() so the opt-in notice's "Enable" can
	 * reuse it in the SAME request as its undo snapshot. Going back through the
	 * AJAX handler would mean the snapshot and the write land in two separate
	 * requests, with the handler's own forget_atomic_undo() deleting the
	 * snapshot the notice had just taken.
	 *
	 * @param array $settings Raw slug => state map.
	 *
	 * @return array{status:bool,total:int}
	 */
	public static function write_widget_option(array $settings): array
	{
		// Build a clean associative array: slug => true for enabled.
		$clean = [];
		foreach ($settings as $slug => $state) {
			$slug = sanitize_key($slug);

			if (isset(Atomic::instance()->get_widgets_registry()[$slug]) && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		$updated = \Wealcoder\AnimationAddons\Compat\Key_Bridge::update_option(Atomic::OPTION_NAME, $clean);

		// Reset cache.
		Atomic::instance()->flush_active_caches( 'widgets' );
		// Both are derived from the active set.
		Atomic::instance()->flush_active_caches( 'widgets' );

		return [
			'status' => $updated,
			'total'  => count($clean),
		];
	}

	/**
	 * AJAX handler — retrieve current atomic widget settings.
	 */
	public static function ajax_get_settings(): void
	{
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		wp_send_json([
			'settings' => Atomic::instance()->get_saved_options(),
			'config'   => self::get_dashboard_config(),
		]);
	}

	/**
	 * AJAX handler — fetch WP Menu HTML for the Elementor Editor (since Atomic JS render lacks it).
	 */
	public static function ajax_get_menu_html(): void
	{
		// Editor-preview only: on the frontend get_atomic_settings() fills
		// `rendered_menu` server-side, so menu.js never reaches this. The
		// nonce rides AAE_MENU_CFG next to the ajax URL, and the action is
		// the same one every other editor-only endpoint in this class uses.
		check_ajax_referer( Nonce::action( Nonce::LOOP_GRID, 'nonce' ), 'nonce' );

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		$menu = isset($_GET['menu']) ? sanitize_text_field(wp_unslash($_GET['menu'])) : '';

		if (empty($menu)) {
			wp_send_json_error(esc_html__('No menu slug provided.', 'animation-addons-for-elementor'));
		}

		$args = [
			'menu' => $menu,
			'menu_class' => 'aae-a-menu-list',
			'container' => false,
			'echo' => false,
			'fallback_cb' => false,
		];

		wp_send_json_success(wp_nav_menu($args));
	}

	/**
	 * AJAX handler — return every registered WordPress menu together with its
	 * items pre-assembled into a nested tree. The AAE Nav panel's import control
	 * consumes this to build atomic nav-items (with dropdowns) that mirror the
	 * WP menu hierarchy. Reuses the `Nonce::LOOP_GRID` editor nonce.
	 */
	public static function ajax_get_nav_menus(): void
	{
		check_ajax_referer( Nonce::action( Nonce::LOOP_GRID, 'nonce' ), 'nonce' );

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$menus = wp_get_nav_menus();
		$out   = [];

		if (! is_wp_error($menus)) {
			foreach ($menus as $menu) {
				$items = wp_get_nav_menu_items($menu->term_id);
				$out[] = [
					'id'    => (int) $menu->term_id,
					'name'  => $menu->name,
					'items' => Atomic::build_nav_menu_tree(is_array($items) ? $items : []),
				];
			}
		}

		wp_send_json_success(['menus' => $out]);
	}

	/**
	 * AJAX handler — save atomic extension toggle states.
	 */
	public static function ajax_save_extension_settings(): void
	{
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['fields'])) {
			wp_send_json_error(esc_html__('Missing fields.', 'animation-addons-for-elementor'));
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decode_slug_map() runs sanitize_text_field() on the raw value before json_decode(), and write_widget_option()/write_extension_option() sanitise every key and value again before storing. The handler has already checked the nonce and the capability.
		$settings = self::decode_slug_map(wp_unslash($_POST['fields']));

		if (null === $settings) {
			wp_send_json_error(esc_html__('Invalid data.', 'animation-addons-for-elementor'));
		}

		$result = self::write_extension_option($settings);

		// See the twin comment in ajax_save_settings().
		self::forget_atomic_undo();

		wp_send_json($result);
	}

	/**
	 * Write the atomic EXTENSION option, sanitised, plus the "offered" baseline.
	 *
	 * Extracted from ajax_save_extension_settings() for the same reason as
	 * write_widget_option() — see its docblock.
	 *
	 * @param array $settings Raw slug => state map.
	 *
	 * @return array{status:bool,total:int}
	 */
	public static function write_extension_option(array $settings): array
	{
		$clean = [];
		foreach ($settings as $slug => $state) {
			$slug = sanitize_key($slug);

			if (isset(Atomic::instance()->extensions_registry()[$slug]) && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		$updated = \Wealcoder\AnimationAddons\Compat\Key_Bridge::update_option(Atomic::EXTENSIONS_OPTION_NAME, $clean);

		// Record what the user was just shown. Without this the setup wizard's
		// choice is silently overruled a moment later: migrate_newly_offered_extensions()
		// bails only while the settings option is ABSENT ("fresh install, the
		// wizard decides"), and the wizard's own save is what ends that. On the
		// next admin_init the offered list is still missing, so it falls back to
		// LEGACY_OFFERED_EXTENSIONS and every extension added since that
		// baseline counts as newly-offered — switching six Pro extensions back
		// on right after someone picked the Basic setup.
		//
		// Writing it here makes "offered" mean what it says: the set the user
		// has actually been presented with. The migration then correctly does
		// nothing until a future plugin update adds an extension neither this
		// save nor the wizard ever displayed.
		update_option(Atomic::EXTENSIONS_OFFERED_OPTION_NAME, array_keys(Atomic::instance()->extensions_registry()));

		// Reset cache.
		Atomic::instance()->flush_active_caches( 'extensions' );

		return [
			'status' => $updated,
			'total'  => count($clean),
		];
	}

	/**
	 * AJAX handler — retrieve current atomic extension settings.
	 */
	public static function ajax_get_extension_settings(): void
	{
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		wp_send_json([
			'settings' => Atomic::instance()->get_saved_extension_options(),
			'config'   => self::get_dashboard_config(),
		]);
	}

	/**
	 * Switch on every atomic widget and extension the site's CONTENT already uses.
	 *
	 * The V4 twin of Animation_Settings::maybe_enable_used_v3_widgets(), and it
	 * exists for the same reason: an unregistered widget renders NOTHING — no
	 * error, no wrapper — so a starter template built from `e-aae-a-*` elements
	 * imports into a site where nothing has switched them on and every page
	 * comes up blank, looking exactly like a missing-class problem. Nothing on
	 * the import path wrote `aaeaddon_atomic_widgets` before this; only the dashboard
	 * save handlers and the opt-in flow did.
	 *
	 * Two differences from the v3 guard, both deliberate:
	 *
	 * 1. It MERGES into the saved option and only ever switches ON. The v3 guard
	 *    bails once `aaeaddon_save_widgets` has been written by hand; here an
	 *    explicit switch-off is still honoured for everything the imported
	 *    content does not use, but the widgets it DOES use come on — importing
	 *    a demo is the user asking for those pages to render.
	 * 2. It matches `elType` as well as `widgetType`. AAE's slider, counter,
	 *    button and every offcanvas part save as their OWN elType — atomic
	 *    container-style elements are not "widgets" in the data at all — so a
	 *    `widgetType`-only scan enables the leaf widgets and leaves every
	 *    container blank. Same finding as Pro's usage scanner.
	 *
	 * Internal children (`WIDGET_PARENT_MAP`) resolve to their parent, because
	 * that is the card the dashboard shows and `is_widget_active()` inherits
	 * through it. Extensions leave no name in the data, only a prop inside some
	 * other element's settings, so they are detected through the registry's
	 * own `usage_prop` declarations — the same rule Pro's usage counter uses —
	 * on the DECODED data, not by regex.
	 *
	 * @param int[]|null $post_ids Restrict the scan to these posts; null = whole site.
	 * @return array{widgets: string[], extensions: string[]} What was newly switched on.
	 */
	public static function enable_used_atomic( ?array $post_ids = null ): array
	{
		global $wpdb;

		// Two families, two markers: our elements carry `e-aae-a-`, our
		// extension props carry `aae_` — an extension can sit on a core
		// e-heading with no AAE element anywhere on the page.
		$like = [
			'%' . $wpdb->esc_like( '"e-aae-a-' ) . '%',
			'%' . $wpdb->esc_like( '"aae_' ) . '%',
		];

		// One complete literal per branch rather than a base string appended to.
		// Appending is what puts a variable into the query TEXT; written out this
		// way the only thing interpolated is a run of %d generated from count().
		if ( null === $post_ids ) {
			$sql  = "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND ( meta_value LIKE %s OR meta_value LIKE %s )";
			$args = $like;
		} else {
			$post_ids = array_values( array_filter( array_map( 'intval', $post_ids ) ) );
			if ( empty( $post_ids ) ) {
				return [ 'widgets' => [], 'extensions' => [] ];
			}
			// One %d per id; the ids themselves travel as prepare() arguments.
			$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
			$sql          = "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND ( meta_value LIKE %s OR meta_value LIKE %s ) AND post_id IN ({$placeholders})";
			$args         = array_merge( $like, $post_ids );
		}

		// $sql is whichever of the two literals above the branch chose; the only
		// variable part of it is the generated run of %d.
		$rows = $wpdb->get_col( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one %d per id; the sniff cannot count through array_merge(). A content scan no WP API answers, run once at import_end for the posts that arrived.

		if ( empty( $rows ) ) {
			return [ 'widgets' => [], 'extensions' => [] ];
		}

		$registry      = Atomic::instance()->get_widgets_registry();
		$parents       = Atomic::instance()->widget_parent_map();
		$always_active = Atomic::instance()->always_active_lookup();
		$saved_widgets = Atomic::instance()->get_saved_options();
		$saved_exts    = Atomic::instance()->get_saved_extension_options();

		// prop => [ [ slug, kind ], ... ] — one prop can belong to several extensions.
		$prop_index = [];
		foreach ( Atomic::instance()->get_extension_usage_props() as $slug => $def ) {
			foreach ( $def['prop'] as $prop ) {
				$prop_index[ $prop ][] = [ $slug, $def['kind'] ];
			}
		}

		$new_widgets = [];
		$new_exts    = [];

		foreach ( $rows as $row ) {
			$row = (string) $row;

			if ( preg_match_all( '/"(?:widgetType|elType)":"e-([a-z0-9-]+)"/', $row, $m ) ) {
				foreach ( $m[1] as $slug ) {
					if ( isset( $parents[ $slug ] ) ) {
						$slug = $parents[ $slug ];
					}
					if ( ! isset( $registry[ $slug ] ) || isset( $always_active[ $slug ] ) || isset( $saved_widgets[ $slug ] ) ) {
						continue;
					}
					$new_widgets[ $slug ] = true;
				}
			}

			if ( ! empty( $prop_index ) && false !== strpos( $row, '"aae_' ) ) {
				$decoded = json_decode( $row, true );
				if ( is_array( $decoded ) ) {
					self::collect_used_extensions( $decoded, $prop_index, $saved_exts, $new_exts );
				}
			}
		}

		if ( ! empty( $new_widgets ) ) {
			self::write_widget_option( $saved_widgets + $new_widgets );
		}

		if ( ! empty( $new_exts ) ) {
			self::write_extension_option( $saved_exts + $new_exts );
		}

		return [
			'widgets'    => array_keys( $new_widgets ),
			'extensions' => array_keys( $new_exts ),
		];
	}

	/**
	 * Hook wrapper — whole-site scan after a content import.
	 */
	public static function enable_used_atomic_after_import(): void
	{
		self::enable_used_atomic( null );
	}

	/**
	 * Walk decoded element data and mark every extension whose usage prop
	 * qualifies. Same three kinds as Pro's Widget_Usage::prop_qualifies():
	 * `present` (the key exists), `boolean` (value === true) and `filled`
	 * (anything with content — an interactions list, a regex string).
	 * Present is not enabled: a switched-off section leaves its prop behind
	 * with `"value":false`, so the value has to be looked at.
	 */
	public static function collect_used_extensions( array $node, array $prop_index, array $saved, array &$found ): void
	{
		foreach ( $node as $key => $value ) {
			if ( is_string( $key ) && isset( $prop_index[ $key ] ) ) {
				$inner = is_array( $value ) && array_key_exists( 'value', $value ) ? $value['value'] : $value;

				foreach ( $prop_index[ $key ] as [ $slug, $kind ] ) {
					if ( isset( $saved[ $slug ] ) || isset( $found[ $slug ] ) ) {
						continue;
					}
					if ( 'present' === $kind
						|| ( 'boolean' === $kind && true === $inner )
						|| ( 'boolean' !== $kind && self::value_has_content( $inner ) ) ) {
						$found[ $slug ] = true;
					}
				}
			}

			if ( is_array( $value ) ) {
				self::collect_used_extensions( $value, $prop_index, $saved, $found );
			}
		}
	}

	public static function value_has_content( $value ): bool
	{
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( self::value_has_content( $item ) ) {
					return true;
				}
			}
			return false;
		}

		return ! ( null === $value || '' === $value || false === $value || 0 === $value );
	}

	/* =====================================================================
	 *  The return path
	 *
	 *  "Enable atomic features" switches on everything this site can register
	 *  in one click. That is the right shape for the offer — and it is exactly
	 *  the shape that needs a way back, because a click that changes a hundred
	 *  things is a click somebody can make by mistake.
	 *
	 *  Getting back is not the same as switching everything off. The site had a
	 *  STATE before the click — usually no rows at all, sometimes a half-built
	 *  one from an abandoned wizard run — and only restoring that exact state
	 *  puts the user where they were. So the enable snapshots first, and the
	 *  undo restores; nothing here computes what the state "should" be.
	 *
	 *  Note what did NOT need saving: the v3 options. Accepting the offer never
	 *  touched them, so an accidental accept never cost the user their V3 site
	 *  in the first place — which is why this is an undo of one option pair
	 *  rather than a migration rollback.
	 * =================================================================== */

	/**
	 * Record the atomic state as it is right now, before the offer writes over it.
	 *
	 * Stores the "offered" baseline too: write_extension_option() rewrites it,
	 * and leaving the new value behind after an undo would change what
	 * migrate_newly_offered_extensions() does on the next admin_init — a
	 * difference nobody would connect to a button they pressed a week ago.
	 *
	 * @param string $signal Signal strength at the moment of the accept.
	 */
	public static function capture_atomic_undo(string $signal): void
	{
		$missing = '__aae_option_absent__';

		$widgets    = get_option(Atomic::OPTION_NAME, $missing);
		$extensions = get_option(Atomic::EXTENSIONS_OPTION_NAME, $missing);
		$offered    = get_option(Atomic::EXTENSIONS_OFFERED_OPTION_NAME, $missing);

		update_option(
			Atomic::UNDO_OPTION_NAME,
			[
				'widgets'           => $missing === $widgets ? [] : $widgets,
				'widgets_absent'    => $missing === $widgets,
				'extensions'        => $missing === $extensions ? [] : $extensions,
				'extensions_absent' => $missing === $extensions,
				'offered'           => $missing === $offered ? [] : $offered,
				'offered_absent'    => $missing === $offered,
				'signal'            => $signal,
				'time'              => time(),
			],
			false
		);
	}

	/**
	 * Put the three options back exactly as capture_atomic_undo() found them.
	 *
	 * delete_option() where the row was absent — see UNDO_OPTION_NAME's docblock
	 * for why an empty array is a different site state, not a tidier one.
	 *
	 * @param array $undo A stored snapshot.
	 */
	public static function restore_atomic_undo(array $undo): void
	{
		$map = [
			Atomic::OPTION_NAME                    => ['widgets', 'widgets_absent'],
			Atomic::EXTENSIONS_OPTION_NAME         => ['extensions', 'extensions_absent'],
			Atomic::EXTENSIONS_OFFERED_OPTION_NAME => ['offered', 'offered_absent'],
		];

		foreach ($map as $name => $keys) {
			list($value_key, $absent_key) = $keys;

			if (! empty($undo[$absent_key])) {
				\Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $name );

				continue;
			}

			$value = is_array($undo[$value_key] ?? null) ? $undo[$value_key] : [];

			// SANITISED ON THE WAY BACK IN, even though we wrote the snapshot
			// ourselves. What it captured is whatever the option held BEFORE the
			// opt-in, which is not necessarily something this plugin wrote — an
			// import, an older release, or another plugin could have left any shape
			// there, and restoring it verbatim would put it back unchecked.
			//
			// Not a privilege boundary (writing that option already needs database
			// access) so much as refusing to be the path that launders unvalidated
			// data into a live option. Anything the two writers produced passes
			// through unchanged, so the exact-restore promise is intact.
			$value = Atomic::EXTENSIONS_OFFERED_OPTION_NAME === $name
				? self::sanitize_slug_list($value)
				: self::sanitize_slug_map($value);

			update_option($name, $value);
		}

		// Every derived cache the two writers reset, reset again — this wrote
		// the same options they do.
		Atomic::instance()->flush_active_caches( 'all' );
	}

	/**
	 * End the undo offer. Called on an accepted "keep", on a completed undo, and
	 * on any hand save of either atomic list.
	 */
	/**
	 * `slug => true` for every key that survives sanitize_key().
	 *
	 * The shape both atomic settings options are stored in. Values are discarded
	 * rather than preserved: the writers only ever store `true`, so anything else
	 * arrived from outside and "on" is the only state the readers understand.
	 *
	 * @param array $value Raw map.
	 *
	 * @return array
	 */
	public static function sanitize_slug_map(array $value): array
	{
		$clean = [];

		foreach ($value as $slug => $state) {
			$slug = sanitize_key($slug);

			if ('' !== $slug && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		return $clean;
	}

	/**
	 * A LIST of slugs — the shape EXTENSIONS_OFFERED_OPTION_NAME uses.
	 *
	 * Deliberately separate from sanitize_slug_map(): that option is written as
	 * `array_keys( $registry )`, so running it through the map sanitiser would
	 * turn a list into `0 => true, 1 => true` and quietly destroy the record of
	 * what the user has been shown — which is the one thing standing between
	 * migrate_newly_offered_extensions() and switching extensions back on by
	 * itself.
	 *
	 * @param array $value Raw list.
	 *
	 * @return array
	 */
	public static function sanitize_slug_list(array $value): array
	{
		$clean = [];

		foreach ($value as $slug) {
			if (! is_scalar($slug)) {
				continue;
			}

			$slug = sanitize_key((string) $slug);

			if ('' !== $slug) {
				$clean[] = $slug;
			}
		}

		return array_values(array_unique($clean));
	}

	public static function forget_atomic_undo(): void
	{
		\Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( Atomic::UNDO_OPTION_NAME );
	}

	/**
	 * The undo offer, for the dashboard payload.
	 *
	 * Reports `available => false` rather than omitting itself, so the React
	 * side reads one shape whatever the state.
	 *
	 * @return array{available:bool,widgets?:int,extensions?:int,expires?:int}
	 */
	public static function atomic_undo_offer(): array
	{
		$undo = get_option(Atomic::UNDO_OPTION_NAME);

		if (! is_array($undo) || empty($undo['time'])) {
			return ['available' => false];
		}

		if ((time() - (int) $undo['time']) > Atomic::UNDO_WINDOW) {
			return ['available' => false];
		}

		$counts = Atomic::instance()->count_active_atomic();

		// A snapshot with NOTHING switched on is not an undo, it is a leftover.
		// The enable takes its snapshot before the first write, so a request that
		// dies in between (closed tab, PHP timeout) leaves one behind describing
		// a state nothing ever moved away from. Offering it would put a bar on
		// screen reading "0 atomic widgets and 0 atomic extensions are active",
		// whose Undo button restores what is already there.
		if (0 === $counts['widgets'] && 0 === $counts['extensions']) {
			return ['available' => false];
		}

		return [
			'available'  => true,
			'widgets'    => $counts['widgets'],
			'extensions' => $counts['extensions'],
			'expires'    => (int) $undo['time'] + Atomic::UNDO_WINDOW,
		];
	}

	/**
	 * Rank a signal so a past dismissal can be compared against today's
	 * evidence. usage > experiment > none.
	 */
	public static function signal_rank(string $signal): int
	{
		$ranks = [
			'none'       => 0,
			'experiment' => 1,
			'usage'      => 2,
		];

		return $ranks[$signal] ?? 0;
	}

	/**
	 * The strongest evidence available that this site is on Elementor V4.
	 *
	 * Reads the CONTENT check first because it is the stronger claim, and the
	 * two are not nested: a site can hold V4 pages with the experiment since
	 * switched back off, and that is still a site whose pages want the atomic set.
	 */
	public static function atomic_signal_strength(): string
	{
		if (Atomic::has_atomic_usage()) {
			return 'usage';
		}

		return Atomic::is_elementor_atomic_active() ? 'experiment' : 'none';
	}

	/**
	 * Everything the dashboard notice needs, in one payload.
	 *
	 * Shipped nested under `addons_config`, NOT at the top level of the localize
	 * data — nested values keep their real JSON types, whereas wp_localize_script
	 * stringifies top-level scalars (which is why `v3_in_use` arrives as "1"/""
	 * and has to be read with `!!`).
	 *
	 * `in_use` is `null`, not `false`, when the question was never asked: the
	 * postmeta scan is skipped entirely once its answer cannot change what is on
	 * screen, and reporting an unasked question as "no" is how a cheap guard
	 * turns into a wrong fact somewhere downstream.
	 *
	 * @return array{
	 *     experiment:bool, in_use:bool|null, signal:string, state:string,
	 *     dismissed_signal:string, has_active:bool, show_notice:bool
	 * }
	 */
	public static function atomic_optin_signal(): array
	{
		$stored = get_option(Atomic::OPTIN_OPTION_NAME);
		$stored = is_array($stored) ? $stored : [];

		$state            = isset($stored['state']) ? (string) $stored['state'] : 'undecided';
		$dismissed_signal = isset($stored['signal']) ? (string) $stored['signal'] : 'none';

		$has_active = Atomic::instance()->has_active_atomic();

		// Nothing below can put a notice on screen once the user has accepted or
		// already has atomic switched on, so do not pay for the evidence.
		if ($has_active || 'accepted' === $state) {
			return [
				'experiment'       => false,
				'in_use'           => null,
				'in_use_count'     => null,
				'signal'           => 'none',
				'state'            => $state,
				'dismissed_signal' => $dismissed_signal,
				'has_active'       => $has_active,
				'show_notice'      => false,
			];
		}

		$in_use     = Atomic::has_atomic_usage();
		$experiment = Atomic::is_elementor_atomic_active();
		$signal     = $in_use ? 'usage' : ($experiment ? 'experiment' : 'none');

		$show = 'none' !== $signal
			&& (
				'dismissed' !== $state
				// A dismissal only covers evidence as strong as what was on the
				// table when it was made. Real pages built in V4 outrank a
				// switch, and are worth saying once even to someone who already
				// said no to the switch.
				|| self::signal_rank($dismissed_signal) < self::signal_rank($signal)
			);

		return [
			'experiment'       => $experiment,
			'in_use'           => $in_use,
			// LAST, and only when a notice is actually going on screen: this is
			// the one query on this path that cannot stop at the first row, and
			// a dismissed site would otherwise pay for a sentence nobody reads.
			'in_use_count'     => ($show && 'usage' === $signal) ? Atomic::count_atomic_usage() : null,
			'signal'           => $signal,
			'state'            => $state,
			'dismissed_signal' => $dismissed_signal,
			'has_active'       => $has_active,
			'show_notice'      => $show,
		];
	}

	/**
	 * AJAX — record the user's answer to the atomic opt-in notice.
	 *
	 * Records the decision, and — when `fields` and `ext_fields` are posted with
	 * it — performs the enable itself.
	 *
	 * ONE REQUEST, deliberately. The first version called the two existing save
	 * endpoints from the browser and recorded the decision in a third call,
	 * which reads better and cannot support an undo: those handlers END the undo
	 * offer (a hand save is a deliberate choice), so a snapshot taken here would
	 * be deleted by the very writes it was taken for. Doing all three in one
	 * request also means an accept can never half-land — widgets on, extensions
	 * off, no decision recorded — from a closed tab.
	 *
	 * The SELECTION is still the browser's, and still comes from
	 * `src/modules/dashboard/lib/setupPresets.js`: this only sanitises and
	 * stores the map it is handed, exactly as the save handlers do, so "what a
	 * recommended setup enables" is never restated in PHP.
	 *
	 * The signal strength is re-read server-side and never taken from the
	 * request: it decides how long a dismissal lasts, and the transient makes
	 * re-reading it free.
	 */
	public static function ajax_atomic_optin(): void
	{
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(__('Permission denied.', 'animation-addons-for-elementor'));
		}

		$state = isset($_POST['state']) ? sanitize_key(wp_unslash($_POST['state'])) : '';

		if (! in_array($state, ['accepted', 'dismissed'], true)) {
			wp_send_json_error(__('Invalid state.', 'animation-addons-for-elementor'));
		}

		$signal  = self::atomic_signal_strength();
		$enabled = null;

		// Both maps or neither: an accept that enabled only the widgets would
		// leave the site in a state the user never chose and the undo snapshot
		// would describe honestly but uselessly.
		if ('accepted' === $state && isset($_POST['fields'], $_POST['ext_fields'])) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decode_slug_map() runs sanitize_text_field() on the raw value before json_decode(), and write_widget_option()/write_extension_option() sanitise every key and value again before storing. The handler has already checked the nonce and the capability.
			$widgets    = self::decode_slug_map(wp_unslash($_POST['fields']));
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decode_slug_map() runs sanitize_text_field() on the raw value before json_decode(), and write_widget_option()/write_extension_option() sanitise every key and value again before storing. The handler has already checked the nonce and the capability.
			$extensions = self::decode_slug_map(wp_unslash($_POST['ext_fields']));

			if (null === $widgets || null === $extensions) {
				wp_send_json_error(__('Invalid data.', 'animation-addons-for-elementor'));
			}

			// BEFORE the first write. This is the whole return path.
			self::capture_atomic_undo($signal);

			$enabled = [
				'widgets'    => self::write_widget_option($widgets)['total'],
				'extensions' => self::write_extension_option($extensions)['total'],
			];
		}

		update_option(
			Atomic::OPTIN_OPTION_NAME,
			[
				'state'  => $state,
				'signal' => $signal,
			]
		);

		wp_send_json_success(
			[
				'state'   => $state,
				'signal'  => $signal,
				'enabled' => $enabled,
			]
		);
	}

	/**
	 * AJAX — go back.
	 *
	 * Three decisions, and the difference between the first two is the whole
	 * reason there are three:
	 *
	 *  - `undo` — replay the snapshot. EXACT: the site ends up byte-for-byte
	 *    where it was, including option rows that had never existed. Needs a
	 *    snapshot, so it is only offered while one is on file.
	 *  - `off`  — switch both atomic options to an empty array. Available
	 *    ALWAYS, and it is what the permanent "Back to V3" escape hatch uses.
	 *    Not an exact restore and does not pretend to be: nothing is deleted,
	 *    because an absent row and an empty one mean different things here (see
	 *    UNDO_OPTION_NAME) and guessing which one this site had before would be
	 *    inventing history. An empty array is the same definite "off" the
	 *    master Enable All switch writes, and it can never re-arm
	 *    migrate_newly_offered_extensions() behind the user's back.
	 *
	 *    It writes NOTHING when nothing is switched on. That is the "Choose
	 *    manually" shape — the offer accepted, the tab revealed, no widget ever
	 *    enabled — and there the options are usually ABSENT. Writing empty rows
	 *    over no rows would not be reversing anything; it would be making the
	 *    one change this request exists to take back, and it would end the
	 *    "fresh install, the wizard decides" state permanently.
	 *  - `keep` — end the offer, change nothing.
	 *
	 * ALL THREE RECORD A DISMISSAL rather than clearing the answer. Going back
	 * to "undecided" would put the original notice on screen again at the next
	 * page load, so going back would look like it had not worked — and
	 * re-offering something somebody just took back is how a helpful prompt
	 * becomes a nag. Where a snapshot exists the dismissal is recorded at the
	 * SNAPSHOT's signal rather than today's, so the ranked rule still holds: if
	 * this site later grows real V4 pages, that stronger signal is still allowed
	 * to speak once.
	 */
	public static function ajax_atomic_optin_undo(): void
	{
		check_ajax_referer( Nonce::action( Nonce::ADMIN, 'nonce' ), 'nonce' );

		if (! current_user_can('manage_options')) {
			wp_send_json_error(__('Permission denied.', 'animation-addons-for-elementor'));
		}

		$decision = isset($_POST['decision']) ? sanitize_key(wp_unslash($_POST['decision'])) : '';

		if (! in_array($decision, ['undo', 'keep', 'off'], true)) {
			wp_send_json_error(__('Invalid decision.', 'animation-addons-for-elementor'));
		}

		$undo         = get_option(Atomic::UNDO_OPTION_NAME);
		$has_snapshot = is_array($undo) && ! empty($undo['time']);

		if ('keep' === $decision) {
			self::forget_atomic_undo();

			wp_send_json_success(['decision' => 'keep']);
		}

		// Only `undo` needs a snapshot. `off` deliberately does not — it is the
		// escape hatch for a site whose snapshot has expired, been ended by a
		// hand save, or never existed because the accept predates this feature.
		if ('undo' === $decision && ! $has_snapshot) {
			wp_send_json_error(esc_html__('There is nothing left to undo.', 'animation-addons-for-elementor'));
		}

		if ('undo' === $decision) {
			self::restore_atomic_undo($undo);
		} elseif (Atomic::instance()->has_active_atomic()) {
			self::write_widget_option([]);
			self::write_extension_option([]);
		}

		// With nothing active there is deliberately no write above: clearing the
		// stored answer below is the entire reversal, and it is enough — the V4
		// tab is offered by ATOMIC_OPTED_IN, so a dismissal takes it away again.

		update_option(
			Atomic::OPTIN_OPTION_NAME,
			[
				'state'  => 'dismissed',
				'signal' => $has_snapshot && isset($undo['signal'])
					? (string) $undo['signal']
					: self::atomic_signal_strength(),
			]
		);

		self::forget_atomic_undo();

		wp_send_json_success(['decision' => $decision]);
	}

	/**
	 * Enqueue global atomic editor scripts into the top-level window.
	 */
	public static function enqueue_atomic_editor_scripts(): void
	{
		// Atomic::instance()->guard_elementor_core_atomic_types();

		$suffix = Atomic::instance()->is_dev_environment() ? '' : '.min';
		$path = 'assets/atomic/js/atomic-editor' . $suffix . '.js';
		$file_path = AAEADDON_PATH . $path;
		// Version the URL from the built file itself. Unlike a timestamp-only
		// version, this also changes for multiple builds written in one second.
		$version = AAEADDON_VERSION;
		if (is_readable($file_path)) {
			$content_hash = md5_file($file_path);
			$version = false !== $content_hash
				? AAEADDON_VERSION . '-' . substr($content_hash, 0, 12)
				: (string) filemtime($file_path);
		}
		

		wp_enqueue_script(
			'aae-atomic-editor',
			AAEADDON_URL . $path,
			[
				'nested-elements',
				'elementor-editor',
				'elementor-common',
				'wp-element',
				'jquery',
			],
			$version,
			true
		);

		// NOTE: AAE_PRESET_CONFIG is NOT localized here. PresetPickerControl.jsx
		// (which reads window.AAE_PRESET_CONFIG) ships inside the
		// 'aae-atomic-common-editor-bridge' bundle (built from
		// src/modules/atomic/editor-bridge.js), NOT this 'aae-atomic-editor'
		// handle (built from inc/AtomicWidgets/assets/js/atomic-editor.js —
		// a small, unrelated outer-frame bridge). See Atomic\Assets::
		// enqueue_editor_bridge() for the correct wp_localize_script() call.

		// Loop Grid: ajax config for the editor "full grid live" preview module,
		// plus the per-post-type taxonomy map the panel's notice card reads
		// (NoticeControl.jsx, source 'loop-grid-taxonomies').
		wp_localize_script(
			'aae-atomic-editor',
			'AAE_LOOP_GRID',
			[
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => Nonce::create( Nonce::LOOP_GRID ),
				'notices' => [
					'taxonomies' => self::loop_grid_taxonomy_notices(),
				],
			]
		);

		// Keep the AAE categories at the top of the Elements panel.
		//
		// promote_panel_categories() orders the config Elementor PRINTS, which
		// covers the initial load. Two client-side paths then replace that map
		// wholesale with PHP's own (appended-last) order and would undo it:
		//
		//   1. elementor.refreshWidgets() assigns
		//      `config.document.panel.elements_categories = data.categories`
		//      straight from the `refresh_widgets_config` AJAX, which has no
		//      filter, then fires `elementor/widgets/refreshed`.
		//   2. Switching documents (Site Settings, a popup/template) assigns
		//      `config.document = config` from a fresh Document::get_config().
		//
		// Re-applying on those two signals is the whole fix. It runs before the
		// panel can read the map: the Elements page builds its categories
		// collection in initialize() → initCategoriesCollection(), which only
		// happens once the panel routes to `panel/elements/categories` — after
		// both `elementor:init` and `document:loaded`.
		//
		// Inline rather than a module under src/, deliberately: no build step,
		// and it stays next to the PHP half it mirrors.
		wp_add_inline_script(
			'aae-atomic-editor',
			sprintf(
				'jQuery( window ).on( "elementor:init", function () {
	var order = %s;

	function promote() {
		var panel = window.elementor && elementor.config && elementor.config.document && elementor.config.document.panel;
		var cats = panel && panel.elements_categories;

		if ( ! cats ) {
			return;
		}

		var out = {};

		// Favorites is a user-pinning surface — it stays above ours.
		if ( cats.favorites ) {
			out.favorites = cats.favorites;
		}

		order.forEach( function ( slug ) {
			if ( cats[ slug ] ) {
				out[ slug ] = cats[ slug ];
			}
		} );

		Object.keys( cats ).forEach( function ( slug ) {
			if ( ! out.hasOwnProperty( slug ) ) {
				out[ slug ] = cats[ slug ];
			}
		} );

		panel.elements_categories = out;
	}

	promote();
	elementor.on( "document:loaded", promote );
	elementor.hooks.addAction( "elementor/widgets/refreshed", promote );
} );',
				wp_json_encode(Atomic::PANEL_CATEGORY_ORDER)
			)
		);
	}

	/**
	 * Panel search for the `aae-query-chips` controls: posts (by title / ID) or
	 * taxonomy terms (by name). Returns [{id, label}] — max 20.
	 */
	public static function ajax_loop_query_options()
	{
		check_ajax_referer( Nonce::action( Nonce::LOOP_GRID, 'nonce' ), 'nonce' );

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : 'post';
		$term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
		$options = [];

		if ('user' === $kind) {
			// Authors for the grid's authors / exclude_authors chips. Only people
			// who can publish — a subscriber is never an author. Numeric search
			// tries the id first, like the post kind.
			if (ctype_digit($term)) {
				$by_id = get_user_by('id', (int) $term);
				if ($by_id instanceof \WP_User && $by_id->has_cap('edit_posts')) {
					$options[] = ['id' => (int) $by_id->ID, 'label' => $by_id->display_name];
				}
			}
			$users = get_users([
				'number'              => ('' === $term) ? 8 : 20,
				'search'              => '' === $term ? '' : '*' . $term . '*',
				'search_columns'      => ['display_name', 'user_login', 'user_nicename', 'user_email'],
				'capability'          => 'edit_posts',
				'orderby'             => 'display_name',
				'order'               => 'ASC',
			]);
			foreach ($users as $u) {
				if (! empty($by_id) && (int) $u->ID === (int) $by_id->ID) {
					continue;
				}
				$options[] = ['id' => (int) $u->ID, 'label' => $u->display_name];
			}
		} elseif ('acf_field' === $kind) {
			// The Field / Date filters' ACF dropdown (AcfFieldControl.jsx). The
			// whole catalogue at once — a site has tens of fields, not
			// thousands — and the control narrows it to the grid's post type
			// itself. `acf` says whether ACF is running at all, so an empty
			// list can be told apart from "no ACF here".
			Atomic::load_loop_grid_class();
			wp_send_json_success([
				'acf'     => function_exists('acf_get_field_groups'),
				'options' => Widgets\LoopGrid\Loop_Filter_Auth::acf_field_catalogue(),
			]);
		} elseif ('term' === $kind) {
			$taxonomy = isset($_POST['taxonomy']) ? sanitize_key(wp_unslash($_POST['taxonomy'])) : '';
			if (! $taxonomy || ! taxonomy_exists($taxonomy)) {
				wp_send_json_error(['message' => 'Invalid taxonomy.'], 400);
			}
			$terms = get_terms([
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 20,
				'search'     => $term,
			]);
			if (! is_wp_error($terms)) {
				foreach ($terms as $t) {
					// post_format terms are stored as "post-format-video" etc. —
					// show the human name ("Video") instead.
					$label = ('post_format' === $taxonomy)
						? get_post_format_string(str_replace('post-format-', '', $t->slug))
						: $t->name;
					$options[] = ['id' => (int) $t->term_id, 'label' => $label];
				}
			}
		} else {
			$public_types = array_keys(get_post_types(['public' => true]));
			$public_types = array_values(array_diff($public_types, ['attachment']));

			// Scope the search to the grid's selected Source post type when the
			// control sends it — "all settings are source related". No/invalid
			// type falls back to every public type.
			$post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
			$search_types = ($post_type && in_array($post_type, $public_types, true))
				? [$post_type]
				: $public_types;

			$args = [
				'post_type'           => $search_types,
				'post_status'         => 'publish',
				// Browsing (empty search) loads a small teaser list for
				// performance; typing searches wider.
				'posts_per_page'      => ('' === $term) ? 4 : 20,
				'ignore_sticky_posts' => true,
				'orderby'             => 'date',
				'order'               => 'DESC',
			];
			if (ctype_digit($term)) {
				// Numeric search: try the exact ID first, fall back to title search.
				$by_id = get_post((int) $term);
				if ($by_id && 'publish' === $by_id->post_status && in_array($by_id->post_type, $search_types, true)) {
					$options[] = ['id' => (int) $by_id->ID, 'label' => get_the_title($by_id)];
				}
			}
			if ('' !== $term) {
				$args['s'] = $term;
			}
			$query = new \WP_Query($args);
			foreach ($query->posts as $p) {
				$options[] = ['id' => (int) $p->ID, 'label' => get_the_title($p)];
			}
			wp_reset_postdata();
		}

		wp_send_json_success(['options' => $options]);
	}

	public static function ajax_loop_post_data()
	{
		check_ajax_referer( Nonce::action( Nonce::LOOP_GRID, 'nonce' ), 'nonce' );

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		Atomic::load_loop_grid_class();

		// The client sends the loop grid's UNWRAPPED settings (query + filters)
		// as one JSON blob; the shared builder sanitizes and assembles the exact
		// query the frontend render will run, so the editor preview always
		// matches the published page.
		$filters = [];
		if (isset($_POST['filters'])) {
			$decoded = json_decode(sanitize_text_field(wp_unslash($_POST['filters'])), true);
			if (is_array($decoded)) {
				$filters = $decoded;
			}
		}

		// Back-compat: individual fields override / fill in when present.
		foreach (['post_type', 'order_by', 'order'] as $k) {
			if (isset($_POST[$k])) {
				$filters[$k] = sanitize_key(wp_unslash($_POST[$k]));
			}
		}
		if (isset($_POST['posts_per_page'])) {
			$filters['posts_per_page'] = absint($_POST['posts_per_page']);
		}

		// Related source preview: the editor has no "current post", so relate
		// from the document's Preview Settings post / sample post — the same
		// post the authored card previews.
		if (('related' === ($filters['post_type'] ?? '')) && empty($filters['_context_post_id'])) {
			$sample = Atomic::get_sample_post();
			if ($sample instanceof \WP_Post) {
				$filters['_context_post_id'] = $sample->ID;
			}
		}

		$query = new \WP_Query(
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::build_query_args($filters)
		);

		$posts = [];
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$posts[] = array_merge([
					'title'   => get_the_title(),
					'url'     => get_permalink(),
					'image'   => get_the_post_thumbnail_url(null, 'large') ?: '',
					'excerpt' => wp_strip_all_tags(get_the_excerpt()),
				], self::excerpt_preview_data(get_post()));
			}
			wp_reset_postdata();
		}

		wp_send_json_success(['posts' => $posts]);
	}

	/**
	 * One post's preview data (title + featured image) for the editor's
	 * authored-card sample. The client sends the LIVE value of the document's
	 * `aae_loop_page_post` page setting (Preview Settings), so the chosen post
	 * previews immediately after "Apply & Preview" — no editor reload needed.
	 */
	public static function ajax_loop_sample_post()
	{
		check_ajax_referer( Nonce::action( Nonce::LOOP_GRID, 'nonce' ), 'nonce' );

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$post = isset($_POST['sample_id']) ? get_post(absint($_POST['sample_id'])) : null;
		if (! $post || 'publish' !== $post->post_status) {
			wp_send_json_error(['message' => 'Invalid sample post.'], 404);
		}

		wp_send_json_success(array_merge([
			'title' => get_the_title($post),
			'image' => get_the_post_thumbnail_url($post, 'large') ?: '',
		], self::excerpt_preview_data($post)));
	}

	/**
	 * The two texts the editor's Post Excerpt mirror needs for one post: the
	 * WordPress excerpt (what `none` / a line clamp shows) and the full plain
	 * text a word / char limit trims from — the SAME two sources the widget's
	 * PHP uses, so the canvas and the page trim the same words. Empty when the
	 * widget is switched off (its class is only loaded while it registers).
	 *
	 * @param \WP_Post|null $post The post.
	 * @return array{excerpt?: string, excerpt_full?: string}
	 */
	public static function excerpt_preview_data($post): array
	{
		$cls = '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostExcerpt\Aaeaddon_A_Post_Excerpt';
		if (! $post instanceof \WP_Post || ! class_exists($cls)) {
			return [];
		}

		return [
			'excerpt'      => $cls::excerpt_for($post, 'none', 0, ''),
			'excerpt_full' => $cls::full_text($post),
		];
	}

	/**
	 * For each public post type: how many taxonomy filters the Loop Grid offers
	 * it, which of them are currently UNREGISTERED (kept by the known-taxonomy
	 * ratchet — their plugin is off) and which are offered although not public
	 * (WooCommerce attributes). The panel cannot compute this per instance
	 * (controls are built once per type), so it is shipped as data and the
	 * notice card resolves it against the element's own Source.
	 *
	 * @return array<string, array{count:int, unregistered:string[], nonPublic:string[]}>
	 */
	public static function loop_grid_taxonomy_notices(): array
	{
		Atomic::load_loop_grid_class();
		$taxes = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::get_query_taxonomies();
		$out   = [];

		foreach (array_keys(get_post_types(['public' => true])) as $type) {
			if ('attachment' === $type) {
				continue;
			}
			$entry = ['count' => 0, 'unregistered' => [], 'nonPublic' => []];
			foreach ($taxes as $tax) {
				if (! in_array($type, (array) ($tax->object_type ?? []), true)) {
					continue;
				}
				$label = (string) ($tax->label ?? $tax->name);
				if (! empty($tax->aae_unregistered)) {
					$entry['unregistered'][] = $label;
					continue;
				}
				$entry['count']++;
				if (empty($tax->public)) {
					$entry['nonPublic'][] = $label;
				}
			}
			$out[ $type ] = $entry;
		}

		return $out;
	}

	/**
	 * Frontend: render the loop-item cells for a given page of a specific Loop
	 * Grid instance, WITH the authored atomic styles intact.
	 *
	 * Finds the loop-item element data inside the requesting document (by the
	 * grid's element id), pushes the paged WP_Query onto the Render_Context stack
	 * (keyed like Aaeaddon_A_Loop_Grid does), and renders the loop-item element — the
	 * exact same path used server-side, so the markup + style classes match.
	 *
	 * Nonce: `Nonce::LOOP_GRID_FRONT` (public). Only reads published post content.
	 */
	public static function ajax_loop_grid_page() {
		check_ajax_referer( Nonce::action( Nonce::LOOP_GRID_FRONT, 'nonce' ), 'nonce' );

		$post_id  = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		$grid_id  = isset($_POST['grid_id']) ? sanitize_key(wp_unslash($_POST['grid_id'])) : '';
		$paged    = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;

		if (! $post_id || ! $grid_id) {
			wp_send_json_error(['message' => 'Missing post_id or grid_id.'], 400);
		}

		$post = get_post($post_id);
		if (! $post || ('publish' !== $post->post_status && ! current_user_can('read_post', $post_id)) || post_password_required($post)) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}


		// Multilingual context (WPML & Polylang): switch active language to match requesting post.
		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpml_switch_language_for_post', $post_id );
		}
		if ( function_exists( 'pll_get_post_language' ) && function_exists( 'PLL' ) ) {
			$pll_lang = pll_get_post_language( $post_id );
			if ( $pll_lang && isset( PLL()->curlang, PLL()->model ) ) {
				PLL()->curlang = PLL()->model->get_language( $pll_lang );
			}
		}

		$doc = \Elementor\Plugin::$instance->documents->get($post_id);
		if (! $doc) {
			wp_send_json_error(['message' => 'Document not found.'], 404);
		}

		$data = $doc->get_elements_data();

		// The static grid and the slider variant share this endpoint. Both root
		// types publish the same Render_Context (keyed by Aaeaddon_A_Loop_Grid::class,
		// which the slider root extends) and repeat a loop-item subtree per post —
		// only the element type names differ, so accept either here.
		$grid_types = ['e-aae-a-loop-grid', 'e-aae-a-loop-grid-slider'];
		$item_types = ['e-aae-a-loop-item', 'e-aae-a-loop-slide-item'];

		// Locate the loop-grid element (by id) and its loop-item descendant.
		$grid_el = null;
		$find_grid = function ($els) use (&$find_grid, &$grid_el, $grid_id, $grid_types) {
			foreach ($els as $el) {
				if (($el['id'] ?? '') === $grid_id && in_array($el['elType'] ?? '', $grid_types, true)) {
					$grid_el = $el;
					return;
				}
				if (! empty($el['elements'])) {
					$find_grid($el['elements']);
					if ($grid_el) {
						return;
					}
				}
			}
		};
		$find_grid($data);

		if (! $grid_el) {
			wp_send_json_error(['message' => 'Loop grid not found.'], 404);
		}

		$item_el = null;
		$find_item = function ($els) use (&$find_item, &$item_el, $item_types) {
			foreach ($els as $el) {
				if (in_array($el['elType'] ?? '', $item_types, true)) {
					$item_el = $el;
					return;
				}
				if (! empty($el['elements'])) {
					$find_item($el['elements']);
					if ($item_el) {
						return;
					}
				}
			}
		};
		$find_item([$grid_el]);

		if (! $item_el) {
			wp_send_json_error(['message' => 'Loop item not found.'], 404);
		}

		// Build the paged query args from the grid's saved settings — the same
		// shared builder the frontend render uses, so pagination honors every
		// query filter (taxonomy terms, include/exclude, date range, meta…).
		Atomic::load_loop_grid_class();
		$gs = (array) ($grid_el['settings'] ?? []);

		// The page this request is FOR, as `path?query` — the URL the visitor's
		// address bar is about to show. Read here rather than at the top of the
		// handler because it is the first line that may touch Loop_Filter_Auth,
		// and load_loop_grid_class() immediately above is what puts that class
		// on disk-to-memory.
		//
		// With it the response is, by construction, what a full page load of
		// that URL would have rendered: the page number and the filter state are
		// read back out of it by the very code the first render uses, and the
		// filter widgets re-rendered below build their links against it instead
		// of against admin-ajax.php. That is what lets the filter runtime be a
		// pure "same render, no reload" upgrade with no second copy of any rule.
		//
		// Without it the handler behaves exactly as it always has, which is what
		// keeps the existing pagination runtime working untouched.
		$path = isset($_POST['path']) ? (string) wp_unslash($_POST['path']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- set_request_url() is the validator: same-host only, capped, no traversal.
		if ('' !== $path && ! \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::set_request_url($path)) {
			wp_send_json_error(['message' => 'Invalid path.'], 400);
		}

		// Related source: the anchor is the post the visitor is READING, which
		// is not $post_id once the grid lives in a theme-builder template —
		// there $post_id is the template document, the one whose saved data
		// declares this grid. The runtime posts the viewed post separately.
		//
		// It goes through the SAME gate $post_id did, and for the same reason.
		// The Related builder reads this post's type and its terms to find posts
		// "like" it, so an unchecked id lets a visitor anchor the query on a
		// draft and learn which published posts share its terms. Nothing
		// unpublished is ever rendered either way — all three builders pin
		// post_status to publish — but the result set is still an inference
		// channel about a post nobody was shown. A rejected id falls back to the
		// document, exactly as an absent one does.
		$context_id = isset($_POST['context_id']) ? absint($_POST['context_id']) : 0;
		if ($context_id && $context_id !== $post_id) {
			$context = get_post($context_id);
			if (
				! $context
				|| ('publish' !== $context->post_status && ! current_user_can('read_post', $context_id))
				|| post_password_required($context)
			) {
				$context_id = 0;
			}
		}
		$gs['_context_post_id'] = $context_id ?: $post_id;

		// Current Query source: the archive's query vars, captured into the
		// pagination config at render time and posted back by the runtime.
		if (isset($_POST['qv'])) {
			$qv = json_decode(sanitize_text_field(wp_unslash($_POST['qv'])), true);
			if (is_array($qv)) {
				$gs['_qv'] = $qv; // whitelist-sanitized inside the builder
			}
		}

		// Visitor filters: a JSON map of url_key => value, the same shape the
		// URL carries on the first render. Authorised against the filter widgets
		// this SAVED document declares for this grid — anything not declared is
		// dropped — and handed to the builder as its own argument, never inside
		// the settings blob. Oversized / malformed is a 400, not a guess.
		$filters = [];
		if ('' !== $path) {
			// URL mode: the filter state is IN the path, so it is read with the
			// same request parser the first render uses. No second shape, and
			// nothing for the browser to get wrong about which keys are filter
			// keys — request_args() is already unslashed, hence `false`.
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::prime_document($post_id, $data);
			$request_args = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::request_args();

			$filters = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::current(
				$post_id,
				$grid_id,
				\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::effective_post_type($gs),
				$request_args,
				false
			);

			// The page number comes from the same place, so a filter link (which
			// strips it) lands on page 1 without the runtime having to say so.
			$paged = isset($request_args['aae_page']) ? max(1, absint($request_args['aae_page'])) : 1;
		} elseif (isset($_POST['filters'])) {
			$raw = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::decode_payload(wp_unslash($_POST['filters'])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded + capped, then every value is authorised
			if (null === $raw) {
				wp_send_json_error(['message' => 'Invalid filters payload.'], 400);
			}
			// current() is the one authorisation pipeline the first render also
			// uses, so page 2 can never be authorised on different terms than
			// page 1. The tree is handed over rather than re-read: this handler
			// already decoded it above. `false` says the payload was unslashed
			// once already, at decode_payload() — unslashing it twice ate real
			// backslashes and made page 2 a different result set.
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::prime_document($post_id, $data);
			$filters = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::current(
				$post_id,
				$grid_id,
				\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::effective_post_type($gs),
				$raw,
				false
			);
		}

		$query_args = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::build_query_args(
			$gs,
			$paged,
			$filters
		);

		// Total + pages (respects the same query, offset-corrected).
		$total     = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::count_total($gs, $query_args);
		$max_pages = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::pages_for_total($total, $query_args);

		// Hand the readouts the numbers this request already paid for. A Result
		// Count re-rendered below would otherwise run the same count again — and,
		// worse, could answer a different one if anything about the request
		// changed between the two, so the grid and its own caption would
		// disagree inside a single response.
		\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::prime_summary(
			$post_id,
			$grid_id,
			[
				'grid_id'   => $grid_id,
				'post_type' => \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::effective_post_type($gs),
				'filters'   => $filters['active'] ?? [],
				'total'     => $total,
				'max_pages' => $max_pages,
				'paged'     => $paged,
				'per_page'  => max(1, (int) ($query_args['posts_per_page'] ?? 6)),
			]
		);

		// Push context (same key the Loop Item reads) and render the item.
		\Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context::push(
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::class,
			['query_args' => $query_args]
		);

		$item_obj = \Elementor\Plugin::$instance->elements_manager->create_element_instance($item_el);
		ob_start();
		if ($item_obj) {
			$item_obj->print_element();
		}
		$html = ob_get_clean();

		\Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context::pop(
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::class
		);

		// The filter widgets, re-rendered for the same URL.
		//
		// Asked for explicitly, so the pagination runtime — which changes the
		// page and never the filter state — pays nothing for it. The widgets are
		// rendered rather than patched in the browser because everything they
		// draw (which terms read as selected, where each link points NEXT, the
		// Clear link) follows from rules that already exist once, in PHP.
		// Recomputing them in JS would be a second copy that can disagree with
		// the page a reload would produce.
		$filters_html = [];
		if (! empty($_POST['with_filters'])) {
			$filters_html = self::render_loop_filters($post_id, $grid_id, $data);
		}

		wp_send_json_success([
			'html'         => $html,
			'paged'        => $paged,
			'max_pages'    => $max_pages,
			'total'        => $total,
			'filters'      => (object) ($filters['active'] ?? []),
			'filters_html' => (object) $filters_html,
		]);
	}

	/**
	 * Every filter widget that targets $grid_id, rendered fresh, keyed by its
	 * element id.
	 *
	 * The list comes from `Loop_Filter_Auth::rerender_ids()`, which is the
	 * declaration walk — so a widget not authorised to filter this grid is never
	 * sent back for it — PLUS the READOUTS pointed at the grid. A Result Count
	 * or an Active Filters bar declares nothing and so has no declaration to be
	 * found by; it still describes the result set, and leaving it out is how a
	 * filtered page ends up saying "24 results" over twelve of them.
	 *
	 * The document is switched to for the duration: a filter widget asks
	 * `Aaeaddon_A_Loop_Grid::current_document_id()` which document declares it, and
	 * in an admin-ajax request there is no current document and no global post,
	 * so without this every widget would resolve nothing and render an empty
	 * list. `switch_to_document()` only swaps Elementor's own pointer — it
	 * touches no post globals, so it cannot disturb anything around it.
	 *
	 * @param int    $post_id  The document whose saved data declares the filters.
	 * @param string $grid_id  The Loop Grid element id they target.
	 * @param array  $elements That document's already-decoded element tree.
	 * @return array<string, string> element id => HTML.
	 */
	public static function render_loop_filters(int $post_id, string $grid_id, array $elements): array {
		$wanted = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::rerender_ids($elements, $grid_id);
		if (! $wanted) {
			return [];
		}

		$found = [];
		$walk  = function ($els) use (&$walk, &$found, $wanted) {
			foreach ((array) $els as $el) {
				if (! is_array($el)) {
					continue;
				}
				$id = (string) ($el['id'] ?? '');
				if ('' !== $id && isset($wanted[$id])) {
					$found[$id] = $el;
				}
				if (! empty($el['elements'])) {
					$walk($el['elements']);
				}
			}
		};
		$walk($elements);

		if (! $found) {
			return [];
		}

		$doc = \Elementor\Plugin::$instance->documents->get($post_id);
		if ($doc) {
			\Elementor\Plugin::$instance->documents->switch_to_document($doc);
		}

		$out = [];
		foreach ($found as $id => $el) {
			$obj = \Elementor\Plugin::$instance->elements_manager->create_element_instance($el);
			if (! $obj) {
				continue;
			}
			ob_start();
			$obj->print_element();
			$out[$id] = ob_get_clean();
		}

		if ($doc) {
			\Elementor\Plugin::$instance->documents->restore_document();
		}

		return $out;
	}
}
