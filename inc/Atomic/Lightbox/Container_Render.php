<?php
namespace WCF_ADDONS\Atomic\Lightbox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
/**
 * Container-level Lightbox.
 *
 * When a container (e-flexbox / e-div-block / e-grid, or any type added via the
 * `aae/lightbox/container_types` filter) has the Lightbox enabled, we publish a
 * single options bag keyed by the container's interaction id into the 'lbc'
 * interactions map. We do NOT resolve child images server-side — the runtime
 * discovers every eligible child from the DOM at click time (via one delegated
 * document listener), which means dynamically-added content (Loop Grid, AJAX,
 * infinite scroll, nested containers) works with zero re-initialization.
 *
 * The container's data-interaction-id is emitted by Elementor's atomic base on
 * every container wrapper, so the runtime maps a click → nearest ancestor with
 * a matching 'lbc' entry → that container's options + its child gallery.
 *
 * This coexists with the per-element {@see Render} path: a standalone e-image
 * keeps its own 'lb' entry; a container publishes an 'lbc' entry. The runtime
 * supports triggers from either source.
 */
final class Container_Render {

	public function register(): void {
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		// The atomic editor re-renders an element over an admin-ajax / REST
		// request on every prop change (e.g. switching Content Mode). Doing our
		// work there adds server latency to that synchronous round-trip — which
		// is what stalls the panel — and it's wasted: the container's own
		// data-interaction-id already renders, and the runtime reads the config
		// map from the preview footer. Match every other AAE Render and skip
		// admin/editor render passes; the frontend + preview-footer paths still
		// publish. Cheapest checks first so unrelated elements bail immediately.
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		if ( ! in_array( $element->get_element_type(), Schema::lightbox_containers(), true ) ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];

		if ( ! Lightbox_Manager::is_enabled( $settings ) ) {
			return;
		}

		$iid = $this->interaction_id( $element );
		if ( '' === $iid ) {
			return;
		}

		// images  → scan the container for image nodes.
		// content → each direct child opens its image OR video.
		// full    → each direct child opens its whole markup (HTML slide).
		$mode = Lightbox_Manager::string( $settings, Schema::LB_CONTAINER_MODE, 'images' );
		if ( ! in_array( $mode, [ 'images', 'content', 'full' ], true ) ) {
			$mode = 'images';
		}
		$selector = Lightbox_Manager::string( $settings, Schema::LB_CHILD_SELECTOR, '' );
		$group    = Lightbox_Manager::string( $settings, Schema::LB_GROUP, '' );

		// Default selector differs per mode: images mode scans for image nodes;
		// per-child modes (content / full) leave it blank so the runtime walks
		// the container's direct children.
		$default_selector = ( 'images' === $mode ) ? Schema::DEFAULT_CHILD_SELECTOR : '';

		$options = [
			// Discovery.
			'mode'       => $mode,
			'selector'   => '' !== $selector ? $selector : $default_selector,
			// Grouping: explicit id, else auto (runtime derives from container id).
			'group'      => '' !== $group ? $group : ( 'aae-lbc-' . $iid ),
			'captionSrc' => Lightbox_Manager::string( $settings, Schema::LB_CAPTION_SRC, 'none' ),
			// Chrome / behaviour.
			'anim'       => Lightbox_Manager::string( $settings, Schema::LB_ANIM, 'zoom' ),
			'zoom'       => Lightbox_Manager::bool( $settings, Schema::LB_ZOOM, true ),
			'loop'       => Lightbox_Manager::bool( $settings, Schema::LB_LOOP, true ),
			'download'   => Lightbox_Manager::bool( $settings, Schema::LB_DOWNLOAD, false ),
			'counter'    => Lightbox_Manager::bool( $settings, Schema::LB_COUNTER, true ),
			// Style: per-breakpoint value maps the runtime resolves to CSS vars
			// on the shared overlay at open time. Empty when nothing was styled
			// (default CSS keeps applying).
			'style'      => $this->collect_style( $settings ),
		];

		/**
		 * Filter a container's Lightbox options before they're published.
		 *
		 * @param array  $options  The option bag.
		 * @param object $element  The container element.
		 */
		$options = apply_filters( 'aae/lightbox/container_options', $options, $element );

		Lightbox_Manager::register_container( $iid, $options );
	}

