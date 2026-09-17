<?php

namespace Wealcoder\AnimationAddons\Extensions;

use Wealcoder\AnimationAddons\Nonce;
use ZipArchive;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (class_exists('\Wealcoder\AnimationAddons\Extensions\CustomIcons')) {
	return;
}

if (aaeaddon_pro_defined( 'VERSION' ) && version_compare(aaeaddon_pro_constant( 'VERSION' ), '2.4.11', '<=')) {
	return;
}


/**
 * Class CustomIcons
 *
 * Handles custom icon management for the plugin, including uploading and processing custom icon zip files,
 * managing icon settings, and integrating with Elementor's icon manager.
 *
 * @package Wealcoder\AnimationAddons\Extensions
 * @since 1.0.0
 */

class CustomIcons_Lite
{

	/** @var array Configuration settings for custom icons */
	public $configs = [];

	/** @var string Meta key for custom icons data */
	public $meta_key = 'wcf_addon_custom_icons';
	public $meta_active_key = 'aae_gl_load';

	/** @var string Custom post type for managing custom icons */
	public $post_type = 'wcf-custom-icons';

	/** @var string|null Process ID for current icon management */
	public $process_id = null;

	/** @var string|null The uploaded icon file name */
	public $file_name = null;

	/** @var string|null The prefix for icon class names */
	public $icon_prefix = null;

	/** @var string|null The postfix for icon class names */
	public $icon_postfix = null;
	public $all_posts = [];

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 *
	 * @var Plugin The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return Plugin An instance of the class.
	 * @since 1.2.0
	 * @access public
	 */
	public static function instance()
	{

		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	/**
	 *  Plugin class constructor. Front-end hooks only; the wp-admin half is in
	 *  class-custom-icon-admin.php, required under is_admin().
	 */
	public function __construct()
	{
		add_action('init', [$this, 'custom_post_type']);
		add_action('wp_enqueue_scripts', [$this, 'frontend_scripts'], 7);

		if (is_admin()) {
			require_once __DIR__ . '/class-custom-icon-admin.php';
			CustomIcons_Icon_Admin::instance();
		}
	}


	/**
	 * Creates a unique slug from a post title.
	 *
	 * @param string $title The post title.
	 * @return string The generated slug.
	 * @since 1.0.0
	 */
	public static function createUniqueSlug($title)
	{
		$slug = preg_replace('/[^a-z0-9]+/i', '-', trim(strtolower($title)));
		$slug = rtrim($slug, '-');
		$uniqueSlug = 'aae' . $slug . '-iset';
		return $uniqueSlug;
	}

	public function frontend_scripts()
	{
		$args = [
			'numberposts' => 15, // Limit number of posts
			'post_type' => $this->post_type,
			'post_status' => 'any',
			// Listing custom-icon posts by their marker meta key; capped at 15 posts.
			'meta_key'   => 'wcf_addon_custom_icons', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		];

		$posts = get_posts($args);

		if (is_array($posts)) {
			foreach ($posts as $post) {
				// check icomoon
				if (get_post_meta($post->ID, 'wcf_addon_custom_icontype', true) == 'icomoon') {
					$slug =	self::createUniqueSlug($post->post_title);
					$metainfo = get_post_meta($post->ID, 'wcf_addon_custom_icons', true);
					$aae_gl_load = get_post_meta($post->ID, 'aae_gl_load', true);
					if (isset($metainfo['elementor_path']) && isset($metainfo['elementor_style']) && $aae_gl_load == 'yes') {
						$style_file = wp_upload_dir()['basedir'] . '/' . $metainfo['elementor_style'];
						$style      = wp_upload_dir()['baseurl'] . '/' . $metainfo['elementor_style'];

						if (file_exists($style_file)) {
							wp_enqueue_style($slug, $style, array(), AAEADDON_VERSION, 'all');
						}
					}
				}
			}
		}
	}

	function custom_post_type()
	{
		$labels = array(
			'name'                  => _x('Custom Icons', 'Post type general name', 'animation-addons-for-elementor'),
			'singular_name'         => _x('Custom Icon', 'Post type singular name', 'animation-addons-for-elementor'),
			'menu_name'             => _x('Custom Icons', 'Admin Menu text', 'animation-addons-for-elementor'),
			'name_admin_bar'        => _x('Custom Icon', 'Add New on Toolbar', 'animation-addons-for-elementor'),
			'add_new'               => __('Add New', 'animation-addons-for-elementor'),
			'add_new_item'          => __('Add New Icon', 'animation-addons-for-elementor'),
			'new_item'              => __('New Icon', 'animation-addons-for-elementor'),
			'edit_item'             => __('Edit Icon', 'animation-addons-for-elementor'),
			'view_item'             => __('View Icon', 'animation-addons-for-elementor'),
			'all_items'             => __('All Icons', 'animation-addons-for-elementor'),
			'search_items'          => __('Search Icon', 'animation-addons-for-elementor'),
			'parent_item_colon'     => __('Parent Icons:', 'animation-addons-for-elementor'),
			'not_found'             => __('No Icon found.', 'animation-addons-for-elementor'),
			'not_found_in_trash'    => __('No Icon found in Trash.', 'animation-addons-for-elementor'),
			'featured_image'        => _x('Icon Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor'),
			'set_featured_image'    => _x('Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor'),
			'remove_featured_image' => _x('Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor'),
			'use_featured_image'    => _x('Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'animation-addons-for-elementor'),
			'archives'              => _x('Icon archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'animation-addons-for-elementor'),
			'insert_into_item'      => _x('Insert into Icon', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'animation-addons-for-elementor'),
			'uploaded_to_this_item' => _x('Uploaded to this Icon', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'animation-addons-for-elementor'),
			'filter_items_list'     => _x('Filter Icons list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'animation-addons-for-elementor'),
			'items_list_navigation' => _x('Icons list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'animation-addons-for-elementor'),
			'items_list'            => _x('Icons list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'animation-addons-for-elementor'),
		);
		register_post_type(
			$this->post_type,
			array(
				'labels'      => $labels,
				'public'              => true,
				'menu_icon'           => 'dashicons-text-page',
				'supports'            => ['title'],
				'exclude_from_search' => true,
				'has_archive'         => false,
				'publicly_queryable'  => false,
				'hierarchical'        => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'show_in_admin_bar'   => false,
			)
		);
	}
}

CustomIcons_Lite::instance();
