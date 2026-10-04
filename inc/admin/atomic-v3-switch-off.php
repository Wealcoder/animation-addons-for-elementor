<?php
namespace WCF_ADDONS\Admin\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WCF_ADDONS\AnimationSettings\Animation_Settings;

/**
 * After a V4 (atomic) starter TEMPLATE import, switch the V3 era off.
 *
 * A V4 demo is a whole site. Once it has landed, everything V3 that was on
 * before it is at best dead weight (the ~70 v3 widgets registering on every
 * request, every extension's control sections, the GSAP family enqueued for
 * nothing) and at worst paints OVER the demo: a v3 preloader, cursor,
 * scroll-to-top button or scroll indicator saved in the old Kit, and any v3
 * popup template whose display conditions still match, all keep rendering on
 * top of pages that were never designed with them. So a V4 template import
 * ends with V3 switched off, in every place V3 is switched on:
 *
 *   | what                          | where it lives                          | off means            |
 *   |-------------------------------|-----------------------------------------|----------------------|
 *   | v3 widgets                    | option `wcf_save_widgets` (slug => true) | empty array          |
 *   | v3 extensions                 | option `wcf_save_extensions`             | empty array          |
 *   | preloader / cursor / to-top / | the ACTIVE Elementor Kit,                | key cleared          |
 *   |   scroll indicator            |   `wcf_enable_*` page settings           |                      |
 *   | v3 popups                     | published `wcf-addons-template` posts   | status → draft       |
 *   |   (template type `popup`)     |   with `<CPT_META>_type` = popup        |                      |
 *   | v3 Site Settings tabs         | `aae_animation_settings[legacy_v3]`      | false                |
 *
 * An EMPTY array, never delete_option(): "absent" means "never decided" to
 * maybe_enable_used_v3_widgets(), which would then re-enable widgets on the
 * next admin_init. An empty array is the same definite "off" the dashboard's
 * master Enable-All switch writes.
 *
 * THE ONE EXCEPTION, and it is the DANGER box in CLAUDE.md applied here: an
 * unregistered v3 widget renders NOTHING on a page that uses it — no wrapper,
 * no error, no log line. So when the site already held v3 content BEFORE the
 * import (snapshotted at step 1 as `aae_site_had_v3`, exactly like
 * `aae_site_has_atomic`, because by the time this runs the demo's own pages
 * are in the DB), the widgets those pages reference stay ON. Every other
 * widget, every extension and all the chrome still go off — an extension
 * merely stops its saved effects running, a widget going off blanks a page.
 * Animation_Settings::used_v3_widget_slugs() is the same scan
 * maybe_enable_used_v3_widgets() uses to switch widgets ON after an import,
 * so the two can never disagree about which pages are at risk.
 *
 * Only for a starter TEMPLATE. A starter PAGE is dropped into an existing site
 * and must not touch that site's V3 at all — the importer gates on
 * `builder_version === 'v4'` AND `import_type !== 'page'`.
 *
 * Everything switched off is written to `aae_v4_import_v3_off` — previous
 * option values, the Kit keys as they were, the popup ids that were drafted —
 * so what happened is inspectable and restore() can put it back.
 */

class Atomic_V3_Switch_Off {

	const RECORD_OPTION = 'aae_v4_import_v3_off';

	/** The v3 chrome keys the Pro renderer reads out of the Kit. */
	const KIT_CHROME_KEYS = [
		'wcf_enable_preloader',
		'wcf_enable_cursor',
		'wcf_enable_scroll_to_top',
		'wcf_enable_scroll_indicator',
	];

	const POPUP_CPT      = 'wcf-addons-template';
	const POPUP_TYPE_KEY = 'wcf-addons-template-meta_type';

	/**
	 * Did this site hold v3 content BEFORE the import started? Ask at step 1
	 * and carry the answer in $template_data; asked here it would also see the
	 * demo's own pages.
	 */
	public static function site_has_v3_content(): bool {
		if ( ! class_exists( Animation_Settings::class ) || ! method_exists( Animation_Settings::class, 'has_v3_usage' ) ) {
			return false;
		}

		return (bool) Animation_Settings::has_v3_usage();
	}

