<?php
namespace WCF_ADDONS\CodeSnippet;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

/**
 * CodeSnippetCompatibility Class
 *
 * Handles frontend execution of code snippets with conditional loading
 *
 * @package WCF_ADDONS\CodeSnippet
 */
class CodeSnippetCompatibility {
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_body_open', array( $this, 'code_snippet_body_start_fallback' ), 999 );
		add_action( 'init', array( $this, 'code_snippet_woocommerce_hooks' ) );
		add_action( 'init', array( $this, 'code_snippet_buddypress_hooks' ) );
	}

	/**
	 * Code Snippet Frontend Integration Hooks
	 *
	 * Add support for themes that don't have wp_body_open hook
	 *
	 * Additional hooks for better theme compatibility and snippet execution
	 */
	public function code_snippet_body_start_fallback() {
		// Only run if wp_body_open hook doesn't exist or hasn't been called.
		if ( ! did_action( 'wp_body_open' ) ) {
			// Try to find a suitable place to inject body start snippets.
			if ( class_exists( '\WCF_ADDONS\CodeSnippet\CodeSnippetFrontend' ) ) {
				$frontend = \WCF_ADDONS\CodeSnippet\CodeSnippetFrontend::instance();
				if ( method_exists( $frontend, 'execute_body_start_snippets' ) ) {
					$frontend->execute_body_start_snippets();
				}
			}
		}
	}


	/**
	 * Adds custom WooCommerce hooks for executing code snippets before and after the main content.
	 *
	 * @return void
	 */
	public function code_snippet_woocommerce_hooks() {
		// FIXED: Better WooCommerce detection to prevent issues when deactivated.
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			// WooCommerce specific hooks.
			add_action(
				'woocommerce_before_main_content',
				function () {
					if ( class_exists( '\WCF_ADDONS\CodeSnippet\CodeSnippetFrontend' ) ) {
						$frontend = \WCF_ADDONS\CodeSnippet\CodeSnippetFrontend::instance();
						if ( method_exists( $frontend, 'execute_content_before_snippets' ) ) {
							$frontend->execute_content_before_snippets();
						}
					}
				},
				5
			);

			add_action(
				'woocommerce_after_main_content',
				function () {
					if ( class_exists( '\WCF_ADDONS\CodeSnippet\CodeSnippetFrontend' ) ) {
						$frontend = \WCF_ADDONS\CodeSnippet\CodeSnippetFrontend::instance();
						if ( method_exists( $frontend, 'execute_content_after_snippets' ) ) {
							$frontend->execute_content_after_snippets();
						}
					}
				},
				15
			);
		}
	}

	/**
	 * Registers BuddyPress hooks to integrate custom snippet functionality
	 * before and after BuddyPress content.
	 *
	 * Hooks into 'bp_before_content' and 'bp_after_content' to execute custom
	 * methods from the CodeSnippetFrontend class if available.
	 *
	 * @return void
	 */
	public function code_snippet_buddypress_hooks() {
		if ( class_exists( 'BuddyPress' ) ) {
			add_action(
				'bp_before_content',
				function () {
					if ( class_exists( '\WCF_ADDONS\CodeSnippet\CodeSnippetFrontend' ) ) {
						$frontend = \WCF_ADDONS\CodeSnippet\CodeSnippetFrontend::instance();
						if ( method_exists( $frontend, 'execute_content_before_snippets' ) ) {
							$frontend->execute_content_before_snippets();
						}
					}
				},
				5
			);

			add_action(
				'bp_after_content',
				function () {
					if ( class_exists( '\WCF_ADDONS\CodeSnippet\CodeSnippetFrontend' ) ) {
						$frontend = \WCF_ADDONS\CodeSnippet\CodeSnippetFrontend::instance();
						if ( method_exists( $frontend, 'execute_content_after_snippets' ) ) {
							$frontend->execute_content_after_snippets();
						}
					}
				},
				15
			);
		}
	}
}
