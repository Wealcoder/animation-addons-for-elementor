<?php
/**
 * Key_Bridge — both spellings of every renamed option answer, whichever the
 * caller uses, from one live row.
 *
 * Booted at plugin FILE-LOAD time (before any `plugins_loaded` callback, so
 * before the paid add-on reads a single option) with the map from
 * `key-map.php`. Two phases, decided by `aaeaddon_migration_state`:
 *
 *   OLD_LIVE  — the pre-4.2 rows are the only copy. Every read/write of a NEW
 *               name is redirected to the OLD row. Nothing is copied, nothing
 *               new is created. This is every existing site until an
 *               administrator presses "Start migration", and it is what makes
 *               a downgrade a no-op.
 *   NEW_LIVE  — the new rows are live. Every read/write of an OLD name (the
 *               paid add-on, a child theme) is redirected to the NEW row, and
 *               every write — either spelling — is mirrored into the OLD row
 *               too, so the old row is a current copy and a downgrade is a
 *               full rollback at any time. Fresh installs start here.
 *
 * Cost on a normal request: the filters below are registered on exact option
 * names, so only a read of a bridged name pays a callback, and that callback
 * resolves against `alloptions` in memory. When the row is absent under the
 * live spelling the requested name is added to WordPress's own `notoptions`
 * cache before falling through, so WordPress does not run the query it would
 * otherwise run for a miss — the bridge adds no query a bare read would not
 * have made. `verify-key-bridge.php` measures that with SAVEQUERIES.
 *
 * `update_option()` on a redirected name returns FALSE even when the live row
 * changed: the write is done by the filter and WordPress is told nothing
 * changed. Code that branches on that return value must read through
 * `Key_Bridge::update_option()` instead, which reports the live write.
 *
 * @package Wealcoder\AnimationAddons
 * @since   4.2.0
 */

namespace Wealcoder\AnimationAddons\Compat;

defined( 'ABSPATH' ) || exit;

final class Key_Bridge {

	const STATE_OPTION = 'aaeaddon_migration_state';

	const OLD_LIVE = 'old';
	const NEW_LIVE = 'new';

	/** @var array|null the decoded key-map.php */
	private static $map = null;

	/** @var array old => new, options + options_pro */
	private static $old_to_new = array();

	/** @var array new => old */
	private static $new_to_old = array();

	/** @var array old prefix => new prefix */
	private static $prefixes = array();

	/** @var string|null */
	private static $phase = null;

	/** @var array requested name => live name, for the current phase */
	private static $redirect = array();

	/** @var int >0 while the bridge is talking to WordPress itself */
	private static $suspended = 0;

	/** @var bool */
	private static $booted = false;

	/** @var object|null unique "absent" marker for redirected reads */
	private static $missing = null;

	/**
	 * Boot: load the map, decide the phase, hook the filters.
	 *
	 * Idempotent. Safe before `plugins_loaded`: only `get_option()` and the
	 * hook API are used, both available once wp-settings.php has run.
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted  = true;
		self::$missing = new \stdClass();

		$map = self::map();
		foreach ( array( 'options', 'options_pro' ) as $group ) {
			foreach ( $map[ $group ] as $old => $new ) {
				self::$old_to_new[ $old ] = $new;
				self::$new_to_old[ $new ] = $old;
			}
		}
		self::$prefixes = $map['prefixes'];

		self::set_phase( self::phase_from_state( get_option( self::STATE_OPTION ) ) );

		// MULTISITE. The phase is a property of ONE site's database (its own
		// migration state, its own option rows) and the bridge memoises it,
		// so a switch_to_blog() -- a network loop, an importer, WooCommerce --
		// would keep redirecting reads with the PREVIOUS site's answer. The
		// `switch_blog` action fires for both switch_to_blog() and
		// restore_current_blog(); re-decide from the site now current. One
		// autoloaded read per actual change of site, nothing on a single site.
		if ( is_multisite() ) {
			add_action( 'switch_blog', array( __CLASS__, 'on_switch_blog' ), 1, 2 );
		}
	}

	/**
	 * `switch_blog`: re-read the migration state of the site now current and
	 * put the bridge in that site's phase. Also drops Migration's own memo.
	 *
	 * @param int $new_blog_id  Site switched to.
	 * @param int $prev_blog_id Site switched from.
	 */
	public static function on_switch_blog( $new_blog_id, $prev_blog_id ) {
		if ( (int) $new_blog_id === (int) $prev_blog_id ) {
			return;
		}
		self::set_phase( self::phase_from_state( self::raw_get( self::STATE_OPTION ) ) );
		if ( class_exists( __NAMESPACE__ . '\Migration', false ) ) {
			Migration::forget_state();
		}
	}

