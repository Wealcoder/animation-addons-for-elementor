<?php
/**
 * Elementor's atomic handler bundles need a webpack runtime that a V4-only
 * page never loads. This gives it to them.
 *
 * Measured on Elementor 4.2.4 (and unchanged on its `main` as of 2026-07-29):
 *
 *  - `youtube-handler.min.js`, `tabs-handler.min.js`, `tabs-preview-handler.min.js`
 *    and `atomic-widgets-action-link-handler.min.js` are all ENTRIES of the
 *    `frontend` webpack build, which is compiled with
 *    `runtimeChunk: { name: 'webpack.runtime' }`. Each file is therefore a bare
 *    chunk — `(self.webpackChunkelementorFrontend = … || []).push([...])` — and
 *    its entry module only executes once the runtime in `webpack.runtime.js`
 *    has installed the real `push` handler (`elementor-webpack-runtime`).
 *  - Every one of them is registered with `elementor-v2-frontend-handlers` (and
 *    Alpine) as its only dependency. The runtime rides along on a classic page
 *    because every classic element declares `elementor-frontend` as a global
 *    script, and `elementor-frontend` → `elementor-frontend-modules` →
 *    `elementor-webpack-runtime`.
 *  - An ATOMIC element returns `[]` from `get_global_scripts()`. So on a page
 *    built only from V4 elements — which is what an AAE Popup template, a V4
 *    starter page, and every one of this plugin's demos is — the runtime never
 *    ships, the chunk sits in a plain array, `register()` is never called, and
 *    Elementor's YouTube widget renders an empty box with no error anywhere.
 *    Reported as "atomic video widget does not work inside the popup"; it does
 *    not work OUTSIDE the popup on the same page either. Adding a single classic
 *    container to the page "fixes" it, which is why it looks intermittent.
 *
 * The fix is one dependency: once Elementor has registered its handles, add
 * `elementor-webpack-runtime` to each affected handler's `deps`. WordPress then
 * prints the runtime whenever the handler is printed and never otherwise (5.8 KB,
 * on exactly the pages that need it). Order does not matter — webpack's runtime
 * replays chunks already pushed onto the array when it installs — but a
 * dependency is still the right shape: it holds through admin-ajax renders,
 * theme-builder templates and a popup enqueued at wp_footer, all of which an
 * `enqueue()` on one hook would miss.
 *
 * Harmless once Elementor fixes it upstream: a dep already present is not added
 * twice, and a handle that is not registered is skipped.
 *
 * @package Wealcoder\AnimationAddons
 */

namespace Wealcoder\AnimationAddons\Atomic\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Elementor_Chunk_Runtime {

	/** The webpack runtime chunk of Elementor's `frontend` build. */
	const RUNTIME = 'elementor-webpack-runtime';

	/**
	 * Handler bundles compiled into that build without a runtime of their own.
	 * Verified by the leading `self.webpackChunkelementorFrontend` in each file;
	 * `atomic-widgets-form-handler` is NOT one (it carries its own runtime).
	 */
	const CHUNKS = [
		'elementor-youtube-handler',
		'elementor-tabs-handler',
		'elementor-tabs-preview-handler',
		'elementor-v2-action-link-handlers',
	];

	public function register(): void {
		// Elementor registers every element's frontend handler inside
		// Frontend::register_scripts() (wp_enqueue_scripts:5) and fires this
		// action at the end of it, so the handles exist by now and nothing has
		// been printed yet.
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'add_runtime_dependency' ] );
	}

	public function add_runtime_dependency(): void {
		$scripts = wp_scripts();

		if ( ! isset( $scripts->registered[ self::RUNTIME ] ) ) {
			return;
		}

		foreach ( self::CHUNKS as $handle ) {
			if ( ! isset( $scripts->registered[ $handle ] ) ) {
				continue;
			}

			$deps = (array) $scripts->registered[ $handle ]->deps;

			if ( in_array( self::RUNTIME, $deps, true ) ) {
				continue;
			}

			$deps[] = self::RUNTIME;
			$scripts->registered[ $handle ]->deps = $deps;
		}
	}
}
