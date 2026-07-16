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
			'aae-a-toggle-pane-main',
			'aae-a-video-mask-btn',
			'aae-a-flip-box-face',
			'aae-a-post-card',
			'aae-a-offcanvas-panel',
			'aae-a-offcanvas-trigger',
			'aae-a-offcanvas-close',
			'aae-a-timeline-item',
			'aae-a-timeline-main-item',
			'aae-a-social-share-main-item',
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
			'aae-a-loop-number',
			'aae-a-loop-loadmore',
			'aae-a-loop-arrow',
			'aae-a-loop-nav-wrap',
			// Loop Grid Slider structural pieces — always-on internal elements,
			// seeded as default children of the slider root (same reasoning as the
			// Loop Grid pieces above).
			'aae-a-loop-slide-track',
			'aae-a-loop-slide-item',
			'aae-a-loop-slide-pagination',
			// Loop Grid current-post building blocks: seeded as default loop-item
			// children — must always be registered so the featured image / title
			// resolve per post.
			'aae-a-post-image',
			'aae-a-post-title',
			// AAE Form parts — seeded as the form's default children, so they
			// must always be registered (same reasoning as the Loop pieces).
			'aae-a-form-label',
			'aae-a-form-input',
			'aae-a-form-textarea',
			'aae-a-form-checkbox',
			'aae-a-form-radio',
			'aae-a-form-select',
			'aae-a-form-submit',
			'aae-a-form-success-message',
			'aae-a-form-error-message',
			// Search Form composite sub-elements — seeded as locked default
			// children of the Search Form root; always-on so the editor never
			// throws ElementTypeNotFound on drop.
			'aae-a-search-toggle',
			'aae-a-search-panel',
			'aae-a-search-field',
			'aae-a-search-input',
			'aae-a-search-filter-date',
			'aae-a-search-filter-category',
			'aae-a-search-submit',
			'aae-a-search-results',
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
			'aae-a-loop-number' => [
				'label'        => 'Loop Page Number',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Number',
				'icon'         => 'eicon-number-field',
				'keywords'     => [ 'loop', 'number', 'pagination' ],
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

			'aae-a-search-form' => [
				'label'        => 'Search Form',
				'description'  => 'Composite Ajax search form (inline / dropdown / fullscreen) with category & date filters — every part is a styleable atomic element.',
				'icon'         => 'eicon-search',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Form',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'search', 'form', 'ajax', 'filter', 'atomic', 'composite' ],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],
			'aae-a-search-toggle' => [
				'label'        => 'Search Toggle',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Toggle',
				'icon'         => 'eicon-search',
				'keywords'     => [ 'search', 'toggle' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-panel' => [
				'label'        => 'Search Panel',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Panel',
				'icon'         => 'eicon-container',
				'keywords'     => [ 'search', 'panel' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-field' => [
				'label'        => 'Search Field',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Field',
				'icon'         => 'eicon-form-horizontal',
				'keywords'     => [ 'search', 'field', 'form' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-input' => [
				'label'        => 'Search Input',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Input',
				'icon'         => 'eicon-form-horizontal',
				'keywords'     => [ 'search', 'input' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-filter-date' => [
				'label'        => 'Date Filter',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Filter_Date',
				'icon'         => 'eicon-calendar',
				'keywords'     => [ 'search', 'filter', 'date' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-filter-category' => [
				'label'        => 'Category Filter',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Filter_Category',
				'icon'         => 'eicon-folder',
				'keywords'     => [ 'search', 'filter', 'category' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-submit' => [
				'label'        => 'Search Submit',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Submit',
				'icon'         => 'eicon-button',
				'keywords'     => [ 'search', 'submit', 'button' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-results' => [
				'label'        => 'Search Results',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Results',
				'icon'         => 'eicon-post-list',
				'keywords'     => [ 'search', 'results', 'ajax' ],
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

			'aae-a-loop-grid-slider' => [
				'label'        => 'Loop Grid Slider',
				'description'  => 'Query posts and present each as a slide, driven by the shared nested-slider runtime (effect / autoplay / coverflow / 3D) with AJAX load-more paging.',
				'icon'         => 'eicon-slider-push',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'loop',
					'grid',
					'slider',
					'carousel',
					'posts',
					'query',
					'dynamic',
				],
				'category'     => 'general',
				'order'        => 1,
				'demo_url'     => '',
				'doc_url'      => '',
			],
			'aae-a-loop-slide-track' => [
				'label'        => 'Slider Track',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Track',
				'icon'         => 'eicon-slider-push',
				'keywords'     => [ 'loop', 'slider', 'track' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-slide-item' => [
				'label'        => 'Loop Slide Item',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Item',
				'icon'         => 'eicon-container',
				'keywords'     => [ 'loop', 'slide', 'item' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-slide-pagination' => [
				'label'        => 'Slider Pagination',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Pagination',
				'icon'         => 'eicon-ellipsis-h',
				'keywords'     => [ 'loop', 'slider', 'pagination' ],
				'hide_from_panel' => true,
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
				'description'  => 'An open, unlocked social-share row — three editable icon+label items to duplicate, restyle, or delete. Pair with the ready-made minimal/outlined/solid templates.',
				'icon'         => 'eicon-share',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'social',
					'share',
					'atomic',
					'aae',
					'open',
				],
				'category'     => 'general',
				'order'        => 10,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-social-share-item' => [
				'label'        => 'Social Share Item',
				'description'  => 'An open icon+label link item used inside Social Share, or on its own.',
				'icon'         => 'eicon-share',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'social',
					'share',
					'item',
					'link',
				],
				'category'     => 'general',
				'order'        => 11,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-social-share-main' => [
				'label'        => 'Social Share Main',
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
					'main',
				],
				'category'     => 'general',
				'order'        => 12,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-social-share-main-item' => [
				'label'        => 'Social Share Main Item',
				'description'  => 'Internal child item for Social Share Main.',
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
				'order'        => 13,
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

			'aae-a-timeline-main' => [
				'label'        => 'Timeline Main',
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
					'main',
				],
				'category'     => 'general',
				'order'        => 15,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-timeline-main-item' => [
				'label'        => 'Timeline Main — Item',
				'description'  => 'Internal event-row sub-element used by Timeline Main (marker + date + title + description).',
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
					'main',
				],
				'category'     => 'general',
				'order'        => 16,
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
				'label'        => 'Progress Bar Template',
				'description'  => 'A very basic open progress-bar container — no style presets, just track/fill or ring children you can fill or restyle natively.',
				'icon'         => 'eicon-skill-bar',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'progress',
					'progressbar',
					'bar',
					'template',
					'container',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 12,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-progressbar-main' => [
				'label'        => 'Progress Bar Main',
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
				'order'        => 13,
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

			'aae-a-toggle-switcher-main' => [
				'label'        => 'Toggle Switcher Main',
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
					'main',
				],
				'category'     => 'general',
				'order'        => 15,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-toggle-pane-main' => [
				'label'        => 'Toggle Pane Main (Internal)',
				'description'  => 'Internal child container for Toggle Switcher Main.',
				'icon'         => 'eicon-inner-section',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'toggle pane',
					'internal',
					'main',
				],
				'category'     => 'general',
				'order'        => 16,
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

			'aae-a-offcanvas-trigger' => [
				'label'           => 'Offcanvas Trigger',
				'class_name'      => 'WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Trigger',
				'icon'            => 'eicon-menu-bar',
				'keywords'        => [ 'offcanvas', 'trigger', 'icon' ],
				'hide_from_panel' => true,
			],
			'aae-a-offcanvas-close' => [
				'label'           => 'Offcanvas Close',
				'class_name'      => 'WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Close',
				'icon'            => 'eicon-close',
				'keywords'        => [ 'offcanvas', 'close', 'icon' ],
				'hide_from_panel' => true,
			],

			'aae-a-form' => [
				'label'        => 'Form',
				'description'  => 'Atomic-first form: real child fields, locked submit button. Milestone 1 skeleton — no submit logic yet.',
				'icon'         => 'eicon-form-horizontal',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form',
					'contact',
					'lead',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-label' => [
				'label'        => 'Form Label',
				'description'  => 'Label widget for AAE Form — linked to an input by ID; drag from the panel to add more fields.',
				'icon'         => 'eicon-t-letter',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form label',
					'internal',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-input' => [
				'label'        => 'Form Input',
				'description'  => 'Input widget for AAE Form — text/email/number/tel/password via a type prop.',
				'icon'         => 'eicon-form-horizontal',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form input',
					'internal',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-textarea' => [
				'label'        => 'Form Textarea',
				'description'  => 'Textarea widget for AAE Form.',
				'icon'         => 'eicon-textarea',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form textarea',
					'internal',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-checkbox' => [
				'label'        => 'Form Checkbox',
				'description'  => 'Checkbox widget for AAE Form — fully styleable, with checked state.',
				'icon'         => 'eicon-check-circle',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form checkbox',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-radio' => [
				'label'        => 'Form Radio',
				'description'  => 'Radio button widget for AAE Form — radios sharing a group name are exclusive.',
				'icon'         => 'eicon-circle-o',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form radio',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-select' => [
				'label'        => 'Form Select',
				'description'  => 'Select/dropdown widget for AAE Form — options one per line, value|Label.',
				'icon'         => 'eicon-select',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form select',
					'dropdown',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-success-message' => [
				'label'        => 'Form Success Message (Internal)',
				'description'  => 'Locked status container shown when the form submits successfully.',
				'icon'         => 'eicon-check',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form success',
					'internal',
				],
				'category'     => 'general',
				'order'        => 19,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-error-message' => [
				'label'        => 'Form Error Message (Internal)',
				'description'  => 'Locked status container shown when the form submission fails.',
				'icon'         => 'eicon-close',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form error',
					'internal',
				],
				'category'     => 'general',
				'order'        => 19,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-submit' => [
				'label'        => 'Form Submit Button',
				'description'  => 'Submit button widget for AAE Form — drag from the panel to place it anywhere inside the form.',
				'icon'         => 'eicon-button',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form submit',
					'internal',
				],
				'category'     => 'general',
				'order'        => 19,
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

			'aae-a-btn' => [
				'label'        => 'ButtonTemplate',
				'description'  => 'A very basic open button container — no style presets, just a link wrapper you can fill with any nested elements.',
				'icon'         => 'wcf-icon-Button',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'button',
					'basic button',
					'template',
					'container',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 21,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-btn-pro' => [
				'label'        => 'ButtonTemplate Pro',
				'description'  => 'A very basic open button container — no style presets, just a link wrapper you can fill with any nested elements.',
				'icon'         => 'wcf-icon-Button',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'button',
					'basic button',
					'template',
					'container',
					'atomic',
					'pro',
				],
				'category'     => 'general',
				'order'        => 22,
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

		// Register library-document types for our atomic top-level widgets so
		// "Save as a template" works on them (Elementor only registers types for
		// e-flexbox / e-div-block / e-form; our roots would otherwise fail with
		// "Invalid template type"). See inc/AtomicWidgets/Library/.
		add_action('elementor/documents/register', [$this, 'register_library_documents']);
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

		// Loop Grid: AJAX search options for the panel's `aae-query-chips`
		// controls (posts by title/ID, taxonomy terms by name).
		add_action('wp_ajax_aae_loop_query_options', [$this, 'ajax_loop_query_options']);

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
				// Editor-only stylesheet: canvas selectability + edit-handle overlay
				// placement for the pagination pieces. Enqueued ONLY in the editor
				// preview (see enqueue_atomic_preview_styles); never on the frontend,
				// so the shipped loop-grid.css stays lean.
				'editor_style_handle' => 'aae-a-loop-grid-editor-css',
				'editor_style_path'   => '/assets/atomic/css/loop-grid-editor.css',
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
			'aae-a-loop-number' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Number',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-number.php',
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

			'aae-a-search-form' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Form',
				'file' => 'Widgets/SearchForm/class-aae-a-search-form.php',
				'script_handle' => 'aae-a-search-form-js',
				'script_path' => '/assets/atomic/js/search-form.js',
				'has_script' => true,
			],
			'aae-a-search-toggle' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Toggle',
				'file' => 'Widgets/SearchForm/class-aae-a-search-toggle.php',
				'has_script' => false,
			],
			'aae-a-search-panel' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Panel',
				'file' => 'Widgets/SearchForm/class-aae-a-search-panel.php',
				'has_script' => false,
			],
			'aae-a-search-field' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Field',
				'file' => 'Widgets/SearchForm/class-aae-a-search-field.php',
				'has_script' => false,
			],
			'aae-a-search-input' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Input',
				'file' => 'Widgets/SearchForm/class-aae-a-search-input.php',
				'has_script' => false,
			],
			'aae-a-search-filter-date' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Filter_Date',
				'file' => 'Widgets/SearchForm/class-aae-a-search-filter-date.php',
				'has_script' => false,
			],
			'aae-a-search-filter-category' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Filter_Category',
				'file' => 'Widgets/SearchForm/class-aae-a-search-filter-category.php',
				'has_script' => false,
			],
			'aae-a-search-submit' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Submit',
				'file' => 'Widgets/SearchForm/class-aae-a-search-submit.php',
				'has_script' => false,
			],
			'aae-a-search-results' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Results',
				'file' => 'Widgets/SearchForm/class-aae-a-search-results.php',
				'has_script' => false,
			],

			// Loop Grid Slider — reuses the Loop Grid query engine + the shared
			// nested-slider runtime. Its only own script is the load-more bridge
			// (paging appends slides then re-binds the shared slider runtime).
			'aae-a-loop-grid-slider' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Grid_Slider',
				'file' => 'Widgets/LoopGridSlider/class-aae-a-loop-grid-slider.php',
				'script_handle' => 'aae-a-loop-grid-slider-js',
				'script_path' => '/assets/atomic/js/loop-grid-slider.js',
				'has_script' => true,
				// Load after the shared runtime so window.AAEADDON.rebind exists when
				// the bridge appends slides (it also guards defensively at call time).
				'script_deps' => [ 'aae-atomic-common' ],
				'style_handle' => 'aae-a-loop-grid-slider-css',
				'style_path' => '/assets/atomic/css/loop-grid-slider.css',
			],
			'aae-a-loop-slide-track' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Track',
				'file' => 'Widgets/LoopGridSlider/class-aae-a-loop-slide-track.php',
				'has_script' => false,
			],
			'aae-a-loop-slide-item' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Item',
				'file' => 'Widgets/LoopGridSlider/class-aae-a-loop-slide-item.php',
				'has_script' => false,
			],
			'aae-a-loop-slide-pagination' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Pagination',
				'file' => 'Widgets/LoopGridSlider/class-aae-a-loop-slide-pagination.php',
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
			'class'        => '\WCF_ADDONS\AtomicWidgets\Widgets\SocialShare\AAE_A_Social_Share',
			'file'         => 'Widgets/SocialShare/class-aae-a-social-share.php',
			'has_script'   => false,
			// No JS behavior yet (see Widgets/SocialShare/assets/js/social-share.js) —
			// only the on-demand stylesheet is registered.
			'style_handle' => 'aae-a-social-share-css',
			'style_path'   => '/assets/atomic/js/social-share.css',
		],
		'aae-a-social-share-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\SocialShare\AAE_A_Social_Share_Item',
			'file'       => 'Widgets/SocialShare/class-aae-a-social-share-item.php',
			'has_script' => false,
		],
		'aae-a-social-share-main' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\SocialShareMain\AAE_A_Social_Share_Main',
			'file'          => 'Widgets/SocialShareMain/class-aae-a-social-share-main.php',
			'script_handle' => 'aae-a-social-share-main-js',
			'script_path'   => '/assets/atomic/js/social-share-main.js',
			'has_script'    => true,
		],
		'aae-a-social-share-main-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\SocialShareMain\AAE_A_Social_Share_Main_Item',
			'file'       => 'Widgets/SocialShareMain/class-aae-a-social-share-main-item.php',
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
		'aae-a-timeline-main' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\TimelineMain\AAE_A_Timeline_Main',
			'file'       => 'Widgets/TimelineMain/class-aae-a-timeline-main.php',
			'has_script' => false,
			// No external CSS: all per-element styles live in the widget's
			// define_base_styles() (compound selectors) + a tiny inline
			// <style> in the item Twig for the spine shorthand + the
			// marker's negative-inset positioning. No `style_handle`.
		],
		'aae-a-timeline-main-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\TimelineMain\AAE_A_Timeline_Main_Item',
			'file'       => 'Widgets/TimelineMain/class-aae-a-timeline-main-item.php',
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

			'aae-a-btn' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Btn\AAE_A_Btn',
				'file'          => 'Widgets/Btn/class-aae-a-btn.php',
				'script_handle' => 'aae-a-btn-js',
				'script_path'   => '/assets/atomic/js/btn.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-btn-css',
				'style_path'    => '/assets/atomic/js/btn.css',
			],

			'aae-a-btn-pro' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\BtnPro\AAE_A_Btn_Pro',
				'file'          => 'Widgets/BtnPro/class-aae-a-btn-pro.php',
				'script_handle' => 'aae-a-btn-pro-js',
				'script_path'   => '/assets/atomic/js/btn-pro.js',
				'script_deps'   => [ 'gsap' ], // Ripple + polygon magnetic-move effects need GSAP.
				'has_script'    => true,
				'style_handle'  => 'aae-a-btn-pro-css',
				'style_path'    => '/assets/atomic/js/btn-pro.css',
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

			'aae-a-progressbar-main' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\ProgressbarMain\AAE_A_Progressbar_Main',
				'file'          => 'Widgets/ProgressbarMain/class-aae-a-progressbar-main.php',
				'script_handle' => 'aae-a-progressbar-main-js',
				'script_path'   => '/assets/atomic/js/progressbar-main.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-progressbar-main-css',
				'style_path'    => '/assets/atomic/js/progressbar-main.css',
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

			'aae-a-toggle-switcher-main' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcherMain\AAE_A_Toggle_Switcher_Main',
				'file'          => 'Widgets/ToggleSwitcherMain/class-aae-a-toggle-switcher-main.php',
				'script_handle' => 'aae-a-toggle-switcher-main-js',
				'script_path'   => '/assets/atomic/js/toggle-switcher-main.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-toggle-switcher-main-css',
				'style_path'    => '/assets/atomic/js/toggle-switcher-main.css',
			],

			'aae-a-toggle-pane-main' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcherMain\AAE_A_Toggle_Pane_Main',
				'file'       => 'Widgets/ToggleSwitcherMain/class-aae-a-toggle-pane-main.php',
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

			'aae-a-offcanvas-trigger' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Trigger',
				'file'       => 'Widgets/Offcanvas/class-aae-a-offcanvas-trigger.php',
				'has_script' => false,
			],
			'aae-a-offcanvas-close' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Close',
				'file'       => 'Widgets/Offcanvas/class-aae-a-offcanvas-close.php',
				'has_script' => false,
			],

			'aae-a-form' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form',
				'file'          => 'Widgets/Form/class-aae-a-form.php',
				'script_handle' => 'aae-a-form-js',
				'script_path'   => '/assets/atomic/js/form.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-form-css',
				'style_path'    => '/assets/atomic/css/form.css',
			],

			'aae-a-form-label' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Label',
				'file'       => 'Widgets/Form/class-aae-a-form-label.php',
				'has_script' => false,
			],

			'aae-a-form-input' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Input',
				'file'       => 'Widgets/Form/class-aae-a-form-input.php',
				'has_script' => false,
			],

			'aae-a-form-textarea' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Textarea',
				'file'       => 'Widgets/Form/class-aae-a-form-textarea.php',
				'has_script' => false,
			],

			'aae-a-form-checkbox' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Checkbox',
				'file'       => 'Widgets/Form/class-aae-a-form-checkbox.php',
				'has_script' => false,
			],

			'aae-a-form-radio' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Radio',
				'file'       => 'Widgets/Form/class-aae-a-form-radio.php',
				'has_script' => false,
			],

			'aae-a-form-select' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Select',
				'file'       => 'Widgets/Form/class-aae-a-form-select.php',
				'has_script' => false,
			],

			'aae-a-form-success-message' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Success_Message',
				'file'       => 'Widgets/Form/class-aae-a-form-success-message.php',
				'has_script' => false,
			],

			'aae-a-form-error-message' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Error_Message',
				'file'       => 'Widgets/Form/class-aae-a-form-error-message.php',
				'has_script' => false,
			],

			'aae-a-form-submit' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Submit',
				'file'       => 'Widgets/Form/class-aae-a-form-submit.php',
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

	/**
	 * Make sure the Loop Grid element class (and its shared query builder) is
	 * loaded. Element classes are normally require'd during Elementor's element
	 * registration, which doesn't run on a plain admin-ajax request.
	 */
	private static function load_loop_grid_class(): void
	{
		if (! class_exists(\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid::class)) {
			require_once __DIR__ . '/Widgets/LoopGrid/class-aae-a-loop-grid.php';
		}
	}

	/**
	 * Panel search for the `aae-query-chips` controls: posts (by title / ID) or
	 * taxonomy terms (by name). Returns [{id, label}] — max 20.
	 */
	public function ajax_loop_query_options()
	{
		check_ajax_referer('aae_loop_grid', 'nonce');

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : 'post';
		$term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
		$options = [];

		if ('term' === $kind) {
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

	public function ajax_loop_post_data()
	{
		check_ajax_referer('aae_loop_grid', 'nonce');

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		self::load_loop_grid_class();

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
			$sample = self::get_sample_post();
			if ($sample instanceof \WP_Post) {
				$filters['_context_post_id'] = $sample->ID;
			}
		}

		$query = new \WP_Query(
			\WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid::build_query_args($filters)
		);

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

		// The static grid and the slider variant share this endpoint. Both root
		// types publish the same Render_Context (keyed by AAE_A_Loop_Grid::class,
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
		self::load_loop_grid_class();
		$gs = (array) ($grid_el['settings'] ?? []);

		// Related source: the requesting page's post is the relatedness anchor
		// (admin-ajax has no queried object of its own).
		$gs['_context_post_id'] = $post_id;

		// Current Query source: the archive's query vars, captured into the
		// pagination config at render time and posted back by the runtime.
		if (isset($_POST['qv'])) {
			$qv = json_decode(sanitize_text_field(wp_unslash($_POST['qv'])), true);
			if (is_array($qv)) {
				$gs['_qv'] = $qv; // whitelist-sanitized inside the builder
			}
		}

		$query_args = \WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid::build_query_args(
			$gs,
			$paged
		);

		// Total pages (respects the same query, offset-corrected).
		$max_pages = \WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid::compute_max_pages($gs, $query_args);

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

		// Our own atomic widget stylesheets (e.g. aae-a-nav-css) must ALSO print
		// after editor-preview. The early add_style_dependency() in
		// enqueue_atomic_preview_styles() silently bails when editor-preview isn't
		// registered yet at preview/enqueue_styles time, so on some hard reloads
		// the widget CSS printed before editor-preview and its positioning lost —
		// the Nav dropdown rendered unpositioned / in-flow ("styles missing on
		// reload"). Patching here (wp_print_styles, when every handle is finally
		// registered) makes the dependency reliable.
		$atomic_handles = [];
		foreach ( $this->get_available_widgets() as $widget_data ) {
			if ( ! empty( $widget_data['style_handle'] ) ) {
				$atomic_handles[] = $widget_data['style_handle'];
			}
			if ( ! empty( $widget_data['editor_style_handle'] ) ) {
				$atomic_handles[] = $widget_data['editor_style_handle'];
			}
		}

		$styles = wp_styles();
		foreach ( $styles->registered as $handle => $style ) {
			if ( 'editor-preview' === $handle ) {
				continue;
			}
			// Elementor per-document CSS handles + our atomic widget stylesheets.
			$is_document = preg_match( '/^(local-\d+-preview|elementor-post-\d+)/', $handle );
			if ( ! $is_document && ! in_array( $handle, $atomic_handles, true ) ) {
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

	/**
	 * Register library-document types for the atomic top-level widgets, so the
	 * editor's "Save as a template" (which sends the element's own elType as the
	 * template type) resolves to a valid document and passes the local source's
	 * is_valid_template_type() check. Mirrors Elementor's own registration for
	 * e-flexbox / e-div-block.
	 *
	 * @param \Elementor\Core\Documents_Manager $documents_manager
	 */
	public function register_library_documents($documents_manager)
	{
		require_once __DIR__ . '/Library/class-aae-a-library-document.php';
		require_once __DIR__ . '/Library/class-aae-a-library-documents.php';

		$documents_manager
			->register_document_type('e-aae-a-loop-grid', \WCF_ADDONS\AtomicWidgets\Library\AAE_A_Loop_Grid_Document::class)
			->register_document_type('e-aae-a-loop-grid-slider', \WCF_ADDONS\AtomicWidgets\Library\AAE_A_Loop_Grid_Slider_Document::class)
			->register_document_type('e-aae-a-slider', \WCF_ADDONS\AtomicWidgets\Library\AAE_A_Slider_Document::class);
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

				// Editor-only stylesheet: NOT registered by register_atomic_styles()
				// (which feeds the frontend), so it never reaches a published page.
				// Register it on the spot here and enqueue it in the preview only.
				if ( ! empty( $widget_data['editor_style_handle'] ) && ! empty( $widget_data['editor_style_path'] ) ) {
					$this->register_editor_style( $widget_data['editor_style_handle'], $widget_data['editor_style_path'] );
					$this->add_style_dependency( $widget_data['editor_style_handle'], 'editor-preview' );
					wp_enqueue_style( $widget_data['editor_style_handle'] );
				}
			}
		}
	}

	/**
	 * Register an editor-only widget stylesheet (preview iframe only).
	 *
	 * Mirrors the min-file + filemtime versioning of register_atomic_styles(),
	 * but is intentionally NOT called from the frontend registration path, so the
	 * handle is unknown on published pages and the CSS is never shipped there.
	 *
	 * @param string $handle Style handle to register.
	 * @param string $path   Plugin-relative path to the .css (min variant used in prod).
	 */
	private function register_editor_style( string $handle, string $path ): void {
		if ( wp_style_is( $handle, 'registered' ) ) {
			return;
		}
		if ( ! $this->is_dev_environment() ) {
			$min_path = str_replace( '.css', '.min.css', $path );
			if ( file_exists( WCF_ADDONS_PATH . $min_path ) ) {
				$path = $min_path;
			}
		}
		$file_path = WCF_ADDONS_PATH . $path;
		$version   = file_exists( $file_path ) ? filemtime( $file_path ) : WCF_ADDONS_VERSION;
		wp_register_style( $handle, WCF_ADDONS_URL . $path, [], $version );
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
		// it break design in editor so i commented it
		//$this->guard_elementor_core_atomic_types();

		$suffix = $this->is_dev_environment() ? '' : '.min';
		$path = 'assets/atomic/js/atomic-editor' . $suffix . '.js';
		$file_path = WCF_ADDONS_PATH . $path;
		// Version the URL from the built file itself. Unlike a timestamp-only
		// version, this also changes for multiple builds written in one second.
		$version = WCF_ADDONS_VERSION;
		if (is_readable($file_path)) {
			$content_hash = md5_file($file_path);
			$version = false !== $content_hash
				? WCF_ADDONS_VERSION . '-' . substr($content_hash, 0, 12)
				: (string) filemtime($file_path);
		}
		

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
	 * Elementor 4.1 throws ("Element type already registered") when its core
	 * Atomic views (`e-div-block` / `e-flexbox`) get registered a second time.
	 *
	 * The previous guard swallowed the throw by RETURNING the already-registered
	 * (stale, first) type object and skipping `original.call`. That was wrong: on
	 * a fresh element drop it bound the container to the earlier, incomplete type
	 * definition, so the new flexbox rendered missing its `e-con`/`e-flexbox-base`
	 * atomic classes (layout broke). See git history / issue: dropping a flexbox
	 * on a new page produced `elementor-element … e-handles-inside` with no base
	 * classes.
	 *
	 * Correct idempotency: on a collision for ONLY these two core types, let the
	 * LATEST registration WIN (overwrite the stored type) instead of throwing or
	 * keeping the stale one. Every other element-type collision still throws
	 * normally, so real double-registration bugs elsewhere stay visible.
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

	var CORE = { 'e-div-block': true, 'e-flexbox': true };
	var original = manager.registerElementType;
	manager.registerElementType = function (element) {
		var type = element && typeof element.getType === 'function' ? element.getType() : '';

		// Only intervene for the two Elementor-owned core atomic types when they
		// are being registered a second time. Let the NEW element replace the old
		// so the freshly-built (complete) type wins — this keeps `e-con` /
		// `e-flexbox-base` base classes on freshly-dropped containers.
		if (CORE[type] && this.elementTypes && this.elementTypes[type]) {
			this.elementTypes[type] = element;
			return element;
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
		$scanned_dirs = [];

		foreach ($this->get_available_widgets() as $widget_data) {
			if (empty($widget_data['file'])) {
				continue;
			}

			$widget_dir = wp_normalize_path(dirname(WCF_ADDONS_PATH . 'inc/AtomicWidgets/' . $widget_data['file']));
			$preset_dir = $widget_dir . '/presets';

			if (! is_dir($preset_dir)) {
				continue;
			}

			// Many widgets share one folder (e.g. all LoopGrid parts live in
			// Widgets/LoopGrid), so the same presets/ dir would be globbed once
			// per sibling widget and every preset would appear N times. Scan each
			// dir only once.
			if (isset($scanned_dirs[$preset_dir])) {
				continue;
			}
			$scanned_dirs[$preset_dir] = true;

			foreach (glob($preset_dir . '/*.json') as $file) {
				$preset = $this->parse_preset_file($file);
				if (! $preset) {
					continue;
				}

				// Key by the primary atomic widget inside the model (so a
				// flex-wrapped heading preset shows when a heading is selected),
				// falling back to the model's own type.
				$type = $this->detect_primary_widget_type($preset['model']);
				if ('' === $type) {
					continue;
				}

				$presets[$type][] = $preset;
			}
		}

		// Native atomic widgets (e-heading, e-button, …) have no widget dir of
		// ours to host a presets/ folder, and detect_primary_widget_type() only
		// recognises e-aae-a-* widgets. So their presets live in one shared root,
		// one sub-folder per element type — the FOLDER NAME is the key:
		//   inc/AtomicWidgets/Presets/e-heading/*.json  =>  presets['e-heading']
		// The matching panel section is injected by Atomic\Presets\Controls,
		// which checks the same folders (keep the path in sync with it).
		$native_root = wp_normalize_path(WCF_ADDONS_PATH . 'inc/AtomicWidgets/Presets');
		if (is_dir($native_root)) {
			foreach (glob($native_root . '/*', GLOB_ONLYDIR) as $type_dir) {
				$type = basename($type_dir);

				foreach (glob($type_dir . '/*.json') as $file) {
					$preset = $this->parse_preset_file($file);
					if ($preset) {
						$presets[$type][] = $preset;
					}
				}
			}
		}

		return $presets;
	}

	/**
	 * Parse one preset .json file into [ id, name, model ], accepting both the
	 * Elementor native export format ({ content:[<model>], title }) and the
	 * plugin format ({ name, model }). Returns null when unreadable/invalid.
	 *
	 * @param string $file Absolute path to the .json file.
	 * @return array{id:string,name:string,model:array}|null
	 */
	private function parse_preset_file(string $file): ?array
	{
		$raw = file_get_contents($file);
		if (false === $raw) {
			return null;
		}

		// Presets ship with a portable `{{AAE_ASSET_URL}}` placeholder
		// instead of a baked-in domain (so the JSON works on any install
		// after this plugin is distributed) — resolve it here the same
		// way live widget code resolves its own asset URLs via
		// WCF_ADDONS_URL (see e.g. AAE_A_Social_Share_Item::get_vendor_svg_url()).
		if (defined('WCF_ADDONS_URL')) {
			$raw = str_replace('{{AAE_ASSET_URL}}', WCF_ADDONS_URL . 'inc/AtomicWidgets/', $raw);
		}

		$data = json_decode($raw, true);
		if (! is_array($data)) {
			return null;
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
			return null;
		}

		return [
			'id'    => sanitize_key(basename($file, '.json')),
			'name'  => $name,
			'model' => $model,
		];
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