	/** The key map (key-map.php), loaded once. */
	public static function map() {
		if ( null === self::$map ) {
			self::$map = require __DIR__ . '/key-map.php';
		}
		return self::$map;
	}

	/** old => new for every renamed option (free + pro). */
	public static function pairs() {
		self::boot();
		return self::$old_to_new;
	}

	/** Which phase a stored migration state puts the bridge in. */
	public static function phase_from_state( $state ) {
		return ( is_array( $state ) && isset( $state['status'] ) && 'complete' === $state['status'] )
			? self::NEW_LIVE
			: self::OLD_LIVE;
	}

	public static function phase() {
		self::boot();
		return self::$phase;
	}

	/**
	 * Switch phase in-process (the consent click does this after the copy).
	 * Unhooks the previous set of filters and hooks the new one.
	 */
	public static function set_phase( $phase ) {
		$phase = self::NEW_LIVE === $phase ? self::NEW_LIVE : self::OLD_LIVE;
		if ( $phase === self::$phase ) {
			return;
		}
		if ( null !== self::$phase ) {
			self::unhook();
		}
		self::$phase    = $phase;
		self::$redirect = array();

		if ( self::OLD_LIVE === $phase ) {
			// new name requested -> old row.
			foreach ( self::$new_to_old as $new => $old ) {
				self::$redirect[ $new ] = $old;
				add_filter( 'pre_option_' . $new, array( __CLASS__, 'redirect_read' ), 5, 3 );
				add_filter( 'pre_update_option_' . $new, array( __CLASS__, 'redirect_write' ), 5, 3 );
				add_action( 'add_option_' . $new, array( __CLASS__, 'mirror_added' ), 10, 2 );
			}
		} else {
			// old name requested -> new row; every write mirrored into the old row.
			foreach ( self::$old_to_new as $old => $new ) {
				self::$redirect[ $old ] = $new;
				add_filter( 'pre_option_' . $old, array( __CLASS__, 'redirect_read' ), 5, 3 );
				add_filter( 'pre_update_option_' . $old, array( __CLASS__, 'redirect_write' ), 5, 3 );
				add_action( 'add_option_' . $old, array( __CLASS__, 'mirror_added' ), 10, 2 );
				add_action( 'add_option_' . $new, array( __CLASS__, 'mirror_added' ), 10, 2 );
				add_action( 'update_option_' . $new, array( __CLASS__, 'mirror_updated' ), 10, 3 );
			}
		}
		if ( self::$prefixes ) {
			add_filter( 'pre_option', array( __CLASS__, 'prefix_read' ), 5, 3 );
			add_filter( 'pre_update_option', array( __CLASS__, 'prefix_write' ), 5, 3 );
			add_action( 'added_option', array( __CLASS__, 'prefix_mirror' ), 10, 2 );
			add_action( 'updated_option', array( __CLASS__, 'prefix_mirror_updated' ), 10, 3 );
		}
		add_action( 'delete_option', array( __CLASS__, 'mirror_deleted' ), 10, 1 );
	}

	private static function unhook() {
		foreach ( self::$redirect as $from => $to ) {
			remove_filter( 'pre_option_' . $from, array( __CLASS__, 'redirect_read' ), 5 );
			remove_filter( 'pre_update_option_' . $from, array( __CLASS__, 'redirect_write' ), 5 );
			remove_action( 'add_option_' . $from, array( __CLASS__, 'mirror_added' ), 10 );
			remove_action( 'add_option_' . $to, array( __CLASS__, 'mirror_added' ), 10 );
			remove_action( 'update_option_' . $to, array( __CLASS__, 'mirror_updated' ), 10 );
		}
		remove_filter( 'pre_option', array( __CLASS__, 'prefix_read' ), 5 );
		remove_filter( 'pre_update_option', array( __CLASS__, 'prefix_write' ), 5 );
		remove_action( 'added_option', array( __CLASS__, 'prefix_mirror' ), 10 );
		remove_action( 'updated_option', array( __CLASS__, 'prefix_mirror_updated' ), 10 );
		remove_action( 'delete_option', array( __CLASS__, 'mirror_deleted' ), 10 );
	}