	/**
	 * Style prop → runtime key. The runtime maps each key to a CSS custom
	 * property (`--aae-lb-<key>`) on the overlay root at open time. Only
	 * sizing/spacing keys carry per-breakpoint variants; the rest resolve to
	 * their desktop value (cascaded). Keeping the map here makes the published
	 * `style` bag small and the JS side a dumb var-writer.
	 */
	private function style_map(): array {
		return [
			Schema::LB_OVERLAY_COLOR     => 'overlay-color',
			Schema::LB_OVERLAY_OPACITY   => 'overlay-opacity',
			Schema::LB_CONTENT_FULLWIDTH => 'content-fullwidth',
			Schema::LB_CONTENT_WIDTH     => 'content-width',
			Schema::LB_CONTENT_MAXWIDTH  => 'content-maxwidth',
			Schema::LB_CONTENT_PADDING   => 'content-padding',
			Schema::LB_CONTENT_RADIUS    => 'content-radius',
			Schema::LB_CONTENT_BG        => 'content-bg',
			Schema::LB_CONTENT_SHADOW    => 'content-shadow',
			Schema::LB_ARROW_SIZE        => 'arrow-size',
			Schema::LB_ARROW_BOX         => 'arrow-box',
			Schema::LB_ARROW_COLOR       => 'arrow-color',
			Schema::LB_ARROW_BG          => 'arrow-bg',
			Schema::LB_ARROW_RADIUS      => 'arrow-radius',
			Schema::LB_ARROW_BORDER_W    => 'arrow-border-w',
			Schema::LB_ARROW_BORDER_C    => 'arrow-border-c',
			Schema::LB_ARROW_COLOR_HOVER => 'arrow-color-hover',
			Schema::LB_ARROW_BG_HOVER    => 'arrow-bg-hover',
			Schema::LB_ARROW_OFFSET      => 'arrow-offset',
			Schema::LB_CLOSE_SIZE        => 'close-size',
			Schema::LB_CLOSE_BOX         => 'close-box',
			Schema::LB_CLOSE_COLOR       => 'close-color',
			Schema::LB_CLOSE_BG          => 'close-bg',
			Schema::LB_CLOSE_RADIUS      => 'close-radius',
			Schema::LB_CLOSE_BORDER_W    => 'close-border-w',
			Schema::LB_CLOSE_BORDER_C    => 'close-border-c',
			Schema::LB_CLOSE_COLOR_HOVER => 'close-color-hover',
			Schema::LB_CLOSE_BG_HOVER    => 'close-bg-hover',
		];
	}

	/**
	 * Build the published `style` bag: { <key>: { desktop, tablet, … } } for
	 * every styled prop, skipping props the user never touched (all-empty
	 * breakpoints) so the JS writes vars only for real overrides.
	 */
	private function collect_style( array $settings ): array {
		$out = [];
		foreach ( $this->style_map() as $prop => $key ) {
			$map = Lightbox_Manager::responsive( $settings, $prop );
			$clean = [];
			foreach ( $map as $bp => $val ) {
				// Keep booleans (fullwidth) and any non-empty scalar/array.
				if ( is_bool( $val ) ) {
					if ( $val ) {
						$clean[ $bp ] = true;
					}
					continue;
				}
				if ( null !== $val && '' !== $val && [] !== $val ) {
					$clean[ $bp ] = $val;
				}
			}
			if ( ! empty( $clean ) ) {
				$out[ $key ] = $clean;
			}
		}
		return $out;
	}

	/**
	 * Resolve the value Elementor renders as data-interaction-id:
	 * get_interaction_id() === origin_id ?? get_id(). Public on atomic widgets,
	 * protected on the element base — try it, then origin_id, then get_id().
	 */
	private function interaction_id( $element ): string {
		if ( method_exists( $element, 'get_interaction_id' ) ) {
			try {
				$iid = $element->get_interaction_id();
				if ( is_string( $iid ) && '' !== $iid ) {
					return $iid;
				}
			} catch ( \Throwable $e ) {
				// protected in this context — fall through.
			}
		}
		if ( isset( $element->origin_id ) && is_string( $element->origin_id ) && '' !== $element->origin_id ) {
			return $element->origin_id;
		}
		return method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
	}
}