	/**
	 * Switch V3 off. Returns a summary for the importer's state message.
	 *
	 * @param bool $site_had_v3 The step-1 snapshot: were v3 pages here before this import?
	 * @return array{widgets_off:int,widgets_kept:int,extensions_off:int,chrome_off:int,popups_drafted:int,legacy_v3:bool}
	 */
	public static function run( bool $site_had_v3 ): array {
		$record = [
			'at'              => gmdate( 'c' ),
			'site_had_v3'     => $site_had_v3,
			'widgets'         => [ 'absent' => false, 'value' => null ],
			'extensions'      => [ 'absent' => false, 'value' => null ],
			'kit_id'          => 0,
			'kit_chrome'      => [],
			'popups_drafted'  => [],
			'legacy_v3'       => null,
		];

		$summary = [
			'widgets_off'    => 0,
			'widgets_kept'   => 0,
			'extensions_off' => 0,
			'chrome_off'     => 0,
			'popups_drafted' => 0,
			'legacy_v3'      => false,
		];

		// ---- widgets -------------------------------------------------------------
		$raw                         = get_option( 'wcf_save_widgets', '__ABSENT__' );
		$record['widgets']['absent'] = '__ABSENT__' === $raw;
		$record['widgets']['value']  = '__ABSENT__' === $raw ? null : $raw;
		$was_on                      = is_array( $raw ) ? array_keys( array_filter( $raw ) ) : [];

		// Keep = ON before AND used by a page. The intersection, not the used
		// set: a used widget that was already OFF was the site owner's choice
		// (that page was already blank), and a step called "switch V3 off"
		// must never be the thing that switches a widget ON. Measured before
		// this was an intersection: 3 on → 5 on after the "off".
		$keep = [];
		if ( $site_had_v3 && class_exists( Animation_Settings::class ) && method_exists( Animation_Settings::class, 'used_v3_widget_slugs' ) ) {
			$used = Animation_Settings::used_v3_widget_slugs();
			foreach ( $was_on as $slug ) {
				if ( isset( $used[ $slug ] ) ) {
					$keep[ $slug ] = true;
				}
			}
		}

		update_option( 'wcf_save_widgets', $keep );
		$summary['widgets_kept'] = count( $keep );
		$summary['widgets_off']  = count( array_diff( $was_on, array_keys( $keep ) ) );

		// ---- extensions ----------------------------------------------------------
		$raw                            = get_option( 'wcf_save_extensions', '__ABSENT__' );
		$record['extensions']['absent'] = '__ABSENT__' === $raw;
		$record['extensions']['value']  = '__ABSENT__' === $raw ? null : $raw;
		$summary['extensions_off']      = is_array( $raw ) ? count( array_filter( $raw ) ) : 0;

		update_option( 'wcf_save_extensions', [] );

		// ---- Kit chrome ------------------------------------------------------------
		// The ACTIVE kit: on a first-lane import that is the kit the zip just
		// created (which carries none of these keys — a V4 demo never set them),
		// on a later import it is the site's own kit, where a v3 preloader may
		// well be saved. Cleared, not deleted: the Pro renderer tests
		// empty( $settings[ $key ] ), and Document::update_settings() merges.
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance && \Elementor\Plugin::$instance->kits_manager ) {
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();

			if ( $kit && $kit->get_id() ) {
				$record['kit_id'] = (int) $kit->get_id();
				$current          = (array) $kit->get_settings();
				$off              = [];

				foreach ( self::KIT_CHROME_KEYS as $key ) {
					$record['kit_chrome'][ $key ] = array_key_exists( $key, $current ) ? $current[ $key ] : null;

					if ( ! empty( $current[ $key ] ) ) {
						$off[ $key ] = '';
					}
				}

				if ( $off ) {
					$kit->update_settings( $off );
					$summary['chrome_off'] = count( $off );
				}
			}
		}

		// ---- v3 popups -------------------------------------------------------------
		// No site-level switch exists: a v3 popup is a published template whose
		// display conditions match, rendered by Pro's render_popup_global().
		// Drafting is the reversible lever — the template survives, the builder
		// can republish it, and the ids are recorded below.
		$popups = get_posts( [
			'post_type'      => self::POPUP_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => self::POPUP_TYPE_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 'popup', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		] );

		foreach ( (array) $popups as $popup_id ) {
			$updated = wp_update_post( [ 'ID' => (int) $popup_id, 'post_status' => 'draft' ], true );

			if ( ! is_wp_error( $updated ) ) {
				$record['popups_drafted'][] = (int) $popup_id;
			}
		}
		$summary['popups_drafted'] = count( $record['popups_drafted'] );

