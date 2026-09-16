<?php
namespace Wealcoder\AnimationAddons\Atomic\StyleManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Manager {

	/**
	 * The one cache key this sheet lives under: `[ 'aae_utility_styles', <context> ]`.
	 *
	 * NOT keyed by post id. The utility set is a constant — the same nineteen
	 * classes whatever document is rendering — yet it used to be registered once
	 * PER rendered document, and Elementor's manager writes and links one file per
	 * key per breakpoint. A page with a builder header and footer therefore
	 * shipped NINE utility stylesheets (3 documents × 3 breakpoints), every file
	 * byte-identical to its sibling: 9 requests, ~11 KB, for 841 bytes of unique
	 * CSS. Measured on the store demo, 2026-09-13. One key per context is the
	 * shape Elementor's own base styles use (`Atomic_Widget_Base_Styles`), and it
	 * gives three files — `aae_utility_styles-frontend-{desktop,tablet,mobile}.css`
	 * — shared by every page on the site.
	 */
	const STYLES_KEY = 'aae_utility_styles';

	public function register(): void {
		add_action( 'elementor/atomic-widgets/styles/register', [ $this, 'register_utility_styles' ], 50, 2 );

		// The only invalidation that exists: the sheet's content depends on
		// nothing a save or a delete can change, so a per-post clear on
		// `after_save` / `deleted_post` (which this used to do) only ever threw
		// away a correct file and had it regenerated on the next request.
		add_action( 'elementor/core/files/clear_cache', function() {
			$this->invalidate_cache();
		} );
	}

	/**
	 * Drop every generated utility stylesheet (both contexts, every breakpoint).
	 */
	public function invalidate_cache(): void {
		do_action( 'elementor/atomic-widgets/styles/clear', [ self::STYLES_KEY ] );
	}

	/**
	 * Does any of the rendered documents actually contain atomic content?
	 *
	 * The utility classes below (aae-flex, aae-a-svg, …) only ever apply to
	 * atomic content — AAE atomic widgets emit them from their own twig, and a
	 * user can add one to any atomic element via the `classes` prop. A document
	 * with no atomic element can't use a single one. But Elementor's styles
	 * pipeline runs `styles/register` for EVERY rendered document (its `$post_ids`
	 * come from `elementor/post/render`), so this stylesheet used to be generated
	 * and linked on every Elementor page — a pure-v3 page with zero atomic widgets
	 * included. `$post_ids` covers the main content AND any builder header/footer/
	 * popup rendered on the request, so scanning their saved data is the honest,
	 * render-order-independent signal (atomic widgets don't fire the legacy
	 * `elementor/frontend/before_render`, so a render-time flag never trips).
	 *
	 * Atomic widgets/containers are the only types Elementor saves with an `e-`
	 * prefix (`e-heading`, `e-div-block`, `e-aae-a-*`); v1 (`heading`, `section`)
	 * and v3 (`wcf--*`) never are.
	 *
	 * @param array $post_ids
	 * @return bool
	 */
	private function any_post_has_atomic( array $post_ids ): bool {
		foreach ( array_unique( $post_ids ) as $post_id ) {
			$data = get_post_meta( $post_id, '_elementor_data', true );

			if ( is_string( $data ) && preg_match( '/"(?:elType|widgetType)":"e-/', $data ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register custom Elementor utility classes via the Atomic Styles Manager.
	 */
	public function register_utility_styles( $styles_manager, array $post_ids ): void {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$context = \Elementor\Plugin::$instance->preview->is_editor_or_preview() ? 'preview' : 'frontend';

		// Frontend: emit these utilities only when a rendered document actually
		// contains atomic content — nothing else can use them (see
		// any_post_has_atomic()). The editor always registers, so a live-added
		// atomic widget styles correctly regardless of render order.
		if ( 'frontend' === $context && ! $this->any_post_has_atomic( $post_ids ) ) {
			return;
		}

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

		$styles_manager->register( [ self::STYLES_KEY, $context ], $get_styles );
	}
}
