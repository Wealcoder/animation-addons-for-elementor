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
		// Force internal child widgets to be active always
		$internal_widgets = [
			'aae-a-slide',
			'aae-a-slider-track',
			'aae-a-slider-nav-prev',
			'aae-a-slider-nav-next',
			'aae-a-slider-pagination',
			'aae-a-counter-number',
			'aae-a-accordion-item',
			'aae-a-icon-list-item',
			'aae-a-countdown-unit',
			'aae-a-toggle-pane',
			'aae-a-offcanvas-panel',
			'aae-a-timeline-item',
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

	/* =====================================================================
	 *  Initialisation
	 * =================================================================== */

	/* =====================================================================
	 *  HOW TO ADD A NEW ATOMIC WIDGET
	 * ---------------------------------------------------------------------
	 *  Adding a new v4 atomic widget to this plugin is a 2-step edit
	 *  inside this file (after you create the widget folder under
	 *  inc/AtomicWidgets/Widgets/<PascalName>/). The Menu widget is the
	 *  canonical reference — follow its exact folder structure:
	 *
	 *    inc/AtomicWidgets/Widgets/<PascalName>/
	 *      ├── class-aae-a-<slug>.php   (Atomic_Widget_Base subclass)
	 *      ├── aae-a-<slug>.html.twig   (Twig template)
	 *      └── assets/
	 *          ├── js/<slug>.js         (source JS — uses @elementor/frontend-handlers)
	 *          └── scss/<slug>.scss     (source SCSS — optional)
	 *
	 *  Built outputs land at:
	 *    /assets/atomic/js/<slug>.js
	 *    /assets/atomic/css/<slug>.css
	 *
	 * ---------------------------------------------------------------------
	 *  STEP 1 — Append a definition inside register_widget_definitions()
	 *           below. This makes the widget appear as a toggle on the
	 *           AAE dashboard so users can enable/disable it.
	 *
	 *      'aae-a-<slug>' => [
	 *          'label'        => '<Human Title>',
	 *          'description'  => '<one-line description for dashboard card>',
	 *          'icon'         => 'eicon-<elementor-icon>',
	 *          'is_pro'       => false,
	 *          'is_extension' => false,
	 *          'is_upcoming'  => false,
	 *          'default'      => true,   // enabled on fresh installs
	 *          'keywords'     => [ '<slug>', 'atomic', 'aae' ],
	 *          'category'     => 'general',
	 *          'order'        => 0,
	 *          'demo_url'     => '',
	 *          'doc_url'      => '',
	 *      ],
	 *
	 * ---------------------------------------------------------------------
	 *  STEP 2 — Append a mapping inside get_available_widgets() below.
	 *           This tells Elementor which PHP class to instantiate and
	 *           which JS/CSS handles to register.
	 *
	 *      'aae-a-<slug>' => [
	 *          'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\<PascalName>\AAE_A_<PascalSlug>',
	 *          'file'          => 'Widgets/<PascalName>/class-aae-a-<slug>.php',
	 *          'script_handle' => 'aae-a-<slug>-js',
	 *          'script_path'   => '/assets/atomic/js/<slug>.js',
	 *          'has_script'    => true,
	 *          'style_handle'  => 'aae-a-<slug>-css',          // omit if no SCSS
	 *          'style_path'    => '/assets/atomic/css/<slug>.css', // omit if no SCSS
	 *      ],
	 *
	 *  That's it. The loops in register_widgets(), register_atomic_scripts(),
	 *  and register_atomic_styles() pick up the new entry automatically —
	 *  NO further edits inside this file are needed.
	 *
	 * ---------------------------------------------------------------------
	 *  CONTAINER WIDGETS (Atomic_Element_Base — like Nested Slider)
	 *
	 *  If your widget extends Atomic_Element_Base instead of
	 *  Atomic_Widget_Base, omit the script bits if it has no JS, and
	 *  register_elements() (not register_widgets()) will handle it
	 *  automatically because is_subclass_of() routes the two.
	 *
	 * ---------------------------------------------------------------------
	 *  NAMING RULES (keep these EXACT — they appear in 6+ places)
	 *
	 *    <slug>       lowercase kebab            e.g.  menu       / counter
	 *    <PascalName> folder name                e.g.  Menu       / Counter
	 *    <PascalSlug> class-name slug            e.g.  Menu       / Counter
	 *    element type 'e-aae-a-<slug>'           e.g.  e-aae-a-menu
	 *    PHP class    AAE_A_<PascalSlug>         e.g.  AAE_A_Menu
	 *    namespace    WCF_ADDONS\AtomicWidgets\Widgets\<PascalName>
	 *    dashboard    'aae-a-<slug>'             (key in both arrays below)
	 *    JS handle    'aae-a-<slug>-js'
	 *    CSS handle   'aae-a-<slug>-css'
	 *
	 * ---------------------------------------------------------------------
	 *  COMMON MISTAKES
	 *
	 *  - Toggle visible but widget not in Elementor panel ........... missing entry in get_available_widgets()
	 *  - Widget not in dashboard at all ............................. missing entry in register_widget_definitions()
	 *  - "Prop 'foo' not defined in schema" ......................... bind_to('foo') without matching key in define_props_schema()
	 *  - Twig renders blank in editor ............................... get_templates() key must be 'elementor/elements/aae-a-<slug>' (NO 'e-' prefix on key)
	 *  - JS doesn't fire on frontend ................................ register() elementType in source JS must equal get_element_type() ('e-aae-a-<slug>')
	 *  - CSS missing on frontend .................................... style_handle / style_path not set OR build pipeline didn't emit /assets/atomic/css/<slug>.css
	 *
	 * =================================================================== */

	/**
	 * Register all available atomic widget definitions.
	 *
	 * This array drives the AAE dashboard toggle UI. Each entry here is
	 * rendered as a card with an on/off switch — toggling on then calls
	 * Elementor's register_widgets() through is_widget_active().
	 *
	 * To add a new widget, append an entry here AND in get_available_widgets().
	 * See the "HOW TO ADD A NEW ATOMIC WIDGET" comment block above.
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
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider',
				'keywords'     => ['atomic', 'slider', 'carousel'],
				'icon'         => 'eicon-slider-push',
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
		add_action('elementor/atomic-widgets/frontend/loader/scripts/register', [$this, 'register_atomic_scripts'], 16);
		add_action('elementor/frontend/before_render', [$this, 'maybe_enqueue_widget_script'], 10, 1);
		add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_widget_scripts_in_preview']);
		add_action('elementor/atomic-widgets/styles/register', [$this, 'register_atomic_styles'], 10, 2);
		add_action('elementor/editor/before_enqueue_scripts', [$this, 'register_atomic_styles']);
		add_action('elementor/preview/enqueue_styles', [$this, 'enqueue_atomic_preview_styles']);
		add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_atomic_preview_scripts']);
		add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_atomic_editor_scripts']);

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
				'script_handle' => 'aae-a-slider-js',
				'script_path' => '/assets/atomic/js/nestedslider.js',
				'style_handle' => 'aae-a-slider-css',
				'style_path' => '/assets/atomic/css/nestedslider.css',
				'has_script' => true,
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

			'aae-a-progressbar' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar',
				'file'          => 'Widgets/Progressbar/class-aae-a-progressbar.php',
				'script_handle' => 'aae-a-progressbar-js',
				'script_path'   => '/assets/atomic/js/progressbar.js',
				'script_deps'   => ['progressbar'],
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

		foreach ( $this->get_available_widgets() as $widget_id => $widget_data ) {
			if ( $this->is_widget_active( $widget_id ) && ! empty( $widget_data['style_handle'] ) ) {
				wp_enqueue_style( $widget_data['style_handle'] );
			}
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
	}
}

// Initialize.
Atomic::instance();
