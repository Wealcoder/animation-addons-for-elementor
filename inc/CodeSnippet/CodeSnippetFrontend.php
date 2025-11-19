<?php

namespace WCF_ADDONS\CodeSnippet;

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
	 * Active snippets cache
	 *
	 * @since 2.3.10
	 * @var array
	 */
	private $active_snippets = array();

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
		// Use 'wp' so conditional tags (is_singular, is_archive, etc.) are available.
		$this->run_php_code_snippets();
		add_action( 'wp_head', array( $this, 'execute_head_snippets' ), 1 );
		add_action( 'wp_footer', array( $this, 'execute_footer_snippets' ), 999 );
		add_action( 'wp_body_open', array( $this, 'execute_body_start_snippets' ), 1 );
		add_action( 'elementor/frontend/before_get_content', array( $this, 'execute_content_before_snippets' ) );
		add_action( 'elementor/frontend/after_get_content', array( $this, 'execute_content_after_snippets' ) );

		// Fallback hooks for themes that don't support wp_body_open.
		add_action( 'wp_body_open', array( $this, 'execute_body_start_snippets' ), 1 );
		add_action( 'wp_body_open', array( $this, 'execute_body_start_snippets' ), 1 );

		// Content hooks.
		add_action( 'loop_start', array( $this, 'execute_content_before_snippets' ) );
		add_action( 'loop_end', array( $this, 'execute_content_after_snippets' ) );
	}

	/**
	 * Run PHP code snippets.
	 *
	 * @return void
	 */
	public function run_php_code_snippets() {
		$snippets = $this->get_active_snippets( 'php' );

		foreach ( $snippets as $snippet ) {
			$snippet_data = $this->aae_get_code_snippet_settings( $snippet->ID );
			if ( $this->check_visibility_conditions( $snippet_data ) ) {
				$this->execute_snippet( $snippet_data );
			}
		}
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
		if ( ! empty( $this->active_snippets ) ) {
			return $this->active_snippets;
		}

		$meta_query = array(
			array(
				'key'     => 'is_active',
				'value'   => 'yes',
				'compare' => '=',
			),
		);

		if ( 'php' === $code_type ) {
			$meta_query[] =
				array(
					'key'     => 'code_type',
					'value'   => 'php',
					'compare' => '=',
				);
		}

		$args = array(
			'post_type'      => 'wcf-code-snippet',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_key'       => 'priority', // phpcs:ignore
			'order'          => 'DESC',
		);

		$snippets        = get_posts( $args );
		$active_snippets = array();

		if ( 'php' === $code_type ) {
			return $snippets;
		}

		foreach ( $snippets as $snippet ) {
			$snippet_data = $this->aae_get_code_snippet_settings( $snippet->ID );
			if ( $this->should_load_snippet( $snippet_data ) ) {
				$active_snippets[] = $snippet_data;
			}
		}

		$this->active_snippets = $active_snippets;

		return $active_snippets;
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
		return apply_filters( 'wcf_code_snippet_should_load', $should_load, $snippet_data );
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
		do_action( 'wcf_code_snippet_before_execute', $snippet );

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
				$this->execute_php_snippet( $code_content );
				break;

			default:
				// Default to HTML.
				$this->execute_html_snippet( $code_content );
				break;
		}

		// Fire action after snippet execution.
		do_action( 'wcf_code_snippet_after_execute', $snippet );
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

	/**
	 * Execute PHP snippet
	 *
	 * @param string $content PHP content.
	 *
	 * @since 2.3.10
	 * @return void
	 */
	private function execute_php_snippet( $content ) {
		$content = preg_replace( '/^\s*<\?(php|PHP)?/i', '', $content );
		$content = preg_replace( '/\?>\s*$/', '', $content );
		if ( ! empty( $content ) ) {
			ob_start();

			try {
				$wrapped = 'return function() { ' . $content . ' };';
				$func    = eval( $wrapped ); // phpcs:ignore WordPress.Security.Eval.Discouraged

				if ( is_callable( $func ) ) {
					$func();
				}
			} catch ( \Throwable $e ) {
			}

			$output = ob_get_clean();

			echo wp_kses_post( $output );
		}
	}
}

CodeSnippetFrontend::instance();
