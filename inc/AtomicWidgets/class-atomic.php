<?php

/**
 * AAE Atomic Widgets Bootstrap
 *
 * Handles initialization, registration, and enable/disable logic
 * for AAE's custom atomic widgets.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

final class Atomic
{

	/**
	 * Minimum Elementor version that supports atomic widgets.
	 */
	const MIN_ELEMENTOR_VERSION = '4.0.0';

	/**
	 * WordPress option name for storing atomic widget states.
	 */
	const OPTION_NAME = 'aae_atomic_widgets';

	/**
	 * WordPress option name for storing atomic extension states.
	 */
	const EXTENSIONS_OPTION_NAME = 'aae_atomic_extensions';

	/**
	 * Singleton instance.
	 *
	 * @var Atomic|null
	 */
	private static $instance = null;

	/**
	 * Registry of available atomic widgets.
	 *
	 * Each entry: slug => [
	 *   'label'       => string   Human-readable name,
	 *   'description' => string   Short description,
	 *   'icon'        => string   Elementor icon CSS class,
	 *   'is_pro'      => bool     Whether it requires pro,
	 *   'default'     => bool     Default enabled state (on fresh install),
	 *   'keywords'    => string[] Search keywords,
	 *   'category'    => string   Widget group for dashboard display,
	 * ]
	 *
	 * @var array
	 */
	private $widgets_registry = [];

	/**
	 * Registry of available atomic extensions.
	 *
	 * @var array
	 */
	private $extensions_registry = [];

	/**
	 * Cached active (enabled) widget slugs.
	 *
	 * @var string[]|null
	 */
	private $active_widgets = null;

	/**
	 * Cached active (enabled) extension slugs.
	 *
	 * @var string[]|null
	 */
	private $active_extensions = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Atomic
	 */
	public static function instance(): self
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct()
	{

		$this->register_widget_definitions();
		$this->register_extension_definitions();
		$this->init_hooks();
	}

	/* =====================================================================
	 *  Public API
	 * =================================================================== */

	/**
	 * Get all registered atomic widget definitions.
	 *
	 * @return array
	 */
	public function get_widgets_registry(): array
	{
		return $this->widgets_registry;
	}

	/**
	 * Get the saved option value (associative: slug => true).
	 *
	 * @return array
	 */
	public function get_saved_options(): array
	{
		$option = get_option(self::OPTION_NAME);

		return is_array($option) ? $option : [];
	}

	/**
	 * The EDITOR preview sample post for the current-post widgets (Post Title /
	 * Post Image).
	 *
	 * Resolution order:
	 *   1. The document's "Preview Settings" page setting (`aae_loop_page_post`,
	 *      registered by the Pro plugin's WCF_Page_Loop_Settings) — the user's
	 *      explicit choice always wins.
	 *   2. A random published post that HAS a featured image — without this the
	 *      editor shows the edited page's title and a gray placeholder (pages
	 *      rarely have thumbnails), which reads as broken.
	 *
	 * Cached per-request so every widget previews the SAME post (title matches
	 * image).
	 *
	 * @return \WP_Post|false Post object, or false when none qualifies.
	 */
	public static function get_sample_post()
	{
		static $sample = null;
		if (null !== $sample) {
			return $sample;
		}

		$sample = false;

		// 1) Explicit choice from Page Settings → Preview Settings.
		$chosen = self::get_preview_setting_post();
		if ($chosen) {
			$sample = $chosen;
			return $sample;
		}

		// 2) Random fallback. A handful of candidates: a post can carry a stale
		// _thumbnail_id whose attachment is gone, so verify the URL resolves.
		$candidates = get_posts([
			'post_type'   => 'post',
			'post_status' => 'publish',
			'numberposts' => 5,
			'orderby'     => 'rand',
			'meta_key'    => '_thumbnail_id',
		]);

		foreach ($candidates as $candidate) {
			if (get_the_post_thumbnail_url($candidate, 'large')) {
				$sample = $candidate;
				break;
			}
		}

		return $sample;
	}

	/**
	 * The post chosen in the document's Page Settings → Preview Settings
	 * (`aae_loop_page_post`). False when unset / invalid / Pro inactive.
	 *
	 * @return \WP_Post|false
	 */
	private static function get_preview_setting_post()
	{
		if (! class_exists('\Elementor\Core\Settings\Manager')) {
			return false;
		}

		$doc_id = 0;
		if (isset(\Elementor\Plugin::$instance->editor)) {
			$doc_id = (int) \Elementor\Plugin::$instance->editor->get_post_id();
		}
		if (! $doc_id) {
			$doc_id = (int) get_the_ID();
		}
		if (! $doc_id) {
			return false;
		}

		try {
			$manager = \Elementor\Core\Settings\Manager::get_settings_managers('page');
			$model   = $manager ? $manager->get_model($doc_id) : null;
			$chosen  = $model ? absint($model->get_settings('aae_loop_page_post')) : 0;
		} catch (\Throwable $e) {
			return false;
		}

		if (! $chosen) {
			return false;
		}

		$post = get_post($chosen);

		return ($post && 'publish' === $post->post_status) ? $post : false;
	}

	/**
	 * Get slugs of currently enabled atomic widgets.
	 *
	 * @return string[]
	 */
	public function get_active_widgets(): array
	{
		if (is_null($this->active_widgets)) {
			$saved = $this->get_saved_options();
			$this->active_widgets = ! empty($saved) ? array_keys($saved) : [];
		}

		return $this->active_widgets;
	}

	/**
	 * Check whether a specific atomic widget is enabled.
	 *
	 * @param string $slug Widget slug.
	 *
	 * @return bool
	 */
	public function is_widget_active(string $slug): bool
	{
		// Force internal child widgets to be active always
		$internal_widgets = [
			'aae-a-slide',
			'aae-a-slider-track',
			'aae-a-slider-nav-prev',
			'aae-a-slider-nav-next',
			'aae-a-slider-pagination',
			'aae-a-slider-dot',
			'aae-a-slider-indicators',
			'aae-a-slider-current',
			'aae-a-slider-total',
			'aae-a-slider-percentage',
			'aae-a-slider-progress',
			'aae-a-slider-counter',
			'aae-a-slider-divider',
			'aae-a-slider-progress-fill',
			'aae-a-counter-number',
			'aae-a-accordion-item',
			'aae-a-icon-list-item',	
			'aae-a-countdown-unit',
			'aae-a-toggle-pane',
			'aae-a-video-mask-btn',
			'aae-a-flip-box-face',
			'aae-a-post-card',
			'aae-a-offcanvas-panel',
			'aae-a-timeline-item',
			'aae-a-social-share-item',
			'aae-a-nav-item',
			'aae-a-nav-sub-item',
			'aae-a-mobile-nav',
			// Loop Grid structural pieces — always-on internal elements. The Loop
			// Grid seeds them as default children, so they must be registered even
			// when not toggled in the dashboard (otherwise the editor throws
			// ElementTypeNotFound on drop and nothing renders).
			'aae-a-loop-item',
			'aae-a-loop-layout',
			'aae-a-loop-pagination',
			'aae-a-loop-prev',
			'aae-a-loop-next',
			'aae-a-loop-numbers',
			'aae-a-loop-loadmore',
			'aae-a-loop-arrow',
			'aae-a-loop-nav-wrap',
			// Loop Grid current-post building blocks: seeded as default loop-item
			// children — must always be registered so the featured image / title /
			// meta resolve per post.
			'aae-a-post-image',
			'aae-a-post-title',
			'aae-a-post-meta',
			'aae-a-post-meta-item',
		];
		if (in_array($slug, $internal_widgets)) {
			return true;
		}

		$saved = $this->get_saved_options();

		return isset($saved[$slug]);
	}

	/* =====================================================================
	 *  Extensions Public API
	 * =================================================================== */

	/**
	 * Get all registered atomic extension definitions.
	 *
	 * @return array
	 */
	public function get_extensions_registry(): array
	{
		return $this->extensions_registry;
	}

	/**
	 * Get the saved extension option value (associative: slug => true).
	 *
	 * @return array
	 */
	public function get_saved_extension_options(): array
	{
		$option = get_option(self::EXTENSIONS_OPTION_NAME);

		return is_array($option) ? $option : [];
	}

	/**
	 * Get slugs of currently enabled atomic extensions.
	 *
	 * @return string[]
	 */
	public function get_active_extensions(): array
	{
		if (is_null($this->active_extensions)) {
			$saved = $this->get_saved_extension_options();
			$this->active_extensions = ! empty($saved) ? array_keys($saved) : [];
		}

		return $this->active_extensions;
	}

	/**
	 * Check whether a specific atomic extension is enabled.
	 *
	 * @param string $slug Extension slug.
	 *
	 * @return bool
	 */
	public function is_extension_active(string $slug): bool
	{
		$saved = $this->get_saved_extension_options();

		return isset($saved[$slug]);
	}

	/**
	 * Get the full config array to pass to the React dashboard.
	 *
	 * Structure mirrors the existing `wcf_addons_dashboard_config` format
	 * so the same React component tree can render it.
	 *
	 * @return array
	 */
	public function get_dashboard_config(): array
	{
		$saved   = $this->get_saved_options();
		$widgets = [];

		foreach ($this->widgets_registry as $slug => $def) {
			$widgets[$slug] = array_merge($def, [
				'is_active' => isset($saved[$slug]),
			]);
		}

		$ext_saved    = $this->get_saved_extension_options();
		$extensions   = [];

		foreach ($this->extensions_registry as $slug => $def) {
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

	private function register_widget_definitions(): void
	{
		$this->widgets_registry = [

			// 'aae-a-menu' => [
			// 	'label'        => 'Menu',
			// 	'description'  => 'A modern standard navigation menu with GSAP interactions.',
			// 	'icon'         => 'eicon-nav-menu',
			// 	'is_pro'       => false,
			// 	'is_extension' => false,
			// 	'is_upcoming'  => false,
			// 	'default'      => true,
			// 	'keywords'     => [
			// 		'menu',
			// 		'nav',
			// 		'navigation',
			// 		'atomic',
			// 		'gsap',
			// 	],
			// 	'category'     => 'general',
			// 	'order'        => 0,
			// 	'demo_url'     => '',
			// 	'doc_url'      => '',
			// ],

			'aae-a-post-title' => [
				'label'        => 'Post Title',
				'description'  => 'Dynamically displays the current post title natively in Elementor V4.',
				'icon'         => 'eicon-post-title',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'post',
					'title',
					'heading',
					'atomic',
					'dynamic',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-post-image' => [
				'label'        => 'Post Image',
				'description'  => 'Dynamically displays the current post featured image natively in Elementor V4.',
				'icon'         => 'eicon-featured-image',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => [
					'post',
					'image',
					'featured',
					'atomic',
					'dynamic',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-post-meta' => [
				'label'        => 'Post Meta',
				'description'  => 'Dynamically displays post author, date, comments, taxonomy, or custom fields in Elementor V4.',
				'icon'         => 'eicon-post-info',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'post',
					'meta',
					'info',
					'author',
					'date',
					'comments',
					'atomic',
					'dynamic',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-post-meta-item' => [
				'label'        => 'Post Meta Item',
				'description'  => 'Internal child item for Post Meta.',
				'icon'         => 'eicon-post-info',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'post meta item',
					'internal',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-loop-item' => [
				'label'        => 'Loop Item',
				'description'  => 'Container widget for Loop Grid items with default flex column layout.',
				'icon'         => 'eicon-container',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Item',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'loop',
					'item',
					'container',
					'flex',
					'column',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
				'default_children' => [],
			],

			'aae-a-loop-layout' => [
				'label'        => 'Loop Layout',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Layout',
				'icon'         => 'eicon-loop-builder',
				'keywords'     => [ 'loop', 'layout' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-pagination' => [
				'label'        => 'Loop Pagination',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Pagination',
				'icon'         => 'eicon-ellipsis-h',
				'keywords'     => [ 'loop', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-prev' => [
				'label'        => 'Loop Previous',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Prev',
				'icon'         => 'eicon-chevron-left',
				'keywords'     => [ 'loop', 'prev', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-next' => [
				'label'        => 'Loop Next',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Next',
				'icon'         => 'eicon-chevron-right',
				'keywords'     => [ 'loop', 'next', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-numbers' => [
				'label'        => 'Loop Page Numbers',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Numbers',
				'icon'         => 'eicon-number-field',
				'keywords'     => [ 'loop', 'numbers', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-loadmore' => [
				'label'        => 'Loop Load More',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_LoadMore',
				'icon'         => 'eicon-plus-circle',
				'keywords'     => [ 'loop', 'load more', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-arrow' => [
				'label'        => 'Loop Arrow',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Arrow',
				'icon'         => 'eicon-chevron-right',
				'keywords'     => [ 'loop', 'arrow', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-nav-wrap' => [
				'label'        => 'Loop Nav',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Nav_Wrap',
				'icon'         => 'eicon-navigation-horizontal',
				'keywords'     => [ 'loop', 'nav', 'pagination' ],
				'hide_from_panel' => true,
			],

			'aae-a-posts' => [
				'label'        => 'Posts Grid',
				'description'  => 'A dynamic grid of recent posts with GSAP stagger animations.',
				'icon'         => 'eicon-posts-grid',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => [
					'posts',
					'grid',
					'blog',
					'atomic',
					'dynamic',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-post-card' => [
				'label'        => 'Post Card (Internal)',
				'description'  => 'Internal child card for the Posts Grid widget.',
				'icon'         => 'eicon-post-list',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'post card', 'internal' ],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-loop-grid' => [
				'label'        => 'Loop Grid',
				'description'  => 'Query posts and repeat a custom loop-item template per post (built from atomic widgets).',
				'icon'         => 'eicon-loop-builder',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'loop',
					'grid',
					'posts',
					'query',
					'template',
					'dynamic',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-counter' => [
				'label'        => 'Counter',
				'description'  => 'An animated number counter using pure GSAP with minimal CSS footprint.',
				'icon'         => 'eicon-counter',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'counter',
					'number',
					'atomic',
					'gsap',
					'animate',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-advanced-heading' => [
				'label'        => 'Advanced Heading',
				'description'  => 'Heading with editable text and highlight parts: gradient, bracket, divider+dot, or animated underline.',
				'icon'         => 'eicon-t-letter',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'heading',
					'title',
					'highlight',
					'gradient',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-slider' => [
				'label'        => 'Nested Slider',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider',
				'keywords'     => ['atomic', 'slider', 'carousel'],
				'icon'         => 'eicon-slider-push',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'category'     => 'general',
				'order'        => 1,
				'demo_url'     => '',
				'doc_url'      => '',
			],
			'aae-a-slide' => [
				'label'        => 'Slide (Internal)',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slide',
				'keywords'     => ['atomic', 'slide'],
				'icon'         => 'eicon-slide',
				'hide_from_panel' => true,
			],
			'aae-a-slider-track' => [
				'label'        => 'Slider Track',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Track',
				'keywords'     => ['atomic', 'slider', 'track'],
				'icon'         => 'eicon-slider-push',
				'hide_from_panel' => true,
			],
			'aae-a-slider-nav-prev' => [
				'label'        => 'Slider Prev Nav',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Prev',
				'keywords'     => ['atomic', 'slider', 'navigator', 'prev'],
				'icon'         => 'eicon-chevron-left',
				'hide_from_panel' => true,
			],
			'aae-a-slider-nav-next' => [
				'label'        => 'Slider Next Nav',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Next',
				'keywords'     => ['atomic', 'slider', 'navigator', 'next'],
				'icon'         => 'eicon-chevron-right',
				'hide_from_panel' => true,
			],
			'aae-a-slider-pagination' => [
				'label'        => 'Slider Pagination',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Pagination',
				'keywords'     => ['atomic', 'slider', 'pagination', 'dots'],
				'icon'         => 'eicon-ellipsis-h',
				'hide_from_panel' => true,
			],

			'aae-a-slide' => [
				'label'        => 'Slide (Internal)',
				'description'  => 'Internal child container for Nested Slider.',
				'icon'         => 'eicon-document-file',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'slide',
					'internal',
				],
				'category'     => 'general',
				'order'        => 2,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-atomic-button' => [
				'label'        => 'Button',
				'description'  => 'A fully atomic button widget with advanced styling, hover effects, and icon support.',
				'icon'         => 'wcf-icon-Button',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'button',
					'cta',
					'call to action',
					'atomic button',
					'click',
				],
				'category'     => 'general',
				'order'        => 1,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-atomic-image-box' => [
				'label'        => 'Image Box',
				'description'  => 'An atomic image box widget combining image, heading, and description with animation support.',
				'icon'         => 'wcf-icon-Image-Box',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'image box',
					'image card',
					'photo box',
					'atomic image box',
					'media box',
				],
				'category'     => 'general',
				'order'        => 2,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-atomic-nav-menu' => [
				'label'        => 'Nav Menu (Mobile Support)',
				'description'  => 'Atomic navigation menu with full responsive mobile hamburger/off-canvas support.',
				'icon'         => 'wcf-icon-One-Page-Nav',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'nav menu',
					'navigation',
					'mobile menu',
					'hamburger menu',
					'responsive nav',
					'atomic nav',
				],
				'category'     => 'header-footer',
				'order'        => 4,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-atomic-offcanvas' => [
				'label'        => 'Offcanvas',
				'description'  => 'Atomic off-canvas panel for slide-in menus, sidebars, and overlay content areas.',
				'icon'         => 'wcf-icon-Floating-Elements',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'offcanvas',
					'off-canvas',
					'sidebar',
					'slide panel',
					'drawer',
					'mobile panel',
				],
				'category'     => 'general',
				'order'        => 5,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-accordion' => [
				'label'        => 'Accordion',
				'description'  => 'Atomic accordion with GSAP interactive effects and smooth controls.',
				'icon'         => 'eicon-accordion',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'accordion',
					'tabs',
					'toggle',
					'atomic',
					'gsap',
				],
				'category'     => 'general',
				'order'        => 6,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-accordion-item' => [
				'label'        => 'Accordion Item',
				'description'  => 'Internal child container for Accordion.',
				'icon'         => 'eicon-accordion',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'accordion item',
					'internal',
				],
				'category'     => 'general',
				'order'        => 7,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-icon-list' => [
				'label'        => 'Icon List',
				'description'  => 'An atomic icon list widget with custom icons, text, and link support.',
				'icon'         => 'eicon-bullet-list',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'list',
					'icon',
					'bullet',
					'atomic',
					'item',
				],
				'category'     => 'general',
				'order'        => 8,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-icon-list-item' => [
				'label'        => 'Icon List Item',
				'description'  => 'Internal child item for Icon List.',
				'icon'         => 'eicon-bullet-list',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'list item',
					'internal',
				],
				'category'     => 'general',
				'order'        => 9,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-social-share' => [
				'label'        => 'Social Share',
				'description'  => 'Atomic post social share widget with multiple vendors and AJAX share counts.',
				'icon'         => 'eicon-share',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'social',
					'share',
					'post',
					'atomic',
					'aae',
				],
				'category'     => 'general',
				'order'        => 10,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-social-share-item' => [
				'label'        => 'Social Share Item',
				'description'  => 'Internal child item for Social Share.',
				'icon'         => 'eicon-share',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'social',
					'share',
					'item',
					'internal',
				],
				'category'     => 'general',
				'order'        => 11,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-image-compare' => [
				'label'        => 'Image Compare',
				'description'  => 'A draggable before/after image comparison slider with independently styleable atomic children.',
				'icon'         => 'eicon-image-before-after',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'image',
					'compare',
					'before',
					'after',
					'slider',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 10,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-countdown' => [
				'label'        => 'Countdown',
				'description'  => 'A composite countdown timer with four locked time units (days, hours, minutes, seconds) — each unit, digit, and label is an independent atomic child styleable from its own Style panel.',
				'icon'         => 'eicon-countdown',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'countdown',
					'timer',
					'date',
					'expire',
					'atomic',
					'composite',
				],
				'category'     => 'general',
				'order'        => 11,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-countdown-unit' => [
				'label'        => 'Countdown — Unit',
				'description'  => 'Internal time-fragment sub-element used by Countdown (days, hours, minutes, seconds).',
				'icon'         => 'eicon-clock-o',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'countdown',
					'unit',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 12,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-timeline' => [
				'label'        => 'Timeline',
				'description'  => 'A composite vertical timeline with four locked event items — each marker, date, title, and description is an independent atomic child styleable from its own Style panel.',
				'icon'         => 'eicon-time-line',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'timeline',
					'history',
					'roadmap',
					'atomic',
					'composite',
				],
				'category'     => 'general',
				'order'        => 13,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-timeline-item' => [
				'label'        => 'Timeline — Item',
				'description'  => 'Internal event-row sub-element used by Timeline (marker + date + title + description).',
				'icon'         => 'eicon-bullet-list',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'timeline',
					'item',
					'event',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 14,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-button' => [
				'label'        => 'Button',
				'description'  => 'A fully atomic button widget with advanced styling, hover effects, and icon support.',
				'icon'         => 'wcf-icon-Button',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'button',
					'cta',
					'call to action',
					'atomic button',
					'click',
				],
				'category'     => 'general',
				'order'        => 11,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-progressbar' => [
				'label'        => 'Progress Bar',
				'description'  => 'Animated line, circle, and dot progress bar powered by ProgressBar.js.',
				'icon'         => 'eicon-skill-bar',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'progress',
					'progressbar',
					'bar',
					'circle',
					'skill',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 12,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-toggle-switcher' => [
				'label'        => 'Toggle Switcher',
				'description'  => 'A dual-panel content toggle with two styles — classic switch or label highlight.',
				'icon'         => 'eicon-t-letter',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'toggle',
					'switch',
					'tabs',
					'atomic',
					'switcher',
				],
				'category'     => 'general',
				'order'        => 13,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-toggle-pane' => [
				'label'        => 'Toggle Pane (Internal)',
				'description'  => 'Internal child container for Toggle Switcher.',
				'icon'         => 'eicon-inner-section',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'toggle pane',
					'internal',
				],
				'category'     => 'general',
				'order'        => 14,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-offcanvas' => [
				'label'        => 'Offcanvas',
				'description'  => 'Animated offcanvas drawer with trigger button and panel — vanilla JS, no GSAP.',
				'icon'         => 'eicon-sidebar',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'offcanvas',
					'drawer',
					'sidebar',
					'panel',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 15,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-offcanvas-panel' => [
				'label'        => 'Offcanvas Panel (Internal)',
				'description'  => 'Internal locked panel container for Offcanvas.',
				'icon'         => 'eicon-inner-section',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'offcanvas panel',
					'internal',
				],
				'category'     => 'general',
				'order'        => 16,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-nav' => [
				'label'        => 'Nav',
				'description'  => 'Atomic navbar with fully styleable items and dropdown support.',
				'icon'         => 'eicon-nav-menu',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'nav', 'menu', 'navbar', 'navigation', 'atomic', 'aae' ],
				'category'     => 'general',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-nav-item' => [
				'label'        => 'Nav Item (Internal)',
				'description'  => 'Internal child item for Nav.',
				'icon'         => 'eicon-nav-menu',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'nav item', 'internal' ],
				'category'     => 'general',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-flip-box' => [
				'label'        => 'Flip Box',
				'description'  => 'A hover-triggered flip card with front and back faces. Each face is an open atomic container — drop in any heading, paragraph, image, or button.',
				'icon'         => 'eicon-flip-box',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'flip',
					'box',
					'card',
					'hover',
					'atomic',
					'animation',
				],
				'category'     => 'general',
				'order'        => 15,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-flip-box-face' => [
				'label'        => 'Flip Box Face (Internal)',
				'description'  => 'Internal front/back face container for Flip Box.',
				'icon'         => 'eicon-inner-section',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'flip face',
					'internal',
				],
				'category'     => 'general',
				'order'        => 16,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-site-logo' => [
				'label'        => 'Site Logo',
				'description'  => 'Displays the site logo with a configurable link — wraps a native Elementor image child so each piece is independently styleable.',
				'icon'         => 'eicon-site-logo',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'site logo',
					'logo',
					'branding',
					'atomic',
					'header',
				],
				'category'     => 'header-footer',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-video-mask' => [
				'label'        => 'Video Mask',
				'description'  => 'A click-triggered masked video player with a customisable toggle button — icon and label are independent atomic children.',
				'icon'         => 'eicon-youtube',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'video',
					'mask',
					'play',
					'atomic',
					'shape',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-video-mask-btn' => [
				'label'        => 'Video Mask Button (Internal)',
				'description'  => 'Internal button container for Video Mask. Positioned via the native Style panel.',
				'icon'         => 'eicon-button',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'video mask button', 'internal' ],
				'category'     => 'general',
				'order'        => 19,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-button-pro' => [
				'label'        => 'Button Pro',
				'description'  => 'Advanced button widget with 8 GSAP-powered hover styles: ripple, text flip, border divide, group swap, shadow, outline pill, and slide fill.',
				'icon'         => 'wcf-icon-Button',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'button pro',
					'cta',
					'gsap',
					'hover',
					'ripple',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 20,
				'demo_url'     => '',
				'doc_url'      => '',
			],

		];
	}

	private function register_extension_definitions(): void
	{
		$this->extensions_registry = [

			'regular-animation' => [
				'label'        => 'Regular Animation',
				'description'  => 'Preset-based entrance/exit animations applied to every atomic widget.',
				'icon'         => 'wcf-icon-starter-animation',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['animation', 'entrance', 'fade', 'slide', 'regular animation'],
				'category'     => 'animation',
				'order'        => 1,
			],

			'parallax' => [
				'label'        => 'Parallax',
				'description'  => 'ScrollSmoother-powered parallax depth effect on scroll.',
				'icon'         => 'wcf-icon-parallax',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['parallax', 'scroll', 'depth', 'scroll smoother'],
				'category'     => 'animation',
				'order'        => 2,
			],

			'text-animation' => [
				'label'        => 'Text Animation',
				'description'  => 'Character/word/line reveal animations for heading-class widgets.',
				'icon'         => 'wcf-icon-text-animation',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['text animation', 'char animation', 'word reveal', 'text reveal'],
				'category'     => 'animation',
				'order'        => 3,
			],

			'image-animation' => [
				'label'        => 'Image Animation',
				'description'  => 'Reveal/scale/stretch animations for image and SVG widgets.',
				'icon'         => 'wcf-icon-image-animation',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['image animation', 'image reveal', 'scale', 'stretch'],
				'category'     => 'animation',
				'order'        => 4,
			],

			'image-hover' => [
				'label'        => 'Image Hover',
				'description'  => 'Cursor-following floating image overlay on any atomic widget.',
				'icon'         => 'wcf-icon-image-hover',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['image hover', 'cursor follow', 'floating image', 'hover effect'],
				'category'     => 'interaction',
				'order'        => 5,
			],

			'sticky' => [
				'label'        => 'Sticky',
				'description'  => 'Pin elements to viewport on scroll with configurable offsets.',
				'icon'         => 'wcf-icon-sticky',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['sticky', 'pin', 'fixed', 'scroll pin'],
				'category'     => 'interaction',
				'order'        => 6,
			],

			'horizontal-scroll-anim' => [
				'label'        => 'Horizontal Scroll Animation',
				'description'  => 'GSAP-powered horizontal scroll-triggered animation.',
				'icon'         => 'wcf-icon-horizontal-scroll',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['horizontal scroll', 'scroll animation', 'sideways', 'horizontal'],
				'category'     => 'animation',
				'order'        => 7,
			],

			'cursor-hover-effect' => [
				'label'        => 'Cursor Hover Effect',
				'description'  => 'Cursor-following floating element effect on any atomic widget.',
				'icon'         => 'wcf-icon-cursor-hover',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['cursor', 'hover', 'cursor effect', 'mouse hover'],
				'category'     => 'interaction',
				'order'        => 8,
			],

			'mouse-move-effect' => [
				'label'        => 'Mouse Move Effect',
				'description'  => 'Element moves/rotates based on mouse position.',
				'icon'         => 'wcf-icon-mouse-move',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['mouse move', 'mouse parallax', 'tilt on move', 'mouse effect'],
				'category'     => 'interaction',
				'order'        => 9,
			],

			'advance-tooltip' => [
				'label'        => 'Advance Tooltip',
				'description'  => 'Rich content tooltips on hover for any atomic widget.',
				'icon'         => 'wcf-icon-tooltip',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['tooltip', 'hover tooltip', 'info popup', 'advance tooltip'],
				'category'     => 'interaction',
				'order'        => 10,
			],

			'tilt' => [
				'label'        => 'Tilt',
				'description'  => '3D tilt perspective effect on hover.',
				'icon'         => 'wcf-icon-tilt',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['tilt', '3d tilt', 'perspective', 'hover tilt'],
				'category'     => 'interaction',
				'order'        => 11,
			],

			'scroll-to' => [
				'label'        => 'Scroll To',
				'description'  => 'Smooth scroll-to-target anchor navigation.',
				'icon'         => 'wcf-icon-scroll-to',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['scroll to', 'anchor', 'smooth scroll', 'scroll navigation'],
				'category'     => 'interaction',
				'order'        => 12,
			],

			'wrapper-link' => [
				'label'        => 'Wrapper Link',
				'description'  => 'Make any atomic container clickable as a single link.',
				'icon'         => 'wcf-icon-wrapper-link',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['wrapper link', 'container link', 'clickable section', 'link wrapper'],
				'category'     => 'utility',
				'order'        => 13,
			],

			'custom-css' => [
				'label'        => 'Custom CSS',
				'description'  => 'Add custom CSS rules per-element in the atomic editor.',
				'icon'         => 'wcf-icon-custom-css',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['custom css', 'css', 'style', 'custom style'],
				'category'     => 'utility',
				'order'        => 14,
			],
		];
	}

	/**
	 * Hook into WordPress and Elementor.
	 */
	private function init_hooks(): void
	{
		// Gate: Only run when Elementor 4.0+ with atomic experiment is active.
		if (! $this->meets_requirements()) {
			return;
		}

		// Admin: supply config to dashboard and handle AJAX save.
		if (is_admin()) {
			add_filter('wcf_addons_dashboard_config', [$this, 'inject_dashboard_config'], 12);
			add_action('wp_ajax_aae_save_atomic_widgets', [$this, 'ajax_save_settings']);
			add_action('wp_ajax_aae_get_atomic_widgets', [$this, 'ajax_get_settings']);
			add_action('wp_ajax_aae_save_atomic_extensions', [$this, 'ajax_save_extension_settings']);
			add_action('wp_ajax_aae_get_atomic_extensions', [$this, 'ajax_get_extension_settings']);
		}

		add_action('elementor/widgets/register', [$this, 'register_widgets']);
		add_action('elementor/elements/elements_registered', [$this, 'register_elements']);
		add_action('elementor/atomic-widgets/frontend/loader/scripts/register', [$this, 'register_atomic_scripts'], 16);
		add_action('elementor/frontend/before_render', [$this, 'maybe_enqueue_widget_script'], 10, 1);
		add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_widget_scripts_in_preview']);
		add_action('elementor/atomic-widgets/styles/register', [$this, 'register_atomic_styles'], 10, 2);
		add_action('elementor/editor/before_enqueue_scripts', [$this, 'register_atomic_styles']);
		add_action('elementor/preview/enqueue_styles', [$this, 'enqueue_atomic_preview_styles']);
		add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_atomic_preview_scripts']);
		// Inside the preview iframe, force every Elementor per-document CSS to load
		// after `editor-preview` (fixes the reload layout flash). Hook only fires in
		// the preview because it's added from `elementor/preview/enqueue_styles`.
		add_action('elementor/preview/enqueue_styles', function () {
			add_action('wp_print_styles', [$this, 'fix_preview_css_order'], 0);
		});
		add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_atomic_editor_scripts'], 100);

		// AJAX endpoints for Editor previews
		add_action('wp_ajax_aae_get_menu_html', [$this, 'ajax_get_menu_html']);

		// Loop Grid: per-post data for the editor "full grid live" preview (the
		// atomic preview is client-side and can't run our PHP WP_Query).
		add_action('wp_ajax_aae_loop_post_data', [$this, 'ajax_loop_post_data']);

		// Loop Grid: one post's title/image for the editor's authored-card
		// sample — used after "Apply & Preview" (Page Settings → Preview
		// Settings) so the chosen post shows without a full editor reload.
		add_action('wp_ajax_aae_loop_sample_post', [$this, 'ajax_loop_sample_post']);

		// Dynamic tags editor preview: `ajax_render_tags` switches to the EDITED
		// document before resolving tags, so a Featured Image / Post Title tag
		// inside a loop item resolves against the PAGE (usually no thumbnail →
		// empty). When the document has an explicit Preview Settings post
		// (`aae_loop_page_post`), re-switch to it so core dynamic tags preview
		// that post — same semantics the V3 loop preview always had.
		add_action('elementor/dynamic_tags/before_render', [$this, 'switch_dynamic_tags_to_preview_post']);

		// Loop Grid: frontend paginated cells (AJAX + Load More). Available to
		// logged-out visitors too, so both hooks are registered.
		add_action('wp_ajax_aae_loop_grid_page', [$this, 'ajax_loop_grid_page']);
		add_action('wp_ajax_nopriv_aae_loop_grid_page', [$this, 'ajax_loop_grid_page']);

		// Seed defaults on first install (option doesn't exist yet).
		$this->maybe_seed_widgets_defaults();
		$this->maybe_seed_extension_defaults();
	}

	/* =====================================================================
	 *  Elementor Integration
	 * =================================================================== */

	/**
	 * Define all available atomic widgets and their scripts.
	 *
	 * STEP 2 of adding a new widget — append a new entry to the returned
	 * array using the key 'aae-a-<slug>' (must match the dashboard slug in
	 * register_widget_definitions()).
	 *
	 *   'aae-a-<slug>' => [
	 *       'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\<PascalName>\AAE_A_<PascalSlug>',
	 *       'file'          => 'Widgets/<PascalName>/class-aae-a-<slug>.php',
	 *       'script_handle' => 'aae-a-<slug>-js',
	 *       'script_path'   => '/assets/atomic/js/<slug>.js',
	 *       'has_script'    => true,
	 *       'style_handle'  => 'aae-a-<slug>-css',          // omit if no SCSS
	 *       'style_path'    => '/assets/atomic/css/<slug>.css',
	 *   ],
	 *
	 * See the full "HOW TO ADD A NEW ATOMIC WIDGET" block above
	 * register_widget_definitions() for the complete walkthrough.
	 */
	protected function get_available_widgets()
	{
		return [
			'aae-a-counter' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Counter\AAE_A_Counter',
				'file' => 'Widgets/Counter/class-aae-a-counter.php',
				'script_handle' => 'aae-a-counter-js',
				'script_path' => '/assets/atomic/js/counter.js',
				'has_script' => true,
			],
			'aae-a-slider' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider.php',				
				'style_handle' => 'aae-a-slider-css',
				'style_path' => '/assets/atomic/css/nestedslider.css',
				'has_script' => false,
			],
			'aae-a-slide' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slide',
				'file' => 'Widgets/NestedSlider/class-aae-a-slide.php',
				'has_script' => false,
			],
			'aae-a-slider-track' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Track',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-track.php',
				'has_script' => false,
			],
			'aae-a-slider-nav-prev' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Prev',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-nav-prev.php',
				'has_script' => false,
			],
			'aae-a-slider-nav-next' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Next',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-nav-next.php',
				'has_script' => false,
			],
			'aae-a-slider-dot' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Dot',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-dot.php',
				'has_script' => false,
			],
			'aae-a-slider-indicators' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Indicators',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-indicators.php',
				'has_script' => false,
			],
			'aae-a-slider-current' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Current',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-current.php',
				'has_script' => false,
			],
			'aae-a-slider-total' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Total',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-total.php',
				'has_script' => false,
			],
			'aae-a-slider-percentage' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Percentage',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-percentage.php',
				'has_script' => false,
			],
			'aae-a-slider-progress' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Progress',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-progress.php',
				'has_script' => false,
			],
			'aae-a-slider-progress-fill' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Progress_Fill',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-progress-fill.php',
				'has_script' => false,
			],
			'aae-a-slider-counter' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Counter',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-counter.php',
				'has_script' => false,
			],
			'aae-a-slider-divider' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Divider',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-divider.php',
				'has_script' => false,
			],
			'aae-a-slider-pagination' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Pagination',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-pagination.php',
				'has_script' => false,
			],
			'aae-a-menu' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Menu\AAE_A_Menu',
				'file' => 'Widgets/Menu/class-aae-a-menu.php',
				'script_handle' => 'aae-a-menu-js',
				'script_path' => '/assets/atomic/js/menu.js',
				'has_script' => true,
				'style_handle' => 'aae-a-menu-css',
				'style_path' => '/assets/atomic/css/menu.css',
			],
			'aae-a-post-title' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostTitle\AAE_A_Post_Title',
				'file' => 'Widgets/PostTitle/class-aae-a-post-title.php',
				'has_script' => false,
				'style_handle' => 'aae-a-post-title-css',
				'style_path' => '/assets/atomic/css/post-title.css',
			],

			'aae-a-post-image' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostImage\AAE_A_Post_Image',
				'file' => 'Widgets/PostImage/class-aae-a-post-image.php',
				'has_script' => false,
			],

			'aae-a-post-meta' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostMeta\AAE_A_Post_Meta',
				'file' => 'Widgets/PostMeta/class-aae-a-post-meta.php',
				'has_script' => false,
			],

			'aae-a-post-meta-item' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostMeta\AAE_A_Post_Meta_Item',
				'file' => 'Widgets/PostMeta/class-aae-a-post-meta-item.php',
				'has_script' => false,
			],

			'aae-a-posts' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Posts\AAE_A_Posts',
				'file' => 'Widgets/Posts/class-aae-a-posts.php',
				'script_handle' => 'aae-a-posts-js',
				'script_path' => '/assets/atomic/js/posts.js',
				'has_script' => true,
				'style_handle' => 'aae-a-posts-css',
				'style_path' => '/assets/atomic/css/posts.css',
			],

			'aae-a-post-card' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Posts\AAE_A_Post_Card',
				'file'       => 'Widgets/Posts/class-aae-a-post-card.php',
				'has_script' => false,
			],

			'aae-a-loop-grid' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-grid.php',
				'script_handle' => 'aae-a-loop-grid-js',
				'script_path' => '/assets/atomic/js/loop-grid.js',
				'has_script' => true,
				'style_handle' => 'aae-a-loop-grid-css',
				'style_path' => '/assets/atomic/css/loop-grid.css',
			],

			'aae-a-loop-item' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Item',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-item.php',
				'has_script' => false,
			],
			'aae-a-loop-layout' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Layout',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-layout.php',
				'has_script' => false,
			],
			'aae-a-loop-pagination' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Pagination',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-pagination.php',
				'has_script' => false,
			],
			'aae-a-loop-prev' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Prev',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-prev.php',
				'has_script' => false,
			],
			'aae-a-loop-next' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Next',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-next.php',
				'has_script' => false,
			],
			'aae-a-loop-numbers' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Numbers',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-numbers.php',
				'has_script' => false,
			],
			'aae-a-loop-loadmore' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_LoadMore',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-loadmore.php',
				'has_script' => false,
			],
			'aae-a-loop-arrow' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Arrow',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-arrow.php',
				'has_script' => false,
			],
			'aae-a-loop-nav-wrap' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Nav_Wrap',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-nav-wrap.php',
				'has_script' => false,
			],

			'aae-a-accordion' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Accordion\AAE_A_Accordion',
				'file' => 'Widgets/Accordion/class-aae-a-accordion.php',
				'script_handle' => 'aae-a-accordion-js',
				'script_path' => '/assets/atomic/js/accordion.js',
				'has_script' => true,
				'style_handle' => 'aae-a-accordion-css',
				'style_path' => '/assets/atomic/css/accordion.css',
			],

			'aae-a-accordion-item' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Accordion\AAE_A_Accordion_Item',
				'file' => 'Widgets/Accordion/class-aae-a-accordion-item.php',
				'has_script' => false,
			],

			'aae-a-icon-list' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\IconList\AAE_A_Icon_List',
				'file' => 'Widgets/IconList/class-aae-a-icon-list.php',
				'has_script' => false,
				'style_handle' => 'aae-a-icon-list-css',
				'style_path' => '/assets/atomic/css/icon-list.css',
			],
		'aae-a-icon-list-item' => [
			'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\IconList\AAE_A_Icon_List_Item',
			'file' => 'Widgets/IconList/class-aae-a-icon-list-item.php',
			'has_script' => false,
			'style_handle' => 'aae-a-icon-list-css',
			'style_path' => '/assets/atomic/css/icon-list.css',
		],

		'aae-a-social-share' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\SocialShare\AAE_A_Social_Share',
			'file'          => 'Widgets/SocialShare/class-aae-a-social-share.php',
			'script_handle' => 'aae-a-social-share-js',
			'script_path'   => '/assets/atomic/js/social-share.js',
			'has_script'    => true,
		],
		'aae-a-social-share-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\SocialShare\AAE_A_Social_Share_Item',
			'file'       => 'Widgets/SocialShare/class-aae-a-social-share-item.php',
			'has_script' => false,
		],
		'aae-a-image-compare' => [
			'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\ImageCompare\AAE_A_Image_Compare',
			'file' => 'Widgets/ImageCompare/class-aae-a-image-compare.php',
			'script_handle' => 'aae-a-image-compare-js',
			'script_path' => '/assets/atomic/js/image-compare.js',
			'has_script' => true,
			// No external CSS: all per-element styles live in the widget's
			// define_base_styles() (compound selectors) + the inline <style>
			// block of the Twig template. No `style_handle`/`style_path`.
		],
		'aae-a-countdown' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Countdown\AAE_A_Countdown',
			'file'          => 'Widgets/Countdown/class-aae-a-countdown.php',
			'script_handle' => 'aae-a-countdown-js',
			'script_path'   => '/assets/atomic/js/countdown.js',
			'has_script'    => true,
		],
		'aae-a-countdown-unit' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Countdown\AAE_A_Countdown_Unit',
			'file'       => 'Widgets/Countdown/class-aae-a-countdown-unit.php',
			'has_script' => false,
		],
		'aae-a-timeline' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline',
			'file'       => 'Widgets/Timeline/class-aae-a-timeline.php',
			'has_script' => false,
			// No external CSS: all per-element styles live in the widget's
			// define_base_styles() (compound selectors) + a tiny inline
			// <style> in the item Twig for the spine shorthand + the
			// marker's negative-inset positioning. No `style_handle`.
		],
		'aae-a-timeline-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Item',
			'file'       => 'Widgets/Timeline/class-aae-a-timeline-item.php',
			'has_script' => false,
		],
		// Add new atomic widgets below...
			'aae-a-button' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Button\AAE_A_Button',
				'file'          => 'Widgets/Button/class-aae-a-button.php',
				'script_handle' => 'aae-a-button-js',
				'script_path'   => '/assets/atomic/js/button.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-button-css',
				'style_path'    => '/assets/atomic/js/button.css',
			],

			'aae-a-advanced-heading' => [
				'class'        => '\WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading\AAE_A_Advanced_Heading',
				'file'         => 'Widgets/AdvancedHeading/class-aae-a-advanced-heading.php',
				'has_script'   => false,
				'style_handle' => 'aae-a-advanced-heading-css',
				'style_path'   => '/assets/atomic/css/advanced-heading.css',
			],

			'aae-a-progressbar' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar',
				'file'          => 'Widgets/Progressbar/class-aae-a-progressbar.php',
				'script_handle' => 'aae-a-progressbar-js',
				'script_path'   => '/assets/atomic/js/progressbar.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-progressbar-css',
				'style_path'    => '/assets/atomic/js/progressbar.css',
			],

			'aae-a-toggle-switcher' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Switcher',
				'file'          => 'Widgets/ToggleSwitcher/class-aae-a-toggle-switcher.php',
				'script_handle' => 'aae-a-toggle-switcher-js',
				'script_path'   => '/assets/atomic/js/toggle-switcher.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-toggle-switcher-css',
				'style_path'    => '/assets/atomic/js/toggle-switcher.css',
			],

			'aae-a-toggle-pane' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Pane',
				'file'       => 'Widgets/ToggleSwitcher/class-aae-a-toggle-pane.php',
				'has_script' => false,
			],

			'aae-a-offcanvas' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas',
				'file'          => 'Widgets/Offcanvas/class-aae-a-offcanvas.php',
				'script_handle' => 'aae-a-offcanvas-js',
				'script_path'   => '/assets/atomic/js/offcanvas.js',
				'has_script'    => true,
			],

			'aae-a-offcanvas-panel' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Panel',
				'file'       => 'Widgets/Offcanvas/class-aae-a-offcanvas-panel.php',
				'has_script' => false,
			],

			'aae-a-nav' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav',
			'file'          => 'Widgets/Nav/class-aae-a-nav.php',
			'has_script'    => true,
			'script_handle' => 'aae-a-nav-js',
			'script_path'   => '/assets/atomic/js/nav.js',
			'style_handle'  => 'aae-a-nav-css',
			'style_path'    => '/assets/atomic/css/nav.css',
		],
		'aae-a-nav-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Item',
			'file'       => 'Widgets/Nav/class-aae-a-nav-item.php',
			'has_script' => false,
		],
		'aae-a-nav-sub-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Sub_Item',
			'file'       => 'Widgets/Nav/class-aae-a-nav-sub-item.php',
			'has_script' => false,
		],
		'aae-a-mobile-nav' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Mobile_Nav',
			'file'          => 'Widgets/Nav/class-aae-a-mobile-nav.php',
			'has_script'    => true,
			'script_handle' => 'aae-a-nav-js',
			'script_path'   => '/assets/atomic/js/nav.js',
			'style_handle'  => 'aae-a-nav-css',
			'style_path'    => '/assets/atomic/css/nav.css',
		],

		'aae-a-flip-box' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\FlipBox\AAE_A_Flip_Box',
			'file'          => 'Widgets/FlipBox/class-aae-a-flip-box.php',
			'script_handle' => 'aae-a-flip-box-js',
			'script_path'   => '/assets/atomic/js/flip-box.js',
			'has_script'    => true,
			'style_handle'  => 'aae-a-flip-box-css',
			'style_path'    => '/assets/atomic/js/flip-box.css',
		],

		'aae-a-flip-box-face' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\FlipBox\AAE_A_Flip_Box_Face',
			'file'       => 'Widgets/FlipBox/class-aae-a-flip-box-face.php',
			'has_script' => false,
		],

		'aae-a-site-logo' => [
			'class'        => '\WCF_ADDONS\AtomicWidgets\Widgets\SiteLogo\AAE_A_Site_Logo',
			'file'         => 'Widgets/SiteLogo/class-aae-a-site-logo.php',
			'has_script'   => false,
			'style_handle' => 'aae-a-site-logo-css',
			'style_path'   => '/assets/atomic/css/site-logo.css',
		],

		'aae-a-video-mask' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\VideoMask\AAE_A_Video_Mask',
			'file'          => 'Widgets/VideoMask/class-aae-a-video-mask.php',
			'script_handle' => 'aae-a-video-mask-js',
			'script_path'   => '/assets/atomic/js/video-mask.js',
			'has_script'    => true,
			'style_handle'  => 'aae-a-video-mask-css',
			'style_path'    => '/assets/atomic/js/video-mask.css',
		],

		'aae-a-video-mask-btn' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\VideoMask\AAE_A_Video_Mask_Btn',
			'file'       => 'Widgets/VideoMask/class-aae-a-video-mask-btn.php',
			'has_script' => false,
		],

		'aae-a-button-pro'  => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\ButtonPro\AAE_A_Button_Pro',
			'file'          => 'Widgets/ButtonPro/class-aae-a-button-pro.php',
			'script_handle' => 'aae-a-button-pro-js',
			'script_path'   => '/assets/atomic/js/button-pro.js',
			'script_deps'   => [ 'gsap' ],
			'has_script'    => true,
			'style_handle'  => 'aae-a-button-pro-css',
			'style_path'    => '/assets/atomic/js/button-pro.css',
		],

		// Add new atomic widgets below...
		];
	}

	/**
	 * AJAX: per-post data for the Loop Grid editor "full grid live" preview.
	 *
	 * The atomic editor preview is client-side and never runs the element's PHP
	 * WP_Query, so it natively shows ONE authored loop-item card. The editor JS
	 * (loop-grid module) calls this to get the queried posts' data, then clones
	 * the authored card into inert preview cells filled with each post's values.
	 * Returns a lightweight array — no markup, no document, no print_elements.
	 */
	/**
	 * Resolve editor dynamic tags against the document's Preview Settings post.
	 *
	 * Runs on `elementor/dynamic_tags/before_render` (fired right after
	 * `ajax_render_tags` switched to the edited document). ONLY re-switches
	 * when the user explicitly chose a Preview Settings post — a document
	 * without one keeps stock behavior, so normal pages are unaffected.
	 */
	public function switch_dynamic_tags_to_preview_post()
	{
		// Editor ajax only — never touch frontend rendering.
		if (! is_admin() || ! wp_doing_ajax()) {
			return;
		}

		$chosen = self::get_preview_setting_post();
		if ($chosen) {
			\Elementor\Plugin::$instance->db->switch_to_post($chosen->ID);
		}
	}

	/**
	 * One post's preview data (title + featured image) for the editor's
	 * authored-card sample. The client sends the LIVE value of the document's
	 * `aae_loop_page_post` page setting (Preview Settings), so the chosen post
	 * previews immediately after "Apply & Preview" — no editor reload needed.
	 */
	public function ajax_loop_sample_post()
	{
		check_ajax_referer('aae_loop_grid', 'nonce');

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$post = isset($_POST['sample_id']) ? get_post(absint($_POST['sample_id'])) : null;
		if (! $post || 'publish' !== $post->post_status) {
			wp_send_json_error(['message' => 'Invalid sample post.'], 404);
		}

		wp_send_json_success([
			'title' => get_the_title($post),
			'image' => get_the_post_thumbnail_url($post, 'large') ?: '',
		]);
	}

	public function ajax_loop_post_data()
	{
		check_ajax_referer('aae_loop_grid', 'nonce');

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$query = new \WP_Query([
			'post_type'           => isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => isset($_POST['posts_per_page']) ? absint($_POST['posts_per_page']) : 6,
			'orderby'             => isset($_POST['order_by']) ? sanitize_key(wp_unslash($_POST['order_by'])) : 'date',
			'order'               => isset($_POST['order']) ? (('asc' === sanitize_key(wp_unslash($_POST['order']))) ? 'ASC' : 'DESC') : 'DESC',
			'ignore_sticky_posts' => true,
		]);

		$posts = [];
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$posts[] = [
					'title'   => get_the_title(),
					'url'     => get_permalink(),
					'image'   => get_the_post_thumbnail_url(null, 'large') ?: '',
					'excerpt' => wp_strip_all_tags(get_the_excerpt()),
				];
			}
			wp_reset_postdata();
		}

		wp_send_json_success(['posts' => $posts]);
	}

	/**
	 * Frontend: render the loop-item cells for a given page of a specific Loop
	 * Grid instance, WITH the authored atomic styles intact.
	 *
	 * Finds the loop-item element data inside the requesting document (by the
	 * grid's element id), pushes the paged WP_Query onto the Render_Context stack
	 * (keyed like AAE_A_Loop_Grid does), and renders the loop-item element — the
	 * exact same path used server-side, so the markup + style classes match.
	 *
	 * Nonce: aae_loop_grid_front (public). Only reads published post content.
	 */
	public function ajax_loop_grid_page() {
		check_ajax_referer('aae_loop_grid_front', 'nonce');

		$post_id  = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		$grid_id  = isset($_POST['grid_id']) ? sanitize_key(wp_unslash($_POST['grid_id'])) : '';
		$paged    = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;

		if (! $post_id || ! $grid_id) {
			wp_send_json_error(['message' => 'Missing post_id or grid_id.'], 400);
		}

		$doc = \Elementor\Plugin::$instance->documents->get($post_id);
		if (! $doc) {
			wp_send_json_error(['message' => 'Document not found.'], 404);
		}

		$data = $doc->get_elements_data();

		// Locate the loop-grid element (by id) and its loop-item descendant.
		$grid_el = null;
		$find_grid = function ($els) use (&$find_grid, &$grid_el, $grid_id) {
			foreach ($els as $el) {
				if (($el['id'] ?? '') === $grid_id && ($el['elType'] ?? '') === 'e-aae-a-loop-grid') {
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
		$find_item = function ($els) use (&$find_item, &$item_el) {
			foreach ($els as $el) {
				if (($el['elType'] ?? '') === 'e-aae-a-loop-item') {
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

		// Build the paged query args from the grid's saved settings.
		$gs  = $grid_el['settings'] ?? [];
		$val = function ($k, $d) use ($gs) {
			if (isset($gs[$k]['value'])) {
				return $gs[$k]['value'];
			}
			if (isset($gs[$k]) && ! is_array($gs[$k])) {
				return $gs[$k];
			}
			return $d;
		};

		$per_page   = (int) $val('posts_per_page', 6);
		$query_args = [
			'post_type'           => $val('post_type', 'post'),
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'orderby'             => $val('order_by', 'date'),
			'order'               => ('asc' === strtolower($val('order', 'desc'))) ? 'ASC' : 'DESC',
			'paged'               => $paged,
			'ignore_sticky_posts' => true,
		];

		// Total pages (respects the same query).
		$count     = new \WP_Query(array_merge($query_args, ['fields' => 'ids']));
		$max_pages = max(1, (int) $count->max_num_pages);
		wp_reset_postdata();

		// Push context (same key the Loop Item reads) and render the item.
		\Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context::push(
			\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid::class,
			['query_args' => $query_args]
		);

		$item_obj = \Elementor\Plugin::$instance->elements_manager->create_element_instance($item_el);
		ob_start();
		if ($item_obj) {
			$item_obj->print_element();
		}
		$html = ob_get_clean();

		\Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context::pop(
			\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid::class
		);

		wp_send_json_success([
			'html'      => $html,
			'paged'     => $paged,
			'max_pages' => $max_pages,
		]);
	}

	/**
	 * Make every Elementor document CSS in the editor preview depend on
	 * `editor-preview`, so it prints AFTER it.
	 *
	 * The loop-item document's preview CSS (handle `local-<id>-preview-<device>`)
	 * and post CSS (`elementor-post-<id>`) carry that document's atomic base
	 * styles (`.e-flexbox-base { display:flex; flex-direction:row }` …). Elementor
	 * enqueues them with frontend dependencies only, so on a hard reload they can
	 * print BEFORE `editor-preview.min.css` and momentarily break the preview
	 * layout. Patching their deps just before styles are output guarantees the
	 * correct cascade. Runs on `wp_print_styles` (priority 0) inside the preview
	 * iframe, when every handle is finally registered.
	 */
	public function fix_preview_css_order(): void {
		if ( ! wp_style_is( 'editor-preview', 'registered' ) ) {
			return;
		}
		$styles = wp_styles();
		foreach ( $styles->registered as $handle => $style ) {
			if ( 'editor-preview' === $handle ) {
				continue;
			}
			// Elementor per-document CSS handles only.
			if ( ! preg_match( '/^(local-\d+-preview|elementor-post-\d+)/', $handle ) ) {
				continue;
			}
			if ( ! in_array( 'editor-preview', $style->deps, true ) ) {
				$style->deps[] = 'editor-preview';
			}
		}
	}

	/**
	 * Register active atomic widgets with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets($widgets_manager)
	{
	
		foreach ($this->get_available_widgets() as $widget_id => $widget_data) {
			if ($this->is_widget_active($widget_id)) {
			
				$file_path = wp_normalize_path(__DIR__ . '/' . $widget_data['file']);
				if (! file_exists($file_path)) {
					continue; // Skip missing widget files gracefully.
				}
				require_once $file_path;
				if (class_exists($widget_data['class']) && is_subclass_of($widget_data['class'], '\Elementor\Widget_Base')) {
					$widgets_manager->register(new $widget_data['class']());
				}
			}
		}
	}

	/**
	 * Register active atomic elements (containers) with Elementor.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_elements($elements_manager)
	{
		foreach ($this->get_available_widgets() as $widget_id => $widget_data) {
			if ($this->is_widget_active($widget_id)) {
				$file_path = wp_normalize_path(__DIR__ . '/' . $widget_data['file']);
				if (! file_exists($file_path)) {
					continue; // Skip missing widget files gracefully.
				}
				require_once $file_path;
				if (class_exists($widget_data['class']) && !is_subclass_of($widget_data['class'], '\Elementor\Widget_Base')) {
					$elements_manager->register_element_type(new $widget_data['class']());
				}
			}
		}
	}

	public function register_atomic_scripts($loader)
	{

		foreach ($this->get_available_widgets() as $widget_id => $widget_data) {
			if ($this->is_widget_active($widget_id) && !empty($widget_data['has_script'])) {
				$path = $widget_data['script_path'];
				if (! $this->is_dev_environment()) {
					$min_path = str_replace('.js', '.min.js', $path);
					if (file_exists(WCF_ADDONS_PATH . $min_path)) {
						$path = $min_path;
					}
				}
				$file_path = WCF_ADDONS_PATH . $path;
				$version = file_exists($file_path) ? filemtime($file_path) : WCF_ADDONS_VERSION;

				$deps = [ 'elementor-v2-frontend-handlers' ]; // Required for @elementor/frontend-handlers register API
				if ( ! empty( $widget_data['script_deps'] ) ) {
					$deps = array_merge( $deps, (array) $widget_data['script_deps'] );
				}
				wp_register_script(
					$widget_data['script_handle'],
					WCF_ADDONS_URL . $path,
					$deps,
					$version,
					true
				);
			}
		}
	}

	/**
	 * Enqueue the widget's script when that element type is actually rendered on the page.
	 *
	 * WHY THIS EXISTS:
	 * Atomic_Widget_Base::before_render() is an intentionally empty override of
	 * Widget_Base::before_render(). The parent's before_render() is the only place
	 * enqueue_scripts() is triggered, so get_script_depends() is DEAD CODE for every
	 * atomic widget. We instead hook into Element_Base::print_element() which fires
	 * `elementor/frontend/before_render` for all elements including atomic widgets,
	 * and enqueue the matching script handle here — once, on first encounter.
	 *
	 * @param \Elementor\Element_Base $element
	 */
	public function maybe_enqueue_widget_script($element): void
	{
		if (! method_exists($element, 'get_element_type')) {
			return;
		}

		$element_type = $element::get_element_type();
		// get widget settings condition css / js file load
		//$widget_settings = $element->get_atomic_settings();

		foreach ($this->get_available_widgets() as $slug => $data) {

			if (('e-' . $slug) === $element_type) {
				if (! empty($data['has_script'])) {
					wp_enqueue_script($data['script_handle']);
				}
				if (! empty($data['style_handle'])) {
					wp_enqueue_style($data['style_handle']);
				}
				break;
			}
		}
	}

	/**
	 * Enqueue every active atomic widget's frontend script into the editor
	 * preview iframe.
	 *
	 * WHY THIS EXISTS:
	 * In the editor, atomic widgets render client-side, so
	 * `elementor/frontend/before_render` (which drives maybe_enqueue_widget_script)
	 * never fires for them — meaning their JS never loads in the preview and
	 * interactive behavior (e.g. the accordion toggle) is dead in editor view.
	 * The preview iframe lets the user freely edit any widget, so we blanket-
	 * enqueue all active widget scripts AND styles here, mirroring how the
	 * effect bundles are blanket-enqueued for the preview. The styles matter for
	 * editor-only CSS (e.g. body.elementor-editor-active rules) to take effect.
	 */
	public function enqueue_widget_scripts_in_preview(): void {
		foreach ( $this->get_available_widgets() as $widget_id => $widget_data ) {
			if ( ! $this->is_widget_active( $widget_id ) ) {
				continue;
			}

			if ( ! empty( $widget_data['has_script'] ) ) {
				// The atomic frontend loader's register hook may not have run in
				// the preview context, so register the handle here if missing.
				if ( ! wp_script_is( $widget_data['script_handle'], 'registered' ) ) {
					$path = $widget_data['script_path'];
					if ( ! $this->is_dev_environment() ) {
						$min_path = str_replace( '.js', '.min.js', $path );
						if ( file_exists( WCF_ADDONS_PATH . $min_path ) ) {
							$path = $min_path;
						}
					}
					$file_path = WCF_ADDONS_PATH . $path;
					$version   = file_exists( $file_path ) ? filemtime( $file_path ) : WCF_ADDONS_VERSION;

					wp_register_script(
						$widget_data['script_handle'],
						WCF_ADDONS_URL . $path,
						[ 'elementor-v2-frontend-handlers' ],
						$version,
						true
					);
				}

				wp_enqueue_script( $widget_data['script_handle'] );
			}

			if ( ! empty( $widget_data['style_handle'] ) ) {
				// Register the style handle on the spot if the styles/register
				// hook hasn't run in the preview context.
				if ( ! wp_style_is( $widget_data['style_handle'], 'registered' ) && ! empty( $widget_data['style_path'] ) ) {
					$style_path = $widget_data['style_path'];
					if ( ! $this->is_dev_environment() ) {
						$min_path = str_replace( '.css', '.min.css', $style_path );
						if ( file_exists( WCF_ADDONS_PATH . $min_path ) ) {
							$style_path = $min_path;
						}
					}
					$style_file = WCF_ADDONS_PATH . $style_path;
					$style_ver  = file_exists( $style_file ) ? filemtime( $style_file ) : WCF_ADDONS_VERSION;

					wp_register_style(
						$widget_data['style_handle'],
						WCF_ADDONS_URL . $style_path,
						[],
						$style_ver
					);
				}

				wp_enqueue_style( $widget_data['style_handle'] );
			}
		}
	}

	/**
	 * Register frontend styles for active atomic widgets.
	 */
	public function register_atomic_styles($_styles_manager = null, array $_post_ids = [])
	{
		foreach ($this->get_available_widgets() as $widget_id => $widget_data) {
			if ($this->is_widget_active($widget_id) && !empty($widget_data['style_handle'])) {
				$path = $widget_data['style_path'];
				if (! $this->is_dev_environment()) {
					$min_path = str_replace('.css', '.min.css', $path);
					if (file_exists(WCF_ADDONS_PATH . $min_path)) {
						$path = $min_path;
					}
				}
				$file_path = WCF_ADDONS_PATH . $path;
				$version = file_exists($file_path) ? filemtime($file_path) : WCF_ADDONS_VERSION;
				wp_register_style(
					$widget_data['style_handle'],
					WCF_ADDONS_URL . $path,
					[],
					$version
				);
			}
		}
	}

	/**
	 * Enqueue every active atomic widget's stylesheet inside the editor
	 * preview iframe.
	 *
	 * Why: `maybe_enqueue_widget_script()` rides on
	 * `elementor/frontend/before_render`, which does not fire when the v4
	 * editor renders atomic widgets through its client-side Element_Builder
	 * pipeline. Without this hook, widgets like Image Compare whose slider
	 * button / handle styles live only in the external CSS file render
	 * unstyled inside the editor (frontend is unaffected).
	 */
	public function enqueue_atomic_preview_styles(): void {
		$this->register_atomic_styles();

		// In the editor preview iframe every atomic widget style MUST load AFTER
		// Elementor's `editor-preview` stylesheet. Otherwise, on a hard reload the
		// widget CSS can win source-order before editor-preview.css is parsed,
		// briefly applying the wrong base rules (e.g.
		// `.e-flexbox-base { display:flex; flex-direction:row }`) and breaking the
		// layout until editor-preview settles. add_style_dependency() below makes
		// WordPress emit our <link> after editor-preview's.
		foreach ( $this->get_available_widgets() as $widget_id => $widget_data ) {
			if ( $this->is_widget_active( $widget_id ) ) {
				if ( ! empty( $widget_data['style_handle'] ) ) {
					$this->add_style_dependency( $widget_data['style_handle'], 'editor-preview' );
					wp_enqueue_style( $widget_data['style_handle'] );
				}
			}
		}
	}

	/**
	 * Append a dependency to an already-registered style handle.
	 *
	 * The atomic widget styles are registered once (with empty deps) by
	 * register_atomic_styles(). In the preview iframe we need them to depend on
	 * Elementor's `editor-preview` so they always print after it. Mutating the
	 * registered handle's deps in place is cheaper (and avoids version churn)
	 * than re-registering.
	 *
	 * @param string $handle Registered style handle.
	 * @param string $dep    Dependency handle to add.
	 */
	private function add_style_dependency( string $handle, string $dep ): void {
		if ( ! wp_style_is( $dep, 'registered' ) ) {
			return;
		}
		$styles = wp_styles();
		if ( ! isset( $styles->registered[ $handle ] ) ) {
			return;
		}
		$registered = $styles->registered[ $handle ];
		if ( ! in_array( $dep, $registered->deps, true ) ) {
			$registered->deps[] = $dep;
		}
	}

	/**
	 * Enqueue every active atomic widget's frontend-handlers script inside
	 * the editor preview iframe.
	 *
	 * Why: The per-widget interactivity scripts (Image Compare drag,
	 * Accordion toggle, NestedSlider, etc.) hook in via
	 * `@elementor/frontend-handlers`. They're registered via
	 * `elementor/atomic-widgets/frontend/loader/scripts/register` and only
	 * `wp_enqueue_script()`'d by `maybe_enqueue_widget_script()` on the
	 * frontend `before_render` event — that event doesn't fire for atomic
	 * widgets rendered through the editor preview's Element_Builder
	 * pipeline, leaving widgets unresponsive in the editor.
	 */
	public function enqueue_atomic_preview_scripts(): void {
		$this->register_atomic_scripts( null );

		foreach ( $this->get_available_widgets() as $widget_id => $widget_data ) {
			if ( $this->is_widget_active( $widget_id ) && ! empty( $widget_data['has_script'] ) && ! empty( $widget_data['script_handle'] ) ) {
				wp_enqueue_script( $widget_data['script_handle'] );
			}
		}
	}

	/**
	 * Return true when running in a dev / local environment.
	 *
	 * Minified assets are skipped when ANY of the following is true:
	 *   - WordPress SCRIPT_DEBUG constant is set to true.
	 *   - The HTTP_HOST header is 127.0.0.1, localhost, or a *.local / *.test domain.
	 *   - The server's own IP address (SERVER_ADDR / LOCAL_ADDR) is 127.0.0.1.
	 *
	 * @return bool
	 */
	private function is_dev_environment(): bool
	{
		if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
			return true;
		}

		$host = strtolower($_SERVER['HTTP_HOST'] ?? '');

		if (
			$host === '127.0.0.1' ||
			$host === 'localhost' ||
			str_ends_with($host, '.local') ||
			str_ends_with($host, '.test')
		) {
			return true;
		}

		// Windows IIS uses LOCAL_ADDR; Apache/Nginx use SERVER_ADDR.
		$server_ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '';

		return $server_ip === '127.0.0.1';
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
	public function inject_dashboard_config(array $configs): array
	{
		$dashboard = $this->get_dashboard_config();

		$configs['atomic_widgets']    = $dashboard['atomic_widgets'];
		$configs['atomic_extensions'] = $dashboard['atomic_extensions'];

		return $configs;
	}

	/**
	 * AJAX handler — save atomic widget toggle states.
	 */
	public function ajax_save_settings(): void
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['fields'])) {
			wp_send_json_error(esc_html__('Missing fields.', 'animation-addons-for-elementor'));
		}

		$raw      = sanitize_text_field(wp_unslash($_POST['fields']));
		$settings = json_decode($raw, true);

		if (! is_array($settings)) {
			wp_send_json_error(esc_html__('Invalid data.', 'animation-addons-for-elementor'));
		}

		// Build a clean associative array: slug => true for enabled.
		$clean = [];
		foreach ($settings as $slug => $state) {
			$slug = sanitize_key($slug);

			if (isset($this->widgets_registry[$slug]) && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		$updated = update_option(self::OPTION_NAME, $clean);

		// Reset cache.
		$this->active_widgets = null;

		wp_send_json([
			'status' => $updated,
			'total'  => count($clean),
		]);
	}

	/**
	 * AJAX handler — retrieve current atomic widget settings.
	 */
	public function ajax_get_settings(): void
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		wp_send_json([
			'settings' => $this->get_saved_options(),
			'config'   => $this->get_dashboard_config(),
		]);
	}

	/**
	 * AJAX handler — fetch WP Menu HTML for the Elementor Editor (since Atomic JS render lacks it).
	 */
	public function ajax_get_menu_html(): void
	{
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
	 * AJAX handler — save atomic extension toggle states.
	 */
	public function ajax_save_extension_settings(): void
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['fields'])) {
			wp_send_json_error(esc_html__('Missing fields.', 'animation-addons-for-elementor'));
		}

		$raw      = sanitize_text_field(wp_unslash($_POST['fields']));
		$settings = json_decode($raw, true);

		if (! is_array($settings)) {
			wp_send_json_error(esc_html__('Invalid data.', 'animation-addons-for-elementor'));
		}

		$clean = [];
		foreach ($settings as $slug => $state) {
			$slug = sanitize_key($slug);

			if (isset($this->extensions_registry[$slug]) && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		$updated = update_option(self::EXTENSIONS_OPTION_NAME, $clean);

		// Reset cache.
		$this->active_extensions = null;

		wp_send_json([
			'status' => $updated,
			'total'  => count($clean),
		]);
	}

	/**
	 * AJAX handler — retrieve current atomic extension settings.
	 */
	public function ajax_get_extension_settings(): void
	{
		check_ajax_referer('wcf_admin_nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		wp_send_json([
			'settings' => $this->get_saved_extension_options(),
			'config'   => $this->get_dashboard_config(),
		]);
	}

	/* =====================================================================
	 *  Helpers
	 * =================================================================== */

	/**
	 * Check if Elementor version meets the minimum for atomic widgets.
	 *
	 * @return bool
	 */
	private function meets_requirements(): bool
	{
		if (! defined('ELEMENTOR_VERSION')) {
			return false;
		}

		return version_compare(ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '>=');
	}

	/**
	 * On first activation (option does not exist), seed with defaults.
	 */
	private function maybe_seed_widgets_defaults(): void
	{
		$saved = get_option(self::OPTION_NAME);

		// First install: option doesn't exist yet, seed all defaults.
		if (false === $saved) {
			$defaults = [];

			foreach ($this->widgets_registry as $slug => $def) {
				if (! empty($def['default'])) {
					$defaults[$slug] = true;
				}
			}

			add_option(self::OPTION_NAME, $defaults, '', false);
			return;
		}

		// Existing install: merge in any newly-added default widgets
		// that aren't yet in the saved option. This allows new widgets
		// (added in a plugin update) to auto-activate by default.
		if (! is_array($saved)) {
			$saved = [];
		}

		$changed = false;
		foreach ($this->widgets_registry as $slug => $def) {
			if (! empty($def['default']) && ! isset($saved[$slug])) {
				$saved[$slug] = true;
				$changed = true;
			}
		}

		if ($changed) {
			update_option(self::OPTION_NAME, $saved, false);
		}
	}

	/**
	 * On first activation (option does not exist), seed extension defaults.
	 */
	private function maybe_seed_extension_defaults(): void
	{
		if (false !== get_option(self::EXTENSIONS_OPTION_NAME)) {
			return;
		}

		$defaults = [];

		foreach ($this->extensions_registry as $slug => $def) {
			if (! empty($def['default'])) {
				$defaults[$slug] = true;
			}
		}

		add_option(self::EXTENSIONS_OPTION_NAME, $defaults, '', false);
	}
	/**
	 * Enqueue global atomic editor scripts into the top-level window.
	 */
	public function enqueue_atomic_editor_scripts(): void
	{
		$this->guard_elementor_core_atomic_types();

		$suffix = $this->is_dev_environment() ? '' : '.min';
		$path = 'assets/atomic/js/atomic-editor' . $suffix . '.js';
		$file_path = WCF_ADDONS_PATH . $path;
		$version = file_exists($file_path) ? filemtime($file_path) : WCF_ADDONS_VERSION;

		wp_enqueue_script(
			'aae-atomic-editor',
			WCF_ADDONS_URL . $path,
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

		// Expose bundled widget presets to the editor bridge so its panel UI
		// (Apply Preset dropdown) can list and apply them. Keyed by widget type.
		wp_localize_script(
			'aae-atomic-editor',
			'AAE_WIDGET_PRESETS',
			$this->get_widget_presets()
		);

		// Loop Grid: ajax config for the editor "full grid live" preview module.
		wp_localize_script(
			'aae-atomic-editor',
			'AAE_LOOP_GRID',
			[
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('aae_loop_grid'),
			]
		);
	}

	/**
	 * Elementor 4.1 throws when its core Atomic views were already registered.
	 * Make only the two Elementor-owned registrations idempotent; all custom
	 * element type collisions continue to throw normally.
	 */
	private function guard_elementor_core_atomic_types(): void
	{
		$handle = 'elementor-atomic-widgets-editor';

		if (! wp_script_is($handle, 'enqueued')) {
			return;
		}

		wp_add_inline_script(
			$handle,
			<<<'JS'
(function () {
	var manager = window.elementor && window.elementor.elementsManager;
	if (! manager || manager.__aaeCoreAtomicGuard) {
		return;
	}

	var original = manager.registerElementType;
	manager.registerElementType = function (element) {
		var type = element && typeof element.getType === 'function' ? element.getType() : '';
		if (
			(type === 'e-div-block' || type === 'e-flexbox') &&
			this.elementTypes && this.elementTypes[type]
		) {
			return this.elementTypes[type];
		}

		return original.call(this, element);
	};
	manager.__aaeCoreAtomicGuard = true;
}());
JS,
			'before'
		);
	}

	/**
	 * Scan every widget's presets/ folder and return the parsed JSON presets,
	 * grouped by the widget type they belong to, so the editor can list the
	 * presets relevant to the selected element.
	 *
	 * Two file formats are accepted:
	 *   - Elementor native export: { content:[ <model> ], title, type, ... }
	 *     (the user exports a flex container holding the design)
	 *   - Plugin format:           { name, model:{...} }
	 *
	 * The exposed model is the root export element (e.g. an e-flexbox wrapper).
	 * The editor unwraps a container wrapper on apply and places its children
	 * at the selected element's position. The preset is keyed by the primary
	 * atomic widget found inside (e.g. e-aae-a-advanced-heading) so it shows
	 * when that widget is selected — not when a bare flexbox is selected.
	 *
	 * @return array<string, array<int, array>> elementType => preset[]
	 */
	private function get_widget_presets(): array
	{
		$presets = [];
		
		foreach ($this->get_available_widgets() as $widget_data) {
			if (empty($widget_data['file'])) {
				continue;
			}

			$widget_dir = wp_normalize_path(dirname(WCF_ADDONS_PATH . 'inc/AtomicWidgets/' . $widget_data['file']));
			$preset_dir = $widget_dir . '/presets';

			if (! is_dir($preset_dir)) {
				continue;
			}

			foreach (glob($preset_dir . '/*.json') as $file) {
				$raw = file_get_contents($file);
				if (false === $raw) {
					continue;
				}

				$data = json_decode($raw, true);
				if (! is_array($data)) {
					continue;
				}

				// Resolve the root model + name from either supported format.
				$model = null;
				$name  = basename($file, '.json');

				if (! empty($data['model']) && is_array($data['model'])) {
					// Plugin format.
					$model = $data['model'];
					if (isset($data['name'])) {
						$name = (string) $data['name'];
					}
				} elseif (! empty($data['content'][0]) && is_array($data['content'][0])) {
					// Elementor native export: content[] holds top-level elements;
					// the first is the wrapper we treat as the preset model.
					$model = $data['content'][0];
					if (! empty($data['title'])) {
						$name = (string) $data['title'];
					}
				}

				if (! $model) {
					continue;
				}

				// Key by the primary atomic widget inside the model (so a
				// flex-wrapped heading preset shows when a heading is selected),
				// falling back to the model's own type.
				$type = $this->detect_primary_widget_type($model);
				if ('' === $type) {
					continue;
				}

				$presets[$type][] = [
					'id'    => sanitize_key(basename($file, '.json')),
					'name'  => $name,
					'model' => $model,
				];
			}
		}

		return $presets;
	}

	/**
	 * Find the most relevant widget type a preset targets. If the root is a
	 * layout container, descend to the first AAE atomic widget inside; else use
	 * the root's own type. Returns the type string Elementor reports for the
	 * element (elType for atomic elements, widgetType for classic widgets).
	 *
	 * @param array $model Element model.
	 * @return string
	 */
	private function detect_primary_widget_type(array $model): string
	{
		$container_types = ['e-flexbox', 'e-div-block', 'e-grid', 'container'];

		$root_type = $model['elType'] ?? '';
		if ('widget' === $root_type && ! empty($model['widgetType'])) {
			$root_type = $model['widgetType'];
		}

		// If the root isn't a container, it's the target itself.
		if (! in_array($root_type, $container_types, true)) {
			return $root_type;
		}

		// Descend breadth-first to the first AAE atomic widget.
		$queue = $model['elements'] ?? [];

		while (! empty($queue)) {
			$node = array_shift($queue);
			if (! is_array($node)) {
				continue;
			}

			$type = $node['elType'] ?? '';
			if ('widget' === $type && ! empty($node['widgetType'])) {
				$type = $node['widgetType'];
			}

			if (is_string($type) && 0 === strpos($type, 'e-aae-a-')) {
				return $type;
			}

			if (! empty($node['elements']) && is_array($node['elements'])) {
				foreach ($node['elements'] as $child) {
					$queue[] = $child;
				}
			}
		}

		// No AAE widget inside — fall back to the container type itself.
		return $root_type;
	}
}

// Initialize.
Atomic::instance();
