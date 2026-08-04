<?php
/**
 * Keeps imported Navs in step with their WordPress menu — server-side.
 *
 * Fires when a menu is saved in Appearance → Menus and rewrites the saved
 * `_elementor_data` of every page whose Nav is bound to that menu, so the change
 * reaches the FRONTEND without anyone opening Elementor.
 *
 * Only ever touches a Nav that opted in (`menu_autosync`), and only its imported
 * items (`wp_id`); hand-added items and all styling are left alone.
 *
 * NOTE: lives in the free plugin even though the Nav itself has moved to Pro —
 * this is data integrity, and it must keep running regardless of licence state.
 * Do not delete it with the free plugin's transitional Nav fallback folder.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Nav;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Nav_Menu_Sync {

	const NAV_TYPE  = 'e-aae-a-nav';
	const ITEM_TYPE = 'e-aae-a-nav-item';
	const FLEX_TYPE = 'e-flexbox';

	/** Menu ids to sync at shutdown, deduped — the admin fires these hooks repeatedly. */
	private static array $pending = [];

	private static bool $hooked_shutdown = false;

	public static function register(): void {
		// Both hooks only QUEUE. Syncing inline would read the menu too early:
		// the admin save runs wp_update_nav_menu_object() — which fires
		// wp_update_nav_menu — BEFORE wp_nav_menu_update_menu_items() writes the
		// new items, so a newly added item would be missed until the next save.
		add_action( 'wp_update_nav_menu', [ __CLASS__, 'queue_menu' ], 20, 1 );
		add_action( 'wp_update_nav_menu_item', [ __CLASS__, 'queue_menu' ], 20, 1 );
	}

	/**
	 * @param int $menu_id Term id of the saved menu.
	 */
	public static function queue_menu( $menu_id ): void {
		$menu_id = (int) $menu_id;

		if ( $menu_id <= 0 ) {
			return;
		}

		self::$pending[ $menu_id ] = true;

		if ( ! self::$hooked_shutdown ) {
			self::$hooked_shutdown = true;
			// Shutdown: every item write for this request has landed, so the menu
			// reads final — additions, renames and deletions alike.
			add_action( 'shutdown', [ __CLASS__, 'flush' ], 5 );
		}
	}

	public static function flush(): void {
		$pending        = self::$pending;
		self::$pending  = [];

		foreach ( array_keys( $pending ) as $menu_id ) {
			self::sync_menu( (int) $menu_id );
		}
	}

	private static function sync_menu( int $menu_id ): void {
		$items = wp_get_nav_menu_items( $menu_id );

		// A read failure must never be read as "the menu is now empty" — that would
		// delete every imported item on every page. Only a populated menu syncs.
		if ( ! is_array( $items ) || ! $items ) {
			return;
		}

		$tree = \WCF_ADDONS\AtomicWidgets\Atomic::build_nav_menu_tree( $items );

		if ( ! $tree ) {
			return;
		}

		foreach ( self::candidate_post_ids() as $post_id ) {
			self::sync_post( $post_id, $menu_id, $tree );
		}
	}

	/**
	 * Posts whose saved data could hold an auto-syncing Nav. A cheap SQL prefilter;
	 * sync_post() re-verifies properly after decoding.
	 *
	 * @return int[]
	 */
	private static function candidate_post_ids(): array {
		global $wpdb;

		$ids = $wpdb->get_col(
			"SELECT p.ID FROM {$wpdb->postmeta} m
			 INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
			 WHERE m.meta_key = '_elementor_data'
			   AND m.meta_value LIKE '%e-aae-a-nav%'
			   AND m.meta_value LIKE '%imported_menu_id%'
			   AND p.post_status NOT IN ( 'trash', 'auto-draft' )
			   AND p.post_type != 'revision'"
		);

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * @param int   $post_id
	 * @param int   $menu_id
	 * @param array $tree Nested WP menu nodes.
	 */
	private static function sync_post( int $post_id, int $menu_id, array $tree ): void {
		$raw = get_post_meta( $post_id, '_elementor_data', true );

		if ( ! is_string( $raw ) || '' === $raw ) {
			return;
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
			return;
		}

		$changed = false;
		$data    = self::walk_document( $data, $menu_id, $tree, $changed );

		if ( ! $changed ) {
			return;
		}

		// wp_slash: _elementor_data is stored slashed, and update_post_meta strips
		// one level. Without it every backslash in the JSON is eaten on each save.
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );

		// Drop the rendered caches so the frontend reflects the new tree at once.
		delete_post_meta( $post_id, '_elementor_element_cache' );
		delete_post_meta( $post_id, '_elementor_css' );
	}

	/**
	 * Find every opted-in Nav bound to this menu and sync its children.
	 *
	 * @param array $elements
	 * @param bool  $changed By reference.
	 * @return array
	 */
	private static function walk_document( array $elements, int $menu_id, array $tree, bool &$changed ): array {
		foreach ( $elements as $i => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( self::type_of( $element ) === self::NAV_TYPE
				&& (int) self::read_prop( $element, 'imported_menu_id' ) === $menu_id
				&& self::read_prop( $element, 'menu_autosync' ) ) {

				$element['elements'] = self::sync_level(
					is_array( $element['elements'] ?? null ) ? $element['elements'] : [],
					$tree,
					$changed
				);

				$elements[ $i ] = $element;
				continue;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$elements[ $i ]['elements'] = self::walk_document( $element['elements'], $menu_id, $tree, $changed );
			}
		}

		return $elements;
	}

	/**
	 * Reconcile one level of nav-items against one level of WP nodes.
	 *
	 * Mirrors syncMenuLevel() in NavItemsControl.jsx — matched by `wp_id`, new
	 * items appended, vanished ones dropped, hand-added (no wp_id) untouched.
	 *
	 * @param array $children Current elements at this level.
	 * @param array $nodes    WP nodes at this level.
	 * @param bool  $changed  By reference.
	 * @return array
	 */
	private static function sync_level( array $children, array $nodes, bool &$changed ): array {
		$wp_ids = [];
		foreach ( $nodes as $node ) {
			$wp_ids[ (string) ( $node['id'] ?? '' ) ] = true;
		}

		// Drop imported items whose menu item no longer exists.
		$kept = [];
		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				$kept[] = $child;
				continue;
			}

			$wp_id = (string) self::read_prop( $child, 'wp_id' );

			if ( self::type_of( $child ) === self::ITEM_TYPE && '' !== $wp_id && ! isset( $wp_ids[ $wp_id ] ) ) {
				$changed = true;
				continue;
			}

			$kept[] = $child;
		}

		// Index what survived, so nodes can be matched to it.
		$by_wp_id = [];
		foreach ( $kept as $i => $child ) {
			if ( is_array( $child ) && self::type_of( $child ) === self::ITEM_TYPE ) {
				$wp_id = (string) self::read_prop( $child, 'wp_id' );
				if ( '' !== $wp_id ) {
					$by_wp_id[ $wp_id ] = $i;
				}
			}
		}

		foreach ( $nodes as $node ) {
			$wp_id        = (string) ( $node['id'] ?? '' );
			$has_children = ! empty( $node['children'] ) && is_array( $node['children'] );

			if ( ! isset( $by_wp_id[ $wp_id ] ) ) {
				$kept[]  = self::build_item( $node );
				$changed = true;
				continue;
			}

			$idx  = $by_wp_id[ $wp_id ];
			$item = $kept[ $idx ];

			$item = self::sync_item( $item, $node, $has_children, $changed );

			if ( $has_children ) {
				$item = self::sync_dropdown( $item, $node['children'], $changed );
			}

			$kept[ $idx ] = $item;
		}

		// Re-index: a JSON array with a hole serialises as an object, which
		// Elementor's parser rejects as a children list.
		return array_values( $kept );
	}

	/**
	 * Update one matched item's label / link / dropdown flag.
	 *
	 * `wp_title` records what WordPress last said. The label follows WP only while
	 * it still equals that — once you edit it here, it is yours and is left alone.
	 *
	 * @param array $item
	 * @param bool  $changed By reference.
	 * @return array
	 */
	private static function sync_item( array $item, array $node, bool $has_children, bool &$changed ): array {
		$wp_title      = self::read_prop( $item, 'wp_title' );
		$current_label = self::read_label( $item );
		$new_title     = (string) ( $node['title'] ?? '' );

		if ( null === $wp_title || '' === $wp_title ) {
			// Imported before wp_title existed: adopt the CURRENT label as the
			// baseline, so a later WP rename is detectable without touching it now.
			$item    = self::write_prop( $item, 'wp_title', 'string', $current_label );
			$changed = true;
		} elseif ( $current_label === (string) $wp_title && $current_label !== $new_title ) {
			$item    = self::write_prop( $item, 'text', 'html-v3', [
				'content'  => [ '$$type' => 'string', 'value' => $new_title ],
				'children' => [],
			] );
			$item    = self::write_prop( $item, 'wp_title', 'string', $new_title );
			$changed = true;
		} elseif ( (string) $wp_title !== $new_title ) {
			// Label was edited here — keep it, but track WP's new wording.
			$item    = self::write_prop( $item, 'wp_title', 'string', $new_title );
			$changed = true;
		}

		if ( (bool) self::read_prop( $item, 'has_dropdown' ) !== $has_children ) {
			$item    = self::write_prop( $item, 'has_dropdown', 'boolean', $has_children );
			$changed = true;
		}

		return $item;
	}

	/**
	 * Sync a matched item's children into its dropdown flexbox, creating one if
	 * WordPress just gave the item its first sub-item.
	 *
	 * @param array $item
	 * @param bool  $changed By reference.
	 * @return array
	 */
	private static function sync_dropdown( array $item, array $nodes, bool &$changed ): array {
		$children = is_array( $item['elements'] ?? null ) ? $item['elements'] : [];
		$flex_idx = null;

		foreach ( $children as $i => $child ) {
			if ( is_array( $child ) && self::type_of( $child ) === self::FLEX_TYPE ) {
				$flex_idx = $i;
				break;
			}
		}

		if ( null === $flex_idx ) {
			$children[] = [
				'id'              => self::gen_id(),
				'elType'          => self::FLEX_TYPE,
				'editor_settings' => [ 'title' => 'Dropdown' ],
				'settings'        => (object) [],
				'elements'        => array_map( [ __CLASS__, 'build_item' ], $nodes ),
			];
			$changed          = true;
			$item['elements'] = array_values( $children );

			return $item;
		}

		$flex               = $children[ $flex_idx ];
		$flex['elements']   = self::sync_level(
			is_array( $flex['elements'] ?? null ) ? $flex['elements'] : [],
			$nodes,
			$changed
		);
		$children[ $flex_idx ] = $flex;
		$item['elements']      = array_values( $children );

		return $item;
	}

	/**
	 * Build a fresh nav-item. Mirrors buildImportedItemModel() in
	 * NavItemsControl.jsx — keep the two in step.
	 *
	 * @param array $node
	 * @return array
	 */
	private static function build_item( array $node ): array {
		$title        = ( '' !== (string) ( $node['title'] ?? '' ) ) ? (string) $node['title'] : 'Menu Item';
		$has_children = ! empty( $node['children'] ) && is_array( $node['children'] );

		$settings = [
			'text'               => [
				'$$type' => 'html-v3',
				'value'  => [
					'content'  => [ '$$type' => 'string', 'value' => $title ],
					'children' => [],
				],
			],
			'has_dropdown'       => [ '$$type' => 'boolean', 'value' => $has_children ],
			'trigger'            => [ '$$type' => 'string', 'value' => 'click' ],
			'dropdown_animation' => [ '$$type' => 'string', 'value' => 'gsap' ],
			'wp_id'              => [ '$$type' => 'string', 'value' => (string) ( $node['id'] ?? '' ) ],
			'wp_title'           => [ '$$type' => 'string', 'value' => $title ],
		];

		// A bare "#" is a placeholder, not a destination: rendering <a href="#">
		// makes Elementor's editor anchor handler run querySelector('#') and throw.
		$url = (string) ( $node['url'] ?? '' );

		if ( '' !== $url && '#' !== $url ) {
			$settings['link'] = [
				'$$type' => 'link',
				'value'  => [
					'destination'   => [ '$$type' => 'url', 'value' => $url ],
					'isTargetBlank' => [ '$$type' => 'boolean', 'value' => ( '_blank' === ( $node['target'] ?? '' ) ) ],
					'tag'           => [ '$$type' => 'string', 'value' => 'a' ],
				],
			];
		}

		$model = [
			'id'              => self::gen_id(),
			'elType'          => self::ITEM_TYPE,
			'editor_settings' => [ 'title' => $title ],
			'settings'        => $settings,
			'elements'        => [],
		];

		if ( $has_children ) {
			$model['elements'] = [
				[
					'id'              => self::gen_id(),
					'elType'          => self::FLEX_TYPE,
					'editor_settings' => [ 'title' => 'Dropdown' ],
					'settings'        => (object) [],
					'elements'        => array_map( [ __CLASS__, 'build_item' ], $node['children'] ),
				],
			];
		}

		return $model;
	}

	/* ------------------------------------------------------------- helpers */

	private static function type_of( array $element ) {
		return $element['widgetType'] ?? $element['elType'] ?? '';
	}

	/** Unwrap a `{ $$type, value }` envelope; null when the prop is absent. */
	private static function read_prop( array $element, string $key ) {
		$value = $element['settings'][ $key ] ?? null;

		if ( is_array( $value ) && array_key_exists( 'value', $value ) ) {
			return $value['value'];
		}

		return $value;
	}

	private static function write_prop( array $element, string $key, string $type, $value ): array {
		$element['settings'][ $key ] = [ '$$type' => $type, 'value' => $value ];

		return $element;
	}

	private static function read_label( array $item ): string {
		$text = $item['settings']['text']['value']['content']['value'] ?? '';

		return is_string( $text ) ? $text : '';
	}

	/** 7-char hex, matching Elementor's own element ids. */
	private static function gen_id(): string {
		return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
	}
}
