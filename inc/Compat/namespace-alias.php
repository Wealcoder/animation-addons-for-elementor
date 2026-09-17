<?php
/**
 * Answer the plugin's PRE-4.2 class names for code that still uses them — the
 * paid add-on above all, plus any child theme or site snippet that named a
 * class of ours.
 *
 * TWO renames happened in 4.2.0, both for WordPress.org's "unique prefix"
 * review (2026-09), and this one loader covers either or both at once:
 *
 *   1. the NAMESPACE, `WCF_ADDONS\` → `Wealcoder\AnimationAddons\`;
 *   2. the CLASS names carrying one of the plugin's two three-letter families,
 *      `AAE_*` / `WCF_*` → `Aaeaddon_*` (the pairs are in `class-alias-map.php`).
 *
 * So `WCF_ADDONS\WCF_Post_Query_Trait` — the shipped add-on's spelling — and
 * `Wealcoder\AnimationAddons\WCF_Post_Query_Trait` — an add-on built between
 * the two renames — both resolve to the one class that exists today,
 * `Wealcoder\AnimationAddons\Aaeaddon_Post_Query_Trait`. Nothing in this
 * plugin refers to an old name any more; this file and the map beside it are
 * the only two places either spelling appears in source.
 *
 * How it works: PHP asks the registered autoloaders for a class it has not
 * seen. This one answers only names under one of the two namespaces, loads the
 * current twin through Composer, and declares the old name as an alias of it.
 * `class_alias()` covers classes, interfaces AND traits — a Pro widget doing
 * `use WCF_ADDONS\WCF_Post_Query_Trait;` inside its class body resolves
 * exactly as before, and `instanceof`, `extends`, static calls and string
 * callables all go through the same lookup. What an alias does NOT do is make
 * `Foo::class` return the new spelling, so a comparison against the OLD string
 * keeps matching an object of the NEW class only through `instanceof`; Pro
 * compares no class names as strings (measured), and free's own code has none
 * left.
 *
 * Most of our classes live in `class-*.php` files that are not PSR-4 paths and
 * are `require_once`d by the boot, so Composer cannot reach them on its own
 * and they are aliasable only once that file has been included. That was
 * already true of the namespace rename and is unchanged: the add-on reaches
 * them at a point where the free plugin has long since loaded them.
 *
 * Cost: one `strncmp()` per autoload MISS, two for a miss under the current
 * namespace — the map itself is read only when a miss actually carries an old
 * class prefix. A request that never mentions an old name pays nothing beyond
 * that; a request that does pays it once per class, after which the alias is a
 * real class-table entry.
 *
 * It is appended (`$prepend = false`) so Composer's PSR-4 loader answers the
 * new names first and this one only ever sees what Composer could not load.
 *
 * Loaded from the plugin bootstrap right after `vendor/autoload.php`, i.e.
 * before ANY `plugins_loaded` callback — the add-on's `use` statements bind
 * lazily at first use, so this is early enough by a wide margin.
 *
 * @package Wealcoder\AnimationAddons
 * @since   4.2.0
 */

defined( 'ABSPATH' ) || die();

spl_autoload_register(
	static function ( $class ) {
		static $busy = false;
		static $map  = null;

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- the pre-4.2 namespace being aliased.
		$old_ns = ( 0 === strncmp( $class, 'WCF_ADDONS\\', 11 ) );
		$new_ns = ( ! $old_ns && 0 === strncmp( $class, 'Wealcoder\\AnimationAddons\\', 26 ) );

		if ( ! $old_ns && ! $new_ns ) {
			return;
		}

		$rest  = $old_ns ? substr( $class, 11 ) : substr( $class, 26 );
		$sep   = strrpos( $rest, '\\' );
		$sub   = ( false === $sep ) ? '' : substr( $rest, 0, $sep + 1 );
		$short = ( false === $sep ) ? $rest : substr( $rest, $sep + 1 );

		// Under the CURRENT namespace there is nothing to translate unless the
		// class name itself is a pre-4.2 spelling. Testing the two families
		// here is what keeps the map off every ordinary autoload miss. Three
		// characters, not four: `AAEImporter` and `WCFAddon_BlackList_Notice`
		// carried the family without the underscore (case-sensitive, so the
		// current `Aaeaddon_` names never match).
		if ( $new_ns && 0 !== strncmp( $short, 'AAE', 3 ) && 0 !== strncmp( $short, 'WCF', 3 ) ) {
			return;
		}

		if ( null === $map ) {
			$map = require __DIR__ . '/class-alias-map.php';
		}

		$new = 'Wealcoder\\AnimationAddons\\' . $sub . ( isset( $map[ $short ] ) ? $map[ $short ] : $short );

		if ( $new === $class ) {
			return;
		}

		// The add-on (4.3+) carries the mirror image of this loader — it
		// answers a NEW name by looking for the OLD one, for sites whose free
		// plugin is still 4.1. A class that exists under neither name would
		// bounce between the two forever; the flag ends it after one hop.
		if ( $busy ) {
			return;
		}
		$busy = true;

		// class_exists() triggers Composer's autoloader for the new name, so
		// the twin is loaded here if it was not already.
		if ( class_exists( $new ) || interface_exists( $new ) || trait_exists( $new ) ) {
			class_alias( $new, $class );
		}

		$busy = false;
	},
	true,
	false
);
