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

namespace Wealcoder\AnimationAddons\AtomicWidgets;

use Wealcoder\AnimationAddons\Nonce;
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
	const OPTION_NAME = 'aaeaddon_atomic_widgets';

	/**
	 * WordPress option name for storing atomic extension states.
	 */
	const EXTENSIONS_OPTION_NAME = 'aaeaddon_atomic_extensions';

	/**
	 * The user's answer to the "Elementor V4 is switched on — want AAE's atomic
	 * set?" notice. See atomic_optin_signal() for the whole feature.
	 *
	 * Shape: `[ 'state' => 'accepted'|'dismissed', 'signal' => 'usage'|'experiment'|'none' ]`.
	 * ABSENT means UNDECIDED, which is not the same as dismissed — the notice
	 * shows for the first and never for the second at the same signal strength.
	 *
	 * `signal` records how strong the evidence was when they answered, so a
	 * dismissal made while V4 was merely switched ON does not also silence the
	 * notice later, once the site actually has V4 elements saved on a page.
	 * That is a different and far more concrete claim, and it earns being made
	 * once. Never the reverse: a dismissal at 'usage' silences 'experiment' too.
	 */
	const OPTIN_OPTION_NAME = 'aaeaddon_atomic_optin';

	/**
	 * Cached answer to "does this site's CONTENT use Elementor V4 elements?".
	 * One hour, negative-only invalidation — see maybe_invalidate_atomic_usage().
	 */
	const USAGE_TRANSIENT = 'aaeaddon_atomic_usage';

	/**
	 * Cached answer to "HOW MANY posts use Elementor V4 elements?".
	 *
	 * Deliberately a SECOND transient rather than widening USAGE_TRANSIENT to
	 * hold the count. The boolean's query stops at the first row (LIMIT 1) and
	 * runs on every undecided site; counting cannot stop early and has to join
	 * the posts table to keep revisions out. Keeping them apart means the
	 * expensive one is only ever paid for when a notice is actually going on
	 * screen -- see atomic_optin_signal(), which asks for it last and only when
	 * the notice will show.
	 */
	const USAGE_COUNT_TRANSIENT = 'aaeaddon_atomic_usage_count';

	/**
	 * The exact atomic state this site had immediately BEFORE it accepted the
	 * opt-in notice — the return path out of an accidental "Enable".
	 *
	 * Shape: `[ widgets, widgets_absent, extensions, extensions_absent, offered,
	 * offered_absent, signal, time ]`. See capture_atomic_undo().
	 *
	 * ABSENT IS NOT EMPTY, and this is the reason each value has a companion
	 * boolean rather than a null. `aaeaddon_atomic_extensions` missing means "fresh
	 * install, the wizard decides" to migrate_newly_offered_extensions(), which
	 * bails only while the option has NEVER been written — restoring an empty
	 * array where there had been no row would end that state permanently and
	 * let the migration switch newly-offered extensions on by itself. An undo
	 * that leaves the site subtly different from where it started is not an undo.
	 */
	const UNDO_OPTION_NAME = 'aaeaddon_atomic_optin_undo';

	/**
	 * How long the undo stays on offer.
	 *
	 * A backstop, not the main rule: the offer is really ended by the user
	 * SAVING either atomic list by hand (see write_widget_option()'s callers),
	 * because from that moment the stored state is their own choice and
	 * restoring a snapshot over it would destroy real work. The window exists
	 * for the site that accepts the offer and then never opens the dashboard
	 * again while quietly building pages with the widgets — after a week,
	 * "switch off the ones you do not want" is the honest answer and a bulk
	 * restore is not.
	 */
	const UNDO_WINDOW = 7 * DAY_IN_SECONDS;

	/**
	 * Slugs that have ever been PRESENTED in the Atomic Extensions dashboard.
	 *
	 * The settings option only records what is enabled, so on its own it cannot
	 * distinguish "shipped after this site was set up" from "the user switched it
	 * off". That ambiguity is why the old default-seeder kept re-enabling things
	 * people had disabled. Tracking what has been offered separates the two.
	 */
	const EXTENSIONS_OFFERED_OPTION_NAME = 'aaeaddon_atomic_extensions_offered';

	/**
	 * Plugin version the newly-offered-extensions migration last completed for.
	 *
	 * Its two sibling migrations (V3_ADMIN_BACKFILL_OPTION_NAME,
	 * FORCED_BACKFILL_OPTION_NAME) have always had a marker; this one did not,
	 * which is why it reached update_option() on EVERY request, front end
	 * included. The version — rather than a plain boolean — is what still lets a
	 * plugin update re-run it when the registry gains an extension.
	 */
	const OFFERED_MIGRATION_OPTION_NAME = 'aaeaddon_atomic_offered_migration';

	/**
	 * Marker for the one-time copy of the v3 admin-feature toggles into the
	 * atomic extension option. See backfill_v3_admin_extensions().
	 */
	const V3_ADMIN_BACKFILL_OPTION_NAME = 'aaeaddon_atomic_v3_admin_backfill';

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
	 * Null until something actually asks — see widgets_registry().
	 *
	 * @var array|null
	 */
	private $widgets_registry = null;

	/**
	 * Registry of available atomic extensions.
	 *
	 * Null until something actually asks — see extensions_registry().
	 *
	 * @var array|null
	 */
	private $extensions_registry = null;

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

		// The two card registries are NOT built here — they are loaded on first
		// ask (widgets_registry() / extensions_registry()). Building them in the
		// constructor meant 100 KB of array literal parsed on every request,
		// including the ones that can never read it.
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

		$registry = (array) apply_filters('aae/atomic/widgets_registry', $this->widgets_registry());

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
	const FORCED_BACKFILL_OPTION_NAME = 'aaeaddon_atomic_widgets_forced_backfill';

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
		'aae-a-toggle-switcher-label'  => 'aae-a-toggle-switcher',
		'aae-a-toggle-switcher-tablist' => 'aae-a-toggle-switcher',
		'aae-a-toggle-switcher-track'  => 'aae-a-toggle-switcher',
		'aae-a-toggle-switcher-knob'   => 'aae-a-toggle-switcher',

		// Video Mask / Flip Box
		'aae-a-video-mask-btn'         => 'aae-a-video-mask',
		'aae-a-video-player'           => 'aae-a-video',
		'aae-a-video-playbtn'          => 'aae-a-video',
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
		// Advanced Portfolio
		'aae-a-portfolio-title'        => 'aae-a-advance-portfolio',
		'aae-a-portfolio-list'         => 'aae-a-advance-portfolio',
		'aae-a-portfolio-item'         => 'aae-a-advance-portfolio',
		'aae-a-portfolio-content'      => 'aae-a-advance-portfolio',
		'aae-a-portfolio-date'         => 'aae-a-advance-portfolio',

		'aae-a-loop-slide-track'       => 'aae-a-loop-grid-slider',
		'aae-a-loop-slide-item'        => 'aae-a-loop-grid-slider',
		'aae-a-loop-slide-pagination'  => 'aae-a-loop-grid-slider',

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
		'aae-a-social-share-item'       => 'aae-a-social-share',
		'aae-a-social-share-item-icon'  => 'aae-a-social-share',
		'aae-a-social-share-item-title' => 'aae-a-social-share',

		// Nav
		'aae-a-nav-item'               => 'aae-a-nav',
		'aae-a-nav-sub-item'           => 'aae-a-nav',
		'aae-a-mobile-nav'             => 'aae-a-nav',

		// Search Form
		'aae-a-search-toggle'          => 'aae-a-search-form',
		'aae-a-search-toggle-open'     => 'aae-a-search-form',
		'aae-a-search-toggle-close'    => 'aae-a-search-form',
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
		'aae-a-form-range-group'       => 'aae-a-form',
		'aae-a-form-range-value'       => 'aae-a-form-range-group',
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
		return $this->extensions_registry();
	}

	/**
	 * Which saved prop proves an extension is used on a page.
	 *
	 * An extension writes nothing of its own into `_elementor_data` — unlike a
	 * widget, which saves its own type there. All it leaves behind is a prop
	 * inside some OTHER element's settings, and the prop name cannot be derived
	 * from the slug: `parallax` writes `aae_plx_*`, `image-hover` writes
	 * `aae_ih_*`, `regular-animation` writes `aae_anim_*`, Pro's `popup` writes
	 * `aae_v4_popup_*`. Nor can it be read off the Schema class by convention —
	 * the constant is called `ENABLE` in some, `PARALLAX_ENABLE` /
	 * `IH_ENABLE` / `STICKY_ENABLE` / `TOOLTIP_ENABLE` in others, and two
	 * extensions have no enable prop at all. So it is DECLARED, here, in the
	 * same array where the extension itself is defined.
	 *
	 * `usage_prop` is REQUIRED on every entry — `false` for the ones that
	 * genuinely have no per-page answer, never simply absent. Absent would mean
	 * both "not countable" and "someone forgot", and the second one fails
	 * silently as a permanent zero.
	 * `E:\Local Testing\verify-extension-usage.php` refuses an entry without it.
	 *
	 * Shape: `array( <prop>|<prop[]>, <kind> )`.
	 *
	 * | kind      | used on page when                                        |
	 * |-----------|----------------------------------------------------------|
	 * | `boolean` | the prop's value is exactly `true`                       |
	 * | `filled`  | the prop holds anything that is not null/''/[]/{}         |
	 * | `present` | the key exists at all (style props, which have no toggle) |
	 *
	 * `boolean` is not "the key is there": both `aae_bgv_enable` and
	 * `aae_v4_popup_enabled` appear on this dev site with `"value":false` as
	 * often as with `true`, because switching a section off leaves the prop
	 * behind. Counting presence would report those pages as users of an
	 * extension they explicitly turned off.
	 *
	 * @return array<string,array{prop:string[],kind:string}> Keyed by slug;
	 *                                                        uncountable
	 *                                                        extensions omitted.
	 */
	public function get_extension_usage_props(): array
	{
		$props = [];

		foreach ($this->extensions_registry() as $slug => $def) {
			if (empty($def['usage_prop'])) {
				continue;
			}

			[$keys, $kind] = $def['usage_prop'];

			$props[$slug] = [
				'prop' => (array) $keys,
				'kind' => $kind,
			];
		}

		return $props;
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

		foreach ($this->extensions_registry() as $slug => $def) {
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

	/**
	 * The widget CARD metadata, loaded on first ask.
	 *
	 * Both registries used to be built in the constructor, which runs on every
	 * request because class-plugin.php calls instance(). Measured, that was
	 * 100 KB of array literal parsed on every front-end, REST and cron request
	 * for an array none of them reads: `is_widget_active()` answers from the
	 * saved option and the parent map, registration reads the SEPARATE
	 * `get_available_widgets()` list, and every reader of this one is an admin
	 * screen, an option writer, the importer or the add-on's usage scan.
	 *
	 * @return array<string,array>
	 */
	private function widgets_registry(): array
	{
		if (null === $this->widgets_registry) {
			$this->widgets_registry = require __DIR__ . '/registry/widgets.php';
		}

		return $this->widgets_registry;
	}

	/**
	 * The extension CARD metadata, loaded on first ask. See widgets_registry().
	 *
	 * @return array<string,array>
	 */
	private function extensions_registry(): array
	{
		if (null === $this->extensions_registry) {
			$this->extensions_registry = require __DIR__ . '/registry/extensions.php';
		}

		return $this->extensions_registry;
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
			// 'aae_save_atomic_widgets' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
			\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_save_atomic_widgets', 'aaeaddon_save_atomic_widgets', [$this, 'ajax_save_settings'] );
			// 'aae_get_atomic_widgets' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
			\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_get_atomic_widgets', 'aaeaddon_get_atomic_widgets', [$this, 'ajax_get_settings'] );
			// 'aae_save_atomic_extensions' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
			\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_save_atomic_extensions', 'aaeaddon_save_atomic_extensions', [$this, 'ajax_save_extension_settings'] );
			// 'aae_get_atomic_extensions' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
			\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_get_atomic_extensions', 'aaeaddon_get_atomic_extensions', [$this, 'ajax_get_extension_settings'] );
			// 'aae_atomic_optin' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
			\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_atomic_optin', 'aaeaddon_atomic_optin', [$this, 'ajax_atomic_optin'] );
			// 'aae_atomic_optin_undo' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
			\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_atomic_optin_undo', 'aaeaddon_atomic_optin_undo', [$this, 'ajax_atomic_optin_undo'] );
		}

		// Let a new page CHANGE the answer to has_atomic_usage(). Registered
		// OUTSIDE the is_admin() block on purpose: a page can be saved over the
		// REST API or by an importer running somewhere that is not wp-admin, and
		// the stale hour that leaves behind is spent showing the dashboard the
		// wrong answer. Exact twin of Animation_Settings::maybe_invalidate_v3_usage().
		add_action('save_post', [__CLASS__, 'maybe_invalidate_atomic_usage'], 10, 2);

		// Import-time data-loss guard, the V4 twin of
		// Animation_Settings::maybe_enable_used_v3_widgets(): switch on every atomic
		// widget/extension the content that just arrived uses. `import_end` is fired
		// once by AaeaddonWXRImporter when a content file has been fully imported; the
		// starter-template step hook is the belt for a run that ends there without
		// a WXR pass. Both are idempotent (one LIKE query, a no-op once enabled).
		add_action('import_end', [$this, 'enable_used_atomic_after_import']);
		add_action('aaeaddon/starter-template/import/step/metasettings', [$this, 'enable_used_atomic_after_import']);

		add_action('elementor/widgets/register', [$this, 'register_widgets']);
		add_action('elementor/elements/elements_registered', [$this, 'register_elements']);

		// Locked upsell cards for the Pro-owned atomic widgets, so a free site's
		// panel shows what it is missing instead of eight silently absent cards.
		// Registered right beside the real registration because it answers the
		// same question from the other side: whatever those two hooks did NOT
		// register is exactly what Pro_Promotion advertises. Hand-required — the
		// PSR-4 map expects a class-named file, and this one follows the
		// class-*.php convention its neighbours use.
		require_once AAEADDON_PATH . 'inc/AtomicWidgets/class-pro-promotion.php';
		(new Pro_Promotion())->register();

		// Advanced Heading's `content` prop changed shape (string → html-v3) on
		// 2026-08-04. Registered UNCONDITIONALLY, not behind is_widget_active():
		// the read path has to keep converting even while the widget is switched
		// off, or turning it off and on again is enough to erase every heading
		// on the site the next time a page is saved. See the class docblock.
		require_once __DIR__ . '/Widgets/AdvancedHeading/class-aae-advanced-heading-migration.php';
		\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\AdvancedHeading\Aaeaddon_Advanced_Heading_Migration::register();

		// A Mobile Nav is a SIBLING of its Nav, so Elementor never cascade-deletes
		// it. The editor sweeps are best-effort JS; this is the save-time belt that
		// stops an orphan ever being written to the document. Registered
		// unconditionally for the same reason as the migration above — the guard
		// must hold even while the widget is switched off.
		require_once __DIR__ . '/Widgets/Nav/class-aae-a-nav-companion-sweep.php';
		\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Nav\Aaeaddon_A_Nav_Companion_Sweep::register();

		// Rewrites saved pages when a WP menu changes, so an imported Nav updates on
		// the FRONTEND without anyone opening Elementor.
		require_once __DIR__ . '/Widgets/Nav/class-aae-a-nav-menu-sync.php';
		\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Nav\Aaeaddon_A_Nav_Menu_Sync::register();

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
		// Menu breakpoint gate, printed early — see print_menu_breakpoint_bootstrap().
		add_action('wp_head', [$this, 'print_menu_breakpoint_bootstrap'], 1);
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
		// 'aae_get_menu_html' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_get_menu_html', 'aaeaddon_get_menu_html', [$this, 'ajax_get_menu_html'] );

		// Loop Grid: per-post data for the editor "full grid live" preview (the
		// atomic preview is client-side and can't run our PHP WP_Query).
		// 'aae_loop_post_data' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_loop_post_data', 'aaeaddon_loop_post_data', [$this, 'ajax_loop_post_data'] );

		// Loop Grid: one post's title/image for the editor's authored-card
		// sample — used after "Apply & Preview" (Page Settings → Preview
		// Settings) so the chosen post shows without a full editor reload.
		// 'aae_loop_sample_post' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_loop_sample_post', 'aaeaddon_loop_sample_post', [$this, 'ajax_loop_sample_post'] );

		// Loop Grid: AJAX search options for the panel's `aae-query-chips`
		// controls (posts by title/ID, taxonomy terms by name).
		// 'aae_loop_query_options' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_loop_query_options', 'aaeaddon_loop_query_options', [$this, 'ajax_loop_query_options'] );

		// AAE Nav: list WordPress menus + their nested item trees so the Nav
		// panel's "Import from WordPress menu" control can rebuild them as
		// atomic nav-items. Reuses the `Nonce::LOOP_GRID` editor nonce.
		// 'aae_get_nav_menus' is a deprecated alias (a cached admin bundle) -- remove in 4.3.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_get_nav_menus', 'aaeaddon_get_nav_menus', [$this, 'ajax_get_nav_menus'] );

		// Dynamic tags editor preview: `ajax_render_tags` switches to the EDITED
		// document before resolving tags, so a Featured Image / Post Title tag
		// inside a loop item resolves against the PAGE (usually no thumbnail →
		// empty). When the document has an explicit Preview Settings post
		// (`aae_loop_page_post`), re-switch to it so core dynamic tags preview
		// that post — same semantics the V3 loop preview always had.
		add_action('elementor/dynamic_tags/before_render', [$this, 'switch_dynamic_tags_to_preview_post']);

		// Loop Grid: frontend paginated cells (AJAX + Load More). Available to
		// logged-out visitors too, so both hooks are registered.
		// 'aae_loop_grid_page' is a deprecated alias (a page cache or combined bundle can still post the old name) -- remove in 4.4.
		\Wealcoder\AnimationAddons\Ajax_Alias::register( 'aae_loop_grid_page', 'aaeaddon_loop_grid_page', [$this, 'ajax_loop_grid_page'], true );

		// Loop Grid query-level hooks: the WooCommerce lookup-table clauses.
		// A no-op unless a query carries one of the grid's private vars, so it
		// is hooked here — cheaply, without loading the element class — and only
		// loads it when it fires. This is the ONLY registration site; the class
		// deliberately has no register() of its own, because a second one is how
		// posts_clauses ends up appending the same JOIN twice.
		//
		// Title-only search needs no hook at all: it rides WP's own
		// `search_columns` query var. See merge_visitor_filters().
		//
		// The two var names are spelled here as LITERALS on purpose: reading
		// Loop_Query_Woo::QV_PRICE would mean loading the element class on every
		// WP_Query on the site just to ask a question that is almost always no.
		// They mirror that class's public constants, verify-loop-filter-seam.php
		// asserts the pair still matches, and the constants' own docblock says so.
		add_filter('posts_clauses', function ($clauses, $query) {
			if (! $query instanceof \WP_Query || (! $query->get('aae_woo_price') && ! $query->get('aae_woo_sort'))) {
				return $clauses;
			}
			self::load_loop_grid_class();
			return \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Query_Woo::posts_clauses($clauses, $query);
		}, 10, 2);

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
		//
		// is_admin() as well, because the check compares two STATIC lists and
		// cannot answer differently on a visitor's request than on an admin one
		// -- but asking it there is what forced the 80 KB card registry to be
		// built on every front-end request of every WP_DEBUG site. A developer
		// still gets the error_log on the next admin page load, which is where
		// they are when a widget fails to appear.
		if (is_admin()) {
			add_action('elementor/init', [$this, 'assert_registry_integrity'], 5);
		}
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
			wp_trigger_error(
				__METHOD__,
				'AAE atomic registry: registered widget(s) with no dashboard metadata — unreachable: '
				. implode(', ', $missing_metadata)
			);
		}

		if ($orphan_children) {
			wp_trigger_error(
				__METHOD__,
				'AAE atomic registry: internal widget(s) with no WIDGET_PARENT_MAP parent — cannot inherit: '
				. implode(', ', $orphan_children)
			);
		}

		if ($dangling_parents) {
			wp_trigger_error(
				__METHOD__,
				'AAE atomic registry: WIDGET_PARENT_MAP points at unknown parent(s): '
				. implode(', ', $dangling_parents)
			);
		}

		if ($missing_class) {
			wp_trigger_error(
				__METHOD__,
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

		$legacy  = get_option('aaeaddon_save_extensions');
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
		if (AAEADDON_VERSION === get_option(self::OFFERED_MIGRATION_OPTION_NAME)) {
			return;
		}

		$offered = get_option(self::EXTENSIONS_OFFERED_OPTION_NAME);

		if (! is_array($offered)) {
			$offered = self::LEGACY_OFFERED_EXTENSIONS;
		}

		$registry_slugs = array_keys($this->extensions_registry());
		$newly_offered  = array_diff($registry_slugs, $offered);

		if ($newly_offered) {
			foreach ($newly_offered as $slug) {
				if (! empty($this->extensions_registry()[$slug]['default'])) {
					$saved[$slug] = true;
				}
			}

			update_option(self::EXTENSIONS_OPTION_NAME, $saved);
			$this->active_extensions = null;
		}

		update_option(self::EXTENSIONS_OFFERED_OPTION_NAME, $registry_slugs);

		// Stamped only after the work above completed, so an interrupted request
		// re-runs rather than recording a migration that never finished.
		update_option(self::OFFERED_MIGRATION_OPTION_NAME, AAEADDON_VERSION);
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
	 *       'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\<PascalName>\AAE_A_<PascalSlug>',
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
			// Pro plugin registers it AND the `aaeaddon_save_extensions` option is set,
			// which made a GSAP-driven counter fire on some pages and not others.
			'aae-a-counter' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Counter\Aaeaddon_A_Counter',
				'file' => 'Widgets/Counter/class-aae-a-counter.php',
				'script_handle' => 'aae-a-counter-js',
				'script_path' => '/assets/atomic/js/counter.js',
				'has_script' => true,
			],
			'aae-a-slider' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider.php',				
				'style_handle' => 'aae-a-slider-css',
				'style_path' => '/assets/atomic/css/nestedslider.css',
				'has_script' => false,
			],
			'aae-a-slide' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slide',
				'file' => 'Widgets/NestedSlider/class-aae-a-slide.php',
				'has_script' => false,
			],
			'aae-a-slider-track' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Track',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-track.php',
				'has_script' => false,
			],
			'aae-a-slider-nav-prev' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Nav_Prev',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-nav-prev.php',
				'has_script' => false,
			],
			'aae-a-slider-nav-next' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Nav_Next',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-nav-next.php',
				'has_script' => false,
			],
			'aae-a-slider-dot' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Dot',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-dot.php',
				'has_script' => false,
			],
			'aae-a-slider-indicators' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Indicators',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-indicators.php',
				'has_script' => false,
			],
			'aae-a-slider-current' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Current',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-current.php',
				'has_script' => false,
			],
			'aae-a-slider-total' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Total',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-total.php',
				'has_script' => false,
			],
			'aae-a-slider-percentage' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Percentage',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-percentage.php',
				'has_script' => false,
			],
			'aae-a-slider-progress' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Progress',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-progress.php',
				'has_script' => false,
			],
			'aae-a-slider-progress-fill' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Progress_Fill',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-progress-fill.php',
				'has_script' => false,
			],
			'aae-a-slider-counter' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Counter',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-counter.php',
				'has_script' => false,
			],
			'aae-a-slider-divider' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Divider',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-divider.php',
				'has_script' => false,
			],
			'aae-a-slider-pagination' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\NestedSlider\Aaeaddon_A_Slider_Pagination',
				'file' => 'Widgets/NestedSlider/class-aae-a-slider-pagination.php',
				'has_script' => false,
			],
			'aae-a-menu' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Menu\Aaeaddon_A_Menu',
				'file' => 'Widgets/Menu/class-aae-a-menu.php',
				'script_handle' => 'aae-a-menu-js',
				'script_path' => '/assets/atomic/js/menu.js',
				'has_script' => true,
				'style_handle' => 'aae-a-menu-css',
				'style_path' => '/assets/atomic/css/menu.css',
			],
			'aae-a-post-title' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostTitle\Aaeaddon_A_Post_Title',
				'file' => 'Widgets/PostTitle/class-aae-a-post-title.php',
				'has_script' => false,
				'style_handle' => 'aae-a-post-title-css',
				'style_path' => '/assets/atomic/css/post-title.css',
			],

			'aae-a-search-query' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchQuery\Aaeaddon_A_Search_Query',
				'file' => 'Widgets/SearchQuery/class-aae-a-search-query.php',
				'has_script' => false,
			],

			'aae-a-post-content' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostContent\Aaeaddon_A_Post_Content',
				'file' => 'Widgets/PostContent/class-aae-a-post-content.php',
				'has_script' => false,
			],

			'aae-a-post-excerpt' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostExcerpt\Aaeaddon_A_Post_Excerpt',
				'file' => 'Widgets/PostExcerpt/class-aae-a-post-excerpt.php',
				'has_script' => false,
			],

			'aae-a-post-image' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostImage\Aaeaddon_A_Post_Image',
				'file' => 'Widgets/PostImage/class-aae-a-post-image.php',
				'has_script' => false,
			],

			'aae-a-posts' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Posts\Aaeaddon_A_Posts',
				'file' => 'Widgets/Posts/class-aae-a-posts.php',
				'script_handle' => 'aae-a-posts-js',
				'script_path' => '/assets/atomic/js/posts.js',
				'has_script' => true,
				'style_handle' => 'aae-a-posts-css',
				'style_path' => '/assets/atomic/css/posts.css',
			],

			'aae-a-post-card' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Posts\Aaeaddon_A_Post_Card',
				'file'       => 'Widgets/Posts/class-aae-a-post-card.php',
				'has_script' => false,
			],

			'aae-a-loop-grid' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid',
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

			'aae-a-advance-portfolio' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\AdvancePortfolio\Aaeaddon_A_Advance_Portfolio',
				'file' => 'Widgets/AdvancePortfolio/class-aae-a-advance-portfolio.php',
				'script_handle' => 'aae-a-advance-portfolio-js',
				'script_path' => '/assets/atomic/js/advance-portfolio.js',
				// Pro-only handles; advance-portfolio.js already returns early
				// when window.gsap / window.ScrollTrigger are absent, so the grid
				// still renders on a free-only install, just unanimated.
				'script_deps' => aaeaddon_pro_defined( 'VERSION' ) ? [ 'gsap', 'ScrollTrigger' ] : [],
				'has_script' => true,
				'style_handle' => 'aae-a-advance-portfolio-css',
				'style_path' => '/assets/atomic/css/advance-portfolio.css',
			],
			'aae-a-portfolio-title' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\AdvancePortfolio\Aaeaddon_A_Portfolio_Title',
				'file' => 'Widgets/AdvancePortfolio/class-aae-a-portfolio-title.php',
				'has_script' => false,
			],
			'aae-a-portfolio-list' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\AdvancePortfolio\Aaeaddon_A_Portfolio_List',
				'file' => 'Widgets/AdvancePortfolio/class-aae-a-portfolio-list.php',
				'has_script' => false,
			],
			'aae-a-portfolio-item' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\AdvancePortfolio\Aaeaddon_A_Portfolio_Item',
				'file' => 'Widgets/AdvancePortfolio/class-aae-a-portfolio-item.php',
				'has_script' => false,
			],
			'aae-a-portfolio-content' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\AdvancePortfolio\Aaeaddon_A_Portfolio_Content',
				'file' => 'Widgets/AdvancePortfolio/class-aae-a-portfolio-content.php',
				'has_script' => false,
			],
			'aae-a-portfolio-date' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\AdvancePortfolio\Aaeaddon_A_Portfolio_Date',
				'file' => 'Widgets/AdvancePortfolio/class-aae-a-portfolio-date.php',
				'has_script' => false,
			],

			'aae-a-loop-item' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Item',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-item.php',
				'has_script' => false,
			],
			// --- Loop Filters (M2) -------------------------------------
			// The visitor-facing half. Classes ship FREE and are ALWAYS
			// registered, because an element type nobody registers is deleted
			// from every saved page on the next save; the paid behaviour is
			// gated at runtime through Pro_Gate instead.
			'aae-a-loop-layout' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Layout',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-layout.php',
				'has_script' => false,
			],
			'aae-a-loop-pagination' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Pagination',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-pagination.php',
				'has_script' => false,
			],
			'aae-a-loop-prev' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Prev',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-prev.php',
				'has_script' => false,
			],
			'aae-a-loop-next' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Next',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-next.php',
				'has_script' => false,
			],
			'aae-a-loop-numbers' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Numbers',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-numbers.php',
				'has_script' => false,
			],
			'aae-a-loop-number' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Number',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-number.php',
				'has_script' => false,
			],
			'aae-a-loop-loadmore' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_LoadMore',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-loadmore.php',
				'has_script' => false,
			],
			'aae-a-loop-arrow' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Arrow',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-arrow.php',
				'has_script' => false,
			],
			'aae-a-loop-nav-wrap' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Nav_Wrap',
				'file' => 'Widgets/LoopGrid/class-aae-a-loop-nav-wrap.php',
				'has_script' => false,
			],

			'aae-a-search-form' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Form',
				'file' => 'Widgets/SearchForm/class-aae-a-search-form.php',
				'script_handle' => 'aae-a-search-form-js',
				'script_path' => '/assets/atomic/js/search-form.js',
				'has_script' => true,
			],
			'aae-a-search-toggle' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Toggle',
				'file' => 'Widgets/SearchForm/class-aae-a-search-toggle.php',
				'has_script' => false,
			],
			'aae-a-search-toggle-open' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Toggle_Open',
				'file' => 'Widgets/SearchForm/class-aae-a-search-toggle-open.php',
				'has_script' => false,
			],
			'aae-a-search-toggle-close' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Toggle_Close',
				'file' => 'Widgets/SearchForm/class-aae-a-search-toggle-close.php',
				'has_script' => false,
			],
			'aae-a-search-panel' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Panel',
				'file' => 'Widgets/SearchForm/class-aae-a-search-panel.php',
				'has_script' => false,
			],
			'aae-a-search-field' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Field',
				'file' => 'Widgets/SearchForm/class-aae-a-search-field.php',
				'has_script' => false,
			],
			'aae-a-search-input' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Input',
				'file' => 'Widgets/SearchForm/class-aae-a-search-input.php',
				'has_script' => false,
			],
			'aae-a-search-filter-date' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Filter_Date',
				'file' => 'Widgets/SearchForm/class-aae-a-search-filter-date.php',
				'has_script' => false,
			],
			'aae-a-search-filter-category' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Filter_Category',
				'file' => 'Widgets/SearchForm/class-aae-a-search-filter-category.php',
				'has_script' => false,
			],
			'aae-a-search-submit' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Submit',
				'file' => 'Widgets/SearchForm/class-aae-a-search-submit.php',
				'has_script' => false,
			],
			'aae-a-search-results' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SearchForm\Aaeaddon_A_Search_Results',
				'file' => 'Widgets/SearchForm/class-aae-a-search-results.php',
				'has_script' => false,
			],

			// Loop Grid Slider — reuses the Loop Grid query engine + the shared
			// nested-slider runtime. Its only own script is the load-more bridge
			'aae-a-post-pagination' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination',
				'file' => 'Widgets/PostPagination/class-aae-a-post-pagination.php',
				'script_handle' => 'aae-a-post-pagination-js',
				'script_path' => '/assets/atomic/js/post-pagination.js',
				'has_script' => true,
				'style_handle' => 'aae-a-post-pagination-css',
				'style_path' => '/assets/atomic/css/post-pagination.css',
			],

			'aae-a-post-pagination-prev' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Prev',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-prev.php',
			],

			'aae-a-post-pagination-next' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Next',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-next.php',
			],

			'aae-a-post-pagination-preview' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Preview',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview.php',
			],

			'aae-a-post-pagination-preview-image' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Preview_Image',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-image.php',
			],

			'aae-a-post-pagination-preview-category' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Preview_Category',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-category.php',
			],

			'aae-a-post-pagination-preview-title' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Preview_Title',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-title.php',
			],

			'aae-a-post-pagination-preview-date' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Preview_Date',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-date.php',
			],

			'aae-a-post-pagination-preview-author' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Preview_Author',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-author.php',
			],

			'aae-a-post-pagination-preview-excerpt' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination_Preview_Excerpt',
				'file' => 'Widgets/PostPagination/Parts/class-aae-a-post-pagination-preview-excerpt.php',
			],

			/*
			 * AAE Post Comments family — DISABLED 2026-07-27 (see the matching
			 * commented block in register_widget_definitions() for why).
			 * Root file/class renamed to class-aae-a-comments-ny.php /
			 * AAE_A_Comments_Ny. Uncomment to re-enable.
			 *
			'aae-a-comments-ny' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comments_Ny',
				'file' => 'Widgets/Comments/class-aae-a-comments-ny.php',
				'has_script' => false,
				'style_handle' => 'aae-a-comments-css',
				'style_path' => '/assets/atomic/css/comments.css',
			],

			'aae-a-comment-list' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comment_List',
				'file' => 'Widgets/Comments/class-aae-a-comment-list.php',
			],

			'aae-a-comment-item' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Item',
				'file' => 'Widgets/Comments/class-aae-a-comment-item.php',
			],

			'aae-a-comment-avatar' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Avatar',
				'file' => 'Widgets/Comments/class-aae-a-comment-avatar.php',
				'has_script' => false,
			],

			'aae-a-comment-author' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Author',
				'file' => 'Widgets/Comments/class-aae-a-comment-author.php',
				'has_script' => false,
			],

			'aae-a-comment-date' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Date',
				'file' => 'Widgets/Comments/class-aae-a-comment-date.php',
				'has_script' => false,
			],

			'aae-a-comment-content' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Content',
				'file' => 'Widgets/Comments/class-aae-a-comment-content.php',
				'has_script' => false,
			],

			'aae-a-comment-reply-link' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Reply_Link',
				'file' => 'Widgets/Comments/class-aae-a-comment-reply-link.php',
				'has_script' => false,
			],

			'aae-a-comment-form' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Comments\AAE_A_Comment_Form',
				'file' => 'Widgets/Comments/class-aae-a-comment-form.php',
				'has_script' => false,
			],
			*/

			'aae-a-loop-grid-slider' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGridSlider\Aaeaddon_A_Loop_Grid_Slider',
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
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGridSlider\Aaeaddon_A_Loop_Slide_Track',
				'file' => 'Widgets/LoopGridSlider/class-aae-a-loop-slide-track.php',
				'has_script' => false,
			],
			'aae-a-loop-slide-item' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGridSlider\Aaeaddon_A_Loop_Slide_Item',
				'file' => 'Widgets/LoopGridSlider/class-aae-a-loop-slide-item.php',
				'has_script' => false,
			],
			'aae-a-loop-slide-pagination' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGridSlider\Aaeaddon_A_Loop_Slide_Pagination',
				'file' => 'Widgets/LoopGridSlider/class-aae-a-loop-slide-pagination.php',
				'has_script' => false,
			],

			'aae-a-accordion' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Accordion\Aaeaddon_A_Accordion',
				'file' => 'Widgets/Accordion/class-aae-a-accordion.php',
				'script_handle' => 'aae-a-accordion-js',
				'script_path' => '/assets/atomic/js/accordion.js',
				'has_script' => true,
				'style_handle' => 'aae-a-accordion-css',
				'style_path' => '/assets/atomic/css/accordion.css',
			],

			'aae-a-accordion-item' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Accordion\Aaeaddon_A_Accordion_Item',
				'file' => 'Widgets/Accordion/class-aae-a-accordion-item.php',
				'has_script' => false,
			],

			'aae-a-icon-list' => [
				'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\IconList\Aaeaddon_A_Icon_List',
				'file' => 'Widgets/IconList/class-aae-a-icon-list.php',
				'has_script' => false,
				'style_handle' => 'aae-a-icon-list-css',
				'style_path' => '/assets/atomic/css/icon-list.css',
			],
		'aae-a-icon-list-item' => [
			'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\IconList\Aaeaddon_A_Icon_List_Item',
			'file' => 'Widgets/IconList/class-aae-a-icon-list-item.php',
			'has_script' => false,
			'style_handle' => 'aae-a-icon-list-css',
			'style_path' => '/assets/atomic/css/icon-list.css',
		],

		'aae-a-social-share' => [
			'class'        => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SocialShare\Aaeaddon_A_Social_Share',
			'file'         => 'Widgets/SocialShare/class-aae-a-social-share.php',
			'has_script'   => false,
			// SCSS-only widget, compiled by gulp's compile:atomic-scss task, not webpack.
			'style_handle' => 'aae-a-social-share-css',
			'style_path'   => '/assets/atomic/css/social-share.css',
		],
		'aae-a-social-share-item' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SocialShare\Aaeaddon_A_Social_Share_Item',
			'file'       => 'Widgets/SocialShare/class-aae-a-social-share-item.php',
			'has_script' => false,
		],
		'aae-a-social-share-item-icon' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SocialShare\Aaeaddon_A_Social_Share_Item_Icon',
			'file'       => 'Widgets/SocialShare/Parts/class-aae-a-social-share-item-icon.php',
			'has_script' => false,
		],
		'aae-a-social-share-item-title' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SocialShare\Aaeaddon_A_Social_Share_Item_Title',
			'file'       => 'Widgets/SocialShare/Parts/class-aae-a-social-share-item-title.php',
			'has_script' => false,
		],
		// SocialShareMain entries removed — see the note in
		// register_widget_definitions(). The directory no longer exists.
		'aae-a-image-compare' => [
			'class' => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ImageCompare\Aaeaddon_A_Image_Compare',
			'file' => 'Widgets/ImageCompare/class-aae-a-image-compare.php',
			'script_handle' => 'aae-a-image-compare-js',
			'script_path' => '/assets/atomic/js/image-compare.js',
			'has_script' => true,
			'style_handle' => 'aae-a-image-compare-css',
			'style_path' => '/assets/atomic/css/image-compare.css',
		],
		'aae-a-countdown' => [
			'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Countdown\Aaeaddon_A_Countdown',
			'file'          => 'Widgets/Countdown/class-aae-a-countdown.php',
			'script_handle' => 'aae-a-countdown-js',
			'script_path'   => '/assets/atomic/js/countdown.js',
			'has_script'    => true,
		],
		'aae-a-countdown-unit' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Countdown\Aaeaddon_A_Countdown_Unit',
			'file'       => 'Widgets/Countdown/class-aae-a-countdown-unit.php',
			'has_script' => false,
		],
		'aae-a-timeline' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Timeline\Aaeaddon_A_Timeline',
			'file'       => 'Widgets/Timeline/class-aae-a-timeline.php',
			'has_script' => false,
			// No external CSS and no inline <style> in any Twig: every visual
			// detail (including the marker/year/title/desc typography) is a
			// real base style on its own dedicated widget type. No `style_handle`.
		],
		'aae-a-timeline-item' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Timeline\Aaeaddon_A_Timeline_Item',
			'file'       => 'Widgets/Timeline/class-aae-a-timeline-item.php',
			'has_script' => false,
		],
		'aae-a-timeline-number' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Timeline\Aaeaddon_A_Timeline_Number',
			'file'       => 'Widgets/Timeline/Parts/class-aae-a-timeline-number.php',
			'has_script' => false,
		],
		'aae-a-timeline-year' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Timeline\Aaeaddon_A_Timeline_Year',
			'file'       => 'Widgets/Timeline/Parts/class-aae-a-timeline-year.php',
			'has_script' => false,
		],
		'aae-a-timeline-title' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Timeline\Aaeaddon_A_Timeline_Title',
			'file'       => 'Widgets/Timeline/Parts/class-aae-a-timeline-title.php',
			'has_script' => false,
		],
		'aae-a-timeline-desc' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Timeline\Aaeaddon_A_Timeline_Desc',
			'file'       => 'Widgets/Timeline/Parts/class-aae-a-timeline-desc.php',
			'has_script' => false,
		],
		// Add new atomic widgets below...
			'aae-a-btn' => [
				'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Btn\Aaeaddon_A_Btn',
				'file'          => 'Widgets/Btn/class-aae-a-btn.php',
				'script_handle' => 'aae-a-btn-js',
				'script_path'   => '/assets/atomic/js/btn.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-btn-css',
				'style_path'    => '/assets/atomic/css/btn.css',
			],

			'aae-a-btn-pro' => [
				'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\BtnPro\AAE_A_Btn_Pro',
				'file'          => 'Widgets/BtnPro/class-aae-a-btn-pro.php',
				'script_handle' => 'aae-a-btn-pro-js',
				'script_path'   => '/assets/atomic/js/btn-pro.js',
				// Ripple + polygon magnetic-move effects need GSAP, but the handle
				// is Pro-only. btn-pro.js guards each GSAP-driven effect on
				// `typeof gsap`, so the button's other behaviour still works.
				'script_deps'   => aaeaddon_pro_defined( 'VERSION' ) ? [ 'gsap' ] : [],
				'has_script'    => true,
				'style_handle'  => 'aae-a-btn-pro-css',
				'style_path'    => '/assets/atomic/css/btn-pro.css',
			],

			'aae-a-advanced-heading' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\AdvancedHeading\Aaeaddon_A_Advanced_Heading',
				'file'       => 'Widgets/AdvancedHeading/class-aae-a-advanced-heading.php',
				'has_script' => false,
				// Design-less: this widget ships no CSS. Style your own classes.
			],

			'aae-a-progressbar' => [
				'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Progressbar\Aaeaddon_A_Progressbar',
				'file'          => 'Widgets/Progressbar/class-aae-a-progressbar.php',
				'script_handle' => 'aae-a-progressbar-js',
				'script_path'   => '/assets/atomic/js/progressbar.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-progressbar-css',
				'style_path'    => '/assets/atomic/css/progressbar.css',
			],

			'aae-a-progressbar-track' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Progressbar\Aaeaddon_A_Progressbar_Track',
				'file'       => 'Widgets/Progressbar/Parts/class-aae-a-progressbar-track.php',
				'has_script' => false,
			],
			'aae-a-progressbar-fill' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Progressbar\Aaeaddon_A_Progressbar_Fill',
				'file'       => 'Widgets/Progressbar/Parts/class-aae-a-progressbar-fill.php',
				'has_script' => false,
			],
			'aae-a-progressbar-label' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Progressbar\Aaeaddon_A_Progressbar_Label',
				'file'       => 'Widgets/Progressbar/Parts/class-aae-a-progressbar-label.php',
				'has_script' => false,
			],
			'aae-a-progressbar-dot' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Progressbar\Aaeaddon_A_Progressbar_Dot',
				'file'       => 'Widgets/Progressbar/Parts/class-aae-a-progressbar-dot.php',
				'has_script' => false,
			],

			'aae-a-toggle-switcher' => [
				'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Switcher',
				'file'          => 'Widgets/ToggleSwitcher/class-aae-a-toggle-switcher.php',
				'script_handle' => 'aae-a-toggle-switcher-js',
				'script_path'   => '/assets/atomic/js/toggle-switcher.js',
				'has_script'    => true,
				'style_handle'  => 'aae-a-toggle-switcher-css',
				'style_path'    => '/assets/atomic/css/toggle-switcher.css',
			],

			'aae-a-toggle-pane' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Pane',
				'file'       => 'Widgets/ToggleSwitcher/class-aae-a-toggle-pane.php',
				'has_script' => false,
			],

			'aae-a-toggle-switcher-tabs' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Switcher_Tabs',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-switcher-tabs.php',
				'has_script' => false,
			],
			'aae-a-toggle-switcher-tab' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Switcher_Tab',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-switcher-tab.php',
				'has_script' => false,
			],
			'aae-a-toggle-pane-title' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Pane_Title',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-pane-title.php',
				'has_script' => false,
			],
			'aae-a-toggle-pane-desc' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Pane_Desc',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-pane-desc.php',
				'has_script' => false,
			],
			'aae-a-toggle-switcher-label' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Switcher_Label',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-switcher-label.php',
				'has_script' => false,
			],
			'aae-a-toggle-switcher-tablist' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Switcher_Tablist',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-switcher-tablist.php',
				'has_script' => false,
			],
			'aae-a-toggle-switcher-track' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Switcher_Track',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-switcher-track.php',
				'has_script' => false,
			],
			'aae-a-toggle-switcher-knob' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ToggleSwitcher\Aaeaddon_A_Toggle_Switcher_Knob',
				'file'       => 'Widgets/ToggleSwitcher/Parts/class-aae-a-toggle-switcher-knob.php',
				'has_script' => false,
			],

			'aae-a-form' => [
				'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form',
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
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Label',
				'file'       => 'Widgets/Form/class-aae-a-form-label.php',
				'has_script' => false,
			],

			'aae-a-form-input' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Input',
				'file'       => 'Widgets/Form/class-aae-a-form-input.php',
				'has_script' => false,
			],

			'aae-a-form-textarea' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Textarea',
				'file'       => 'Widgets/Form/class-aae-a-form-textarea.php',
				'has_script' => false,
			],

			'aae-a-form-checkbox' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Checkbox',
				'file'       => 'Widgets/Form/class-aae-a-form-checkbox.php',
				'has_script' => false,
			],

			'aae-a-form-radio' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Radio',
				'file'       => 'Widgets/Form/class-aae-a-form-radio.php',
				'has_script' => false,
			],

			'aae-a-form-select' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Select',
				'file'       => 'Widgets/Form/class-aae-a-form-select.php',
				'has_script' => false,
			],

			'aae-a-form-success-message' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Success_Message',
				'file'       => 'Widgets/Form/class-aae-a-form-success-message.php',
				'has_script' => false,
			],

			'aae-a-form-error-message' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Error_Message',
				'file'       => 'Widgets/Form/class-aae-a-form-error-message.php',
				'has_script' => false,
			],

			'aae-a-form-submit' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Submit',
				'file'       => 'Widgets/Form/class-aae-a-form-submit.php',
				'has_script' => false,
			],

			'aae-a-form-field-error' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Field_Error',
				'file'       => 'Widgets/Form/class-aae-a-form-field-error.php',
				'has_script' => false,
			],

			'aae-a-form-file' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_File',
				'file'       => 'Widgets/Form/class-aae-a-form-file.php',
				'has_script' => false,
			],

			'aae-a-form-rating' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Rating',
				'file'       => 'Widgets/Form/class-aae-a-form-rating.php',
				'has_script' => false, // ships inside aae-a-form-js itself (lib/rating.js).
			],

			'aae-a-form-range' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Range',
				'file'       => 'Widgets/Form/class-aae-a-form-range.php',
				'has_script' => false, // ships inside aae-a-form-js itself (lib/range.js).
			],

			'aae-a-form-range-group' => [
				'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Range_Group',
				'file'          => 'Widgets/Form/class-aae-a-form-range-group.php',
				// Its own bundle, not form.js: the group is a usable page element
				// on its own, and form.js only initialises what sits inside a form.
				'has_script'    => true,
				'script_handle' => 'aae-a-form-range-group-js',
				'script_path'   => '/assets/atomic/js/form-range-group.js',
				'style_handle'  => 'aae-a-form-range-group-css',
				'style_path'    => '/assets/atomic/css/form-range-group.css',
			],

			'aae-a-form-range-value' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Range_Value',
				'file'       => 'Widgets/Form/class-aae-a-form-range-value.php',
				'has_script' => false, // painted by its parent's bundle (form-range-group.js).
			],

			'aae-a-form-country' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Country',
				'file'       => 'Widgets/Form/class-aae-a-form-country.php',
				'has_script' => false, // native single <select>; no JS needed.
			],

			'aae-a-form-calculation' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Calculation',
				'file'       => 'Widgets/Form/class-aae-a-form-calculation.php',
				'has_script' => false, // ships inside aae-a-form-js itself (lib/calculation.js).
			],

			'aae-a-form-password' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Password',
				'file'       => 'Widgets/Form/class-aae-a-form-password.php',
				'has_script' => false, // reveal toggle ships inside aae-a-form-js (lib/password.js).
			],

			'aae-a-form-step' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Step',
				'file'       => 'Widgets/Form/class-aae-a-form-step.php',
				'has_script' => false, // step-nav logic ships inside aae-a-form-js itself.
			],

			'aae-a-form-next' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Next',
				'file'       => 'Widgets/Form/class-aae-a-form-next.php',
				'has_script' => false, // click handler ships inside aae-a-form-js itself.
			],

			'aae-a-form-prev' => [
				'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Form\Aaeaddon_A_Form_Prev',
				'file'       => 'Widgets/Form/class-aae-a-form-prev.php',
				'has_script' => false, // click handler ships inside aae-a-form-js itself.
			],

			'aae-a-nav' => [
			'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Nav\Aaeaddon_A_Nav',
			'file'          => 'Widgets/Nav/class-aae-a-nav.php',
			'has_script'    => true,
			'script_handle' => 'aae-a-nav-js',
			'script_path'   => '/assets/atomic/js/nav.js',
			'style_handle'  => 'aae-a-nav-css',
			'style_path'    => '/assets/atomic/css/nav.css',
		],
		'aae-a-nav-item' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Nav\Aaeaddon_A_Nav_Item',
			'file'       => 'Widgets/Nav/class-aae-a-nav-item.php',
			'has_script' => false,
		],
		'aae-a-nav-sub-item' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Nav\Aaeaddon_A_Nav_Sub_Item',
			'file'       => 'Widgets/Nav/class-aae-a-nav-sub-item.php',
			'has_script' => false,
		],
		'aae-a-mobile-nav' => [
			'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Nav\Aaeaddon_A_Mobile_Nav',
			'file'          => 'Widgets/Nav/class-aae-a-mobile-nav.php',
			'has_script'    => true,
			'script_handle' => 'aae-a-nav-js',
			'script_path'   => '/assets/atomic/js/nav.js',
			'style_handle'  => 'aae-a-nav-css',
			'style_path'    => '/assets/atomic/css/nav.css',
		],

		'aae-a-flip-box' => [
			'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\FlipBox\Aaeaddon_A_Flip_Box',
			'file'          => 'Widgets/FlipBox/class-aae-a-flip-box.php',
			// SCSS-only widget (the flip animation is entirely CSS-driven) —
			// compiled by gulp's compile:atomic-scss task, not webpack.
			'has_script'    => false,
			'style_handle'  => 'aae-a-flip-box-css',
			'style_path'    => '/assets/atomic/css/flip-box.css',
		],

		'aae-a-image-hotspot' => [
			'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ImageHotspot\Aaeaddon_A_Image_Hotspot',
			'file'          => 'Widgets/ImageHotspot/class-aae-a-image-hotspot.php',
			'script_handle' => 'aae-a-image-hotspot-js',
			'script_path'   => '/assets/atomic/js/image-hotspot.js',
			'has_script'    => true,
			'style_handle'  => 'aae-a-image-hotspot-css',
			'style_path'    => '/assets/atomic/css/image-hotspot.css',
		],

		'aae-a-hotspot-point' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ImageHotspot\Aaeaddon_A_Hotspot_Point',
			'file'       => 'Widgets/ImageHotspot/class-aae-a-hotspot-point.php',
			'has_script' => false,
		],

		'aae-a-hotspot-marker' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ImageHotspot\Aaeaddon_A_Hotspot_Marker',
			'file'       => 'Widgets/ImageHotspot/Parts/class-aae-a-hotspot-marker.php',
			'has_script' => false,
		],

		'aae-a-hotspot-content' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ImageHotspot\Aaeaddon_A_Hotspot_Content',
			'file'       => 'Widgets/ImageHotspot/class-aae-a-hotspot-content.php',
			'has_script' => false,
		],

		'aae-a-hotspot-lightbox' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ImageHotspot\Aaeaddon_A_Hotspot_Lightbox',
			'file'       => 'Widgets/ImageHotspot/Parts/class-aae-a-hotspot-lightbox.php',
			'has_script' => false,
		],

		'aae-a-hotspot-close' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\ImageHotspot\Aaeaddon_A_Hotspot_Close',
			'file'       => 'Widgets/ImageHotspot/Parts/class-aae-a-hotspot-close.php',
			'has_script' => false,
		],

		'aae-a-flip-box-front' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\FlipBox\Aaeaddon_A_Flip_Box_Front',
			'file'       => 'Widgets/FlipBox/Parts/class-aae-a-flip-box-front.php',
			'has_script' => false,
		],

		'aae-a-flip-box-back' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\FlipBox\Aaeaddon_A_Flip_Box_Back',
			'file'       => 'Widgets/FlipBox/Parts/class-aae-a-flip-box-back.php',
			'has_script' => false,
		],

		'aae-a-flip-box-title' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\FlipBox\Aaeaddon_A_Flip_Box_Title',
			'file'       => 'Widgets/FlipBox/Parts/class-aae-a-flip-box-title.php',
			'has_script' => false,
		],

		'aae-a-flip-box-text' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\FlipBox\Aaeaddon_A_Flip_Box_Text',
			'file'       => 'Widgets/FlipBox/Parts/class-aae-a-flip-box-text.php',
			'has_script' => false,
		],

		// No style_handle/style_path on purpose: this widget's ~140 bytes of CSS
		// is emitted inline by its twig (guarded so it prints once per request)
		// rather than costing a separate HTTP request. There is no
		// Widgets/SiteLogo/assets/scss for it either — the twig is the source of
		// truth. Don't "restore" the handle without also deleting the twig block.
		'aae-a-site-logo' => [
			'class'        => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\SiteLogo\Aaeaddon_A_Site_Logo',
			'file'         => 'Widgets/SiteLogo/class-aae-a-site-logo.php',
			'has_script'   => false,
		],

		'aae-a-video-mask' => [
			'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\VideoMask\Aaeaddon_A_Video_Mask',
			'file'          => 'Widgets/VideoMask/class-aae-a-video-mask.php',
			'script_handle' => 'aae-a-video-mask-js',
			'script_path'   => '/assets/atomic/js/video-mask.js',
			'has_script'    => true,
			'style_handle'  => 'aae-a-video-mask-css',
			'style_path'    => '/assets/atomic/css/video-mask.css',
		],

		'aae-a-video-mask-btn' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\VideoMask\Aaeaddon_A_Video_Mask_Btn',
			'file'       => 'Widgets/VideoMask/class-aae-a-video-mask-btn.php',
			'has_script' => false,
		],

		'aae-a-video' => [
			'class'         => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Video\Aaeaddon_A_Video',
			'file'          => 'Widgets/Video/class-aae-a-video.php',
			'script_handle' => 'aae-a-video-js',
			'script_path'   => '/assets/atomic/js/video.js',
			'has_script'    => true,
			'style_handle'  => 'aae-a-video-css',
			'style_path'    => '/assets/atomic/css/video.css',
		],

		'aae-a-video-player' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Video\Aaeaddon_A_Video_Player',
			'file'       => 'Widgets/Video/Parts/class-aae-a-video-player.php',
			'has_script' => false,
		],

		'aae-a-video-playbtn' => [
			'class'      => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\Video\Aaeaddon_A_Video_PlayBtn',
			'file'       => 'Widgets/Video/Parts/class-aae-a-video-playbtn.php',
			'has_script' => false,
		],

		'aae-a-curved-text' => [
			'class'        => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\CurvedText\Aaeaddon_A_Curved_Text',
			'file'         => 'Widgets/CurvedText/class-aae-a-curved-text.php',
			'has_script'   => false,
			'style_handle' => 'aae-a-curved-text-css',
			'style_path'   => '/assets/atomic/css/curved-text.css',
		],

		'aae-a-google-maps' => [
			'class'        => '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\GoogleMaps\Aaeaddon_A_Google_Maps',
			'file'         => 'Widgets/GoogleMaps/class-aae-a-google-maps.php',
			// No frontend JS: the embed URL is built in the twig, so the map
			// works with scripting irrelevant. The only stylesheet rule is the
			// editor-only pointer-events guard (google-maps.scss).
			'has_script'   => false,
			'style_handle' => 'aae-a-google-maps-css',
			'style_path'   => '/assets/atomic/css/google-maps.css',
		],

		// Add new atomic widgets below...
		];

		/**
		 * The class/asset registry for every atomic widget.
		 *
		 * Elementor exposes no registry a second plugin can add an atomic element
		 * TYPE to, so a Pro-owned atomic widget has to arrive through here. An
		 * entry may carry its own `base_path` / `base_url` (absolute filesystem
		 * path and URL, both ending in a slash) when its files live outside this
		 * plugin; `asset_url()` and the script/style registrars fall back to
		 * AAEADDON_PATH / AAEADDON_URL when they are absent, so every existing
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
		Nonce::check_ajax( Nonce::LOOP_GRID, 'nonce');

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$post = isset($_POST['sample_id']) ? get_post(absint($_POST['sample_id'])) : null;
		if (! $post || 'publish' !== $post->post_status) {
			wp_send_json_error(['message' => 'Invalid sample post.'], 404);
		}

		wp_send_json_success(array_merge([
			'title' => get_the_title($post),
			'image' => get_the_post_thumbnail_url($post, 'large') ?: '',
		], self::excerpt_preview_data($post)));
	}

	/**
	 * The two texts the editor's Post Excerpt mirror needs for one post: the
	 * WordPress excerpt (what `none` / a line clamp shows) and the full plain
	 * text a word / char limit trims from — the SAME two sources the widget's
	 * PHP uses, so the canvas and the page trim the same words. Empty when the
	 * widget is switched off (its class is only loaded while it registers).
	 *
	 * @param \WP_Post|null $post The post.
	 * @return array{excerpt?: string, excerpt_full?: string}
	 */
	private static function excerpt_preview_data($post): array
	{
		$cls = '\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostExcerpt\Aaeaddon_A_Post_Excerpt';
		if (! $post instanceof \WP_Post || ! class_exists($cls)) {
			return [];
		}

		return [
			'excerpt'      => $cls::excerpt_for($post, 'none', 0, ''),
			'excerpt_full' => $cls::full_text($post),
		];
	}

	/**
	 * Make sure the Loop Grid element class (and its shared query builder) is
	 * loaded. Element classes are normally require'd during Elementor's element
	 * registration, which doesn't run on a plain admin-ajax request.
	 */
	private static function load_loop_grid_class(): void
	{
		if (! class_exists(\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::class)) {
			require_once __DIR__ . '/Widgets/LoopGrid/class-aae-a-loop-grid.php';
		}
	}

	/**
	 * For each public post type: how many taxonomy filters the Loop Grid offers
	 * it, which of them are currently UNREGISTERED (kept by the known-taxonomy
	 * ratchet — their plugin is off) and which are offered although not public
	 * (WooCommerce attributes). The panel cannot compute this per instance
	 * (controls are built once per type), so it is shipped as data and the
	 * notice card resolves it against the element's own Source.
	 *
	 * @return array<string, array{count:int, unregistered:string[], nonPublic:string[]}>
	 */
	private static function loop_grid_taxonomy_notices(): array
	{
		self::load_loop_grid_class();
		$taxes = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::get_query_taxonomies();
		$out   = [];

		foreach (array_keys(get_post_types(['public' => true])) as $type) {
			if ('attachment' === $type) {
				continue;
			}
			$entry = ['count' => 0, 'unregistered' => [], 'nonPublic' => []];
			foreach ($taxes as $tax) {
				if (! in_array($type, (array) ($tax->object_type ?? []), true)) {
					continue;
				}
				$label = (string) ($tax->label ?? $tax->name);
				if (! empty($tax->aae_unregistered)) {
					$entry['unregistered'][] = $label;
					continue;
				}
				$entry['count']++;
				if (empty($tax->public)) {
					$entry['nonPublic'][] = $label;
				}
			}
			$out[ $type ] = $entry;
		}

		return $out;
	}

	/**
	 * Panel search for the `aae-query-chips` controls: posts (by title / ID) or
	 * taxonomy terms (by name). Returns [{id, label}] — max 20.
	 */
	public function ajax_loop_query_options()
	{
		Nonce::check_ajax( Nonce::LOOP_GRID, 'nonce');

		if (! current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}

		$kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : 'post';
		$term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
		$options = [];

		if ('user' === $kind) {
			// Authors for the grid's authors / exclude_authors chips. Only people
			// who can publish — a subscriber is never an author. Numeric search
			// tries the id first, like the post kind.
			if (ctype_digit($term)) {
				$by_id = get_user_by('id', (int) $term);
				if ($by_id instanceof \WP_User && $by_id->has_cap('edit_posts')) {
					$options[] = ['id' => (int) $by_id->ID, 'label' => $by_id->display_name];
				}
			}
			$users = get_users([
				'number'              => ('' === $term) ? 8 : 20,
				'search'              => '' === $term ? '' : '*' . $term . '*',
				'search_columns'      => ['display_name', 'user_login', 'user_nicename', 'user_email'],
				'capability'          => 'edit_posts',
				'orderby'             => 'display_name',
				'order'               => 'ASC',
			]);
			foreach ($users as $u) {
				if (! empty($by_id) && (int) $u->ID === (int) $by_id->ID) {
					continue;
				}
				$options[] = ['id' => (int) $u->ID, 'label' => $u->display_name];
			}
		} elseif ('acf_field' === $kind) {
			// The Field / Date filters' ACF dropdown (AcfFieldControl.jsx). The
			// whole catalogue at once — a site has tens of fields, not
			// thousands — and the control narrows it to the grid's post type
			// itself. `acf` says whether ACF is running at all, so an empty
			// list can be told apart from "no ACF here".
			self::load_loop_grid_class();
			wp_send_json_success([
				'acf'     => function_exists('acf_get_field_groups'),
				'options' => Widgets\LoopGrid\Loop_Filter_Auth::acf_field_catalogue(),
			]);
		} elseif ('term' === $kind) {
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
		Nonce::check_ajax( Nonce::LOOP_GRID, 'nonce');

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
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::build_query_args($filters)
		);

		$posts = [];
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$posts[] = array_merge([
					'title'   => get_the_title(),
					'url'     => get_permalink(),
					'image'   => get_the_post_thumbnail_url(null, 'large') ?: '',
					'excerpt' => wp_strip_all_tags(get_the_excerpt()),
				], self::excerpt_preview_data(get_post()));
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
	 * (keyed like Aaeaddon_A_Loop_Grid does), and renders the loop-item element — the
	 * exact same path used server-side, so the markup + style classes match.
	 *
	 * Nonce: `Nonce::LOOP_GRID_FRONT` (public). Only reads published post content.
	 */
	public function ajax_loop_grid_page() {
		Nonce::check_ajax( Nonce::LOOP_GRID_FRONT, 'nonce');

		$post_id  = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		$grid_id  = isset($_POST['grid_id']) ? sanitize_key(wp_unslash($_POST['grid_id'])) : '';
		$paged    = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;

		if (! $post_id || ! $grid_id) {
			wp_send_json_error(['message' => 'Missing post_id or grid_id.'], 400);
		}

		$post = get_post($post_id);
		if (! $post || ('publish' !== $post->post_status && ! current_user_can('read_post', $post_id)) || post_password_required($post)) {
			wp_send_json_error(['message' => 'Access denied.'], 403);
		}


		// Multilingual context (WPML & Polylang): switch active language to match requesting post.
		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpml_switch_language_for_post', $post_id );
		}
		if ( function_exists( 'pll_get_post_language' ) && function_exists( 'PLL' ) ) {
			$pll_lang = pll_get_post_language( $post_id );
			if ( $pll_lang && isset( PLL()->curlang, PLL()->model ) ) {
				PLL()->curlang = PLL()->model->get_language( $pll_lang );
			}
		}

		$doc = \Elementor\Plugin::$instance->documents->get($post_id);
		if (! $doc) {
			wp_send_json_error(['message' => 'Document not found.'], 404);
		}

		$data = $doc->get_elements_data();

		// The static grid and the slider variant share this endpoint. Both root
		// types publish the same Render_Context (keyed by Aaeaddon_A_Loop_Grid::class,
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

		// The page this request is FOR, as `path?query` — the URL the visitor's
		// address bar is about to show. Read here rather than at the top of the
		// handler because it is the first line that may touch Loop_Filter_Auth,
		// and load_loop_grid_class() immediately above is what puts that class
		// on disk-to-memory.
		//
		// With it the response is, by construction, what a full page load of
		// that URL would have rendered: the page number and the filter state are
		// read back out of it by the very code the first render uses, and the
		// filter widgets re-rendered below build their links against it instead
		// of against admin-ajax.php. That is what lets the filter runtime be a
		// pure "same render, no reload" upgrade with no second copy of any rule.
		//
		// Without it the handler behaves exactly as it always has, which is what
		// keeps the existing pagination runtime working untouched.
		$path = isset($_POST['path']) ? (string) wp_unslash($_POST['path']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- set_request_url() is the validator: same-host only, capped, no traversal.
		if ('' !== $path && ! \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::set_request_url($path)) {
			wp_send_json_error(['message' => 'Invalid path.'], 400);
		}

		// Related source: the anchor is the post the visitor is READING, which
		// is not $post_id once the grid lives in a theme-builder template —
		// there $post_id is the template document, the one whose saved data
		// declares this grid. The runtime posts the viewed post separately.
		//
		// It goes through the SAME gate $post_id did, and for the same reason.
		// The Related builder reads this post's type and its terms to find posts
		// "like" it, so an unchecked id lets a visitor anchor the query on a
		// draft and learn which published posts share its terms. Nothing
		// unpublished is ever rendered either way — all three builders pin
		// post_status to publish — but the result set is still an inference
		// channel about a post nobody was shown. A rejected id falls back to the
		// document, exactly as an absent one does.
		$context_id = isset($_POST['context_id']) ? absint($_POST['context_id']) : 0;
		if ($context_id && $context_id !== $post_id) {
			$context = get_post($context_id);
			if (
				! $context
				|| ('publish' !== $context->post_status && ! current_user_can('read_post', $context_id))
				|| post_password_required($context)
			) {
				$context_id = 0;
			}
		}
		$gs['_context_post_id'] = $context_id ?: $post_id;

		// Current Query source: the archive's query vars, captured into the
		// pagination config at render time and posted back by the runtime.
		if (isset($_POST['qv'])) {
			$qv = json_decode(sanitize_text_field(wp_unslash($_POST['qv'])), true);
			if (is_array($qv)) {
				$gs['_qv'] = $qv; // whitelist-sanitized inside the builder
			}
		}

		// Visitor filters: a JSON map of url_key => value, the same shape the
		// URL carries on the first render. Authorised against the filter widgets
		// this SAVED document declares for this grid — anything not declared is
		// dropped — and handed to the builder as its own argument, never inside
		// the settings blob. Oversized / malformed is a 400, not a guess.
		$filters = [];
		if ('' !== $path) {
			// URL mode: the filter state is IN the path, so it is read with the
			// same request parser the first render uses. No second shape, and
			// nothing for the browser to get wrong about which keys are filter
			// keys — request_args() is already unslashed, hence `false`.
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::prime_document($post_id, $data);
			$request_args = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::request_args();

			$filters = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::current(
				$post_id,
				$grid_id,
				\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::effective_post_type($gs),
				$request_args,
				false
			);

			// The page number comes from the same place, so a filter link (which
			// strips it) lands on page 1 without the runtime having to say so.
			$paged = isset($request_args['aae_page']) ? max(1, absint($request_args['aae_page'])) : 1;
		} elseif (isset($_POST['filters'])) {
			$raw = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::decode_payload(wp_unslash($_POST['filters'])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded + capped, then every value is authorised
			if (null === $raw) {
				wp_send_json_error(['message' => 'Invalid filters payload.'], 400);
			}
			// current() is the one authorisation pipeline the first render also
			// uses, so page 2 can never be authorised on different terms than
			// page 1. The tree is handed over rather than re-read: this handler
			// already decoded it above. `false` says the payload was unslashed
			// once already, at decode_payload() — unslashing it twice ate real
			// backslashes and made page 2 a different result set.
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::prime_document($post_id, $data);
			$filters = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::current(
				$post_id,
				$grid_id,
				\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::effective_post_type($gs),
				$raw,
				false
			);
		}

		$query_args = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::build_query_args(
			$gs,
			$paged,
			$filters
		);

		// Total + pages (respects the same query, offset-corrected).
		$total     = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::count_total($gs, $query_args);
		$max_pages = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::pages_for_total($total, $query_args);

		// Hand the readouts the numbers this request already paid for. A Result
		// Count re-rendered below would otherwise run the same count again — and,
		// worse, could answer a different one if anything about the request
		// changed between the two, so the grid and its own caption would
		// disagree inside a single response.
		\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::prime_summary(
			$post_id,
			$grid_id,
			[
				'grid_id'   => $grid_id,
				'post_type' => \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::effective_post_type($gs),
				'filters'   => $filters['active'] ?? [],
				'total'     => $total,
				'max_pages' => $max_pages,
				'paged'     => $paged,
				'per_page'  => max(1, (int) ($query_args['posts_per_page'] ?? 6)),
			]
		);

		// Push context (same key the Loop Item reads) and render the item.
		\Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context::push(
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::class,
			['query_args' => $query_args]
		);

		$item_obj = \Elementor\Plugin::$instance->elements_manager->create_element_instance($item_el);
		ob_start();
		if ($item_obj) {
			$item_obj->print_element();
		}
		$html = ob_get_clean();

		\Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context::pop(
			\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Aaeaddon_A_Loop_Grid::class
		);

		// The filter widgets, re-rendered for the same URL.
		//
		// Asked for explicitly, so the pagination runtime — which changes the
		// page and never the filter state — pays nothing for it. The widgets are
		// rendered rather than patched in the browser because everything they
		// draw (which terms read as selected, where each link points NEXT, the
		// Clear link) follows from rules that already exist once, in PHP.
		// Recomputing them in JS would be a second copy that can disagree with
		// the page a reload would produce.
		$filters_html = [];
		if (! empty($_POST['with_filters'])) {
			$filters_html = $this->render_loop_filters($post_id, $grid_id, $data);
		}

		wp_send_json_success([
			'html'         => $html,
			'paged'        => $paged,
			'max_pages'    => $max_pages,
			'total'        => $total,
			'filters'      => (object) ($filters['active'] ?? []),
			'filters_html' => (object) $filters_html,
		]);
	}

	/**
	 * Every filter widget that targets $grid_id, rendered fresh, keyed by its
	 * element id.
	 *
	 * The list comes from `Loop_Filter_Auth::rerender_ids()`, which is the
	 * declaration walk — so a widget not authorised to filter this grid is never
	 * sent back for it — PLUS the READOUTS pointed at the grid. A Result Count
	 * or an Active Filters bar declares nothing and so has no declaration to be
	 * found by; it still describes the result set, and leaving it out is how a
	 * filtered page ends up saying "24 results" over twelve of them.
	 *
	 * The document is switched to for the duration: a filter widget asks
	 * `Aaeaddon_A_Loop_Grid::current_document_id()` which document declares it, and
	 * in an admin-ajax request there is no current document and no global post,
	 * so without this every widget would resolve nothing and render an empty
	 * list. `switch_to_document()` only swaps Elementor's own pointer — it
	 * touches no post globals, so it cannot disturb anything around it.
	 *
	 * @param int    $post_id  The document whose saved data declares the filters.
	 * @param string $grid_id  The Loop Grid element id they target.
	 * @param array  $elements That document's already-decoded element tree.
	 * @return array<string, string> element id => HTML.
	 */
	private function render_loop_filters(int $post_id, string $grid_id, array $elements): array {
		$wanted = \Wealcoder\AnimationAddons\AtomicWidgets\Widgets\LoopGrid\Loop_Filter_Auth::rerender_ids($elements, $grid_id);
		if (! $wanted) {
			return [];
		}

		$found = [];
		$walk  = function ($els) use (&$walk, &$found, $wanted) {
			foreach ((array) $els as $el) {
				if (! is_array($el)) {
					continue;
				}
				$id = (string) ($el['id'] ?? '');
				if ('' !== $id && isset($wanted[$id])) {
					$found[$id] = $el;
				}
				if (! empty($el['elements'])) {
					$walk($el['elements']);
				}
			}
		};
		$walk($elements);

		if (! $found) {
			return [];
		}

		$doc = \Elementor\Plugin::$instance->documents->get($post_id);
		if ($doc) {
			\Elementor\Plugin::$instance->documents->switch_to_document($doc);
		}

		$out = [];
		foreach ($found as $id => $el) {
			$obj = \Elementor\Plugin::$instance->elements_manager->create_element_instance($el);
			if (! $obj) {
				continue;
			}
			ob_start();
			$obj->print_element();
			$out[$id] = ob_get_clean();
		}

		if ($doc) {
			\Elementor\Plugin::$instance->documents->restore_document();
		}

		return $out;
	}

	/**
	 * Invalidate AAE Post Pagination's cached ordered-id lists for a post type the
	 * moment its content changes (save/trash/delete), rather than relying on
	 * the transient TTL alone. Cheap no-op for post types that never used the
	 * widget (bumping a version nobody reads costs nothing).
	 */
	public function bump_post_pagination_cache_version($post_id): void {
		$post_type = get_post_type($post_id);
		if (! $post_type || ! class_exists('\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination')) {
			return;
		}
		\Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostPagination\Aaeaddon_A_Post_Pagination::bump_cache_version($post_type);
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
		// after editor-preview, or the Nav dropdown renders unpositioned / in-flow
		// ("styles missing on reload").
		//
		// THIS PASS IS THE ONLY ONE THAT CAN DO IT, and the reason is timing, not
		// a race. An earlier revision of this comment blamed the dependency being
		// added "too early to stick" — that cannot happen: Elementor registers AND
		// enqueues editor-preview on the two lines immediately before it fires
		// `elementor/preview/enqueue_styles` (includes/preview.php:271, :287,
		// :299), so any callback on that hook always finds the handle registered.
		//
		// The real reason is that half the handles do not EXIST yet at that point.
		// Elementor's per-document CSS (`local-<id>-preview-*`,
		// `elementor-post-<id>`) registers later, while the document renders, so
		// nothing hooked to an enqueue action can reach it. wp_print_styles at
		// priority 0 is the last moment when every handle is finally registered
		// and nothing has been echoed yet.
		//
		// Do not "simplify" this away on the assumption the enqueue-time
		// dependency already covers it — it covers the widget sheets only.
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
	 * Aaeaddon_A_Btn's `e-aae-a-btn-icon`, 30px) collides with a native default
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
					wp_add_inline_style( $handle, wp_strip_all_tags( $css ) );
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
		'aae-atomic-woo',
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

		// Registered here even though every widget that uses it is PRO-owned:
		// add_category() runs on an Elementor hook the free plugin already
		// answers, and a widget returning a slug nobody registered lands under
		// Elementor's own "Atomic Elements" with no error to explain it. Costs
		// nothing on a site without Pro — an empty category is not rendered,
		// and promote_panel_categories() skips a slug that is not in the map.
		$elements_manager->add_category('aae-atomic-woo', [
			'title' => esc_html__('AAE WooCommerce', 'animation-addons-for-elementor'),
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
			->register_document_type('e-aae-a-loop-grid', \Wealcoder\AnimationAddons\AtomicWidgets\Library\Aaeaddon_A_Loop_Grid_Document::class)
			->register_document_type('e-aae-a-loop-grid-slider', \Wealcoder\AnimationAddons\AtomicWidgets\Library\Aaeaddon_A_Loop_Grid_Slider_Document::class)
			->register_document_type('e-aae-a-slider', \Wealcoder\AnimationAddons\AtomicWidgets\Library\Aaeaddon_A_Slider_Document::class);
	}

	/**
	 * Build a plugin asset URL from a registry-relative path.
	 *
	 * AAEADDON_URL comes from plugin_dir_url(), which always ends in a slash,
	 * while every `script_path` / `style_path` in the widget registry is written
	 * with a leading slash. Concatenating them raw yields
	 * `.../animation-addons-for-elementor//assets/...` — the browser treats that
	 * as a different URL from the single-slash form, so a file enqueued both
	 * ways is fetched (and cached) twice. Normalise here rather than editing all
	 * 50 registry entries, so new entries can keep either spelling safely.
	 */
	private static function asset_url(string $relative_path, ?string $base_url = null): string
	{
		return ($base_url ?? AAEADDON_URL) . ltrim($relative_path, '/');
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
		return ! empty($widget_data['base_path']) ? $widget_data['base_path'] : AAEADDON_PATH;
	}

	private static function widget_base_url(array $widget_data): string
	{
		return ! empty($widget_data['base_url']) ? $widget_data['base_url'] : AAEADDON_URL;
	}

	/**
	 * Slides an unlicensed site may author in either slider.
	 *
	 * Lives HERE, not on Aaeaddon_A_Loop_Grid_Slider, because Assets.php has to ship
	 * it to the editor on every load and that widget's class is only required
	 * when the widget is switched on — reading it there would fatal on a site
	 * that has the slider disabled.
	 *
	 * PANEL ONLY. Nothing downstream of a saved value consults it, so a slider
	 * built with more slides keeps rendering all of them if the licence lapses.
	 */
	const FREE_SLIDE_LIMIT = 3;

	/**
	 * Where every in-editor upsell sends the user.
	 *
	 * One constant because two of them drifting is not a hypothetical: the
	 * dashboard already reaches the site root in some places and /pricing/ in
	 * others, which is how a campaign link ends up half-applied. Anything new
	 * that upsells from inside the editor should read this rather than typing
	 * the URL again.
	 */
	const UPGRADE_URL = 'https://animation-addons.com/pricing/';

	/**
	 * Is there a Pro plugin here with a VALID licence?
	 *
	 * The same gate Pro puts on its own include_files(), so it answers the only
	 * question that matters downstream: will Pro's code actually run. Absent
	 * function means either no Pro at all or one too old to have it, and both
	 * are correctly "no".
	 *
	 * Licence-only, with NO version floor: it answers "has this customer
	 * paid", which is the only question a feature gate needs. Anything that
	 * must ALSO know "is the Pro here new enough" has to check
	 * aaeaddon_pro_constant( 'VERSION' ) itself rather than widening this.
	 *
	 * Pro memoises the underlying option read in a static, so repeat calls are
	 * free.
	 */
	public static function pro_licensed(): bool
	{
		return function_exists('wcf__addons__pro__status') && (bool) wcf__addons__pro__status();
	}

	/**
	 * Is the CODE for this atomic widget present on disk?
	 *
	 * The registry-entry half and the file half are both required and mean
	 * different things: a slug missing entirely is a widget this build never
	 * knew about, while an entry whose file is gone is a widget this build
	 * expects someone else to ship (a moved-to-Pro slug on a site with no Pro,
	 * or a partial deploy). Both end the same way in
	 * resolve_registerable_classes() — the element type never registers — which
	 * is the only thing a caller asking this question cares about.
	 *
	 * Deliberately NOT is_widget_active(): that answers whether the user
	 * switched it on, which is their choice and not a missing-code condition.
	 * Pro_Promotion needs exactly this split, so that a widget somebody turned
	 * off in the dashboard is not advertised back at them as a paid upgrade.
	 */
	public function widget_code_present(string $slug): bool
	{
		$widgets = $this->get_available_widgets();

		if (! isset($widgets[$slug])) {
			return false;
		}

		$file = self::widget_class_file($widgets[$slug]);

		return '' !== $file && file_exists($file);
	}

	/**
	 * Absolute path to a registry entry's class file.
	 *
	 * `file` is normally relative to this directory. An entry from another
	 * plugin gives an absolute path instead, which is passed through untouched.
	 * Both callers already skip a path that does not exist, so a Pro entry left
	 * behind by a partial deploy costs that widget, not the request.
	 */
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
	 * The version source is deliberately still filemtime, not AAEADDON_VERSION:
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
			'version' => file_exists($file_path) ? filemtime($file_path) : AAEADDON_VERSION,
		];
	}

	/**
	 * Print the menu breakpoint gate as an inline <head> script.
	 *
	 * `.aae-a-menu--mobile` is the ONLY switch between the desktop bar and the
	 * mobile drawer: the stylesheet carries no `max-width` media query, because a
	 * media query cannot read the PER-WIDGET "Mobile Breakpoint" value. That makes
	 * the class load-bearing, and menu.js — a footer module that additionally
	 * depends on `elementor-v2-frontend-handlers` — is far too late to be its only
	 * source:
	 *
	 *   - Until the module runs, a phone paints the full desktop nav and then
	 *     snaps to the hamburger. That flash is the visible half of the bug.
	 *   - If the module never runs for an instance — a JS error earlier in the
	 *     footer, a deferring/combining optimiser, a header fragment served from a
	 *     full-page cache — the menu stays in desktop layout for good. That is the
	 *     "works on some page loads, not others" half.
	 *
	 * This runs at `wp_head` priority 1, before any menu markup exists, so it
	 * installs a MutationObserver and classes each root the moment it is parsed —
	 * i.e. before that node's first paint. menu.js still does its own sync; the two
	 * are idempotent and agree on the rule (`innerWidth <= breakpoint`, unparseable
	 * falls back to 768, and 0 legitimately means "never go mobile").
	 *
	 * Deliberately not gated on "does this page use the widget": the check is not
	 * reliable at wp_head time for theme-builder headers, and the payload is a few
	 * hundred bytes that no-ops when it finds no menus.
	 */
	public function print_menu_breakpoint_bootstrap(): void {
		if ( ! $this->is_widget_active( 'aae-a-menu' ) ) {
			return;
		}

		// Editor preview excluded: menu.js owns the class there, and the panel
		// re-render churn would fight an observer it does not know about.
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			return;
		}

		$js = <<<'JS'
(function(){
var S='.aae-a-menu[data-breakpoint]';
function sync(el){
	var bp=parseInt(el.getAttribute('data-breakpoint'),10);
	if(!isFinite(bp)){bp=768;}
	el.classList.toggle('aae-a-menu--mobile',window.innerWidth<=bp);
}
function all(){
	var n=document.querySelectorAll(S),i=0;
	for(;i<n.length;i++){sync(n[i]);}
}
var f=0;
function schedule(){
	if(f){return;}
	f=requestAnimationFrame(function(){f=0;all();});
}
if(typeof MutationObserver!=='undefined'){
	new MutationObserver(function(recs){
		for(var i=0;i<recs.length;i++){
			var a=recs[i].addedNodes;
			for(var j=0;j<a.length;j++){
				var el=a[j];
				if(el.nodeType!==1){continue;}
				if(el.matches&&el.matches(S)){sync(el);}
				if(el.querySelectorAll){
					var d=el.querySelectorAll(S),k=0;
					for(;k<d.length;k++){sync(d[k]);}
				}
			}
		}
	}).observe(document.documentElement,{childList:true,subtree:true});
}
window.addEventListener('resize',schedule);
window.addEventListener('orientationchange',schedule);
document.addEventListener('DOMContentLoaded',all);
window.addEventListener('load',all);
})();
JS;

		wp_print_inline_script_tag( $js, array( 'id' => 'aae-a-menu-breakpoint-bootstrap' ) );
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
						if ( file_exists( AAEADDON_PATH . $min_path ) ) {
							$path = $min_path;
						}
					}
					$file_path = AAEADDON_PATH . $path;
					$version   = file_exists( $file_path ) ? filemtime( $file_path ) : AAEADDON_VERSION;

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
						[
							'ajaxUrl' => admin_url( 'admin-ajax.php' ),
							// Verified by ajax_get_menu_html().
							'nonce'   => Nonce::create( Nonce::LOOP_GRID ),
						]
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
						if ( file_exists( AAEADDON_PATH . $min_path ) ) {
							$style_path = $min_path;
						}
					}
					$style_file = AAEADDON_PATH . $style_path;
					$style_ver  = file_exists( $style_file ) ? filemtime( $style_file ) : AAEADDON_VERSION;

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
	 * Enqueue the EDITOR-ONLY atomic widget stylesheets into the preview iframe.
	 *
	 * WHAT IS LEFT HERE, AND WHY IT IS ONLY THIS. An `editor_style_handle` is
	 * deliberately absent from `register_atomic_styles()` (which feeds the
	 * frontend), so `register_editor_style()` below is the ONLY thing in the
	 * codebase that registers it — nothing else can, and without this the sheet
	 * never reaches the preview and never reaches a published page either.
	 * Today that is one widget: `aae-a-loop-grid-editor-css`.
	 *
	 * WHAT WAS REMOVED, AND WHY IT WAS SAFE. This method used to enqueue every
	 * active widget's ordinary `style_handle` too, and add the `editor-preview`
	 * dependency to each. Both halves were already being done, in the same
	 * request, by two other passes:
	 *
	 *   - `enqueue_widget_scripts_in_preview()` walks the same registry with the
	 *     same `is_widget_active()` filter on `elementor/preview/enqueue_scripts`
	 *     and enqueues the same `style_handle` (its docblock says so).
	 *   - `fix_preview_css_order()` re-adds the `editor-preview` dependency to
	 *     every AAE handle — `editor_style_handle` included — at
	 *     `wp_print_styles` priority 0, which is the only pass that can also
	 *     reach Elementor's later-registered per-document CSS.
	 *
	 * MEASURED, 16 editor loads with the old loop toggled on/off/on/off across
	 * two pages: the preview carried 26 AAE stylesheets with it and 25 without,
	 * with ZERO landing before `editor-preview` either way. The single missing
	 * sheet was the editor-only one — which is exactly what this method now
	 * owns, and nothing else changed. Editor-ready time was indistinguishable
	 * (within-state spread 2.3-4.6 s, larger than any gap between states, and
	 * the direction flipped between batches).
	 *
	 * So this is not a speed change — it removes duplicated work while keeping
	 * the one job no other pass can do.
	 *
	 * `add_style_dependency()` is still called here as belt-and-braces: it costs
	 * one array check and keeps this sheet correctly ordered even if
	 * `fix_preview_css_order()` is ever narrowed.
	 */
	public function enqueue_atomic_preview_styles(): void {
		$this->register_atomic_styles();

		foreach ( $this->get_available_widgets() as $widget_id => $widget_data ) {
			if ( ! $this->is_widget_active( $widget_id ) ) {
				continue;
			}

			if ( empty( $widget_data['editor_style_handle'] ) || empty( $widget_data['editor_style_path'] ) ) {
				continue;
			}

			$this->register_editor_style( $widget_data['editor_style_handle'], $widget_data['editor_style_path'] );
			$this->add_style_dependency( $widget_data['editor_style_handle'], 'editor-preview' );
			wp_enqueue_style( $widget_data['editor_style_handle'] );
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
			if ( file_exists( AAEADDON_PATH . $min_path ) ) {
				$path = $min_path;
			}
		}
		$file_path = AAEADDON_PATH . $path;
		$version   = file_exists( $file_path ) ? filemtime( $file_path ) : AAEADDON_VERSION;
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
	 * Memoised answer to is_dev_environment().
	 *
	 * SAFE TO MEMOISE, unlike count_active_atomic() — and the difference is the
	 * whole point. Every input here is fixed for the life of the request: two
	 * constants, the SAPI-populated $_SERVER entries, and the site's own
	 * home_url(). Nothing in a normal request can change any of them, so a cached
	 * answer cannot go stale. count_active_atomic() reads OPTIONS, which three
	 * migrations rewrite mid-request, which is exactly why its memo was reverted.
	 *
	 * @var bool|null
	 */
	private $dev_environment = null;

	/**
	 * Return true when running in a dev / local environment.
	 *
	 * Decides whether minified assets are served, whether the remote preset cache
	 * is bypassed, and — through aaeaddon_asset_version() — whether admin assets are
	 * cacheable at all. A FALSE NEGATIVE makes a developer's edits appear not to
	 * take; a false positive costs a production site its asset caching.
	 *
	 * @return bool
	 */
	private function is_dev_environment(): bool
	{
		if (null === $this->dev_environment) {
			$this->dev_environment = self::detect_dev_environment();
		}

		return $this->dev_environment;
	}

	/**
	 * The detection itself — pure, static, and deliberately NOT memoised.
	 *
	 * Split out from is_dev_environment() so the rule can be exercised for every
	 * host shape in one process. A memoised method can only be measured once, so
	 * a suite covering seven cases against it would really cover the first one
	 * seven times.
	 *
	 * WP_DEBUG IS THE MASTER SWITCH. With it off this is a production site
	 * whatever else says, and nothing below is consulted. Worth knowing it
	 * outranks SCRIPT_DEBUG, which WordPress itself treats as independent — a
	 * site running SCRIPT_DEBUG on with WP_DEBUG off gets minified assets here.
	 * That is a deliberate local choice, not WordPress convention.
	 *
	 * Past that gate, any of these means dev:
	 *
	 *   - `wp_get_environment_type()` says `local` or `development` — WordPress's
	 *     own declared answer, set by whoever built the site, so it is asked
	 *     first. It defaults to `production` when nothing is set, which is why
	 *     `production` is NOT treated as authoritative: doing so would turn every
	 *     unconfigured local site into a production one.
	 *   - `SCRIPT_DEBUG` is on.
	 *   - The request host, or the site's own home_url() host, is loopback or a
	 *     development TLD.
	 *   - The server's own IP is loopback.
	 *
	 * THE PORT IS STRIPPED FIRST, and its absence was a real bug. `HTTP_HOST`
	 * carries `host:port`, so `localhost:8080`, `127.0.0.1:8000` and
	 * `mysite.local:8888` matched nothing and reported PRODUCTION — that is MAMP,
	 * `wp server`, Docker and Local's localhost router mode, all served minified
	 * assets and told their edits had not taken. Measured before the fix: 4 of 8
	 * real dev host shapes wrong.
	 *
	 * IPv6 loopback (`::1`) is included for the same reason — nginx and Apache
	 * both report it on a modern dual-stack box, and only `127.0.0.1` was tested.
	 *
	 * KNOWN LIMIT: `HTTP_HOST` comes from a request header, so a crafted
	 * `Host: something.local` can force dev mode on. It is still checked, because
	 * it is what catches a production database copied to a local machine, where
	 * home_url() still names the live domain. The cost of being wrong is bounded
	 * — unminified assets, a bypassed preset cache and an uncacheable asset
	 * version, for that one request, with no data exposed — so the detection is
	 * worth more than the hardening. Revisit that trade before widening what this
	 * gates.
	 *
	 * @return bool
	 */
	public static function detect_dev_environment(): bool
	{
		// One test, not two: the short-circuit means !WP_DEBUG is never evaluated
		// against an undefined constant.
		if (! defined('WP_DEBUG') || ! WP_DEBUG) {
			return false;
		}

		if (function_exists('wp_get_environment_type')
			&& in_array(wp_get_environment_type(), ['local', 'development'], true)
		) {
			return true;
		}

		if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
			return true;
		}

		$hosts = [self::request_host()];

		if (function_exists('home_url')) {
			$hosts[] = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
		}

		foreach (array_filter(array_unique($hosts)) as $host) {
			if (self::is_local_host($host)) {
				return true;
			}
		}

		// Windows IIS uses LOCAL_ADDR; Apache/Nginx use SERVER_ADDR.
		$server_ip = '';
		if (isset($_SERVER['SERVER_ADDR'])) {
			$server_ip = sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR']));
		} elseif (isset($_SERVER['LOCAL_ADDR'])) {
			$server_ip = sanitize_text_field(wp_unslash($_SERVER['LOCAL_ADDR']));
		}
		$server_ip = strtolower($server_ip);

		return in_array($server_ip, ['127.0.0.1', '::1'], true);
	}

	/**
	 * The request's host, lower-cased, with any port removed.
	 *
	 * IPv6 arrives bracketed per RFC 3986 (`[::1]:8080`), so the bracketed form is
	 * unwrapped rather than port-stripped — a naive `:\d+$` trim would turn a bare
	 * `::1` into `::`. Exactly one colon means `host:port`; more than one and
	 * unbracketed is a raw IPv6 literal and is left alone.
	 *
	 * @return string
	 */
	private static function request_host(): string
	{
		$host = strtolower(trim(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'] ?? ''))));

		if ('' === $host) {
			return '';
		}

		if ('[' === $host[0]) {
			$close = strpos($host, ']');

			return false === $close ? $host : substr($host, 1, $close - 1);
		}

		if (1 === substr_count($host, ':')) {
			return (string) strstr($host, ':', true);
		}

		return $host;
	}

	/**
	 * Is this hostname a local one?
	 *
	 * `.local` is mDNS/Bonjour; `.test` and `.localhost` are reserved for exactly
	 * this purpose by RFC 6761 and can never be registered, so none of the three
	 * can collide with a real production domain. Deliberately NOT `.dev` — that
	 * is a real gTLD with enforced HSTS and live sites on it.
	 *
	 * @param string $host Already lower-cased and port-stripped.
	 *
	 * @return bool
	 */
	private static function is_local_host(string $host): bool
	{
		if (in_array($host, ['127.0.0.1', '::1', 'localhost'], true)) {
			return true;
		}

		foreach (['.local', '.test', '.localhost'] as $tld) {
			if (str_ends_with($host, $tld)) {
				return true;
			}
		}

		return false;
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

		// Whether this site has moved to Elementor V4, and what the user has
		// already said about it. Read by lib/systemVisibility.js — which is the
		// ONLY place allowed to turn any of it into a visibility rule.
		$configs['atomic_optin'] = $this->atomic_optin_signal();

		// The return path out of an accidental "Enable" — see the block above
		// capture_atomic_undo(). `available => false` most of the time.
		$configs['atomic_undo'] = $this->atomic_undo_offer();

		// Can a V4 (atomic) starter template or page be imported here at all?
		// Read by the starter-template grids in BOTH modules (dashboard and
		// page-import share this filter) to badge V4 cards and to disable their
		// Import button. `in_use` is deliberately NOT shipped here -- it is asked
		// fresh at click time (ajax_atomic_import_status), because a payload
		// snapshot goes stale the moment the first V4 import lands.
		$configs['atomic_import'] = [ 'available' => self::is_elementor_atomic_active() ];

		return $configs;
	}

	/**
	 * AJAX handler — save atomic widget toggle states.
	 */
	public function ajax_save_settings(): void
	{
		Nonce::check_ajax( Nonce::ADMIN, 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['fields'])) {
			wp_send_json_error(esc_html__('Missing fields.', 'animation-addons-for-elementor'));
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decode_slug_map() runs sanitize_text_field() on the raw value before json_decode(), and write_widget_option()/write_extension_option() sanitise every key and value again before storing. The handler has already checked the nonce and the capability.
		$settings = self::decode_slug_map(wp_unslash($_POST['fields']));

		if (null === $settings) {
			wp_send_json_error(esc_html__('Invalid data.', 'animation-addons-for-elementor'));
		}

		$result = $this->write_widget_option($settings);

		// A hand save is the user's own choice, and it ENDS the undo offer:
		// restoring a snapshot over a list somebody has since curated would
		// destroy real work, which is the one thing an undo must never do.
		$this->forget_atomic_undo();

		wp_send_json($result);
	}

	/**
	 * Decode a `{ slug: bool }` map posted by the dashboard.
	 *
	 * @param string $raw Raw, already-unslashed request value.
	 *
	 * @return array|null Null when the payload is not a map.
	 */
	private static function decode_slug_map($raw): ?array
	{
		$decoded = json_decode(sanitize_text_field((string) $raw), true);

		return is_array($decoded) ? $decoded : null;
	}

	/**
	 * Write the atomic WIDGET option, sanitised, and drop the derived caches.
	 *
	 * Extracted from ajax_save_settings() so the opt-in notice's "Enable" can
	 * reuse it in the SAME request as its undo snapshot. Going back through the
	 * AJAX handler would mean the snapshot and the write land in two separate
	 * requests, with the handler's own forget_atomic_undo() deleting the
	 * snapshot the notice had just taken.
	 *
	 * @param array $settings Raw slug => state map.
	 *
	 * @return array{status:bool,total:int}
	 */
	private function write_widget_option(array $settings): array
	{
		// Build a clean associative array: slug => true for enabled.
		$clean = [];
		foreach ($settings as $slug => $state) {
			$slug = sanitize_key($slug);

			if (isset($this->get_widgets_registry()[$slug]) && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		$updated = \Wealcoder\AnimationAddons\Compat\Key_Bridge::update_option(self::OPTION_NAME, $clean);

		// Reset cache.
		$this->active_widgets = null;
		// Both are derived from the active set.
		$this->registerable_classes = null;
		$this->widget_active_cache  = [];

		return [
			'status' => $updated,
			'total'  => count($clean),
		];
	}

	/**
	 * AJAX handler — retrieve current atomic widget settings.
	 */
	public function ajax_get_settings(): void
	{
		Nonce::check_ajax( Nonce::ADMIN, 'nonce');

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
		// Editor-preview only: on the frontend get_atomic_settings() fills
		// `rendered_menu` server-side, so menu.js never reaches this. The
		// nonce rides AAE_MENU_CFG next to the ajax URL, and the action is
		// the same one every other editor-only endpoint in this class uses.
		Nonce::check_ajax( Nonce::LOOP_GRID, 'nonce');

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
	 * WP menu hierarchy. Reuses the `Nonce::LOOP_GRID` editor nonce.
	 */
	public function ajax_get_nav_menus(): void
	{
		Nonce::check_ajax( Nonce::LOOP_GRID, 'nonce');

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
		Nonce::check_ajax( Nonce::ADMIN, 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(esc_html__('Permission denied.', 'animation-addons-for-elementor'));
		}

		if (! isset($_POST['fields'])) {
			wp_send_json_error(esc_html__('Missing fields.', 'animation-addons-for-elementor'));
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decode_slug_map() runs sanitize_text_field() on the raw value before json_decode(), and write_widget_option()/write_extension_option() sanitise every key and value again before storing. The handler has already checked the nonce and the capability.
		$settings = self::decode_slug_map(wp_unslash($_POST['fields']));

		if (null === $settings) {
			wp_send_json_error(esc_html__('Invalid data.', 'animation-addons-for-elementor'));
		}

		$result = $this->write_extension_option($settings);

		// See the twin comment in ajax_save_settings().
		$this->forget_atomic_undo();

		wp_send_json($result);
	}

	/**
	 * Write the atomic EXTENSION option, sanitised, plus the "offered" baseline.
	 *
	 * Extracted from ajax_save_extension_settings() for the same reason as
	 * write_widget_option() — see its docblock.
	 *
	 * @param array $settings Raw slug => state map.
	 *
	 * @return array{status:bool,total:int}
	 */
	private function write_extension_option(array $settings): array
	{
		$clean = [];
		foreach ($settings as $slug => $state) {
			$slug = sanitize_key($slug);

			if (isset($this->extensions_registry()[$slug]) && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		$updated = \Wealcoder\AnimationAddons\Compat\Key_Bridge::update_option(self::EXTENSIONS_OPTION_NAME, $clean);

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
		update_option(self::EXTENSIONS_OFFERED_OPTION_NAME, array_keys($this->extensions_registry()));

		// Reset cache.
		$this->active_extensions = null;

		return [
			'status' => $updated,
			'total'  => count($clean),
		];
	}

	/**
	 * AJAX handler — retrieve current atomic extension settings.
	 */
	public function ajax_get_extension_settings(): void
	{
		Nonce::check_ajax( Nonce::ADMIN, 'nonce');

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

	/* =====================================================================
	 *  "This site has moved to V4" — detection, and the one-time offer it feeds
	 *
	 *  The dashboard hides the era a site does not use (see
	 *  src/modules/dashboard/lib/systemVisibility.js). That is right for a
	 *  settled site and wrong for the moment one MOVES: a long-time v3 user who
	 *  switches Elementor's V4 on has no way to discover that AAE ships an
	 *  atomic registry at all, because the tab that would tell them is hidden
	 *  precisely BECAUSE they have nothing atomic switched on yet — and
	 *  "nothing atomic switched on" is exactly what someone who just arrived
	 *  looks like.
	 *
	 *  So the notice is the discovery path, and it is the ONLY thing these
	 *  methods add. Nothing here registers, unregisters or renders anything;
	 *  every pre-existing rule keeps its pre-existing answer until the user
	 *  presses the button, which does no more than switching the atomic widgets
	 *  on by hand already does.
	 * =================================================================== */

	/**
	 * Is Elementor's own V4 (Atomic) element system switched on for this site?
	 *
	 * `e_atomic_elements` ships hidden and INACTIVE; Elementor defaults it on
	 * only for sites first installed on 4.0+. On every upgraded site it is
	 * therefore a deliberate act by the user — which is the moment AAE has
	 * something worth saying.
	 *
	 * Deliberately NOT folded into meets_requirements(). That gate decides
	 * whether AAE's atomic registry loads AT ALL, and it has to keep saying yes
	 * while the experiment is off: the Schemas must stay registered or Elementor
	 * strips every saved `aae_*` prop out of `_elementor_data` on the next save
	 * (see the docblock on Bootstrap::init()). Toggling the experiment off may
	 * cost the user their editor panel; it must never cost them their saved work.
	 */
	public static function is_elementor_atomic_active(): bool
	{
		if (! did_action('elementor/loaded') || ! class_exists('\\Elementor\\Plugin')) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;

		if (! isset($elementor->experiments) || ! method_exists($elementor->experiments, 'is_feature_active')) {
			return false;
		}

		return (bool) $elementor->experiments->is_feature_active('e_atomic_elements');
	}

	/**
	 * Switch on every atomic widget and extension the site's CONTENT already uses.
	 *
	 * The V4 twin of Animation_Settings::maybe_enable_used_v3_widgets(), and it
	 * exists for the same reason: an unregistered widget renders NOTHING — no
	 * error, no wrapper — so a starter template built from `e-aae-a-*` elements
	 * imports into a site where nothing has switched them on and every page
	 * comes up blank, looking exactly like a missing-class problem. Nothing on
	 * the import path wrote `aaeaddon_atomic_widgets` before this; only the dashboard
	 * save handlers and the opt-in flow did.
	 *
	 * Two differences from the v3 guard, both deliberate:
	 *
	 * 1. It MERGES into the saved option and only ever switches ON. The v3 guard
	 *    bails once `aaeaddon_save_widgets` has been written by hand; here an
	 *    explicit switch-off is still honoured for everything the imported
	 *    content does not use, but the widgets it DOES use come on — importing
	 *    a demo is the user asking for those pages to render.
	 * 2. It matches `elType` as well as `widgetType`. AAE's slider, counter,
	 *    button and every offcanvas part save as their OWN elType — atomic
	 *    container-style elements are not "widgets" in the data at all — so a
	 *    `widgetType`-only scan enables the leaf widgets and leaves every
	 *    container blank. Same finding as Pro's usage scanner.
	 *
	 * Internal children (`WIDGET_PARENT_MAP`) resolve to their parent, because
	 * that is the card the dashboard shows and `is_widget_active()` inherits
	 * through it. Extensions leave no name in the data, only a prop inside some
	 * other element's settings, so they are detected through the registry's
	 * own `usage_prop` declarations — the same rule Pro's usage counter uses —
	 * on the DECODED data, not by regex.
	 *
	 * @param int[]|null $post_ids Restrict the scan to these posts; null = whole site.
	 * @return array{widgets: string[], extensions: string[]} What was newly switched on.
	 */
	public function enable_used_atomic( ?array $post_ids = null ): array
	{
		global $wpdb;

		// Two families, two markers: our elements carry `e-aae-a-`, our
		// extension props carry `aae_` — an extension can sit on a core
		// e-heading with no AAE element anywhere on the page.
		$like = [
			'%' . $wpdb->esc_like( '"e-aae-a-' ) . '%',
			'%' . $wpdb->esc_like( '"aae_' ) . '%',
		];

		// One complete literal per branch rather than a base string appended to.
		// Appending is what puts a variable into the query TEXT; written out this
		// way the only thing interpolated is a run of %d generated from count().
		if ( null === $post_ids ) {
			$sql  = "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND ( meta_value LIKE %s OR meta_value LIKE %s )";
			$args = $like;
		} else {
			$post_ids = array_values( array_filter( array_map( 'intval', $post_ids ) ) );
			if ( empty( $post_ids ) ) {
				return [ 'widgets' => [], 'extensions' => [] ];
			}
			// One %d per id; the ids themselves travel as prepare() arguments.
			$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
			$sql          = "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND ( meta_value LIKE %s OR meta_value LIKE %s ) AND post_id IN ({$placeholders})";
			$args         = array_merge( $like, $post_ids );
		}

		// $sql is whichever of the two literals above the branch chose; the only
		// variable part of it is the generated run of %d.
		$rows = $wpdb->get_col( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- one %d per id; the sniff cannot count through array_merge().

		if ( empty( $rows ) ) {
			return [ 'widgets' => [], 'extensions' => [] ];
		}

		$registry      = $this->get_widgets_registry();
		$parents       = $this->widget_parent_map();
		$always_active = $this->always_active_lookup();
		$saved_widgets = $this->get_saved_options();
		$saved_exts    = $this->get_saved_extension_options();

		// prop => [ [ slug, kind ], ... ] — one prop can belong to several extensions.
		$prop_index = [];
		foreach ( $this->get_extension_usage_props() as $slug => $def ) {
			foreach ( $def['prop'] as $prop ) {
				$prop_index[ $prop ][] = [ $slug, $def['kind'] ];
			}
		}

		$new_widgets = [];
		$new_exts    = [];

		foreach ( $rows as $row ) {
			$row = (string) $row;

			if ( preg_match_all( '/"(?:widgetType|elType)":"e-([a-z0-9-]+)"/', $row, $m ) ) {
				foreach ( $m[1] as $slug ) {
					if ( isset( $parents[ $slug ] ) ) {
						$slug = $parents[ $slug ];
					}
					if ( ! isset( $registry[ $slug ] ) || isset( $always_active[ $slug ] ) || isset( $saved_widgets[ $slug ] ) ) {
						continue;
					}
					$new_widgets[ $slug ] = true;
				}
			}

			if ( ! empty( $prop_index ) && false !== strpos( $row, '"aae_' ) ) {
				$decoded = json_decode( $row, true );
				if ( is_array( $decoded ) ) {
					$this->collect_used_extensions( $decoded, $prop_index, $saved_exts, $new_exts );
				}
			}
		}

		if ( ! empty( $new_widgets ) ) {
			$this->write_widget_option( $saved_widgets + $new_widgets );
		}

		if ( ! empty( $new_exts ) ) {
			$this->write_extension_option( $saved_exts + $new_exts );
		}

		return [
			'widgets'    => array_keys( $new_widgets ),
			'extensions' => array_keys( $new_exts ),
		];
	}

	/**
	 * Hook wrapper — whole-site scan after a content import.
	 */
	public function enable_used_atomic_after_import(): void
	{
		$this->enable_used_atomic( null );
	}

	/**
	 * Walk decoded element data and mark every extension whose usage prop
	 * qualifies. Same three kinds as Pro's Widget_Usage::prop_qualifies():
	 * `present` (the key exists), `boolean` (value === true) and `filled`
	 * (anything with content — an interactions list, a regex string).
	 * Present is not enabled: a switched-off section leaves its prop behind
	 * with `"value":false`, so the value has to be looked at.
	 */
	private function collect_used_extensions( array $node, array $prop_index, array $saved, array &$found ): void
	{
		foreach ( $node as $key => $value ) {
			if ( is_string( $key ) && isset( $prop_index[ $key ] ) ) {
				$inner = is_array( $value ) && array_key_exists( 'value', $value ) ? $value['value'] : $value;

				foreach ( $prop_index[ $key ] as [ $slug, $kind ] ) {
					if ( isset( $saved[ $slug ] ) || isset( $found[ $slug ] ) ) {
						continue;
					}
					if ( 'present' === $kind
						|| ( 'boolean' === $kind && true === $inner )
						|| ( 'boolean' !== $kind && self::value_has_content( $inner ) ) ) {
						$found[ $slug ] = true;
					}
				}
			}

			if ( is_array( $value ) ) {
				$this->collect_used_extensions( $value, $prop_index, $saved, $found );
			}
		}
	}

	private static function value_has_content( $value ): bool
	{
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( self::value_has_content( $item ) ) {
					return true;
				}
			}
			return false;
		}

		return ! ( null === $value || '' === $value || false === $value || 0 === $value );
	}

	/**
	 * The two facts the V4 import UI needs, asked fresh.
	 *
	 * `available` -- Elementor's atomic element system is on, so a V4 template's
	 * elements have something to render with. `in_use` -- this site already
	 * holds V4 content, which is what decides HOW a V4 design system lands
	 * (`match_site` on an empty site, `keep_create` beside existing classes;
	 * see inc/admin/atomic-kit-import.php) and is therefore what the second-
	 * import warning is about.
	 *
	 * `has_atomic_usage()` is the same cached signal the import step snapshots
	 * into `aae_site_has_atomic`, so the dialog and the importer cannot
	 * disagree about which mode is coming.
	 *
	 * @return array{available: bool, in_use: bool}
	 */
	public static function import_signal(): array
	{
		return [
			'available' => self::is_elementor_atomic_active(),
			'in_use'    => self::has_atomic_usage(),
		];
	}

	/**
	 * Does this site's CONTENT already use Elementor V4 elements?
	 *
	 * The honest signal, and the stronger of the two: the experiment switch says
	 * what someone INTENDED, a saved `e-flexbox` says what they actually built.
	 *
	 * Every atomic type Elementor ships is `e-`-prefixed and lands in
	 * `_elementor_data` under one of two keys — a container saves as
	 * `"elType":"e-flexbox"`, a leaf as `"elType":"widget","widgetType":"e-heading"`
	 * — so one alternation covers both, and covers types added in later Elementor
	 * releases without this having to be kept in sync with a list. Its v3 twin
	 * (`Animation_Settings::v3_widget_name_regexp()`) has to enumerate names only
	 * because v3 widget names share no prefix at all.
	 *
	 * A false positive is harmless here by construction: the worst it can do is
	 * offer the atomic set to someone who does not want it, and the notice has a
	 * dismiss button. Nothing on this path enables anything on its own.
	 *
	 * Cached for an hour, same as has_v3_usage(), with the same negative-only
	 * bust on save_post — see maybe_invalidate_atomic_usage().
	 */
	public static function has_atomic_usage(): bool
	{
		$cached = get_transient(self::USAGE_TRANSIENT);

		if (false !== $cached) {
			return '1' === $cached;
		}

		global $wpdb;

		$found = (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->postmeta}
				 WHERE meta_key = '_elementor_data'
				   AND meta_value REGEXP %s
				 LIMIT 1",
				'"(elType|widgetType)":"e-'
			)
		);

		set_transient(self::USAGE_TRANSIENT, $found ? '1' : '0', HOUR_IN_SECONDS);

		return $found;
	}

	/**
	 * How many of this site's posts are built with Elementor V4 elements?
	 *
	 * "Pages on this site are built with V4" is true of a site with one test
	 * page and of a site that has moved wholesale, and those two people want
	 * opposite things. The number is the difference, so the notice states it.
	 *
	 * MUST EXCLUDE REVISIONS, which the boolean never had to care about.
	 * _elementor_data is copied onto every revision, so a single page edited
	 * forty times owns forty-one rows carrying atomic markup -- counting
	 * postmeta alone would announce "41 pages" to someone who built one. Hence
	 * the join, and hence auto-drafts and trashed posts are dropped too: they
	 * are not pages the user thinks of as part of their site.
	 *
	 * Same hour-long cache and the same negative-only bust as the boolean; an
	 * hour-stale count is the right trade for one sentence in a notice that
	 * disappears the moment it is answered.
	 */
	public static function count_atomic_usage(): int
	{
		$cached = get_transient(self::USAGE_COUNT_TRANSIENT);

		if (false !== $cached) {
			return (int) $cached;
		}

		global $wpdb;

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT pm.post_id)
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_elementor_data'
				   AND p.post_type != 'revision'
				   AND p.post_status NOT IN ('auto-draft', 'trash')
				   AND pm.meta_value REGEXP %s",
				'"(elType|widgetType)":"e-'
			)
		);

		set_transient(self::USAGE_COUNT_TRANSIENT, (string) $count, HOUR_IN_SECONDS);

		return $count;
	}

	/**
	 * Drop the cached usage answer when a save may have changed it.
	 *
	 * Only the NEGATIVE is busted, which is what keeps this affordable on a hook
	 * as hot as save_post: after the first bust every later call is one
	 * get_transient() read.
	 *
	 *  - cached '0' → a save could make it '1', so re-ask next time.
	 *  - cached '1' → already known, and nothing downstream needs it to go back
	 *    to '0' in a hurry; by then the notice has already been answered.
	 *  - nothing cached → nothing to delete.
	 *
	 * Deliberately NOT `updated_post_meta`/`added_post_meta`: those fire many
	 * times per save and are what got the Loop Grid count cache deleted.
	 *
	 * @param int           $post_id Saved post.
	 * @param \WP_Post|null $post    Saved post object.
	 */
	public static function maybe_invalidate_atomic_usage($post_id, $post = null): void
	{
		if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
			return;
		}

		// An auto-draft holds no content yet; the real save follows.
		if ($post instanceof \WP_Post && 'auto-draft' === $post->post_status) {
			return;
		}

		if ('0' !== get_transient(self::USAGE_TRANSIENT)) {
			return;
		}

		delete_transient(self::USAGE_TRANSIENT);

		// The two answer the same question at different resolutions, so a bust
		// that left the count behind would have the notice reporting "0 pages"
		// on a site the boolean had just decided does use V4.
		delete_transient(self::USAGE_COUNT_TRANSIENT);
	}

	/**
	 * Is anything in AAE's atomic registry switched on right now?
	 *
	 * Mirrors the JS `V4_HAS_ACTIVE` exactly — raw saved option, intersected
	 * with the registry, `is_internal` entries skipped — because the two decide
	 * the same thing from opposite ends. If they disagree the site lands in the
	 * one state neither was designed for: PHP calls it settled and sends no
	 * notice, JS calls it empty and shows no V4 tab, and the atomic registry
	 * becomes unreachable from the dashboard.
	 */
	/**
	 * PUBLIC because the editor-bridge enqueue asks it too.
	 *
	 * `Atomic\Assets::enqueue_editor_bridge()` ships ~700 KB of editor JS whose
	 * only job is to power panel controls for AAE's atomic widgets and
	 * extensions. With none of them switched on there are no controls to power,
	 * so the honest precondition is this exact question — and asking it through
	 * this method rather than re-reading the option keeps one answer, intersected
	 * with the registry and skipping `is_internal`, in one place.
	 */
	public function has_active_atomic(): bool
	{
		$counts = $this->count_active_atomic();

		return $counts['widgets'] > 0 || $counts['extensions'] > 0;
	}

	/**
	 * How many atomic widgets and extensions are switched on.
	 *
	 * The undo notice names both numbers — "37 widgets and 20 extensions will be
	 * switched back off" is a sentence somebody can check against the list in
	 * front of them, where "your atomic set" is not.
	 *
	 * @return array{widgets:int,extensions:int}
	 */
	/*
	 * NOT MEMOISED, and that was tried and reverted (2026-08-19).
	 *
	 * A per-request memo looks free here — three callers ask on one dashboard
	 * load. It is not: THREE OTHER PLACES write these options directly rather
	 * than through write_widget_option()/write_extension_option() —
	 * backfill_formerly_forced_widgets(), migrate_newly_offered_extensions() and
	 * backfill_v3_admin_extensions(), all on admin_init — so a memo filled before
	 * one of them runs answers from before the write, order-dependently. Five
	 * assertions in verify-atomic-optin.php caught exactly that.
	 *
	 * What it bought was one walk of ~56 in-memory array entries. Not worth an
	 * order-dependent staleness bug, and not worth a rule that every future
	 * writer must remember to reset something.
	 */
	private function count_active_atomic(): array
	{
		$saved   = $this->get_saved_options();
		$widgets = 0;

		foreach ($this->get_widgets_registry() as $slug => $def) {
			if (! empty($def['is_internal'])) {
				continue;
			}

			if (isset($saved[$slug])) {
				$widgets++;
			}
		}

		$ext_saved  = $this->get_saved_extension_options();
		$extensions = 0;

		foreach ($this->extensions_registry() as $slug => $def) {
			if (isset($ext_saved[$slug])) {
				$extensions++;
			}
		}

		return [
			'widgets'    => $widgets,
			'extensions' => $extensions,
		];
	}

	/* =====================================================================
	 *  The return path
	 *
	 *  "Enable atomic features" switches on everything this site can register
	 *  in one click. That is the right shape for the offer — and it is exactly
	 *  the shape that needs a way back, because a click that changes a hundred
	 *  things is a click somebody can make by mistake.
	 *
	 *  Getting back is not the same as switching everything off. The site had a
	 *  STATE before the click — usually no rows at all, sometimes a half-built
	 *  one from an abandoned wizard run — and only restoring that exact state
	 *  puts the user where they were. So the enable snapshots first, and the
	 *  undo restores; nothing here computes what the state "should" be.
	 *
	 *  Note what did NOT need saving: the v3 options. Accepting the offer never
	 *  touched them, so an accidental accept never cost the user their V3 site
	 *  in the first place — which is why this is an undo of one option pair
	 *  rather than a migration rollback.
	 * =================================================================== */

	/**
	 * Record the atomic state as it is right now, before the offer writes over it.
	 *
	 * Stores the "offered" baseline too: write_extension_option() rewrites it,
	 * and leaving the new value behind after an undo would change what
	 * migrate_newly_offered_extensions() does on the next admin_init — a
	 * difference nobody would connect to a button they pressed a week ago.
	 *
	 * @param string $signal Signal strength at the moment of the accept.
	 */
	private function capture_atomic_undo(string $signal): void
	{
		$missing = '__aae_option_absent__';

		$widgets    = get_option(self::OPTION_NAME, $missing);
		$extensions = get_option(self::EXTENSIONS_OPTION_NAME, $missing);
		$offered    = get_option(self::EXTENSIONS_OFFERED_OPTION_NAME, $missing);

		update_option(
			self::UNDO_OPTION_NAME,
			[
				'widgets'           => $missing === $widgets ? [] : $widgets,
				'widgets_absent'    => $missing === $widgets,
				'extensions'        => $missing === $extensions ? [] : $extensions,
				'extensions_absent' => $missing === $extensions,
				'offered'           => $missing === $offered ? [] : $offered,
				'offered_absent'    => $missing === $offered,
				'signal'            => $signal,
				'time'              => time(),
			],
			false
		);
	}

	/**
	 * Put the three options back exactly as capture_atomic_undo() found them.
	 *
	 * delete_option() where the row was absent — see UNDO_OPTION_NAME's docblock
	 * for why an empty array is a different site state, not a tidier one.
	 *
	 * @param array $undo A stored snapshot.
	 */
	private function restore_atomic_undo(array $undo): void
	{
		$map = [
			self::OPTION_NAME                    => ['widgets', 'widgets_absent'],
			self::EXTENSIONS_OPTION_NAME         => ['extensions', 'extensions_absent'],
			self::EXTENSIONS_OFFERED_OPTION_NAME => ['offered', 'offered_absent'],
		];

		foreach ($map as $name => $keys) {
			list($value_key, $absent_key) = $keys;

			if (! empty($undo[$absent_key])) {
				\Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( $name );

				continue;
			}

			$value = is_array($undo[$value_key] ?? null) ? $undo[$value_key] : [];

			// SANITISED ON THE WAY BACK IN, even though we wrote the snapshot
			// ourselves. What it captured is whatever the option held BEFORE the
			// opt-in, which is not necessarily something this plugin wrote — an
			// import, an older release, or another plugin could have left any shape
			// there, and restoring it verbatim would put it back unchecked.
			//
			// Not a privilege boundary (writing that option already needs database
			// access) so much as refusing to be the path that launders unvalidated
			// data into a live option. Anything the two writers produced passes
			// through unchanged, so the exact-restore promise is intact.
			$value = self::EXTENSIONS_OFFERED_OPTION_NAME === $name
				? self::sanitize_slug_list($value)
				: self::sanitize_slug_map($value);

			update_option($name, $value);
		}

		// Every derived cache the two writers reset, reset again — this wrote
		// the same options they do.
		$this->active_widgets       = null;
		$this->registerable_classes = null;
		$this->widget_active_cache  = [];
		$this->active_extensions    = null;
	}

	/**
	 * End the undo offer. Called on an accepted "keep", on a completed undo, and
	 * on any hand save of either atomic list.
	 */
	/**
	 * `slug => true` for every key that survives sanitize_key().
	 *
	 * The shape both atomic settings options are stored in. Values are discarded
	 * rather than preserved: the writers only ever store `true`, so anything else
	 * arrived from outside and "on" is the only state the readers understand.
	 *
	 * @param array $value Raw map.
	 *
	 * @return array
	 */
	private static function sanitize_slug_map(array $value): array
	{
		$clean = [];

		foreach ($value as $slug => $state) {
			$slug = sanitize_key($slug);

			if ('' !== $slug && ! empty($state)) {
				$clean[$slug] = true;
			}
		}

		return $clean;
	}

	/**
	 * A LIST of slugs — the shape EXTENSIONS_OFFERED_OPTION_NAME uses.
	 *
	 * Deliberately separate from sanitize_slug_map(): that option is written as
	 * `array_keys( $registry )`, so running it through the map sanitiser would
	 * turn a list into `0 => true, 1 => true` and quietly destroy the record of
	 * what the user has been shown — which is the one thing standing between
	 * migrate_newly_offered_extensions() and switching extensions back on by
	 * itself.
	 *
	 * @param array $value Raw list.
	 *
	 * @return array
	 */
	private static function sanitize_slug_list(array $value): array
	{
		$clean = [];

		foreach ($value as $slug) {
			if (! is_scalar($slug)) {
				continue;
			}

			$slug = sanitize_key((string) $slug);

			if ('' !== $slug) {
				$clean[] = $slug;
			}
		}

		return array_values(array_unique($clean));
	}

	private function forget_atomic_undo(): void
	{
		\Wealcoder\AnimationAddons\Compat\Key_Bridge::delete_option( self::UNDO_OPTION_NAME );
	}

	/**
	 * The undo offer, for the dashboard payload.
	 *
	 * Reports `available => false` rather than omitting itself, so the React
	 * side reads one shape whatever the state.
	 *
	 * @return array{available:bool,widgets?:int,extensions?:int,expires?:int}
	 */
	public function atomic_undo_offer(): array
	{
		$undo = get_option(self::UNDO_OPTION_NAME);

		if (! is_array($undo) || empty($undo['time'])) {
			return ['available' => false];
		}

		if ((time() - (int) $undo['time']) > self::UNDO_WINDOW) {
			return ['available' => false];
		}

		$counts = $this->count_active_atomic();

		// A snapshot with NOTHING switched on is not an undo, it is a leftover.
		// The enable takes its snapshot before the first write, so a request that
		// dies in between (closed tab, PHP timeout) leaves one behind describing
		// a state nothing ever moved away from. Offering it would put a bar on
		// screen reading "0 atomic widgets and 0 atomic extensions are active",
		// whose Undo button restores what is already there.
		if (0 === $counts['widgets'] && 0 === $counts['extensions']) {
			return ['available' => false];
		}

		return [
			'available'  => true,
			'widgets'    => $counts['widgets'],
			'extensions' => $counts['extensions'],
			'expires'    => (int) $undo['time'] + self::UNDO_WINDOW,
		];
	}

	/**
	 * Rank a signal so a past dismissal can be compared against today's
	 * evidence. usage > experiment > none.
	 */
	private static function signal_rank(string $signal): int
	{
		$ranks = [
			'none'       => 0,
			'experiment' => 1,
			'usage'      => 2,
		];

		return $ranks[$signal] ?? 0;
	}

	/**
	 * The strongest evidence available that this site is on Elementor V4.
	 *
	 * Reads the CONTENT check first because it is the stronger claim, and the
	 * two are not nested: a site can hold V4 pages with the experiment since
	 * switched back off, and that is still a site whose pages want the atomic set.
	 */
	private static function atomic_signal_strength(): string
	{
		if (self::has_atomic_usage()) {
			return 'usage';
		}

		return self::is_elementor_atomic_active() ? 'experiment' : 'none';
	}

	/**
	 * Everything the dashboard notice needs, in one payload.
	 *
	 * Shipped nested under `addons_config`, NOT at the top level of the localize
	 * data — nested values keep their real JSON types, whereas wp_localize_script
	 * stringifies top-level scalars (which is why `v3_in_use` arrives as "1"/""
	 * and has to be read with `!!`).
	 *
	 * `in_use` is `null`, not `false`, when the question was never asked: the
	 * postmeta scan is skipped entirely once its answer cannot change what is on
	 * screen, and reporting an unasked question as "no" is how a cheap guard
	 * turns into a wrong fact somewhere downstream.
	 *
	 * @return array{
	 *     experiment:bool, in_use:bool|null, signal:string, state:string,
	 *     dismissed_signal:string, has_active:bool, show_notice:bool
	 * }
	 */
	public function atomic_optin_signal(): array
	{
		$stored = get_option(self::OPTIN_OPTION_NAME);
		$stored = is_array($stored) ? $stored : [];

		$state            = isset($stored['state']) ? (string) $stored['state'] : 'undecided';
		$dismissed_signal = isset($stored['signal']) ? (string) $stored['signal'] : 'none';

		$has_active = $this->has_active_atomic();

		// Nothing below can put a notice on screen once the user has accepted or
		// already has atomic switched on, so do not pay for the evidence.
		if ($has_active || 'accepted' === $state) {
			return [
				'experiment'       => false,
				'in_use'           => null,
				'in_use_count'     => null,
				'signal'           => 'none',
				'state'            => $state,
				'dismissed_signal' => $dismissed_signal,
				'has_active'       => $has_active,
				'show_notice'      => false,
			];
		}

		$in_use     = self::has_atomic_usage();
		$experiment = self::is_elementor_atomic_active();
		$signal     = $in_use ? 'usage' : ($experiment ? 'experiment' : 'none');

		$show = 'none' !== $signal
			&& (
				'dismissed' !== $state
				// A dismissal only covers evidence as strong as what was on the
				// table when it was made. Real pages built in V4 outrank a
				// switch, and are worth saying once even to someone who already
				// said no to the switch.
				|| self::signal_rank($dismissed_signal) < self::signal_rank($signal)
			);

		return [
			'experiment'       => $experiment,
			'in_use'           => $in_use,
			// LAST, and only when a notice is actually going on screen: this is
			// the one query on this path that cannot stop at the first row, and
			// a dismissed site would otherwise pay for a sentence nobody reads.
			'in_use_count'     => ($show && 'usage' === $signal) ? self::count_atomic_usage() : null,
			'signal'           => $signal,
			'state'            => $state,
			'dismissed_signal' => $dismissed_signal,
			'has_active'       => $has_active,
			'show_notice'      => $show,
		];
	}

	/**
	 * AJAX — record the user's answer to the atomic opt-in notice.
	 *
	 * Records the decision, and — when `fields` and `ext_fields` are posted with
	 * it — performs the enable itself.
	 *
	 * ONE REQUEST, deliberately. The first version called the two existing save
	 * endpoints from the browser and recorded the decision in a third call,
	 * which reads better and cannot support an undo: those handlers END the undo
	 * offer (a hand save is a deliberate choice), so a snapshot taken here would
	 * be deleted by the very writes it was taken for. Doing all three in one
	 * request also means an accept can never half-land — widgets on, extensions
	 * off, no decision recorded — from a closed tab.
	 *
	 * The SELECTION is still the browser's, and still comes from
	 * `src/modules/dashboard/lib/setupPresets.js`: this only sanitises and
	 * stores the map it is handed, exactly as the save handlers do, so "what a
	 * recommended setup enables" is never restated in PHP.
	 *
	 * The signal strength is re-read server-side and never taken from the
	 * request: it decides how long a dismissal lasts, and the transient makes
	 * re-reading it free.
	 */
	public function ajax_atomic_optin(): void
	{
		Nonce::check_ajax( Nonce::ADMIN, 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(__('Permission denied.', 'animation-addons-for-elementor'));
		}

		$state = isset($_POST['state']) ? sanitize_key(wp_unslash($_POST['state'])) : '';

		if (! in_array($state, ['accepted', 'dismissed'], true)) {
			wp_send_json_error(__('Invalid state.', 'animation-addons-for-elementor'));
		}

		$signal  = self::atomic_signal_strength();
		$enabled = null;

		// Both maps or neither: an accept that enabled only the widgets would
		// leave the site in a state the user never chose and the undo snapshot
		// would describe honestly but uselessly.
		if ('accepted' === $state && isset($_POST['fields'], $_POST['ext_fields'])) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decode_slug_map() runs sanitize_text_field() on the raw value before json_decode(), and write_widget_option()/write_extension_option() sanitise every key and value again before storing. The handler has already checked the nonce and the capability.
			$widgets    = self::decode_slug_map(wp_unslash($_POST['fields']));
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decode_slug_map() runs sanitize_text_field() on the raw value before json_decode(), and write_widget_option()/write_extension_option() sanitise every key and value again before storing. The handler has already checked the nonce and the capability.
			$extensions = self::decode_slug_map(wp_unslash($_POST['ext_fields']));

			if (null === $widgets || null === $extensions) {
				wp_send_json_error(__('Invalid data.', 'animation-addons-for-elementor'));
			}

			// BEFORE the first write. This is the whole return path.
			$this->capture_atomic_undo($signal);

			$enabled = [
				'widgets'    => $this->write_widget_option($widgets)['total'],
				'extensions' => $this->write_extension_option($extensions)['total'],
			];
		}

		update_option(
			self::OPTIN_OPTION_NAME,
			[
				'state'  => $state,
				'signal' => $signal,
			]
		);

		wp_send_json_success(
			[
				'state'   => $state,
				'signal'  => $signal,
				'enabled' => $enabled,
			]
		);
	}

	/**
	 * AJAX — go back.
	 *
	 * Three decisions, and the difference between the first two is the whole
	 * reason there are three:
	 *
	 *  - `undo` — replay the snapshot. EXACT: the site ends up byte-for-byte
	 *    where it was, including option rows that had never existed. Needs a
	 *    snapshot, so it is only offered while one is on file.
	 *  - `off`  — switch both atomic options to an empty array. Available
	 *    ALWAYS, and it is what the permanent "Back to V3" escape hatch uses.
	 *    Not an exact restore and does not pretend to be: nothing is deleted,
	 *    because an absent row and an empty one mean different things here (see
	 *    UNDO_OPTION_NAME) and guessing which one this site had before would be
	 *    inventing history. An empty array is the same definite "off" the
	 *    master Enable All switch writes, and it can never re-arm
	 *    migrate_newly_offered_extensions() behind the user's back.
	 *
	 *    It writes NOTHING when nothing is switched on. That is the "Choose
	 *    manually" shape — the offer accepted, the tab revealed, no widget ever
	 *    enabled — and there the options are usually ABSENT. Writing empty rows
	 *    over no rows would not be reversing anything; it would be making the
	 *    one change this request exists to take back, and it would end the
	 *    "fresh install, the wizard decides" state permanently.
	 *  - `keep` — end the offer, change nothing.
	 *
	 * ALL THREE RECORD A DISMISSAL rather than clearing the answer. Going back
	 * to "undecided" would put the original notice on screen again at the next
	 * page load, so going back would look like it had not worked — and
	 * re-offering something somebody just took back is how a helpful prompt
	 * becomes a nag. Where a snapshot exists the dismissal is recorded at the
	 * SNAPSHOT's signal rather than today's, so the ranked rule still holds: if
	 * this site later grows real V4 pages, that stronger signal is still allowed
	 * to speak once.
	 */
	public function ajax_atomic_optin_undo(): void
	{
		Nonce::check_ajax( Nonce::ADMIN, 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(__('Permission denied.', 'animation-addons-for-elementor'));
		}

		$decision = isset($_POST['decision']) ? sanitize_key(wp_unslash($_POST['decision'])) : '';

		if (! in_array($decision, ['undo', 'keep', 'off'], true)) {
			wp_send_json_error(__('Invalid decision.', 'animation-addons-for-elementor'));
		}

		$undo         = get_option(self::UNDO_OPTION_NAME);
		$has_snapshot = is_array($undo) && ! empty($undo['time']);

		if ('keep' === $decision) {
			$this->forget_atomic_undo();

			wp_send_json_success(['decision' => 'keep']);
		}

		// Only `undo` needs a snapshot. `off` deliberately does not — it is the
		// escape hatch for a site whose snapshot has expired, been ended by a
		// hand save, or never existed because the accept predates this feature.
		if ('undo' === $decision && ! $has_snapshot) {
			wp_send_json_error(esc_html__('There is nothing left to undo.', 'animation-addons-for-elementor'));
		}

		if ('undo' === $decision) {
			$this->restore_atomic_undo($undo);
		} elseif ($this->has_active_atomic()) {
			$this->write_widget_option([]);
			$this->write_extension_option([]);
		}

		// With nothing active there is deliberately no write above: clearing the
		// stored answer below is the entire reversal, and it is enough — the V4
		// tab is offered by ATOMIC_OPTED_IN, so a dismissal takes it away again.

		update_option(
			self::OPTIN_OPTION_NAME,
			[
				'state'  => 'dismissed',
				'signal' => $has_snapshot && isset($undo['signal'])
					? (string) $undo['signal']
					: self::atomic_signal_strength(),
			]
		);

		$this->forget_atomic_undo();

		wp_send_json_success(['decision' => $decision]);
	}

	/**
	 * Enqueue global atomic editor scripts into the top-level window.
	 */
	public function enqueue_atomic_editor_scripts(): void
	{
		// $this->guard_elementor_core_atomic_types();

		$suffix = $this->is_dev_environment() ? '' : '.min';
		$path = 'assets/atomic/js/atomic-editor' . $suffix . '.js';
		$file_path = AAEADDON_PATH . $path;
		// Version the URL from the built file itself. Unlike a timestamp-only
		// version, this also changes for multiple builds written in one second.
		$version = AAEADDON_VERSION;
		if (is_readable($file_path)) {
			$content_hash = md5_file($file_path);
			$version = false !== $content_hash
				? AAEADDON_VERSION . '-' . substr($content_hash, 0, 12)
				: (string) filemtime($file_path);
		}
		

		wp_enqueue_script(
			'aae-atomic-editor',
			AAEADDON_URL . $path,
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

		// Loop Grid: ajax config for the editor "full grid live" preview module,
		// plus the per-post-type taxonomy map the panel's notice card reads
		// (NoticeControl.jsx, source 'loop-grid-taxonomies').
		wp_localize_script(
			'aae-atomic-editor',
			'AAE_LOOP_GRID',
			[
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => Nonce::create( Nonce::LOOP_GRID ),
				'notices' => [
					'taxonomies' => self::loop_grid_taxonomy_notices(),
				],
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
