<?php
/**
 * Animation Settings — the dashboard-owned (v4) home for AAE's site-wide
 * animation chrome: preloader, cursor, scroll-to-top, scroll indicator, popup.
 *
 * Why this exists next to the v3 settings rather than replacing them:
 *
 * The v3 versions of these five features are Elementor Site Settings tabs in
 * the PRO plugin (pro/inc/settings/wcf-*.php), storing into the Elementor Kit
 * and rendered from pro/inc/global-elements.php. Live sites already depend on
 * that data, so it must keep rendering. This module is a SEPARATE system with
 * its own option, its own renderer, and its own on/off — nothing here reads or
 * writes the Kit.
 *
 * The two systems are kept from colliding by two independent rules:
 *
 *   1. Per feature, the v4 renderer wins. Pro's v3 renderer bails out for a
 *      feature whose v4 counterpart is enabled — otherwise a site with both on
 *      would paint two preloaders. This is per feature, never wholesale: with
 *      the v4 preloader on and no v4 popup, an existing v3 popup keeps working
 *      untouched.
 *
 *   2. `legacy_v3` hides the v3 EDITOR UI only. Switching it off
 *      unregisters all five Site Settings tabs so the Elementor editor stops
 *      offering them — it does NOT stop anything already saved from rendering.
 *      Hiding a settings screen must never silently take a live site's popup
 *      off its pages.
 *
 * Its DEFAULT is decided per site, once, by detect_legacy_usage(): a site with
 * v3 chrome already configured in its Kit keeps the tabs; a site with none —
 * i.e. a new user — never sees them at all, popup included. A flat default
 * would be wrong in one direction or the other: `true` litters every new
 * install with a legacy UI, `false` yanks the settings screens out from under
 * everyone who is mid-project.
 *
 * @package AnimationAddons
 */

namespace WCF_ADDONS\AnimationSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Animation_Settings {

	const OPTION_NAME = 'aae_animation_settings';
	const NONCE       = 'wcf_admin_nonce';

	/** Features this module can own. Only `preloader` is implemented so far. */
	const FEATURES = [ 'smooth_scroll', 'preloader', 'cursor', 'scroll_to_top', 'scroll_indicator', 'popup' ];

	/**
	 * Device buckets for the smooth scroller, in the order the panel lists them.
	 *
	 * These four names are not ours to choose — `wcf-addons-ex.js` resolves the
	 * current device with its own getCurrentDevice() and indexes the payload by
	 * the result, so the keys have to be exactly these.
	 */
	const SMOOTH_DEVICES = [ 'desktop', 'laptop', 'tablet', 'mobile' ];

	/**
	 * Field key the shared display-conditions control stores under.
	 *
	 * `conditions`, not `display`, because the scroll indicator already has a
	 * v3 field called `display` — its own three-way "Entire Website / Specific
	 * Pages / Specific Post Types" enum, mapped to a Kit control. The injection
	 * in schema() skips a feature that already has the key, so the collision did
	 * not overwrite anything; it silently left the scroll indicator as the one
	 * feature with no conditions at all.
	 */
	const CONDITIONS_KEY = 'conditions';

	/** @var array|null Request-level cache of the sanitized option. */
	private static ?array $cache = null;

	private static ?Animation_Settings $instance = null;

	public static function instance(): Animation_Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_ajax_aae_get_animation_settings', [ $this, 'ajax_get' ] );
		add_action( 'wp_ajax_aae_save_animation_settings', [ $this, 'ajax_save' ] );
		add_action( 'wp_ajax_aae_search_content', [ $this, 'ajax_search_content' ] );

		// The request-level cache has to die whenever the option changes, no
		// matter who changed it — a WP-CLI script, a migration, another plugin.
		// Relying on ajax_save() to refresh it means any other writer leaves a
		// stale copy behind and the renderer keeps painting the old settings.
		add_action( 'update_option_' . self::OPTION_NAME, [ __CLASS__, 'flush_cache' ] );
		add_action( 'add_option_' . self::OPTION_NAME, [ __CLASS__, 'flush_cache' ] );