	/* ------------------------------------------------------------------ */
	/* Name resolution                                                      */
	/* ------------------------------------------------------------------ */

	/** Is this name (either spelling, or a mapped prefix) one the bridge owns? */
	public static function is_mapped( $name ) {
		self::boot();
		return isset( self::$old_to_new[ $name ] ) || isset( self::$new_to_old[ $name ] ) || null !== self::prefix_pair( $name );
	}

	/** The other spelling of a mapped name, or null. */
	public static function other_name( $name ) {
		self::boot();
		if ( isset( self::$old_to_new[ $name ] ) ) {
			return self::$old_to_new[ $name ];
		}
		if ( isset( self::$new_to_old[ $name ] ) ) {
			return self::$new_to_old[ $name ];
		}
		$pair = self::prefix_pair( $name );
		return $pair ? $pair[1] : null;
	}

	/** The NEW spelling of a mapped name (the name itself if not mapped). */
	public static function new_name( $name ) {
		self::boot();
		if ( isset( self::$old_to_new[ $name ] ) ) {
			return self::$old_to_new[ $name ];
		}
		$pair = self::prefix_pair( $name );
		return ( $pair && 'old' === $pair[0] ) ? $pair[1] : $name;
	}

	/** The OLD spelling of a mapped name (the name itself if not mapped). */
	public static function old_name( $name ) {
		self::boot();
		if ( isset( self::$new_to_old[ $name ] ) ) {
			return self::$new_to_old[ $name ];
		}
		$pair = self::prefix_pair( $name );
		return ( $pair && 'new' === $pair[0] ) ? $pair[1] : $name;
	}

	/**
	 * The spelling the row is LIVE under right now — for the rare caller
	 * that must address the row without going through get_option()
	 * (a `$wpdb` query, `wp_load_alloptions()`).
	 */
	public static function live_name( $name ) {
		return self::NEW_LIVE === self::phase() ? self::new_name( $name ) : self::old_name( $name );
	}

	/**
	 * [ 'old'|'new', other_name ] when $name starts with a mapped prefix.
	 */
	private static function prefix_pair( $name ) {
		foreach ( self::$prefixes as $old_prefix => $new_prefix ) {
			if ( 0 === strncmp( $name, $old_prefix, strlen( $old_prefix ) ) ) {
				return array( 'old', $new_prefix . substr( $name, strlen( $old_prefix ) ) );
			}
			if ( 0 === strncmp( $name, $new_prefix, strlen( $new_prefix ) ) ) {
				return array( 'new', $old_prefix . substr( $name, strlen( $new_prefix ) ) );
			}
		}
		return null;
	}

	/** Where a request for $name should be answered from in this phase, or null. */
	private static function target_for( $name ) {
		if ( isset( self::$redirect[ $name ] ) ) {
			return self::$redirect[ $name ];
		}
		$pair = self::prefix_pair( $name );
		if ( ! $pair ) {
			return null;
		}
		// OLD_LIVE redirects new->old; NEW_LIVE redirects old->new.
		if ( self::OLD_LIVE === self::$phase && 'new' === $pair[0] ) {
			return $pair[1];
		}
		if ( self::NEW_LIVE === self::$phase && 'old' === $pair[0] ) {
			return $pair[1];
		}
		return null;
	}

	/* ------------------------------------------------------------------ */
	/* Filters                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * pre_option_{name}: answer from the live row.
	 *
	 * Returns false (no short-circuit) when the live row is absent, after
	 * telling WordPress the requested name is absent too — so the caller's
	 * $default is honoured and no query is made for it.
	 */
	public static function redirect_read( $pre, $option, $default_value = false ) {
		if ( self::$suspended || false !== $pre ) {
			return $pre;
		}
		$target = self::target_for( $option );
		if ( null === $target ) {
			return $pre;
		}
		self::$suspended++;
		$value = get_option( $target, self::$missing );
		self::$suspended--;

		if ( self::$missing === $value ) {
			self::mark_missing( $option );
			return false;
		}

		/*
		 * The REQUESTED name's own read filter, which get_option() would have
		 * applied and now cannot: a pre_option_{name} short-circuit returns
		 * before option_{name} ever runs. get_option( $target ) above applied
		 * the LIVE name's filter; both spellings are views of one row, so both
		 * filters belong. Pro filters option_wcf_save_extensions -- the old
		 * spelling, i.e. the redirected one once the new rows go live.
		 */
		return apply_filters( "option_{$option}", $value, $option );
	}

