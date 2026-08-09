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
	 * Slugs that have ever been PRESENTED in the Atomic Extensions dashboard.
	 *
	 * The settings option only records what is enabled, so on its own it cannot
	 * distinguish "shipped after this site was set up" from "the user switched it
	 * off". That ambiguity is why the old default-seeder kept re-enabling things
	 * people had disabled. Tracking what has been offered separates the two.
	 */
	const EXTENSIONS_OFFERED_OPTION_NAME = 'aae_atomic_extensions_offered';

	/**
	 * Plugin version the newly-offered-extensions migration last completed for.
	 *
	 * Its two sibling migrations (V3_ADMIN_BACKFILL_OPTION_NAME,
	 * FORCED_BACKFILL_OPTION_NAME) have always had a marker; this one did not,
	 * which is why it reached update_option() on EVERY request, front end
	 * included. The version — rather than a plain boolean — is what still lets a
	 * plugin update re-run it when the registry gains an extension.
	 */
	const OFFERED_MIGRATION_OPTION_NAME = 'aae_atomic_offered_migration';

	/**
	 * Marker for the one-time copy of the v3 admin-feature toggles into the
	 * atomic extension option. See backfill_v3_admin_extensions().
	 */
	const V3_ADMIN_BACKFILL_OPTION_NAME = 'aae_atomic_v3_admin_backfill';

	/**
	 * Admin-feature extensions that exist in BOTH dashboards.
	 *
	 * Slugs are identical on the two sides (config.php's `general-extensions`
	 * keys and this class's extensions_registry), which is what makes the
	 * backfill a straight lookup. Every entry must also be present in
	 * register_extension_definitions() or its card can never be shown, and in
	 * class-plugin.php's loading gates or the toggle does nothing.
	 */
	const V3_ADMIN_EXTENSIONS = [
		'custom-fonts',
		'custom-cpt',
		'custom-icon',
		'code-snippet',
	];

	/**
	 * Widgets deliberately present in the class registry but withheld from the
	 * dashboard, so assert_registry_integrity() does not report them as drift.
	 *
	 * Currently empty: 'aae-a-menu' was the only entry and has now shipped with
	 * its own dashboard metadata in register_widget_definitions(). Keep the
	 * constant — it is the documented way to park a widget whose class exists
	 * before its dashboard card is ready, and assert_registry_integrity() reads
	 * it unconditionally.
	 */
	private const PARKED_WIDGETS = [];

	/**
	 * Extensions that shipped before EXTENSIONS_OFFERED_OPTION_NAME existed.
	 *
	 * Used once, on sites that predate the marker: any of these missing from the
	 * saved option was deliberately turned off and must stay off. Anything NOT in
	 * this list is genuinely new and gets switched on once.
	 */
	const LEGACY_OFFERED_EXTENSIONS = [
		'regular-animation',
		'parallax',
		'text-animation',
		'image-animation',
		'image-hover',
		'sticky',
		'horizontal-scroll-anim',
		'cursor-hover-effect',
		'mouse-move-effect',
		'advance-tooltip',
		'tilt',
		'scroll-to',
		'custom-css',
	];

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
	 *   'is_internal' => bool     Optional. True hides this entry from the
	 *                             dashboard widget list entirely — for
	 *                             sub-elements of a composite widget (e.g. a
	 *                             Flip Box's Front/Back/Title/Text) that
	 *                             should never be individually toggled.
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
	 * Memoised return of build_available_widgets().
	 *
	 * Null means "not cached yet" — see get_available_widgets() for why this is
	 * not simply filled on first call.
	 *
	 * @var array<string,array>|null
	 */
	private $available_widgets = null;

	/**
	 * Signature of the `aae/atomic/available_widgets` callback set at the moment
	 * $available_widgets was cached. A change means someone hooked (or unhooked)
	 * the filter after we cached, so the cache is stale.
	 *
	 * @var string|null
	 */
	private $available_widgets_signature = null;

	/**
	 * Memoised output of resolve_registerable_classes().
	 *
	 * Depends on the registry AND on which slugs are active, so it is cleared
	 * wherever $active_widgets is cleared.
	 *
	 * @var array{widgets: array<string,string>, elements: array<string,string>}|null
	 */
	private $registerable_classes = null;

	/**
	 * Memoised is_widget_active() answers, keyed by slug.
	 *
	 * Derived from the saved option, so it is cleared wherever $active_widgets is.
	 *
	 * @var array<string,bool>
	 */
	private $widget_active_cache = [];

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
		/**
		 * Dashboard metadata (card, category, PRO badge, keywords) for every
		 * atomic widget — including Pro-owned ones, which have no registry of
		 * their own to live in.
		 *
		 * Filtered on READ, not where the array is built: the array is built in
		 * this class's constructor, which runs on `plugins_loaded` when free
		 * includes its files, and Pro registers its modules on `elementor/init`.
		 * Filtering at build time would mean Pro's cards silently never appeared.
		 *
		 * @param array<string,array> $registry
		 */
		// Memoised on the same terms as get_available_widgets(): never cached
		// before `init` (Pro adds its cards filter at plugins_loaded 11, and
		// caching earlier would drop every Pro dashboard card), and invalidated
		// when the callback set changes. Pro's callback runs ~60 esc_html__()
		// lookups, and assert_registry_integrity() alone asked for this twice.
		static $cache = null;
		static $signature = null;

		$current = $this->filter_signature('aae/atomic/widgets_registry');

		if (null !== $cache && $current === $signature) {
			return $cache;
		}

		$registry = (array) apply_filters('aae/atomic/widgets_registry', $this->widgets_registry);

		if (did_action('init')) {
			$cache = $registry;
			$signature = $current;
		}

		return $registry;
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
	/**
	 * Widgets that must register regardless of the saved option, because
	 * nothing in the dashboard can ever switch them on.
	 *
	 * Only for slugs with NO widgets_registry entry: 'aae-a-counter-number'
	 * is a structural child of Counter that is not listed as its own card and
	 * is not routed through WIDGET_PARENT_MAP, so without this it would never
	 * be active and Counter would render incomplete.
	 *
	 * Post Title / Post Image used to sit here too, which contradicted this
	 * constant's own purpose: they DO have dashboard cards, so force-active
	 * made their toggle inert, and — because get_dashboard_config() reports
	 * is_active from the raw saved option rather than is_widget_active() — a
	 * card could read "off" while the widget was in fact registering. They now
	 * follow their own toggle like every other carded widget; see
	 * backfill_formerly_forced_widgets() for the one-time upgrade path.
	 */
	private const ALWAYS_ACTIVE_WIDGETS = [
		'aae-a-counter-number',
	];

	/**
	 * Widgets that were previously in ALWAYS_ACTIVE_WIDGETS and so registered
	 * unconditionally. Used once to preserve that state on upgrade.
	 */
	private const FORMERLY_FORCED_WIDGETS = [
		'aae-a-post-title',
		'aae-a-post-image',
	];

	/**
	 * Marker recording that the FORMERLY_FORCED_WIDGETS backfill has run, so a
	 * later deliberate switch-off is never undone on the next page load.
	 */
	const FORCED_BACKFILL_OPTION_NAME = 'aae_atomic_widgets_forced_backfill';

	/**
	 * Maps every purely-internal child widget (`is_internal => true` in
	 * widgets_registry — never shown/toggleable in the dashboard on its
	 * own) to the composite parent widget it structurally belongs to.
	 * Verified against each parent's own `define_default_children()`.
	 *
	 * is_widget_active() consults this so disabling the parent from the
	 * dashboard also disables — and hides from the Elementor editor —
	 * every one of its internal children, instead of them always being
	 * force-active regardless of the parent's state.
	 */
	private const WIDGET_PARENT_MAP = [
		// Nested Slider
		'aae-a-slide'                  => 'aae-a-slider',
		'aae-a-slider-track'           => 'aae-a-slider',
		'aae-a-slider-nav-prev'        => 'aae-a-slider',
		'aae-a-slider-nav-next'        => 'aae-a-slider',
		'aae-a-slider-pagination'      => 'aae-a-slider',
		'aae-a-slider-dot'             => 'aae-a-slider',
		'aae-a-slider-indicators'      => 'aae-a-slider',
		'aae-a-slider-current'         => 'aae-a-slider',
		'aae-a-slider-total'           => 'aae-a-slider',
		'aae-a-slider-percentage'      => 'aae-a-slider',
		'aae-a-slider-progress'        => 'aae-a-slider',
		'aae-a-slider-counter'         => 'aae-a-slider',
		'aae-a-slider-divider'         => 'aae-a-slider',
		'aae-a-slider-progress-fill'   => 'aae-a-slider',

		// Accordion / Icon List / Countdown
		'aae-a-accordion-item'         => 'aae-a-accordion',
		'aae-a-icon-list-item'         => 'aae-a-icon-list',
		'aae-a-countdown-unit'         => 'aae-a-countdown',

		// Toggle Switcher — two independent parents
		'aae-a-toggle-pane'            => 'aae-a-toggle-switcher',
		'aae-a-toggle-switcher-tabs'   => 'aae-a-toggle-switcher',
		'aae-a-toggle-switcher-tab'    => 'aae-a-toggle-switcher',
		'aae-a-toggle-pane-title'      => 'aae-a-toggle-switcher',
		'aae-a-toggle-pane-desc'       => 'aae-a-toggle-switcher',

		// Video Mask / Flip Box
		'aae-a-video-mask-btn'         => 'aae-a-video-mask',
		'aae-a-flip-box-front'         => 'aae-a-flip-box',
		'aae-a-flip-box-back'          => 'aae-a-flip-box',
		'aae-a-flip-box-title'         => 'aae-a-flip-box',
		'aae-a-flip-box-text'          => 'aae-a-flip-box',

		// Posts / Loop Grid / Loop Grid Slider
		'aae-a-post-card'              => 'aae-a-posts',
		'aae-a-loop-item'              => 'aae-a-loop-grid',
		'aae-a-loop-layout'            => 'aae-a-loop-grid',
		'aae-a-loop-pagination'        => 'aae-a-loop-grid',
		'aae-a-loop-prev'              => 'aae-a-loop-grid',
		'aae-a-loop-next'              => 'aae-a-loop-grid',
		'aae-a-loop-numbers'           => 'aae-a-loop-grid',
		'aae-a-loop-number'            => 'aae-a-loop-grid',
		'aae-a-loop-loadmore'          => 'aae-a-loop-grid',
		'aae-a-loop-arrow'             => 'aae-a-loop-grid',
		'aae-a-loop-nav-wrap'          => 'aae-a-loop-grid',
		'aae-a-loop-slide-track'       => 'aae-a-loop-grid-slider',
		'aae-a-loop-slide-item'        => 'aae-a-loop-grid-slider',
		'aae-a-loop-slide-pagination'  => 'aae-a-loop-grid-slider',

		// Offcanvas
		'aae-a-offcanvas-panel'        => 'aae-a-offcanvas',
		'aae-a-offcanvas-trigger'      => 'aae-a-offcanvas',
		'aae-a-offcanvas-close'        => 'aae-a-offcanvas',
		'aae-a-offcanvas-overlay'      => 'aae-a-offcanvas',

		// Image Hotspot
		'aae-a-hotspot-point'          => 'aae-a-image-hotspot',
		'aae-a-hotspot-marker'         => 'aae-a-image-hotspot',
		'aae-a-hotspot-content'        => 'aae-a-image-hotspot',
		'aae-a-hotspot-close'          => 'aae-a-image-hotspot',
		'aae-a-hotspot-lightbox'       => 'aae-a-image-hotspot',

		// Post Pagination
		'aae-a-post-pagination-prev'              => 'aae-a-post-pagination',
		'aae-a-post-pagination-next'              => 'aae-a-post-pagination',
		'aae-a-post-pagination-preview'           => 'aae-a-post-pagination',
		'aae-a-post-pagination-preview-image'     => 'aae-a-post-pagination',
		'aae-a-post-pagination-preview-category'  => 'aae-a-post-pagination',
		'aae-a-post-pagination-preview-title'     => 'aae-a-post-pagination',
		'aae-a-post-pagination-preview-date'      => 'aae-a-post-pagination',
		'aae-a-post-pagination-preview-author'    => 'aae-a-post-pagination',
		'aae-a-post-pagination-preview-excerpt'   => 'aae-a-post-pagination',

		// Stack Cards
		'aae-a-stack-card'             => 'aae-a-stack-cards',

		// Timeline
		'aae-a-timeline-item'          => 'aae-a-timeline',
		'aae-a-timeline-number'        => 'aae-a-timeline',
		'aae-a-timeline-year'          => 'aae-a-timeline',
		'aae-a-timeline-title'         => 'aae-a-timeline',
		'aae-a-timeline-desc'          => 'aae-a-timeline',

		// Progress Bar Template (first parent — Progress Bar Main is separate)
		'aae-a-progressbar-track'      => 'aae-a-progressbar',
		'aae-a-progressbar-fill'       => 'aae-a-progressbar',
		'aae-a-progressbar-label'      => 'aae-a-progressbar',
		'aae-a-progressbar-dot'        => 'aae-a-progressbar',

		// Social Share
		'aae-a-social-share-item'      => 'aae-a-social-share',

		// Nav
		'aae-a-nav-item'               => 'aae-a-nav',
		'aae-a-nav-sub-item'           => 'aae-a-nav',
		'aae-a-mobile-nav'             => 'aae-a-nav',

		// Search Form
		'aae-a-search-toggle'          => 'aae-a-search-form',
		'aae-a-search-panel'           => 'aae-a-search-form',
		'aae-a-search-field'           => 'aae-a-search-form',
		'aae-a-search-input'           => 'aae-a-search-form',
		'aae-a-search-filter-date'     => 'aae-a-search-form',
		'aae-a-search-filter-category' => 'aae-a-search-form',
		'aae-a-search-submit'          => 'aae-a-search-form',
		'aae-a-search-results'         => 'aae-a-search-form',

		// Form
		'aae-a-form-label'             => 'aae-a-form',
		'aae-a-form-input'             => 'aae-a-form',
		'aae-a-form-textarea'          => 'aae-a-form',
		'aae-a-form-checkbox'          => 'aae-a-form',
		'aae-a-form-radio'             => 'aae-a-form',
		'aae-a-form-select'            => 'aae-a-form',
		'aae-a-form-submit'            => 'aae-a-form',
		'aae-a-form-success-message'   => 'aae-a-form',
		'aae-a-form-error-message'     => 'aae-a-form',
		'aae-a-form-field-error'       => 'aae-a-form',
		'aae-a-form-file'              => 'aae-a-form',
		'aae-a-form-step'              => 'aae-a-form',
		'aae-a-form-next'              => 'aae-a-form',
		'aae-a-form-prev'              => 'aae-a-form',
		'aae-a-form-rating'            => 'aae-a-form',
		'aae-a-form-range'             => 'aae-a-form',
		'aae-a-form-password'          => 'aae-a-form',
		'aae-a-form-calculation'       => 'aae-a-form',
		'aae-a-form-country'           => 'aae-a-form',
	];

	/**
	 * The two membership lists above, as filtered values.
	 *
	 * Atomic element TYPES can only be registered from this plugin — Elementor
	 * has no registry a second plugin can add to — so a Pro-owned atomic widget
	 * still has to travel through these lists to be gated, activated and
	 * inherited correctly. These accessors are the seam it comes in through;
	 * read them instead of the constants, or a Pro widget's internal children
	 * silently stop inheriting their parent's active state.
	 *
	 * @return string[]
	 */
	private function always_active_widgets(): array
	{
		// Memoised: is_widget_active() asks for this once per slug, for every
		// registration and asset loop, and each call re-ran the filter chain.
		// Pro hooks it at plugins_loaded 11; the signature guard catches anyone
		// hooking or unhooking later.
		static $cache = null;
		static $signature = null;

		$current = $this->filter_signature('aae/atomic/always_active_widgets');

		if (null === $cache || $current !== $signature) {
			$cache = (array) apply_filters('aae/atomic/always_active_widgets', self::ALWAYS_ACTIVE_WIDGETS);
			$signature = $current;
		}

		return $cache;
	}

	/**
	 * always_active_widgets() as a slug => true lookup.
	 *
	 * is_widget_active() used in_array() over the list, i.e. a linear scan per
	 * slug on every registration and asset loop. This must stay SEPARATE from
	 * always_active_widgets(): assert_registry_integrity() passes that method's
	 * return straight into array_diff(), which compares VALUES — handing it a
	 * flipped map would silently stop excluding always-active slugs and report
	 * phantom orphan children.
	 *
	 * @return array<string,int>
	 */
	private function always_active_lookup(): array
	{
		static $cache = null;
		static $signature = null;

		$current = $this->filter_signature('aae/atomic/always_active_widgets');

		if (null === $cache || $current !== $signature) {
			$cache = array_flip($this->always_active_widgets());
			$signature = $current;
		}

		return $cache;
	}

	/** @return array<string,string> child slug => parent slug */
	private function widget_parent_map(): array
	{
		static $cache = null;
		static $signature = null;

		$current = $this->filter_signature('aae/atomic/widget_parent_map');

		if (null === $cache || $current !== $signature) {
			$cache = (array) apply_filters('aae/atomic/widget_parent_map', self::WIDGET_PARENT_MAP);
			$signature = $current;
		}

		return $cache;
	}

	public function is_widget_active(string $slug): bool
	{
		// Asked for every slug of every registration and asset loop, and it
		// recurses through the parent map on top of that. The answer only moves
		// when the saved option does, so this is cleared alongside
		// $active_widgets.
		if (isset($this->widget_active_cache[$slug])) {
			return $this->widget_active_cache[$slug];
		}

		// Hash lookup rather than the previous in_array() scan over the list.
		if (isset($this->always_active_lookup()[$slug])) {
			return $this->widget_active_cache[$slug] = true;
		}

		// Internal child widgets inherit their parent's active state, so
		// disabling the parent also disables (and hides from the editor)
		// every one of its children.
		$parents = $this->widget_parent_map();
		if (isset($parents[$slug])) {
			return $this->widget_active_cache[$slug] = $this->is_widget_active($parents[$slug]);
		}

		$saved = $this->get_saved_options();

		return $this->widget_active_cache[$slug] = isset($saved[$slug]);
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
	/**
	 * Group every internal child under the widget it belongs to.
	 *
	 * WIDGET_PARENT_MAP already records the relationship — this inverts it into
	 * the shape the dashboard renders: parent slug => list of its parts, each
	 * with the label/icon needed to display it. Children are read-only here;
	 * they have no toggle of their own and follow the parent's state (see
	 * is_widget_active()).
	 *
	 * Chains are followed to the top so a grandchild is listed under the
	 * widget the user can actually switch off, not under an intermediate part
	 * that never appears in the dashboard.
	 *
	 * @return array<string, array<int, array{slug:string,label:string,icon:string}>>
	 */
	private function get_widget_parts(): array
	{
		$parts = [];

		$parent_map = $this->widget_parent_map();

		foreach ($parent_map as $child => $parent) {
			// Walk up to the toggleable ancestor. Guarded against a cycle so a
			// bad map entry can never hang the dashboard request.
			$seen = [];
			while (isset($parent_map[$parent]) && ! isset($seen[$parent])) {
				$seen[$parent] = true;
				$parent        = $parent_map[$parent];
			}

			$def = $this->get_widgets_registry()[$child] ?? null;

			if (null === $def) {
				continue;
			}

			$parent_label = $this->get_widgets_registry()[$parent]['label'] ?? '';

			$parts[$parent][] = [
				'slug'  => $child,
				'label' => $this->part_label($def['label'] ?? $child, $parent_label),
				'icon'  => $def['icon'] ?? '',
			];
		}

		foreach ($parts as &$list) {
			usort($list, static function ($a, $b) {
				return strcasecmp($a['label'], $b['label']);
			});
		}
		unset($list);

		return $parts;
	}

	/**
	 * Tidy an internal widget's label for display inside its parent's group.
	 *
	 * These labels were never user-facing before — they exist so developers can
	 * identify a slug — so they carry bookkeeping the dashboard should not show:
	 * an "(Internal)" marker, and the parent's own name repeated as a prefix
	 * ("Flip Box Back", "Countdown — Unit"). The group is already titled with the
	 * parent, so the prefix is noise. Falls back to the original whenever
	 * stripping would leave nothing.
	 *
	 * @param string $label        Raw registry label.
	 * @param string $parent_label Label of the widget this part belongs to.
	 *
	 * @return string
	 */
	private function part_label(string $label, string $parent_label): string
	{
		$clean = trim(preg_replace('/\s*\((?:internal)\)\s*$/i', '', $label));

		if ('' !== $parent_label) {
			// "Countdown — Unit" / "Flip Box Back" -> "Unit" / "Back".
			$pattern = '/^' . preg_quote($parent_label, '/') . '\s*(?:—|-|–|:)?\s+/i';
			$trimmed = trim(preg_replace($pattern, '', $clean));

			if ('' !== $trimmed) {
				$clean = $trimmed;
			}
		}

		return '' !== $clean ? $clean : $label;
	}

	public function get_dashboard_config(): array
	{
		$saved   = $this->get_saved_options();
		$widgets = [];
		$parts   = $this->get_widget_parts();

		foreach ($this->get_widgets_registry() as $slug => $def) {
			// Sub-elements of a composite widget (e.g. Flip Box's own
			// Front/Back/Title/Text) are never individually toggleable —
			// keep them out of the dashboard list entirely. They are not
			// dropped from the payload though: each one is attached to its
			// parent below as a read-only `parts` entry, so the dashboard can
			// show what a widget contains without offering a switch for it.
			if (! empty($def['is_internal'])) {
				continue;
			}

			$widgets[$slug] = array_merge($def, [
				'is_active'   => isset($saved[$slug]),
				'parts'       => $parts[$slug] ?? [],
				'parts_count' => isset($parts[$slug]) ? count($parts[$slug]) : 0,
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

			/*
			 * Menu renders an EXISTING WordPress menu (wp_get_nav_menus() picker +
			 * wp_nav_menu()) styled through flat props. It is not a duplicate of
			 * aae-a-nav, which builds its items as separate atomic elements
			 * (nav-item / nav-sub-item / mobile-nav) that are individually
			 * styleable — so it gets its own toggle rather than being routed
			 * through WIDGET_PARENT_MAP under Nav.
			 *
			 * Was commented out (and listed in PARKED_WIDGETS) while incomplete;
			 * class, twig, css and js have all been present for a while, and with
			 * no metadata here is_widget_active() could never return true, so
			 * register_widgets() skipped it permanently.
			 */
			'aae-a-menu' => [
				'label'        => 'WP Menu',
				'description'  => 'Renders an existing WordPress menu, styled natively in Elementor V4.',
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
				// Sits with Nav and Site Logo — it is the second of the two
				// nav/menu widgets, not a general-purpose element.
				'category'     => 'header-footer',
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
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-search-query' => [
				'label'        => 'Search Query',
				'description'  => 'Displays the current search-results heading (query + active date/category filters).',
				'icon'         => 'eicon-search',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'search',
					'query',
					'results',
					'atomic',
					'dynamic',
				],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-post-content' => [
				'label'        => 'Post Content',
				'description'  => 'Dynamically displays the current post content natively in Elementor V4.',
				'icon'         => 'eicon-post-content',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'post',
					'content',
					'atomic',
					'dynamic',
				],
				'category'     => 'blog',
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
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-loop-item' => [
				'is_internal'  => true,
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
				'is_internal'  => true,
				'label'        => 'Loop Layout',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Layout',
				'icon'         => 'eicon-loop-builder',
				'keywords'     => [ 'loop', 'layout' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-pagination' => [
				'is_internal'  => true,
				'label'        => 'Loop Pagination',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Pagination',
				'icon'         => 'eicon-ellipsis-h',
				'keywords'     => [ 'loop', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-prev' => [
				'is_internal'  => true,
				'label'        => 'Loop Previous',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Prev',
				'icon'         => 'eicon-chevron-left',
				'keywords'     => [ 'loop', 'prev', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-next' => [
				'is_internal'  => true,
				'label'        => 'Loop Next',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Next',
				'icon'         => 'eicon-chevron-right',
				'keywords'     => [ 'loop', 'next', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-numbers' => [
				'is_internal'  => true,
				'label'        => 'Loop Page Numbers',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Numbers',
				'icon'         => 'eicon-number-field',
				'keywords'     => [ 'loop', 'numbers', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-number' => [
				'is_internal'  => true,
				'label'        => 'Loop Page Number',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Number',
				'icon'         => 'eicon-number-field',
				'keywords'     => [ 'loop', 'number', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-loadmore' => [
				'is_internal'  => true,
				'label'        => 'Loop Load More',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_LoadMore',
				'icon'         => 'eicon-plus-circle',
				'keywords'     => [ 'loop', 'load more', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-arrow' => [
				'is_internal'  => true,
				'label'        => 'Loop Arrow',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Arrow',
				'icon'         => 'eicon-chevron-right',
				'keywords'     => [ 'loop', 'arrow', 'pagination' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-nav-wrap' => [
				'is_internal'  => true,
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
				'category'     => 'form',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],
			'aae-a-search-toggle' => [
				'is_internal'  => true,
				'label'        => 'Search Toggle',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Toggle',
				'icon'         => 'eicon-search',
				'keywords'     => [ 'search', 'toggle' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-panel' => [
				'is_internal'  => true,
				'label'        => 'Search Panel',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Panel',
				'icon'         => 'eicon-container',
				'keywords'     => [ 'search', 'panel' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-field' => [
				'is_internal'  => true,
				'label'        => 'Search Field',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Field',
				'icon'         => 'eicon-form-horizontal',
				'keywords'     => [ 'search', 'field', 'form' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-input' => [
				'is_internal'  => true,
				'label'        => 'Search Input',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Input',
				'icon'         => 'eicon-form-horizontal',
				'keywords'     => [ 'search', 'input' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-filter-date' => [
				'is_internal'  => true,
				'label'        => 'Date Filter',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Filter_Date',
				'icon'         => 'eicon-calendar',
				'keywords'     => [ 'search', 'filter', 'date' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-filter-category' => [
				'is_internal'  => true,
				'label'        => 'Category Filter',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Filter_Category',
				'icon'         => 'eicon-folder',
				'keywords'     => [ 'search', 'filter', 'category' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-submit' => [
				'is_internal'  => true,
				'label'        => 'Search Submit',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Submit',
				'icon'         => 'eicon-button',
				'keywords'     => [ 'search', 'submit', 'button' ],
				'hide_from_panel' => true,
			],
			'aae-a-search-results' => [
				'is_internal'  => true,
				'label'        => 'Search Results',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Results',
				'icon'         => 'eicon-post-list',
				'keywords'     => [ 'search', 'results', 'ajax' ],
				'hide_from_panel' => true,
			],

			'aae-a-draw-svg' => [
				'label'        => 'DrawSVG',
				'description'  => 'Draw an SVG\'s paths with GSAP DrawSVGPlugin — per-path, optional ScrollTrigger, from/to/method/ease/duration/yoyo/scrub and an optional wrapper link.',
				'icon'         => 'eicon-animation',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\DrawSvg\AAE_A_Draw_Svg',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'draw', 'svg', 'gsap', 'animation', 'scroll', 'atomic' ],
				'category'     => 'animation',
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
				'category'     => 'dynamic',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-post-card' => [
				'is_internal'  => true,
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
				'category'     => 'dynamic',
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
				'category'     => 'slider',
				'order'        => 1,
				'demo_url'     => '',
				'doc_url'      => '',
			],
			'aae-a-loop-slide-track' => [
				'is_internal'  => true,
				'label'        => 'Slider Track',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Track',
				'icon'         => 'eicon-slider-push',
				'keywords'     => [ 'loop', 'slider', 'track' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-slide-item' => [
				'is_internal'  => true,
				'label'        => 'Loop Slide Item',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Item',
				'icon'         => 'eicon-container',
				'keywords'     => [ 'loop', 'slide', 'item' ],
				'hide_from_panel' => true,
			],
			'aae-a-loop-slide-pagination' => [
				'is_internal'  => true,
				'label'        => 'Slider Pagination',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\LoopGridSlider\AAE_A_Loop_Slide_Pagination',
				'icon'         => 'eicon-ellipsis-h',
				'keywords'     => [ 'loop', 'slider', 'pagination' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination' => [
				'label'        => 'Post Pagination',
				'description'  => 'Prev/Next single-post navigation — taxonomy-constrained, orderable (date/title/menu-order/custom-field), loop-around, sticky-bar/side-arrow display modes, keyboard/swipe/prefetch, and a customizable Hover Preview Card. Works on WooCommerce single Product pages too.',
				'icon'         => 'eicon-post-navigation',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'post',
					'nav',
					'navigation',
					'prev',
					'next',
					'pagination',
					'dynamic',
				],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-post-pagination-prev' => [
				'is_internal'  => true,
				'label'        => 'Previous Post',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Prev',
				'icon'         => 'eicon-chevron-left',
				'keywords'     => [ 'post', 'nav', 'prev' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination-next' => [
				'is_internal'  => true,
				'label'        => 'Next Post',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Next',
				'icon'         => 'eicon-chevron-right',
				'keywords'     => [ 'post', 'nav', 'next' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination-preview' => [
				'is_internal'  => true,
				'label'        => 'Hover Preview Card',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview',
				'icon'         => 'eicon-post-navigation',
				'keywords'     => [ 'post', 'preview', 'hover', 'card' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination-preview-image' => [
				'is_internal'  => true,
				'label'        => 'Thumbnail',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Image',
				'icon'         => 'eicon-image',
				'keywords'     => [ 'post', 'pagination', 'preview', 'thumbnail', 'image' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination-preview-category' => [
				'is_internal'  => true,
				'label'        => 'Category',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Category',
				'icon'         => 'eicon-tags',
				'keywords'     => [ 'post', 'pagination', 'preview', 'category' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination-preview-title' => [
				'is_internal'  => true,
				'label'        => 'Title',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Title',
				'icon'         => 'eicon-t-letter',
				'keywords'     => [ 'post', 'pagination', 'preview', 'title' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination-preview-date' => [
				'is_internal'  => true,
				'label'        => 'Date',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Date',
				'icon'         => 'eicon-calendar',
				'keywords'     => [ 'post', 'pagination', 'preview', 'date' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination-preview-author' => [
				'is_internal'  => true,
				'label'        => 'Author',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Author',
				'icon'         => 'eicon-user-circle-o',
				'keywords'     => [ 'post', 'pagination', 'preview', 'author' ],
				'hide_from_panel' => true,
			],

			'aae-a-post-pagination-preview-excerpt' => [
				'is_internal'  => true,
				'label'        => 'Excerpt',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Excerpt',
				'icon'         => 'eicon-text-align-left',
				'keywords'     => [ 'post', 'pagination', 'preview', 'excerpt' ],
				'hide_from_panel' => true,
			],

			/*
			 * AAE Post Comments family — DISABLED 2026-07-27: the senior dev
			 * is building the comments/reply-form feature himself. Kept here
			 * (not deleted) for reference; root class renamed to
			 * AAE_A_Comments_Ny / e-aae-a-comments-ny so it can't collide
			 * with whatever he ends up calling his own version. Uncomment
			 * this whole block (and the matching block in
			 * get_available_widgets(), and the internal-widgets list in
			 * is_widget_active()) to re-enable.
			 *
			'aae-a-comments-ny' => [
				'label'        => 'Post Comments',
				'description'  => 'Query the current post\'s comments and repeat a custom comment-item template per comment (avatar/author/date/content/reply-link, freely mixed with Paragraph/Image/Button/Heading), plus a native reply form.',
				'icon'         => 'eicon-comments',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'comment',
					'comments',
					'discussion',
					'reply',
					'template',
					'dynamic',
				],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-comment-list' => [
				'is_internal'  => true,
				'label'        => 'Comment List',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_List',
				'icon'         => 'eicon-comments',
				'keywords'     => [ 'comment', 'list' ],
				'hide_from_panel' => true,
			],

			'aae-a-comment-item' => [
				'is_internal'  => true,
				'label'        => 'Comment Item',
				'description'  => 'Container widget for Post Comments items with default flex column layout.',
				'icon'         => 'eicon-container',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Item',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'comment',
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

			'aae-a-comment-avatar' => [
				'label'        => 'AAE Comment Avatar',
				'description'  => 'The current comment\'s avatar — resolves per comment inside a Post Comments item.',
				'icon'         => 'eicon-person',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'comment', 'avatar', 'gravatar', 'atomic', 'dynamic' ],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-comment-author' => [
				'label'        => 'AAE Comment Author',
				'description'  => 'The current comment\'s author name — resolves per comment inside a Post Comments item.',
				'icon'         => 'eicon-t-letter',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'comment', 'author', 'name', 'atomic', 'dynamic' ],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-comment-date' => [
				'label'        => 'AAE Comment Date',
				'description'  => 'The current comment\'s date/time — resolves per comment inside a Post Comments item.',
				'icon'         => 'eicon-calendar',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'comment', 'date', 'time', 'atomic', 'dynamic' ],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-comment-content' => [
				'label'        => 'AAE Comment Content',
				'description'  => 'The current comment\'s text — resolves per comment inside a Post Comments item.',
				'icon'         => 'eicon-text-align-left',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'comment', 'content', 'text', 'atomic', 'dynamic' ],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-comment-reply-link' => [
				'label'        => 'AAE Comment Reply Link',
				'description'  => 'The current comment\'s reply link — resolves per comment inside a Post Comments item.',
				'icon'         => 'eicon-reply',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'comment', 'reply', 'link', 'atomic', 'dynamic' ],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-comment-form' => [
				'label'        => 'AAE Comment Form',
				'description'  => 'A restyleable wrapper around WordPress\'s native comment_form() — real submissions, nonces and comment-reply threading included.',
				'icon'         => 'eicon-form-horizontal',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'comment', 'form', 'reply', 'submit', 'atomic', 'dynamic' ],
				'category'     => 'blog',
				'order'        => 0,
				'demo_url'     => '',
				'doc_url'      => '',
			],
			*/

			'aae-a-counter' => [
				'label'        => 'Counter',
				'description'  => 'An animated number counter that counts up on scroll, with a minimal CSS footprint.',
				'icon'         => 'eicon-counter',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'description'  => 'Heading with rich inline text editing — bold, italic, underline, strikethrough, super/subscript and links, on any tag from h1 to span.',
				'icon'         => 'eicon-t-letter',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'heading',
					'title',
					'highlight',
					'html',
					'span',
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
				'category'     => 'slider',
				'order'        => 1,
				'demo_url'     => '',
				'doc_url'      => '',
			],
			'aae-a-slide' => [
				'is_internal'  => true,
				'label'        => 'Slide (Internal)',
				'description'  => 'Internal child container for Nested Slider.',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slide',
				'keywords'     => ['atomic', 'slide', 'internal'],
				'icon'         => 'eicon-slide',
				'hide_from_panel' => true,
			],
			'aae-a-slider-track' => [
				'is_internal'  => true,
				'label'        => 'Slider Track',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Track',
				'keywords'     => ['atomic', 'slider', 'track'],
				'icon'         => 'eicon-slider-push',
				'hide_from_panel' => true,
			],
			'aae-a-slider-nav-prev' => [
				'is_internal'  => true,
				'label'        => 'Slider Prev Nav',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Prev',
				'keywords'     => ['atomic', 'slider', 'navigator', 'prev'],
				'icon'         => 'eicon-chevron-left',
				'hide_from_panel' => true,
			],
			'aae-a-slider-nav-next' => [
				'is_internal'  => true,
				'label'        => 'Slider Next Nav',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Nav_Next',
				'keywords'     => ['atomic', 'slider', 'navigator', 'next'],
				'icon'         => 'eicon-chevron-right',
				'hide_from_panel' => true,
			],
			'aae-a-slider-pagination' => [
				'is_internal'  => true,
				'label'        => 'Slider Pagination',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Pagination',
				'keywords'     => ['atomic', 'slider', 'pagination', 'dots'],
				'icon'         => 'eicon-ellipsis-h',
				'hide_from_panel' => true,
			],

			// ── Nested Slider — remaining HELPER widgets. These were registered
			// as classes and routed through WIDGET_PARENT_MAP, but had no entry
			// here at all, so nothing could name them. `is_internal => true`
			// keeps them out of the dashboard list exactly like their siblings
			// above; the label/icon exist so the parent can list what it owns.
			'aae-a-slider-dot' => [
				'is_internal'  => true,
				'label'        => 'Slider Dot',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Dot',
				'keywords'     => ['atomic', 'slider', 'dot', 'bullet'],
				'icon'         => 'eicon-dot-circle-o',
				'hide_from_panel' => true,
			],

			'aae-a-slider-indicators' => [
				'is_internal'  => true,
				'label'        => 'Slider Indicators',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Indicators',
				'keywords'     => ['atomic', 'slider', 'indicators'],
				'icon'         => 'eicon-ellipsis-h',
				'hide_from_panel' => true,
			],

			'aae-a-slider-current' => [
				'is_internal'  => true,
				'label'        => 'Slider Current Index',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Current',
				'keywords'     => ['atomic', 'slider', 'current', 'index'],
				'icon'         => 'eicon-number-field',
				'hide_from_panel' => true,
			],

			'aae-a-slider-total' => [
				'is_internal'  => true,
				'label'        => 'Slider Total',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Total',
				'keywords'     => ['atomic', 'slider', 'total', 'count'],
				'icon'         => 'eicon-number-field',
				'hide_from_panel' => true,
			],

			'aae-a-slider-percentage' => [
				'is_internal'  => true,
				'label'        => 'Slider Percentage',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Percentage',
				'keywords'     => ['atomic', 'slider', 'percentage', 'progress'],
				'icon'         => 'eicon-number-field',
				'hide_from_panel' => true,
			],

			'aae-a-slider-progress' => [
				'is_internal'  => true,
				'label'        => 'Slider Progress',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Progress',
				'keywords'     => ['atomic', 'slider', 'progress', 'bar'],
				'icon'         => 'eicon-skill-bar',
				'hide_from_panel' => true,
			],

			'aae-a-slider-progress-fill' => [
				'is_internal'  => true,
				'label'        => 'Slider Progress Fill',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Progress_Fill',
				'keywords'     => ['atomic', 'slider', 'progress', 'fill'],
				'icon'         => 'eicon-skill-bar',
				'hide_from_panel' => true,
			],

			'aae-a-slider-counter' => [
				'is_internal'  => true,
				'label'        => 'Slider Counter',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Counter',
				'keywords'     => ['atomic', 'slider', 'counter'],
				'icon'         => 'eicon-counter',
				'hide_from_panel' => true,
			],

			'aae-a-slider-divider' => [
				'is_internal'  => true,
				'label'        => 'Slider Divider',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\NestedSlider\AAE_A_Slider_Divider',
				'keywords'     => ['atomic', 'slider', 'divider', 'separator'],
				'icon'         => 'eicon-divider',
				'hide_from_panel' => true,
			],

			'aae-a-stack-cards' => [
				'label'        => 'Stack Cards',
				'description'  => 'A scroll-driven card deck: independently-styleable cards that stack and animate with GSAP ScrollTrigger. First release ships the Scroll Stack animation; more arrive as presets.',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\StackCards\AAE_A_Stack_Cards',
				'keywords'     => ['atomic', 'stack', 'cards', 'scroll', 'gsap'],
				'icon'         => 'eicon-post-list',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'category'     => 'slider',
				'order'        => 1,
				'demo_url'     => '',
				'doc_url'      => '',
			],
			'aae-a-stack-card' => [
				'is_internal'  => true,
				'label'        => 'Stack Card (Internal)',
				'description'  => 'Internal card element for Stack Cards.',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\StackCards\AAE_A_Stack_Card',
				'keywords'     => ['atomic', 'stack', 'card', 'internal'],
				'icon'         => 'eicon-single-post',
				'hide_from_panel' => true,
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

			'aae-a-toc' => [
				'label'        => 'Table of Content',
				'description'  => 'Auto-generated Table of Contents from the page headings — nested hierarchy, active-heading highlighting, smooth scroll, collapsible + responsive minimize box.',
				'icon'         => 'eicon-table-of-contents',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'toc',
					'table',
					'content',
					'contents',
					'anchor',
					'heading',
					'atomic',
				],
				'category'     => 'blog',
				'order'        => 7,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-accordion-item' => [
				'is_internal'  => true,
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
				'is_internal'  => true,
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

			// ── Social Share — PARENT widget. This is the only Social Share
			// entry exposed in the dashboard widget list / editor panel.
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

			// ── Social Share — HELPER widget below. `is_internal => true`
			// hides it from the dashboard widget list; it is never
			// individually toggled, only used internally by Social Share
			// above (managed via its "Items" repeater control).
			'aae-a-social-share-item' => [
				'is_internal'  => true,
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
					'link',
				],
				'category'     => 'general',
				'order'        => 11,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			// 'aae-a-social-share-main' / '-main-item' removed: the whole
			// Widgets/SocialShareMain/ directory was deleted in 165a85e5, but
			// these entries were left behind. Class loading is file_exists()
			// guarded so they failed silently, while the script enqueue was
			// not — the editor 404'd on assets/atomic/js/social-share-main.js
			// on every load. Use 'aae-a-social-share' instead.

			'aae-a-image-compare' => [
				'label'        => 'Image Compare',
				'description'  => 'A draggable before/after image comparison slider — no manual setup needed, apply the ready-made Horizontal/Vertical presets and swap in your own images.',
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
					'template',
				],
				'category'     => 'general',
				'order'        => 9,
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
				'is_internal'  => true,
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

			// ── Timeline — PARENT widget. This is the only Timeline entry
			// exposed in the dashboard widget list / editor panel.
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

			// ── Timeline — HELPER widgets below. `is_internal => true` hides
			// each of these from the dashboard widget list; they are never
			// individually toggled, only used internally by Timeline above.
			'aae-a-timeline-item' => [
				'label'        => 'Timeline — Item',
				'description'  => 'Internal event-row sub-element used by Timeline (marker + date + title + description).',
				'icon'         => 'eicon-bullet-list',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
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

			'aae-a-timeline-number' => [
				'label'        => 'Timeline — Number',
				'description'  => 'Internal milestone-index label used by Timeline — Item.',
				'icon'         => 'eicon-number-field',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'timeline', 'number', 'atomic' ],
				'category'     => 'general',
				'order'        => 15,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-timeline-year' => [
				'label'        => 'Timeline — Year',
				'description'  => 'Internal milestone-date label used by Timeline — Item.',
				'icon'         => 'eicon-calendar',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'timeline', 'year', 'date', 'atomic' ],
				'category'     => 'general',
				'order'        => 16,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-timeline-title' => [
				'label'        => 'Timeline — Title',
				'description'  => 'Internal milestone-title label used by Timeline — Item.',
				'icon'         => 'eicon-t-letter-bold',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'timeline', 'title', 'heading', 'atomic' ],
				'category'     => 'general',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-timeline-desc' => [
				'label'        => 'Timeline — Description',
				'description'  => 'Internal milestone-description paragraph used by Timeline — Item.',
				'icon'         => 'eicon-paragraph',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'timeline', 'description', 'paragraph', 'atomic' ],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			// ── Progress Bar Template — PARENT widget #1. The only entry of
			// this sub-group exposed in the dashboard widget list.
			'aae-a-progressbar' => [
				'label'        => 'Progress Bar',
				'description'  => 'An open progress-bar container — drops as a plain Line bar built from real track/fill/label children you can restyle natively, with Circle and Dot presets one click away.',
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

			// ── Progress Bar Template — HELPER widgets below. `is_internal
			// => true` hides these from the dashboard widget list; they are
			// only used internally by "Progress Bar Template" above.
			'aae-a-progressbar-track' => [
				'label'        => 'Progress Bar — Track',
				'description'  => 'Internal track sub-element used by the Progress Bar Template.',
				'icon'         => 'eicon-skill-bar',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'progress', 'progressbar', 'track', 'atomic' ],
				'category'     => 'general',
				'order'        => 12,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-progressbar-fill' => [
				'label'        => 'Progress Bar — Fill',
				'description'  => 'Internal fill sub-element used by the Progress Bar Template\'s Track.',
				'icon'         => 'eicon-skill-bar',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'progress', 'progressbar', 'fill', 'atomic' ],
				'category'     => 'general',
				'order'        => 12,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-progressbar-label' => [
				'label'        => 'Progress Bar — Label',
				'description'  => 'Internal percentage-label sub-element used by the Progress Bar Template.',
				'icon'         => 'eicon-t-letter-bold',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'progress', 'progressbar', 'label', 'atomic' ],
				'category'     => 'general',
				'order'        => 12,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-progressbar-dot' => [
				'label'        => 'Progress Bar — Dot',
				'description'  => 'Internal step-dot sub-element used by the Progress Bar Template\'s Dot preset.',
				'icon'         => 'eicon-dot-circle-o',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'progress', 'progressbar', 'dot', 'atomic' ],
				'category'     => 'general',
				'order'        => 12,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			// ── Toggle Switcher — PARENT widget #1. The only entry of this
			// sub-group exposed in the dashboard widget list.
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

			// ── Toggle Switcher — HELPER widgets below. `is_internal =>
			// true` hides these from the dashboard widget list; they are
			// only used internally by "Toggle Switcher" above.
			'aae-a-toggle-pane' => [
				'label'        => 'Toggle Pane (Internal)',
				'description'  => 'Internal child container for Toggle Switcher.',
				'icon'         => 'eicon-inner-section',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
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

			'aae-a-toggle-switcher-tabs' => [
				'label'        => 'Toggle Switcher — Tabs',
				'description'  => 'Internal tabs row used by the Toggle Switcher.',
				'icon'         => 'eicon-t-letter',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'toggle', 'switch', 'tabs', 'atomic' ],
				'category'     => 'general',
				'order'        => 14,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-toggle-switcher-tab' => [
				'label'        => 'Toggle Switcher — Tab',
				'description'  => 'Internal tab button used by the Toggle Switcher Tabs row.',
				'icon'         => 'eicon-t-letter',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'toggle', 'switch', 'tab', 'atomic' ],
				'category'     => 'general',
				'order'        => 14,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-toggle-pane-title' => [
				'label'        => 'Toggle Pane — Title',
				'description'  => 'Internal pane-title label used by the Toggle Pane.',
				'icon'         => 'eicon-t-letter-bold',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'toggle', 'pane', 'title', 'atomic' ],
				'category'     => 'general',
				'order'        => 14,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-toggle-pane-desc' => [
				'label'        => 'Toggle Pane — Description',
				'description'  => 'Internal pane-description paragraph used by the Toggle Pane.',
				'icon'         => 'eicon-paragraph',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'toggle', 'pane', 'description', 'atomic' ],
				'category'     => 'general',
				'order'        => 14,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-offcanvas' => [
				'label'        => 'Offcanvas',
				'description'  => 'Offcanvas drawer with trigger + panel and selectable GSAP open/close animations.',
				'icon'         => 'eicon-sidebar',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'category'     => 'interaction',
				'order'        => 15,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-offcanvas-panel' => [
				'is_internal'  => true,
				'label'        => 'Offcanvas Panel (Internal)',
				'description'  => 'Internal locked panel container for Offcanvas.',
				'icon'         => 'eicon-inner-section',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'is_internal'  => true,
				'label'           => 'Offcanvas Trigger',
				'class_name'      => 'WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Trigger',
				'icon'            => 'eicon-menu-bar',
				'keywords'        => [ 'offcanvas', 'trigger', 'icon' ],
				'hide_from_panel' => true,
			],
			'aae-a-offcanvas-close' => [
				'is_internal'  => true,
				'label'           => 'Offcanvas Close',
				'class_name'      => 'WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Close',
				'icon'            => 'eicon-close',
				'keywords'        => [ 'offcanvas', 'close', 'icon' ],
				'hide_from_panel' => true,
			],
			// ── Offcanvas backdrop — HELPER widget. Seeded as a locked child of
			// the Offcanvas root, never dragged from the panel on its own, so
			// `is_internal => true` keeps it out of the dashboard list. This
			// entry previously carried the class-registry keys (`class_name`,
			// `hide_from_panel`) instead of dashboard metadata, which rendered
			// it as a card with no category, toggle state or description.
			'aae-a-offcanvas-overlay' => [
				'label'        => 'Offcanvas Overlay',
				'description'  => 'Backdrop layer behind an open Offcanvas panel.',
				'icon'         => 'eicon-square',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'offcanvas', 'overlay', 'backdrop', 'scrim' ],
				'category'     => 'general',
				'is_internal'  => true,
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
				'category'     => 'form',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-label' => [
				'is_internal'  => true,
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
				'is_internal'  => true,
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
				'is_internal'  => true,
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
				'is_internal'  => true,
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
				'is_internal'  => true,
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
				'is_internal'  => true,
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
				'is_internal'  => true,
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
				'is_internal'  => true,
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

			'aae-a-form-file' => [
				'is_internal'  => true,
				'label'        => 'Form File Upload',
				'description'  => 'File upload field for AAE Form — files land in private local storage, validated server-side (type, size), downloadable only by admins.',
				'icon'         => 'eicon-upload',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form file',
					'upload',
					'attachment',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-field-error' => [
				'is_internal'  => true,
				'label'        => 'Form Field Error',
				'description'  => 'Style source for inline validation messages — its look and text are copied onto every "This field is required." error the form shows. Deleting it never removes validation; errors just fall back to the default look.',
				'icon'         => 'eicon-warning',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form error',
					'validation',
					'required',
				],
				'category'     => 'general',
				'order'        => 19,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-rating' => [
				'is_internal'  => true,
				'label'        => 'Form Rating',
				'description'  => 'Advanced Field (Pro) — a star-rating field. Progressively enhances a real number input, so submit and validation work exactly like any other number field.',
				'icon'         => 'eicon-star-o',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form rating',
					'star',
					'review',
					'feedback',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-range' => [
				'is_internal'  => true,
				'label'        => 'Form Range',
				'description'  => 'Advanced Field (Pro) — a range slider. Style tab → Background Color sets the slider\'s own color.',
				'icon'         => 'eicon-slider-push',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form range',
					'slider',
					'range',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-password' => [
				'is_internal'  => true,
				'label'        => 'Form Password',
				'description'  => 'Advanced Field (Pro) — a masked password field with an optional reveal button, minimum length and confirm-match. Never stored, emailed or sent to webhooks in readable form.',
				'icon'         => 'eicon-lock-user',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form password',
					'password',
					'secret',
					'confirm',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-calculation' => [
				'is_internal'  => true,
				'label'        => 'Form Calculation',
				'description'  => 'Advanced Field (Pro) — a read-only total computed from other fields by a formula, e.g. {quantity} * {price}. The server recomputes it on submit, so the stored value can never be tampered with.',
				'icon'         => 'eicon-number-field',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form calculation',
					'calculator',
					'total',
					'price',
					'quote',
					'sum',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-country' => [
				'is_internal'  => true,
				'label'        => 'Form Country',
				'description'  => 'Advanced Field (Pro) — a country dropdown with the full ISO country list built in. Prune or reorder the list to pin priority countries; values submit as ISO codes.',
				'icon'         => 'eicon-globe',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form country',
					'country',
					'dropdown',
					'nationality',
				],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-submit' => [
				'is_internal'  => true,
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

			'aae-a-form-step' => [
				'is_internal'  => true,
				'label'        => 'Form Step',
				'description'  => 'Multi-Step Forms (Pro) — one page of a multi-step form. Add 2+ inside a form to turn it into a wizard with Next/Previous navigation and per-step validation.',
				'icon'         => 'eicon-single-page',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form step',
					'multi-step',
					'wizard',
					'page',
				],
				'category'     => 'general',
				'order'        => 19,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-next' => [
				'is_internal'  => true,
				'label'        => 'Next Step Button',
				'description'  => 'Multi-Step Forms (Pro) — advances a multi-step form to the next step. Validates the current step first.',
				'icon'         => 'eicon-arrow-right',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form next',
					'multi-step',
					'wizard',
					'button',
				],
				'category'     => 'general',
				'order'        => 19,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-form-prev' => [
				'is_internal'  => true,
				'label'        => 'Previous Step Button',
				'description'  => 'Multi-Step Forms (Pro) — steps a multi-step form back one step. Never validated.',
				'icon'         => 'eicon-arrow-left',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'form previous',
					'form back',
					'multi-step',
					'wizard',
					'button',
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
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'nav', 'menu', 'navbar', 'navigation', 'atomic', 'aae' ],
				'category'     => 'header-footer',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-nav-item' => [
				'is_internal'  => true,
				'label'        => 'Nav Item (Internal)',
				'description'  => 'Internal child item for Nav.',
				'icon'         => 'eicon-nav-menu',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [ 'nav item', 'internal' ],
				'category'     => 'header-footer',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			// ── Nav — remaining HELPER widgets. Registered as classes and
			// routed through WIDGET_PARENT_MAP, but with no entry here, so
			// nothing could name them (same gap as the slider parts above).
			'aae-a-nav-sub-item' => [
				'is_internal'  => true,
				'label'        => 'Nav Sub Item',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Sub_Item',
				'keywords'     => [ 'nav', 'submenu', 'dropdown', 'internal' ],
				'icon'         => 'eicon-nav-menu',
				'hide_from_panel' => true,
			],

			'aae-a-mobile-nav' => [
				'is_internal'  => true,
				'label'        => 'Mobile Nav',
				'class_name'   => 'WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Mobile_Nav',
				'keywords'     => [ 'nav', 'mobile', 'responsive', 'internal' ],
				'icon'         => 'eicon-menu-bar',
				'hide_from_panel' => true,
			],

			'aae-a-flip-box' => [
				'label'        => 'Flip Box',
				'description'  => 'An open, unlocked flip card — front and back faces are plain containers you can restyle or fill freely. Pair with the ready-made preset designs for each flip direction.',
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
					'preset',
					'template',
				],
				'category'     => 'interaction',
				'order'        => 14,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-flip-box-front' => [
				'label'        => 'Flip Box Front (Internal)',
				'description'  => 'Internal front face container for the AAE Flip Box.',
				'icon'         => 'eicon-inner-section',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'flip face', 'internal' ],
				'category'     => 'general',
				'order'        => 17,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-flip-box-back' => [
				'label'        => 'Flip Box Back (Internal)',
				'description'  => 'Internal back face container for the AAE Flip Box.',
				'icon'         => 'eicon-inner-section',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'flip face', 'internal' ],
				'category'     => 'general',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-image-hotspot' => [
				'label'        => 'AAE Image Hotspot',
				'description'  => 'Interactive markers over an image — inline tooltips, teleported lightboxes (with drill-down support), a guided auto-tour, auto-numbered badges, and 6 marker animations.',
				'icon'         => 'eicon-image-hotspot',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => [
					'hotspot',
					'image',
					'tooltip',
					'lightbox',
					'tour',
					'marker',
					'atomic',
				],
				'category'     => 'interaction',
				'order'        => 20,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-hotspot-point' => [
				'label'        => 'Hotspot Point (Internal)',
				'description'  => 'Internal repeating marker for the AAE Image Hotspot. Inserted only via its Hotspots control.',
				'icon'         => 'eicon-point',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'hotspot point', 'internal' ],
				'category'     => 'general',
				'order'        => 21,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-hotspot-marker' => [
				'label'        => 'Hotspot Marker (Internal)',
				'description'  => 'Internal marker (icon/dot/text) for the AAE Hotspot Point.',
				'icon'         => 'eicon-point',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'hotspot marker', 'internal' ],
				'category'     => 'general',
				'order'        => 22,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-hotspot-content' => [
				'label'        => 'Hotspot Content (Internal)',
				'description'  => 'Internal tooltip/lightbox box for the AAE Hotspot Point.',
				'icon'         => 'eicon-post-content',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'hotspot content', 'internal' ],
				'category'     => 'general',
				'order'        => 23,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-hotspot-close' => [
				'label'        => 'Hotspot Close (Internal)',
				'description'  => 'Internal close button for the AAE Hotspot Point\'s lightbox.',
				'icon'         => 'eicon-close',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'hotspot close', 'internal' ],
				'category'     => 'general',
				'order'        => 24,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-hotspot-lightbox' => [
				'label'        => 'Hotspot Lightbox (Internal)',
				'description'  => 'Internal dark backdrop + centering frame the AAE Hotspot Point\'s content is moved into for lightbox display.',
				'icon'         => 'eicon-lightbox-expand',
				'is_pro'       => true,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'hotspot lightbox', 'internal' ],
				'category'     => 'general',
				'order'        => 25,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-flip-box-title' => [
				'label'        => 'Flip Box Title (Internal)',
				'description'  => 'Internal face title used by the AAE Flip Box front/back faces.',
				'icon'         => 'eicon-t-letter-bold',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'flip title', 'internal' ],
				'category'     => 'general',
				'order'        => 19,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-flip-box-text' => [
				'label'        => 'Flip Box Text (Internal)',
				'description'  => 'Internal face body copy used by the AAE Flip Box front/back faces.',
				'icon'         => 'eicon-paragraph',
				'is_pro'       => false,
				'is_extension' => false,
				'is_upcoming'  => false,
				'is_internal'  => true,
				'default'      => true,
				'keywords'     => [ 'flip text', 'internal' ],
				'category'     => 'general',
				'order'        => 20,
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
				'category'     => 'video',
				'order'        => 18,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'aae-a-video-mask-btn' => [
				'is_internal'  => true,
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

			'aae-a-btn' => [
				'label'        => 'Button',
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
				'label'        => 'Button Pro',
				// Was a copy of aae-a-btn's description; the accurate text lived on
				// the duplicate 'aae-a-button-pro' entry that has now been removed.
				'description'  => 'Advanced button widget with 8 GSAP-powered hover styles: ripple, text flip, border divide, group swap, shadow, outline pill, and slide fill.',
				'icon'         => 'wcf-icon-Button',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Animation',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Animation-Builder',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Text-Animation',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Image-Animation',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Image-Hover-Effect',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Pin-Elements',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Horizontal',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Cursor-Hover-Effect',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Cursor-Move-Effect',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Advanced-Tooltip',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Tilt-Effect',
				'is_pro'       => true,
				'badge_only'       => true,
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
				'icon'         => 'wcf-icon-Horizontal',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['scroll to', 'anchor', 'smooth scroll', 'scroll navigation'],
				'category'     => 'interaction',
				'order'        => 12,
			],

			// Implemented in the Pro plugin (inc/extensions/wcf-dynamic-tags.php +
			// inc/core/dynamic-tags/). It used to be reachable ONLY through the v3
			// extension list, so a site working purely in v4 had no way to switch it
			// on and dynamic tags silently did nothing on atomic widgets. Pro loads
			// it from this toggle as well — see WCFAddonsPro\Plugin::register_extensions().
			'dynamic-tags' => [
				'label'        => 'Dynamic Tags',
				'description'  => 'Bind atomic widget content to dynamic sources: post, author, site, archive, comments and ACF fields.',
				// Capitalised on purpose: this is the glyph name that actually
				// exists in the icon font. The lower-case names the other atomic
				// extensions use (wcf-icon-parallax, wcf-icon-custom-css, …) match
				// nothing, which is why they render as empty circles.
				'icon'         => 'wcf-icon-Dynamic-Tags',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['dynamic tags', 'dynamic', 'acf', 'custom field', 'post data'],
				'category'     => 'utility',
				'order'        => 13,
			],

			'mask' => [
				'label'        => 'Mask',
				'description'  => 'Clip a Flexbox, Div Block or Grid to a shape — 20 built-in shapes or your own SVG, with responsive and hover variants.',
				'icon'         => 'wcf-icon-Custom-CSS',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['mask', 'shape', 'clip', 'svg', 'container'],
				'category'     => 'utility',
				'order'        => 16,
			],

			'background-video' => [
				'label'        => 'Background Video',
				'description'  => 'Play a looping video behind a Flexbox, Div Block or Grid — the option the atomic Background control is missing.',
				'icon'         => 'wcf-icon-Custom-CSS',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['background video', 'video', 'background', 'container'],
				'category'     => 'utility',
				'order'        => 15,
			],

			'custom-css' => [
				'label'        => 'Custom CSS',
				'description'  => 'Add custom CSS rules per-element in the atomic editor.',
				'icon'         => 'wcf-icon-Custom-CSS',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['custom css', 'css', 'style', 'custom style'],
				'category'     => 'utility',
				'order'        => 14,
			],

			/*
			 * Pro AtomicV4 modules (animation-addons-for-elementor-pro/inc/AtomicV4/).
			 * They used to load unconditionally from AtomicV4\Bootstrap, so they never
			 * appeared here and could not be switched off. The registry lives in the
			 * free plugin — same constraint as widgets — so their definitions sit here
			 * and Pro gates itself on is_extension_active().
			 *
			 * `requires` lists the widget slugs an extension is useless without, and
			 * `requires_note` is the ready-to-render tooltip string for the dashboard.
			 * Both are optional; extensions that apply to any atomic element omit them.
			 */
			// Slug stays `flexbox-child-hover` — it is what Pro's AtomicV4
			// Bootstrap gates on, and a saved option is keyed by slug, so
			// renaming it would orphan every site's setting. The user-facing
			// label/keywords take the clearer "Parent Child Hover" wording;
			// keywords carry both namings so search finds it either way.
			'flexbox-child-hover' => [
				'label'        => 'Parent Child Hover',
				'description'  => 'Hover a container to trigger a "Parent Hover" style state on its child elements.',
				'icon'         => 'wcf-icon-Grid-Hover-Posts',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['parent child hover', 'flexbox child hover', 'parent hover', 'hover source', 'hover target', 'child hover', 'container hover'],
				'category'     => 'interaction',
				'order'        => 15,
				'requires_note' => 'Applies to Elementor\'s Flexbox container and its children.',
			],

			'form-conditions' => [
				'label'        => 'Conditional Display',
				'description'  => 'Show or hide AAE Form fields/containers based on the value of other fields.',
				'icon'         => 'wcf-icon-Toggle-Switch',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['conditional display', 'conditional logic', 'show hide fields', 'form conditions', 'dynamic fields'],
				'category'     => 'form',
				'order'        => 16,
				'requires'     => ['aae-a-form'],
				'requires_note' => 'Requires the Form widget.',
			],

			'form-validation' => [
				'label'        => 'Validation Pro',
				'description'  => 'Regex validation rules with custom messages on form inputs and textareas.',
				// There is no `wcf-icon-Form` in the icon font — it rendered as
				// an empty circle on the dashboard card.
				'icon'         => 'wcf-icon-Content-Protection',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['validation', 'regex', 'pattern', 'form validation'],
				'category'     => 'form',
				'order'        => 17,
				'requires'     => ['aae-a-form'],
				'requires_note' => 'Requires the Form widget.',
			],

			'form-user' => [
				'label'        => 'Create User',
				'description'  => 'Turn a form submission into a real WordPress account, with role and alias mapping.',
				// See the note on Validation Pro above — `wcf-icon-Form` does
				// not exist in the icon font.
				'icon'         => 'wcf-icon-Team',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['create user', 'registration', 'signup', 'account'],
				'category'     => 'form',
				'order'        => 18,
				'requires'     => ['aae-a-form'],
				'requires_note' => 'Requires the Form widget. Configured per form in the Actions dialog.',
			],

			'popup' => [
				'label'        => 'Popup',
				'description'  => 'Site-wide popup system for atomic elements, triggered from AAE Builder templates.',
				'icon'         => 'wcf-icon-Popup',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['popup', 'modal', 'lightbox', 'dialog'],
				'category'     => 'interaction',
				'order'        => 19,
				'requires_note' => 'Popups are built as AAE Builder templates.',
			],

			/*
			 * Template Library — the "Add AAE Template" modal in the Elementor
			 * editor (Library_Source + inc/class-wcf-template-library.php, driven
			 * by assets/js/wcf-template-library.js).
			 *
			 * There is a `template-library` entry in the V3 registry (config.php)
			 * too, but NOTHING reads that key — it is a display-only card, so the
			 * feature has never actually been switchable. This entry is the one
			 * that works: class-plugin.php::include_files() gates the require of
			 * inc/class-wcf-template-library.php on it, which in turn is what
			 * defines Library_Source and therefore satisfies the two
			 * class_exists('\WCF_ADDONS\Library_Source') checks that register the
			 * editor script and the modal's Underscore templates.
			 *
			 * `default` is false on purpose. Unlike the Pro AtomicV4 modules
			 * above — which this flag exists to rescue, because they USED to load
			 * unconditionally — this file has never been required from anywhere,
			 * so no site has ever had the feature on. Defaulting to true would
			 * make migrate_newly_offered_extensions() silently introduce a new
			 * editor modal on every existing install rather than restore
			 * something they already had.
			 */
			'template-library' => [
				'label'        => 'Template Library',
				'description'  => 'Ready-made AAE layouts, importable from a library modal inside the Elementor editor.',
				// Capitalised to match the glyph that actually exists in the icon
				// font (\e957) — see the Dynamic Tags note above for why the
				// lower-case spellings render as empty circles.
				'icon'         => 'wcf-icon-Template-library',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['template library', 'templates', 'layout', 'import', 'blocks', 'pages', 'library'],
				'category'     => 'utility',
				'order'        => 20,
			],

			/*
			 * Site-wide admin features that predate the atomic dashboard.
			 *
			 * These four are not element extensions — they add wp-admin screens
			 * (a font manager, a post-type builder, an icon-set uploader, a
			 * snippet editor) and their output is consumed by v3 and v4 alike:
			 * a font uploaded here shows up in the atomic Typography control,
			 * a post type built here is what an atomic Loop Grid queries.
			 *
			 * They used to be reachable ONLY from the v3 extension list, so a
			 * site working purely in v4 had no way to switch them on at all —
			 * the same defect Dynamic Tags had (see its entry above). Their
			 * slugs deliberately MATCH the v3 config.php keys so the two option
			 * arrays stay legible side by side.
			 *
			 * Loading is an OR of the two toggles — see
			 * class-plugin.php::register_extensions() and include_files(). Turning
			 * one off here does NOT stop it if the v3 switch is still on; that is
			 * the same contract Dynamic Tags and Template Library already have,
			 * and it is what keeps an existing v3 site working untouched.
			 *
			 * `default` is false on purpose: these have shipped for a long time
			 * and every site already has a deliberate answer for them stored in
			 * `wcf_save_extensions`. Defaulting to true would make
			 * migrate_newly_offered_extensions() switch four features on for
			 * everybody, including the people who turned them off. The real
			 * answer is copied across once by backfill_v3_admin_extensions().
			 */
			'custom-fonts' => [
				'label'        => 'Custom Fonts',
				'description'  => 'Upload and manage your own font families, selectable from the Elementor typography controls.',
				'icon'         => 'wcf-icon-Custom-Fonts',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['custom fonts', 'fonts', 'typography', 'webfont', 'woff', 'font upload'],
				'category'     => 'utility',
				'order'        => 21,
				'demo_url'     => 'https://animation-addons.com/docs/general-extensions/custom-fonts/',
				'doc_url'      => 'https://animation-addons.com/docs/general-extensions/custom-fonts/',
			],

			'custom-cpt' => [
				'label'        => 'Post Type Builder',
				'description'  => 'Create custom post types and taxonomies without code, ready to query from a Loop Grid.',
				'icon'         => 'wcf-icon-Custom-Post-Type',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['post type builder', 'custom post type', 'cpt', 'taxonomy', 'content type'],
				'category'     => 'utility',
				'order'        => 22,
				'demo_url'     => 'https://animation-addons.com/docs/general-extensions/post-type-builder/',
				'doc_url'      => 'https://animation-addons.com/docs/general-extensions/post-type-builder/',
			],

			'custom-icon' => [
				'label'        => 'Custom Icon',
				'description'  => 'Upload icon-font sets (IcoMoon/Fontello zips) and use them anywhere Elementor offers an icon picker.',
				'icon'         => 'wcf-icon-Custom-Icons',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['custom icon', 'icons', 'icon font', 'icomoon', 'fontello', 'svg icons'],
				'category'     => 'utility',
				'order'        => 23,
				'demo_url'     => 'https://animation-addons.com/docs/general-extensions/custom-icon/',
				'doc_url'      => 'https://animation-addons.com/docs/general-extensions/custom-icon/',
			],

			'code-snippet' => [
				'label'        => 'Code Snippet',
				'description'  => 'Add PHP, CSS, JS or HTML snippets from wp-admin, with per-snippet placement and activation.',
				// There is no `wcf-icon-Code-Snippet` glyph in the icon font —
				// this is the same one the v3 card uses. See the Dynamic Tags
				// note above for why a guessed name renders as an empty circle.
				'icon'         => 'wcf-icon-Content-Protection',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['code snippet', 'snippets', 'php', 'custom code', 'functions'],
				'category'     => 'utility',
				'order'        => 24,
				'demo_url'     => 'https://animation-addons.com/docs/general-extensions/code-snippet/',
				'doc_url'      => 'https://animation-addons.com/docs/general-extensions/code-snippet/',
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

		// Advanced Heading's `content` prop changed shape (string → html-v3) on
		// 2026-08-04. Registered UNCONDITIONALLY, not behind is_widget_active():
		// the read path has to keep converting even while the widget is switched
		// off, or turning it off and on again is enough to erase every heading
		// on the site the next time a page is saved. See the class docblock.
		require_once __DIR__ . '/Widgets/AdvancedHeading/class-aae-advanced-heading-migration.php';
		\WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading\AAE_Advanced_Heading_Migration::register();

		// A Mobile Nav is a SIBLING of its Nav, so Elementor never cascade-deletes
		// it. The editor sweeps are best-effort JS; this is the save-time belt that
		// stops an orphan ever being written to the document. Registered
		// unconditionally for the same reason as the migration above — the guard
		// must hold even while the widget is switched off.
		require_once __DIR__ . '/Widgets/Nav/class-aae-a-nav-companion-sweep.php';
		\WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Companion_Sweep::register();

		// Rewrites saved pages when a WP menu changes, so an imported Nav updates on
		// the FRONTEND without anyone opening Elementor.
		require_once __DIR__ . '/Widgets/Nav/class-aae-a-nav-menu-sync.php';
		\WCF_ADDONS\AtomicWidgets\Widgets\Nav\AAE_A_Nav_Menu_Sync::register();

		// Panel grouping: AAE's atomic widgets otherwise inherit Elementor's
		// generic "Atomic Elements" (v4-elements) category and all land in one
		// bucket. Note the base classes read the category from DIFFERENT hooks:
		// Atomic_Widget_Base (leaf) uses get_categories(), Atomic_Element_Base
		// (container) uses define_panel_categories() — see the docblock on
		// register_atomic_categories().
		add_action('elementor/elements/categories_registered', [$this, 'register_atomic_categories']);

		// …and then move them to the TOP of the panel. Registration can only
		// append (see PANEL_CATEGORY_ORDER), so the order is re-imposed on the
		// editor config instead. See promote_panel_categories().
		add_filter('elementor/editor/localize_settings', [$this, 'promote_panel_categories']);

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
		// Frontend counterpart: our atomic widget stylesheets (e.g. aae-a-btn-css)
		// must print AFTER Elementor's own cached `base-desktop` styles. See
		// fix_frontend_atomic_css_order()'s docblock for why.
		add_action('wp_print_styles', [$this, 'fix_frontend_atomic_css_order'], 0);
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

		// AAE Nav: list WordPress menus + their nested item trees so the Nav
		// panel's "Import from WordPress menu" control can rebuild them as
		// atomic nav-items. Reuses the `aae_loop_grid` editor nonce.
		add_action('wp_ajax_aae_get_nav_menus', [$this, 'ajax_get_nav_menus']);

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

		// AAE Post Pagination: invalidate the cached ordered-id lists for a post
		// type the moment content actually changes, rather than trusting the
		// transient TTL alone.
		add_action('save_post', [$this, 'bump_post_pagination_cache_version']);
		add_action('deleted_post', [$this, 'bump_post_pagination_cache_version']);
		add_action('trashed_post', [$this, 'bump_post_pagination_cache_version']);
		add_action('untrashed_post', [$this, 'bump_post_pagination_cache_version']);

		// AAE Post Pagination: "Order By > Menu Order / Manual Sequence" needs the
		// Order field available on plain Posts (Pages/WooCommerce Products
		// already support it natively) — Document > Page Attributes > Order
		// in the block editor, or the classic Page Attributes meta box.
		add_action('init', function () {
			if (post_type_exists('post') && ! post_type_supports('post', 'page-attributes')) {
				add_post_type_support('post', 'page-attributes');
			}
		}, 20);

		// No defaults are seeded for atomic widgets or atomic extensions: the
		// Atomic dashboard (and, on a brand new site, the setup wizard) owns both
		// option arrays outright. A slug absent from the saved option simply reads
		// as inactive.
		$this->migrate_newly_offered_extensions();
		// Must run AFTER the migration: that one writes the offered list, which
		// is what stops these four being treated as brand new on the next load.
		$this->backfill_v3_admin_extensions();
		$this->backfill_formerly_forced_widgets();

		// Deferred, not run inline: this method builds its registries in the
		// constructor on `plugins_loaded`, and the Pro plugin adds its widgets
		// through the `aae/atomic/*` filters when ITS plugin class is constructed
		// — after ours, because WordPress loads the two alphabetically. Asserting
		// here compared a complete card list against an incomplete class list and
		// reported every Pro-owned widget as drift on every request.
		//
		// `elementor/init` is late enough for both sides and still earlier than
		// any widget registration, so real drift is still caught before it can
		// matter.
		add_action('elementor/init', [$this, 'assert_registry_integrity'], 5);
	}

	/**
	 * Report drift between the two widget registries. WP_DEBUG only.
	 *
	 * Widget data is split across two hand-maintained arrays that must agree:
	 * get_available_widgets() (class / file / asset handles — what registers with
	 * Elementor) and widgets_registry (dashboard metadata — what can be toggled).
	 * Nothing enforced that agreement, and three separate defects had accumulated
	 * silently:
	 *
	 *   - 'aae-a-button', 'aae-a-button-pro', 'aae-a-image-compare-main' had
	 *     metadata but no class, so the dashboard rendered duplicate cards whose
	 *     toggles controlled nothing.
	 *   - 'aae-a-menu' had a class but no metadata, so it could never be enabled.
	 *
	 * None of that surfaces at runtime — a missing entry just makes a widget
	 * quietly unreachable — so it can persist for releases. This turns it into an
	 * immediate, visible failure while developing.
	 *
	 * Children routed through WIDGET_PARENT_MAP are expected to have no metadata:
	 * they are grouped under their parent rather than listed, which is why they
	 * are excluded here.
	 */
	public function assert_registry_integrity(): void
	{
		if (! defined('WP_DEBUG') || ! WP_DEBUG) {
			return;
		}

		$available = array_keys($this->get_available_widgets());
		$metadata  = array_keys($this->get_widgets_registry());

		// Registered, but nothing in the dashboard can ever switch it on.
		$missing_metadata = array_diff(
			$available,
			$metadata,
			array_keys($this->widget_parent_map()),
			self::PARKED_WIDGETS
		);

		// A dashboard toggle for a widget that cannot load.
		$missing_class = array_diff($metadata, $available);

		// An internal child with no parent. Once the forced-active list is gone
		// these fall through to the saved-option lookup, which they can never
		// satisfy (they have no toggle), so they would never register and the
		// editor would throw ElementTypeNotFound on any page already using one.
		$internal = [];
		foreach ($this->get_widgets_registry() as $slug => $def) {
			if (! empty($def['is_internal'])) {
				$internal[] = $slug;
			}
		}

		$orphan_children = array_diff(
			$internal,
			array_keys($this->widget_parent_map()),
			$this->always_active_widgets()
		);

		// A parent that no longer exists — the children would inherit from a
		// slug that is never active, silently disabling the whole family.
		$dangling_parents = array_diff(
			array_unique(array_values($this->widget_parent_map())),
			$metadata
		);

		if ($missing_metadata) {
			error_log(
				'AAE atomic registry: registered widget(s) with no dashboard metadata — unreachable: '
				. implode(', ', $missing_metadata)
			);
		}

		if ($orphan_children) {
			error_log(
				'AAE atomic registry: internal widget(s) with no WIDGET_PARENT_MAP parent — cannot inherit: '
				. implode(', ', $orphan_children)
			);
		}

		if ($dangling_parents) {
			error_log(
				'AAE atomic registry: WIDGET_PARENT_MAP points at unknown parent(s): '
				. implode(', ', $dangling_parents)
			);
		}

		if ($missing_class) {
			error_log(
				'AAE atomic registry: dashboard metadata with no class/file — toggle does nothing: '
				. implode(', ', $missing_class)
			);
		}
	}

	/**
	 * Switch on extensions that have never been offered to this site before.
	 *
	 * NOT a return of the default-seeder. The seeder could only see "enabled or
	 * absent" and so re-enabled anything the user had switched off; this compares
	 * against a separate record of what has been PRESENTED, which distinguishes
	 * "new in this release" from "deliberately disabled".
	 *
	 * Needed because several Pro AtomicV4 modules (Conditional Display, Validation
	 * Pro, Flexbox Child Hover, Create User, Popup) previously loaded
	 * unconditionally. Now that they are gated on is_extension_active(), an
	 * existing site would otherwise lose them silently on update — their slugs
	 * have never been written to anyone's settings.
	 *
	 * Brand new sites are skipped entirely: with no saved option at all the setup
	 * wizard owns first-run configuration.
	 */
	/**
	 * Keep Post Title / Post Image switched on for sites that already had them.
	 *
	 * They used to be in ALWAYS_ACTIVE_WIDGETS, so is_widget_active() returned
	 * true whether or not the slug was ever written to the saved option — most
	 * sites therefore have them active but ABSENT from that option. Now that
	 * they follow their own toggle, doing nothing here would silently
	 * deactivate them on upgrade, and any page using AAE Post Title/Image (or a
	 * Loop Grid item, which seeds both as default children) would fail to
	 * render that element.
	 *
	 * Runs once, guarded by its own marker option rather than by "is the slug
	 * missing?" — otherwise a user who deliberately switches one off would have
	 * it switched back on by the very next page load.
	 *
	 * Brand new sites are skipped: with no saved option at all the setup wizard
	 * owns first-run configuration, exactly as in
	 * migrate_newly_offered_extensions().
	 */
	private function backfill_formerly_forced_widgets(): void
	{
		if (get_option(self::FORCED_BACKFILL_OPTION_NAME)) {
			return;
		}

		$saved = get_option(self::OPTION_NAME);

		// No settings yet -> fresh install, the wizard decides. Don't pre-empt
		// it, and don't burn the marker either: let the wizard write first.
		if (! is_array($saved)) {
			return;
		}

		$changed = false;

		foreach (self::FORMERLY_FORCED_WIDGETS as $slug) {
			if (! isset($saved[$slug])) {
				$saved[$slug] = true;
				$changed      = true;
			}
		}

		if ($changed) {
			update_option(self::OPTION_NAME, $saved);
			$this->active_widgets = null;
			// Both are derived from the active set.
			$this->registerable_classes = null;
			$this->widget_active_cache  = [];
		}

		update_option(self::FORCED_BACKFILL_OPTION_NAME, true);
	}

	/**
	 * Copy the v3 answer for the four shared admin features into the v4 option.
	 *
	 * Custom Fonts / Post Type Builder / Custom Icon / Code Snippet now have a
	 * card on the Atomic Extensions screen as well as the v3 one. Loading is an
	 * OR of the two toggles, so nothing about an existing site's behaviour
	 * changes when this ships — but WITHOUT this copy the new card would open
	 * reading "off" on a site that has been using custom fonts for a year, which
	 * is the "the dashboard card can lie" failure documented in CLAUDE.md. The
	 * user's only recourse would be to flip a switch that was already on.
	 *
	 * Runs once, guarded by its own marker option rather than by "is the slug
	 * missing?" — otherwise someone who deliberately turns a card off here would
	 * have it switched back on by the very next admin page load, because the v3
	 * option still says yes.
	 *
	 * Brand new sites are skipped, exactly as in
	 * migrate_newly_offered_extensions(): with no atomic option at all the setup
	 * wizard owns first-run configuration, and there is no v3 history to copy.
	 * The marker is deliberately NOT burned in that case, so the copy still
	 * happens for a site that installs v4 first and imports v3 content later.
	 */
	private function backfill_v3_admin_extensions(): void
	{
		if (get_option(self::V3_ADMIN_BACKFILL_OPTION_NAME)) {
			return;
		}

		$saved = get_option(self::EXTENSIONS_OPTION_NAME);

		// No settings yet -> fresh install, the wizard decides.
		if (! is_array($saved)) {
			return;
		}

		$legacy  = get_option('wcf_save_extensions');
		$legacy  = is_array($legacy) ? $legacy : [];
		$changed = false;

		foreach (self::V3_ADMIN_EXTENSIONS as $slug) {
			// Only ever switches ON, and only what v3 already had on. An
			// extension the user has since turned off here keeps its own state
			// because the marker below stops this from running twice.
			if (! isset($saved[$slug]) && ! empty($legacy[$slug])) {
				$saved[$slug] = true;
				$changed      = true;
			}
		}

		if ($changed) {
			update_option(self::EXTENSIONS_OPTION_NAME, $saved);
			$this->active_extensions = null;
		}

		update_option(self::V3_ADMIN_BACKFILL_OPTION_NAME, true);
	}

	private function migrate_newly_offered_extensions(): void
	{
		$saved = get_option(self::EXTENSIONS_OPTION_NAME);

		// No settings yet -> fresh install, the wizard decides. Don't pre-empt it.
		//
		// Deliberately does NOT stamp the marker below: the migration still has
		// to run on the first boot AFTER the wizard saves, which is exactly the
		// moment the DANGER box in CLAUDE.md is about.
		if (! is_array($saved)) {
			return;
		}

		// Already done for this build.
		//
		// This runs on `plugins_loaded`, on every request including the front
		// end, and it used to fall through to update_option() unconditionally.
		// That is a no-op only while the stored array happens to match — append
		// or reorder a single registry slug and it becomes a real UPDATE plus an
		// `alloptions` cache flush on EVERY page view.
		//
		// The hook is left exactly where it is on purpose. Moving it to
		// admin_init would change which boots the migration observes, and that
		// is the precise axis the wizard bug lived on; it would also break the
		// documented "must run AFTER the migration" ordering with
		// backfill_v3_admin_extensions().
		if (WCF_ADDONS_VERSION === get_option(self::OFFERED_MIGRATION_OPTION_NAME)) {
			return;
		}

		$offered = get_option(self::EXTENSIONS_OFFERED_OPTION_NAME);

		if (! is_array($offered)) {
			$offered = self::LEGACY_OFFERED_EXTENSIONS;
		}

		$registry_slugs = array_keys($this->extensions_registry);
		$newly_offered  = array_diff($registry_slugs, $offered);

		if ($newly_offered) {
			foreach ($newly_offered as $slug) {
				if (! empty($this->extensions_registry[$slug]['default'])) {
					$saved[$slug] = true;
				}
			}

			update_option(self::EXTENSIONS_OPTION_NAME, $saved);
			$this->active_extensions = null;
		}

		update_option(self::EXTENSIONS_OFFERED_OPTION_NAME, $registry_slugs);

		// Stamped only after the work above completed, so an interrupted request
		// re-runs rather than recording a migration that never finished.
		update_option(self::OFFERED_MIGRATION_OPTION_NAME, WCF_ADDONS_VERSION);
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
	 * This map is only half of it — a widget also needs a
	 * register_widget_definitions() entry or it can never be switched on. See
	 * "Registering a new widget or extension — what must stay in sync" in
	 * CLAUDE.md for the full checklist. (An older comment here pointed at a
	 * "HOW TO ADD A NEW ATOMIC WIDGET" block above register_widget_definitions();
	 * no such block exists.)
	 */
	protected function get_available_widgets()
	{
		// Cheap path: the registry is immutable within a request, but it is
		// assembled from a 940-line literal plus a filter, and it used to be
		// rebuilt at all 14 call sites — including once per rendered element via
		// maybe_enqueue_widget_script(), which made a 200-element page build this
		// array 200 times.
		//
		// TWO GUARDS, both load-bearing:
		//
		// 1. NEVER CACHE BEFORE `init`. Pro injects ~20 widgets through
		//    `aae/atomic/available_widgets`, and it adds that filter at
		//    `plugins_loaded` priority 11. Caching a call made before that would
		//    freeze a pre-Pro registry and PERMANENTLY DELETE every Pro atomic
		//    widget from the site — silently, with no error. do_action()
		//    increments its counter before running callbacks, so did_action('init')
		//    is already true inside `elementor/init` callbacks (where the real
		//    first call lives) and false during plugins_loaded. An early caller
		//    still gets a correct array; it just does not get to cache it.
		//
		// 2. INVALIDATE WHEN THE CALLBACK SET CHANGES. Guard 1 does not cover a
		//    third-party addon that hooks the filter on `elementor/init` at a
		//    priority after our first read, nor remove_filter(). Comparing a
		//    signature of the callback set catches both.
		$signature = $this->filter_signature('aae/atomic/available_widgets');

		if (null !== $this->available_widgets && $signature === $this->available_widgets_signature) {
			return $this->available_widgets;
		}

		$widgets = $this->build_available_widgets();

		if (did_action('init')) {
			$this->available_widgets = $widgets;
			$this->available_widgets_signature = $signature;
		}

		return $widgets;
	}

	/**
	 * Identity of a filter's callback set, used to invalidate memoised results.
	 *
	 * Priorities plus per-priority callback counts are enough: the only way to
	 * change what the filter produces without changing this string is to mutate
	 * a callback in place, which nothing does.
	 */
	private function filter_signature(string $filter): string
	{
		$hook = $GLOBALS['wp_filter'][$filter] ?? null;

		if (!$hook || !isset($hook->callbacks) || !is_array($hook->callbacks)) {
			return '0';
		}

		$parts = [];
		foreach ($hook->callbacks as $priority => $callbacks) {
			$parts[] = $priority . ':' . count((array) $callbacks);
		}

		return implode('|', $parts);
	}

	/**
	 * Drop the memoised registry.
	 *
	 * For tests and for any code that changes what the filter would return
	 * mid-request. Normal operation never needs this — the signature guard in
	 * get_available_widgets() handles filter changes on its own.
	 */
	public function flush_available_widgets_cache(): void
	{
		$this->available_widgets = null;
		$this->available_widgets_signature = null;
	}

	/**
	 * Assemble the registry. Call get_available_widgets() instead — this is the
	 * uncached builder and is expensive.
	 */
	private function build_available_widgets()
	{
		$widgets = [
			// Counter — deliberately GSAP-free (rAF + IntersectionObserver), so it
			// needs no `script_deps`. The `gsap` handle only ever exists when the
			// Pro plugin registers it AND the `wcf_save_extensions` option is set,
			// which made a GSAP-driven counter fire on some pages and not others.
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

			'aae-a-search-query' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\SearchQuery\AAE_A_Search_Query',
				'file' => 'Widgets/SearchQuery/class-aae-a-search-query.php',
				'has_script' => false,
			],

			'aae-a-post-content' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostContent\AAE_A_Post_Content',
				'file' => 'Widgets/PostContent/class-aae-a-post-content.php',
				'has_script' => false,
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

			'aae-a-draw-svg' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\DrawSvg\AAE_A_Draw_Svg',
				'file' => 'Widgets/DrawSvg/class-aae-a-draw-svg.php',
				'script_handle' => 'aae-a-draw-svg-js',
				'script_path' => '/assets/atomic/js/draw-svg.js',
				// Only when Pro is present — see aae-a-offcanvas below. Free ships
				// no GSAP of its own and Atomic\Assets::ensure_gsap_registered()
				// sources these from Pro's assets/lib, so without Pro the handles
				// never exist. draw-svg.js guards on `typeof gsap` and no-ops.
				'script_deps' => defined( 'WCF_ADDONS_PRO_VERSION' )
					? [ 'gsap', 'ScrollTrigger', 'DrawSVGPlugin', 'MotionPathPlugin' ]
					: [],
				'has_script' => true,
			],

			'aae-a-stack-cards' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\StackCards\AAE_A_Stack_Cards',
				'file' => 'Widgets/StackCards/class-aae-a-stack-cards.php',
				'script_handle' => 'aae-a-stack-cards-js',
				'script_path' => '/assets/atomic/js/stack-cards.js',
				// Pro-only handles; stack-cards.js already returns early when
				// window.gsap / window.ScrollTrigger are absent.
				'script_deps' => defined( 'WCF_ADDONS_PRO_VERSION' ) ? [ 'gsap', 'ScrollTrigger' ] : [],
				'has_script' => true,
			],
			'aae-a-stack-card' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\StackCards\AAE_A_Stack_Card',
				'file' => 'Widgets/StackCards/class-aae-a-stack-card.php',
				'has_script' => false,
			],

			// Loop Grid Slider — reuses the Loop Grid query engine + the shared
			// nested-slider runtime. Its only own script is the load-more bridge
			'aae-a-post-pagination' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination',
				'file' => 'Widgets/PostPagination/class-aae-a-post-pagination.php',
				'script_handle' => 'aae-a-post-pagination-js',
				'script_path' => '/assets/atomic/js/post-pagination.js',
				'has_script' => true,
				'style_handle' => 'aae-a-post-pagination-css',
				'style_path' => '/assets/atomic/css/post-pagination.css',
			],

			'aae-a-post-pagination-prev' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Prev',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-prev.php',
			],

			'aae-a-post-pagination-next' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Next',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-next.php',
			],

			'aae-a-post-pagination-preview' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview.php',
			],

			'aae-a-post-pagination-preview-image' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Image',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-image.php',
			],

			'aae-a-post-pagination-preview-category' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Category',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-category.php',
			],

			'aae-a-post-pagination-preview-title' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Title',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-title.php',
			],

			'aae-a-post-pagination-preview-date' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Date',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-date.php',
			],

			'aae-a-post-pagination-preview-author' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Author',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-author.php',
			],

			'aae-a-post-pagination-preview-excerpt' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination_Preview_Excerpt',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-excerpt.php',
			],

			/*
			 * AAE Post Comments family — DISABLED 2026-07-27 (see the matching
			 * commented block in register_widget_definitions() for why).
			 * Root file/class renamed to class-aae-a-comments-ny.php /
			 * AAE_A_Comments_Ny. Uncomment to re-enable.
			 *
			'aae-a-comments-ny' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comments_Ny',
				'file' => 'Widgets/Comments/class-aae-a-comments-ny.php',
				'has_script' => false,
				'style_handle' => 'aae-a-comments-css',
				'style_path' => '/assets/atomic/css/comments.css',
			],

			'aae-a-comment-list' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_List',
				'file' => 'Widgets/Comments/class-aae-a-comment-list.php',
			],

			'aae-a-comment-item' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Item',
				'file' => 'Widgets/Comments/class-aae-a-comment-item.php',
			],

			'aae-a-comment-avatar' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Avatar',
				'file' => 'Widgets/Comments/class-aae-a-comment-avatar.php',
				'has_script' => false,
			],

			'aae-a-comment-author' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Author',
				'file' => 'Widgets/Comments/class-aae-a-comment-author.php',
				'has_script' => false,
			],

			'aae-a-comment-date' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Date',
				'file' => 'Widgets/Comments/class-aae-a-comment-date.php',
				'has_script' => false,
			],

			'aae-a-comment-content' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Content',
				'file' => 'Widgets/Comments/class-aae-a-comment-content.php',
				'has_script' => false,
			],

			'aae-a-comment-reply-link' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Reply_Link',
				'file' => 'Widgets/Comments/class-aae-a-comment-reply-link.php',
				'has_script' => false,
			],

			'aae-a-comment-form' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Form',
				'file' => 'Widgets/Comments/class-aae-a-comment-form.php',
				'has_script' => false,
			],
			*/

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

			// Table of Content — leaf widget. Its JS uses GSAP ScrollTrigger
			// (active-heading scroll-spy) + ScrollToPlugin (smooth scroll) when
			// present, and degrades gracefully to native smooth scroll without
			// them — so, like Counter, it declares NO gsap script_deps (those
			// handles live in the Pro plugin; a missing registered dep would
			// silently prevent this free widget's script from enqueuing).
			'aae-a-toc' => [
				'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\TableOfContents\AAE_A_Table_Of_Contents',
				'file' => 'Widgets/TableOfContents/class-aae-a-table-of-contents.php',
				'script_handle' => 'aae-a-toc-js',
				'script_path' => '/assets/atomic/js/table-of-contents.js',
				'has_script' => true,
				'style_handle' => 'aae-a-toc-css',
				'style_path' => '/assets/atomic/css/table-of-contents.css',
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
			// SCSS-only widget, compiled by gulp's compile:atomic-scss task, not webpack.
			'style_handle' => 'aae-a-social-share-css',
			'style_path'   => '/assets/atomic/css/social-share.css',
		],
		'aae-a-social-share-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\SocialShare\AAE_A_Social_Share_Item',
			'file'       => 'Widgets/SocialShare/class-aae-a-social-share-item.php',
			'has_script' => false,
		],
		// SocialShareMain entries removed — see the note in
		// register_widget_definitions(). The directory no longer exists.
		'aae-a-image-compare' => [
			'class' => '\WCF_ADDONS\AtomicWidgets\Widgets\ImageCompare\AAE_A_Image_Compare',
			'file' => 'Widgets/ImageCompare/class-aae-a-image-compare.php',
			'script_handle' => 'aae-a-image-compare-js',
			'script_path' => '/assets/atomic/js/image-compare.js',
			'has_script' => true,
			'style_handle' => 'aae-a-image-compare-css',
			'style_path' => '/assets/atomic/css/image-compare.css',
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
			// No external CSS and no inline <style> in any Twig: every visual
			// detail (including the marker/year/title/desc typography) is a
			// real base style on its own dedicated widget type. No `style_handle`.
		],
		'aae-a-timeline-item' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Item',
			'file'       => 'Widgets/Timeline/class-aae-a-timeline-item.php',
			'has_script' => false,
		],
		'aae-a-timeline-number' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Number',
			'file'       => 'Widgets/Timeline/Parts/class-aae-a-timeline-number.php',
			'has_script' => false,
		],
		'aae-a-timeline-year' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Year',
			'file'       => 'Widgets/Timeline/Parts/class-aae-a-timeline-year.php',
			'has_script' => false,
		],
		'aae-a-timeline-title' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Title',
			'file'       => 'Widgets/Timeline/Parts/class-aae-a-timeline-title.php',
			'has_script' => false,
		],
		'aae-a-timeline-desc' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Timeline\AAE_A_Timeline_Desc',
			'file'       => 'Widgets/Timeline/Parts/class-aae-a-timeline-desc.php',
			'has_script' => false,
		],
		// Add new atomic widgets below...
			'aae-a-btn' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Btn\AAE_A_Btn',
				'file'          => 'Widgets/Btn/class-aae-a-btn.php',
				'script_handle' => 'aae-a-btn-js',
				'script_path'   => '/assets/atomic/js/btn.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-btn-css',
				'style_path'    => '/assets/atomic/css/btn.css',
			],

			'aae-a-btn-pro' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\BtnPro\AAE_A_Btn_Pro',
				'file'          => 'Widgets/BtnPro/class-aae-a-btn-pro.php',
				'script_handle' => 'aae-a-btn-pro-js',
				'script_path'   => '/assets/atomic/js/btn-pro.js',
				// Ripple + polygon magnetic-move effects need GSAP, but the handle
				// is Pro-only. btn-pro.js guards each GSAP-driven effect on
				// `typeof gsap`, so the button's other behaviour still works.
				'script_deps'   => defined( 'WCF_ADDONS_PRO_VERSION' ) ? [ 'gsap' ] : [],
				'has_script'    => true,
				'style_handle'  => 'aae-a-btn-pro-css',
				'style_path'    => '/assets/atomic/css/btn-pro.css',
			],

			'aae-a-advanced-heading' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading\AAE_A_Advanced_Heading',
				'file'       => 'Widgets/AdvancedHeading/class-aae-a-advanced-heading.php',
				'has_script' => false,
				// Design-less: this widget ships no CSS. Style your own classes.
			],

			'aae-a-progressbar' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar',
				'file'          => 'Widgets/Progressbar/class-aae-a-progressbar.php',
				'script_handle' => 'aae-a-progressbar-js',
				'script_path'   => '/assets/atomic/js/progressbar.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-progressbar-css',
				'style_path'    => '/assets/atomic/css/progressbar.css',
			],

			'aae-a-progressbar-track' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar_Track',
				'file'       => 'Widgets/Progressbar/Parts/class-aae-a-progressbar-track.php',
				'has_script' => false,
			],
			'aae-a-progressbar-fill' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar_Fill',
				'file'       => 'Widgets/Progressbar/Parts/class-aae-a-progressbar-fill.php',
				'has_script' => false,
			],
			'aae-a-progressbar-label' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar_Label',
				'file'       => 'Widgets/Progressbar/Parts/class-aae-a-progressbar-label.php',
				'has_script' => false,
			],
			'aae-a-progressbar-dot' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar_Dot',
				'file'       => 'Widgets/Progressbar/Parts/class-aae-a-progressbar-dot.php',
				'has_script' => false,
			],

			'aae-a-toggle-switcher' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Switcher',
				'file'          => 'Widgets/ToggleSwitcher/class-aae-a-toggle-switcher.php',
				'script_handle' => 'aae-a-toggle-switcher-js',
				'script_path'   => '/assets/atomic/js/toggle-switcher.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-toggle-switcher-css',
				'style_path'    => '/assets/atomic/css/toggle-switcher.css',
			],

			'aae-a-toggle-pane' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Pane',
				'file'       => 'Widgets/ToggleSwitcher/class-aae-a-toggle-pane.php',
				'has_script' => false,
			],

			'aae-a-toggle-switcher-tabs' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Switcher_Tabs',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-switcher-tabs.php',
				'has_script' => false,
			],
			'aae-a-toggle-switcher-tab' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Switcher_Tab',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-switcher-tab.php',
				'has_script' => false,
			],
			'aae-a-toggle-pane-title' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Pane_Title',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-pane-title.php',
				'has_script' => false,
			],
			'aae-a-toggle-pane-desc' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ToggleSwitcher\AAE_A_Toggle_Pane_Desc',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-pane-desc.php',
				'has_script' => false,
			],

			'aae-a-offcanvas' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas',
				'file'          => 'Widgets/Offcanvas/class-aae-a-offcanvas.php',
				'script_handle' => 'aae-a-offcanvas-js',
				'script_path'   => '/assets/atomic/js/offcanvas.js',
				'has_script'    => true,
				// GSAP powers the open/close animations, but the `gsap` handle is
				// registered only by the Pro plugin. Depend on it ONLY when Pro is
				// present (an unregistered dep would silently block the script);
				// the runtime falls back to a CSS slide when GSAP is absent.
				'script_deps'   => defined( 'WCF_ADDONS_PRO_VERSION' ) ? [ 'gsap' ] : [],
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
			'aae-a-offcanvas-overlay' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Offcanvas\AAE_A_Offcanvas_Overlay',
				'file'       => 'Widgets/Offcanvas/class-aae-a-offcanvas-overlay.php',
				'has_script' => false,
			],

			'aae-a-form' => [
				'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form',
				'file'          => 'Widgets/Form/class-aae-a-form.php',
				'script_handle' => 'aae-a-form-js',
				'script_path'   => '/assets/atomic/js/form.js',
				// Multi-Step step-transition animations (lib/multi-step.js)
				// are plain CSS transform/opacity transitions — no JS
				// tweening library dependency needed.
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

			'aae-a-form-field-error' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Field_Error',
				'file'       => 'Widgets/Form/class-aae-a-form-field-error.php',
				'has_script' => false,
			],

			'aae-a-form-file' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_File',
				'file'       => 'Widgets/Form/class-aae-a-form-file.php',
				'has_script' => false,
			],

			'aae-a-form-rating' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Rating',
				'file'       => 'Widgets/Form/class-aae-a-form-rating.php',
				'has_script' => false, // ships inside aae-a-form-js itself (lib/rating.js).
			],

			'aae-a-form-range' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Range',
				'file'       => 'Widgets/Form/class-aae-a-form-range.php',
				'has_script' => false, // ships inside aae-a-form-js itself (lib/range.js).
			],

			'aae-a-form-country' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Country',
				'file'       => 'Widgets/Form/class-aae-a-form-country.php',
				'has_script' => false, // native single <select>; no JS needed.
			],

			'aae-a-form-calculation' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Calculation',
				'file'       => 'Widgets/Form/class-aae-a-form-calculation.php',
				'has_script' => false, // ships inside aae-a-form-js itself (lib/calculation.js).
			],

			'aae-a-form-password' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Password',
				'file'       => 'Widgets/Form/class-aae-a-form-password.php',
				'has_script' => false, // reveal toggle ships inside aae-a-form-js (lib/password.js).
			],

			'aae-a-form-step' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Step',
				'file'       => 'Widgets/Form/class-aae-a-form-step.php',
				'has_script' => false, // step-nav logic ships inside aae-a-form-js itself.
			],

			'aae-a-form-next' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Next',
				'file'       => 'Widgets/Form/class-aae-a-form-next.php',
				'has_script' => false, // click handler ships inside aae-a-form-js itself.
			],

			'aae-a-form-prev' => [
				'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\Form\AAE_A_Form_Prev',
				'file'       => 'Widgets/Form/class-aae-a-form-prev.php',
				'has_script' => false, // click handler ships inside aae-a-form-js itself.
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
			// SCSS-only widget (the flip animation is entirely CSS-driven) —
			// compiled by gulp's compile:atomic-scss task, not webpack.
			'has_script'    => false,
			'style_handle'  => 'aae-a-flip-box-css',
			'style_path'    => '/assets/atomic/css/flip-box.css',
		],

		'aae-a-image-hotspot' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Image_Hotspot',
			'file'          => 'Widgets/ImageHotspot/class-aae-a-image-hotspot.php',
			'script_handle' => 'aae-a-image-hotspot-js',
			'script_path'   => '/assets/atomic/js/image-hotspot.js',
			'has_script'    => true,
			'style_handle'  => 'aae-a-image-hotspot-css',
			'style_path'    => '/assets/atomic/css/image-hotspot.css',
		],

		'aae-a-hotspot-point' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Point',
			'file'       => 'Widgets/ImageHotspot/class-aae-a-hotspot-point.php',
			'has_script' => false,
		],

		'aae-a-hotspot-marker' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Marker',
			'file'       => 'Widgets/ImageHotspot/Parts/class-aae-a-hotspot-marker.php',
			'has_script' => false,
		],

		'aae-a-hotspot-content' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Content',
			'file'       => 'Widgets/ImageHotspot/class-aae-a-hotspot-content.php',
			'has_script' => false,
		],

		'aae-a-hotspot-lightbox' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Lightbox',
			'file'       => 'Widgets/ImageHotspot/Parts/class-aae-a-hotspot-lightbox.php',
			'has_script' => false,
		],

		'aae-a-hotspot-close' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\ImageHotspot\AAE_A_Hotspot_Close',
			'file'       => 'Widgets/ImageHotspot/Parts/class-aae-a-hotspot-close.php',
			'has_script' => false,
		],

		'aae-a-flip-box-front' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\FlipBox\AAE_A_Flip_Box_Front',
			'file'       => 'Widgets/FlipBox/Parts/class-aae-a-flip-box-front.php',
			'has_script' => false,
		],

		'aae-a-flip-box-back' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\FlipBox\AAE_A_Flip_Box_Back',
			'file'       => 'Widgets/FlipBox/Parts/class-aae-a-flip-box-back.php',
			'has_script' => false,
		],

		'aae-a-flip-box-title' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\FlipBox\AAE_A_Flip_Box_Title',
			'file'       => 'Widgets/FlipBox/Parts/class-aae-a-flip-box-title.php',
			'has_script' => false,
		],

		'aae-a-flip-box-text' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\FlipBox\AAE_A_Flip_Box_Text',
			'file'       => 'Widgets/FlipBox/Parts/class-aae-a-flip-box-text.php',
			'has_script' => false,
		],

		// No style_handle/style_path on purpose: this widget's ~140 bytes of CSS
		// is emitted inline by its twig (guarded so it prints once per request)
		// rather than costing a separate HTTP request. There is no
		// Widgets/SiteLogo/assets/scss for it either — the twig is the source of
		// truth. Don't "restore" the handle without also deleting the twig block.
		'aae-a-site-logo' => [
			'class'        => '\WCF_ADDONS\AtomicWidgets\Widgets\SiteLogo\AAE_A_Site_Logo',
			'file'         => 'Widgets/SiteLogo/class-aae-a-site-logo.php',
			'has_script'   => false,
		],

		'aae-a-video-mask' => [
			'class'         => '\WCF_ADDONS\AtomicWidgets\Widgets\VideoMask\AAE_A_Video_Mask',
			'file'          => 'Widgets/VideoMask/class-aae-a-video-mask.php',
			'script_handle' => 'aae-a-video-mask-js',
			'script_path'   => '/assets/atomic/js/video-mask.js',
			'has_script'    => true,
			'style_handle'  => 'aae-a-video-mask-css',
			'style_path'    => '/assets/atomic/css/video-mask.css',
		],

		'aae-a-video-mask-btn' => [
			'class'      => '\WCF_ADDONS\AtomicWidgets\Widgets\VideoMask\AAE_A_Video_Mask_Btn',
			'file'       => 'Widgets/VideoMask/class-aae-a-video-mask-btn.php',
			'has_script' => false,
		],

		// Add new atomic widgets below...
		];

		$widgets = self::drop_widgets_owned_by_pro($widgets);

		/**
		 * The class/asset registry for every atomic widget.
		 *
		 * Elementor exposes no registry a second plugin can add an atomic element
		 * TYPE to, so a Pro-owned atomic widget has to arrive through here. An
		 * entry may carry its own `base_path` / `base_url` (absolute filesystem
		 * path and URL, both ending in a slash) when its files live outside this
		 * plugin; `asset_url()` and the script/style registrars fall back to
		 * WCF_ADDONS_PATH / WCF_ADDONS_URL when they are absent, so every existing
		 * entry keeps working untouched.
		 *
		 * A slug added here still needs its dashboard card via
		 * `aae/atomic/widgets_registry`, or nothing can switch it on.
		 *
		 * @param array<string,array> $widgets
		 */
		return (array) apply_filters('aae/atomic/available_widgets', $widgets);
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
	 * Invalidate AAE Post Pagination's cached ordered-id lists for a post type the
	 * moment its content changes (save/trash/delete), rather than relying on
	 * the transient TTL alone. Cheap no-op for post types that never used the
	 * widget (bumping a version nobody reads costs nothing).
	 */
	public function bump_post_pagination_cache_version($post_id): void {
		$post_type = get_post_type($post_id);
		if (! $post_type || ! class_exists('\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination')) {
			return;
		}
		\WCF_ADDONS\AtomicWidgets\Widgets\PostPagination\AAE_A_Post_Pagination::bump_cache_version($post_type);
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
	 * Force our atomic widget stylesheets to print AFTER Elementor's cached
	 * atomic base-styles file on the frontend.
	 *
	 * WHY THIS EXISTS:
	 * Elementor merges EVERY registered atomic element's define_base_styles()
	 * into one cached file (`base-desktop.css`), ordered by element
	 * registration — and its own native elements (e-svg, e-heading, …)
	 * register after ours. So when one of our named base-style classes (e.g.
	 * AAE_A_Btn's `e-aae-a-btn-icon`, 30px) collides with a native default
	 * sharing the exact same selector shape (`.elementor .<class>`, hence the
	 * same specificity — e.g. `e-svg-base`'s 65px), the native rule lands
	 * LATER in that single file and wins the tie on the frontend, even though
	 * the builder recomputes styles live per request and shows the correct
	 * value. Confirmed on a real page: `aae-a-btn-css`'s <link> already prints
	 * BEFORE `base-desktop-css`'s in <head>, so a same-specificity override in
	 * our own stylesheet loses regardless of what it says.
	 *
	 * Deliberately NOT !important and NOT extra selector specificity — either
	 * would ALSO out-rank a future per-element LOCAL style override of the
	 * same property, since Elementor compiles every local override to the
	 * identical selector shape/specificity too. Depending on `base-desktop`
	 * only changes ORDER: Elementor always enqueues local per-element
	 * overrides even later than `base-desktop` (Atomic_Widget_Styles' 'local'
	 * style key registers at priority 30 vs Atomic_Widget_Base_Styles' 'base'
	 * key at priority 10, both on the same `elementor/atomic-widgets/styles/
	 * register` action), so a real customization still wins.
	 *
	 * Mirrors fix_preview_css_order() above: patch wp_styles()->registered
	 * deps directly at wp_print_styles (priority 0, the last safe moment
	 * before anything is echoed) rather than declaring the dependency at
	 * registration time, since our style handles are registered/enqueued
	 * before Elementor's own `base-desktop` handle even exists.
	 */
	public function fix_frontend_atomic_css_order(): void {
		if ( ! wp_style_is( 'base-desktop', 'registered' ) ) {
			return;
		}

		$styles = wp_styles();

		foreach ( $this->get_available_widgets() as $widget_data ) {
			if ( empty( $widget_data['style_handle'] ) ) {
				continue;
			}

			$handle = $widget_data['style_handle'];

			if ( ! isset( $styles->registered[ $handle ] ) ) {
				continue;
			}

			$style = $styles->registered[ $handle ];

			if ( ! in_array( 'base-desktop', $style->deps, true ) ) {
				$style->deps[] = 'base-desktop';
			}

			// Opt-in: a widget class may expose get_frontend_css_override() to
			// inject a small inline CSS block (e.g. pinning a named base-style
			// class's size against a native Elementor default sharing the same
			// selector specificity) that must load after base-desktop.css.
			// wp_add_inline_style() attaches directly after this handle's own
			// <link>, and we've just guaranteed this handle loads after
			// base-desktop above, so the inline block does too. The widget
			// class is the single source of truth for the value — nothing is
			// hardcoded here.
			$class = $widget_data['class'] ?? null;
			if ( $class && is_callable( [ $class, 'get_frontend_css_override' ] ) ) {
				$css = $class::get_frontend_css_override();
				if ( '' !== $css ) {
					wp_add_inline_style( $handle, $css );
				}
			}
		}
	}

	/**
	 * AAE's panel categories, in the order they should appear at the TOP of the
	 * Elements panel.
	 *
	 * Elementor gives no way to control this from the registration side:
	 * `init_categories()` builds its own list first and only then fires
	 * `elementor/elements/categories_registered`, and `add_category()` can only
	 * APPEND (`$categories` is private, there is no setter, `get_categories()`
	 * has no filter, and Elementor's own `promote_category_after()` is private).
	 * So every third-party category lands dead last.
	 *
	 * The order is therefore re-imposed on the editor CONFIG instead — see
	 * promote_panel_categories() for the PHP half and
	 * enqueue_atomic_editor_scripts() for the JS half. This constant is the
	 * single source of truth for both.
	 *
	 * Membership here is by SLUG only — a category does not have to be one of
	 * ours from register_atomic_categories() to be listed. `wcf-hf-addon` is
	 * registered on the v3 side (class-plugin.php::widget_categories()) and
	 * holds both v3 widgets (Nav Menu) and atomic ones (Nav, WP Menu, Offcanvas,
	 * Search Form), so it belongs in the promoted block with the rest of the AAE
	 * groups rather than stranded below Layout/Basic where v3's own append order
	 * leaves it. Slugs that are not present in a given editor's map are simply
	 * skipped by both halves, so listing one costs nothing when it is empty.
	 */
	private const PANEL_CATEGORY_ORDER = [
		'aae-atomic-general',
		'aae-atomic-form',
		'aae-atomic-post',
		'wcf-hf-addon',
	];

	/**
	 * Register AAE's own panel categories for atomic widgets. Each atomic
	 * widget class returns one of these slugs from its own override (added
	 * per-widget — the base defaults are plain, non-abstract methods, not
	 * something a central hook can redirect).
	 *
	 * WHICH METHOD TO OVERRIDE depends on the base class — getting this wrong
	 * fails SILENTLY, the widget just shows up under Elementor's own "Atomic
	 * Elements" instead:
	 *   - Atomic_Widget_Base  (leaf)      → public function get_categories(): array
	 *   - Atomic_Element_Base (container) → protected function define_panel_categories(): array
	 * Element_Base has no get_categories() at all; Atomic_Element_Base's
	 * get_initial_config() sets $config['categories'] from
	 * define_panel_categories(), so a get_categories() on a container element
	 * is dead code. Our container classes define both, the latter delegating
	 * to the former, so the two can't drift.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_atomic_categories($elements_manager): void
	{
		$elements_manager->add_category('aae-atomic-general', [
			'title' => esc_html__('AAE General', 'animation-addons-for-elementor'),
			'icon'  => 'fa fa-plug',
		]);

		$elements_manager->add_category('aae-atomic-form', [
			'title' => esc_html__('AAE Form', 'animation-addons-for-elementor'),
			'icon'  => 'fa fa-plug',
		]);

		$elements_manager->add_category('aae-atomic-post', [
			'title' => esc_html__('AAE Post', 'animation-addons-for-elementor'),
			'icon'  => 'fa fa-plug',
		]);
	}

	/**
	 * Move the AAE categories to the front of a panel-categories map.
	 *
	 * Panel order is NOTHING BUT the key order of this array — Elementor's
	 * editor iterates it with `_.each` in initCategoriesCollection() and adds
	 * each key to the collection in encounter order, with no sort afterwards.
	 * So re-keying the array in the right order IS the feature.
	 *
	 * `favorites` is deliberately kept pinned above ours when present: it is a
	 * user-pinning surface, not a vendor category.
	 *
	 * Uses array_merge rather than `+` — array_merge preserves insertion order
	 * for string keys and does not renumber them, whereas `+` would keep the
	 * left operand's value on a key collision.
	 *
	 * @param array $categories slug => category config.
	 *
	 * @return array
	 */
	private static function reorder_panel_categories(array $categories): array
	{
		$ours = [];

		foreach (self::PANEL_CATEGORY_ORDER as $slug) {
			if (isset($categories[$slug])) {
				$ours[$slug] = $categories[$slug];
				unset($categories[$slug]);
			}
		}

		// Every AAE widget disabled (categories are hideIfEmpty, so Elementor
		// never registered ours) — leave core's order exactly as it was.
		if (empty($ours)) {
			return $categories;
		}

		$head = [];

		if (isset($categories['favorites'])) {
			$head['favorites'] = $categories['favorites'];
			unset($categories['favorites']);
		}

		return array_merge($head, $ours, $categories);
	}

	/**
	 * Promote the AAE categories to the top of the Elements panel.
	 *
	 * `elementor/editor/localize_settings` is the ONLY server-side seam that can
	 * do this. The obvious-looking `elementor/document/config` cannot: its return
	 * value is applied with array_replace_recursive(), which preserves the
	 * ORIGINAL array's key order for keys present in both — so handing it a
	 * reordered map silently changes nothing.
	 *
	 * This covers the initial editor load only. Two client-side paths replace
	 * the whole map afterwards and are re-ordered by the inline script in
	 * enqueue_atomic_editor_scripts(): Elementor's `refreshWidgets()` (which
	 * assigns straight from the unfiltered `refresh_widgets_config` AJAX) and
	 * switching documents (which loads a fresh Document::get_config()).
	 *
	 * @param array $settings Editor client env.
	 *
	 * @return array
	 */
	public function promote_panel_categories($settings)
	{
		if (! is_array($settings)) {
			return $settings;
		}

		if (isset($settings['initial_document']['panel']['elements_categories'])
			&& is_array($settings['initial_document']['panel']['elements_categories'])
		) {
			$settings['initial_document']['panel']['elements_categories'] =
				self::reorder_panel_categories($settings['initial_document']['panel']['elements_categories']);
		}

		return $settings;
	}

	/**
	 * Register active atomic widgets with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets($widgets_manager)
	{
		foreach ($this->resolve_registerable_classes()['widgets'] as $class) {
			$widgets_manager->register(new $class());
		}
	}

	/**
	 * Classify every active atomic slug as a WIDGET or an ELEMENT, once.
	 *
	 * register_widgets() and register_elements() ran byte-identical loops and
	 * differed only in the polarity of the is_subclass_of() test and which
	 * manager received the instance. Both fire on a rendered page, so the work
	 * was done twice: two registry reads, two is_widget_active() passes over
	 * ~135 slugs, and ~270 file_exists calls.
	 *
	 * The predicate chain below is the SAME chain, in the SAME order, with the
	 * SAME short-circuits, so the set of registered types is unchanged — which
	 * matters more than the speed: CLAUDE.md's DANGER box notes that failing to
	 * register a widget renders NOTHING on pages already using it, with no error.
	 *
	 * Note is_subclass_of() is called on a class-name STRING, so it does not
	 * construct anything; each class is instantiated exactly once, by whichever
	 * registrar owns it.
	 *
	 * @return array{widgets: array<string,string>, elements: array<string,string>}
	 */
	private function resolve_registerable_classes(): array
	{
		if (null !== $this->registerable_classes) {
			return $this->registerable_classes;
		}

		$buckets = ['widgets' => [], 'elements' => []];

		foreach ($this->get_available_widgets() as $widget_id => $widget_data) {
			if (! $this->is_widget_active($widget_id)) {
				continue;
			}

			$file_path = self::widget_class_file($widget_data);
			if (! file_exists($file_path)) {
				continue; // Skip missing widget files gracefully.
			}

			require_once $file_path;

			if (! class_exists($widget_data['class'])) {
				continue;
			}

			$bucket = is_subclass_of($widget_data['class'], '\Elementor\Widget_Base') ? 'widgets' : 'elements';

			$buckets[$bucket][$widget_id] = $widget_data['class'];
		}

		$this->registerable_classes = $buckets;

		return $buckets;
	}

	/**
	 * Register active atomic elements (containers) with Elementor.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_elements($elements_manager)
	{
		foreach ($this->resolve_registerable_classes()['elements'] as $class) {
			$elements_manager->register_element_type(new $class());
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

	/**
	 * Build a plugin asset URL from a registry-relative path.
	 *
	 * WCF_ADDONS_URL comes from plugin_dir_url(), which always ends in a slash,
	 * while every `script_path` / `style_path` in the widget registry is written
	 * with a leading slash. Concatenating them raw yields
	 * `.../animation-addons-for-elementor//assets/...` — the browser treats that
	 * as a different URL from the single-slash form, so a file enqueued both
	 * ways is fetched (and cached) twice. Normalise here rather than editing all
	 * 50 registry entries, so new entries can keep either spelling safely.
	 */
	private static function asset_url(string $relative_path, ?string $base_url = null): string
	{
		return ($base_url ?? WCF_ADDONS_URL) . ltrim($relative_path, '/');
	}

	/**
	 * Where a registry entry's files live.
	 *
	 * Entries added through `aae/atomic/available_widgets` may sit in another
	 * plugin, so they carry their own `base_path` / `base_url` (both ending in a
	 * slash). Everything shipped by this plugin omits them and keeps the
	 * original constants — so no existing entry had to be touched.
	 *
	 * @param array $widget_data One `get_available_widgets()` entry.
	 */
	private static function widget_base_path(array $widget_data): string
	{
		return ! empty($widget_data['base_path']) ? $widget_data['base_path'] : WCF_ADDONS_PATH;
	}

	private static function widget_base_url(array $widget_data): string
	{
		return ! empty($widget_data['base_url']) ? $widget_data['base_url'] : WCF_ADDONS_URL;
	}

	/**
	 * Absolute path to a registry entry's class file.
	 *
	 * `file` is normally relative to this directory. An entry from another
	 * plugin gives an absolute path instead, which is passed through untouched.
	 * Both callers already skip a path that does not exist, so a Pro entry left
	 * behind by a partial deploy costs that widget, not the request.
	 */
	/**
	 * Atomic widgets that moved to the Pro plugin, and the Pro release that took
	 * them. Free keeps its own copies for ONE release as a transitional
	 * fallback: an atomic element type that nothing registers is not merely
	 * invisible, Elementor DROPS it from `_elementor_data` on the next save
	 * (get_elements_raw_data(), elementor/core/base/document.php:1111), so a
	 * version-skew window with no registrar would destroy customers' pages.
	 *
	 * Delete these entries, this method and the widget folders in the follow-up
	 * release, once Pro 4.2.0 is the floor.
	 */
	const PRO_OWNS_WIDGETS_FROM = '4.2.0';

	const WIDGETS_MOVED_TO_PRO = [
		'aae-a-counter',
		'aae-a-draw-svg',
		'aae-a-stack-cards',
		'aae-a-stack-card',
		'aae-a-btn-pro',
		'aae-a-offcanvas',
		'aae-a-offcanvas-panel',
		'aae-a-offcanvas-trigger',
		'aae-a-offcanvas-close',
		'aae-a-offcanvas-overlay',
		'aae-a-nav',
		'aae-a-nav-item',
		'aae-a-nav-sub-item',
		'aae-a-mobile-nav',
		'aae-a-toc',
	];

	/**
	 * True once the installed Pro is new enough to register the moved widgets
	 * itself.
	 *
	 * Two conditions, and the LICENCE half is the one that matters.
	 *
	 * The atomic EXTENSIONS deliberately guard on Pro's version alone: an
	 * unlicensed Pro must not make free resume rendering a paid effect. Widgets
	 * cannot use that rule. WCF_ADDONS_PRO_VERSION is defined at Pro's file
	 * scope, BEFORE its licence gate, while Pro only registers these widgets
	 * when the licence is valid — so version-only would leave an expired site
	 * with NOBODY registering them, and an unregistered atomic element type is
	 * deleted from `_elementor_data` on the next save of any page using it
	 * (get_elements_raw_data(), elementor/core/base/document.php:1111).
	 *
	 * A lapsed customer keeping these widgets alive is a revenue leak. A lapsed
	 * customer's pages silently losing their content is not recoverable. So free
	 * stands down only when Pro will actually take over.
	 *
	 * Still not `class_exists` on a Pro widget: Pro loads its classes on
	 * `elementor/init`, long after this runs, so that check would read false
	 * even on a perfectly licensed site.
	 */
	public static function pro_owns_widgets(): bool
	{
		if (! defined('WCF_ADDONS_PRO_VERSION')
			|| version_compare(WCF_ADDONS_PRO_VERSION, self::PRO_OWNS_WIDGETS_FROM, '<')) {
			return false;
		}

		// Same gate Pro puts on its own include_files(); absent means a Pro too
		// old to have the function, which the version check already excluded.
		return function_exists('wcf__addons__pro__status') && (bool) wcf__addons__pro__status();
	}

	/**
	 * @param array<string,array> $widgets
	 * @return array<string,array>
	 */
	private static function drop_widgets_owned_by_pro(array $widgets): array
	{
		if (! self::pro_owns_widgets()) {
			return $widgets;
		}

		foreach (self::WIDGETS_MOVED_TO_PRO as $slug) {
			unset($widgets[$slug]);
		}

		return $widgets;
	}

	private static function widget_class_file(array $widget_data): string
	{
		$file = $widget_data['file'] ?? '';

		if ('' === $file) {
			return '';
		}

		if (path_is_absolute($file)) {
			return wp_normalize_path($file);
		}

		return wp_normalize_path(__DIR__ . '/' . $file);
	}

	/**
	 * Resolve an atomic asset's served path and cache-busting version, once.
	 *
	 * Both registrars did the same two things per handle: probe for a `.min`
	 * sibling (production only) and stat the file for its mtime. That is up to
	 * three filesystem calls per handle, and register_atomic_styles() alone is
	 * invoked from four places, so the same stats were repeated several times a
	 * request.
	 *
	 * The version source is deliberately still filemtime, not WCF_ADDONS_VERSION:
	 * switching it would change cache-busting behaviour for existing sites
	 * mid-release, which is a user-visible change, not an optimisation.
	 *
	 * @param array  $widget_data Registry entry.
	 * @param string $kind        'script' or 'style'.
	 * @return array{path: string, version: int|string}
	 */
	private function resolve_asset_meta(array $widget_data, string $kind): array
	{
		static $cache = [];

		$base = self::widget_base_path($widget_data);
		$raw  = (string) ($widget_data[$kind . '_path'] ?? '');
		$key  = $base . '|' . $raw;

		if (isset($cache[$key])) {
			return $cache[$key];
		}

		$path = $raw;

		if (! $this->is_dev_environment()) {
			$ext = 'script' === $kind ? '.js' : '.css';
			$min = str_replace($ext, '.min' . $ext, $path);
			if (file_exists($base . $min)) {
				$path = $min;
			}
		}

		$file_path = $base . $path;

		return $cache[$key] = [
			'path'    => $path,
			'version' => file_exists($file_path) ? filemtime($file_path) : WCF_ADDONS_VERSION,
		];
	}

	public function register_atomic_scripts($loader)
	{

		foreach ($this->get_available_widgets() as $widget_id => $widget_data) {
			if ($this->is_widget_active($widget_id) && !empty($widget_data['has_script'])) {
				$asset   = $this->resolve_asset_meta($widget_data, 'script');
				$path    = $asset['path'];
				$version = $asset['version'];

				$deps = [ 'elementor-v2-frontend-handlers' ]; // Required for @elementor/frontend-handlers register API
				if ( ! empty( $widget_data['script_deps'] ) ) {
					$deps = array_merge( $deps, (array) $widget_data['script_deps'] );
				}
				wp_register_script(
					$widget_data['script_handle'],
					self::asset_url($path, self::widget_base_url($widget_data)),
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

		// This fires once per RENDERED ELEMENT. It used to walk the whole
		// registry looking for one string, so a 200-element page did ~200 × 135
		// comparisons on top of 200 registry builds. An element-type-keyed map
		// makes it a single hash lookup, and $enqueued collapses the repeat work
		// for pages that use the same widget many times (wp_enqueue_* is
		// idempotent, so skipping a repeat cannot change the output).
		static $enqueued = [];

		if (isset($enqueued[$element_type])) {
			return;
		}

		$map = $this->widget_assets_by_element_type();

		if (! isset($map[$element_type])) {
			return;
		}

		$data = $map[$element_type];

		if (! empty($data['has_script'])) {
			wp_enqueue_script($data['script_handle']);
		}
		if (! empty($data['style_handle'])) {
			wp_enqueue_style($data['style_handle']);
		}

		$enqueued[$element_type] = true;
	}

	/**
	 * The registry re-keyed by element type (`e-<slug>`) instead of slug.
	 *
	 * Built from the memoised registry, so it inherits its invalidation: when the
	 * `aae/atomic/available_widgets` callback set changes the registry is rebuilt
	 * and this map is rebuilt with it.
	 *
	 * @return array<string,array>
	 */
	private function widget_assets_by_element_type(): array
	{
		static $map = null;
		static $signature = null;

		$current = $this->filter_signature('aae/atomic/available_widgets');

		if (null !== $map && $current === $signature) {
			return $map;
		}

		$map = [];

		foreach ($this->get_available_widgets() as $slug => $data) {
			// Slugs are unique keys, so re-keying preserves the old loop's
			// first-match-wins semantics exactly.
			$map['e-' . $slug] = $data;
		}

		$signature = $current;

		return $map;
	}

	/**
	 * Enqueue the atomic assets used by ONE specific document, up front.
	 *
	 * WHY THIS EXISTS:
	 * maybe_enqueue_widget_script() enqueues a widget's handles while the element
	 * renders, which is correct for main-loop content — that render happens inside
	 * wp_head()'s window. It is NOT correct for a document rendered outside the
	 * main loop, such as a theme-builder header: templates/header.php calls
	 * wp_head() first and renders the header afterwards, so anything enqueued at
	 * render time misses <head> and gets flushed by print_late_styles() at
	 * wp_footer — the header paints unstyled first.
	 *
	 * Reading the document's element types up front lets the caller enqueue just
	 * that document's handles during wp_enqueue_scripts. Only the widgets the
	 * document actually contains are touched, unlike the editor-preview path which
	 * blanket-enqueues everything.
	 *
	 * @param int $post_id Elementor document whose assets should be enqueued.
	 */
	public function enqueue_document_widget_assets($post_id): void
	{
		$post_id = (int) $post_id;

		if (! $post_id) {
			return;
		}

		$data = get_post_meta($post_id, '_elementor_data', true);

		if (empty($data) || ! is_string($data)) {
			return;
		}

		// Element types are stored as "widgetType":"e-…" (widgets) and
		// "elType":"e-…" (atomic elements such as e-flexbox).
		if (! preg_match_all('/"(?:widgetType|elType)":"(e-[^"]+)"/', $data, $matches)) {
			return;
		}

		$element_types = array_unique($matches[1]);

		// The handles have to exist before they can be enqueued; on the frontend
		// nothing else registers them for a non-main-loop document.
		$this->register_atomic_styles();

		foreach ($this->get_available_widgets() as $slug => $widget_data) {
			if (! in_array('e-' . $slug, $element_types, true)) {
				continue;
			}

			if (! $this->is_widget_active($slug)) {
				continue;
			}

			if (! empty($widget_data['style_handle'])) {
				wp_enqueue_style($widget_data['style_handle']);
			}

			if (! empty($widget_data['has_script']) && ! empty($widget_data['script_handle'])) {
				wp_enqueue_script($widget_data['script_handle']);
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
						self::asset_url( $path ),
						[ 'elementor-v2-frontend-handlers' ],
						$version,
						true
					);
				}

				wp_enqueue_script( $widget_data['script_handle'] );

				// The Menu widget builds its markup with wp_nav_menu() in PHP
				// (get_atomic_settings), so the client-rendered canvas has no menu
				// HTML and menu.js fetches it over admin-ajax instead.
				//
				// The URL has to come from admin_url(). On a subdirectory MULTISITE
				// a subsite's admin lives at /<site>/wp-admin/, so the root-relative
				// '/wp-admin/admin-ajax.php' the script fell back to resolved to the
				// NETWORK MAIN SITE — where the subsite's menu slug does not exist,
				// so wp_nav_menu() returned nothing, the response carried no markup
				// and the editor placeholder never went away. It only ever looked
				// correct on a single site installed at the domain root.
				if ( 'aae-a-menu' === $widget_id ) {
					wp_localize_script(
						$widget_data['script_handle'],
						'AAE_MENU_CFG',
						[ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ]
					);
				}
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
						self::asset_url( $style_path ),
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
				$asset   = $this->resolve_asset_meta($widget_data, 'style');
				$path    = $asset['path'];
				$version = $asset['version'];
				wp_register_style(
					$widget_data['style_handle'],
					self::asset_url($path, self::widget_base_url($widget_data)),
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
		wp_register_style( $handle, self::asset_url( $path ), [], $version );
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

	/**
	 * Public accessor for is_dev_environment() — used by the remote preset
	 * system (Atomic\Presets\Cache) to decide whether to bypass its cache
	 * and always fetch fresh from the remote server.
	 *
	 * @return bool
	 */
	public function is_dev_environment_public(): bool
	{
		return $this->is_dev_environment();
	}

	/**
	 * Public accessor for get_available_widgets() — used by
	 * Atomic\Presets\Local_Fallback to walk each widget's presets/ folder
	 * without duplicating this plugin's widget registry.
	 *
	 * @return array
	 */
	public function get_available_widgets_public(): array
	{
		return $this->get_available_widgets();
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

			if (isset($this->get_widgets_registry()[$slug]) && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		$updated = update_option(self::OPTION_NAME, $clean);

		// Reset cache.
		$this->active_widgets = null;
		// Both are derived from the active set.
		$this->registerable_classes = null;
		$this->widget_active_cache  = [];

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
	 * AJAX handler — return every registered WordPress menu together with its
	 * items pre-assembled into a nested tree. The AAE Nav panel's import control
	 * consumes this to build atomic nav-items (with dropdowns) that mirror the
	 * WP menu hierarchy. Reuses the `aae_loop_grid` editor nonce.
	 */
	public function ajax_get_nav_menus(): void
	{
		check_ajax_referer('aae_loop_grid', 'nonce');

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$menus = wp_get_nav_menus();
		$out   = [];

		if (! is_wp_error($menus)) {
			foreach ($menus as $menu) {
				$items = wp_get_nav_menu_items($menu->term_id);
				$out[] = [
					'id'    => (int) $menu->term_id,
					'name'  => $menu->name,
					'items' => self::build_nav_menu_tree(is_array($items) ? $items : []),
				];
			}
		}

		wp_send_json_success(['menus' => $out]);
	}

	/**
	 * Turn WordPress's flat, menu_order-sorted item list into a nested tree
	 * keyed by parent. Each node exposes only what the editor needs to build a
	 * nav-item: label, url, target, and its children.
	 *
	 * @param array $items Output of wp_get_nav_menu_items().
	 * @return array Nested nodes: [ [ 'title', 'url', 'target', 'children' ], ... ].
	 */
	public static function build_nav_menu_tree(array $items): array
	{
		$by_parent = [];
		foreach ($items as $item) {
			$by_parent[(int) $item->menu_item_parent][] = $item;
		}

		$build = function ($parent_id) use (&$build, $by_parent) {
			$nodes = [];
			foreach ($by_parent[$parent_id] ?? [] as $item) {
				$nodes[] = [
					'id'       => (int) $item->ID,
					'title'    => wp_strip_all_tags($item->title),
					'url'      => esc_url_raw($item->url),
					'target'   => ('_blank' === $item->target) ? '_blank' : '',
					'children' => $build((int) $item->ID),
				];
			}
			return $nodes;
		};

		return $build(0);
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

		// Record what the user was just shown. Without this the setup wizard's
		// choice is silently overruled a moment later: migrate_newly_offered_extensions()
		// bails only while the settings option is ABSENT ("fresh install, the
		// wizard decides"), and the wizard's own save is what ends that. On the
		// next admin_init the offered list is still missing, so it falls back to
		// LEGACY_OFFERED_EXTENSIONS and every extension added since that
		// baseline counts as newly-offered — switching six Pro extensions back
		// on right after someone picked the Basic setup.
		//
		// Writing it here makes "offered" mean what it says: the set the user
		// has actually been presented with. The migration then correctly does
		// nothing until a future plugin update adds an extension neither this
		// save nor the wizard ever displayed.
		update_option(self::EXTENSIONS_OFFERED_OPTION_NAME, array_keys($this->extensions_registry));

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
	 * Enqueue global atomic editor scripts into the top-level window.
	 */
	public function enqueue_atomic_editor_scripts(): void
	{
		// $this->guard_elementor_core_atomic_types();

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

		// NOTE: AAE_PRESET_CONFIG is NOT localized here. PresetPickerControl.jsx
		// (which reads window.AAE_PRESET_CONFIG) ships inside the
		// 'aae-atomic-common-editor-bridge' bundle (built from
		// src/modules/atomic/editor-bridge.js), NOT this 'aae-atomic-editor'
		// handle (built from inc/AtomicWidgets/assets/js/atomic-editor.js —
		// a small, unrelated outer-frame bridge). See Atomic\Assets::
		// enqueue_editor_bridge() for the correct wp_localize_script() call.

		// Loop Grid: ajax config for the editor "full grid live" preview module.
		wp_localize_script(
			'aae-atomic-editor',
			'AAE_LOOP_GRID',
			[
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('aae_loop_grid'),
			]
		);

		// Keep the AAE categories at the top of the Elements panel.
		//
		// promote_panel_categories() orders the config Elementor PRINTS, which
		// covers the initial load. Two client-side paths then replace that map
		// wholesale with PHP's own (appended-last) order and would undo it:
		//
		//   1. elementor.refreshWidgets() assigns
		//      `config.document.panel.elements_categories = data.categories`
		//      straight from the `refresh_widgets_config` AJAX, which has no
		//      filter, then fires `elementor/widgets/refreshed`.
		//   2. Switching documents (Site Settings, a popup/template) assigns
		//      `config.document = config` from a fresh Document::get_config().
		//
		// Re-applying on those two signals is the whole fix. It runs before the
		// panel can read the map: the Elements page builds its categories
		// collection in initialize() → initCategoriesCollection(), which only
		// happens once the panel routes to `panel/elements/categories` — after
		// both `elementor:init` and `document:loaded`.
		//
		// Inline rather than a module under src/, deliberately: no build step,
		// and it stays next to the PHP half it mirrors.
		wp_add_inline_script(
			'aae-atomic-editor',
			sprintf(
				'jQuery( window ).on( "elementor:init", function () {
	var order = %s;

	function promote() {
		var panel = window.elementor && elementor.config && elementor.config.document && elementor.config.document.panel;
		var cats = panel && panel.elements_categories;

		if ( ! cats ) {
			return;
		}

		var out = {};

		// Favorites is a user-pinning surface — it stays above ours.
		if ( cats.favorites ) {
			out.favorites = cats.favorites;
		}

		order.forEach( function ( slug ) {
			if ( cats[ slug ] ) {
				out[ slug ] = cats[ slug ];
			}
		} );

		Object.keys( cats ).forEach( function ( slug ) {
			if ( ! out.hasOwnProperty( slug ) ) {
				out[ slug ] = cats[ slug ];
			}
		} );

		panel.elements_categories = out;
	}

	promote();
	elementor.on( "document:loaded", promote );
	elementor.hooks.addAction( "elementor/widgets/refreshed", promote );
} );',
				wp_json_encode(self::PANEL_CATEGORY_ORDER)
			)
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

}

// Initialize.
Atomic::instance();
