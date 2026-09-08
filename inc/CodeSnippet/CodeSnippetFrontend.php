<?php
/**
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\CodeSnippet;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

/**
 * CodeSnippetFrontend Class
 *
 * Handles frontend execution of code snippets with conditional loading
 *
 * @package WCF_ADDONS\CodeSnippet
 */
class CodeSnippetFrontend {
	use CodeSnippetSettingsTrait;

	/**
	 * Instance of the class
	 *
	 * @since 2.3.10
	 * @var CodeSnippetFrontend
	 */
	private static $_instance = null;

	/**
	 * Loadable snippet_data for the location path, computed once per request.
	 * null = not computed yet, so an empty result still memoises (an
	 * array() default would re-run the whole walk on every location hook).
	 *
	 * @since 2.3.10
	 * @var array|null
	 */
	private $active_snippets = null;

	/**
	 * All active snippet POSTS, fetched once. Both the PHP path and the
	 * location path filter from this in memory instead of each running its own
	 * `posts_per_page => -1` query — the query was firing at least twice per
	 * front-end request (the PHP path returned before the memo was set).
	 *
	 * @var \WP_Post[]|null
	 */
	private $all_active_posts = null;

	/**
	 * Snippet post ids whose PHP has already been considered this request,
	 * so the two lanes below can never execute the same snippet twice.
	 * Marked when a snippet is EVALUATED, not only when it runs, because a
	 * context-free visibility answer cannot change between the two lanes.
	 *
	 * @var array<int,bool>
	 */
	private $php_considered = array();

	/**
	 * Visibility values that can be answered before the main query exists.
	 * Everything else asks a conditional query tag and is only valid on 'wp'.
	 *
	 * @var string[]
	 */
	const CONTEXT_FREE_VISIBILITY = array( 'global', 'admin', 'frontend' );

	/**
	 * Constructor
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Get a singleton instance
	 *
	 * @since 2.3.10
	 * @return CodeSnippetFrontend
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Initialize hooks for frontend execution
	 *
	 * @return void
	 */
	private function init_hooks() {
		// PHP snippets are DEFERRED, never run at include time. This file is
		// included from the free plugin's own plugins_loaded:10 callback, and
		// Pro registers wcf_code_snippet_execute_php from ITS plugins_loaded:11
		// callback -- so the has_action() guard in run_php_code_snippets() was
		// being asked one priority too early and always answered "no Pro".
		// Measured with a probe: false at 9/10/11, TRUE at 12.
		//
		// Priority 20 keeps snippets as early as they have always been (before
		// after_setup_theme and init, so a snippet can still hook either) while
		// being after Pro. The 'wp' pass is for snippets whose visibility asks a
		// conditional query tag, which is only answerable once the main query
		// has run; run_php_code_snippets() decides which lane each snippet
		// belongs to and never repeats one.
		if ( did_action( 'plugins_loaded' ) && ! doing_action( 'plugins_loaded' ) ) {
			$this->run_php_code_snippets();
		} else {
			add_action( 'plugins_loaded', array( $this, 'run_php_code_snippets' ), 20 );
		}
		add_action( 'wp', array( $this, 'run_php_code_snippets' ), 1 );
		add_action( 'wp_head', array( $this, 'execute_head_snippets' ), 1 );
		add_action( 'wp_footer', array( $this, 'execute_footer_snippets' ), 999 );
		// Registered ONCE. It used to be added to wp_body_open three times (a
		// stray "fallback" that is not one — re-registering the same callback on
		// the same hook cannot help a theme that never fires the hook, it only
		// makes themes that DO fire it echo every body-start snippet 3×).
		add_action( 'wp_body_open', array( $this, 'execute_body_start_snippets' ), 1 );
		add_action( 'elementor/frontend/before_get_content', array( $this, 'execute_content_before_snippets' ) );
		add_action( 'elementor/frontend/after_get_content', array( $this, 'execute_content_after_snippets' ) );

		// Content hooks.
		add_action( 'loop_start', array( $this, 'execute_content_before_snippets' ) );
		add_action( 'loop_end', array( $this, 'execute_content_after_snippets' ) );
	}