	/**
	 * pre_update_option_{name}: write the live row (and, once the new rows
	 * are live, the old row as a mirror) and tell WordPress nothing changed.
	 */
	public static function redirect_write( $value, $old_value, $option ) {
		if ( self::$suspended ) {
			return $value;
		}
		$target = self::target_for( $option );
		if ( null === $target ) {
			return $value;
		}
		self::$suspended++;
		$existed = ( self::$missing !== get_option( $target, self::$missing ) );
		update_option( $target, $value );
		if ( self::NEW_LIVE === self::$phase ) {
			// $option is the OLD spelling here: keep it current for a downgrade.
			update_option( $option, $value );
		}
		self::$suspended--;

		self::replay_write_hooks( $option, $value, $old_value, $existed );

		return $old_value;
	}

	/**
	 * Re-fire the REQUESTED name's own option hooks after a redirected write.
	 *
	 * redirect_write() returns $old_value, which tells WordPress nothing
	 * changed. That is what stops it writing a second row -- and it equally
	 * stops it firing update_option_{$option} / add_option_{$option} and their
	 * generic twins, so anything listening on the spelling the CALLER used is
	 * silently dead.
	 *
	 * In phase A that is every listener on a NEW name, which is all of ours.
	 * Measured 2026-09-16: update_option( 'aaeaddon_animation_settings', ... )
	 * never ran Animation_Settings::flush_cache(), so get() kept serving the
	 * PRE-save value for the rest of that request -- the settings screen read
	 * back what the user had just replaced, and Pro's renderer was handed the
	 * old answer through the same cache. Pro's Performance::flush_cache() is
	 * hooked identically and had the identical hole.
	 *
	 * Skipped in phase B, where the mirror write above targets $option's own row
	 * and fires these hooks naturally -- replaying them there would run every
	 * listener twice.
	 *
	 * @param string $option   The name the caller used.
	 * @param mixed  $value    The value now stored.
	 * @param mixed  $old_value What the caller's own read returned beforehand.
	 * @param bool   $existed  Whether the live row existed before this write.
	 */
	private static function replay_write_hooks( $option, $value, $old_value, $existed ) {
		if ( self::NEW_LIVE === self::$phase ) {
			return;
		}

		// update_option() fires nothing when the value did not actually change.
		if ( maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
			return;
		}

		if ( $existed ) {
			do_action( "update_option_{$option}", $old_value, $value, $option );
			do_action( 'updated_option', $option, $old_value, $value );
		} else {
			do_action( "add_option_{$option}", $option, $value );
			do_action( 'added_option', $option, $value );
		}
	}

	/** add_option_{name}: a row was created under one spelling — copy it to the other. */
	public static function mirror_added( $option, $value ) {
		if ( self::$suspended ) {
			return;
		}
		$other = self::other_name( $option );
		if ( null === $other ) {
			return;
		}
		if ( self::OLD_LIVE === self::$phase && isset( self::$old_to_new[ $option ] ) ) {
			// Old row created directly (old Pro) — nothing to mirror before consent.
			return;
		}
		self::$suspended++;
		update_option( $other, $value );
		self::$suspended--;
	}

	/** update_option_{new} (NEW_LIVE only): keep the old row current. */
	public static function mirror_updated( $old_value, $value, $option ) {
		if ( self::$suspended ) {
			return;
		}
		$other = self::other_name( $option );
		if ( null === $other ) {
			return;
		}
		self::$suspended++;
		update_option( $other, $value );
		self::$suspended--;
	}