		add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'hide_legacy_widgets_in_panel' ] );

		// Settle the legacy question once, in admin, where a write is cheap and
		// expected. get() still answers correctly before this runs — it just
		// re-detects each time instead of reading a stored decision.
		add_action( 'admin_init', [ __CLASS__, 'maybe_bootstrap' ] );

		// …and let it come BACK on if v3 turns up later. Runs after bootstrap.
		add_action( 'admin_init', [ __CLASS__, 'maybe_reactivate_legacy' ], 11 );

		// Showing the legacy UI is not enough: imported v3 content renders
		// nothing until those widgets are actually registered.
		add_action( 'admin_init', [ __CLASS__, 'maybe_enable_used_v3_widgets' ], 12 );
	}

	/**
	 * Persist the one-time legacy decision if this site has never made it.
	 *
	 * Runs only when the option is entirely absent, so a user who later flips
	 * the switch by hand is never overridden by a re-detection.
	 */
	public static function maybe_bootstrap(): void {
		if ( false !== get_option( self::OPTION_NAME, false ) ) {
			return;
		}

		$settings = self::defaults();
		$settings['legacy_v3'] = self::detect_legacy_usage();

		// Carry an existing v3 configuration across so the new panel opens
		// already filled in. See import_from_kit() for why this matters more
		// than the detection above.
		$settings = self::import_from_kit( $settings );
		$settings = self::import_smooth_scroll_from_v3( $settings );

		update_option( self::OPTION_NAME, $settings );
		self::$cache = $settings;
	}

	/**
	 * Bring the legacy surface BACK when v3 turns up on a site we'd called new.
	 *
	 * The hole this fills: a fresh install is correctly given the v4-only
	 * experience, and then the user imports a starter template or demo built on
	 * v3 widgets — or simply decides they want the old features after watching a
	 * tutorial. The one-time decision at bootstrap can't see any of that coming.
	 *
	 * So the flag is a ONE-WAY RATCHET. Evidence of v3 can always switch it back
	 * on; nothing here ever switches it off. Off is only ever a default for a
	 * site with no v3 evidence at all.
	 *
	 * The exception is a deliberate human choice: once the user has touched the
	 * switch themselves (`legacy_v3_user_set`), the ratchet stops — an import
	 * must not overrule someone who explicitly turned the old surface off.
	 */
	public static function maybe_reactivate_legacy(): void {
		$settings = self::get();

		if ( ! empty( $settings['legacy_v3'] ) || ! empty( $settings['legacy_v3_user_set'] ) ) {
			return;
		}

		if ( ! self::has_v3_usage() ) {
			return;
		}

		$settings['legacy_v3'] = true;

		// Bring their old chrome config across too, for the same reason
		// import_from_kit() exists — arriving at a half-populated screen is
		// what makes a user think something was lost.
		$settings = self::import_from_kit( $settings );

		update_option( self::OPTION_NAME, self::sanitize( $settings ) );
	}

	/**
	 * Register the v3 widgets an imported site actually USES.
	 *
	 * The gap this closes, found by importing a real starter template:
	 * `maybe_reactivate_legacy()` correctly notices the imported v3 content and
	 * turns `legacy_v3` back on — but that flag governs UI VISIBILITY only.
	 * Registration is driven by `wcf_save_widgets`, which a template import
	 * never writes. So on a fresh site the demo lands with 34 pages built from
	 * `wcf--title` / `wcf--image` / `wcf--counter` and every one of them renders
	 * COMPLETELY BLANK: an unregistered widget emits nothing at all, not even a
	 * wrapper, and there is no error, no notice, and nothing in the log.
	 *
	 * Two rules make this safe:
	 *
	 *   1. Only when `wcf_save_widgets` is ABSENT — a site that has ever saved
	 *      the widgets screen has made a deliberate choice, and an import must
	 *      not overrule it. (An empty array is a choice; only "never set" is
	 *      not.)
	 *   2. Only the slugs the content actually references. Switching on all ~70
	 *      would be enabling widgets nobody asked for.
	 *
	 * Enabling is the safe direction here: a widget that renders is never worse
	 * than one that renders nothing. The DANGER box in CLAUDE.md is about the
	 * opposite move — never DISABLE registration on a heuristic.
	 */
	public static function maybe_enable_used_v3_widgets(): void {
		if ( false !== get_option( 'wcf_save_widgets', false ) ) {
			return;
		}

		if ( ! self::has_v3_usage() ) {
			return;
		}

		global $wpdb;

		$rows = $wpdb->get_col(
			"SELECT meta_value FROM {$wpdb->postmeta}
			  WHERE meta_key = '_elementor_data'
			    AND meta_value LIKE '%\"widgetType\":\"wcf--%'"
		);

		$used = [];

		foreach ( (array) $rows as $row ) {
			if ( preg_match_all( '/"widgetType":"(wcf--[a-z0-9-]+)"/', (string) $row, $matches ) ) {
				foreach ( $matches[1] as $name ) {
					$used[ $name ] = true;
				}
			}
		}

		if ( empty( $used ) ) {
			return;
		}

		$map   = self::widget_name_to_slug_map();
		$slugs = [];

		foreach ( array_keys( $used ) as $name ) {
			if ( isset( $map[ $name ] ) ) {
				$slugs[ $map[ $name ] ] = true;
			}
		}

		if ( empty( $slugs ) ) {
			return;
		}

		update_option( 'wcf_save_widgets', $slugs );
	}

	/**
	 * Elementor widget name (`wcf--title`) => dashboard slug (`animated-title`).
	 *
	 * There is no declared mapping anywhere — `Plugin::$widget_element_keys` is
	 * built while widgets REGISTER, which is useless here because the whole
	 * point is deciding what to register. And the two are genuinely different:
	 * `wcf--title` lives in `animated-title.php`, so deriving the slug by
	 * trimming the `wcf--` prefix silently misses a third of the catalogue —
	 * including `title` and `text`, the two most-used widgets in a demo.
	 *
	 * So this reads the same files the registrar would: for each configured
	 * slug, find `widgets/<slug>.php` or `widgets/<slug>/<slug>.php` in either
	 * plugin and pull the string `get_name()` returns. Cheap enough for a
	 * one-time, option-absent path, and it cannot drift from the source.
	 */
	private static function widget_name_to_slug_map(): array {
		if ( ! function_exists( 'wcf_get_config' ) ) {
			return [];
		}

		$config = wcf_get_config();
		$dirs   = [ WCF_ADDONS_PATH . 'widgets/' ];

		if ( defined( 'WCF_ADDONS_PRO_PATH' ) ) {
			$dirs[] = WCF_ADDONS_PRO_PATH . 'widgets/';
		}

		$map = [];

		foreach ( (array) ( $config['widgets']['elements'] ?? [] ) as $category ) {
			foreach ( array_keys( (array) ( $category['elements'] ?? [] ) ) as $slug ) {
				foreach ( $dirs as $dir ) {
					foreach ( [ "{$dir}{$slug}.php", "{$dir}{$slug}/{$slug}.php" ] as $file ) {
						if ( ! is_readable( $file ) ) {
							continue;
						}

						$source = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

						if ( preg_match( '/function\s+get_name\s*\(\s*\)[^{]*\{[^}]*return\s+[\'"](wcf--[a-z0-9-]+)[\'"]/s', $source, $m ) ) {
							$map[ $m[1] ] = $slug;
						}

						break 2;
					}
				}
			}
		}

		return $map;
	}

	/**
	 * Is any v3 widget actually used anywhere on this site?
	 *
	 * The honest signal, as opposed to the post-count heuristic: v3 widgets save
	 * as `"widgetType":"wcf--<slug>"` inside `_elementor_data`, so one LIKE finds
	 * them wherever they are. An imported demo or a migrated database looks
	 * "fresh" by post count but lights this up immediately.
	 *
	 * Cached for an hour — it runs on admin_init, and the answer only changes
	 * when someone imports or builds something.
	 */
	public static function has_v3_usage(): bool {
		$cached = get_transient( 'aae_v3_usage' );

		if ( false !== $cached ) {
			return '1' === $cached;
		}

		global $wpdb;

		$found = (bool) $wpdb->get_var(
			"SELECT 1 FROM {$wpdb->postmeta}
			 WHERE meta_key = '_elementor_data'
			   AND meta_value LIKE '%\"widgetType\":\"wcf--%'
			 LIMIT 1"
		);

		// The Kit counts as usage too — someone configured v3 chrome by hand.
		if ( ! $found && did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) ) {
			$found = self::kit_has_legacy_chrome();
		}

		set_transient( 'aae_v3_usage', $found ? '1' : '0', HOUR_IN_SECONDS );

		return $found;
	}

	/**
	 * One-time, non-destructive import of a v3 preloader configuration.
	 *
	 * Detection alone leaves a real gap for existing users. Hide the old Site
	 * Settings tab and their preloader still RENDERS (rule 2) — but they can no
	 * longer EDIT it, and the new panel shows them defaults that look nothing
	 * like their site. That reads as "my settings are gone" even though nothing
	 * was deleted.
	 *
	 * Importing closes the gap in the only way that costs the user nothing:
	 * their exact preloader appears in the new screen, pre-filled, with no
	 * action required. And because the v4 side now reports the feature as
	 * enabled, the render bridge hands the preloader to v4 — same look, one
	 * owner, no double paint.
	 *
	 * The Kit is READ, never written. Turning the legacy switch back on
	 * therefore restores the old path byte for byte.
	 */
	/**
	 * Carry a v3 Smooth Scroller configuration into the v4 panel.
	 *
	 * Separate from import_from_kit() because smooth scroll never lived in the
	 * Kit — its v3 home is the `wcf_smooth_scroller` option, written by the
	 * Extensions screen and shaped for the runtime rather than for storage.
	 *
	 * Runs at bootstrap only, so it cannot overwrite anyone's later edits. The
	 * point is that a site already scrolling smoothly should not open this panel
	 * and find the feature reading "off" — the setting moved, the behaviour did
	 * not, and a panel that disagrees with the page is how support tickets start.
	 */
	public static function import_smooth_scroll_from_v3( array $settings ): array {
		$stored = get_option( 'wcf_smooth_scroller', '' );
		$v3     = is_string( $stored ) && '' !== $stored ? json_decode( $stored, true ) : null;

		if ( ! is_array( $v3 ) ) {
			return $settings;
		}

		$enabled = false;

		foreach ( self::SMOOTH_DEVICES as $device ) {
			$row = is_array( $v3[ $device ] ?? null ) ? $v3[ $device ] : null;

			// The pre-2025 shape had no per-device rows at all, just a single
			// `smooth` value plus a media query. Treat that as desktop-only,
			// which is what its `min-width: 768px` matchMedia amounted to.
			if ( ! $row ) {
				if ( isset( $v3['smooth'] ) && 'desktop' === $device ) {
					$settings['smooth_scroll']['devices'][ $device ] = [
						'enabled' => true,
						'level'   => (float) $v3['smooth'],
					];
					$enabled = true;
				}

				continue;
			}

			$settings['smooth_scroll']['devices'][ $device ] = [
				'enabled' => ! empty( $row['enabled'] ),
				'level'   => (float) ( $row['smotherLevel'] ?? 1.35 ),
			];

			$enabled = $enabled || ! empty( $row['enabled'] );
		}

		if ( ! $enabled ) {
			return $settings;
		}

		// Only claim the feature when the v3 EXTENSION was actually switched on.
		// Device rows can sit in the option from a since-disabled extension, and
		// importing those would turn smoothing on for a site that had turned it
		// off.
		if ( ! wcf_addons_get_settings( 'wcf_save_extensions', 'wcf-smooth-scroller' ) ) {
			return $settings;
		}

		$settings['smooth_scroll']['enable'] = true;

		if ( isset( $v3['disableInEditor'] ) ) {
			$settings['smooth_scroll']['disable_in_editor'] = ! empty( $v3['disableInEditor'] );
		}

		return $settings;
	}

	public static function import_from_kit( array $settings ): array {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return $settings;
		}

		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( ! $kit || ! $kit->get_id() ) {
			return $settings;
		}

		$saved = get_post_meta( $kit->get_id(), '_elementor_page_settings', true );
		if ( ! is_array( $saved ) ) {
			return $settings;
		}

		foreach ( self::schema() as $feature => $config ) {
			$enable_key = $config['fields']['enable']['kit'] ?? '';

			// Only import a feature the user actually switched on in v3.
			// Copying a disabled feature's leftover colours would silently
			// enable nothing while making the new panel look "configured".
			if ( '' === $enable_key || empty( $saved[ $enable_key ] ) ) {
				continue;
			}

			foreach ( $config['fields'] as $key => $field ) {
				$kit_key = $field['kit'] ?? '';

				if ( '' === $kit_key || ! isset( $saved[ $kit_key ] ) ) {
					continue;
				}

				$value = $saved[ $kit_key ];

				switch ( $field['type'] ) {
					case 'bool':
						$settings[ $feature ][ $key ] = ! empty( $value );
						break;

					case 'color':
						// Elementor colour controls hold a literal hex; a global
						// reference lives separately under __globals__, which we
						// deliberately don't chase — a literal is what the v3
						// renderer itself emitted.
						$hex = sanitize_hex_color( (string) $value );
						if ( $hex ) {
							$settings[ $feature ][ $key ] = [
								'mode'   => 'custom',
								'custom' => $hex,
								'global' => '',
							];
						}
						break;

					case 'size':
						if ( is_array( $value ) && isset( $value['size'] ) && is_numeric( $value['size'] ) ) {
							$settings[ $feature ][ $key ] = [
								'size' => 0 + $value['size'],
								'unit' => $value['unit'] ?? ( $field['unit'] ?? 'px' ),
							];
						}
						break;

					case 'enum':
						$options = self::options( (string) ( $field['options'] ?? '' ) );
						if ( is_scalar( $value ) && array_key_exists( (string) $value, $options ) ) {
							$settings[ $feature ][ $key ] = (string) $value;
						}
						break;
				}
			}
		}

		return $settings;
	}

	/**
	 * Should this site keep the v3 Site Settings tabs?
	 *
	 * Two signals, checked in order, because either one alone gets a real case
	 * wrong:
	 *
	 *   1. v3 chrome saved in the Kit -> legacy user, keep the tabs.
	 *   2. Otherwise, is this a fresh / under-construction site? Only then is it
	 *      safe to call someone a NEW user and hide the old screens outright.
	 *
	 * The second check exists because signal 1 has a blind spot: v3 popups are
	 * TEMPLATE-driven and the Popup tab only styles them, so an established site
	 * can be deep into using v3 popups while its Kit holds no chrome key at all.
	 * Hiding that user's Popup tab because of an empty Kit would be wrong, so an
	 * established site defaults to keeping the tabs and the dashboard switch is
	 * there to turn them off deliberately.
	 */
	public static function detect_legacy_usage(): bool {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			// Can't tell yet. Assume legacy: showing a settings screen nobody
			// needs is recoverable, hiding one that's in use isn't.
			return true;
		}

		if ( self::kit_has_legacy_chrome() ) {
			return true;
		}

		return ! self::is_fresh_site();
	}

	/**
	 * Does the Kit hold any saved v3 chrome setting?
	 *
	 * Reads the RAW `_elementor_page_settings` meta rather than
	 * get_settings_for_display(), which merges in control defaults and would
	 * report every fresh install as a legacy user.
	 */
	private static function kit_has_legacy_chrome(): bool {
		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( ! $kit || ! $kit->get_id() ) {
			return false;
		}

		$saved = get_post_meta( $kit->get_id(), '_elementor_page_settings', true );
		if ( ! is_array( $saved ) ) {
			return false;
		}

		foreach ( $saved as $key => $value ) {
			if ( ! preg_match( '/^wcf_(enable_)?(preloader|cursor|scroll_to_top|scroll_indicator|popup)/', (string) $key ) ) {
				continue;
			}

			if ( '' !== $value && null !== $value && [] !== $value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Does this look like a brand-new or still-under-construction site?
	 *
	 * Thresholds are deliberately low: the question is not "is this site small"
	 * but "has anyone actually built anything here yet". A site past all three
	 * is treated as established, and established sites keep their legacy tabs.
	 *
	 * Trashed and auto-draft entries are excluded — a bin full of deleted posts
	 * doesn't make a site established.
	 */
	public static function is_fresh_site(): bool {
		return self::count_posts( 'page' ) <= 1
			&& self::count_posts( 'post' ) < 3
			&& self::count_posts( 'attachment' ) < 4;
	}

	/** Non-trashed, non-auto-draft count for one post type. */
	private static function count_posts( string $post_type ): int {
		$counts = wp_count_posts( $post_type );

		if ( ! is_object( $counts ) ) {
			return 0;
		}

		$total = 0;

		foreach ( [ 'publish', 'future', 'draft', 'pending', 'private', 'inherit' ] as $status ) {
			$total += isset( $counts->$status ) ? (int) $counts->$status : 0;
		}

		return $total;
	}

	/* ---------------------------------------------------------------------
	 * Schema
	 * ------------------------------------------------------------------ */

	/**
	 * The preloader layouts, value => label.
	 *
	 * Deliberately duplicated from pro/inc/settings/wcf-preloader.php rather
	 * than imported: the dashboard is a FREE-plugin screen and has to render
	 * the list (locked, behind the PRO badge) on sites with no Pro installed.
	 */
	public static function preloader_layouts(): array {
		return [
			'whirlpool'          => __( 'Whirlpool', 'animation-addons-for-elementor' ),
			'floating-circles'   => __( 'Floating Circle', 'animation-addons-for-elementor' ),
			'eight-spinning'     => __( 'Eight Spinning', 'animation-addons-for-elementor' ),
			'double-torus'       => __( 'Double Torus', 'animation-addons-for-elementor' ),
			'tube-tunnel'        => __( 'Tube Tunnel', 'animation-addons-for-elementor' ),
			'speeding-wheel'     => __( 'Speeding Wheel', 'animation-addons-for-elementor' ),
			'loading'            => __( 'Loading', 'animation-addons-for-elementor' ),
			'dot-loading'        => __( 'Dot Loading', 'animation-addons-for-elementor' ),
			'fountainTextG'      => __( 'FountainTextG', 'animation-addons-for-elementor' ),
			'circle-loading'     => __( 'Circle Loading', 'animation-addons-for-elementor' ),
			'dot-circle-rotator' => __( 'Dot Circle Rotator', 'animation-addons-for-elementor' ),
			'bubblingG'          => __( 'BubblingG', 'animation-addons-for-elementor' ),
			'coffee'             => __( 'Coffee', 'animation-addons-for-elementor' ),
			'orbit-loading'      => __( 'Orbit Loading', 'animation-addons-for-elementor' ),
			'battery'            => __( 'Battery', 'animation-addons-for-elementor' ),
			'equalizer'          => __( 'Equalizer', 'animation-addons-for-elementor' ),
			'square-swapping'    => __( 'Square Swapping', 'animation-addons-for-elementor' ),
			'jackhammer'         => __( 'Jackhammer', 'animation-addons-for-elementor' ),
		];
	}

	/**
	 * A colour field: either a literal hex, or a reference to one of the Kit's
	 * global colours (the "Custom Color / Global Color" pair in the panel).
	 */
	private static function color_defaults( string $fallback ): array {
		return [
			'mode'   => 'custom',
			'custom' => $fallback,
			'global' => '',
		];
	}

	/**
	 * THE single source of truth for every v4 chrome feature.
	 *
	 * Defaults, sanitising, the Kit import and Pro's render bridge are all
	 * generated from this — there is no per-feature sanitiser or importer to
	 * keep in sync, and the dashboard renders its panels from the same schema
	 * shipped as JSON. Adding a feature means adding an entry here and nothing
	 * else on the server.
	 *
	 * `kit` is the v3 control id the value maps to, which is what makes both
	 * directions work: importing an existing configuration in, and handing the
	 * feature to Pro's existing renderer on the way out.
	 *
	 * Field types: bool (v3 SWITCHER, 'yes'/''), enum (SELECT), color (COLOR,
	 * with the custom/global pair on top), size (SLIDER, {size,unit}).
	 */
	public static function schema(): array {
		$schema = self::feature_schema();

		// Every feature gets display conditions, injected rather than repeated.
		// They are the same control with the same semantics everywhere, and a
		// hand-written copy per feature is five chances for one of them to drift
		// — different label, different default, a missed sanitiser case.
		//
		// Appended last so it reads as the closing question of each panel
		// ("…and where?") rather than interrupting the feature's own options.
		foreach ( $schema as $feature => $config ) {
			if ( isset( $config['fields'][ self::CONDITIONS_KEY ] ) ) {
				continue;
			}

			$schema[ $feature ]['fields'][ self::CONDITIONS_KEY ] = self::display_field();
			$schema[ $feature ]['width'] = 'wide';
		}

		return $schema;
	}

	/**
	 * The shared display-conditions field.
	 *
	 * @param string $help Feature-specific explanation, or '' for the default.
	 */
	public static function display_field( string $help = '' ): array {
		return [
			'type'      => 'conditions',
			'locations' => 'display_locations',
			'default'   => [ [ 'action' => 'include', 'location' => 'entire' ] ],
			'label'     => __( 'Display Conditions', 'animation-addons-for-elementor' ),
			'help'      => $help !== '' ? $help : __(
				'Where this runs. Exclude always beats Include, so "Entire site" plus an exclusion is the usual shape.',
				'animation-addons-for-elementor'
			),
		];
	}

	/** The features themselves, before display conditions are injected. */
	private static function feature_schema(): array {
		return [
			/*
			 * Smooth Scroll has no `kit` keys, because unlike the other four it
			 * was never an Elementor Site Settings tab — its v3 form is the
			 * "Smooth Scroller" EXTENSION, stored in the `wcf_smooth_scroller`
			 * option and read straight off `WCF_ADDONS_JS.smoothScroller` by
			 * wcf-addons-ex.js. So the handover is a different shape: Pro
			 * overrides that localized payload rather than the Kit. Fields with
			 * no `kit` are skipped by the Bridge and by import_from_kit(), which
			 * is exactly what should happen here.
			 */
			'smooth_scroll' => [
				'label' => __( 'Smooth Scroll', 'animation-addons-for-elementor' ),

				// Display conditions need room to breathe; the other panels are
				// a single column of narrow controls.
				'width' => 'wide',

				'fields' => [
					'enable'  => [ 'type' => 'bool', 'default' => false, 'label' => __( 'Smooth Scroll', 'animation-addons-for-elementor' ) ],
					'devices' => [
						'type'    => 'devices',
						'devices' => 'smooth_devices',
						'min'     => 0.1,
						'max'     => 3,
						'step'    => 0.05,
						'default' => 1.35,
						'label'   => __( 'Smoothness per device', 'animation-addons-for-elementor' ),
						'help'    => __( 'Seconds the scroll takes to catch up. Higher is smoother and laggier. Touch devices are off by default — smoothing fights native momentum scrolling.', 'animation-addons-for-elementor' ),
					],
					// Declared here rather than left to the injection in schema()
					// only so it can carry wording specific to this feature —
					// the shape and the semantics are identical.
					self::CONDITIONS_KEY => self::display_field(
						__( 'Where the smooth scroller loads. Excluded pages do not download ScrollSmoother at all. Exclude always beats Include, so "Entire site" plus an exclusion is the usual shape.', 'animation-addons-for-elementor' )
					),
					'disable_in_editor' => [
						'type'    => 'bool',
						'default' => true,
						'label'   => __( 'Disable inside the Elementor editor', 'animation-addons-for-elementor' ),
					],
				],
			],

			'preloader' => [
				'label'  => __( 'Preloader', 'animation-addons-for-elementor' ),
				'fields' => [
					'enable'     => [ 'type' => 'bool',  'kit' => 'wcf_enable_preloader', 'default' => false, 'label' => __( 'Preloader', 'animation-addons-for-elementor' ) ],
					'layout'     => [ 'type' => 'enum',  'kit' => 'wcf_preloader_layout', 'default' => 'orbit-loading', 'options' => 'preloader_layouts', 'label' => __( 'Layout', 'animation-addons-for-elementor' ) ],
					'background' => [ 'type' => 'color', 'kit' => 'wcf_preloader_background', 'default' => '#ffffff', 'label' => __( 'Background Color', 'animation-addons-for-elementor' ) ],
					'primary'    => [ 'type' => 'color', 'kit' => 'wcf_preloader_color', 'default' => '#4a5fd9', 'label' => __( 'Primary Color', 'animation-addons-for-elementor' ) ],
					'secondary'  => [ 'type' => 'color', 'kit' => 'wcf_preloader_color2', 'default' => '#e94ec3', 'label' => __( 'Secondary Color', 'animation-addons-for-elementor' ) ],
				],
			],

			'cursor' => [
				'label'  => __( 'Cursor', 'animation-addons-for-elementor' ),
				'fields' => [
					'enable'         => [ 'type' => 'bool',  'kit' => 'wcf_enable_cursor', 'default' => false, 'label' => __( 'Cursor', 'animation-addons-for-elementor' ) ],
					'size'           => [ 'type' => 'size',  'kit' => 'wcf_cursor_size', 'default' => 8, 'unit' => 'px', 'min' => 0, 'max' => 100, 'label' => __( 'Cursor Size', 'animation-addons-for-elementor' ) ],
					'follower_size'  => [ 'type' => 'size',  'kit' => 'wcf_cursor_follower_size', 'default' => 36, 'unit' => 'px', 'min' => 0, 'max' => 200, 'label' => __( 'Cursor Follower Size', 'animation-addons-for-elementor' ) ],
					'color'          => [ 'type' => 'color', 'kit' => 'wcf_cursor_color', 'default' => '#000000', 'label' => __( 'Cursor Color', 'animation-addons-for-elementor' ) ],
					'follower_color' => [ 'type' => 'color', 'kit' => 'wcf_cursor_follower_color', 'default' => '#000000', 'label' => __( 'Cursor Follower Color', 'animation-addons-for-elementor' ) ],
					'blend_mode'     => [ 'type' => 'enum',  'kit' => 'wcf_cursor_blend_mode', 'default' => '', 'options' => 'blend_modes', 'label' => __( 'Blend Mode', 'animation-addons-for-elementor' ) ],
					'breakpoint'     => [ 'type' => 'enum',  'kit' => 'wcf_cursor_breakpoint', 'default' => 'mobile', 'options' => 'breakpoints', 'label' => __( 'Breakpoint', 'animation-addons-for-elementor' ) ],
				],
			],

			'scroll_to_top' => [
				'label'  => __( 'Scroll to Top', 'animation-addons-for-elementor' ),

				/*
				 * Kit keys the v3 renderer READS but the v4 panel does not
				 * offer. Written only when this feature is claimed and the Kit
				 * has nothing there.
				 *
				 * Needed because global-elements.php reads the Kit through
				 * `$kit->get_settings()` — RAW, so Elementor's own control
				 * defaults are never merged in. A v4 user enables scroll-to-top
				 * without ever opening the v3 tab, so `scroll_to_icon` is
				 * guaranteed absent and the button renders with no icon at all
				 * (plus an undefined-key warning on every page load). The value
				 * mirrors the v3 control's declared default.
				 */
				'kit_defaults' => [
					'scroll_to_icon' => [ 'value' => 'fas fa-arrow-up', 'library' => 'fa-solid' ],
				],

				'fields' => [
					'enable'           => [ 'type' => 'bool',  'kit' => 'wcf_enable_scroll_to_top', 'default' => false, 'label' => __( 'Scroll to Top', 'animation-addons-for-elementor' ) ],
					'layout'           => [ 'type' => 'enum',  'kit' => 'wcf_scroll_to_top_layout', 'default' => '', 'options' => 'scroll_to_top_layouts', 'label' => __( 'Layout', 'animation-addons-for-elementor' ) ],
					'position'         => [ 'type' => 'enum',  'kit' => 'wcf_scroll_to_top_position', 'default' => 'bottom-right', 'options' => 'corner_positions', 'label' => __( 'Position', 'animation-addons-for-elementor' ) ],
					'icon_size'        => [ 'type' => 'size',  'kit' => 'wcf_scroll_to_top_icon_size', 'default' => 16, 'unit' => 'px', 'min' => 0, 'max' => 100, 'label' => __( 'Icon Size', 'animation-addons-for-elementor' ) ],
					'width'            => [ 'type' => 'size',  'kit' => 'wcf_scroll_to_top_width', 'default' => 45, 'unit' => 'px', 'min' => 0, 'max' => 300, 'label' => __( 'Width', 'animation-addons-for-elementor' ) ],
					'height'           => [ 'type' => 'size',  'kit' => 'wcf_scroll_to_top_height', 'default' => 45, 'unit' => 'px', 'min' => 0, 'max' => 300, 'label' => __( 'Height', 'animation-addons-for-elementor' ) ],
					'border_radius'    => [ 'type' => 'size',  'kit' => 'wcf_scroll_to_top_border_radius', 'default' => 50, 'unit' => '%', 'min' => 0, 'max' => 100, 'label' => __( 'Border Radius', 'animation-addons-for-elementor' ) ],
					'z_index'          => [ 'type' => 'size',  'kit' => 'wcf_scroll_to_top_z_index', 'default' => 99, 'unit' => 'px', 'min' => 0, 'max' => 99999, 'label' => __( 'Z Index', 'animation-addons-for-elementor' ) ],
					'progress_color'   => [ 'type' => 'color', 'kit' => 'wcf_scroll_to_top_progress_color', 'default' => '#000000', 'label' => __( 'Progress Color', 'animation-addons-for-elementor' ) ],
					'icon_color'       => [ 'type' => 'color', 'kit' => 'wcf_scroll_to_top_icon_color', 'default' => '#ffffff', 'label' => __( 'Icon Color', 'animation-addons-for-elementor' ) ],
					'bg_color'         => [ 'type' => 'color', 'kit' => 'wcf_scroll_to_top_bg_color', 'default' => '#000000', 'label' => __( 'Background Color', 'animation-addons-for-elementor' ) ],
					'icon_hover_color' => [ 'type' => 'color', 'kit' => 'wcf_scroll_to_top_icon_hover_color', 'default' => '#ffffff', 'label' => __( 'Icon Color (Hover)', 'animation-addons-for-elementor' ) ],
					'hover_bg_color'   => [ 'type' => 'color', 'kit' => 'wcf_scroll_to_top_hover_bg_color', 'default' => '#000000', 'label' => __( 'Background Color (Hover)', 'animation-addons-for-elementor' ) ],
					'blend_mode'       => [ 'type' => 'enum',  'kit' => 'wcf_scroll_to_top_blend_mode', 'default' => 'normal', 'options' => 'blend_modes_with_normal', 'label' => __( 'Blend Mode', 'animation-addons-for-elementor' ) ],
				],
			],

			'scroll_indicator' => [
				'label'  => __( 'Scroll Indicator', 'animation-addons-for-elementor' ),
				'fields' => [
					'enable'     => [ 'type' => 'bool',  'kit' => 'wcf_enable_scroll_indicator', 'default' => false, 'label' => __( 'Scroll Indicator', 'animation-addons-for-elementor' ) ],
					'display'    => [ 'type' => 'enum',  'kit' => 'wcf_scroll_indicator_display', 'default' => 'entire-website', 'options' => 'indicator_display', 'label' => __( 'Display', 'animation-addons-for-elementor' ) ],
					'position'   => [ 'type' => 'enum',  'kit' => 'wcf_scroll_indicator_position', 'default' => 'top', 'options' => 'edge_positions', 'label' => __( 'Position', 'animation-addons-for-elementor' ) ],
					'height'     => [ 'type' => 'size',  'kit' => 'wcf_scroll_indicator_height', 'default' => 5, 'unit' => 'px', 'min' => 0, 'max' => 50, 'label' => __( 'Height', 'animation-addons-for-elementor' ) ],
					// `number`, not `size`: this one v3 control is a NUMBER while
					// its scroll-to-top twin is a SLIDER, and the renderer
					// interpolates it straight into CSS. Sending {size,unit}
					// emitted `z-index: Array` plus an Array-to-string warning.
					'z_index'    => [ 'type' => 'number', 'kit' => 'wcf_scroll_indicator_z_index', 'default' => 99, 'min' => 0, 'max' => 99999, 'label' => __( 'Z-index', 'animation-addons-for-elementor' ) ],
					'background' => [ 'type' => 'color', 'kit' => 'wcf_scroll_indicator_background', 'default' => '#eeeeee', 'label' => __( 'Background Color', 'animation-addons-for-elementor' ) ],
					'color'      => [ 'type' => 'color', 'kit' => 'wcf_scroll_indicator_color', 'default' => '#000000', 'label' => __( 'Indicator Color', 'animation-addons-for-elementor' ) ],
				],
			],
		];
	}

	/**
	 * The schema with every `options` reference resolved to a real list, for
	 * shipping to the dashboard.
	 *
	 * The panel renders itself from this rather than from hand-written React
	 * per feature — so a field added to schema() appears in the UI with no JS
	 * change, and the control types, labels, ranges and option lists can never
	 * drift from what the sanitiser will accept.
	 */
	public static function schema_for_ui(): array {
		$out = [];

		foreach ( self::schema() as $feature => $config ) {
			$fields = [];

			foreach ( $config['fields'] as $key => $field ) {
				// `kit` is a server-side mapping detail; the UI has no use for it.
				unset( $field['kit'] );

				if ( 'enum' === $field['type'] ) {
					$field['options'] = self::options( (string) ( $field['options'] ?? '' ) );
				}

				// Same idea as `options`, for the two composite controls: the
				// panel must never carry its own copy of the device list or the
				// location list, or a new post type would show up in the
				// sanitiser and not in the UI.
				if ( 'devices' === $field['type'] ) {
					$field['devices'] = self::options( (string) ( $field['devices'] ?? '' ) );
				}

				if ( 'conditions' === $field['type'] ) {
					$field['locations'] = self::display_locations();
				}

				$fields[ $key ] = $field;
			}

			$out[ $feature ] = [
				'label'  => $config['label'],
				'fields' => $fields,
			];

			if ( ! empty( $config['width'] ) ) {
				$out[ $feature ]['width'] = $config['width'];
			}
		}

		return $out;
	}

	/** Named option lists referenced by `options` in the schema. */
	public static function options( string $name ): array {
		switch ( $name ) {
			case 'preloader_layouts':
				return self::preloader_layouts();

			case 'blend_modes':
			case 'blend_modes_with_normal':
				$modes = [
					'multiply'    => __( 'Multiply', 'animation-addons-for-elementor' ),
					'screen'      => __( 'Screen', 'animation-addons-for-elementor' ),
					'overlay'     => __( 'Overlay', 'animation-addons-for-elementor' ),
					'darken'      => __( 'Darken', 'animation-addons-for-elementor' ),
					'lighten'     => __( 'Lighten', 'animation-addons-for-elementor' ),
					'color-dodge' => __( 'Color Dodge', 'animation-addons-for-elementor' ),
					'saturation'  => __( 'Saturation', 'animation-addons-for-elementor' ),
					'color'       => __( 'Color', 'animation-addons-for-elementor' ),
					'difference'  => __( 'Difference', 'animation-addons-for-elementor' ),
					'exclusion'   => __( 'Exclusion', 'animation-addons-for-elementor' ),
					'hue'         => __( 'Hue', 'animation-addons-for-elementor' ),
					'luminosity'  => __( 'Luminosity', 'animation-addons-for-elementor' ),
				];

				return 'blend_modes_with_normal' === $name
					? array_merge( [ 'normal' => __( 'Normal', 'animation-addons-for-elementor' ) ], $modes )
					: array_merge( [ '' => __( 'Default', 'animation-addons-for-elementor' ) ], $modes );

			case 'scroll_to_top_layouts':
				return [
					''       => __( 'Default', 'animation-addons-for-elementor' ),
					'circle' => __( 'Progress Circle', 'animation-addons-for-elementor' ),
				];

			case 'corner_positions':
				return [
					'bottom-left'  => __( 'Bottom Left', 'animation-addons-for-elementor' ),
					'bottom-right' => __( 'Bottom Right', 'animation-addons-for-elementor' ),
				];

			case 'edge_positions':
				return [
					'top'    => __( 'Top', 'animation-addons-for-elementor' ),
					'bottom' => __( 'Bottom', 'animation-addons-for-elementor' ),
				];

			case 'indicator_display':
				return [
					'entire-website'        => __( 'Entire Website', 'animation-addons-for-elementor' ),
					'specific-pages'        => __( 'Specific Pages', 'animation-addons-for-elementor' ),
					'specific-s-post-types' => __( 'Specific Post Types Singular', 'animation-addons-for-elementor' ),
				];

			case 'breakpoints':
				return self::breakpoint_options();

			case 'smooth_devices':
				return [
					'desktop' => __( 'Desktop', 'animation-addons-for-elementor' ),
					'laptop'  => __( 'Laptop', 'animation-addons-for-elementor' ),
					'tablet'  => __( 'Tablet', 'animation-addons-for-elementor' ),
					'mobile'  => __( 'Mobile', 'animation-addons-for-elementor' ),
				];

			case 'display_locations':
				return self::display_locations();
		}

		return [];
	}

	/* ---------------------------------------------------------------------
	 * Display conditions
	 *
	 * Type-level only, deliberately: every location below answers with a
	 * WordPress conditional tag, so evaluating a rule set costs nothing and
	 * needs no database lookup. Adding "this specific page" would mean storing
	 * post IDs, and an ID that is later deleted or replaced by an import turns
	 * into a rule that silently stops matching.
	 * ------------------------------------------------------------------ */

	/**
	 * Every location a rule can name, as slug => label.
	 *
	 * Post types are read live rather than listed, so a CPT registered by the
	 * theme or another plugin is targetable without a code change here.
	 */
	public static function display_locations(): array {
		$locations = [
			'entire'           => __( 'Entire site', 'animation-addons-for-elementor' ),
			'specific'         => __( 'Specific pages…', 'animation-addons-for-elementor' ),
			'front'            => __( 'Front page', 'animation-addons-for-elementor' ),
			'blog'             => __( 'Blog / posts page', 'animation-addons-for-elementor' ),
			'search'           => __( 'Search results', 'animation-addons-for-elementor' ),
			'404'              => __( '404 page', 'animation-addons-for-elementor' ),
			'singular'         => __( 'All singular content', 'animation-addons-for-elementor' ),
			'archive'          => __( 'All archives', 'animation-addons-for-elementor' ),
			'archive_category' => __( 'Category archive', 'animation-addons-for-elementor' ),
			'archive_tag'      => __( 'Tag archive', 'animation-addons-for-elementor' ),
			'archive_author'   => __( 'Author archive', 'animation-addons-for-elementor' ),
			'archive_date'     => __( 'Date archive', 'animation-addons-for-elementor' ),
		];

		foreach ( self::public_post_types() as $name => $object ) {
			/* translators: %s: post type label. */
			$locations[ 'singular_' . $name ] = sprintf( __( 'Single: %s', 'animation-addons-for-elementor' ), $object->labels->singular_name );

			if ( ! empty( $object->has_archive ) ) {
				/* translators: %s: post type label. */
				$locations[ 'archive_' . $name ] = sprintf( __( 'Archive: %s', 'animation-addons-for-elementor' ), $object->labels->name );
			}
		}

		return $locations;
	}

	/** Public post types worth targeting. Attachments are noise here. */
	private static function public_post_types(): array {
		$types = get_post_types( [ 'public' => true ], 'objects' );

		unset( $types['attachment'] );

		return is_array( $types ) ? $types : [];
	}

	/**
	 * Does the current request satisfy this rule set?
	 *
	 * Exclude beats include, always. An exclude-only list therefore reads as
	 * "everywhere except these", which is what someone writing a single Exclude
	 * row means — treating a missing Include as "nowhere" would make that rule
	 * set silently disable the feature everywhere.
	 *
	 * An empty rule set is "everywhere": the default is a single
	 * include-entire row, so empty can only mean the sanitiser rejected
	 * everything, and failing open matches how the feature behaved before
	 * conditions existed.
	 */
	public static function display_matches( array $rules ): bool {
		if ( ! $rules ) {
			return true;
		}

		$has_include = false;
		$included    = false;

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['location'] ) ) {
				continue;
			}

			$match = self::location_matches( (string) $rule['location'], $rule );

			if ( 'exclude' === ( $rule['action'] ?? 'include' ) ) {
				if ( $match ) {
					return false;
				}

				continue;
			}

			$has_include = true;
			$included    = $included || $match;
		}

		return $has_include ? $included : true;
	}

	/**
	 * One location slug against the current query.
	 *
	 * @param string $location Location slug.
	 * @param array  $rule     The whole rule — `specific` carries its ids there.
	 */
	private static function location_matches( string $location, array $rule = [] ): bool {
		switch ( $location ) {
			case 'entire':
				return true;

			case 'specific':
				$ids = array_map( 'absint', (array) ( $rule['ids'] ?? [] ) );

				// No ids is not "every page" — it is an unfinished rule. Matching
				// nothing keeps a half-built exclusion from taking the whole
				// site down while someone is still picking pages.
				if ( ! $ids ) {
					return false;
				}

				return in_array( (int) get_queried_object_id(), $ids, true );

			case 'front':
				return is_front_page();

			case 'blog':
				return is_home();

			case 'search':
				return is_search();

			case '404':
				return is_404();

			case 'singular':
				return is_singular();

			case 'archive':
				return is_archive();

			case 'archive_category':
				return is_category();

			case 'archive_tag':
				return is_tag();

			case 'archive_author':
				return is_author();

			case 'archive_date':
				return is_date();
		}

		if ( 0 === strpos( $location, 'singular_' ) ) {
			return is_singular( substr( $location, 9 ) );
		}

		if ( 0 === strpos( $location, 'archive_' ) ) {
			return is_post_type_archive( substr( $location, 8 ) );
		}

		// An unknown slug matches nothing rather than everything — a stored rule
		// for a post type that has since disappeared must not start including
		// the whole site.
		return false;
	}

	/**
	 * The smooth scroller payload for THIS request, or null when it should not
	 * run here — feature off, or the display conditions exclude this page.
	 *
	 * Returning null is not enough to stop anything on its own. `WCF_ADDONS_JS.
	 * smoothScroller === null` makes wcf-addons-ex.js fall through to its own
	 * defaults and create a smoother anyway; the actual gate is Pro declining to
	 * enqueue the `ScrollSmoother` library. Both are needed, and Pro drives both
	 * off this one method so they cannot disagree.
	 */
	public static function smooth_scroll_payload(): ?array {
		if ( ! self::is_feature_active( 'smooth_scroll' ) ) {
			return null;
		}

		$config = self::feature( 'smooth_scroll' );

		$devices = is_array( $config['devices'] ?? null ) ? $config['devices'] : [];
		$payload = [];

		foreach ( self::SMOOTH_DEVICES as $device ) {
			$payload[ $device ] = [
				'enabled' => ! empty( $devices[ $device ]['enabled'] ),

				// `smotherLevel`, not `smoothLevel`. The misspelling shipped in
				// v3 and is what the runtime reads; correcting it here would
				// simply stop the value arriving. The v4 option stores the sane
				// name and it is translated at exactly this one point.
				'smotherLevel' => (float) ( $devices[ $device ]['level'] ?? 1.35 ),
			];
		}

		$payload['disableInEditor'] = ! empty( $config['disable_in_editor'] );

		return $payload;
	}

	/**
	 * Breakpoints, built from Elementor's own active list so the choice matches
	 * whatever the site actually has configured (v3 does the same). Falls back
	 * to the stock set when Elementor can't be reached.
	 */
	private static function breakpoint_options(): array {
		$fallback = [
			'mobile' => __( 'Mobile', 'animation-addons-for-elementor' ),
			'tablet' => __( 'Tablet', 'animation-addons-for-elementor' ),
		];

		// `elementor/loaded` fires while Elementor is still wiring itself up, so
		// the action having run is NOT proof that its managers exist. Reading
		// settings any earlier than `init` — which Pro now does, to decide
		// whether to register the smooth-scroll page control — lands exactly in
		// that window and fataled on a null `breakpoints`.
		if ( ! did_action( 'elementor/loaded' )
			|| ! class_exists( '\Elementor\Plugin' )
			|| ! isset( \Elementor\Plugin::$instance->breakpoints ) ) {
			return $fallback;
		}

		$breakpoints = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();

		if ( ! is_array( $breakpoints ) || ! $breakpoints ) {
			return $fallback;
		}

		$out = [];
		foreach ( $breakpoints as $key => $breakpoint ) {
			$out[ (string) $key ] = method_exists( $breakpoint, 'get_label' )
				? $breakpoint->get_label()
				: (string) $key;
		}

		return $out;
	}

	public static function defaults(): array {
		$defaults = [];

		foreach ( self::schema() as $feature => $config ) {
			foreach ( $config['fields'] as $key => $field ) {
				$defaults[ $feature ][ $key ] = self::field_default( $field );
			}
		}

		// Placeholder only. The real per-site value is decided by
		// detect_legacy_usage() in maybe_bootstrap(); this is what a
		// sanitize() call falls back to when nothing is stored yet.
		$defaults['legacy_v3'] = true;

		return $defaults;
	}

	/** The stored shape of one field's default. */
	private static function field_default( array $field ) {
		switch ( $field['type'] ) {
			case 'color':
				return self::color_defaults( (string) $field['default'] );

			case 'size':
				return [
					'size' => $field['default'],
					'unit' => $field['unit'] ?? 'px',
				];

			case 'bool':
				return (bool) $field['default'];

			case 'devices':
				$out = [];

				foreach ( self::SMOOTH_DEVICES as $device ) {
					$out[ $device ] = [
						// Touch is off by default. Smoothing there fights the
						// platform's own momentum scrolling, and the result reads
						// as lag rather than polish.
						'enabled' => in_array( $device, [ 'desktop', 'laptop' ], true ),
						'level'   => (float) $field['default'],
					];
				}

				return $out;

			case 'conditions':
				return $field['default'];
		}

		return $field['default'];
	}

	/* ---------------------------------------------------------------------
	 * Read
	 * ------------------------------------------------------------------ */

	/** Drop the request-level cache; the next get() re-reads the option. */
	public static function flush_cache(): void {
		self::$cache = null;
	}

	/**
	 * Hide the v3 (`wcf--*`) widgets from the Elementor panel when the legacy
	 * surface is switched off.
	 *
	 * CLIENT-SIDE ON PURPOSE, and the only way this can be done at all. Panel
	 * visibility is decided entirely in the editor JS — `shouldAddWidget()`
	 * reads `widget.show_in_panel` off `elementor.widgetsCache`, and Elementor's
	 * own code hides deprecated widgets by flipping exactly that flag. There is
	 * no PHP seam: `show_in_panel()` is read once in
	 * Widget_Base::get_initial_config() with no filter, and the configs reach
	 * the editor over AJAX whose response has no filter either. See the DANGER
	 * box in CLAUDE.md.
	 *
	 * Registration is deliberately untouched. An unregistered widget renders
	 * NOTHING on pages that already use it; this only changes what the panel
	 * lists, so existing pages are byte-for-byte unaffected.
	 */
	public static function hide_legacy_widgets_in_panel(): void {
		if ( self::legacy_v3_enabled() ) {
			return;
		}

		/*
		 * addWidgetsCache() is WRAPPED rather than hooked to an event because
		 * the cache is filled from an AJAX success callback (requestWidgetsConfig)
		 * that fires no action afterwards — so there is no moment to listen for.
		 * Wrapping runs in the same tick as every write (boot, refreshWidgets,
		 * and the locale pass all funnel through it), which means nothing can
		 * read an un-hidden entry in between.
		 */
		$js = <<<'JS'
( function () {
	if ( ! window.elementor || 'function' !== typeof elementor.addWidgetsCache ) {
		return;
	}

	var PREFIX = 'wcf--';

	function hideLegacyWidgets() {
		var cache = elementor.widgetsCache;

		if ( ! cache ) {
			return;
		}

		Object.keys( cache ).forEach( function ( name ) {
			if ( 0 === name.indexOf( PREFIX ) && cache[ name ] ) {
				cache[ name ].show_in_panel = false;
			}
		} );
	}

	var addWidgetsCache = elementor.addWidgetsCache;

	elementor.addWidgetsCache = function () {
		addWidgetsCache.apply( elementor, arguments );
		hideLegacyWidgets();
	};

	// Anything already cached before this ran.
	hideLegacyWidgets();
}() );
JS;

		wp_add_inline_script( 'elementor-editor', $js );
	}

	/** The full, sanitized settings array (defaults merged in). */
	public static function get(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$saved = get_option( self::OPTION_NAME, false );

		if ( ! is_array( $saved ) ) {
			// Nothing stored yet (front-end hit before the first admin load).
			// Answer from live detection without writing — maybe_bootstrap()
			// persists the same decision on the next admin request.
			$settings = self::defaults();
			$settings['legacy_v3'] = self::detect_legacy_usage();
			self::$cache = $settings;

			return self::$cache;
		}

		self::$cache = self::sanitize( $saved );

		return self::$cache;
	}

	/** One feature's settings, or [] for a feature that has no schema yet. */
	public static function feature( string $feature ): array {
		$all = self::get();

		return isset( $all[ $feature ] ) && is_array( $all[ $feature ] ) ? $all[ $feature ] : [];
	}

	/**
	 * Is the v4 version of `$feature` switched on?
	 *
	 * This is the ONLY thing Pro's v3 renderer should test before standing
	 * down. Keep it cheap and never fatal — it runs on every front-end request,
	 * possibly before this class is loaded on an older free plugin, which is
	 * why Pro guards the call with class_exists().
	 */
	public static function is_feature_enabled( string $feature ): bool {
		$config = self::feature( $feature );

		return ! empty( $config['enable'] );
	}

	/**
	 * Is `$feature` switched on AND allowed on the CURRENT request?
	 *
	 * The distinction between this and is_feature_enabled() is the whole point
	 * of display conditions, and getting it backwards is subtle enough to be
	 * worth spelling out:
	 *
	 *   - is_feature_enabled() — "has the v4 side taken ownership of this?"
	 *     Site-level. It is what tells Pro's v3 renderer to stand down, and it
	 *     must stay true even on pages the conditions exclude. Otherwise
	 *     excluding a page hands it straight back to the v3 settings, and the
	 *     preloader you just switched off on the 404 page reappears there.
	 *
	 *   - is_feature_active() — "should it actually run here?" Request-level.
	 *     This is what a renderer or an enqueue should ask.
	 *
	 * Cheap: every location resolves to a WordPress conditional tag, no queries.
	 */
	public static function is_feature_active( string $feature ): bool {
		if ( ! self::is_feature_enabled( $feature ) ) {
			return false;
		}

		$config = self::feature( $feature );

		return self::display_matches( (array) ( $config[ self::CONDITIONS_KEY ] ?? [] ) );
	}

	/** Should the v3 Elementor Site Settings tabs still be registered? */
	public static function legacy_v3_enabled(): bool {
		$all = self::get();

		return ! empty( $all['legacy_v3'] );
	}

	/**
	 * Flatten a colour field to a CSS colour.
	 *
	 * A `global` reference is resolved against the active Kit's system colours;
	 * if the Kit, the colour, or Elementor itself is unavailable we fall back to
	 * the field's own custom value rather than emitting an empty declaration.
	 */
	public static function resolve_color( array $color ): string {
		$custom = isset( $color['custom'] ) ? (string) $color['custom'] : '';

		if ( 'global' !== ( $color['mode'] ?? 'custom' ) ) {
			return $custom;
		}

		$id = (string) ( $color['global'] ?? '' );
		if ( '' === $id ) {
			return $custom;
		}

		foreach ( self::global_colors() as $global ) {
			if ( ( $global['id'] ?? '' ) === $id ) {
				return (string) ( $global['value'] ?? $custom );
			}
		}

		return $custom;
	}

	/**
	 * The active Kit's global colours, as [ id, title, value ].
	 *
	 * Returns [] when Elementor is missing or the Kit can't be read — the
	 * dashboard then simply offers no global colours, which is correct rather
	 * than an error state.
	 */
	public static function global_colors(): array {
		if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return [];
		}

		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
		if ( ! $kit ) {
			return [];
		}

		$out = [];

		foreach ( [ 'system_colors', 'custom_colors' ] as $group ) {
			$colors = $kit->get_settings_for_display( $group );

			if ( ! is_array( $colors ) ) {
				continue;
			}

			foreach ( $colors as $color ) {
				if ( empty( $color['_id'] ) ) {
					continue;
				}

				$out[] = [
					'id'    => (string) $color['_id'],
					'title' => (string) ( $color['title'] ?? $color['_id'] ),
					'value' => (string) ( $color['color'] ?? '' ),
				];
			}
		}

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Write
	 * ------------------------------------------------------------------ */

	private static function sanitize_color( $raw, array $fallback ): array {
		if ( ! is_array( $raw ) ) {
			return $fallback;
		}

		$mode = ( 'global' === ( $raw['mode'] ?? '' ) ) ? 'global' : 'custom';

		// sanitize_hex_color() rejects rgba()/var() and anything malformed; a
		// rejected value falls back rather than writing an empty colour that
		// would render as "no colour at all".
		$custom = isset( $raw['custom'] ) ? sanitize_hex_color( (string) $raw['custom'] ) : null;

		return [
			'mode'   => $mode,
			'custom' => $custom ?: $fallback['custom'],
			'global' => isset( $raw['global'] ) ? sanitize_text_field( (string) $raw['global'] ) : '',
		];
	}

	/** A slider value: {size, unit}, clamped to the schema's own min/max. */
	private static function sanitize_size( $raw, array $field, array $fallback ): array {
		$size = is_array( $raw ) && isset( $raw['size'] ) ? $raw['size'] : null;

		if ( ! is_numeric( $size ) ) {
			return $fallback;
		}

		$size = (float) $size;

		if ( isset( $field['min'] ) ) {
			$size = max( (float) $field['min'], $size );
		}
		if ( isset( $field['max'] ) ) {
			$size = min( (float) $field['max'], $size );
		}

		return [
			// Keep whole numbers whole so the stored value round-trips to the
			// same string the slider showed.
			'size' => ( floor( $size ) === $size ) ? (int) $size : $size,
			'unit' => $field['unit'] ?? 'px',
		];
	}

	/** Clamp a plain numeric field to its schema range. */
	private static function sanitize_number( $raw, array $field, $fallback ) {
		if ( ! is_numeric( $raw ) ) {
			return $fallback;
		}

		$value = (float) $raw;

		if ( isset( $field['min'] ) ) {
			$value = max( (float) $field['min'], $value );
		}
		if ( isset( $field['max'] ) ) {
			$value = min( (float) $field['max'], $value );
		}

		return ( floor( $value ) === $value ) ? (int) $value : $value;
	}

	/**
	 * Per-device on/off plus smoothness.
	 *
	 * Rebuilt from SMOOTH_DEVICES rather than from what arrived, so the stored
	 * shape always has all four keys in a known order — the runtime indexes this
	 * by device name and a missing key would read as "off" for reasons nobody
	 * could see in the UI.
	 */
	private static function sanitize_devices( $raw, array $field, $fallback ): array {
		if ( ! is_array( $raw ) ) {
			return is_array( $fallback ) ? $fallback : [];
		}

		$min = (float) ( $field['min'] ?? 0.1 );
		$max = (float) ( $field['max'] ?? 3 );
		$out = [];

		foreach ( self::SMOOTH_DEVICES as $device ) {
			$incoming = is_array( $raw[ $device ] ?? null ) ? $raw[ $device ] : [];
			$level    = $incoming['level'] ?? ( $fallback[ $device ]['level'] ?? $field['default'] );

			$out[ $device ] = [
				'enabled' => ! empty( $incoming['enabled'] ),
				'level'   => min( $max, max( $min, is_numeric( $level ) ? (float) $level : (float) $field['default'] ) ),
			];
		}

		return $out;
	}

	/**
	 * Display-condition rules.
	 *
	 * Rows naming a location that no longer exists are DROPPED rather than
	 * kept — display_matches() treats an unknown location as "matches nothing",
	 * so keeping one would leave a rule that reads as meaningful in the UI and
	 * does nothing. Losing the row makes that visible.
	 *
	 * An empty result falls back to the default (include everywhere) instead of
	 * being stored as-is: an empty rule set is indistinguishable from "the user
	 * deleted every row", and silently disabling a feature is the worse of the
	 * two readings.
	 */
	public static function sanitize_conditions( $raw, $fallback ): array {
		if ( ! is_array( $raw ) ) {
			return is_array( $fallback ) ? $fallback : [];
		}

		$locations = self::display_locations();
		$out       = [];

		foreach ( $raw as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}

			$location = isset( $rule['location'] ) && is_scalar( $rule['location'] )
				? sanitize_text_field( (string) $rule['location'] )
				: '';

			if ( ! array_key_exists( $location, $locations ) ) {
				continue;
			}

			$action = 'exclude' === ( $rule['action'] ?? '' ) ? 'exclude' : 'include';
			$clean  = [ 'action' => $action, 'location' => $location ];

			if ( 'specific' === $location ) {
				$ids = array_values( array_unique( array_filter(
					array_map( 'absint', (array) ( $rule['ids'] ?? [] ) )
				) ) );

				// A "Specific pages" row with nothing picked is an unfinished
				// thought, not a rule. Dropping it is visible; storing it would
				// leave a row that reads as meaningful and matches nothing.
				if ( ! $ids ) {
					continue;
				}

				sort( $ids );
				$clean['ids'] = $ids;
			}

			// Same action + same location twice changes nothing, and a duplicate
			// row is confusing to look at. Specific rows are keyed by their ids
			// too, so two different page lists are two different rules.
			$key = $action . '|' . $location
				. ( isset( $clean['ids'] ) ? '|' . implode( ',', $clean['ids'] ) : '' );

			$out[ $key ] = $clean;
		}

		return $out ? array_values( $out ) : ( is_array( $fallback ) ? $fallback : [] );
	}

	public static function sanitize( array $raw ): array {
		$defaults = self::defaults();
		$clean    = [];

		foreach ( self::schema() as $feature => $config ) {
			$incoming = isset( $raw[ $feature ] ) && is_array( $raw[ $feature ] ) ? $raw[ $feature ] : [];

			foreach ( $config['fields'] as $key => $field ) {
				$value    = $incoming[ $key ] ?? null;
				$fallback = $defaults[ $feature ][ $key ];

				switch ( $field['type'] ) {
					case 'bool':
						$clean[ $feature ][ $key ] = ! empty( $value );
						break;

					case 'color':
						$clean[ $feature ][ $key ] = self::sanitize_color( $value, $fallback );
						break;

					case 'size':
						$clean[ $feature ][ $key ] = self::sanitize_size( $value, $field, $fallback );
						break;

					case 'number':
						// A bare scalar, for the v3 controls that are NUMBER
						// rather than SLIDER — the renderer drops these straight
						// into CSS, so a {size,unit} array would print as "Array".
						$clean[ $feature ][ $key ] = self::sanitize_number( $value, $field, $fallback );
						break;

					case 'devices':
						$clean[ $feature ][ $key ] = self::sanitize_devices( $value, $field, $fallback );
						break;

					case 'conditions':
						$clean[ $feature ][ $key ] = self::sanitize_conditions( $value, $fallback );
						break;

					case 'enum':
						$options = self::options( (string) ( $field['options'] ?? '' ) );
						$value   = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
						// An unknown option falls back rather than being stored —
						// otherwise a stale or hand-edited payload writes a value
						// the renderer has no branch for.
						$clean[ $feature ][ $key ] = array_key_exists( $value, $options ) ? $value : $fallback;
						break;

					default:
						$clean[ $feature ][ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $fallback;
				}
			}
		}

		return $clean + [
			// An absent key means this payload simply didn't carry the flag —
			// keep whatever the site already decided rather than resetting it.
			'legacy_v3' => array_key_exists( 'legacy_v3', $raw )
				? ! empty( $raw['legacy_v3'] )
				: $defaults['legacy_v3'],
			// Sticky: once a human has set the switch, it stays theirs.
			'legacy_v3_user_set' => ! empty( $raw['legacy_v3_user_set'] ),
		];
	}

	/* ---------------------------------------------------------------------
	 * AJAX (same contract as the widgets/extensions dashboard screens)
	 * ------------------------------------------------------------------ */

	private function guard(): void {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'Permission denied.', 'animation-addons-for-elementor' ) );
		}
	}

	/**
	 * Back the "Specific pages" picker: search by title, or resolve stored ids
	 * back to titles.
	 *
	 * Both directions are here because the panel needs both and they are the
	 * same query with a different WHERE. Without the resolve half, a saved rule
	 * reloads as a row of bare numbers.
	 */
	public function ajax_search_content(): void {
		$this->guard();

		$types = array_keys( get_post_types( [ 'public' => true ], 'names' ) );
		$types = array_values( array_diff( $types, [ 'attachment' ] ) );

		$args = [
			'post_type'           => $types,
			'post_status'         => [ 'publish', 'private', 'draft' ],
			'posts_per_page'      => 20,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'title',
			'order'               => 'ASC',
		];

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- guard() verified it.
		$ids = isset( $_POST['ids'] ) ? array_filter( array_map( 'absint', (array) $_POST['ids'] ) ) : [];

		if ( $ids ) {
			// Resolving a known set: return exactly those, however many.
			$args['post__in']       = $ids;
			$args['posts_per_page'] = count( $ids );
			$args['orderby']        = 'post__in';
		} else {
			$args['s'] = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		}
		// phpcs:enable

		$results = [];

		foreach ( get_posts( $args ) as $post ) {
			$type = get_post_type_object( $post->post_type );

			$results[] = [
				'id'    => $post->ID,
				'title' => $post->post_title !== '' ? $post->post_title : sprintf(
					/* translators: %d: post ID. */
					__( '(no title) #%d', 'animation-addons-for-elementor' ),
					$post->ID
				),
				'type'  => $type ? $type->labels->singular_name : $post->post_type,
			];
		}

		wp_send_json_success( [ 'results' => $results ] );
	}

	public function ajax_get(): void {
		$this->guard();

		wp_send_json_success( [
			'settings'      => self::get(),
			'schema'        => self::schema_for_ui(),
			'global_colors' => self::global_colors(),
			'has_pro'       => self::has_pro(),
		] );
	}

	public function ajax_save(): void {
		$this->guard();

		$raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '';

		// The payload arrives as a JSON string (nested arrays don't survive
		// urlencoded POST cleanly), so decode before sanitizing.
		$decoded = is_string( $raw ) ? json_decode( $raw, true ) : null;

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( esc_html__( 'Invalid data.', 'animation-addons-for-elementor' ) );
		}

		$previous = self::get();
		$clean    = self::sanitize( $decoded );

		// Only a real CHANGE to the switch counts as the user claiming it —
		// every panel save posts the whole object, so equality would mark it
		// claimed the first time someone saved a preloader colour.
		if ( array_key_exists( 'legacy_v3', $decoded )
			&& ! empty( $previous['legacy_v3'] ) !== ! empty( $decoded['legacy_v3'] ) ) {
			$clean['legacy_v3_user_set'] = true;
		} else {
			$clean['legacy_v3_user_set'] = ! empty( $previous['legacy_v3_user_set'] );
		}

		update_option( self::OPTION_NAME, $clean );
		self::$cache = $clean;

		wp_send_json_success( [
			'settings' => $clean,
			'message'  => esc_html__( 'Settings saved.', 'animation-addons-for-elementor' ),
		] );
	}

	/** Is the Pro plugin — which owns every renderer for these features — active? */
	public static function has_pro(): bool {
		return defined( 'WCF_ADDONS_PRO_VERSION' ) || defined( 'WCF_ADDONS_PRO_PATH' );
	}
}
