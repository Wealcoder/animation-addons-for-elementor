<?php
namespace WCF_ADDONS\Atomic\StyleManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Manager {

	public function register(): void {
		add_action( 'elementor/atomic-widgets/styles/register', [ $this, 'register_utility_styles' ], 50, 2 );
	}

	/**
	 * Register custom Elementor utility classes via the Atomic Styles Manager.
	 */
	public function register_utility_styles( $styles_manager, array $post_ids ): void {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$context = \Elementor\Plugin::$instance->preview->is_editor_or_preview() ? 'preview' : 'frontend';

		$get_styles = function() {
			$utilities = [
				'aae-flex'         => [ 'display' => 'flex' ],
				'aae-items-center' => [ 'align-items' => 'center' ],
			];

			// Map class suffix to Elementor breakpoint name
			$breakpoints = [
				''        => '',       // Base (All devices / Desktop)
				'-tablet' => 'tablet', // Tablet breakpoint
				'-mobile' => 'mobile', // Mobile breakpoint
			];

			$styles = [];

			foreach ( $utilities as $base_id => $props ) {
				foreach ( $breakpoints as $suffix => $breakpoint_name ) {
					$variant = [
						'props' => $props,
					];

					// Only add the breakpoint meta if it's not the base device
					if ( ! empty( $breakpoint_name ) ) {
						$variant['meta'] = [ 'breakpoint' => $breakpoint_name ];
					} else {
						$variant['meta'] = [];
					}

					$styles[] = [
						'id'       => $base_id . $suffix,
						'type'     => 'class',
						'variants' => [ $variant ],
					];
				}
			}

			return $styles;
		};

		foreach ( $post_ids as $post_id ) {
			$cache_key = [ 'aae_utility_styles', $post_id, $context ];
			$styles_manager->register( $cache_key, $get_styles );
		}
	}
}