	/**
	 * Run PHP code snippets.
	 *
	 * Note: PHP code execution requires Animation Addons Pro. The free plugin
	 * only ever fires wcf_code_snippet_execute_php; it evaluates nothing.
	 *
	 * Called twice per request (plugins_loaded:20 and wp:1). Each snippet is
	 * considered by exactly one of them -- see $php_considered.
	 *
	 * @return void
	 */
	public function run_php_code_snippets() {
		// Guard: Do not process PHP snippets unless Pro handler is registered and file editing is allowed.
		if ( ! has_action( 'wcf_code_snippet_execute_php' ) ) {
			return;
		}

		if ( ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) || ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) ) {
			return;
		}

		// Conditional query tags are only meaningful once 'wp' has run.
		$query_ready = did_action( 'wp' ) > 0;

		$snippets = $this->get_active_snippets( 'php' );

		foreach ( $snippets as $snippet ) {
			if ( isset( $this->php_considered[ $snippet->ID ] ) ) {
				continue;
			}

			$snippet_data = $this->aae_get_code_snippet_settings( $snippet->ID );

			// Leave a query-dependent snippet for the 'wp' pass rather than
			// asking is_singular() before there is a query to ask about: that
			// answers false AND emits _doing_it_wrong.
			if ( ! $query_ready && ! $this->visibility_is_context_free( $snippet_data ) ) {
				continue;
			}

			// Marked before executing, so a snippet that fatals cannot be
			// retried by the second pass.
			$this->php_considered[ $snippet->ID ] = true;

			if ( $this->check_visibility_conditions( $snippet_data ) ) {
				$this->execute_snippet( $snippet_data );
			}
		}
	}

	/**
	 * Can this snippet's visibility be decided without the main query?
	 *
	 * @param array $snippet_data Snippet configuration data.
	 *
	 * @return bool
	 */
	private function visibility_is_context_free( $snippet_data ) {
		$page = isset( $snippet_data['visibility_page'] ) ? $snippet_data['visibility_page'] : '';
		$list = isset( $snippet_data['visibility_page_list'] ) ? $snippet_data['visibility_page_list'] : array();

		// A page list is compared against get_the_ID(), so it needs the query.
		if ( ! empty( $list ) && is_array( $list ) ) {
			return false;
		}

		return in_array( $page, self::CONTEXT_FREE_VISIBILITY, true );
	}

	/**
	 * Get all active code snippets
	 *
	 * @param string $code_type Code type.
	 *
	 * @since 2.3.10
	 * @return array
	 */
	private function get_active_snippets( $code_type = null ) {
		// PHP path: the active posts whose code_type is php, filtered in memory
		// from the shared fetch. Returns post objects, as run_php expects.
		if ( 'php' === $code_type ) {
			$php = array();
			foreach ( $this->get_all_active_posts() as $snippet ) {
				$data = $this->aae_get_code_snippet_settings( $snippet->ID );
				if ( isset( $data['code_type'] ) && 'php' === $data['code_type'] ) {
					$php[] = $snippet;
				}
			}
			return $php;
		}

		// Location path: loadable snippet_data, memoised (null = not computed).
		if ( null !== $this->active_snippets ) {
			return $this->active_snippets;
		}

		$active_snippets = array();
		foreach ( $this->get_all_active_posts() as $snippet ) {
			$snippet_data = $this->aae_get_code_snippet_settings( $snippet->ID );
			if ( $this->should_load_snippet( $snippet_data ) ) {
				$active_snippets[] = $snippet_data;
			}
		}

		$this->active_snippets = $active_snippets;

		return $active_snippets;
	}

	/**
	 * Every published, active snippet post — one query per request, memoised.
	 *
	 * @since 2.3.10
	 * @return \WP_Post[]
	 */
	private function get_all_active_posts() {
		if ( null !== $this->all_active_posts ) {
			return $this->all_active_posts;
		}

		$this->all_active_posts = get_posts(
			array(
				'post_type'      => 'wcf-code-snippet',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'is_active',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
				'meta_key'       => 'priority', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'          => 'DESC',
			)
		);

		return $this->all_active_posts;
	}

	/**
	 * Check if snippet should be loaded based on conditions
	 *
	 * @param array $snippet_data Snippet configuration data.
	 *
	 * @since 2.3.10
	 * @return bool
	 */
	private function should_load_snippet( $snippet_data ) {

		// Check if snippet is active.
		if ( empty( $snippet_data['is_active'] ) || 'yes' !== $snippet_data['is_active'] ) {
			return false;
		}

		// Check if snippet has content.
		if ( empty( $snippet_data['code_content'] ) ) {
			return false;
		}

		// Check visibility conditions.
		$should_load = $this->check_visibility_conditions( $snippet_data );

		// Allow developers to filter the result.
		return apply_filters( 'wcf_code_snippet_should_load', $should_load, $snippet_data ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility with existing wcf hooks.
	}

	/**
	 * Check visibility conditions for snippet
	 *
	 * @param array $snippet_data Snippet configuration data.
	 *
	 * @since 2.3.10
	 * @return bool
	 */
	private function check_visibility_conditions( $snippet_data ) {
		$code_type            = isset( $snippet_data['code_type'] ) ? $snippet_data['code_type'] : '';
		$load_location        = isset( $snippet_data['load_location'] ) ? $snippet_data['load_location'] : '';
		$visibility_page      = isset( $snippet_data['visibility_page'] ) ? $snippet_data['visibility_page'] : '';
		$visibility_page_list = isset( $snippet_data['visibility_page_list'] ) ? $snippet_data['visibility_page_list'] : array();

		if ( 'php' !== $code_type && empty( $load_location ) ) {
			return false;
		}

		// If no specific visibility is set, load everywhere.
		if ( empty( $visibility_page ) || ( ! empty( $visibility_page ) && empty( $visibility_page_list ) ) ) {
			return false;
		}

		// Check a specific page list first.
		if ( ! empty( $visibility_page_list ) && is_array( $visibility_page_list ) ) {
			$current_post_id = get_the_ID();
			if ( in_array( $current_post_id, $visibility_page_list, false ) ) {
				return true;
			}
		}

		// Check general visibility conditions.
		if ( ! empty( $visibility_page ) ) {
			return $this->check_page_visibility( $visibility_page );
		}

		return false;
	}

	/**
	 * Check page visibility based on condition
	 *
	 * @param string $visibility_condition Visibility condition.
	 *
	 * @since 2.3.10
	 * @return bool
	 */
	private function check_page_visibility( $visibility_condition ) {
		switch ( $visibility_condition ) {
			case 'global':
				return true;

			case 'singulars':
				if ( is_front_page() ) { return false; }
				return is_singular();

			case 'archives':
				return is_archive();

			case '404':
				return is_404();

			case 'search':
				return is_search();

			case 'blog':
				return is_home();

			case 'front':
				return is_front_page();

			case 'date':
				return is_date();

			case 'author':
				return is_author();

			case 'post-archive':
				return is_home() || is_archive();

			case 'post-singulars':
				return is_single();

			case 'allpage':
				return is_page();

			case 'singular':
				return is_singular();

			case 'singular_post':
				return is_single();

			case 'singular_page':
				return is_page();

			case 'singular_attachment':
				return is_attachment();

			case 'archive':
				return is_archive();

			case 'archive_post':
				return is_home() || is_archive();

			case 'not_found':
				return is_404();

			case 'front_page':
				return is_front_page();

			case 'home':
				return is_home();

			case 'privacy_policy':
				return is_privacy_policy();

			case 'category':
				return is_category();

			case 'tag':
				return is_tag();

			case 'tax':
				return is_tax();

			case 'post_type_archive':
				return is_post_type_archive();

			case 'admin':
				return is_admin();

			case 'frontend':
				return ! is_admin();

			default:
				// Check for custom post-types.

				if ( ! empty( $visibility_condition ) && str_contains( $visibility_condition, 'singulars' ) ) {
					$post_type = str_replace( '-singulars', '', $visibility_condition );
					return is_singular( $post_type );
				}

				if ( ! empty( $visibility_condition ) && str_contains( $visibility_condition, 'archive' ) ) {
					$post_type = str_replace( '-archive', '', $visibility_condition );
					return is_post_type_archive( $post_type );
				}

				return false;
		}
	}

	/**
	 * Execute snippets for a head section
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function execute_head_snippets() {
		$this->execute_snippets_by_location( 'head' );
	}

	/**
	 * Execute snippets for a footer section
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function execute_footer_snippets() {
		$this->execute_snippets_by_location( 'footer' );
	}

	/**
	 * Execute snippets for body start
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function execute_body_start_snippets() {
		$this->execute_snippets_by_location( 'body_start' );
	}

	/**
	 * Execute snippets before content
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function execute_content_before_snippets() {
		$this->execute_snippets_by_location( 'content_before' );
	}

	/**
	 * Execute snippets after content
	 *
	 * @since 2.3.10
	 * @return void
	 */
	public function execute_content_after_snippets() {
		$this->execute_snippets_by_location( 'content_after' );
	}

	/**
	 * Execute snippets by location
	 *
	 * @param string $location Location to execute snippets.
	 * @return void
	 */
	private function execute_snippets_by_location( $location ) {
		$snippets = $this->get_active_snippets();

		foreach ( $snippets as $snippet ) {
			if ( isset( $snippet['load_location'] ) && $snippet['load_location'] === $location ) {
				$this->execute_snippet( $snippet );
			}
		}
	}

	/**
	 * Execute a single snippet
	 *
	 * @param array $snippet Snippet data.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	private function execute_snippet( $snippet ) {
		$code_type    = isset( $snippet['code_type'] ) ? $snippet['code_type'] : '';
		$code_content = isset( $snippet['code_content'] ) ? $snippet['code_content'] : '';

		if ( empty( $code_content ) ) {
			return;
		}

		// Fire action before snippet execution.
		do_action( 'wcf_code_snippet_before_execute', $snippet ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility with existing wcf hooks.

		// Sanitize and prepare code content.
		$code_content = $this->prepare_code_content( $code_content, $code_type );

		// Execute based on a code type.
		switch ( $code_type ) {
			case 'html':
				$this->execute_html_snippet( $code_content );
				break;

			case 'css':
				$this->execute_css_snippet( $code_content );
				break;

			case 'javascript':
				$this->execute_javascript_snippet( $code_content );
				break;

			case 'php':
				// PHP snippet execution is handled by Animation Addons Pro.
				do_action( 'wcf_code_snippet_execute_php', $code_content, $snippet );
				break;

			default:
				// Default to HTML.
				$this->execute_html_snippet( $code_content );
				break;
		}

		// Fire action after snippet execution.
		do_action( 'wcf_code_snippet_after_execute', $snippet ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility with existing wcf hooks.
	}

	/**
	 * Prepare code content for execution
	 *
	 * @param string $code_content Raw code content.
	 * @param string $code_type Type of code.
	 *
	 * @since 2.3.10
	 * @return string
	 */
	private function prepare_code_content( $code_content, $code_type ) {
		// Remove PHP tags if present in non-PHP code.
		if ( 'php' !== $code_type ) {
			$code_content = preg_replace( '/<\?php\s*/', '', $code_content );
			$code_content = preg_replace( '/\?>/', '', $code_content );
		}

		// Trim whitespace.
		$code_content = trim( $code_content );

		return $code_content;
	}

	/**
	 * Execute HTML snippet
	 *
	 * @param string $content HTML content.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	private function execute_html_snippet( $content ) {
		if ( ! empty( $content ) ) {
			$allowed_tags          = wp_kses_allowed_html( 'post' );
			$allowed_tags['style'] = array();
			$allowed_tags['meta']  = array(
				'name'       => true,
				'content'    => true,
				'http-equiv' => true,
				'property'   => true,
				'itemprop'   => true,
				'charset'    => true,
				'scheme'     => true,
			);

			$allowed_tags['link'] = array(
				'rel'   => true,
				'href'  => true,
				'type'  => true,
				'media' => true,
				'title' => true,
			);
			echo wp_kses( $content, $allowed_tags );
		}
	}

	/**
	 * Execute CSS snippet
	 *
	 * @param string $content CSS content.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	private function execute_css_snippet( $content ) {
		if ( ! empty( $content ) ) {
			echo '<style type="text/css">' . "\n";
			echo wp_strip_all_tags( $content ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</style>' . "\n";
		}
	}

	/**
	 * Execute JavaScript snippet
	 *
	 * @param string $content JavaScript content.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	private function execute_javascript_snippet( $content ) {
		if ( ! empty( $content ) ) {
			echo '<script type="text/javascript">' . "\n";
			echo wp_strip_all_tags( $content ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</script>' . "\n";
		}
	}

}

CodeSnippetFrontend::instance();