	/**
	 * delete_option (fires only when the row exists): a deliberate delete of
	 * one spelling removes the other, so "the option is gone" stays true
	 * under both names. Before consent an OLD row deleted by the add-on only
	 * takes a stray new copy with it; a NEW spelling deleted then is the
	 * plugin's own code, which goes through self::delete_option().
	 */
	public static function mirror_deleted( $option ) {
		if ( self::$suspended ) {
			return;
		}
		$other = self::other_name( $option );
		if ( null === $other ) {
			return;
		}
		self::$suspended++;
		delete_option( $other );
		self::$suspended--;
	}

	/* Generic filters for the prefixed family (one row per menu id). */

	public static function prefix_read( $pre, $option, $default_value = false ) {
		if ( false !== $pre || self::$suspended || ! self::prefix_candidate( $option ) ) {
			return $pre;
		}
		return self::redirect_read( $pre, $option, $default_value );
	}

	public static function prefix_write( $value, $option, $old_value ) {
		if ( self::$suspended || ! self::prefix_candidate( $option ) ) {
			return $value;
		}
		return self::redirect_write( $value, $old_value, $option );
	}

	public static function prefix_mirror( $option, $value ) {
		if ( self::prefix_candidate( $option ) && null !== self::prefix_pair( $option ) ) {
			self::mirror_added( $option, $value );
		}
	}

	public static function prefix_mirror_updated( $option, $old_value, $value ) {
		if ( self::NEW_LIVE === self::$phase && self::prefix_candidate( $option ) ) {
			$pair = self::prefix_pair( $option );
			if ( $pair && 'new' === $pair[0] ) {
				self::mirror_updated( $old_value, $value, $option );
			}
		}
	}

	/**
	 * Cheap pre-test so the generic filters cost one comparison for foreign
	 * names, and skip the exact-name pairs the per-name filters already
	 * answered.
	 */
	private static function prefix_candidate( $option ) {
		return '' !== $option
			&& ( 'w' === $option[0] || 'a' === $option[0] )
			&& ! isset( self::$old_to_new[ $option ] )
			&& ! isset( self::$new_to_old[ $option ] );
	}

	/* ------------------------------------------------------------------ */
	/* Helpers for the plugin's own code                                    */
	/* ------------------------------------------------------------------ */

	/**
	 * update_option() that reports the LIVE write's result rather than the
	 * redirected call's constant false. For the handful of callers that
	 * branch on "did it change".
	 */
	public static function update_option( $name, $value, $autoload = null ) {
		self::boot();
		$live  = self::live_name( $name );
		$other = self::other_name( $name );
		self::$suspended++;
		$result = update_option( $live, $value, $autoload );
		if ( self::NEW_LIVE === self::$phase && null !== $other && $result ) {
			update_option( self::old_name( $name ), $value );
		}
		self::$suspended--;
		return $result;
	}

	/** Delete BOTH spellings. The plugin's own "this option is gone". */
	public static function delete_option( $name ) {
		self::boot();
		$other = self::other_name( $name );
		self::$suspended++;
		$result = delete_option( $name );
		if ( null !== $other ) {
			$result = delete_option( $other ) || $result;
		}
		self::$suspended--;
		return $result;
	}

	/** get_option() with the bridge out of the way — the raw row under exactly this name. */
	public static function raw_get( $name, $default_value = false ) {
		self::boot();
		self::$suspended++;
		$value = get_option( $name, $default_value );
		self::$suspended--;
		return $value;
	}

	/** update_option() with the bridge out of the way — writes exactly this name. */
	public static function raw_update( $name, $value, $autoload = null ) {
		self::boot();
		self::$suspended++;
		$result = update_option( $name, $value, $autoload );
		self::$suspended--;
		return $result;
	}

	/** Run $fn with the bridge suspended (the copy step). */
	public static function suspended( callable $fn ) {
		self::boot();
		self::$suspended++;
		try {
			return $fn();
		} finally {
			self::$suspended--;
		}
	}

	/** Add $option to WordPress's notoptions cache, exactly as a miss would. */
	private static function mark_missing( $option ) {
		$notoptions = wp_cache_get( 'notoptions', 'options' );
		if ( ! is_array( $notoptions ) ) {
			$notoptions = array();
		}
		if ( ! isset( $notoptions[ $option ] ) ) {
			$notoptions[ $option ] = true;
			wp_cache_set( 'notoptions', $notoptions, 'options' );
		}
	}
}
