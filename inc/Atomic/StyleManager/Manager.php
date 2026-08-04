<?php
namespace WCF_ADDONS\Atomic\StyleManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Manager {

	public function register(): void {
		add_action( 'elementor/atomic-widgets/styles/register', [ $this, 'register_utility_styles' ], 50, 2 );

		// Invalidate cache when Elementor clears all caches
		add_action( 'elementor/core/files/clear_cache', function() {
			$this->invalidate_cache();
		} );

		// Invalidate cache when a specific post is saved
		add_action( 'elementor/document/after_save', function( $document ) {
			$this->invalidate_cache( [ $document->get_main_id() ] );
		} );

		// Invalidate cache when a post is deleted
		add_action( 'deleted_post', function( $post_id ) {
			$this->invalidate_cache( [ $post_id ] );
		} );
	}

	/**
	 * Invalidate the styles cache.
	 *
	 * @param array|null $post_ids
	 * @param string|null $context
	 */
	public function invalidate_cache( ?array $post_ids = null, ?string $context = null ): void {
		if ( empty( $post_ids ) ) {
			do_action( 'elementor/atomic-widgets/styles/clear', [ 'aae_utility_styles' ] );
			return;
		}

		foreach ( $post_ids as $post_id ) {
			if ( empty( $context ) ) {
				do_action( 'elementor/atomic-widgets/styles/clear', [ 'aae_utility_styles', $post_id ] );
			} else {
				do_action( 'elementor/atomic-widgets/styles/clear', [ 'aae_utility_styles', $post_id, $context ] );
			}
		}
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
			// AAE utility classes, emitted through Elementor's atomic styles pipeline.
			//
			// IMPORTANT — only keys present in Elementor's style-schema
			// (modules/atomic-widgets/styles/style-schema.php) emit; anything else is
			// silently dropped. Notably there is NO `border` or `list-style` shorthand
			// (use `border-width`/`border-style`/`border-color`; list-style isn't
			// supported at all). SIZE values must carry a UNIT — a bare '0' is dropped,
			// so write '0px'. (Both verified in-browser.)
			$utilities = [
				// Display / flex
				'aae-flex'          => [ 'display' => 'flex' ],
				'aae-a-inline-flex' => [ 'display' => 'inline-flex' ],
				'aae-a-block'       => [ 'display' => 'block' ],
				'aae-a-hidden'      => [ 'display' => 'none' ],
				'aae-items-center'  => [ 'align-items' => 'center' ],
				'aae-a-justify-center' => [ 'justify-content' => 'center' ],
				'aae-a-justify-between' => [ 'justify-content' => 'space-between' ],
				'aae-a-col'         => [ 'flex-direction' => 'column' ],
				'aae-a-wrap'        => [ 'flex-wrap' => 'wrap' ],

				// Spacing (units required)
				'aae-a-m0'          => [ 'margin' => '0px' ],
				'aae-a-p0'          => [ 'padding' => '0px' ],

				// Sizing
				'aae-a-w-full'      => [ 'width' => '100%' ],
				'aae-a-h-full'      => [ 'height' => '100%' ],
				'aae-a-svg'         => [ 'width' => '20px', 'height' => '20px' ],
				'aae-a-svg-25'      => [ 'width' => '25px', 'height' => '25px' ],
				'aae-a-svg-30'      => [ 'width' => '30px', 'height' => '30px' ],

				// Border reset (no `border` shorthand in the schema — zero the width)
				'aae-a-b0'          => [ 'border-width' => '0px' ],

				// Misc layout
				'aae-a-relative'    => [ 'position' => 'relative' ],
				'aae-a-overflow-hidden' => [ 'overflow' => 'hidden' ],
				'aae-a-text-center' => [ 'text-align' => 'center' ],
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
