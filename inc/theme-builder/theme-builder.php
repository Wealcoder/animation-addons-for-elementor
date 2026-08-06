<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

use Elementor\Modules\Library\Documents\Library_Document;
use Elementor\Plugin as ElementorPlugin;

if (! defined('ABSPATH')) {
	exit();
} // Exit if accessed directly

class WCF_Theme_Builder
{

	const CPTTYPE  = 'wcf-addons-template';
	const CPT_META = 'wcf-addons-template-meta';


	/**
	 * [$_instance]
	 *
	 * @var null
	 */
	public static $_instance = null;

	/**
	 * Theme-builder documents pulled into this request's asset pass.
	 *
	 * Filled by register_builder_template_assets(); read by
	 * defer_builder_template_styles().
	 *
	 * @var int[]
	 */
	private $builder_template_ids = array();

	/**
	 * Theme-builder stylesheets pulled out of <head> for the editor canvas.
	 *
	 * Filled by defer_builder_template_styles(); printed by
	 * print_builder_template_styles().
	 *
	 * @var array<int, array{handle: string, src: string, media: string}>
	 */
	private $deferred_template_styles = array();

	/**
	 * Per-request memo of get_current_post_by_condition(), keyed by template
	 * type. The resolver runs a `-1` scan of the template CPT (plus, for
	 * slug-based "specific" rules, a query over site content) and is called
	 * 6–12 times per front-end page — from get_header, get_footer, body_class,
	 * template_include and the builder-content actions. Within one request the
	 * conditional tags it branches on do not change, so the answer is a pure
	 * function of the template type: compute once, reuse.
	 *
	 * @var array<string, int|false>
	 */
	private $condition_cache = array();

	/**
	 * [instance] Initializes a singleton instance
	 *
	 */
	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct()
	{

		add_action('init', array($this, 'init'));

		// Add Menu
		add_action('admin_menu', array($this, 'admin_menu'), 225);

		// Print template tabs.
		add_filter('views_edit-' . self::CPTTYPE, array($this, 'print_tabs'));

		// query filter
		add_filter('parse_query', array($this, 'query_filter'));

		// Template type column.
		add_action('manage_' . self::CPTTYPE . '_posts_columns', array($this, 'manage_columns'));
		add_action('manage_' . self::CPTTYPE . '_posts_custom_column', array($this, 'columns_content'), 10, 2);

		// Print template edit popup.
		add_action('admin_footer', array($this, 'print_popup'));

		// Template store ajax action
		add_action('wp_ajax_wcf_save_template', array($this, 'save_template_request'));

		// Get template data Ajax action
		add_action('wp_ajax_wcf_get_template', array($this, 'get_post_By_id'));

		add_action('wp_ajax_wcf_get_posts_by_query', array($this, 'get_posts_by_query'));

		// Load Scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

		// Change Template
		add_filter('template_include', array($this, 'template_loader'), 30);

		// Archive Page
		add_action('animation_addons_archive_builder_content', array($this, 'archive_page_builder_content'));

		// single
		add_action('animation_addons_single_builder_content', array($this, 'single_post_builder_content'));

		// Body classes
		add_filter('body_class', array($this, 'body_classes'));

		// header footer
		add_action('get_header', array($this, 'override_header'));
		add_action('get_footer', array($this, 'override_footer'));
		add_action('animation_addons_header_builder_content', array($this, 'header_builder_content'));
		add_action('animation_addons_footer_builder_content', array($this, 'footer_builder_content'));

		// Must run while wp_head() is still open — see the method docblock.
		add_action('wp_enqueue_scripts', array($this, 'register_builder_template_assets'), 5);

		// …and must survive the editor canvas — see the method docblocks.
		add_action('wp_head', array($this, 'defer_builder_template_styles'), 7);
		add_action('wp_footer', array($this, 'print_builder_template_styles'), 1);
	}

