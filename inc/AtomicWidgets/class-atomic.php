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

	/* =====================================================================
	 *  Initialisation
	 * =================================================================== */

	/**
	 * Register all available atomic widget definitions.
	 *
	 * This is the dummy/test array the user requested.
	 * Replace or extend with real widget classes later.
	 */
	private function register_widget_definitions(): void
	{
		$this->widgets_registry = [

			'aae-a-menu' => [
				'label'        => 'Menu',
				'description'  => 'A modern standard navigation menu with GSAP interactions.',
				'icon'         => 'eicon-nav-menu',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'menu',
					'nav',
					'navigation',
					'atomic',
					'gsap',
				],
				'category'     => 'general',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

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

			'aae-a-slider' => [
				'label'        => 'Nested Slider',
				'description'  => 'A GSAP powered fully draggable nested slider container.',
				'icon'         => 'eicon-slider-push',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'slider',
					'nested',
					'carousel',
					'gsap',
				],
				'category'     => 'general',
				'order'        => 1,
				'demo_url'     => '',
				'doc_url'      => '',
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

			'aae-a-toggle-switcher' => [
				'label'        => 'Toggle Switcher',
				'description'  => 'A two-state toggle switcher for displaying alternating content panels.',
				'icon'         => 'eicon-toggle',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'toggle',
					'switcher',
					'switch',
					'tabs',
					'pricing',
					'atomic',
				],
				'category'     => 'general',
				'order'        => 2,
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

			'aae-atomic-nested-slider' => [
				'label'        => 'Nested Slider',
				'description'  => 'Atomic nested slider with drag-and-drop slide management and GSAP-powered transitions.',
				'icon'         => 'wcf-icon-Content-Slider',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'slider',
					'carousel',
					'nested slider',
					'atomic slider',
					'slideshow',
				],
				'category'     => 'general',
				'order'        => 3,
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
		];
	}

	/**
	 * Register all available atomic extension definitions.
	 *
	 * Maps directly to the extensions initialised in
	 * \WCF_ADDONS\Atomic\Bootstrap::init().
	 */
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
		add_action('elementor/atomic-widgets/frontend/loader/scripts/register', [$this, 'register_atomic_scripts']);
		add_action('wp_enqueue_scripts', [$this, 'register_atomic_styles']);
		add_action('elementor/editor/before_enqueue_scripts', [$this, 'register_atomic_styles']);

		// AJAX endpoints for Editor previews
		add_action('wp_ajax_aae_get_menu_html', [$this, 'ajax_get_menu_html']);

		// Seed defaults on first install (option doesn't exist yet).
		$this->maybe_seed_widgets_defaults();
		$this->maybe_seed_extension_defaults();
	}

	/* =====================================================================
	 *  Elementor Integration
	 * =================================================================== */

	/**
	 * Define all available atomic widgets and their scripts.
	 * Simply add a new array entry here when creating a new widget!
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
				'script_handle' => 'aae-a-slider-js',
				'script_path' => '/assets/atomic/js/nestedslider.js',
				'has_script' => true,
			],
			'aae-a-slide' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slide',
				'file' => 'Widgets/NestedSlider/class-aae-a-slide.php',
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
				'style_handle' => 'aae-a-post-image-css',
				'style_path' => '/assets/atomic/css/post-image.css',
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

			'aae-atomic-button' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Button\AAE_Atomic_Button',
				'file'  => 'Widgets/Button/class-aae-atomic-button.php',
				'has_script' => true,
				'script_handle' => 'aae-a-button-js',
				'script_path' => '/assets/atomic/js/button.js',
				'style_handle' => 'aae-a-button-css',
				'style_path' => '/assets/atomic/js/button.css',
			],

			'aae-a-toggle-switcher' => [
				'class'          => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Switcher',
				'file'           => 'Widgets/ToggleSwitcher/class-aae-a-toggle-switcher.php',
				'has_script'     => true,
				'script_handle'  => 'aae-a-toggle-switcher-js',
				'script_path'    => '/assets/atomic/js/toggle-switcher.js',
				'style_handle'   => 'aae-a-toggle-switcher-css',
				'style_path'     => '/assets/atomic/js/toggle-switcher.css',
			],

			// Add new atomic widgets below...
		];
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
				require_once wp_normalize_path(__DIR__ . '/' . $widget_data['file']);
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
				require_once wp_normalize_path(__DIR__ . '/' . $widget_data['file']);
				if (class_exists($widget_data['class']) && !is_subclass_of($widget_data['class'], '\Elementor\Widget_Base')) {
					$elements_manager->register_element_type(new $widget_data['class']());
				}
			}
		}
	}

	/**
	 * Register frontend scripts for active atomic widgets.
	 */
	public function register_atomic_scripts($loader)
	{

		foreach ($this->get_available_widgets() as $widget_id => $widget_data) {
			if ($this->is_widget_active($widget_id) && !empty($widget_data['has_script'])) {
				wp_register_script(
					$widget_data['script_handle'],
					WCF_ADDONS_URL . $widget_data['script_path'],
					['elementor-v2-frontend-handlers'], // Required for @elementor/frontend-handlers register API
					'1.0.0',
					true
				);
			}
		}
	}

	/**
	 * Register frontend styles for active atomic widgets.
	 */
	public function register_atomic_styles()
	{
		foreach ($this->get_available_widgets() as $widget_id => $widget_data) {
			if ($this->is_widget_active($widget_id) && !empty($widget_data['style_handle'])) {
				wp_register_style(
					$widget_data['style_handle'],
					WCF_ADDONS_URL . $widget_data['style_path'],
					[],
					'1.0.0'
				);
			}
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
	 * Seed widget defaults on first install; merge new default widgets on upgrades.
	 */
	private function maybe_seed_widgets_defaults(): void
	{
		$existing = \get_option(self::OPTION_NAME);

		if (false === $existing) {
			$defaults = [];
			foreach ($this->widgets_registry as $slug => $def) {
				if (! empty($def['default'])) {
					$defaults[$slug] = true;
				}
			}
			\add_option(self::OPTION_NAME, $defaults, '', false);
			return;
		}

		// Merge any newly registered widgets (with default => true) that are not
		// yet present in the saved option so they activate automatically on upgrade.
		$merged  = false;
		foreach ($this->widgets_registry as $slug => $def) {
			if (! empty($def['default']) && ! \array_key_exists($slug, $existing)) {
				$existing[$slug] = true;
				$merged          = true;
			}
		}
		if ($merged) {
			\update_option(self::OPTION_NAME, $existing);
		}
	}

	/**
	 * Seed extension defaults on first install; merge new default extensions on upgrades.
	 */
	private function maybe_seed_extension_defaults(): void
	{
		$existing = \get_option(self::EXTENSIONS_OPTION_NAME);

		if (false === $existing) {
			$defaults = [];
			foreach ($this->extensions_registry as $slug => $def) {
				if (! empty($def['default'])) {
					$defaults[$slug] = true;
				}
			}
			\add_option(self::EXTENSIONS_OPTION_NAME, $defaults, '', false);
			return;
		}

		$merged = false;
		foreach ($this->extensions_registry as $slug => $def) {
			if (! empty($def['default']) && ! \array_key_exists($slug, $existing)) {
				$existing[$slug] = true;
				$merged          = true;
			}
		}
		if ($merged) {
			\update_option(self::EXTENSIONS_OPTION_NAME, $existing);
		}
	}
}

// Initialize.
Atomic::instance();