		// ---- Site Settings tabs ----------------------------------------------------
		if ( class_exists( Animation_Settings::class ) && method_exists( Animation_Settings::class, 'set_legacy_v3' ) ) {
			$record['legacy_v3'] = Animation_Settings::legacy_v3_enabled();
			Animation_Settings::set_legacy_v3( false );
			$summary['legacy_v3'] = true;
		}

		// The dashboard's era rules read has_v3_usage() through this transient;
		// a cached "yes" from before the import would keep the V3 tab on screen
		// for up to an hour on a site that no longer has anything v3 to show.
		// Only a NEGATIVE is ever kept stale on purpose (see maybe_invalidate_v3_usage());
		// dropping the row here just makes the next read honest.
		delete_transient( 'aae_v3_usage' );

		update_option( self::RECORD_OPTION, $record, false );

		return $summary;
	}

	/**
	 * Put back exactly what run() changed, from its record.
	 *
	 * Absent options are DELETED, not written empty — "absent" and "empty" are
	 * different answers to maybe_enable_used_v3_widgets(). Popups go back to
	 * publish only if they are still drafts (a builder who edited one since has
	 * made a newer choice).
	 */
	public static function restore(): bool {
		$record = get_option( self::RECORD_OPTION );

		if ( ! is_array( $record ) ) {
			return false;
		}

		foreach ( [ 'widgets' => 'wcf_save_widgets', 'extensions' => 'wcf_save_extensions' ] as $k => $option ) {
			if ( ! empty( $record[ $k ]['absent'] ) ) {
				delete_option( $option );
			} elseif ( isset( $record[ $k ] ) && array_key_exists( 'value', $record[ $k ] ) ) {
				update_option( $option, $record[ $k ]['value'] );
			}
		}

		if ( ! empty( $record['kit_id'] ) && class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance ) {
			$kit = \Elementor\Plugin::$instance->documents->get( (int) $record['kit_id'] );

			if ( $kit ) {
				$back = [];
				foreach ( (array) $record['kit_chrome'] as $key => $value ) {
					if ( null !== $value ) {
						$back[ $key ] = $value;
					}
				}
				if ( $back ) {
					$kit->update_settings( $back );
				}
			}
		}

		foreach ( (array) ( $record['popups_drafted'] ?? [] ) as $popup_id ) {
			if ( 'draft' === get_post_status( (int) $popup_id ) ) {
				wp_update_post( [ 'ID' => (int) $popup_id, 'post_status' => 'publish' ] );
			}
		}

		if ( null !== ( $record['legacy_v3'] ?? null ) && class_exists( Animation_Settings::class ) && method_exists( Animation_Settings::class, 'set_legacy_v3' ) ) {
			Animation_Settings::set_legacy_v3( (bool) $record['legacy_v3'] );
		}

		delete_transient( 'aae_v3_usage' );
		delete_option( self::RECORD_OPTION );

		return true;
	}

	/** One sentence for the importer's state message. */
	public static function describe( array $summary ): string {
		$parts = [];

		/* translators: %d: number of v3 widgets switched off */
		$parts[] = sprintf( _n( '%d v3 widget off', '%d v3 widgets off', $summary['widgets_off'], 'animation-addons-for-elementor' ), $summary['widgets_off'] );

		if ( ! empty( $summary['widgets_kept'] ) ) {
			/* translators: %d: number of v3 widgets kept because existing pages use them */
			$parts[] = sprintf( _n( '%d kept (used by existing pages)', '%d kept (used by existing pages)', $summary['widgets_kept'], 'animation-addons-for-elementor' ), $summary['widgets_kept'] );
		}

		/* translators: %d: number of v3 extensions switched off */
		$parts[] = sprintf( _n( '%d extension off', '%d extensions off', $summary['extensions_off'], 'animation-addons-for-elementor' ), $summary['extensions_off'] );

		if ( ! empty( $summary['chrome_off'] ) ) {
			/* translators: %d: number of v3 site-wide features (preloader, cursor, …) switched off */
			$parts[] = sprintf( _n( '%d site feature off', '%d site features off', $summary['chrome_off'], 'animation-addons-for-elementor' ), $summary['chrome_off'] );
		}

		if ( ! empty( $summary['popups_drafted'] ) ) {
			/* translators: %d: number of v3 popup templates set to draft */
			$parts[] = sprintf( _n( '%d popup drafted', '%d popups drafted', $summary['popups_drafted'], 'animation-addons-for-elementor' ), $summary['popups_drafted'] );
		}

		return esc_html__( 'V3 switched off:', 'animation-addons-for-elementor' ) . ' ' . implode( ', ', $parts );
	}
}