	/**
	 * Pull theme-builder documents into Elementor's asset registration pass.
	 *
	 * WHY THIS EXISTS:
	 * A header/footer template is an Elementor document rendered OUTSIDE the main
	 * loop. templates/header.php calls wp_head() first and only then fires
	 * `animation_addons_header_builder_content`, so the document renders after the
	 * stylesheets for the page have already been printed.
	 *
	 * Elementor's atomic styles manager only walks the MAIN queried document, so
	 * `elementor/atomic-widgets/styles/register` never fired for the header. That
	 * left every atomic style handle it needs (aae-a-nav-css, aae-a-toc-css, …)
	 * unregistered — and wp_enqueue_style() silently does nothing for an unknown
	 * handle, which is why the header CSS was missing from the page entirely rather
	 * than merely arriving late.
	 *
	 * Firing `elementor/post/render` for each template makes Elementor treat it like
	 * any other rendered document, registering its styles while wp_head() is still
	 * open. This mirrors the Tier-1 fix in the Pro plugin's
	 * AtomicV4\Popup\Registry, which solves the identical problem for popups
	 * printed at wp_footer.
	 *
	 * The single/archive CONTENT templates need exactly the same treatment: our
	 * templates/single.php and templates/archive.php also call get_header() (hence
	 * wp_head()) before firing `animation_addons_*_builder_content`, so their
	 * documents render just as late as the header does. Elementor only auto-renders
	 * the MAIN queried post — on a single post that is the post itself, never the
	 * theme-builder template that draws it — so `local-<template-id>-*.css` was
	 * never generated or enqueued at all.
	 *
	 * @return void
	 */
	public function register_builder_template_assets()
	{
		if (is_admin()) {
			return;
		}

		$this->builder_template_ids = $this->get_rendered_template_ids();

		foreach ($this->builder_template_ids as $template_id) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Elementor core hook.
			do_action('elementor/post/render', (string) $template_id);

			// Registration alone still leaves the widget handles to be enqueued at
			// render time, i.e. after wp_head() has closed, so the header would
			// paint unstyled before print_late_styles() catches up at wp_footer.
			// Enqueue this document's own handles now instead.
			if (class_exists('\WCF_ADDONS\AtomicWidgets\Atomic')) {
				\WCF_ADDONS\AtomicWidgets\Atomic::instance()
					->enqueue_document_widget_assets($template_id);
			}
		}
	}

	/**
	 * Take the theme-builder documents' atomic CSS out of <head> in the editor.
	 *
	 * WHY THIS EXISTS — two separate things go wrong in the canvas, and printing
	 * the same stylesheets from wp_footer instead is what fixes both:
	 *
	 * 1. THE LINK IS DELETED. register_builder_template_assets() gets the header /
	 *    footer enqueued in the preview exactly as it does on the front end — the
	 *    browser really does fetch `local-<template-id>-preview-*.css` — and the
	 *    editor then removes the <link> again. `document-elements-styles-provider`
	 *    treats any stylesheet whose id matches
	 *    `/^local-\d+-(preview|frontend)-[a-zA-Z_-]+-css$/` as a "pregenerated
	 *    link", and `removeProviderPregeneratedLinks()` drops every one of them
	 *    from the canvas HEAD once that provider has rendered its own styles. For
	 *    the document being edited that is correct — the editor re-renders those
	 *    styles live, so the file is a stale duplicate. But the pattern keys on
	 *    nothing except the shape of the id, so it matches every post id, including
	 *    theme-builder documents that the editor never re-renders because they are
	 *    not in its element tree.
	 *
	 * 2. EVEN A SURVIVING LINK LOSES THE CASCADE. The editor re-renders the atomic
	 *    BASE styles client-side into <style> elements appended to the canvas head,
	 *    i.e. after everything WordPress printed. `.elementor .e-div-block-base`
	 *    (padding:10px, display:block) and a local style's
	 *    `.elementor .e-<id>-<hash>` are both (0,2,0), so the tie is decided purely
	 *    by document order and the base styles win — the header keeps its markup
	 *    and silently falls back to default padding/display. Moving our links into
	 *    the BODY puts them after every head sheet no matter when the editor
	 *    inserts one, which restores the front end's order.
	 *
	 * Elementor still owns the files and their cache invalidation; only where the
	 * tag is printed changes, and only inside the editor preview.
	 *
	 * The document currently being edited is deliberately left alone — there the
	 * removal is right, and keeping the file would fight the user's live edits with
	 * stale CSS.
	 *
	 * Runs at wp_head:7, i.e. after wp_enqueue_scripts (wp_head:1, where the atomic
	 * styles manager enqueues) and before wp_print_styles (wp_head:8).
	 *
	 * @return void
	 */
	public function defer_builder_template_styles()
	{
		if (empty($this->builder_template_ids) || ! class_exists('\Elementor\Plugin')) {
			return;
		}

		if (! ElementorPlugin::$instance->preview->is_preview_mode()) {
			return;
		}

		$edited_id = (int) ElementorPlugin::$instance->preview->get_post_id();
		$styles    = wp_styles();

		foreach ($this->builder_template_ids as $template_id) {
			if ($template_id === $edited_id) {
				continue;
			}

			foreach ($styles->queue as $handle) {
				if (! preg_match('/^local-(\d+)-preview-[a-zA-Z_-]+$/', $handle, $matches)) {
					continue;
				}

				if ((int) $matches[1] !== $template_id || ! isset($styles->registered[$handle])) {
					continue;
				}

				$style = $styles->registered[$handle];

				if (empty($style->src)) {
					continue;
				}

				$src = $style->src;

				if (! empty($style->ver)) {
					$src = add_query_arg('ver', $style->ver, $src);
				}

				$this->deferred_template_styles[] = array(
					'handle' => $handle,
					'src'    => $src,
					'media'  => is_string($style->args) && '' !== $style->args ? $style->args : 'all',
				);

				wp_dequeue_style($handle);
			}
		}
	}

	/**
	 * Print the stylesheets defer_builder_template_styles() removed from <head>.
	 *
	 * The `aae-tb-` id prefix is not cosmetic: it keeps these tags out of the
	 * pregenerated-link pattern described above, so they survive even if a future
	 * Elementor version widens that sweep from `head` to the whole document.
	 *
	 * @return void
	 */
	public function print_builder_template_styles()
	{
		foreach ($this->deferred_template_styles as $style) {
			printf(
				"<link rel='stylesheet' id='aae-tb-%s-css' href='%s' media='%s' />\n",
				esc_attr($style['handle']),
				esc_url($style['src']),
				esc_attr($style['media'])
			);
		}

		$this->deferred_template_styles = array();
	}

	/**
	 * Theme-builder documents that will actually be printed on this request.
	 *
	 * The content-template branch deliberately mirrors
	 * get_template_loader_default_file() (including its `aaeid` bail-out in
	 * template_loader()) rather than just asking has_template() for every type:
	 * a 'single' template can satisfy get_current_post_by_condition() on a page
	 * where our single.php is never loaded, and registering a document that is
	 * never printed would enqueue dead CSS into <head>.
	 *
	 * @return int[] Unique template post IDs, header/footer first.
	 */
	/**
	 * Page templates that output neither a header nor a footer.
	 *
	 * Elementor's `canvas.php` calls `wp_head()` and `wp_footer()` directly and
	 * never calls `get_header()` / `get_footer()` — read from
	 * `elementor/modules/page-templates/templates/canvas.php`, not assumed.
	 *
	 * @var string[]
	 */
	const HEADLESS_PAGE_TEMPLATES = array('elementor_canvas');

	/**
	 * Will this request actually output our header/footer templates?
	 *
	 * The two answers are reached differently, and only one of them is a guess.
	 *
	 * **The header is provable.** `override_header()` is hooked to `get_header`,
	 * so if `get_header()` is never called our header cannot render — and by the
	 * time `wp_enqueue_scripts` runs, `get_header` has ALREADY fired in every
	 * template that calls it: `get_header()` fires the action, loads header.php,
	 * which calls `wp_head()`, which fires `wp_enqueue_scripts` at priority 1.
	 * Our own `templates/header.php` follows the same order. So
	 * `did_action( 'get_header' ) === 0` here is a fact about this request, not
	 * an inference.
	 *
	 * **The footer is not**, because `get_footer()` fires long after enqueueing
	 * and cannot be observed this early. So when nothing called `get_header()`
	 * we only skip if the page template is one we KNOW outputs neither — which
	 * is the case that matters, since a template that skips `get_header()`
	 * almost always skips `get_footer()` too.
	 *
	 * Erring toward keeping the assets is deliberate: a wrong "skip" renders the
	 * header with no CSS, which is a visibly broken page. A wrong "keep" only
	 * wastes bytes, which is what happens today anyway.
	 *
	 * @return bool
	 */
	private function renders_theme_parts()
	{
		$renders = true;

		if (0 === did_action('get_header')) {
			$template = basename((string) get_page_template_slug());

			if (in_array($template, self::HEADLESS_PAGE_TEMPLATES, true)) {
				$renders = false;
			}
		}

		/**
		 * Override whether header/footer template assets are registered.
		 *
		 * For a custom theme whose template also skips get_header()/get_footer()
		 * — we cannot detect those, so they keep the old behaviour and can opt
		 * out here.
		 *
		 * @param bool $renders Whether the header/footer will be output.
		 */
		return (bool) apply_filters('aae_theme_builder_renders_theme_parts', $renders);
	}

	private function get_rendered_template_ids()
	{
		$template_ids = array();

		// Header and footer are only worth registering if this request will
		// actually OUTPUT them. On an Elementor Canvas page it never does, and
		// registering them anyway ships the whole header+footer asset set to a
		// page that renders neither. Measured on /aae-blank/ (canvas): ~20 dead
		// files — aae-a-menu-js/css, aae-a-nav-js/css, and every
		// local-<header id>-* / local-<footer id>-* / aae_utility_styles-* file
		// for both templates.
		if ($this->renders_theme_parts()) {
			foreach (array('header', 'footer') as $template_type) {
				$template_id = $this->has_template($template_type);

				if ($template_id) {
					$template_ids[] = $template_id;
				}
			}
		}

		// Read-only request check for template routing; no nonce applies.
		$is_routed = ! is_embed()
			&& ! (isset($_REQUEST['aaeid']) && ! isset($_REQUEST['preview_id'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ($is_routed) {
			$content_type = '';

			if (is_singular()) {
				$content_type = 'single';
			} elseif (is_archive() || is_home() || is_search() || is_404() || (function_exists('is_shop') && is_shop())) {
				$content_type = 'archive';
			}

			if ($content_type) {
				$template_id = $this->has_template($content_type);

				if ($template_id) {
					$template_ids[] = $template_id;
				}
			}
		}

		return array_unique(array_map('absint', $template_ids));
	}

	/**
	 * @param $post_id
	 *
	 * @return Library_Document
	 */
	public static function get_document($post_id)
	{
		$document = null;

		try {
			$document = ElementorPlugin::$instance->documents->get($post_id);
		} catch (\Exception $e) {
			// Do nothing.
			unset($e);
		}

		if (! empty($document) && ! $document instanceof Library_Document) {
			$document = null;
		}

		return $document;
	}

	// header footer//

	/**
	 * Function for overriding the header in the elmentor way.
	 */
	public function override_header($name)
	{

		if (! $this->has_template('header')) {
			return;
		}
	
		require WCF_ADDONS_PATH . '/templates/header.php';

		$templates = array();
		$name      = (string) $name;
		if ('' !== $name) {
			$templates[] = "header-{$name}.php";
		}

		$templates[] = 'header.php';

		// Avoid running wp_head hooks again
		remove_all_actions('wp_head');
		ob_start();
		// It cause a `require_once` so, in the get_header it self it will not be required again.
		locate_template($templates, true);
		ob_get_clean();
	}

	/**
	 * Function for overriding the footer in the elmentor way.
	 */
	public function override_footer($name)
	{

		// fixed div ending issues
		if (! $this->has_template('header') && $this->has_template('footer')) {

			$current_template = basename(get_page_template_slug());
			if ($current_template == 'elementor_canvas') {
				return;
			}

			$current_theme = get_template();

			switch ($current_theme) {
				case 'astra':
					echo '</div></div>';
					break;

				default:
					break;
			}
		}

		if (! $this->has_template('footer')) {
			return;
		}

		require WCF_ADDONS_PATH . '/templates/footer.php';

		$templates = array();
		$name      = (string) $name;
		if ('' !== $name) {
			$templates[] = "footer-{$name}.php";
		}

		$templates[] = 'footer.php';

		// Avoid running wp_head hooks again
		remove_all_actions('wp_footer');
		ob_start();
		// It cause a `require_once` so, in the get_header it self it will not be required again.
		locate_template($templates, true);
		ob_get_clean();
	}

	// Set Builder content header footer
	public function header_builder_content()
	{

		$archive_template_id = $this->get_template_id('header');
		if ($archive_template_id != '0') {
			// PHPCS - should not be escaped.
			
			echo self::render_build_content($archive_template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function footer_builder_content()
	{
		$archive_template_id = $this->get_template_id('footer');
		if ($archive_template_id != '0') {
			// PHPCS - should not be escaped.
			echo self::render_build_content($archive_template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function body_classes($classes)
	{

		$class_prefix = 'elementor-page-';

		if (is_singular() && $this->has_template('single')) {
			$classes[] = $class_prefix . self::has_template('single');
		} elseif ((is_archive() || is_home() || is_search()) && $this->has_template('archive')) {
			$classes[] = $class_prefix . self::has_template('archive');
		}

		return $classes;
	}

	/**
	 * Load template.
	 *
	 * @param string $template template to load.
	 *
	 * @return string
	 */
	public function template_loader($template)
	{

		if (is_embed()) {
			return $template;
		}

		// Read-only request check for template routing; no nonce applies.
		if (isset($_REQUEST['aaeid']) && ! isset($_REQUEST['preview_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ($default_file) {
			$template = WCF_ADDONS_PATH . '/templates/' . $default_file;
		}

		return $template;
	}

	/**
	 * Get the default filename for a template except if a block template with
	 * the same name exists.
	 *
	 * @return string
	 * @since  5.5.0 If a block template with the same name exists, return an
	 * empty string.
	 * @since  6.3.0 It checks custom product taxonomies
	 * @since  3.0.0
	 */
	private function get_template_loader_default_file()
	{

		if (is_singular() && $this->has_template('single')) {
			$default_file = 'single.php';
		} elseif ((is_archive() || is_home() || is_search() || is_404() || (function_exists('is_shop') && is_shop())) && $this->has_template('archive')) {
			$default_file = 'archive.php';
		} else {
			$default_file = '';
		}

		return $default_file;
	}

	/**
	 * [has_template]
	 *
	 * @param  [string] $field_key
	 *
	 * @return boolean | int
	 */
	public function has_template($tmpType = '')
	{
		$template_ID = self::get_current_post_by_condition($tmpType);
			
		if ($template_ID) {
			if($tmpType == 'header'){
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				$GLOBALS['aae_header_smoother'] = get_post_meta( $template_ID, 'aae_header_smoother', true ); 
				$offsetY = get_post_meta( $template_ID, 'aae_header_smoother_offsety', true );
				$offsetY = preg_replace( '/[^0-9.\-]/', '', $offsetY );
				if($offsetY && is_numeric($offsetY)){
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					$GLOBALS['aae_header_smoother_offsetY'] = $offsetY;
				}
				
			}
			
			return $template_ID;
		}

		return false;
	}

	/**
	 * [get_template_id]
	 *
	 * @param  [string] $field_key
	 * @param  [string] $meta_key
	 *
	 * @return boolean | int
	 */
	public function get_template_id($tmpType = '')
	{
		$template_ID = self::get_current_post_by_condition($tmpType);

		if ($template_ID) {
			return $template_ID;
		}

		return false;
	}

	function get_ids_from_slugs_any_type($slugs = []) {

		$clean_slugs = array_filter(array_map('sanitize_title', (array) $slugs));

		if (empty($clean_slugs)) {
			return [];
		}

		/*
		 * Target the slugs at the SQL level with post_name__in instead of
		 * loading every post of every type into memory and filtering with a
		 * PHP in_array(). The old form hydrated the entire posts table on each
		 * call — and this runs inside the per-request resolver, so on a large
		 * site it was tens of thousands of post objects materialised per page.
		 * fields=ids and no_found_rows keep it to the ids we actually need.
		 */
		$query = new \WP_Query([
			'post_type'           => 'any',
			'post_status'         => 'any',
			'post_name__in'       => $clean_slugs,
			'fields'              => 'ids',
			'posts_per_page'      => -1,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		]);

		return $query->posts;
	}


	/**
	 * Memoising front door — see $condition_cache. The heavy resolution below
	 * runs at most once per template type per request; every later caller in
	 * the same request gets the cached answer.
	 */
	public function get_current_post_by_condition($tmpType = '')
	{
		if (array_key_exists($tmpType, $this->condition_cache)) {
			return $this->condition_cache[$tmpType];
		}

		return $this->condition_cache[$tmpType] = $this->resolve_current_post_by_condition($tmpType);
	}

	private function resolve_current_post_by_condition($tmpType = '')
	{
		$query_args         = array(
			'post_type'      => self::CPTTYPE,
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'date',
			// Templates are selected by their _type marker meta; meta lookup is required here.
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => self::CPT_META . '_type',
					'value' => $tmpType,
				),
			),
		);
		
		$query              = new \WP_Query($query_args);
		$count              = $query->post_count;
		$templates          = array();
		$templates_specific = array('specifics' => array());

		foreach ($query->posts as $key => $post_id) {

			$location   = get_post_meta(absint($post_id), self::CPT_META . '_location', true);
			$splocation = get_post_meta(absint($post_id), self::CPT_META . '_splocation', true);
		
			if (! empty($location)) {
				if ('specifics' === $location) {
					
					$splocation = json_decode($splocation);
					if(is_array($splocation) && isset($splocation[0]) && !is_numeric($splocation[0])){
						$splocation = $this->get_ids_from_slugs_any_type($splocation);
					}
					
					array_push(
						$templates_specific['specifics'],
						array(
							'id'    => $post_id,
							'posts' => $splocation,
						)
					);
				}elseif($location == '404'){
					$templates['404'] = $post_id;
				} else {
					$templates[$location] = $post_id;
				}

				
			}

			if ($key === $count - 1 && ! empty($templates_specific['specifics'])) {
				
				if (!isset($templates['specifics'])) {
					$templates['specifics'] = [];
				}

				// merge specifics without changing keys
				$templates['specifics'] = array_merge(
					$templates['specifics'],
					$templates_specific['specifics']
				);
				
			}
		}

		wp_reset_postdata();

		if (empty($templates)) {
			return false;
		}


		// check for specific page and post
		if (! is_home() && ! is_archive() && array_key_exists('specifics', $templates)) {
			foreach ($templates['specifics'] as $specific) {
				$key = array_search(get_the_ID(), $specific['posts']);
				if (false !== $key) {
					return $specific['id'];
				}
			}
		}
		
	
		// check 404 page
		if (is_404() && array_key_exists('404', $templates)) {		
			return $templates['404'];
		}

		// check search page
		if (is_search() && array_key_exists('search', $templates)) {
			return $templates['search'];
		}

		// check front page
		if (is_front_page() && array_key_exists('front', $templates)) {
			return $templates['front'];
		}

		// check for blog/posts page
		if (is_home() && array_key_exists('blog', $templates)) {
			return $templates['blog'];
		}

		if (function_exists('is_shop') && is_shop()) {
			// check for WooCommerce shop archive
			if (function_exists('is_shop') && is_shop() && array_key_exists('product-archive', $templates)) {
				return $templates['product-archive'];
			}
		}
		// check for archive
		if (is_archive()) {

			if (is_category() && isset($templates['specifics_cat']) && is_numeric($templates['specifics_cat'])) {
				// get category slug
				$get_queried_object         = get_queried_object();
				$splocation                 = $get_queried_object->slug; // Get the category slug.
				$query_args['meta_query'][] = array(
					'key'     => self::CPT_META . '_location',
					'value'   => 'specifics_cat',
					'compare' => 'LIKE',
				);
				$query                      = new \WP_Query($query_args);
				$cat_id                     = null;
				foreach ($query->posts as $key => $post_id) {
					$location   = get_post_meta(absint($post_id), self::CPT_META . '_location', true);
					$splocation = json_decode(get_post_meta(absint($post_id), self::CPT_META . '_splocation', true));
					if (! empty($location) && ! empty($splocation)) {
						if ('specifics_cat' === $location && $splocation[0] === $get_queried_object->slug) {
							$cat_id = $post_id;
						}
					}
				}
				wp_reset_postdata();
				if (is_numeric($cat_id)) {
					return $cat_id;
				}
			}

			// check for all date archive
			if (is_date() && array_key_exists('date', $templates)) {
				return $templates['date'];
			}

			// check for all author archive
			if (is_author() && array_key_exists('author', $templates)) {
				return $templates['author'];
			}

			// check for custom post type archive
			$custom_archive = get_post_type() . '-archive';

			if (is_tax()) {

				$get_queried_object = get_queried_object();
				$taxonomy           = $get_queried_object->taxonomy; // Get the taxonomy slug.
				$post_types         = get_taxonomy($taxonomy)->object_type; // Get all post types for this taxonomy.

				if (is_array($post_types)) {
					foreach ($post_types as $ptype) {
						$custom_archive = $ptype . '-archive';
						if (array_key_exists($custom_archive, $templates)) {
							return $templates[$custom_archive];
						}
					}
				}
			}

			if (array_key_exists($custom_archive, $templates)) {
				return $templates[$custom_archive];
			}

			// all archives
			if (array_key_exists('archives', $templates)) {
				return $templates['archives'];
			}
		}

		// check for singular
		if (is_singular()) {
			// check for specific post format current post format

			if (is_singular('post') && isset($templates['post-singulars']) && is_numeric($templates['post-singulars'])) {
				// get category slug
				$get_queried_object = get_queried_object();
			
				if(get_post_type($templates['post-singulars']) === 'post') {
				
					$query_args['meta_query'][] = array(
						'key'     => self::CPT_META . '_location',
						'value'   => 'post-singular',
						'compare' => 'LIKE',
					);

					$query  = new \WP_Query($query_args);
					$cat_id = null;
					foreach ($query->posts as $key => $post_id) {
						$format     = get_post_format($post_id) ?: 'standard';
						$location   = get_post_meta(absint($post_id), self::CPT_META . '_location', true);
						$splocation = json_decode(get_post_meta(absint($post_id), self::CPT_META . '_splocation', true));
						if (! empty($location) && ! empty($splocation)) {

							if ('post-singular' === $location && $splocation[0] === $get_queried_object->slug) {
								$cat_id = $post_id;
							}
						}
					}
					wp_reset_postdata();
					if (is_numeric($cat_id)) {
						return $cat_id;
					}
				}

				return $templates['post-singulars'];
			}
			
			// if template type single ignore post type page
			if ((('page' === get_post_type() && !is_front_page() ) || ( self::CPTTYPE === get_post_type() && !is_front_page() )) && 'single' === $tmpType) {
				return false;
			}
			
			// check for custom post type singular
			$custom_single = get_post_type() . '-singular';

			if (array_key_exists($custom_single, $templates)) {
				
				return $templates[$custom_single];
			}

			// all singular
			if (array_key_exists('singulars', $templates) && !is_front_page()) {
			
				return $templates['singulars'];
			}
		}

		// check for global
		if (array_key_exists('global', $templates)) {
			return $templates['global'];
		}
	}

	/**
	 * [get_template_id]
	 *
	 * @param  [string] $field_key
	 * @param  [string] $meta_key
	 *
	 * @return boolean | int
	 */
	public function get_template_popup_id($tmpType, $meta = array())
	{
		$template_ID = self::get_current_popup_by_condition($tmpType, $meta);

		if ($template_ID) {
			return $template_ID;
		}

		return false;
	}

	public function get_current_popup_by_condition($tmpType, $extraConditions)
	{
		$typeCondition = array(
			array(
				'key'   => self::CPT_META . '_type',
				'value' => $tmpType,
			),
		);

		$return_ids = [];
		$poup_data = [];

		$meta_query = array_merge($typeCondition, []);
		$query_args = array(
			'post_type'      => self::CPTTYPE,
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'date',
			// Templates are filtered by their _type marker meta; meta lookup is required here.
			'meta_query'     => array_merge(array('relation' => 'AND'), $meta_query), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		$query              = new \WP_Query($query_args);
		$count              = $query->post_count;
		$templates          = array();
		$templates_specific = array('specifics' => array());

		foreach ($query->posts as $key => $post_id) {
			// to strong
			$location   = get_post_meta(absint($post_id), self::CPT_META . '_location', true);
			$splocation = get_post_meta(absint($post_id), self::CPT_META . '_splocation', true);

			$popup_trigger = get_post_meta($post_id, 'popup_trigger', true);
			$popup_selector = get_post_meta($post_id, 'popup_selector', true);
			$delayTime = get_post_meta($post_id, 'delayTime', true);
			$effect = get_post_meta($post_id, 'effect', true);
			$scrollPostion = get_post_meta($post_id, 'scrollPostion', true);
			$poup_data[$post_id] = array(
				'popup_trigger' => $popup_trigger,
				'popup_selector' => $popup_selector,
				'delayTime' => $delayTime,
				'effect' => $effect,
				'scrollPostion' => $scrollPostion,
			);
			if (! empty($location)) {
				if ('specifics' === $location) {
					array_push(
						$templates_specific['specifics'],
						array(
							'id'    => $post_id,
							'posts' => json_decode($splocation),
						)
					);
				} else {
					if ($location == 404) {
						$location = '_404';
					}
					$templates[$location][] = $post_id;
				}
			}

			if ($key === $count - 1 && ! empty($templates_specific['specifics'])) {
				$templates = array_merge($templates, $templates_specific);
			}
		}
		wp_reset_postdata();
		if (empty($templates)) {
			return false;
		}
		$return_ids = array_merge($return_ids, array_values($templates['global'] ?? []));
		// check for specific page and post
		if (! is_home() && ! is_archive() && array_key_exists('specifics', $templates)) {
			foreach ($templates['specifics'] as $specific) {
				if (isset($specific['posts']) && is_array($specific['posts'])) {
					$key = array_search(get_the_ID(), $specific['posts']);
					if (false !== $key) {
						$return_ids[] = $specific['id'];
					}
				}
			}
		}

		// check 404 page
		if (is_404() && array_key_exists('_404', $templates)) {
			$return_ids = array_merge($return_ids, array_values($templates['_404'] ?? []));
		}

		// check search page
		if (is_search() && array_key_exists('search', $templates)) {
			$return_ids = array_merge($return_ids, array_values($templates['search'] ?? []));
		}

		// check front page
		if (is_front_page() && array_key_exists('front', $templates)) {
			$return_ids = array_merge($return_ids, array_values($templates['front'] ?? []));
		}

		// check for blog/posts page
		if (is_home() && array_key_exists('blog', $templates)) {
			$return_ids = array_merge($return_ids, array_values($templates['blog'] ?? []));
		}


		if (function_exists('is_shop') && is_shop()) {
			// check for WooCommerce shop archive
			if (function_exists('is_shop') && is_shop() && array_key_exists('product-archive', $templates)) {
				$return_ids = array_merge($return_ids, array_values($templates['product-archive'] ?? []));
			}
		}
		// check for archive
		if (is_archive()) {

			if (is_category() && isset($templates['specifics_cat']) && is_numeric($templates['specifics_cat'])) {
			}

			// check for all date archive
			if (is_date() && array_key_exists('date', $templates)) {
				$return_ids = array_merge($return_ids, array_values($templates['date'] ?? []));
			}

			// check for all author archive
			if (is_author() && array_key_exists('author', $templates)) {
				$return_ids = array_merge($return_ids, array_values($templates['author'] ?? []));
			}

			// check for custom post type archive
			$custom_archive = get_post_type() . '-archive';

			if (is_tax()) {

				$get_queried_object = get_queried_object();
				$taxonomy           = $get_queried_object->taxonomy; // Get the taxonomy slug.
				$post_types         = get_taxonomy($taxonomy)->object_type; // Get all post types for this taxonomy.

				if (is_array($post_types)) {
					foreach ($post_types as $ptype) {
						$custom_archive = $ptype . '-archive';
						if (array_key_exists($custom_archive, $templates)) {
							$return_ids = array_merge($return_ids, array_values($templates[$custom_archive] ?? []));
						}
					}
				}
			}

			if (array_key_exists($custom_archive, $templates)) {
				$return_ids = array_merge($return_ids, array_values($templates[$custom_archive] ?? []));
			}

			// all archives
			if (array_key_exists('archives', $templates)) {
				$return_ids = array_merge($return_ids, array_values($templates['archives'] ?? []));
			}
		}

		// check for singular
		if (is_singular()) {
			// check for specific post format current post format
			// if template type single ignore post type page		

			if (is_page() && array_key_exists('allpage', $templates)) {
				$return_ids = array_merge($return_ids, array_values($templates['allpage'] ?? []));
			}

			// check for custom post type singular
			$custom_single = get_post_type() . '-singulars';

			if (array_key_exists($custom_single, $templates)) {
				$return_ids = array_merge($return_ids, array_values($templates[$custom_single] ?? []));
			}

			// all singular
			if (array_key_exists('singulars', $templates)) {
				$return_ids = array_merge($return_ids, array_values($templates['singulars'] ?? []));
			}
		}
		$uniq_ids = array_unique($return_ids);

		$poup_data = array_intersect_key($poup_data, array_flip($uniq_ids));
		return $poup_data;
	}


	/**
	 * Get current page type
	 *
	 * @return string Page Type.
	 * @since  1.0.0
	 */
	public function get_current_page_type()
	{
		$page_type = '';

		if (is_404()) {
			$page_type = 'is_404';
		} elseif (is_search()) {
			$page_type = 'is_search';
		} elseif (is_archive()) {
			$page_type = 'is_archive';

			if (is_category() || is_tag() || is_tax()) {
				$page_type = 'is_tax';
			} elseif (is_date()) {
				$page_type = 'is_date';
			} elseif (is_author()) {
				$page_type = 'is_author';
			} elseif (function_exists('is_shop') && is_shop()) {
				$page_type = 'is_woo_shop_page';
			}
		} elseif (is_home()) {
			$page_type = 'is_home';
		} elseif (is_front_page()) {
			$page_type  = 'is_front_page';
			$current_id = get_the_id();
		} elseif (is_singular()) {
			$page_type  = 'is_singular';
			$current_id = get_the_id();
		} else {
			$current_id = get_the_id();
		}

		return $page_type;
	}

	/**
	 * [render_build_content]
	 *
	 * @param  [int] $id
	 *
	 * @return string
	 */
	public static function render_build_content($id)
	{
		$output   = '';
		$document = ElementorPlugin::instance()->documents->get($id);

		if ($document && $document->is_built_with_elementor()) {
			$output = ElementorPlugin::instance()->frontend->get_builder_content_for_display($id, true);
		} else {
			$content = get_the_content(null, false, $id);

			if (has_blocks($content)) {
				$blocks = parse_blocks($content);
				foreach ($blocks as $block) {
					$output .= do_shortcode(render_block($block));
				}
			} else {
				// 'the_content' is a WordPress core filter, not a plugin-defined hook.
				$content = apply_filters('the_content', $content); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$content = str_replace(']]>', ']]&gt;', $content);

				return $content;
			}
		}

		return $output;
	}

	// Set Builder Content For archive page
	public function archive_page_builder_content()
	{
		$archive_template_id = $this->get_template_id('archive');
		if ($archive_template_id != '0') {
			// PHPCS - should not be escaped.
			echo self::render_build_content($archive_template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	// Set Builder Content For single post
	public function single_post_builder_content()
	{
		$archive_template_id = $this->get_template_id('single');
		if ($archive_template_id != '0') {
			// PHPCS - should not be escaped.
			echo self::render_build_content($archive_template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Print Admin Tab
	 *
	 * @param [array] $views
	 *
	 * @return array
	 */
	public function print_tabs($views)
	{
		$active_class = 'nav-tab-active';
		$current_type = '';
		// Read-only admin list-table tab filter; no nonce applies.
		if (isset($_GET['template_type'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active_class = '';
			$current_type = sanitize_key(wp_unslash($_GET['template_type'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
?>
		<div id="wcf-template-tabs-wrapper" class="nav-tab-wrapper">
			<div class="wcf-menu-area">
				<a class="nav-tab <?php echo esc_attr($active_class); ?>"
					href="edit.php?post_type=<?php echo esc_attr(self::CPTTYPE); ?>">
					<?php echo esc_html__('All', 'animation-addons-for-elementor'); ?>
				</a>
				<?php
				foreach (self::get_offered_template_type() as $tabkey => $tab) {
					// Legacy v3 type on a site that has switched v3 off and owns
					// none of them. query_filter() still honours the GET param,
					// so an old ?template_type=popup bookmark keeps working.
					if (! empty($tab['hidden'])) {
						continue;
					}

					$active_class = ($current_type == $tabkey ? 'nav-tab-active' : '');
					$url          = 'edit.php?post_type=' . self::CPTTYPE . '&template_type=' . $tabkey;

					printf(
						'<a class="nav-tab %s" href="%s">%s</a>',
						esc_attr($active_class),
						esc_url($url),
						esc_html($tab['label'])
					);
				}
				?>
			</div>
		</div>
		<?php
		return $views;
	}

	/**
	 * Manage Template filter by template type
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function query_filter(\WP_Query $query)
	{
		if (! is_admin() || ! empty($query->query_vars['meta_key']) || self::CPTTYPE !== $query->get('post_type')) {
			return;
		}

		// Read-only admin list-table query filter; no nonce applies.
		$template_type = isset($_GET['template_type']) ? sanitize_key(wp_unslash($_GET['template_type'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ('' !== $template_type && 'all' !== $template_type) {
			$type = $template_type;

			// Filtering the admin list table by template type marker meta; meta lookup is required here.
			$query->query_vars['meta_key']     = self::CPT_META . '_type'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->query_vars['meta_value']   = $type; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			$query->query_vars['meta_compare'] = '=';
		}
	}

	/**
	 * Manage Post Table columns
	 *
	 * @param [array] $columns
	 *
	 * @return array
	 */
	public function manage_columns($columns)
	{

		$column_date = $columns['date'];
		unset($columns['date']);

		$columns['type']   = esc_html__('Type', 'animation-addons-for-elementor');
		$columns['status'] = esc_html__('Display', 'animation-addons-for-elementor');
		$columns['date']   = esc_html($column_date);

		return $columns;
	}

	/**
	 * Manage Custom column content
	 *
	 * @param [string] $column_name
	 * @param [int]    $post_id
	 *
	 * @return void
	 */
	public function columns_content($column_name, $post_id)
	{
		$tmpType = get_post_meta($post_id, self::CPT_META . '_type', true);

		if (! array_key_exists($tmpType, self::get_template_type())) {
			return;
		}

		if ($column_name === 'type') {
			// PHPCS - should not be escaped.
			echo isset(self::get_template_type()[$tmpType]) ? '<div class="column-tmptype">' . self::get_template_type()[$tmpType]['label'] . '</div>' : '-'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ($column_name === 'status') {
			$tmpDisplay = get_post_meta($post_id, self::CPT_META . '_location', true);
		?>
			<div class="post-status">
				<strong>Display: </strong>
				<?php echo esc_html($tmpDisplay); ?>
			</div>
		<?php
		}
	}

	/**
	 * Get Template Type
	 *
	 * @return array
	 */
	public static function get_template_type()
	{

		$template_type = array(
			'header'  => array(
				'label'     => esc_html__('Header', 'animation-addons-for-elementor'),
				'optionkey' => 'header',
			),
			'footer'  => array(
				'label'     => esc_html__('Footer', 'animation-addons-for-elementor'),
				'optionkey' => 'footer',
			),
			'archive' => array(
				'label'     => esc_html__('Archive/404/Search', 'animation-addons-for-elementor'),
				'optionkey' => 'archivepage',
			),
			'single'  => array(
				'label'     => esc_html__('Single', 'animation-addons-for-elementor'),
				'optionkey' => 'singlepage',
			),
		);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility hook.
		return apply_filters('wcf_builder_template_types', $template_type);
	}

	/**
	 * Template types that belong to the v3 era and should disappear from the
	 * builder's UI once the legacy surface is switched off.
	 *
	 * `popup` is registered by the Pro plugin (inc/hook.php) and `loop-builder`
	 * by widgets/loop-builder/init.php. Both are listed here rather than being
	 * dropped at their own registration points ON PURPOSE — see
	 * get_offered_template_type().
	 */
	const LEGACY_V3_TEMPLATE_TYPES = array('popup', 'loop-builder');

	/**
	 * The template types the UI should OFFER, as opposed to the ones it must be
	 * able to name.
	 *
	 * Legacy entries are marked `hidden => true`, never removed. Two things
	 * break the moment a type actually leaves get_template_type():
	 *
	 * 1. columns_content() bails on `! array_key_exists( $tmpType, … )`, so an
	 *    existing popup template would lose BOTH its Type and its Display
	 *    column and render as a blank row.
	 * 2. The edit modal's type <select> is built from this same list, and
	 *    theme-builder.js does an unguarded
	 *    `document.querySelector( "#wcf-addons-template-type option[value='popup']" ).selected`.
	 *    With the option gone that throws, the success callback dies before it
	 *    fills in the title or enables "Edit with Elementor" — and because the
	 *    select then sits on its first entry, saving would silently rewrite the
	 *    template's type to `header`.
	 *
	 * A hidden <option> is still selectable from script and still submits, so
	 * marking beats removing on both counts: a NEW template is never offered the
	 * legacy types, while an EXISTING one keeps editing and saving exactly as
	 * before.
	 *
	 * @return array
	 */
	public static function get_offered_template_type()
	{
		$types = self::get_template_type();

		foreach (self::hidden_legacy_template_types() as $key) {
			if (isset($types[$key])) {
				$types[$key]['hidden'] = true;
			}
		}

		return $types;
	}

	/**
	 * Which legacy types to hide right now.
	 *
	 * A type that already has templates behind it is never hidden — the tab is
	 * the only filtered view of them, and taking it away from someone who has
	 * four popups is how a feature reads as "my templates are gone". This
	 * mirrors the ratchet the animation settings use: evidence of v3 keeps the
	 * v3 surface reachable, and only a site with nothing to show gets the clean
	 * UI.
	 *
	 * @return string[]
	 */
	private static function hidden_legacy_template_types()
	{
		if (self::legacy_v3_enabled()) {
			return array();
		}

		$hidden = array();

		foreach (self::LEGACY_V3_TEMPLATE_TYPES as $key) {
			if (! in_array($key, self::template_types_in_use(), true)) {
				$hidden[] = $key;
			}
		}

		return $hidden;
	}

	/**
	 * Is the v3 UI surface still switched on?
	 *
	 * Guarded exactly like the Pro plugin's own callers: an older free plugin
	 * without the class must fall back to SHOWING the tabs, never to hiding
	 * them.
	 *
	 * @return bool
	 */
	private static function legacy_v3_enabled()
	{
		$cls = '\WCF_ADDONS\AnimationSettings\Animation_Settings';

		if (class_exists($cls) && method_exists($cls, 'legacy_v3_enabled')) {
			return (bool) $cls::legacy_v3_enabled();
		}

		return true;
	}

	/**
	 * Every template type that at least one non-trashed template is using.
	 *
	 * One query, memoised per request: this is asked once for the tab bar and
	 * once for the modal's localized payload, both on the same admin screen.
	 *
	 * @return string[]
	 */
	private static function template_types_in_use()
	{
		static $types = null;

		if (null !== $types) {
			return $types;
		}

		global $wpdb;

		// Direct query: counting DISTINCT meta values across a post type has no
		// WP_Query equivalent that does not load the posts themselves.
		$types = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s
				   AND p.post_type = %s
				   AND p.post_status NOT IN ( 'trash', 'auto-draft' )",
				self::CPT_META . '_type',
				self::CPTTYPE
			)
		);

		$types = array_map('strval', (array) $types);

		return $types;
	}

	/**
	 * Get header footer location selection options.
	 *
	 * @return array
	 */
	public static function get_hf_location_selections()
	{
		$args = array(
			'public'            => true,
			'show_in_nav_menus' => true,
		);

		$post_types = get_post_types($args, 'objects');

		$special_pages = array(
			'404'    => esc_html__('404 Page', 'animation-addons-for-elementor'),
			'search' => esc_html__('Search Page', 'animation-addons-for-elementor'),
			'blog'   => esc_html__('Blog / Posts Page', 'animation-addons-for-elementor'),
			'front'  => esc_html__('Front Page', 'animation-addons-for-elementor'),
			'date'   => esc_html__('Date Archive', 'animation-addons-for-elementor'),
			'author' => esc_html__('Author Archive', 'animation-addons-for-elementor'),
		);

		if (class_exists('WooCommerce')) {
			$special_pages['woo-shop'] = esc_html__('WooCommerce Shop Page', 'animation-addons-for-elementor');
		}

		$selection_options = array(
			'basic'         => array(
				'label' => esc_html__('Basic', 'animation-addons-for-elementor'),
				'value' => array(
					''          => esc_html__('None', 'animation-addons-for-elementor'),
					'global'    => esc_html__('Entire Website', 'animation-addons-for-elementor'),
					'singulars' => esc_html__('All Singulars', 'animation-addons-for-elementor'),
					'archives'  => esc_html__('All Archives', 'animation-addons-for-elementor'),
				),
			),

			'special-pages' => array(
				'label' => esc_html__('Special Pages', 'animation-addons-for-elementor'),
				'value' => $special_pages,
			),
		);

		foreach ($post_types as $post_type) {
			if ('page' === $post_type->name) {
				$selection_options[$post_type->name] = array(
					'label' => esc_html($post_type->label),
					'value' => array(
						'all' . $post_type->name => esc_html('All' . $post_type->label),
					),
				);
			} else {
				$selection_options[$post_type->name] = array(
					'label' => esc_html($post_type->label),
					'value' => array(
						$post_type->name . '-archive'   => esc_html($post_type->label . ' Archive'),
						$post_type->name . '-singulars' => esc_html($post_type->label . ' Singulars'),
					),
				);
			}
		}

		$selection_options['specific-target'] = array(
			'label' => esc_html__('Specific Target', 'animation-addons-for-elementor'),
			'value' => array(
				'specifics' => esc_html__('Specific Pages / Posts.', 'animation-addons-for-elementor'),
			),
		);

		/**
		 * Filter options displayed in the display conditions select field of Display conditions.
		 *
		 * @since 1.0.0
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility hook.
		return apply_filters('wcf_display_hf_list', $selection_options);
	}

	/**
	 * Get archive location selection options.
	 *
	 * @return array
	 */
	public static function get_archive_location_selections()
	{
		$args = array(
			'public'            => true,
			'show_in_nav_menus' => true,
		);

		$post_types = get_post_types($args, 'objects');

		// unset unnecessary post type
		unset($post_types['page']);
		unset($post_types['post']);
		// unset( $post_types['product'] );
		unset($post_types[self::CPTTYPE]);

		$special_pages = array(
			'404'    => esc_html__('404 Page', 'animation-addons-for-elementor'),
			'search' => esc_html__('Search Page', 'animation-addons-for-elementor'),
			'blog'   => esc_html__('Blog / Posts Page', 'animation-addons-for-elementor'),
			'date'   => esc_html__('Date Archive', 'animation-addons-for-elementor'),
			'author' => esc_html__('Author Archive', 'animation-addons-for-elementor'),
		);

		$selection_options = array(
			'basic'         => array(
				'label' => esc_html__('Basic', 'animation-addons-for-elementor'),
				'value' => array(
					''         => esc_html__('None', 'animation-addons-for-elementor'),
					'archives' => esc_html__('All Archives', 'animation-addons-for-elementor'),
				),
			),

			'special-pages' => array(
				'label' => esc_html__('Special Pages', 'animation-addons-for-elementor'),
				'value' => $special_pages,
			),
		);

		foreach ($post_types as $post_type) {
			$selection_options[$post_type->name] = array(
				'label' => esc_html($post_type->label),
				'value' => array(
					$post_type->name . '-archive' => esc_html($post_type->label . ' Archive'),
				),
			);
		}

		$selection_options['specific-target'] = array(
			'label' => esc_html__('Specific Target', 'animation-addons-for-elementor'),
			'value' => array(
				'specifics_cat' => esc_html__('Specific Category', 'animation-addons-for-elementor'),
			),
		);

		/**
		 * Filter options displayed in the display conditions select field of Display conditions.
		 *
		 * @since 1.0.0
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return apply_filters('wcf_display_archive_list', $selection_options);
	}

	/**
	 * Get single location selection options.
	 *
	 * @return array
	 */
	public static function get_single_location_selections()
	{
		$args = array(
			'public'            => true,
			'show_in_nav_menus' => true,
		);

		$post_types = get_post_types($args, 'objects');

		// unset unnecessary post type
		unset($post_types['page']);
		// unset( $post_types['product'] );
		unset($post_types[self::CPTTYPE]);

		$selection_options = array(
			'basic' => array(
				'label' => esc_html__('Basic', 'animation-addons-for-elementor'),
				'value' => array(
					'singulars' => esc_html__('All Singular', 'animation-addons-for-elementor'),
				),
			),
		);

		foreach ($post_types as $post_type) {
			$selection_options[$post_type->name] = array(
				'label' => esc_html($post_type->label),
				'value' => array(
					$post_type->name . '-singular' => esc_html($post_type->label . ' Singular'),
				),
			);
		}

		/**
		 * Filter options displayed in the display conditions select field of Display conditions.
		 *
		 * @since 1.0.0
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility hook.
		return apply_filters('wcf_display_archive_list', $selection_options);
	}

	/**
	 * Get single location selection options.
	 *
	 * @return array
	 */
	public static function get_category_location_selections()
	{
		$categories = get_categories(
			array(
				'orderby' => 'name',
				'order'   => 'ASC',
			)
		);

		$selection_options = array();
		foreach ($categories as $cat) {
			$selection_options[$cat->name] = array(
				'label' => esc_html($cat->name),
				'value' => array(
					$cat->slug => esc_html($cat->name . ' ' . $cat->taxonomy),
				),
			);
		}

		/**
		 * Filter options displayed in the display conditions select field of Display conditions.
		 *
		 * @since 1.0.0
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility hook.
		return apply_filters('wcf_display_taxonomy_list', $selection_options);
	}

	/**
	 * Register Builder Custom post
	 *
	 * @return void
	 */
	public function register_custom_post_type()
	{

		$labels = array(
			'name'                  => esc_html_x('AAE Builder', 'Post Type General Name', 'animation-addons-for-elementor'),
			'singular_name'         => esc_html_x('AAE Builder', 'Post Type Singular Name', 'animation-addons-for-elementor'),
			'menu_name'             => esc_html__('AAE Builder', 'animation-addons-for-elementor'),
			'name_admin_bar'        => esc_html__('AAE Builder', 'animation-addons-for-elementor'),
			'archives'              => esc_html__('Template Archives', 'animation-addons-for-elementor'),
			'attributes'            => esc_html__('Template Attributes', 'animation-addons-for-elementor'),
			'parent_item_colon'     => esc_html__('Parent Item:', 'animation-addons-for-elementor'),
			'all_items'             => esc_html__('Templates', 'animation-addons-for-elementor'),
			'add_new_item'          => esc_html__('Add New Template', 'animation-addons-for-elementor'),
			'add_new'               => esc_html__('Add New', 'animation-addons-for-elementor'),
			'new_item'              => esc_html__('New Template', 'animation-addons-for-elementor'),
			'edit_item'             => esc_html__('Edit Template', 'animation-addons-for-elementor'),
			'update_item'           => esc_html__('Update Template', 'animation-addons-for-elementor'),
			'view_item'             => esc_html__('View Template', 'animation-addons-for-elementor'),
			'view_items'            => esc_html__('View Templates', 'animation-addons-for-elementor'),
			'search_items'          => esc_html__('Search Templates', 'animation-addons-for-elementor'),
			'not_found'             => esc_html__('Not found', 'animation-addons-for-elementor'),
			'not_found_in_trash'    => esc_html__('Not found in Trash', 'animation-addons-for-elementor'),
			'featured_image'        => esc_html__('Featured Image', 'animation-addons-for-elementor'),
			'set_featured_image'    => esc_html__('Set featured image', 'animation-addons-for-elementor'),
			'remove_featured_image' => esc_html__('Remove featured image', 'animation-addons-for-elementor'),
			'use_featured_image'    => esc_html__('Use as featured image', 'animation-addons-for-elementor'),
			'insert_into_item'      => esc_html__('Insert into Template', 'animation-addons-for-elementor'),
			'uploaded_to_this_item' => esc_html__('Uploaded to this Template', 'animation-addons-for-elementor'),
			'items_list'            => esc_html__('Templates list', 'animation-addons-for-elementor'),
			'items_list_navigation' => esc_html__('Templates list navigation', 'animation-addons-for-elementor'),
			'filter_items_list'     => esc_html__('Filter from list', 'animation-addons-for-elementor'),
		);

		$args = array(
			'label'               => esc_html__('Theme Builder', 'animation-addons-for-elementor'),
			'description'         => esc_html__('AAE Theme Builder', 'animation-addons-for-elementor'),
			'labels'              => $labels,
			'supports'            => array('title', 'elementor', 'thumbnail'),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'wcf-template',
				'pages'      => false,
				'with_front' => true,
				'feeds'      => false,
			),
			'query_var'           => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
			'rest_base'           => self::CPTTYPE,
		);

		register_post_type(self::CPTTYPE, $args);

		flush_rewrite_rules();
	}

	/**
	 * Print Template edit popup
	 *
	 * @return void
	 */
	public function print_popup()
	{
		// Read-only admin screen check; no nonce applies.
		if (isset($_GET['post_type']) && self::CPTTYPE === sanitize_key(wp_unslash($_GET['post_type']))) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
	<script type="text/template" id="tmpl-wcf-addons-ctppopup">
		<div class="wcf-addons-template-edit-popup-area">
			<div class="wcf-addons-body-overlay"></div>
			<div class="wcf-addons-template-edit-popup">

				<div class="wcf-addons-template-edit-header">
					<h3 class="wcf-addons-template-edit-setting-title">
						{{{data.heading.head}}}
					</h3>
					<span class="wcf-addons-template-edit-cross">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
							class="bi bi-x-lg" viewBox="0 0 16 16"><path
									d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/></svg>
					</span>
				</div>

				<div class="wcf-addons-template-edit-body">

					<div class="wcf-addons-template-edit-field">
						<label class="wcf-addons-template-edit-label">{{{ data.heading.fields.name.title}}}</label>
						<input class="wcf-addons-template-edit-input" id="wcf-addons-template-title" type="text" name="wcf-addons-template-title" placeholder="{{ data.heading.fields.name.placeholder }}"/>
					</div>

					<div class="wcf-addons-template-edit-field">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.type}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-template-type"
								id="wcf-addons-template-type">
							<#
							_.each( data.templatetype, function( item, key ) {

							#>
							<option value="{{ key }}" <# if ( item.hidden ) { #>hidden<# } #>>{{{ item.label }}}</option>
							<#

							} );
							#>
						</select>
					</div>

					<div class="wcf-addons-template-edit-field hf-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-hf-display-type"
								id="wcf-addons-hf-display-type">
							<#
							_.each( data.hflocation, function( items, keys ) {
							#>
							<optgroup label="{{{ items.label }}}">
								<#
								_.each( items.value, function( item, key ) {
								#>
								<option value="{{ key }}">{{{ item }}}</option>
								<#
								} );
								#>
							</optgroup>
							<#
							} );
							#>
						</select>
					</div>

					<div class="wcf-addons-template-edit-field hf-s-location hidden">
						<label class="wcf-addons-template-edit-label"></label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-hf-s-display-type[]"
								id="wcf-addons-hf-s-display-type" multiple="multiple">
						</select>
					</div>

					<div class="wcf-addons-template-edit-field archive-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-archive-display-type"
								id="wcf-addons-archive-display-type">
							<#
							_.each( data.archivelocation, function( items, keys ) {
							#>
							<optgroup label="{{{ items.label }}}">
								<#
								_.each( items.value, function( item, key ) {
								#>
								<option value="{{ key }}">{{{ item }}}</option>
								<#
								} );
								#>
							</optgroup>
							<#
							} );
							#>
						</select>
					</div>

					<div class="wcf-addons-template-edit-field single-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.display}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-single-display-type"
								id="wcf-addons-single-display-type">
							<#
							_.each( data.singlelocation, function( items, keys ) {
							#>
							<optgroup label="{{{ items.label }}}">
								<#
								_.each( items.value, function( item, key ) {
								#>
								<option value="{{ key }}">{{{ item }}}</option>
								<#
								} );
								#>
							</optgroup>
							<#
							} );
							#>
						</select>
					</div>

					<div class="wcf-addons-template-edit-field single-category-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.category}}}</label>
						<select class="wcf-addons-template-edit-input" name="wcf-addons-single-category-display-type"
								id="wcf-addons-single-category-display-type">
							<#								
							_.each( data.postcategory, function( items, keys ) {
							#>                                   
								<#
								_.each( items.value, function( item, key ) {
								#>
								<option value="{{ key }}">{{{ item }}}</option>
								<#
								} );
								#>                                  
							<#
							} );
							#>
						</select>
					</div>
					
					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.trigger}}}</label>
							<select class="wcf-addons-template-edit-input" name="wcf-addons--popup--builder-trigger"
								id="wcf-addons--popup--builder-trigger">
								<option value="click"><?php echo esc_html__('Click', 'animation-addons-for-elementor'); ?></option>
								<option value="pageloaded"><?php echo esc_html__('Page Loaded', 'animation-addons-for-elementor'); ?></option>
								<option value="pageexit"><?php echo esc_html__('Page Body Exist', 'animation-addons-for-elementor'); ?></option>
								<option value="user_inactivity"><?php echo esc_html__('User Inactivity', 'animation-addons-for-elementor'); ?></option>
								<option value="page_scroll"><?php echo esc_html__('Page Scroll', 'animation-addons-for-elementor'); ?></option>
								<option value="page_scroll_up"><?php echo esc_html__('Page Scroll Up', 'animation-addons-for-elementor'); ?></option>
							</select>
					</div>
				
					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.delay}}}</label>
						<input class="wcf-addons-template-edit-input" id="aae-popup-builder-delay" type="number"
								name="aae-popup-builder-delay"
								placeholder="{{ data.heading.fields.delay.placeholder }}">
					</div>

					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label">{{{data.heading.fields.selector}}}</label>
						<input class="wcf-addons-template-edit-input" id="aae-popup-builder-selector" type="text"
								name="aae-popup-builder-selector"
								placeholder=".body">
					</div>
					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label"><?php echo esc_html__('Scroll Postion','animation-addons-for-elementor') ?></label>
						<input class="wcf-addons-template-edit-input" id="aae-popup-builder-scrollPostion" type="text"
								name="aae-popup-builder-scrollPostion"
								placeholder="1500">
					</div>
					
					<div class="wcf-addons-template-edit-field aae-popup-builder-location hidden">
						<label class="wcf-addons-template-edit-label">Effects</label>
							<select class="wcf-addons-template-edit-input" name="wcf-addons--popup--builder-effect"
								id="wcf-addons--popup--builder-effect">
								<option value="flip"><?php echo esc_html__('Flip', 'animation-addons-for-elementor'); ?></option>
								<option value="shakeEffect"><?php echo esc_html__('Scale + Shake Effect', 'animation-addons-for-elementor'); ?></option>
								<option value="slideFromTo"><?php echo esc_html__('Slide From Top', 'animation-addons-for-elementor'); ?></option>
								<option value="zoomBounce"><?php echo esc_html__('Zoom + Bounce', 'animation-addons-for-elementor'); ?></option>
								<option value="fadeSlideup"><?php echo esc_html__('Fade + Slide Up', 'animation-addons-for-elementor'); ?></option>
							</select>
					</div>
							<!-- Header Smoother -->
					<div class="wcf-addons-template-edit-field aae-header-smoother-location hidden">
						<label class="wcf-addons-template-edit-label"><?php echo esc_html__('Smoother?', 'animation-addons-for-elementor'); ?></label>
							<select class="wcf-addons-template-edit-input" name="aae-header-smoother-location"
								id="aae-header-smoother-location">
								<option value=""><?php echo esc_html__('Default', 'animation-addons-for-elementor'); ?></option>
								<option value="yes"><?php echo esc_html__('Yes', 'animation-addons-for-elementor'); ?></option>	
								<option value="no"><?php echo esc_html__('No', 'animation-addons-for-elementor'); ?></option>								
							</select>
					</div>

					<div class="wcf-addons-template-edit-field aae-header-smoother-location yoffset hidden">
						<label class="wcf-addons-template-edit-label"><?php echo esc_html__('OffsetY(px)', 'animation-addons-for-elementor'); ?></label>
							<input class="wcf-addons-template-edit-input" id="aae-header-smoother-yoffset" type="text"
								name="aae-header-smoother-yoffset"
								placeholder="120">
					</div>
					
				</div>
				
				<div class="wcf-addons-template-edit-footer">
					<div class="wcf-addons-template-button-group">
						<div class="wcf-addons-template-button-item wcf-addons-editor-elementor {{ data.haselementor === 'yes' ? 'button-show' : '' }}">
							<button class="wcf-addons-tmp-elementor button">{{{
								data.heading.buttons.elementor.label
								}}}
							</button>
						</div>
						<div class="wcf-addons-template-button-item">
							<button class="wcf-addons-tmp-save button button-primary">{{{
								data.heading.buttons.save.label }}}
							</button>
						</div>
					</div>
				</div>
				
			</div>
		</div>
	</script>
<?php
		}
	}

	/**
	 * Save Template
	 *
	 * @return void
	 */
	public function save_template_request()
	{
		if (isset($_POST)) {

			if (! (current_user_can('manage_options') || current_user_can('edit_others_posts'))) {
				$errormessage = array(
					'message' => esc_html__('You are unauthorize to adding template!', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

			if (! wp_verify_nonce($nonce, 'wcf_tmp_nonce')) {
				$errormessage = array(
					'message' => esc_html__('Nonce Varification Faild !', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$title            = ! empty($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
			$tmpid            = ! empty($_POST['tmpId']) ? sanitize_text_field(wp_unslash($_POST['tmpId'])) : '';
			$tmpType          = ! empty($_POST['tmpType']) ? sanitize_text_field(wp_unslash($_POST['tmpType'])) : 'single';
			$tmplocation      = ! empty($_POST['tmpDisplay']) ? sanitize_text_field(wp_unslash($_POST['tmpDisplay'])) : '';
			$specificsDisplay = ! empty($_POST['specificsDisplay']) ? sanitize_text_field(wp_unslash($_POST['specificsDisplay'])) : '';
			$popupDelay       = ! empty($_POST['tmpDelay']) ? sanitize_text_field(wp_unslash($_POST['tmpDelay'])) : 0;
			$popuptrigger     = ! empty($_POST['tmpTrigger']) ? sanitize_text_field(wp_unslash($_POST['tmpTrigger'])) : 'pageloaded';
			$popupEffect     = ! empty($_POST['tmpEffect']) ? sanitize_text_field(wp_unslash($_POST['tmpEffect'])) : 'flip';
			$selector     = ! empty($_POST['tmpSelector']) ? sanitize_text_field(wp_unslash($_POST['tmpSelector'])) : '';
			$scrollPostion     = ! empty($_POST['tmpScrollPostion']) ? sanitize_text_field(wp_unslash($_POST['tmpScrollPostion'])) : 0;
			$headerSmoother     = ! empty($_POST['tmpHeaderSmoother']) ? sanitize_text_field(wp_unslash($_POST['tmpHeaderSmoother'])) : '';
			$headerSmootheroffset     = ! empty($_POST['tmpHeaderSmootherOffsetY']) ? sanitize_text_field(wp_unslash($_POST['tmpHeaderSmootherOffsetY'])) : '';

			$data = array(
				'title'         => $title,
				'id'            => $tmpid,
				'tmptype'       => $tmpType,
				'tmplocation'   => $tmplocation,
				'tmpSpLocation' => $specificsDisplay,
				'tmpDelay'      => $popupDelay,
				'tmpTrigger'    => $popuptrigger,
				'tmpSelector'    => $selector,
				'tmpScrollPostion'    => $scrollPostion,
				'tmpEffect' => $popupEffect,
				'tmpHeaderSmoother' => $headerSmoother,
				'tmpHeaderSmootherOffsetY' => $headerSmootheroffset
			);

			if ($tmpid) {
				$this->update($data);
			} else {
				$this->insert($data);
			}
		} else {
			$errormessage = array(
				'message' => esc_html__('Post request dose not found', 'animation-addons-for-elementor'),
			);
			wp_send_json_error($errormessage);
		}
	}

	/**
	 * Get Template data by id
	 *
	 * @return void
	 */
	public function get_post_By_id()
	{
		if (isset($_POST)) {

			if (! (current_user_can('manage_options') || current_user_can('edit_others_posts'))) {
				$errormessage = array(
					'message' => esc_html__('You are unauthorize to adding template!', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

			if (! wp_verify_nonce($nonce, 'wcf_tmp_nonce')) {
				$errormessage = array(
					'message' => esc_html__('Nonce Varification Failed !', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$tmpid            = ! empty($_POST['tmpId']) ? sanitize_text_field(wp_unslash($_POST['tmpId'])) : '';
			$postdata         = get_post($tmpid);
			$tmpType          = ! empty(get_post_meta($tmpid, self::CPT_META . '_type', true)) ? get_post_meta($tmpid, self::CPT_META . '_type', true) : 'single';
			$tmpLocation      = ! empty(get_post_meta($tmpid, self::CPT_META . '_location', true)) ? get_post_meta($tmpid, self::CPT_META . '_location', true) : '';
			$specificsDisplay = ! empty(get_post_meta($tmpid, self::CPT_META . '_splocation', true)) ? get_post_meta($tmpid, self::CPT_META . '_splocation', true) : '';
			$tmpDelay         = ! empty(get_post_meta($tmpid, 'delayTime', true)) ? get_post_meta($tmpid, 'delayTime', true) : 0;
			$popupTrigger     = ! empty(get_post_meta($tmpid, 'popup_trigger', true)) ? get_post_meta($tmpid, 'popup_trigger', true) : 'pageloaded';
			$popupEffect     = ! empty(get_post_meta($tmpid, 'effect', true)) ? get_post_meta($tmpid, 'effect', true) : 'flip';
			$popup_selector     = ! empty(get_post_meta($tmpid, 'popup_selector', true)) ? get_post_meta($tmpid, 'popup_selector', true) : '';
			$scrollPostion     = ! empty(get_post_meta($tmpid, 'scrollPostion', true)) ? get_post_meta($tmpid, 'scrollPostion', true) : '';
			$aae_header_smoother     = ! empty(get_post_meta($tmpid, 'aae_header_smoother', true)) ? get_post_meta($tmpid, 'aae_header_smoother', true) : '';
			$header_smootheroffsety     = ! empty(get_post_meta($tmpid, 'aae_header_smoother_offsety', true)) ? get_post_meta($tmpid, 'aae_header_smoother_offsety', true) : '';
			$spLocations      = array();

			if (! empty($specificsDisplay)) {

				foreach (json_decode($specificsDisplay) as $item) {

					// If it's an ID
					if (is_numeric($item)) {

						$post = get_post(intval($item));

						$spLocations[$item] = $post ? $post->post_title : '';

					}

					// If it's a slug or string
					elseif (is_string($item) && ! is_numeric($item)) {

						$slug  = sanitize_text_field($item);
						$post  = get_page_by_path($slug, OBJECT);

						if ($post) {
							$spLocations[$item] = $post->post_title; // Real title
						} else {
							// fallback title if page not found
							$spLocations[$item] = ucwords(str_replace('-', ' ', $slug));
						}
					}
				}
			}

			$data = array(
				'tmpTitle'      => $postdata->post_title,
				'tmpType'       => $tmpType,
				'tmpLocation'   => $tmpLocation,
				'tmpSpLocation' => $spLocations,
				'tmpDelay'      => $tmpDelay,
				'tmpTrigger'    => $popupTrigger,
				'tmpSelector' => $popup_selector,
				'tmpEffect' => $popupEffect,
				'tmpScrollPostion' => $scrollPostion,
				'tmpHeaderSmoother' => $aae_header_smoother,
				'tmpHeaderSmootherOffsetY' => $header_smootheroffsety,
			);
			wp_send_json_success($data);
		} else {
			$errormessage = array(
				'message' => esc_html__('Some thing is worng !', 'animation-addons-for-elementor'),
			);
			wp_send_json_error($errormessage);
		}
	}

	/**
	 * Ajax handeler to return the posts based on the search query.
	 * When searching for the post/pages only titles are searched for.
	 *
	 * @since  1.0.0
	 */
	function get_posts_by_query()
	{

		if (isset($_POST)) {

			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

			if (! wp_verify_nonce($nonce, 'wcf_tmp_nonce')) {
				$errormessage = array(
					'message' => esc_html__('Nonce Varification Faild !', 'animation-addons-for-elementor'),
				);
				wp_send_json_error($errormessage);
			}

			$search_string = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
			$data          = array();
			$result        = array();

			$args = array(
				'public'   => true,
				'_builtin' => false,
			);

			$output     = 'names'; // names or objects, note names is the default.
			$operator   = 'and'; // also supports 'or'.
			$post_types = get_post_types($args, $output, $operator);

			unset($post_types[self::CPTTYPE]); // Exclude wcf post type templates.
			unset($post_types['elementor_library']); // Exclude wcf elementor_library templates.

			$post_types['Posts'] = 'post';
			$post_types['Pages'] = 'page';

			foreach ($post_types as $key => $post_type) {
				$data = array();

				add_filter('posts_search', array($this, 'search_only_titles'), 10, 2);

				$query = new \WP_Query(
					array(
						's'              => $search_string,
						'post_type'      => $post_type,
						'posts_per_page' => -1,
					)
				);

				if ($query->have_posts()) {
					while ($query->have_posts()) {
						$query->the_post();
						$title  = get_the_title();
						$title .= (0 != $query->post->post_parent) ? ' (' . get_the_title($query->post->post_parent) . ')' : '';
						$id     = get_the_id();
						
						$data[] = array(
							'id' => get_post_field('post_name', $id),
							//'id'   => $id,
							'text' => $title,
						);
					}
				}

				if (is_array($data) && ! empty($data)) {
					$result[] = array(
						'text'     => $key,
						'children' => $data,
					);
				}
			}

			$data = array();

			wp_reset_postdata();

			// return the result in json.
			wp_send_json($result);
		} else {
			$errormessage = array(
				'message' => esc_html__('Some thing is worng !', 'animation-addons-for-elementor'),
			);
			wp_send_json_error($errormessage);
		}
	}

	/**
	 * Return search results only by post title.
	 * This is only run from hfe_get_posts_by_query()
	 *
	 * @param  (string)   $search   Search SQL for WHERE clause.
	 * @param  (WP_Query) $wp_query The current WP_Query object.
	 *
	 * @return (string) The Modified Search SQL for WHERE clause.
	 */
	function search_only_titles($search, $wp_query)
	{
		if (! empty($search) && ! empty($wp_query->query_vars['search_terms'])) {
			global $wpdb;

			$q = $wp_query->query_vars;
			$n = ! empty($q['exact']) ? '' : '%';

			$search = array();

			foreach ((array) $q['search_terms'] as $term) {
				$search[] = $wpdb->prepare("$wpdb->posts.post_title LIKE %s", $n . $wpdb->esc_like($term) . $n);
			}

			if (! is_user_logged_in()) {
				$search[] = "$wpdb->posts.post_password = ''";
			}

			$search = ' AND ' . implode(' AND ', $search);
		}

		return $search;
	}

	/**
	 * Template Insert
	 *
	 * @param [array] $data
	 *
	 * @return void
	 */
	public function insert($data)
	{

		$args        = array(
			'post_type'   => self::CPTTYPE,
			'post_status' => $data['tmptype'] == 'popup' ? 'draft' : 'publish',
			'post_title'  => $data['title'],
		);
		$new_post_id = wp_insert_post($args);

		if ($new_post_id) {
			$return = array(
				'message' => esc_html__('Template has been inserted', 'animation-addons-for-elementor'),
				'id'      => $new_post_id,
			);

			// Meta data
			update_post_meta($new_post_id, self::CPT_META . '_type', $data['tmptype']);
			update_post_meta($new_post_id, self::CPT_META . '_location', $data['tmplocation']);
			update_post_meta($new_post_id, '_elementor_edit_mode', 'builder');
			update_post_meta($new_post_id, '_wp_page_template', 'elementor_canvas');

			// specific page and post template header footer
			if ('header' === $data['tmptype'] || 'footer' === $data['tmptype']) {
				update_post_meta($new_post_id, self::CPT_META . '_splocation', $data['tmpSpLocation']);
				update_post_meta($new_post_id, 'aae_header_smoother', $data['tmpHeaderSmoother']);
				update_post_meta($new_post_id, 'aae_header_smoother_offsety', $data['tmpHeaderSmootherOffsetY']);

			}

			if ('archive' === $data['tmptype'] && 'specifics_cat' === $data['tmplocation']) {
				update_post_meta($new_post_id, self::CPT_META . '_splocation', $data['tmpSpLocation']);
			}

			if ('post-singular' === $data['tmplocation'] && 'single' === $data['tmptype']) {
				update_post_meta($new_post_id, self::CPT_META . '_splocation', $data['tmpSpLocation']);
			}

			if ('popup' === $data['tmptype']) {
				update_post_meta($new_post_id, 'delayTime', $data['tmpDelay']);
				update_post_meta($new_post_id, 'popup_trigger', $data['tmpTrigger']);
				update_post_meta($new_post_id, 'popup_selector', $data['tmpEffect']);
				update_post_meta($new_post_id, 'effect', $data['tmpEffect']);
				update_post_meta($new_post_id, 'scrollPostion', $data['tmpScrollPostion']);

				update_post_meta($new_post_id, self::CPT_META . '_splocation', $data['tmpSpLocation']);
			}

			wp_send_json_success($return);
		} else {
			$errormessage = array(
				'message' => esc_html__('Some thing is worng !', 'animation-addons-for-elementor'),
			);
			wp_send_json_error($errormessage);
		}
	}

	/**
	 * Template Update
	 *
	 * @param [array] $data
	 *
	 * @return void
	 */
	public function update($data)
	{

		$update_post_args = array(
			'ID'         => $data['id'],
			'post_title' => $data['title'],
		);
		wp_update_post($update_post_args);

		// Update Meta data
		update_post_meta($data['id'], self::CPT_META . '_type', $data['tmptype']);
		update_post_meta($data['id'], self::CPT_META . '_location', $data['tmplocation']);

		// specific page and post template header footer
		if ('header' === $data['tmptype'] || 'footer' === $data['tmptype']) {
			update_post_meta($data['id'], self::CPT_META . '_splocation', $data['tmpSpLocation']);
			update_post_meta($data['id'], 'aae_header_smoother', $data['tmpHeaderSmoother']);
			update_post_meta($data['id'], 'aae_header_smoother_offsety', $data['tmpHeaderSmootherOffsetY']);
		} else {
			delete_post_meta($data['id'], self::CPT_META . '_splocation');
		}

		if ('archive' === $data['tmptype'] && 'specifics_cat' === $data['tmplocation']) {
			update_post_meta($data['id'], self::CPT_META . '_splocation', $data['tmpSpLocation']);
		}

		if ('post-singular' === $data['tmplocation'] && 'single' === $data['tmptype']) {
			update_post_meta($data['id'], self::CPT_META . '_splocation', $data['tmpSpLocation']);
		}

		if ('popup' === $data['tmptype']) {
			update_post_meta($data['id'], 'delayTime', $data['tmpDelay']);
			update_post_meta($data['id'], 'popup_trigger', $data['tmpTrigger']);
			update_post_meta($data['id'], 'popup_selector', $data['tmpSelector']);
			update_post_meta($data['id'], 'effect', $data['tmpEffect']);
			update_post_meta($data['id'], 'scrollPostion', $data['tmpScrollPostion']);

			update_post_meta($data['id'], self::CPT_META . '_splocation', $data['tmpSpLocation']);
		}

		$return = array(
			'message' => esc_html__('Template has been updated', 'animation-addons-for-elementor'),
		);
		wp_send_json_success($return);
	}

	/**
	 * Manage Scripts
	 *
	 * @param [string] $hook
	 *
	 * @return void
	 */
	public function enqueue_scripts($hook)
	{

		// Read-only admin screen check; no nonce applies.
		if (isset($_GET['post_type']) && self::CPTTYPE === sanitize_key(wp_unslash($_GET['post_type']))) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// CSS
			wp_enqueue_style('select2', WCF_ADDONS_URL . '/assets/css/select2.min.css', array(), WCF_ADDONS_VERSION);
			wp_enqueue_style('wcf-theme-builder', WCF_ADDONS_URL . '/assets/css/theme-builder.min.css', array(), WCF_ADDONS_VERSION);

			// JS
			wp_enqueue_script('select2', WCF_ADDONS_URL . 'assets/js/select2.min.js', array('jquery'), WCF_ADDONS_VERSION, true);
			wp_enqueue_script(
				'wcf-theme-builder',
				WCF_ADDONS_URL . 'assets/js/theme-builder.js',
				array(
					'jquery',
					'wp-util',
				),
				WCF_ADDONS_VERSION,
				true
			);

			$localize_data = array(
				'ajaxurl'         => admin_url('admin-ajax.php'),
				'nonce'           => wp_create_nonce('wcf_tmp_nonce'),
				'adminURL'        => admin_url(),
				'hflocation'      => self::get_hf_location_selections(),
				'archivelocation' => self::get_archive_location_selections(),
				'singlelocation'  => self::get_single_location_selections(),
				'postcategory'    => self::get_category_location_selections(),
				'templatetype'    => self::get_offered_template_type(),
				'labels'          => array(
					'fields'  => array(
						'name'     => array(
							'title'       => esc_html__('Name', 'animation-addons-for-elementor'),
							'placeholder' => esc_html__('Enter a template name', 'animation-addons-for-elementor'),
						),
						'type'     => esc_html__('Type', 'animation-addons-for-elementor'),
						'display'  => esc_html__('Display', 'animation-addons-for-elementor'),
						'category' => esc_html__('Category', 'animation-addons-for-elementor'),
						'delay'    => esc_html__('Delay', 'animation-addons-for-elementor'),
						'trigger'  => esc_html__('Trigger', 'animation-addons-for-elementor'),
						'selector' => esc_html__('Selector', 'animation-addons-for-elementor'),
					),
					'head'    => esc_html__('Template Settings', 'animation-addons-for-elementor'),
					'buttons' => array(
						'elementor' => array(
							'label' => esc_html__('Edit With Elementor', 'animation-addons-for-elementor'),
							'link'  => '#',
						),
						'save'      => array(
							'label'  => esc_html__('Save Settings', 'animation-addons-for-elementor'),
							'saving' => esc_html__('Saving...', 'animation-addons-for-elementor'),
							'saved'  => esc_html__('All Data Saved', 'animation-addons-for-elementor'),
							'link'   => '#',
						),
					),
				),
			);
			wp_localize_script('wcf-theme-builder', 'WCF_Theme_Builder', $localize_data);
		}
	}

	/**
	 * [init] Assets Initializes
	 *
	 * @return [void]
	 */
	public function init()
	{
		// Register Custom Post Type
		$this->register_custom_post_type();
	}

	/**
	 * [admin_menu] Add Post type Submenu
	 *
	 * @return void
	 */
	public function admin_menu()
	{
		$link_custom_post = 'edit.php?post_type=' . self::CPTTYPE;
		add_submenu_page(
			'wcf_addons_page',
			esc_html__('Theme Builder', 'animation-addons-for-elementor'),
			esc_html__('Theme Builder', 'animation-addons-for-elementor'),
			'manage_options',
			$link_custom_post,
			null
		);
	}
}

WCF_Theme_Builder::instance();